<?php
require_once '../config/db.php';
require_once '../functions.php';

header('Content-Type: application/json');

// Enable CORS if needed
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Get restaurant ID from query parameter
$restaurant_id = isset($_GET['restaurant_id']) ? (int)$_GET['restaurant_id'] : 0;

if (!$restaurant_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Restaurant ID is required']);
    exit();
}

try {
    // Fetch tables from database
    $stmt = $pdo->prepare("
        SELECT table_no, status, is_occupied 
        FROM restaurant_tables 
        WHERE restaurant_id = ? 
        ORDER BY table_no
    ");
    $stmt->execute([$restaurant_id]);
    $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($tables);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}