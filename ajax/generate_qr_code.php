<?php
session_start();
require_once '../config.php';
require_once '../functions.php';

header('Content-Type: application/json; charset=utf-8');

// التعامل مع الأخطاء
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
    
    $product_id = $input['product_id'] ?? null;
    $store_owner_id = $input['store_owner_id'] ?? null;
    $customer_id = $_SESSION['customer_id'];
    
    if (!$product_id || !$store_owner_id) {
        throw new Exception('بيانات غير مكتملة');
    }
    
    // التحقق من أن المنتج موجود
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
    
    if (!$product) {
        throw new Exception('المنتج غير موجود');
    }
    
    // إنشاء كود QR بسيط (بدون مكتبات خارجية في البداية)
    $qr_code = 'QR_' . uniqid() . '_' . $product_id;
    $discount_percentage = 10;
    $original_price = $product['final_price'];
    $discounted_price = $original_price * (1 - ($discount_percentage / 100));
    
    // حفظ في قاعدة البيانات
    $stmt = $pdo->prepare("INSERT INTO store_qr_codes (product_id, customer_id, store_owner_id, qr_code, qr_data, original_price, discounted_price, discount_percentage, expires_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $qr_data = json_encode([
        'product_id' => $product_id,
        'product_title' => $product['title'],
        'customer_id' => $customer_id,
        'store_owner_id' => $store_owner_id,
        'original_price' => $original_price,
        'discounted_price' => $discounted_price,
        'discount_percentage' => $discount_percentage,
        'qr_code' => $qr_code,
        'created_at' => date('Y-m-d H:i:s'),
        'expires_at' => date('Y-m-d H:i:s', strtotime('+24 hours'))
    ]);
    
    $result = $stmt->execute([
        $product_id,
        $customer_id,
        $store_owner_id,
        $qr_code,
        $qr_data,
        $original_price,
        $discounted_price,
        $discount_percentage,
        date('Y-m-d H:i:s', strtotime('+24 hours'))
    ]);
    
    if (!$result) {
        throw new Exception('فشل في حفظ كود QR في قاعدة البيانات');
    }
    
    // إنشاء صورة QR باستخدام API خارجي بسيط
    $qr_image_url = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($qr_data);
    
    echo json_encode([
        'success' => true,
        'qr_code' => $qr_code,
        'qr_image_url' => $qr_image_url,
        'qr_data' => $qr_data,
        'product_title' => $product['title'],
        'original_price' => formatPrice($original_price),
        'discounted_price' => formatPrice($discounted_price),
        'discount_percentage' => $discount_percentage,
        'expires_at' => date('Y-m-d H:i:s', strtotime('+24 hours'))
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>