<?php
/**
 * صفحة بوابة الدفع
 */
require_once 'functions.php';

// التحقق من وضع الصيانة
if (getSetting('maintenance_mode', '0') == '1' && !isset($_SESSION['admin_id'])) {
    header('Location: maintenance.php');
    exit;
}

// التحقق من وجود order_id من الجلسة أو GET
$orderId = $_GET['order'] ?? $_SESSION['pending_order_id'] ?? null;
$paymentMethod = $_GET['method'] ?? null;

$storeDescription = getSetting('store_description', '');
if (!$orderId || !$paymentMethod || !in_array($paymentMethod, ['visa', 'instapay', 'vodafone_cash', 'fawry'])) {
    header('Location: checkout.php');
    exit;
}

// جلب تفاصيل الطلب
global $pdo;
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = :id");
$stmt->bindValue(':id', (int)$orderId, PDO::PARAM_INT);
$stmt->execute();
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header('Location: checkout.php');
    exit;
}

// التحقق من أن الطلب لم يتم دفعه مسبقاً
if ($order['payment_status'] === 'paid') {
    header('Location: order-details.php?id=' . $orderId);
    exit;
}

// التحقق من أن الطلب مازال معلقاً
if ($order['payment_status'] !== 'pending') {
    header('Location: checkout.php');
    exit;
}

// الحصول على إعدادات الدفع من قاعدة البيانات
$storeName = getSetting('store_name', 'متجر إلكتروني');
$vodafoneNumber = getSetting('vodafone_cash_number', '01012345678');
$vodafoneName = getSetting('vodafone_cash_name', $storeName);
$instapayAccount = getSetting('instapay_account', 'example@instapay');
$fawryMerchantCode = getSetting('fawry_merchant_code', 'TEST123');
$visaMerchantEmail = getSetting('visa_merchant_email', 'merchant@example.com');
$sandboxMode = getSetting('sandbox_mode', '0') == '1';

$paymentError = '';
$paymentSuccess = false;

// معالجة الدفع (محاكاة فقط - في الإنتاج، استخدم تكامل حقيقي)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_payment'])) {
    // هنا يتم التكامل الحقيقي مع بوابة الدفع بناءً على $paymentMethod
    // مثل: Paytabs لـ Visa، Fawry API لـ Fawry، إلخ.
    // للاختبار، نفترض نجاح الدفع
    $paymentSuccess = true; // محاكاة نجاح الدفع

    if ($paymentSuccess) {
        // تحديث حالة الطلب إلى paid
        $stmt = $pdo->prepare("UPDATE orders SET payment_status = 'paid', updated_at = NOW() WHERE id = :id");
        $stmt->bindValue(':id', (int)$orderId, PDO::PARAM_INT);
        $stmt->execute();

        // إرسال إشعار أو بريد (اختياري)
        // sendEmail($order['customer_email'], 'تأكيد دفع الطلب', 'تم دفع طلبك بنجاح.');

        // إزالة order_id المعلق من الجلسة
        unset($_SESSION['pending_order_id']);
        
        // إفراغ سلة التسوق بعد الدفع الناجح
        unset($_SESSION['cart']);
        
        // إعادة تعيين الكوبون إن وجد
        unset($_SESSION['coupon']);
    } else {
        $paymentError = 'حدث خطأ أثناء معالجة الدفع. يرجى المحاولة مرة أخرى.';
        
        // تحديث حالة الطلب إلى فاشل
        $stmt = $pdo->prepare("UPDATE orders SET payment_status = 'failed', updated_at = NOW() WHERE id = :id");
        $stmt->bindValue(':id', (int)$orderId, PDO::PARAM_INT);
        $stmt->execute();
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>بوابة الدفع - <?= htmlspecialchars($storeName) ?></title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .payment-page { padding: 3rem 0; min-height: 60vh; }
        .payment-container { background: white; padding: 2rem; border-radius: 8px; max-width: 600px; margin: 0 auto; }
        .payment-method-icon { font-size: 3rem; color: var(--primary-color); margin-bottom: 1rem; }
        .payment-form input { margin-bottom: 1rem; }
        .qr-code { max-width: 200px; margin: 1rem auto; display: block; }
        .payment-instructions { background: #f8f9fa; padding: 1rem; border-radius: 8px; margin: 1rem 0; }
        .payment-details { background: #e8f4fd; padding: 1rem; border-radius: 8px; margin: 1rem 0; }
        .payment-details strong { color: var(--primary-color); }
        .sandbox-notice { background: #fff3cd; color: #856404; padding: 0.75rem; border-radius: 4px; margin: 1rem 0; border-right: 4px solid #ffc107; }
    </style>
</head>
<body>
    <header class="site-header">
        <div class="container">
            <div class="header-content">
                <div class="logo"><a href="index.php"><h1><?= htmlspecialchars($storeName) ?></h1></a></div>
            </div>
        </div>
    </header>

    <main class="payment-page">
        <div class="container">
            <div class="payment-container">
                <h1 class="text-center">بوابة الدفع</h1>
                <p class="text-center">الطلب رقم: <?= htmlspecialchars($order['order_number']) ?></p>
                <p class="text-center">المبلغ المطلوب: <?= formatPrice($order['total']) ?></p>

                <?php if ($sandboxMode): ?>
                    <div class="sandbox-notice">
                        <i class="fas fa-flask"></i> <strong>وضع التجربة مفعل</strong> - هذا بيئة اختبار، لن يتم خصم أي مبالغ حقيقية
                    </div>
                <?php endif; ?>

                <?php if ($paymentError): ?>
                    <div style="background: #f8d7da; color: #721c24; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                        <?= htmlspecialchars($paymentError) ?>
                    </div>
                <?php endif; ?>

                <?php if ($paymentSuccess): ?>
                    <div style="background: #d4edda; color: #155724; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                        تم الدفع بنجاح! شكراً لك.
                    </div>
                    <div class="text-center">
                        <a href="track.php?order=<?= htmlspecialchars($order['order_number']) ?>" class="btn btn-primary">
                            <i class="fas fa-search"></i> تتبع الطلب
                        </a>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-home"></i> العودة للرئيسية
                        </a>
                    </div>
                <?php else: ?>
                    <form method="post" class="payment-form">
                        <input type="hidden" name="process_payment" value="1">
                        
                        <?php switch ($paymentMethod): 
                            case 'visa': ?>
                                <div class="text-center">
                                    <i class="fab fa-cc-visa payment-method-icon"></i>
                                    <h2>الدفع ببطاقة فيزا / ماستركارد</h2>
                                </div>
                                
                                <div class="payment-details">
                                    <p><i class="fas fa-store"></i> <strong>التاجر:</strong> <?= htmlspecialchars($storeName) ?></p>
                                    <p><i class="fas fa-envelope"></i> <strong>البريد:</strong> <?= htmlspecialchars($visaMerchantEmail) ?></p>
                                </div>

                                <div class="form-group">
                                    <label for="card_number">رقم البطاقة</label>
                                    <input type="text" id="card_number" name="card_number" placeholder="XXXX XXXX XXXX XXXX" required>
                                </div>
                                <div class="form-group">
                                    <label for="card_holder">اسم صاحب البطاقة</label>
                                    <input type="text" id="card_holder" name="card_holder" required>
                                </div>
                                <div style="display: flex; gap: 1rem;">
                                    <div class="form-group" style="flex: 1;">
                                        <label for="expiry_date">تاريخ الانتهاء</label>
                                        <input type="text" id="expiry_date" name="expiry_date" placeholder="MM/YY" required>
                                    </div>
                                    <div class="form-group" style="flex: 1;">
                                        <label for="cvv">CVV</label>
                                        <input type="text" id="cvv" name="cvv" placeholder="XXX" required>
                                    </div>
                                </div>
                                <p class="payment-instructions">
                                    <i class="fas fa-info-circle"></i> سيتم خصم المبلغ من بطاقتك لصالح <?= htmlspecialchars($storeName) ?>.
                                    <br>هذا تكامل مع Paytabs أو PayMob (محاكاة).
                                </p>
                            <?php break; ?>

                            <?php case 'instapay': ?>
                                <div class="text-center">
                                    <i class="fas fa-mobile-alt payment-method-icon"></i>
                                    <h2>الدفع عبر InstaPay</h2>
                                </div>
                                
                                <div class="payment-details">
                                    <p><i class="fas fa-user"></i> <strong>اسم المستلم:</strong> <?= htmlspecialchars($storeName) ?></p>
                                    <p><i class="fas fa-at"></i> <strong>الحساب:</strong> <?= htmlspecialchars($instapayAccount) ?></p>
                                    <p><i class="fas fa-hashtag"></i> <strong>المرجع:</strong> <?= htmlspecialchars($order['order_number']) ?></p>
                                </div>
                                
                                <p class="payment-instructions">
                                    <i class="fas fa-info-circle"></i> افتح تطبيق InstaPay وادفع إلى الحساب أعلاه.
                                    <br>استخدم رقم الطلب كمرجع للدفع.
                                    <br>بعد إتمام التحويل، اضغط "تأكيد الدفع".
                                </p>
                            <?php break; ?>

                            <?php case 'vodafone_cash': ?>
                                <div class="text-center">
                                    <i class="fab fa-vimeo-v payment-method-icon"></i>
                                    <h2>الدفع عبر Vodafone Cash</h2>
                                </div>
                                
                                <div class="payment-details">
                                    <p><i class="fas fa-phone"></i> <strong>رقم الهاتف:</strong> <?= htmlspecialchars($vodafoneNumber) ?></p>
                                    <p><i class="fas fa-user"></i> <strong>اسم المستلم:</strong> <?= htmlspecialchars($vodafoneName) ?></p>
                                    <p><i class="fas fa-hashtag"></i> <strong>المرجع:</strong> <?= htmlspecialchars($order['order_number']) ?></p>
                                </div>
                                
                                <p class="payment-instructions">
                                    <i class="fas fa-info-circle"></i> اذهب إلى أقرب فرع Vodafone أو استخدم التطبيق للتحويل إلى الرقم أعلاه.
                                    <br>استخدم رقم الطلب كمرجع للدفع.
                                    <br>بعد الدفع، أدخل رقم العملية هنا:
                                </p>
                                <div class="form-group">
                                    <label for="transaction_id">رقم العملية</label>
                                    <input type="text" id="transaction_id" name="transaction_id" required>
                                </div>
                            <?php break; ?>

                            <?php case 'fawry': ?>
                                <div class="text-center">
                                    <i class="fas fa-barcode payment-method-icon"></i>
                                    <h2>الدفع عبر Fawry</h2>
                                </div>
                                
                                <div class="payment-details">
                                    <p><i class="fas fa-store"></i> <strong>التاجر:</strong> <?= htmlspecialchars($storeName) ?></p>
                                    <p><i class="fas fa-hashtag"></i> <strong>كود التاجر:</strong> <?= htmlspecialchars($fawryMerchantCode) ?></p>
                                </div>
                                
                                <p class="payment-instructions">
                                    <i class="fas fa-info-circle"></i> اذهب إلى أقرب منفذ Fawry وادفع باستخدام الكود التالي:
                                    <br><strong style="font-size: 1.2rem;">كود الدفع: <?= strtoupper(bin2hex(random_bytes(4))) ?></strong>
                                    <br>أو استخدم تطبيق Fawry للدفع عبر الإنترنت.
                                    <br>بعد الدفع، اضغط "تأكيد الدفع".
                                </p>
                            <?php break; ?>
                        <?php endswitch; ?>

                        <div style="display: flex; gap: 1rem; margin-top: 1rem;">
                            <button type="submit" class="btn btn-primary" style="flex: 1;">
                                <i class="fas fa-credit-card"></i> تأكيد الدفع
                            </button>
                            <a href="checkout.php?cancel=1" class="btn btn-secondary" style="flex: 1; text-align: center;">
                                <i class="fas fa-times"></i> إلغاء
                            </a>
                        </div>
                    </form>
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


    <script src="assets/js/app.js"></script>
</body>
</html>