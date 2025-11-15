<?php
/**
 * صفحة سياسة الخصوصية
 */
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
    <title>سياسة الخصوصية - <?= htmlspecialchars($storeName) ?></title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .policy-page { padding: 3rem 0; background: #f5f7fa; }
        .policy-content { background: white; padding: 3rem; border-radius: 12px; max-width: 900px; margin: 0 auto; box-shadow: 0 2px 12px rgba(0,0,0,0.08); }
        .policy-content h1 { color: var(--primary-color); margin-bottom: 1rem; font-size: 2.5rem; }
        .policy-content h2 { margin-top: 2.5rem; margin-bottom: 1rem; color: var(--text-primary); font-size: 1.5rem; padding-bottom: 0.5rem; border-bottom: 2px solid #e9ecef; }
        .policy-content p { margin-bottom: 1rem; line-height: 1.8; color: #495057; }
        .policy-content ul { margin: 1rem 0 1rem 2rem; }
        .policy-content ul li { margin-bottom: 0.75rem; line-height: 1.7; }
        .policy-content strong { color: var(--text-primary); }
        .last-updated { color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 2rem; padding: 0.75rem; background: #e7f3ff; border-radius: 6px; }
        .info-box { background: #e7f3ff; padding: 1.5rem; border-radius: 8px; border-right: 4px solid var(--primary-color); margin: 1.5rem 0; }
        .warning-box { background: #fff3cd; padding: 1.5rem; border-radius: 8px; border-right: 4px solid #ffc107; margin: 1.5rem 0; }
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

    <div class="breadcrumb">
        <div class="container">
            <a href="index.php">الرئيسية</a>
            <i class="fas fa-chevron-left"></i>
            <span>سياسة الخصوصية</span>
        </div>
    </div>

    <main class="policy-page">
        <div class="container">
            <div class="policy-content">
                <h1><i class="fas fa-shield-alt"></i> سياسة الخصوصية</h1>
                <p class="last-updated">
                    <i class="fas fa-calendar-alt"></i> آخر تحديث: <?= date('d F Y', strtotime('2025-01-01')) ?>
                </p>

                <p>نحن في <?= htmlspecialchars($storeName) ?> نحترم خصوصيتك ونلتزم بحماية بياناتك الشخصية. توضح هذه السياسة كيفية جمع واستخدام وحماية معلوماتك الشخصية عند استخدامك لموقعنا الإلكتروني وخدماتنا.</p>

                <div class="info-box">
                    <p style="margin: 0;"><strong><i class="fas fa-info-circle"></i> التزامنا:</strong> نحن ملتزمون بحماية خصوصيتك وفقاً لقوانين حماية البيانات المصرية والدولية.</p>
                </div>

                <h2><i class="fas fa-database"></i> المعلومات التي نجمعها</h2>
                <p>نقوم بجمع الأنواع التالية من المعلومات:</p>
                
                <h3>1. المعلومات الشخصية</h3>
                <ul>
                    <li><strong>معلومات الحساب:</strong> الاسم الكامل، البريد الإلكتروني، رقم الهاتف</li>
                    <li><strong>معلومات التوصيل:</strong> العنوان الكامل، المحافظة، المدينة، رقم المبنى</li>
                    <li><strong>معلومات الدفع:</strong> نوع طريقة الدفع (لا نخزن بيانات البطاقات الائتمانية)</li>
                </ul>

                <h3>2. معلومات الطلب</h3>
                <ul>
                    <li>تفاصيل المنتجات المشتراة</li>
                    <li>سجل الطلبات والمشتريات</li>
                    <li>تفضيلات الشراء</li>
                    <li>ملاحظاتك وتعليقاتك على الطلبات</li>
                </ul>

                <h3>3. المعلومات التقنية</h3>
                <ul>
                    <li>عنوان IP الخاص بك</li>
                    <li>نوع المتصفح ونظام التشغيل</li>
                    <li>سلوك التصفح على الموقع</li>
                    <li>الصفحات التي تزورها ومدة الزيارة</li>
                    <li>الجهاز المستخدم (كمبيوتر، موبايل، تابلت)</li>
                </ul>

                <h3>4. ملفات تعريف الارتباط (Cookies)</h3>
                <ul>
                    <li>معلومات الجلسة والتفضيلات</li>
                    <li>محتويات سلة التسوق</li>
                    <li>إعدادات اللغة والعرض</li>
                </ul>

                <h2><i class="fas fa-bullseye"></i> كيف نستخدم معلوماتك</h2>
                <p>نستخدم المعلومات التي نجمعها للأغراض التالية:</p>

                <h3>أغراض أساسية:</h3>
                <ul>
                    <li><strong>معالجة الطلبات:</strong> لإتمام وتنفيذ طلباتك</li>
                    <li><strong>التواصل:</strong> للتواصل معك بشأن طلباتك وحالة التوصيل</li>
                    <li><strong>الدفع:</strong> لمعالجة عمليات الدفع بشكل آمن</li>
                    <li><strong>خدمة العملاء:</strong> للرد على استفساراتك وحل المشاكل</li>
                </ul>

                <h3>أغراض تحسينية:</h3>
                <ul>
                    <li>تحسين تجربة المستخدم على الموقع</li>
                    <li>تطوير منتجاتنا وخدماتنا</li>
                    <li>تخصيص المحتوى والعروض حسب اهتماماتك</li>
                    <li>إجراء تحليلات إحصائية لفهم احتياجات العملاء</li>
                </ul>

                <h3>أغراض تسويقية (بموافقتك فقط):</h3>
                <ul>
                    <li>إرسال النشرات الإخبارية والعروض الخاصة</li>
                    <li>إبلاغك بالمنتجات الجديدة</li>
                    <li>تقديم عروض مخصصة بناءً على اهتماماتك</li>
                </ul>

                <h3>أغراض أمنية:</h3>
                <ul>
                    <li>منع الاحتيال والأنشطة المشبوهة</li>
                    <li>حماية أمن الموقع والمستخدمين</li>
                    <li>الامتثال للمتطلبات القانونية والتنظيمية</li>
                </ul>

                <h2><i class="fas fa-user-shield"></i> كيف نحمي بياناتك</h2>
                <p>نتخذ إجراءات أمنية صارمة لحماية معلوماتك الشخصية:</p>

                <ul>
                    <li><strong>التشفير:</strong> استخدام تشفير SSL/TLS لجميع البيانات المنقولة عبر الإنترنت</li>
                    <li><strong>الخوادم الآمنة:</strong> تخزين البيانات على خوادم محمية بجدران نارية متقدمة</li>
                    <li><strong>الوصول المحدود:</strong> فقط الموظفون المصرح لهم يمكنهم الوصول للبيانات</li>
                    <li><strong>المراقبة المستمرة:</strong> مراقبة الأنظمة على مدار الساعة للكشف عن أي اختراقات</li>
                    <li><strong>النسخ الاحتياطي:</strong> نسخ احتياطية منتظمة للبيانات لمنع الفقدان</li>
                    <li><strong>التحديثات الأمنية:</strong> تحديث مستمر للبرامج والأنظمة الأمنية</li>
                    <li><strong>PCI DSS:</strong> الامتثال لمعايير أمان بطاقات الدفع</li>
                </ul>

                <div class="warning-box">
                    <p><strong><i class="fas fa-exclamation-triangle"></i> تنبيه مهم:</strong> لا نخزن أبداً بيانات بطاقتك الائتمانية الكاملة على خوادمنا. يتم معالجة جميع معاملات البطاقات عبر بوابات دفع آمنة ومعتمدة.</p>
                </div>

                <h2><i class="fas fa-share-alt"></i> مشاركة المعلومات</h2>
                <p><strong>لا نبيع أو نؤجر معلوماتك الشخصية لأطراف ثالثة.</strong> قد نشارك بياناتك فقط مع:</p>

                <h3>1. مزودو الخدمات الأساسية:</h3>
                <ul>
                    <li><strong>شركات الشحن:</strong> لتوصيل طلباتك إلى عنوانك</li>
                    <li><strong>معالجات الدفع:</strong> لإتمام المعاملات المالية بشكل آمن</li>
                    <li><strong>خدمات الاستضافة:</strong> لتشغيل الموقع وحفظ البيانات</li>
                    <li><strong>خدمات البريد الإلكتروني:</strong> لإرسال تأكيدات الطلبات والإشعارات</li>
                </ul>

                <h3>2. الجهات القانونية:</h3>
                <ul>
                    <li>عند وجود طلب قانوني من السلطات المختصة</li>
                    <li>لحماية حقوقنا القانونية</li>
                    <li>للامتثال للقوانين واللوائح</li>
                    <li>لمنع الاحتيال أو الأنشطة غير القانونية</li>
                </ul>

                <p><strong>جميع الأطراف الثالثة ملزمة بحماية بياناتك وفقاً لسياسات خصوصية صارمة.</strong></p>

                <h2><i class="fas fa-cookie-bite"></i> ملفات تعريف الارتباط (Cookies)</h2>
                <p>نستخدم ملفات تعريف الارتباط لتحسين تجربتك:</p>

                <h3>أنواع Cookies المستخدمة:</h3>
                <ul>
                    <li><strong>Cookies ضرورية:</strong> مطلوبة لعمل الموقع الأساسي (تسجيل الدخول، السلة)</li>
                    <li><strong>Cookies وظيفية:</strong> لحفظ تفضيلاتك وإعدادات اللغة</li>
                    <li><strong>Cookies تحليلية:</strong> لفهم كيفية استخدامك للموقع (Google Analytics)</li>
                    <li><strong>Cookies تسويقية:</strong> لعرض إعلانات مخصصة (بموافقتك)</li>
                </ul>

                <h3>إدارة Cookies:</h3>
                <p>يمكنك التحكم في ملفات تعريف الارتباط من خلال:</p>
                <ul>
                    <li>إعدادات متصفحك (Chrome، Firefox، Safari، Edge)</li>
                    <li>حذف Cookies الموجودة</li>
                    <li>رفض Cookies غير الضرورية</li>
                </ul>

                <div class="info-box">
                    <p style="margin: 0;"><strong>ملاحظة:</strong> رفض بعض Cookies قد يؤثر على وظائف معينة في الموقع.</p>
                </div>

                <h2><i class="fas fa-user-check"></i> حقوقك</h2>
                <p>وفقاً لقوانين حماية البيانات، لديك الحقوق التالية:</p>

                <ul>
                    <li><strong>حق الوصول:</strong> طلب نسخة من بياناتك الشخصية</li>
                    <li><strong>حق التصحيح:</strong> تصحيح أي معلومات غير دقيقة</li>
                    <li><strong>حق الحذف:</strong> طلب حذف بياناتك (في ظروف معينة)</li>
                    <li><strong>حق التقييد:</strong> تقييد معالجة بياناتك</li>
                    <li><strong>حق الاعتراض:</strong> الاعتراض على استخدام بياناتك لأغراض معينة</li>
                    <li><strong>حق نقل البيانات:</strong> الحصول على بياناتك بصيغة قابلة للنقل</li>
                    <li><strong>حق سحب الموافقة:</strong> سحب موافقتك في أي وقت</li>
                </ul>

                <p><strong>لممارسة حقوقك، تواصل معنا على:</strong> <?= getSetting('store_email', 'privacy@store.com') ?></p>

                <h2><i class="fas fa-child"></i> خصوصية الأطفال</h2>
                <p>خدماتنا غير موجهة للأطفال دون سن 18 عاماً. لا نجمع معلومات من الأطفال عن قصد. إذا علمنا أننا جمعنا بيانات طفل دون موافقة الوالدين، سنحذفها فوراً. إذا كنت والداً واكتشفت أن طفلك قدم معلومات شخصية، يرجى التواصل معنا.</p>

                <h2><i class="fas fa-clock"></i> الاحتفاظ بالبيانات</h2>
                <p>نحتفظ بمعلوماتك الشخصية للمدة اللازمة لتحقيق الأغراض المذكورة:</p>
                <ul>
                    <li><strong>بيانات الحساب:</strong> طالما حسابك نشط</li>
                    <li><strong>سجل الطلبات:</strong> 7 سنوات (للأغراض الضريبية والقانونية)</li>
                    <li><strong>بيانات التسويق:</strong> حتى سحب موافقتك</li>
                    <li><strong>السجلات التقنية:</strong> 90 يوماً</li>
                </ul>

                <h2><i class="fas fa-sync-alt"></i> التحديثات على السياسة</h2>
                <p>قد نحدث سياسة الخصوصية من وقت لآخر لتعكس التغييرات في ممارساتنا أو القوانين. عند إجراء تغييرات جوهرية:</p>
                <ul>
                    <li>سننشر النسخة المحدثة على هذه الصفحة</li>
                    <li>سنغير تاريخ "آخر تحديث" في أعلى الصفحة</li>
                    <li>قد نرسل إشعاراً عبر البريد الإلكتروني للتغييرات المهمة</li>
                    <li>نشجعك على مراجعة هذه السياسة بانتظام</li>
                </ul>

                <h2><i class="fas fa-globe"></i> النقل الدولي للبيانات</h2>
                <p>خوادمنا موجودة في مصر. إذا كنت تصل إلى موقعنا من خارج مصر، يُرجى ملاحظة أن بياناتك قد تُنقل إلى مصر وتُعالج هناك وفقاً لهذه السياسة والقوانين المصرية.</p>

                <h2><i class="fas fa-phone"></i> اتصل بنا</h2>
                <p>إذا كان لديك أي أسئلة أو مخاوف بشأن سياسة الخصوصية أو ممارسات معالجة البيانات، تواصل معنا:</p>
                
                <div style="background: #f8f9fa; padding: 1.5rem; border-radius: 8px; margin-top: 1rem;">
                    <ul style="margin: 0; list-style: none;">
                        <li style="margin-bottom: 0.75rem;">
                            <i class="fas fa-envelope" style="color: var(--primary-color); width: 20px;"></i>
                            <strong>البريد الإلكتروني:</strong> <?= getSetting('store_email', 'privacy@store.com') ?>
                        </li>
                        <li style="margin-bottom: 0.75rem;">
                            <i class="fas fa-phone" style="color: var(--primary-color); width: 20px;"></i>
                            <strong>الهاتف:</strong> <?= getSetting('store_phone', '+20 100 000 0000') ?>
                        </li>
                        <li style="margin-bottom: 0.75rem;">
                            <i class="fas fa-map-marker-alt" style="color: var(--primary-color); width: 20px;"></i>
                            <strong>العنوان:</strong> القاهرة، جمهورية مصر العربية
                        </li>
                        <li>
                            <i class="fas fa-clock" style="color: var(--primary-color); width: 20px;"></i>
                            <strong>ساعات العمل:</strong> السبت - الخميس (9 صباحاً - 6 مساءً)
                        </li>
                    </ul>
                </div>

                <div style="background: #d1e7dd; padding: 1.5rem; border-radius: 8px; margin-top: 2rem; border-right: 4px solid #198754;">
                    <p style="margin: 0;"><strong><i class="fas fa-check-circle"></i> التزامنا تجاهك:</strong> نحن ملتزمون بحماية خصوصيتك وضمان أمان بياناتك. باستخدامك لموقعنا، فإنك توافق على سياسة الخصوصية هذه.</p>
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


    <script src="assets/js/app.js"></script>
</body>
</html>