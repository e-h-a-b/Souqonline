<?php
require_once '../functions.php';

if (!isset($_GET['product_id'])) {
    echo json_encode(['success' => false, 'message' => 'معرف المنتج مطلوب']);
    exit;
}

$productId = (int)$_GET['product_id'];
//$product = getProductById($productId);
// جلب المنتجات حسب الفلاتر
$products = getProducts([ 
    'id' => $productId
]);
if (!$product || !$product['auction_enabled']) {
    echo json_encode(['success' => false, 'message' => 'المزاد غير متاح']);
    exit;
}

// جلب آخر المزايدات
$bids = getRecentBids($productId, 10);

echo json_encode([
    'success' => true,
    'product_id' => $productId,
    'product_title' => $product['title'],
    'product_image' => $product['main_image'] ?: 'assets/images/placeholder.jpg',
    'current_bid' => max($product['current_bid'], $product['starting_price']),
    'min_bid' => max($product['current_bid'], $product['starting_price']) + 1,
    'max_bid' => max($product['current_bid'], $product['starting_price']) * 2,
    'time_left' => getAuctionTimeLeft($product['auction_end_time']),
    'recent_bids' => $bids
]);
?>