<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'tenant') {
    header('Location: index.php');
    exit;
}

require 'backend/config.php';

$tenant_id = $_SESSION['user_id'];

// Fetch tenant's name
try {
    $stmt = $pdo->prepare("SELECT full_name FROM tenants WHERE id = ?");
    $stmt->execute([$tenant_id]);
    $tenant = $stmt->fetch(PDO::FETCH_ASSOC);
    $tenant_name = $tenant['full_name'] ?? 'Tenant';
} catch (PDOException $e) {
    $tenant_name = 'Tenant';
    error_log("Error fetching tenant name: " . $e->getMessage());
}

// Determine greeting based on time of day
$hour = (int)date('H');
$greeting = ($hour < 12) ? "Good Morning" : (($hour < 17) ? "Good Afternoon" : "Good Evening");

// Fetch available properties (max 9, ensure no duplicates by id)
try {
    $stmt = $pdo->prepare("SELECT DISTINCT p.* FROM properties p WHERE status = 'available' LIMIT 9");
    $stmt->execute();
    $properties = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($properties as &$property) {
        $images = json_decode($property['image'], true);
        $property['image'] = is_array($images) && !empty($images) ? $images[0] : 'https://via.placeholder.com/300x150?text=No+Image';
    }
} catch (PDOException $e) {
    $properties = [];
    error_log("Error fetching properties: " . $e->getMessage());
}

// Fetch tenant's requests
try {
    $stmt = $pdo->prepare("SELECT r.*, p.title AS property_title, o.full_name AS owner_name 
                          FROM requests r 
                          JOIN properties p ON r.property_id = p.id 
                          JOIN owners o ON p.owner_id = o.id 
                          WHERE r.tenant_id = ?");
    $stmt->execute([$tenant_id]);
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $requests = [];
    error_log("Error fetching requests: " . $e->getMessage());
}

// Handle request submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_request'])) {
    $property_id = $_POST['property_id'];
    $name = $_POST['name'];
    $age = $_POST['age'];
    $occupation = $_POST['occupation'];
    $num_people = $_POST['num_people'];

    $stmt = $pdo->prepare("SELECT owner_id FROM properties WHERE id = ?");
    $stmt->execute([$property_id]);
    $owner_id = $stmt->fetchColumn();

    $citizenship_copy = null;
    if (isset($_FILES['citizenship_copy']) && $_FILES['citizenship_copy']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'Uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $citizenship_copy = $upload_dir . uniqid() . '-' . basename($_FILES['citizenship_copy']['name']);
        move_uploaded_file($_FILES['citizenship_copy']['tmp_name'], $citizenship_copy);
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO requests (property_id, tenant_id, owner_id, name, age, occupation, num_people, citizenship_copy, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())");
        $stmt->execute([$property_id, $tenant_id, $owner_id, $name, $age, $occupation, $num_people, $citizenship_copy]);
        header("Location: tenantDashboard.php?success=Request submitted successfully!&refresh=true");
        exit;
    } catch (PDOException $e) {
        error_log("Error submitting request: " . $e->getMessage());
        echo "<script>alert('Error submitting request: " . htmlspecialchars($e->getMessage()) . "');</script>";
    }
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
    <style>
        .dashboard-container { padding: 20px; max-width: 1200px; margin: 0 auto; }
        .greeting { font-size: 1.2em; color: #333; margin-bottom: 10px; display: block; }
        .search-form { display: flex; gap: 15px; margin-bottom: 20px; align-items: center; }
        .search-input-wrapper { position: relative; flex: 1; }
        .search-input-wrapper i { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #3182ce; }
        .search-input-wrapper input { padding: 10px 10px 10px 35px; width: 100%; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        .btn.gradient-btn { background: linear-gradient(135deg, #3182ce, #68d391); color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; }
        .btn.gradient-btn:hover { background: linear-gradient(135deg, #2a70b8, #5abf7f); }
        .property-list { display: flex; flex-wrap: wrap; gap: 20px; justify-content: center; padding: 20px; }
        .house-card { width: 300px; border: 1px solid #ddd; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .house-card img { width: 100%; height: 150px; object-fit: cover; border-radius: 8px 8px 0 0; }
        .house-info { padding: 10px; display: flex; flex-direction: column; gap: 5px; }
        .house-info h3 { margin: 0; font-size: 1.1em; }
        .house-info p { margin: 3px 0; font-size: 0.9em; }
        .house-info .btn { padding: 6px 10px; font-size: 13px; text-align: center; border-radius: 4px; cursor: pointer; }
        .request-modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); justify-content: center; align-items: center; z-index: 1000; }
        .request-modal-content { background: white; padding: 20px; border-radius: 8px; max-width: 400px; width: 90%; position: relative; box-shadow: 0 4px 8px rgba(0,0,0,0.2); }
        .request-modal-content .close { position: absolute; top: 10px; right: 15px; font-size: 24px; cursor: pointer; color: #333; font-weight: bold; }
        .request-modal-content .close:hover { color: #ff0000; }
        .request-form input, .request-form select { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        .requests-section { margin-top: 20px; }
        .request-card { border: 1px solid #ddd; border-radius: 8px; padding: 15px; margin: 10px 0; background: #f9f9f9; }
        .no-requests { text-align: center; color: #666; font-style: italic; margin: 20px 0; }
        .message-container { padding: 10px; background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; border-radius: 4px; margin-bottom: 10px; }
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
            <div class="greeting"><?php echo htmlspecialchars($greeting . ", " . $tenant_name); ?>!</div>
            <h1>Tenant Dashboard</h1>
            <?php if (isset($_GET['success'])): ?>
                <div class="message-container success" id="success-message">
                    <?php echo htmlspecialchars($_GET['success']); ?>
                </div>
            <?php endif; ?>

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
                <div class="property-list" id="available-properties">
                    <?php if (empty($properties)): ?>
                        <p class="no-requests">No available properties at the moment.</p>
                    <?php else: ?>
                        <?php foreach ($properties as $property): ?>
                            <div class="house-card">
                                <img src="<?php echo htmlspecialchars($property['image']); ?>" alt="<?php echo htmlspecialchars($property['title']); ?>">
                                <div class="house-info">
                                    <h3><?php echo htmlspecialchars($property['title']); ?></h3>
                                    <p>Location: <?php echo htmlspecialchars($property['location']); ?></p>
                                    <p>Rs. <?php echo htmlspecialchars($property['rent']); ?>/month</p>
                                    <p><?php echo htmlspecialchars($property['description']); ?></p>
                                    <button class="btn gradient-btn" onclick="showRequestForm(<?php echo $property['id']; ?>)">Request to Rent</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <h2 class="requests-section">Pending Requests</h2>
                <div class="property-list" id="pending-requests">
                    <?php 
                    $pending_requests = array_filter($requests, fn($request) => $request['status'] === 'pending');
                    if (empty($pending_requests)): ?>
                        <p class="no-requests">No pending requests for now.</p>
                    <?php else: ?>
                        <?php foreach ($pending_requests as $request): ?>
                            <div class="request-card">
                                <h3>Property: <?php echo htmlspecialchars($request['property_title']); ?></h3>
                                <p>Owner: <?php echo htmlspecialchars($request['owner_name']); ?></p>
                                <p>Status: <?php echo htmlspecialchars($request['status']); ?></p>
                                <p>Submitted: <?php echo htmlspecialchars($request['created_at']); ?></p>
                                <p>Meeting Time: <?php echo htmlspecialchars($request['meeting_time'] ?: 'N/A'); ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <h2 class="requests-section">Responded Requests</h2>
                <div class="property-list" id="responded-requests">
                    <?php 
                    $responded_requests = array_filter($requests, fn($request) => $request['status'] !== 'pending');
                    if (empty($responded_requests)): ?>
                        <p class="no-requests">No responded requests yet.</p>
                    <?php else: ?>
                        <?php foreach ($responded_requests as $request): ?>
                            <div class="request-card">
                                <h3>Property: <?php echo htmlspecialchars($request['property_title']); ?></h3>
                                <p>Owner: <?php echo htmlspecialchars($request['owner_name']); ?></p>
                                <p>Status: <?php echo htmlspecialchars($request['status']); ?></p>
                                <p>Response: <?php echo htmlspecialchars($request['owner_response'] ?? 'No response yet'); ?></p>
                                <p>Submitted: <?php echo htmlspecialchars($request['created_at']); ?></p>
                                <p>Meeting Time: <?php echo htmlspecialchars($request['meeting_time'] ?: 'N/A'); ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>
        </div>

        <!-- Request Form Modal -->
        <div id="request-modal" class="request-modal">
            <div class="request-modal-content">
                <span class="close" onclick="closeRequestForm()">×</span>
                <h2>Request to Rent</h2>
                <form class="request-form" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="property_id" id="request-property-id">
                    <input type="text" name="name" placeholder="Full Name" required>
                    <input type="number" name="age" placeholder="Age" required>
                    <input type="text" name="occupation" placeholder="Occupation" required>
                    <input type="number" name="num_people" placeholder="Number of People" required>
                    <input type="file" name="citizenship_copy" accept="image/*" required>
                    <button type="submit" name="submit_request" class="btn gradient-btn">Submit Request</button>
                </form>
            </div>
        </div>
    </main>

    <footer>
        <p>© 2025 Baas Paincha. All rights reserved.</p>
    </footer>

    <script src="script.js"></script>
    <script>
        function showRequestForm(propertyId) {
            document.getElementById('request-property-id').value = propertyId;
            document.getElementById('request-modal').style.display = 'flex';
        }

        function closeRequestForm() {
            document.getElementById('request-modal').style.display = 'none';
            document.querySelector('.request-form').reset();
        }

        document.getElementById('search-form').addEventListener('submit', (e) => {
            e.preventDefault();
            const location = document.getElementById('location-search').value.toLowerCase();
            const rate = parseFloat(document.getElementById('rate-search').value) || 0;

            fetch(backend/search_properties.php?location=${encodeURIComponent(location)}&rate=${rate})
                .then(response => response.json())
                .then(data => {
                    const availableProperties = document.getElementById('available-properties');
                    availableProperties.innerHTML = '';
                    if (data.length > 0) {
                        data.forEach(property => {
                            const card = document.createElement('div');
                            card.className = 'house-card';
                            card.innerHTML = `
                                <img src="${property.image || 'https://via.placeholder.com/300x150?text=No+Image'}" alt="${property.title}">
                                <div class="house-info">
                                    <h3>${property.title}</h3>
                                    <p>Location: ${property.location}</p>
                                    <p>Rs. ${property.rent}/month</p>
                                    <p>${property.description}</p>
                                    <button class="btn gradient-btn" onclick="showRequestForm(${property.id})">Request to Rent</button>
                                </div>
                            `;
                            availableProperties.appendChild(card);
                        });
                    } else {
                        availableProperties.innerHTML = '<p class="no-requests">No results found.</p>';
                    }
                })
                .catch(error => console.error('Error:', error));
        });

        function refreshAvailableProperties() {
            fetch('backend/get_available_properties.php')
                .then(response => response.json())
                .then(data => {
                    const availableProperties = document.getElementById('available-properties');
                    availableProperties.innerHTML = data.length ? '' : '<p class="no-requests">No available properties at the moment.</p>';
                    data.forEach(property => {
                        const card = document.createElement('div');
                        card.className = 'house-card';
                        card.innerHTML = `
                            <img src="${property.image || 'https://via.placeholder.com/300x150?text=No+Image'}" alt="${property.title}">
                            <div class="house-info">
                                <h3>${property.title}</h3>
                                <p>Location: ${property.location}</p>
                                <p>Rs. ${property.rent}/month</p>
                                <p>${property.description}</p>
                                <button class="btn gradient-btn" onclick="showRequestForm(${property.id})">Request to Rent</button>
                            </div>
                        `;
                        availableProperties.appendChild(card);
                    });
                })
                .catch(error => console.error('Error refreshing properties:', error));
        }

        function refreshRequests() {
            fetch('backend/get_requests.php?tenant_id=<?php echo $tenant_id; ?>')
                .then(response => response.json())
                .then(data => {
                    const pendingRequests = document.getElementById('pending-requests');
                    const respondedRequests = document.getElementById('responded-requests');

                    // Update pending requests atomically
                    pendingRequests.innerHTML = data.pending.length ? '' : '<p class="no-requests">No pending requests for now.</p>';
                    data.pending.forEach(request => {
                        pendingRequests.innerHTML += `
                            <div class="request-card">
                                <h3>Property: ${request.property_title}</h3>
                                <p>Owner: ${request.owner_name}</p>
                                <p>Status: ${request.status}</p>
                                <p>Submitted: ${request.created_at}</p>
                                <p>Meeting Time: ${request.meeting_time || 'N/A'}</p>
                            </div>`;
                    });

                    // Update responded requests atomically
                    respondedRequests.innerHTML = data.responded.length ? '' : '<p class="no-requests">No responded requests yet.</p>';
                    data.responded.forEach(request => {
                        respondedRequests.innerHTML += `
                            <div class="request-card">
                                <h3>Property: ${request.property_title}</h3>
                                <p>Owner: ${request.owner_name}</p>
                                <p>Status: ${request.status}</p>
                                <p>Response: ${request.owner_response || 'No response yet'}</p>
                                <p>Submitted: ${request.created_at}</p>
                                <p>Meeting Time: ${request.meeting_time || 'N/A'}</p>
                            </div>`;
                    });
                })
                .catch(error => console.error('Error refreshing requests:', error));
        }

        window.onload = function() {
            if (window.location.search.includes('refresh=true')) {
                refreshRequests();
                refreshAvailableProperties();
            }
            refreshRequests();
            refreshAvailableProperties();
            setInterval(() => {
                refreshRequests();
                refreshAvailableProperties();
            }, 30000);

            // Hide success message after 3 seconds
            const successMessage = document.getElementById('success-message');
            if (successMessage) {
                setTimeout(() => {
                    successMessage.style.display = 'none';
                    window.history.replaceState({}, document.title, window.location.pathname);
                }, 3000);
            }
        };
    </script>
</body>
</html>