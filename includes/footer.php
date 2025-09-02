        </main>

        <footer class="footer">
            <div class="footer-content">
                <div class="footer-links">
                    <a href="privacy.php">Privacy Policy</a>
                    <a href="terms.php">Terms of Service</a>
                    <a href="contact.php">Contact Us</a>
                    <a href="support.php">Support</a>
                </div>
                
                <div class="social-links">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-linkedin-in"></i></a>
                </div>
                
                <p class="copyright">&copy; <?php echo date('Y'); ?> RestaurantPro. All rights reserved.</p>
            </div>
        </footer>

        <style>
            .footer {
                background: var(--secondary);
                color: white;
                padding: 2rem 1rem;
                text-align: center;
                margin-top: auto;
            }

            .footer-content {
                max-width: 100%;
                margin: 0 auto;
            }

            .footer-links {
                display: flex;
                justify-content: center;
                gap: 2rem;
                margin-bottom: 1.5rem;
            }

            .footer-links a {
                color: var(--light);
                text-decoration: none;
                transition: var(--transition);
            }

            .footer-links a:hover {
                color: var(--accent);
            }

            .copyright {
                margin-top: 1rem;
                font-size: 0.9rem;
                color: var(--light-gray);
            }

            .social-links {
                display: flex;
                justify-content: center;
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

            .main-content {
                flex: 1;
                padding: 2rem;
            }

            @media (max-width: 768px) {
                .footer-links {
                    flex-direction: column;
                    gap: 1rem;
                }
            }
        </style>
    </body>
</html>