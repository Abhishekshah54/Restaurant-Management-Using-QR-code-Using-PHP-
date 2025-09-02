<?php
require_once '../config/db.php';
require_once '../functions.php';

session_start();

$restaurant_id = isset($_GET['restaurant']) ? (int)$_GET['restaurant'] : 0;
$table_no = isset($_GET['table']) ? (int)$_GET['table'] : 0;

if (!$restaurant_id || !$table_no) {
    header("Location: menu.php");
    exit();
}

// Get restaurant info
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$restaurant_id]);
    $restaurant = $stmt->fetch();
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $restaurant = false;
}

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Initialize payment method if not set
if (!isset($_SESSION['payment_method'])) {
    $_SESSION['payment_method'] = 'cash'; // Default payment method
}

// Handle quantity updates via form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_quantity'])) {
        $item_id = (int)$_POST['item_id'];
        $quantity = (int)$_POST['quantity'];
        $action = $_POST['action'] ?? '';
        
        if ($action === 'decrease') {
            $quantity = max(1, $quantity - 1);
        } elseif ($action === 'increase') {
            $quantity = $quantity + 1;
        }
        
        if ($quantity < 1) {
            $_SESSION['error'] = "Quantity must be at least 1";
        } elseif (isset($_SESSION['cart'][$item_id])) {
            $_SESSION['cart'][$item_id]['quantity'] = $quantity;
            $_SESSION['success'] = "Quantity updated successfully";
        }
        
        header("Location: cart.php?restaurant=$restaurant_id&table=$table_no");
        exit();
    }
    
    // Handle payment method change
    if (isset($_POST['update_payment_method'])) {
        $payment_method = $_POST['payment_method'] ?? 'cash';
        $_SESSION['payment_method'] = $payment_method;
        $_SESSION['success'] = "Payment method updated to " . ($payment_method === 'cash' ? 'Cash' : 'Online');
        header("Location: cart.php?restaurant=$restaurant_id&table=$table_no");
        exit();
    }
}

// Remove item from cart
if (isset($_GET['remove_item'])) {
    $item_id = (int)$_GET['remove_item'];
    if (isset($_SESSION['cart'][$item_id])) {
        unset($_SESSION['cart'][$item_id]);
        $_SESSION['success'] = "Item removed from cart";
    }
    header("Location: cart.php?restaurant=$restaurant_id&table=$table_no");
    exit();
}

// Calculate totals
$subtotal = 0;
foreach ($_SESSION['cart'] as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

$tax_rate = 0.05; // 5% tax
$tax = $subtotal * $tax_rate;
$total = $subtotal + $tax;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart - <?= htmlspecialchars($restaurant['restaurant_name'] ?? 'Restaurant') ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
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
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }

        .cart-header {
            margin-bottom: 2rem;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            margin-bottom: 1rem;
        }

        .back-btn:hover {
            color: var(--secondary);
        }

        .restaurant-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .restaurant-logo {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 50%;
            border: 2px solid var(--primary);
        }

        .restaurant-info h1 {
            font-size: 1.75rem;
            color: var(--dark);
        }

        .table-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--gray);
        }

        .cart-content {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }

        .cart-items-section h2,
        .order-summary h2 {
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            color: var(--dark);
        }

        .cart-items {
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
        }

        .cart-item {
            display: grid;
            grid-template-columns: 1fr auto auto;
            gap: 1rem;
            padding: 1.25rem;
            border-bottom: 1px solid var(--gray-light);
            align-items: center;
        }

        .cart-item:last-child {
            border-bottom: none;
        }

        .item-info h3 {
            font-size: 1.1rem;
            margin-bottom: 0.25rem;
            color: var(--dark);
        }

        .item-price {
            color: var(--primary);
            font-weight: 500;
        }

        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .quantity-btn {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: var(--gray-light);
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
        }

        .quantity-btn:hover {
            background: var(--primary);
            color: var(--white);
        }

        .item-quantity {
            width: 40px;
            text-align: center;
            font-weight: 500;
            border: 1px solid var(--gray-light);
            border-radius: var(--border-radius);
            padding: 0.25rem;
        }

        .remove-item {
            background: none;
            border: none;
            color: var(--danger);
            cursor: pointer;
            font-size: 1rem;
            margin-left: 0.5rem;
        }

        .item-total {
            font-weight: 600;
            color: var(--dark);
            min-width: 80px;
            text-align: right;
        }

        .order-summary {
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 1.5rem;
            align-self: start;
            position: sticky;
            top: 2rem;
        }

        .summary-details {
            margin-bottom: 2rem;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
        }

        .summary-row.total {
            border-top: 1px solid var(--gray-light);
            margin-top: 0.5rem;
            padding-top: 1rem;
            font-weight: 600;
            font-size: 1.1rem;
            color: var(--dark);
        }

        #checkout-form h3 {
            font-size: 1.1rem;
            margin-bottom: 1rem;
            color: var(--dark);
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--dark);
        }

        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--gray-light);
            border-radius: var(--border-radius);
            font-family: 'Poppins', sans-serif;
            resize: vertical;
        }

        .payment-methods {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .payment-method {
            position: relative;
        }

        .payment-method input[type="radio"] {
            position: absolute;
            opacity: 0;
        }

        .payment-method label {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 0.75rem;
            border: 2px solid var(--gray-light);
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: var(--transition);
            text-align: center;
        }

        .payment-method input[type="radio"]:checked + label {
            border-color: var(--primary);
            background-color: rgba(67, 97, 238, 0.05);
        }

        .payment-method label i {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }

        .payment-method.cash label i { color: var(--success); }
        .payment-method.online label i { color: var(--primary); }

        .checkout-btn {
            width: 100%;
            padding: 0.75rem;
            background: var(--success);
            color: var(--white);
            border: none;
            border-radius: var(--border-radius);
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .checkout-btn:hover:not(:disabled) {
            background: #3aa8d8;
        }

        .checkout-btn:disabled {
            background: var(--gray-light);
            cursor: not-allowed;
        }

        .error-message {
            background-color: #fee;
            color: var(--danger);
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-bottom: 1rem;
            border-left: 4px solid var(--danger);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .success-message {
            background-color: #efe;
            color: var(--success);
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-bottom: 1rem;
            border-left: 4px solid var(--success);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .empty-cart {
            text-align: center;
            padding: 2rem;
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }

        .empty-cart i {
            font-size: 3rem;
            color: var(--gray-light);
            margin-bottom: 1rem;
        }

        .empty-cart h3 {
            margin-bottom: 0.5rem;
            color: var(--dark);
        }

        .empty-cart p {
            color: var(--gray);
            margin-bottom: 1.5rem;
        }

        .browse-menu-btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background: var(--primary);
            color: var(--white);
            text-decoration: none;
            border-radius: var(--border-radius);
            font-weight: 500;
            transition: var(--transition);
        }

        .browse-menu-btn:hover {
            background: var(--secondary);
        }

        .update-quantity-form {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .payment-form {
            margin-bottom: 1.5rem;
            padding: 1rem;
            background: var(--light);
            border-radius: var(--border-radius);
        }

        .payment-form h4 {
            margin-bottom: 1rem;
            color: var(--dark);
        }

        @media (max-width: 768px) {
            .cart-content {
                grid-template-columns: 1fr;
            }
            
            .cart-item {
                grid-template-columns: 1fr;
                text-align: center;
                gap: 0.75rem;
            }
            
            .quantity-controls {
                justify-content: center;
            }
            
            .restaurant-info {
                flex-direction: column;
                text-align: center;
            }
            
            .payment-methods {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="cart-header">
            <a href="menu.php?restaurant=<?= $restaurant_id ?>&table=<?= $table_no ?>" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Menu
            </a>
            <div class="restaurant-info">
                <?php if (!empty($restaurant['logo'])): ?>
                <img src="../assets/images/restaurant_logos/<?= htmlspecialchars($restaurant['logo']) ?>" alt="Restaurant Logo" class="restaurant-logo">
                <?php endif; ?>
                <div>
                    <h1>Your Order from <?= htmlspecialchars($restaurant['restaurant_name'] ?? 'Restaurant') ?></h1>
                    <div class="table-info">
                        <i class="fas fa-table"></i> Table: <?= $table_no ?>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if (!empty($_SESSION['error'])): ?>
        <div class="error-message">
            <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($_SESSION['error']) ?>
            <?php unset($_SESSION['error']); ?>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($_SESSION['success'])): ?>
        <div class="success-message">
            <i class="fas fa-check-circle"></i> <?= htmlspecialchars($_SESSION['success']) ?>
            <?php unset($_SESSION['success']); ?>
        </div>
        <?php endif; ?>
        
        <div class="cart-content">
            <div class="cart-items-section">
                <h2>Order Items</h2>
                
                <?php if (empty($_SESSION['cart'])): ?>
                <div class="empty-cart">
                    <i class="fas fa-shopping-cart"></i>
                    <h3>Your cart is empty</h3>
                    <p>Add some delicious items from the menu</p>
                    <a href="menu.php?restaurant=<?= $restaurant_id ?>&table=<?= $table_no ?>" class="browse-menu-btn">Browse Menu</a>
                </div>
                <?php else: ?>
                <div class="cart-items">
                    <?php foreach ($_SESSION['cart'] as $item_id => $item): 
                        $item_total = $item['price'] * $item['quantity'];
                    ?>
                    <div class="cart-item">
                        <div class="item-info">
                            <h3><?= htmlspecialchars($item['name']) ?></h3>
                            <p class="item-price">₹<?= number_format($item['price'], 2) ?></p>
                        </div>
                        <div class="quantity-controls">
                            <form method="POST" action="" class="update-quantity-form">
                                <input type="hidden" name="item_id" value="<?= $item_id ?>">
                                <input type="hidden" name="update_quantity" value="1">
                                <button type="submit" name="action" value="decrease" class="quantity-btn">-</button>
                                <input type="number" class="item-quantity" name="quantity" value="<?= $item['quantity'] ?>" min="1" onchange="this.form.submit()">
                                <button type="submit" name="action" value="increase" class="quantity-btn">+</button>
                            </form>
                            <a href="cart.php?restaurant=<?= $restaurant_id ?>&table=<?= $table_no ?>&remove_item=<?= $item_id ?>" class="remove-item">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                        <div class="item-total">
                            ₹<?= number_format($item_total, 2) ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="order-summary">
                <h2>Order Summary</h2>
                <div class="summary-details">
                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span id="subtotal">₹<?= number_format($subtotal, 2) ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Tax (5%)</span>
                        <span id="tax">₹<?= number_format($tax, 2) ?></span>
                    </div>
                    <div class="summary-row total">
                        <span>Total</span>
                        <span id="total">₹<?= number_format($total, 2) ?></span>
                    </div>
                </div>
                
                <!-- Payment Method Selection -->
                <div class="payment-form">
                    <h4>Payment Method</h4>
                    <form method="POST" action="">
                        <div class="payment-methods">
                            <div class="payment-method cash">
                                <input type="radio" id="cash" name="payment_method" value="cash" <?= $_SESSION['payment_method'] === 'cash' ? 'checked' : '' ?>>
                                <label for="cash">
                                    <i class="fas fa-money-bill-wave"></i>
                                    <span>Cash</span>
                                </label>
                            </div>
                            <div class="payment-method online">
                                <input type="radio" id="online" name="payment_method" value="online" <?= $_SESSION['payment_method'] === 'online' ? 'checked' : '' ?>>
                                <label for="online">
                                    <i class="fas fa-credit-card"></i>
                                    <span>Online</span>
                                </label>
                            </div>
                        </div>
                        <button type="submit" name="update_payment_method" class="checkout-btn">
                            <i class="fas fa-sync-alt"></i> Update Payment Method
                        </button>
                    </form>
                </div>
                
                <form id="checkout-form" method="POST" action="place_order.php">
                    <input type="hidden" name="restaurant_id" value="<?= $restaurant_id ?>">
                    <input type="hidden" name="table_no" value="<?= $table_no ?>">
                    <input type="hidden" name="payment_method" value="<?= $_SESSION['payment_method'] ?>">
                    
                    <div class="form-group">
                        <label for="notes">Special Instructions (Optional)</label>
                        <textarea id="notes" name="notes" rows="3" placeholder="Any special requests..."><?= isset($_POST['notes']) ? htmlspecialchars($_POST['notes']) : '' ?></textarea>
                    </div>
                    
                    <button type="submit" name="place_order" id="place-order-btn" class="checkout-btn" <?= empty($_SESSION['cart']) ? 'disabled' : '' ?>>
                        <i class="fas fa-check-circle"></i> 
                        <?= $_SESSION['payment_method'] === 'cash' ? 'Place Order' : 'Proceed to Payment' ?>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Update button text based on payment method
        document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const paymentMethod = this.value;
                const placeOrderBtn = document.getElementById('place-order-btn');
                
                if (paymentMethod === 'cash') {
                    placeOrderBtn.innerHTML = '<i class="fas fa-check-circle"></i> Place Order';
                } else {
                    placeOrderBtn.innerHTML = '<i class="fas fa-check-circle"></i> Proceed to Payment';
                }
            });
        });
    </script>
</body>
</html>