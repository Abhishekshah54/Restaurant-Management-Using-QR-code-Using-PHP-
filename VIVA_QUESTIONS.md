# RESTAURANT MANAGEMENT USING QR CODE - VIVA QUESTIONS

**Project**: Table-Based QR Code Restaurant Management System  
**Institution**: Academic Project  
**Date**: February 12, 2026  
**Restaurant**: Shah's Kitchen (ID: 8)  

---

## TABLE OF CONTENTS

1. [Project Overview Questions](#project-overview-questions)
2. [System Architecture Questions](#system-architecture-questions)
3. [Database Design Questions](#database-design-questions)
4. [QR Code Implementation Questions](#qr-code-implementation-questions)
5. [Customer Workflow Questions](#customer-workflow-questions)
6. [Admin Panel Questions](#admin-panel-questions)
7. [Technical Implementation Questions](#technical-implementation-questions)
8. [Security & Database Questions](#security--database-questions)
9. [User Interface & Experience Questions](#user-interface--experience-questions)
10. [Troubleshooting & Maintenance Questions](#troubleshooting--maintenance-questions)

---

## PROJECT OVERVIEW QUESTIONS

### 1. What is the purpose of your project?
**Expected Answer:**  
The project is a web-based restaurant management system that enables contactless dining through QR code technology. Customers scan table-specific QR codes to access the digital menu and place orders without manual intervention. The system automates table occupancy tracking, order management, and provides real-time visibility to administrators.

**Key Points:**
- Contactless ordering system
- QR code-based table detection
- Real-time order tracking
- Automated table occupancy management
- Admin dashboard for order management

---

### 2. What are the main objectives of the system?
**Expected Answer:**
- Reduce ordering time and manual errors
- Provide contactless ordering experience
- Enable real-time table status tracking
- Automate order linking to tables
- Simplify admin operations
- Improve customer experience

---

### 3. Who are the primary users of your system?
**Expected Answer:**
1. **Customers**: Scan QR codes, view menu, place orders, track order status
2. **Admin/Restaurant Manager**: Generate QR codes, manage menu, monitor orders, track tables, process payments
3. **Kitchen Staff**: View orders (KDS - Kitchen Display System)

---

### 4. What problem does your system solve?
**Expected Answer:**
- Eliminates manual table entry by customers
- Reduces contact points during ordering
- Provides real-time order tracking
- Automates table occupancy management
- Reduces paper-based ordering
- Minimizes ordering errors and wait time

---

### 5. What are the key features of your system?
**Expected Answer:**
- QR code generation for 10 tables
- Automatic table detection via QR scan
- Digital menu with categorized items
- Real-time order placement and tracking
- Admin dashboard for orders and tables
- Table occupancy management
- Order status workflow (Pending → Preparing → Ready → Delivered → Paid)
- Bill generation and payment tracking

---

## SYSTEM ARCHITECTURE QUESTIONS

### 6. Describe the overall architecture of your system.
**Expected Answer:**
The system follows an MVC-like architecture with three main components:

1. **Frontend (Presentation Layer)**:
   - HTML5/CSS3 for responsive design
   - JavaScript for dynamic interactions
   - Separate interfaces for customers and admin

2. **Backend (Business Logic Layer)**:
   - PHP 7.4+ for server-side processing
   - Session management for user state
   - CRUD operations for orders and menu items
   - QR code generation via external API

3. **Database Layer**:
   - MySQL 5.7+ database
   - PDO (PHP Data Objects) for database abstraction
   - Prepared statements for security

---

### 7. How does the QR code link to the menu?
**Expected Answer:**
1. Each table has a unique QR code
2. QR code encodes URL: `http://localhost/Restaurant-Management-Using-QR-code-Using-PHP-/customer/menu.php?restaurant=8&table=X`
3. When scanned:
   - Browser opens the URL
   - PHP script reads `?table=X` parameter
   - System loads menu for that specific table
   - Table number stored in `$_SESSION['table_no']`

---

### 8. What is the workflow when a customer scans a QR code?
**Expected Answer:**
1. Customer sits at Table 5
2. Scans QR code using phone camera
3. Browser opens the encoded URL
4. `customer/menu.php` receives `?restaurant=8&table=5`
5. System validates parameters
6. Updates `restaurant_tables` to mark table as occupied
7. Stores table number in session
8. Displays menu with table context
9. Customer browses items and adds to cart
10. Places order → Order is linked to Table 5
11. Admin can see order for Table 5

---

### 9. How is table status managed in the system?
**Expected Answer:**
1. **Table Occupancy Tracking**:
   - When customer scans QR: `UPDATE restaurant_tables SET is_occupied = 1`
   - When bill is paid: `UPDATE restaurant_tables SET is_occupied = 0`

2. **Table States**:
   - `available`: Table ready for customers
   - `occupied`: Customer seated and ordering
   - `reserved`: Table reserved for future
   - `out_of_service`: Table not available

3. **Admin View**: Dashboard shows all tables with color-coded status

---

### 10. What is the payment workflow in the system?
**Expected Answer:**
1. Customer places order
2. Admin receives order in orders page
3. Kitchen prepares food (status: Preparing)
4. Ready for pickup (status: Ready)
5. Customer receives order (status: Delivered)
6. Admin generates bill in bill.php
7. Customer pays (cash or online)
8. Admin marks as Paid
9. Table marked as available for next customer

---

## DATABASE DESIGN QUESTIONS

### 11. Explain the database schema for your project.
**Expected Answer:**
The database consists of 5 main tables:

1. **users**
   - Stores admin/restaurant manager credentials
   - Fields: id, name, email, password, restaurant_name, address, logo

2. **menu_items**
   - Stores restaurant menu items
   - Fields: id, restaurant_id, category, name, description, price, image

3. **orders**
   - Stores customer orders
   - Fields: id, restaurant_id, table_no, total_price, status, payment_method, is_active, created_at, updated_at

4. **order_items**
   - Links orders to menu items
   - Fields: id, order_id, menu_item_id, quantity, price

5. **restaurant_tables**
   - Stores table information
   - Fields: id, restaurant_id, table_no, status, is_occupied

---

### 12. What are the foreign key relationships in your database?
**Expected Answer:**
```
orders → users (restaurant_id)
orders → restaurant_tables (restaurant_id, table_no)
order_items → orders (order_id)
order_items → menu_items (menu_item_id)
menu_items → users (restaurant_id)
restaurant_tables → users (restaurant_id)
```

These relationships ensure data integrity and proper linking between tables.

---

### 13. Why is the `table_no` field important in the `orders` table?
**Expected Answer:**
The `table_no` field is critical because:
- Links each order to a specific table
- Enables filtering orders by table
- Tracks which table's order is being prepared
- Allows staff to deliver food to correct table
- Helps in occupancy tracking
- Enables split billing by table

---

### 14. How is data integrity maintained in the database?
**Expected Answer:**
1. **Foreign Key Constraints**: Prevents orphaned records
2. **Prepared Statements**: Prevents SQL injection
3. **NOT NULL Constraints**: Ensures required fields are filled
4. **UNIQUE Constraints**: Prevents duplicate entries
5. **Data Validation**: PHP-level validation before database insertion
6. **Transactions**: Ensures atomicity of order creation
7. **Indexes**: Improves query performance

---

### 15. Explain the `restaurant_tables` table structure.
**Expected Answer:**
```sql
CREATE TABLE restaurant_tables (
    id INT PRIMARY KEY AUTO_INCREMENT,
    restaurant_id INT NOT NULL,
    table_no INT NOT NULL,
    status ENUM('available','occupied','reserved','out_of_service'),
    is_occupied BOOLEAN DEFAULT 0,
    FOREIGN KEY (restaurant_id) REFERENCES users(id)
);
```

- `table_no`: Unique table number (1-10)
- `status`: Current status of table
- `is_occupied`: Binary flag for quick occupancy check
- Allows tracking of all tables in the restaurant

---

## QR CODE IMPLEMENTATION QUESTIONS

### 16. How are QR codes generated in your system?
**Expected Answer:**
1. Location: `admin/qr_generator.php`
2. Uses external API: `https://api.qrserver.com/v1/create-qr-code/`
3. Process:
   ```php
   $url = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($text);
   $qrCode = file_get_contents($url);
   file_put_contents($filename, $qrCode);
   ```
4. Generates 10 QR codes (one per table)
5. Stores as PNG files in `/assets/qrcodes/`
6. Each QR encodes unique URL for that table

---

### 17. What URL is encoded in each table QR code?
**Expected Answer:**
Format: `http://localhost/Restaurant-Management-Using-QR-code-Using-PHP-/customer/menu.php?restaurant=8&table=X`

Example:
- Table 1: `...menu.php?restaurant=8&table=1`
- Table 5: `...menu.php?restaurant=8&table=5`
- Table 10: `...menu.php?restaurant=8&table=10`

Parameters:
- `restaurant=8`: Restaurant ID (Shah's Kitchen)
- `table=X`: Table number (1-10)

---

### 18. Why do we use an external API for QR generation?
**Expected Answer:**
1. **Advantages**:
   - No need to install QR libraries on server
   - Reduces server dependencies
   - Reliable and maintained service
   - Easy to implement
   - No storage of complex library code

2. **Disadvantages**:
   - Requires internet connection
   - Depends on third-party service availability
   - API rate limiting possible

---

### 19. How are QR codes downloaded and printed?
**Expected Answer:**
1. Admin visits `admin/qr_generator.php`
2. All 10 table QR codes displayed in grid
3. Each QR shows:
   - QR code image
   - Table number label
   - Download button
4. Admin can:
   - Download individual QR as PNG
   - Print all together
   - Print in batches
   - Laminate for durability

---

### 20. What happens if a QR code becomes invalid?
**Expected Answer:**
1. Admin visits QR generator with `?regen=1`
2. System deletes old QR code files
3. Regenerates all 10 QR codes
4. Updates `/assets/qrcodes/` directory
5. New codes ready for printing
6. Old printed codes still work if URL hasn't changed

---

## CUSTOMER WORKFLOW QUESTIONS

### 21. Describe the complete customer journey in your system.
**Expected Answer:**
1. **Arrival**: Customer sits at Table 5
2. **Scan**: Scans QR code on table
3. **Redirect**: Browser opens menu page with table=5
4. **Browse**: Views menu categorized by food type
5. **Select**: Adds items to cart with quantities
6. **Review**: Reviews cart items and total price
7. **Payment**: Selects payment method (cash/online)
8. **Submit**: Places order
9. **Confirmation**: Receives order confirmation
10. **Track**: Can view order status (Pending → Preparing → Ready)
11. **Receive**: Gets food when status is "Ready"
12. **Pay**: Settles bill with admin

---

### 22. How does the system identify which table placed an order?
**Expected Answer:**
1. QR URL contains `?table=5`
2. PHP retrieves: `$table_no = $_GET['table'];`
3. Validated and stored in session: `$_SESSION['table_no'] = 5;`
4. When order placed, `INSERT INTO orders (..., table_no, ...) VALUES (..., 5, ...);`
5. Order permanently linked to Table 5
6. Admin can filter orders by table

---

### 23. What happens if a customer forgets their table number?
**Expected Answer:**
1. Table number is embedded in QR URL
2. Cannot be manually entered or changed
3. If QR lost, customer must ask staff
4. Staff can provide table number
5. Alternative: Admin can manually create order for table
6. Session automatically maintains table throughout ordering

---

### 24. How is the cart managed in the customer interface?
**Expected Answer:**
1. **Storage**: Cart stored in PHP session (`$_SESSION['cart']`)
2. **Operations**:
   - Add item: Increases quantity in session
   - Remove item: Deletes item from session
   - Update quantity: Modifies session value
3. **Display**: Cart shows all items with prices
4. **Total**: Calculated from all items: `SUM(price × quantity)`
5. **Persistence**: Cart maintained until checkout or session expires

---

### 25. What payment methods are supported?
**Expected Answer:**
1. **Cash**: Direct payment at restaurant
2. **Online**: Digital payment (UPI, credit card, etc.)

When placing order:
```php
$payment_method = $_POST['payment_method']; // 'cash' or 'online'
INSERT INTO orders (..., payment_method, ...) VALUES (..., $payment_method, ...);
```

This is stored with order for reference during billing.

---

## ADMIN PANEL QUESTIONS

### 26. What are the main functions available in the admin panel?
**Expected Answer:**
1. **QR Code Management**: Generate, view, download QR codes
2. **Menu Management**: Add, edit, delete menu items with pricing
3. **Order Management**: View all orders, filter by table, update status
4. **Table Management**: View table status, occupancy
5. **Dashboard**: Real-time statistics (pending, preparing, ready, paid orders)
6. **Bill Generation**: Create bills, process payments
7. **Reports**: Order history, sales reports
8. **Profile**: Admin profile and restaurant details

---

### 27. How does the admin view orders by table?
**Expected Answer:**
1. Location: `admin/orders.php`
2. Process:
   ```php
   SELECT * FROM orders 
   WHERE restaurant_id = 8 AND table_no = 5
   ORDER BY created_at DESC;
   ```
3. Display shows:
   - Order ID
   - Table number
   - Items ordered
   - Total amount
   - Current status
4. Admin can filter by table number using dropdown

---

### 28. Describe the order status update workflow.
**Expected Answer:**
1. Admin views orders page
2. For each order, sees status dropdown
3. Clicks to change status:
   - `Pending` → `Preparing` (kitchen started)
   - `Preparing` → `Ready` (food ready)
   - `Ready` → `Delivered` (given to customer)
   - `Delivered` → `Paid` (payment received)
4. AJAX request sent to server
5. Database updated: `UPDATE orders SET status = ? WHERE id = ?`
6. UI updates instantly
7. Order statistics recalculated

---

### 29. How is the table occupancy status managed by admin?
**Expected Answer:**
1. Admin visits `admin/tables.php`
2. See all 10 tables with status:
   - Green: Available
   - Red: Occupied
   - Yellow: Reserved
3. Real-time updates when:
   - Customer scans QR → Marked occupied
   - Bill paid → Marked available
4. Admin can manually:
   - Mark table as available
   - Mark out of service
   - Add notes (e.g., "Table 3: Broken chair")

---

### 30. How does the bill generation work?
**Expected Answer:**
1. Location: `admin/bill.php`
2. Admin selects table number
3. System fetches all active orders for that table:
   ```sql
   SELECT * FROM orders WHERE restaurant_id = 8 AND table_no = 5 AND is_active = 1;
   ```
4. Displays:
   - Order details
   - Items with quantities
   - Item-wise totals
   - Grand total
   - Payment method
5. Admin clicks "Mark as Paid"
6. Order status updated to "Paid"
7. Table marked as available
8. Bill printed if needed

---

## TECHNICAL IMPLEMENTATION QUESTIONS

### 31. Explain how PHP session management works in your system.
**Expected Answer:**
1. **Session Initialization**:
   ```php
   session_start();
   ```

2. **Storing Table Info**:
   ```php
   $_SESSION['table_no'] = 5;
   $_SESSION['restaurant_id'] = 8;
   $_SESSION['user_id'] = 8; // Admin ID
   ```

3. **Accessing Session**:
   ```php
   $table = $_SESSION['table_no']; // Retrieve in any page
   ```

4. **Session Lifespan**:
   - Created when user first visits
   - Persists across page loads
   - Destroyed on logout
   - Browser session ID in cookie

5. **Security**: Session IDs regenerated on login

---

### 32. What is PDO and why is it used in your project?
**Expected Answer:**
PDO = PHP Data Objects

**Purpose**: Database abstraction layer providing:
1. **Security**:
   - Prepared statements prevent SQL injection
   - Parameter binding separates code from data

2. **Flexibility**:
   - Works with multiple database types
   - Easy to switch databases

3. **Usage**:
   ```php
   $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
   $stmt->execute([$order_id]);
   $result = $stmt->fetch(PDO::FETCH_ASSOC);
   ```

4. **Error Handling**:
   - Throws exceptions on errors
   - Can catch and handle gracefully

---

### 33. How are prepared statements used for security?
**Expected Answer:**
**Problem**: SQL Injection
```php
// UNSAFE:
$query = "SELECT * FROM orders WHERE id = " . $_GET['id'];
```

**Solution**: Prepared Statements
```php
// SAFE:
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$_GET['id']]);
```

**Benefits**:
- Parameters kept separate from SQL code
- Database handles escaping
- Prevents injection attacks
- Maintains data integrity

---

### 34. Explain the order creation process from code perspective.
**Expected Answer:**
Location: `customer/place_order.php`

```php
// 1. Get cart from session
$cart = $_SESSION['cart']; // Array of items

// 2. Calculate total
$total_price = 0;
foreach ($cart as $item) {
    $total_price += $item['price'] * $item['quantity'];
}

// 3. Get table from session
$table_no = $_SESSION['table_no'];
$restaurant_id = $_SESSION['user_id'];

// 4. Insert order
$stmt = $pdo->prepare("INSERT INTO orders 
    (restaurant_id, table_no, total_price, status, payment_method, created_at) 
    VALUES (?, ?, ?, 'Pending', ?, NOW())");
$stmt->execute([$restaurant_id, $table_no, $total_price, $payment_method]);
$order_id = $pdo->lastInsertId();

// 5. Insert order items
foreach ($cart as $item) {
    $stmt = $pdo->prepare("INSERT INTO order_items 
        (order_id, menu_item_id, quantity, price) 
        VALUES (?, ?, ?, ?)");
    $stmt->execute([$order_id, $item['id'], $item['quantity'], $item['price']]);
}

// 6. Clear cart
unset($_SESSION['cart']);
```

---

### 35. How does the menu filtering by category work?
**Expected Answer:**
1. **Database Storage**: Each menu item has a `category` field
2. **Categories**: Appetizers, Main Course, Beverages, Desserts, etc.

3. **Filtering Logic**:
   ```php
   if (isset($_GET['category'])) {
       $category = $_GET['category'];
       $stmt = $pdo->prepare("SELECT * FROM menu_items 
           WHERE restaurant_id = ? AND category = ?");
       $stmt->execute([$restaurant_id, $category]);
   }
   ```

4. **Display**: Category buttons/dropdown filter menu items
5. **Persistence**: Category selection stored in URL or session

---

## SECURITY & DATABASE QUESTIONS

### 36. What security measures are implemented in your system?
**Expected Answer:**
1. **SQL Injection Prevention**:
   - Use prepared statements
   - Parameter binding
   - Never concatenate user input in SQL

2. **Authentication**:
   - Login required for admin
   - Password hashing (recommended)
   - Session-based auth
   - Logout functionality

3. **Session Security**:
   - Session ID verification
   - User data isolation
   - Session timeout
   - HTTPS recommended for production

4. **Input Validation**:
   - Check data types
   - Validate required fields
   - Sanitize user inputs
   - HTML encoding for display

5. **Authorization**:
   - Check user role before operations
   - Verify restaurant_id ownership
   - Prevent unauthorized access

---

### 37. How is the admin authentication handled?
**Expected Answer:**
Location: `includes/auth.php`

```php
// Login check:
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

// User data stored in session:
$_SESSION['user_id'] = 8; // Admin ID
$_SESSION['restaurant_name'] = "Shah's Kitchen";

// Logout:
unset($_SESSION['user_id']);
session_destroy();
```

**Security**:
- Session ID in cookie (HTTP-only recommended)
- Regenerate session ID on login
- Timeout on inactivity
- Secure password storage (hashing)

---

### 38. What happens if an unauthorized user tries to access admin panel?
**Expected Answer:**
1. Request comes to protected page (e.g., `admin/orders.php`)
2. Page includes `require_once '../includes/auth.php'`
3. `auth.php` checks:
   ```php
   if (!isset($_SESSION['user_id'])) {
       header("Location: ../login.php");
       exit;
   }
   ```
4. User redirected to login page
5. Cannot access admin functionality without authentication
6. Prevents unauthorized order manipulation

---

### 39. How is data validation performed before inserting into database?
**Expected Answer:**
1. **Server-Side Validation** (PHP):
   ```php
   // Check required fields
   if (empty($_POST['quantity']) || empty($_POST['menu_item_id'])) {
       echo "Invalid data";
       exit;
   }
   
   // Validate data types
   $quantity = (int)$_POST['quantity'];
   $menu_item_id = (int)$_POST['menu_item_id'];
   
   // Check ranges
   if ($quantity <= 0 || $quantity > 100) {
       echo "Invalid quantity";
       exit;
   }
   ```

2. **Database-Level Validation**:
   - NOT NULL constraints
   - FOREIGN KEY constraints
   - ENUM for fixed values
   - DECIMAL for prices

3. **Client-Side Validation** (JavaScript):
   - User feedback before submission
   - Prevents obvious errors
   - Not sufficient alone

---

### 40. Explain the transaction mechanism in order creation.
**Expected Answer:**
```php
try {
    $pdo->beginTransaction();
    
    // 1. Insert order
    $stmt = $pdo->prepare("INSERT INTO orders ...");
    $stmt->execute($orderData);
    $order_id = $pdo->lastInsertId();
    
    // 2. Insert order items
    foreach ($cart_items as $item) {
        $stmt = $pdo->prepare("INSERT INTO order_items ...");
        $stmt->execute($itemData);
    }
    
    // 3. Update table occupancy
    $stmt = $pdo->prepare("UPDATE restaurant_tables SET is_occupied = 1 ...");
    $stmt->execute($tableData);
    
    // 4. Clear cart
    unset($_SESSION['cart']);
    
    // All succeeded - commit
    $pdo->commit();
    
} catch (PDOException $e) {
    // Error occurred - rollback
    $pdo->rollBack();
    echo "Order creation failed";
}
```

**Benefits**:
- All-or-nothing execution
- Prevents partial order creation
- Maintains data consistency
- Automatic rollback on error

---

## USER INTERFACE & EXPERIENCE QUESTIONS

### 41. Describe the customer interface design.
**Expected Answer:**
1. **Menu Page** (`customer/menu.php`):
   - Responsive design (works on mobile)
   - Category filters/tabs
   - Item display with:
     - Item image
     - Name and description
     - Price
     - Quantity selector
     - "Add to Cart" button
   - Prominent cart summary
   - Table number displayed

2. **Cart Page** (`customer/cart.php`):
   - Lists all added items
   - Edit quantities
   - Remove items
   - Subtotal/tax calculation
   - Payment method selection
   - "Place Order" button

3. **Order Confirmation** (`customer/order_success.php`):
   - Order ID
   - Items summary
   - Total amount paid
   - Table number
   - Estimated time
   - "Order More" link

---

### 42. Describe the admin interface design.
**Expected Answer:**
1. **Dashboard** (`admin/dashboard.php`):
   - Statistics cards (pending, preparing, ready, paid)
   - Recent orders list
   - Chart of order status distribution

2. **Orders Page** (`admin/orders.php`):
   - Table view of all orders
   - Columns: Order ID, Table, Items, Total, Status, Time
   - Status dropdown for quick updates
   - Filter by table
   - Delete option
   - View details link

3. **Tables Page** (`admin/tables.php`):
   - Grid/list of 10 tables
   - Color-coded status
   - Click to see orders for that table
   - Occupancy duration

4. **QR Generator** (`admin/qr_generator.php`):
   - Display all 10 QR codes
   - Download individual codes
   - Print all option
   - Regenerate if needed

---

### 43. How is real-time data update achieved for status changes?
**Expected Answer:**
1. **Technology**: AJAX (Asynchronous JavaScript and XML)

2. **Process**:
   ```javascript
   // When admin changes status dropdown
   function updateOrderStatus(selectElement, orderId) {
       const status = selectElement.value;
       
       var xmlhttp = new XMLHttpRequest();
       xmlhttp.onreadystatechange = function() {
           if (this.readyState == 4 && this.status == 200) {
               const data = JSON.parse(this.responseText);
               if (data.success) {
                   // Update UI without page reload
                   updateStatistics();
                   showNotification('Status updated successfully');
               }
           }
       };
       
       xmlhttp.open("POST", "", true);
       xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
       xmlhttp.send("update_status=1&order_id=" + orderId + "&status=" + status);
   }
   ```

3. **Benefits**:
   - No page reload required
   - Instant feedback to user
   - Better user experience
   - Reduced server load

---

### 44. What CSS framework or techniques are used for responsive design?
**Expected Answer:**
1. **Responsive Techniques**:
   - CSS Flexbox for layouts
   - Media queries for mobile adaptability
   - Viewport meta tag for mobile browsers
   - Relative units (%, em, rem) instead of fixed pixels

2. **Breakpoints**:
   - Desktop: > 1024px
   - Tablet: 768px - 1024px
   - Mobile: < 768px

3. **Components**:
   - Sidebar collapses on mobile
   - Table becomes scrollable
   - Buttons resize appropriately
   - Font sizes adjust

---

### 45. How is the cart displayed and updated on the customer interface?
**Expected Answer:**
1. **Display**:
   - Small cart icon in header showing item count
   - Click to expand cart details
   - Items listed with:
     - Item name and price
     - Quantity
     - Item total (qty × price)
     - Remove button

2. **Updates** (Real-time):
   ```javascript
   // When adding item
   cart.push({id: itemId, name: name, price: price, qty: 1});
   updateCartDisplay();
   
   // Update cart summary
   totalItems = cart.reduce((sum, item) => sum + item.qty, 0);
   totalPrice = cart.reduce((sum, item) => sum + (item.price * item.qty), 0);
   
   // Display in header
   document.getElementById('cart-count').textContent = totalItems;
   document.getElementById('cart-total').textContent = totalPrice.toFixed(2);
   ```

3. **Persistence**:
   - Data in session on server
   - Not lost on page refresh
   - Cleared after order placed

---

## TROUBLESHOOTING & MAINTENANCE QUESTIONS

### 46. What if a QR code doesn't work after scanning?
**Expected Answer:**
**Troubleshooting Steps**:
1. **Check URL**: Verify QR code contains correct URL format
2. **Network**: Ensure internet connection is available
3. **Server**: Confirm PHP server is running (`php -S localhost:8000`)
4. **File Exists**: Check if menu.php file is accessible
5. **Browser**: Try different browser (Firefox, Chrome, Safari)
6. **Manual Test**: Open URL directly in browser
7. **Regenerate**: If failed, regenerate QR codes
8. **Check Logs**: Review server error logs

**Fix Options**:
- Regenerate QR code with ?regen=1
- Check server logs for errors
- Verify database connection
- Test with different device

---

### 47. How would you handle database connection failures?
**Expected Answer:**
```php
try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Log error securely
    error_log("Database connection failed: " . $e->getMessage());
    
    // Display user-friendly message
    die("System error: Unable to connect to database. Please try again later.");
}
```

**Prevention**:
- Check database credentials
- Verify MySQL service is running
- Check network connectivity
- Verify database exists
- Check user permissions

---

### 48. What if a customer's session expires during ordering?
**Expected Answer:**
1. **Timeout Configuration**:
   ```php
   ini_set('session.gc_maxlifetime', 3600); // 1 hour
   ```

2. **On Expiry**:
   - Customer's session destroyed
   - Table data lost
   - Cart cleared
   - Next page access redirects to login

3. **Recovery**:
   - Customer scans QR again
   - New session created with table info
   - Needs to rebuild cart
   - Previously added items can be easily re-added

4. **Prevention**:
   - Long session timeout for customers (6+ hours)
   - Auto-save cart periodically
   - Warn before expiry
   - Cookies for persistent cart (consider privacy)

---

### 49. How would you scale this system for multiple restaurants?
**Expected Answer:**
**Current Limitation**: Restaurant ID hardcoded to 8 (Shah's Kitchen)

**To Support Multiple Restaurants**:

1. **Database Changes**:
   - Store restaurant_id dynamically
   - Multiple restaurants in users table
   - Multi-tenancy architecture

2. **URL Changes**:
   ```
   http://localhost/shop/restaurant_8/menu.php
   http://localhost/shop/restaurant_12/menu.php
   ```

3. **Session Management**:
   ```php
   $_SESSION['restaurant_id'] = $restaurant_id; // Dynamic
   $_SESSION['user_id'] = $admin_id;
   ```

4. **Queries**:
   - All queries filter by restaurant_id
   - Each admin sees only their data
   - Complete data isolation

5. **Admin Setup**:
   - Multi-restaurant dashboard
   - Select restaurant to manage
   - Generate QR for selected restaurant

---

### 50. What are the recommended improvements for future versions?
**Expected Answer:**
1. **Feature Enhancements**:
   - Call Waiter button on menu
   - Table reservation system
   - Split billing functionality
   - Customer feedback/ratings
   - Loyalty program

2. **Technical Improvements**:
   - Mobile app development
   - Push notifications for order updates
   - Real-time WebSocket for live updates
   - Payment gateway integration
   - SMS/Email notifications

3. **Performance**:
   - Database query optimization
   - Caching implementation (Redis)
   - CDN for static files
   - Load balancing for multiple servers

4. **Security**:
   - SSL/HTTPS implementation
   - Two-factor authentication
   - Password strength requirements
   - Regular security audits
   - Data encryption

5. **User Experience**:
   - Multi-language support
   - Accessibility improvements
   - Voice-based ordering
   - Augmented reality menu
   - AI-based recommendations

---

## ADVANCED TECHNICAL QUESTIONS

### 51. Explain how data flows from customer to database.
**Expected Answer:**
```
Customer Action → JavaScript Event → AJAX Request → PHP Processing 
→ Database Query → Response JSON → JavaScript Update → UI Display
```

**Example - Adding Item to Cart**:
1. Customer clicks "Add to Cart" button
2. JavaScript captures click
3. AJAX POST request sent with: `item_id=5&quantity=2`
4. PHP processes: `$_POST['item_id']`, `$_POST['quantity']`
5. Validation performed
6. Item added to session: `$_SESSION['cart'][] = $data`
7. JSON response returned: `{"success": true, "cart_count": 3}`
8. JavaScript receives response
9. Updates cart display
10. Shows success notification

---

### 52. How does the system prevent duplicate orders?
**Expected Answer:**
1. **Order Uniqueness**:
   - Each order has unique `id` (PRIMARY KEY, AUTO_INCREMENT)
   - Created once per submission
   - Cannot be accidentally submitted twice

2. **Prevention Mechanisms**:
   ```php
   // In place_order.php
   if (empty($_SESSION['cart'])) {
       echo "Cart is empty!";
       exit;
   }
   
   // Prevent resubmission with token
   if (isset($_SESSION['order_submitted'])) {
       echo "Order already submitted!";
       exit;
   }
   
   // Insert order
   $pdo->beginTransaction();
   // ... insert operations ...
   $pdo->commit();
   
   // Mark as submitted
   $_SESSION['order_submitted'] = true;
   unset($_SESSION['cart']);
   ```

3. **Database Constraint**:
   - Timestamp recorded: `created_at`
   - If same user tries to order again immediately, new order created (intentionally)
   - No duplicate prevention at DB level (by design)

---

### 53. What performance optimizations are implemented?
**Expected Answer:**
1. **Database Optimization**:
   - Indexes on frequently queried columns:
     ```sql
     CREATE INDEX idx_restaurant ON orders(restaurant_id);
     CREATE INDEX idx_table ON orders(table_no);
     ```
   - Avoid SELECT * (select only needed columns)
   - Prepared statements (cached execution)

2. **Code Optimization**:
   - Minimize database queries
   - Cache static data
   - Lazy loading for images
   - Minify CSS/JavaScript

3. **UI Optimization**:
   - AJAX for partial updates (no full page reload)
   - Lazy image loading
   - CSS sprite sheets (optional)

4. **Caching Strategy**:
   - Browser caching for static files
   - Session-based caching for menu
   - Query result caching (future)

---

### 54. How are errors logged and handled?
**Expected Answer:**
```php
try {
    // Risky operation
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$id]);
    
} catch (PDOException $e) {
    // Log error
    error_log("Database error: " . $e->getMessage());
    
    // Notify user
    $_SESSION['error'] = "An error occurred. Please try again.";
    
    // Redirect to safe page
    header("Location: orders.php");
    exit;
}
```

**Logging Methods**:
1. Error logs: `/var/log/php.log`
2. Application logs: Custom log file
3. Database logs: MySQL error log
4. User feedback: Display user-friendly messages

---

### 55. What is the difference between `status` and `is_occupied` in the tables?
**Expected Answer:**
- **`is_occupied`**: Boolean flag (0 or 1)
  - Quick occupancy check
  - 0 = Available (empty)
  - 1 = Occupied (customer seated)

- **`status`**: Descriptive status (ENUM)
  - 'available' = Ready for customers
  - 'occupied' = Customer eating/ordering
  - 'reserved' = Reserved for future
  - 'out_of_service' = Cannot be used

**Why Both?**:
- `is_occupied`: Fast queries (index)
- `status`: Detailed information

---

## SUMMARY & PROJECT PRESENTATION

### 56. Summarize the key achievements of your project.
**Expected Answer:**
1. **Fully Functional System**:
   - QR code-based table ordering
   - 10 table setup complete
   - Live order tracking
   - Real-time status updates

2. **Database Implementation**:
   - 5 normalized tables
   - Foreign key relationships
   - Sample data populated
   - Ready for production

3. **Security Measures**:
   - Prepared statements
   - Session authentication
   - Input validation
   - SQL injection prevention

4. **User Experience**:
   - Intuitive customer interface
   - Responsive design
   - Real-time updates via AJAX
   - Seamless ordering flow

5. **Admin Capabilities**:
   - Order management
   - Table tracking
   - QR code generation
   - Bill processing

---

### 57. What did you learn from developing this project?
**Expected Answer:**
1. **Technical Skills**:
   - PHP web development
   - MySQL database design
   - JavaScript/AJAX implementation
   - Session management

2. **Software Design**:
   - Database normalization
   - Security best practices
   - User authentication
   - Transaction management

3. **Problem Solving**:
   - Debugging techniques
   - Error handling
   - System integration
   - Testing methodology

4. **Project Management**:
   - Documentation
   - Code organization
   - Version control
   - Deployment preparation

---

### 58. What challenges did you face and how did you overcome them?
**Expected Answer:**
**Common Challenges**:
1. **QR Code Generation**:
   - Challenge: Need library-free solution
   - Solution: Used external API (qrserver.com)

2. **Table Identification**:
   - Challenge: How to know which table customer is at?
   - Solution: Pass table number in QR URL parameter

3. **Session Management**:
   - Challenge: Keep table info across pages
   - Solution: Store in PHP session with security

4. **Real-time Updates**:
   - Challenge: Admin needs live order updates
   - Solution: Implement AJAX for partial page updates

5. **Data Integrity**:
   - Challenge: Ensure order data consistency
   - Solution: Use transactions and prepared statements

---

### 59. How would you test this system?
**Expected Answer:**
**Testing Strategy**:

1. **Unit Testing**:
   - Test individual functions
   - Verify QR generation
   - Test order creation logic

2. **Integration Testing**:
   - QR → Menu → Order flow
   - Database connections
   - Session management

3. **System Testing**:
   - Full user journey
   - Multi-user scenarios
   - Error handling

4. **Load Testing**:
   - Multiple simultaneous orders
   - 10+ concurrent customers
   - Performance benchmarking

5. **Security Testing**:
   - SQL injection attempts
   - Session hijacking attempts
   - Unauthorized access attempts

6. **User Acceptance Testing**:
   - Real customers testing
   - Feedback collection
   - Performance evaluation

---

### 60. What documentation has been created for this project?
**Expected Answer:**
1. **User Documentation**:
   - Quick Start Guide
   - Customer Usage Guide
   - Admin Usage Guide

2. **Technical Documentation**:
   - Database Schema
   - API Documentation
   - Code Comments
   - Implementation Details

3. **System Documentation**:
   - Architecture Diagram
   - Data Flow Diagram
   - System Design Document

4. **Deployment Documentation**:
   - Installation Guide
   - Configuration Guide
   - Troubleshooting Guide

5. **Testing Documentation**:
   - Testing Guide
   - Test Cases
   - Bug Reports

---

## CONCLUSION

This comprehensive VIVA question set covers:
- ✓ Project overview and objectives
- ✓ System architecture and design
- ✓ Database implementation
- ✓ QR code technology
- ✓ Customer and admin workflows
- ✓ Technical implementation details
- ✓ Security measures
- ✓ UI/UX design
- ✓ Troubleshooting and maintenance
- ✓ Advanced technical concepts
- ✓ Project achievements and learnings

---

**Prepared by**: Developer  
**Date**: February 12, 2026  
**Project Status**: ✓ COMPLETE  
**Documentation**: ✓ COMPREHENSIVE  

**Good Luck with Your VIVA! 🎓**
