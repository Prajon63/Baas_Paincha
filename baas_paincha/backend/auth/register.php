<?php
require 'config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = filter_input(INPUT_POST, 'full_name', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['role'];

    // Validation
    if (empty($full_name) || empty($email) || empty($password) || empty($role)) {
        echo json_encode(['error' => 'All fields are required']);
        exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['error' => 'Invalid email format']);
        exit;
    }
    if ($password !== $confirm_password) {
        echo json_encode(['error' => 'Passwords do not match']);
        exit;
    }
    if (strlen($password) < 8) {
        echo json_encode(['error' => 'Password must be at least 8 characters']);
        exit;
    }

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert into appropriate table
    try {
        $table = $role === 'owner' ? 'owners' : 'tenants';
        $stmt = $pdo->prepare("INSERT INTO $table (full_name, email, password) VALUES (?, ?, ?)");
        $stmt->execute([$full_name, $email, $hashed_password]);
        $_SESSION['user_id'] = $pdo->lastInsertId();
        $_SESSION['role'] = $role;
        echo json_encode(['success' => true, 'redirect' => $role === 'owner' ? 'ownerDashboard.php' : 'tenantDashboard.php']);
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) { // Duplicate email
            echo json_encode(['error' => 'Email already registered']);
        } else {
            echo json_encode(['error' => 'Registration failed: ' . $e->getMessage()]);
        }
    }
}
?>