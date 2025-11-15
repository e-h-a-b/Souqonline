<?php
/**
 * صفحة سلة المشتريات
 */
require_once 'functions.php';
// التحقق من وضع الصيانة
if (getSetting('maintenance_mode', '0') == '1' && !isset($_SESSION['admin_id'])) {
    header('Location: maintenance.php');
    exit;
}
$cart = $_SESSION['cart'] ?? [];
$subtotal = getCartTotal();
$shippingCost = 0;
$discount = 0;
$couponCode = '';
$couponError = '';

$storeDescription = getSetting('store_description', '');

// جلب معلومات المتاجر للمنتجات في السلة
$storeInfo = [];
if (!empty($cart)) {
    $productIds = array_keys($cart);
    if (!empty($productIds)) {
        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        
        $stmt = $pdo->prepare("
            SELECT p.id, p.store_type, p.created_by, 
                   CASE 
                       WHEN p.store_type = 'customer' THEN CONCAT(c.first_name, ' ', c.last_name) 
                       ELSE 'المتجر الرئيسي' 
                   END as store_name
            FROM products p 
            LEFT JOIN customers c ON p.created_by = c.id 
            WHERE p.id IN ($placeholders)
        ");
        $stmt->execute($productIds);
        $storeResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // تحويل النتائج إلى مصفوفة بالمفتاح product_id
        foreach ($storeResults as $row) {
            $storeInfo[$row['id']] = [
                'store_type' => $row['store_type'],
                'store_name' => $row['store_name'],
                'created_by' => $row['created_by']
            ];
        }
    }
}

// معالجة تحديث السلة
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_cart'])) {
        foreach ($_POST['qty'] as $productId => $qty) {
            updateCartItem($productId, (int)$qty);
        }
        header('Location: cart.php');
        exit;
    }
    
    if (isset($_POST['remove_item'])) {
        removeFromCart($_POST['product_id']);
        header('Location: cart.php');
        exit;
    }
    
    if (isset($_POST['apply_coupon'])) {
        $couponCode = cleanInput($_POST['coupon_code']);
        $validation = validateCoupon($couponCode, $subtotal);
        
        if ($validation['valid']) {
            $discount = $validation['discount'];
            $_SESSION['coupon'] = [
                'code' => $couponCode,
                'discount' => $discount,
                'id' => $validation['coupon_id']
            ];
        } else {
            $couponError = $validation['message'];
        }
    }
    
    if (isset($_POST['remove_coupon'])) {
        unset($_SESSION['coupon']);
        header('Location: cart.php');
        exit;
    }
}

// استرجاع الكوبون من الجلسة
if (isset($_SESSION['coupon'])) {
    $discount = $_SESSION['coupon']['discount'];
    $couponCode = $_SESSION['coupon']['code'];
}

// حساب تكلفة الشحن التقديرية
$estimatedShipping = getSetting('shipping_cost_cairo', 30);

$total = $subtotal + $estimatedShipping - $discount;

$storeName = getSetting('store_name', 'متجر إلكتروني');
$cartCount = getCartCount();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>سلة المشتريات - <?= htmlspecialchars($storeName) ?></title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
                    <a href="cart.php" class="cart-btn active">
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
            <span>سلة المشتريات</span>
        </div>
    </div>

    <!-- Cart Page -->
    <main class="cart-page">
        <div class="container">
            <h1 class="page-title">سلة المشتريات</h1>

            <?php if (empty($cart)): ?>
                <div class="empty-cart">
                    <i class="fas fa-shopping-cart"></i>
                    <h2>سلة المشتريات فارغة</h2>
                    <p>لم تقم بإضافة أي منتجات بعد</p>
                    <a href="index.php" class="btn btn-primary">تصفح المنتجات</a>
                </div>
            <?php else: ?>
                <div class="cart-wrapper">
                    <!-- Cart Items -->
                    <div class="cart-items-section">
                        <form method="post" action="cart.php">
                           <table class="cart-table">
    <thead>
        <tr>
            <th>المنتج</th>
            <th>المتجر</th>
            <th>السعر</th>
            <th>الكمية</th>
            <th>المجموع</th>
            <th></th>
        </tr>
    </thead>
<tbody>
    <?php foreach ($cart as $productId => $item): 
        $storeData = $storeInfo[$productId] ?? ['store_type' => 'main', 'store_name' => 'المتجر الرئيسي'];
        $storeClass = $storeData['store_type'] === 'customer' ? 'customer-store' : 'main-store';
    ?>
        <tr class="<?= $storeClass ?>">
            <td class="product-info">
                <img src="<?= htmlspecialchars($item['image'] ?: 'assets/images/placeholder.jpg') ?>" 
                     alt="<?= htmlspecialchars($item['title']) ?>">
                <div class="info">
                    <h3><?= htmlspecialchars($item['title']) ?></h3>
                    <?php if ($item['stock'] < $item['qty']): ?>
                        <span class="stock-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            متوفر <?= $item['stock'] ?> فقط
                        </span>
                    <?php endif; ?>
                </div>
            </td>
            <td class="store-info">
                <span class="store-badge store-<?= $storeData['store_type'] ?>">
                    <i class="fas <?= $storeData['store_type'] === 'customer' ? 'fa-user' : 'fa-store' ?>"></i>
                    <?= htmlspecialchars($storeData['store_name']) ?>
                </span>
            </td>
            <td class="price">
                <?= formatPrice($item['price']) ?>
            </td>
            <td class="quantity">
                <div class="qty-input">
                    <button type="button" onclick="updateQty(<?= $productId ?>, -1)" class="qty-btn">
                        <i class="fas fa-minus"></i>
                    </button>
                    <input type="number" 
                           name="qty[<?= $productId ?>]" 
                           value="<?= $item['qty'] ?>" 
                           min="1" 
                           max="<?= $item['stock'] ?>"
                           id="qty-<?= $productId ?>"
                           readonly>
                    <button type="button" onclick="updateQty(<?= $productId ?>, 1, <?= $item['stock'] ?>)" class="qty-btn">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
            </td>
            <td class="total">
                <strong><?= formatPrice($item['price'] * $item['qty']) ?></strong>
            </td>
            <td class="actions">
                <button type="submit" name="remove_item" value="<?= $productId ?>" 
                        class="btn-remove" 
                        onclick="return confirm('هل تريد حذف هذا المنتج؟')">
                    <i class="fas fa-trash"></i>
                </button>
                <input type="hidden" name="product_id" value="<?= $productId ?>">
            </td>
        </tr>
    <?php endforeach; ?>
</tbody>

</table>
						   
                            <div class="cart-actions">
                                <a href="index.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-right"></i> متابعة التسوق
                                </a>
                                <button type="submit" name="update_cart" class="btn btn-secondary">
                                    <i class="fas fa-sync"></i> تحديث السلة
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Cart Summary -->
                    <div class="cart-summary">
                        <h2>ملخص الطلب</h2>

                        <div class="summary-item">
                            <span>المجموع الفرعي</span>
                            <span><?= formatPrice($subtotal) ?></span>
                        </div>

                        <div class="summary-item">
                            <span>الشحن (تقديري)</span>
                            <span><?= formatPrice($estimatedShipping) ?></span>
                        </div>

                        <?php if ($discount > 0): ?>
                            <div class="summary-item discount">
                                <span>
                                    الخصم (<?= htmlspecialchars($couponCode) ?>)
                                    <form method="post" style="display:inline;">
                                        <button type="submit" name="remove_coupon" class="btn-link">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </form>
                                </span>
                                <span class="discount-amount">-<?= formatPrice($discount) ?></span>
                            </div>
                        <?php endif; ?>

                        <div class="summary-total">
                            <span>الإجمالي</span>
                            <span><?= formatPrice($total) ?></span>
                        </div>

                        <!-- Coupon Form -->
                        <?php if (!isset($_SESSION['coupon'])): ?>
                            <form method="post" class="coupon-form">
                                <h3>لديك كوبون خصم؟</h3>
                                <div class="input-group">
                                    <input type="text" name="coupon_code" placeholder="أدخل الكوبون" required>
                                    <button type="submit" name="apply_coupon" class="btn btn-secondary">
                                        تطبيق
                                    </button>
                                </div>
                                <?php if ($couponError): ?>
                                    <div class="error-message">
                                        <i class="fas fa-exclamation-circle"></i>
                                        <?= htmlspecialchars($couponError) ?>
                                    </div>
                                <?php endif; ?>
                            </form>
                        <?php endif; ?>

                        <!-- Checkout Button -->
                        <a href="checkout.php" class="btn btn-primary btn-checkout">
                            <i class="fas fa-lock"></i> إتمام الطلب
                        </a>

                        <!-- Payment Methods -->
                        <div class="payment-methods">
                            <p>طرق الدفع المتاحة:</p>
                            <div class="payment-icons">
                                <i class="fas fa-money-bill-wave" title="الدفع عند الاستلام"></i>
                                <i class="fab fa-cc-visa" title="Visa"></i>
                                <i class="fab fa-cc-mastercard" title="Mastercard"></i>
                                <i class="fas fa-mobile-alt" title="InstaPay"></i>
                            </div>
                        </div>

                        <!-- Security Badge -->
                        <div class="security-badge">
                            <i class="fas fa-shield-alt"></i>
                            <span>دفع آمن ومحمي 100%</span>
                        </div>
                    </div>
                </div>

                <!-- Additional Info -->
                <div class="cart-features">
                    <div class="feature-item">
                        <i class="fas fa-truck"></i>
                        <div>
                            <h4>شحن سريع</h4>
                            <p>توصيل خلال 2-7 أيام عمل</p>
                        </div>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-undo"></i>
                        <div>
                            <h4>إرجاع مجاني</h4>
                            <p>خلال 14 يوم من الاستلام</p>
                        </div>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-headset"></i>
                        <div>
                            <h4>دعم متواصل</h4>
                            <p>خدمة العملاء 24/7</p>
                        </div>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-lock"></i>
                        <div>
                            <h4>دفع آمن</h4>
                            <p>جميع المعاملات محمية</p>
                        </div>
                    </div>
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

<button class="back-to-top show" aria-label="العودة للأعلى"><i class="fas fa-arrow-up"></i></button>
    <div id="toast" class="toast"></div>

    <script src="assets/js/app.js"></script>
    <script>
        function updateQty(productId, change, max = 999) {
            const input = document.getElementById('qty-' + productId);
            let newValue = parseInt(input.value) + change;
            
            if (newValue < 1) newValue = 1;
            if (newValue > max) {
                newValue = max;
                showToast('الحد الأقصى المتوفر هو ' + max, 'warning');
            }
            
            input.value = newValue;
        }
    </script>
</body>
</html>