<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Property Owner Dashboard - Baas Paincha</title>
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
                <li><a href="aboutUs.php">About Us</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
            <div class="hamburger">☰</div>
        </nav>
    </header>

    <main>
        <div class="dashboard-container">
            <div class="dashboard-header">
                <h1>Property Owner Dashboard</h1>
                <a href="#" class="btn gradient-btn" onclick="toggleAddForm()">Add New Property</a>
            </div>

            <div class="add-property-form" id="add-property-form">
                <h2>Add New Property</h2>
                <form id="property-form">
                    <input type="text" name="title" placeholder="Property Title" required>
                    <input type="text" name="location" placeholder="Location" required>
                    <input type="number" name="rent" placeholder="Rent (Rs.)" required>
                    <input type="url" name="image" placeholder="Image URL" required>
                    <textarea name="description" rows="3" placeholder="Description" required></textarea>
                    <button type="submit" class="btn gradient-btn">Add Property</button>
                </form>
            </div>

            <div class="dashboard-section">
                <h2>My Properties</h2>
                <div class="property-list" id="property-list">
                    <!-- Properties will be loaded dynamically -->
                </div>
            </div>

            <div class="dashboard-section">
                <h2>Tenant Requests</h2>
                <div class="request-list" id="request-list">
                    <!-- Requests will be loaded dynamically -->
                </div>
            </div>
        </div>
    </main>

    <footer>
        <p>© 2025 Baas Paincha. All rights reserved.</p>
    </footer>

    <script src="script.js"></script>
    <script>
        let properties = [];

        const dummyRequests = [
            {
                tenantName: 'John Doe',
                propertyTitle: 'Cozy Family Home',
                message: 'Interested in renting this property for my family.'
            },
            {
                tenantName: 'Jane Smith',
                propertyTitle: 'Modern Apartment',
                message: 'Can I schedule a visit this weekend?'
            }
        ];

        function toggleAddForm() {
            const form = document.getElementById('add-property-form');
            form.classList.toggle('active');
        }

        function renderProperties() {
            const propertyList = document.getElementById('property-list');
            propertyList.innerHTML = '';
            properties.forEach((property, index) => {
                const card = document.createElement('div');
                card.className = 'house-card';
                card.innerHTML = `
                    <img src="${property.image}" alt="${property.title}">
                    <div class="house-info">
                        <h3>${property.title}</h3>
                        <p>Location: ${property.location}</p>
                        <p>Rs. ${property.rent}/month</p>
                        <p>${property.description}</p>
                        <a href="#" class="btn view-btn">View Details</a>
                        <button class="btn delete-btn" onclick="deleteProperty(${index})">Delete</button>
                    </div>
                `;
                propertyList.appendChild(card);
            });
        }

        function renderRequests() {
            const requestList = document.getElementById('request-list');
            requestList.innerHTML = '';
            dummyRequests.forEach(request => {
                const card = document.createElement('div');
                card.className = 'request-card';
                card.innerHTML = `
                    <h3>${request.tenantName}</h3>
                    <p>Property: ${request.propertyTitle}</p>
                    <p>Message: ${request.message}</p>
                    <a href="#" class="btn gradient-btn" onclick="alert('Response feature will be implemented soon!')">Respond</a>
                `;
                requestList.appendChild(card);
            });
        }

        document.getElementById('property-form').addEventListener('submit', (e) => {
            e.preventDefault();
            const form = e.target;
            const newProperty = {
                title: form.title.value,
                location: form.location.value,
                rent: parseFloat(form.rent.value),
                image: form.image.value,
                description: form.description.value
            };
            properties.push(newProperty);
            form.reset();
            toggleAddForm();
            renderProperties();
        });

        function deleteProperty(index) {
            if (confirm('Are you sure you want to delete this property?')) {
                properties.splice(index, 1);
                renderProperties();
            }
        }

        // Initial render
        renderProperties();
        renderRequests();
    </script>
</body>
</html>