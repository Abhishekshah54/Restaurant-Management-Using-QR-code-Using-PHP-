<?php
require_once __DIR__ . '/../config/db.php';

try {
    // Find a restaurant/user to attach menu items
    $stmt = $pdo->query("SELECT id, restaurant_name FROM users ORDER BY id ASC LIMIT 1");
    $restaurant = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$restaurant) {
        echo "No users found. Please register an admin/restaurant first.\n";
        exit(1);
    }

    $restaurantId = (int)$restaurant['id'];

    // Ensure image directory exists
    $imageDir = __DIR__ . '/../assets/images/menu_items/';
    if (!is_dir($imageDir)) {
        if (!mkdir($imageDir, 0755, true)) {
            throw new RuntimeException("Failed to create image directory: $imageDir");
        }
    }

    // Create simple SVG placeholder images
    $images = [
        'classic_burger.svg' => ['#F4A261', 'Classic Burger'],
        'margherita_pizza.svg' => ['#E76F51', 'Margherita Pizza'],
        'grilled_pasta.svg' => ['#2A9D8F', 'Grilled Pasta'],
        'caesar_salad.svg' => ['#264653', 'Caesar Salad'],
        'chocolate_mousse.svg' => ['#6C757D', 'Chocolate Mousse']
    ];

    foreach ($images as $file => [$color, $label]) {
        $svg = "<svg xmlns='http://www.w3.org/2000/svg' width='600' height='400'>" .
               "<rect width='100%' height='100%' fill='{$color}'/>" .
               "<text x='50%' y='50%' font-size='36' fill='#ffffff' text-anchor='middle' dominant-baseline='middle' font-family='Arial, sans-serif'>{$label}</text>" .
               "</svg>";
        file_put_contents($imageDir . $file, $svg);
    }

    // Insert dummy menu items
    $items = [
        ['Main Course', 'Classic Burger', 'Juicy beef patty with lettuce, tomato, cheese, and house sauce.', 8.99, 'classic_burger.svg'],
        ['Pizza', 'Margherita Pizza', 'Fresh tomatoes, mozzarella, basil, and olive oil.', 11.50, 'margherita_pizza.svg'],
        ['Pasta', 'Grilled Pasta', 'Penne pasta with grilled vegetables and pesto.', 10.25, 'grilled_pasta.svg'],
        ['Salads', 'Caesar Salad', 'Crisp romaine lettuce with parmesan and croutons.', 7.75, 'caesar_salad.svg'],
        ['Dessert', 'Chocolate Mousse', 'Rich chocolate mousse with whipped cream.', 6.50, 'chocolate_mousse.svg']
    ];

    $insert = $pdo->prepare("INSERT INTO menu_items (restaurant_id, category, name, description, price, image) VALUES (?, ?, ?, ?, ?, ?)");

    foreach ($items as $item) {
        $insert->execute([$restaurantId, $item[0], $item[1], $item[2], $item[3], $item[4]]);
    }

    echo "Seeded " . count($items) . " menu items for restaurant ID {$restaurantId} ({$restaurant['restaurant_name']}).\n";
    echo "Images saved in assets/images/menu_items.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
