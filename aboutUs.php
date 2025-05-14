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
     <style>
        .password-container {
            position: relative;
            width: 100%;
        }
        .password-container input {
            width: 100%;
            padding: 10px;
            padding-right: 40px;
            box-sizing: border-box;
        }
        .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
        }
        .house-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 8px 8px 0 0;
        }
        .property-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        .property-modal-content {
            background: white;
            padding: 20px;
            border-radius: 8px;
            max-width: 500px;
            width: 90%;
            position: relative;
        }
        .property-modal-content img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
        }
        .property-modal-content .close {
            position: absolute;
            top: 10px;
            right: 15px;
            font-size: 24px;
            cursor: pointer;
        }
        .slider {
            position: relative;
            width: 100%;
            overflow: hidden;
        }
        .slides {
            display: flex;
            transition: transform 0.5s ease;
        }
        .slide {
            min-width: 100%;
            box-sizing: border-box;
        }
        .slider button {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(0, 0, 0, 0.5);
            color: white;
            border: none;
            padding: 10px;
            cursor: pointer;
        }
        .slider .prev {
            left: 10px;
        }
        .slider .next {
            right: 10px;
        }
    </style>
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
    <div id="auth-modal" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="close">×</span>
            <div class="tabs">
                <button class="tab-link active" data-tab="login">Login</button>
                <button class="tab-link" data-tab="register">Register</button>
                <button class="tab-link" data-tab="forgot-password">Forgot Password</button>
            </div>
            <div id="login" class="tab-content active">
                <h2>Login</h2>
                <form id="login-form">
                    <input type="email" name="email" placeholder="Email" required>
                    <div class="password-container">
                        <input type="password" id="login-password" name="password" placeholder="Password" required>
                        <i class="fas fa-eye toggle-password" data-target="login-password"></i>
                    </div>
                    <a href="#" class="forgot-password switch-tab" data-tab="forgot-password">Forgot Password?</a>
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
                    <div class="password-container">
                        <input type="password" id="register-password" name="password" placeholder="Password" required>
                        <i class="fas fa-eye toggle-password" data-target="register-password"></i>
                    </div>
                    <div class="password-container">
                        <input type="password" id="confirm-password" name="confirm_password" placeholder="Confirm Password" required>
                        <i class="fas fa-eye toggle-password" data-target="confirm-password"></i>
                    </div>
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
            <div id="forgot-password" class="tab-content">
                <h2>Reset Password</h2>
                <form id="forgot-password-form">
                    <input type="email" name="email" placeholder="Enter your email" required>
                    <button type="submit" class="btn gradient-btn">Send Reset Link</button>
                </form>
                <p>Back to <a href="#" class="switch-tab" data-tab="login">Login</a></p>
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
        <p>©️ 2025 Baas Paincha. All rights reserved.</p>
    </footer>
    <script src="script.js"></script>
    <script>
        // Password toggle function
        document.querySelectorAll('.toggle-password').forEach(function(icon) {
            icon.addEventListener('click', function() {
                var targetId = icon.getAttribute('data-target');
                var passwordField = document.getElementById(targetId);
                var isPasswordVisible = passwordField.type === 'text';
                passwordField.type = isPasswordVisible ? 'password' : 'text';
                icon.classList.toggle('fa-eye-slash', !isPasswordVisible);
            });
        });

        // Dummy data for property listing
        const dummyProperties = [
            {
                title: 'Cozy Family Home',
                location: 'Kathmandu, Nepal',
                rent: 12000,
                image: 'https://via.placeholder.com/300x200?text=Cozy+Home',
                description: 'A cozy home for families'
            },
            {
                title: 'Modern Apartment',
                location: 'Lalitpur, Nepal',
                rent: 9000,
                image: 'https://via.placeholder.com/300x200?text=Modern+Apt',
                description: 'Modern living space'
            },
            {
                title: 'Luxury House',
                location: 'Bhaktapur, Nepal',
                rent: 18000,
                image: 'https://via.placeholder.com/300x200?text=Luxury+House',
                description: 'Luxurious living'
            }
        ];

        function renderProperties() {
            const propertyList = document.getElementById('property-list');
            propertyList.innerHTML = '';
            dummyProperties.forEach(property => {
                const card = document.createElement('div');
                card.className = 'house-card';
                card.innerHTML = `
                    <img src="${property.image}" alt="${property.title}">
                    <div class="house-info">
                        <h3>${property.title}</h3>
                        <p>Location: ${property.location}</p>
                        <p>Rs. ${property.rent}/month</p>
                        <p>${property.description}</p>
                        <a href="#" class="btn gradient-btn" onclick="alert('Please login to view details!')">View Details</a>
                    </div>
                `;
                propertyList.appendChild(card);
            });
        }

        // Initial render
        renderProperties();
    </script>
</body>
</html>