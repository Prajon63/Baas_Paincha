<?php
require 'config.php';

$tenant_id = $_GET['tenant_id'] ?? '';

try {
    $stmt = $pdo->prepare("SELECT r.*, p.title, p.status AS property_status, o.full_name AS owner_name 
                          FROM requests r 
                          JOIN properties p ON r.property_id = p.id 
                          JOIN owners o ON p.owner_id = o.id 
                          WHERE r.tenant_id = ?");
    $stmt->execute([$tenant_id]);
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $pending = array_filter($requests, fn($request) => $request['status'] === 'pending');
    $responded = array_filter($requests, fn($request) => $request['status'] !== 'pending');

    error_log("Tenant $tenant_id - Pending requests: " . json_encode(array_column($pending, 'id')));
    error_log("Tenant $tenant_id - Responded requests: " . json_encode(array_column($responded, 'id')));

    echo json_encode(['pending' => $pending, 'responded' => $responded]);
} catch (PDOException $e) {
    error_log("Error fetching tenant requests: " . $e->getMessage());
    echo json_encode(['pending' => [], 'responded' => []]);
}
?>
get teenant_requests.php