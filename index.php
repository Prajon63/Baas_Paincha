<?php
session_start();
$isLoggedIn = isset($_SESSION['user_id']);
$role = $_SESSION['role'] ?? '';
$dashboardUrl = $role === 'owner' ? 'ownerDashboard.php' : 'tenantDashboard.php';

// Include database configuration
require 'backend/config.php';

// Fetch properties from the database
try {
    $stmt = $pdo->query("SELECT * FROM properties LIMIT 3");
    $properties = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Decode the JSON image field for each property
    foreach ($properties as &$property) {
        $property['image'] = json_decode($property['image'], true) ?? [];
    }
    unset($property);
} catch (PDOException $e) {
    $properties = [];
    error_log("Error fetching properties: " . $e->getMessage());
}
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
        .property-list {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: center;
            padding: 20px;
        }
        .house-card {
            width: 300px;
            margin: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        .house-card img {
            width: 100%;
            height: 150px; /* Reduced from 200px to make card less portrait */
            object-fit: cover;
            border-radius: 8px 8px 0 0;
        }
        .house-info {
            padding: 10px; /* Reduced from 15px for compactness */
            display: flex;
            flex-direction: column;
            gap: 5px; /* Reduced from 10px for tighter spacing */
        }
        .house-info h3 {
            margin: 0;
            font-size: 1.1em; /* Slightly smaller font for compactness */
        }
        .house-info p {
            margin: 3px 0; /* Reduced from 5px for tighter spacing */
            font-size: 0.9em; /* Slightly smaller font for compactness */
        }
        .house-info .btn {
            padding: 6px 10px; /* Reduced from 8px 12px for compactness */
            font-size: 13px; /* Reduced from 14px */
            text-align: center;
            text-decoration: none;
            border-radius: 4px;
            cursor: pointer;
            background: linear-gradient(135deg, #3182ce, #68d391);
            color: white;
            border: none;
            display: block;
            margin: 5px auto 0; /* Adjusted margin for better alignment */
        }
        .house-info .btn:hover {
            background: linear-gradient(135deg, #2a70b8, #5abf7f);
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
            width: 100%;
            height: 300px;
            object-fit: cover;
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
                <?php foreach ($properties as $index => $property): ?>
                    <div class="house-card" data-property-id="<?php echo $property['id']; ?>">
                        <img src="<?php echo htmlspecialchars($property['image'][0] ?? '/baas_paincha/uploads/placeholder.jpg'); ?>" alt="<?php echo htmlspecialchars($property['title']); ?>">
                        <div class="house-info">
                            <h3><?php echo htmlspecialchars($property['title']); ?></h3>
                            <p>Location: <?php echo htmlspecialchars($property['location']); ?></p>
                            <p>Rs. <?php echo htmlspecialchars($property['rent']); ?>/month</p>
                            <p><?php echo htmlspecialchars($property['description']); ?></p>
                            <?php if ($isLoggedIn): ?>
                                <a href="#" class="btn gradient-btn" onclick='showPropertyDetails(<?php echo $property["id"]; ?>, event, <?php echo json_encode($property["image"]); ?>)'>View Details</a>
                            <?php else: ?>
                                <a href="#" class="btn gradient-btn" onclick="showLoginPrompt(event)">View Details</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </main>

    <!-- Property Details Modal -->
    <div id="property-modal" class="property-modal">
        <div class="property-modal-content">
            <span class="close" onclick="closePropertyModal()">×</span>
            <h2 id="modal-title"></h2>
            <div class="slider">
                <div class="slides" id="slides"></div>
                <button class="prev" onclick="moveSlide(-1)">❮</button>
                <button class="next" onclick="moveSlide(1)">❯</button>
            </div>
            <p><strong>Location:</strong> <span id="modal-location"></span></p>
            <p><strong>Rent:</strong> Rs. <span id="modal-rent"></span>/month</p>
            <p><strong>Description:</strong> <span id="modal-description"></span></p>
        </div>
    </div>

    <footer>
        <p>©️ 2025 Baas Paincha. All rights reserved.</p>
    </footer>
    <script src="script.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        // Password toggle
        document.querySelectorAll('.toggle-password').forEach(function(icon) {
            icon.addEventListener('click', function () {
                const targetId = this.getAttribute('data-target');
                const passwordInput = document.getElementById(targetId);
                if (!passwordInput) return;
                const isVisible = passwordInput.type === 'text';
                passwordInput.type = isVisible ? 'password' : 'text';
                this.classList.toggle('fa-eye');
                this.classList.toggle('fa-eye-slash');
            });
        });

        let slideIndex = 0;
        window.showPropertyDetails = function(propertyId, event, images) {
            event.preventDefault();
            const houseCard = document.querySelector(⁠ .house-card[data-property-id="${propertyId}"] ⁠);
            const title = houseCard.querySelector('h3').textContent;
            const location = houseCard.querySelector('p:nth-child(2)').textContent.replace('Location: ', '');
            const rent = houseCard.querySelector('p:nth-child(3)').textContent.replace('Rs. ', '').replace('/month', '');
            const description = houseCard.querySelector('p:nth-child(4)').textContent;

            document.getElementById('modal-title').textContent = title;
            const slides = document.getElementById('slides');
            slides.innerHTML = '';
            images.forEach(image => {
                const slide = document.createElement('div');
                slide.className = 'slide';
                slide.innerHTML = ⁠ <img src="${image}" alt="${title}"> ⁠;
                slides.appendChild(slide);
            });
            slideIndex = 0;
            updateSlidePosition();

            document.getElementById('modal-location').textContent = location;
            document.getElementById('modal-rent').textContent = rent;
            document.getElementById('modal-description').textContent = description;

            document.getElementById('property-modal').style.display = 'flex';
        };

        window.closePropertyModal = function() {
            document.getElementById('property-modal').style.display = 'none';
        };

        window.showLoginPrompt = function(event) {
            event.preventDefault();
            alert('Please login to view details!');
            document.getElementById('auth-modal').style.display = 'block';
            document.querySelector('.tab-link[data-tab="login"]').click();
        };

        window.moveSlide = function(direction) {
            const slides = document.getElementById('slides');
            const totalSlides = slides.children.length;
            slideIndex += direction;
            if (slideIndex >= totalSlides) slideIndex = 0;
            if (slideIndex < 0) slideIndex = totalSlides - 1;
            updateSlidePosition();
        };

        function updateSlidePosition() {
            const slides = document.getElementById('slides');
            slides.style.transform = ⁠ translateX(-${slideIndex * 100}%) ⁠;
        }
    });
    </script>
</body>
</html>