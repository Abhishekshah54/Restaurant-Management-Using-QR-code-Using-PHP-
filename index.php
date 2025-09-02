<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RestaurantPro - Complete Restaurant Management Solution</title>
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
            position: sticky;
            top: 0;
            z-index: 100;
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

        /* Hero Section */
        .hero {
            background: linear-gradient(rgba(38, 70, 83, 0.85), rgba(38, 70, 83, 0.9)), 
                         url('https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxzZWFyY2h8Mnx8cmVzdGF1cmFudCUyMGludGVyaW9yfGVufDB8fDB8fA%3D%3D&auto=format&fit=crop&w=1200&q=80');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 5rem 2rem;
            text-align: center;
        }

        .hero-content {
            max-width: 1000px;
            margin: 0 auto;
        }

        .hero h1 {
            font-size: 3.5rem;
            margin-bottom: 1.5rem;
            line-height: 1.2;
        }

        .hero p {
            font-size: 1.2rem;
            margin-bottom: 2.5rem;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }

        .hero-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn-primary {
            background: var(--accent);
            color: var(--secondary);
            padding: 1rem 2rem;
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        .btn-secondary {
            background: transparent;
            color: white;
            border: 2px solid white;
            padding: 1rem 2rem;
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-secondary:hover {
            background: white;
            color: var(--secondary);
        }

        /* Features Section */
        .features {
            padding: 5rem 2rem;
            background: white;
        }

        .section-header {
            text-align: center;
            max-width: 800px;
            margin: 0 auto 4rem;
        }

        .section-header h2 {
            font-size: 2.5rem;
            color: var(--secondary);
            margin-bottom: 1rem;
        }

        .section-header p {
            font-size: 1.1rem;
            color: var(--gray);
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .feature-card {
            background: var(--light);
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: var(--shadow);
            transition: var(--transition);
            text-align: center;
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
        }

        .feature-icon {
            background: var(--primary-light);
            color: var(--primary-dark);
            width: 70px;
            height: 70px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            margin: 0 auto 1.5rem;
        }

        .feature-card h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: var(--secondary);
        }

        .feature-card p {
            color: var(--gray);
        }

        /* How It Works Section */
        .how-it-works {
            padding: 5rem 2rem;
            background: var(--light);
        }

        .steps {
            display: flex;
            justify-content: center;
            gap: 2rem;
            max-width: 1200px;
            margin: 0 auto;
            flex-wrap: wrap;
        }

        .step {
            background: white;
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: var(--shadow);
            text-align: center;
            flex: 1;
            min-width: 250px;
            max-width: 300px;
        }

        .step-number {
            background: var(--primary);
            color: white;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0 auto 1.5rem;
        }

        .step h3 {
            font-size: 1.3rem;
            margin-bottom: 1rem;
            color: var(--secondary);
        }

        /* Testimonials */
        .testimonials {
            padding: 5rem 2rem;
            background: white;
        }

        .testimonial-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .testimonial {
            background: var(--light);
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: var(--shadow);
            position: relative;
        }

        .testimonial-text {
            font-style: italic;
            margin-bottom: 1.5rem;
            color: var(--secondary);
        }

        .testimonial-author {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .author-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
        }

        .author-info h4 {
            color: var(--secondary);
            margin-bottom: 0.25rem;
        }

        .author-info p {
            color: var(--gray);
            font-size: 0.9rem;
        }

        /* Pricing Section */
        .pricing {
            padding: 5rem 2rem;
            background: var(--light);
        }

        .pricing-plans {
            display: flex;
            justify-content: center;
            gap: 2rem;
            max-width: 1200px;
            margin: 0 auto;
            flex-wrap: wrap;
        }

        .plan {
            background: white;
            border-radius: var(--border-radius);
            padding: 3rem 2rem;
            box-shadow: var(--shadow);
            text-align: center;
            flex: 1;
            min-width: 280px;
            max-width: 350px;
            transition: var(--transition);
        }

        .plan.popular {
            border: 2px solid var(--primary);
            transform: scale(1.05);
        }

        .plan-header h3 {
            font-size: 1.5rem;
            color: var(--secondary);
            margin-bottom: 0.5rem;
        }

        .plan-price {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 1.5rem;
        }

        .plan-price span {
            font-size: 1rem;
            font-weight: 400;
            color: var(--gray);
        }

        .plan-features {
            list-style: none;
            margin-bottom: 2rem;
        }

        .plan-features li {
            padding: 0.5rem 0;
            color: var(--gray);
            border-bottom: 1px solid var(--light-gray);
        }

        .plan-features li:last-child {
            border-bottom: none;
        }

        /* CTA Section */
        .cta {
            padding: 5rem 2rem;
            background: linear-gradient(rgba(42, 157, 143, 0.9), rgba(29, 120, 116, 0.9)), 
                         url('https://images.unsplash.com/photo-1414235077428-338989a2e8c0?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxzZWFyY2h8M3x8cmVzdGF1cmFudCUyMGZvb2R8ZW58MHx8MHx8&auto=format&fit=crop&w=1200&q=80');
            background-size: cover;
            background-position: center;
            color: white;
            text-align: center;
        }

        .cta-content {
            max-width: 800px;
            margin: 0 auto;
        }

        .cta h2 {
            font-size: 2.5rem;
            margin-bottom: 1.5rem;
        }

        .cta p {
            font-size: 1.2rem;
            margin-bottom: 2.5rem;
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
            
            .hero h1 {
                font-size: 2.5rem;
            }
            
            .hero-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .steps, .pricing-plans {
                flex-direction: column;
                align-items: center;
            }
            
            .plan.popular {
                transform: none;
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

    <section class="hero">
        <div class="hero-content">
            <h1>Streamline Your Restaurant Operations</h1>
            <p>All-in-one restaurant management solution for inventory, orders, staff, and customer management. Increase efficiency and grow your business.</p>
            <div class="hero-buttons">
                <a href="register.php" class="btn-primary">
                    <i class="fas fa-rocket"></i> Get Started Free
                </a>
                <a href="#features" class="btn-secondary">
                    <i class="fas fa-play-circle"></i> See Features
                </a>
            </div>
        </div>
    </section>

    <section class="features" id="features">
        <div class="section-header">
            <h2>Powerful Features for Your Restaurant</h2>
            <p>Everything you need to manage your restaurant efficiently in one platform</p>
        </div>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-utensils"></i>
                </div>
                <h3>Menu Management</h3>
                <p>Easily create, update, and organize your menu items with categories, prices, and descriptions.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-shopping-basket"></i>
                </div>
                <h3>Inventory Tracking</h3>
                <p>Track ingredients, set low stock alerts, and manage supplier information all in one place.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-receipt"></i>
                </div>
                <h3>Order Management</h3>
                <p>Streamline order taking, kitchen communication, and billing processes for better efficiency.</p>
            </div>
        </div>
    </section>

    <section class="how-it-works">
        <div class="section-header">
            <h2>How RestaurantPro Works</h2>
            <p>Get started in just three simple steps</p>
        </div>
        <div class="steps">
            <div class="step">
                <div class="step-number">1</div>
                <h3>Create Your Account</h3>
                <p>Sign up and provide basic information about your restaurant.</p>
            </div>
            <div class="step">
                <div class="step-number">2</div>
                <h3>Set Up Your Restaurant</h3>
                <p>Add your menu, staff details, and inventory information.</p>
            </div>
            <div class="step">
                <div class="step-number">3</div>
                <h3>Start Managing</h3>
                <p>Begin using the system to streamline your operations.</p>
            </div>
        </div>
    </section>

    <section class="pricing">
        <div class="section-header">
            <h2>Simple, Transparent Pricing</h2>
            <p>Choose the plan that works best for your restaurant</p>
        </div>
        <div class="pricing-plans">
            <div class="plan">
                <div class="plan-header">
                    <h3>Starter</h3>
                    <div class="plan-price">$49<span>/month</span></div>
                </div>
                <ul class="plan-features">
                    <li>Up to 49 users</li>
                    <li>Basic menu management</li>
                    <li>Inventory tracking</li>
                    <li>Email support</li>
                    <li>Basic reporting</li>
                </ul>
                <a href="register.php" class="btn-primary">Get Started</a>
            </div>
            <div class="plan popular">
                <div class="plan-header">
                    <h3>Professional</h3>
                    <div class="plan-price">$99<span>/month</span></div>
                </div>
                <ul class="plan-features">
                    <li>Up to 100 users</li>
                    <li>Advanced menu management</li>
                    <li>Inventory + supplier management</li>
                    <li>Priority support</li>
                    <li>Advanced analytics</li>
                    <li>Staff scheduling</li>
                </ul>
                <a href="register.php" class="btn-primary">Get Started</a>
            </div>
            <div class="plan">
                <div class="plan-header">
                    <h3>Enterprise</h3>
                    <div class="plan-price">$199<span>/month</span></div>
                </div>
                <ul class="plan-features">
                    <li>Unlimited users</li>
                    <li>Multi-location support</li>
                    <li>Full feature access</li>
                    <li>24/7 dedicated support</li>
                    <li>Custom reporting</li>
                    <li>API access</li>
                </ul>
                <a href="register.php" class="btn-primary">Get Started</a>
            </div>
        </div>
    </section>

    <section class="cta">
        <div class="cta-content">
            <h2>Ready to Transform Your Restaurant?</h2>
            <p>Join thousands of restaurants that have streamlined their operations with RestaurantPro</p>
            <a href="register.php" class="btn-primary">
                <i class="fas fa-user-plus"></i> Start Your Free Trial
            </a>
        </div>
    </section>

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
</body>
</html>