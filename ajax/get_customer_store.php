<?php
ob_start();
session_start(); 
require_once '../config.php';
require_once '../functions.php';

header('Content-Type: application/json; charset=utf-8');
ob_clean();

if (!isset($_GET['customer_id']) || !is_numeric($_GET['customer_id'])) {
    echo json_encode(['success' => false, 'message' => 'معرف المستخدم مطلوب']);
    exit;
}

$customerId = (int)$_GET['customer_id'];

// === إضافة تشخيصية مؤقتة ===
$debug = [
    'customer_id' => $customerId,
    'session_customer_id' => $_SESSION['customer_id'] ?? null,
    'total_products_by_user' => 0
];

$stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE created_by = ?");
$stmt->execute([$customerId]);
$debug['total_products_by_user'] = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE created_by = ? AND is_active = 1");
$stmt->execute([$customerId]);
$debug['active_products'] = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT id, title, is_active FROM products WHERE created_by = ? LIMIT 3");
$stmt->execute([$customerId]);
$debug['sample_products'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
// === انتهاء التشخيص ===

try {
    $products = getCustomerStoreProducts($customerId, 12);
    
    if (empty($products)) {
        echo json_encode([
            'success' => false,
            'message' => 'المتجر غير موجود',
            'debug' => $debug  // مؤقت
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            'success' => true,
            'products' => $products,
            'count' => count($products)
        ], JSON_UNESCAPED_UNICODE);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'خطأ: ' . $e->getMessage(),
        'debug' => $debug
    ], JSON_UNESCAPED_UNICODE);
}
exit;
?>