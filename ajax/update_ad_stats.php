<?php
session_start();
require_once '../config.php';
require_once '../functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $adId = intval($_POST['ad_id'] ?? 0);
    $type = $_POST['type'] ?? 'views';
    
    if ($adId > 0) {
        try {
            // تحديث الإحصائيات في قاعدة البيانات
            $field = $type === 'clicks' ? 'clicks' : 'views';
            $stmt = $pdo->prepare("UPDATE advertisements SET $field = $field + 1 WHERE id = ?");
            $stmt->execute([$adId]);
            
            echo json_encode(['success' => true, 'message' => 'Ad stats updated']);
        } catch (Exception $e) {
            error_log("Error updating ad stats: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error updating stats']);
        }
    } else {
        echo json_encode(['success' => true, 'message' => 'Test ad - no stats updated']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>