<?php
/**
 * صفحة من نحن
 */ 
require_once 'functions.php';
// التحقق من وضع الصيانة
if (getSetting('maintenance_mode', '0') == '1' && !isset($_SESSION['admin_id'])) {
    header('Location: maintenance.php');
    exit;
}
$storeName = getSetting('store_name', 'متجر إلكتروني');
$cartCount = getCartCount();
$storeDescription = getSetting('store_description', '');
// جلب الإحصائيات الديناميكية من قاعدة البيانات
global $pdo; // تأكد من أن $pdo متاح من config.php عبر functions.php

// عدد العملاء السعداء (عدد العملاء الفريدين)
$stmt = $pdo->query("SELECT COUNT(DISTINCT id) as count FROM customers");
$happyCustomers = $stmt->fetchColumn() ?: 0;

// عدد المنتجات المتنوعة (المنتجات النشطة)
$stmt = $pdo->query("SELECT COUNT(id) as count FROM products WHERE is_active = 1");
$diverseProducts = $stmt->fetchColumn() ?: 0;

// عدد الطلبات المُوصلة
$stmt = $pdo->query("SELECT COUNT(id) as count FROM orders WHERE status = 'delivered'");
$deliveredOrders = $stmt->fetchColumn() ?: 0;

// متوسط التقييم (من المنتجات التي لها تقييمات)
$stmt = $pdo->query("SELECT AVG(rating_avg) as avg FROM products WHERE rating_count > 0");
$avgRating = round($stmt->fetchColumn() ?: 0, 1);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>من نحن - <?= htmlspecialchars($storeName) ?></title>
    <meta name="description" content="تعرف على قصتنا ورؤيتنا في تقديم أفضل المنتجات والخدمات">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .about-hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            padding: 80px 0;
            text-align: center;
        }
        .about-hero h1 {
            font-size: 48px;
            margin-bottom: 20px;
        }
        .about-section {
            padding: 60px 0;
        }
        .about-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: center;
        }
        .about-text h2 {
            font-size: 36px;
            margin-bottom: 20px;
            color: #1e293b;
        }
        .about-text p {
            line-height: 1.8;
            color: #475569;
            margin-bottom: 15px;
        }
        .about-image img {
            width: 100%;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .values-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            margin-top: 40px;
        }
        .value-card {
            background: #fff;
            padding: 40px;
            border-radius: 16px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.07);
            transition: transform 0.3s;
        }
        .value-card:hover {
            transform: translateY(-10px);
        }
        .value-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            color: #fff;
            margin: 0 auto 20px;
        }
        .stats-section {
            background: #f8fafc;
            padding: 60px 0;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 30px;
            text-align: center;
        }
        .stat-item h3 {
            font-size: 48px;
            color: #667eea;
            margin-bottom: 10px;
        }
        .team-section {
            padding: 60px 0;
        }
        .team-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
        }
        .team-member {
            text-align: center;
        }
        .team-member img {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            margin-bottom: 20px;
            object-fit: cover;
        }
        @media (max-width: 768px) {
            .about-content { grid-template-columns: 1fr; }
            .about-hero h1 { font-size: 32px; }
        }
    </style>
</head>
<body>
    <!-- Header --> 
    <header class="site-header">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <a href="index.php">
                        <h1><?= htmlspecialchars($storeName) ?></h1>
                    </a>
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
                        <span class="cart-count" id="cart-count"><?= $cartCount ?></span>
                        <span>السلة</span>
                    </a>
                    <a href="account.php" class="user-btn">
                        <i class="fas fa-user"></i>
                        <span>حسابي</span>
                    </a>
                </div>
            </div>
            
           
        </div>
    </header>

    <!-- Hero -->
    <section class="about-hero">
        <div class="container">
            <h1>من نحن</h1>
            <p style="font-size: 20px; opacity: 0.9;">قصتنا ورؤيتنا في خدمة عملائنا</p>
        </div>
    </section>

    <!-- About Content -->
    <section class="about-section">
        <div class="container">
            <div class="about-content">
                <div class="about-text">
                    <h2>قصتنا</h2>
                    <p>
                        <?= htmlspecialchars($storeName) ?> هو متجر إلكتروني رائد يقدم مجموعة واسعة من المنتجات عالية الجودة 
                        بأسعار تنافسية. بدأنا رحلتنا بهدف توفير تجربة تسوق فريدة ومريحة لعملائنا.
                    </p>
                    <p>
                        نؤمن بأن التسوق الإلكتروني يجب أن يكون سهلاً وآمناً وممتعاً. لذلك، نعمل باستمرار على 
                        تحسين خدماتنا وتوسيع مجموعة منتجاتنا لتلبية احتياجات عملائنا المتنوعة.
                    </p>
                    <p>
                        فريقنا المتفاني يعمل على مدار الساعة لضمان رضاكم الكامل، من اختيار المنتج وحتى 
                        وصوله إلى باب منزلكم.
                    </p>
                </div>
                <div class="about-image">
                    <img src="assets/images/about-store.jpg" alt="متجرنا" 
                         onerror="this.src='https://via.placeholder.com/600x400/667eea/ffffff?text=متجرنا'">
                </div>
            </div>
        </div>
    </section>

    <!-- Values -->
    <section class="about-section" style="background: #f8fafc;">
        <div class="container">
            <h2 style="text-align: center; font-size: 36px; margin-bottom: 20px;">قيمنا</h2>
            <p style="text-align: center; color: #64748b; margin-bottom: 40px;">
                المبادئ التي نؤمن بها ونعمل على تحقيقها يومياً
            </p>
            <div class="values-grid">
                <div class="value-card">
                    <div class="value-icon">
                        <i class="fas fa-gem"></i>
                    </div>
                    <h3>الجودة</h3>
                    <p>نختار منتجاتنا بعناية لضمان أعلى معايير الجودة</p>
                </div>
                <div class="value-card">
                    <div class="value-icon">
                        <i class="fas fa-handshake"></i>
                    </div>
                    <h3>الثقة</h3>
                    <p>نبني علاقات طويلة الأمد مع عملائنا قائمة على الثقة</p>
                </div>
                <div class="value-card">
                    <div class="value-icon">
                        <i class="fas fa-rocket"></i>
                    </div>
                    <h3>الابتكار</h3>
                    <p>نواكب أحدث التقنيات لتوفير أفضل تجربة تسوق</p>
                </div>
                <div class="value-card">
                    <div class="value-icon">
                        <i class="fas fa-heart"></i>
                    </div>
                    <h3>رضا العملاء</h3>
                    <p>سعادتكم هي هدفنا الأول والأخير</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats -->
    <section class="stats-section">
        <div class="container">
            <h2 style="text-align: center; font-size: 36px; margin-bottom: 40px;">إنجازاتنا بالأرقام</h2>
            <div class="stats-grid">
                <div class="stat-item">
                    <h3><i class="fas fa-users"></i> <?= number_format($happyCustomers) ?>+</h3>
                    <p>عميل سعيد</p>
                </div>
                <div class="stat-item">
                    <h3><i class="fas fa-box"></i> <?= number_format($diverseProducts) ?>+</h3>
                    <p>منتج متنوع</p>
                </div>
                <div class="stat-item">
                    <h3><i class="fas fa-truck"></i> <?= number_format($deliveredOrders) ?>+</h3>
                    <p>طلب تم توصيله</p>
                </div>
                <div class="stat-item">
                    <h3><i class="fas fa-star"></i> <?= $avgRating ?>/5</h3>
                    <p>تقييم العملاء</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Vision & Mission -->
    <section class="about-section">
        <div class="container">
            <div class="about-content" style="grid-template-columns: 1fr 1fr;">
                <div style="background: #667eea; color: #fff; padding: 50px; border-radius: 16px;">
                    <i class="fas fa-eye" style="font-size: 48px; margin-bottom: 20px;"></i>
                    <h3 style="font-size: 28px; margin-bottom: 15px;">رؤيتنا</h3>
                    <p style="line-height: 1.8;">
                        أن نكون الوجهة الأولى للتسوق الإلكتروني في مصر والشرق الأوسط، 
                        من خلال تقديم تجربة تسوق استثنائية ومنتجات عالية الجودة بأسعار تنافسية.
                    </p>
                </div>
                <div style="background: #764ba2; color: #fff; padding: 50px; border-radius: 16px;">
                    <i class="fas fa-bullseye" style="font-size: 48px; margin-bottom: 20px;"></i>
                    <h3 style="font-size: 28px; margin-bottom: 15px;">مهمتنا</h3>
                    <p style="line-height: 1.8;">
                        توفير منصة تسوق إلكترونية موثوقة وسهلة الاستخدام، تربط العملاء بأفضل المنتجات 
                        والخدمات، مع التركيز على الجودة والسرعة والأمان.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section class="about-section" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; text-align: center;">
        <div class="container">
            <h2 style="font-size: 36px; margin-bottom: 20px;">هل أنت مستعد للانضمام إلينا؟</h2>
            <p style="font-size: 18px; margin-bottom: 30px; opacity: 0.9;">
                ابدأ تجربة تسوق فريدة معنا اليوم
            </p>
            <div style="display: flex; gap: 20px; justify-content: center;">
                <a href="index.php" class="btn btn-primary" style="background: #fff; color: #667eea;">
                    <i class="fas fa-shopping-bag"></i> تسوق الآن
                </a>
                <a href="contact.php" class="btn btn-secondary" style="background: transparent; border: 2px solid #fff;">
                    <i class="fas fa-envelope"></i> اتصل بنا
                </a>
            </div>
        </div>
    </section>

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