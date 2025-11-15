<?php
// register.php - Redirect handler for referrals
session_start();

// Get referral parameters
$referral_code = isset($_GET['ref']) ? $_GET['ref'] : '';
$product_id = isset($_GET['product']) ? intval($_GET['product']) : 0;

// Store referral in session
if ($referral_code) {
    $_SESSION['referral_code'] = $referral_code;
    $_SESSION['referral_product_id'] = $product_id;
}

// Redirect to appropriate page
if ($product_id > 0) {
    // Redirect to product page
    header("Location: product.php?id=$product_id");
} else {
    // Redirect to homepage
    header("Location: index.php");
}
exit;
?>