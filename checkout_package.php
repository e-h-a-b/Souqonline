<?php
/**
 * صفحة دفع الباقات
 */
session_start();
require_once 'functions.php';

// التحقق من تسجيل الدخول
if (!isset($_SESSION['customer_id'])) {
    header('Location: account.php');
    exit;
}

$storeName = getSetting('store_name', 'متجر إلكتروني');
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

// جلب بيانات طلب الباقة
global $pdo;
$stmt = $pdo->prepare("
    SELECT po.*, p.name as package_name, p.description, p.points, p.bonus_points
    FROM package_orders po
    JOIN packages p ON po.package_id = p.id
    WHERE po.id = ? AND po.customer_id = ?
");
$stmt->execute([$order_id, $_SESSION['customer_id']]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: packages.php');
    exit;
}

// معالجة الدفع
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_payment'])) {
    $payment_method = cleanInput($_POST['payment_method']);
    
    // في بيئة حقيقية، هنا ستتم معالجة الدفع عبر البوابات
    // سنفترض أن الدفع تم بنجاح لمثالنا هذا
    
    $result = processPackagePayment($order_id);
    
    if ($result['success']) {
        $message = $result['message'];
        // إعادة توجيه إلى صفحة النجاح
        header('Location: package_success.php?order_id=' . $order_id);
        exit;
    } else {
        $error = $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>دفع الباقة - <?= htmlspecialchars($storeName) ?></title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Header -->
    <header class="site-header">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <a href="index.php"><h1><?= htmlspecialchars($storeName) ?></h1></a>
                </div>
            </div>
        </div>
    </header>

    <main class="checkout-page" style="padding: 3rem 0; min-height: 80vh;">
        <div class="container">
            <div style="max-width: 600px; margin: 0 auto;">
                <h1 style="text-align: center; margin-bottom: 2rem;">إتمام شراء الباقة</h1>

                <?php if ($error): ?>
                    <div style="background: #f8d7da; color: #721c24; padding: 1rem; border-radius: 8px; margin-bottom: 2rem;">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <div class="order-summary" style="background: white; padding: 2rem; border-radius: 12px; margin-bottom: 2rem;">
                    <h2 style="margin-bottom: 1rem;">ملخص الطلب</h2>
                    
                    <div style="display: grid; gap: 1rem;">
                        <div style="display: flex; justify-content: space-between;">
                            <span>الباقة:</span>
                            <strong><?= htmlspecialchars($order['package_name']) ?></strong>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span>النقاط الأساسية:</span>
                            <span><?= number_format($order['points']) ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span>نقاط المكافأة:</span>
                            <span style="color: #059669;">+<?= number_format($order['bonus_points']) ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; border-top: 1px solid #e5e7eb; padding-top: 1rem;">
                            <span>إجمالي النقاط:</span>
                            <strong style="color: #059669; font-size: 1.2rem;">
                                <?= number_format($order['points_amount']) ?> نقطة
                            </strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; border-top: 1px solid #e5e7eb; padding-top: 1rem;">
                            <span>المبلغ المستحق:</span>
                            <strong style="color: #dc2626; font-size: 1.5rem;">
                                <?= formatPrice($order['price']) ?>
                            </strong>
                        </div>
                    </div>
                </div>

                <form method="post" class="payment-form" style="background: white; padding: 2rem; border-radius: 12px;">
                    <h2 style="margin-bottom: 1rem;">طريقة الدفع</h2>
                    
                    <div style="display: grid; gap: 1rem;">
                        <label style="display: flex; align-items: center; gap: 1rem; padding: 1rem; border: 2px solid #e5e7eb; border-radius: 8px;">
                            <input type="radio" name="payment_method" value="cod" checked>
                            <div>
                                <strong>الدفع عند الاستلام</strong>
                                <p style="margin: 0.25rem 0 0 0; color: #6b7280; font-size: 0.875rem;">
                                    سيدفع المبلغ عند توصيل الباقة
                                </p>
                            </div>
                        </label>

                        <label style="display: flex; align-items: center; gap: 1rem; padding: 1rem; border: 2px solid #e5e7eb; border-radius: 8px;">
                            <input type="radio" name="payment_method" value="vodafone_cash">
                            <div>
                                <strong>فودافون كاش</strong>
                                <p style="margin: 0.25rem 0 0 0; color: #6b7280; font-size: 0.875rem;">
                                    الدفع عبر فودافون كاش
                                </p>
                            </div>
                        </label>

                        <label style="display: flex; align-items: center; gap: 1rem; padding: 1rem; border: 2px solid #e5e7eb; border-radius: 8px;">
                            <input type="radio" name="payment_method" value="fawry">
                            <div>
                                <strong>فوري</strong>
                                <p style="margin: 0.25rem 0 0 0; color: #6b7280; font-size: 0.875rem;">
                                    الدفع عبر محطات فوري
                                </p>
                            </div>
                        </label>
                    </div>

                    <button type="submit" name="process_payment" class="btn btn-primary" style="width: 100%; padding: 1rem; margin-top: 2rem; font-size: 1.1rem;">
                        <i class="fas fa-lock"></i> تأكيد الشراء
                    </button>
                </form>
            </div>
        </div>
    </main>
</body>
</html>