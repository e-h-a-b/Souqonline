<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['customer_id'])) {
    echo json_encode(['success' => false, 'message' => 'يجب تسجيل الدخول']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['target_product_id']) || !isset($input['my_product_id'])) {
    echo json_encode(['success' => false, 'message' => 'بيانات غير مكتملة']);
    exit;
}

try {
    // إدخال عرض المقايضة في قاعدة البيانات
    $stmt = $pdo->prepare("
        INSERT INTO barter_offers 
        (target_product_id, target_owner_id, offer_product_id, offer_owner_id, message, status, created_at) 
        VALUES (?, ?, ?, ?, ?, 'pending', NOW())
    ");
    
    $success = $stmt->execute([
        $input['target_product_id'],
        $input['target_owner_id'],
        $input['my_product_id'],
        $_SESSION['customer_id'],
        $input['message'] ?? ''
    ]);
    
    if ($success) {
        // يمكن إضافة إشعار للمالك هنا
        echo json_encode(['success' => true, 'message' => 'تم إرسال العرض بنجاح']);
    } else {
        echo json_encode(['success' => false, 'message' => 'فشل في إرسال العرض']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'خطأ في قاعدة البيانات']);
}
?>