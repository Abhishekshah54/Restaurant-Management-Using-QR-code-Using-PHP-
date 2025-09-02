<?php
require_once '../config/db.php';
require_once '../functions.php';

session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

if (!isset($_SESSION['cart']) || !isset($_POST['item_id']) || !isset($_POST['quantity'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit();
}

$item_id = (int)$_POST['item_id'];
$quantity = (int)$_POST['quantity'];

if ($quantity < 1) {
    echo json_encode(['success' => false, 'message' => 'Quantity must be at least 1']);
    exit();
}

// Update the cart
if (isset($_SESSION['cart'][$item_id])) {
    $_SESSION['cart'][$item_id]['quantity'] = $quantity;
    
    // Calculate updated totals
    $subtotal = 0;
    foreach ($_SESSION['cart'] as $item) {
        $subtotal += $item['price'] * $item['quantity'];
    }
    $tax = $subtotal * 0.08;
    $total = $subtotal + $tax;
    $item_total = $_SESSION['cart'][$item_id]['price'] * $quantity;
    
    echo json_encode([
        'success' => true,
        'item_total' => $item_total,
        'subtotal' => $subtotal,
        'tax' => $tax,
        'total' => $total
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Item not found in cart']);
}
?>