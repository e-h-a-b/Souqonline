<?php
require_once 'functions.php';
// التحقق من وضع الصيانة
if (getSetting('maintenance_mode', '0') == '1' && !isset($_SESSION['admin_id'])) {
    header('Location: maintenance.php');
    exit;
} 
$storeName = getSetting('store_name', 'متجر إلكتروني');

$storeDescription = getSetting('store_description', '');
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الشروط والأحكام - <?= htmlspecialchars($storeName) ?></title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .terms-page { padding: 3rem 0; }
        .terms-content { background: white; padding: 3rem; border-radius: 8px; max-width: 900px; margin: 0 auto; }
        .terms-content h1 { color: var(--primary-color); margin-bottom: 1rem; }
        .terms-content h2 { margin-top: 2rem; color: var(--text-primary); font-size: 1.5rem; }
        .terms-content p { margin-bottom: 1rem; line-height: 1.8; }
        .terms-content ul { margin: 1rem 0 1rem 2rem; }
        .terms-content ul li { margin-bottom: 0.5rem; }
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

    <main class="terms-page">
        <div class="container">
            <div class="terms-content">
                <h1>الشروط والأحكام</h1>
                <p>آخر تحديث: <?= date('Y-m-d') ?></p>

                <h2>1. القبول بالشروط</h2>
                <p>باستخدامك لموقع <?= htmlspecialchars($storeName) ?>، فإنك توافق على الالتزام بهذه الشروط والأحكام. إذا كنت لا توافق على أي جزء من هذه الشروط، يرجى عدم استخدام الموقع.</p>

                <h2>2. استخدام الموقع</h2>
                <ul>
                    <li>يجب أن تكون بعمر 18 عاماً أو أكثر للطلب من الموقع</li>
                    <li>أنت مسؤول عن الحفاظ على سرية حسابك وكلمة المرور</li>
                    <li>يجب تقديم معلومات دقيقة وصحيحة عند التسجيل والطلب</li>
                    <li>يُحظر استخدام الموقع لأي أغراض غير قانونية أو احتيالية</li>
                </ul>

                <h2>3. الطلبات والدفع</h2>
                <ul>
                    <li>جميع الطلبات خاضعة للتوافر</li>
                    <li>نحتفظ بالحق في رفض أي طلب لأي سبب</li>
                    <li>الأسعار معروضة بالجنيه المصري وتشمل الضرائب</li>
                    <li>طرق الدفع المتاحة: الدفع عند الاستلام، بطاقات الائتمان، المحافظ الإلكترونية</li>
                    <li>يجب تأكيد الطلب خلال 24 ساعة</li>
                </ul>

                <h2>4. الشحن والتوصيل</h2>
                <ul>
                    <li>نشحن إلى جميع محافظات مصر</li>
                    <li>مدة التوصيل من 2-7 أيام حسب الموقع</li>
                    <li>تكلفة الشحن تحسب حسب المحافظة</li>
                    <li>يرجى التحقق من الطلب عند الاستلام</li>
                    <li>لسنا مسؤولين عن التأخير خارج نطاق سيطرتنا</li>
                </ul>

                <h2>5. الإرجاع والاستبدال</h2>
                <ul>
                    <li>يمكنك إرجاع المنتجات خلال 14 يوماً من الاستلام</li>
                    <li>يجب أن تكون المنتجات في حالتها الأصلية مع العبوة</li>
                    <li>بعض المنتجات غير قابلة للإرجاع (مثل المنتجات الشخصية)</li>
                    <li>نتحمل تكلفة الإرجاع في حالة العيوب التصنيعية</li>
                    <li>الاسترداد المالي خلال 7-14 يوم عمل</li>
                </ul>

                <h2>6. حقوق الملكية الفكرية</h2>
                <p>جميع المحتويات على الموقع (النصوص، الصور، الشعارات) محمية بحقوق الطبع والنشر. لا يجوز نسخها أو توزيعها دون إذن كتابي.</p>

                <h2>7. إخلاء المسؤولية</h2>
                <ul>
                    <li>نسعى لتوفير معلومات دقيقة لكن لا نضمن عدم وجود أخطاء</li>
                    <li>الموقع متاح "كما هو" دون ضمانات صريحة أو ضمنية</li>
                    <li>لسنا مسؤولين عن الأضرار الناتجة عن استخدام الموقع</li>
                    <li>قد تحتوي المنتجات على اختلافات طفيفة عن الصور المعروضة</li>
                </ul>

                <h2>8. تحديد المسؤولية</h2>
                <p>في أي حال، لن تتجاوز مسؤوليتنا تجاهك قيمة المنتج المشترى. لن نكون مسؤولين عن أي أضرار غير مباشرة أو عرضية.</p>

                <h2>9. القانون الحاكم</h2>
                <p>تخضع هذه الشروط والأحكام لقوانين جمهورية مصر العربية. أي نزاعات تحل وفقاً للقانون المصري.</p>

                <h2>10. التعديلات</h2>
                <p>نحتفظ بالحق في تعديل هذه الشروط في أي وقت. التعديلات سارية فوراً بعد نشرها. استمرارك في استخدام الموقع يعني موافقتك على الشروط المعدلة.</p>

                <h2>11. الإنهاء</h2>
                <p>يمكننا إيقاف أو إنهاء وصولك للموقع في أي وقت دون إشعار مسبق إذا خالفت هذه الشروط.</p>

                <h2>12. الاتصال</h2>
                <p>لأي استفسارات حول الشروط والأحكام، يرجى التواصل معنا:</p>
                <ul>
                    <li><strong>البريد الإلكتروني:</strong> <?= getSetting('store_email', 'legal@store.com') ?></li>
                    <li><strong>الهاتف:</strong> <?= getSetting('store_phone', '+20 100 000 0000') ?></li>
                    <li><strong>العنوان:</strong> القاهرة، مصر</li>
                </ul>

                <div style="background: #e7f3ff; padding: 1.5rem; border-radius: 8px; margin-top: 2rem;">
                    <p style="margin: 0;"><i class="fas fa-info-circle"></i> <strong>تنبيه:</strong> باستخدامك للموقع وإتمام أي طلب، فإنك توافق على جميع الشروط والأحكام المذكورة أعلاه.</p>
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

</body>
</html>