<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

// Get restaurant ID
$restaurant_id = $_SESSION['user_id'];

// Get sales data for the last 7 days
$sales_data = $pdo->prepare("
    SELECT DATE(created_at) AS date, SUM(total_price) AS total
    FROM orders
    WHERE status = 'Delivered' AND restaurant_id = ?
    GROUP BY DATE(created_at)
    ORDER BY date DESC
    LIMIT 7
");
$sales_data->execute([$restaurant_id]);
$sales_data = $sales_data->fetchAll(PDO::FETCH_ASSOC);

$labels = [];
$data = [];

foreach ($sales_data as $row) {
    $labels[] = date('M j', strtotime($row['date']));
    $data[] = $row['total'];
}

// Get today's sales
$today_sales = $pdo->prepare("
    SELECT SUM(total_price) AS total
    FROM orders
    WHERE status = 'Delivered' AND restaurant_id = ? AND DATE(created_at) = CURDATE()
");
$today_sales->execute([$restaurant_id]);
$today_sales = $today_sales->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Get yesterday's sales
$yesterday_sales = $pdo->prepare("
    SELECT SUM(total_price) AS total
    FROM orders
    WHERE status = 'Delivered' AND restaurant_id = ? AND DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)
");
$yesterday_sales->execute([$restaurant_id]);
$yesterday_sales = $yesterday_sales->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Calculate percentage change
$sales_change = $yesterday_sales > 0 ? (($today_sales - $yesterday_sales) / $yesterday_sales) * 100 : 0;

// Get total orders count
$total_orders = $pdo->prepare("
    SELECT COUNT(*) AS count
    FROM orders
    WHERE restaurant_id = ? AND status = 'Delivered'
");
$total_orders->execute([$restaurant_id]);
$total_orders = $total_orders->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

// Get average order value
$avg_order = $pdo->prepare("
    SELECT AVG(total_price) AS avg_value
    FROM orders
    WHERE restaurant_id = ? AND status = 'Delivered'
");
$avg_order->execute([$restaurant_id]);
$avg_order = $avg_order->fetch(PDO::FETCH_ASSOC)['avg_value'] ?? 0;

// Get top selling items
$top_items = $pdo->prepare("
    SELECT m.name, COUNT(oi.id) AS order_count, SUM(oi.quantity) AS total_quantity
    FROM order_items oi
    JOIN menu_items m ON oi.menu_item_id = m.id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.restaurant_id = ? AND o.status = 'Delivered'
    GROUP BY m.id
    ORDER BY total_quantity DESC
    LIMIT 5
");
$top_items->execute([$restaurant_id]);
$top_items = $top_items->fetchAll(PDO::FETCH_ASSOC);

// Get restaurant name for sidebar
$stmt = $pdo->prepare("SELECT restaurant_name FROM users WHERE id = ?");
$stmt->execute([$restaurant_id]);
$restaurant_name = $stmt->fetchColumn() ?? 'RestaurantPro';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Reports - RestaurantPro</title>
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
            --warning: #f8961e;
            --danger: #e76f51;
            --info: #4895ef;
            --border-radius: 12px;
            --border-radius-sm: 8px;
            --shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            --shadow-lg: 0 10px 30px rgba(0, 0, 0, 0.12);
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

        /* Page Header */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding: 1rem 1.5rem;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
        }

        .page-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--secondary);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .page-title i {
            color: var(--primary);
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

        .stat-card.sales {
            border-top-color: var(--primary);
        }

        .stat-card.orders {
            border-top-color: var(--success);
        }

        .stat-card.avg {
            border-top-color: var(--info);
        }

        .stat-card.change {
            border-top-color: var(--warning);
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

        .stat-icon.sales {
            background: var(--primary);
        }

        .stat-icon.orders {
            background: var(--success);
        }

        .stat-icon.avg {
            background: var(--info);
        }

        .stat-icon.change {
            background: var(--warning);
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
            color: var(--danger);
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

        /* Chart Container */
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }

        /* Top Items Styles */
        .top-items {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .top-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem;
            border-radius: var(--border-radius-sm);
            transition: var(--transition);
            border: 1px solid transparent;
            background: var(--light);
        }

        .top-item:hover {
            border-color: var(--primary-light);
            background: rgba(42, 157, 143, 0.03);
            transform: translateY(-2px);
        }

        .item-rank {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.8rem;
            flex-shrink: 0;
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

        .item-stats {
            font-size: 0.8rem;
            color: var(--gray);
        }

        /* Filter Controls */
        .filter-controls {
            display: flex;
            gap: 1rem;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .filter-select {
            padding: 0.5rem 1rem;
            border: 1px solid var(--light-gray);
            border-radius: var(--border-radius-sm);
            font-size: 0.9rem;
            background: white;
            cursor: pointer;
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
                padding: 1.5rem;
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
            
            .page-header {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }
            
            .filter-controls {
                flex-wrap: wrap;
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
            <!-- Page Header -->
            <div class="page-header animate-fade-in">
                <h1 class="page-title">
                    <i class="fas fa-chart-bar"></i> Sales Analytics
                </h1>
                <div class="filter-controls">
                    <select class="filter-select" id="timeFilter">
                        <option value="7">Last 7 Days</option>
                        <option value="30">Last 30 Days</option>
                        <option value="90">Last 90 Days</option>
                    </select>
                    <button class="btn btn-outline btn-sm">
                        <i class="fas fa-download"></i> Export Report
                    </button>
                </div>
            </div>
            
            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card sales animate-slide-up delay-1">
                    <div class="stat-header">
                        <div class="stat-title">Today's Sales</div>
                        <div class="stat-icon sales">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                    </div>
                    <div class="stat-value">₹<?= number_format($today_sales, 2) ?></div>
                    <div class="stat-change <?= $sales_change >= 0 ? 'positive' : 'negative' ?>">
                        <i class="fas fa-arrow-<?= $sales_change >= 0 ? 'up' : 'down' ?>"></i>
                        <?= abs(round($sales_change)) ?>% from yesterday
                    </div>
                </div>
                
                <div class="stat-card orders animate-slide-up delay-2">
                    <div class="stat-header">
                        <div class="stat-title">Total Orders</div>
                        <div class="stat-icon orders">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?= number_format($total_orders) ?></div>
                    <div class="stat-change neutral">
                        <i class="fas fa-chart-line"></i> All time orders
                    </div>
                </div>
                
                <div class="stat-card avg animate-slide-up delay-3">
                    <div class="stat-header">
                        <div class="stat-title">Avg Order Value</div>
                        <div class="stat-icon avg">
                            <i class="fas fa-calculator"></i>
                        </div>
                    </div>
                    <div class="stat-value">₹<?= number_format($avg_order, 2) ?></div>
                    <div class="stat-change neutral">
                        <i class="fas fa-info-circle"></i> Per order average
                    </div>
                </div>
                
                <div class="stat-card change animate-slide-up delay-4">
                    <div class="stat-header">
                        <div class="stat-title">Sales Trend</div>
                        <div class="stat-icon change">
                            <i class="fas fa-trending-up"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?= $sales_change >= 0 ? '+' : '' ?><?= round($sales_change) ?>%</div>
                    <div class="stat-change <?= $sales_change >= 0 ? 'positive' : 'negative' ?>">
                        <i class="fas fa-<?= $sales_change >= 0 ? 'arrow-up' : 'arrow-down' ?>"></i>
                        Daily change
                    </div>
                </div>
            </div>
            
            <!-- Dashboard Content Grid -->
            <div class="dashboard-grid">
                <!-- Sales Chart -->
                <div class="dashboard-card animate-fade-in delay-3">
                    <div class="card-header">
                        <h2 class="card-title">
                            <i class="fas fa-chart-line"></i> Sales Trend
                        </h2>
                        <select class="btn-outline btn-sm" id="chartType">
                            <option value="line">Line Chart</option>
                            <option value="bar">Bar Chart</option>
                        </select>
                    </div>
                    <div class="chart-container">
                        <canvas id="salesChart"></canvas>
                    </div>
                </div>
                
                <!-- Top Selling Items -->
                <div class="dashboard-card animate-fade-in delay-4">
                    <div class="card-header">
                        <h2 class="card-title">
                            <i class="fas fa-star"></i> Top Selling Items
                        </h2>
                        <a href="menu.php" class="btn-outline btn-sm">View All</a>
                    </div>
                    <?php if (!empty($top_items)): ?>
                    <ul class="top-items">
                        <?php foreach ($top_items as $index => $item): ?>
                        <li class="top-item animate-slide-up" style="animation-delay: <?php echo 0.1 + ($index * 0.1); ?>s">
                            <div class="item-rank">#<?= $index + 1 ?></div>
                            <div class="item-info">
                                <div class="item-name"><?= htmlspecialchars($item['name']) ?></div>
                                <div class="item-stats"><?= $item['total_quantity'] ?> sold • <?= $item['order_count'] ?> orders</div>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-utensils"></i>
                        <p>No sales data available. Orders will appear here.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Additional Reports -->
            <div class="dashboard-card animate-fade-in delay-5" style="margin-top: 1.5rem;">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-file-alt"></i> Detailed Reports
                    </h2>
                    <div>
                        <button class="btn-outline btn-sm">
                            <i class="fas fa-calendar"></i> Daily
                        </button>
                        <button class="btn-primary btn-sm">
                            <i class="fas fa-download"></i> Export PDF
                        </button>
                    </div>
                </div>
                <div style="padding: 1rem;">
                    <p style="color: var(--gray); margin-bottom: 1rem;">
                        View detailed sales reports with item-wise breakdown, customer analytics, and revenue trends.
                    </p>
                    <div style="display: flex; gap: 1rem;">
                        <button class="btn btn-outline">
                            <i class="fas fa-calendar-week"></i> Weekly Report
                        </button>
                        <button class="btn btn-outline">
                            <i class="fas fa-calendar-month"></i> Monthly Report
                        </button>
                        <button class="btn btn-outline">
                            <i class="fas fa-calendar-alt"></i> Custom Range
                        </button>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Sales Chart
        const salesCtx = document.getElementById('salesChart').getContext('2d');
        const salesChart = new Chart(salesCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($labels); ?>,
                datasets: [{
                    label: 'Daily Sales (₹)',
                    data: <?php echo json_encode($data); ?>,
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

        // Chart type toggle
        document.getElementById('chartType').addEventListener('change', function() {
            salesChart.config.type = this.value;
            salesChart.update();
        });

        // Time filter
        document.getElementById('timeFilter').addEventListener('change', function() {
            // This would typically make an AJAX request to fetch new data
            alert('Filter functionality would load data for ' + this.value + ' days');
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