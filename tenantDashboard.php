<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'tenant') {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tenant Dashboard - Baas Paincha</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="logo"><a href="index.php">Baas Paincha</a></div>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="contactUs.php">Contact Us</a></li>
                <li><a href="aboutUs.php">About Us</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
            <div class="hamburger">☰</div>
        </nav>
    </header>

    <main>
        <div class="dashboard-container">
            <h1>Tenant Dashboard</h1>

            <section>
                <form class="search-form" id="search-form">
                    <div class="search-input-wrapper">
                        <i class="fas fa-map-marker-alt"></i>
                        <input type="text" name="location" id="location-search" placeholder="Enter location">
                    </div>
                    <div class="search-input-wrapper">
                        <i class="fas fa-rupee-sign"></i>
                        <input type="number" name="rate" id="rate-search" placeholder="Around rate (Rs./month)" step="1000">
                    </div>
                    <button type="submit" class="btn gradient-btn"><i class="fas fa-search"></i> Search</button>
                </form>

                <h2>Available Properties</h2>
                <div class="property-list" id="property-list">
                    <!-- Properties will be loaded dynamically -->
                </div>
            </section>
        </div>
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

        function renderProperties(properties) {
            const propertyList = document.getElementById('property-list');
            propertyList.innerHTML = '';
            properties.forEach(property => {
                const card = document.createElement('div');
                card.className = 'house-card';
                card.innerHTML = `
                    <img src="${property.image}" alt="${property.title}">
                    <div class="house-info">
                        <h3>${property.title}</h3>
                        <p>Location: ${property.location}</p>
                        <p>Rs. ${property.rent}/month</p>
                        <p>${property.description}</p>
                        <a href="#" class="btn gradient-btn" onclick="alert('Request to rent will be implemented soon!')">Request to Rent</a>
                    </div>
                `;
                propertyList.appendChild(card);
            });
        }

        // Initial render
        renderProperties(dummyProperties);

        // Search functionality
        document.getElementById('search-form').addEventListener('submit', (e) => {
            e.preventDefault();
            const location = document.getElementById('location-search').value.toLowerCase();
            const rate = parseFloat(document.getElementById('rate-search').value);

            const filteredProperties = dummyProperties.filter(property => {
                const matchesLocation = location ? property.location.toLowerCase().includes(location) : true;
                const matchesRate = rate ? property.rent >= rate - 3000 && property.rent <= rate + 3000 : true;
                return matchesLocation && matchesRate;
            });

            renderProperties(filteredProperties);
        });
    </script>
</body>
</html>