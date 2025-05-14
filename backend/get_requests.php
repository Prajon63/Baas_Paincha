<?php
require 'config.php';

header('Content-Type: application/json');

// Initialize response with empty arrays to avoid null values
$response = ['pending' => [], 'responded' => []];

$tenant_id = $_GET['tenant_id'] ?? null;
$owner_id = $_GET['owner_id'] ?? null;

if ($tenant_id) {
    try {
        $stmt = $pdo->prepare("SELECT r.*, p.title AS property_title, o.full_name AS owner_name 
                              FROM requests r 
                              JOIN properties p ON r.property_id = p.id 
                              JOIN owners o ON p.owner_id = o.id 
                              WHERE r.tenant_id = ?");
        $stmt->execute([$tenant_id]);
        $requests = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: []; // Fallback to empty array

        $response = [
            'pending' => array_values(array_filter($requests, fn($request) => $request['status'] === 'pending')),
            'responded' => array_values(array_filter($requests, fn($request) => $request['status'] !== 'pending'))
        ];
        error_log("Fetched tenant requests for $tenant_id: " . json_encode(array_column($requests, 'id')));
    } catch (PDOException $e) {
        error_log("Error fetching tenant requests: " . $e->getMessage());
    }
} elseif ($owner_id) {
    try {
        $stmt = $pdo->prepare("SELECT r.*, p.title, t.full_name AS tenant_name 
                              FROM requests r 
                              JOIN properties p ON r.property_id = p.id 
                              JOIN tenants t ON r.tenant_id = t.id 
                              WHERE p.owner_id = ?");
        $stmt->execute([$owner_id]);
        $requests = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: []; // Fallback to empty array

        $response = [
            'pending' => array_values(array_filter($requests, fn($request) => $request['status'] === 'pending')),
            'responded' => array_values(array_filter($requests, fn($request) => $request['status'] !== 'pending'))
        ];
        error_log("Fetched requests for owner $owner_id: " . json_encode(array_column($requests, 'id')));
    } catch (PDOException $e) {
        error_log("Error fetching owner requests: " . $e->getMessage());
    }
}

// Ensure response is always a valid JSON with arrays
echo json_encode($response);
?>