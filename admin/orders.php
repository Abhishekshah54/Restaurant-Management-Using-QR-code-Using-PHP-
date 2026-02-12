<?php
require_once '../includes/auth.php';
require_once '../config/db.php';

// Update order status via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    
    if (isset($_POST['update_status']) && isset($_POST['order_id']) && isset($_POST['status'])) {
        $status = trim($_POST['status']);
        $orderId = (int)$_POST['order_id'];
        $restaurantId = $_SESSION['user_id'] ?? 0;

        try {
            // First try simple update
            $stmt = $pdo->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ? AND restaurant_id = ?");
            $result = $stmt->execute([$status, $orderId, $restaurantId]);

            if ($result && $stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
                exit;
            } else {
                // If failed with Paid, try to add it to enum
                if (strcasecmp($status, 'Paid') === 0) {
                    try {
                        $colStmt = $pdo->prepare("SHOW COLUMNS FROM orders WHERE Field = 'status'");
                        $colStmt->execute();
                        $colInfo = $colStmt->fetch(PDO::FETCH_ASSOC);

                        if ($colInfo && strpos($colInfo['Type'], 'enum') !== false) {
                            $pdo->exec("ALTER TABLE orders MODIFY status ENUM('Pending','Preparing','Ready','Delivered','Paid','Cancelled') NOT NULL DEFAULT 'Pending'");
                            
                            $stmt = $pdo->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ? AND restaurant_id = ?");
                            $stmt->execute([$status, $orderId, $restaurantId]);
                            
                            echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
                            exit;
                        }
                    } catch (Exception $e) {
                        // Continue to error response
                    }
                }
                
                echo json_encode(['success' => false, 'message' => 'No rows updated']);
                exit;
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
            exit;
        }
    }
    
    // Delete order via AJAX
    if (isset($_POST['delete_order']) && isset($_POST['order_id'])) {
        $orderId = (int)$_POST['order_id'];
        $restaurantId = $_SESSION['user_id'] ?? 0;

        try {
            // First delete order items
            $stmt = $pdo->prepare("DELETE FROM order_items WHERE order_id = ?");
            $stmt->execute([$orderId]);
            
            // Then delete the order
            $stmt = $pdo->prepare("DELETE FROM orders WHERE id = ? AND restaurant_id = ?");
            $result = $stmt->execute([$orderId, $restaurantId]);
            
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Order deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete order']);
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
        exit;
    }
}

// Get all orders
$stmt = $pdo->prepare("
    SELECT o.*, 
           GROUP_CONCAT(m.name SEPARATOR ', ') AS items,
           SUM(oi.quantity) AS total_items
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    JOIN menu_items m ON oi.menu_item_id = m.id
    WHERE o.restaurant_id = ?
    GROUP BY o.id
    ORDER BY o.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get restaurant name for sidebar
$stmt = $pdo->prepare("SELECT restaurant_name FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$restaurant_name = $stmt->fetchColumn() ?? 'RestaurantPro';

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - Restaurant Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
            --border-radius: 10px;
            --shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', system-ui, -apple-system, sans-serif;
        }

        body {
            background-color: #f5f7fb;
            color: var(--dark);
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

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--light-gray);
        }

        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--secondary);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .page-title i {
            color: var(--primary);
        }

        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: var(--transition);
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .stat-pending .stat-icon { background: rgba(248, 150, 30, 0.1); color: var(--warning); }
        .stat-preparing .stat-icon { background: rgba(73, 149, 239, 0.1); color: var(--info); }
        .stat-ready .stat-icon { background: rgba(82, 199, 136, 0.1); color: var(--success); }
        .stat-delivered .stat-icon { background: rgba(42, 157, 143, 0.1); color: var(--primary); }

        .stat-info h3 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        .stat-info p {
            color: var(--gray);
            font-size: 0.9rem;
        }

        .card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            overflow: hidden;
            margin-bottom: 2rem;
        }

        .card-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--light-gray);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--secondary);
        }

        .filter-controls {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .filter-select {
            padding: 0.5rem 1rem;
            border: 1px solid var(--light-gray);
            border-radius: var(--border-radius);
            font-size: 0.9rem;
            background: white;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th {
            background-color: var(--light);
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: var(--secondary);
            border-bottom: 1px solid var(--light-gray);
        }

        .data-table td {
            padding: 1rem;
            border-bottom: 1px solid var(--light-gray);
        }

        .data-table tr:last-child td {
            border-bottom: none;
        }

        .data-table tr:hover {
            background-color: #fafafa;
        }

        .status-badge {
            display: inline-block;
            padding: 0.35rem 0.75rem;
            border-radius: 2rem;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .status-pending { background: rgba(248, 150, 30, 0.1); color: var(--warning); }
        .status-preparing { background: rgba(73, 149, 239, 0.1); color: var(--info); }
        .status-ready { background: rgba(82, 199, 136, 0.1); color: var(--success); }
        .status-delivered { background: rgba(42, 157, 143, 0.1); color: var(--primary); }

        .status-form {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        .status-select {
            padding: 0.4rem 0.8rem;
            border: 1px solid var(--light-gray);
            border-radius: var(--border-radius);
            font-size: 0.85rem;
            background: white;
            width: 100%;
            cursor: pointer;
            transition: var(--transition);
        }

        .status-select:focus {
            outline: none;
            border-color: var(--primary);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius);
            font-size: 0.85rem;
            font-weight: 500;
            text-decoration: none;
            transition: var(--transition);
            border: none;
            cursor: pointer;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
        }

        .btn-outline {
            background: transparent;
            color: var(--primary);
            border: 1px solid var(--primary);
        }

        .btn-outline:hover {
            background: var(--primary);
            color: white;
        }

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .btn-danger:hover {
            background: #d94c2e;
        }

        .btn-small {
            padding: 0.35rem 0.75rem;
            font-size: 0.8rem;
        }

        .time-badge {
            background: var(--light);
            color: var(--gray);
            padding: 0.35rem 0.75rem;
            border-radius: var(--border-radius);
            font-size: 0.8rem;
            font-weight: 500;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: var(--gray);
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: var(--light-gray);
        }

        .empty-state p {
            margin-bottom: 1.5rem;
        }

        /* Notification Styles */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 1rem 1.5rem;
            border-radius: var(--border-radius);
            background: white;
            box-shadow: var(--shadow);
            z-index: 1000;
            transform: translateX(100%);
            opacity: 0;
            transition: all 0.3s ease;
        }
        
        .notification.show {
            transform: translateX(0);
            opacity: 1;
        }
        
        .notification-success {
            border-left: 4px solid var(--success);
        }
        
        .notification-error {
            border-left: 4px solid var(--danger);
        }
        
        .notification-info {
            border-left: 4px solid var(--info);
        }
        
        .notification-content {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .notification-success i {
            color: var(--success);
        }
        
        .notification-error i {
            color: var(--danger);
        }
        
        .notification-info i {
            color: var(--info);
        }
        
        /* Highlight animation for stat cards */
        .highlight {
            animation: highlight 2s ease;
        }
        
        @keyframes highlight {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15); }
            100% { transform: scale(1); }
        }
        
        /* Spinner animation */
        .fa-spin {
            animation: fa-spin 1s infinite linear;
        }
        
        @keyframes fa-spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Action buttons */
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        /* Confirmation modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1001;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background-color: white;
            padding: 2rem;
            border-radius: var(--border-radius);
            width: 400px;
            max-width: 90%;
            box-shadow: var(--shadow);
        }

        .modal-header {
            margin-bottom: 1.5rem;
        }

        .modal-body {
            margin-bottom: 2rem;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
        }

        @media (max-width: 1024px) {
            .main-content {
                margin-left: 240px;
                padding: 1.5rem;
            }
            
            .stats-cards {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
            
            .stats-cards {
                grid-template-columns: 1fr;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .filter-controls {
                flex-wrap: wrap;
            }
            
            .data-table {
                display: block;
                overflow-x: auto;
            }
            
            .action-buttons {
                flex-direction: column;
            }
        }

        @media (max-width: 576px) {
            .main-content {
                padding: 1rem;
            }
            
            .card-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .stat-card {
                flex-direction: column;
                text-align: center;
            }
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
                <h1 class="page-title"><i class="fas fa-clipboard-list"></i> Order Management</h1>
                <div class="filter-controls">
                    <select class="filter-select" id="statusFilter">
                        <option value="all">All Orders</option>
                        <option value="Pending">Pending</option>
                        <option value="Preparing">Preparing</option>
                        <option value="Ready">Ready</option>
                        <option value="Delivered">Delivered</option>
                        <option value="Cancelled">Cancelled</option>
                        <option value="Paid">Paid</option>
                        
                    </select>
                    <select class="filter-select" id="timeFilter">
                        <option value="all">All Time</option>
                        <option value="today">Today</option>
                        <option value="week">This Week</option>
                        <option value="month">This Month</option>
                    </select>
                </div>
            </div>
            
            <!-- Statistics Cards -->
            <div class="stats-cards">
                <div class="stat-card stat-pending animate-slide-up delay-1">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <h3 id="pending-count"><?= count(array_filter($orders, function($o) { return $o['status'] == 'Pending'; })) ?></h3>
                        <p>Pending Orders</p>
                    </div>
                </div>
                
                <div class="stat-card stat-preparing animate-slide-up delay-2">
                    <div class="stat-icon">
                        <i class="fas fa-utensils"></i>
                    </div>
                    <div class="stat-info">
                        <h3 id="preparing-count"><?= count(array_filter($orders, function($o) { return $o['status'] == 'Preparing'; })) ?></h3>
                        <p>Preparing Orders</p>
                    </div>
                </div>
                
                <div class="stat-card stat-ready animate-slide-up delay-3">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3 id="ready-count"><?= count(array_filter($orders, function($o) { return $o['status'] == 'Ready'; })) ?></h3>
                        <p>Ready for Pickup</p>
                    </div>
                </div>
                
                <div class="stat-card stat-delivered animate-slide-up delay-4">
                    <div class="stat-icon">
                        <i class="fas fa-credit-card"></i>
                    </div>
                    <div class="stat-info">
                        <h3 id="delivered-count"><?= count(array_filter($orders, function($o) { return $o['status'] == 'Paid'; })) ?></h3>
                        <p>Paid Orders</p>
                    </div>
                </div>
            </div>
            
            <div class="card animate-fade-in delay-5">
                <div class="card-header">
                    <h2 class="card-title">All Orders</h2>
                    <div class="actions">
                        <button class="btn btn-outline" id="refreshBtn">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                    </div>
                </div>
                
                <?php if (count($orders) > 0): ?>
                <div class="table-responsive">
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
                            <?php foreach ($orders as $index => $order): ?>
                            <tr data-status="<?= strtolower($order['status']) ?>" data-order-id="<?= $order['id'] ?>" class="animate-slide-up" style="animation-delay: <?php echo 0.1 + ($index * 0.05); ?>s">
                                <td><strong>#<?= $order['id'] ?></strong></td>
                                <td>
                                    <span class="time-badge">
                                        <i class="fas fa-table"></i> <?= htmlspecialchars($order['table_no']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div><?= htmlspecialchars($order['items']) ?></div>
                                    <small class="text-muted">(<?= $order['total_items'] ?> items)</small>
                                </td>
                                <td><strong>₹<?= number_format($order['total_price'], 2) ?></strong></td>
                                <td>
                                    <div class="status-form">
                                        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                        <select name="status" class="status-select" onchange="updateOrderStatus(this, <?= $order['id'] ?>)">
                                            <option value="Pending"   <?= $order['status'] == 'Pending' ? 'selected' : '' ?>>Pending</option>
                                            <option value="Preparing" <?= $order['status'] == 'Preparing' ? 'selected' : '' ?>>Preparing</option>
                                            <option value="Ready"     <?= $order['status'] == 'Ready' ? 'selected' : '' ?>>Ready</option>
                                            <option value="Delivered" <?= $order['status'] == 'Delivered' ? 'selected' : '' ?>>Delivered</option>
                                            <option value="Paid"      <?= $order['status'] == 'Paid' ? 'selected' : '' ?>>Paid</option>
                                            <option value="Cancelled" <?= $order['status'] == 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                        </select>
                                    </div>
                                </td>

                                <td>
                                    <span class="time-badge">
                                        <i class="fas fa-clock"></i> <?= date('h:i A', strtotime($order['created_at'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="order_details.php?id=<?= $order['id'] ?>" class="btn btn-primary btn-small">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                        <button type="button" class="btn btn-danger btn-small delete-order-btn" data-order-id="<?= $order['id'] ?>">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-clipboard-list"></i>
                    <h3>No Orders Yet</h3>
                    <p>When customers place orders, they will appear here.</p>
                    <a href="../customer/menu.php?restaurant=<?= $_SESSION['user_id'] ?>" class="btn btn-primary" target="_blank">
                        <i class="fas fa-external-link-alt"></i> View Customer Menu
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal" id="deleteModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Confirm Delete</h3>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this order? This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" id="cancelDelete">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">Delete</button>
            </div>
        </div>
    </div>

    <script>
        // Function to update order status using XMLHttpRequest
        function updateOrderStatus(selectElement, orderId) {
            const status = selectElement.value;
            const row = selectElement.closest('tr');
            
            // Store original value for potential rollback
            const originalStatus = selectElement.getAttribute('data-original-status') || selectElement.value;
            selectElement.setAttribute('data-original-status', originalStatus);
            
            // Show loading state
            selectElement.disabled = true;
            
            // Create XMLHttpRequest
            if (status.length === 0) {
                selectElement.disabled = false;
                return;
            }

            var xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function() {
                if (this.readyState === 4) {
                    selectElement.disabled = false;

                    if (this.status === 200) {
                        try {
                            const responseText = this.responseText.trim();
                            const data = JSON.parse(responseText);
                            
                            if (data.success) {
                                // Update UI on success
                                selectElement.setAttribute('data-original-status', status);
                                row.setAttribute('data-status', status.toLowerCase());
                                
                                // Update statistics
                                updateStatistics();
                                
                                // Show success notification
                                showNotification('Status updated successfully', 'success');
                            } else {
                                // Revert on failure
                                selectElement.value = originalStatus;
                                showNotification('Failed to update status: ' + (data.message || 'Unknown error'), 'error');
                            }
                        } catch (e) {
                            // Revert on parse error
                            selectElement.value = originalStatus;
                            console.error('Parse error:', e, 'Response:', this.responseText);
                            showNotification('Error parsing response: ' + e.message, 'error');
                        }
                    } else {
                        // Revert on HTTP error
                        selectElement.value = originalStatus;
                        console.error('HTTP Error:', this.status, this.responseText);
                        showNotification('HTTP Error ' + this.status + ': ' + this.statusText, 'error');
                    }
                }
            };
            
            xmlhttp.onerror = function() {
                selectElement.disabled = false;
                selectElement.value = originalStatus;
                console.error('Network error');
                showNotification('Network error occurred', 'error');
            };
            
            // Send the request
            xmlhttp.open("POST", "", true);
            xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            const sendData = "update_status=1&order_id=" + encodeURIComponent(orderId) + "&status=" + encodeURIComponent(status);
            xmlhttp.send(sendData);
        }

        // Filter functionality
        document.getElementById('statusFilter').addEventListener('change', function() {
            const status = this.value;
            const rows = document.querySelectorAll('.data-table tbody tr');
            
            rows.forEach(row => {
                if (status === 'all' || row.getAttribute('data-status') === status.toLowerCase()) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        // Delete order functionality
        let orderToDelete = null;
        
        document.querySelectorAll('.delete-order-btn').forEach(button => {
            button.addEventListener('click', function() {
                orderToDelete = this.getAttribute('data-order-id');
                document.getElementById('deleteModal').style.display = 'flex';
            });
        });
        
        document.getElementById('cancelDelete').addEventListener('click', function() {
            document.getElementById('deleteModal').style.display = 'none';
            orderToDelete = null;
        });
        
        document.getElementById('confirmDelete').addEventListener('click', function() {
            if (!orderToDelete) return;
            
            // Show loading state
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting...';
            this.disabled = true;
            
            // Create XMLHttpRequest for delete
            if (orderToDelete.length == 0) {
                this.innerHTML = 'Delete';
                this.disabled = false;
                return;
            } else {
                var xmlhttp = new XMLHttpRequest();
                xmlhttp.onreadystatechange = function() {
                    if (this.readyState == 4 && this.status == 200) {
                        try {
                            const data = JSON.parse(this.responseText);
                            if (data.success) {
                                // Remove the row from the table
                                const row = document.querySelector(`tr[data-order-id="${orderToDelete}"]`);
                                if (row) {
                                    row.remove();
                                }
                                
                                // Update statistics
                                updateStatistics();
                                
                                // Show success notification
                                showNotification('Order deleted successfully', 'success');
                                
                                // Close modal
                                document.getElementById('deleteModal').style.display = 'none';
                                
                                // Reset button
                                document.getElementById('confirmDelete').innerHTML = 'Delete';
                                document.getElementById('confirmDelete').disabled = false;
                                
                                // Check if no orders left
                                if (document.querySelectorAll('.data-table tbody tr').length === 0) {
                                    window.location.reload();
                                }
                            } else {
                                // Show error notification
                                showNotification('Failed to delete order: ' + data.message, 'error');
                                document.getElementById('confirmDelete').innerHTML = 'Delete';
                                document.getElementById('confirmDelete').disabled = false;
                            }
                        } catch (e) {
                            // Show error notification
                            showNotification('Error deleting order: ' + e.message, 'error');
                            document.getElementById('confirmDelete').innerHTML = 'Delete';
                            document.getElementById('confirmDelete').disabled = false;
                        }
                    }
                };
                
                // Send the request
                xmlhttp.open("POST", "", true);
                xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
                xmlhttp.send("delete_order=1&order_id=" + orderToDelete);
            }
        });

        function updateStatistics() {
            // Count orders by status
            const statusCounts = {
                pending: 0,
                preparing: 0,
                ready: 0,
                delivered: 0
            };
            
            document.querySelectorAll('.data-table tbody tr').forEach(row => {
                const status = (row.getAttribute('data-status') || '').toLowerCase();
                if (status === 'paid') {
                    statusCounts.delivered++;
                } else if (statusCounts.hasOwnProperty(status)) {
                    statusCounts[status]++;
                }
            });
            
            // Update the counts in the UI
            document.getElementById('pending-count').textContent = statusCounts.pending;
            document.getElementById('preparing-count').textContent = statusCounts.preparing;
            document.getElementById('ready-count').textContent = statusCounts.ready;
            document.getElementById('delivered-count').textContent = statusCounts.delivered;
            
            // Highlight the relevant stat card
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach(card => {
                card.classList.remove('highlight');
            });
            
            // Add highlight animation to all stat cards
            setTimeout(() => {
                statCards.forEach(card => {
                    card.classList.add('highlight');
                    setTimeout(() => {
                        card.classList.remove('highlight');
                    }, 2000);
                });
            }, 100);
        }

        // Refresh button
        document.getElementById('refreshBtn').addEventListener('click', function() {
            this.classList.add('rotating');
            setTimeout(() => {
                window.location.reload();
            }, 500);
        });

        // Notification function
        function showNotification(message, type = 'info') {
            // Remove existing notifications
            const existingNotifications = document.querySelectorAll('.notification');
            existingNotifications.forEach(notification => {
                notification.remove();
            });
            
            // Create new notification
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.innerHTML = `
                <div class="notification-content">
                    <i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle'}"></i>
                    <span>${message}</span>
                </div>
            `;
            
            // Add to page
            document.body.appendChild(notification);
            
            // Show with animation
            setTimeout(() => {
                notification.classList.add('show');
            }, 10);
            
            // Auto remove after 3 seconds
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => {
                    notification.remove();
                }, 300);
            }, 3000);
        }

        // Add animation class
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