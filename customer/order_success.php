<?php
require_once '../config/db.php';
require_once '../functions.php';

session_start();

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$restaurant_id = isset($_GET['restaurant']) ? (int)$_GET['restaurant'] : 0;
$table_no = isset($_GET['table']) ? (int)$_GET['table'] : 0;

if (!$order_id || !$restaurant_id || !$table_no) {
    header("Location: menu.php");
    exit();
}

// Get order details
$order = $pdo->query("SELECT * FROM orders WHERE id = $order_id")->fetch();
$restaurant = $pdo->query("SELECT * FROM users WHERE id = $restaurant_id")->fetch();

// Get order items
$order_items = $pdo->query("SELECT * FROM order_items WHERE order_id = $order_id")->fetchAll();

// Calculate total from items (as a backup in case total_price is not set)
$calculated_total = 0;
foreach ($order_items as $item) {
    $calculated_total += $item['price'] * $item['quantity'];
}

// Use the order total_price if available, otherwise use calculated total
$total_amount = $order['total_price'] ?? $calculated_total;

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - <?php echo htmlspecialchars($restaurant['restaurant_name']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Reuse the same styles from cart.php */
        :root {
            --primary: #4361ee;
            --secondary: #3f37c9;
            --success: #4cc9f0;
            --danger: #f72585;
            --warning: #f8961e;
            --info: #4895ef;
            --light: #f8f9fa;
            --dark: #212529;
            --white: #ffffff;
            --gray: #6c757d;
            --gray-light: #e9ecef;
            --border-radius: 0.375rem;
            --box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f7fb;
            color: #4a5568;
            line-height: 1.6;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }

        .order-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .restaurant-logo {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 50%;
            border: 3px solid var(--primary);
            margin-bottom: 1rem;
        }

        .order-details {
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .order-items {
            margin: 1.5rem 0;
        }

        .order-item {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--gray-light);
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .order-totals {
            border-top: 2px solid var(--gray-light);
            padding-top: 1rem;
            margin-top: 1rem;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
        }

        .grand-total {
            font-weight: bold;
            font-size: 1.2rem;
            border-top: 1px solid var(--gray-light);
            margin-top: 0.5rem;
            padding-top: 1rem;
        }

        .actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: var(--border-radius);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
        }

        .btn.primary {
            background: var(--primary);
            color: var(--white);
        }

        .btn.primary:hover {
            background: var(--secondary);
        }

        .btn.secondary {
            background: var(--gray-light);
            color: var(--dark);
        }

        .btn.secondary:hover {
            background: var(--gray);
        }

        .status {
            color: var(--success);
            font-weight: 600;
        }

        .item-info {
            display: flex;
            justify-content: space-between;
            width: 100%;
        }

        .item-details {
            flex: 1;
        }

        .item-price {
            min-width: 80px;
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="order-header">
            <div class="restaurant-info">
                <?php if (!empty($restaurant['logo'])): ?>
                <img src="../assets/images/restaurant_logos/<?= htmlspecialchars($restaurant['logo']) ?>" alt="Restaurant Logo" class="restaurant-logo">
                <?php endif; ?>
                <h1>Order Confirmation</h1>
                <p>Thank you for your order at <?= htmlspecialchars($restaurant['restaurant_name']) ?></p>
            </div>
        </div>
        
        <div class="order-details">
            <h2>Order #<?= $order_id ?></h2>
            <p>Table: <?= $table_no ?></p>
            <p>Order Status: <span class="status"><?= ucfirst($order['status']) ?></span></p>
            <p>Order Time: <?= date('F j, Y, g:i a', strtotime($order['created_at'])) ?></p>
            
            <h3>Order Items</h3>
            <div class="order-items">
                <?php foreach ($order_items as $item): ?>
                <div class="order-item">
                    <div class="item-info">
                        <div class="item-details">
                            <strong>Item #<?= $item['menu_item_id'] ?></strong>
                            <div>Price: $<?= number_format($item['price'], 2) ?> each</div>
                        </div>
                        <div class="item-price">
                            <div>Quantity: x<?= $item['quantity'] ?></div>
                            <strong>$<?= number_format($item['price'] * $item['quantity'], 2) ?></strong>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="order-totals">
                <div class="total-row grand-total">
                    <span>Total Amount:</span>
                    <span>$<?= number_format($total_amount, 2) ?></span>
                </div>
            </div>
            
            <?php if (!empty($order['notes'])): ?>
            <div class="order-notes">
                <h3>Special Instructions</h3>
                <p><?= htmlspecialchars($order['notes']) ?></p>
            </div>
            <?php endif; ?>

            <?php if (!empty($order['payment_method'])): ?>
            <div class="payment-method">
                <h3>Payment Method</h3>
                <p><?= ucfirst($order['payment_method']) ?></p>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="actions">
            <a href="menu.php?restaurant=<?= $restaurant_id ?>&table=<?= $table_no ?>" class="btn primary">Order More</a>
            <a href="../index.php" class="btn secondary">Back to Home</a>
        </div>
    </div>
</body>
</html>