<?php
require_once '../includes/auth.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'Admin Panel'; ?></title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <header class="admin-header">
        <div class="header-container">
            <div class="branding">
                <h1><i class="fas fa-utensils"></i> <?php echo $_SESSION['restaurant_name']; ?></h1>
            </div>
            
            <nav class="admin-nav">
                <ul>
                    <li><a href="dashboard.php" <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'class="active"' : ''; ?>>
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a></li>
                    <li><a href="menu.php" <?php echo basename($_SERVER['PHP_SELF']) == 'menu.php' ? 'class="active"' : ''; ?>>
                        <i class="fas fa-book-open"></i> Menu
                    </a></li>
                    <li><a href="orders.php" <?php echo basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'class="active"' : ''; ?>>
                        <i class="fas fa-clipboard-list"></i> Orders
                        <?php 
                        $stmt = $GLOBALS['pdo']->prepare("SELECT COUNT(*) FROM orders 
                                                         WHERE restaurant_id = ? 
                                                         AND status IN ('Pending', 'Preparing')");
                        $stmt->execute([$_SESSION['user_id']]);
                        $pending_orders = $stmt->fetchColumn();
                        if ($pending_orders > 0): ?>
                            <span class="badge"><?php echo $pending_orders; ?></span>
                        <?php endif; ?>
                    </a></li>
                    <li><a href="tables.php" <?php echo basename($_SERVER['PHP_SELF']) == 'tables.php' ? 'class="active"' : ''; ?>>
                        <i class="fas fa-chair"></i> Tables
                    </a></li>
                    <li><a href="qr_generator.php" <?php echo basename($_SERVER['PHP_SELF']) == 'qr_generator.php' ? 'class="active"' : ''; ?>>
                        <i class="fas fa-qrcode"></i> QR Codes
                    </a></li>
                    <li><a href="reports.php" <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'class="active"' : ''; ?>>
                        <i class="fas fa-chart-bar"></i> Reports
                    </a></li>
                </ul>
            </nav>
            
            <div class="user-controls">
                <div class="dropdown">
                    <button class="dropbtn">
                        <i class="fas fa-user-circle"></i> <?php echo $_SESSION['email']; ?>
                        <i class="fas fa-caret-down"></i>
                    </button>
                    <div class="dropdown-content">
                        <a href="profile.php"><i class="fas fa-cog"></i> Profile</a>
                        <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="admin-main">