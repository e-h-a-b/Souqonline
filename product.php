<?php
/**
 * صفحة تفاصيل المنتج
 */
require_once 'functions.php';

// التحقق من وضع الصيانة
if (getSetting('maintenance_mode', '0') == '1' && !isset($_SESSION['admin_id'])) {
    header('Location: maintenance.php');
    exit;
}

// التحقق من تسجيل الدخول وعرض معلومات التصحيح
echo "<!-- Debug: customer_id = " . ($_SESSION['customer_id'] ?? 'null') . " -->";
echo "<!-- Debug: is logged in = " . (isset($_SESSION['customer_id']) ? 'yes' : 'no') . " -->";

$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$product = getProduct($productId);

$storeDescription = getSetting('store_description', '');
if (!$product) {
    header('Location: index.php');
    exit;
}

// زيادة عدد المشاهدات
increaseView($productId);

// جلب المنتجات ذات الصلة
$relatedProducts = getRelatedProducts($productId, $product['category_id'], 4);

// جلب التقييمات
$reviews = getProductReviews($productId, 10);

$storeName = getSetting('store_name', 'متجر إلكتروني');
$cartCount = getCartCount();

// التحقق من حالة المفضلة للمستخدم الحالي
$customerId = $_SESSION['customer_id'] ?? 0;
$isInWishlist = isInWishlist($customerId, $productId);
echo "<!-- Debug: isInWishlist = " . ($isInWishlist ? 'true' : 'false') . " -->";

// معالجة رابط الإحالة
if (isset($_GET['ref']) && !isset($_SESSION['customer_id'])) {
    $_SESSION['referral_code'] = cleanInput($_GET['ref']);
    $_SESSION['referral_product_id'] = $product_id;
    
    // تسجيل النقرة
    if (isValidReferralCode($_SESSION['referral_code'])) {
        recordReferralClick($_SESSION['referral_code']);
    }
    
    showToast('🎁 احصل على نقاط مجانية عند التسجيل والشراء عبر هذا الرابط!', 'info');
}
// معالجة الإحالة بعد التسجيل الناجح
if (isset($_SESSION['referral_code']) && $signup_success) {
    $referred_customer_id = $new_customer_id; // افترض أن هذا هو ID العميل الجديد
    $referral_code = $_SESSION['referral_code'];
    
    processReferralSignup($referred_customer_id, $referral_code);
    
    // مسح بيانات الإحالة من الجلسة
    unset($_SESSION['referral_code']);
    unset($_SESSION['referral_product_id']);
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= htmlspecialchars($product['meta_description'] ?: $product['short_description']) ?>">
    <title><?= htmlspecialchars($product['title']) ?> - <?= htmlspecialchars($storeName) ?></title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
.product-image { 
    padding-top: 100%; 
}
</style>
<style>
.product-image { 
    padding-top: 100%; 
}

/* تنسيقات النقاط */
.points-btn {
    position: relative;
    display: flex;
    align-items: center;
    gap: 5px;
    padding: 8px 12px;
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: white;
    border-radius: 8px;
    text-decoration: none;
    transition: all 0.3s ease;
}

.points-btn:hover {
    background: linear-gradient(135deg, #d97706, #b45309);
    transform: translateY(-2px);
    color: white;
}

.points-count {
    background: rgba(255,255,255,0.2);
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.product-points-detail {
    margin: 1rem 0;
    padding: 1rem;
    background: linear-gradient(135deg, #fffbeb, #fef3c7);
    border: 1px solid #fcd34d;
    border-radius: 8px;
}

.points-badge {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 1.1rem;
    color: #92400e;
}

.points-badge i {
    color: #f59e0b;
}

.points-text {
    font-weight: 600;
}

.points-value {
    color: #065f46;
    font-weight: 600;
}

.points-info {
    margin-top: 0.5rem;
    color: #92400e;
    font-size: 0.875rem;
}

/* تحسينات للهيدر */
.header-actions {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.wishlist-btn, .cart-btn, .user-btn, .points-btn {
    display: flex;
    align-items: center;
    gap: 5px;
    padding: 8px 12px;
    border-radius: 8px;
    text-decoration: none;
    transition: all 0.3s ease;
    color: #333;
}

.wishlist-btn:hover, .cart-btn:hover, .user-btn:hover {
    background: #f8f9fa;
}

.wishlist-count, .cart-count, .points-count {
    background: #ef4444;
    color: white;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    min-width: 20px;
    text-align: center;
}

.points-count {
    background: #f59e0b;
}
</style>
<script>
// تحديث عداد النقاط في الهيدر
function updatePointsCount() {
    fetch('ajax/get_points.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const pointsCount = document.getElementById('points-count');
                if (pointsCount) {
                    pointsCount.textContent = data.formatted_points;
                }
            }
        })
        .catch(error => console.error('Error updating points count:', error));
}

// تحديث جميع العدادات
function updateAllCounters() {
    updatePointsCount();
    updateWishlistCount();
    updateCartCount();
}

// تحديث تلقائي للعدادات كل 30 ثانية
setInterval(updateAllCounters, 30000);

// تحديث عند تحميل الصفحة
document.addEventListener('DOMContentLoaded', function() {
    updateAllCounters();
});

// دوال المفضلة والسلة الموجودة...
function toggleWishlist(productId) {
    console.log('Toggle wishlist called for product:', productId);
    
    <?php if (!isset($_SESSION['customer_id'])): ?>
        showToast('يجب تسجيل الدخول لإضافة المنتج إلى المفضلة', 'warning');
        setTimeout(() => {
            window.location.href = 'account.php?redirect=' + encodeURIComponent(window.location.href);
        }, 2000);
        return;
    <?php endif; ?>
    
    const wishlistBtn = document.getElementById('wishlist-btn-' + productId);
    const wishlistIcon = document.getElementById('wishlist-icon-' + productId);
    const wishlistText = document.getElementById('wishlist-text-' + productId);
    
    wishlistBtn.disabled = true;
    wishlistIcon.className = 'fas fa-spinner fa-spin';
    
    fetch('ajax/wishlist.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'toggle',
            product_id: productId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (data.in_wishlist) {
                wishlistBtn.classList.add('in-wishlist');
                wishlistIcon.className = 'fas fa-heart';
                wishlistText.textContent = 'في المفضلة';
                showToast('تمت إضافة المنتج إلى المفضلة', 'success');
            } else {
                wishlistBtn.classList.remove('in-wishlist');
                wishlistIcon.className = 'far fa-heart';
                wishlistText.textContent = 'أضف إلى المفضلة';
                showToast('تمت إزالة المنتج من المفضلة', 'info');
            }
            
            updateWishlistCount();
        } else {
            showToast(data.message || 'حدث خطأ', 'error');
            resetWishlistButton(productId);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('حدث خطأ في الاتصال', 'error');
        resetWishlistButton(productId);
    })
    .finally(() => {
        wishlistBtn.disabled = false;
    });
}

// دوال أخرى...
function changeImage(src) {
    document.getElementById('main-product-image').src = src;
    document.querySelectorAll('.thumb').forEach(t => t.classList.remove('active'));
    event.target.classList.add('active');
}

function openTab(tabName) {
    document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById(tabName).classList.add('active');
    event.target.classList.add('active');
}

function increaseQty(max) {
    const input = document.getElementById('product-quantity');
    if (parseInt(input.value) < max) {
        input.value = parseInt(input.value) + 1;
    }
}

function decreaseQty() {
    const input = document.getElementById('product-quantity');
    if (parseInt(input.value) > 1) {
        input.value = parseInt(input.value) - 1;
    }
}

function addToCartFromDetail(productId) {
    const qty = parseInt(document.getElementById('product-quantity').value);
    addToCart(productId, qty);
}
</script>
</head>
<body>
    <!-- Header (مختصر) -->
    <header class="site-header">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <a href="index.php"><h1><?= htmlspecialchars($storeName) ?></h1></a>
                </div>
                <div class="header-actions">
                    <a href="cart.php" class="cart-btn">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="cart-count" id="cart-count"><?= $cartCount ?></span>
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
            <?php if ($product['category_name']): ?>
                <a href="index.php?category=<?= $product['category_id'] ?>">
                    <?= htmlspecialchars($product['category_name']) ?>
                </a>
                <i class="fas fa-chevron-left"></i>
            <?php endif; ?>
            <span><?= htmlspecialchars($product['title']) ?></span>
        </div>
    </div>

    <!-- Product Details -->
    <main class="product-details-page">
        <div class="container">
            <div class="product-detail-wrapper">
                <!-- Product Images -->
                <div class="product-gallery">
                    <div class="main-image">
                        <img id="main-product-image" 
                             src="<?= htmlspecialchars($product['main_image'] ?: 'assets/images/placeholder.jpg') ?>" 
                             alt="<?= htmlspecialchars($product['title']) ?>">
                        <?php if ($product['discount_percentage'] > 0): ?>
                            <span class="discount-badge">-<?= $product['discount_percentage'] ?>%</span>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!empty($product['images'])): ?>
                    <div class="thumbnail-images">
                        <img class="thumb active" 
                             src="<?= htmlspecialchars($product['main_image']) ?>" 
                             onclick="changeImage(this.src)">
                        <?php foreach ($product['images'] as $img): ?>
                            <img class="thumb" 
                                 src="<?= htmlspecialchars($img['image_path']) ?>" 
                                 onclick="changeImage(this.src)">
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Product Info -->
                <div class="product-info-detail">
                    <h1 class="product-title"><?= htmlspecialchars($product['title']) ?></h1>
                    
                    <div class="product-rating-detail">
                        <div class="stars">
                            <?php 
                            $rating = $product['rating_avg'];
                            for ($i = 1; $i <= 5; $i++): 
                                if ($i <= $rating): ?>
                                    <i class="fas fa-star"></i>
                                <?php elseif ($i - 0.5 <= $rating): ?>
                                    <i class="fas fa-star-half-alt"></i>
                                <?php else: ?>
                                    <i class="far fa-star"></i>
                                <?php endif;
                            endfor; ?>
                        </div>
                        <span class="rating-text">
                            <?= number_format($rating, 1) ?> 
                            (<?= $product['rating_count'] ?> تقييم)
                        </span>
                        <span class="views-count">
                            <i class="fas fa-eye"></i> <?= $product['views'] ?> مشاهدة
                        </span>
                    </div>

                    <?php if ($product['sku']): ?>
                        <div class="product-sku">
                            <span>رمز المنتج: <strong><?= htmlspecialchars($product['sku']) ?></strong></span>
                        </div>
                    <?php endif; ?>

                    <div class="product-price-detail">
                        <?php if ($product['discount_percentage'] > 0): ?>
                            <span class="old-price"><?= formatPrice($product['price']) ?></span>
                            <span class="new-price"><?= formatPrice($product['final_price']) ?></span>
                            <span class="save-amount">
                                وفر <?= formatPrice($product['price'] - $product['final_price']) ?>
                            </span>
                        <?php else: ?>
                            <span class="new-price"><?= formatPrice($product['price']) ?></span>
                        <?php endif; ?>
                    </div>
<!-- إضافة قسم النقاط المكتسبة -->
<?php if (getSetting('points_enabled', '1') == '1'): ?>
    <?php
    $points_earned = calculatePointsFromPurchase($product['final_price']);
    $points_value = pointsToCurrency($points_earned);
    ?>
    <div class="product-points-detail">
        <div class="points-badge">
            <i class="fas fa-coins"></i>
            <span class="points-text">اكسب <strong><?= number_format($points_earned) ?></strong> نقطة</span>
            <span class="points-value">(تعادل <?= formatPrice($points_value) ?>)</span>
        </div>
        <div class="points-info">
            <small>ستحصل على هذه النقاط بعد اكتمال الشراء</small>
        </div>
    </div>
<?php endif; ?>
                    <div class="product-stock">
                        <?php if ($product['stock'] > 10): ?>
                            <span class="in-stock"><i class="fas fa-check-circle"></i> متوفر في المخزون</span>
                        <?php elseif ($product['stock'] > 0): ?>
                            <span class="low-stock"><i class="fas fa-exclamation-circle"></i> متبقي <?= $product['stock'] ?> فقط</span>
                        <?php else: ?>
                            <span class="out-stock"><i class="fas fa-times-circle"></i> نفذت الكمية</span>
                        <?php endif; ?>
                    </div>

                    <?php if ($product['short_description']): ?>
                        <div class="product-short-desc">
                            <p><?= nl2br(htmlspecialchars($product['short_description'])) ?></p>
                        </div>
                    <?php endif; ?>

                    <?php if ($product['stock'] > 0): ?>
<div class="product-actions">
    <div class="quantity-selector">
        <button type="button" onclick="decreaseQty()"><i class="fas fa-minus"></i></button>
        <input type="number" id="product-quantity" value="1" min="1" max="<?= $product['stock'] ?>" readonly>
        <button type="button" onclick="increaseQty(<?= $product['stock'] ?>)"><i class="fas fa-plus"></i></button>
    </div>
    
    <button class="btn btn-primary btn-add-cart-detail" onclick="addToCartFromDetail(<?= $product['id'] ?>)">
        <i class="fas fa-shopping-cart"></i> أضف إلى السلة
    </button>
    
    <!-- زر المفضلة المحسن -->
    <button class="btn btn-wishlist <?= $isInWishlist ? 'in-wishlist' : '' ?>" 
            id="wishlist-btn-<?= $product['id'] ?>" 
            onclick="toggleWishlist(<?= $product['id'] ?>)">
        <i class="<?= $isInWishlist ? 'fas' : 'far' ?> fa-heart" id="wishlist-icon-<?= $product['id'] ?>"></i>
        <span class="wishlist-text" id="wishlist-text-<?= $product['id'] ?>">
            <?= $isInWishlist ? 'في المفضلة' : 'أضف إلى المفضلة' ?>
        </span>
    </button>
</div>
                    <?php else: ?>
                        <div class="product-actions">
                            <button class="btn btn-disabled" disabled>نفذت الكمية</button>
                        </div>
                    <?php endif; ?>

                    <div class="product-meta">
                        <div class="meta-item">
                            <i class="fas fa-truck"></i>
                            <span>شحن سريع لجميع المحافظات</span>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-undo"></i>
                            <span>إرجاع مجاني خلال 14 يوم</span>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-shield-alt"></i>
                            <span>دفع آمن ومضمون</span>
                        </div>
                    </div>

                    <div class="product-share">
                        <span>شارك:</span>
                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($_SERVER['REQUEST_URI']) ?>" 
                           target="_blank" class="share-btn facebook">
                            <i class="fab fa-facebook"></i>
                        </a>
                        <a href="https://twitter.com/intent/tweet?url=<?= urlencode($_SERVER['REQUEST_URI']) ?>" 
                           target="_blank" class="share-btn twitter">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="https://wa.me/?text=<?= urlencode($product['title'] . ' ' . $_SERVER['REQUEST_URI']) ?>" 
                           target="_blank" class="share-btn whatsapp">
                            <i class="fab fa-whatsapp"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Product Tabs -->
            <div class="product-tabs">
                <div class="tab-buttons">
                    <button class="tab-btn active" onclick="openTab('description')">الوصف</button>
                    <button class="tab-btn" onclick="openTab('reviews')">التقييمات (<?= count($reviews) ?>)</button>
                    <button class="tab-btn" onclick="openTab('shipping')">الشحن والإرجاع</button>
                </div>

                <div id="description" class="tab-content active">
                    <div class="description-content">
                        <?= nl2br(htmlspecialchars($product['description'])) ?>
                    </div>
                </div>

                <div id="reviews" class="tab-content">
                    <div class="reviews-section">
                        <?php if (!empty($reviews)): ?>
                            <div class="reviews-list">
                                <?php foreach ($reviews as $review): ?>
                                    <div class="review-item">
                                        <div class="review-header">
                                            <div class="reviewer-info">
                                                <strong><?= htmlspecialchars($review['first_name']) ?></strong>
                                                <?php if ($review['is_verified_purchase']): ?>
                                                    <span class="verified-badge">
                                                        <i class="fas fa-check-circle"></i> مشترٍ موثق
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="review-rating">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <i class="<?= $i <= $review['rating'] ? 'fas' : 'far' ?> fa-star"></i>
                                                <?php endfor; ?>
                                            </div>
                                        </div>
                                        <?php if ($review['title']): ?>
                                            <h4 class="review-title"><?= htmlspecialchars($review['title']) ?></h4>
                                        <?php endif; ?>
                                        <p class="review-comment"><?= nl2br(htmlspecialchars($review['comment'])) ?></p>
                                        <div class="review-date">
                                            <?= date('Y-m-d', strtotime($review['created_at'])) ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="no-reviews">لا توجد تقييمات حتى الآن. كن أول من يقيم هذا المنتج!</p>
                        <?php endif; ?>
                    </div>
					<!-- قسم إضافة التقييم -->
<div class="add-review-section">
    <h3>أضف تقييمك</h3>
    <?php if (isset($_SESSION['customer_id'])): ?>
        <a href="review_form.php?product_id=<?= $product['id'] ?>" class="btn btn-primary">
            <i class="fas fa-edit"></i> اكتب تقييمك
        </a>
    <?php else: ?>
        <p>يجب <a href="account.php">تسجيل الدخول</a> لإضافة تقييم</p>
    <?php endif; ?>
</div>

<!-- إحصائيات التقييمات -->
<?php
$ratingStats = getProductRatingStats($product['id']);
if ($ratingStats && $ratingStats['total_reviews'] > 0):
?>
<div class="rating-stats">
    <h4>تقييمات العملاء</h4>
    <div class="rating-overview">
        <div class="average-rating">
            <span class="rating-number"><?= number_format($ratingStats['average_rating'], 1) ?></span>
            <div class="stars">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <i class="<?= $i <= floor($ratingStats['average_rating']) ? 'fas' : ($i <= $ratingStats['average_rating'] ? 'fas fa-star-half-alt' : 'far') ?> fa-star"></i>
                <?php endfor; ?>
            </div>
            <span class="total-reviews">(<?= $ratingStats['total_reviews'] ?> تقييم)</span>
        </div>
        
        <div class="rating-bars">
            <?php for ($i = 5; $i >= 1; $i--): 
                $count = $ratingStats[$i . '_star'] ?? 0;
                $percentage = $ratingStats['total_reviews'] > 0 ? ($count / $ratingStats['total_reviews']) * 100 : 0;
            ?>
                <div class="rating-bar-item">
                    <span class="star-count"><?= $i ?> نجوم</span>
                    <div class="bar-container">
                        <div class="bar" style="width: <?= $percentage ?>%"></div>
                    </div>
                    <span class="count"><?= $count ?></span>
                </div>
            <?php endfor; ?>
        </div>
    </div>
</div>
<?php endif; ?>
                </div>

                <div id="shipping" class="tab-content">
                    <div class="shipping-info">
                        <h3><i class="fas fa-truck"></i> الشحن</h3>
                        <p>نقوم بالشحن إلى جميع محافظات مصر</p>
                        <ul>
                            <li>القاهرة والجيزة: 30 ج.م - التوصيل خلال 2-3 أيام عمل</li>
                            <li>الإسكندرية: 50 ج.م - التوصيل خلال 3-4 أيام عمل</li>
                            <li>باقي المحافظات: 70 ج.م - التوصيل خلال 4-7 أيام عمل</li>
                        </ul>
                        <p><strong>شحن مجاني للطلبات أكثر من <?= formatPrice(getSetting('free_shipping_threshold', 500)) ?></strong></p>

                        <h3><i class="fas fa-undo"></i> الإرجاع والاستبدال</h3>
                        <ul>
                            <li>يمكنك إرجاع أو استبدال المنتج خلال 14 يوم من تاريخ الاستلام</li>
                            <li>يجب أن يكون المنتج في حالته الأصلية وبالتغليف الأصلي</li>
                            <li>نتحمل تكاليف الإرجاع في حالة وجود عيب بالمنتج</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Related Products -->
            <?php if (!empty($relatedProducts)): ?>
            <section class="related-products">
                <h2>منتجات ذات صلة</h2>
                <div class="products-grid">
                    <?php foreach ($relatedProducts as $related): ?>
                        <div class="product-card">
                            <a href="product.php?id=<?= $related['id'] ?>" class="product-image">
                                <img src="<?= htmlspecialchars($related['main_image'] ?: 'assets/images/placeholder.jpg') ?>" 
                                     alt="<?= htmlspecialchars($related['title']) ?>">
                            </a>
                            <div class="product-info">
                                <h3 class="product-title">
                                    <a href="product.php?id=<?= $related['id'] ?>">
                                        <?= htmlspecialchars($related['title']) ?>
                                    </a>
                                </h3>
                                <div class="product-price">
                                    <span class="price-new"><?= formatPrice($related['final_price']) ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
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


    <div id="toast" class="toast"></div>

    <script src="assets/js/app.js"></script>
    <script>
        function changeImage(src) {
            document.getElementById('main-product-image').src = src;
            document.querySelectorAll('.thumb').forEach(t => t.classList.remove('active'));
            event.target.classList.add('active');
        }

        function openTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.getElementById(tabName).classList.add('active');
            event.target.classList.add('active');
        }

        function increaseQty(max) {
            const input = document.getElementById('product-quantity');
            if (parseInt(input.value) < max) {
                input.value = parseInt(input.value) + 1;
            }
        }

        function decreaseQty() {
            const input = document.getElementById('product-quantity');
            if (parseInt(input.value) > 1) {
                input.value = parseInt(input.value) - 1;
            }
        }

        function addToCartFromDetail(productId) {
            const qty = parseInt(document.getElementById('product-quantity').value);
            addToCart(productId, qty);
        }

        function toggleWishlist(productId) {
            // يمكن إضافة وظيفة قائمة الرغبات هنا
            showToast('تمت الإضافة إلى المفضلة', 'success');
        }
    </script>
<script>
// دالة المفضلة المحسنة
function toggleWishlist(productId) {
    console.log('Toggle wishlist called for product:', productId);
    
    // التحقق من تسجيل الدخول
    <?php if (!isset($_SESSION['customer_id'])): ?>
        showToast('يجب تسجيل الدخول لإضافة المنتج إلى المفضلة', 'warning');
        setTimeout(() => {
            window.location.href = 'account.php?redirect=' + encodeURIComponent(window.location.href);
        }, 2000);
        return;
    <?php endif; ?>
    
    const wishlistBtn = document.getElementById('wishlist-btn-' + productId);
    const wishlistIcon = document.getElementById('wishlist-icon-' + productId);
    const wishlistText = document.getElementById('wishlist-text-' + productId);
    
    // إظهار تحميل
    wishlistBtn.disabled = true;
    wishlistIcon.className = 'fas fa-spinner fa-spin';
    
    fetch('ajax/wishlist.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'toggle',
            product_id: productId
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        console.log('Response:', data);
        
        if (data.success) {
            if (data.in_wishlist) {
                // تمت الإضافة إلى المفضلة
                wishlistBtn.classList.add('in-wishlist');
                wishlistIcon.className = 'fas fa-heart';
                wishlistText.textContent = 'في المفضلة';
                showToast('تمت إضافة المنتج إلى المفضلة', 'success');
            } else {
                // تمت الإزالة من المفضلة
                wishlistBtn.classList.remove('in-wishlist');
                wishlistIcon.className = 'far fa-heart';
                wishlistText.textContent = 'أضف إلى المفضلة';
                showToast('تمت إزالة المنتج من المفضلة', 'info');
            }
            
            // تحديث عداد المفضلة في الهيدر
            updateWishlistCount();
        } else {
            showToast(data.message || 'حدث خطأ في الإضافة إلى المفضلة', 'error');
            // إعادة تعيين الأيقونة للحالة الأصلية
            resetWishlistButton(productId);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('حدث خطأ في الاتصال بالخادم', 'error');
        resetWishlistButton(productId);
    })
    .finally(() => {
        wishlistBtn.disabled = false;
    });
}

// إعادة تعيين زر المفضلة للحالة الأصلية
function resetWishlistButton(productId) {
    const wishlistBtn = document.getElementById('wishlist-btn-' + productId);
    const wishlistIcon = document.getElementById('wishlist-icon-' + productId);
    const wishlistText = document.getElementById('wishlist-text-' + productId);
    
    <?php
    $currentState = isInWishlist($_SESSION['customer_id'] ?? 0, $product['id']);
    ?>
    
    if (<?= $currentState ? 'true' : 'false' ?>) {
        wishlistBtn.classList.add('in-wishlist');
        wishlistIcon.className = 'fas fa-heart';
        wishlistText.textContent = 'في المفضلة';
    } else {
        wishlistBtn.classList.remove('in-wishlist');
        wishlistIcon.className = 'far fa-heart';
        wishlistText.textContent = 'أضف إلى المفضلة';
    }
}

// تحديث عدد المفضلة في الهيدر
function updateWishlistCount() {
    fetch('ajax/wishlist_count.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const wishlistCount = document.getElementById('wishlist-count');
                if (wishlistCount) {
                    wishlistCount.textContent = data.count;
                }
            }
        })
        .catch(error => console.error('Error updating wishlist count:', error));
}

// دوال أخرى موجودة...
function changeImage(src) {
    document.getElementById('main-product-image').src = src;
    document.querySelectorAll('.thumb').forEach(t => t.classList.remove('active'));
    event.target.classList.add('active');
}

function openTab(tabName) {
    document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById(tabName).classList.add('active');
    event.target.classList.add('active');
}

function increaseQty(max) {
    const input = document.getElementById('product-quantity');
    if (parseInt(input.value) < max) {
        input.value = parseInt(input.value) + 1;
    }
}

function decreaseQty() {
    const input = document.getElementById('product-quantity');
    if (parseInt(input.value) > 1) {
        input.value = parseInt(input.value) - 1;
    }
}

function addToCartFromDetail(productId) {
    const qty = parseInt(document.getElementById('product-quantity').value);
    addToCart(productId, qty);
}
</script>
</body>
</html>