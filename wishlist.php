<?php
/**
 * صفحة قائمة المفضلة
 */
require_once 'functions.php';

// التحقق من تسجيل الدخول
if (!isset($_SESSION['customer_id'])) {
    header('Location: account.php?redirect=wishlist.php');
    exit;
}

$customerId = $_SESSION['customer_id'];
$wishlistProducts = getWishlistProducts($customerId);

$storeDescription = getSetting('store_description', '');
// معالجة إزالة منتج من المفضلة
if (isset($_GET['remove'])) {
    $productId = (int)$_GET['remove'];
    if (removeFromWishlist($customerId, $productId)) {
        $_SESSION['success_message'] = 'تم إزالة المنتج من المفضلة';
        header('Location: wishlist.php');
        exit;
    }
}

$storeName = getSetting('store_name', 'متجر إلكتروني');
$cartCount = getCartCount();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>قائمة المفضلة - <?= htmlspecialchars($storeName) ?></title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    /* تنسيقات خاصة بصفحة المفضلة */
    .wishlist-page {
        padding: 2rem 0;
        min-height: 60vh;
    }

    .page-title {
        text-align: center;
        margin-bottom: 2rem;
        color: #333;
        font-size: 2rem;
        border-bottom: 2px solid #007bff;
        padding-bottom: 1rem;
    }

    .empty-wishlist {
        text-align: center;
        padding: 4rem 2rem;
        background: #f8f9fa;
        border-radius: 10px;
        margin: 2rem 0;
    }

    .empty-wishlist i {
        font-size: 4rem;
        color: #dc3545;
        margin-bottom: 1rem;
    }

    .empty-wishlist h2 {
        color: #333;
        margin-bottom: 1rem;
    }

    .empty-wishlist p {
        color: #666;
        margin-bottom: 2rem;
        font-size: 1.1rem;
    }

    .wishlist-products {
        display: grid;
        gap: 1.5rem;
        margin-top: 2rem;
    }

    .wishlist-item {
        display: flex;
        align-items: center;
        background: white;
        border: 1px solid #e0e0e0;
        border-radius: 10px;
        padding: 1.5rem;
        transition: all 0.3s ease;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }

    .wishlist-item:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 20px rgba(0,0,0,0.15);
    }

    .wishlist-item .product-image {
        width: 100px; /* تم التصغير من 120px */
        height: 100px; /* تم التصغير من 120px */
        flex-shrink: 0;
        margin-left: 1.5rem;
    }

    .wishlist-item .product-image img {
        width: 100%;
        height: 100%;
        object-fit: cover; /* هذا يضمن أن الصورة تملأ المساحة بدون تشويه */
        border-radius: 8px;
    }

    .wishlist-item .product-details {
        flex: 1;
        min-width: 0; /* يمنع تجاوز النص للمساحة */
    }

    .wishlist-item .product-title {
        margin: 0 0 0.5rem 0;
        font-size: 1.1rem; /* تم تصغير الخط قليلاً */
        line-height: 1.4;
    }

    .wishlist-item .product-title a {
        color: #333;
        text-decoration: none;
        transition: color 0.3s ease;
        display: block;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .wishlist-item .product-title a:hover {
        color: #007bff;
    }

    .wishlist-item .product-price {
        font-size: 1.2rem; /* تم تصغير الخط قليلاً */
        font-weight: bold;
        color: #007bff;
        margin-bottom: 0.5rem;
    }

    .wishlist-item .product-stock {
        margin-bottom: 0.5rem;
        font-size: 0.9rem;
    }

    .wishlist-item .in-stock {
        color: #28a745;
        font-weight: bold;
    }

    .wishlist-item .out-stock {
        color: #dc3545;
        font-weight: bold;
    }

    .wishlist-item .added-date {
        color: #666;
        font-size: 0.85rem;
    }

    .wishlist-item .product-actions {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        min-width: 140px; /* تم التصغير قليلاً */
    }

    .wishlist-item .btn {
        padding: 0.6rem 1rem; /* تم تصغير الحشوة */
        font-size: 0.85rem; /* تم تصغير الخط */
        white-space: nowrap;
    }

    .btn-remove {
        background: #dc3545;
        color: white;
        border: none;
        padding: 0.6rem 1rem;
        border-radius: 5px;
        text-decoration: none;
        text-align: center;
        transition: all 0.3s ease;
        font-size: 0.85rem;
        cursor: pointer;
    }

    .btn-remove:hover {
        background: #c82333;
        transform: translateY(-2px);
    }

    .success-message {
        background: #d4edda;
        color: #155724;
        padding: 1rem;
        border-radius: 5px;
        margin-bottom: 2rem;
        border: 1px solid #c3e6cb;
    }

    /* تصميم متجاوب */
    @media (max-width: 768px) {
        .wishlist-item {
            flex-direction: column;
            text-align: center;
            padding: 1rem;
        }

        .wishlist-item .product-image {
            width: 80px; /* أصغر في الجوال */
            height: 80px; /* أصغر في الجوال */
            margin-left: 0;
            margin-bottom: 1rem;
        }

        .wishlist-item .product-details {
            margin-bottom: 1rem;
            width: 100%;
        }

        .wishlist-item .product-title {
            font-size: 1rem;
            white-space: normal; /* السماح بلف النص في الجوال */
        }

        .wishlist-item .product-actions {
            flex-direction: row;
            justify-content: center;
            width: 100%;
            min-width: auto;
        }

        .wishlist-item .product-actions .btn {
            flex: 1;
            margin: 0 0.25rem;
            font-size: 0.8rem;
            padding: 0.5rem 0.75rem;
        }

        .page-title {
            font-size: 1.5rem;
        }
    }

    @media (max-width: 480px) {
        .wishlist-item .product-actions {
            flex-direction: column;
        }

        .wishlist-item .product-actions .btn {
            margin: 0.25rem 0;
        }

        .wishlist-item .product-image {
            width: 70px;
            height: 70px;
        }
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
                <div class="header-actions">
                    <a href="cart.php" class="cart-btn">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="cart-count"><?= $cartCount ?></span>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Breadcrumb -->
    <div class="breadcrumb">
        <div class="container">
            <a href="index.php">الرئيسية</a>
            <i class="fas fa-chevron-left"></i>
            <span>قائمة المفضلة</span>
        </div>
    </div>

    <!-- Wishlist Content -->
<!-- Wishlist Content -->
<main class="wishlist-page">
    <div class="container">
        <h1 class="page-title">قائمة المفضلة</h1>
        
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="success-message">
                <?= htmlspecialchars($_SESSION['success_message']) ?>
                <?php unset($_SESSION['success_message']); ?>
            </div>
        <?php endif; ?>

        <?php if (empty($wishlistProducts)): ?>
            <div class="empty-wishlist">
                <i class="fas fa-heart"></i>
                <h2>قائمة المفضلة فارغة</h2>
                <p>لم تقم بإضافة أي منتجات إلى المفضلة بعد</p>
                <a href="index.php" class="btn btn-primary">استمر في التسوق</a>
            </div>
        <?php else: ?>
            <div class="wishlist-products">
                <?php foreach ($wishlistProducts as $product): ?>
                    <div class="wishlist-item">
                        <div class="product-image">
                            <a href="product.php?id=<?= $product['id'] ?>">
                                <img src="<?= htmlspecialchars($product['main_image'] ?: 'assets/images/placeholder.jpg') ?>" 
                                     alt="<?= htmlspecialchars($product['title']) ?>"
                                     onerror="this.src='assets/images/placeholder.jpg'">
                            </a>
                        </div>
                        
                        <div class="product-details">
                            <h3 class="product-title">
                                <a href="product.php?id=<?= $product['id'] ?>" title="<?= htmlspecialchars($product['title']) ?>">
                                    <?= htmlspecialchars($product['title']) ?>
                                </a>
                            </h3>
                            
                            <div class="product-price">
                                <?= formatPrice($product['final_price']) ?>
                            </div>
                            
                            <div class="product-stock">
                                <?php if ($product['stock'] > 0): ?>
                                    <span class="in-stock">
                                        <i class="fas fa-check-circle"></i> متوفر في المخزون
                                    </span>
                                <?php else: ?>
                                    <span class="out-stock">
                                        <i class="fas fa-times-circle"></i> نفذت الكمية
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="added-date">
                                <i class="far fa-calendar"></i>
                                أضيف في: <?= date('Y-m-d', strtotime($product['added_date'])) ?>
                            </div>
                        </div>
                        
                        <div class="product-actions">
                            <?php if ($product['stock'] > 0): ?>
                                <button class="btn btn-primary" onclick="addToCart(<?= $product['id'] ?>)">
                                    <i class="fas fa-cart-plus"></i> أضف إلى السلة
                                </button>
                            <?php else: ?>
                                <button class="btn btn-disabled" disabled>
                                    <i class="fas fa-cart-plus"></i> غير متوفر
                                </button>
                            <?php endif; ?>
                            
                            <a href="wishlist.php?remove=<?= $product['id'] ?>" class="btn-remove" 
                               onclick="return confirm('هل تريد إزالة هذا المنتج من المفضلة؟')">
                                <i class="fas fa-trash"></i> إزالة
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
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