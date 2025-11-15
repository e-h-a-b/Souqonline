<?php
session_start();
require_once '../functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'طريقة غير مسموحة']);
    exit;
}

header('Content-Type: application/json');

// التحقق من تسجيل الدخول
if (!isset($_SESSION['customer_id'])) {
    echo json_encode([
        'success' => false, 
        'message' => 'يجب تسجيل الدخول للتفاوض على السعر',
        'redirect' => 'account.php'
    ]);
    exit;
}

// التحقق من إمكانية التفاوض
if (!isNegotiationEnabled()) {
    echo json_encode(['success' => false, 'message' => 'خدمة التفاوض غير متاحة حالياً']);
    exit;
}

$customerId = $_SESSION['customer_id'];
$productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$offeredPrice = isset($_POST['offered_price']) ? (float)$_POST['offered_price'] : 0;
$notes = isset($_POST['notes']) ? cleanInput($_POST['notes']) : '';

if ($productId <= 0 || $offeredPrice <= 0) {
    echo json_encode(['success' => false, 'message' => 'بيانات غير صحيحة']);
    exit;
}

// إرسال طلب التفاوض
$result = submitNegotiation($customerId, $productId, $offeredPrice, $notes);
echo json_encode($result);
?>