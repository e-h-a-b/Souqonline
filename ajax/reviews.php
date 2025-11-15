<?php
require_once '../functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

switch ($action) {
    case 'vote_helpful':
        $reviewId = (int)($input['review_id'] ?? 0);
        if ($reviewId > 0 && voteOnReview($reviewId, true)) {
            // جلب العدد الجديد
            global $pdo;
            $stmt = $pdo->prepare("SELECT helpful_count FROM reviews WHERE id = ?");
            $stmt->execute([$reviewId]);
            $newCount = $stmt->fetchColumn();
            
            echo json_encode(['success' => true, 'new_count' => $newCount]);
        } else {
            echo json_encode(['success' => false, 'message' => 'فشل في التصويت']);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'إجراء غير معروف']);
}