<?php
require 'config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $role = $_POST['role'];

    // Validation
    if (empty($email) || empty($password) || empty($role)) {
        echo json_encode(['error' => 'All fields are required']);
        exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['error' => 'Invalid email format']);
        exit;
    }

    // Check user in appropriate table
    try {
        $table = $role === 'owner' ? 'owners' : 'tenants';
        $stmt = $pdo->prepare("SELECT * FROM $table WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $role;
            echo json_encode(['success' => true, 'redirect' => $role === 'owner' ? 'ownerDashboard.php' : 'tenantDashboard.php']);
        } else {
            echo json_encode(['error' => 'Invalid email or password']);
        }
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Login failed: ' . $e->getMessage()]);
    }
}
?>