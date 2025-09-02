<?php
require_once '../includes/auth.php';
require_once '../config/db.php';

// Add new menu item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_item'])) {
    $data = [
        'restaurant_id' => $_SESSION['user_id'],
        'category' => $_POST['category'],
        'name' => $_POST['name'],
        'description' => $_POST['description'],
        'price' => $_POST['price'],
        'image' => uploadImage($_FILES['image'])
    ];
    
    $stmt = $pdo->prepare("INSERT INTO menu_items (restaurant_id, category, name, description, price, image) 
                          VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute(array_values($data));
    header("Location: menu.php?success=1");
    exit();
}

// Delete menu item
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM menu_items WHERE id = ? AND restaurant_id = ?");
    $stmt->execute([$_GET['delete'], $_SESSION['user_id']]);
    header("Location: menu.php?deleted=1");
    exit();
}

// Get all menu items
$stmt = $pdo->prepare("SELECT * FROM menu_items WHERE restaurant_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$menu_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Image upload helper with improved error handling
// Replace the uploadImage function with this improved version
function uploadImage($file) {
    // Check if upload directory exists
    $uploadDir = "../assets/images/menu_items/";
    
    // Create directory if it doesn't exist
    if (!file_exists($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            die("Failed to create upload directory");
        }
    }

    // Check if file was uploaded without errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return 'default.jpg'; // Fallback image
    }

    // Validate file is actually an image
    $imageInfo = getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        return 'default.jpg';
    }

    // Supported image MIME types
    $allowedTypes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp'
    ];

    // Check if the detected MIME type is allowed
    $mime = $imageInfo['mime'];
    if (!array_key_exists($mime, $allowedTypes)) {
        return 'default.jpg';
    }

    // Get file extension from MIME type mapping
    $ext = $allowedTypes[$mime];
    
    // Generate unique filename with proper extension
    $filename = uniqid() . '.' . $ext;
    $target = $uploadDir . $filename;
    
    // Additional security checks
    $maxFileSize = 5 * 1024 * 1024; // 5MB
    if ($file['size'] > $maxFileSize) {
        return 'default.jpg';
    }

    // Move uploaded file with additional security
    if (move_uploaded_file($file['tmp_name'], $target)) {
        // Set proper permissions
        chmod($target, 0644);
        return $filename;
    } else {
        return 'default.jpg';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RestaurantPro - Menu Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2a9d8f;
            --primary-light: #76c7ba;
            --primary-dark: #1d7874;
            --secondary: #264653;
            --accent: #e9c46a;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
            --light-gray: #e9ecef;
            --success: #52b788;
            --error: #e76f51;
            --warning: #f4a261;
            --info: #3a86ff;
            --shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            --shadow-lg: 0 10px 30px rgba(0, 0, 0, 0.12);
            --border-radius: 12px;
            --border-radius-sm: 8px;
            --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', system-ui, -apple-system, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            color: var(--dark);
            min-height: 100vh;
            line-height: 1.6;
        }

        /* Dashboard Layout */
        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 2rem;
        }

        /* Top Bar */
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding: 1rem 1.5rem;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
        }

        .page-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--secondary);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .page-title i {
            color: var(--primary);
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .date-display {
            background: var(--light);
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius-sm);
            font-weight: 500;
            color: var(--gray);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Cards */
        .card {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            transition: var(--transition);
            margin-bottom: 2rem;
        }

        .card:hover {
            box-shadow: var(--shadow-lg);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--light-gray);
        }

        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--secondary);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .card-title i {
            color: var(--primary);
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.875rem 1.5rem;
            border-radius: var(--border-radius-sm);
            font-weight: 600;
            text-decoration: none;
            transition: var(--transition);
            border: none;
            cursor: pointer;
            font-size: 1rem;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .btn-outline {
            background: transparent;
            color: var(--primary);
            border: 1px solid var(--primary);
        }

        .btn-outline:hover {
            background: rgba(42, 157, 143, 0.1);
        }

        .btn-danger {
            background: var(--error);
            color: white;
        }

        .btn-danger:hover {
            background: #d45a3f;
            transform: translateY(-2px);
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
        }

        /* View Toggle */
        .view-toggle {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 1.5rem;
            background: white;
            padding: 0.5rem;
            border-radius: var(--border-radius-sm);
            box-shadow: var(--shadow);
        }

        .view-toggle-btn {
            padding: 0.5rem 1rem;
            border: none;
            background: transparent;
            cursor: pointer;
            transition: var(--transition);
            border-radius: var(--border-radius-sm);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .view-toggle-btn.active {
            background: var(--primary);
            color: white;
        }

        /* Menu Grid */
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .menu-item-card {
            background: white;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: var(--transition);
            position: relative;
        }

        .menu-item-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .menu-item-image {
            height: 200px;
            overflow: hidden;
        }

        .menu-item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .menu-item-card:hover .menu-item-image img {
            transform: scale(1.05);
        }

        .menu-item-content {
            padding: 1.25rem;
        }

        .menu-item-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--secondary);
        }

        .menu-item-meta {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.75rem;
        }

        .menu-item-category {
            background-color: var(--light);
            color: var(--primary);
            padding: 0.35rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .menu-item-price {
            font-weight: 700;
            color: var(--primary);
        }

        .menu-item-description {
            color: var(--gray);
            font-size: 0.875rem;
            margin-bottom: 1rem;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .menu-item-actions {
            display: flex;
            gap: 0.5rem;
        }

        /* Table View */
        .data-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background: white;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--shadow);
        }

        .data-table thead th {
            background: var(--light);
            padding: 1rem;
            text-align: left;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--gray);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .data-table tbody td {
            padding: 1rem;
            border-bottom: 1px solid var(--light-gray);
            vertical-align: middle;
        }

        .data-table tbody tr:last-child td {
            border-bottom: none;
        }

        .data-table tbody tr:hover {
            background: rgba(42, 157, 143, 0.03);
        }

        .table-img {
            width: 60px;
            height: 60px;
            border-radius: var(--border-radius-sm);
            object-fit: cover;
        }

        /* Alerts */
        .alert {
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            border-radius: var(--border-radius-sm);
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .alert-success {
            background: rgba(82, 183, 136, 0.15);
            color: var(--success);
            border-left: 4px solid var(--success);
        }

        .alert-error {
            background: rgba(231, 111, 81, 0.15);
            color: var(--error);
            border-left: 4px solid var(--error);
        }

        /* Empty State */
        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 3rem 2rem;
            text-align: center;
            color: var(--gray);
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .empty-state p {
            margin: 0;
            max-width: 300px;
            line-height: 1.6;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            overflow-y: auto;
            animation: fadeIn 0.3s ease;
        }

        .modal-content {
            background: white;
            margin: 2rem auto;
            width: 90%;
            max-width: 700px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-lg);
            animation: slideIn 0.3s ease;
            position: relative;
        }

        .modal-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--light-gray);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--secondary);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .modal-title i {
            color: var(--primary);
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--gray);
            transition: var(--transition);
        }

        .modal-close:hover {
            color: var(--error);
        }

        .modal-body {
            padding: 1.5rem;
        }

        /* Form Styles */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--secondary);
        }

        .form-control {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 1px solid var(--light-gray);
            border-radius: var(--border-radius-sm);
            font-size: 1rem;
            transition: var(--transition);
            background-color: var(--light);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-light);
            box-shadow: 0 0 0 3px rgba(42, 157, 143, 0.1);
        }

        .form-control::placeholder {
            color: var(--gray);
            opacity: 0.6;
        }

        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }

        /* File Upload */
        .file-upload {
            border: 2px dashed var(--light-gray);
            border-radius: var(--border-radius-sm);
            padding: 1.5rem;
            text-align: center;
            transition: var(--transition);
            cursor: pointer;
        }

        .file-upload:hover {
            border-color: var(--primary-light);
        }

        .file-upload i {
            font-size: 2rem;
            color: var(--gray);
            margin-bottom: 0.5rem;
        }

        .file-upload p {
            color: var(--gray);
            margin: 0;
        }

        .file-upload input {
            display: none;
        }

        .modal-footer {
            padding: 1.5rem;
            border-top: 1px solid var(--light-gray);
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .sidebar {
                width: 240px;
            }
            
            .main-content {
                margin-left: 240px;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                width: 280px;
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
            
            .menu-grid {
                grid-template-columns: 1fr;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .top-bar {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }
            
            .user-menu {
                width: 100%;
                justify-content: space-between;
            }
            
            .data-table {
                display: block;
                overflow-x: auto;
            }
            
            .modal-content {
                width: 95%;
                margin: 1rem auto;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <?php include '../includes/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Top Bar -->
            <div class="top-bar">
                <h1 class="page-title">
                    <i class="fas fa-book-open"></i> Menu Management
                </h1>
                
                <div class="user-menu">
                    <div class="date-display">
                        <i class="far fa-calendar-alt"></i>
                        <?php echo date('l, F j, Y'); ?>
                    </div>
                    <button class="btn btn-primary" id="addItemBtn">
                        <i class="fas fa-plus"></i> Add New Item
                    </button>
                </div>
            </div>
            
            <!-- Success Messages -->
            <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> Menu item added successfully!
            </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['deleted'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> Menu item deleted successfully!
            </div>
            <?php endif; ?>
            
            <!-- View Toggle -->
            <div class="view-toggle">
                <button class="view-toggle-btn active" id="gridViewBtn">
                    <i class="fas fa-th-large"></i> Grid View
                </button>
                <button class="view-toggle-btn" id="tableViewBtn">
                    <i class="fas fa-table"></i> Table View
                </button>
            </div>
            
            <!-- Menu Items Grid View -->
            <div id="gridView">
                <?php if (!empty($menu_items)): ?>
                <div class="menu-grid">
                    <?php foreach ($menu_items as $item): ?>
                    <div class="menu-item-card">
                        <div class="menu-item-image">
                            <img src="../assets/images/menu_items/<?= $item['image'] ?>" alt="<?= htmlspecialchars($item['name']) ?>">
                        </div>
                        <div class="menu-item-content">
                            <h3 class="menu-item-title"><?= htmlspecialchars($item['name']) ?></h3>
                            <div class="menu-item-meta">
                                <span class="menu-item-category"><?= htmlspecialchars($item['category']) ?></span>
                                <span class="menu-item-price">₹<?= number_format($item['price'], 2) ?></span>
                            </div>
                            <p class="menu-item-description"><?= htmlspecialchars($item['description']) ?></p>
                            <div class="menu-item-actions">
                                <a href="edit_item.php?id=<?= $item['id'] ?>" class="btn btn-outline btn-sm">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <a href="menu.php?delete=<?= $item['id'] ?>" class="btn btn-danger btn-sm" 
                                   onclick="return confirm('Are you sure you want to delete this item?')">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-utensils"></i>
                    <p>No menu items found. Add your first menu item to get started.</p>
                    <button class="btn btn-primary" style="margin-top: 1rem;" id="addFirstItemBtn">
                        <i class="fas fa-plus"></i> Add Your First Item
                    </button>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Menu Items Table View (Hidden by default) -->
            <div id="tableView" style="display: none;">
                <?php if (!empty($menu_items)): ?>
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">
                            <i class="fas fa-list"></i> Current Menu
                        </h2>
                    </div>
                    
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Description</th>
                                <th>Price</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($menu_items as $item): ?>
                            <tr>
                                <td><img src="../assets/images/menu_items/<?= $item['image'] ?>" class="table-img" alt="<?= htmlspecialchars($item['name']) ?>"></td>
                                <td><?= htmlspecialchars($item['name']) ?></td>
                                <td><?= htmlspecialchars($item['category']) ?></td>
                                <td style="max-width: 200px;"><?= htmlspecialchars($item['description']) ?></td>
                                <td>₹<?= number_format($item['price'], 2) ?></td>
                                <td>
                                    <div style="display: flex; gap: 0.5rem;">
                                        <a href="edit_item.php?id=<?= $item['id'] ?>" class="btn btn-outline btn-sm">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <a href="menu.php?delete=<?= $item['id'] ?>" class="btn btn-danger btn-sm" 
                                           onclick="return confirm('Are you sure you want to delete this item?')">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-utensils"></i>
                    <p>No menu items found. Add your first menu item to get started.</p>
                    <button class="btn btn-primary" style="margin-top: 1rem;" id="addFirstItemBtn2">
                        <i class="fas fa-plus"></i> Add Your First Item
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Add Item Modal -->
    <div class="modal" id="addItemModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">
                    <i class="fas fa-plus-circle"></i> Add New Menu Item
                </h2>
                <button class="modal-close" id="closeModal">&times;</button>
            </div>
            
            <form method="POST" enctype="multipart/form-data" id="menuItemForm" action="menu.php">
                <div class="modal-body">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="category">Category</label>
                            <select class="form-control" name="category" id="category" required>
                                <option value="">Select Category</option>
                                <option value="Appetizers">Appetizers</option>
                                <option value="Main Course">Main Course</option>
                                <option value="Desserts">Desserts</option>
                                <option value="Drinks">Drinks</option>
                                <option value="Sides">Sides</option>
                                <option value="Specials">Specials</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="name">Item Name</label>
                            <input type="text" class="form-control" name="name" id="name" required placeholder="e.g., Margherita Pizza">
                        </div>
                        
                        <div class="form-group">
                            <label for="price">Price (₹)</label>
                            <input type="number" step="0.01" class="form-control" name="price" id="price" required placeholder="0.00" min="0.01">
                        </div>
                        
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label for="description">Description</label>
                            <textarea class="form-control" name="description" id="description" placeholder="Brief description of the item"></textarea>
                        </div>
                        
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label>Image</label>
                            <div class="file-upload" id="fileUploadArea">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <p>Click to upload an image</p>
                                <input type="file" name="image" accept="image/*" id="imageUpload" required>
                            </div>
                            <div id="fileError" style="color: var(--error); font-size: 0.875rem; margin-top: 0.5rem; display: none;"></div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" id="cancelBtn">Cancel</button>
                    <button type="submit" name="add_item" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Item
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // View Toggle Functionality
        const gridViewBtn = document.getElementById('gridViewBtn');
        const tableViewBtn = document.getElementById('tableViewBtn');
        const gridView = document.getElementById('gridView');
        const tableView = document.getElementById('tableView');

        gridViewBtn.addEventListener('click', () => {
            gridView.style.display = 'block';
            tableView.style.display = 'none';
            gridViewBtn.classList.add('active');
            tableViewBtn.classList.remove('active');
        });

        tableViewBtn.addEventListener('click', () => {
            gridView.style.display = 'none';
            tableView.style.display = 'block';
            gridViewBtn.classList.remove('active');
            tableViewBtn.classList.add('active');
        });

        // Modal Functionality
        const addItemBtn = document.getElementById('addItemBtn');
        const addFirstItemBtn = document.getElementById('addFirstItemBtn');
        const addFirstItemBtn2 = document.getElementById('addFirstItemBtn2');
        const addItemModal = document.getElementById('addItemModal');
        const closeModal = document.getElementById('closeModal');
        const cancelBtn = document.getElementById('cancelBtn');

        // Open modal from different buttons
        [addItemBtn, addFirstItemBtn, addFirstItemBtn2].forEach(btn => {
            if (btn) {
                btn.addEventListener('click', () => {
                    addItemModal.style.display = 'block';
                    document.body.style.overflow = 'hidden';
                });
            }
        });

        // Close modal
        [closeModal, cancelBtn].forEach(btn => {
            btn.addEventListener('click', () => {
                addItemModal.style.display = 'none';
                document.body.style.overflow = 'auto';
                resetForm();
            });
        });

        // Close modal when clicking outside
        window.addEventListener('click', (e) => {
            if (e.target === addItemModal) {
                addItemModal.style.display = 'none';
                document.body.style.overflow = 'auto';
                resetForm();
            }
        });

        // Image upload preview and validation
        const imageUpload = document.getElementById('imageUpload');
        const fileUploadArea = document.getElementById('fileUploadArea');
        const fileError = document.getElementById('fileError');
        const maxFileSize = 5 * 1024 * 1024; // 5MB

        imageUpload.addEventListener('change', (e) => {
            const file = e.target.files[0];
            fileError.style.display = 'none';

            if (file) {
                // Validate file size
                if (file.size > maxFileSize) {
                    fileError.textContent = 'File size must be less than 5MB';
                    fileError.style.display = 'block';
                    imageUpload.value = '';
                    return;
                }

                // Validate file type
                const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (!validTypes.includes(file.type)) {
                    fileError.textContent = 'Please select a valid image file (JPG, PNG, GIF, or WebP)';
                    fileError.style.display = 'block';
                    imageUpload.value = '';
                    return;
                }

                // Show preview without replacing input
                const reader = new FileReader();
                reader.onload = (event) => {
                    // Clear old preview if exists
                    let oldPreview = document.querySelector('.file-preview');
                    if (oldPreview) oldPreview.remove();

                    const preview = document.createElement('div');
                    preview.classList.add('file-preview');
                    preview.innerHTML = `
                        <img src="${event.target.result}" 
                            style="max-height: 120px; border-radius: 8px; margin-bottom: 0.5rem;">
                        <p style="margin:0; color: var(--gray);">${file.name}</p>
                    `;
                    fileUploadArea.appendChild(preview);
                };
                reader.readAsDataURL(file);
            }
        });

        // Make file upload area clickable
        fileUploadArea.addEventListener('click', () => {
            imageUpload.click();
        });

        // Reset form when modal is closed
        function resetForm() {
            menuItemForm.reset();
            fileError.style.display = 'none';
            let oldPreview = document.querySelector('.file-preview');
            if (oldPreview) oldPreview.remove();
        }

        // Make file upload area clickable
        fileUploadArea.addEventListener('click', () => {
            imageUpload.click();
        });

        // Form validation
        const menuItemForm = document.getElementById('menuItemForm');
        menuItemForm.addEventListener('submit', (e) => {
            const price = document.getElementById('price').value;
            const image = document.getElementById('imageUpload').value;
            
            if (price && parseFloat(price) <= 0) {
                e.preventDefault();
                alert('Price must be greater than 0');
                return;
            }
            
            if (!image) {
                e.preventDefault();
                fileError.textContent = 'Please select an image';
                fileError.style.display = 'block';
                return;
            }
        });

        // Reset form when modal is closed
        function resetForm() {
            menuItemForm.reset();
            fileUploadArea.innerHTML = `
                <i class="fas fa-cloud-upload-alt"></i>
                <p>Click to upload an image</p>
                <input type="file" name="image" accept="image/*" id="imageUpload" required>
            `;
            fileError.style.display = 'none';
            
            // Re-add event listener to the new file input
            document.getElementById('imageUpload').addEventListener('change', imageUpload.onchange);
        }
    </script>

</body>
</html>