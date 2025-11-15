<?php
session_start();
require_once '../config.php';
require_once '../functions.php';

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
    
    // التحقق من صحة الكود
    $stmt = $pdo->prepare("SELECT sqc.*, p.title as product_title, c.first_name as customer_name, c.phone as customer_phone, store.first_name as store_owner_name 
                          FROM store_qr_codes sqc 
                          LEFT JOIN products p ON sqc.product_id = p.id 
                          LEFT JOIN customers c ON sqc.customer_id = c.id 
                          LEFT JOIN customers store ON sqc.store_owner_id = store.id 
                          WHERE sqc.qr_code = ? AND sqc.is_valid = 1 AND sqc.expires_at > NOW()");
    $stmt->execute([$qr_code]);
    $qr_data = $stmt->fetch();
    
    if (!$qr_data) {
        throw new Exception('كود QR غير صالح أو منتهي الصلاحية');
    }
    
    if ($qr_data['is_used']) {
        throw new Exception('كود QR مستخدم مسبقاً');
    }
    
    echo json_encode([
        'valid' => true,
        'data' => [
            'product_title' => $qr_data['product_title'],
            'customer_name' => $qr_data['customer_name'] ?: 'عميل',
            'customer_phone' => $qr_data['customer_phone'] ?: 'غير متوفر',
            'store_owner_name' => $qr_data['store_owner_name'] ?: 'متجر',
            'original_price' => formatPrice($qr_data['original_price']),
            'discounted_price' => formatPrice($qr_data['discounted_price']),
            'discount_percentage' => $qr_data['discount_percentage'],
            'expires_at' => $qr_data['expires_at']
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'valid' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>