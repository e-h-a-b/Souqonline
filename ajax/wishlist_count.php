<?php
require_once '../functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['customer_id'])) {
    echo json_encode(['success' => false, 'count' => 0]);
    exit;
}

$customerId = $_SESSION['customer_id'];
$count = getWishlistCount();

echo json_encode(['success' => true, 'count' => $count]);
?>