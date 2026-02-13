<?php
require_once '../includes/auth.php';
require_once '../config/db.php';

// Add new table
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_table'])) {
    $table_no = $_POST['table_no'];
    $status = $_POST['status'];
    
    // Determine is_occupied based on status
    $is_occupied = ($status === 'occupied') ? 1 : 0;
    
    // Check if table already exists
    $stmt = $pdo->prepare("SELECT id FROM restaurant_tables 
                          WHERE table_no = ? AND restaurant_id = ?");
    $stmt->execute([$table_no, $_SESSION['user_id']]);
    
    if ($stmt->rowCount() === 0) {
        // Insert new table
        $stmt = $pdo->prepare("INSERT INTO restaurant_tables 
                             (restaurant_id, table_no, status, is_occupied) 
                             VALUES (?, ?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $table_no, $status, $is_occupied]);
        header("Location: tables.php?added=1");
        exit();
    } else {
        header("Location: tables.php?error=exists");
        exit();
    }
}

// Delete table
if (isset($_GET['delete_id'])) {
    $table_id = (int)$_GET['delete_id'];
    
    // Check if table exists and belongs to this restaurant, and get table_no for QR file deletion
    $stmt = $pdo->prepare("SELECT id, table_no FROM restaurant_tables 
                          WHERE id = ? AND restaurant_id = ?");
    $stmt->execute([$table_id, $_SESSION['user_id']]);
    
    if ($stmt->rowCount() > 0) {
        $table = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Delete the table from database
        $stmt = $pdo->prepare("DELETE FROM restaurant_tables 
                              WHERE id = ? AND restaurant_id = ?");
        $stmt->execute([$table_id, $_SESSION['user_id']]);
        
        // Delete the corresponding QR code file
        $qrFilename = "restaurant_{$_SESSION['user_id']}_table_{$table['table_no']}_qrcode.png";
        $qrFilePath = "../assets/qrcodes/" . $qrFilename;
        if (file_exists($qrFilePath)) {
            unlink($qrFilePath);
        }
        
        header("Location: tables.php?deleted=1");
        exit();
    } else {
        header("Location: tables.php?error=notfound");
        exit();
    }
}

// Update table
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_table'])) {
    $table_id = (int)$_POST['table_id'];
    $table_no = $_POST['table_no'];
    $status = $_POST['status'];
    
    // Determine is_occupied based on status
    $is_occupied = ($status === 'occupied') ? 1 : 0;
    
    // Check if table exists and belongs to this restaurant
    $stmt = $pdo->prepare("SELECT id FROM restaurant_tables 
                          WHERE id = ? AND restaurant_id = ?");
    $stmt->execute([$table_id, $_SESSION['user_id']]);
    
    if ($stmt->rowCount() > 0) {
        // Update the table with correct is_occupied status
        $stmt = $pdo->prepare("UPDATE restaurant_tables 
                              SET table_no = ?, status = ?, is_occupied = ?
                              WHERE id = ? AND restaurant_id = ?");
        $stmt->execute([$table_no, $status, $is_occupied, $table_id, $_SESSION['user_id']]);
        
        header("Location: tables.php?updated=1");
        exit();
    } else {
        header("Location: tables.php?error=notfound");
        exit();
    }
}

// Get all tables
$stmt = $pdo->prepare("SELECT * FROM restaurant_tables 
                      WHERE restaurant_id = ? 
                      ORDER BY table_no");
$stmt->execute([$_SESSION['user_id']]);
$tables = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get restaurant name for sidebar
$stmt = $pdo->prepare("SELECT restaurant_name FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$restaurant_name = $stmt->fetchColumn() ?? 'RestaurantPro';

// Get table data for editing if requested
$edit_table = null;
if (isset($_GET['edit_id'])) {
    $table_id = (int)$_GET['edit_id'];
    $stmt = $pdo->prepare("SELECT * FROM restaurant_tables 
                          WHERE id = ? AND restaurant_id = ?");
    $stmt->execute([$table_id, $_SESSION['user_id']]);
    $edit_table = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Table Management - RestaurantPro</title>
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
            --warning: #f8961e;
            --danger: #e76f51;
            --info: #4895ef;
            --border-radius: 12px;
            --border-radius-sm: 8px;
            --shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            --shadow-lg: 0 10px 30px rgba(0, 0, 0, 0.12);
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

        /* Page Header */
        .page-header {
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
            padding: 0.6rem 1.25rem;
            border-radius: var(--border-radius-sm);
            font-weight: 500;
            text-decoration: none;
            transition: var(--transition);
            border: none;
            cursor: pointer;
            font-size: 0.875rem;
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
            background: var(--danger);
            color: white;
        }

        .btn-danger:hover {
            background: #d45b44;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: var(--gray);
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.8rem;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--secondary);
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--light-gray);
            border-radius: var(--border-radius-sm);
            font-family: 'Poppins', sans-serif;
            transition: var(--transition);
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(42, 157, 143, 0.1);
        }

        /* Table Grid */
        .table-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1.5rem;
        }

        .table-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            transition: var(--transition);
            text-align: center;
            position: relative;
            overflow: hidden;
            border-top: 4px solid;
        }

        .table-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .table-card.available {
            border-top-color: var(--success);
        }

        .table-card.occupied {
            border-top-color: var(--danger);
        }

        .table-card.reserved {
            border-top-color: var(--warning);
        }

        .table-card.out_of_service {
            border-top-color: var(--gray);
        }

        .table-card h3 {
            font-size: 1.5rem;
            margin: 0 0 0.5rem;
            color: var(--secondary);
        }

        .table-status {
            display: inline-block;
            padding: 0.35rem 0.75rem;
            border-radius: 2rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 1rem;
        }

        .available .table-status {
            background: rgba(82, 183, 136, 0.1);
            color: var(--success);
        }

        .occupied .table-status {
            background: rgba(231, 111, 81, 0.1);
            color: var(--danger);
        }

        .reserved .table-status {
            background: rgba(248, 150, 30, 0.1);
            color: var(--warning);
        }

        .out_of_service .table-status {
            background: rgba(108, 117, 125, 0.1);
            color: var(--gray);
        }

        .table-actions {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
        }

        .table-icon {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: var(--light-gray);
        }

        .available .table-icon {
            color: var(--success);
        }

        .occupied .table-icon {
            color: var(--danger);
        }

        .reserved .table-icon {
            color: var(--warning);
        }

        .out_of_service .table-icon {
            color: var(--gray);
        }

        /* Alerts */
        .alert {
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            border-radius: var(--border-radius);
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .alert.success {
            background: rgba(82, 183, 136, 0.1);
            color: var(--success);
            border-left: 4px solid var(--success);
        }

        .alert.error {
            background: rgba(231, 111, 81, 0.1);
            color: var(--danger);
            border-left: 4px solid var(--danger);
        }

        .alert.warning {
            background: rgba(248, 150, 30, 0.1);
            color: var(--warning);
            border-left: 4px solid var(--warning);
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: white;
            border-radius: var(--border-radius);
            padding: 2rem;
            width: 90%;
            max-width: 500px;
            box-shadow: var(--shadow-lg);
            animation: modalFadeIn 0.3s ease;
        }

        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--light-gray);
        }

        .modal-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--secondary);
            display: flex;
            align-items: center;
            gap: 0.75rem;
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
            color: var(--danger);
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: var(--gray);
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

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideUp {
            from { 
                opacity: 0;
                transform: translateY(20px);
            }
            to { 
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fade-in {
            animation: fadeIn 0.6s ease forwards;
        }

        .animate-slide-up {
            animation: slideUp 0.6s ease forwards;
        }

        .delay-1 { animation-delay: 0.1s; }
        .delay-2 { animation-delay: 0.2s; }
        .delay-3 { animation-delay: 0.3s; }
        .delay-4 { animation-delay: 0.4s; }
        .delay-5 { animation-delay: 0.5s; }

        /* Responsive */
        @media (max-width: 1024px) {
            .sidebar {
                width: 240px;
            }
            
            .main-content {
                margin-left: 240px;
                padding: 1.5rem;
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
            
            .table-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            }
            
            .page-header {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }
            
            .table-actions {
                flex-direction: column;
            }
        }

        @media (max-width: 576px) {
            .table-grid {
                grid-template-columns: 1fr;
            }
            
            .modal-content {
                width: 95%;
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <?php include "../includes/sidebar.php";?>

         <!-- Main Content -->
        <main class="main-content">
            <!-- Page Header -->
            <div class="page-header animate-fade-in">
                <h1 class="page-title">
                    <i class="fas fa-chair"></i> Table Management
                </h1>
                <div class="animate-fade-in delay-1" style="margin-top: 0.5rem; text-align: right;">
                    <button id="showAddForm" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add New Table
                    </button>
                </div>
            </div>
            
            <!-- Alerts -->
            <?php if (isset($_GET['added'])): ?>
            <div class="alert success animate-fade-in">
                <i class="fas fa-check-circle"></i> Table added successfully!
            </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['updated'])): ?>
            <div class="alert success animate-fade-in">
                <i class="fas fa-check-circle"></i> Table updated successfully!
            </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['deleted'])): ?>
            <div class="alert success animate-fade-in">
                <i class="fas fa-check-circle"></i> Table deleted successfully!
            </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['error']) && $_GET['error'] === 'exists'): ?>
            <div class="alert error animate-fade-in">
                <i class="fas fa-exclamation-circle"></i> A table with that number already exists!
            </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['error']) && $_GET['error'] === 'notfound'): ?>
            <div class="alert error animate-fade-in">
                <i class="fas fa-exclamation-circle"></i> Table not found or you don't have permission to modify it!
            </div>
            <?php endif; ?>
            
            <!-- Add Table Form (Initially Hidden) -->
            <div class="card animate-fade-in delay-2" id="addTableForm" style="display: none;">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-plus-circle"></i> Add New Table
                    </h2>
                </div>
                <form method="POST">
                    <input type="hidden" name="add_table" value="1">
                    <div class="form-group">
                        <label>Table Number</label>
                        <input type="text" name="table_no" required placeholder="e.g., 1, 2, A1, B2">
                    </div>
                    
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" required>
                            <option value="available">Available</option>
                            <option value="occupied">Occupied</option>
                            <option value="reserved">Reserved</option>
                            <option value="out_of_service">Out of Service</option>
                        </select>
                    </div>
                    
                    <div class="form-group" style="display: flex; gap: 1rem;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Table
                        </button>
                        <button type="button" id="cancelAdd" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Tables Display -->
            <div class="card animate-fade-in delay-3">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-list"></i> Current Tables (<?= count($tables) ?>)
                    </h2>
                </div>
                
                <?php if (count($tables) > 0): ?>
                <div class="table-grid">
                    <?php foreach ($tables as $index => $table): 
                        // Use is_occupied field to determine if table is occupied
                        $actual_status = ($table['is_occupied'] == 1) ? 'occupied' : $table['status'];
                    ?>
                    <div class="table-card <?= $actual_status ?> animate-slide-up" style="animation-delay: <?php echo 0.1 + ($index * 0.1); ?>s">
                        <div class="table-content">
                            <div class="table-icon">
                                <i class="fas fa-utensils"></i>
                            </div>
                            <h3>Table <?= htmlspecialchars($table['table_no']) ?></h3>
                            <span class="table-status"><?= ucfirst(str_replace('_', ' ', $actual_status)) ?></span>
                            <div class="table-actions">
                                <a href="tables.php?edit_id=<?= $table['id'] ?>" class="btn btn-outline btn-sm">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <a href="tables.php?delete_id=<?= $table['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this table?')">
                                    <i class="fas fa-trash-alt"></i> Delete
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-table"></i>
                    <p>No tables found. Add your first table to get started!</p>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Edit Table Form (if editing) -->
            <?php if ($edit_table): ?>
            <div class="card animate-fade-in delay-4">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-edit"></i> Edit Table
                    </h2>
                </div>
                <form method="POST">
                    <input type="hidden" name="update_table" value="1">
                    <input type="hidden" name="table_id" value="<?= $edit_table['id'] ?>">
                    
                    <div class="form-group">
                        <label>Table Number</label>
                        <input type="text" name="table_no" value="<?= htmlspecialchars($edit_table['table_no']) ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" required>
                            <option value="available" <?= $edit_table['status'] == 'available' ? 'selected' : '' ?>>Available</option>
                            <option value="occupied" <?= $edit_table['status'] == 'occupied' ? 'selected' : '' ?>>Occupied</option>
                            <option value="reserved" <?= $edit_table['status'] == 'reserved' ? 'selected' : '' ?>>Reserved</option>
                            <option value="out_of_service" <?= $edit_table['status'] == 'out_of_service' ? 'selected' : '' ?>>Out of Service</option>
                        </select>
                    </div>
                    
                    <div class="form-group" style="display: flex; gap: 1rem;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                        <a href="tables.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
            <?php endif; ?>
        </main>
    </div>

    <script>
        // Show/hide add table form
        const showAddForm = document.getElementById('showAddForm');
        const addTableForm = document.getElementById('addTableForm');
        const cancelAdd = document.getElementById('cancelAdd');
        
        showAddForm.addEventListener('click', () => {
            addTableForm.style.display = 'block';
            showAddForm.style.display = 'none';
        });
        
        cancelAdd.addEventListener('click', () => {
            addTableForm.style.display = 'none';
            showAddForm.style.display = 'inline-block';
        });

        // Animate elements when they come into view
        document.addEventListener('DOMContentLoaded', function() {
            const animatedElements = document.querySelectorAll('.animate-slide-up, .animate-fade-in');
            
            animatedElements.forEach(element => {
                element.style.opacity = '0';
            });
            
            setTimeout(() => {
                animatedElements.forEach(element => {
                    element.style.opacity = '1';
                    element.style.transform = 'translateY(0)';
                });
            }, 100);
        });
    </script>
</body>
</html>