<?php
/**
 * صفحة تفاصيل الطلب للعميل
 */
session_start();
require_once 'functions.php';
// التحقق من وضع الصيانة
if (getSetting('maintenance_mode', '0') == '1' && !isset($_SESSION['admin_id'])) {
    header('Location: maintenance.php');
    exit;
}
$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$order = getOrder($orderId);

if (!$order) {
    header('Location: index.php');
    exit;
}

$storeName = getSetting('store_name', 'متجر إلكتروني');

$storeDescription = getSetting('store_description', '');
// حساب الشحن المتوقع
$shippingDays = 2;
switch ($order['governorate']) {
    case 'القاهرة':
    case 'الجيزة':
        $shippingDays = 2;
        break;
    case 'الإسكندرية':
        $shippingDays = 3;
        break;
    default:
        $shippingDays = 5;
}

$estimatedDelivery = date('Y-m-d', strtotime("+$shippingDays days", strtotime($order['created_at'])));

// حالة الطلب
$statusSteps = [
    'pending' => ['label' => 'قيد الانتظار', 'icon' => 'clock', 'color' => '#f59e0b'],
    'confirmed' => ['label' => 'مؤكد', 'icon' => 'check', 'color' => '#3b82f6'],
    'processing' => ['label' => 'قيد التجهيز', 'icon' => 'box', 'color' => '#8b5cf6'],
    'shipped' => ['label' => 'تم الشحن', 'icon' => 'truck', 'color' => '#06b6d4'],
    'delivered' => ['label' => 'تم التوصيل', 'icon' => 'check-circle', 'color' => '#10b981'],
    'cancelled' => ['label' => 'ملغي', 'icon' => 'times-circle', 'color' => '#ef4444']
];

$currentStatus = $order['status'];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تفاصيل الطلب #<?= htmlspecialchars($order['order_number']) ?> - <?= htmlspecialchars($storeName) ?></title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .order-page { padding: 40px 0; }
        .order-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            padding: 40px;
            border-radius: 16px;
            margin-bottom: 30px;
        }
        .order-header h1 { font-size: 32px; margin-bottom: 10px; }
        .order-header .order-meta {
            display: flex;
            gap: 30px;
            flex-wrap: wrap;
            margin-top: 20px;
        }
        .order-meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        /* حالة الطلب */
        .order-status-tracker {
            background: #fff;
            padding: 40px;
            border-radius: 16px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.07);
        }
        .status-steps {
            display: flex;
            justify-content: space-between;
            position: relative;
            margin: 40px 0;
        }
        .status-steps::before {
            content: '';
            position: absolute;
            top: 25px;
            right: 0;
            left: 0;
            height: 4px;
            background: #e2e8f0;
            z-index: 0;
        }
        .status-step {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            z-index: 1;
        }
        .status-step-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: #94a3b8;
            margin-bottom: 10px;
            transition: all 0.3s;
        }
        .status-step.active .status-step-icon {
            background: var(--step-color);
            color: #fff;
            transform: scale(1.1);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        .status-step.completed .status-step-icon {
            background: #10b981;
            color: #fff;
        }
        .status-step-label {
            font-size: 14px;
            color: #64748b;
            text-align: center;
        }
        .status-step.active .status-step-label {
            color: #1e293b;
            font-weight: 600;
        }
        
        /* تفاصيل الطلب */
        .order-details-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        .card {
            background: #fff;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.07);
        }
        .card-title {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 20px;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        /* العناصر */
        .order-item {
            display: flex;
            gap: 20px;
            padding: 20px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        .order-item:last-child { border-bottom: none; }
        .order-item-image {
            width: 80px;
            height: 80px;
            border-radius: 8px;
            overflow: hidden;
        }
        .order-item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .order-item-details {
            flex: 1;
        }
        .order-item-title {
            font-weight: 600;
            margin-bottom: 5px;
            color: #1e293b;
        }
        .order-item-quantity {
            color: #64748b;
            font-size: 14px;
        }
        .order-item-price {
            font-size: 18px;
            font-weight: 700;
            color: #2563eb;
        }
        
        /* الملخص */
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        .summary-row:last-child {
            border-bottom: none;
            padding-top: 15px;
            margin-top: 15px;
            border-top: 2px solid #1e293b;
        }
        .summary-row.total {
            font-size: 20px;
            font-weight: 700;
            color: #1e293b;
        }
        
        /* العنوان */
        .address-block {
            background: #f8fafc;
            padding: 20px;
            border-radius: 12px;
            line-height: 1.8;
            color: #475569;
        }
        
        /* الأزرار */
        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }
        .btn {
            padding: 12px 24px;
            border-radius: 10px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }
        .btn-primary {
            background: #2563eb;
            color: #fff;
        }
        .btn-primary:hover {
            background: #1d4ed8;
            transform: translateY(-2px);
        }
        .btn-secondary {
            background: #f1f5f9;
            color: #475569;
        }
        .btn-secondary:hover {
            background: #e2e8f0;
        }
        
        @media print {
            .site-header, .site-footer, .action-buttons { display: none; }
        }
        
        @media (max-width: 768px) {
            .order-details-grid {
                grid-template-columns: 1fr;
            }
            .status-steps {
                flex-wrap: wrap;
            }
            .order-header .order-meta {
                flex-direction: column;
                gap: 10px;
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
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-home"></i> الرئيسية
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Order Details -->
    <main class="order-page">
        <div class="container">
            <!-- Order Header -->
            <div class="order-header">
                <h1>الطلب #<?= htmlspecialchars($order['order_number']) ?></h1>
                <div class="order-meta">
                    <div class="order-meta-item">
                        <i class="fas fa-calendar"></i>
                        <span>تاريخ الطلب: <?= date('Y-m-d H:i', strtotime($order['created_at'])) ?></span>
                    </div>
                    <div class="order-meta-item">
                        <i class="fas fa-credit-card"></i>
                        <span>
                            <?php
                            $payment_methods = [
                                'cod' => 'الدفع عند الاستلام',
                                'visa' => 'بطاقة ائتمان',
                                'instapay' => 'إنستاباي',
                                'vodafone_cash' => 'فودافون كاش',
                                'fawry' => 'فوري'
                            ];
                            echo $payment_methods[$order['payment_method']] ?? $order['payment_method'];
                            ?>
                        </span>
                    </div>
                    <div class="order-meta-item">
                        <i class="fas fa-truck"></i>
                        <span>التوصيل المتوقع: <?= $estimatedDelivery ?></span>
                    </div>
                </div>
            </div>

            <!-- Order Status Tracker -->
            <?php if ($currentStatus !== 'cancelled'): ?>
            <div class="order-status-tracker">
                <h2 class="card-title">
                    <i class="fas fa-route"></i>
                    تتبع الطلب
                </h2>
                <div class="status-steps">
                    <?php
                    $steps = ['pending', 'confirmed', 'processing', 'shipped', 'delivered'];
                    $currentIndex = array_search($currentStatus, $steps);
                    
                    foreach ($steps as $index => $step):
                        $stepInfo = $statusSteps[$step];
                        $isActive = $step === $currentStatus;
                        $isCompleted = $index < $currentIndex;
                        $class = $isActive ? 'active' : ($isCompleted ? 'completed' : '');
                    ?>
                        <div class="status-step <?= $class ?>" style="--step-color: <?= $stepInfo['color'] ?>">
                            <div class="status-step-icon">
                                <i class="fas fa-<?= $stepInfo['icon'] ?>"></i>
                            </div>
                            <div class="status-step-label"><?= $stepInfo['label'] ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($order['tracking_number']): ?>
                    <div style="text-align: center; margin-top: 20px; padding: 15px; background: #dbeafe; border-radius: 8px;">
                        <strong style="color: #1e40af;">رقم التتبع:</strong>
                        <span style="color: #1e293b; font-weight: 600;"><?= htmlspecialchars($order['tracking_number']) ?></span>
                    </div>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <div class="order-status-tracker" style="text-align: center;">
                <div class="status-step-icon" style="width: 80px; height: 80px; font-size: 40px; background: #ef4444; color: #fff; margin: 0 auto 20px;">
                    <i class="fas fa-times-circle"></i>
                </div>
                <h2 style="color: #ef4444; margin-bottom: 10px;">تم إلغاء الطلب</h2>
                <p style="color: #64748b;">تم إلغاء هذا الطلب. إذا كان لديك أي استفسار، يرجى التواصل معنا.</p>
            </div>
            <?php endif; ?>

            <!-- Order Details Grid -->
            <div class="order-details-grid">
 <!-- Order Items -->
    <div class="card">
        <h2 class="card-title">
            <i class="fas fa-box"></i>
            عناصر الطلب
        </h2>
        <?php foreach ($order['items'] as $item): 
            $storeType = $item['store_type'] ?? 'main';
            $storeName = $item['store_name'] ?? 'المتجر الرئيسي';
            $storeClass = $storeType === 'customer' ? 'customer-store-item' : 'main-store-item';
        ?>
            <div class="order-item <?= $storeClass ?>">
                <div class="order-item-image">
                    <img src="<?= htmlspecialchars($item['main_image'] ?: 'assets/images/placeholder.jpg') ?>" 
                         alt="<?= htmlspecialchars($item['product_title']) ?>">
                </div>
                <div class="order-item-details">
                    <div class="order-item-title"><?= htmlspecialchars($item['product_title']) ?></div>
                    <div class="order-item-quantity">الكمية: <?= $item['qty'] ?></div>
                    <div class="store-info-small">
                        <span class="store-badge-small store-<?= $storeType ?>">
                            <i class="fas <?= $storeType === 'customer' ? 'fa-user' : 'fa-store' ?>"></i>
                            <?= htmlspecialchars($storeName) ?>
                        </span>
                    </div>
                </div>
                <div class="order-item-price">
                    <?= formatPrice($item['total_price']) ?>
                </div>
            </div>
        <?php endforeach; ?>

        <!-- Stores Summary -->
        <div class="stores-summary">
            <h4><i class="fas fa-store"></i> ملخص المتاجر</h4>
            <?php
            $storesSummary = [];
            foreach ($order['items'] as $item) {
                $storeType = $item['store_type'] ?? 'main';
                $storeName = $item['store_name'] ?? 'المتجر الرئيسي';
                $itemTotal = $item['total_price'];
                
                if (!isset($storesSummary[$storeName])) {
                    $storesSummary[$storeName] = [
                        'type' => $storeType,
                        'total' => 0,
                        'count' => 0
                    ];
                }
                
                $storesSummary[$storeName]['total'] += $itemTotal;
                $storesSummary[$storeName]['count']++;
            }
            
            foreach ($storesSummary as $storeName => $storeData):
                $storeClass = $storeData['type'] === 'customer' ? 'store-customer' : 'store-main';
            ?>
                <div class="store-summary-item">
                    <span class="store-badge-small <?= $storeClass ?>">
                        <i class="fas <?= $storeData['type'] === 'customer' ? 'fa-user' : 'fa-store' ?>"></i>
                        <?= htmlspecialchars($storeName) ?>
                        <small>(<?= $storeData['count'] ?> منتج)</small>
                    </span>
                    <span style="font-weight: 600; color: #2563eb;">
                        <?= formatPrice($storeData['total']) ?>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
                <!-- Order Summary -->
                <div>
                    <div class="card">
                        <h2 class="card-title">
                            <i class="fas fa-receipt"></i>
                            ملخص الطلب
                        </h2>
                        <div class="summary-row">
                            <span>المجموع الفرعي</span>
                            <span><?= formatPrice($order['subtotal']) ?></span>
                        </div>
                        <div class="summary-row">
                            <span>الشحن</span>
                            <span><?= formatPrice($order['shipping_cost']) ?></span>
                        </div>
                        <?php if ($order['discount_amount'] > 0): ?>
                            <div class="summary-row" style="color: #10b981;">
                                <span>الخصم</span>
                                <span>-<?= formatPrice($order['discount_amount']) ?></span>
                            </div>
                        <?php endif; ?>
                        <div class="summary-row total">
                            <span>الإجمالي</span>
                            <span><?= formatPrice($order['total']) ?></span>
                        </div>

                        <?php if ($order['payment_status'] === 'paid'): ?>
                            <div style="text-align: center; margin-top: 15px; padding: 10px; background: #dcfce7; border-radius: 8px; color: #166534;">
                                <i class="fas fa-check-circle"></i>
                                <strong>مدفوع</strong>
                            </div>
                        <?php else: ?>
                            <div style="text-align: center; margin-top: 15px; padding: 10px; background: #fef3c7; border-radius: 8px; color: #92400e;">
                                <i class="fas fa-clock"></i>
                                <strong>في انتظار الدفع</strong>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Customer Info -->
                    <div class="card" style="margin-top: 20px;">
                        <h2 class="card-title">
                            <i class="fas fa-user"></i>
                            معلومات العميل
                        </h2>
                        <div style="line-height: 2; color: #475569;">
                            <div><strong>الاسم:</strong> <?= htmlspecialchars($order['customer_name']) ?></div>
                            <div><strong>الهاتف:</strong> <?= htmlspecialchars($order['customer_phone']) ?></div>
                            <?php if ($order['customer_email']): ?>
                                <div><strong>البريد:</strong> <?= htmlspecialchars($order['customer_email']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Shipping Address -->
                    <div class="card" style="margin-top: 20px;">
                        <h2 class="card-title">
                            <i class="fas fa-map-marker-alt"></i>
                            عنوان الشحن
                        </h2>
                        <div class="address-block">
                            <div><strong><?= htmlspecialchars($order['governorate']) ?></strong></div>
                            <?php if ($order['city']): ?>
                                <div><?= htmlspecialchars($order['city']) ?></div>
                            <?php endif; ?>
                            <div><?= nl2br(htmlspecialchars($order['shipping_address'])) ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notes -->
            <?php if ($order['notes']): ?>
                <div class="card">
                    <h2 class="card-title">
                        <i class="fas fa-sticky-note"></i>
                        ملاحظات الطلب
                    </h2>
                    <div style="background: #f8fafc; padding: 15px; border-radius: 8px; color: #475569;">
                        <?= nl2br(htmlspecialchars($order['notes'])) ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Actions -->
            <div class="action-buttons">
                <button onclick="window.print()" class="btn btn-primary">
                    <i class="fas fa-print"></i>
                    طباعة الطلب
                </button>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-shopping-bag"></i>
                    متابعة التسوق
                </a>
                <?php if ($currentStatus === 'pending' || $currentStatus === 'confirmed'): ?>
                    <button onclick="confirmCancel()" class="btn btn-secondary" style="background: #fef2f2; color: #991b1b;">
                        <i class="fas fa-times"></i>
                        إلغاء الطلب
                    </button>
                <?php endif; ?>
                <a href="contact.php" class="btn btn-secondary">
                    <i class="fas fa-headset"></i>
                    الدعم الفني
                </a>
            </div>

            <!-- Help Section -->
            <div class="card" style="margin-top: 30px; background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);">
                <h2 class="card-title">
                    <i class="fas fa-question-circle"></i>
                    هل تحتاج مساعدة؟
                </h2>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-top: 20px;">
                    <div style="text-align: center;">
                        <i class="fas fa-phone" style="font-size: 30px; color: #0284c7; margin-bottom: 10px;"></i>
                        <h4 style="margin-bottom: 5px;">اتصل بنا</h4>
                        <p style="color: #64748b;"><?= getSetting('store_phone', '01234567890') ?></p>
                    </div>
                    <div style="text-align: center;">
                        <i class="fas fa-envelope" style="font-size: 30px; color: #0284c7; margin-bottom: 10px;"></i>
                        <h4 style="margin-bottom: 5px;">راسلنا</h4>
                        <p style="color: #64748b;"><?= getSetting('store_email', 'info@store.com') ?></p>
                    </div>
                    <?php if ($whatsapp = getSetting('whatsapp_number')): ?>
                        <div style="text-align: center;">
                            <i class="fab fa-whatsapp" style="font-size: 30px; color: #0284c7; margin-bottom: 10px;"></i>
                            <h4 style="margin-bottom: 5px;">واتساب</h4>
                            <a href="https://wa.me/<?= $whatsapp ?>?text=استفسار عن الطلب <?= $order['order_number'] ?>" 
                               style="color: #0284c7;">تواصل معنا</a>
                        </div>
                    <?php endif; ?>
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
        function confirmCancel() {
            if (confirm('هل أنت متأكد من إلغاء هذا الطلب؟\nلن تتمكن من التراجع عن هذا الإجراء.')) {
                // إرسال طلب إلغاء
                fetch('api/orders.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        action: 'cancel',
                        order_id: <?= $orderId ?>
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('تم إلغاء الطلب بنجاح');
                        location.reload();
                    } else {
                        alert(data.message || 'حدث خطأ أثناء إلغاء الطلب');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('حدث خطأ في الاتصال');
                });
            }
        }

        // مشاركة الطلب
        function shareOrder() {
            if (navigator.share) {
                navigator.share({
                    title: 'طلب #<?= $order['order_number'] ?>',
                    text: 'تفاصيل طلبي من <?= htmlspecialchars($storeName) ?>',
                    url: window.location.href
                });
            }
        }
    </script>
</body>
</html>