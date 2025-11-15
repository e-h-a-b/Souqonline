<?php
/**
 * صفحة معالجة شحن المحفظة والتوجه لبوابة الدفع
 */
session_start();
require_once 'config.php';
require_once 'functions.php';

if (!isset($_SESSION['customer_id'])) {
    header('Location: login.php');
    exit;
}

$customer_id = $_SESSION['customer_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'deposit') {
    $amount = floatval($_POST['amount']);
    $payment_method = $_POST['payment_method'];
    
    // التحقق من صحة المبلغ
    if ($amount < 10 || $amount > 10000) {
        $_SESSION['error'] = "المبلغ يجب أن يكون بين 10 و 10,000 جنيه";
        header('Location: account.php');
        exit;
    }
    
    // إنشاء طلب شحن جديد في قاعدة البيانات
    try {
        $pdo->beginTransaction();
        
        // إنشاء رقم مرجعي فريد للشحن
        $deposit_ref = 'DEP' . date('YmdHis') . rand(1000, 9999);
        
        // إدخال طلب الشحن
        $stmt = $pdo->prepare("
            INSERT INTO wallet_deposits 
            (customer_id, amount, payment_method, deposit_ref, status, created_at) 
            VALUES (?, ?, ?, ?, 'pending', NOW())
        ");
        $stmt->execute([$customer_id, $amount, $payment_method, $deposit_ref]);
        
        $deposit_id = $pdo->lastInsertId();
        
        $pdo->commit();
        
        // تخزين معلومات الشحن في الجلسة للتوجه لصفحة الدفع
        $_SESSION['pending_deposit'] = [
            'id' => $deposit_id,
            'amount' => $amount,
            'payment_method' => $payment_method,
            'deposit_ref' => $deposit_ref
        ];
        
        // التوجه لصفحة الدفع
        header('Location: payment-gateway.php?type=deposit&deposit=' . $deposit_id);
        exit;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "حدث خطأ في عملية الشحن: " . $e->getMessage();
        header('Location: account.php');
        exit;
    }
} else {
    header('Location: account.php');
    exit;
}
?>