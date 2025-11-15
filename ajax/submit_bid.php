<?php
session_start();
require_once '../functions.php';

header('Content-Type: application/json');

try {
    // التحقق من تسجيل الدخول
    if (!isset($_SESSION['customer_id'])) {
        throw new Exception('يجب تسجيل الدخول للمشاركة في المزاد');
    }

    // التحقق من البيانات
    if (!isset($_POST['product_id']) || !isset($_POST['bid_amount'])) {
        throw new Exception('بيانات غير مكتملة');
    }

    $productId = (int)$_POST['product_id'];
    $bidAmount = (float)$_POST['bid_amount'];
    $customerId = $_SESSION['customer_id'];

    // التحقق من المبلغ
    if ($bidAmount <= 0) {
        throw new Exception('يجب أن يكون مبلغ المزايدة أكبر من الصفر');
    }

    // الحصول على بيانات المنتج
    $product = getProduct($productId);
    if (!$product || !$product['auction_enabled']) {
        throw new Exception('المزاد غير متاح لهذا المنتج');
    }

    // التحقق من أن المزاد لم ينتهِ
    if (!isAuctionActive($product)) {
        throw new Exception('انتهى وقت المزاد');
    }

    // الحصول على أعلى مزايدة حالية
    $currentBid = getHighestBid($productId);
    $minBid = max($currentBid, $product['starting_price']) + 1;

    if ($bidAmount < $minBid) {
        throw new Exception("يجب أن تكون المزايدة أعلى من " . formatPrice($minBid));
    }

    // تقديم المزايدة
    $result = placeBid($productId, $customerId, $bidAmount);

    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'message' => $result['message'],
            'new_bid' => $bidAmount
        ]);
    } else {
        throw new Exception($result['message']);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>