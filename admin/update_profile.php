<?php
require_once '../includes/auth.php';
require_once '../config/db.php';

header('Content-Type: application/json');

// Initialize response array
$response = array('success' => false, 'message' => '');

try {
    // Get form data
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $mobile = $_POST['mobile'] ?? '';
    $restaurantName = $_POST['restaurantName'] ?? '';
    $address = $_POST['address'] ?? '';
    $currentPassword = $_POST['currentPassword'] ?? '';
    $newPassword = $_POST['newPassword'] ?? '';
    
    // Validate required fields
    if (empty($name) || empty($email) || empty($mobile) || empty($restaurantName) || empty($address)) {
        $response['message'] = 'Please fill in all required fields.';
        echo json_encode($response);
        exit;
    }
    
    // Validate mobile number format (10 digits)
    if (!preg_match('/^[0-9]{10}$/', $mobile)) {
        $response['message'] = 'Please enter a valid 10-digit phone number.';
        echo json_encode($response);
        exit;
    }
    
    $user_id = $_SESSION['user_id'];
    
    // Check if password change is requested
    if (!empty($currentPassword) || !empty($newPassword)) {
        // Verify current password
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if (!$user || !password_verify($currentPassword, $user['password'])) {
            $response['message'] = 'Current password is incorrect.';
            echo json_encode($response);
            exit;
        }
        
        // Update password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hashedPassword, $user_id]);
    }
    
    // Update user profile
    $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, mobile = ?, restaurant_name = ?, address = ? WHERE id = ?");
    $success = $stmt->execute([$name, $email, $mobile, $restaurantName, $address, $user_id]);
    
    if ($success) {
        $response['success'] = true;
        $response['message'] = 'Profile updated successfully.';
    } else {
        $response['message'] = 'Failed to update profile. Please try again.';
    }
    
} catch (PDOException $e) {
    $response['message'] = 'Database error: ' . $e->getMessage();
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
}

echo json_encode($response);