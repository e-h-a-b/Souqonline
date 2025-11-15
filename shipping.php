<?php
session_start(); // إضافة session_start للتحقق من حالة المسؤول

require_once 'functions.php';

// التحقق من وضع الصيانة
if (getSetting('maintenance_mode', '0') == '1' && !isset($_SESSION['admin_id'])) {
    header('Location: maintenance.php');
    exit;
}

$storeName = getSetting('store_name', 'متجر إلكتروني');

$storeDescription = getSetting('store_description', '');
// جلب بيانات الشحن من قاعدة البيانات
global $pdo;
$stmt = $pdo->query("SELECT region, cost, delivery_time FROM shipping_rates WHERE is_active = 1 ORDER BY cost ASC");
$shippingRates = $stmt->fetchAll(PDO::FETCH_ASSOC);

// جلب إعدادات إضافية
$freeShippingThreshold = getSetting('free_shipping_threshold', '500');
$storePhone = getSetting('store_phone', 'غير متوفر');
$storeEmail = getSetting('store_email', 'غير متوفر');
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>سياسة الشحن - <?= htmlspecialchars($storeName) ?></title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .shipping-page { padding: 3rem 0; }
        .shipping-content { background: white; padding: 3rem; border-radius: 8px; max-width: 900px; margin: 0 auto; }
        .shipping-table { width: 100%; border-collapse: collapse; margin: 1.5rem 0; }
        .shipping-table th, .shipping-table td { padding: 1rem; border: 1px solid #dee2e6; text-align: right; }
        .shipping-table th { background: var(--primary-color); color: white; }
        .highlight-box { background: #e7f3ff; padding: 1.5rem; border-radius: 8px; border-right: 4px solid var(--primary-color); margin: 1.5rem 0; }
    </style>
</head>
<body>
<header class="site-header">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <a href="index.php"><h1><?= htmlspecialchars($storeName) ?></h1></a>
                </div>
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

    <main class="shipping-page">
        <div class="container">
            <div class="shipping-content">
                <h1><i class="fas fa-truck"></i> سياسة الشحن والتوصيل</h1>

                <h2>مناطق الشحن</h2>
                <p>نقوم بالشحن إلى جميع محافظات جمهورية مصر العربية.</p>

                <h2>تكلفة ومدة الشحن</h2>
                <?php if (empty($shippingRates)): ?>
                    <p style="color: #dc2626;">لا توجد بيانات شحن متاحة حاليًا. يرجى التواصل مع الدعم.</p>
                <?php else: ?>
                    <table class="shipping-table">
                        <thead>
                            <tr>
                                <th>المنطقة</th>
                                <th>تكلفة الشحن</th>
                                <th>مدة التوصيل</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($shippingRates as $rate): ?>
                                <tr>
                                    <td><?= htmlspecialchars($rate['region']) ?></td>
                                    <td><?= formatPrice($rate['cost']) ?> </td>
                                    <td><?= htmlspecialchars($rate['delivery_time']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>

                <div class="highlight-box">
                    <h3><i class="fas fa-gift"></i> شحن مجاني!</h3>
                    <p style="margin: 0;">استمتع بالشحن المجاني على جميع الطلبات التي تزيد عن <strong><?= formatPrice($freeShippingThreshold) ?> جنيه مصري</strong></p>
                </div>

                <h2>وقت معالجة الطلب</h2>
                <ul>
                    <li>يتم معالجة الطلبات خلال 24 ساعة من تأكيدها</li>
                    <li>الطلبات المستلمة بعد الساعة 3 مساءً قد تُعالج في اليوم التالي</li>
                    <li>لا نقوم بالشحن أيام الجمعة والعطلات الرسمية</li>
                </ul>

                <h2>تتبع الشحنة</h2>
                <p>بمجرد شحن طلبك، ستتلقى:</p>
                <ul>
                    <li>رسالة نصية (SMS) تحتوي على رقم تتبع الشحنة</li>
                    <li>بريد إلكتروني مع تفاصيل الشحن (إذا أدخلت بريدك)</li>
                    <li>يمكنك تتبع الطلب من صفحة <a href="track.php">تتبع الطلب</a> على موقعنا</li>
                </ul>

                <h2>استلام الطلب</h2>
                <ul>
                    <li>يتم التوصيل من الأحد إلى الخميس (ما عدا الجمعة والعطلات)</li>
                    <li>ساعات التوصيل: من 10 صباحاً حتى 8 مساءً</li>
                    <li>يُرجى التواجد في العنوان المحدد وقت التوصيل</li>
                    <li>في حالة عدم التواجد، سيتم إجراء محاولة ثانية</li>
                    <li>بعد 3 محاولات فاشلة، يُعاد الطلب للمخزن</li>
                </ul>

                <h2>فحص الطلب عند الاستلام</h2>
                <div class="highlight-box">
                    <p><strong>مهم جداً:</strong> يُرجى فحص الطلب أمام مندوب التوصيل قبل التوقيع على الاستلام. تأكد من:</p>
                    <ul style="margin: 0.5rem 0 0 1.5rem;">
                        <li>المنتجات مطابقة لما طلبته</li>
                        <li>عدم وجود أضرار أو عيوب ظاهرة</li>
                        <li>اكتمال جميع المحتويات</li>
                    </ul>
                </div>

                <h2>حالات التأخير</h2>
                <p>قد يحدث تأخير في التوصيل بسبب:</p>
                <ul>
                    <li>الظروف الجوية السيئة</li>
                    <li>الازدحامات المرورية</li>
                    <li>العطلات الرسمية</li>
                    <li>مشاكل في عنوان التوصيل</li>
                </ul>
                <p>في حالة التأخير غير المبرر (أكثر من يومين عن المدة المحددة)، يرجى التواصل معنا على <strong><?= htmlspecialchars($storePhone) ?></strong></p>

                <h2>الشحن للمناطق النائية</h2>
                <p>بعض المناطق النائية أو الجديدة قد تتطلب:</p>
                <ul>
                    <li>وقت توصيل إضافي (1-2 يوم)</li>
                    <li>رسوم شحن إضافية (يتم إبلاغك بها قبل التأكيد)</li>
                    <li>تنسيق مسبق لنقطة استلام بديلة</li>
                </ul>

                <h2>الشحن الدولي</h2>
                <p>حاليًا، نقوم بالشحن داخل مصر فقط. نعمل على توسيع خدماتنا للشحن الدولي قريبًا.</p>

                <h2>المسؤولية</h2>
                <ul>
                    <li>نحن مسؤولون عن الشحنة حتى استلامك لها</li>
                    <li>في حالة فقدان أو تلف الشحنة، نتحمل المسؤولية الكاملة</li>
                    <li>بعد التوقيع على الاستلام، تنتقل المسؤولية إليك</li>
                </ul>

                <h2>للاستفسارات</h2>
                <p>لأي استفسار عن الشحن، تواصل معنا:</p>
                <ul>
                    <li><strong>الهاتف:</strong> <?= htmlspecialchars($storePhone) ?></li>
                    <li><strong>البريد الإلكتروني:</strong> <?= htmlspecialchars($storeEmail) ?></li>
                    <li><strong>ساعات العمل:</strong> السبت - الخميس (9 ص - 6 م)</li>
                </ul>
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

</body>
</html>