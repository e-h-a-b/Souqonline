<?php
session_start();
require_once '../functions.php';

header('Content-Type: application/json');

try {
    if (!isset($_GET['product_id'])) {
        throw new Exception('معرف المنتج مطلوب');
    }

    $productId = (int)$_GET['product_id'];
    $product = getProduct($productId);

    if (!$product) {
        throw new Exception('المنتج غير موجود');
    }

    if (!$product['auction_enabled']) {
        throw new Exception('المزاد غير مفعل لهذا المنتج');
    }

    // جلب المشاركين في المزاد
    $participants = getAuctionParticipants($productId, 20);
    $stats = getAuctionStats($productId);
    $highestBid = getHighestBid($productId);
    $currentBid = max($highestBid, $product['starting_price']);

    $response = [
        'success' => true,
        'product_id' => $productId,
        'product_title' => $product['title'],
        'product_image' => $product['main_image'] ?: 'assets/images/placeholder.jpg',
        'current_bid' => $currentBid,
        'min_bid' => $currentBid + 1,
        'time_left' => getAuctionTimeLeft($product['auction_end_time']),
        'stats' => $stats ?: [
            'total_bids' => 0,
            'total_bidders' => 0,
            'highest_bid' => $product['starting_price'],
            'lowest_bid' => $product['starting_price'],
            'average_bid' => $product['starting_price']
        ],
        'participants' => $participants ?: []
    ];

    echo json_encode($response);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>