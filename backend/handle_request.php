<?php
session_start();
require '../backend/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    header('Location: ../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id']) && isset($_POST['action'])) {
    $request_id = (int)$_POST['request_id'];
    $action = $_POST['action'];
    $owner_id = $_SESSION['user_id'];
    $status = ($action === 'approve') ? 'approved' : 'rejected';

    try {
        // Update request status
        $stmt = $pdo->prepare("UPDATE rental_requests SET status = ? WHERE id = ? AND owner_id = ?");
        $stmt->execute([$status, $request_id, $owner_id]);

        header('Location: ../ownerDashboard.php?success=Request ' . $action . 'd successfully');
        exit;
    } catch (PDOException $e) {
        error_log("Error handling request: " . $e->getMessage());
        header('Location: ../ownerDashboard.php?error=Failed to process request');
        exit;
    }
} else {
    header('Location: ../ownerDashboard.php');
    exit;
}
?>