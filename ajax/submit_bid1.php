<?php
require_once '../functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'طريقة غير مسموحة']);
    exit;
}

if (!isset($_SESSION['customer_id'])) {
    echo json_encode(['success' => false, 'message' => 'يجب تسجيل الدخول', 'redirect' => 'account.php']);
    exit;
}

$productId = (int)$_POST['product_id'];
$bidAmount = (float)$_POST['bid_amount'];
$customerId = $_SESSION['customer_id'];

// التحقق من صحة المزايدة
$product = getProductById($productId);
if (!$product || !$product['auction_enabled']) {
    echo json_encode(['success' => false, 'message' => 'المزاد غير متاح']);
    exit;
}

if (strtotime($product['auction_end_time']) <= time()) {
    echo json_encode(['success' => false, 'message' => 'انتهى وقت المزاد']);
    exit;
}

$minBid = max($product['current_bid'], $product['starting_price']) + 1;
if ($bidAmount < $minBid) {
    echo json_encode(['success' => false, 'message' => "المبلغ يجب أن يكون {$minBid} على الأقل"]);
    exit;
}

// حفظ المزايدة
global $pdo;
try {
    $pdo->beginTransaction();
    
    // إدخال المزايدة
    $stmt = $pdo->prepare("INSERT INTO product_bids (product_id, customer_id, bid_amount) VALUES (?, ?, ?)");
    $stmt->execute([$productId, $customerId, $bidAmount]);
    
    // تحديث أعلى مزايدة
    $stmt = $pdo->prepare("UPDATE products SET current_bid = ?, bid_count = bid_count + 1 WHERE id = ?");
    $stmt->execute([$bidAmount, $productId]);
    
    $pdo->commit();
    
    echo json_encode(['success' => true, 'message' => 'تم تقديم المزايدة بنجاح']);
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'حدث خطأ في تقديم المزايدة']);
}
?>