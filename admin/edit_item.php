<?php
require_once '../includes/auth.php';
require_once '../config/db.php';

// Check if ID parameter is provided
if (!isset($_GET['id'])) {
    header("Location: menu.php");
    exit();
}

// Get the menu item details
$stmt = $pdo->prepare("SELECT * FROM menu_items WHERE id = ? AND restaurant_id = ?");
$stmt->execute([$_GET['id'], $_SESSION['user_id']]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if item exists and belongs to the restaurant
if (!$item) {
    header("Location: menu.php");
    exit();
}

// Update menu item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_item'])) {
    $data = [
        'category' => $_POST['category'],
        'name' => $_POST['name'],
        'description' => $_POST['description'],
        'price' => $_POST['price'],
        'id' => $_GET['id']
    ];
    
    // Handle image upload if a new image was provided
    if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $newImage = uploadImage($_FILES['image'], $item);
        
        if ($newImage !== $item['image']) {
            $data['image'] = $newImage;
            $stmt = $pdo->prepare("UPDATE menu_items SET category = ?, name = ?, description = ?, price = ?, image = ? WHERE id = ?");
            $stmt->execute([$data['category'], $data['name'], $data['description'], $data['price'], $data['image'], $data['id']]);
        } else {
            // Image didn't change, update without image
            $stmt = $pdo->prepare("UPDATE menu_items SET category = ?, name = ?, description = ?, price = ? WHERE id = ?");
            $stmt->execute([$data['category'], $data['name'], $data['description'], $data['price'], $data['id']]);
        }
    } else {
        // No new image uploaded, update without image
        $stmt = $pdo->prepare("UPDATE menu_items SET category = ?, name = ?, description = ?, price = ? WHERE id = ?");
        $stmt->execute([$data['category'], $data['name'], $data['description'], $data['price'], $data['id']]);
    }
    
    header("Location: menu.php?updated=1");
    exit();
}

// Updated uploadImage function with better error handling
function uploadImage($file, $currentItem) {
    // Check if upload directory exists
    $uploadDir = "../assets/images/menu_items/";
    
    // Create directory if it doesn't exist
    if (!file_exists($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            error_log("Failed to create upload directory: " . $uploadDir);
            return $currentItem['image']; // Keep the existing image
        }
    }

    // Check if file was uploaded without errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        error_log("File upload error: " . $file['error']);
        return $currentItem['image']; // Keep the existing image
    }

    // Check if file is actually uploaded
    if (!is_uploaded_file($file['tmp_name'])) {
        error_log("File not uploaded: " . $file['tmp_name']);
        return $currentItem['image']; // Keep the existing image
    }

    // Validate file is actually an image
    $imageInfo = @getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        error_log("File is not a valid image: " . $file['name']);
        return $currentItem['image']; // Keep the existing image
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
        error_log("MIME type not allowed: " . $mime);
        return $currentItem['image']; // Keep the existing image
    }

    // Get file extension from MIME type mapping
    $ext = $allowedTypes[$mime];
    
    // Generate unique filename with proper extension
    $filename = uniqid() . '.' . $ext;
    $target = $uploadDir . $filename;
    
    // Additional security checks
    $maxFileSize = 5 * 1024 * 1024; // 5MB
    if ($file['size'] > $maxFileSize) {
        error_log("File too large: " . $file['size'] . " bytes");
        return $currentItem['image']; // Keep the existing image
    }

    // Move uploaded file with additional security
    if (move_uploaded_file($file['tmp_name'], $target)) {
        // Set proper permissions
        chmod($target, 0644);
        
        // Delete the old image if it's not the default and exists
        if ($currentItem['image'] !== 'default.jpg' && file_exists($uploadDir . $currentItem['image'])) {
            if (!unlink($uploadDir . $currentItem['image'])) {
                error_log("Failed to delete old image: " . $uploadDir . $currentItem['image']);
            }
        }
        
        return $filename;
    } else {
        error_log("Failed to move uploaded file to: " . $target);
        return $currentItem['image']; // Keep the existing image
    }
}

// Get restaurant name for sidebar
$restaurant_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT restaurant_name FROM users WHERE id = ?");
$stmt->execute([$restaurant_id]);
$restaurant_name = $stmt->fetchColumn() ?? 'RestaurantPro';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RestaurantPro - Edit Menu Item</title>
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

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
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
            display: block;
        }

        .file-upload p {
            color: var(--gray);
            margin: 0;
        }

        .file-upload input {
            display: none;
        }

        .current-image {
            text-align: center;
            margin-bottom: 1rem;
        }

        .current-image img {
            max-width: 200px;
            max-height: 150px;
            border-radius: var(--border-radius-sm);
            box-shadow: var(--shadow);
            object-fit: cover;
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--light-gray);
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

        /* Sidebar Styles */
        .sidebar {
            width: 280px;
            background: var(--secondary);
            color: white;
            padding: 1.5rem 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            transition: var(--transition);
            z-index: 1000;
        }

        .sidebar-header {
            padding: 0 1.5rem 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 1rem;
            position: sticky;
            top: 0;
            background: var(--secondary);
            z-index: 100;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            text-decoration: none;
            color: white;
        }

        .logo-icon {
            background: var(--accent);
            color: var(--secondary);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }

        .logo-text {
            font-size: 1.4rem;
            font-weight: 700;
        }

        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .menu-item {
            margin-bottom: 0.25rem;
        }

        .menu-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.875rem 1.5rem;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: var(--transition);
            border-left: 4px solid transparent;
        }

        .menu-link:hover, .menu-link.active {
            background: rgba(255, 255, 255, 0.05);
            color: white;
            border-left-color: var(--accent);
        }

        .menu-link i {
            width: 20px;
            text-align: center;
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
            
            .current-image img {
                max-width: 150px;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <a href="index.php" class="logo">
                    <div class="logo-icon">
                        <i class="fas fa-utensils"></i>
                    </div>
                    <span class="logo-text"><?php echo $restaurant_name; ?></span>
                </a>
            </div>
            
            <ul class="sidebar-menu">
                <li class="menu-item">
                    <a href="dashboard.php" class="menu-link">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="menu.php" class="menu-link active">
                        <i class="fas fa-book-open"></i>
                        <span>Menu</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="orders.php" class="menu-link">
                        <i class="fas fa-clipboard-list"></i>
                        <span>Orders</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="tables.php" class="menu-link">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Reservations</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="qr_generator.php" class="menu-link">
                        <i class="fas fa-qrcode"></i>
                        <span>QR Codes</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="reports.php" class="menu-link">
                        <i class="fas fa-chart-bar"></i>
                        <span>Reports</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="profile.php" class="menu-link">
                        <i class="fas fa-user-circle"></i>
                        <span>Profile</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="../logout.php" class="menu-link">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Top Bar -->
            <div class="top-bar">
                <h1 class="page-title">
                    <i class="fas fa-edit"></i> Edit Menu Item
                </h1>
                
                <div class="user-menu">
                    <div class="date-display">
                        <i class="far fa-calendar-alt"></i>
                        <?php echo date('l, F j, Y'); ?>
                    </div>
                    <a href="menu.php" class="btn btn-outline">
                        <i class="fas fa-arrow-left"></i> Back to Menu
                    </a>
                </div>
            </div>
            
            <!-- Success Message -->
            <?php if (isset($_GET['updated'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> Menu item updated successfully!
            </div>
            <?php endif; ?>
            
            <!-- Error Message -->
            <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> 
                <?php 
                if ($_GET['error'] == 'upload') {
                    echo 'Error uploading image. Please try again.';
                } else {
                    echo 'An error occurred. Please try again.';
                }
                ?>
            </div>
            <?php endif; ?>
            
            <!-- Edit Form -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-pencil-alt"></i> Edit Item Details
                    </h2>
                </div>
                
                <form method="POST" enctype="multipart/form-data" id="editItemForm">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="category">Category</label>
                            <select class="form-control" name="category" id="category" required>
                                <option value="">Select Category</option>
                                <option value="Appetizers" <?= $item['category'] == 'Appetizers' ? 'selected' : '' ?>>Appetizers</option>
                                <option value="Main Course" <?= $item['category'] == 'Main Course' ? 'selected' : '' ?>>Main Course</option>
                                <option value="Desserts" <?= $item['category'] == 'Desserts' ? 'selected' : '' ?>>Desserts</option>
                                <option value="Drinks" <?= $item['category'] == 'Drinks' ? 'selected' : '' ?>>Drinks</option>
                                <option value="Sides" <?= $item['category'] == 'Sides' ? 'selected' : '' ?>>Sides</option>
                                <option value="Specials" <?= $item['category'] == 'Specials' ? 'selected' : '' ?>>Specials</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="name">Item Name</label>
                            <input type="text" class="form-control" name="name" id="name" required 
                                   value="<?= htmlspecialchars($item['name']) ?>" placeholder="e.g., Margherita Pizza">
                        </div>
                        
                        <div class="form-group">
                            <label for="price">Price (₹)</label>
                            <input type="number" step="0.01" class="form-control" name="price" id="price" required 
                                   value="<?= htmlspecialchars($item['price']) ?>" placeholder="0.00" min="0.01">
                        </div>
                        
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label for="description">Description</label>
                            <textarea class="form-control" name="description" id="description" 
                                      placeholder="Brief description of the item"><?= htmlspecialchars($item['description']) ?></textarea>
                        </div>
                        
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label>Current Image</label>
                            <div class="current-image">
                                <img src="../assets/images/menu_items/<?= $item['image'] ?>" alt="<?= htmlspecialchars($item['name']) ?>" 
                                     onerror="this.src='../assets/images/menu_items/default.jpg'">
                                <p><small>Current image: <?= $item['image'] ?></small></p>
                            </div>
                            
                            <label>Upload New Image (Optional)</label>
                            <div class="file-upload" id="fileUploadArea">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <p>Click to upload a new image</p>
                                <p><small>Max file size: 5MB. Supported formats: JPG, PNG, GIF, WebP</small></p>
                                <input type="file" name="image" accept="image/*" id="imageUpload">
                            </div>
                            <div id="fileError" style="color: var(--error); font-size: 0.875rem; margin-top: 0.5rem; display: none;"></div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <a href="menu.php" class="btn btn-outline">Cancel</a>
                        <button type="submit" name="update_item" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Item
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
        const imageUpload = document.getElementById('imageUpload');
        const fileUploadArea = document.getElementById('fileUploadArea');
        const fileError = document.getElementById('fileError');
        const maxFileSize = 5 * 1024 * 1024; // 5MB

        imageUpload.addEventListener('change', (e) => {
            const file = e.target.files[0];
            fileError.style.display = 'none';

            if (file) {
                // Validate size
                if (file.size > maxFileSize) {
                    fileError.textContent = 'File size must be less than 5MB';
                    fileError.style.display = 'block';
                    imageUpload.value = '';
                    return;
                }

                // Validate type
                const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (!validTypes.includes(file.type)) {
                    fileError.textContent = 'Invalid file type. Use JPG, PNG, GIF, or WebP.';
                    fileError.style.display = 'block';
                    imageUpload.value = '';
                    return;
                }

                // Show preview (without replacing input)
                const reader = new FileReader();
                reader.onload = (event) => {
                    fileUploadArea.querySelector('i').style.display = 'none';
                    fileUploadArea.innerHTML = `
                        <img src="${event.target.result}" 
                            style="max-height: 120px; border-radius: 8px; margin-bottom: 0.5rem;">
                        <p style="margin: 0; color: var(--gray);">${file.name}</p>
                        <p><small>Click to change image</small></p>
                    `;
                    fileUploadArea.appendChild(imageUpload); // keep the input inside
                };
                reader.readAsDataURL(file);
            }
        });

        // Make upload area clickable
        fileUploadArea.addEventListener('click', () => {
            imageUpload.click();
        });
        // Form validation
        const editItemForm = document.getElementById('editItemForm');
        editItemForm.addEventListener('submit', (e) => {
            const price = document.getElementById('price').value;
            
            if (price && parseFloat(price) <= 0) {
                e.preventDefault();
                alert('Price must be greater than 0');
                return;
            }
        });
    </script>
</body>
</html>