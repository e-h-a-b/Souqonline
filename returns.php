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
    <title>سياسة الإرجاع والاستبدال - <?= htmlspecialchars($storeName) ?></title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .returns-page { padding: 3rem 0; }
        .returns-content { background: white; padding: 3rem; border-radius: 8px; max-width: 900px; margin: 0 auto; }
        .step-box { background: #f8f9fa; padding: 1.5rem; border-radius: 8px; margin: 1rem 0; border-right: 4px solid var(--primary-color); }
        .warning-box { background: #fff3cd; padding: 1.5rem; border-radius: 8px; margin: 1.5rem 0; border-right: 4px solid #ffc107; }
        .success-box { background: #d1e7dd; padding: 1.5rem; border-radius: 8px; margin: 1.5rem 0; border-right: 4px solid #198754; }
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

    <main class="returns-page">
        <div class="container">
            <div class="returns-content">
                <h1><i class="fas fa-undo"></i> سياسة الإرجاع والاستبدال</h1>

                <p>نحن في <?= htmlspecialchars($storeName) ?> نسعى لرضاك التام. إذا لم تكن راضياً عن مشترياتك، يمكنك إرجاعها أو استبدالها وفقاً للشروط التالية:</p>

                <h2>المدة الزمنية</h2>
                <div class="success-box">
                    <p style="margin: 0;"><i class="fas fa-calendar-check"></i> <strong>14 يوماً</strong> من تاريخ استلام المنتج لطلب الإرجاع أو الاستبدال</p>
                </div>

                <h2>شروط الإرجاع</h2>
                <p>لقبول طلب الإرجاع، يجب أن تتوفر الشروط التالية:</p>
                <ul>
                    <li><i class="fas fa-check-circle" style="color: #198754;"></i> المنتج في حالته الأصلية دون استخدام</li>
                    <li><i class="fas fa-check-circle" style="color: #198754;"></i> التغليف الأصلي سليم وغير تالف</li>
                    <li><i class="fas fa-check-circle" style="color: #198754;"></i> جميع الملحقات والهدايا المجانية (إن وجدت)</li>
                    <li><i class="fas fa-check-circle" style="color: #198754;"></i> فاتورة الشراء أو إيصال الطلب</li>
                    <li><i class="fas fa-check-circle" style="color: #198754;"></i> الملصقات والبطاقات الأصلية متصلة بالمنتج</li>
                </ul>

                <h2>المنتجات غير القابلة للإرجاع</h2>
                <div class="warning-box">
                    <p><strong>لا يمكن إرجاع المنتجات التالية لأسباب صحية:</strong></p>
                    <ul style="margin: 0.5rem 0 0 1.5rem;">
                        <li>منتجات العناية الشخصية والتجميل المفتوحة</li>
                        <li>الملابس الداخلية والجوارب</li>
                        <li>منتجات الطعام والمشروبات</li>
                        <li>المنتجات المصنوعة خصيصاً أو المخصصة</li>
                        <li>المنتجات المخفضة أو في تصفية (ما لم تكن معيبة)</li>
                    </ul>
                </div>

                <h2>خطوات الإرجاع</h2>
                
                <div class="step-box">
                    <h3>الخطوة 1: التواصل معنا</h3>
                    <p>اتصل بنا خلال 14 يوماً من الاستلام عبر:</p>
                    <ul>
                        <li><strong>الهاتف:</strong> <?= getSetting('store_phone') ?></li>
                        <li><strong>البريد الإلكتروني:</strong> <?= getSetting('store_email') ?></li>
                        <li><strong>واتساب:</strong> <?= getSetting('whatsapp_number') ?></li>
                    </ul>
                </div>

                <div class="step-box">
                    <h3>الخطوة 2: تقديم المعلومات</h3>
                    <p>قدم لنا المعلومات التالية:</p>
                    <ul>
                        <li>رقم الطلب</li>
                        <li>تفاصيل المنتج المراد إرجاعه</li>
                        <li>سبب الإرجاع</li>
                        <li>صور للمنتج (في حالة العيب)</li>
                    </ul>
                </div>

                <div class="step-box">
                    <h3>الخطوة 3: الموافقة والاستلام</h3>
                    <p>بعد مراجعة طلبك:</p>
                    <ul>
                        <li>سنوافق على الإرجاع ونحدد موعد استلام المنتج</li>
                        <li>أو نطلب معلومات إضافية إذا لزم الأمر</li>
                    </ul>
                </div>

                <div class="step-box">
                    <h3>الخطوة 4: الفحص والاسترداد</h3>
                    <p>بعد استلام المنتج:</p>
                    <ul>
                        <li>سنفحص المنتج للتأكد من مطابقته للشروط</li>
                        <li>يتم الاسترداد المالي خلال 7-14 يوم عمل</li>
                        <li>أو يتم إرسال المنتج البديل (في حالة الاستبدال)</li>
                    </ul>
                </div>

                <h2>تكلفة الإرجاع</h2>
                <table style="width: 100%; border-collapse: collapse; margin: 1rem 0;">
                    <thead>
                        <tr style="background: var(--primary-color); color: white;">
                            <th style="padding: 1rem; border: 1px solid #dee2e6;">الحالة</th>
                            <th style="padding: 1rem; border: 1px solid #dee2e6;">من يتحمل التكلفة</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td style="padding: 1rem; border: 1px solid #dee2e6;">عيب تصنيعي</td>
                            <td style="padding: 1rem; border: 1px solid #dee2e6;"><strong style="color: #198754;">المتجر</strong></td>
                        </tr>
                        <tr>
                            <td style="padding: 1rem; border: 1px solid #dee2e6;">منتج خاطئ</td>
                            <td style="padding: 1rem; border: 1px solid #dee2e6;"><strong style="color: #198754;">المتجر</strong></td>
                        </tr>
                        <tr>
                            <td style="padding: 1rem; border: 1px solid #dee2e6;">تلف أثناء الشحن</td>
                            <td style="padding: 1rem; border: 1px solid #dee2e6;"><strong style="color: #198754;">المتجر</strong></td>
                        </tr>
                        <tr>
                            <td style="padding: 1rem; border: 1px solid #dee2e6;">تغيير الرأي</td>
                            <td style="padding: 1rem; border: 1px solid #dee2e6;"><strong style="color: #dc3545;">العميل</strong></td>
                        </tr>
                        <tr>
                            <td style="padding: 1rem; border: 1px solid #dee2e6;">طلب مقاس/لون مختلف</td>
                            <td style="padding: 1rem; border: 1px solid #dee2e6;"><strong style="color: #dc3545;">العميل</strong></td>
                        </tr>
                    </tbody>
                </table>

                <h2>طرق الاسترداد المالي</h2>
                <ul>
                    <li><strong>الدفع عند الاستلام:</strong> إيداع نقدي أو تحويل بنكي</li>
                    <li><strong>بطاقة ائتمان:</strong> استرداد على نفس البطاقة</li>
                    <li><strong>المحفظة الإلكترونية:</strong> استرداد على نفس المحفظة</li>
                    <li><strong>رصيد في المتجر:</strong> يمكن استخدامه في مشتريات مستقبلية (مع مكافأة 5%)</li>
                </ul>

                <h2>الاستبدال</h2>
                <p>يمكنك استبدال المنتج بـ:</p>
                <ul>
                    <li>نفس المنتج بمقاس أو لون مختلف</li>
                    <li>منتج آخر بنفس القيمة أو أعلى (مع دفع الفرق)</li>
                    <li>رصيد في المتجر لاستخدامه لاحقاً</li>
                </ul>

                <h2>حالات خاصة</h2>
                
                <h3>المنتجات المعيبة</h3>
                <p>إذا استلمت منتجاً معيباً:</p>
                <ul>
                    <li>أبلغنا فوراً (يفضل خلال 48 ساعة)</li>
                    <li>التقط صوراً واضحة للعيب</li>
                    <li>سنقوم بإرسال بديل أو استرداد كامل المبلغ</li>
                    <li>نتحمل جميع تكاليف الإرجاع</li>
                </ul>

                <h3>المنتج التالف أثناء الشحن</h3>
                <p>إذا وصلك المنتج تالفاً:</p>
                <ul>
                    <li>لا توقع على الاستلام إذا لاحظت التلف</li>
                    <li>التقط صوراً للعبوة والمنتج</li>
                    <li>اتصل بنا فوراً</li>
                    <li>سنرسل بديلاً أو نسترد المبلغ كاملاً</li>
                </ul>

                <h2>الضمان</h2>
                <p>بعض المنتجات تأتي مع ضمان من الشركة المصنعة:</p>
                <ul>
                    <li>يختلف نوع ومدة الضمان حسب المنتج</li>
                    <li>تفاصيل الضمان موضحة في صفحة المنتج</li>
                    <li>يمكنك مراسلتنا لتفعيل الضمان</li>
                </ul>

                <div class="success-box">
                    <h3><i class="fas fa-headset"></i> دعم العملاء</h3>
                    <p>فريقنا جاهز لمساعدتك في أي استفسار:</p>
                    <ul style="margin: 0.5rem 0 0 1.5rem;">
                        <li><strong>الهاتف:</strong> <?= getSetting('store_phone') ?></li>
                        <li><strong>البريد الإلكتروني:</strong> <?= getSetting('store_email') ?></li>
                        <li><strong>ساعات العمل:</strong> السبت - الخميس (9 ص - 6 م)</li>
                    </ul>
                </div>

                <p style="margin-top: 2rem; color: var(--text-secondary); font-size: 0.9rem;">
                    <strong>ملاحظة:</strong> نحتفظ بالحق في رفض الإرجاع إذا لم تتوفر الشروط المذكورة أعلاه.
                </p>
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