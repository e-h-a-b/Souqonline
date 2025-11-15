<?php
/**
 * لوحة تحكم المتجر الإلكتروني
 */
session_start();
require_once '../config.php';
require_once '../functions.php';

// التحقق من تسجيل الدخول
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// جلب الإحصائيات
$stats = [];
// جلب إحصائيات النقاط
$stmt = $pdo->query("SELECT SUM(points) as total_points FROM customer_points");
$stats['total_points'] = $stmt->fetchColumn() ?: 0;

$stmt = $pdo->query("SELECT COUNT(DISTINCT customer_id) as active_members FROM customer_points WHERE points > 0");
$stats['active_members'] = $stmt->fetchColumn() ?: 0;
 // في قسم جلب الإحصائيات (بعد الإحصائيات الحالية)
// إحصائيات التفاوض
$stmt = $pdo->query("SELECT COUNT(*) as total FROM product_negotiations WHERE status = 'pending'");
$stats['pending_negotiations'] = $stmt->fetchColumn();

// إحصائيات المزايدات النشطة
$stmt = $pdo->query("SELECT COUNT(*) as total FROM products WHERE auction_enabled = 1 AND auction_end_time > NOW()");
$stats['active_auctions'] = $stmt->fetchColumn();

// إحصائيات العدادات التنازلية
$stmt = $pdo->query("SELECT COUNT(*) as total FROM price_countdowns WHERE is_active = 1 AND countdown_end > NOW()");
$stats['active_countdowns'] = $stmt->fetchColumn();

// إحصائيات العروض الخاصة
$stmt = $pdo->query("SELECT COUNT(*) as total FROM products WHERE special_offer_type != 'none' AND is_active = 1");
$stats['special_offers'] = $stmt->fetchColumn();
 
 // جلب إحصائيات المحفظة
$stmt = $pdo->query("SELECT COUNT(*) as total FROM customer_wallets WHERE balance > 0");
$stats['wallet_users'] = $stmt->fetchColumn() ?: 0;

$stmt = $pdo->query("SELECT SUM(balance) as total FROM customer_wallets");
$stats['total_wallet_balance'] = $stmt->fetchColumn() ?: 0;

// جلب إحصائيات المشرفين
$stmt = $pdo->query("SELECT COUNT(*) as total FROM admins WHERE is_active = 1");
$stats['active_admins'] = $stmt->fetchColumn() ?: 0;

$stmt = $pdo->query("SELECT COUNT(*) as total FROM admin_roles WHERE is_active = 1");
$stats['admin_roles'] = $stmt->fetchColumn() ?: 0;
// عدد الطلبات
$stmt = $pdo->query("SELECT COUNT(*) as total FROM orders");
$stats['total_orders'] = $stmt->fetchColumn();

// الطلبات الجديدة
$stmt = $pdo->query("SELECT COUNT(*) as total FROM orders WHERE status = 'pending'");
$stats['pending_orders'] = $stmt->fetchColumn();

// عدد المنتجات
$stmt = $pdo->query("SELECT COUNT(*) as total FROM products WHERE is_active = 1");
$stats['total_products'] = $stmt->fetchColumn();

// عدد العملاء
$stmt = $pdo->query("SELECT COUNT(*) as total FROM customers");
$stats['total_customers'] = $stmt->fetchColumn();

// إجمالي المبيعات
$stmt = $pdo->query("SELECT SUM(total) as total FROM orders WHERE payment_status = 'paid'");
$stats['total_revenue'] = $stmt->fetchColumn() ?: 0;

// مبيعات هذا الشهر
$stmt = $pdo->query("SELECT SUM(total) as total FROM orders WHERE payment_status = 'paid' AND MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())");
$stats['month_revenue'] = $stmt->fetchColumn() ?: 0;

// الطلبات الأخيرة
$stmt = $pdo->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 10");
$recent_orders = $stmt->fetchAll();

// المنتجات الأكثر مبيعاً
$stmt = $pdo->query("SELECT * FROM products WHERE is_active = 1 ORDER BY orders_count DESC LIMIT 5");
$top_products = $stmt->fetchAll();

$adminName = $_SESSION['admin_name'] ?? 'المسؤول';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم - <?= getSetting('store_name') ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8fafc;
            color: #334155;
        }
        .admin-wrapper { display: flex; min-height: 100vh; }
        
        /* Sidebar */
        .sidebar {
            width: 260px;
            background: #1e293b;
            color: #fff;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }
        .sidebar-header {
            padding: 25px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .sidebar-header h2 {
            font-size: 20px;
            color: #fff;
        }
        .sidebar-menu { padding: 20px 0; }
        .menu-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 20px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.3s;
        }
        .menu-item:hover, .menu-item.active {
            background: rgba(255,255,255,0.1);
            color: #fff;
        }
        .menu-item i { width: 20px; }
        
        /* Main Content */
        .main-content {
            flex: 1;
            margin-right: 260px;
            padding: 30px;
        }
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .page-title h1 { font-size: 28px; color: #1e293b; }
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: #fff;
        }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: #fff;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: transform 0.3s;
        }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
        .stat-info h3 { font-size: 14px; color: #64748b; margin-bottom: 8px; }
        .stat-info .number { font-size: 32px; font-weight: 700; color: #1e293b; }
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        .stat-card.blue .stat-icon { background: #dbeafe; color: #2563eb; }
        .stat-card.green .stat-icon { background: #dcfce7; color: #16a34a; }
        .stat-card.orange .stat-icon { background: #fed7aa; color: #ea580c; }
        .stat-card.purple .stat-icon { background: #e9d5ff; color: #9333ea; }
        
        /* Tables */
        .card {
            background: #fff;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .card-header h2 { font-size: 20px; color: #1e293b; }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }
        .btn-primary { background: #2563eb; color: #fff; }
        .btn-primary:hover { background: #1d4ed8; }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px;
            text-align: right;
            border-bottom: 1px solid #e2e8f0;
        }
        th {
            background: #f8fafc;
            font-weight: 600;
            color: #475569;
        }
        tr:hover { background: #f8fafc; }
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-success { background: #dcfce7; color: #166534; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        .badge-info { background: #dbeafe; color: #1e40af; }
        
        /* Charts Placeholder */
        .chart-container {
            height: 300px;
            background: #f8fafc;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #94a3b8;
        }
    </style>
<?php

// في أعلى index.php، بعد getProducts()
$popupAds = getActiveAds('popup');
$sideAds = getActiveAds('side_button');
$betweenAds = getActiveAds('between_products');
?>
</head>
<body>
    <div class="admin-wrapper">
        <!-- Sidebar -->
                <!-- تضمين القائمة الجانبية -->
        <?php include 'sidebar.php'; ?>
        <!-- Main Content -->
        <main class="main-content">
            <div class="top-bar">
                <div class="page-title">
                    <h1>مرحباً، <?= htmlspecialchars($adminName) ?></h1>
                    <p style="color: #64748b; margin-top: 5px;">إليك نظرة عامة على متجرك</p>
                </div>
                <div class="user-info">
                    <div class="user-avatar"><?= mb_substr($adminName, 0, 1) ?></div>
                    <div>
                        <strong><?= htmlspecialchars($adminName) ?></strong>
                        <p style="font-size: 13px; color: #64748b;">مسؤول</p>
                    </div>
                </div>
            </div>

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card blue">
                    <div class="stat-info">
                        <h3>إجمالي الطلبات</h3>
                        <div class="number"><?= number_format($stats['total_orders']) ?></div>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                </div>

                <div class="stat-card orange">
                    <div class="stat-info">
                        <h3>طلبات جديدة</h3>
                        <div class="number"><?= number_format($stats['pending_orders']) ?></div>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>

                <div class="stat-card green">
                    <div class="stat-info">
                        <h3>إجمالي المبيعات</h3>
                        <div class="number"><?= number_format($stats['total_revenue'], 0) ?></div>
                        <small style="color: #64748b;">ج.م</small>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                </div>

                <div class="stat-card purple">
                    <div class="stat-info">
                        <h3>مبيعات الشهر</h3>
                        <div class="number"><?= number_format($stats['month_revenue'], 0) ?></div>
                        <small style="color: #64748b;">ج.م</small>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-calendar"></i>
                    </div>
                </div>
            
                <div class="stat-card" style="background: linear-gradient(135deg, #f093fb, #f5576c); color: white;">
                    <div class="stat-info">
                        <h3 style="color: white;">إجمالي النقاط</h3>
                        <div class="number" style="color: white;"><?= number_format($stats['total_points']) ?></div>
                    </div>
                    <div class="stat-icon" style="background: rgba(255,255,255,0.2); color: white;">
                        <i class="fas fa-coins"></i>
                    </div>
                </div>
            
                <div class="stat-card" style="background: linear-gradient(135deg, #4facfe, #00f2fe); color: white;">
                    <div class="stat-info">
                        <h3 style="color: white;">أعضاء النقاط</h3>
                        <div class="number" style="color: white;"><?= number_format($stats['active_members']) ?></div>
                    </div>
                    <div class="stat-icon" style="background: rgba(255,255,255,0.2); color: white;">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            		
<!-- في قسم الإحصائيات -->

<div class="stat-card">
    <div class="stat-icon" style="background: #f0f9ff; color: #0369a1;">
        <i class="fas fa-share-alt"></i>
    </div>
    <div class="stat-info">
        <h3><?= number_format($referral_stats['total_referrers'] ?? 0) ?></h3>
        <p>المحيلين النشطين</p>
    </div>
</div>

<div class="stat-card">
    <div class="stat-icon" style="background: #f0fdf4; color: #16a34a;">
        <i class="fas fa-handshake"></i>
    </div>
    <div class="stat-info">
        <h3><?= number_format($referral_stats['successful_referrals'] ?? 0) ?></h3>
        <p>إحالات ناجحة</p>
    </div>
</div>					
			<!-- في قسم الإحصائيات بعد البطاقات الحالية -->
<div class="stat-card" style="background: linear-gradient(135deg, #ffd700, #ffed4e); color: white;">
    <div class="stat-info">
        <h3 style="color: white;">عروض خاصة</h3>
        <div class="number" style="color: white;"><?= number_format($stats['special_offers']) ?></div>
    </div>
    <div class="stat-icon" style="background: rgba(255,255,255,0.2); color: white;">
        <i class="fas fa-gift"></i>
    </div>
</div>

<div class="stat-card" style="background: linear-gradient(135deg, #ff6b6b, #ff8e8e); color: white;">
    <div class="stat-info">
        <h3 style="color: white;">مزايدات نشطة</h3>
        <div class="number" style="color: white;"><?= number_format($stats['active_auctions']) ?></div>
    </div>
    <div class="stat-icon" style="background: rgba(255,255,255,0.2); color: white;">
        <i class="fas fa-gavel"></i>
    </div>
</div>

<div class="stat-card" style="background: linear-gradient(135deg, #4ecdc4, #88dac8); color: white;">
    <div class="stat-info">
        <h3 style="color: white;">عدادات تنازلية</h3>
        <div class="number" style="color: white;"><?= number_format($stats['active_countdowns']) ?></div>
    </div>
    <div class="stat-icon" style="background: rgba(255,255,255,0.2); color: white;">
        <i class="fas fa-clock"></i>
    </div>
</div>

<div class="stat-card" style="background: linear-gradient(135deg, #a78bfa, #c4b5fd); color: white;">
    <div class="stat-info">
        <h3 style="color: white;">طلبات تفاوض</h3>
        <div class="number" style="color: white;"><?= number_format($stats['pending_negotiations']) ?></div>
    </div>
    <div class="stat-icon" style="background: rgba(255,255,255,0.2); color: white;">
        <i class="fas fa-handshake"></i>
    </div>
</div>
			
			<!-- بطاقات إحصائيات المحفظة والمشرفين -->
<div class="stat-card" style="background: linear-gradient(135deg, #10b981, #34d399); color: white;">
    <div class="stat-info">
        <h3 style="color: white;">رصيد المحافظ</h3>
        <div class="number" style="color: white;"><?= number_format($stats['total_wallet_balance'], 0) ?></div>
        <small style="color: rgba(255,255,255,0.8);">ج.م</small>
    </div>
    <div class="stat-icon" style="background: rgba(255,255,255,0.2); color: white;">
        <i class="fas fa-wallet"></i>
    </div>
</div>

<div class="stat-card" style="background: linear-gradient(135deg, #8b5cf6, #a78bfa); color: white;">
    <div class="stat-info">
        <h3 style="color: white;">عملاء بالمحفظة</h3>
        <div class="number" style="color: white;"><?= number_format($stats['wallet_users']) ?></div>
    </div>
    <div class="stat-icon" style="background: rgba(255,255,255,0.2); color: white;">
        <i class="fas fa-credit-card"></i>
    </div>
</div>

<div class="stat-card" style="background: linear-gradient(135deg, #f59e0b, #fbbf24); color: white;">
    <div class="stat-info">
        <h3 style="color: white;">المشرفين</h3>
        <div class="number" style="color: white;"><?= number_format($stats['active_admins']) ?></div>
    </div>
    <div class="stat-icon" style="background: rgba(255,255,255,0.2); color: white;">
        <i class="fas fa-user-shield"></i>
    </div>
</div>

<div class="stat-card" style="background: linear-gradient(135deg, #ec4899, #f472b6); color: white;">
    <div class="stat-info">
        <h3 style="color: white;">أدوار المشرفين</h3>
        <div class="number" style="color: white;"><?= number_format($stats['admin_roles']) ?></div>
    </div>
    <div class="stat-icon" style="background: rgba(255,255,255,0.2); color: white;">
        <i class="fas fa-users-cog"></i>
    </div>
</div>
			
			</div>

            <!-- Recent Orders -->
            <div class="card">
                <div class="card-header">
                    <h2>الطلبات الأخيرة</h2>
                    <a href="orders.php" class="btn btn-primary">
                        عرض الكل
                        <i class="fas fa-arrow-left"></i>
                    </a>
                </div>
                
                <?php if (empty($recent_orders)): ?>
                    <p style="text-align: center; color: #64748b; padding: 40px;">لا توجد طلبات حتى الآن</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>رقم الطلب</th>
                                <th>العميل</th>
                                <th>الإجمالي</th>
                                <th>الحالة</th>
                                <th>التاريخ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_orders as $order): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($order['order_number']) ?></strong></td>
                                    <td><?= htmlspecialchars($order['customer_name']) ?></td>
                                    <td><?= formatPrice($order['total']) ?></td>
                                    <td>
                                        <?php
                                        $badge_class = 'badge-info';
                                        $status_text = $order['status'];
                                        switch ($order['status']) {
                                            case 'pending': $badge_class = 'badge-warning'; $status_text = 'قيد الانتظار'; break;
                                            case 'confirmed': $badge_class = 'badge-info'; $status_text = 'مؤكد'; break;
                                            case 'processing': $badge_class = 'badge-info'; $status_text = 'قيد التجهيز'; break;
                                            case 'shipped': $badge_class = 'badge-info'; $status_text = 'تم الشحن'; break;
                                            case 'delivered': $badge_class = 'badge-success'; $status_text = 'تم التوصيل'; break;
                                            case 'cancelled': $badge_class = 'badge-danger'; $status_text = 'ملغي'; break;
                                        }
                                        ?>
                                        <span class="badge <?= $badge_class ?>"><?= $status_text ?></span>
                                    </td>
                                    <td><?= date('Y-m-d H:i', strtotime($order['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <!-- Top Products -->
            <div class="card">
                <div class="card-header">
                    <h2>المنتجات الأكثر مبيعاً</h2>
                    <a href="products.php" class="btn btn-primary">
                        عرض الكل
                        <i class="fas fa-arrow-left"></i>
                    </a>
                </div>

                <?php if (empty($top_products)): ?>
                    <p style="text-align: center; color: #64748b; padding: 40px;">لا توجد منتجات</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>المنتج</th>
                                <th>السعر</th>
                                <th>المخزون</th>
                                <th>عدد المبيعات</th>
                                <th>التقييم</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($top_products as $product): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($product['title']) ?></strong>
                                    </td>
                                    <td><?= formatPrice($product['final_price']) ?></td>
                                    <td>
                                        <?php if ($product['stock'] < 5): ?>
                                            <span class="badge badge-warning"><?= $product['stock'] ?></span>
                                        <?php else: ?>
                                            <?= $product['stock'] ?>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $product['orders_count'] ?></td>
                                    <td>
                                        <i class="fas fa-star" style="color: #fbbf24;"></i>
                                        <?= number_format($product['rating_avg'], 1) ?>
                                        (<?= $product['rating_count'] ?>)
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <!-- Quick Stats -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                <div class="card">
                    <h3 style="margin-bottom: 15px;">المنتجات</h3>
                    <div style="font-size: 36px; font-weight: 700; color: #2563eb; margin-bottom: 10px;">
                        <?= number_format($stats['total_products']) ?>
                    </div>
                    <p style="color: #64748b;">منتج نشط في المتجر</p>
                </div>

                <div class="card">
                    <h3 style="margin-bottom: 15px;">العملاء</h3>
                    <div style="font-size: 36px; font-weight: 700; color: #16a34a; margin-bottom: 10px;">
                        <?= number_format($stats['total_customers']) ?>
                    </div>
                    <p style="color: #64748b;">عميل مسجل</p>
                </div>
            </div>
        </main>
    </div>
</body>
</html>