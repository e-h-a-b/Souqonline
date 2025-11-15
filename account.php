<?php
/**
 * صفحة حساب المستخدم
 */
session_start();
require_once 'functions.php';
require_once 'referral_functions.php';

// === التحقق من وضع الصيانة ===
if (getSetting('maintenance_mode', '0') == '1' && !isset($_SESSION['admin_id'])) {
    header('Location: maintenance.php');
    exit;
}

// === معالجة تسجيل الخروج ===
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: account.php');
    exit;
}

// === إعدادات المتجر ===
$storeDescription = getSetting('store_description', '');
$storeName = getSetting('store_name', 'متجر إلكتروني');

// === المتغيرات الأساسية (تُعرّف قبل أي استخدام) ===
$message = '';
$error = '';
$isLoggedIn = isset($_SESSION['customer_id']);
$customer = null;
$customerId = null;
$customerEmail = '';
$wallet_balance = 0.00;
$total_deposited = 0.00;
$total_withdrawn = 0.00;
$recent_transactions = [];
$orders = [];
$totalOrders = 0;
$totalSpent = 0.0;
$referral_stats = [];
$referral_history = [];
$ibrahim_products = [];
$ibrahim_stats = ['total_products' => 0, 'active_products' => 0, 'total_orders' => 0, 'total_pages' => 0];

// === جلب بيانات العميل إذا كان مسجلاً دخوله ===
if ($isLoggedIn) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->execute([$_SESSION['customer_id']]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$customer) {
        session_destroy();
        header('Location: account.php');
        exit;
    }

    // تعيين القيم الآمنة
    $customerId = $_SESSION['customer_id'];
    $customerEmail = $customer['email'] ?? '';

    // === إحصائيات الإحالة ===
    $referral_stats = getReferralStats($customerId);
    $referral_history = getReferralHistory($customerId, 5);

    // === جلب الطلبات + ربط الطلبات القديمة ===
    $stmt = $pdo->prepare("
        SELECT o.*, 
               COUNT(oi.id) as items_count,
               SUM(oi.qty) as total_items,
               GROUP_CONCAT(oi.product_title SEPARATOR ', ') as product_names
        FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id
        WHERE o.customer_email = ? OR o.customer_id = ?
        GROUP BY o.id
        ORDER BY o.created_at DESC
        LIMIT 20
    ");
    $stmt->execute([$customerEmail, $customerId]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ربط الطلبات القديمة (مرة واحدة فقط)
    $updatedCount = 0;
    if (!isset($_SESSION['orders_linked'])) {
        foreach ($orders as $order) {
            if (empty($order['customer_id']) && $order['customer_email'] === $customerEmail) {
                $updateStmt = $pdo->prepare("UPDATE orders SET customer_id = ? WHERE id = ?");
                $updateStmt->execute([$customerId, $order['id']]);
                $updatedCount++;
            }
        }
        if ($updatedCount > 0) {
            $message = "تم ربط {$updatedCount} طلب سابق بحسابك بنجاح!";
            $_SESSION['orders_linked'] = true;
        }
    }

    // === إحصائيات الطلبات ===
    $totalOrders = count($orders);
    $totalSpent = array_sum(array_column($orders, 'total'));

    // === جلب بيانات المحفظة (آمن من null) ===
    $stmt = $pdo->prepare("
        SELECT balance, total_deposited, total_withdrawn 
        FROM customer_wallets 
        WHERE customer_id = ?
    ");
    $stmt->execute([$customerId]);
    $wallet = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($wallet) {
        $wallet_balance   = (float)($wallet['balance'] ?? 0);
        $total_deposited  = (float)($wallet['total_deposited'] ?? 0);
        $total_withdrawn  = (float)($wallet['total_withdrawn'] ?? 0);
    }

    // === جلب آخر 5 معاملات ===
    $stmt = $pdo->prepare("
        SELECT *, 
               CASE 
                   WHEN type = 'deposit' THEN 'إيداع'
                   WHEN type = 'withdrawal' THEN 'سحب'
                   WHEN type = 'refund' THEN 'استرداد'
                   ELSE 'مكافأة'
               END as type_ar
        FROM wallet_transactions 
        WHERE customer_id = ? 
        ORDER BY transaction_date DESC 
        LIMIT 5
    ");
    $stmt->execute([$customerId]);
    $recent_transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
	$page = max(1, intval($_GET['page'] ?? 1));
    $limit = 12;
    $offset = ($page - 1) * $limit;

    // جلب منتجات العميل
    $stmt = $pdo->prepare("
        SELECT * FROM products 
        WHERE created_by = ? 
        ORDER BY created_at DESC 
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$customerId, $limit, $offset]);
    $customer_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // إحصائيات منتجات العميل
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_products,
            SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_products,
            SUM(orders_count) as total_orders
        FROM products 
        WHERE created_by = ?
    ");
    $stmt->execute([$customerId]);
    $customer_products_stats = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['total_products' => 0, 'active_products' => 0, 'total_orders' => 0];
    $customer_products_stats['total_pages'] = ceil(($customer_products_stats['total_products'] ?? 0) / $limit);
}

// === معالجة تسجيل الدخول ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = cleanInput($_POST['email']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM customers WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && verifyPassword($password, $user['password'])) {
        $_SESSION['customer_id'] = $user['id'];
        $_SESSION['customer_name'] = $user['first_name'];
        header('Location: account.php');
        exit;
    } else {
        $error = 'البريد الإلكتروني أو كلمة المرور غير صحيحة';
    }
}

// === معالجة التسجيل ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $firstName = cleanInput($_POST['first_name']);
    $lastName = cleanInput($_POST['last_name']);
    $email = cleanInput($_POST['reg_email']);
    $phone = cleanInput($_POST['phone']);
    $password = $_POST['reg_password'];
    $confirmPassword = $_POST['confirm_password'];

    if ($password !== $confirmPassword) {
        $error = 'كلمات المرور غير متطابقة';
    } elseif (strlen($password) < 6) {
        $error = 'كلمة المرور يجب أن تكون 6 أحرف على الأقل';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM customers WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'البريد الإلكتروني مسجل مسبقاً';
        } else {
            try {
                $hashedPassword = hashPassword($password);
                $stmt = $pdo->prepare("
                    INSERT INTO customers (first_name, last_name, email, phone, password) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$firstName, $lastName, $email, $phone, $hashedPassword]);

                $newCustomerId = $pdo->lastInsertId();
                $_SESSION['customer_id'] = $newCustomerId;
                $_SESSION['customer_name'] = $firstName;

                // معالجة الإحالة
                if (isset($_GET['ref'])) {
                    $referral_code = cleanInput($_GET['ref']);
                    processReferralSignup($newCustomerId, $referral_code);
                }

                header('Location: account.php');
                exit;
            } catch (Exception $e) {
                $error = 'حدث خطأ أثناء التسجيل';
            }
        }
    }
}

// === تحديث الملف الشخصي ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile']) && $isLoggedIn) {
    $firstName = cleanInput($_POST['first_name']);
    $lastName = cleanInput($_POST['last_name']);
    $phone = cleanInput($_POST['phone']);

    try {
        $stmt = $pdo->prepare("
            UPDATE customers 
            SET first_name = ?, last_name = ?, phone = ? 
            WHERE id = ?
        ");
        $stmt->execute([$firstName, $lastName, $phone, $customerId]);
        $message = 'تم تحديث البيانات بنجاح';

        $customer['first_name'] = $firstName;
        $customer['last_name'] = $lastName;
        $customer['phone'] = $phone;
    } catch (Exception $e) {
        $error = 'حدث خطأ أثناء التحديث';
    }
}

// === تغيير كلمة المرور ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password']) && $isLoggedIn) {
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmNewPassword = $_POST['confirm_new_password'];

    if (!verifyPassword($currentPassword, $customer['password'])) {
        $error = 'كلمة المرور الحالية غير صحيحة';
    } elseif ($newPassword !== $confirmNewPassword) {
        $error = 'كلمات المرور الجديدة غير متطابقة';
    } elseif (strlen($newPassword) < 6) {
        $error = 'كلمة المرور يجب أن تكون 6 أحرف على الأقل';
    } else {
        try {
            $hashedPassword = hashPassword($newPassword);
            $stmt = $pdo->prepare("UPDATE customers SET password = ? WHERE id = ?");
            $stmt->execute([$hashedPassword, $customerId]);
            $message = 'تم تغيير كلمة المرور بنجاح';
        } catch (Exception $e) {
            $error = 'حدث خطأ أثناء تغيير كلمة المرور';
        }
    }
}

// === جلب منتجات متجر إبراهيم ===
if ($isLoggedIn) {
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = 12;
    $offset = ($page - 1) * $limit;

    $stmt = $pdo->prepare("
        SELECT * FROM products 
        WHERE created_by = ? AND store_type = 'ibrahim'
        ORDER BY created_at DESC 
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$customerId, $limit, $offset]);
    $ibrahim_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_products,
            SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_products,
            SUM(orders_count) as total_orders
        FROM products 
        WHERE created_by = ? AND store_type = 'ibrahim'
    ");
    $stmt->execute([$customerId]);
    $ibrahim_stats = $stmt->fetch(PDO::FETCH_ASSOC) ?: $ibrahim_stats;
    $ibrahim_stats['total_pages'] = ceil(($ibrahim_stats['total_products'] ?? 0) / $limit);
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>حسابي - <?= htmlspecialchars($storeName) ?></title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
 	<!-- مكتبة jsQR للمسح الضوئي -->
<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.js"></script>

<!-- مكتبة توليد QR Code -->
<script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
	<script src="assets/js/scripts.js" defer></script>
    <style>
        .account-page { padding: 3rem 0; min-height: 70vh; }
        .account-tabs { display: flex; gap: 1rem; margin-bottom: 2rem; border-bottom: 2px solid #e9ecef; flex-wrap: wrap; }
        .account-tabs button { padding: 1rem 2rem; background: none; border: none; border-bottom: 3px solid transparent; cursor: pointer; font-weight: 600; transition: 0.3s; }
        .account-tabs button:hover { color: var(--primary-color); }
        .account-tabs button.active { color: var(--primary-color); border-bottom-color: var(--primary-color); }
        .tab-content { display: none; background: white; padding: 2rem; border-radius: 8px; }
        .tab-content.active { display: block; animation: fadeIn 0.3s; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .login-register-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 3rem; }
        .auth-card { background: white; padding: 2rem; border-radius: 8px; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 600; }
        .form-group input { width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 8px; }
        .orders-table { width: 100%; border-collapse: collapse; }
        .orders-table th, .orders-table td { padding: 1rem; text-align: right; border-bottom: 1px solid #e9ecef; }
        .orders-table th { background: #f8f9fa; font-weight: 600; }
        .status-badge { padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.875rem; font-weight: 600; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-confirmed { background: #cfe2ff; color: #084298; }
        .status-shipped { background: #d1e7dd; color: #0f5132; }
        .status-delivered { background: #198754; color: white; }
        .profile-header { display: flex; align-items: center; gap: 2rem; margin-bottom: 2rem; padding-bottom: 2rem; border-bottom: 2px solid #e9ecef; }
        .profile-avatar { width: 80px; height: 80px; background: var(--primary-color); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2rem; font-weight: 700; }
        @media (max-width: 768px) { 
            .login-register-grid { grid-template-columns: 1fr; }
            .account-tabs { overflow-x: auto; }
        }
		.status-pending { background: #fff3cd; color: #856404; }
.status-confirmed { background: #cfe2ff; color: #084298; }
.status-processing { background: #d1e7dd; color: #0f5132; }
.status-shipped { background: #d1e7dd; color: #0f5132; }
.status-delivered { background: #198754; color: white; }
.status-cancelled { background: #f8d7da; color: #721c24; }
.status-returned { background: #e2e3e5; color: #383d41; }

.btn-outline-primary {
    background: transparent;
    border: 2px solid #007bff;
    color: #007bff;
    padding: 10px 20px;
    border-radius: 8px;
    text-decoration: none;
    transition: all 0.3s;
}

.btn-outline-primary:hover {
    background: #007bff;
    color: white;
}
    </style>
<style>
/* أيقونة QR Code */
.qr-discount-btn {
    position: absolute;
    top: 150px;
    right: 10px;
    background: rgba(34, 197, 94, 0.9);
    border: none;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
    transition: all 0.3s ease;
    z-index: 10;
    box-shadow: 0 4px 12px rgba(34, 197, 94, 0.3);
}

.qr-discount-btn:hover {
    background: #22c55e;
    transform: scale(1.1);
    box-shadow: 0 6px 20px rgba(34, 197, 94, 0.4);
}

 

#qrScannerModal { 
    display:none;
    position: fixed;
    top: 0%;
    right: 30%;
    width: 50%;
    height: 100%;
    overflow: auto;
    background: #1f2937;
    color: white;
    padding: 0.75rem 1rem;
    border-radius: 8px;
    font-size: 0.8rem;
    transition: all 0.3s ease;
    z-index: 1000;
    align-items: center;
    justify-content: center;
}

 

/* نافذة QR Code */
.qr-modal .modal-content {
    max-width: 500px;
    text-align: center;
}

.qr-content {
    padding: 2rem;
}

.qr-code-image {
    margin: 1rem 0;
    padding: 1rem;
    background: white;
    border-radius: 10px;
    border: 2px dashed #e5e7eb;
}

.qr-instructions {
    background: #f8fafc;
    padding: 1rem;
    border-radius: 8px;
    margin: 1rem 0;
    text-align: right;
}

.qr-instructions h4 {
    color: #374151;
    margin-bottom: 0.5rem;
}

.qr-instructions ol {
    text-align: right;
    padding-right: 1.5rem;
}

.qr-details {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin: 1rem 0;
}

.qr-detail-item {
    background: white;
    padding: 1rem;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
}

.detail-label {
    font-weight: 600;
    color: #6b7280;
    font-size: 0.875rem;
}

.detail-value {
    font-weight: 700;
    color: #1f2937;
    font-size: 1rem;
}

/* نافذة الماسح الضوئي */
.qr-scanner-modal .modal-content {
    max-width: 600px;
}

.scanner-instructions {
    background: #f0f9ff;
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1rem;
    border-right: 4px solid #3b82f6;
}

.scanner-instructions h4 {
    color: #1e40af;
    margin-bottom: 0.5rem;
}

.scanner-instructions ol {
    text-align: right;
    padding-right: 1.5rem;
}

.scanner-area {
    margin: 1rem 0;
    position: relative;
}

.scanner-result {
    margin-top: 1rem;
    padding: 1rem;
    border-radius: 8px;
    text-align: center;
    font-weight: 600;
}

.scanner-result.valid {
    background: #dcfce7;
    color: #166534;
    border: 1px solid #bbf7d0;
}

.scanner-result.invalid {
    background: #fef2f2;
    color: #991b1b;
    border: 1px solid #fecaca;
}

.manual-input {
    margin-top: 1rem;
    padding: 1rem;
    background: #f8fafc;
    border-radius: 8px;
}

.manual-input label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: #374151;
}

.manual-input input {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    margin-bottom: 0.5rem;
}

/* تصميم متجاوب */
@media (max-width: 768px) {
    .qr-details {
        grid-template-columns: 1fr;
    }
    
    .qr-discount-btn {
        top: 5px;
        left: 5px;
        width: 35px;
        height: 35px;
        font-size: 1rem;
    }
     
}
/* الإشعارات */
.notification {
    position: fixed;
    top: 20px;
    right: 20px;
    background: white;
    border-radius: 8px;
    padding: 1rem 1.5rem;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    border-left: 4px solid #3b82f6;
    transform: translateX(400px);
    transition: transform 0.3s ease;
    z-index: 10000;
    max-width: 350px;
}

.notification.show {
    transform: translateX(0);
}

.notification-success {
    border-left-color: #10b981;
}

.notification-error {
    border-left-color: #ef4444;
}

.notification-content {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.notification-content i {
    font-size: 1.25rem;
}

.notification-success .notification-content i {
    color: #10b981;
}

.notification-error .notification-content i {
    color: #ef4444;
}

.notification-content span {
    color: #374151;
    font-weight: 500;
}

/* تحسين النافذة */
.qr-header {
    text-align: center;
    margin-bottom: 1.5rem;
}

.qr-header i {
    font-size: 2rem;
    color: #3b82f6;
    margin-bottom: 0.5rem;
}

.qr-header h4 {
    margin: 0;
    color: #1f2937;
    font-size: 1.25rem;
}

/* تصميم متجاوب */
@media (max-width: 768px) {
    .qr-details {
        grid-template-columns: 1fr !important;
    }
    
    .qr-actions {
        flex-direction: column;
    }
    
    .notification {
        right: 10px;
        left: 10px;
        max-width: none;
    }
}
/* تعليمات الماسح النشط */
.scanner-instructions-active {
    text-align: center;
    padding: 2rem;
    color: #6b7280;
}

.scanner-instructions-active i {
    font-size: 3rem;
    color: #3b82f6;
    margin-bottom: 1rem;
}

.scanner-instructions-active h4 {
    color: #374151;
    margin-bottom: 0.5rem;
}

.loading-spinner {
    width: 40px;
    height: 40px;
    border: 4px solid #f3f4f6;
    border-left: 4px solid #3b82f6;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 1rem auto;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* دعم المتصفح */
.browser-support {
    background: #fffbeb;
    border: 1px solid #fef3c7;
    border-radius: 6px;
    padding: 0.75rem;
    margin-top: 1rem;
}

.browser-support p {
    margin: 0;
    color: #92400e;
    font-size: 0.875rem;
}

/* تحسين عرض النتائج */
.scanner-result {
    margin-top: 1rem;
    padding: 1.5rem;
    border-radius: 8px;
    text-align: center;
}

.scanner-result.valid {
    background: #f0fdf4;
    color: #166534;
    border: 1px solid #bbf7d0;
}

.scanner-result.invalid {
    background: #fef2f2;
    color: #991b1b;
    border: 1px solid #fecaca;
}

.scanner-result h4 {
    margin: 0 0 1rem 0;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

/* تصميم متجاوب */
@media (max-width: 768px) {
    .scanner-area {
        margin: 1rem 0;
    }
    
    #qrScanner {
        height: 250px;
    }
    
    .scanner-instructions-active {
        padding: 1rem;
    }
}
</style>

</head>
<body>
    <header class="site-header">
        <div class="container">
            <div class="header-content">
                <div class="logo"><a href="index.php"><h1><?= htmlspecialchars($storeName) ?></h1></a></div>
                <div class="header-actions">
				 <!-- زر المفضلة --> 
                <a href="wishlist.php" class="wishlist-btn">
                    <i class="fas fa-heart"></i>
                    <span class="wishlist-count" id="wishlist-count">
                        <?= getWishlistCount() ?>
                    </span>
                </a>  
                    <a href="cart.php" class="cart-btn">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="cart-count"><?= getCartCount() ?></span>
                    </a>
                </div>
            </div>
        </div>
    </header> 

    <main class="account-page">
        <div class="container">
            <?php if (!$isLoggedIn): ?>
                <h1 class="text-center">تسجيل الدخول أو إنشاء حساب</h1>
                
                <?php if ($error): ?>
                    <div style="background: #f8d7da; color: #721c24; padding: 1rem; border-radius: 8px; margin: 1rem 0; max-width: 600px; margin-left: auto; margin-right: auto;">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <div class="login-register-grid" style="margin-top: 2rem;">
                    <!-- تسجيل الدخول -->
                    <div class="auth-card">
                        <h2><i class="fas fa-sign-in-alt"></i> تسجيل الدخول</h2>
                        <p style="color: #6c757d; margin-bottom: 2rem;">إذا كان لديك حساب مسبقاً</p>
                        
                        <form method="post">
                            <div class="form-group">
                                <label>البريد الإلكتروني</label>
                                <input type="email" name="email" required>
                            </div>
                            <div class="form-group">
                                <label>كلمة المرور</label>
                                <input type="password" name="password" required>
                            </div>
                            <button type="submit" name="login" class="btn btn-primary" style="width: 100%;">
                                تسجيل الدخول
                            </button>
                            <a href="#" style="display: block; text-align: center; margin-top: 1rem; color: var(--primary-color);">
                                نسيت كلمة المرور؟
                            </a>
                        </form>
                    </div>

                    <!-- إنشاء حساب -->
                    <div class="auth-card">
                        <h2><i class="fas fa-user-plus"></i> إنشاء حساب جديد</h2>
                        <p style="color: #6c757d; margin-bottom: 2rem;">انضم إلينا الآن</p>
                        
                        <form method="post">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                <div class="form-group">
                                    <label>الاسم الأول</label>
                                    <input type="text" name="first_name" required>
                                </div>
                                <div class="form-group">
                                    <label>اسم العائلة</label>
                                    <input type="text" name="last_name" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>البريد الإلكتروني</label>
                                <input type="email" name="reg_email" required>
                            </div>
                            <div class="form-group">
                                <label>رقم الهاتف</label>
                                <input type="tel" name="phone" pattern="01[0-2,5]{1}[0-9]{8}" placeholder="01XXXXXXXXX" required>
                            </div>
                            <div class="form-group">
                                <label>كلمة المرور</label>
                                <input type="password" name="reg_password" minlength="6" required>
                            </div>
                            <div class="form-group">
                                <label>تأكيد كلمة المرور</label>
                                <input type="password" name="confirm_password" minlength="6" required>
                            </div>
                            <button type="submit" name="register" class="btn btn-success" style="width: 100%;">
                                إنشاء حساب
                            </button>
                        </form>
                    </div>
                </div>

            <?php else: ?>
                <div class="profile-header">
                    <div class="profile-avatar">
                        <?= strtoupper(substr($customer['first_name'], 0, 1)) ?>
                    </div>
                    <div>
                        <h1>مرحباً، <?= htmlspecialchars($customer['first_name']) ?>!</h1>
                        <p style="color: #6c757d;">عضو منذ <?= date('Y-m-d', strtotime($customer['created_at'])) ?></p>
                    </div>
                    <div style="margin-right: auto;">
                        <a href="?logout=1" class="btn btn-secondary" onclick="return confirm('هل تريد تسجيل الخروج؟')">
                            <i class="fas fa-sign-out-alt"></i> تسجيل الخروج
                        </a>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div style="background: #d1e7dd; color: #0f5132; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div style="background: #f8d7da; color: #721c24; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

<div class="account-tabs">
    <button class="tab-btn active" onclick="openTab('orders')">
        <i class="fas fa-shopping-bag"></i> طلباتي
    </button>
    <button class="tab-btn" onclick="openTab('wallet')">
        <i class="fas fa-wallet"></i> محفظتي
    </button>
    <button class="tab-btn" onclick="openTab('points')">
        <i class="fas fa-coins"></i> نقاطي
    </button>
    <button class="tab-btn" onclick="openTab('referrals')">
        <i class="fas fa-share-alt"></i> الإحالات والنقاط
    </button>
    <!-- تبويب منتجات متجر إبراهيم الجديد -->
    <button class="tab-btn" onclick="openTab('ibrahim_products')">
        <i class="fas fa-store"></i> سلع متجرك
    </button>
    <button class="tab-btn" onclick="openTab('profile')">
        <i class="fas fa-user"></i> الملف الشخصي
    </button>
    <button class="tab-btn" onclick="openTab('security')">
        <i class="fas fa-lock"></i> الأمان
    </button>
</div>

<!-- الطلبات -->
<div id="orders" class="tab-content active">
    <div class="orders-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; padding: 1.5rem; background: #f8f9fa; border-radius: 8px;">
        <div>
            <h2 style="margin: 0 0 0.5rem 0;">طلباتي</h2>
            <p style="margin: 0; color: #6c757d;">
                إجمالي الطلبات: <strong><?= $totalOrders ?></strong> | 
                إجمالي المشتريات: <strong><?= formatPrice($totalSpent) ?></strong>
            </p>
        </div>
        <?php if ($totalOrders > 0): ?>
            <a href="orders-export.php" class="btn btn-secondary">
                <i class="fas fa-download"></i> تصدير الطلبات
            </a>
        <?php endif; ?>
    </div>
    
    <?php if (empty($orders)): ?>
        <div style="text-align: center; padding: 3rem; background: white; border-radius: 8px;">
            <i class="fas fa-shopping-bag" style="font-size: 4rem; color: #dee2e6; margin-bottom: 1rem;"></i>
            <h3 style="color: #6c757d; margin-bottom: 1rem;">لا توجد طلبات حتى الآن</h3>
            <p style="color: #6c757d; margin-bottom: 2rem;">لم تقم بأي طلبات سابقة، ابدأ بالتسوق الآن!</p>
            <a href="index.php" class="btn btn-primary" style="padding: 12px 30px;">
                <i class="fas fa-shopping-cart"></i> ابدأ التسوق
            </a>
        </div>
    <?php else: ?>
        <div class="orders-list">
            <?php foreach ($orders as $order): ?>
                <div class="order-card" style="background: white; border-radius: 8px; padding: 1.5rem; margin-bottom: 1rem; border: 1px solid #e9ecef;">
                    <div class="order-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; padding-bottom: 1rem; border-bottom: 1px solid #e9ecef;">
                        <div>
                            <h4 style="margin: 0 0 0.5rem 0;">
                                <i class="fas fa-receipt"></i>
                                طلب رقم: <?= htmlspecialchars($order['order_number']) ?>
                            </h4>
                            <p style="margin: 0; color: #6c757d; font-size: 0.9rem;">
                                <i class="fas fa-calendar"></i>
                                <?= date('Y-m-d H:i', strtotime($order['created_at'])) ?>
                            </p>
                        </div>
                        <div style="text-align: left;">
                            <span class="status-badge status-<?= $order['status'] ?>" style="padding: 0.5rem 1rem; border-radius: 20px; font-weight: 600;">
                                <?php
                                $statuses = [
                                    'pending' => 'قيد المراجعة',
                                    'confirmed' => 'مؤكد',
                                    'processing' => 'قيد التجهيز',
                                    'shipped' => 'تم الشحن',
                                    'delivered' => 'تم التوصيل',
                                    'cancelled' => 'ملغي',
                                    'returned' => 'مرتجع'
                                ];
                                echo $statuses[$order['status']] ?? $order['status'];
                                ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="order-details" style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                        <div>
                            <strong>المنتجات:</strong>
                            <div style="color: #6c757d; font-size: 0.9rem;">
                                <?= $order['items_count'] ?> منتج (<?= $order['total_items'] ?> قطعة)
                            </div>
                        </div>
                        <div>
                            <strong>طريقة الدفع:</strong>
                            <div style="color: #6c757d; font-size: 0.9rem;">
                                <?php
                                $paymentMethods = [
                                    'cod' => 'الدفع عند الاستلام',
                                    'visa' => 'بطاقة ائتمان',
                                    'instapay' => 'انستاباي',
                                    'vodafone_cash' => 'فودافون كاش',
                                    'fawry' => 'فوري'
                                ];
                                echo $paymentMethods[$order['payment_method']] ?? $order['payment_method'];
                                ?>
                            </div>
                        </div>
                        <div>
                            <strong>المجموع:</strong>
                            <div style="color: #2ecc71; font-weight: 600; font-size: 1.1rem;">
                                <?= formatPrice($order['total']) ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="order-actions" style="display: flex; gap: 0.5rem; justify-content: flex-end;">
                        <a href="track-order.php?order=<?= $order['order_number'] ?>" class="btn btn-primary" style="padding: 0.5rem 1rem; font-size: 0.875rem;">
                            <i class="fas fa-eye"></i> تفاصيل الطلب
                        </a>
                        <?php if ($order['status'] === 'pending'): ?>
                            <a href="cancel-order.php?order=<?= $order['id'] ?>" class="btn btn-danger" style="padding: 0.5rem 1rem; font-size: 0.875rem;" 
                               onclick="return confirm('هل أنت متأكد من إلغاء الطلب؟')">
                                <i class="fas fa-times"></i> إلغاء الطلب
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <?php if (count($orders) >= 20): ?>
            <div style="text-align: center; margin-top: 2rem;">
                <a href="orders.php" class="btn btn-outline-primary">
                    <i class="fas fa-list"></i> عرض جميع الطلبات
                </a>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- المحفظة -->
<div id="wallet" class="tab-content">
    <div class="wallet-header" style="background: linear-gradient(135deg, #10b981, #34d399); color: white; padding: 2rem; border-radius: 12px; margin-bottom: 2rem;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h2 style="margin: 0 0 0.5rem 0; color: white;">محفظتك المالية</h2>
                <p style="margin: 0; opacity: 0.9;">رصيدك المتاح للاستخدام في المشتريات</p>
            </div>
            <div style="text-align: center;">
                <div style="font-size: 3rem; font-weight: 700; line-height: 1;">
                    <?php 
                    $wallet_data = getCustomerWallet($_SESSION['customer_id']);
                    echo number_format($wallet_data['balance'], 2);
                    ?>
                </div>
                <p style="margin: 0; opacity: 0.9;">ج.م</p>
            </div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">
        <div class="stat-card" style="background: white; padding: 1.5rem; border-radius: 8px; text-align: center;">
            <div style="font-size: 2rem; color: #10b981; font-weight: 700;">
                <?= number_format($wallet_data['total_deposited'], 2) ?> ج.م
            </div>
            <p style="color: #6b7280; margin: 0;">إجمالي الإيداعات</p>
        </div>
        <div class="stat-card" style="background: white; padding: 1.5rem; border-radius: 8px; text-align: center;">
            <div style="font-size: 2rem; color: #ef4444; font-weight: 700;">
                <?= number_format($wallet_data['total_withdrawn'], 2) ?> ج.م
            </div>
            <p style="color: #6b7280; margin: 0;">إجمالي السحوبات</p>
        </div>
    </div>

    <!-- أزرار الإجراءات -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 2rem;">
        <button onclick="showDepositModal()" class="btn btn-primary" style="padding: 1rem; font-size: 1.1rem;">
            <i class="fas fa-plus-circle"></i> شحن المحفظة
        </button>
        <button onclick="showWithdrawModal()" class="btn btn-outline-primary" style="padding: 1rem; font-size: 1.1rem;">
            <i class="fas fa-download"></i> سحب رصيد
        </button>
    </div>

    <!-- كيفية استخدام المحفظة -->
    <div class="card" style="background: white; padding: 2rem; border-radius: 8px; margin-bottom: 2rem;">
        <h3 style="margin-bottom: 1.5rem;">كيفية استخدام المحفظة؟</h3>
        <div style="display: grid; gap: 1rem;">
            <div style="display: flex; align-items: center; gap: 1rem; padding: 1rem; background: #f8fafc; border-radius: 8px;">
                <div style="width: 40px; height: 40px; background: #3b82f6; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-plus"></i>
                </div>
                <div>
                    <strong>شحن المحفظة</strong>
                    <p style="margin: 0.25rem 0 0 0; color: #6b7280;">
                        قم بشحن محفظتك باستخدام أي من طرق الدفع المتاحة
                    </p>
                </div>
            </div>
            <div style="display: flex; align-items: center; gap: 1rem; padding: 1rem; background: #f8fafc; border-radius: 8px;">
                <div style="width: 40px; height: 40px; background: #10b981; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div>
                    <strong>الدفع من المحفظة</strong>
                    <p style="margin: 0.25rem 0 0 0; color: #6b7280;">
                        اختر الدفع من المحفظة في خطوة الدفع لأي طلب
                    </p>
                </div>
            </div>
            <div style="display: flex; align-items: center; gap: 1rem; padding: 1rem; background: #f8fafc; border-radius: 8px;">
                <div style="width: 40px; height: 40px; background: #f59e0b; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-download"></i>
                </div>
                <div>
                    <strong>سحب الرصيد</strong>
                    <p style="margin: 0.25rem 0 0 0; color: #6b7280;">
                        يمكنك سحب رصيدك إلى حسابك البنكي (بحد أدنى 50 جنيهاً)
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- سجل المعاملات -->
    <div class="card" style="background: white; padding: 2rem; border-radius: 8px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h3 style="margin: 0;">سجل المعاملات</h3>
            <a href="wallet-history.php" class="btn btn-outline-primary">عرض الكل</a>
        </div>
        
        <?php
        $transactions = getWalletTransactions($_SESSION['customer_id'], 5);
        if (empty($transactions)):
        ?>
            <p style="text-align: center; color: #6b7280; padding: 2rem;">لا توجد معاملات حتى الآن</p>
        <?php else: ?>
            <div class="transactions-list">
                <?php foreach ($transactions as $transaction): ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 1rem; border-bottom: 1px solid #e5e7eb;">
                        <div>
                            <strong><?= htmlspecialchars($transaction['description']) ?></strong>
                            <p style="margin: 0.25rem 0 0 0; color: #6b7280; font-size: 0.875rem;">
                                <?= date('Y-m-d H:i', strtotime($transaction['transaction_date'])) ?>
                                <?php if ($transaction['status'] !== 'completed'): ?>
                                    <span class="badge badge-<?= $transaction['status'] === 'pending' ? 'warning' : 'danger' ?>" style="margin-right: 0.5rem;">
                                        <?= $transaction['status'] === 'pending' ? 'قيد المعالجة' : 'فاشل' ?>
                                    </span>
                                <?php endif; ?>
                            </p>
                        </div>
                        <div style="text-align: left;">
                            <span style="font-weight: 700; color: 
                                <?= $transaction['type'] === 'deposit' || $transaction['type'] === 'refund' || $transaction['type'] === 'bonus' ? '#10b981' : '#ef4444' ?>;">
                                <?= $transaction['type'] === 'deposit' || $transaction['type'] === 'refund' || $transaction['type'] === 'bonus' ? '+' : '-' ?>
                                <?= number_format($transaction['amount'], 2) ?> ج.م
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>


<!-- قسم منتجاتي -->
<div id="ibrahim_products" class="tab-content">
<!-- في واجهة التاجر -->
<button class="btn btn-scanner" onclick="openQRScannerModal()" style="display: flex; align-items: center; gap: 0.5rem; background: linear-gradient(135deg, #8b5cf6, #a855f7); color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 8px; font-weight: 600;">
    <i class="fas fa-camera"></i>
    مسح كود QR
</button>
<div class="card" style="background: white; padding: 2rem; border-radius: 8px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <h3 style="margin: 0;">منتجاتي</h3>
        <div style="display: flex; gap: 1rem;">
            <button onclick="showAddProductModal()" class="btn btn-primary">
                <i class="fas fa-plus"></i> إضافة منتج جديد
            </button>
        </div>
    </div>

    <!-- إحصائيات سريعة -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
        <div style="background: #f0f9ff; padding: 1rem; border-radius: 8px; text-align: center; border-right: 4px solid #3b82f6;">
            <div style="font-size: 2rem; font-weight: bold; color: #3b82f6;"><?= $customer_products_stats['total_products'] ?></div>
            <div style="color: #6b7280;">إجمالي المنتجات</div>
        </div>
        <div style="background: #f0fdf4; padding: 1rem; border-radius: 8px; text-align: center; border-right: 4px solid #10b981;">
            <div style="font-size: 2rem; font-weight: bold; color: #10b981;"><?= $customer_products_stats['active_products'] ?></div>
            <div style="color: #6b7280;">المنتجات النشطة</div>
        </div>
        <div style="background: #fef3f2; padding: 1rem; border-radius: 8px; text-align: center; border-right: 4px solid #ef4444;">
            <div style="font-size: 2rem; font-weight: bold; color: #ef4444;"><?= $customer_products_stats['total_products'] - $customer_products_stats['active_products'] ?></div>
            <div style="color: #6b7280;">المنتجات غير النشطة</div>
        </div>
        <div style="background: #faf5ff; padding: 1rem; border-radius: 8px; text-align: center; border-right: 4px solid #8b5cf6;">
            <div style="font-size: 2rem; font-weight: bold; color: #8b5cf6;"><?= $customer_products_stats['total_orders'] ?></div>
            <div style="color: #6b7280;">إجمالي المبيعات</div>
        </div>
    </div>

    <?php if (empty($customer_products)): ?>
        <div style="text-align: center; padding: 3rem; color: #6b7280;">
            <i class="fas fa-box-open" style="font-size: 4rem; margin-bottom: 1rem; opacity: 0.5;"></i>
            <h3 style="color: #6b7280; margin-bottom: 1rem;">لا توجد منتجات حتى الآن</h3>
            <p style="color: #6b7280; margin-bottom: 2rem;">ابدأ بإضافة أول منتج لك</p>
            <button onclick="showAddProductModal()" class="btn btn-primary">
                <i class="fas fa-plus"></i> إضافة أول منتج
            </button>
        </div>
    <?php else: ?>
        <div class="products-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem;">
            <?php foreach ($customer_products as $product): ?>
                <div class="product-card" style="
                    background: white;
                    border: 1px solid #e5e7eb;
                    border-radius: 12px;
                    padding: 1.5rem;
                    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
                    position: relative;
                    transition: all 0.3s ease;
                ">
                    <!-- حالة المنتج -->
                    <div style="
                        position: absolute;
                        top: 10px;
                        right: 10px;
                        background: <?= $product['is_active'] ? '#10b981' : '#6b7280' ?>;
                        color: white;
                        padding: 0.25rem 0.5rem;
                        border-radius: 15px;
                        font-size: 0.75rem;
                    ">
                        <?= $product['is_active'] ? 'نشط' : 'غير نشط' ?>
                    </div>

                    <!-- صورة المنتج -->
                    <div style="text-align: center; margin-bottom: 1rem;">
                        <img src="<?= htmlspecialchars($product['main_image'] ?: 'assets/images/placeholder.jpg') ?>" 
                             alt="<?= htmlspecialchars($product['title']) ?>"
                             style="width: 100%; height: 200px; object-fit: cover; border-radius: 8px;">
                    </div>

                    <!-- معلومات المنتج -->
                    <div style="margin-bottom: 1rem;">
                        <h4 style="margin: 0 0 0.5rem 0; color: #1f2937; font-size: 1.1rem;">
                            <?= htmlspecialchars($product['title']) ?>
                        </h4>
                        <p style="color: #6b7280; font-size: 0.875rem; margin: 0 0 0.5rem 0;">
                            <?= htmlspecialchars($product['short_description'] ?: 'لا يوجد وصف مختصر') ?>
                        </p>
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-weight: 700; color: #3b82f6; font-size: 1.25rem;">
                                <?= formatPrice($product['final_price']) ?>
                            </span>
                            <span style="color: #6b7280; font-size: 0.875rem;">
                                متبقي <?= $product['stock'] ?> قطعة
                            </span>
                        </div>
                    </div>

                    <!-- إحصائيات المنتج -->
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.5rem; margin-bottom: 1rem; padding: 1rem; background: #f8fafc; border-radius: 8px;">
                        <div style="text-align: center;">
                            <div style="font-weight: 700; color: #3b82f6;"><?= $product['views'] ?></div>
                            <div style="font-size: 0.75rem; color: #6b7280;">مشاهدة</div>
                        </div>
                        <div style="text-align: center;">
                            <div style="font-weight: 700; color: #10b981;"><?= $product['orders_count'] ?></div>
                            <div style="font-size: 0.75rem; color: #6b7280;">مبيعات</div>
                        </div>
                        <div style="text-align: center;">
                            <div style="font-weight: 700; color: #f59e0b;"><?= $product['rating_avg'] ?></div>
                            <div style="font-size: 0.75rem; color: #6b7280;">تقييم</div>
                        </div>
                    </div>

                    <!-- أزرار التحكم -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem;">
                        <button onclick="editProduct(<?= $product['id'] ?>)" 
                                class="btn btn-outline" style="padding: 0.5rem; font-size: 0.875rem;">
                            <i class="fas fa-edit"></i> تعديل
                        </button>
                        <button onclick="deleteProduct(<?= $product['id'] ?>)" 
                                class="btn btn-danger" style="padding: 0.5rem; font-size: 0.875rem;"
                                onclick="return confirm('هل أنت متأكد من حذف هذا المنتج؟')">
                            <i class="fas fa-trash"></i> حذف
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- الترقيم -->
        <?php if ($customer_products_stats['total_pages'] > 1): ?>
            <div style="display: flex; justify-content: center; gap: 0.5rem; margin-top: 2rem;">
                <?php for ($i = 1; $i <= $customer_products_stats['total_pages']; $i++): ?>
                    <a href="?tab=my_products&page=<?= $i ?>" 
                       class="page-link <?= $i == ($_GET['page'] ?? 1) ? 'active' : '' ?>"
                       style="padding: 0.5rem 1rem; border: 1px solid #d1d5db; border-radius: 6px; text-decoration: none; color: #374151; <?= $i == ($_GET['page'] ?? 1) ? 'background: #3b82f6; color: white; border-color: #3b82f6;' : '' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

 
</div>
<script>
function showAddProductModal() {
    document.getElementById('addProductModal').style.display = 'block';
}

function hideAddProductModal() {
    document.getElementById('addProductModal').style.display = 'none';
}

function editProduct(productId) {
    // سيتم تنفيذ تعديل المنتج
    alert('تعديل المنتج: ' + productId);
}

function deleteProduct(productId) {
    if (confirm('هل أنت متأكد من حذف هذا المنتج؟')) {
        // سيتم تنفيذ حذف المنتج
        alert('حذف المنتج: ' + productId);
    }
}

function submitProductForm() {
    // سيتم تنفيذ حفظ المنتج
    alert('سيتم حفظ المنتج');
}
</script>
				<!-- الملف الشخصي -->
                <div id="profile" class="tab-content">
                    <h2>الملف الشخصي</h2>
                    <form method="post">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                            <div class="form-group">
                                <label>الاسم الأول</label>
                                <input type="text" name="first_name" value="<?= htmlspecialchars($customer['first_name']) ?>" required>
                            </div>
                            <div class="form-group">
                                <label>اسم العائلة</label>
                                <input type="text" name="last_name" value="<?= htmlspecialchars($customer['last_name']) ?>" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>البريد الإلكتروني</label>
                            <input type="email" value="<?= htmlspecialchars($customer['email']) ?>" disabled style="background: #e9ecef;">
                            <small style="color: #6c757d;">لا يمكن تغيير البريد الإلكتروني</small>
                        </div>
                        <div class="form-group">
                            <label>رقم الهاتف</label>
                            <input type="tel" name="phone" value="<?= htmlspecialchars($customer['phone']) ?>" pattern="01[0-2,5]{1}[0-9]{8}" required>
                        </div>
                        <button type="submit" name="update_profile" class="btn btn-primary">
                            <i class="fas fa-save"></i> حفظ التغييرات
                        </button>
                    </form>
                </div>

                <!-- الأمان -->
                <div id="security" class="tab-content">
                    <h2>تغيير كلمة المرور</h2>
                    <form method="post" style="max-width: 500px;">
                        <div class="form-group">
                            <label>كلمة المرور الحالية</label>
                            <input type="password" name="current_password" required>
                        </div>
                        <div class="form-group">
                            <label>كلمة المرور الجديدة</label>
                            <input type="password" name="new_password" minlength="6" required>
                        </div>
                        <div class="form-group">
                            <label>تأكيد كلمة المرور الجديدة</label>
                            <input type="password" name="confirm_new_password" minlength="6" required>
                        </div>
                        <button type="submit" name="change_password" class="btn btn-primary">
                            <i class="fas fa-key"></i> تغيير كلمة المرور
                        </button>
                    </form>
                </div>
            
			<!--  ثم أضف محتوى تبويب النقاط:-->
                <div id="points" class="tab-content">
    <div class="points-header" style="background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 2rem; border-radius: 12px; margin-bottom: 2rem;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h2 style="margin: 0 0 0.5rem 0; color: white;">رصيد نقاطك</h2>
                <p style="margin: 0; opacity: 0.9;">استخدم نقاطك للحصول على مكافآت حصرية</p>
            </div>
            <div style="text-align: center;">
                <div style="font-size: 3rem; font-weight: 700; line-height: 1;">
                    <?php 
                    $points_data = getCustomerPoints($_SESSION['customer_id']);
                    echo number_format($points_data['available_points']);
                    ?>
                </div>
                <p style="margin: 0; opacity: 0.9;">نقطة</p>
            </div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">
        <div class="stat-card" style="background: white; padding: 1.5rem; border-radius: 8px; text-align: center;">
            <div style="font-size: 2rem; color: #10b981; font-weight: 700;">
                <?= number_format($points_data['total_earned']) ?>
            </div>
            <p style="color: #6b7280; margin: 0;">إجمالي النقاط المكتسبة</p>
        </div>
        <div class="stat-card" style="background: white; padding: 1.5rem; border-radius: 8px; text-align: center;">
            <div style="font-size: 2rem; color: #ef4444; font-weight: 700;">
                <?= number_format($points_data['total_spent']) ?>
            </div>
            <p style="color: #6b7280; margin: 0;">إجمالي النقاط المستهلكة</p>
        </div>
    </div>

    <div class="card" style="background: white; padding: 2rem; border-radius: 8px;">
        <h3 style="margin-bottom: 1.5rem;">كيفية كسب النقاط؟</h3>
        <div style="display: grid; gap: 1rem;">
            <div style="display: flex; align-items: center; gap: 1rem; padding: 1rem; background: #f8fafc; border-radius: 8px;">
                <div style="width: 40px; height: 40px; background: #3b82f6; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div>
                    <strong>شراء المنتجات</strong>
                    <p style="margin: 0.25rem 0 0 0; color: #6b7280;">
                        احصل على <?= getSetting('points_earn_rate', 10) ?> نقطة لكل 100 جنيه تشتريها
                    </p>
                </div>
            </div>
            <div style="display: flex; align-items: center; gap: 1rem; padding: 1rem; background: #f8fafc; border-radius: 8px;">
                <div style="width: 40px; height: 40px; background: #10b981; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-gift"></i>
                </div>
                <div>
                    <strong>الهدايا والعروض</strong>
                    <p style="margin: 0.25rem 0 0 0; color: #6b7280;">
                        احصل على نقاط إضافية من العروض الموسمية والهدايا
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="card" style="background: white; padding: 2rem; border-radius: 8px; margin-top: 2rem;">
        <div style="display: flex; justify-content: between; align-items: center; margin-bottom: 1.5rem;">
            <h3 style="margin: 0;">سجل المعاملات</h3>
            <a href="points-history.php" class="btn btn-outline-primary">عرض الكل</a>
        </div>
        
        <?php
        $transactions = getPointTransactions($_SESSION['customer_id'], 5);
        if (empty($transactions)):
        ?>
            <p style="text-align: center; color: #6b7280; padding: 2rem;">لا توجد معاملات حتى الآن</p>
        <?php else: ?>
            <div class="transactions-list">
                <?php foreach ($transactions as $transaction): ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 1rem; border-bottom: 1px solid #e5e7eb;">
                        <div>
                            <strong><?= htmlspecialchars($transaction['description']) ?></strong>
                            <p style="margin: 0.25rem 0 0 0; color: #6b7280; font-size: 0.875rem;">
                                <?= date('Y-m-d H:i', strtotime($transaction['created_at'])) ?>
                            </p>
                        </div>
                        <div style="text-align: left;">
                            <span style="font-weight: 700; color: <?= $transaction['type'] === 'earn' ? '#10b981' : '#ef4444' ?>;">
                                <?= $transaction['type'] === 'earn' ? '+' : '-' ?><?= number_format($transaction['points']) ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
			

<!-- محتوى تبويب الإحالات -->
<div id="referrals" class="tab-content">
    <div class="referral-header" style="background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 2rem; border-radius: 12px; margin-bottom: 2rem;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h2 style="margin: 0 0 0.5rem 0; color: white;">برنامج الإحالات</h2>
                <p style="margin: 0; opacity: 0.9;">ادعُ أصدقاءك واكسب نقاطاً مجانية</p>
            </div>
            <div style="text-align: center;">
                <div style="font-size: 2rem; font-weight: 700; line-height: 1;">
                    <?= number_format($referral_stats['total_earned_points']) ?>
                </div>
                <p style="margin: 0; opacity: 0.9;">نقطة مكتسبة</p>
            </div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; margin-bottom: 2rem;">
        <div class="stat-card" style="background: white; padding: 1.5rem; border-radius: 8px; text-align: center;">
            <div style="font-size: 2rem; color: #3b82f6; font-weight: 700;">
                <?= number_format($referral_stats['signups']) ?>
            </div>
            <p style="color: #6b7280; margin: 0;">اشتراكات</p>
        </div>
        <div class="stat-card" style="background: white; padding: 1.5rem; border-radius: 8px; text-align: center;">
            <div style="font-size: 2rem; color: #10b981; font-weight: 700;">
                <?= number_format($referral_stats['completed_orders']) ?>
            </div>
            <p style="color: #6b7280; margin: 0;">طلبات مكتملة</p>
        </div>
        <div class="stat-card" style="background: white; padding: 1.5rem; border-radius: 8px; text-align: center;">
            <div style="font-size: 2rem; color: #f59e0b; font-weight: 700;">
                <?= number_format($referral_stats['total_earned_points']) ?>
            </div>
            <p style="color: #6b7280; margin: 0;">نقاط مكتسبة</p>
        </div>
    </div>

    <!-- رابط الإحالة -->
    <div class="card" style="background: white; padding: 2rem; border-radius: 8px; margin-bottom: 2rem;">
        <h3 style="margin-bottom: 1rem;">رابط الإحالة الخاص بك</h3>
        <div style="display: flex; gap: 1rem; margin-bottom: 1rem;">
            <input type="text" id="referralLink" value="<?= $referral_stats['referral_url'] ?>" readonly 
                   style="flex: 1; padding: 0.75rem; border: 1px solid #ddd; border-radius: 8px;">
            <button onclick="copyReferralLink()" class="btn btn-primary" style="white-space: nowrap;">
                <i class="fas fa-copy"></i> نسخ الرابط
            </button>
        </div>
        <p style="color: #6b7280; font-size: 0.9rem; margin: 0;">
            <i class="fas fa-info-circle"></i>
            شارك هذا الرابط مع أصدقائك. ستحصل على <?= getSetting('referral_points_referrer', '500') ?> نقطة عند تسجيلهم 
            و <?= getSetting('referral_points_referrer_order', '500') ?> نقطة إضافية عند إكمالهم لأول طلب.
        </p>
    </div>

    <!-- كيفية العمل -->
    <div class="card" style="background: white; padding: 2rem; border-radius: 8px; margin-bottom: 2rem;">
        <h3 style="margin-bottom: 1.5rem;">كيف يعمل برنامج الإحالات؟</h3>
        <div style="display: grid; gap: 1rem;">
            <div style="display: flex; align-items: center; gap: 1rem; padding: 1rem; background: #f8fafc; border-radius: 8px;">
                <div style="width: 40px; height: 40px; background: #3b82f6; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700;">
                    1
                </div>
                <div>
                    <strong>انشر رابط الإحالة</strong>
                    <p style="margin: 0.25rem 0 0 0; color: #6b7280;">
                        شارك رابط الإحالة الخاص بك مع أصدقائك وعائلتك
                    </p>
                </div>
            </div>
            <div style="display: flex; align-items: center; gap: 1rem; padding: 1rem; background: #f8fafc; border-radius: 8px;">
                <div style="width: 40px; height: 40px; background: #10b981; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700;">
                    2
                </div>
                <div>
                    <strong>يسجل صديقك</strong>
                    <p style="margin: 0.25rem 0 0 0; color: #6b7280;">
                        يحصل صديقك على <?= getSetting('referral_points_referred', '300') ?> نقطة ترحيبية عند التسجيل
                    </p>
                </div>
            </div>
            <div style="display: flex; align-items: center; gap: 1rem; padding: 1rem; background: #f8fafc; border-radius: 8px;">
                <div style="width: 40px; height: 40px; background: #f59e0b; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700;">
                    3
                </div>
                <div>
                    <strong>يكمل صديقك طلباً</strong>
                    <p style="margin: 0.25rem 0 0 0; color: #6b7280;">
                        تحصل على <?= getSetting('referral_points_referrer_order', '500') ?> نقطة عند إكمال صديقك لأول طلب
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- تاريخ الإحالات -->
    <div class="card" style="background: white; padding: 2rem; border-radius: 8px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h3 style="margin: 0;">تاريخ الإحالات</h3>
        </div>
        
        <?php if (empty($referral_history)): ?>
            <p style="text-align: center; color: #6b7280; padding: 2rem;">لا توجد إحالات حتى الآن</p>
        <?php else: ?>
            <div class="referrals-list">
                <?php foreach ($referral_history as $referral): ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 1rem; border-bottom: 1px solid #e5e7eb;">
                        <div>
                            <strong><?= htmlspecialchars($referral['referred_name']) ?></strong>
                            <p style="margin: 0.25rem 0 0 0; color: #6b7280; font-size: 0.875rem;">
                                <?= htmlspecialchars($referral['referred_email']) ?> | 
                                <?= date('Y-m-d', strtotime($referral['created_at'])) ?>
                            </p>
                        </div>
                        <div style="text-align: left;">
                            <span style="font-weight: 700; color: 
                                <?= $referral['status'] === 'completed_order' ? '#10b981' : 
                                   ($referral['status'] === 'signed_up' ? '#3b82f6' : '#6b7280') ?>">
                                <?php
                                $statuses = [
                                    'pending' => 'قيد الانتظار',
                                    'signed_up' => 'مسجل',
                                    'completed_order' => 'مكتمل',
                                    'expired' => 'منتهي'
                                ];
                                echo $statuses[$referral['status']] ?? $referral['status'];
                                ?>
                            </span>
                            <?php if ($referral['points_earned'] > 0): ?>
                                <div style="color: #10b981; font-weight: 600;">
                                    +<?= number_format($referral['points_earned']) ?> نقطة
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<!-- نموذج شحن المحفظة -->
<div id="depositModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">
    <div class="modal-content" style="background-color: white; margin: 5% auto; padding: 2rem; border-radius: 12px; width: 90%; max-width: 500px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h3 style="margin: 0;">شحن المحفظة</h3>
            <span onclick="closeDepositModal()" style="cursor: pointer; font-size: 1.5rem;">&times;</span>
        </div>
        
        <form id="depositForm" method="POST" action="wallet-actions.php">
            <input type="hidden" name="action" value="deposit">
            
            <div class="form-group">
                <label>المبلغ (ج.م)</label>
                <input type="number" name="amount" min="10" max="10000" step="0.01" required 
                       style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 8px;">
                <small style="color: #6b7280;">الحد الأدنى للشحن: 10 جنيه - الحد الأقصى: 10,000 جنيه</small>
            </div>
            
            <div class="form-group">
                <label>طريقة الدفع</label>
                <select name="payment_method" required style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 8px;">
                    <option value="vodafone_cash">فودافون كاش</option>
                    <option value="instapay">انستاباي</option>
                    <option value="visa">بطاقة ائتمان</option>
                    <option value="fawry">فوري</option>
                </select>
            </div>
            
            <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                <button type="button" onclick="closeDepositModal()" class="btn btn-secondary" style="flex: 1;">إلغاء</button>
                <button type="submit" class="btn btn-primary" style="flex: 1;">متابعة للدفع</button>
            </div>
        </form>
    </div>
</div>

<!-- نموذج سحب الرصيد -->
<div id="withdrawModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">
    <div class="modal-content" style="background-color: white; margin: 5% auto; padding: 2rem; border-radius: 12px; width: 90%; max-width: 500px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h3 style="margin: 0;">سحب رصيد</h3>
            <span onclick="closeWithdrawModal()" style="cursor: pointer; font-size: 1.5rem;">&times;</span>
        </div>
        
        <form id="withdrawForm" method="POST" action="wallet-actions.php">
            <input type="hidden" name="action" value="withdraw">
            
            <div class="form-group">
                <label>المبلغ (ج.م)</label>
                <input type="number" name="amount" min="50" max="<?= $wallet['balance'] ?>" step="0.01" required 
                       style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 8px;">
                <small style="color: #6b7280;">
                    الرصيد المتاح: <?= number_format($wallet['balance'], 2) ?> ج.م - الحد الأدنى للسحب: 50 جنيه
                </small>
            </div>
            
            <div class="form-group">
                <label>طريقة السحب</label>
                <select name="withdraw_method" required style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 8px;">
                    <option value="bank_transfer">تحويل بنكي</option>
                    <option value="vodafone_cash">فودافون كاش</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>معلومات الاستلام</label>
                <input type="text" name="receiver_info" required 
                       placeholder="رقم الحساب البنكي أو رقم الهاتف" 
                       style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 8px;">
            </div>
            
            <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                <button type="button" onclick="closeWithdrawModal()" class="btn btn-secondary" style="flex: 1;">إلغاء</button>
                <button type="submit" class="btn btn-primary" style="flex: 1;">طلب السحب</button>
            </div>
        </form>
    </div>
</div>
<script>
function copyReferralLink() {
    const linkInput = document.getElementById('referralLink');
    linkInput.select();
    linkInput.setSelectionRange(0, 99999);
    document.execCommand('copy');
    
    // عرض رسالة نجاح
    alert('تم نسخ رابط الإحالة بنجاح!');
}
</script>
			<script>
// وظائف المحفظة
function showDepositModal() {
    document.getElementById('depositModal').style.display = 'block';
}

function closeDepositModal() {
    document.getElementById('depositModal').style.display = 'none';
}

function showWithdrawModal() {
    document.getElementById('withdrawModal').style.display = 'block';
}

function closeWithdrawModal() {
    document.getElementById('withdrawModal').style.display = 'none';
}

// إغلاق النماذج عند النقر خارجها
window.onclick = function(event) {
    const depositModal = document.getElementById('depositModal');
    const withdrawModal = document.getElementById('withdrawModal');
    
    if (event.target == depositModal) {
        closeDepositModal();
    }
    if (event.target == withdrawModal) {
        closeWithdrawModal();
    }
}

// التحقق من صحة مبلغ السحب
document.getElementById('withdrawForm').addEventListener('submit', function(e) {
    const amount = parseFloat(this.amount.value);
    const balance = parseFloat(<?= $wallet['balance'] ?>);
    
    if (amount > balance) {
        e.preventDefault();
        alert('المبلغ المطلوب أكبر من الرصيد المتاح');
        return false;
    }
    
    if (amount < 50) {
        e.preventDefault();
        alert('الحد الأدنى للسحب هو 50 جنيهاً');
        return false;
    }
});
</script>
			<?php endif; ?>
        </div>
    </main>

    <!-- Footer -->
    <footer class="site-footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3><?= htmlspecialchars($storeName) ?></h3>
                    <p><?= htmlspecialchars($storeDescription) ?></p>
                    <div class="social-links">
                        <?php if ($fb = getSetting('facebook_url')): ?>
                            <a href="<?= htmlspecialchars($fb) ?>" target="_blank"><i class="fab fa-facebook"></i></a>
                        <?php endif; ?>
                        <?php if ($ig = getSetting('instagram_url')): ?>
                            <a href="<?= htmlspecialchars($ig) ?>" target="_blank"><i class="fab fa-instagram"></i></a>
                        <?php endif; ?>
                        <?php if ($tw = getSetting('twitter_url')): ?>
                            <a href="<?= htmlspecialchars($tw) ?>" target="_blank"><i class="fab fa-twitter"></i></a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="footer-section">
                    <h4>روابط سريعة</h4>
                    <ul>
                        <li><a href="index.php">الرئيسية</a></li>
                        <li><a href="about.php">من نحن</a></li>
                        <li><a href="contact.php">اتصل بنا</a></li>
                        <li><a href="privacy.php">سياسة الخصوصية</a></li>
                        <li><a href="terms.php">الشروط والأحكام</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>خدمة العملاء</h4>
                    <ul>
                        <li><a href="faq.php">الأسئلة الشائعة</a></li>
                        <li><a href="shipping.php">سياسة الشحن</a></li>
                        <li><a href="returns.php">سياسة الاسترجاع</a></li>
                        <li><a href="track.php">تتبع الطلب</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>تواصل معنا</h4>
                    <ul class="contact-info">
                        <li><i class="fas fa-phone"></i> <?= getSetting('store_phone', '') ?></li>
                        <li><i class="fas fa-envelope"></i> <?= getSetting('store_email', '') ?></li>
                        <?php if ($whatsapp = getSetting('whatsapp_number')): ?>
                            <li>
                                <a href="https://wa.me/<?= $whatsapp ?>" target="_blank">
                                    <i class="fab fa-whatsapp"></i> تواصل واتساب
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?= date('Y') ?> <?= htmlspecialchars($storeName) ?>. جميع الحقوق محفوظة.</p>
            </div>
        </div>
    </footer>
<button class="back-to-top show" aria-label="العودة للأعلى"><i class="fas fa-arrow-up"></i></button>

    <script src="assets/js/app.js"></script>
    <script>
        function openTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.getElementById(tabName).classList.add('active');
            event.target.classList.add('active');
        }
    </script>
	<!-- نموذج إضافة منتج جديد -->
<div id="addProductModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">
    <div class="modal-content" style="background-color: white; margin: 2% auto; padding: 2rem; border-radius: 12px; width: 90%; max-width: 800px; max-height: 90vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h3 style="margin: 0;">إضافة منتج جديد لمتجر إبراهيم</h3>
            <span onclick="closeAddProductModal()" style="cursor: pointer; font-size: 1.5rem;">&times;</span>
        </div>
        
        <form id="addProductForm" method="POST" action="customer-products-actions.php" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add_product">
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                <div class="form-group">
                    <label>اسم المنتج *</label>
                    <input type="text" name="title" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label>السعر (ج.م) *</label>
                    <input type="number" name="price" class="form-control" step="0.01" min="0" required>
                </div>
            </div>
            
            <div class="form-group">
                <label>الوصف المختصر</label>
                <textarea name="short_description" class="form-control" rows="2" placeholder="وصف مختصر للمنتج..."></textarea>
            </div>
            
            <div class="form-group">
                <label>الوصف الكامل</label>
                <textarea name="description" class="form-control" rows="4" placeholder="وصف كامل للمنتج..."></textarea>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                <div class="form-group">
                    <label>الكمية المتاحة *</label>
                    <input type="number" name="stock" class="form-control" min="0" required>
                </div>
                
                <div class="form-group">
                    <label>نسبة الخصم (%)</label>
                    <input type="number" name="discount_percentage" class="form-control" min="0" max="100" step="0.01">
                </div>
                
                <div class="form-group">
                    <label>الفئة</label>
                    <select name="category_id" class="form-control">
                        <option value="">اختر فئة...</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label>صورة المنتج الرئيسية</label>
                <input type="file" name="main_image" class="form-control" accept="image/*">
            </div>
            
            <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                <button type="button" onclick="closeAddProductModal()" class="btn btn-secondary" style="flex: 1;">إلغاء</button>
                <button type="submit" class="btn btn-primary" style="flex: 1;">إضافة المنتج</button>
            </div>
        </form>
    </div>
</div>

<!-- نموذج رفع مجموعة منتجات -->
<div id="bulkUploadModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">
    <div class="modal-content" style="background-color: white; margin: 5% auto; padding: 2rem; border-radius: 12px; width: 90%; max-width: 600px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h3 style="margin: 0;">رفع مجموعة منتجات</h3>
            <span onclick="closeBulkUploadModal()" style="cursor: pointer; font-size: 1.5rem;">&times;</span>
        </div>
        
        <div style="background: #f0f9ff; padding: 1.5rem; border-radius: 8px; margin-bottom: 1.5rem;">
            <h4 style="color: #0369a1; margin-bottom: 1rem;">
                <i class="fas fa-info-circle"></i> تعليمات الرفع
            </h4>
            <ul style="color: #0369a1; margin: 0; padding-right: 1.5rem;">
                <li>يمكنك تحميل ملف Excel يحتوي على بيانات المنتجات</li>
                <li>يجب أن يحتوي الملف على الأعمدة: الاسم، السعر، الكمية، الوصف</li>
                <li>يمكنك <a href="assets/templates/products-template.xlsx" download>تحميل القالب</a> لمساعدتك</li>
                <li>الحد الأقصى لحجم الملف: 5MB</li>
            </ul>
        </div>
        
        <form id="bulkUploadForm" method="POST" action="ibrahim-products-actions.php" enctype="multipart/form-data">
            <input type="hidden" name="action" value="bulk_upload">
            
            <div class="form-group">
                <label>اختر ملف Excel</label>
                <input type="file" name="bulk_file" class="form-control" accept=".xlsx,.xls" required>
            </div>
            
            <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                <button type="button" onclick="closeBulkUploadModal()" class="btn btn-secondary" style="flex: 1;">إلغاء</button>
                <button type="submit" class="btn btn-primary" style="flex: 1;">رفع الملف</button>
            </div>
        </form>
    </div>
</div>
<script>
// وظائف منتجات متجر إبراهيم
function showAddProductModal() {
    document.getElementById('addProductModal').style.display = 'block';
}

function closeAddProductModal() {
    document.getElementById('addProductModal').style.display = 'none';
}

function showBulkUploadModal() {
    document.getElementById('bulkUploadModal').style.display = 'block';
}

function closeBulkUploadModal() {
    document.getElementById('bulkUploadModal').style.display = 'none';
}

function editProduct(productId) {
    window.location.href = `edit-product.php?id=${productId}&store=ibrahim`;
}

function deleteProduct(productId) {
    if (confirm('هل أنت متأكد من حذف هذا المنتج؟ سيتم حذف جميع البيانات المرتبطة به.')) {
        fetch('ibrahim-products-actions.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=delete_product&product_id=${productId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('حدث خطأ أثناء الحذف: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('حدث خطأ أثناء الحذف');
        });
    }
}

function exportProducts() {
    window.open('ibrahim-products-export.php', '_blank');
}

// إغلاق النماذج عند النقر خارجها
window.onclick = function(event) {
    const addModal = document.getElementById('addProductModal');
    const bulkModal = document.getElementById('bulkUploadModal');
    
    if (event.target == addModal) closeAddProductModal();
    if (event.target == bulkModal) closeBulkUploadModal();
}
// التحقق من دعم الماسح الضوئي
function checkScannerSupport() {
    const support = {
        mediaDevices: !!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia),
        https: window.location.protocol === 'https:',
        jsQR: typeof jsQR !== 'undefined'
    };
    
    if (!support.https) {
        console.warn('الماسح الضوئي يعمل بشكل أفضل مع HTTPS');
    }
    
    if (!support.mediaDevices) {
        console.error('المتصفح لا يدعم الوصول إلى الكاميرا');
        return false;
    }
    
    if (!support.jsQR) {
        console.error('مكتبة jsQR غير محملة');
        return false;
    }
    
    return true;
}

// تحديث دالة openQRScannerModal مع التحقق
function openQRScannerModal() {
    if (!checkScannerSupport()) {
        alert('الماسح الضوئي غير مدعوم في متصفحك الحالي. يرجى استخدام متصفح حديث مع HTTPS.');
        return;
    }
    
    document.getElementById('qrScannerModal').style.display = 'block';
    document.getElementById('scannerResult').innerHTML = '';
    document.getElementById('manualQRCode').value = '';
    
    showScannerInstructions();
    
    setTimeout(() => {
        startQRScanner();
    }, 500);
}
// حل للبيئات غير الآمنة (للتطوير فقط)
function setupScannerForDevelopment() {
    if (window.location.protocol !== 'https:' && window.location.hostname !== 'localhost') {
        console.warn('للاستخدام الكامل، يرجى تشغيل الموقع على HTTPS');
        
        // إظهار تحذير للمستخدم
        const warning = document.createElement('div');
        warning.className = 'browser-warning';
        warning.innerHTML = `
            <div style="background: #fffbeb; border: 1px solid #fef3c7; padding: 1rem; border-radius: 8px; margin: 1rem 0;">
                <p style="margin: 0; color: #92400e;">
                    <i class="fas fa-exclamation-triangle"></i>
                    للمسح الضوئي الكامل، يرجى زيارة الموقع عبر HTTPS
                </p>
            </div>
        `;
        
        const scannerContent = document.getElementById('scannerContent');
        if (scannerContent) {
            scannerContent.insertBefore(warning, scannerContent.firstChild);
        }
    }
}

// استدعاء عند تحميل الصفحة
document.addEventListener('DOMContentLoaded', function() {
    setupScannerForDevelopment();
});
</script>
<!-- نافذة مسح QR Code للتجار -->
<!-- نافذة مسح QR Code للتجار -->
<div id="qrScannerModal" class="modal qr-scanner-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-camera"></i> مسح كود QR</h3>
            <span class="close" onclick="closeQRScannerModal()">&times;</span>
        </div>
        <div class="modal-body">
            <div id="scannerContent">
                <div class="scanner-instructions">
                    <h4>تعليمات الاستخدام:</h4>
                    <ol>
                        <li>اطلب من العميل عرض كود QR على هاتفه</li>
                        <li>استخدم كاميرا الجهاز لمسح الكود</li>
                        <li>سيتم التحقق تلقائياً من صحة الكود</li>
                        <li>اضغط على تأكيد لإتمام العملية</li>
                    </ol>
                    <div class="browser-support">
                        <p><strong>ملاحظة:</strong> يتطلب HTTPS للعمل على المتصفحات الحديثة</p>
                    </div>
                </div>
                
                <div class="scanner-area">
                    <div class="scanner-placeholder" id="scannerPlaceholder">
                        <i class="fas fa-qrcode"></i>
                        <p>جاري تحميل الماسح الضوئي...</p>
                    </div>
                    <video id="qrScanner" style="display: none; width: 100%; height: 300px; border: 2px dashed #ddd; border-radius: 8px;"></video>
                    <div id="scannerResult" class="scanner-result"></div>
                </div>
                
                <div class="manual-input" style="display: none;">
                    <h4><i class="fas fa-keyboard"></i> الإدخال اليدوي</h4>
                    <label>أدخل كود QR يدوياً:</label>
                    <input type="text" id="manualQRCode" placeholder="أدخل كود QR هنا" style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 6px; margin: 0.5rem 0;">
                    <div style="display: flex; gap: 0.5rem;">
                        <button onclick="validateManualQRCode()" class="btn btn-primary">
                            <i class="fas fa-check"></i> تحقق
                        </button>
                        <button onclick="retryScanner()" class="btn btn-secondary">
                            <i class="fas fa-camera"></i> العودة للمسح
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html> 