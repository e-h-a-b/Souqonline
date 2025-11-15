<?php
session_start();
require_once '../config.php';
require_once '../functions.php';
require_once 'admin/qr_functions.php';

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
    
    if (!$qr_code) {
        throw new Exception('كود QR مطلوب');
    }
    
    // جلب تفاصيل QR Code
    $stmt = $pdo->prepare("
        SELECT 
            sqc.*,
            p.title as product_title,
            c.first_name as customer_name,
            store.first_name as store_owner_name,
            (SELECT COUNT(*) FROM qr_code_analytics WHERE qr_code_id = sqc.id AND action = 'scanned') as scan_count,
            (SELECT COUNT(*) FROM qr_code_analytics WHERE qr_code_id = sqc.id AND action = 'used') as use_count
        FROM store_qr_codes sqc
        LEFT JOIN products p ON sqc.product_id = p.id
        LEFT JOIN customers c ON sqc.customer_id = c.id
        LEFT JOIN customers store ON sqc.store_owner_id = store.id
        WHERE sqc.qr_code = ? AND (sqc.customer_id = ? OR sqc.store_owner_id = ?)
    ");
    $stmt->execute([$qr_code, $_SESSION['customer_id'], $_SESSION['customer_id']]);
    $qr_data = $stmt->fetch();
    
    if (!$qr_data) {
        throw new Exception('كود QR غير موجود أو ليس لديك صلاحية الوصول');
    }
    
    // تنسيق البيانات
    $qr_data['original_price'] = formatPrice($qr_data['original_price']);
    $qr_data['discounted_price'] = formatPrice($qr_data['discounted_price']);
    $qr_data['created_at'] = formatDate($qr_data['created_at']);
    $qr_data['expires_at'] = formatDate($qr_data['expires_at']);
    $qr_data['used_at'] = $qr_data['used_at'] ? formatDate($qr_data['used_at']) : null;
    
    echo json_encode([
        'success' => true,
        'data' => $qr_data
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>