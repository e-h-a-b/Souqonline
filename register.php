<?php
session_start();
require_once 'config.php';
require_once 'functions.php';
require_once 'referral_functions.php';

// معالجة رابط الإحالة
$referral_code = isset($_GET['ref']) ? cleanInput($_GET['ref']) : '';
$product_id = isset($_GET['product']) ? intval($_GET['product']) : 0;

// حفظ بيانات الإحالة في الجلسة
if ($referral_code) {
    $_SESSION['referral_code'] = $referral_code;
    $_SESSION['referral_product_id'] = $product_id;
    
    // تسجيل النقرة
    if (isValidReferralCode($referral_code)) {
        recordReferralClick($referral_code);
    }
}

// إذا كان هناك منتج محدد، توجيه إلى صفحة المنتج
if ($product_id > 0) {
    header("Location: product.php?id=$product_id&ref=$referral_code");
    exit;
}

// إذا لم يكن هناك منتج، توجيه إلى الصفحة الرئيسية
header("Location: index.php?ref=$referral_code");
exit;
?>