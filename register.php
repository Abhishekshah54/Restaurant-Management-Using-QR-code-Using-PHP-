<?php
require_once 'config/db.php';
require_once 'functions.php';

// Initialize variables
$error = '';
$success = '';
$formData = [
    'name' => '',
    'email' => '',
    'phone' => '',
    'restaurant_name' => '',
    'address' => ''
];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize and store form data
    $name             = trim($_POST['name']);
    $email            = trim($_POST['email']);
    $mobile           = trim($_POST['phone']);
    $password         = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $restaurant_name  = trim($_POST['restaurant_name']);
    $address          = trim($_POST['address']);
    
    // Store form data for repopulation
    $formData = [
        'name' => $name,
        'email' => $email,
        'phone' => $mobile,
        'restaurant_name' => $restaurant_name,
        'address' => $address
    ];

    // Validate inputs
    if (empty($name) || empty($email) || empty($mobile) || empty($password) || 
        empty($confirm_password) || empty($restaurant_name) || empty($address)) {
        $error = "All fields are required!";
    } 
    // Check password match
    elseif ($password !== $confirm_password) {
        $error = "Passwords do not match!";
    } 
    // Validate email format
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format!";
    }
    // Validate password strength
    elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long!";
    }
    else {
        // Check duplicate email or mobile
        $check_sql = "SELECT * FROM users WHERE email = :email OR mobile = :mobile LIMIT 1";
        $check_stmt = $pdo->prepare($check_sql);
        $check_stmt->execute([
            ':email'  => $email,
            ':mobile' => $mobile
        ]);
 
        if ($check_stmt->rowCount() > 0) {
            $error = "Email or Mobile number already exists!";
        } else {
            // Hash password
            $hashed_pass = password_hash($password, PASSWORD_DEFAULT);

            // Insert new user
            $sql = "INSERT INTO users (name, email, mobile, password, restaurant_name, address) 
                    VALUES (:name, :email, :mobile, :password, :restaurant_name, :address)";

            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([
                ':name'            => $name,
                ':email'           => $email,
                ':mobile'          => $mobile,
                ':password'        => $hashed_pass,
                ':restaurant_name' => $restaurant_name,
                ':address'         => $address
            ]);

            if ($result) {
                $success = "Registration successful! Redirecting to login...";
                // Clear form data
                $formData = [
                    'name' => '',
                    'email' => '',
                    'phone' => '',
                    'restaurant_name' => '',
                    'address' => ''
                ];
                
                // Redirect to login page after 2 seconds
                header("refresh:2;url=login.php");
            } else {
                $error = "Registration failed. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RestaurantPro - Register</title>
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
            --shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            --border-radius: 10px;
            --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', system-ui, -apple-system, sans-serif;
        }

        body {
            background-color: var(--light);
            color: var(--dark);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            line-height: 1.6;
        }

        /* Header Styles */
        .header {
            background-color: white;
            box-shadow: var(--shadow);
            padding: 1rem 0;
            width: 100%;
        }

        .header-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 2rem;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            text-decoration: none;
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
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--secondary);
        }

        .nav-links {
            display: flex;
            gap: 2rem;
            align-items: center;
        }

        .nav-links a {
            color: var(--dark);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
            padding: 0.5rem 0;
            position: relative;
        }

        .nav-links a:not(.btn):after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: 0;
            left: 0;
            background-color: var(--primary);
            transition: width 0.3s ease;
        }

        .nav-links a:not(.btn):hover:after {
            width: 100%;
        }

        .nav-links a:hover {
            color: var(--primary);
        }

        .btn {
            padding: 0.6rem 1.5rem;
            border-radius: 50px;
            font-weight: 600;
            transition: var(--transition);
        }

        .btn-login {
            color: var(--primary);
            border: 2px solid var(--primary);
        }

        .btn-login:hover {
            background-color: var(--primary);
            color: white;
        }

        .btn-register {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        /* Main Content */
        .main-content {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            background-image: linear-gradient(rgba(248, 249, 250, 0.95), rgba(248, 249, 250, 0.95)), 
                              url('https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxzZWFyY2h8Mnx8cmVzdGF1cmFudCUyMGludGVyaW9yJTdyZXN0YXVyYW50JTIwZGluaW5nJmF1dG89Zm9ybWF0JmZpdD1jcm9wJnc9MTIwMCZxPTgw');
            background-size: cover;
            background-position: center;
        }

        /* Auth Container */
        .auth-container {
            width: 100%;
            max-width: 900px;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .auth-header {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 2.5rem 2rem;
            text-align: center;
            position: relative;
        }

        .auth-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--accent);
        }

        .auth-header h2 {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
        }

        .auth-header p {
            font-size: 0.95rem;
            opacity: 0.9;
        }

        .auth-body {
            padding: 2.5rem 2rem;
        }

        /* Horizontal Form Layout */
        .form-row {
            display: flex;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .form-group {
            flex: 1;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.75rem;
            font-weight: 600;
            color: var(--secondary);
            font-size: 0.95rem;
        }

        .input-with-icon {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary);
            font-size: 1.1rem;
            width: 1.5rem;
            text-align: center;
        }

        .form-control {
            width: 100%;
            padding: 0.9rem 1rem 0.9rem 2.8rem;
            border: 1px solid var(--light-gray);
            border-radius: var(--border-radius);
            font-size: 0.95rem;
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

        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--gray);
        }

        .btn-auth {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            border: none;
            border-radius: var(--border-radius);
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            margin-top: 1.5rem;
        }

        .btn-auth:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .btn-auth:active {
            transform: translateY(0);
        }

        .auth-footer {
            text-align: center;
            padding: 1.5rem 2rem;
            color: var(--gray);
            font-size: 0.95rem;
            border-top: 1px solid var(--light-gray);
        }

        .auth-footer a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
        }

        .auth-footer a:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        .error {
            color: var(--error);
            background-color: rgba(231, 111, 81, 0.1);
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            border-left: 4px solid var(--error);
        }

        .error i {
            font-size: 1.2rem;
        }

        .success {
            color: var(--success);
            background-color: rgba(82, 183, 136, 0.1);
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            border-left: 4px solid var(--success);
        }

        .success i {
            font-size: 1.2rem;
        }

        .logo-auth {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            gap: 0.75rem;
        }

        .logo-auth-icon {
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

        .password-strength {
            margin-top: 0.5rem;
            height: 5px;
            border-radius: 5px;
            background: var(--light-gray);
            overflow: hidden;
        }

        .password-strength-bar {
            height: 100%;
            width: 0;
            transition: width 0.3s ease;
        }

        .password-strength-weak {
            background-color: var(--error);
            width: 33%;
        }

        .password-strength-medium {
            background-color: var(--accent);
            width: 66%;
        }

        .password-strength-strong {
            background-color: var(--success);
            width: 100%;
        }

        .password-hint {
            font-size: 0.8rem;
            color: var(--gray);
            margin-top: 0.25rem;
        }

        /* Footer Styles */
        .footer {
            background: var(--secondary);
            color: white;
            padding: 3rem 1rem;
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
        }

        .footer-column h3 {
            font-size: 1.2rem;
            margin-bottom: 1.5rem;
            position: relative;
            padding-bottom: 0.5rem;
        }

        .footer-column h3:after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 40px;
            height: 2px;
            background: var(--accent);
        }

        .footer-links {
            list-style: none;
        }

        .footer-links li {
            margin-bottom: 0.75rem;
        }

        .footer-links a {
            color: var(--light-gray);
            text-decoration: none;
            transition: var(--transition);
        }

        .footer-links a:hover {
            color: var(--accent);
            padding-left: 0.5rem;
        }

        .social-links {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .social-links a {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            transition: var(--transition);
        }

        .social-links a:hover {
            background: var(--primary);
            transform: translateY(-3px);
        }

        .copyright {
            text-align: center;
            margin-top: 3rem;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            color: var(--light-gray);
            font-size: 0.9rem;
        }

        /* Responsive styles */
        @media (max-width: 768px) {
            .header-container {
                flex-direction: column;
                gap: 1rem;
                padding: 0 1rem;
            }
            
            .nav-links {
                flex-wrap: wrap;
                justify-content: center;
                gap: 1rem;
            }
            
            .auth-container {
                margin: 1rem;
            }
            
            .form-row {
                flex-direction: column;
                gap: 1rem;
            }
            
            .footer-links {
                flex-direction: column;
                gap: 1rem;
            }
            
            .auth-header, .auth-body {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-container">
            <a href="index.php" class="logo">
                <div class="logo-icon">
                    <i class="fas fa-utensils"></i>
                </div>
                <span class="logo-text">RestaurantPro</span>
            </a>
            <nav class="nav-links">
                <a href="index.php">Home</a>
                <a href="login.php" >Login</a>
                <a href="register.php" >Register</a>
            </nav>
        </div>
    </header>

    <main class="main-content">
        <div class="auth-container">
            <div class="auth-header">
                <div class="logo-auth">
                    <div class="logo-auth-icon">
                        <i class="fas fa-utensils"></i>
                    </div>
                    <h2>RestaurantPro</h2>
                </div>
                <p>Create your restaurant management account</p>
            </div>
            
            <div class="auth-body">
                <?php if (!empty($error)): ?>
                    <div class="error">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="success">
                        <i class="fas fa-check-circle"></i>
                        <span><?php echo htmlspecialchars($success); ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" id="registrationForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">Your Name</label>
                            <div class="input-with-icon">
                                <i class="fas fa-user input-icon"></i>
                                <input type="text" id="name" name="name" class="form-control" 
                                       placeholder="John Doe" required value="<?php echo htmlspecialchars($formData['name']); ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <div class="input-with-icon">
                                <i class="fas fa-envelope input-icon"></i>
                                <input type="email" id="email" name="email" class="form-control" 
                                       placeholder="your@email.com" required value="<?php echo htmlspecialchars($formData['email']); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="password">Password</label>
                            <div class="input-with-icon">
                                <i class="fas fa-lock input-icon"></i>
                                <input type="password" id="password" name="password" class="form-control" 
                                       placeholder="••••••••" required minlength="8">
                                <span class="password-toggle" id="passwordToggle">
                                    <i class="fas fa-eye"></i>
                                </span>
                            </div>
                            <div class="password-strength" id="passwordStrength">
                                <div class="password-strength-bar" id="passwordStrengthBar"></div>
                            </div>
                            <div class="password-hint">Must be at least 8 characters</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirm Password</label>
                            <div class="input-with-icon">
                                <i class="fas fa-lock input-icon"></i>
                                <input type="password" id="confirm_password" name="confirm_password" 
                                       class="form-control" placeholder="••••••••" required>
                                <span class="password-toggle" id="confirmPasswordToggle">
                                    <i class="fas fa-eye"></i>
                                </span>
                            </div>
                            <div id="passwordMatch" class="password-hint"></div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="restaurant_name">Restaurant Name</label>
                            <div class="input-with-icon">
                                <i class="fas fa-store input-icon"></i>
                                <input type="text" id="restaurant_name" name="restaurant_name" class="form-control" 
                                       placeholder="My Awesome Restaurant" required value="<?php echo htmlspecialchars($formData['restaurant_name']); ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <div class="input-with-icon">
                                <i class="fas fa-phone input-icon"></i>
                                <input type="tel" id="phone" name="phone" class="form-control" 
                                       placeholder="(123) 456-7890" required value="<?php echo htmlspecialchars($formData['phone']); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="address">Restaurant Address</label>
                            <div class="input-with-icon">
                                <i class="fas fa-map-marker-alt input-icon"></i>
                                <input type="text" id="address" name="address" class="form-control" 
                                       placeholder="123 Main St, City, State" required value="<?php echo htmlspecialchars($formData['address']); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-auth">
                        <i class="fas fa-user-plus"></i> Register Now
                    </button>
                </form>
            </div>
            
            <div class="auth-footer">
                Already have an account? <a href="login.php">Sign in here</a>
            </div>
        </div>
    </main>

        <footer class="footer">
        <div class="footer-content">
            <div class="footer-column">
                <h3>RestaurantPro</h3>
                <p>Complete restaurant management solution for establishments of all sizes.</p>
                <div class="social-links">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>
            <div class="footer-column">
                <h3>Features</h3>
                <ul class="footer-links">
                    <li><a href="#">Menu Management</a></li>
                    <li><a href="#">Inventory Tracking</a></li>
                    <li><a href="#">Order Management</a></li>
                </ul>
            </div>
            <div class="footer-column">
                <h3>Company</h3>
                <ul class="footer-links">
                    <li><a href="#">About Us</a></li>
                    <li><a href="#">Contact</a></li>
                    <li><a href="#">Careers</a></li>
                    <li><a href="#">Blog</a></li>
                    <li><a href="#">Press</a></li>
                </ul>
            </div>
            <div class="footer-column">
                <h3>Support</h3>
                <ul class="footer-links">
                    <li><a href="#">Help Center</a></li>
                    <li><a href="#">FAQ</a></li>
                    <li><a href="#">Documentation</a></li>
                    <li><a href="#">Community</a></li>
                    <li><a href="#">Privacy Policy</a></li>
                </ul>
            </div>
        </div>
        <div class="copyright">
            &copy; 2025 RestaurantPro. All rights reserved.
        </div>
    </footer>

    <script>
        // Password visibility toggle
        const passwordToggle = document.getElementById('passwordToggle');
        const confirmPasswordToggle = document.getElementById('confirmPasswordToggle');
        const passwordField = document.getElementById('password');
        const confirmPasswordField = document.getElementById('confirm_password');
        
        passwordToggle.addEventListener('click', function() {
            const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordField.setAttribute('type', type);
            this.querySelector('i').classList.toggle('fa-eye');
            this.querySelector('i').classList.toggle('fa-eye-slash');
        });
        
        confirmPasswordToggle.addEventListener('click', function() {
            const type = confirmPasswordField.getAttribute('type') === 'password' ? 'text' : 'password';
            confirmPasswordField.setAttribute('type', type);
            this.querySelector('i').classList.toggle('fa-eye');
            this.querySelector('i').classList.toggle('fa-eye-slash');
        });
        
        // Password strength indicator
        passwordField.addEventListener('input', function() {
            const strengthBar = document.getElementById('passwordStrengthBar');
            const password = this.value;
            let strength = 0;
            
            // Reset classes
            strengthBar.className = 'password-strength-bar';
            
            if (password.length >= 8) strength++;
            if (password.match(/[a-z]+/)) strength++;
            if (password.match(/[A-Z]+/)) strength++;
            if (password.match(/[0-9]+/)) strength++;
            if (password.match(/[!@#$%^&*(),.?":{}|<>]+/)) strength++;
            
            if (password.length > 0) {
                if (strength < 2) {
                    strengthBar.classList.add('password-strength-weak');
                } else if (strength < 4) {
                    strengthBar.classList.add('password-strength-medium');
                } else {
                    strengthBar.classList.add('password-strength-strong');
                }
            }
        });
        
        // Password confirmation check
        confirmPasswordField.addEventListener('input', function() {
            const passwordMatch = document.getElementById('passwordMatch');
            if (this.value !== passwordField.value) {
                passwordMatch.textContent = "Passwords don't match";
                passwordMatch.style.color = 'var(--error)';
            } else {
                passwordMatch.textContent = "Passwords match";
                passwordMatch.style.color = 'var(--success)';
            }
        });
        
        // Form validation
        document.getElementById('registrationForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return false;
            }
            
            if (password.length < 8) {
                e.preventDefault();
                alert('Password must be at least 8 characters long!');
                return false;
            }
        });
    </script>
</body>
</html>