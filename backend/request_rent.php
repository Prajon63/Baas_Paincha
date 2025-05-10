<?php
session_start();
require '../backend/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'tenant') {
    header('Location: ../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['property_id'])) {
    $tenant_id = $_SESSION['user_id'];
    $property_id = (int)$_POST['property_id'];

    try {
        // Fetch property details to get owner_id
        $stmt = $pdo->prepare("SELECT owner_id FROM properties WHERE id = ?");
        $stmt->execute([$property_id]);
        $property = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$property) {
            header('Location: ../index.php?error=Property not found');
            exit;
        }

        // Check if a request already exists
        $stmt = $pdo->prepare("SELECT id FROM rental_requests WHERE tenant_id = ? AND property_id = ? AND status = 'pending'");
        $stmt->execute([$tenant_id, $property_id]);
        if ($stmt->fetch()) {
            header('Location: ../index.php?error=You have already sent a request for this property');
            exit;
        }

        // Insert rental request
        $stmt = $pdo->prepare("INSERT INTO rental_requests (tenant_id, property_id, owner_id, status) VALUES (?, ?, ?, 'pending')");
        $stmt->execute([$tenant_id, $property_id, $property['owner_id']]);

        header('Location: ../index.php?success=Rental request sent successfully');
        exit;
    } catch (PDOException $e) {
        error_log("Error creating rental request: " . $e->getMessage());
        header('Location: ../index.php?error=Failed to send rental request');
        exit;
    }
} else {
    header('Location: ../index.php');
    exit;
}
?>