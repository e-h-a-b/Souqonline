<?php
/**
 * صفحة شراء الباقات
 */
session_start();
require_once 'functions.php';

// التحقق من وضع الصيانة
if (getSetting('maintenance_mode', '0') == '1' && !isset($_SESSION['admin_id'])) {
    header('Location: maintenance.php');
    exit;
}

// التحقق من تسجيل الدخول
if (!isset($_SESSION['customer_id'])) {
    header('Location: account.php?redirect=packages.php');
    exit;
}

$storeName = getSetting('store_name', 'متجر إلكتروني');
$storeDescription = getSetting('store_description', '');
$cartCount = getCartCount();

// جلب الباقات
$packages = getActivePackages();
$featuredPackages = getFeaturedPackages();

// معالجة شراء الباقة
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buy_package'])) {
    $package_id = (int)$_POST['package_id'];
    
    $result = createPackageOrder($_SESSION['customer_id'], $package_id);
    
    if ($result['success']) {
        header('Location: checkout_package.php?order_id=' . $result['order_id']);
        exit;
    } else {
        $error = $result['message'];
    }
}

// جلب طلبات الباقات السابقة
$customerOrders = getCustomerPackageOrders($_SESSION['customer_id'], 5);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>شراء الباقات - <?= htmlspecialchars($storeName) ?></title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .packages-page {
            padding: 3rem 0;
            background: #f8fafc;
            min-height: 100vh;
        }

        .packages-header {
            text-align: center;
            margin-bottom: 3rem;
            padding: 2rem;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-radius: 12px;
        }

        .packages-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .package-card {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            border: 2px solid transparent;
            position: relative;
            overflow: hidden;
        }

        .package-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }

        .package-card.featured {
            border-color: #f59e0b;
            transform: scale(1.05);
        }

        .package-card.featured::before {
            content: 'مميز';
            position: absolute;
            top: 15px;
            left: 15px;
            background: #f59e0b;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
        }

        .package-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 1rem;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
        }

        .package-name {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: #1e293b;
        }

        .package-price {
            font-size: 2.5rem;
            font-weight: 700;
            color: #059669;
            margin-bottom: 1rem;
        }

        .package-points {
            font-size: 1.25rem;
            color: #6b7280;
            margin-bottom: 1.5rem;
        }

        .points-breakdown {
            background: #f8fafc;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            text-align: right;
        }

        .points-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .points-item:last-child {
            margin-bottom: 0;
            padding-top: 0.5rem;
            border-top: 1px solid #e5e7eb;
            font-weight: 600;
            color: #059669;
        }

        .package-features {
            text-align: right;
            margin-bottom: 2rem;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
            color: #6b7280;
        }

        .feature-item i {
            color: #10b981;
        }

        .btn-package {
            width: 100%;
            padding: 1rem;
            font-size: 1.1rem;
            font-weight: 600;
        }

        .orders-history {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            margin-top: 3rem;
        }

        .order-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            border-bottom: 1px solid #e5e7eb;
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
        }

        .badge-success { background: #dcfce7; color: #166534; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
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
                    <!-- زر النقاط -->
                    <?php if (isset($_SESSION['customer_id']) && getSetting('points_enabled', '1') == '1'): ?>
                        <?php 
                        $customer_points = getCustomerPoints($_SESSION['customer_id']);
                        $available_points = $customer_points['available_points'] ?? 0;
                        ?>
                        <a href="packages.php" class="points-btn" title="شراء الباقات">
                            <i class="fas fa-crown"></i>
                            <span class="points-count" id="points-count">
                                <?= number_format($available_points) ?>
                            </span>
                        </a>
                    <?php endif; ?>

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

    <main class="packages-page">
        <div class="container">
            <?php if ($error): ?>
                <div style="background: #f8d7da; color: #721c24; padding: 1rem; border-radius: 8px; margin-bottom: 2rem;">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <div class="packages-header">
                <h1>باقات النقاط المميزة</h1>
                <p>اشترِ باقات النقاط واحصل على مكافآت إضافية</p>
                <div style="margin-top: 1rem; font-size: 1.1rem;">
                    <i class="fas fa-coins"></i>
                    رصيدك الحالي: <strong><?= number_format($available_points) ?></strong> نقطة
                </div>
            </div>

            <div class="packages-grid">
                <?php foreach ($packages as $package): ?>
                    <div class="package-card <?= $package['is_featured'] ? 'featured' : '' ?>">
                        <div class="package-icon">
                            <i class="fas fa-crown"></i>
                        </div>
                        
                        <h2 class="package-name"><?= htmlspecialchars($package['name']) ?></h2>
                        
                        <div class="package-price">
                            <?= formatPrice($package['price']) ?>
                        </div>
                        
                        <div class="package-points">
                            <?= number_format($package['points'] + $package['bonus_points']) ?> نقطة
                        </div>

                        <div class="points-breakdown">
                            <div class="points-item">
                                <span>النقاط الأساسية:</span>
                                <span><?= number_format($package['points']) ?></span>
                            </div>
                            <?php if ($package['bonus_points'] > 0): ?>
                                <div class="points-item">
                                    <span>نقاط المكافأة:</span>
                                    <span style="color: #059669;">+<?= number_format($package['bonus_points']) ?></span>
                                </div>
                            <?php endif; ?>
                            <div class="points-item">
                                <span>المجموع:</span>
                                <span><?= number_format($package['points'] + $package['bonus_points']) ?></span>
                            </div>
                        </div>

                        <?php if ($package['description']): ?>
                            <div class="package-features">
                                <p><?= nl2br(htmlspecialchars($package['description'])) ?></p>
                            </div>
                        <?php endif; ?>

                        <form method="post" onsubmit="return confirm('هل تريد شراء هذه الباقة؟')">
                            <input type="hidden" name="package_id" value="<?= $package['id'] ?>">
                            <button type="submit" name="buy_package" class="btn btn-primary btn-package">
                                <i class="fas fa-shopping-cart"></i> شراء الباقة
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- سجل الطلبات -->
            <div class="orders-history">
                <h2 style="margin-bottom: 1.5rem;">سجل طلبات الباقات</h2>
                
                <?php if (empty($customerOrders)): ?>
                    <p style="text-align: center; color: #6b7280; padding: 2rem;">
                        لا توجد طلبات باقات سابقة
                    </p>
                <?php else: ?>
                    <div class="orders-list">
                        <?php foreach ($customerOrders as $order): ?>
                            <div class="order-item">
                                <div>
                                    <strong><?= htmlspecialchars($order['package_name']) ?></strong>
                                    <div style="color: #6b7280; font-size: 0.875rem; margin-top: 0.25rem;">
                                        <?= $order['order_number'] ?> - 
                                        <?= date('Y-m-d H:i', strtotime($order['created_at'])) ?>
                                    </div>
                                </div>
                                <div style="text-align: left;">
                                    <div style="font-weight: 600; color: #059669; margin-bottom: 0.5rem;">
                                        +<?= number_format($order['points_amount']) ?> نقطة
                                    </div>
                                    <div>
                                        <?php
                                        $badge_class = 'badge-warning';
                                        $status_text = $order['payment_status'];
                                        if ($order['payment_status'] === 'paid') {
                                            $badge_class = 'badge-success';
                                            $status_text = 'مدفوع';
                                        } elseif ($order['payment_status'] === 'failed') {
                                            $badge_class = 'badge-danger';
                                            $status_text = 'فاشل';
                                        }
                                        ?>
                                        <span class="badge <?= $badge_class ?>"><?= $status_text ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="site-footer">
        <div class="container">
            <!-- نفس الفوتر الموجود في الصفحات الأخرى -->
        </div>
    </footer>

    <script src="assets/js/app.js"></script>
    <script>
        // تحديث عداد النقاط
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

        // تحديث عند تحميل الصفحة
        document.addEventListener('DOMContentLoaded', function() {
            updatePointsCount();
        });
    </script>
</body>
</html>