<?php
require_once __DIR__ . '/../config/db.php';

try {
    // Find a restaurant/user
    $stmt = $pdo->query("SELECT id, restaurant_name FROM users ORDER BY id ASC LIMIT 1");
    $restaurant = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$restaurant) {
        echo "No users found. Please register an admin/restaurant first.\n";
        exit(1);
    }

    $restaurantId = (int)$restaurant['id'];

    // Add tables (1-10)
    echo "Adding tables...\n";
    $tableInsert = $pdo->prepare("INSERT IGNORE INTO restaurant_tables (restaurant_id, table_no, status, is_occupied) VALUES (?, ?, ?, ?)");
    
    $tableStatuses = ['available', 'available', 'available', 'available', 'occupied', 'available', 'occupied', 'available', 'available', 'reserved'];
    $tableOccupied = [0, 0, 0, 0, 1, 0, 1, 0, 0, 0];

    for ($i = 1; $i <= 10; $i++) {
        $tableInsert->execute([
            $restaurantId,
            $i,
            $tableStatuses[$i - 1],
            $tableOccupied[$i - 1]
        ]);
    }
    echo "Added 10 tables.\n";

    // Get menu items
    $stmt = $pdo->prepare("SELECT id, price FROM menu_items WHERE restaurant_id = ? ORDER BY RAND()");
    $stmt->execute([$restaurantId]);
    $menuItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($menuItems) < 3) {
        echo "Need at least 3 menu items. Found " . count($menuItems) . ".\n";
        exit(1);
    }

    // Create sample orders
    echo "Adding orders...\n";
    $orderInsert = $pdo->prepare("INSERT INTO orders (restaurant_id, table_no, total_price, status, notes, payment_method, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $orderItemInsert = $pdo->prepare("INSERT INTO order_items (order_id, menu_item_id, quantity, price, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)");

    $statuses = ['Pending', 'Preparing', 'Ready', 'Delivered', 'Paid'];
    $paymentMethods = ['cash', 'online'];
    $tables = [1, 2, 3, 4, 5];

    for ($o = 0; $o < 5; $o++) {
        $tableNo = $tables[$o];
        $status = $statuses[$o];
        $isActive = ($status === 'Pending' || $status === 'Preparing' || $status === 'Ready') ? 1 : 0;
        $paymentMethod = $paymentMethods[$o % 2];
        
        $itemCount = rand(2, 4);
        $totalPrice = 0;
        $selectedItems = [];
        
        for ($i = 0; $i < $itemCount; $i++) {
            $item = $menuItems[$i];
            $qty = rand(1, 3);
            $totalPrice += $item['price'] * $qty;
            $selectedItems[] = ['id' => $item['id'], 'qty' => $qty, 'price' => $item['price']];
        }

        $now = date('Y-m-d H:i:s');
        $orderResult = $orderInsert->execute([
            $restaurantId,
            $tableNo,
            $totalPrice,
            $status,
            rand(0, 1) ? 'No onions, extra sauce' : '',
            $paymentMethod,
            $isActive,
            $now,
            $now
        ]);

        if ($orderResult) {
            $orderId = $pdo->lastInsertId();
            foreach ($selectedItems as $item) {
                $orderItemInsert->execute([
                    $orderId,
                    $item['id'],
                    $item['qty'],
                    $item['price'],
                    $now,
                    $now
                ]);
            }
            echo "Order #$orderId (Table $tableNo): $status - \$" . number_format($totalPrice, 2) . "\n";
        }
    }

    echo "\nSeeding complete!\n";
    echo "Restaurant: {$restaurant['restaurant_name']} (ID: $restaurantId)\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
