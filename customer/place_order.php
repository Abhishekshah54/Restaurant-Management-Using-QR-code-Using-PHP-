<?php
require_once '../config/db.php';
require_once '../functions.php';

session_start();

// Debug: Check what's being posted
error_log("POST data: " . print_r($_POST, true));
error_log("SESSION cart: " . print_r($_SESSION['cart'] ?? 'empty', true));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $restaurant_id = (int)$_POST['restaurant_id'];
    $table_no = $_POST['table_no'];
    $cart = $_SESSION['cart'] ?? [];
    $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';
    $payment_method = isset($_POST['payment_method']) ? $_POST['payment_method'] : 'cash';
    
    if (empty($cart)) {
        $_SESSION['error'] = "Cart is empty";
        header("Location: cart.php?restaurant=$restaurant_id&table=$table_no");
        exit();
    }
    
    try {
        // Check database connection
        if (!$pdo) {
            throw new Exception("Database connection failed");
        }

        // Begin transaction
        $pdo->beginTransaction();
        
        // Calculate total amount
        $total_price = 0;
        foreach ($cart as $item) {
            $total_price += $item['price'] * $item['quantity'];
        }
        
        // Get current timestamp
        $current_time = date('Y-m-d H:i:s');
        
        // Insert order with correct column names matching your database
        $stmt = $pdo->prepare("INSERT INTO orders (restaurant_id, table_no, total_price, status, notes, payment_method, is_active, created_at, updated_at) 
                               VALUES (?, ?, ?, 'pending', ?, ?, 1, ?, ?)");
        $stmt->execute([
            $restaurant_id, 
            $table_no, 
            $total_price, 
            $notes, 
            $payment_method,
            $current_time,
            $current_time
        ]);
        
        $order_id = $pdo->lastInsertId();
        
        // Insert order items - UPDATED to match your order_items table structure
        $stmt = $pdo->prepare("INSERT INTO order_items (order_id, menu_item_id, quantity, price, created_at, updated_at) 
                               VALUES (?, ?, ?, ?, ?, ?)");
        
        foreach ($cart as $menu_item_id => $item) {
            $stmt->execute([
                $order_id, 
                $menu_item_id, 
                $item['quantity'], 
                $item['price'],
                $current_time,
                $current_time
            ]);
        }
        
        // Mark table as occupied (if restaurant_tables table exists)
        $stmt = $pdo->prepare("UPDATE restaurant_tables SET is_occupied = 1, status = 'occupied' 
                               WHERE restaurant_id = ? AND table_no = ?");
        $stmt->execute([$restaurant_id, $table_no]);
        
        // Commit transaction
        $pdo->commit();
        
        // Clear cart
        unset($_SESSION['cart']);
        
        // Redirect to order confirmation
        header("Location: order_success.php?order_id=$order_id&restaurant=$restaurant_id&table=$table_no");
        exit();
        
    } catch (Exception $e) {
        // Rollback transaction on error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Order placement error: " . $e->getMessage());
        $_SESSION['error'] = "Order placement failed. Please try again. Error: " . $e->getMessage();
        header("Location: cart.php?restaurant=$restaurant_id&table=$table_no");
        exit();
    }
} else {
    header("Location: menu.php");
    exit();
}
?>