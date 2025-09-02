<?php
require_once '../includes/auth.php';
require_once '../config/db.php';

// Get today's stats
$restaurant_id = $_SESSION['user_id'];

// Today's orders
$stmt = $pdo->prepare("SELECT COUNT(*) FROM orders 
                      WHERE restaurant_id = ? 
                      AND DATE(created_at) = CURDATE()");
$stmt->execute([$restaurant_id]);
$today_orders = $stmt->fetchColumn();

// Today's revenue
$stmt = $pdo->prepare("SELECT SUM(total_price) FROM orders 
                      WHERE restaurant_id = ? 
                      AND DATE(created_at) = CURDATE() 
                      AND status = 'Paid'");
$stmt->execute([$restaurant_id]);
$today_revenue = $stmt->fetchColumn() ?? 0;

// Pending orders
$stmt = $pdo->prepare("SELECT COUNT(*) FROM orders 
                      WHERE restaurant_id = ? 
                      AND status IN ('Pending', 'Preparing')");
$stmt->execute([$restaurant_id]);
$pending_orders = $stmt->fetchColumn();

// Menu items count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM menu_items 
                      WHERE restaurant_id = ?");
$stmt->execute([$restaurant_id]);
$menu_items = $stmt->fetchColumn();

// Recent orders (last 5)
$stmt = $pdo->prepare("
    SELECT o.*, 
           GROUP_CONCAT(m.name SEPARATOR ', ') AS items
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    JOIN menu_items m ON oi.menu_item_id = m.id
    WHERE o.restaurant_id = ?
    GROUP BY o.id
    ORDER BY o.created_at DESC
    LIMIT 5
");
$stmt->execute([$restaurant_id]);
$recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Popular items (top 3)
$stmt = $pdo->prepare("
    SELECT m.name, COUNT(oi.id) AS order_count, m.image
    FROM order_items oi
    JOIN menu_items m ON oi.menu_item_id = m.id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.restaurant_id = ?
    GROUP BY m.id
    ORDER BY order_count DESC
    LIMIT 3
");
$stmt->execute([$restaurant_id]);
$popular_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Weekly revenue data (for chart)
$stmt = $pdo->prepare("
    SELECT 
        DAYNAME(created_at) AS day, 
        SUM(total_price) AS revenue
    FROM orders
    WHERE restaurant_id = ?
    AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    GROUP BY DAYOFWEEK(created_at), DAYNAME(created_at)
    ORDER BY DAYOFWEEK(created_at)
");
$stmt->execute([$restaurant_id]);
$weekly_revenue = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Prepare chart data
$chart_labels = [];
$chart_data = [];
$days_in_order = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

foreach ($days_in_order as $day) {
    $chart_labels[] = substr($day, 0, 3);
    $found = false;
    foreach ($weekly_revenue as $row) {
        if ($row['day'] == $day) {
            $chart_data[] = $row['revenue'];
            $found = true;
            break;
        }
    }
    if (!$found) {
        $chart_data[] = 0;
    }
}

// Get yesterday's revenue for comparison
$stmt = $pdo->prepare("SELECT COALESCE(SUM(total_price),0) FROM orders 
                      WHERE restaurant_id = ? 
                      AND DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)
                      AND status IN ('Paid')");
$stmt->execute([$restaurant_id]);
$yesterday_revenue = $stmt->fetchColumn() ?? 0;

// Get yesterday's orders for comparison
$stmt = $pdo->prepare("SELECT COUNT(*) FROM orders 
                      WHERE restaurant_id = ? 
                      AND DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)");
$stmt->execute([$restaurant_id]);
$yesterday_orders = $stmt->fetchColumn() ?? 0;

// Calculate orders % change
if ($yesterday_orders > 0) {
    $orders_change = (($today_orders - $yesterday_orders) / $yesterday_orders) * 100;
} else {
    $orders_change = $today_orders > 0 ? 100 : 0;
}

// Calculate revenue % change
if ($yesterday_revenue > 0) {
    $revenue_change = (($today_revenue - $yesterday_revenue) / $yesterday_revenue) * 100;
} else {
    $revenue_change = $today_revenue > 0 ? 100 : 0;
}

// Format changes nicely
$orders_change = round($orders_change, 2);
$revenue_change = round($revenue_change, 2);

// Get restaurant name
$stmt = $pdo->prepare("SELECT restaurant_name FROM users WHERE id = ?");
$stmt->execute([$restaurant_id]);
$restaurant_name = $stmt->fetchColumn() ?? 'RestaurantPro';
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RestaurantPro - Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary: #2a9d8f;
            --primary-light: #76c7ba;
            --primary-dark: #1d7874;
            --secondary: #264653;
            --accent: #e9c46a;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
            --light-gray: #e9ecef;
            --success: #52b788;
            --error: #e76f51;
            --warning: #f4a261;
            --info: #3a86ff;
            --shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            --shadow-lg: 0 10px 30px rgba(0, 0, 0, 0.12);
            --border-radius: 12px;
            --border-radius-sm: 8px;
            --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', system-ui, -apple-system, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            color: var(--dark);
            min-height: 100vh;
            line-height: 1.6;
        }

        /* Dashboard Layout */
        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 2rem;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            border-top: 4px solid;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .stat-card.revenue {
            border-top-color: var(--primary);
        }

        .stat-card.orders {
            border-top-color: var(--success);
        }

        .stat-card.pending {
            border-top-color: var(--warning);
        }

        .stat-card.menu {
            border-top-color: var(--info);
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .stat-title {
            font-size: 0.9rem;
            font-weight: 500;
            color: var(--gray);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            color: white;
        }

        .stat-icon.revenue {
            background: var(--primary);
        }

        .stat-icon.orders {
            background: var(--success);
        }

        .stat-icon.pending {
            background: var(--warning);
        }

        .stat-icon.menu {
            background: var(--info);
        }

        .stat-value {
            font-size: 2.25rem;
            font-weight: 700;
            color: var(--secondary);
            margin: 0.5rem 0;
        }

        .stat-change {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            font-size: 0.875rem;
            font-weight: 500;
            padding: 0.35rem 0.75rem;
            border-radius: 20px;
        }

        .stat-change.positive {
            background: rgba(82, 183, 136, 0.15);
            color: var(--success);
        }

        .stat-change.negative {
            background: rgba(231, 111, 81, 0.15);
            color: var(--error);
        }

        .stat-change.neutral {
            background: rgba(244, 162, 97, 0.15);
            color: var(--warning);
        }

        /* Dashboard Content Grid */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 1.5rem;
        }

        @media (max-width: 1200px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Dashboard Cards */
        .dashboard-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            transition: var(--transition);
            height: fit-content;
        }

        .dashboard-card:hover {
            box-shadow: var(--shadow-lg);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--light-gray);
        }

        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--secondary);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .card-title i {
            color: var(--primary);
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.6rem 1.25rem;
            border-radius: var(--border-radius-sm);
            font-weight: 500;
            text-decoration: none;
            transition: var(--transition);
            border: none;
            cursor: pointer;
            font-size: 0.875rem;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .btn-outline {
            background: transparent;
            color: var(--primary);
            border: 1px solid var(--primary);
        }

        .btn-outline:hover {
            background: rgba(42, 157, 143, 0.1);
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.8rem;
        }

        /* Chart Container */
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }

        /* Popular Items Styles */
        .popular-items {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .popular-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem;
            border-radius: var(--border-radius-sm);
            transition: var(--transition);
            border: 1px solid transparent;
            background: var(--light);
        }

        .popular-item:hover {
            border-color: var(--primary-light);
            background: rgba(42, 157, 143, 0.03);
            transform: translateY(-2px);
        }

        .item-img {
            width: 45px;
            height: 45px;
            border-radius: var(--border-radius-sm);
            overflow: hidden;
            flex-shrink: 0;
            background: var(--light-gray);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .item-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .item-img i {
            color: var(--gray);
            font-size: 1.1rem;
        }

        .item-info {
            flex: 1;
            min-width: 0;
        }

        .item-name {
            font-weight: 600;
            color: var(--secondary);
            margin-bottom: 0.2rem;
            font-size: 0.9rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .item-count {
            font-size: 0.8rem;
            color: var(--gray);
        }

        .item-badge {
            background: var(--primary);
            color: white;
            padding: 0.3rem 0.6rem;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 600;
            flex-shrink: 0;
        }

        /* For the dashboard card header */
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.25rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--light-gray);
        }

        .card-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--secondary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .card-title i {
            color: var(--primary);
            font-size: 1rem;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .popular-item {
                padding: 0.6rem;
            }
            
            .item-img {
                width: 40px;
                height: 40px;
            }
            
            .item-name {
                font-size: 0.85rem;
            }
            
            .item-count {
                font-size: 0.75rem;
            }
        }

        /* Data Table */
        .data-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .data-table thead th {
            background: var(--light);
            padding: 1rem;
            text-align: left;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--gray);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .data-table thead th:first-child {
            border-top-left-radius: var(--border-radius-sm);
        }

        .data-table thead th:last-child {
            border-top-right-radius: var(--border-radius-sm);
        }

        .data-table tbody td {
            padding: 1rem;
            border-bottom: 1px solid var(--light-gray);
            vertical-align: middle;
        }

        .data-table tbody tr:last-child td {
            border-bottom: none;
        }

        .data-table tbody tr:hover {
            background: rgba(42, 157, 143, 0.03);
        }

        /* Status Badges */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.5rem 0.875rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-badge.pending {
            background: rgba(244, 162, 97, 0.15);
            color: var(--warning);
        }

        .status-badge.preparing {
            background: rgba(58, 134, 255, 0.15);
            color: var(--info);
        }

        .status-badge.ready {
            background: rgba(82, 183, 136, 0.15);
            color: var(--success);
        }

        .status-badge.delivered {
            background: rgba(42, 157, 143, 0.15);
            color: var(--primary);
        }

        .order-items {
            max-width: 200px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            color: var(--gray);
            font-size: 0.875rem;
        }

        .order-time {
            color: var(--gray);
            font-size: 0.875rem;
        }

        .order-amount {
            font-weight: 600;
            color: var(--secondary);
        }

        /* Empty States */
        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 3rem 2rem;
            text-align: center;
            color: var(--gray);
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .empty-state p {
            margin: 0;
            max-width: 300px;
            line-height: 1.6;
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideUp {
            from { 
                opacity: 0;
                transform: translateY(20px);
            }
            to { 
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fade-in {
            animation: fadeIn 0.6s ease forwards;
        }

        .animate-slide-up {
            animation: slideUp 0.6s ease forwards;
        }

        .delay-1 { animation-delay: 0.1s; }
        .delay-2 { animation-delay: 0.2s; }
        .delay-3 { animation-delay: 0.3s; }
        .delay-4 { animation-delay: 0.4s; }
        .delay-5 { animation-delay: 0.5s; }

        /* Responsive */
        @media (max-width: 1024px) {
            .main-content {
                margin-left: 240px;
            }
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Include Sidebar -->
        <?php include '../includes/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Include Top Bar -->
            <?php include '../includes/topbar.php'; ?>
            
            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card revenue animate-slide-up delay-1">
                    <div class="stat-header">
                        <div class="stat-title">Today's Revenue</div>
                        <div class="stat-icon revenue">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                    </div>
                    <div class="stat-value">₹<?php echo number_format($today_revenue, 2); ?></div>
                    <div class="stat-change <?php echo $revenue_change >= 0 ? 'positive' : 'negative'; ?>">
                        <i class="fas fa-arrow-<?php echo $revenue_change >= 0 ? 'up' : 'down'; ?>"></i>
                        <?php echo abs(round($revenue_change)) ?>% from yesterday
                    </div>
                </div>
                
                <div class="stat-card orders animate-slide-up delay-2">
                    <div class="stat-header">
                        <div class="stat-title">Today's Orders</div>
                        <div class="stat-icon orders">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo $today_orders; ?></div>
                    <div class="stat-change <?php echo $orders_change >= 0 ? 'positive' : 'negative'; ?>">
                        <i class="fas fa-arrow-<?php echo $orders_change >= 0 ? 'up' : 'down'; ?>"></i>
                        <?php echo abs(round($orders_change)) ?>% from yesterday
                    </div>
                </div>
                
                <div class="stat-card pending animate-slide-up delay-3">
                    <div class="stat-header">
                        <div class="stat-title">Pending Orders</div>
                        <div class="stat-icon pending">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo $pending_orders; ?></div>
                    <a href="orders.php?filter=pending" class="stat-change neutral" style="text-decoration: none; margin-top: 0.5rem; display: inline-block;">
                        <i class="fas fa-eye"></i> View pending orders
                    </a>
                </div>
            </div>
            
            <!-- Dashboard Content Grid -->
            <div class="dashboard-grid">
                <!-- Revenue Chart -->
                <div class="dashboard-card animate-fade-in delay-3">
                    <div class="card-header">
                        <h2 class="card-title">
                            <i class="fas fa-chart-line"></i> Weekly Revenue
                        </h2>
                        <select class="btn-outline btn-sm">
                            <option>This Week</option>
                            <option>Last Week</option>
                            <option>Last 30 Days</option>
                        </select>
                    </div>
                    <div class="chart-container">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>
                
                <!-- Popular Items -->
                <div class="dashboard-card animate-fade-in delay-4">
                    <div class="card-header">
                        <h2 class="card-title">
                            <i class="fas fa-star"></i> Popular Items
                        </h2>
                        <a href="menu.php" class="btn-outline btn-sm">View All</a>
                    </div>
                    <?php if (!empty($popular_items)): ?>
                    <ul class="popular-items">
                        <?php foreach ($popular_items as $index => $item): ?>
                        <li class="popular-item animate-slide-up" style="animation-delay: <?php echo 0.1 + ($index * 0.1); ?>s">
                            <div class="item-img">
                                <?php if (!empty($item['image'])): ?>
                                    <img src="../assets/images/menu_items/<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                <?php else: ?>
                                    <i class="fas fa-utensils"></i>
                                <?php endif; ?>
                            </div>
                            <div class="item-info">
                                <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                <div class="item-count"><?php echo $item['order_count']; ?> orders</div>
                            </div>
                            <div class="item-badge">#<?php echo $index + 1; ?></div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-utensils"></i>
                        <p>No popular items to display. Orders will appear here.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Recent Orders -->
            <div class="dashboard-card animate-fade-in delay-5" style="margin-top: 1.5rem;">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-history"></i> Recent Orders
                    </h2>
                    <div>
                        <a href="orders.php?filter=pending" class="btn-outline btn-sm">
                            <i class="fas fa-clock"></i> Pending
                        </a>
                        <a href="orders.php" class="btn-primary btn-sm">
                            View All <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
                <?php if (!empty($recent_orders)): ?>
                <div style="overflow-x: auto;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Table</th>
                                <th>Items</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Time</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_orders as $index => $order): ?>
                            <tr class="animate-slide-up" style="animation-delay: <?php echo 0.1 + ($index * 0.05); ?>s">
                                <td>#<?php echo $order['id']; ?></td>
                                <td><?php echo htmlspecialchars($order['table_no']); ?></td>
                                <td class="order-items"><?php echo htmlspecialchars($order['items']); ?></td>
                                <td class="order-amount">₹<?php echo number_format($order['total_price'], 2); ?></td>
                                <td>
                                    <span class="status-badge <?php echo strtolower($order['status']); ?>">
                                        <i class="fas fa-<?php 
                                            echo strtolower($order['status']) == 'pending' ? 'clock' : 
                                                (strtolower($order['status']) == 'preparing' ? 'utensils' : 
                                                (strtolower($order['status']) == 'ready' ? 'bell' : 
                                                (strtolower($order['status']) == 'delivered' ? 'check-circle' : 
                                                (strtolower($order['status']) == 'paid' ? 'money-bill-wave' : 
                                                (strtolower($order['status']) == 'cancelled' ? 'times-circle' : 'info-circle')))));
                                            ?>">
                                        </i>
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </td>

                                <td class="order-time"><?php echo date('h:i A', strtotime($order['created_at'])); ?></td>
                                <td>
                                    <a href="order_details.php?id=<?php echo $order['id']; ?>" class="btn-outline btn-sm">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-clipboard-list"></i>
                    <p>No recent orders to display. New orders will appear here.</p>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        // Weekly Revenue Chart
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        const revenueChart = new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($chart_labels); ?>,
                datasets: [{
                    label: 'Revenue (₹)',
                    data: <?php echo json_encode($chart_data); ?>,
                    backgroundColor: 'rgba(42, 157, 143, 0.1)',
                    borderColor: 'rgba(42, 157, 143, 1)',
                    borderWidth: 3,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: 'rgba(42, 157, 143, 1)',
                    pointRadius: 5,
                    pointHoverRadius: 7,
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return '₹' + context.raw.toFixed(2);
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '₹' + value;
                            }
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

        // Animate elements when they come into view
        document.addEventListener('DOMContentLoaded', function() {
            const animatedElements = document.querySelectorAll('.animate-slide-up, .animate-fade-in');
            
            animatedElements.forEach(element => {
                element.style.opacity = '0';
            });
            
            setTimeout(() => {
                animatedElements.forEach(element => {
                    element.style.opacity = '1';
                    element.style.transform = 'translateY(0)';
                });
            }, 100);
        });
    </script>
</body>
</html>