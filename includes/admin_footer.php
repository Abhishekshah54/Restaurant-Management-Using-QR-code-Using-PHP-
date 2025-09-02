<footer class="admin-footer">
    <div class="footer-container">
        <div class="footer-section">
            <h4><?php echo $_SESSION['restaurant_name']; ?></h4>
            <p>QR Menu & Ordering System</p>
            <p>&copy; <?php echo date('Y'); ?> All Rights Reserved</p>
        </div>
        
        <div class="footer-section">
            <h4>Quick Links</h4>
            <ul>
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="menu.php"><i class="fas fa-book-open"></i> Menu</a></li>
                <li><a href="orders.php"><i class="fas fa-clipboard-list"></i> Orders</a></li>
                <li><a href="tables.php"><i class="fas fa-chair"></i> Tables</a></li>
            </ul>
        </div>
        
        <div class="footer-section">
            <h4>Support</h4>
            <ul>
                <li><a href="#"><i class="fas fa-question-circle"></i> Help Center</a></li>
                <li><a href="#"><i class="fas fa-envelope"></i> Contact Us</a></li>
                <li><a href="#"><i class="fas fa-file-alt"></i> Documentation</a></li>
            </ul>
        </div>
        
        <div class="footer-section">
            <h4>System Info</h4>
            <p>Version: 2.0.0</p>
            <p>PHP: <?php echo phpversion(); ?></p>
            <p>Database: MySQL</p>
        </div>
    </div>
</footer>

<!-- Include JavaScript files -->
<script src="../assets/js/main.js"></script>
</body>
</html>