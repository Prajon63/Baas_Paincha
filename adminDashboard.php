<?php
session_start();
require 'backend/config.php';

// Basic authentication (replace with your desired username and password)
$admin_username = 'admin';
$admin_password = 'admin123'; // Hash this in production (e.g., password_hash())

if (!isset($_SESSION['admin_authenticated']) || $_SESSION['admin_authenticated'] !== true) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
        $username = $_POST['username'];
        $password = $_POST['password'];
        if ($username === $admin_username && $password === $admin_password) {
            $_SESSION['admin_authenticated'] = true;
        } else {
            $error = "Invalid credentials. Please try again.";
        }
    }
    if (!isset($_SESSION['admin_authenticated'])) {
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Admin Login - Baas Paincha</title>
            <style>
                body { font-family: Arial, sans-serif; background: #f0f0f0; margin: 0; padding: 0; }
                .login-container { width: 300px; margin: 100px auto; padding: 20px; border: 1px solid #ccc; background: #fff; }
                .login-container h2 { text-align: center; margin-bottom: 20px; }
                .login-container input { width: 100%; padding: 8px; margin: 10px 0; border: 1px solid #ccc; box-sizing: border-box; }
                .login-container button { width: 100%; padding: 10px; background: #005566; color: white; border: none; cursor: pointer; }
                .login-container button:hover { background: #003d4d; }
                .error { color: red; text-align: center; margin-top: 10px; }
            </style>
        </head>
        <body>
            <div class="login-container">
                <h2>Admin Login</h2>
                <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
                <form method="POST">
                    <input type="text" name="username" placeholder="Username" required>
                    <input type="password" name="password" placeholder="Password" required>
                    <button type="submit" name="login">Login</button>
                </form>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}

// Fetch all data
try {
    $stmt = $pdo->query("SELECT p.*, o.full_name AS owner_name FROM properties p LEFT JOIN owners o ON p.owner_id = o.id");
    $properties = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($properties as &$property) {
        $property['image'] = json_decode($property['image'], true) ? json_decode($property['image'], true)[0] : '';
    }
    unset($property);
} catch (PDOException $e) {
    $properties = [];
    error_log("Error fetching properties: " . $e->getMessage());
}

try {
    $stmt = $pdo->query("SELECT * FROM owners");
    $owners = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $owners = [];
    error_log("Error fetching owners: " . $e->getMessage());
}

try {
    $stmt = $pdo->query("SELECT * FROM tenants");
    $tenants = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $tenants = [];
    error_log("Error fetching tenants: " . $e->getMessage());
}

try {
    $stmt = $pdo->query("SELECT r.*, p.title AS property_title, t.full_name AS tenant_name, o.full_name AS owner_name 
                         FROM requests r 
                         LEFT JOIN properties p ON r.property_id = p.id 
                         LEFT JOIN tenants t ON r.tenant_id = t.id 
                         LEFT JOIN owners o ON r.owner_id = o.id");
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $requests = [];
    error_log("Error fetching requests: " . $e->getMessage());
}

try {
    $stmt = $pdo->query("SELECT * FROM password_resets");
    $password_resets = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $password_resets = [];
    error_log("Error fetching password resets: " . $e->getMessage());
}

// Handle Delete Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    $table = $_POST['table'];
    $id = $_POST['id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM $table WHERE id = ?");
        $stmt->execute([$id]);
        header("Location: adminDashboard.php?success=Record deleted successfully");
        exit;
    } catch (PDOException $e) {
        error_log("Error deleting record: " . $e->getMessage());
        header("Location: adminDashboard.php?error=Error deleting record");
        exit;
    }
}

// Handle Add/Edit Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    $table = $_POST['table'];
    $id = $_POST['id'] ?? null;

    if ($table === 'properties') {
        $title = $_POST['title'];
        $location = $_POST['location'];
        $rent = $_POST['rent'];
        $description = $_POST['description'];
        $status = $_POST['status'];
        $owner_id = $_POST['owner_id'];
        $image = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'Uploads/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            $image_path = $upload_dir . uniqid() . '-' . basename($_FILES['image']['name']);
            move_uploaded_file($_FILES['image']['tmp_name'], $image_path);
            $image = json_encode([$image_path]);
        } else {
            $image = $_POST['existing_image'] ?? json_encode([]);
        }
        try {
            if ($id) {
                $stmt = $pdo->prepare("UPDATE properties SET title = ?, location = ?, rent = ?, description = ?, status = ?, owner_id = ?, image = ? WHERE id = ?");
                $stmt->execute([$title, $location, $rent, $description, $status, $owner_id, $image, $id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO properties (title, location, rent, description, status, owner_id, image) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$title, $location, $rent, $description, $status, $owner_id, $image]);
            }
        } catch (PDOException $e) {
            error_log("Error saving property: " . $e->getMessage());
        }
    } elseif ($table === 'owners' || $table === 'tenants') {
        $full_name = $_POST['full_name'];
        $email = $_POST['email'];
        $password = $_POST['password'] ? password_hash($_POST['password'], PASSWORD_DEFAULT) : $_POST['existing_password'];
        try {
            if ($id) {
                $stmt = $pdo->prepare("UPDATE $table SET full_name = ?, email = ?, password = ? WHERE id = ?");
                $stmt->execute([$full_name, $email, $password, $id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO $table (full_name, email, password) VALUES (?, ?, ?)");
                $stmt->execute([$full_name, $email, $password]);
            }
        } catch (PDOException $e) {
            error_log("Error saving $table: " . $e->getMessage());
        }
    } elseif ($table === 'requests') {
        $property_id = $_POST['property_id'];
        $tenant_id = $_POST['tenant_id'];
        $owner_id = $_POST['owner_id'];
        $status = $_POST['status'];
        $name = $_POST['name'];
        $age = $_POST['age'];
        $occupation = $_POST['occupation'];
        $num_people = $_POST['num_people'];
        $citizenship_copy = $_POST['existing_citizenship_copy'] ?? null;
        if (isset($_FILES['citizenship_copy']) && $_FILES['citizenship_copy']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'Uploads/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            $citizenship_copy = $upload_dir . uniqid() . '-' . basename($_FILES['citizenship_copy']['name']);
            move_uploaded_file($_FILES['citizenship_copy']['tmp_name'], $citizenship_copy);
        }
        $owner_response = $_POST['owner_response'];
        $meeting_time = $_POST['meeting_time'] ?? null;

        // Check for duplicate request (property_id, tenant_id combination)
        try {
            $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM requests WHERE property_id = ? AND tenant_id = ? AND id != ?");
            $checkStmt->execute([$property_id, $tenant_id, $id ?? 0]);
            if ($checkStmt->fetchColumn() > 0) {
                header("Location: adminDashboard.php?error=Duplicate request detected for this property and tenant.");
                exit;
            }

            if ($id) {
                $stmt = $pdo->prepare("UPDATE requests SET property_id = ?, tenant_id = ?, owner_id = ?, status = ?, name = ?, age = ?, occupation = ?, num_people = ?, citizenship_copy = ?, owner_response = ?, meeting_time = ? WHERE id = ?");
                $stmt->execute([$property_id, $tenant_id, $owner_id, $status, $name, $age, $occupation, $num_people, $citizenship_copy, $owner_response, $meeting_time, $id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO requests (property_id, tenant_id, owner_id, status, name, age, occupation, num_people, citizenship_copy, owner_response, meeting_time) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$property_id, $tenant_id, $owner_id, $status, $name, $age, $occupation, $num_people, $citizenship_copy, $owner_response, $meeting_time]);
            }
        } catch (PDOException $e) {
            error_log("Error saving request: " . $e->getMessage());
            header("Location: adminDashboard.php?error=Error saving request: " . urlencode($e->getMessage()));
            exit;
        }
    } elseif ($table === 'password_resets') {
        $email = $_POST['email'];
        $token = $_POST['token'];
        $expires_at = $_POST['expires_at'];
        try {
            if ($id) {
                $stmt = $pdo->prepare("UPDATE password_resets SET email = ?, token = ?, expires_at = ? WHERE id = ?");
                $stmt->execute([$email, $token, $expires_at, $id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
                $stmt->execute([$email, $token, $expires_at]);
            }
        } catch (PDOException $e) {
            error_log("Error saving password reset: " . $e->getMessage());
        }
    }
    header("Location: adminDashboard.php?success=Record saved successfully");
    exit;
}

// Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: adminDashboard.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Baas Paincha</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background: #f0f0f0; }
        .navbar { background: #005566; color: white; padding: 10px; display: flex; justify-content: space-between; align-items: center; }
        .navbar .logo a { color: white; text-decoration: none; font-size: 20px; }
        .navbar a.logout-btn { color: white; text-decoration: none; padding: 8px 16px; background: #c0392b; border-radius: 4px; }
        .navbar a.logout-btn:hover { background: #a93226; }
        .container { max-width: 1200px; margin: 20px auto; padding: 20px; background: white; border: 1px solid #ccc; }
        h1, h2 { color: #333; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background: #e0e0e0; }
        .btn { padding: 6px 12px; text-decoration: none; border-radius: 4px; color: white; }
        .edit-btn { background: #2ecc71; }
        .edit-btn:hover { background: #27ae60; }
        .delete-btn { background: #c0392b; }
        .delete-btn:hover { background: #a93226; }
        .add-btn { background: #005566; margin-bottom: 10px; display: inline-block; }
        .add-btn:hover { background: #003d4d; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center; }
        .modal-content { background: white; padding: 20px; border: 1px solid #ccc; max-width: 500px; width: 90%; position: relative; max-height: 80vh; overflow-y: auto; }
        .modal-content .close { position: absolute; top: 10px; right: 15px; font-size: 24px; cursor: pointer; }
        .modal-content form input, .modal-content form select, .modal-content form textarea { width: 100%; padding: 8px; margin: 5px 0; border: 1px solid #ccc; box-sizing: border-box; }
        .modal-content img { max-width: 100px; max-height: 100px; margin-top: 5px; }
        .message-container { margin-bottom: 10px; text-align: center; }
        .message-container.success { color: green; }
        .message-container.error { color: red; }
        footer { text-align: center; padding: 10px; background: #005566; color: white; }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="logo"><a href="adminDashboard.php">Baas Paincha Admin</a></div>
        <a href="?logout" class="logout-btn">Logout</a>
    </nav>

    <div class="container">
        <h1>Admin Dashboard</h1>
        <?php if (isset($_GET['success'])): ?>
            <div class="message-container success" id="success-message">
                <?php echo htmlspecialchars($_GET['success']); ?>
            </div>
        <?php elseif (isset($_GET['error'])): ?>
            <div class="message-container error" id="error-message">
                <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>

        <!-- Properties Section -->
        <h2>Properties</h2>
        <a href="#" class="btn add-btn" onclick="showAddForm('properties')">Add Property</a>
        <table>
            <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Location</th>
                <th>Rent</th>
                <th>Owner</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
            <?php if (empty($properties)): ?>
                <tr><td colspan="7">No properties found.</td></tr>
            <?php else: ?>
                <?php foreach ($properties as $property): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($property['id']); ?></td>
                        <td><?php echo htmlspecialchars($property['title']); ?></td>
                        <td><?php echo htmlspecialchars($property['location']); ?></td>
                        <td>Rs. <?php echo htmlspecialchars($property['rent']); ?></td>
                        <td><?php echo htmlspecialchars($property['owner_name'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($property['status']); ?></td>
                        <td>
                            <button class="btn edit-btn" onclick='editRecord(<?php echo json_encode(['table' => 'properties', 'data' => $property]); ?>)'>Edit</button>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="table" value="properties">
                                <input type="hidden" name="id" value="<?php echo $property['id']; ?>">
                                <button type="submit" name="delete" class="btn delete-btn">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </table>

        <!-- Owners Section -->
        <h2>Owners</h2>
        <a href="#" class="btn add-btn" onclick="showAddForm('owners')">Add Owner</a>
        <table>
            <tr>
                <th>ID</th>
                <th>Full Name</th>
                <th>Email</th>
                <th>Created At</th>
                <th>Actions</th>
            </tr>
            <?php if (empty($owners)): ?>
                <tr><td colspan="5">No owners found.</td></tr>
            <?php else: ?>
                <?php foreach ($owners as $owner): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($owner['id']); ?></td>
                        <td><?php echo htmlspecialchars($owner['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($owner['email']); ?></td>
                        <td><?php echo htmlspecialchars($owner['created_at']); ?></td>
                        <td>
                            <button class="btn edit-btn" onclick='editRecord(<?php echo json_encode(['table' => 'owners', 'data' => $owner]); ?>)'>Edit</button>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="table" value="owners">
                                <input type="hidden" name="id" value="<?php echo $owner['id']; ?>">
                                <button type="submit" name="delete" class="btn delete-btn">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </table>

        <!-- Tenants Section -->
        <h2>Tenants</h2>
        <a href="#" class="btn add-btn" onclick="showAddForm('tenants')">Add Tenant</a>
        <table>
            <tr>
                <th>ID</th>
                <th>Full Name</th>
                <th>Email</th>
                <th>Created At</th>
                <th>Actions</th>
            </tr>
            <?php if (empty($tenants)): ?>
                <tr><td colspan="5">No tenants found.</td></tr>
            <?php else: ?>
                <?php foreach ($tenants as $tenant): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($tenant['id']); ?></td>
                        <td><?php echo htmlspecialchars($tenant['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($tenant['email']); ?></td>
                        <td><?php echo htmlspecialchars($tenant['created_at']); ?></td>
                        <td>
                            <button class="btn edit-btn" onclick='editRecord(<?php echo json_encode(['table' => 'tenants', 'data' => $tenant]); ?>)'>Edit</button>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="table" value="tenants">
                                <input type="hidden" name="id" value="<?php echo $tenant['id']; ?>">
                                <button type="submit" name="delete" class="btn delete-btn">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </table>

        <!-- Requests Section -->
        <h2>Requests</h2>
        <a href="#" class="btn add-btn" onclick="showAddForm('requests')">Add Request</a>
        <table>
            <tr>
                <th>ID</th>
                <th>Property</th>
                <th>Tenant</th>
                <th>Owner</th>
                <th>Name</th>
                <th>Age</th>
                <th>Occupation</th>
                <th>People</th>
                <th>Status</th>
                <th>Meeting Time</th>
                <th>Actions</th>
            </tr>
            <?php if (empty($requests)): ?>
                <tr><td colspan="11">No requests found.</td></tr>
            <?php else: ?>
                <?php foreach ($requests as $request): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($request['id']); ?></td>
                        <td><?php echo htmlspecialchars($request['property_title']); ?></td>
                        <td><?php echo htmlspecialchars($request['tenant_name']); ?></td>
                        <td><?php echo htmlspecialchars($request['owner_name']); ?></td>
                        <td><?php echo htmlspecialchars($request['name']); ?></td>
                        <td><?php echo htmlspecialchars($request['age']); ?></td>
                        <td><?php echo htmlspecialchars($request['occupation']); ?></td>
                        <td><?php echo htmlspecialchars($request['num_people']); ?></td>
                        <td><?php echo htmlspecialchars($request['status']); ?></td>
                        <td><?php echo htmlspecialchars($request['meeting_time'] ?? 'N/A'); ?></td>
                        <td>
                            <button class="btn edit-btn" onclick='editRecord(<?php echo json_encode(['table' => 'requests', 'data' => $request]); ?>)'>Edit</button>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="table" value="requests">
                                <input type="hidden" name="id" value="<?php echo $request['id']; ?>">
                                <button type="submit" name="delete" class="btn delete-btn">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </table>

        <!-- Password Resets Section -->
        <h2>Password Resets</h2>
        <a href="#" class="btn add-btn" onclick="showAddForm('password_resets')">Add Password Reset</a>
        <table>
            <tr>
                <th>ID</th>
                <th>Email</th>
                <th>Token</th>
                <th>Created At</th>
                <th>Expires At</th>
                <th>Actions</th>
            </tr>
            <?php if (empty($password_resets)): ?>
                <tr><td colspan="6">No password reset records found.</td></tr>
            <?php else: ?>
                <?php foreach ($password_resets as $reset): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($reset['id']); ?></td>
                        <td><?php echo htmlspecialchars($reset['email']); ?></td>
                        <td><?php echo htmlspecialchars($reset['token']); ?></td>
                        <td><?php echo htmlspecialchars($reset['created_at']); ?></td>
                        <td><?php echo htmlspecialchars($reset['expires_at']); ?></td>
                        <td>
                            <button class="btn edit-btn" onclick='editRecord(<?php echo json_encode(['table' => 'password_resets', 'data' => $reset]); ?>)'>Edit</button>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="table" value="password_resets">
                                <input type="hidden" name="id" value="<?php echo $reset['id']; ?>">
                                <button type="submit" name="delete" class="btn delete-btn">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </table>
    </div>

    <!-- Modal -->
    <div id="modal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">×</span>
            <h2 id="modal-title"></h2>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="table" id="modal-table">
                <input type="hidden" name="id" id="modal-id">
                <div id="modal-form-content">
                    <!-- Dynamic form fields will be populated here -->
                </div>
                <button type="submit" name="save" class="btn add-btn">Save</button>
            </form>
        </div>
    </div>

    <footer>
        <p>© 2025 Baas Paincha. All rights reserved.</p>
    </footer>

    <script>
        function showAddForm(table) {
            document.getElementById('modal-title').textContent = `Add ${table.charAt(0).toUpperCase() + table.slice(1)}`;
            document.getElementById('modal-table').value = table;
            document.getElementById('modal-id').value = '';
            let formContent = '';
            if (table === 'properties') {
                formContent = `
                    <input type="text" name="title" id="modal-title-input" placeholder="Title" required>
                    <input type="text" name="location" id="modal-location" placeholder="Location" required>
                    <input type="number" name="rent" id="modal-rent" placeholder="Rent (Rs.)" required>
                    <textarea name="description" id="modal-description" placeholder="Description" required></textarea>
                    <select name="status" id="modal-status">
                        <option value="available">Available</option>
                        <option value="rented">Rented</option>
                        <option value="unavailable">Unavailable</option>
                    </select>
                    <select name="owner_id" id="modal-owner-id">
                        <?php foreach ($owners as $owner): ?>
                            <option value="<?php echo $owner['id']; ?>"><?php echo htmlspecialchars($owner['full_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="hidden" name="existing_image" id="modal-existing-image">
                    <img id="modal-image-preview" src="" alt="Property Image" style="display: none;">
                    <input type="file" name="image" id="modal-image" accept="image/*">
                `;
            } else if (table === 'owners' || table === 'tenants') {
                formContent = `
                    <input type="text" name="full_name" id="modal-full-name" placeholder="Full Name" required>
                    <input type="email" name="email" id="modal-email" placeholder="Email" required>
                    <input type="password" name="password" id="modal-password" placeholder="Password (leave blank to keep unchanged)">
                    <input type="hidden" name="existing_password" id="modal-existing-password">
                `;
            } else if (table === 'requests') {
                formContent = `
                    <select name="property_id" id="modal-property-id">
                        <?php foreach ($properties as $property): ?>
                            <option value="<?php echo $property['id']; ?>"><?php echo htmlspecialchars($property['title']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="tenant_id" id="modal-tenant-id">
                        <?php foreach ($tenants as $tenant): ?>
                            <option value="<?php echo $tenant['id']; ?>"><?php echo htmlspecialchars($tenant['full_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="owner_id" id="modal-owner-id">
                        <?php foreach ($owners as $owner): ?>
                            <option value="<?php echo $owner['id']; ?>"><?php echo htmlspecialchars($owner['full_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="status" id="modal-status">
                        <option value="pending">Pending</option>
                        <option value="accepted">Accepted</option>
                        <option value="rejected">Rejected</option>
                    </select>
                    <input type="text" name="name" id="modal-name" placeholder="Name" required>
                    <input type="number" name="age" id="modal-age" placeholder="Age" required>
                    <input type="text" name="occupation" id="modal-occupation" placeholder="Occupation" required>
                    <input type="number" name="num_people" id="modal-num-people" placeholder="Number of People" required>
                    <input type="hidden" name="existing_citizenship_copy" id="modal-existing-citizenship-copy">
                    <img id="modal-citizenship-preview" src="" alt="Citizenship Copy" style="display: none;">
                    <input type="file" name="citizenship_copy" id="modal-citizenship-copy" accept="image/*">
                    <textarea name="owner_response" id="modal-owner-response" placeholder="Owner Response"></textarea>
                    <input type="datetime-local" name="meeting_time" id="modal-meeting-time" placeholder="Meeting Time (if accepted)">
                `;
            } else if (table === 'password_resets') {
                formContent = `
                    <input type="text" name="email" id="modal-email" placeholder="Email" required>
                    <input type="text" name="token" id="modal-token" placeholder="Token" required>
                    <input type="datetime-local" name="expires_at" id="modal-expires-at" placeholder="Expires At" required>
                `;
            }
            document.getElementById('modal-form-content').innerHTML = formContent;
            document.getElementById('modal').style.display = 'flex';
        }

        function editRecord(data) {
            document.getElementById('modal-table').value = data.table;
            document.getElementById('modal-id').value = data.data.id;
            document.getElementById('modal-title').textContent = `Edit ${data.table.charAt(0).toUpperCase() + data.table.slice(1)}`;
            let formContent = '';
            if (data.table === 'properties') {
                formContent = `
                    <input type="text" name="title" id="modal-title-input" value="${data.data.title}" required>
                    <input type="text" name="location" id="modal-location" value="${data.data.location}" required>
                    <input type="number" name="rent" id="modal-rent" value="${data.data.rent}" required>
                    <textarea name="description" id="modal-description" required>${data.data.description}</textarea>
                    <select name="status" id="modal-status">
                        <option value="available" ${data.data.status === 'available' ? 'selected' : ''}>Available</option>
                        <option value="rented" ${data.data.status === 'rented' ? 'selected' : ''}>Rented</option>
                        <option value="unavailable" ${data.data.status === 'unavailable' ? 'selected' : ''}>Unavailable</option>
                    </select>
                    <select name="owner_id" id="modal-owner-id">
                        <?php foreach ($owners as $owner): ?>
                            <option value="<?php echo $owner['id']; ?>" ${data.data.owner_id == <?php echo $owner['id']; ?> ? 'selected' : ''}><?php echo htmlspecialchars($owner['full_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="hidden" name="existing_image" id="modal-existing-image" value="${data.data.image}">
                    <img id="modal-image-preview" src="${data.data.image}" alt="Property Image" style="display: ${data.data.image ? 'block' : 'none'};">
                    <input type="file" name="image" id="modal-image" accept="image/*">
                `;
            } else if (data.table === 'owners' || data.table === 'tenants') {
                formContent = `
                    <input type="text" name="full_name" id="modal-full-name" value="${data.data.full_name}" required>
                    <input type="email" name="email" id="modal-email" value="${data.data.email}" required>
                    <input type="password" name="password" id="modal-password" placeholder="Password (leave blank to keep unchanged)">
                    <input type="hidden" name="existing_password" id="modal-existing-password" value="${data.data.password}">
                `;
            } else if (data.table === 'requests') {
                formContent = `
                    <select name="property_id" id="modal-property-id">
                        <?php foreach ($properties as $property): ?>
                            <option value="<?php echo $property['id']; ?>" ${data.data.property_id == <?php echo $property['id']; ?> ? 'selected' : ''}><?php echo htmlspecialchars($property['title']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="tenant_id" id="modal-tenant-id">
                        <?php foreach ($tenants as $tenant): ?>
                            <option value="<?php echo $tenant['id']; ?>" ${data.data.tenant_id == <?php echo $tenant['id']; ?> ? 'selected' : ''}><?php echo htmlspecialchars($tenant['full_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="owner_id" id="modal-owner-id">
                        <?php foreach ($owners as $owner): ?>
                            <option value="<?php echo $owner['id']; ?>" ${data.data.owner_id == <?php echo $owner['id']; ?> ? 'selected' : ''}><?php echo htmlspecialchars($owner['full_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="status" id="modal-status">
                        <option value="pending" ${data.data.status === 'pending' ? 'selected' : ''}>Pending</option>
                        <option value="accepted" ${data.data.status === 'accepted' ? 'selected' : ''}>Accepted</option>
                        <option value="rejected" ${data.data.status === 'rejected' ? 'selected' : ''}>Rejected</option>
                    </select>
                    <input type="text" name="name" id="modal-name" value="${data.data.name}" required>
                    <input type="number" name="age" id="modal-age" value="${data.data.age}" required>
                    <input type="text" name="occupation" id="modal-occupation" value="${data.data.occupation}" required>
                    <input type="number" name="num_people" id="modal-num-people" value="${data.data.num_people}" required>
                    <input type="hidden" name="existing_citizenship_copy" id="modal-existing-citizenship-copy" value="${data.data.citizenship_copy}">
                    <img id="modal-citizenship-preview" src="${data.data.citizenship_copy}" alt="Citizenship Copy" style="display: ${data.data.citizenship_copy ? 'block' : 'none'};">
                    <input type="file" name="citizenship_copy" id="modal-citizenship-copy" accept="image/*">
                    <textarea name="owner_response" id="modal-owner-response">${data.data.owner_response || ''}</textarea>
                    <input type="datetime-local" name="meeting_time" id="modal-meeting-time" value="${data.data.meeting_time ? new Date(data.data.meeting_time).toISOString().slice(0, 16) : ''}">
                `;
            } else if (data.table === 'password_resets') {
                formContent = `
                    <input type="text" name="email" id="modal-email" value="${data.data.email}" required>
                    <input type="text" name="token" id="modal-token" value="${data.data.token}" required>
                    <input type="datetime-local" name="expires_at" id="modal-expires-at" value="${data.data.expires_at ? new Date(data.data.expires_at).toISOString().slice(0, 16) : ''}" required>
                `;
            }
            document.getElementById('modal-form-content').innerHTML = formContent;
            document.getElementById('modal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('modal').style.display = 'none';
        }

        window.onload = function() {
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