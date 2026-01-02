</main>
    <!-- End Main Content -->

    <!-- Footer -->
    <footer class="main-footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>About Us</h3>
                    <p>Your trusted online clothing store offering the latest fashion trends and styles.</p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
                
                <div class="footer-section">
                    <h3>Customer Service</h3>
                    <ul>
                        <li><a href="<?php echo SITE_URL; ?>/about.php">About Us</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/contact.php">Contact Us</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/shipping-info.php">Shipping Info</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/returns-policy.php">Returns</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/faq.php">FAQ</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/size-guide.php">Size Guide</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h3>My Account</h3>
                    <ul>
                        <li><a href="<?php echo SITE_URL; ?>/profile.php">My Profile</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/orders.php">Order History</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/wishlist.php">Wishlist</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/cart.php">Shopping Cart</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h3>Newsletter</h3>
                    <p>Subscribe to get special offers and updates</p>
                    <form class="newsletter-form">
                        <input type="email" placeholder="Enter your email">
                        <button type="submit"><i class="fas fa-paper-plane"></i></button>
                    </form>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
                <div class="payment-methods">
                    <i class="fab fa-cc-visa"></i>
                    <i class="fab fa-cc-mastercard"></i>
                    <i class="fab fa-cc-paypal"></i>
                    <i class="fab fa-cc-amex"></i>
                </div>
            </div>
        </div>
    </footer>

    <!-- JavaScript Files -->
    <script src="<?php echo SITE_URL; ?>/assets/js/main.js"></script>
    <script src="<?php echo SITE_URL; ?>/assets/js/password-strength.js"></script>
</body>
</html>