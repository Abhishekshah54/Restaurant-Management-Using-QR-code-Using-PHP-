<?php
require_once 'config/db.php';

// Simple authentication check
// function isLoggedIn() {
//     return isset($_SESSION['user_id']);
// }

// Redirect function
function redirect($url) {
    header("Location: $url");
    exit();
}

function __($key, $lang = 'en') {
    global $TRANSLATIONS;
    return $TRANSLATIONS[$key][$lang] ?? $key;
}

// Get restaurant menu
function getMenuItems($restaurant_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM menu_items WHERE restaurant_id = ?");
    $stmt->execute([$restaurant_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Add new menu item
function addMenuItem($data) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO menu_items (restaurant_id, category, name, description, price, image) VALUES (?, ?, ?, ?, ?, ?)");
    return $stmt->execute([$data['restaurant_id'], $data['category'], $data['name'], $data['description'], $data['price'], $data['image']]);
}
?>

