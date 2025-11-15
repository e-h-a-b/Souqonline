<?php
/**
 * صفحة إرجاع الدفع من بوابات الدفع الإلكترونية
 */
session_start();
require_once 'functions.php';
// التحقق من وضع الصيانة
if (getSetting('maintenance_mode', '0') == '1' && !isset($_SESSION['admin_id'])) {
    header('Location: maintenance.php');
    exit;
}
$success = false;
$message = '';
$orderId = null;
$orderNumber = null;

$storeDescription = getSetting('store_description', '');
// معالجة استجابة بوابة الدفع
if ($_SERVER['REQUEST_METHOD'] === 'GET' || $_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // معالجة Paytabs Response
    if (isset($_POST['payment_reference']) || isset($_GET['payment_reference'])) {
        $paymentRef = $_POST['payment_reference'] ?? $_GET['payment_reference'];
        $responseCode = $_POST['response_code'] ?? $_GET['response_code'];
        $transactionId = $_POST['transaction_id'] ?? $_GET['transaction_id'];
        
        // التحقق من صحة الاستجابة
        // TODO: التحقق من التوقيع الرقمي (Signature)
        
        if ($responseCode == '100') {
            // دفع ناجح
            $orderId = $_SESSION['pending_order_id'] ?? null;
            
            if ($orderId) {
                // تحديث حالة الدفع في قاعدة البيانات
                $stmt = $pdo->prepare("
                    UPDATE orders 
                    SET payment_status = 'paid',
                        payment_transaction_id = ?
                    WHERE id = ?
                ");
                $stmt->execute([$transactionId, $orderId]);
                
                // جلب معلومات الطلب
                $order = getOrder($orderId);
                $orderNumber = $order['order_number'];
                
                // إرسال إشعار بريدي للعميل
                if (!empty($order['customer_email'])) {
                    $subject = "تأكيد الدفع - طلب #" . $orderNumber;
                    $message_body = "
                        <h2>تم استلام دفعتك بنجاح</h2>
                        <p>عزيزي {$order['customer_name']},</p>
                        <p>تم تأكيد دفعتك للطلب رقم: <strong>{$orderNumber}</strong></p>
                        <p>المبلغ المدفوع: <strong>" . formatPrice($order['total']) . "</strong></p>
                        <p>رقم المعاملة: {$transactionId}</p>
                        <p>سيتم شحن طلبك قريباً.</p>
                    ";
                    sendEmail($order['customer_email'], $subject, $message_body);
                }
                
                // تسجيل النشاط
                logActivity('payment_success', "دفع ناجح للطلب $orderNumber - Transaction: $transactionId");
                
                unset($_SESSION['pending_order_id']);
                $success = true;
                $message = 'تم الدفع بنجاح';
            }
        } else {
            // فشل الدفع
            $message = 'فشل في عملية الدفع. يرجى المحاولة مرة أخرى.';
            logActivity('payment_failed', "فشل دفع - Reference: $paymentRef - Code: $responseCode");
        }
    }
    
    // معالجة Fawry Response
    elseif (isset($_GET['fawry_ref'])) {
        $fawryRef = $_GET['fawry_ref'];
        $statusCode = $_GET['status'] ?? '';
        
        // TODO: استدعاء Fawry API للتحقق من حالة الدفع
        
        if ($statusCode == 'PAID') {
            $orderId = $_SESSION['pending_order_id'] ?? null;
            
            if ($orderId) {
                $stmt = $pdo->prepare("
                    UPDATE orders 
                    SET payment_status = 'paid',
                        payment_transaction_id = ?
                    WHERE id = ?
                ");
                $stmt->execute([$fawryRef, $orderId]);
                
                $order = getOrder($orderId);
                $orderNumber = $order['order_number'];
                
                unset($_SESSION['pending_order_id']);
                $success = true;
                $message = 'تم الدفع عبر فوري بنجاح';
            }
        } else {
            $message = 'لم يتم استكمال الدفع عبر فوري';
        }
    }
    
    // معالجة Vodafone Cash Response
    elseif (isset($_POST['vodafone_ref'])) {
        $vodafoneRef = $_POST['vodafone_ref'];
        $status = $_POST['status'] ?? '';
        
        // TODO: التحقق من صحة الاستجابة من Vodafone
        
        if ($status == 'SUCCESS') {
            $orderId = $_SESSION['pending_order_id'] ?? null;
            
            if ($orderId) {
                $stmt = $pdo->prepare("
                    UPDATE orders 
                    SET payment_status = 'paid',
                        payment_transaction_id = ?
                    WHERE id = ?
                ");
                $stmt->execute([$vodafoneRef, $orderId]);
                
                $order = getOrder($orderId);
                $orderNumber = $order['order_number'];
                
                unset($_SESSION['pending_order_id']);
                $success = true;
                $message = 'تم الدفع عبر فودافون كاش بنجاح';
            }
        } else {
            $message = 'فشل الدفع عبر فودافون كاش';
        }
    }
    
    // معالجة InstaPay Response
    elseif (isset($_GET['instapay_ref'])) {
        $instapayRef = $_GET['instapay_ref'];
        $status = $_GET['payment_status'] ?? '';
        
        // TODO: التحقق من InstaPay
        
        if ($status == 'completed') {
            $orderId = $_SESSION['pending_order_id'] ?? null;
            
            if ($orderId) {
                $stmt = $pdo->prepare("
                    UPDATE orders 
                    SET payment_status = 'paid',
                        payment_transaction_id = ?
                    WHERE id = ?
                ");
                $stmt->execute([$instapayRef, $orderId]);
                
                $order = getOrder($orderId);
                $orderNumber = $order['order_number'];
                
                unset($_SESSION['pending_order_id']);
                $success = true;
                $message = 'تم الدفع عبر إنستاباي بنجاح';
            }
        } else {
            $message = 'فشل الدفع عبر إنستاباي';
        }
    }
}

$storeName = getSetting('store_name', 'متجر إلكتروني');
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $success ? 'تم الدفع بنجاح' : 'فشل الدفع' ?> - <?= htmlspecialchars($storeName) ?></title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .payment-result {
            min-height: 70vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }
        .result-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            padding: 50px;
            text-align: center;
            max-width: 600px;
            width: 100%;
        }
        .result-icon {
            font-size: 80px;
            margin-bottom: 20px;
        }
        .result-icon.success {
            color: #10b981;
            animation: scaleIn 0.5s ease;
        }
        .result-icon.error {
            color: #ef4444;
            animation: shake 0.5s ease;
        }
        @keyframes scaleIn {
            0% { transform: scale(0); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }
        .result-title {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 15px;
            color: #1e293b;
        }
        .result-message {
            font-size: 18px;
            color: #64748b;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        .order-details {
            background: #f8fafc;
            border-radius: 12px;
            padding: 25px;
            margin: 30px 0;
            text-align: right;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            color: #64748b;
            font-weight: 500;
        }
        .detail-value {
            color: #1e293b;
            font-weight: 600;
        }
        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
        }
        .btn {
            padding: 14px 30px;
            border-radius: 10px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }
        .btn-primary {
            background: #2563eb;
            color: #fff;
        }
        .btn-primary:hover {
            background: #1d4ed8;
            transform: translateY(-2px);
        }
        .btn-secondary {
            background: #f1f5f9;
            color: #475569;
        }
        .btn-secondary:hover {
            background: #e2e8f0;
        }
        .print-btn {
            background: #10b981;
            color: #fff;
        }
        .print-btn:hover {
            background: #059669;
        }
    </style>
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

    <!-- Payment Result -->
    <main class="payment-result">
        <div class="container">
            <div class="result-card">
                <?php if ($success): ?>
                    <!-- نجاح الدفع -->
                    <div class="result-icon success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h1 class="result-title">تم الدفع بنجاح!</h1>
                    <p class="result-message">
                        شكراً لك! تم استلام دفعتك بنجاح.<br>
                        سيتم معالجة طلبك وشحنه قريباً.
                    </p>

                    <?php if ($orderId && $orderNumber): ?>
                        <div class="order-details">
                            <h3 style="margin-bottom: 20px; color: #1e293b;">تفاصيل الطلب</h3>
                            <div class="detail-row">
                                <span class="detail-label">رقم الطلب</span>
                                <span class="detail-value"><?= htmlspecialchars($orderNumber) ?></span>
                            </div>
                            <?php
                            $order = getOrder($orderId);
                            if ($order):
                            ?>
                                <div class="detail-row">
                                    <span class="detail-label">المبلغ المدفوع</span>
                                    <span class="detail-value"><?= formatPrice($order['total']) ?></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">طريقة الدفع</span>
                                    <span class="detail-value">
                                        <?php
                                        $payment_labels = [
                                            'visa' => 'بطاقة ائتمان',
                                            'instapay' => 'إنستاباي',
                                            'vodafone_cash' => 'فودافون كاش',
                                            'fawry' => 'فوري'
                                        ];
                                        echo $payment_labels[$order['payment_method']] ?? $order['payment_method'];
                                        ?>
                                    </span>
                                </div>
                                <?php if ($order['payment_transaction_id']): ?>
                                    <div class="detail-row">
                                        <span class="detail-label">رقم المعاملة</span>
                                        <span class="detail-value"><?= htmlspecialchars($order['payment_transaction_id']) ?></span>
                                    </div>
                                <?php endif; ?>
                                <div class="detail-row">
                                    <span class="detail-label">التاريخ</span>
                                    <span class="detail-value"><?= date('Y-m-d H:i', strtotime($order['created_at'])) ?></span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="action-buttons">
                            <a href="order-details.php?id=<?= $orderId ?>" class="btn btn-primary">
                                <i class="fas fa-file-alt"></i>
                                عرض تفاصيل الطلب
                            </a>
                            <button onclick="window.print()" class="btn print-btn">
                                <i class="fas fa-print"></i>
                                طباعة
                            </button>
                            <a href="index.php" class="btn btn-secondary">
                                <i class="fas fa-home"></i>
                                العودة للمتجر
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="action-buttons">
                            <a href="index.php" class="btn btn-primary">
                                <i class="fas fa-home"></i>
                                العودة للمتجر
                            </a>
                        </div>
                    <?php endif; ?>

                    <!-- معلومات إضافية -->
                    <div style="margin-top: 30px; padding-top: 30px; border-top: 1px solid #e2e8f0;">
                        <h4 style="color: #64748b; font-size: 14px; margin-bottom: 10px;">الخطوات التالية:</h4>
                        <ul style="text-align: right; color: #64748b; font-size: 14px; line-height: 1.8;">
                            <li>✅ سيتم إرسال إشعار تأكيد على بريدك الإلكتروني</li>
                            <li>📦 سيتم تجهيز طلبك خلال 1-2 يوم عمل</li>
                            <li>🚚 سيصلك الطلب خلال 2-7 أيام عمل</li>
                            <li>📞 يمكنك التواصل معنا على <?= getSetting('store_phone') ?></li>
                        </ul>
                    </div>

                <?php else: ?>
                    <!-- فشل الدفع -->
                    <div class="result-icon error">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <h1 class="result-title">فشل في عملية الدفع</h1>
                    <p class="result-message">
                        <?= htmlspecialchars($message) ?><br>
                        لم يتم خصم أي مبلغ من حسابك.
                    </p>

                    <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 12px; padding: 20px; margin: 25px 0;">
                        <h4 style="color: #991b1b; margin-bottom: 10px;">الأسباب المحتملة:</h4>
                        <ul style="text-align: right; color: #991b1b; font-size: 14px; line-height: 1.8;">
                            <li>رصيد غير كافٍ</li>
                            <li>بيانات البطاقة غير صحيحة</li>
                            <li>تم إلغاء العملية</li>
                            <li>مشكلة في الاتصال</li>
                        </ul>
                    </div>

                    <div class="action-buttons">
                        <a href="checkout.php" class="btn btn-primary">
                            <i class="fas fa-redo"></i>
                            إعادة المحاولة
                        </a>
                        <a href="cart.php" class="btn btn-secondary">
                            <i class="fas fa-shopping-cart"></i>
                            العودة للسلة
                        </a>
                        <a href="contact.php" class="btn btn-secondary">
                            <i class="fas fa-headset"></i>
                            اتصل بنا
                        </a>
                    </div>

                    <div style="margin-top: 25px; padding-top: 25px; border-top: 1px solid #e2e8f0;">
                        <p style="color: #64748b; font-size: 14px;">
                            <i class="fas fa-info-circle"></i>
                            طلبك محفوظ في السلة ويمكنك إتمامه في أي وقت
                        </p>
                    </div>
                <?php endif; ?>
            </div>
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


    <script>
        // إرسال تحليلات الدفع (Google Analytics, Facebook Pixel, etc.)
        <?php if ($success && $orderId): ?>
        // Google Analytics
        if (typeof gtag !== 'undefined') {
            gtag('event', 'purchase', {
                transaction_id: '<?= $orderNumber ?>',
                value: <?= $order['total'] ?>,
                currency: 'EGP',
                items: [
                    // يمكن إضافة تفاصيل المنتجات هنا
                ]
            });
        }

        // Facebook Pixel
        if (typeof fbq !== 'undefined') {
            fbq('track', 'Purchase', {
                value: <?= $order['total'] ?>,
                currency: 'EGP'
            });
        }
        <?php endif; ?>
    </script>
</body>
</html>