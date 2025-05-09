<?php
// Include database configuration (which also handles session_start)
require 'config.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if the user is logged in and has the owner role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    header('Location: ../index.php');
    exit;
}

// Ensure the request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../ownerDashboard.php?error=Invalid request');
    exit;
}

// Get the property ID from the form
$property_id = filter_input(INPUT_POST, 'property_id', FILTER_SANITIZE_NUMBER_INT);
if (!$property_id) {
    header('Location: ../ownerDashboard.php?error=Invalid property ID');
    exit;
}

try {
    // Verify the property belongs to the logged-in owner
    $owner_id = $_SESSION['user_id'];
    $stmt = $pdo->prepare("SELECT image FROM properties WHERE id = ? AND owner_id = ?");
    $stmt->execute([$property_id, $owner_id]);
    $property = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$property) {
        header('Location: ../ownerDashboard.php?error=Property not found or you do not have permission to delete it');
        exit;
    }

    // Delete associated images from the filesystem
    $images = json_decode($property['image'], true) ?? [];
    foreach ($images as $imageUrl) {
        // Convert URL to filesystem path (e.g., /baas_paincha/uploads/image.jpg to ../uploads/image.jpg)
        $imagePath = str_replace('/baas_paincha/uploads/', '../uploads/', $imageUrl);
        if (file_exists($imagePath)) {
            unlink($imagePath); // Delete the file
        }
    }

    // Delete the property from the database
    $stmt = $pdo->prepare("DELETE FROM properties WHERE id = ? AND owner_id = ?");
    $stmt->execute([$property_id, $owner_id]);

    // Redirect back to the dashboard with a success message
    header('Location: ../ownerDashboard.php?success=Property deleted successfully');
    exit;
} catch (PDOException $e) {
    error_log("Error deleting property: " . $e->getMessage());
    header('Location: ../ownerDashboard.php?error=Error deleting property: ' . htmlspecialchars($e->getMessage()));
    exit;
}
?>