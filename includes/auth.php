<?php
session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isKitchenStaff() {
    // Add logic if you have separate kitchen staff logins
    return isLoggedIn();
}

// Redirect to login if not authenticated
if (!isLoggedIn() && basename($_SERVER['PHP_SELF']) != 'login.php') {
    header('Location: ../login.php');
    exit();
}
?>