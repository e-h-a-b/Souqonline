<?php
/**
 * صفحة تتبع الطلب
 */
require_once 'functions.php';
// التحقق من وضع الصيانة
if (getSetting('maintenance_mode', '0') == '1' && !isset($_SESSION['admin_id'])) {
    header('Location: maintenance.php');
    exit;
} 
$storeName = getSetting('store_name', 'متجر إلكتروني');

$storeDescription = getSetting('store_description', '');
$order = null;
$error = '';

if (isset($_GET['order']) || isset($_POST['order_number'])) {
    $orderNumber = cleanInput($_GET['order'] ?? $_POST['order_number']);
    
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT o.*, 
               (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as items_count
        FROM orders o
        WHERE o.order_number = ?
    ");
    $stmt->execute([$orderNumber]);
    $order = $stmt->fetch();
    
    if (!$order) {
        $error = 'لم يتم العثور على الطلب. تحقق من رقم الطلب وحاول مرة أخرى.';
    } else {
        // جلب عناصر الطلب
        $stmt = $pdo->prepare("
            SELECT oi.*, p.main_image 
            FROM order_items oi
            LEFT JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = ?
        ");
        $stmt->execute([$order['id']]);
        $order['items'] = $stmt->fetchAll();
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تتبع الطلب - <?= htmlspecialchars($storeName) ?></title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .track-page { padding: 3rem 0; min-height: 60vh; }
        .track-form { background: white; padding: 2rem; border-radius: 8px; max-width: 500px; margin: 0 auto; text-align: center; }
        .track-result { background: white; padding: 2rem; border-radius: 8px; max-width: 900px; margin: 2rem auto; }
        .order-header { background: var(--primary-color); color: white; padding: 1.5rem; border-radius: 8px 8px 0 0; margin: -2rem -2rem 2rem; }
        .status-timeline { position: relative; padding: 2rem 0; }
        .status-step { display: flex; align-items: center; margin-bottom: 2rem; position: relative; }
        .status-step::before { content: ''; position: absolute; right: 20px; top: 50px; width: 2px; height: 100%; background: #ddd; z-index: 0; }
        .status-step:last-child::before { display: none; }
        .status-icon { width: 40px; height: 40px; background: #ddd; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; margin-left: 1.5rem; z-index: 1; position: relative; }
        .status-step.active .status-icon { background: var(--primary-color); animation: pulse 2s infinite; }
        .status-step.completed .status-icon { background: var(--success-color); }
        @keyframes pulse { 0%, 100% { box-shadow: 0 0 0 0 rgba(13, 110, 253, 0.7); } 50% { box-shadow: 0 0 0 10px rgba(13, 110, 253, 0); } }
        .status-content h3 { margin-bottom: 0.25rem; }
        .status-content p { color: var(--text-secondary); font-size: 0.9rem; }
        .order-items { background: #f8f9fa; padding: 1.5rem; border-radius: 8px; margin: 2rem 0; }
        .item-row { display: flex; gap: 1rem; padding: 1rem 0; border-bottom: 1px solid #dee2e6; }
        .item-row:last-child { border-bottom: none; }
        .item-image { width: 60px; height: 60px; object-fit: cover; border-radius: 4px; }
        .order-summary { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; margin: 2rem 0; }
        .summary-card { background: #f8f9fa; padding: 1rem; border-radius: 8px; text-align: center; }
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

    <main class="track-page">
        <div class="container">
            <h1 class="text-center">تتبع طلبك</h1>

            <?php if (!$order): ?>
                <div class="track-form">
                    <i class="fas fa-search" style="font-size: 3rem; color: var(--primary-color); margin-bottom: 1rem;"></i>
                    <p style="margin-bottom: 2rem;">أدخل رقم طلبك لمعرفة حالته</p>
                    
                    <?php if ($error): ?>
                        <div style="background: #f8d7da; color: #721c24; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                            <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>

                    <form method="post">
                        <input type="text" 
                               name="order_number" 
                               placeholder="مثال: ORD00000001" 
                               required
                               style="width: 100%; padding: 1rem; border: 2px solid #ddd; border-radius: 8px; margin-bottom: 1rem; text-align: center; font-size: 1.1rem;">
                        <button type="submit" class="btn btn-primary" style="width: 100%; padding: 1rem;">
                            <i class="fas fa-search"></i> تتبع الطلب
                        </button>
                    </form>

                    <p style="margin-top: 2rem; font-size: 0.9rem; color: var(--text-secondary);">
                        يمكنك العثور على رقم الطلب في رسالة التأكيد المرسلة إليك
                    </p>
                </div>
            <?php else: ?>
                <div class="track-result">
                    <div class="order-header">
                        <h2 style="margin: 0;">الطلب #<?= htmlspecialchars($order['order_number']) ?></h2>
                        <p style="margin: 0.5rem 0 0; opacity: 0.9;">تاريخ الطلب: <?= date('Y-m-d H:i', strtotime($order['created_at'])) ?></p>
                    </div>

                    <div class="order-summary">
                        <div class="summary-card">
                            <i class="fas fa-box" style="font-size: 2rem; color: var(--primary-color);"></i>
                            <h3><?= $order['items_count'] ?></h3>
                            <p>عدد المنتجات</p>
                        </div>
                        <div class="summary-card">
                            <i class="fas fa-money-bill-wave" style="font-size: 2rem; color: var(--success-color);"></i>
                            <h3><?= formatPrice($order['total']) ?></h3>
                            <p>إجمالي الطلب</p>
                        </div>
                    </div>

                    <h3>حالة الطلب</h3>
                    <div class="status-timeline">
                        <?php
                        $statuses = [
                            'pending' => ['title' => 'تم استلام الطلب', 'icon' => 'check-circle', 'desc' => 'تم استلام طلبك وجاري المراجعة'],
                            'confirmed' => ['title' => 'تم تأكيد الطلب', 'icon' => 'thumbs-up', 'desc' => 'تم تأكيد طلبك ويجري تجهيزه'],
                            'processing' => ['title' => 'جاري التجهيز', 'icon' => 'box', 'desc' => 'يتم تجهيز طلبك للشحن'],
                            'shipped' => ['title' => 'تم الشحن', 'icon' => 'truck', 'desc' => 'طلبك في الطريق إليك'],
                            'delivered' => ['title' => 'تم التوصيل', 'icon' => 'home', 'desc' => 'تم توصيل طلبك بنجاح']
                        ];

                        $currentStatus = $order['status'];
                        $statusOrder = array_keys($statuses);
                        $currentIndex = array_search($currentStatus, $statusOrder);

                        foreach ($statuses as $key => $status):
                            $stepIndex = array_search($key, $statusOrder);
                            $isCompleted = $stepIndex < $currentIndex;
                            $isActive = $key === $currentStatus;
                            $class = $isCompleted ? 'completed' : ($isActive ? 'active' : '');
                        ?>
                            <div class="status-step <?= $class ?>">
                                <div class="status-icon">
                                    <i class="fas fa-<?= $status['icon'] ?>"></i>
                                </div>
                                <div class="status-content">
                                    <h3><?= $status['title'] ?></h3>
                                    <p><?= $status['desc'] ?></p>
                                    <?php if ($isActive && $order['updated_at']): ?>
                                        <small style="color: var(--primary-color);">
                                            <?= date('Y-m-d H:i', strtotime($order['updated_at'])) ?>
                                        </small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <?php if ($currentStatus === 'cancelled'): ?>
                            <div class="status-step" style="border-right: 2px solid #dc3545; padding-right: 1rem;">
                                <div class="status-icon" style="background: #dc3545;">
                                    <i class="fas fa-times"></i>
                                </div>
                                <div class="status-content">
                                    <h3>تم إلغاء الطلب</h3>
                                    <p>تم إلغاء طلبك</p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if ($order['tracking_number']): ?>
                        <div style="background: #e7f3ff; padding: 1rem; border-radius: 8px; margin: 1.5rem 0;">
                            <strong><i class="fas fa-shipping-fast"></i> رقم التتبع:</strong>
                            <code style="background: white; padding: 0.5rem; border-radius: 4px; margin-right: 0.5rem;"><?= htmlspecialchars($order['tracking_number']) ?></code>
                        </div>
                    <?php endif; ?>

                    <h3>تفاصيل الشحن</h3>
                    <div style="background: #f8f9fa; padding: 1.5rem; border-radius: 8px;">
                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem;">
                            <div>
                                <strong><i class="fas fa-user"></i> الاسم:</strong>
                                <p><?= htmlspecialchars($order['customer_name']) ?></p>
                            </div>
                            <div>
                                <strong><i class="fas fa-phone"></i> الهاتف:</strong>
                                <p><?= htmlspecialchars($order['customer_phone']) ?></p>
                            </div>
                            <div style="grid-column: 1 / -1;">
                                <strong><i class="fas fa-map-marker-alt"></i> العنوان:</strong>
                                <p><?= htmlspecialchars($order['shipping_address']) ?></p>
                            </div>
                            <div>
                                <strong><i class="fas fa-credit-card"></i> طريقة الدفع:</strong>
                                <p>
                                    <?php
                                    $paymentMethods = [
                                        'cod' => 'الدفع عند الاستلام',
                                        'visa' => 'بطاقة ائتمان',
                                        'instapay' => 'InstaPay',
                                        'vodafone_cash' => 'Vodafone Cash',
                                        'fawry' => 'Fawry'
                                    ];
                                    echo $paymentMethods[$order['payment_method']] ?? $order['payment_method'];
                                    ?>
                                </p>
                            </div>
                            <div>
                                <strong><i class="fas fa-info-circle"></i> حالة الدفع:</strong>
                                <p>
                                    <?php
                                    $paymentStatuses = [
                                        'pending' => 'قيد الانتظار',
                                        'paid' => 'مدفوع',
                                        'failed' => 'فشل',
                                        'refunded' => 'مسترد'
                                    ];
                                    echo $paymentStatuses[$order['payment_status']] ?? $order['payment_status'];
                                    ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <h3 style="margin-top: 2rem;">المنتجات</h3>
                    <div class="order-items">
                        <?php foreach ($order['items'] as $item): ?>
                            <div class="item-row">
                                <img src="<?= htmlspecialchars($item['main_image'] ?: 'assets/images/placeholder.jpg') ?>" 
                                     alt="<?= htmlspecialchars($item['product_title']) ?>"
                                     class="item-image">
                                <div style="flex: 1;">
                                    <h4><?= htmlspecialchars($item['product_title']) ?></h4>
                                    <p style="color: var(--text-secondary); font-size: 0.9rem;">
                                        الكمية: <?= $item['qty'] ?> × <?= formatPrice($item['unit_price']) ?>
                                    </p>
                                </div>
                                <div style="text-align: left;">
                                    <strong><?= formatPrice($item['total_price']) ?></strong>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 2px solid #dee2e6;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                <span>المجموع الفرعي:</span>
                                <strong><?= formatPrice($order['subtotal']) ?></strong>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                <span>الشحن:</span>
                                <strong><?= formatPrice($order['shipping_cost']) ?></strong>
                            </div>
                            <?php if ($order['discount_amount'] > 0): ?>
                                <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem; color: var(--success-color);">
                                    <span>الخصم:</span>
                                    <strong>-<?= formatPrice($order['discount_amount']) ?></strong>
                                </div>
                            <?php endif; ?>
                            <div style="display: flex; justify-content: space-between; font-size: 1.25rem; color: var(--primary-color); margin-top: 1rem; padding-top: 1rem; border-top: 2px solid #dee2e6;">
                                <span>الإجمالي:</span>
                                <strong><?= formatPrice($order['total']) ?></strong>
                            </div>
                        </div>
                    </div>

                    <?php if ($order['notes']): ?>
                        <div style="background: #fff3cd; padding: 1rem; border-radius: 8px; border-right: 4px solid #ffc107; margin-top: 1.5rem;">
                            <strong><i class="fas fa-sticky-note"></i> ملاحظاتك:</strong>
                            <p style="margin: 0.5rem 0 0;"><?= nl2br(htmlspecialchars($order['notes'])) ?></p>
                        </div>
                    <?php endif; ?>

                    <div style="display: flex; gap: 1rem; margin-top: 2rem; flex-wrap: wrap;">
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-home"></i> العودة للرئيسية
                        </a>
                        <a href="contact.php" class="btn btn-primary">
                            <i class="fas fa-envelope"></i> اتصل بنا
                        </a>
                        <?php if (in_array($order['status'], ['pending', 'confirmed'])): ?>
                            <button onclick="if(confirm('هل تريد إلغاء هذا الطلب؟')) window.location.href='cancel-order.php?id=<?= $order['id'] ?>'" 
                                    class="btn btn-danger">
                                <i class="fas fa-times"></i> إلغاء الطلب
                            </button>
                        <?php endif; ?>
                    </div>

                    <div style="background: #f8f9fa; padding: 1.5rem; border-radius: 8px; margin-top: 2rem; text-align: center;">
                        <p style="margin: 0; color: var(--text-secondary);">
                            <i class="fas fa-question-circle"></i> 
                            لأي استفسار، تواصل معنا على: 
                            <strong><?= getSetting('store_phone') ?></strong>
                        </p>
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

</body>
</html>