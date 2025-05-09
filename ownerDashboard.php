<?php
require 'backend/config.php';

// Check if the user is logged in and has the owner role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    header('Location: index.php');
    exit;
}

// Fetch properties for the logged-in owner
$owner_id = $_SESSION['user_id'];
try {
    $stmt = $pdo->prepare("SELECT * FROM properties WHERE owner_id = ?");
    $stmt->execute([$owner_id]);
    $properties = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Decode the JSON image field for each property
    foreach ($properties as &$property) {
        $property['image'] = json_decode($property['image'], true) ?? [];
    }
    unset($property); // Unset reference to avoid issues
} catch (PDOException $e) {
    $properties = [];
    error_log("Error fetching properties: " . $e->getMessage());
    echo "Error fetching properties. Please try again later.";
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
    <style>
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
        .file-input-container input {
            margin-bottom: 10px;
        }
        .file-input-container {
            margin-bottom: 15px; /* Add spacing below the file inputs */
        }
        .btn.secondary {
            padding: 5px 10px;
            font-size: 12px;
            background: rgb(78, 115, 148); /* Secondary button color */
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            display: block; /* Ensure the button takes its own line */
            margin-bottom: 15px; /* Add spacing below the button */
        }
        .btn.secondary:hover {
            background: rgb(28, 71, 102);
        }
        .message-container {
            margin-top: 10px;
            text-align: center;
        }
        .message-container.success {
            color: green;
        }
        .message-container.error {
            color: red;
        }
        .add-property-form textarea {
            margin-top: 10px; /* Add spacing above the textarea */
            width: 100%; /* Ensure the textarea takes full width */
            box-sizing: border-box; /* Prevent overflow */
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
                <?php if (isset($_GET['success'])): ?>
                    <div class="message-container success" id="success-message">
                        <?php echo htmlspecialchars($_GET['success']); ?>
                    </div>
                <?php elseif (isset($_GET['error'])): ?>
                    <div class="message-container error" id="error-message">
                        <?php echo htmlspecialchars($_GET['error']); ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="add-property-form" id="add-property-form">
                <h2>Add New Property</h2>
                <form id="property-form" action="backend/add_property.php" method="POST" enctype="multipart/form-data">
                    <input type="text" name="title" placeholder="Property Title" required>
                    <input type="text" name="location" placeholder="Location" required>
                    <input type="number" name="rent" placeholder="Rent (Rs.)" required>
                    <div class="file-input-container" id="file-input-container">
                        <input type="file" name="image[]" accept="image/*" id="file-input-0" required>
                    </div>
                    <button type="button" id="add-more-files" class="btn secondary">Add Another File</button>
                    <textarea name="description" rows="3" placeholder="Description" required></textarea>
                    <button type="submit" class="btn gradient-btn">Add Property</button>
                </form>
            </div>

            <div class="dashboard-section">
                <h2>My Properties</h2>
                <div class="property-list" id="property-list">
                    <?php foreach ($properties as $index => $property): ?>
                        <div class="house-card" data-property-id="<?php echo $property['id']; ?>">
                            <img src="<?php echo htmlspecialchars($property['image'][0] ?? '/baas_paincha/Uploads/placeholder.jpg'); ?>" alt="<?php echo htmlspecialchars($property['title']); ?>">
                            <div class="house-info">
                                <h3><?php echo htmlspecialchars($property['title']); ?></h3>
                                <p>Location: <?php echo htmlspecialchars($property['location']); ?></p>
                                <p>Rs. <?php echo htmlspecialchars($property['rent']); ?>/month</p>
                                <p><?php echo htmlspecialchars($property['description']); ?></p>
                                <a href="#" class="btn view-btn" onclick='showPropertyDetails(<?php echo $property["id"]; ?>, event, <?php echo json_encode($property["image"]); ?>)'>View Details</a>
                                <form action="backend/delete_property.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="property_id" value="<?php echo $property['id']; ?>">
                                    <button type="submit" class="btn delete-btn">Delete</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="dashboard-section">
                <h2>Tenant Requests</h2>
                <div class="request-list" id="request-list">
                    <!-- Requests will be loaded dynamically (still dummy for now) -->
                </div>
            </div>
        </div>

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
    </main>

    <footer>
        <p>© 2025 Baas Paincha. All rights reserved.</p>
    </footer>

    <script src="script.js"></script>
    <script>
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

        let fileIndex = 0;

        function toggleAddForm() {
            const form = document.getElementById('add-property-form');
            form.classList.toggle('active');
        }

        document.getElementById('add-more-files').addEventListener('click', function() {
            fileIndex++;
            const container = document.getElementById('file-input-container');
            const newInput = document.createElement('input');
            newInput.type = 'file';
            newInput.name = 'image[]';
            newInput.accept = 'image/*';
            newInput.id = `file-input-${fileIndex}`;
            newInput.required = false; // Subsequent inputs are optional
            container.appendChild(newInput);
            container.appendChild(document.createElement('br')); // Add spacing
        });

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

        let slideIndex = 0;
        function showPropertyDetails(propertyId, event, images) {
            event.preventDefault();
            const houseCard = document.querySelector(`.house-card[data-property-id="${propertyId}"]`);
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
                slide.innerHTML = `<img src="${image}" alt="${title}">`;
                slides.appendChild(slide);
            });
            slideIndex = 0;
            updateSlidePosition();

            document.getElementById('modal-location').textContent = location;
            document.getElementById('modal-rent').textContent = rent;
            document.getElementById('modal-description').textContent = description;

            document.getElementById('property-modal').style.display = 'flex';
        }

        function closePropertyModal() {
            document.getElementById('property-modal').style.display = 'none';
        }

        function moveSlide(direction) {
            const slides = document.getElementById('slides');
            const totalSlides = slides.children.length;
            slideIndex += direction;
            if (slideIndex >= totalSlides) slideIndex = 0;
            if (slideIndex < 0) slideIndex = totalSlides - 1;
            updateSlidePosition();
        }

        function updateSlidePosition() {
            const slides = document.getElementById('slides');
            slides.style.transform = `translateX(-${slideIndex * 100}%)`;
        }

        // Initial render for requests
        renderRequests();

        // Handle success/error message display
        window.onload = function() {
            const successMessage = document.getElementById('success-message');
            const errorMessage = document.getElementById('error-message');
            
            if (successMessage) {
                setTimeout(() => {
                    successMessage.style.display = 'none';
                    window.history.replaceState({}, document.title, window.location.pathname);
                }, 3000); // Hide after 3 seconds
            }
            
            if (errorMessage) {
                setTimeout(() => {
                    errorMessage.style.display = 'none';
                    window.history.replaceState({}, document.title, window.location.pathname);
                }, 3000); // Hide after 3 seconds
            }
        };
    </script>
</body>
</html>