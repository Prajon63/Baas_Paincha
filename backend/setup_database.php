<?php
$host = 'localhost';
$username = 'root';
$password = ''; // Update with your MySQL password
$dbname = 'baas_paincha';

try {
    // Connect to MySQL without selecting a database
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $dbname");
    echo "Database '$dbname' created or already exists.<br>";

    // Select the database
    $pdo->exec("USE $dbname");

    // Create owners table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS owners (
            id INT AUTO_INCREMENT PRIMARY KEY,
            full_name VARCHAR(255) NOT NULL,
            email VARCHAR(255) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "Table 'owners' created or already exists.<br>";

    // Create tenants table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS tenants (
            id INT AUTO_INCREMENT PRIMARY KEY,
            full_name VARCHAR(255) NOT NULL,
            email VARCHAR(255) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "Table 'tenants' created or already exists.<br>";

    // Create password_resets table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS password_resets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) NOT NULL,
            token VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            expires_at TIMESTAMP NOT NULL,
            INDEX(email)
        )
    ");
    echo "Table 'password_resets' created or already exists.<br>";

    // Create properties table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS properties (
            id INT AUTO_INCREMENT PRIMARY KEY,
            owner_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            location VARCHAR(255) NOT NULL,
            rent DECIMAL(10, 2) NOT NULL,
            image TEXT NOT NULL,
            description TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (owner_id) REFERENCES owners(id) ON DELETE CASCADE
        )
    ");
    echo "Table 'properties' created or already exists.<br>";

    echo "Database setup completed successfully!";
} catch (PDOException $e) {
    die("Database setup failed: " . $e->getMessage());
}
?>