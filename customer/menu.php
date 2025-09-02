<?php
require_once '../config/db.php';
require_once '../functions.php';

session_start();

$restaurant_id = isset($_GET['restaurant']) ? (int)$_GET['restaurant'] : 0;

if (!$restaurant_id) {
    die("Invalid restaurant");
}

// Get restaurant info
$restaurant = $pdo->query("SELECT * FROM users WHERE id = $restaurant_id")->fetch();

// Get menu items
$menu_items = getMenuItems($restaurant_id);

// Get all unique categories
$categories = [];
foreach ($menu_items as $item) {
    if (!in_array($item['category'], $categories)) {
        $categories[] = $item['category'];
    }
}

// Check if table number is provided
$table_no = isset($_GET['table']) ? (int)$_GET['table'] : 0;

// If table number is provided, mark it as occupied
if ($table_no) {
    // Check if table exists for this restaurant
    $table_check = $pdo->prepare("SELECT * FROM restaurant_tables WHERE restaurant_id = ? AND table_no = ?");
    $table_check->execute([$restaurant_id, $table_no]);
    
    if ($table_check->rowCount() > 0) {
        // Update table status to occupied
        $update_table = $pdo->prepare("UPDATE restaurant_tables SET is_occupied = 1 WHERE restaurant_id = ? AND table_no = ?");
        $update_table->execute([$restaurant_id, $table_no]);
    } else {
        // Create table entry if it doesn't exist
        $insert_table = $pdo->prepare("INSERT INTO restaurant_tables (restaurant_id, table_no, is_occupied) VALUES (?, ?, 1)");
        $insert_table->execute([$restaurant_id, $table_no]);
    }
    
    // Store table number in session
    $_SESSION['table_no'] = $table_no;
    $_SESSION['restaurant_id'] = $restaurant_id;
} elseif (isset($_SESSION['table_no']) && isset($_SESSION['restaurant_id']) && $_SESSION['restaurant_id'] == $restaurant_id) {
    $table_no = $_SESSION['table_no'];
}

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Add to cart functionality
if (isset($_POST['add_to_cart'])) {
    $item_id = (int)$_POST['item_id'];
    $quantity = (int)$_POST['quantity'];
    
    // Check if item exists in menu
    $item_exists = false;
    foreach ($menu_items as $item) {
        if ($item['id'] == $item_id) {
            $item_exists = true;
            $item_name = $item['name'];
            $item_price = $item['price'];
            break;
        }
    }
    
    if ($item_exists) {
        // Add to cart or update quantity
        if (isset($_SESSION['cart'][$item_id])) {
            $_SESSION['cart'][$item_id]['quantity'] += $quantity;
        } else {
            $_SESSION['cart'][$item_id] = [
                'name' => $item_name,
                'price' => $item_price,
                'quantity' => $quantity
            ];
        }
        
        header("Location: menu.php?restaurant=" . $restaurant_id . "&table=" . $table_no);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($restaurant['restaurant_name']); ?> Menu</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3f37c9;
            --success: #4cc9f0;
            --danger: #f72585;
            --warning: #f8961e;
            --info: #4895ef;
            --light: #f8f9fa;
            --dark: #212529;
            --white: #ffffff;
            --gray: #6c757d;
            --gray-light: #e9ecef;
            --border-radius: 0.375rem;
            --box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f7fb;
            color: #4a5568;
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }

        .restaurant-header {
            text-align: center;
            margin-bottom: 2rem;
            position: relative;
        }

        .restaurant-logo {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 50%;
            margin-bottom: 1rem;
            border: 3px solid var(--primary);
            box-shadow: var(--box-shadow);
        }

        .restaurant-header h1 {
            font-size: 2.5rem;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }

        .table-info {
            background: var(--white);
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            display: inline-block;
            margin-bottom: 1rem;
            font-weight: 500;
            box-shadow: var(--box-shadow);
        }

        .restaurant-description {
            color: var(--gray);
            max-width: 600px;
            margin: 0 auto 2rem;
        }

        .menu-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .menu-categories {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .category-btn {
            padding: 0.5rem 1.25rem;
            background: var(--white);
            border: 1px solid var(--gray-light);
            border-radius: 2rem;
            cursor: pointer;
            transition: var(--transition);
            font-size: 0.875rem;
            font-weight: 500;
        }

        .category-btn:hover, .category-btn.active {
            background: var(--primary);
            color: var(--white);
            border-color: var(--primary);
        }

        .search-box {
            position: relative;
            min-width: 250px;
        }

        .search-box input {
            width: 100%;
            padding: 0.5rem 1rem 0.5rem 2.5rem;
            border: 1px solid var(--gray-light);
            border-radius: 2rem;
            font-family: 'Poppins', sans-serif;
        }

        .search-box i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
        }

        .menu-items {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .menu-item {
            background: var(--white);
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--box-shadow);
            transition: var(--transition);
            position: relative;
        }

        .menu-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 1rem 3rem rgba(0, 0, 0, 0.175);
        }

        .item-image {
            height: 200px;
            overflow: hidden;
            position: relative;
        }

        .item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .menu-item:hover .item-image img {
            transform: scale(1.05);
        }

        .popular-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: var(--warning);
            color: var(--white);
            padding: 0.25rem 0.75rem;
            border-radius: 2rem;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .item-details {
            padding: 1.25rem;
        }

        .item-details h3 {
            font-size: 1.25rem;
            margin-bottom: 0.5rem;
            color: var(--dark);
        }

        .item-category {
            display: inline-block;
            background: var(--gray-light);
            color: var(--gray);
            padding: 0.25rem 0.75rem;
            border-radius: 2rem;
            font-size: 0.75rem;
            font-weight: 600;
            margin-bottom: 0.75rem;
        }

        .item-description {
            color: var(--gray);
            font-size: 0.875rem;
            margin-bottom: 1rem;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .item-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .item-price {
            font-weight: 700;
            color: var(--primary);
            font-size: 1.1rem;
        }

        .add-to-cart {
            padding: 0.5rem 1rem;
            background: var(--primary);
            color: var(--white);
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: var(--transition);
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .add-to-cart:hover {
            background: var(--secondary);
        }

        .add-to-cart:disabled {
            background: var(--gray);
            cursor: not-allowed;
        }

        .no-items {
            text-align: center;
            padding: 3rem;
            grid-column: 1 / -1;
            color: var(--gray);
        }

        .no-items i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: var(--gray-light);
        }

        .no-items h3 {
            margin-bottom: 0.5rem;
        }

        /* Cart Toggle */
        .cart-toggle {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            width: 60px;
            height: 60px;
            background: var(--primary);
            color: var(--white);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: var(--box-shadow);
            z-index: 999;
            transition: var(--transition);
        }

        .cart-toggle:hover {
            transform: scale(1.05);
        }

        .cart-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: var(--danger);
            color: var(--white);
            width: 25px;
            height: 25px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: 600;
        }

        /* Table Selection Modal */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2000;
            opacity: 0;
            visibility: hidden;
            transition: var(--transition);
        }

        .modal.active {
            opacity: 1;
            visibility: visible;
        }

        .modal-content {
            background: var(--white);
            border-radius: var(--border-radius);
            width: 90%;
            max-width: 500px;
            overflow: hidden;
            box-shadow: var(--box-shadow);
            transform: translateY(-20px);
            transition: var(--transition);
        }

        .modal.active .modal-content {
            transform: translateY(0);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem;
            border-bottom: 1px solid var(--gray-light);
            background: var(--primary);
            color: var(--white);
        }

        .modal-header h2 {
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .close-modal {
            background: none;
            border: none;
            font-size: 1.25rem;
            cursor: pointer;
            color: var(--white);
        }

        .modal-body {
            padding: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--dark);
        }

        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--gray-light);
            border-radius: var(--border-radius);
            font-family: 'Poppins', sans-serif;
            font-size: 1rem;
        }

        .modal-footer {
            padding: 1.5rem;
            border-top: 1px solid var(--gray-light);
            text-align: right;
        }

        .confirm-table {
            padding: 0.75rem 1.5rem;
            background: var(--primary);
            color: var(--white);
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            font-weight: 500;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .confirm-table:hover {
            background: var(--secondary);
        }

        .confirm-table:disabled {
            background: var(--gray);
            cursor: not-allowed;
        }

        .table-status-info {
            margin-top: 1rem;
            padding: 0.75rem;
            background-color: #f8f9fa;
            border-radius: var(--border-radius);
            font-size: 0.875rem;
        }

        .status-available {
            color: var(--success);
            font-weight: 500;
        }

        .status-occupied {
            color: var(--danger);
            font-weight: 500;
        }

        @media (max-width: 768px) {
            .menu-items {
                grid-template-columns: 1fr;
            }
            
            .menu-controls {
                flex-direction: column;
                align-items: stretch;
            }
            
            .menu-categories {
                justify-content: center;
            }
            
            .search-box {
                width: 100%;
            }
            
            .restaurant-header h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="restaurant-header">
            <?php if (!empty($restaurant['logo'])): ?>
            <img src="../assets/images/restaurant_logos/<?= htmlspecialchars($restaurant['logo']) ?>" alt="Restaurant Logo" class="restaurant-logo">
            <?php endif; ?>
            <h1><?php echo htmlspecialchars($restaurant['restaurant_name']); ?></h1>
            
            
           
            <button id="selectTableBtn" class="add-to-cart">
                <i class="fas fa-table"></i> Select Table
            </button>
           
            
            <?php if (!empty($restaurant['description'])): ?>
            <p class="restaurant-description"><?= htmlspecialchars($restaurant['description']) ?></p>
            <?php endif; ?>
        </div>
        
        <div class="menu-controls">
            <div class="menu-categories">
                <button class="category-btn active" data-category="all">All</button>
                <?php foreach ($categories as $category): ?>
                <button class="category-btn" data-category="<?= htmlspecialchars($category) ?>"><?= htmlspecialchars($category) ?></button>
                <?php endforeach; ?>
            </div>
            
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="search-input" placeholder="Search menu items...">
            </div>
        </div>
        
        <div class="menu-items">
            <?php if (empty($menu_items)): ?>
                <div class="no-items">
                    <i class="fas fa-utensils"></i>
                    <h3>No menu items available</h3>
                    <p>Please check back later</p>
                </div>
            <?php else: ?>
                <?php foreach ($menu_items as $item): ?>
                <div class="menu-item" data-category="<?= htmlspecialchars($item['category']) ?>">
                    <div class="item-image">
                        <img src="../assets/images/menu_items/<?= htmlspecialchars($item['image'] ?? 'default-food.jpg') ?>" alt="<?= htmlspecialchars($item['name']) ?>">
                        <?php if ($item['is_popular']): ?>
                        <span class="popular-badge">Popular</span>
                        <?php endif; ?>
                    </div>
                    <div class="item-details">
                        <span class="item-category"><?= htmlspecialchars($item['category']) ?></span>
                        <h3><?= htmlspecialchars($item['name']) ?></h3>
                        <p class="item-description"><?= htmlspecialchars($item['description']) ?></p>
                        <div class="item-footer">
                            <span class="item-price">₹<?= number_format($item['price'], 2) ?></span>
                            <form method="POST" action="">
                                <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                                <input type="hidden" name="quantity" value="1">
                                <button type="submit" name="add_to_cart" class="add-to-cart" <?= !$table_no ? 'disabled' : '' ?>>
                                    <i class="fas fa-plus"></i> Add
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Cart Toggle Button -->
    <?php if ($table_no && !empty($_SESSION['cart'])): ?>
    <div class="cart-toggle" id="cartToggle">
        <i class="fas fa-shopping-cart"></i>
        <span class="cart-badge" id="cartBadge"><?= array_sum(array_column($_SESSION['cart'], 'quantity')) ?></span>
    </div>
    <?php endif; ?>
    
    <!-- Table Selection Modal -->
    <div class="modal" id="tableModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-table"></i> Select Your Table</h2>
                <button class="close-modal" id="closeModal"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <form id="tableSelectionForm">
                    <div class="form-group">
                        <label for="tableSelect">Choose a table:</label>
                        <select id="tableSelect" name="table_no" required>
                            <option value="">-- Select a table --</option>
                            <?php
                            // Fetch tables from database
                            try {
                                $stmt = $pdo->prepare("
                                    SELECT table_no, status, is_occupied 
                                    FROM restaurant_tables 
                                    WHERE restaurant_id = ? 
                                    ORDER BY table_no
                                ");
                                $stmt->execute([$restaurant_id]);
                                $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                
                                // Get active orders for table status
                                $order_stmt = $pdo->prepare("SELECT table_no FROM orders 
                                                            WHERE restaurant_id = ? 
                                                            AND status IN ('Pending', 'Preparing')");
                                $order_stmt->execute([$restaurant_id]);
                                $occupied_tables = $order_stmt->fetchAll(PDO::FETCH_COLUMN, 0);
                                
                                foreach ($tables as $table) {
                                    $table_no_val = $table['table_no'];
                                    $status = $table['status'];
                                    $is_occupied = $table['is_occupied'] || 
                                                 (in_array($table_no_val, $occupied_tables)) ||
                                                 ($status === 'occupied');
                                    
                                    echo '<option value="' . $table_no_val . '"';
                                    echo $is_occupied ? ' disabled style="color: #dc3545;"' : '';
                                    echo '>';
                                    echo 'Table ' . $table_no_val;
                                    echo $is_occupied ? ' (Occupied)' : ' (Available)';
                                    echo '</option>';
                                }
                            } catch (PDOException $e) {
                                echo '<option value="" disabled>Error loading tables</option>';
                                error_log("Database error: " . $e->getMessage());
                            }
                            ?>
                        </select>
                    </div>
                </form>
                <div class="table-status-info">
                    <p><span class="status-available">● Available</span> - Tables that are ready for customers</p>
                    <p><span class="status-occupied">● Occupied</span> - Tables that are currently in use</p>
                </div>
            </div>
            <div class="modal-footer">
                <button class="confirm-table" id="confirmTable" disabled>
                    <i class="fas fa-check"></i> Confirm Table
                </button>
            </div>
        </div>
    </div>

    <script>
        // Table selection functionality
        const tableModal = document.getElementById('tableModal');
        const selectTableBtn = document.getElementById('selectTableBtn');
        const closeModalBtn = document.getElementById('closeModal');
        const confirmTableBtn = document.getElementById('confirmTable');
        const tableSelect = document.getElementById('tableSelect');
        
        // Show table modal
        if (selectTableBtn) {
            selectTableBtn.addEventListener('click', () => {
                tableModal.classList.add('active');
            });
        }
        
        // Close modal
        closeModalBtn.addEventListener('click', () => {
            tableModal.classList.remove('active');
        });
        
        // Close modal when clicking outside
        tableModal.addEventListener('click', (e) => {
            if (e.target === tableModal) {
                tableModal.classList.remove('active');
            }
        });
        
        // Enable confirm button when a table is selected
        tableSelect.addEventListener('change', function() {
            confirmTableBtn.disabled = !this.value || this.options[this.selectedIndex].disabled;
        });
        
        // Confirm table selection
        confirmTableBtn.addEventListener('click', () => {
            if (tableSelect.value && !tableSelect.options[tableSelect.selectedIndex].disabled) {
                window.location.href = `menu.php?restaurant=<?= $restaurant_id ?>&table=${tableSelect.value}`;
            }
        });
        
        // Filter by category
        document.querySelectorAll('.category-btn').forEach(button => {
            button.addEventListener('click', function() {
                const category = this.getAttribute('data-category');
                
                // Update active button
                document.querySelectorAll('.category-btn').forEach(btn => {
                    btn.classList.remove('active');
                });
                this.classList.add('active');
                
                // Filter items
                document.querySelectorAll('.menu-item').forEach(item => {
                    if (category === 'all' || item.getAttribute('data-category') === category) {
                        item.style.display = 'block';
                    } else {
                        item.style.display = 'none';
                    }
                });
            });
        });
        
        // Search functionality
        document.getElementById('search-input').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            
            document.querySelectorAll('.menu-item').forEach(item => {
                const name = item.querySelector('h3').textContent.toLowerCase();
                const description = item.querySelector('.item-description').textContent.toLowerCase();
                const category = item.querySelector('.item-category').textContent.toLowerCase();
                
                if (name.includes(searchTerm) || description.includes(searchTerm) || category.includes(searchTerm)) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });
        
        // Cart toggle functionality
        const cartToggle = document.getElementById('cartToggle');
        if (cartToggle) {
            cartToggle.addEventListener('click', () => {
                window.location.href = `cart.php?restaurant=<?= $restaurant_id ?>&table=<?= $table_no ?>`;
            });
        }
    </script>
</body>
</html>