<?php
session_start();
require 'backend/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'tenant') {
    header('Location: index.php');
    exit;
}

$tenant_id = $_SESSION['user_id'];
$property_id = isset($_GET['property_id']) ? (int)$_GET['property_id'] : 0;

if ($property_id <= 0) {
    header('Location: tenantDashboard.php?error=Invalid property');
    exit;
}

try {
    // Verify the request is approved
    $stmt = $pdo->prepare("
        SELECT p.id, p.title, p.rent, p.location, p.description, p.image
        FROM rental_requests rr
        JOIN properties p ON rr.property_id = p.id
        WHERE rr.tenant_id = ? AND rr.property_id = ? AND rr.status = 'approved'
    ");
    $stmt->execute([$tenant_id, $property_id]);
    $property = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$property) {
        header('Location: tenantDashboard.php?error=Property not found or not approved');
        exit;
    }

    // Decode image field
    $property['image'] = json_decode($property['image'], true) ?? [];
} catch (PDOException $e) {
    error_log("Error fetching property: " . $e->getMessage());
    header('Location: tenantDashboard.php?error=Failed to load property details');
    exit;
}

// Handle rental confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_rental'])) {
    try {
        // Update property status (optional: add a status column to properties table)
        // For simplicity, we'll just redirect with a success message
        // In a real app, integrate payment processing here
        header('Location: tenantDashboard.php?success=Rental confirmed successfully');
        exit;
    } catch (Exception $e) {
        error_log("Error confirming rental: " . $e->getMessage());
        header('Location: tenantDashboard.php?error=Failed to confirm rental');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta Dus="width=device-width, initial-scale=1.0">
    <title>Confirm Rental - Baas Paincha</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .property-details {
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 8px;
        }
        .property-details img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
        }
        .message-container {
            margin: 10px auto;
            padding: 10px;
            text-align: center;
            max-width: 600px;
        }
        .message-container.success {
            background-color: #dff0d8;
            color: #3c763d;
            border: 1px solid #d6e9c6;
        }
        .message-container.error {
            background-color: #f2dede;
            color: #a94442;
            border: 1px solid #ebccd1;
        }
    </style>
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="logo"><a href="index.php">Baas Paincha</a></div>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="tenantDashboard.php">Dashboard</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
            <div class="hamburger">☰</div>
        </nav>
    </header>

    <main>
        <div class="property-details">
            <h2>Confirm Rental: <?php echo htmlspecialchars($property['title']); ?></h2>
            <?php if (isset($_GET['success'])): ?>
                <div class="message-container success"><?php echo htmlspecialchars($_GET['success']); ?></div>
            <?php elseif (isset($_GET['error'])): ?>
                <div class="message-container error"><?php echo htmlspecialchars($_GET['error']); ?></div>
            <?php endif; ?>
            <img src="<?php echo htmlspecialchars($property['image'][0] ?? '/baas_paincha/uploads/placeholder.jpg'); ?>" alt="<?php echo htmlspecialchars($property['title']); ?>">
            <p><strong>Location:</strong> <?php echo htmlspecialchars($property['location']); ?></p>
            <p><strong>Rent:</strong> Rs. <?php echo htmlspecialchars($property['rent']); ?>/month</p>
            <p><strong>Description:</strong> <?php echo htmlspecialchars($property['description']); ?></p>
            <form method="POST">
                <input type="hidden" name="property_id" value="<?php echo $property['id']; ?>">
                <button type="submit" name="confirm_rental" class="btn gradient-btn">Confirm Rental</button>
            </form>
        </div>
    </main>

    <footer>
        <p>© 2025 Baas Paincha. All rights reserved.</p>
    </footer>

    <script src="script.js"></script>
    <script>
        window.onload = function() {
            const successMessage = document.querySelector('.message-container.success');
            const errorMessage = document.querySelector('.message-container.error');
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