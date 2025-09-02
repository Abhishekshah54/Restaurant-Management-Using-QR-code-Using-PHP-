<?php
require_once '../config/db.php';

$order_id = $_GET['order_id'] ?? 0;
$order = $pdo->query("SELECT * FROM orders WHERE id = $order_id")->fetch();

if (!$order) {
    die("Order not found");
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Track Order #<?php echo $order_id; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <h1>Order Status</h1>
        <div class="order-status">
            <div class="status-step <?php if ($order['status'] == 'Pending') echo 'active'; ?>">
                <span>Pending</span>
            </div>
            <div class="status-step <?php if ($order['status'] == 'Preparing') echo 'active'; ?>">
                <span>Preparing</span>
            </div>
            <div class="status-step <?php if ($order['status'] == 'Ready') echo 'active'; ?>">
                <span>Ready</span>
            </div>
        </div>
    </div>
    <script>
        // Poll for updates every 5 seconds
        setInterval(() => {
            fetch(`../api/get_order_status.php?order_id=<?php echo $order_id; ?>`)
                .then(res => res.json())
                .then(data => {
                    if (data.status !== '<?php echo $order['status']; ?>') {
                        location.reload(); // Refresh if status changed
                    }
                });
        }, 5000);
    </script>
</body>
</html>