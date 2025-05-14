<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    header('Location: index.php');
    exit;
}

require 'backend/config.php';

$owner_id = $_SESSION['user_id'];

// Fetch owner's name
try {
    $stmt = $pdo->prepare("SELECT full_name FROM owners WHERE id = ?");
    $stmt->execute([$owner_id]);
    $owner = $stmt->fetch(PDO::FETCH_ASSOC);
    $owner_name = $owner['full_name'] ?? 'Owner';
} catch (PDOException $e) {
    $owner_name = 'Owner';
    error_log("Error fetching owner name: " . $e->getMessage());
}

// Determine greeting based on time of day
$hour = (int)date('H');
$greeting = ($hour < 12) ? "Good Morning" : (($hour < 17) ? "Good Afternoon" : "Good Evening");

// Fetch owner's properties
try {
    $stmt = $pdo->prepare("SELECT * FROM properties WHERE owner_id = ?");
    $stmt->execute([$owner_id]);
    $properties = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($properties as &$property) {
        $property['image'] = json_decode($property['image'], true) ?? [];
    }
    unset($property);
} catch (PDOException $e) {
    $properties = [];
    error_log("Error fetching properties: " . $e->getMessage());
    echo "Error fetching properties. Please try again later.";
}

// Fetch requests for owner's properties
try {
    $stmt = $pdo->prepare("SELECT r.*, p.title, t.full_name AS tenant_name 
                          FROM requests r 
                          JOIN properties p ON r.property_id = p.id 
                          JOIN tenants t ON r.tenant_id = t.id 
                          WHERE p.owner_id = ?");
    $stmt->execute([$owner_id]);
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("Fetched requests for owner $owner_id: " . json_encode(array_column($requests, 'id')));
} catch (PDOException $e) {
    $requests = [];
    error_log("Error fetching requests: " . $e->getMessage());
}

// Handle request response
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['respond_request'])) {
    $request_id = filter_input(INPUT_POST, 'request_id', FILTER_SANITIZE_NUMBER_INT);
    $status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING);
    $response = filter_input(INPUT_POST, 'response', FILTER_SANITIZE_STRING);
    $meeting_time = ($status === 'accepted' && isset($_POST['meeting_time']) && !empty($_POST['meeting_time'])) ? $_POST['meeting_time'] : null;

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("UPDATE requests SET status = ?, owner_response = ?, meeting_time = ? WHERE id = ?");
        $stmt->execute([$status, $response, $meeting_time, $request_id]);
        error_log("Request $request_id updated to status: $status, meeting_time: $meeting_time");

        if ($status === 'accepted') {
            $stmt = $pdo->prepare("SELECT property_id, status FROM requests WHERE id = ?");
            $stmt->execute([$request_id]);
            $request = $stmt->fetch(PDO::FETCH_ASSOC);
            $property_id = $request['property_id'];

            $stmt = $pdo->prepare("SELECT status FROM properties WHERE id = ?");
            $stmt->execute([$property_id]);
            $current_status = $stmt->fetchColumn();

            if ($current_status === 'available') {
                $stmt = $pdo->prepare("UPDATE properties SET status = 'rented' WHERE id = ?");
                $stmt->execute([$property_id]);
                error_log("Property $property_id status updated to rented");
            } else {
                echo "<script>alert('This property is already rented.');</script>";
            }
        }

        $pdo->commit();
        header("Location: ownerDashboard.php?success=Response submitted successfully!");
        exit;
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Error updating request $request_id: " . $e->getMessage());
        echo "<script>alert('Error submitting response: " . htmlspecialchars($e->getMessage()) . "');</script>";
    }
}

// Handle meeting scheduling for responded requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['schedule_meeting'])) {
    $request_id = filter_input(INPUT_POST, 'request_id', FILTER_SANITIZE_NUMBER_INT);
    $meeting_time = filter_input(INPUT_POST, 'meeting_time', FILTER_SANITIZE_STRING);

    if (!empty($meeting_time)) {
        try {
            $stmt = $pdo->prepare("UPDATE requests SET meeting_time = ? WHERE id = ?");
            $stmt->execute([$meeting_time, $request_id]);
            error_log("Meeting scheduled for request $request_id at $meeting_time");
            header("Location: ownerDashboard.php?success=Meeting scheduled successfully!");
            exit;
        } catch (PDOException $e) {
            error_log("Error scheduling meeting for request $request_id: " . $e->getMessage());
            echo "<script>alert('Error scheduling meeting: " . htmlspecialchars($e->getMessage()) . "');</script>";
        }
    }
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
        .dashboard-container { padding: 20px; max-width: 1200px; margin: 0 auto; }
        .dashboard-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .greeting { font-size: 1.2em; color: #333; margin-bottom: 10px; }
        .property-list { display: flex; flex-wrap: wrap; gap: 20px; justify-content: center; padding: 20px; }
        .house-card { width: 300px; border: 1px solid #ddd; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .house-card img { width: 100%; height: 150px; object-fit: cover; border-radius: 8px 8px 0 0; }
        .house-info { padding: 10px; display: flex; flex-direction: column; gap: 5px; }
        .house-info h3 { margin: 0; font-size: 1.1em; }
        .house-info p { margin: 3px 0; font-size: 0.9em; }
        .house-info .button-container { display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; }
        .house-info .btn { padding: 6px 10px; font-size: 13px; text-align: center; text-decoration: none; border-radius: 4px; cursor: pointer; }
        .house-info .view-btn { background: linear-gradient(135deg, #3182ce, #68d391); color: white; border: none; }
        .house-info .view-btn:hover { background: linear-gradient(135deg, #2a70b8, #5abf7f); }
        .house-info .edit-btn { background: #ffc107; color: white; border: none; }
        .house-info .edit-btn:hover { background: #e0a800; }
        .house-info .delete-btn { background: #dc3545; color: white; border: none; }
        .house-info .delete-btn:hover { background: #b02a37; }
        .property-modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center; }
        .property-modal-content { background: white; padding: 20px; border-radius: 8px; max-width: 500px; width: 90%; position: relative; }
        .property-modal-content img { width: 100%; height: 250px; object-fit: cover; border-radius: 8px; }
        .property-modal-content .close { position: absolute; top: 10px; right: 15px; font-size: 24px; cursor: pointer; color: #333; font-weight: bold; }
        .property-modal-content .close:hover { color: #ff0000; }
        .slider { position: relative; width: 100%; overflow: hidden; }
        .slides { display: flex; transition: transform 0.5s ease; }
        .slide { min-width: 100%; box-sizing: border-box; }
        .slider button { position: absolute; top: 50%; transform: translateY(-50%); background: rgba(0,0,0,0.5); color: white; border: none; padding: 10px; cursor: pointer; }
        .slider .prev { left: 10px; }
        .slider .next { right: 10px; }
        .file-input-container { margin-bottom: 15px; position: relative; display: inline-block; width: 100%; }
        .file-input-container input[type="file"] { padding-right: 30px; width: calc(100% - 30px); }
        .file-input-container .remove-file { position: absolute; right: 5px; top: 50%; transform: translateY(-50%); color: #dc3545; cursor: pointer; font-size: 18px; vertical-align: middle; }
        .btn.gradient-btn { background: linear-gradient(135deg, #3182ce, #68d391); color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; }
        .btn.gradient-btn:hover { background: linear-gradient(135deg, #2a70b8, #5abf7f); }
        .btn.secondary { padding: 5px 10px; font-size: 12px; background: rgb(78, 115, 148); color: white; border: none; border-radius: 5px; cursor: pointer; display: block; margin-bottom: 15px; }
        .btn.secondary:hover { background: rgb(28, 71, 102); }
        .message-container { margin-top: 10px; text-align: center; }
        .message-container.success { color: green; }
        .message-container.error { color: red; }
        .add-property-form { display: none; }
        .add-property-form.active { display: block; }
        .add-property-form textarea { margin-top: 10px; width: 100%; box-sizing: border-box; }
        .existing-images { margin-bottom: 15px; }
        .existing-images img { width: 100px; height: 100px; object-fit: cover; margin-right: 10px; border-radius: 4px; }
        .existing-images .remove-image { color: #dc3545; cursor: pointer; margin-left: 5px; font-size: 18px; vertical-align: middle; }
        .requests-section { margin-top: 20px; }
        .request-card { border: 1px solid #ddd; border-radius: 8px; padding: 15px; margin: 10px 0; background: #f9f9f9; }
        .request-card form { margin-top: 10px; }
        .request-card select, .request-card textarea, .request-card input[type="datetime-local"] { width: 100%; padding: 10px; margin: 5px 0; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        .no-requests { text-align: center; color: #666; font-style: italic; margin: 20px 0; }
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
            <div class="greeting"><?php echo htmlspecialchars($greeting . ", " . $owner_name); ?>!</div>
            <div class="dashboard-header">
                <h1>Property Owner Dashboard</h1>
                <a href="#" class="btn gradient-btn" id="add-property-btn" onclick="toggleAddForm('add')">Add New Property</a>
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
                <h2 id="form-title">Add New Property</h2>
                <form id="property-form" action="backend/add_property.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="property_id" id="property-id">
                    <input type="hidden" name="existing_images" id="existing-images">
                    <input type="text" name="title" id="property-title" placeholder="Property Title" required>
                    <input type="text" name="location" id="property-location" placeholder="Location" required>
                    <input type="number" name="rent" id="property-rent" placeholder="Rent (Rs.)" required>
                    <div class="existing-images" id="existing-images-preview"></div>
                    <div class="file-input-container" id="file-input-container">
                        <input type="file" name="image[]" accept="image/*" id="file-input-0" required>
                        <span class="remove-file" onclick="removeFileInput(this)">×</span>
                    </div>
                    <button type="button" id="add-more-files" class="btn secondary">Add Another File</button>
                    <textarea name="description" id="property-description" rows="3" placeholder="Description" required></textarea>
                    <button type="submit" class="btn gradient-btn" id="form-submit-btn">Add Property</button>
                </form>
            </div>

            <div class="dashboard-section">
                <h2>My Properties</h2>
                <div class="property-list" id="property-list">
                    <?php if (empty($properties)): ?>
                        <p class="no-requests">You have not listed any properties yet.</p>
                    <?php else: ?>
                        <?php foreach ($properties as $index => $property): ?>
                            <div class="house-card" data-property-id="<?php echo $property['id']; ?>">
                                <img src="<?php echo htmlspecialchars($property['image'][0] ?? 'https://via.placeholder.com/300x150?text=No+Image'); ?>" alt="<?php echo htmlspecialchars($property['title']); ?>">
                                <div class="house-info">
                                    <h3><?php echo htmlspecialchars($property['title']); ?></h3>
                                    <p>Location: <?php echo htmlspecialchars($property['location']); ?></p>
                                    <p>Rs. <?php echo htmlspecialchars($property['rent']); ?>/month</p>
                                    <p><?php echo htmlspecialchars($property['description']); ?></p>
                                    <p>Status: <?php echo htmlspecialchars($property['status']); ?></p>
                                    <div class="button-container">
                                        <a href="#" class="btn view-btn" onclick='showPropertyDetails(<?php echo $property["id"]; ?>, event, <?php echo json_encode($property["image"]); ?>)'>View Details</a>
                                        <a href="#" class="btn edit-btn" onclick='editProperty(<?php echo $property["id"]; ?>, event, <?php echo json_encode($property); ?>)'>Edit</a>
                                        <form action="backend/delete_property.php" method="POST" style="display:inline;">
                                            <input type="hidden" name="property_id" value="<?php echo $property['id']; ?>">
                                            <button type="submit" class="btn delete-btn">Delete</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="dashboard-section">
                <h2 class="requests-section">Pending Requests</h2>
                <div class="property-list" id="pending-requests">
                    <?php 
                    $pending_requests = array_filter($requests, fn($request) => $request['status'] === 'pending');
                    if (empty($pending_requests)): ?>
                        <p class="no-requests">No pending requests for your properties.</p>
                    <?php else: ?>
                        <?php foreach ($pending_requests as $request): ?>
                            <div class="request-card">
                                <h3>Property: <?php echo htmlspecialchars($request['title']); ?></h3>
                                <p>Tenant: <?php echo htmlspecialchars($request['tenant_name']); ?></p>
                                <p>Name: <?php echo htmlspecialchars($request['name']); ?></p>
                                <p>Age: <?php echo htmlspecialchars($request['age']); ?></p>
                                <p>Occupation: <?php echo htmlspecialchars($request['occupation']); ?></p>
                                <p>People: <?php echo htmlspecialchars($request['num_people']); ?></p>
                                <?php if ($request['citizenship_copy']): ?>
                                    <p>Citizenship: <a href="<?php echo htmlspecialchars($request['citizenship_copy']); ?>" target="_blank">View</a></p>
                                <?php endif; ?>
                                <p>Submitted: <?php echo htmlspecialchars($request['created_at']); ?></p>
                                <p><strong>Meeting Time:</strong> <?php echo htmlspecialchars($request['meeting_time'] ?? 'Not scheduled'); ?></p>
                                <form method="POST">
                                    <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                    <select name="status">
                                        <option value="accepted">Accept</option>
                                        <option value="rejected">Reject</option>
                                    </select>
                                    <textarea name="response" placeholder="Add response (optional)"></textarea>
                                    <input type="datetime-local" name="meeting_time" placeholder="Schedule a meeting (if accepted)" style="display: none;" id="meeting-time-<?php echo $request['id']; ?>">
                                    <button type="submit" name="respond_request" class="btn gradient-btn">Respond</button>
                                </form>
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
                                <h3>Property: <?php echo htmlspecialchars($request['title']); ?></h3>
                                <p>Tenant: <?php echo htmlspecialchars($request['tenant_name']); ?></p>
                                <p>Status: <?php echo htmlspecialchars($request['status']); ?></p>
                                <p>Response: <?php echo htmlspecialchars($request['owner_response'] ?? 'No response provided'); ?></p>
                                <p>Submitted: <?php echo htmlspecialchars($request['created_at']); ?></p>
                                <p><strong>Meeting Time:</strong> <?php echo htmlspecialchars($request['meeting_time'] ?? 'Not scheduled'); ?></p>
                                <form method="POST" style="margin-top: 10px;">
                                    <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                    <input type="datetime-local" name="meeting_time" required>
                                    <button type="submit" name="schedule_meeting" class="btn gradient-btn">Schedule Meeting</button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
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
                <p><strong>Status:</strong> <span id="modal-status"></span></p>
            </div>
        </div>
    </main>

    <footer>
        <p>© 2025 Baas Paincha. All rights reserved.</p>
    </footer>

    <script src="script.js"></script>
    <script>
        let fileIndex = 0;
        let currentMode = 'add';
        let slideIndex = 0;

        function toggleAddForm(mode = 'add', property = null) {
            const form = document.getElementById('add-property-form');
            const formTitle = document.getElementById('form-title');
            const formAction = document.getElementById('property-form');
            const submitBtn = document.getElementById('form-submit-btn');
            const fileInputContainer = document.getElementById('file-input-container');
            const fileInput = document.getElementById('file-input-0');
            const existingImagesPreview = document.getElementById('existing-images-preview');
            const existingImagesInput = document.getElementById('existing-images');

            if (mode === 'add') {
                if (form.classList.contains('active')) {
                    form.classList.remove('active');
                    return;
                } else {
                    form.classList.add('active');
                }
            } else {
                form.classList.add('active');
            }

            formAction.reset();
            fileInputContainer.innerHTML = '<div class="file-input-container"><input type="file" name="image[]" accept="image/*" id="file-input-0" required><span class="remove-file" onclick="removeFileInput(this)">×</span></div>';
            existingImagesPreview.innerHTML = '';
            fileIndex = 0;

            if (mode === 'edit' && property) {
                currentMode = 'edit';
                formTitle.textContent = 'Edit Property';
                formAction.action = 'backend/edit_property.php';
                submitBtn.textContent = 'Save Changes';

                document.getElementById('property-id').value = property.id;
                document.getElementById('property-title').value = property.title;
                document.getElementById('property-location').value = property.location;
                document.getElementById('property-rent').value = property.rent;
                document.getElementById('property-description').value = property.description;

                if (property.image && property.image.length > 0) {
                    property.image.forEach(image => {
                        const imgContainer = document.createElement('span');
                        const imgElement = document.createElement('img');
                        imgElement.src = image;
                        const removeBtn = document.createElement('span');
                        removeBtn.className = 'remove-image';
                        removeBtn.textContent = '×';
                        removeBtn.onclick = function() { removeExistingImage(image, existingImagesInput); };
                        imgContainer.appendChild(imgElement);
                        imgContainer.appendChild(removeBtn);
                        existingImagesPreview.appendChild(imgContainer);
                    });
                }

                existingImagesInput.value = JSON.stringify(property.image);
                fileInput.required = false;

                form.scrollIntoView({ behavior: 'smooth', block: 'start' });
            } else {
                currentMode = 'add';
                formTitle.textContent = 'Add New Property';
                formAction.action = 'backend/add_property.php';
                submitBtn.textContent = 'Add Property';
                document.getElementById('property-id').value = '';
                existingImagesInput.value = '';
                fileInput.required = true;
            }
        }

        document.getElementById('add-more-files').addEventListener('click', function() {
            fileIndex++;
            const container = document.getElementById('file-input-container');
            const newInput = document.createElement('div');
            newInput.className = 'file-input-container';
            newInput.innerHTML = `<input type="file" name="image[]" accept="image/*" id="file-input-${fileIndex}" ${fileIndex === 0 ? 'required' : ''}><span class="remove-file" onclick="removeFileInput(this)">×</span>`;
            container.appendChild(newInput);
        });

        function removeFileInput(button) {
            const inputContainer = button.parentElement;
            if (inputContainer && inputContainer.className === 'file-input-container') {
                inputContainer.remove();
            }
        }

        function removeExistingImage(imageUrl, existingImagesInput) {
            let images = JSON.parse(existingImagesInput.value);
            images = images.filter(img => img !== imageUrl);
            existingImagesInput.value = JSON.stringify(images);
            const preview = document.getElementById('existing-images-preview');
            preview.innerHTML = '';
            images.forEach(image => {
                const imgContainer = document.createElement('span');
                const imgElement = document.createElement('img');
                imgElement.src = image;
                const removeBtn = document.createElement('span');
                removeBtn.className = 'remove-image';
                removeBtn.textContent = '×';
                removeBtn.onclick = function() { removeExistingImage(image, existingImagesInput); };
                imgContainer.appendChild(imgElement);
                imgContainer.appendChild(removeBtn);
                preview.appendChild(imgContainer);
            });
        }

        function showPropertyDetails(propertyId, event, images) {
            event.preventDefault();
            const houseCard = document.querySelector(`.house-card[data-property-id="${propertyId}"]`);
            const title = houseCard.querySelector('h3').textContent;
            const location = houseCard.querySelector('p:nth-child(2)').textContent.replace('Location: ', '');
            const rent = houseCard.querySelector('p:nth-child(3)').textContent.replace('Rs. ', '').replace('/month', '');
            const description = houseCard.querySelector('p:nth-child(4)').textContent;
            const status = houseCard.querySelector('p:nth-child(5)').textContent.replace('Status: ', '');

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
            document.getElementById('modal-status').textContent = status;

            document.getElementById('property-modal').style.display = 'flex';
        }

        function editProperty(propertyId, event, property) {
            event.preventDefault();
            toggleAddForm('edit', property);
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

        // Show/hide meeting time input based on status selection
        document.querySelectorAll('select[name="status"]').forEach(select => {
            select.addEventListener('change', function() {
                const meetingInput = this.closest('form').querySelector('input[name="meeting_time"]');
                meetingInput.style.display = this.value === 'accepted' ? 'block' : 'none';
                if (this.value === 'accepted') {
                    meetingInput.required = true;
                } else {
                    meetingInput.required = false;
                }
            });
        });

        // Auto-refresh pending and responded requests every 30 seconds
        function refreshRequests() {
            fetch('backend/get_requests.php?owner_id=<?php echo $owner_id; ?>')
                .then(response => response.json())
                .then(data => {
                    const pendingRequests = document.getElementById('pending-requests');
                    const respondedRequests = document.getElementById('responded-requests');
                    
                    // Update pending requests
                    const pending = data.pending;
                    pendingRequests.innerHTML = pending.length ? '' : '<p class="no-requests">No pending requests for your properties.</p>';
                    pending.forEach(request => {
                        pendingRequests.innerHTML += `
                            <div class="request-card">
                                <h3>Property: ${request.title}</h3>
                                <p>Tenant: ${request.tenant_name}</p>
                                <p>Name: ${request.name}</p>
                                <p>Age: ${request.age}</p>
                                <p>Occupation: ${request.occupation}</p>
                                <p>People: ${request.num_people}</p>
                                ${request.citizenship_copy ? `<p>Citizenship: <a href="${request.citizenship_copy}" target="_blank">View</a></p>` : ''}
                                <p>Submitted: ${request.created_at}</p>
                                <p><strong>Meeting Time:</strong> ${request.meeting_time || 'Not scheduled'}</p>
                                <form method="POST">
                                    <input type="hidden" name="request_id" value="${request.id}">
                                    <select name="status">
                                        <option value="accepted">Accept</option>
                                        <option value="rejected">Reject</option>
                                    </select>
                                    <textarea name="response" placeholder="Add response (optional)"></textarea>
                                    <input type="datetime-local" name="meeting_time" placeholder="Schedule a meeting (if accepted)" style="display: none;" id="meeting-time-${request.id}">
                                    <button type="submit" name="respond_request" class="btn gradient-btn">Respond</button>
                                </form>
                            </div>`;
                    });

                    // Update responded requests
                    const responded = data.responded;
                    respondedRequests.innerHTML = responded.length ? '' : '<p class="no-requests">No responded requests yet.</p>';
                    responded.forEach(request => {
                        respondedRequests.innerHTML += `
                            <div class="request-card">
                                <h3>Property: ${request.title}</h3>
                                <p>Tenant: ${request.tenant_name}</p>
                                <p>Status: ${request.status}</p>
                                <p>Response: ${request.owner_response || 'No response provided'}</p>
                                <p>Submitted: ${request.created_at}</p>
                                <p><strong>Meeting Time:</strong> ${request.meeting_time || 'Not scheduled'}</p>
                                <form method="POST" style="margin-top: 10px;">
                                    <input type="hidden" name="request_id" value="${request.id}">
                                    <input type="datetime-local" name="meeting_time" required>
                                    <button type="submit" name="schedule_meeting" class="btn gradient-btn">Schedule Meeting</button>
                                </form>
                            </div>`;
                    });

                    // Re-attach event listeners for status selection
                    document.querySelectorAll('select[name="status"]').forEach(select => {
                        select.addEventListener('change', function() {
                            const meetingInput = this.closest('form').querySelector('input[name="meeting_time"]');
                            meetingInput.style.display = this.value === 'accepted' ? 'block' : 'none';
                            if (this.value === 'accepted') {
                                meetingInput.required = true;
                            } else {
                                meetingInput.required = false;
                            }
                        });
                    });
                })
                .catch(error => console.error('Error refreshing requests:', error));
        }

        // Initial load and periodic refresh
        window.onload = function() {
            refreshRequests();
            setInterval(refreshRequests, 30000); // Refresh every 30 seconds

            const successMessage = document.getElementById('success-message');
            const errorMessage = document.getElementById('error-message');
            
            if (successMessage) {
                setTimeout(() => {
                    successMessage.style.display = 'none';
                    window.history.replaceState({}, document.title, window.location.pathname);
                }, 3000);
            }
            
            if (errorMessage) {
                setTimeout(() => {
                    errorMessage.style.display = 'none';
                    window.history.replaceState({}, document.title, window.location.pathname);
                }, 3000);
            }
        };
    </script>
</body>
</html>