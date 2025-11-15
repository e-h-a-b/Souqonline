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
    <title>الأسئلة الشائعة - <?= htmlspecialchars($storeName) ?></title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .faq-page { padding: 3rem 0; }
        .faq-content { max-width: 900px; margin: 0 auto; }
        .faq-category { background: white; padding: 2rem; border-radius: 8px; margin-bottom: 2rem; }
        .faq-category h2 { color: var(--primary-color); margin-bottom: 1.5rem; }
        .faq-item { border-bottom: 1px solid #eee; padding: 1.5rem 0; }
        .faq-item:last-child { border-bottom: none; }
        .faq-question { font-weight: 600; font-size: 1.1rem; margin-bottom: 0.75rem; cursor: pointer; display: flex; justify-content: space-between; align-items: center; }
        .faq-question:hover { color: var(--primary-color); }
        .faq-answer { color: var(--text-secondary); line-height: 1.8; display: none; }
        .faq-answer.show { display: block; animation: fadeIn 0.3s; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        .faq-icon { transition: transform 0.3s; }
        .faq-icon.rotate { transform: rotate(180deg); }
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

    <main class="faq-page">
        <div class="container">
            <h1 class="text-center">الأسئلة الشائعة</h1>
            <p class="text-center text-secondary" style="margin-bottom: 3rem;">إجابات سريعة لأسئلتك الأكثر شيوعاً</p>

            <div class="faq-content">
                <!-- عن الطلبات -->
                <div class="faq-category">
                    <h2><i class="fas fa-shopping-cart"></i> عن الطلبات</h2>
                    
                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleAnswer(this)">
                            <span>كيف أقوم بتقديم طلب؟</span>
                            <i class="fas fa-chevron-down faq-icon"></i>
                        </div>
                        <div class="faq-answer">
                            <p>للطلب من موقعنا:</p>
                            <ol>
                                <li>تصفح المنتجات واختر ما يعجبك</li>
                                <li>اضغط على "أضف للسلة"</li>
                                <li>اذهب إلى السلة واضغط "إتمام الطلب"</li>
                                <li>املأ بياناتك وعنوان التوصيل</li>
                                <li>اختر طريقة الدفع وأكمل الطلب</li>
                            </ol>
                        </div>
                    </div>

                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleAnswer(this)">
                            <span>هل يمكنني تعديل طلبي بعد إرساله؟</span>
                            <i class="fas fa-chevron-down faq-icon"></i>
                        </div>
                        <div class="faq-answer">
                            <p>يمكنك تعديل أو إلغاء طلبك خلال ساعة من تقديمه. بعد ذلك، يدخل الطلب مرحلة التجهيز ولا يمكن تعديله. تواصل معنا فوراً على <?= getSetting('store_phone') ?> للمساعدة.</p>
                        </div>
                    </div>

                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleAnswer(this)">
                            <span>كيف أتتبع طلبي؟</span>
                            <i class="fas fa-chevron-down faq-icon"></i>
                        </div>
                        <div class="faq-answer">
                            <p>يمكنك تتبع طلبك من خلال:</p>
                            <ul>
                                <li>صفحة "تتبع الطلب" باستخدام رقم الطلب</li>
                                <li>الرسالة النصية التي تصلك على هاتفك</li>
                                <li>البريد الإلكتروني (إذا أدخلت بريدك)</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- الشحن والتوصيل -->
                <div class="faq-category">
                    <h2><i class="fas fa-truck"></i> الشحن والتوصيل</h2>
                    
                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleAnswer(this)">
                            <span>كم تستغرق مدة التوصيل؟</span>
                            <i class="fas fa-chevron-down faq-icon"></i>
                        </div>
                        <div class="faq-answer">
                            <p>تختلف مدة التوصيل حسب موقعك:</p>
                            <ul>
                                <li><strong>القاهرة والجيزة:</strong> 2-3 أيام عمل</li>
                                <li><strong>الإسكندرية:</strong> 3-4 أيام عمل</li>
                                <li><strong>باقي المحافظات:</strong> 4-7 أيام عمل</li>
                            </ul>
                        </div>
                    </div>

                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleAnswer(this)">
                            <span>كم تكلفة الشحن؟</span>
                            <i class="fas fa-chevron-down faq-icon"></i>
                        </div>
                        <div class="faq-answer">
                            <p>تكلفة الشحن:</p>
                            <ul>
                                <li>القاهرة والجيزة: 30 ج.م</li>
                                <li>الإسكندرية: 50 ج.م</li>
                                <li>باقي المحافظات: 70 ج.م</li>
                                <li><strong>شحن مجاني للطلبات أكثر من 500 ج.م</strong></li>
                            </ul>
                        </div>
                    </div>

                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleAnswer(this)">
                            <span>هل تشحنون خارج مصر؟</span>
                            <i class="fas fa-chevron-down faq-icon"></i>
                        </div>
                        <div class="faq-answer">
                            <p>حالياً، نشحن فقط داخل مصر. نعمل على توسيع خدماتنا قريباً.</p>
                        </div>
                    </div>
                </div>

                <!-- الدفع -->
                <div class="faq-category">
                    <h2><i class="fas fa-credit-card"></i> طرق الدفع</h2>
                    
                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleAnswer(this)">
                            <span>ما هي طرق الدفع المتاحة؟</span>
                            <i class="fas fa-chevron-down faq-icon"></i>
                        </div>
                        <div class="faq-answer">
                            <p>نوفر طرق دفع متعددة:</p>
                            <ul>
                                <li><strong>الدفع عند الاستلام:</strong> ادفع نقداً عند استلام الطلب</li>
                                <li><strong>بطاقات الائتمان:</strong> Visa, Mastercard, Meeza</li>
                                <li><strong>InstaPay:</strong> الدفع الفوري عبر الموبايل</li>
                                <li><strong>Vodafone Cash:</strong> من محفظتك الإلكترونية</li>
                            </ul>
                        </div>
                    </div>

                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleAnswer(this)">
                            <span>هل الدفع الإلكتروني آمن؟</span>
                            <i class="fas fa-chevron-down faq-icon"></i>
                        </div>
                        <div class="faq-answer">
                            <p>نعم، جميع عمليات الدفع الإلكتروني محمية بتشفير SSL وتتم عبر بوابات دفع معتمدة. لا نحتفظ ببيانات بطاقتك الائتمانية على خوادمنا.</p>
                        </div>
                    </div>
                </div>

                <!-- الإرجاع والاستبدال -->
                <div class="faq-category">
                    <h2><i class="fas fa-undo"></i> الإرجاع والاستبدال</h2>
                    
                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleAnswer(this)">
                            <span>ما هي سياسة الإرجاع؟</span>
                            <i class="fas fa-chevron-down faq-icon"></i>
                        </div>
                        <div class="faq-answer">
                            <p>يمكنك إرجاع المنتجات خلال 14 يوماً من تاريخ الاستلام بشرط:</p>
                            <ul>
                                <li>أن يكون المنتج في حالته الأصلية</li>
                                <li>عدم استخدام المنتج</li>
                                <li>وجود التغليف الأصلي</li>
                                <li>إرفاق فاتورة الشراء</li>
                            </ul>
                        </div>
                    </div>

                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleAnswer(this)">
                            <span>كيف أقوم بإرجاع منتج؟</span>
                            <i class="fas fa-chevron-down faq-icon"></i>
                        </div>
                        <div class="faq-answer">
                            <p>لإرجاع منتج:</p>
                            <ol>
                                <li>اتصل بنا على <?= getSetting('store_phone') ?></li>
                                <li>اذكر رقم الطلب وسبب الإرجاع</li>
                                <li>سيتم تحديد موعد لاستلام المنتج</li>
                                <li>الاسترداد المالي خلال 7-14 يوم عمل</li>
                            </ol>
                        </div>
                    </div>

                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleAnswer(this)">
                            <span>من يتحمل تكلفة الإرجاع؟</span>
                            <i class="fas fa-chevron-down faq-icon"></i>
                        </div>
                        <div class="faq-answer">
                            <p>نتحمل تكلفة الإرجاع في حالات:</p>
                            <ul>
                                <li>وجود عيب تصنيعي</li>
                                <li>استلام منتج خاطئ</li>
                                <li>تلف أثناء الشحن</li>
                            </ul>
                            <p>في حالات أخرى، قد يتحمل العميل تكلفة الإرجاع.</p>
                        </div>
                    </div>
                </div>

                <!-- حسابي -->
                <div class="faq-category">
                    <h2><i class="fas fa-user"></i> حسابي</h2>
                    
                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleAnswer(this)">
                            <span>هل أحتاج لإنشاء حساب للطلب؟</span>
                            <i class="fas fa-chevron-down faq-icon"></i>
                        </div>
                        <div class="faq-answer">
                            <p>لا، يمكنك الطلب كزائر. لكن إنشاء حساب يمنحك مزايا:</p>
                            <ul>
                                <li>تتبع طلباتك بسهولة</li>
                                <li>حفظ عناوينك المفضلة</li>
                                <li>إعادة الطلب بسرعة</li>
                                <li>الحصول على عروض حصرية</li>
                            </ul>
                        </div>
                    </div>

                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleAnswer(this)">
                            <span>نسيت كلمة المرور، ماذا أفعل؟</span>
                            <i class="fas fa-chevron-down faq-icon"></i>
                        </div>
                        <div class="faq-answer">
                            <p>اضغط على "نسيت كلمة المرور" في صفحة تسجيل الدخول، وأدخل بريدك الإلكتروني. ستصلك رسالة لإعادة تعيين كلمة المرور.</p>
                        </div>
                    </div>
                </div>

                <!-- أسئلة أخرى -->
                <div class="faq-category">
                    <h2><i class="fas fa-question-circle"></i> أسئلة أخرى</h2>
                    
                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleAnswer(this)">
                            <span>هل لديكم فروع؟</span>
                            <i class="fas fa-chevron-down faq-icon"></i>
                        </div>
                        <div class="faq-answer">
                            <p>نحن متجر إلكتروني فقط حالياً. لكن يمكنك التواصل معنا عبر الهاتف أو البريد الإلكتروني.</p>
                        </div>
                    </div>

                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleAnswer(this)">
                            <span>كيف أستخدم كود الخصم؟</span>
                            <i class="fas fa-chevron-down faq-icon"></i>
                        </div>
                        <div class="faq-answer">
                            <p>في صفحة السلة، ستجد حقل "كود الخصم". أدخل الكود واضغط "تطبيق" لتفعيل الخصم على طلبك.</p>
                        </div>
                    </div>

                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleAnswer(this)">
                            <span>هل تقدمون فواتير ضريبية؟</span>
                            <i class="fas fa-chevron-down faq-icon"></i>
                        </div>
                        <div class="faq-answer">
                            <p>نعم، نصدر فواتير ضريبية لجميع الطلبات. يمكنك طلب نسخة إلكترونية عبر التواصل معنا.</p>
                        </div>
                    </div>
                </div>

                <div style="background: #f8f9fa; padding: 2rem; border-radius: 8px; text-align: center; margin-top: 3rem;">
                    <h3>لم تجد إجابة لسؤالك؟</h3>
                    <p>فريقنا جاهز لمساعدتك</p>
                    <div style="display: flex; gap: 1rem; justify-content: center; margin-top: 1.5rem; flex-wrap: wrap;">
                        <a href="contact.php" class="btn btn-primary">اتصل بنا</a>
                        <a href="tel:<?= getSetting('store_phone') ?>" class="btn btn-secondary">
                            <i class="fas fa-phone"></i> <?= getSetting('store_phone') ?>
                        </a>
                    </div>
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
        function toggleAnswer(element) {
            const answer = element.nextElementSibling;
            const icon = element.querySelector('.faq-icon');
            
            answer.classList.toggle('show');
            icon.classList.toggle('rotate');
        }
    </script>
</body>
</html>