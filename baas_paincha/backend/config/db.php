<?php
session_start();
$host = 'localhost';
$dbname = 'baas_paincha';
$username = 'root';
$password = ''; // Update with your MySQL password

try {
    // Check if database exists
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $result = $pdo->query("SHOW DATABASES LIKE '$dbname'");
    
    if ($result->rowCount() == 0) {
        // Database doesn't exist, run setup script
        include 'setup_database.php';
    }

    // Connect to the database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>