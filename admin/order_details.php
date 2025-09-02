<?php
require_once '../includes/auth.php';
require_once '../config/db.php';

// Check if order ID is provided
if (!isset($_GET['id'])) {
    header("Location: orders.php");
    exit();
}

$order_id = (int)$_GET['id'];

// Get order details
$order_stmt = $pdo->prepare("
    SELECT o.*, r.restaurant_name
    FROM orders o 
    JOIN users r ON o.restaurant_id = r.id
    WHERE o.id = ? AND o.restaurant_id = ?
");
$order_stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $order_stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    die("Order not found or access denied");
}

// Get order items
$items_stmt = $pdo->prepare("
    SELECT m.name, m.description, oi.quantity, oi.price, (oi.quantity * oi.price) as total
    FROM order_items oi
    JOIN menu_items m ON oi.menu_item_id = m.id
    WHERE oi.order_id = ?
");
$items_stmt->execute([$order_id]);
$order_items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals
$subtotal = array_sum(array_column($order_items, 'total'));
$tax = $subtotal * 0.1; // 10% tax for example
$total = $subtotal + $tax;

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
    <title>Order #<?= $order_id ?> - RestaurantPro</title>
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

        /* Cards */
        .card {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            transition: var(--transition);
            margin-bottom: 2rem;
        }

        .card:hover {
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

        /* Order Info */
        .order-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .info-group {
            padding: 1rem;
            background: var(--light);
            border-radius: var(--border-radius);
        }

        .info-label {
            font-size: 0.85rem;
            color: var(--gray);
            margin-bottom: 0.25rem;
        }

        .info-value {
            font-size: 1rem;
            font-weight: 500;
            color: var(--dark);
        }

        /* Status Badge */
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

        /* Table Styles */
        .data-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-bottom: 1.5rem;
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

        /* Totals */
        .totals {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
        }

        .total-label {
            font-weight: 500;
        }

        .total-amount {
            font-weight: 600;
        }

        .grand-total {
            border-top: 2px solid var(--light-gray);
            padding-top: 1rem;
            margin-top: 1rem;
            font-size: 1.2rem;
            color: var(--primary);
        }

        /* Print Styles */
        @media print {
            body {
                background: white;
                color: black;
                font-size: 12pt;
            }
            
            .dashboard-container {
                display: block;
            }
            
            .main-content {
                margin-left: 0;
                padding: 0;
                max-width: 100%;
            }
            
            .page-header, .btn, .card-header, .no-print {
                display: none !important;
            }
            
            .card {
                box-shadow: none;
                border: 1px solid #ddd;
            }
            
            .invoice-header {
                text-align: center;
                margin-bottom: 1.5rem;
                padding-bottom: 1rem;
                border-bottom: 2px solid #333;
            }
            
            .invoice-title {
                font-size: 18pt;
                font-weight: bold;
                margin-bottom: 0.5rem;
            }
            
            .invoice-subtitle {
                font-size: 14pt;
                color: #666;
            }
            
            .print-only {
                display: block !important;
            }
            
            .data-table {
                font-size: 10pt;
            }
            
            .data-table th, .data-table td {
                padding: 0.5rem;
            }
            
            .totals {
                margin-top: 1rem;
            }
            
            .grand-total {
                font-size: 14pt;
            }
            
            .footer {
                margin-top: 2rem;
                padding-top: 1rem;
                border-top: 1px solid #ddd;
                text-align: center;
                font-size: 9pt;
                color: #666;
            }
        }

        .print-only {
            display: none;
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
            
            .page-header {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }
            
            .order-info {
                grid-template-columns: 1fr;
            }
            
            .data-table {
                display: block;
                overflow-x: auto;
            }
            
            .totals {
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
            <!-- Page Header -->
            <div class="page-header animate-fade-in">
                <h1 class="page-title">
                    <i class="fas fa-receipt"></i> Order Details
                </h1>
                <div class="actions">
                    <a href="orders.php" class="btn btn-outline">
                        <i class="fas fa-arrow-left"></i> Back to Orders
                    </a>
                    <button class="btn btn-primary">
                        <i class="fas fa-print"></i> <a href="bill.php" class="nav-item">Print Bill</a>
                    </button>
                </div>
            </div>
            
            <!-- Order Details Card -->
            <div class="card animate-fade-in delay-1">
                <div class="card-header no-print">
                    <h2 class="card-title">
                        <i class="fas fa-clipboard-list"></i> Order #<?= $order_id ?>
                    </h2>
                    <span class="status-badge <?php echo strtolower($order['status']); ?>">
                        <i class="fas fa-<?php 
                            echo strtolower($order['status']) == 'pending' ? 'clock' : 
                                (strtolower($order['status']) == 'preparing' ? 'utensils' : 
                                (strtolower($order['status']) == 'ready' ? 'check-circle' : 'truck')); 
                        ?>"></i>
                        <?= $order['status'] ?>
                    </span>
                </div>
                
                <div class="card-body">
                    <!-- Print Header (only shows when printing) -->
                    <div class="print-only invoice-header">
                        <h1 class="invoice-title"><?= htmlspecialchars($order['restaurant_name']) ?></h1>
                        <p class="invoice-subtitle">Restaurant Bill</p>
                    </div>
                    
                    <div class="order-info">
                        <div class="info-group animate-slide-up delay-2">
                            <div class="info-label">Order Date & Time</div>
                            <div class="info-value"><?= date('F j, Y, h:i A', strtotime($order['created_at'])) ?></div>
                        </div>
                        
                        <div class="info-group animate-slide-up delay-3">
                            <div class="info-label">Table Number</div>
                            <div class="info-value"><?= htmlspecialchars($order['table_no']) ?></div>
                        </div>
                        
                        <div class="info-group animate-slide-up delay-4">
                            <div class="info-label">Order Status</div>
                            <div class="info-value"><?= $order['status'] ?></div>
                        </div>
                        
                        <div class="info-group animate-slide-up delay-5">
                            <div class="info-label">Order ID</div>
                            <div class="info-value">#<?= $order_id ?></div>
                        </div>
                    </div>
                    
                    <h3 style="margin-bottom: 1rem;">Order Items</h3>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Quantity</th>
                                <th>Price</th>
                                <th>Special Instruction</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($order_items as $index => $item): ?>
                            <tr class="animate-slide-up" style="animation-delay: <?php echo 0.1 + ($index * 0.05); ?>s">
                                <td>
                                    <div><strong><?= htmlspecialchars($item['name']) ?></strong></div>
                                    <?php if (!empty($item['description'])): ?>
                                    <small style="color: var(--gray);"><?= htmlspecialchars($item['description']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?= $item['quantity'] ?></td>
                                <td>₹<?= number_format($item['price'], 2) ?></td>
                                <td><?= htmlspecialchars($order['notes']) ?></td>
                                <td>₹<?= number_format($item['total'], 2) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <div class="totals">
                        <div></div>
                        <div>
                            <div class="total-row">
                                <span class="total-label">Subtotal:</span>
                                <span class="total-amount">₹<?= number_format($subtotal, 2) ?></span>
                            </div>
                            <div class="total-row">
                                <span class="total-label">Tax (10%):</span>
                                <span class="total-amount">₹<?= number_format($tax, 2) ?></span>
                            </div>
                            <div class="total-row grand-total">
                                <span class="total-label">Total Amount:</span>
                                <span class="total-amount">₹<?= number_format($total, 2) ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Restaurant Info for Printing -->
                    <div class="print-only footer">
                        <p><?= htmlspecialchars($order['restaurant_name']) ?> | 
                           Ahmedabad Highway Rajkot | 
                           Phone: 9327470468 | 
                           Email: shahskitechen@gmail.com</p>
                        <p>Thank you for dining with us!</p>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
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