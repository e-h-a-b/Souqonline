<?php
session_start();
require_once '../config.php';
require_once '../functions.php';

header('Content-Type: application/json; charset=utf-8');

// التحقق من تسجيل الدخول
if (!isset($_SESSION['customer_id'])) {
    echo json_encode(['success' => false, 'message' => 'يجب تسجيل الدخول']);
    exit;
}

// التحقق من وجود product_id
if (!isset($_POST['product_id']) || empty($_POST['product_id'])) {
    echo json_encode(['success' => false, 'message' => 'معرف المنتج مطلوب']);
    exit;
}

$product_id = intval($_POST['product_id']);
$customer_id = intval($_SESSION['customer_id']);

try {
    // التحقق من اتصال قاعدة البيانات
    if (!$pdo) {
        throw new Exception('لا يمكن الاتصال بقاعدة البيانات');
    }

    // التحقق من وجود المنتج
    $stmt = $pdo->prepare("SELECT id, title FROM products WHERE id = ? AND is_active = 1");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();

    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'المنتج غير موجود']);
        exit;
    }

    // التحقق من وجود رابط إحالة مسبق للعميل
    $stmt = $pdo->prepare("SELECT * FROM referral_links WHERE customer_id = ?");
    $stmt->execute([$customer_id]);
    $existing_link = $stmt->fetch();

    if (!$existing_link) {
        // إنشاء كود إحالة فريد
        $referral_code = generateUniqueReferralCode();
        
        // إنشاء رابط إحالة جديد
        $base_url = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);
        $referral_url = $base_url . "/register.php?ref=" . $referral_code;
        
        $stmt = $pdo->prepare("
            INSERT INTO referral_links 
            (customer_id, referral_code, referral_url, clicks, signups, completed_orders, total_earned_points, is_active) 
            VALUES (?, ?, ?, 0, 0, 0, 0, 1)
        ");
        $stmt->execute([$customer_id, $referral_code, $referral_url]);
        
        $link_id = $pdo->lastInsertId();
    } else {
        $referral_code = $existing_link['referral_code'];
        $referral_url = $existing_link['referral_url'];
        $link_id = $existing_link['id'];
    }

    // إنشاء الرابط الخاص بالمنتج
    $product_referral_url = $referral_url . "&product=" . $product_id;
    
    // تحديث عدد النقرات
    $stmt = $pdo->prepare("UPDATE referral_links SET clicks = clicks + 1 WHERE id = ?");
    $stmt->execute([$link_id]);

    // الرد بنجاح
    echo json_encode([
        'success' => true,
        'referral_link' => $product_referral_url,
        'message' => 'تم إنشاء رابط الإحالة بنجاح'
    ]);

} catch (PDOException $e) {
    error_log("Referral Link PDO Error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Referral Link Error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'حدث خطأ: ' . $e->getMessage()
    ]);
}

/**
 * توليد كود إحالة فريد
 */
function generateUniqueReferralCode($length = 8) {
    global $pdo;
    
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $max_attempts = 10;
    $attempt = 0;
    
    do {
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= $characters[rand(0, strlen($characters) - 1)];
        }
        
        // التحقق من التكرار
        $stmt = $pdo->prepare("SELECT id FROM referral_links WHERE referral_code = ?");
        $stmt->execute([$code]);
        $exists = $stmt->fetch();
        
        $attempt++;
        
    } while ($exists && $attempt < $max_attempts);
    
    // إذا فشلنا في إنشاء كود فريد، نضيف timestamp
    if ($exists) {
        $code = $code . substr(time(), -4);
    }
    
    return $code;
}
?>