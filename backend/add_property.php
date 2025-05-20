<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    header('Location: ../index.php');
    exit;
}

require 'config.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../ownerDashboard.php');
    exit;
}

$owner_id = $_SESSION['user_id'];
$title = $_POST['title'] ?? '';
$location = $_POST['location'] ?? '';
$rent = $_POST['rent'] ?? '';
$description = $_POST['description'] ?? '';

// Validate required fields
if (empty($title) || empty($location) || empty($rent) || empty($description)) {
    die('All fields are required.');
}

// Additional validation: Rent must be a positive number
if (!is_numeric($rent) || $rent <= 0) {
    die('Rent must be a positive number.');
}

// Handle multiple image uploads
$images = [];
if (isset($_FILES['image']) && !empty($_FILES['image']['name'][0])) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $maxSize = 5 * 1024 * 1024; // 5MB

    $uploadDir = '../uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    foreach ($_FILES['image']['tmp_name'] as $index => $tmpName) {
        if ($_FILES['image']['error'][$index] === UPLOAD_ERR_OK) {
            if (!in_array($_FILES['image']['type'][$index], $allowedTypes)) {
                die('Invalid image format at index ' . $index . '. Only JPEG, PNG, and GIF are allowed.');
            }
            if ($_FILES['image']['size'][$index] > $maxSize) {
                die('Image at index ' . $index . ' exceeds 5MB limit.');
            }

            $imageName = uniqid() . '_' . basename($_FILES['image']['name'][$index]);
            $imagePath = $uploadDir . $imageName;
            $imageUrl = '/baas_paincha/uploads/' . $imageName;

            if (move_uploaded_file($tmpName, $imagePath)) {
                $images[] = $imageUrl;
            } else {
                die('Failed to upload image at index ' . $index . '.');
            }
        }
    }

    if (empty($images)) {
        die('No images were uploaded successfully.');
    }

    // Optional: Log uploaded image URLs for audit/debug
    file_put_contents('upload_log.txt', implode("\n", $images) . "\n", FILE_APPEND);

} else {
    die('At least one image is required.');
}

try {
    // Insert with created_at timestamp
    $stmt = $pdo->prepare("INSERT INTO properties (owner_id, title, location, rent, image, description, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$owner_id, $title, $location, $rent, json_encode($images), $description]);

    header('Location: ../ownerDashboard.php?success=Property added successfully');
    exit;
} catch (PDOException $e) {
    error_log("Error adding property: " . $e->getMessage());
    die('Error adding property: ' . $e->getMessage());
}
?>
