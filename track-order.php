<?php
/**
 * صفحة تتبع الطلبات
 */
session_start();
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
$success = '';

// البحث عن الطلب
if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['order'])) {
    $orderNumber = isset($_POST['order_number']) ? cleanInput($_POST['order_number']) : cleanInput($_GET['order'] ?? '');
    
    if (empty($orderNumber)) {
        $error = 'يرجى إدخال رقم الطلب';
    } else {
        global $pdo;
        $stmt = $pdo->prepare("
            SELECT o.*, 
                   COUNT(oi.id) as items_count,
                   SUM(oi.qty) as total_items
            FROM orders o
            LEFT JOIN order_items oi ON o.id = oi.order_id
            WHERE o.order_number = ?
            GROUP BY o.id
        ");
        $stmt->execute([$orderNumber]);
        $order = $stmt->fetch();
        
        if (!$order) {
            $error = 'رقم الطلب غير موجود';
        }
    }
}

// جلب سجل حالة الطلب
$statusHistory = [];
if ($order) {
    $stmt = $pdo->prepare("
        SELECT * FROM order_status_history 
        WHERE order_id = ? 
        ORDER BY created_at DESC
    ");
    $stmt->execute([$order['id']]);
    $statusHistory = $stmt->fetchAll();
}

// جلب عناصر الطلب
$orderItems = [];
if ($order) {
    $stmt = $pdo->prepare("
        SELECT oi.*, p.main_image, p.slug
        FROM order_items oi
        LEFT JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = ?
    ");
    $stmt->execute([$order['id']]);
    $orderItems = $stmt->fetchAll();
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
        .tracking-page { padding: 2rem 0; min-height: 70vh; }
        .search-box { background: white; padding: 2rem; border-radius: 12px; margin-bottom: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .order-details { background: white; border-radius: 12px; padding: 2rem; margin-bottom: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .status-timeline { background: white; border-radius: 12px; padding: 2rem; margin-bottom: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .order-items { background: white; border-radius: 12px; padding: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        
        .timeline { position: relative; padding: 20px 0; }
        .timeline::before { content: ''; position: absolute; right: 20px; top: 0; bottom: 0; width: 2px; background: #e9ecef; }
        .timeline-item { position: relative; margin-bottom: 30px; padding-right: 60px; }
        .timeline-item::before { content: ''; position: absolute; right: 16px; top: 5px; width: 12px; height: 12px; border-radius: 50%; background: #6c757d; }
        .timeline-item.active::before { background: #28a745; box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.2); }
        .timeline-item.completed::before { background: #28a745; }
        .timeline-date { color: #6c757d; font-size: 0.875rem; margin-bottom: 5px; }
        .timeline-content { background: #f8f9fa; padding: 15px; border-radius: 8px; }
        
        .status-badge { padding: 0.5rem 1rem; border-radius: 20px; font-weight: 600; display: inline-block; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-confirmed { background: #cfe2ff; color: #084298; }
        .status-processing { background: #d1e7dd; color: #0f5132; }
        .status-shipped { background: #d1e7dd; color: #0f5132; }
        .status-delivered { background: #198754; color: white; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        
        .order-summary { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
        .summary-card { background: #f8f9fa; padding: 1.5rem; border-radius: 8px; text-align: center; }
        .summary-card i { font-size: 2rem; color: var(--primary-color); margin-bottom: 1rem; }
        .summary-value { font-size: 1.5rem; font-weight: 700; color: #2c3e50; }
        .summary-label { color: #6c757d; margin-top: 0.5rem; }
        
        .items-table { width: 100%; border-collapse: collapse; }
        .items-table th, .items-table td { padding: 1rem; text-align: right; border-bottom: 1px solid #e9ecef; }
        .items-table th { background: #f8f9fa; font-weight: 600; }
        .product-cell { display: flex; align-items: center; gap: 1rem; }
        .product-image { width: 60px; height: 60px; object-fit: cover; border-radius: 8px; }
        
        .progress-bar { height: 8px; background: #e9ecef; border-radius: 4px; margin: 2rem 0; overflow: hidden; }
        .progress-fill { height: 100%; background: linear-gradient(90deg, #28a745, #20c997); transition: width 0.5s ease; }
        
        @media (max-width: 768px) {
            .order-summary { grid-template-columns: 1fr; }
            .product-cell { flex-direction: column; text-align: center; }
            .timeline::before { right: 10px; }
            .timeline-item { padding-right: 40px; }
        }
    </style>
</head>
<body>
    <header class="site-header">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <a href="index.php">
                        <h1><?= htmlspecialchars($storeName) ?></h1>
                    </a>
                </div>
                <div class="header-actions">
                    <a href="cart.php" class="cart-btn">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="cart-count"><?= getCartCount() ?></span>
                    </a>
                    <a href="account.php" class="user-btn">
                        <i class="fas fa-user"></i>
                        <span>حسابي</span>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <main class="tracking-page">
        <div class="container">
            <h1 class="text-center" style="margin-bottom: 2rem;">
                <i class="fas fa-truck"></i>
                تتبع طلبك
            </h1>

            <!-- نموذج البحث -->
            <div class="search-box">
                <form method="post" action="track-order.php">
                    <div style="display: flex; gap: 1rem; max-width: 600px; margin: 0 auto;">
                        <input type="text" name="order_number" 
                               placeholder="أدخل رقم الطلب (مثال: ORD00000001)" 
                               value="<?= htmlspecialchars($_POST['order_number'] ?? $_GET['order'] ?? '') ?>"
                               style="flex: 1; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px;"
                               required>
                        <button type="submit" class="btn btn-primary" style="padding: 12px 24px;">
                            <i class="fas fa-search"></i> تتبع الطلب
                        </button>
                    </div>
                </form>
                
                <?php if ($error): ?>
                    <div style="background: #f8d7da; color: #721c24; padding: 1rem; border-radius: 8px; margin-top: 1rem; text-align: center;">
                        <i class="fas fa-exclamation-circle"></i>
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($order): ?>
                <!-- تفاصيل الطلب -->
                <div class="order-details">
                    <div class="order-header" style="display: flex; justify-content: between; align-items: center; margin-bottom: 2rem; padding-bottom: 1rem; border-bottom: 2px solid #e9ecef;">
                        <div>
                            <h2 style="margin: 0 0 0.5rem 0;">
                                <i class="fas fa-receipt"></i>
                                طلب رقم: <?= htmlspecialchars($order['order_number']) ?>
                            </h2>
                            <p style="margin: 0; color: #6c757d;">
                                <i class="fas fa-calendar"></i>
                                تاريخ الطلب: <?= date('Y-m-d H:i', strtotime($order['created_at'])) ?>
                            </p>
                        </div>
                        <div>
                            <span class="status-badge status-<?= $order['status'] ?>" style="font-size: 1.1rem;">
                                <?= getOrderStatusText($order['status']) ?>
                            </span>
                        </div>
                    </div>

                    <!-- شريط التقدم -->
                    <div class="progress-bar">
                        <?php
                        $progress = 0;
                        switch ($order['status']) {
                            case 'pending': $progress = 20; break;
                            case 'confirmed': $progress = 40; break;
                            case 'processing': $progress = 60; break;
                            case 'shipped': $progress = 80; break;
                            case 'delivered': $progress = 100; break;
                            default: $progress = 0;
                        }
                        ?>
                        <div class="progress-fill" style="width: <?= $progress ?>%;"></div>
                    </div>

                    <!-- ملخص الطلب -->
                    <div class="order-summary">
                        <div class="summary-card">
                            <i class="fas fa-boxes"></i>
                            <div class="summary-value"><?= $order['items_count'] ?></div>
                            <div class="summary-label">عدد المنتجات</div>
                        </div>
                        <div class="summary-card">
                            <i class="fas fa-cube"></i>
                            <div class="summary-value"><?= $order['total_items'] ?></div>
                            <div class="summary-label">إجمالي القطع</div>
                        </div>
                        <div class="summary-card">
                            <i class="fas fa-money-bill-wave"></i>
                            <div class="summary-value"><?= formatPrice($order['total']) ?></div>
                            <div class="summary-label">المبلغ الإجمالي</div>
                        </div>
                        <div class="summary-card">
                            <i class="fas fa-truck"></i>
                            <div class="summary-value"><?= formatPrice($order['shipping_cost']) ?></div>
                            <div class="summary-label">تكلفة الشحن</div>
                        </div>
                    </div>

                    <!-- معلومات التوصيل -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-top: 2rem;">
                        <div>
                            <h4><i class="fas fa-user"></i> معلومات العميل</h4>
                            <div style="background: #f8f9fa; padding: 1rem; border-radius: 8px;">
                                <p><strong>الاسم:</strong> <?= htmlspecialchars($order['customer_name']) ?></p>
                                <p><strong>الهاتف:</strong> <?= htmlspecialchars($order['customer_phone']) ?></p>
                                <?php if ($order['customer_email']): ?>
                                    <p><strong>البريد الإلكتروني:</strong> <?= htmlspecialchars($order['customer_email']) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div>
                            <h4><i class="fas fa-map-marker-alt"></i> عنوان التوصيل</h4>
                            <div style="background: #f8f9fa; padding: 1rem; border-radius: 8px;">
                                <p><?= nl2br(htmlspecialchars($order['shipping_address'])) ?></p>
                                <?php if ($order['governorate']): ?>
                                    <p><strong>المحافظة:</strong> <?= htmlspecialchars($order['governorate']) ?></p>
                                <?php endif; ?>
                                <?php if ($order['city']): ?>
                                    <p><strong>المدينة:</strong> <?= htmlspecialchars($order['city']) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- معلومات الدفع -->
                    <div style="margin-top: 2rem;">
                        <h4><i class="fas fa-credit-card"></i> معلومات الدفع</h4>
                        <div style="background: #f8f9fa; padding: 1rem; border-radius: 8px;">
                            <p><strong>طريقة الدفع:</strong> 
                                <?php
                                $paymentMethods = [
                                    'cod' => 'الدفع عند الاستلام',
                                    'visa' => 'بطاقة ائتمان',
                                    'instapay' => 'انستاباي',
                                    'vodafone_cash' => 'فودافون كاش',
                                    'fawry' => 'فوري'
                                ];
                                echo $paymentMethods[$order['payment_method']] ?? $order['payment_method'];
                                ?>
                            </p>
                            <p><strong>حالة الدفع:</strong> 
                                <span class="status-badge <?= $order['payment_status'] === 'paid' ? 'status-delivered' : 'status-pending' ?>">
                                    <?= $order['payment_status'] === 'paid' ? 'تم الدفع' : 'قيد الانتظار' ?>
                                </span>
                            </p>
                            <?php if ($order['tracking_number']): ?>
                                <p><strong>رقم التتبع:</strong> <?= htmlspecialchars($order['tracking_number']) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- خط سير حالة الطلب -->
                <div class="status-timeline">
                    <h3 style="margin-bottom: 2rem;">
                        <i class="fas fa-history"></i>
                        خط سير الطلب
                    </h3>
                    
                    <div class="timeline">
                        <?php
                        $statuses = [
                            'pending' => ['icon' => 'fa-clock', 'title' => 'قيد المراجعة', 'description' => 'تم استلام طلبك بنجاح وجاري مراجعته'],
                            'confirmed' => ['icon' => 'fa-check', 'title' => 'تم التأكيد', 'description' => 'تم تأكيد طلبك وجاري تجهيزه'],
                            'processing' => ['icon' => 'fa-cogs', 'title' => 'قيد التجهيز', 'description' => 'جاري تجهيز طلبك للتوصيل'],
                            'shipped' => ['icon' => 'fa-truck', 'title' => 'تم الشحن', 'description' => 'تم شحن طلبك وهو في الطريق إليك'],
                            'delivered' => ['icon' => 'fa-box-open', 'title' => 'تم التوصيل', 'description' => 'تم توصيل طلبك بنجاح']
                        ];
                        
                        $currentStatusIndex = array_search($order['status'], array_keys($statuses));
                        if ($currentStatusIndex === false) $currentStatusIndex = -1;
                        
                        foreach ($statuses as $status => $info):
                            $statusIndex = array_search($status, array_keys($statuses));
                            $isCompleted = $statusIndex <= $currentStatusIndex;
                            $isActive = $statusIndex === $currentStatusIndex;
                        ?>
                            <div class="timeline-item <?= $isCompleted ? 'completed' : '' ?> <?= $isActive ? 'active' : '' ?>">
                                <div class="timeline-date">
                                    <?php
                                    // البحث عن وقت هذا الحالة في السجل
                                    $statusTime = null;
                                    foreach ($statusHistory as $history) {
                                        if ($history['new_status'] === $status) {
                                            $statusTime = $history['created_at'];
                                            break;
                                        }
                                    }
                                    if (!$statusTime && $status === 'pending') {
                                        $statusTime = $order['created_at'];
                                    }
                                    echo $statusTime ? date('Y-m-d H:i', strtotime($statusTime)) : '--';
                                    ?>
                                </div>
                                <div class="timeline-content">
                                    <div style="display: flex; align-items: center; gap: 1rem;">
                                        <i class="fas <?= $info['icon'] ?>" style="color: <?= $isCompleted ? '#28a745' : '#6c757d' ?>;"></i>
                                        <div>
                                            <h5 style="margin: 0 0 5px 0; color: <?= $isCompleted ? '#28a745' : '#495057' ?>;">
                                                <?= $info['title'] ?>
                                            </h5>
                                            <p style="margin: 0; color: #6c757d; font-size: 0.9rem;">
                                                <?= $info['description'] ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- عناصر الطلب -->
                <div class="order-items">
                    <h3 style="margin-bottom: 2rem;">
                        <i class="fas fa-shopping-bag"></i>
                        المنتجات المطلوبة
                    </h3>
                    
                    <table class="items-table">
                        <thead>
                            <tr>
                                <th>المنتج</th>
                                <th>السعر</th>
                                <th>الكمية</th>
                                <th>المجموع</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orderItems as $item): ?>
                                <tr>
                                    <td>
                                        <div class="product-cell">
                                            <img src="<?= htmlspecialchars($item['main_image'] ?: 'assets/images/placeholder.jpg') ?>" 
                                                 alt="<?= htmlspecialchars($item['product_title']) ?>" 
                                                 class="product-image">
                                            <div>
                                                <strong><?= htmlspecialchars($item['product_title']) ?></strong>
                                                <?php if ($item['product_sku']): ?>
                                                    <div style="color: #6c757d; font-size: 0.875rem;">
                                                        SKU: <?= htmlspecialchars($item['product_sku']) ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?= formatPrice($item['unit_price']) ?></td>
                                    <td><?= $item['qty'] ?></td>
                                    <td><strong><?= formatPrice($item['total_price']) ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot style="background: #f8f9fa;">
                            <tr>
                                <td colspan="3" style="text-align: left; font-weight: 600;">المجموع الفرعي:</td>
                                <td><strong><?= formatPrice($order['subtotal']) ?></strong></td>
                            </tr>
                            <tr>
                                <td colspan="3" style="text-align: left; font-weight: 600;">تكلفة الشحن:</td>
                                <td><strong><?= formatPrice($order['shipping_cost']) ?></strong></td>
                            </tr>
                            <?php if ($order['discount_amount'] > 0): ?>
                                <tr>
                                    <td colspan="3" style="text-align: left; font-weight: 600;">الخصم:</td>
                                    <td><strong style="color: #28a745;">-<?= formatPrice($order['discount_amount']) ?></strong></td>
                                </tr>
                            <?php endif; ?>
                            <tr>
                                <td colspan="3" style="text-align: left; font-weight: 600; font-size: 1.1rem;">المجموع النهائي:</td>
                                <td><strong style="font-size: 1.1rem; color: #2c3e50;"><?= formatPrice($order['total']) ?></strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- أزرار إضافية -->
                <div style="text-align: center; margin-top: 2rem;">
                    <a href="index.php" class="btn btn-primary" style="margin: 0 0.5rem;">
                        <i class="fas fa-shopping-cart"></i> مواصلة التسوق
                    </a>
                    <?php if (isset($_SESSION['customer_id'])): ?>
                        <a href="account.php" class="btn btn-secondary" style="margin: 0 0.5rem;">
                            <i class="fas fa-user"></i> العودة للحساب
                        </a>
                    <?php endif; ?>
                    <button onclick="window.print()" class="btn btn-outline-primary" style="margin: 0 0.5rem;">
                        <i class="fas fa-print"></i> طباعة الطلب
                    </button>
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


    <script>
        // تحديث تلقائي للصفحة كل 30 ثانية إذا كان الطلب قيد التجهيز
        <?php if ($order && in_array($order['status'], ['pending', 'confirmed', 'processing', 'shipped'])): ?>
        setTimeout(function() {
            window.location.reload();
        }, 30000); // 30 ثانية
        <?php endif; ?>

        // نسخ رقم الطلب
        function copyOrderNumber() {
            const orderNumber = '<?= $order ? htmlspecialchars($order['order_number']) : '' ?>';
            navigator.clipboard.writeText(orderNumber).then(function() {
                alert('تم نسخ رقم الطلب: ' + orderNumber);
            });
        }
    </script>
</body>
</html>