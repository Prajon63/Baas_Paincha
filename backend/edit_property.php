<?php
session_start();
require 'config.php';

// Check if the user is logged in and has the owner role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    header('Location: ../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $property_id = $_POST['property_id'] ?? '';
    $title = $_POST['title'] ?? '';
    $location = $_POST['location'] ?? '';
    $rent = $_POST['rent'] ?? '';
    $description = $_POST['description'] ?? '';
    $existing_images_json = $_POST['existing_images'] ?? '[]';
    $owner_id = $_SESSION['user_id'];

    // Validate input
    if (empty($property_id) || empty($title) || empty($location) || empty($rent) || empty($description)) {
        header('Location: ../ownerDashboard.php?error=All fields are required');
        exit;
    }

    try {
        // Decode existing images (after client-side removals)
        $existing_images = json_decode($existing_images_json, true);
        if (!is_array($existing_images)) {
            $existing_images = [];
        }

        // Handle file uploads (if any)
        $uploaded_images = [];
        if (!empty($_FILES['image']['name'][0])) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $max_size = 5 * 1024 * 1024; // 5MB

            $upload_dir = '../uploads/'; // Align with add_property.php (lowercase)
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            foreach ($_FILES['image']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['image']['error'][$key] === UPLOAD_ERR_OK) {
                    $file_name = uniqid() . '_' . basename($_FILES['image']['name'][$key]);
                    $file_path = $upload_dir . $file_name;
                    $file_url = '/baas_paincha/uploads/' . $file_name; // Align with add_property.php

                    // Validate file type
                    $file_type = mime_content_type($tmp_name);
                    if (!in_array($file_type, $allowed_types)) {
                        header('Location: ../ownerDashboard.php?error=Invalid file type');
                        exit;
                    }

                    // Validate file size
                    if ($_FILES['image']['size'][$key] > $max_size) {
                        header('Location: ../ownerDashboard.php?error=File size exceeds 5MB');
                        exit;
                    }

                    // Move the uploaded file
                    if (move_uploaded_file($tmp_name, $file_path)) {
                        $uploaded_images[] = $file_url;
                    } else {
                        header('Location: ../ownerDashboard.php?error=Failed to upload image');
                        exit;
                    }
                }
            }
        }

        // Combine existing images (after removals) with new uploaded images
        $images_to_save = array_merge($existing_images, $uploaded_images);

        // Update the property in the database
        $stmt = $pdo->prepare("UPDATE properties SET title = ?, location = ?, rent = ?, description = ?, image = ? WHERE id = ? AND owner_id = ?");
        $stmt->execute([$title, $location, $rent, $description, json_encode($images_to_save), $property_id, $owner_id]);

        header('Location: ../ownerDashboard.php?success=Property updated successfully');
        exit;
    } catch (PDOException $e) {
        error_log("Error updating property: " . $e->getMessage());
        header('Location: ../ownerDashboard.php?error=Failed to update property');
        exit;
    }
} else {
    header('Location: ../ownerDashboard.php?error=Invalid request');
    exit;
}
?>