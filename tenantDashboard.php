<?php
session_start();
require 'backend/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'tenant') {
    header('Location: index.php');
    exit;
}

$tenant_id = $_SESSION['user_id'];

// Fetch available properties
try {
    $query = "SELECT * FROM properties";
    $conditions = [];
    $params = [];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $location = $_POST['location'] ?? '';
        $rate = $_POST['rate'] ?? '';

        if (!empty($location)) {
            $conditions[] = "location LIKE ?";
            $params[] = "%$location%";
        }
        if (!empty($rate)) {
            $rate = (float)$rate;
            $conditions[] = "rent BETWEEN ? AND ?";
            $params[] = $rate - 3000;
            $params[] = $rate + 3000;
        }

        if (!empty($conditions)) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
    } else {
        $stmt = $pdo->query($query);
    }

    $properties = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Decode JSON image field
    foreach ($properties as &$property) {
        $property['image'] = json_decode($property['image'], true) ?? [];
    }
    unset($property);
} catch (PDOException $e) {
    $properties = [];
    error_log("Error fetching properties: " . $e->getMessage());
}

// Fetch tenant's rental requests
try {
    $stmt = $pdo->prepare("
        SELECT rr.id, rr.property_id, rr.status, p.title AS property_title
        FROM rental_requests rr
        JOIN properties p ON rr.property_id = p.id
        WHERE rr.tenant_id = ?
        ORDER BY rr.request_date DESC
    ");
    $stmt->execute([$tenant_id]);
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $requests = [];
    error_log("Error fetching rental requests: " . $e->getMessage());
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
        .request-card {
            background: #f9f9f9;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 8px;
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
            <h1>Tenant Dashboard</h1>
            <?php if (isset($_GET['success'])): ?>
                <div class="message-container success"><?php echo htmlspecialchars($_GET['success']); ?></div>
            <?php elseif (isset($_GET['error'])): ?>
                <div class="message-container error"><?php echo htmlspecialchars($_GET['error']); ?></div>
            <?php endif; ?>

            <section>
                <form class="search-form" method="POST">
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
                    <?php foreach ($properties as $property): ?>
                        <div class="house-card" data-property-id="<?php echo $property['id']; ?>">
                            <img src="<?php echo htmlspecialchars($property['image'][0] ?? '/baas_paincha/uploads/placeholder.jpg'); ?>" alt="<?php echo htmlspecialchars($property['title']); ?>">
                            <div class="house-info">
                                <h3><?php echo htmlspecialchars($property['title']); ?></h3>
                                <p>Location: <?php echo htmlspecialchars($property['location']); ?></p>
                                <p>Rs. <?php echo htmlspecialchars($property['rent']); ?>/month</p>
                                <p><?php echo htmlspecialchars($property['description']); ?></p>
                                <?php
                                // Check if tenant has a request for this property
                                $request = array_filter($requests, fn($r) => $r['property_id'] === $property['id']);
                                $request = reset($request);
                                ?>
                                <?php if ($request): ?>
                                    <p>Request Status: <?php echo htmlspecialchars(ucfirst($request['status'])); ?></p>
                                    <?php if ($request['status'] === 'approved'): ?>
                                        <a href="purchase.php?property_id=<?php echo $property['id']; ?>" class="btn gradient-btn">Proceed to Rent</a>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <form activity="backend/request_rent.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="property_id" value="<?php echo $property['id']; ?>">
                                        <button type="submit" class="btn gradient-btn">Request to Rent</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <section>
                <h2>My Rental Requests</h2>
                <div class="request-list">
                    <?php if (empty($requests)): ?>
                        <p>No rental requests submitted.</p>
                    <?php else: ?>
                        <?php foreach ($requests as $request): ?>
                            <div class="request-card">
                                <h3><?php echo htmlspecialchars($request['property_title']); ?></h3>
                                <p>Status: <?php echo htmlspecialchars(ucfirst($request['status'])); ?></p>
                                <?php if ($request['status'] === 'approved'): ?>
                                    <a href="purchase.php?property_id=<?php echo $request['property_id']; ?>" class="btn gradient-btn">Proceed to Rent</a>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>
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