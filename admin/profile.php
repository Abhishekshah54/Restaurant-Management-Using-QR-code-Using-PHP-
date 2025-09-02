<?php
    require_once '../includes/auth.php';
    require_once '../config/db.php';

    // Initialize variables
    $userData = array(
        'name' => '',
        'email' => '',
        'mobile' => '',
        'restaurant_name' => '',
        'address' => ''
    );
    
    // Fetch user data from database
    $user_id = $_SESSION['user_id']; // Assuming user ID is stored in session
    
    try {
        $stmt = $pdo->prepare("SELECT name, email, mobile, restaurant_name, address FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$userData) {
            // Handle case where user is not found
            $userData = array(
                'name' => '',
                'email' => '',
                'mobile' => '',
                'restaurant_name' => '',
                'address' => ''
            );
        }
    } catch (PDOException $e) {
        // Handle database error
        error_log("Database error: " . $e->getMessage());
    }
    
    // Get restaurant name for sidebar
    $restaurant_name = $userData['restaurant_name'] ?? 'RestaurantPro';
    $current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Settings - RestaurantPro</title>
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

        /* Notification Styles */
        .notification {
            padding: 15px;
            margin: 20px 0;
            border-radius: 8px;
            display: none;
            align-items: center;
            gap: 10px;
        }

        .success {
            background-color: #d4edda;
            color: var(--success);
            border: 1px solid #c3e6cb;
        }

        .error {
            background-color: #f8d7da;
            color: var(--danger);
            border: 1px solid #f5c6cb;
        }

        /* Profile Card */
        .profile-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            transition: var(--transition);
            margin-bottom: 2rem;
        }

        .profile-card:hover {
            box-shadow: var(--shadow-lg);
        }

        .profile-header {
            background: var(--primary);
            color: white;
            padding: 2rem;
            text-align: center;
            position: relative;
            border-radius: var(--border-radius) var(--border-radius) 0 0;
            margin: -1.5rem -1.5rem 2rem -1.5rem;
        }

        .avatar-container {
            position: relative;
            display: inline-block;
            margin-bottom: 15px;
        }

        .avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background-color: var(--accent);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2.5rem;
            margin: 0 auto;
            border: 4px solid rgba(255, 255, 255, 0.2);
            position: relative;
            overflow: hidden;
        }

        .avatar-upload {
            position: absolute;
            bottom: 0;
            right: 0;
            background: var(--accent);
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            border: 2px solid white;
        }

        .avatar-upload input {
            display: none;
        }

        .avatar-upload i {
            font-size: 14px;
        }

        .profile-name {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }

        .profile-role {
            color: rgba(255, 255, 255, 0.8);
            font-size: 1rem;
        }

        .form-section {
            margin-bottom: 2rem;
        }

        .section-title {
            font-size: 1.3rem;
            color: var(--primary);
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--light);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title i {
            color: var(--accent);
        }

        .form-row {
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .form-group {
            flex: 1 1 calc(50% - 1.5rem);
            min-width: 250px;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--dark);
        }

        .form-input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: var(--border-radius-sm);
            font-size: 16px;
            transition: var(--transition);
        }

        .form-input:focus {
            border-color: var(--accent);
            outline: none;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }

        .password-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .password-wrapper input {
            width: 100%;
            padding-right: 2.5rem; /* space for the eye icon */
        }

        .password-toggle {
            position: absolute;
            right: 0.75rem;
            cursor: pointer;
            color: var(--accent);
            font-size: 14px;
            margin-top: 5px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .password-strength {
            height: 5px;
            margin-top: 5px;
            border-radius: 3px;
            background: #eee;
            overflow: hidden;
        }

        .password-strength-bar {
            height: 100%;
            width: 0%;
            transition: var(--transition);
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

        .btn-secondary {
            background: var(--gray);
            color: white;
        }

        .btn-secondary:hover {
            background: #7f8c8d;
            transform: translateY(-2px);
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
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
            .main-content {
                margin-left: 240px;
                padding: 1.5rem;
            }
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
            
            .form-group {
                flex: 1 1 100%;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .profile-header {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Include Sidebar -->
        <?php include '../includes/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Page Header -->
            <div class="page-header animate-fade-in">
                <h1 class="page-title">
                    <i class="fas fa-user"></i> Profile Settings
                </h1>
            </div>

            <div class="notification success" id="successNotification">
                <i class="fas fa-check-circle"></i> 
                <span id="successMessage">Profile updated successfully!</span>
            </div>
            
            <div class="notification error" id="errorNotification">
                <i class="fas fa-exclamation-circle"></i> 
                <span id="errorMessage">There was an error updating your profile.</span>
            </div>

            <div class="profile-card animate-slide-up delay-1">
                <div class="profile-header">
                    <div class="avatar-container">
                        <div class="avatar" id="avatar">
                            <span id="avatarInitials">
                                <?php
                                    // Generate initials from name
                                    $names = explode(' ', $userData['name']);
                                    $initials = '';
                                    if (count($names) > 0) {
                                        $initials = $names[0][0];
                                        if (count($names) > 1) {
                                            $initials .= $names[count($names)-1][0];
                                        }
                                    }
                                    echo $initials;
                                ?>
                            </span>
                        </div>
                        <label class="avatar-upload" title="Change profile picture">
                            <i class="fas fa-camera"></i>
                            <input type="file" id="avatarUpload" accept="image/*">
                        </label>
                    </div>
                    <h2 class="profile-name" id="profileName"><?php echo htmlspecialchars($userData['name']); ?></h2>
                    <p class="profile-role">Restaurant Owner</p>
                </div>

                <form id="profileForm" action="update_profile.php" method="POST">
                    <div class="form-section">
                        <h3 class="section-title">
                            <i class="fas fa-user"></i>
                            <span>Personal Information</span>
                        </h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="name" class="form-label">Full Name</label>
                                <input type="text" id="name" name="name" class="form-input" value="<?php echo htmlspecialchars($userData['name']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" id="email" name="email" class="form-input" value="<?php echo htmlspecialchars($userData['email']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="mobile" class="form-label">Mobile Number</label>
                                <input type="tel" id="mobile" name="mobile" class="form-input" value="<?php echo htmlspecialchars($userData['mobile']); ?>" 
                                       pattern="[0-9]{10}" title="Please enter a valid 10-digit phone number" required>
                                <small>Format: 10 digits without any spaces or special characters</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h3 class="section-title">
                            <i class="fas fa-store"></i>
                            <span>Restaurant Information</span>
                        </h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="restaurantName" class="form-label">Restaurant Name</label>
                                <input type="text" id="restaurantName" name="restaurantName" class="form-input" value="<?php echo htmlspecialchars($userData['restaurant_name']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="address" class="form-label">Address</label>
                                <input type="text" id="address" name="address" class="form-input" value="<?php echo htmlspecialchars($userData['address']); ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h3 class="section-title">
                            <i class="fas fa-lock"></i>
                            <span>Password Change</span>
                        </h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="currentPassword" class="form-label">Current Password</label>
                                <div class="password-wrapper">
                                    <input type="password" id="currentPassword" name="currentPassword" class="form-input" placeholder="Enter current password to make changes">
                                    <i class="fas fa-eye password-toggle" onclick="togglePassword('currentPassword', this)"></i>
                                </div>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="newPassword" class="form-label">New Password</label>
                                <div class="password-wrapper">
                                    <input type="password" id="newPassword" name="newPassword" class="form-input" placeholder="At least 6 characters">
                                    <i class="fas fa-eye password-toggle" onclick="togglePassword('newPassword', this)"></i>
                                </div>
                                <div class="password-strength">
                                    <div class="password-strength-bar" id="passwordStrengthBar"></div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="confirmPassword" class="form-label">Confirm New Password</label>
                                <div class="password-wrapper">
                                    <input type="password" id="confirmPassword" name="confirmPassword" class="form-input" placeholder="Confirm your new password">
                                    <i class="fas fa-eye password-toggle" onclick="togglePassword('confirmPassword', this)"></i>
                                </div>
                            </div>
                        </div>

                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Profile
                        </button>
                        <button type="reset" class="btn btn-secondary" id="resetButton">
                            <i class="fas fa-undo"></i> Reset Changes
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
        // Function to toggle password visibility
        function togglePassword(inputId) {
            const passwordInput = document.getElementById(inputId);
            const icon = passwordInput.parentNode.querySelector('.password-toggle i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
        
        // Function to check password strength
        function checkPasswordStrength(password) {
            let strength = 0;
            
            if (password.length >= 6) strength++;
            if (password.length >= 8) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            
            return strength;
        }
        
        // Function to update password strength indicator
        function updatePasswordStrength() {
            const password = document.getElementById('newPassword').value;
            const strength = checkPasswordStrength(password);
            const strengthBar = document.getElementById('passwordStrengthBar');
            
            // Reset strength bar
            strengthBar.style.width = '0%';
            strengthBar.style.backgroundColor = '#e74c3c';
            
            if (password.length > 0) {
                const width = (strength / 5) * 100;
                strengthBar.style.width = width + '%';
                
                // Change color based on strength
                if (strength >= 4) {
                    strengthBar.style.backgroundColor = '#2ecc71';
                } else if (strength >= 2) {
                    strengthBar.style.backgroundColor = '#f39c12';
                }
            }
        }
        
        // Function to validate the form
        function validateForm(formData) {
            // Check if new password fields are filled
            const currentPassword = formData.get('currentPassword');
            const newPassword = formData.get('newPassword');
            const confirmPassword = formData.get('confirmPassword');
            
            // Validate phone number
            const mobileInput = document.getElementById('mobile');
            const mobilePattern = /^[0-9]{10}$/;
            if (!mobilePattern.test(mobileInput.value)) {
                showError('Please enter a valid 10-digit phone number.');
                mobileInput.focus();
                return false;
            }
            
            if (newPassword || confirmPassword || currentPassword) {
                if (!currentPassword) {
                    showError('Please enter your current password to change your password.');
                    return false;
                }
                
                if (newPassword !== confirmPassword) {
                    showError('New password and confirmation do not match.');
                    return false;
                }
                
                if (newPassword.length < 6) {
                    showError('New password must be at least 6 characters long.');
                    return false;
                }
            }
            
            return true;
        }
        
        // Function to show error message
        function showError(message) {
            const errorNotification = document.getElementById('errorNotification');
            const errorMessage = document.getElementById('errorMessage');
            
            errorMessage.textContent = message;
            errorNotification.style.display = 'flex';
            
            // Hide notification after 5 seconds
            setTimeout(() => {
                errorNotification.style.display = 'none';
            }, 5000);
        }
        
        // Function to show success message
        function showSuccess(message) {
            const successNotification = document.getElementById('successNotification');
            const successMessage = document.getElementById('successMessage');
            
            successMessage.textContent = message;
            successNotification.style.display = 'flex';
            
            // Hide notification after 5 seconds
            setTimeout(() => {
                successNotification.style.display = 'none';
            }, 5000);
        }
        
        // Function to handle form submission
        function handleFormSubmit(event) {
            event.preventDefault();
            
            const form = event.target;
            const formData = new FormData(form);
            
            if (!validateForm(formData)) {
                return;
            }
            
            // Submit the form via AJAX
            fetch(form.action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success notification
                    showSuccess(data.message || 'Profile updated successfully!');
                    
                    // Update the displayed name if it was changed
                    const nameInput = document.getElementById('name');
                    if (nameInput.value !== '<?php echo $userData["name"]; ?>') {
                        const names = nameInput.value.split(' ');
                        let initials = '';
                        if (names.length > 0) {
                            initials = names[0].charAt(0);
                            if (names.length > 1) {
                                initials += names[names.length - 1].charAt(0);
                            }
                        }
                        document.getElementById('avatarInitials').textContent = initials;
                        document.getElementById('profileName').textContent = nameInput.value;
                    }
                } else {
                    // Show error notification
                    showError(data.message || 'There was an error updating your profile.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showError('Network error. Please try again.');
            });
        }
        
        // Function to handle avatar upload
        function handleAvatarUpload(event) {
            const file = event.target.files[0];
            if (!file) return;
            
            if (!file.type.match('image.*')) {
                showError('Please select a valid image file.');
                return;
            }
            
            if (file.size > 2 * 1024 * 1024) { // 2MB limit
                showError('Image size must be less than 2MB.');
                return;
            }
            
            const reader = new FileReader();
            reader.onload = function(e) {
                const avatar = document.getElementById('avatar');
                avatar.style.backgroundImage = `url(${e.target.result})`;
                avatar.style.backgroundSize = 'cover';
                avatar.style.backgroundPosition = 'center';
                document.getElementById('avatarInitials').style.display = 'none';
                
                // In a real application, you would upload the image to the server here
                showSuccess('Profile picture updated successfully!');
            };
            reader.readAsDataURL(file);
        }
        
        // Initialize the page
        document.addEventListener('DOMContentLoaded', () => {
            document.getElementById('profileForm').addEventListener('submit', handleFormSubmit);
            document.getElementById('avatarUpload').addEventListener('change', handleAvatarUpload);
            document.getElementById('newPassword').addEventListener('input', updatePasswordStrength);
            
            // Add input validation for mobile number
            const mobileInput = document.getElementById('mobile');
            mobileInput.addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '');
                if (this.value.length > 10) {
                    this.value = this.value.slice(0, 10);
                }
            });
            
            // Reset button functionality
            document.getElementById('resetButton').addEventListener('click', function() {
                document.getElementById('newPassword').value = '';
                document.getElementById('confirmPassword').value = '';
                document.getElementById('currentPassword').value = '';
                document.getElementById('passwordStrengthBar').style.width = '0%';
            });

            // Animate elements when they come into view
            const animatedElements = document.querySelectorAll('.animate-slide-up, .animate-fade-in');
            
            animatedElements.forEach(element => {
                element.style.opacity = '0';
            });
            
            setTimeout(() => {
                animatedElements.forEach(element => {
                    element.style.opacity = '1';
                    if (element.classList.contains('animate-slide-up')) {
                        element.style.transform = 'translateY(0)';
                    }
                });
            }, 100);
        });
    </script>
</body>
</html>