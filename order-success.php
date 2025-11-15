<?php
/**
 * صفحة تأكيد نجاح الطلب
 */
require_once 'functions.php';
// التحقق من وضع الصيانة
if (getSetting('maintenance_mode', '0') == '1' && !isset($_SESSION['admin_id'])) {
    header('Location: maintenance.php');
    exit;
}
$orderNumber = $_GET['order'] ?? null;

$storeDescription = getSetting('store_description', '');
if (!$orderNumber) {
    header('Location: index.php');
    exit;
}

// جلب تفاصيل الطلب
global $pdo;
$stmt = $pdo->prepare("
    SELECT o.*, 
           COUNT(oi.id) as items_count
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    WHERE o.order_number = ?
    GROUP BY o.id
");
$stmt->execute([$orderNumber]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: index.php');
    exit;
}

$storeName = getSetting('store_name', 'متجر إلكتروني');
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تم الطلب بنجاح - <?= htmlspecialchars($storeName) ?></title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .success-page {
            min-height: 60vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 0;
        }
        .success-card {
            background: white;
            border-radius: 12px;
            padding: 3rem;
            max-width: 600px;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        .success-icon {
            width: 80px;
            height: 80px;
            background: #198754;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
            animation: scaleIn 0.5s ease;
        }
        .success-icon i {
            font-size: 2.5rem;
            color: white;
        }
        @keyframes scaleIn {
            from { transform: scale(0); }
            to { transform: scale(1); }
        }
        .order-number {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            margin: 2rem 0;
            font-size: 1.25rem;
        }
        .order-details {
            text-align: right;
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            margin: 1.5rem 0;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid #dee2e6;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 2rem;
        }
    </style>
</head>
<body>
    <header class="site-header">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <a href="index.php"><h1><?= htmlspecialchars($storeName) ?></h1></a>
                </div>
            </div>
        </div>
    </header>

    <main class="success-page">
        <div class="container">
            <div class="success-card">
                <div class="success-icon">
                    <i class="fas fa-check"></i>
                </div>
                
                <h1>تم استلام طلبك بنجاح!</h1>
                <p style="color: #6c757d; margin: 1rem 0;">
                    شكراً لك على الطلب. سنتواصل معك قريباً لتأكيد الطلب.
                </p>

                <div class="order-number">
                    <strong>رقم الطلب:</strong> <?= htmlspecialchars($order['order_number']) ?>
                </div>

                <div class="order-details">
                    <h3 style="margin-bottom: 1rem;">تفاصيل الطلب</h3>
                    
                    <div class="detail-row">
                        <span>عدد المنتجات:</span>
                        <strong><?= $order['items_count'] ?> منتج</strong>
                    </div>
                    
                    <div class="detail-row">
                        <span>المجموع:</span>
                        <strong><?= formatPrice($order['total']) ?></strong>
                    </div>
                    
                    <div class="detail-row">
                        <span>طريقة الدفع:</span>
                        <strong>
                            <?php
                            $paymentMethods = [
                                'cod' => 'الدفع عند الاستلام',
                                'visa' => 'بطاقة ائتمان',
                                'instapay' => 'InstaPay',
                                'vodafone_cash' => 'Vodafone Cash'
                            ];
                            echo $paymentMethods[$order['payment_method']] ?? $order['payment_method'];
                            ?>
                        </strong>
                    </div>
                    
                    <div class="detail-row">
                        <span>حالة الطلب:</span>
                        <strong style="color: #0d6efd;">
                            <?php
                            $statuses = [
                                'pending' => 'قيد المراجعة',
                                'confirmed' => 'مؤكد',
                                'processing' => 'قيد التجهيز',
                                'shipped' => 'تم الشحن',
                                'delivered' => 'تم التوصيل'
                            ];
                            echo $statuses[$order['status']] ?? $order['status'];
                            ?>
                        </strong>
                    </div>
                </div>

                <div style="background: #e7f3ff; padding: 1rem; border-radius: 8px; margin: 1.5rem 0; text-align: right;">
                    <strong><i class="fas fa-info-circle"></i> معلومات هامة:</strong>
                    <ul style="margin: 0.5rem 0 0 1.5rem; text-align: right;">
                        <li>سيتم التواصل معك خلال 24 ساعة لتأكيد الطلب</li>
                        <li>مدة التوصيل من 2-7 أيام حسب موقعك</li>
                        <li>يمكنك تتبع طلبك من خلال رقم الطلب</li>
                    </ul>
                </div>

                <div class="actions">
                    <a href="index.php" class="btn btn-primary">
                        <i class="fas fa-home"></i> العودة للرئيسية
                    </a>
                    <a href="track-order.php?order=<?= htmlspecialchars($order['order_number']) ?>" 
                       class="btn btn-secondary">
                        <i class="fas fa-map-marker-alt"></i> تتبع الطلب
                    </a>
                </div>

                <div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid #dee2e6;">
                    <p style="color: #6c757d; font-size: 0.9rem;">
                        لأي استفسار، تواصل معنا على:<br>
                        <strong><?= getSetting('store_phone', '') ?></strong>
                    </p>
                </div>
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
        // إرسال حدث التحويل لـ Google Analytics
        if (typeof gtag !== 'undefined') {
            gtag('event', 'purchase', {
                'transaction_id': '<?= $order['order_number'] ?>',
                'value': <?= $order['total'] ?>,
                'currency': 'EGP',
                'items': []
            });
        }
    </script>
</body>
</html>
