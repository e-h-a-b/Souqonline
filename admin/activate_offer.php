<?php
session_start();
require_once '../config.php';
require_once '../functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['customer_id'])) {
    echo json_encode(['success' => false, 'message' => 'غير مصرح']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['product_id']) || !isset($input['type'])) {
    echo json_encode(['success' => false, 'message' => 'بيانات غير مكتملة']);
    exit;
}

$result = activateSmartOffer($input['product_id'], $input['type'], $_SESSION['customer_id']);
echo json_encode($result);
?>