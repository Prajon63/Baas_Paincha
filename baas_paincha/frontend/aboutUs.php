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
    <title>About Us - Baas Paincha</title>
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
                <li><a href="contactUs.php">Contact Us</a></li>
                <li><a href="aboutUs.php" class="active">About Us</a></li>
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
        <section class="about-section">
            <div class="about-content">
                <h2>Our Mission</h2>
                <p>At Baas Paincha, we aim to simplify the rental process by connecting tenants with property owners seamlessly. Our platform is designed to make finding and renting a home effortless and transparent.</p>
            </div>

            <div class="team-section">
                <h2>Meet Our Team</h2>
                <div class="team-container">
                    <div class="team-card">
                        <div class="team-image" style="background-image: url('https://via.placeholder.com/150');"></div>
                        <h3>Rakshyak</h3>
                        <p>Project Manager</p>
                    </div>
                    <div class="team-card">
                        <div class="team-image" style="background-image: url('https://via.placeholder.com/150');"></div>
                        <h3>Nitisha</h3>
                        <p>Business Analyst</p>
                    </div>
                    <div class="team-card">
                        <div class="team-image" style="background-image: url('https://via.placeholder.com/150');"></div>
                        <h3>Bipash</h3>
                        <p>Developer</p>
                    </div>
                    <div class="team-card">
                        <div class="team-image" style="background-image: url('https://via.placeholder.com/150');"></div>
                        <h3>Prajwon</h3>
                        <p>Developer</p>
                    </div>
                    <div class="team-card">
                        <div class="team-image" style="background-image: url('https://via.placeholder.com/150');"></div>
                        <h3>Sachin</h3>
                        <p>Developer</p>
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