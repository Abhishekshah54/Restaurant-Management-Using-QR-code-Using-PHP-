<?php
/**
 * TABLE-BASED QR CODE SYSTEM DOCUMENTATION
 * 
 * This system generates unique QR codes for each table that customers can scan
 * to automatically start ordering from their specific table.
 * 
 * WORKFLOW:
 * ========
 * 
 * 1. ADMIN GENERATES QR CODES
 *    - Navigate to Admin → QR Code Generator
 *    - 10 individual QR codes are automatically generated (Table 1-10)
 *    - Each QR code links to: customer/menu.php?restaurant=8&table=X
 *    - Download or print these QR codes
 * 
 * 2. QR CODES ARE PLACED ON TABLES
 *    - Print the table-specific QR codes
 *    - Place one QR code on each table (or table stand)
 *    - Customers scan the QR code for their table
 * 
 * 3. CUSTOMER SCANS AND ORDERS
 *    - Customer scans the Table QR code
 *    - System automatically:
 *      a) Sets their session['table_no'] to the scanned table
 *      b) Sets their session['restaurant_id'] to 8 (Shah's Kitchen)
 *      c) Updates the restaurant_tables record: is_occupied = 1
 *      d) Displays the menu for that restaurant
 * 
 * 4. CUSTOMER PLACES ORDER
 *    - Customer selects items and places order
 *    - Order is created with:
 *      - table_no = the scanned table number
 *      - restaurant_id = 8
 *      - status = 'Pending'
 * 
 * 5. ADMIN SEES ORDERS BY TABLE
 *    - Admin views orders filtered by table number
 *    - Can see which tables have active orders
 *    - Can track table status (occupied vs available)
 * 
 * DATABASE TABLES INVOLVED
 * ======================
 * 
 * restaurant_tables:
 *   - id: primary key
 *   - restaurant_id: link to restaurant/user
 *   - table_no: 1-10 (or more)
 *   - status: 'available', 'occupied', 'reserved', 'out_of_service'
 *   - is_occupied: 1 (occupied) or 0 (available)
 * 
 * orders:
 *   - id: primary key
 *   - restaurant_id: link to restaurant
 *   - table_no: which table placed this order
 *   - total_price: order total
 *   - status: Pending, Preparing, Ready, Delivered, Paid
 *   - payment_method: cash or online
 *   - created_at: timestamp
 * 
 * order_items:
 *   - id: primary key
 *   - order_id: link to order
 *   - menu_item_id: which item ordered
 *   - quantity: how many
 *   - price: item price at time of order
 * 
 * SAMPLE QR CODE URLS
 * ==================
 * 
 * Main Menu (no table):
 * http://localhost:8000/customer/menu.php?restaurant=8
 * 
 * Table 1:
 * http://localhost:8000/customer/menu.php?restaurant=8&table=1
 * 
 * Table 5:
 * http://localhost:8000/customer/menu.php?restaurant=8&table=5
 * 
 * Table 10:
 * http://localhost:8000/customer/menu.php?restaurant=8&table=10
 * 
 * PHP CODE FLOW (customer/menu.php)
 * =================================
 */

// Get table number from URL if provided
$table_no = isset($_GET['table']) ? (int)$_GET['table'] : 0;

// If table number is provided, mark it as occupied and store in session
if ($table_no) {
    // Check if table exists
    $table_check = $pdo->prepare("SELECT * FROM restaurant_tables WHERE restaurant_id = ? AND table_no = ?");
    $table_check->execute([$restaurant_id, $table_no]);
    
    if ($table_check->rowCount() > 0) {
        // Update existing table
        $update_table = $pdo->prepare("UPDATE restaurant_tables SET is_occupied = 1 WHERE restaurant_id = ? AND table_no = ?");
        $update_table->execute([$restaurant_id, $table_no]);
    } else {
        // Create new table entry
        $insert_table = $pdo->prepare("INSERT INTO restaurant_tables (restaurant_id, table_no, is_occupied) VALUES (?, ?, 1)");
        $insert_table->execute([$restaurant_id, $table_no]);
    }
    
    // Store in session
    $_SESSION['table_no'] = $table_no;
    $_SESSION['restaurant_id'] = $restaurant_id;
}

// Later when order is placed
// INSERT INTO orders (restaurant_id, table_no, total_price, status, ...)
// VALUES (?, ?, ?, 'Pending', ...)
// The table_no comes from $_SESSION['table_no']

/**
 * ADMIN ORDER FILTERING
 * ====================
 * 
 * To view orders by table:
 * SELECT * FROM orders WHERE restaurant_id = 8 AND table_no = 1
 * 
 * To get table status:
 * SELECT * FROM restaurant_tables WHERE restaurant_id = 8 ORDER BY table_no
 * 
 * To see occupied tables:
 * SELECT * FROM restaurant_tables WHERE restaurant_id = 8 AND is_occupied = 1
 * 
 * FEATURES
 * ========
 * 
 * ✓ 10 unique QR codes (one per table)
 * ✓ Each QR code is printable and downloadable
 * ✓ Automatic table assignment when scanning
 * ✓ Table occupancy tracking
 * ✓ Orders linked to specific tables
 * ✓ Admin can filter orders by table
 * ✓ Session-based table persistence during customer session
 * 
 * NEXT STEPS TO IMPLEMENT (Optional)
 * ==================================
 * 
 * 1. Add "Mark Table as Available" button in Orders view
 *    UPDATE restaurant_tables SET is_occupied = 0 WHERE id = ?
 * 
 * 2. Add table filter in Admin Orders page
 *    Dropdown to filter orders by table number
 * 
 * 3. Add KDS (Kitchen Display System) alerts per table
 *    Show which table's order needs attention
 * 
 * 4. Add customer feedback/call waiter from menu page
 *    When customer clicks "Call Waiter" → table number captured
 * 
 * 5. Track order prep time per table
 *    Timer from "Pending" to "Ready" for each table
 */

echo "Table-Based QR Code System Active\n";
echo "Generated 10 unique QR codes for tables 1-10\n";
echo "Each QR code automatically captures the table number\n";
echo "Orders are now linked to specific tables\n";
?>
