<?php
/**
 * =============================================================================
 * TABLE-BASED QR CODE SYSTEM - TECHNICAL CODE IMPLEMENTATION
 * =============================================================================
 * 
 * This file documents the actual PHP code that implements the table QR system
 */

/**
 * ============================================================================
 * PART 1: QR CODE GENERATION (admin/qr_generator.php)
 * ============================================================================
 */

// Generate QR code using external API
function generateQRCode($text, $size = 300) {
    $url = "https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&data=" . urlencode($text);
    $qrCode = @file_get_contents($url);
    return $qrCode ? $qrCode : false;
}

// Get restaurant ID from admin session
$restaurant_id = $_SESSION['user_id']; // = 8 for Shah's Kitchen

// Generate individual table QR codes
$tableQRCodes = [];
for ($table_no = 1; $table_no <= 10; $table_no++) {
    $table_url = $menu_url . "&table=" . $table_no;
    // Example URL: http://localhost:8000/customer/menu.php?restaurant=8&table=1
    
    $table_filename = "restaurant_{$restaurant_id}_table_{$table_no}_qrcode.png";
    $tableQRImage = generateQRCode($table_url);
    
    if ($tableQRImage) {
        // Save to /assets/qrcodes/
        file_put_contents($qrCodeDir . $table_filename, $tableQRImage);
        $tableQRCodes[$table_no] = [
            'filename' => $table_filename,
            'url' => $table_url,
            'exists' => true
        ];
    }
}

// Output in HTML
echo <<<HTML
<div class="qr-grid">
    <? for ($table_no = 1; $table_no <= 10; $table_no++): ?>
        <div class="qr-card">
            <div class="qr-card-header">
                <h3 class="qr-card-title">
                    <i class="fas fa-chair"></i> Table {$table_no}
                </h3>
            </div>
            <img src="../assets/qrcodes/restaurant_8_table_{$table_no}_qrcode.png" />
            <a href="../assets/qrcodes/restaurant_8_table_{$table_no}_qrcode.png" download>
                Download
            </a>
        </div>
    <? endfor; ?>
</div>
HTML;

/**
 * ============================================================================
 * PART 2: CUSTOMER TABLE DETECTION (customer/menu.php)
 * ============================================================================
 */

// Get table number from URL parameter (set by QR code)
$table_no = isset($_GET['table']) ? (int)$_GET['table'] : 0;

if ($table_no) {
    // When customer scans QR code: ...&table=5
    
    // Step 1: Update table status in database
    $table_check = $pdo->prepare(
        "SELECT * FROM restaurant_tables 
         WHERE restaurant_id = ? AND table_no = ?"
    );
    $table_check->execute([$restaurant_id, $table_no]);
    
    if ($table_check->rowCount() > 0) {
        // Table exists, mark as occupied
        $update_table = $pdo->prepare(
            "UPDATE restaurant_tables 
             SET is_occupied = 1 
             WHERE restaurant_id = ? AND table_no = ?"
        );
        $update_table->execute([$restaurant_id, $table_no]);
    } else {
        // Table doesn't exist, create it
        $insert_table = $pdo->prepare(
            "INSERT INTO restaurant_tables 
             (restaurant_id, table_no, is_occupied) 
             VALUES (?, ?, 1)"
        );
        $insert_table->execute([$restaurant_id, $table_no]);
    }
    
    // Step 2: Store table info in PHP session
    $_SESSION['table_no'] = $table_no;        // = 5
    $_SESSION['restaurant_id'] = $restaurant_id; // = 8
}

// Get table from session if available
elseif (isset($_SESSION['table_no']) && isset($_SESSION['restaurant_id']) 
        && $_SESSION['restaurant_id'] == $restaurant_id) {
    $table_no = $_SESSION['table_no'];
}

/**
 * ============================================================================
 * PART 3: ORDER CREATION WITH TABLE NUMBER (place_order.php)
 * ============================================================================
 */

// When customer places order
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    
    // Get cart total
    $total_price = 0;
    foreach ($_SESSION['cart'] as $item) {
        $total_price += $item['price'] * $item['quantity'];
    }
    
    // Get table number from session
    $table_no = $_SESSION['table_no'] ?? 0;
    $restaurant_id = $_SESSION['restaurant_id'] ?? 0;
    $payment_method = $_POST['payment_method'] ?? 'cash';
    
    // Create order WITH table number
    $stmt = $pdo->prepare(
        "INSERT INTO orders 
         (restaurant_id, table_no, total_price, status, payment_method, is_active, created_at, updated_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );
    
    $now = date('Y-m-d H:i:s');
    $result = $stmt->execute([
        $restaurant_id,      // 8 (Shah's Kitchen)
        $table_no,           // 5 (Table 5)
        $total_price,        // 370.00
        'Pending',
        $payment_method,     // 'cash'
        1,
        $now,
        $now
    ]);
    
    if ($result) {
        $order_id = $pdo->lastInsertId(); // Get order ID
        
        // Add items to order
        $itemStmt = $pdo->prepare(
            "INSERT INTO order_items 
             (order_id, menu_item_id, quantity, price, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        
        foreach ($_SESSION['cart'] as $item) {
            $itemStmt->execute([
                $order_id,
                $item['id'],
                $item['quantity'],
                $item['price'],
                $now,
                $now
            ]);
        }
        
        // Order created with table number!
        // Admin can now see: Order #25, Table 5, Shah's Kitchen
    }
}

/**
 * ============================================================================
 * PART 4: ADMIN ORDER FILTERING BY TABLE (admin/orders.php)
 * ============================================================================
 */

// Get orders for current restaurant
$stmt = $pdo->prepare(
    "SELECT o.*, 
            GROUP_CONCAT(mi.name SEPARATOR ', ') as items
     FROM orders o
     LEFT JOIN order_items oi ON o.id = oi.order_id
     LEFT JOIN menu_items mi ON oi.menu_item_id = mi.id
     WHERE o.restaurant_id = ?
     GROUP BY o.id
     ORDER BY o.created_at DESC"
);
$stmt->execute([$restaurant_id]); // = 8
$orders = $stmt->fetchAll();

// Now $orders contains:
// Array [
//   0 => [
//     'id' => 25,
//     'restaurant_id' => 8,
//     'table_no' => 5,          // <-- Table number!
//     'total_price' => 370.00,
//     'status' => 'Pending',
//     'items' => 'Biryani, Raita'
//   ]
// ]

// Display table filter (optional)
$table_filter = $_GET['table'] ?? null;
if ($table_filter) {
    // Filter orders by table
    $orders = array_filter($orders, function($order) use ($table_filter) {
        return $order['table_no'] == $table_filter;
    });
}

// HTML to display
foreach ($orders as $order) {
    echo "
    <div class='order-card'>
        <h3>Order #{$order['id']}</h3>
        <p>Table: {$order['table_no']}</p>
        <p>Items: {$order['items']}</p>
        <p>Total: ₹{$order['total_price']}</p>
        <p>Status: {$order['status']}</p>
    </div>
    ";
}

/**
 * ============================================================================
 * PART 5: TABLE OCCUPANCY TRACKING (admin/tables.php)
 * ============================================================================
 */

// Get all tables for restaurant
$stmt = $pdo->prepare(
    "SELECT * FROM restaurant_tables 
     WHERE restaurant_id = ?
     ORDER BY table_no"
);
$stmt->execute([$restaurant_id]); // = 8
$tables = $stmt->fetchAll();

// Display table status
foreach ($tables as $table) {
    $status = $table['is_occupied'] ? 'Occupied' : 'Available';
    $color = $table['is_occupied'] ? 'red' : 'green';
    
    echo "
    <div class='table-card' style='background-color: {$color}'>
        <h4>Table {$table['table_no']}</h4>
        <p>Status: {$status}</p>
        <button onclick='markAvailable({$table['id']})'>Mark Available</button>
    </div>
    ";
}

// Mark table as available (after customer leaves)
function markAvailable($table_id) {
    global $pdo;
    $stmt = $pdo->prepare(
        "UPDATE restaurant_tables SET is_occupied = 0 WHERE id = ?"
    );
    $stmt->execute([$table_id]);
}

/**
 * ============================================================================
 * DATABASE SCHEMA CHANGES
 * ============================================================================
 */

// No schema changes needed!
// Table columns already exist:
// 
// restaurant_tables:
//   - id
//   - restaurant_id
//   - table_no
//   - status
//   - is_occupied
//
// orders:
//   - id
//   - restaurant_id
//   - table_no        <-- Already exists, now being used!
//   - total_price
//   - status
//   - payment_method
//   - created_at
//   - updated_at
//
// order_items:
//   - id
//   - order_id
//   - menu_item_id
//   - quantity
//   - price
//   - created_at
//   - updated_at

/**
 * ============================================================================
 * SQL QUERIES USED
 * ============================================================================
 */

// 1. Create or update table (when customer scans QR)
// UPDATE restaurant_tables SET is_occupied = 1 WHERE restaurant_id = 8 AND table_no = 5;
// OR
// INSERT INTO restaurant_tables (restaurant_id, table_no, is_occupied) VALUES (8, 5, 1);

// 2. Create order with table
// INSERT INTO orders (restaurant_id, table_no, total_price, status, payment_method, is_active, created_at, updated_at) 
// VALUES (8, 5, 370.00, 'Pending', 'cash', 1, NOW(), NOW());

// 3. Add order items
// INSERT INTO order_items (order_id, menu_item_id, quantity, price, created_at, updated_at) 
// VALUES (25, 1, 1, 250.00, NOW(), NOW());

// 4. Get orders for table
// SELECT * FROM orders WHERE restaurant_id = 8 AND table_no = 5 ORDER BY created_at DESC;

// 5. Get occupied tables
// SELECT * FROM restaurant_tables WHERE restaurant_id = 8 AND is_occupied = 1;

// 6. Mark table as available
// UPDATE restaurant_tables SET is_occupied = 0 WHERE restaurant_id = 8 AND table_no = 5;

/**
 * ============================================================================
 * WORKFLOW SUMMARY
 * ============================================================================
 *
 * 1. QR Generation:
 *    - Admin visits qr_generator.php
 *    - 10 QR codes generated for tables 1-10
 *    - URLs: .../menu.php?restaurant=8&table=X
 *    - Files saved: /assets/qrcodes/restaurant_8_table_X_qrcode.png
 *
 * 2. Customer Scan:
 *    - Customer scans QR for Table 5
 *    - Browser opens: ...menu.php?restaurant=8&table=5
 *    - $_GET['table'] = 5 captured
 *
 * 3. Table Detection:
 *    - customer/menu.php processes $_GET['table']
 *    - Updates database: is_occupied = 1 for table 5
 *    - Session stored: $_SESSION['table_no'] = 5
 *
 * 4. Menu Display:
 *    - Menu items display for restaurant 8
 *    - Table 5 context maintained in session
 *
 * 5. Order Placement:
 *    - Customer selects items
 *    - place_order.php creates order with:
 *      - table_no = 5 (from session)
 *      - restaurant_id = 8 (from session)
 *
 * 6. Admin View:
 *    - admin/orders.php displays all orders
 *    - Shows: Order ID, Table Number, Items, Status
 *    - Can filter by table or see all
 *
 * 7. Table Management:
 *    - admin/tables.php shows occupancy
 *    - Green = Available, Red = Occupied
 *    - Admins can mark as available when customer leaves
 *
 * 8. Order Completion:
 *    - Admin updates status: Pending → Preparing → Ready → Delivered → Paid
 *    - Table marked as available after payment
 *    - New customers can use table
 */

?>
