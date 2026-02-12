<?php
require_once __DIR__ . '/../config/db.php';

try {
    // Get all orders with their items
    $orders = $pdo->query('SELECT * FROM orders ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);

    echo "Updating order totals based on menu prices...\n\n";

    foreach ($orders as $order) {
        $orderId = $order['id'];
        
        // Get order items and calculate total from menu prices
        $stmt = $pdo->prepare('SELECT oi.quantity, mi.price FROM order_items oi JOIN menu_items mi ON oi.menu_item_id = mi.id WHERE oi.order_id = ?');
        $stmt->execute([$orderId]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $calculatedTotal = 0;
        foreach ($items as $item) {
            $calculatedTotal += $item['quantity'] * $item['price'];
        }
        
        // Update if different
        if ($calculatedTotal != $order['total_price']) {
            $updateStmt = $pdo->prepare('UPDATE orders SET total_price = ? WHERE id = ?');
            $updateStmt->execute([$calculatedTotal, $orderId]);
            echo "Order #$orderId: Updated total from \${$order['total_price']} to \${$calculatedTotal}\n";
        } else {
            echo "Order #$orderId: Total correct at \${$calculatedTotal}\n";
        }
    }

    echo "\nPrice update complete!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
