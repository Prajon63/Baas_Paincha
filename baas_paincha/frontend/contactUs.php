<?php
session_start();
$isLoggedIn = isset($_SESSION['user_id']);
$role = $_SESSION['role'] ?? '';
$dashboardUrl = $role === 'owner' ? 'ownerDashboard.php' : 'tenantDashboard.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Baas Paincha</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="logo"><a href="index.php">Baas Paincha</a></div>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="contactUs.php" class="active">Contact Us</a></li>
                <li><a href="aboutUs.php">About Us</a></li>
                <li>
                    <?php if ($isLoggedIn): ?>
                        <a href="<?php echo $dashboardUrl; ?>" title="Go to Dashboard">
                            <i class="fas fa-user-circle"></i>
                        </a>
                    <?php else: ?>
                        <a href="#" id="login-link">Login/Register</a>
                    <?php endif; ?>
                </li>
            </ul>
            <div class="hamburger">☰</div>
        </nav>
    </header>

    <!-- Auth Modal -->
    <div id="auth-modal" class="modal">
        <div class="modal-content">
            <span class="close">×</span>
            <div class="tabs">
                <button class="tab-link active" data-tab="login">Login</button>
                <button class="tab-link" data-tab="register">Register</button>
            </div>
            <div id="login" class="tab-content active">
                <h2>Login</h2>
                <form id="login-form">
                    <input type="email" name="email" placeholder="Email" required>
                    <input type="password" name="password" placeholder="Password" required>
                    <a href="#" class="forgot-password">Forgot Password?</a>
                    <div class="login-options">
                        <button type="submit" class="btn gradient-btn" data-role="owner">Login as Property Owner</button>
                        <button type="submit" class="btn gradient-btn" data-role="tenant">Login as Tenant</button>
                    </div>
                </form>
                <p>New to Baas Paincha? <a href="#" class="switch-tab" data-tab="register">Register now</a></p>
            </div>
            <div id="register" class="tab-content">
                <h2>Register</h2>
                <form id="register-form">
                    <input type="text" name="full_name" placeholder="Full Name" required>
                    <input type="email" name="email" placeholder="Email" required>
                    <input type="password" name="password" placeholder="Password" required>
                    <input type="password" name="confirm_password" placeholder="Confirm Password" required>
                    <select name="role" required>
                        <option value="" disabled selected>Select Role...</option>
                        <option value="owner">Property Owner</option>
                        <option value="tenant">Tenant</option>
                    </select>
                    <div class="register-button-container">
                        <button type="submit" class="btn gradient-btn">Create Account</button>
                    </div>
                </form>
                <p>Already have an account? <a href="#" class="switch-tab" data-tab="login">Login</a></p>
            </div>
        </div>
    </div>

    <main>
        <section class="contact-section">
            <div class="contact-container">
                <div class="contact-form">
                    <h2>Contact Us</h2>
                    <form id="contact-form">
                        <input type="text" name="name" placeholder="Your Name" required>
                        <input type="email" name="email" placeholder="Your Email" required>
                        <input type="text" name="subject" placeholder="Subject" required>
                        <textarea name="message" rows="5" placeholder="Your Message" required></textarea>
                        <button type="submit" class="btn gradient-btn">Send Message</button>
                    </form>
                </div>
                <div class="contact-info">
                    <h2>Get in Touch</h2>
                    <div class="info-item">
                        <p><strong>Email:</strong> support@baaspaincha.com</p>
                        <p><strong>Phone:</strong> +977 1-443-9000</p>
                        <p><strong>Address:</strong> Herald College Kathmandu, Naxal, Kathmandu, Nepal</p>
                    </div>
                    <div class="map">
                        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3532.221766252888!2d85.32396031506276!3d27.71724598278147!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x39eb190a56a6d2e7%3A0x3f6f7e2f3b6f7e2!2sHerald%20College%20Kathmandu!5e0!3m2!1sen!2snp!4v1634567890123!5m2!1sen!2snp" allowfullscreen="" loading="lazy"></iframe>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer>
        <p>© 2025 Baas Paincha. All rights reserved.</p>
    </footer>
    <script src="script.js"></script>
</body>
</html>