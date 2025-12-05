<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['customer_id'])) {
    echo json_encode(['error' => 'يجب تسجيل الدخول']);
    exit;
}

$userId = $_SESSION['customer_id'];

try {
    $stmt = $pdo->prepare("
        SELECT * FROM products 
        WHERE created_by = ? AND stock > 0 AND status = 'active'
        ORDER BY created_at DESC
    ");
    $stmt->execute([$userId]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($products);
} catch (PDOException $e) {
    echo json_encode(['error' => 'خطأ في قاعدة البيانات']);
}
?>