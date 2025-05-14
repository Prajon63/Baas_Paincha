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

    // Create properties table with status column
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS properties (
            id INT AUTO_INCREMENT PRIMARY KEY,
            owner_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            location VARCHAR(255) NOT NULL,
            rent DECIMAL(10, 2) NOT NULL,
            image TEXT NOT NULL,
            description TEXT NOT NULL,
            status ENUM('available', 'rented', 'unavailable') DEFAULT 'available',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (owner_id) REFERENCES owners(id) ON DELETE CASCADE
        )
    ");
    echo "Table 'properties' created or already exists.<br>";

    // Add status column to properties if it doesn't exist
    $columns = $pdo->query("SHOW COLUMNS FROM properties LIKE 'status'")->fetchAll();
    if (empty($columns)) {
        $pdo->exec("ALTER TABLE properties ADD COLUMN status ENUM('available', 'rented', 'unavailable') DEFAULT 'available'");
        echo "Added 'status' column to 'properties' table.<br>";
    } else {
        echo "'status' column already exists in 'properties' table.<br>";
    }

    // Optional: Add unique constraint to prevent duplicate properties (based on title, location, rent)
    $pdo->exec("ALTER TABLE properties ADD UNIQUE (title, location, rent)");
    echo "Added unique constraint on (title, location, rent) to 'properties' table.<br>";

    // Create requests table with meeting_time and unique constraint
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            property_id INT NOT NULL,
            tenant_id INT NOT NULL,
            owner_id INT NOT NULL,
            status ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending',
            name VARCHAR(255) NOT NULL,
            age INT NOT NULL,
            occupation VARCHAR(255) NOT NULL,
            num_people INT NOT NULL,
            citizenship_copy VARCHAR(255),
            owner_response TEXT,
            meeting_time DATETIME,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
            FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
            FOREIGN KEY (owner_id) REFERENCES owners(id) ON DELETE CASCADE,
            CONSTRAINT unique_request UNIQUE (property_id, tenant_id)
        )
    ");
    echo "Table 'requests' created or already exists.<br>";

    // Check if meeting_time column exists and add it if not
    $columns = $pdo->query("SHOW COLUMNS FROM requests LIKE 'meeting_time'")->fetchAll();
    if (empty($columns)) {
        $pdo->exec("ALTER TABLE requests ADD COLUMN meeting_time DATETIME");
        echo "Added 'meeting_time' column to 'requests' table.<br>";
    } else {
        echo "'meeting_time' column already exists in 'requests' table.<br>";
    }

    // Check if unique constraint exists and add it if not
    $result = $pdo->query("SHOW INDEX FROM requests WHERE Key_name = 'unique_request'")->fetchAll();
    if (empty($result)) {
        $pdo->exec("ALTER TABLE requests ADD CONSTRAINT unique_request UNIQUE (property_id, tenant_id)");
        echo "Added unique constraint on (property_id, tenant_id) to 'requests' table.<br>";
    } else {
        echo "Unique constraint on (property_id, tenant_id) already exists in 'requests' table.<br>";
    }

    echo "Database setup completed successfully!";
} catch (PDOException $e) {
    die("Database setup failed: " . $e->getMessage());
}
?>