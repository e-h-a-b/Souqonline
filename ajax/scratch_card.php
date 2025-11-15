<?php
session_start();
require_once '../functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['customer_id'])) {
    echo json_encode(['success' => false, 'message' => 'يجب تسجيل الدخول']);
    exit;
}

$customer_id = $_SESSION['customer_id'];
$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'get_cards':
            $product_id = $_POST['product_id'] ?? null;
            $cards = getCustomerScratchCards($customer_id, $product_id);
            echo json_encode(['success' => true, 'cards' => $cards]);
            break;
            
        case 'scratch':
            $card_id = $_POST['card_id'] ?? 0;
            if (scratchCard($card_id, $customer_id)) {
                // الحصول على بيانات الكارت المخدوش
                $stmt = $pdo->prepare("SELECT * FROM scratch_cards WHERE id = ?");
                $stmt->execute([$card_id]);
                $card = $stmt->fetch(PDO::FETCH_ASSOC);
                
                echo json_encode(['success' => true, 'card' => $card]);
            } else {
                echo json_encode(['success' => false, 'message' => 'فشل في خربشة الكارت']);
            }
            break;
            
        case 'claim':
            $card_id = $_POST['card_id'] ?? 0;
            $result = claimScratchCardReward($card_id, $customer_id);
            if ($result) {
                echo json_encode(['success' => true, 'reward' => $result]);
            } else {
                echo json_encode(['success' => false, 'message' => 'فشل في المطالبة بالمكافأة']);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'إجراء غير معروف']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>