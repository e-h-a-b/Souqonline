<?php
/**
 * صفحة إتمام الطلب
 */
require_once 'functions.php';

// التحقق من وجود منتجات في السلة
$cart = $_SESSION['cart'] ?? [];
if (empty($cart)) {
    header('Location: cart.php');
    exit;
}

$errors = [];
$success = false;
$orderId = null;
$orderNumber = null;

$storeDescription = getSetting('store_description', '');
// معالجة إلغاء الدفع
if (isset($_GET['cancel']) && isset($_SESSION['pending_order_id'])) {
    $orderId = $_SESSION['pending_order_id'];
    
    // تحديث حالة الطلب إلى ملغى
    $stmt = $pdo->prepare("UPDATE orders SET payment_status = 'cancelled', updated_at = NOW() WHERE id = :id");
    $stmt->bindValue(':id', (int)$orderId, PDO::PARAM_INT);
    $stmt->execute();
    
    unset($_SESSION['pending_order_id']);
    header('Location: checkout.php');
    exit;
}
// معالجة إرسال الطلب
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    // التحقق من CSRF Token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $errors[] = 'حدث خطأ في التحقق. يرجى المحاولة مرة أخرى.';
    } else {
        // التحقق من البيانات
        $customerName = cleanInput($_POST['customer_name'] ?? '');
        $customerPhone = cleanInput($_POST['customer_phone'] ?? '');
        $customerEmail = cleanInput($_POST['customer_email'] ?? '');
        $governorate = cleanInput($_POST['governorate'] ?? '');
        $city = cleanInput($_POST['city'] ?? '');
        $address = cleanInput($_POST['address'] ?? '');
        $paymentMethod = cleanInput($_POST['payment_method'] ?? 'cod');
        $notes = cleanInput($_POST['notes'] ?? '');

        // التحقق من صحة البيانات
        if (empty($customerName)) $errors[] = 'الاسم مطلوب';
        if (empty($customerPhone)) $errors[] = 'رقم الهاتف مطلوب';
        if (empty($governorate)) $errors[] = 'المحافظة مطلوبة';
        if (empty($address)) $errors[] = 'العنوان مطلوب';

        // التحقق من رقم الهاتف
        if (!empty($customerPhone) && !preg_match('/^(01)[0-9]{9}$/', $customerPhone)) {
            $errors[] = 'رقم الهاتف غير صحيح';
        }

        // التحقق من البريد الإلكتروني
        if (!empty($customerEmail) && !filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'البريد الإلكتروني غير صحيح';
        }

        if (empty($errors)) {
            // حساب التكاليف
            $shippingCost = calculateShipping($governorate);
            $discount = isset($_SESSION['coupon']) ? $_SESSION['coupon']['discount'] : 0;

            // إنشاء الطلب
            $orderData = [
                'customer_name' => $customerName,
                'customer_phone' => $customerPhone,
                'customer_email' => $customerEmail,
                'shipping_address' => $address,
                'governorate' => $governorate,
                'city' => $city,
                'payment_method' => $paymentMethod,
                'discount' => $discount
            ];

            $result = createOrder($orderData);

            if ($result && $result['success']) {
                $orderId = $result['order_id'];
                $orderNumber = $result['order_number'];
                
                // استخدام الكوبون
                if (isset($_SESSION['coupon'])) {
                    useCoupon($_SESSION['coupon']['id']);
                    unset($_SESSION['coupon']);
                }

                // للدفع الإلكتروني - التوجيه لبوابة الدفع
                // للدفع الإلكتروني - التوجيه لبوابة الدفع
                if (in_array($paymentMethod, ['visa', 'instapay', 'vodafone_cash', 'fawry'])) {
                    // حفظ رقم الطلب في الجلسة
                    $_SESSION['pending_order_id'] = $orderId;
                    
                    // التوجيه لصفحة الدفع مع تمرير رقم الطلب وطريقة الدفع
                    header('Location: payment-gateway.php?order=' . $orderId . '&method=' . $paymentMethod);
                    exit;
                }

                $success = true;
            } else {
                $errors[] = 'حدث خطأ أثناء إنشاء الطلب. يرجى المحاولة مرة أخرى.';
            }
        }
    }
}

$subtotal = getCartTotal();
$shippingCost = 0;
$discount = isset($_SESSION['coupon']) ? $_SESSION['coupon']['discount'] : 0;

$storeName = getSetting('store_name', 'متجر إلكتروني');
$csrfToken = generateCSRFToken();
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
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إتمام الطلب - <?= htmlspecialchars($storeName) ?></title>
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
            </div>
        </div>
    </header>

    <!-- Checkout Steps -->
    <div class="checkout-steps">
        <div class="container">
            <div class="step active">
                <div class="step-number">1</div>
                <span>السلة</span>
            </div>
            <div class="step active">
                <div class="step-number">2</div>
                <span>معلومات الشحن</span>
            </div>
            <div class="step">
                <div class="step-number">3</div>
                <span>الدفع</span>
            </div>
        </div>
    </div>

    <main class="checkout-page">
        <div class="container">

            <?php if ($success): ?>
                <!-- Success Message -->
                <div class="order-success">
                    <div class="success-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h1>تم إنشاء الطلب بنجاح!</h1>
                    <p>رقم الطلب: <strong><?= htmlspecialchars($orderNumber) ?></strong></p>
                    
                    <?php if ($_POST['payment_method'] === 'cod'): ?>
                        <div class="success-message">
                            <p>شكراً لك! تم استلام طلبك بنجاح.</p>
                            <p>سيتم التواصل معك قريباً لتأكيد الطلب والشحن.</p>
                            <p><strong>طريقة الدفع:</strong> الدفع عند الاستلام</p>
                        </div>
                    <?php else: ?>
                        <div class="success-message">
                            <p>يتم الآن تحويلك إلى صفحة الدفع...</p>
									<?php if ($success && in_array($_POST['payment_method'], ['visa', 'instapay', 'vodafone_cash', 'fawry'])): ?>
    <div class="order-success">
        <!-- ... المحتوى الحالي ... -->
        <div class="success-message">
            <p>يتم الآن تحويلك إلى صفحة الدفع الآمن...</p>
            <p>إذا لم يتم التوجيه تلقائياً، <a href="payment-gateway.php?order=<?= $orderId ?>&method=<?= $_POST['payment_method'] ?>">انقر هنا</a></p>
        </div>
        
        <script>
            // توجيه تلقائي بعد 3 ثواني
            setTimeout(function() {
                window.location.href = 'payment-gateway.php?order=<?= $orderId ?>&method=<?= $_POST['payment_method'] ?>';
            }, 3000);
        </script>
    </div>
<?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <div class="success-actions">
                        <a href="index.php" class="btn btn-primary">العودة للمتجر</a>
                        <a href="order-details.php?id=<?= $orderId ?>" class="btn btn-secondary">عرض تفاصيل الطلب</a>
                    </div>
                </div>
            <?php else: ?>
                <!-- Checkout Form -->
                <div class="checkout-wrapper">
                    <div class="checkout-form-section">
                        <h1>معلومات الشحن</h1>

                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-error">
                                <ul>
                                    <?php foreach ($errors as $error): ?>
                                        <li><?= htmlspecialchars($error) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <form method="post" action="checkout.php" id="checkout-form">
                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

                            <div class="form-section">
                                <h3><i class="fas fa-user"></i> معلومات العميل</h3>
                                
                                <div class="form-group">
                                    <label for="customer_name">الاسم الكامل *</label>
                                    <input type="text" id="customer_name" name="customer_name" 
                                           value="<?= htmlspecialchars($_POST['customer_name'] ?? '') ?>" required>
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="customer_phone">رقم الهاتف *</label>
                                        <input type="tel" id="customer_phone" name="customer_phone" 
                                               placeholder="01xxxxxxxxx"
                                               value="<?= htmlspecialchars($_POST['customer_phone'] ?? '') ?>" required>
                                        <small>مثال: 01012345678</small>
                                    </div>

                                    <div class="form-group">
                                        <label for="customer_email">البريد الإلكتروني (اختياري)</label>
                                        <input type="email" id="customer_email" name="customer_email" 
                                               value="<?= htmlspecialchars($_POST['customer_email'] ?? '') ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="form-section">
                                <h3><i class="fas fa-map-marker-alt"></i> عنوان الشحن</h3>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="governorate">المحافظة *</label>
                                        <select id="governorate" name="governorate" required onchange="calculateShippingCost()">
                                            <option value="">اختر المحافظة</option>
                                            <option value="القاهرة" <?= ($_POST['governorate'] ?? '') === 'القاهرة' ? 'selected' : '' ?>>القاهرة</option>
                                            <option value="الجيزة" <?= ($_POST['governorate'] ?? '') === 'الجيزة' ? 'selected' : '' ?>>الجيزة</option>
                                            <option value="الإسكندرية" <?= ($_POST['governorate'] ?? '') === 'الإسكندرية' ? 'selected' : '' ?>>الإسكندرية</option>
                                            <option value="الشرقية" <?= ($_POST['governorate'] ?? '') === 'الشرقية' ? 'selected' : '' ?>>الشرقية</option>
                                            <option value="الدقهلية" <?= ($_POST['governorate'] ?? '') === 'الدقهلية' ? 'selected' : '' ?>>الدقهلية</option>
                                            <option value="القليوبية" <?= ($_POST['governorate'] ?? '') === 'القليوبية' ? 'selected' : '' ?>>القليوبية</option>
                                            <option value="المنوفية" <?= ($_POST['governorate'] ?? '') === 'المنوفية' ? 'selected' : '' ?>>المنوفية</option>
                                            <option value="الغربية" <?= ($_POST['governorate'] ?? '') === 'الغربية' ? 'selected' : '' ?>>الغربية</option>
                                            <option value="البحيرة" <?= ($_POST['governorate'] ?? '') === 'البحيرة' ? 'selected' : '' ?>>البحيرة</option>
                                            <option value="كفر الشيخ" <?= ($_POST['governorate'] ?? '') === 'كفر الشيخ' ? 'selected' : '' ?>>كفر الشيخ</option>
                                            <option value="دمياط" <?= ($_POST['governorate'] ?? '') === 'دمياط' ? 'selected' : '' ?>>دمياط</option>
                                             <option value="بورسعيد" <?= ($_POST['governorate'] ?? '') === 'بورسعيد' ? 'selected' : '' ?>>بورسعيد</option>
                                            <option value="السويس" <?= ($_POST['governorate'] ?? '') === 'السويس' ? 'selected' : '' ?>>السويس</option>
                                            <option value="الإسماعيلية" <?= ($_POST['governorate'] ?? '') === 'الإسماعيلية' ? 'selected' : '' ?>>الإسماعيلية</option>
                                            <option value="شمال سيناء" <?= ($_POST['governorate'] ?? '') === 'شمال سيناء' ? 'selected' : '' ?>>شمال سيناء</option>
                                            <option value="جنوب سيناء" <?= ($_POST['governorate'] ?? '') === 'جنوب سيناء' ? 'selected' : '' ?>>جنوب سيناء</option>
                                            <option value="الفيوم" <?= ($_POST['governorate'] ?? '') === 'الفيوم' ? 'selected' : '' ?>>الفيوم</option>
                                            <option value="بني سويف" <?= ($_POST['governorate'] ?? '') === 'بني سويف' ? 'selected' : '' ?>>بني سويف</option>
                                            <option value="المنيا" <?= ($_POST['governorate'] ?? '') === 'المنيا' ? 'selected' : '' ?>>المنيا</option>
                                            <option value="أسيوط" <?= ($_POST['governorate'] ?? '') === 'أسيوط' ? 'selected' : '' ?>>أسيوط</option>
                                            <option value="سوهاج" <?= ($_POST['governorate'] ?? '') === 'سوهاج' ? 'selected' : '' ?>>سوهاج</option>
                                            <option value="قنا" <?= ($_POST['governorate'] ?? '') === 'قنا' ? 'selected' : '' ?>>قنا</option>
                                            <option value="الأقصر" <?= ($_POST['governorate'] ?? '') === 'الأقصر' ? 'selected' : '' ?>>الأقصر</option>
                                            <option value="أسوان" <?= ($_POST['governorate'] ?? '') === 'أسوان' ? 'selected' : '' ?>>أسوان</option>
                                            <option value="البحر الأحمر" <?= ($_POST['governorate'] ?? '') === 'البحر الأحمر' ? 'selected' : '' ?>>البحر الأحمر</option>
                                            <option value="الوادي الجديد" <?= ($_POST['governorate'] ?? '') === 'الوادي الجديد' ? 'selected' : '' ?>>الوادي الجديد</option>
                                            <option value="مطروح" <?= ($_POST['governorate'] ?? '') === 'مطروح' ? 'selected' : '' ?>>مطروح</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label for="city">المدينة/المنطقة</label>
                                        <input type="text" id="city" name="city" 
                                               value="<?= htmlspecialchars($_POST['city'] ?? '') ?>">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="address">العنوان بالتفصيل *</label>
                                    <textarea id="address" name="address" rows="3" required 
                                              placeholder="الشارع، رقم المبنى، الدور، الشقة..."><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea>
                                </div>

                                <div class="form-group">
                                    <label for="notes">ملاحظات (اختياري)</label>
                                    <textarea id="notes" name="notes" rows="2" 
                                              placeholder="أي معلومات إضافية لتوصيل طلبك..."><?= htmlspecialchars($_POST['notes'] ?? '') ?></textarea>
                                </div>
                            </div>

                            <div class="form-section">
                                <h3><i class="fas fa-credit-card"></i> طريقة الدفع</h3>

                                <div class="payment-methods-grid">
                                    <label class="payment-option">
                                        <input type="radio" name="payment_method" value="cod" checked>
                                        <div class="payment-card">
                                            <i class="fas fa-money-bill-wave"></i>
                                            <h4>الدفع عند الاستلام</h4>
                                            <p>ادفع نقداً عند استلام الطلب</p>
                                        </div>
                                    </label>

                                    <label class="payment-option">
                                        <input type="radio" name="payment_method" value="visa">
                                        <div class="payment-card">
                                            <i class="fab fa-cc-visa"></i>
                                            <h4>بطاقة الائتمان</h4>
                                            <p>Visa / Mastercard</p>
                                        </div>
                                    </label>

                                    <label class="payment-option">
                                        <input type="radio" name="payment_method" value="instapay">
                                        <div class="payment-card">
                                            <i class="fas fa-mobile-alt"></i>
                                            <h4>InstaPay</h4>
                                            <p>الدفع عبر إنستاباي</p>
                                        </div>
                                    </label>

                                    <label class="payment-option">
                                        <input type="radio" name="payment_method" value="vodafone_cash">
                                        <div class="payment-card">
                                            <i class="fas fa-phone-square"></i>
                                            <h4>Vodafone Cash</h4>
                                            <p>الدفع عبر فودافون كاش</p>
                                        </div>
                                    </label>

                                    <label class="payment-option">
                                        <input type="radio" name="payment_method" value="fawry">
                                        <div class="payment-card">
                                            <i class="fas fa-store"></i>
                                            <h4>فوري</h4>
                                            <p>الدفع عبر فوري</p>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <div class="form-actions">
                                <a href="cart.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-right"></i> العودة للسلة
                                </a>
                                <button type="submit" name="place_order" class="btn btn-primary btn-place-order">
                                    <i class="fas fa-check"></i> تأكيد الطلب
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Order Summary -->
                    <div class="order-summary-section">
                        <div class="order-summary-sticky">
                            <h2>ملخص الطلب</h2>

<div class="summary-items">
    <?php foreach ($cart as $productId => $item): 
        $storeData = $storeInfo[$productId] ?? ['store_type' => 'main', 'store_name' => 'المتجر الرئيسي'];
        $storeClass = $storeData['store_type'] === 'customer' ? 'customer-store-item' : 'main-store-item';
    ?>
        <div class="summary-item <?= $storeClass ?>">
            <img src="<?= htmlspecialchars($item['image'] ?: 'assets/images/placeholder.jpg') ?>" 
                 alt="<?= htmlspecialchars($item['title']) ?>">
            <div class="item-info">
                <h4><?= htmlspecialchars($item['title']) ?></h4>
                <p>الكمية: <?= $item['qty'] ?></p>
                <div class="store-info-small">
                    <span class="store-badge-small store-<?= $storeData['store_type'] ?>">
                        <i class="fas <?= $storeData['store_type'] === 'customer' ? 'fa-user' : 'fa-store' ?>"></i>
                        <?= htmlspecialchars($storeData['store_name']) ?>
                    </span>
                </div>
            </div>
            <div class="item-price">
                <?= formatPrice($item['price'] * $item['qty']) ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

                            <div class="summary-calculations">
                                <div class="calc-row">
                                    <span>المجموع الفرعي</span>
                                    <span><?= formatPrice($subtotal) ?></span>
                                </div>

                                <div class="calc-row shipping-row">
                                    <span>
                                        الشحن
                                        <small id="shipping-location">اختر المحافظة</small>
                                    </span>
                                    <span id="shipping-cost">-</span>
                                </div>

                                <?php if ($discount > 0): ?>
                                    <div class="calc-row discount-row">
                                        <span>الخصم</span>
                                        <span class="discount-amount">-<?= formatPrice($discount) ?></span>
                                    </div>
                                <?php endif; ?>

                                <div class="calc-total">
                                    <span>الإجمالي</span>
                                    <span id="total-amount"><?= formatPrice($subtotal) ?></span>
                                </div>
                            </div>

                            <div class="summary-features">
                                <div class="feature">
                                    <i class="fas fa-shield-alt"></i>
                                    <span>دفع آمن 100%</span>
                                </div>
                                <div class="feature">
                                    <i class="fas fa-truck"></i>
                                    <span>توصيل سريع</span>
                                </div>
                                <div class="feature">
                                    <i class="fas fa-undo"></i>
                                    <span>إرجاع مجاني</span>
                                </div>
                            </div>
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
        // حساب تكلفة الشحن عند اختيار المحافظة
        function calculateShippingCost() {
            const governorate = document.getElementById('governorate').value;
            const shippingCostEl = document.getElementById('shipping-cost');
            const shippingLocationEl = document.getElementById('shipping-location');
            const totalEl = document.getElementById('total-amount');
            
            const subtotal = <?= $subtotal ?>;
            const discount = <?= $discount ?>;
            
            let shipping = 0;
            let location = '';
            
            const shippingRates = {
                'القاهرة': <?= getSetting('shipping_cost_cairo', 30) ?>,
                'الجيزة': <?= getSetting('shipping_cost_giza', 30) ?>,
                'الإسكندرية': <?= getSetting('shipping_cost_alex', 50) ?>
            };
            
            if (governorate && shippingRates[governorate]) {
                shipping = shippingRates[governorate];
                location = governorate;
            } else if (governorate) {
                shipping = <?= getSetting('shipping_cost_other', 70) ?>;
                location = 'محافظات أخرى';
            }
            
            // التحقق من الشحن المجاني
            const freeShippingThreshold = <?= getSetting('free_shipping_threshold', 500) ?>;
            if (subtotal >= freeShippingThreshold) {
                shipping = 0;
                shippingCostEl.innerHTML = '<span class="free-shipping">مجاناً</span>';
            } else if (shipping > 0) {
                shippingCostEl.textContent = shipping.toFixed(2) + ' <?= getSetting('currency_symbol', 'ج.م') ?>';
            } else {
                shippingCostEl.textContent = '-';
            }
            
            shippingLocationEl.textContent = location ? '(' + location + ')' : 'اختر المحافظة';
            
            const total = subtotal + shipping - discount;
            totalEl.textContent = total.toFixed(2) + ' <?= getSetting('currency_symbol', 'ج.م') ?>';
        }

        // التحقق من صحة النموذج قبل الإرسال
        document.getElementById('checkout-form').addEventListener('submit', function(e) {
            const phone = document.getElementById('customer_phone').value;
            const phonePattern = /^(01)[0-9]{9}$/;
            
            if (!phonePattern.test(phone)) {
                e.preventDefault();
                showToast('رقم الهاتف يجب أن يبدأ بـ 01 ويتكون من 11 رقم', 'error');
                return false;
            }

            const governorate = document.getElementById('governorate').value;
            if (!governorate) {
                e.preventDefault();
                showToast('يرجى اختيار المحافظة', 'error');
                return false;
            }
        });

        // تحديث الشحن عند تحميل الصفحة إذا كانت المحافظة محددة
        window.addEventListener('DOMContentLoaded', function() {
            const governorate = document.getElementById('governorate').value;
            if (governorate) {
                calculateShippingCost();
            }
        });
    </script>
</body>
</html>