<?php
session_start();
require_once '../config.php';
require_once '../functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['customer_id'])) {
    echo json_encode(['success' => false, 'points' => 0]);
    exit;
}

$points_data = updateCustomerPointsDisplay();
echo json_encode($points_data);
?>