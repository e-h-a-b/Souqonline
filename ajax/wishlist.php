<?php
session_start();
require_once '../functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

if (!isset($_SESSION['customer_id'])) {
    echo json_encode(['success' => false, 'message' => 'يجب تسجيل الدخول']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$customerId = $_SESSION['customer_id'];
$productId = (int)($input['product_id'] ?? 0);

if ($productId <= 0) {
    echo json_encode(['success' => false, 'message' => 'معرف المنتج غير صحيح']);
    exit;
}

$action = $input['action'] ?? 'toggle';

try {
    if ($action === 'toggle') {
        if (isInWishlist($customerId, $productId)) {
            $result = removeFromWishlist($customerId, $productId);
            echo json_encode(['success' => $result, 'in_wishlist' => false]);
        } else {
            $result = addToWishlist($customerId, $productId);
            echo json_encode(['success' => $result, 'in_wishlist' => true]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'إجراء غير معروف']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'حدث خطأ: ' . $e->getMessage()]);
}
?>