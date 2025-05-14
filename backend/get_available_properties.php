<?php
require '../backend/config.php';

try {
    $stmt = $pdo->prepare("SELECT DISTINCT p.* FROM properties p WHERE status = 'available' LIMIT 9");
    $stmt->execute();
    $properties = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($properties as &$property) {
        $images = json_decode($property['image'], true);
        $property['image'] = is_array($images) && !empty($images) ? $images[0] : 'https://via.placeholder.com/300x150?text=No+Image';
    }
    echo json_encode($properties);
} catch (PDOException $e) {
    error_log("Error fetching available properties: " . $e->getMessage());
    echo json_encode([]);
}
?>