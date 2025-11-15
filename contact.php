<?php
/**
 * صفحة اتصل بنا
 */
require_once 'functions.php';
// التحقق من وضع الصيانة
if (getSetting('maintenance_mode', '0') == '1' && !isset($_SESSION['admin_id'])) {
    header('Location: maintenance.php');
    exit;
}

$storeDescription = getSetting('store_description', '');
$storeName = getSetting('store_name', 'متجر إلكتروني');
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = cleanInput($_POST['name'] ?? '');
    $email = cleanInput($_POST['email'] ?? '');
    $phone = cleanInput($_POST['phone'] ?? '');
    $subject = cleanInput($_POST['subject'] ?? '');
    $messageText = cleanInput($_POST['message'] ?? '');
    
    if (empty($name) || empty($email) || empty($messageText)) {
        $error = 'الرجاء ملء جميع الحقول المطلوبة';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'البريد الإلكتروني غير صحيح';
    } else {
        // حفظ الرسالة في قاعدة البيانات أو إرسالها بالبريد
        // هنا مثال بسيط
        try {
            global $pdo;
            $stmt = $pdo->prepare("
                INSERT INTO contact_messages (name, email, phone, subject, message, ip_address) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$name, $email, $phone, $subject, $messageText, $_SERVER['REMOTE_ADDR']]);
            
            // إرسال بريد إلكتروني (اختياري)
            // sendEmail(getSetting('store_email'), 'رسالة جديدة من العميل', $messageText);
            
            $message = 'تم إرسال رسالتك بنجاح. سنتواصل معك قريباً!';
        } catch (Exception $e) {
            $error = 'حدث خطأ أثناء الإرسال. حاول مرة أخرى.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>اتصل بنا - <?= htmlspecialchars($storeName) ?></title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .contact-page { padding: 3rem 0; }
        .contact-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 3rem; margin-top: 2rem; }
        .contact-info-card { background: white; padding: 2rem; border-radius: 8px; }
        .info-item { display: flex; gap: 1rem; margin-bottom: 1.5rem; }
        .info-item i { font-size: 1.5rem; color: var(--primary-color); }
        .contact-form { background: white; padding: 2rem; border-radius: 8px; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 600; }
        .form-group input, .form-group textarea { width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 8px; }
        .map-container { margin-top: 3rem; height: 400px; border-radius: 8px; overflow: hidden; }
        @media (max-width: 768px) { .contact-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <header class="site-header">
        <div class="container">
            <div class="header-content">
                <div class="logo"><a href="index.php"><h1><?= htmlspecialchars($storeName) ?></h1></a></div>
                <div class="header-actions"><a href="cart.php" class="cart-btn"><i class="fas fa-shopping-cart"></i></a></div>
            </div>
        </div>
    </header>

    <div class="breadcrumb">
        <div class="container">
            <a href="index.php">الرئيسية</a>
            <i class="fas fa-chevron-left"></i>
            <span>اتصل بنا</span>
        </div>
    </div>

    <main class="contact-page">
        <div class="container">
            <h1 class="text-center">تواصل معنا</h1>
            <p class="text-center text-secondary">نحن هنا للإجابة على استفساراتك</p>

            <?php if ($message): ?>
                <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div class="contact-grid">
                <div class="contact-info-card">
                    <h2>معلومات التواصل</h2>
                    <div class="info-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <div>
                            <strong>العنوان</strong>
                            <p>القاهرة، مصر</p>
                        </div>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-phone"></i>
                        <div>
                            <strong>الهاتف</strong>
                            <p><?= getSetting('store_phone', '+20 100 000 0000') ?></p>
                        </div>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-envelope"></i>
                        <div>
                            <strong>البريد الإلكتروني</strong>
                            <p><?= getSetting('store_email', 'info@store.com') ?></p>
                        </div>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-clock"></i>
                        <div>
                            <strong>ساعات العمل</strong>
                            <p>السبت - الخميس: 9 ص - 6 م<br>الجمعة: عطلة</p>
                        </div>
                    </div>
                    
                    <h3 style="margin-top: 2rem;">تابعنا</h3>
                    <div style="display: flex; gap: 1rem; margin-top: 1rem;">
                        <a href="#" style="width: 40px; height: 40px; background: #3b5998; color: white; display: flex; align-items: center; justify-content: center; border-radius: 50%;"><i class="fab fa-facebook"></i></a>
                        <a href="#" style="width: 40px; height: 40px; background: #E1306C; color: white; display: flex; align-items: center; justify-content: center; border-radius: 50%;"><i class="fab fa-instagram"></i></a>
                        <a href="#" style="width: 40px; height: 40px; background: #25D366; color: white; display: flex; align-items: center; justify-content: center; border-radius: 50%;"><i class="fab fa-whatsapp"></i></a>
                    </div>
                </div>

                <div class="contact-form">
                    <h2>أرسل رسالة</h2>
                    <form method="post">
                        <div class="form-group">
                            <label>الاسم *</label>
                            <input type="text" name="name" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label>البريد الإلكتروني *</label>
                            <input type="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label>رقم الهاتف</label>
                            <input type="tel" name="phone" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label>الموضوع</label>
                            <input type="text" name="subject" value="<?= htmlspecialchars($_POST['subject'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label>الرسالة *</label>
                            <textarea name="message" rows="5" required><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary" style="width: 100%;">
                            <i class="fas fa-paper-plane"></i> إرسال الرسالة
                        </button>
                    </form>
                </div>
            </div>

            <div class="map-container">
                <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d221088.49265254608!2d31.02!3d30.06!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x14583fa60b21beeb%3A0x79dfb296e8423bba!2sCairo%2C%20Egypt!5e0!3m2!1sen!2sus!4v1234567890" 
                        width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
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
<button class="back-to-top show" aria-label="العودة للأعلى"><i class="fas fa-arrow-up"></i></button>
</body>
</html>
