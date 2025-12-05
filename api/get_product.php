<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'معرف المنتج مطلوب']);
    exit;
}

$productId = intval($_GET['id']);

try {
    $stmt = $pdo->prepare("
        SELECT p.*, c.name as owner_name 
        FROM products p 
        LEFT JOIN customers c ON p.created_by = c.id 
        WHERE p.id = ?
    ");
    $stmt->execute([$productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($product) {
        echo json_encode($product);
    } else {
        echo json_encode(['error' => 'المنتج غير موجود']);
    }
} catch (PDOException $e) {
    echo json_encode(['error' => 'خطأ في قاعدة البيانات']);
}
?>