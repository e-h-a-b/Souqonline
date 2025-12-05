<?php
session_start();
require_once '../config.php';
require_once '../functions.php';
require_once '../qr_functions.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if (!isset($_SESSION['customer_id'])) {
        throw new Exception('يجب تسجيل الدخول أولاً');
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('طريقة الطلب غير صحيحة');
    }

    $input = json_decode(file_get_contents('php://input'), true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('بيانات JSON غير صالحة');
    }
    
    $qr_code = $input['qr_code'] ?? null;
    $store_owner_id = $_SESSION['customer_id'];
    
    if (!$qr_code) {
        throw new Exception('كود QR مطلوب');
    }
    
    // استخدام الدالة الجديدة مع التسجيل
    $result = useQRCodeWithLogging($qr_code, $store_owner_id);
    
    echo json_encode($result, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>