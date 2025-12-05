<?php
session_start();
require_once 'config.php';
require_once 'smart_command_processor.php';

header('Content-Type: application/json');

if (!isset($_SESSION['customer_id'])) {
    echo json_encode(['success' => false, 'message' => 'يجب تسجيل الدخول أولاً']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['command'])) {
    $command = trim($_POST['command']);
    $userId = $_SESSION['customer_id'];
    
    $processor = new SmartCommandProcessor($pdo, $userId);
    $result = $processor->processCommand($command);
    
    echo json_encode([
        'success' => true,
        'message' => $result,
        'cart_updated' => true // أو منطق للتحقق من تحديث السلة
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'لم يتم استلام أمر']);
}
?>