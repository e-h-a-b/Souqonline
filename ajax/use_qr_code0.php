<?php
session_start();
require_once '../config.php';
require_once '../functions.php';

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
    
    // التحقق من صحة الكود أولاً
    $stmt = $pdo->prepare("SELECT * FROM store_qr_codes WHERE qr_code = ? AND is_valid = 1 AND expires_at > NOW() AND is_used = 0");
    $stmt->execute([$qr_code]);
    $qr_data = $stmt->fetch();
    
    if (!$qr_data) {
        throw new Exception('كود QR غير صالح أو مستخدم مسبقاً');
    }
    
    // استخدام الكود
    $stmt = $pdo->prepare("UPDATE store_qr_codes SET is_used = 1, used_at = NOW() WHERE qr_code = ? AND store_owner_id = ?");
    $result = $stmt->execute([$qr_code, $store_owner_id]);
    
    if (!$result) {
        throw new Exception('فشل في استخدام الكود');
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'تم استخدام الكود وتطبيق الخصم بنجاح'
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>