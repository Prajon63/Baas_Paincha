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
    <title>Baas Paincha - Home</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="logo"><a href="index.php">Baas Paincha</a></div>
            <ul class="nav-links">
                <li><a href="index.php" class="active">Home</a></li>
                <li><a href="contactUs.php">Contact Us</a></li>
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
        <section class="hero">
            <h1>Find Your Perfect Home</h1>
            <p>Rent your dream space with ease and confidence.</p>
            <a href="#features" class="btn cta-btn">Explore Now</a>
        </section>

        <section class="features" id="features">
            <div class="feature-card">
                <h3>Wide Variety</h3>
                <p>From cozy apartments to spacious houses.</p>
            </div>
            <div class="feature-card">
                <h3>Clear Visuals</h3>
                <p>High-quality images for every property.</p>
            </div>
            <div class="feature-card">
                <h3>Flexible Pricing</h3>
                <p>Options for every budget.</p>
            </div>
        </section>

        <section class="house-listings">
            <h2>Featured Properties</h2>
            <div class="property-list" id="property-list">
                <!-- Properties will be loaded dynamically -->
            </div>
        </section>
    </main>

    <footer>
        <p>© 2025 Baas Paincha. All rights reserved.</p>
    </footer>

    <script src="script.js"></script>
    <script>
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