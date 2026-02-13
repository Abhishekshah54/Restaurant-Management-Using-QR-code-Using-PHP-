<?php
/**
 * TEST FILE - Order Status Update
 * This file helps diagnose issues with status updates
 */

require_once '../includes/auth.php';
require_once '../config/db.php';

echo "<h2>Testing Order Status Update</h2>";

// Check if we're logged in
echo "<p><strong>Session User ID:</strong> " . ($_SESSION['user_id'] ?? 'NOT SET') . "</p>";

// Test 1: Check database connection
try {
    $stmt = $pdo->query("SELECT 1");
    echo "<p style='color:green'>✓ Database connection OK</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>✗ Database connection failed: " . $e->getMessage() . "</p>";
    exit;
}

// Test 2: Get orders
try {
    $stmt = $pdo->prepare("SELECT id, status FROM orders WHERE restaurant_id = ? LIMIT 5");
    $stmt->execute([$_SESSION['user_id']]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p style='color:green'>✓ Found " . count($orders) . " orders</p>";
    
    if (count($orders) > 0) {
        echo "<table border='1' cellpadding='10'>";
        echo "<tr><th>Order ID</th><th>Current Status</th><th>Test Update</th></tr>";
        foreach ($orders as $order) {
            echo "<tr>";
            echo "<td>" . $order['id'] . "</td>";
            echo "<td>" . $order['status'] . "</td>";
            echo "<td><a href='?test_update=" . $order['id'] . "'>Test Update to 'Preparing'</a></td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>✗ Error fetching orders: " . $e->getMessage() . "</p>";
}

// Test 3: Actually perform update if requested
if (isset($_GET['test_update'])) {
    $orderId = (int)$_GET['test_update'];
    $restaurantId = $_SESSION['user_id'];
    
    echo "<h3>Testing Update for Order #$orderId</h3>";
    
    try {
        $stmt = $pdo->prepare("UPDATE orders SET status = 'Preparing', updated_at = NOW() WHERE id = ? AND restaurant_id = ?");
        $result = $stmt->execute([$orderId, $restaurantId]);
        
        if ($result && $stmt->rowCount() > 0) {
            echo "<p style='color:green;font-size:20px'>✓ SUCCESS! Order #$orderId updated to 'Preparing'</p>";
            echo "<p><a href='test_update.php'>Refresh Test Page</a> | <a href='orders.php'>Back to Orders</a></p>";
        } else {
            echo "<p style='color:orange'>⚠ No rows affected. Order may not exist or status already 'Preparing'</p>";
            echo "<p>Rows affected: " . $stmt->rowCount() . "</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color:red'>✗ UPDATE FAILED: " . $e->getMessage() . "</p>";
    }
}

// Test 4: Check column definition
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM orders WHERE Field = 'status'");
    $column = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<h3>Status Column Definition:</h3>";
    echo "<pre>" . print_r($column, true) . "</pre>";
} catch (Exception $e) {
    echo "<p style='color:red'>✗ Error checking column: " . $e->getMessage() . "</p>";
}
?>
