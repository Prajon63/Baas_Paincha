<?php
require 'config.php';

header('Content-Type: application/json');

$location = trim($_GET['location'] ?? ''); // Trim spaces
$rate = $_GET['rate'] ? (int)$_GET['rate'] : 0;

try {
    $query = "SELECT * FROM properties WHERE status = 'available'";
    $params = [];
    if ($location) {
        $query .= " AND LOWER(location) LIKE ?";
        $params[] = "%" . strtolower($location) . "%";
    }
    if ($rate) {
        $query .= " AND rent BETWEEN ? AND ?";
        $params[] = $rate - 3000;
        $params[] = $rate + 3000;
    }
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $properties = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Decode image field for each property
    foreach ($properties as &$property) {
        $property['image'] = json_decode($property['image'], true) ? json_decode($property['image'], true)[0] : $property['image'];
    }

    echo json_encode($properties);
} catch (PDOException $e) {
    error_log("Error searching properties: " . $e->getMessage());
    echo json_encode([]);
}
?>