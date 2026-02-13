<?php
// Don't include auth.php to avoid redirects during debugging
session_start();

// Check if running on correct server
echo "<!DOCTYPE html><html><head><title>Debug Status Update</title>";
echo "<style>body{font-family:Arial;padding:20px;} table{border-collapse:collapse;} td,th{padding:10px;border:1px solid #ddd;}</style>";
echo "</head><body>";

echo "<h1>🔍 Debug: Order Status Update</h1>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Current Time: " . date('Y-m-d H:i:s') . "</p>";
echo "<hr>";

echo "<h2>Step 1: Session Check</h2>";
echo "<p>Session User ID: <strong>" . ($_SESSION['user_id'] ?? 'NOT SET') . "</strong></p>";
if (!isset($_SESSION['user_id'])) {
    echo "<p style='color:red'>❌ YOU ARE NOT LOGGED IN! This is why updates fail.</p>";
    echo "<p><strong>Please login first:</strong> <a href='../login.php'>Go to Login Page</a></p>";
    echo "<p>After logging in, come back to this page.</p>";
    echo "</body></html>";
    exit;
} else {
    echo "<p style='color:green'>✓ You are logged in as User ID: " . $_SESSION['user_id'] . "</p>";
}

echo "<h2>Step 2: Database Connection</h2>";
try {
    require_once '../config/db.php';
    echo "<p style='color:green'>✓ Database connected</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Database error: " . $e->getMessage() . "</p>";
    exit;
}

echo "<h2>Step 3: Check Orders</h2>";
try {
    $stmt = $pdo->prepare("SELECT id, table_no, status, total_price FROM orders WHERE restaurant_id = ? LIMIT 5");
    $stmt->execute([$_SESSION['user_id']]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Found <strong>" . count($orders) . "</strong> orders for restaurant ID: " . $_SESSION['user_id'] . "</p>";
    
    if (count($orders) > 0) {
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
        echo "<tr style='background:#f0f0f0'><th>ID</th><th>Table</th><th>Status</th><th>Total</th><th>Test Update</th></tr>";
        foreach ($orders as $ord) {
            echo "<tr>";
            echo "<td>#" . $ord['id'] . "</td>";
            echo "<td>" . $ord['table_no'] . "</td>";
            echo "<td>" . $ord['status'] . "</td>";
            echo "<td>₹" . $ord['total_price'] . "</td>";
            echo "<td><a href='?test_id=" . $ord['id'] . "&test_status=Preparing' style='color:blue'>Test Update</a></td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color:orange'>No orders found for your restaurant</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<h2>Step 4: Test Update</h2>";
if (isset($_GET['test_id']) && isset($_GET['test_status'])) {
    $testId = (int)$_GET['test_id'];
    $testStatus = $_GET['test_status'];
    $restaurantId = $_SESSION['user_id'];
    
    echo "<p>Attempting to update Order #$testId to status '$testStatus'...</p>";
    
    try {
        $stmt = $pdo->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ? AND restaurant_id = ?");
        $result = $stmt->execute([$testStatus, $testId, $restaurantId]);
        $rowCount = $stmt->rowCount();
        
        echo "<p><strong>Result:</strong> " . ($result ? 'SUCCESS' : 'FAILED') . "</p>";
        echo "<p><strong>Rows Affected:</strong> $rowCount</p>";
        
        if ($rowCount > 0) {
            echo "<p style='color:green;font-size:20px;'>✓ ORDER UPDATED SUCCESSFULLY!</p>";
            echo "<p><a href='debug_status.php'>Refresh</a> | <a href='orders.php'>Back to Orders</a></p>";
        } else {
            echo "<p style='color:orange;'>⚠ No rows updated. This means:</p>";
            echo "<ul>";
            echo "<li>Order #$testId doesn't exist, OR</li>";
            echo "<li>Order doesn't belong to restaurant #$restaurantId, OR</li>";
            echo "<li>Status is already '$testStatus'</li>";
            echo "</ul>";
        }
    } catch (Exception $e) {
        echo "<p style='color:red'>❌ UPDATE FAILED: " . $e->getMessage() . "</p>";
    }
}

echo "<hr>";
echo "<h2>Step 5: Test the Form</h2>";
echo "<p>This form mimics what's on the orders page:</p>";

if (isset($orders) && count($orders) > 0) {
    $firstOrder = $orders[0];
    ?>
    <form method="POST" action="orders.php" style="border:2px solid #333; padding:20px; background:#f9f9f9;">
        <p><strong>Order ID:</strong> #<?= $firstOrder['id'] ?></p>
        <p><strong>Current Status:</strong> <?= $firstOrder['status'] ?></p>
        <hr>
        <input type="hidden" name="update_status" value="1">
        <input type="hidden" name="order_id" value="<?= $firstOrder['id'] ?>">
        <label>Change Status To:</label>
        <select name="status" style="padding:10px; font-size:16px;">
            <option value="Pending">Pending</option>
            <option value="Preparing">Preparing</option>
            <option value="Ready">Ready</option>
            <option value="Delivered">Delivered</option>
            <option value="Paid">Paid</option>
            <option value="Cancelled">Cancelled</option>
        </select>
        <br><br>
        <button type="submit" style="padding:10px 20px; background:#52b788; color:white; border:none; border-radius:5px; cursor:pointer; font-size:16px;">
            Submit Update
        </button>
    </form>
    <?php
}
?>
</body>
</html>
