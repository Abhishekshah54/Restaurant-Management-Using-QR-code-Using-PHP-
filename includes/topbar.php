<?php
// topbar.php
// This file should be included in pages that need the top bar
?>

<div class="top-bar animate-fade-in">
    <h1 class="page-title">
        <i class="fas fa-tachometer-alt"></i> Dashboard Overview
    </h1>
    
    <div class="user-menu">
        <div class="date-display">
            <i class="far fa-calendar-alt"></i>
            <?php echo date('l, F j, Y'); ?>
        </div>
        
        <div class="user-profile">
                <div class="user-avatar">
                    <?php 
                        $initial = strtoupper(substr($_SESSION['name'] ?? 'U', 0, 1));
                        echo $initial;
                    ?>
                </div>
                <a href="profile.php" style="text-decoration: none;">
                    <div class="user-info">
                        <span class="user-name"><?php echo $_SESSION['name'] ?? 'User'; ?></span>
                        <span class="user-role">Restaurant Manager</span>
                    </div>
                </a>
        </div>
    </div>
</div>

<style>
    /* Top Bar Styles */
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

    .user-profile {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.5rem;
        border-radius: var(--border-radius-sm);
        transition: var(--transition);
        cursor: pointer;
    }

    .user-profile:hover {
        background: var(--light);
    }

    .user-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: var(--primary);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 600;
    }

    .user-info {
        display: flex;
        flex-direction: column;
    }

    .user-name {
        font-weight: 600;
        color: var(--dark);
    }

    .user-role {
        font-size: 0.8rem;
        color: var(--gray);
    }

    @media (max-width: 768px) {
        .top-bar {
            flex-direction: column;
            gap: 1rem;
            align-items: flex-start;
        }
        
        .user-menu {
            width: 100%;
            justify-content: space-between;
        }
    }
</style>