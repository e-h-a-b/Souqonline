<?php
session_start();
require_once '../config.php';
require_once '../functions.php';
require_once 'admin/qr_functions.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('طريقة الطلب غير صحيحة');
    }

    $input = json_decode(file_get_contents('php://input'), true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('بيانات JSON غير صالحة');
    }
    
    $qr_code = $input['qr_code'] ?? null;
    
    if (!$qr_code) {
        throw new Exception('كود QR مطلوب');
    }
    
    // استخدام الدالة الجديدة مع التسجيل
    $validation = validateQRCodeWithLogging($qr_code);
    
    if ($validation['valid']) {
        echo json_encode([
            'valid' => true,
            'data' => [
                'product_title' => $validation['data']['product_title'],
                'customer_name' => $validation['data']['customer_name'],
                'customer_phone' => $validation['data']['customer_phone'],
                'store_owner_name' => $validation['data']['store_owner_name'],
                'original_price' => formatPrice($validation['data']['original_price']),
                'discounted_price' => formatPrice($validation['data']['discounted_price']),
                'discount_percentage' => $validation['data']['discount_percentage'],
                'expires_at' => $validation['data']['expires_at']
            ]
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(['valid' => false, 'message' => $validation['message']]);
    }

} catch (Exception $e) {
    echo json_encode([
        'valid' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>