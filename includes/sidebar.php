<?php
// sidebar.php
// This file should be included in pages that need the sidebar

// Get restaurant name if not already set
if (!isset($restaurant_name)) {
    require_once '../config/db.php';
    $restaurant_id = $_SESSION['user_id'];
    $stmt = $pdo->prepare("SELECT restaurant_name FROM users WHERE id = ?");
    $stmt->execute([$restaurant_id]);
    $restaurant_name = $stmt->fetchColumn() ?? 'RestaurantPro';
}

// Get current page to set active class
$current_page = basename($_SERVER['PHP_SELF']);
?>

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
            <a href="dashboard.php" class="menu-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
        </li>
        <li class="menu-item">
            <a href="menu.php" class="menu-link <?php echo $current_page == 'menu.php' ? 'active' : ''; ?>">
                <i class="fas fa-book-open"></i>
                <span>Menu</span>
            </a>
        </li>
        <li class="menu-item">
            <a href="orders.php" class="menu-link <?php echo $current_page == 'orders.php' ? 'active' : ''; ?>">
                <i class="fas fa-clipboard-list"></i>
                <span>Orders</span>
            </a>
        </li>
        <li class="menu-item">
            <a href="tables.php" class="menu-link <?php echo $current_page == 'tables.php' ? 'active' : ''; ?>">
                <i class="fas fa-calendar-alt"></i>
                <span>Tables</span>
            </a>
        </li>
        <li class="menu-item">
            <a href="qr_generator.php" class="menu-link <?php echo $current_page == 'qr_generator.php' ? 'active' : ''; ?>">
                <i class="fas fa-qrcode"></i>
                <span>QR Codes</span>
            </a>
        </li>
        <li class="menu-item">
            <a href="bill.php" class="menu-link <?php echo $current_page == 'bill.php' ? 'active' : ''; ?>">
                <i class="fas fa-qrcode"></i>
                <span>Bill Generate</span>
            </a>
        </li>
        <li class="menu-item">
            <a href="reports.php" class="menu-link <?php echo $current_page == 'reports.php' ? 'active' : ''; ?>">
                <i class="fas fa-chart-bar"></i>
                <span>Reports</span>
            </a>
        </li>
        <li class="menu-item">
            <a href="profile.php" class="menu-link <?php echo $current_page == 'profile.php' ? 'active' : ''; ?>">
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

<style>
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

    @media (max-width: 1024px) {
        .sidebar {
            width: 240px;
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
    }
</style>