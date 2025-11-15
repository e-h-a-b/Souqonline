<?php
require_once 'donation_system.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get_required_amount':
        $storeId = isset($_GET['store_id']) && $_GET['store_id'] !== '' ? $_GET['store_id'] : null;
        $result = DonationSystem::calculateRequiredDonation($storeId);
        echo json_encode(['success' => true, ...$result]);
        break;
        
    case 'preview_distribution':
        $storeId = isset($_GET['store_id']) && $_GET['store_id'] !== '' ? $_GET['store_id'] : null;
        $amount = floatval($_GET['amount']);
        $method = $_GET['method'] ?? 'equal';
        
        $result = DonationSystem::distributeDonation($amount, $storeId, $method);
        echo json_encode($result);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Action not found']);
}
?>