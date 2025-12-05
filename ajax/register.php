<?php
// register.php - معالج روابط الإحالة
session_start();

// جلب معاملات الإحالة
$referral_code = isset($_GET['ref']) ? trim($_GET['ref']) : '';
$product_id = isset($_GET['product']) ? intval($_GET['product']) : 0;

// تخزين بيانات الإحالة في الجلسة
if (!empty($referral_code)) {
    $_SESSION['referral_code'] = $referral_code;
    $_SESSION['referral_product_id'] = $product_id;
    
    // يمكنك إضافة تسجيل النقرة هنا إذا أردت
}

// التوجيه للصفحة المناسبة
if ($product_id > 0) {
    // التوجيه لصفحة المنتج
    header("Location: product.php?id=$product_id");
} else {
    // التوجيه للصفحة الرئيسية
    header("Location: ../index.php");
}
exit;
?>