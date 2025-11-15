<?php
session_start();
require_once 'config.php';
require_once 'functions.php';

if (!isset($_SESSION['customer_id'])) {
    header('Location: login.php');
    exit;
}

$customer_id = $_SESSION['customer_id'];
$action = $_POST['action'] ?? '';

if ($action === 'deposit') {
    $amount = floatval($_POST['amount']);
    $payment_method = $_POST['payment_method'];
    
    if ($amount < 10 || $amount > 10000) {
        $_SESSION['error'] = "المبلغ يجب أن يكون بين 10 و 10,000 جنيه";
        header('Location: account.php');
        exit;
    }
    
    $transaction_id = depositToWallet($customer_id, $amount, $payment_method);
    
    if ($transaction_id) {
        $_SESSION['success'] = "تم إنشاء طلب الشحن بنجاح. سيتم تفعيل الرصيد بعد تأكيد الدفع.";
        // هنا يمكنك إضافة التكامل مع بوابة الدفع
    } else {
        $_SESSION['error'] = "حدث خطأ في عملية الشحن";
    }
    
    header('Location: account.php');
    exit;
}

if ($action === 'withdraw') {
    $amount = floatval($_POST['amount']);
    $withdraw_method = $_POST['withdraw_method'];
    $receiver_info = $_POST['receiver_info'];
    
    $wallet = getCustomerWallet($customer_id);
    
    if ($amount < 50) {
        $_SESSION['error'] = "الحد الأدنى للسحب هو 50 جنيهاً";
        header('Location: account.php');
        exit;
    }
    
    if ($amount > $wallet['balance']) {
        $_SESSION['error'] = "المبلغ المطلوب أكبر من الرصيد المتاح";
        header('Location: account.php');
        exit;
    }
    
    // معالجة طلب السحب
    if (processWithdrawal($customer_id, $amount, $withdraw_method, $receiver_info)) {
        $_SESSION['success'] = "تم تقديم طلب السحب بنجاح. سيتم معالجته خلال 24-48 ساعة.";
    } else {
        $_SESSION['error'] = "حدث خطأ في عملية السحب";
    }
    
    header('Location: account.php');
    exit;
}

function processWithdrawal($customer_id, $amount, $method, $receiver_info) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // تحديث رصيد المحفظة
        $stmt = $pdo->prepare("
            UPDATE customer_wallets 
            SET balance = balance - ?, total_withdrawn = total_withdrawn + ?
            WHERE customer_id = ? AND balance >= ?
        ");
        $stmt->execute([$amount, $amount, $customer_id, $amount]);
        
        if ($stmt->rowCount() === 0) {
            throw new Exception("رصيد غير كافي");
        }
        
        // تسجيل المعاملة
        $stmt = $pdo->prepare("
            INSERT INTO wallet_transactions (customer_id, amount, type, description, reference_type, status) 
            VALUES (?, ?, 'withdrawal', ?, 'manual', 'pending')
        ");
        $description = "سحب رصيد عبر " . $method . " - " . $receiver_info;
        $stmt->execute([$customer_id, $amount, $description]);
        
        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        return false;
    }
}