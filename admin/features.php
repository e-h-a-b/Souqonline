<?php
/**
 * إدارة الخصائص المتقدمة
 */
session_start();
require_once '../config.php';
require_once '../functions.php';

// التحقق من تسجيل الدخول
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// معالجة النماذج
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_settings'])) {
        // تحديث إعدادات الخصائص
        $settings = [
            'negotiation_enabled' => $_POST['negotiation_enabled'] ?? '0',
            'negotiation_min_percentage' => $_POST['negotiation_min_percentage'] ?? '70',
            'auction_enabled' => $_POST['auction_enabled'] ?? '0',
            'countdown_enabled' => $_POST['countdown_enabled'] ?? '0',
            'special_offers_enabled' => $_POST['special_offers_enabled'] ?? '0'
        ];
        
        foreach ($settings as $key => $value) {
            updateSetting($key, $value);
        }
        
        // تسجيل النشاط
        logActivity($_SESSION['admin_id'], 'settings_updated', 'تم تحديث إعدادات الخصائص المتقدمة');
        
        $_SESSION['success'] = 'تم تحديث إعدادات الخصائص بنجاح';
        header('Location: features.php');
        exit;
    }
    
    if (isset($_POST['add_countdown'])) {
        // إضافة عداد تنازلي جديد
        $product_id = $_POST['product_id'];
        $new_price = $_POST['new_price'];
        $countdown_end = $_POST['countdown_end'];
        
        $stmt = $pdo->prepare("INSERT INTO price_countdowns (product_id, new_price, countdown_end) VALUES (?, ?, ?)");
        if ($stmt->execute([$product_id, $new_price, $countdown_end])) {
            // تسجيل النشاط
            logActivity($_SESSION['admin_id'], 'countdown_added', 'تم إضافة عداد تنازلي للمنتج #' . $product_id);
            
            $_SESSION['success'] = 'تم إضافة العداد التنازلي بنجاح';
        } else {
            $_SESSION['error'] = 'حدث خطأ أثناء إضافة العداد التنازلي';
        }
        
        header('Location: features.php');
        exit;
    }
    
    if (isset($_POST['update_negotiation'])) {
        // تحديث حالة التفاوض
        $negotiation_id = $_POST['negotiation_id'];
        $status = $_POST['status'];
        $counter_price = $_POST['counter_price'] ?? null;
        $admin_notes = $_POST['admin_notes'] ?? '';
        
        $stmt = $pdo->prepare("UPDATE product_negotiations SET status = ?, counter_price = ?, admin_notes = ?, updated_at = NOW() WHERE id = ?");
        if ($stmt->execute([$status, $counter_price, $admin_notes, $negotiation_id])) {
            // تسجيل النشاط
            logActivity($_SESSION['admin_id'], 'negotiation_updated', 'تم تحديث حالة التفاوض #' . $negotiation_id);
            
            $_SESSION['success'] = 'تم تحديث حالة التفاوض بنجاح';
        } else {
            $_SESSION['error'] = 'حدث خطأ أثناء تحديث التفاوض';
        }
        
        header('Location: features.php');
        exit;
    }
    
    if (isset($_POST['delete_countdown'])) {
        // حذف عداد تنازلي
        $countdown_id = $_POST['countdown_id'];
        
        $stmt = $pdo->prepare("DELETE FROM price_countdowns WHERE id = ?");
        if ($stmt->execute([$countdown_id])) {
            // تسجيل النشاط
            logActivity($_SESSION['admin_id'], 'countdown_deleted', 'تم حذف العداد التنازلي #' . $countdown_id);
            
            $_SESSION['success'] = 'تم حذف العداد التنازلي بنجاح';
        } else {
            $_SESSION['error'] = 'حدث خطأ أثناء حذف العداد التنازلي';
        }
        
        header('Location: features.php');
        exit;
    }
    
    // إضافة معالجة جديدة للخصائص المتقدمة للمنتجات
    if (isset($_POST['update_product_features'])) {
        $product_id = $_POST['product_id'];
        $product_condition = $_POST['product_condition'];
        $special_offer_type = $_POST['special_offer_type'];
        $special_offer_value = $_POST['special_offer_value'];
        $auction_enabled = $_POST['auction_enabled'] ?? '0';
        $auction_end_time = $_POST['auction_end_time'];
        $starting_price = $_POST['starting_price'];
        
        // تحديث خصائص المنتج
        $stmt = $pdo->prepare("UPDATE products SET 
                              product_condition = ?, 
                              special_offer_type = ?, 
                              special_offer_value = ?, 
                              auction_enabled = ?, 
                              auction_end_time = ?, 
                              starting_price = ?,
                              updated_at = NOW()
                              WHERE id = ?");
        
        if ($stmt->execute([
            $product_condition, 
            $special_offer_type, 
            $special_offer_value, 
            $auction_enabled, 
            $auction_end_time ?: null, 
            $starting_price ?: 0,
            $product_id
        ])) {
            // تسجيل النشاط
            logActivity($_SESSION['admin_id'], 'product_features_updated', 'تم تحديث خصائص المنتج #' . $product_id);
            
            $_SESSION['success'] = 'تم تحديث خصائص المنتج بنجاح';
        } else {
            $_SESSION['error'] = 'حدث خطأ أثناء تحديث خصائص المنتج';
        }
        
        header('Location: features.php');
        exit;
    }
    
    if (isset($_POST['add_auction_bid'])) {
        // إضافة مزايدة يدوية (للتجربة)
        $product_id = $_POST['auction_product_id'];
        $customer_id = $_POST['customer_id'];
        $bid_amount = $_POST['bid_amount'];
        
        $stmt = $pdo->prepare("INSERT INTO product_bids (product_id, customer_id, bid_amount) VALUES (?, ?, ?)");
        if ($stmt->execute([$product_id, $customer_id, $bid_amount])) {
            // تحديث السعر الحالي في المنتج
            $stmt = $pdo->prepare("UPDATE products SET current_bid = ? WHERE id = ?");
            $stmt->execute([$bid_amount, $product_id]);
            
            // تسجيل النشاط
            logActivity($_SESSION['admin_id'], 'auction_bid_added', 'تم إضافة مزايدة للمنتج #' . $product_id);
            
            $_SESSION['success'] = 'تم إضافة المزايدة بنجاح';
        } else {
            $_SESSION['error'] = 'حدث خطأ أثناء إضافة المزايدة';
        }
        
        header('Location: features.php');
        exit;
    }
}

// جلب البيانات
// طلبات التفاوض المعلقة
$stmt = $pdo->query("SELECT pn.*, p.title as product_name, c.first_name, c.last_name 
                     FROM product_negotiations pn 
                     JOIN products p ON pn.product_id = p.id 
                     JOIN customers c ON pn.customer_id = c.id 
                     WHERE pn.status = 'pending' 
                     ORDER BY pn.created_at DESC");
$pending_negotiations = $stmt->fetchAll();

// المزايدات النشطة
$stmt = $pdo->query("SELECT p.*, COUNT(pb.id) as bid_count 
                     FROM products p 
                     LEFT JOIN product_bids pb ON p.id = pb.product_id 
                     WHERE p.auction_enabled = 1 AND p.auction_end_time > NOW() 
                     GROUP BY p.id 
                     ORDER BY p.auction_end_time ASC");
$active_auctions = $stmt->fetchAll();

// العدادات التنازلية النشطة
$stmt = $pdo->query("SELECT pc.*, p.title as product_name, p.price as original_price 
                     FROM price_countdowns pc 
                     JOIN products p ON pc.product_id = p.id 
                     WHERE pc.is_active = 1 AND pc.countdown_end > NOW() 
                     ORDER BY pc.countdown_end ASC");
$active_countdowns = $stmt->fetchAll();

// المنتجات للاستخدام في القوائم المنسدلة
$stmt = $pdo->query("SELECT id, title FROM products WHERE is_active = 1 ORDER BY title");
$products = $stmt->fetchAll();

// العملاء للاستخدام في القوائم المنسدلة
$stmt = $pdo->query("SELECT id, first_name, last_name FROM customers ORDER BY first_name");
$customers = $stmt->fetchAll();

// جلب المنتجات مع خصائصها المتقدمة
$stmt = $pdo->query("SELECT p.*, 
                     COUNT(pb.id) as total_bids,
                     MAX(pb.bid_amount) as highest_bid
                     FROM products p 
                     LEFT JOIN product_bids pb ON p.id = pb.product_id 
                     WHERE p.is_active = 1 
                     GROUP BY p.id 
                     ORDER BY p.created_at DESC 
                     LIMIT 50");
$products_with_features = $stmt->fetchAll();

$adminName = $_SESSION['admin_name'] ?? 'المسؤول';
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الخصائص - <?= getSetting('store_name') ?></title>
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
        
        .feature-section {
            background: #fff;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
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
        .btn-secondary { background: #6b7280; color: #fff; }
        .btn-secondary:hover { background: #4b5563; }
        .btn-danger { background: #dc2626; color: #fff; }
        .btn-danger:hover { background: #b91c1c; }
        .btn-success { background: #059669; color: #fff; }
        .btn-success:hover { background: #047857; }
        .btn-warning { background: #d97706; color: #fff; }
        .btn-warning:hover { background: #b45309; }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
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
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-accepted { background: #dcfce7; color: #166534; }
        .status-rejected { background: #fee2e2; color: #991b1b; }
        .status-counter { background: #dbeafe; color: #1e40af; }
        .status-new { background: #dcfce7; color: #166534; }
        .status-used { background: #fef3c7; color: #92400e; }
        .status-refurbished { background: #dbeafe; color: #1e40af; }
        .status-needs_repair { background: #fee2e2; color: #991b1b; }
        
        .offer-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            margin: 2px;
        }
        .offer-points { background: #fef3c7; color: #92400e; }
        .offer-coupon { background: #dbeafe; color: #1e40af; }
        .offer-gift { background: #fce7f3; color: #be185d; }
        .offer-discount { background: #dcfce7; color: #166534; }
        
        .success-message {
            background: #dcfce7;
            color: #166534;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #bbf7d0;
        }
        
        .error-message {
            background: #fee2e2;
            color: #991b1b;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #fecaca;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        input, select, textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
        }
        
        .toggle-container {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }
        
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }
        
        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        
        input:checked + .toggle-slider {
            background-color: #2196F3;
        }
        
        input:checked + .toggle-slider:before {
            transform: translateX(26px);
        }
        
        .grid-2 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .grid-3 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .grid-4 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
        }
        
        .tab-container {
            margin-bottom: 20px;
        }
        
        .tab-buttons {
            display: flex;
            background: #f8fafc;
            border-radius: 8px;
            padding: 4px;
            margin-bottom: 20px;
        }
        
        .tab-btn {
            flex: 1;
            padding: 12px 20px;
            text-align: center;
            background: none;
            border: none;
            cursor: pointer;
            border-radius: 6px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .tab-btn.active {
            background: #fff;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .product-card {
            background: #f8fafc;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            border: 1px solid #e2e8f0;
        }
        
        .product-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .product-features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
            margin-top: 10px;
        }
        
        .feature-item {
            background: white;
            padding: 8px 12px;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <!-- Sidebar -->
        <?php include 'sidebar.php'; ?>
        <!-- Main Content -->
        <main class="main-content">
            <div class="top-bar">
                <div class="page-title">
                    <h1>إدارة الخصائص المتقدمة</h1>
                    <p style="color: #64748b; margin-top: 5px;">إدارة خصائص خربش واكسب، المزايدات، والعدادات التنازلية</p>
                </div>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="success-message">
                    <?= $_SESSION['success'] ?>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="error-message">
                    <?= $_SESSION['error'] ?>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <!-- تبويبات الصفحة -->
            <div class="tab-container">
                <div class="tab-buttons">
                    <button class="tab-btn active" onclick="openTab('settings')">الإعدادات العامة</button>
                    <button class="tab-btn" onclick="openTab('products')">إدارة المنتجات</button>
                    <button class="tab-btn" onclick="openTab('negotiations')">طلبات التفاوض</button>
                    <button class="tab-btn" onclick="openTab('auctions')">المزايدات</button>
                    <button class="tab-btn" onclick="openTab('countdowns')">العدادات التنازلية</button>
                </div>

                <!-- تبويب الإعدادات العامة -->
                <div id="settings" class="tab-content active">
                    <div class="feature-section">
                        <h2 style="margin-bottom: 20px;">الإعدادات العامة</h2>
                        <form method="POST">
                            <div class="grid-2">
                                <div class="form-group">
                                    <div class="toggle-container">
                                        <label style="margin: 0;">تفعيل نظام التفاوض</label>
                                        <label class="toggle-switch">
                                            <input type="hidden" name="negotiation_enabled" value="0">
                                            <input type="checkbox" name="negotiation_enabled" value="1" <?= getSetting('negotiation_enabled', '0') == '1' ? 'checked' : '' ?>>
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </div>
                                    <input type="number" name="negotiation_min_percentage" value="<?= getSetting('negotiation_min_percentage', '70') ?>" 
                                           min="1" max="99" style="width: 100px;">
                                    <span>% الحد الأدنى للتفاوض</span>
                                </div>
                                
                                <div class="form-group">
                                    <div class="toggle-container">
                                        <label style="margin: 0;">تفعيل نظام المزايدات</label>
                                        <label class="toggle-switch">
                                            <input type="hidden" name="auction_enabled" value="0">
                                            <input type="checkbox" name="auction_enabled" value="1" <?= getSetting('auction_enabled', '0') == '1' ? 'checked' : '' ?>>
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <div class="toggle-container">
                                        <label style="margin: 0;">تفعيل العدادات التنازلية</label>
                                        <label class="toggle-switch">
                                            <input type="hidden" name="countdown_enabled" value="0">
                                            <input type="checkbox" name="countdown_enabled" value="1" <?= getSetting('countdown_enabled', '0') == '1' ? 'checked' : '' ?>>
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <div class="toggle-container">
                                        <label style="margin: 0;">تفعيل العروض الخاصة</label>
                                        <label class="toggle-switch">
                                            <input type="hidden" name="special_offers_enabled" value="0">
                                            <input type="checkbox" name="special_offers_enabled" value="1" <?= getSetting('special_offers_enabled', '0') == '1' ? 'checked' : '' ?>>
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <button type="submit" name="update_settings" class="btn btn-primary" style="margin-top: 20px;">
                                <i class="fas fa-save"></i> حفظ الإعدادات
                            </button>
                        </form>
                    </div>
                </div>

                <!-- تبويب إدارة المنتجات -->
                <div id="products" class="tab-content">
                    <div class="feature-section">
                        <h2 style="margin-bottom: 20px;">إدارة خصائص المنتجات</h2>
                        
                        <?php foreach ($products_with_features as $product): ?>
                            <div class="product-card">
                                <div class="product-header">
                                    <h3><?= htmlspecialchars($product['title']) ?></h3>
                                    <span class="btn btn-primary"><?= formatPrice($product['price']) ?></span>
                                </div>
                                
                                <form method="POST">
                                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                    
                                    <div class="grid-3">
                                        <div class="form-group">
                                            <label>حالة المنتج</label>
                                            <select name="product_condition">
                                                <option value="new" <?= $product['product_condition'] == 'new' ? 'selected' : '' ?>>جديد</option>
                                                <option value="used" <?= $product['product_condition'] == 'used' ? 'selected' : '' ?>>مستعمل</option>
                                                <option value="refurbished" <?= $product['product_condition'] == 'refurbished' ? 'selected' : '' ?>>مجدد</option>
                                                <option value="needs_repair" <?= $product['product_condition'] == 'needs_repair' ? 'selected' : '' ?>>يحتاج صيانة</option>
                                            </select>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label>عرض خربش واكسب</label>
                                            <select name="special_offer_type">
                                                <option value="none" <?= $product['special_offer_type'] == 'none' ? 'selected' : '' ?>>لا يوجد</option>
                                                <option value="points" <?= $product['special_offer_type'] == 'points' ? 'selected' : '' ?>>نقاط</option>
                                                <option value="coupon" <?= $product['special_offer_type'] == 'coupon' ? 'selected' : '' ?>>كوبون خصم</option>
                                                <option value="gift" <?= $product['special_offer_type'] == 'gift' ? 'selected' : '' ?>>هدية</option>
                                                <option value="discount" <?= $product['special_offer_type'] == 'discount' ? 'selected' : '' ?>>خصم إضافي</option>
                                            </select>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label>قيمة العرض</label>
                                            <input type="text" name="special_offer_value" value="<?= htmlspecialchars($product['special_offer_value'] ?? '') ?>" 
                                                   placeholder="مثال: 100 نقطة، كود الخصم، اسم الهدية...">
                                        </div>
                                    </div>
                                    
                                    <div class="grid-3">
                                        <div class="form-group">
                                            <div class="toggle-container">
                                                <label style="margin: 0;">تفعيل المزاد العلني</label>
                                                <label class="toggle-switch">
                                                    <input type="hidden" name="auction_enabled" value="0">
                                                    <input type="checkbox" name="auction_enabled" value="1" <?= $product['auction_enabled'] == '1' ? 'checked' : '' ?>>
                                                    <span class="toggle-slider"></span>
                                                </label>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label>سعر البدء في المزاد</label>
                                            <input type="number" name="starting_price" value="<?= $product['starting_price'] ?: $product['price'] ?>" step="0.01">
                                        </div>
                                        
                                        <div class="form-group">
                                            <label>ينتهي المزاد في</label>
                                            <input type="datetime-local" name="auction_end_time" 
                                                   value="<?= $product['auction_end_time'] ? date('Y-m-d\TH:i', strtotime($product['auction_end_time'])) : '' ?>">
                                        </div>
                                    </div>
                                    
                                    <button type="submit" name="update_product_features" class="btn btn-success">
                                        <i class="fas fa-save"></i> حفظ الخصائص
                                    </button>
                                </form>
                                
                                <!-- عرض الخصائص الحالية -->
                                <div class="product-features">
                                    <div class="feature-item">
                                        <strong>الحالة:</strong> 
                                        <?php
                                        $condition_labels = [
                                            'new' => ['label' => 'جديد', 'class' => 'status-new'],
                                            'used' => ['label' => 'مستعمل', 'class' => 'status-used'],
                                            'refurbished' => ['label' => 'مجدد', 'class' => 'status-refurbished'],
                                            'needs_repair' => ['label' => 'يحتاج صيانة', 'class' => 'status-needs_repair']
                                        ];
                                        $condition = $condition_labels[$product['product_condition'] ?? 'new'];
                                        ?>
                                        <span class="status-badge <?= $condition['class'] ?>"><?= $condition['label'] ?></span>
                                    </div>
                                    
                                    <div class="feature-item">
                                        <strong>عرض خاص:</strong> 
                                        <?php if ($product['special_offer_type'] != 'none'): ?>
                                            <?php
                                            $offer_labels = [
                                                'points' => ['label' => 'نقاط', 'class' => 'offer-points'],
                                                'coupon' => ['label' => 'كوبون', 'class' => 'offer-coupon'],
                                                'gift' => ['label' => 'هدية', 'class' => 'offer-gift'],
                                                'discount' => ['label' => 'خصم', 'class' => 'offer-discount']
                                            ];
                                            $offer = $offer_labels[$product['special_offer_type']];
                                            ?>
                                            <span class="offer-badge <?= $offer['class'] ?>">
                                                <?= $offer['label'] ?>: <?= htmlspecialchars($product['special_offer_value']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span style="color: #6b7280;">لا يوجد</span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="feature-item">
                                        <strong>المزاد:</strong> 
                                        <?php if ($product['auction_enabled'] == '1'): ?>
                                            <span class="status-badge status-pending">نشط</span>
                                            <br>
                                            <small>أعلى مزايدة: <?= formatPrice($product['highest_bid'] ?: $product['starting_price']) ?></small>
                                            <br>
                                            <small>عدد المزايدات: <?= $product['total_bids'] ?></small>
                                        <?php else: ?>
                                            <span style="color: #6b7280;">غير مفعل</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- تبويب طلبات التفاوض -->
                <div id="negotiations" class="tab-content">
                    <div class="feature-section">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                            <h2>طلبات التفاوض المعلقة</h2>
                            <span class="btn btn-primary"><?= count($pending_negotiations) ?> طلب</span>
                        </div>
                        
                        <?php if (empty($pending_negotiations)): ?>
                            <p style="text-align: center; color: #64748b; padding: 40px;">لا توجد طلبات تفاوض معلقة</p>
                        <?php else: ?>
                            <table>
                                <thead>
                                    <tr>
                                        <th>المنتج</th>
                                        <th>العميل</th>
                                        <th>السعر الحالي</th>
                                        <th>السعر المقترح</th>
                                        <th>ملاحظات العميل</th>
                                        <th>التاريخ</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pending_negotiations as $negotiation): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($negotiation['product_name']) ?></td>
                                            <td><?= htmlspecialchars($negotiation['first_name'] . ' ' . $negotiation['last_name']) ?></td>
                                            <td>
                                                <?php
                                                $product_stmt = $pdo->prepare("SELECT final_price FROM products WHERE id = ?");
                                                $product_stmt->execute([$negotiation['product_id']]);
                                                $product_price = $product_stmt->fetchColumn();
                                                echo formatPrice($product_price);
                                                ?>
                                            </td>
                                            <td style="color: #dc3545; font-weight: bold;"><?= formatPrice($negotiation['offered_price']) ?></td>
                                            <td><?= htmlspecialchars($negotiation['customer_notes'] ?? 'لا توجد ملاحظات') ?></td>
                                            <td><?= date('Y-m-d H:i', strtotime($negotiation['created_at'])) ?></td>
                                            <td>
                                                <button onclick="openNegotiationModal(<?= $negotiation['id'] ?>)" class="btn btn-primary" style="padding: 5px 10px; font-size: 12px;">
                                                    <i class="fas fa-edit"></i> معالجة
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- تبويب المزايدات -->
                <div id="auctions" class="tab-content">
                    <div class="feature-section">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                            <h2>المزايدات النشطة</h2>
                            <span class="btn btn-primary"><?= count($active_auctions) ?> مزايدة</span>
                        </div>
                        
                        <?php if (empty($active_auctions)): ?>
                            <p style="text-align: center; color: #64748b; padding: 40px;">لا توجد مزايدات نشطة حالياً</p>
                        <?php else: ?>
                            <table>
                                <thead>
                                    <tr>
                                        <th>المنتج</th>
                                        <th>السعر الابتدائي</th>
                                        <th>السعر الحالي</th>
                                        <th>عدد المزايدات</th>
                                        <th>ينتهي في</th>
                                        <th>الحالة</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($active_auctions as $auction): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($auction['title']) ?></td>
                                            <td><?= formatPrice($auction['starting_price']) ?></td>
                                            <td style="color: #dc3545; font-weight: bold;"><?= formatPrice(max($auction['current_bid'], $auction['starting_price'])) ?></td>
                                            <td><?= $auction['bid_count'] ?></td>
                                            <td><?= date('Y-m-d H:i', strtotime($auction['auction_end_time'])) ?></td>
                                            <td>
                                                <span class="status-badge status-pending">نشط</span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>

                        <!-- نموذج إضافة مزايدة يدوية -->
                        <div style="margin-top: 30px; padding: 20px; background: #f8fafc; border-radius: 8px;">
                            <h3 style="margin-bottom: 15px;">إضافة مزايدة يدوية (للتجربة)</h3>
                            <form method="POST">
                                <div class="grid-3">
                                    <div class="form-group">
                                        <label>المنتج</label>
                                        <select name="auction_product_id" required>
                                            <option value="">اختر المنتج</option>
                                            <?php foreach ($products as $product): ?>
                                                <option value="<?= $product['id'] ?>"><?= htmlspecialchars($product['title']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>العميل</label>
                                        <select name="customer_id" required>
                                            <option value="">اختر العميل</option>
                                            <?php foreach ($customers as $customer): ?>
                                                <option value="<?= $customer['id'] ?>"><?= htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>مبلغ المزايدة</label>
                                        <input type="number" name="bid_amount" step="0.01" required>
                                    </div>
                                </div>
                                
                                <button type="submit" name="add_auction_bid" class="btn btn-warning">
                                    <i class="fas fa-gavel"></i> إضافة مزايدة
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- تبويب العدادات التنازلية -->
                <div id="countdowns" class="tab-content">
                    <div class="feature-section">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                            <h2>العدادات التنازلية النشطة</h2>
                            <span class="btn btn-primary"><?= count($active_countdowns) ?> عداد</span>
                        </div>
                        
                        <?php if (empty($active_countdowns)): ?>
                            <p style="text-align: center; color: #64748b; padding: 40px;">لا توجد عدادات تنازلية نشطة</p>
                        <?php else: ?>
                            <table>
                                <thead>
                                    <tr>
                                        <th>المنتج</th>
                                        <th>السعر الأصلي</th>
                                        <th>السعر بعد العرض</th>
                                        <th>ينتهي في</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($active_countdowns as $countdown): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($countdown['product_name']) ?></td>
                                            <td><?= formatPrice($countdown['original_price']) ?></td>
                                            <td style="color: #dc3545; font-weight: bold;"><?= formatPrice($countdown['new_price']) ?></td>
                                            <td><?= date('Y-m-d H:i', strtotime($countdown['countdown_end'])) ?></td>
                                            <td>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="countdown_id" value="<?= $countdown['id'] ?>">
                                                    <button type="submit" name="delete_countdown" class="btn btn-danger" style="padding: 5px 10px; font-size: 12px;">
                                                        <i class="fas fa-trash"></i> حذف
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>

                        <!-- نموذج إضافة عداد تنازلي جديد -->
                        <div style="margin-top: 30px; padding: 20px; background: #f8fafc; border-radius: 8px;">
                            <h3 style="margin-bottom: 15px;">إضافة عداد تنازلي جديد</h3>
                            <form method="POST">
                                <div class="grid-3">
                                    <div class="form-group">
                                        <label>المنتج</label>
                                        <select name="product_id" required>
                                            <option value="">اختر المنتج</option>
                                            <?php foreach ($products as $product): ?>
                                                <option value="<?= $product['id'] ?>"><?= htmlspecialchars($product['title']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>السعر الجديد</label>
                                        <input type="number" name="new_price" step="0.01" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>ينتهي في</label>
                                        <input type="datetime-local" name="countdown_end" required>
                                    </div>
                                </div>
                                
                                <button type="submit" name="add_countdown" class="btn btn-success">
                                    <i class="fas fa-plus"></i> إضافة عداد تنازلي
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal for Negotiation Handling -->
    <div id="negotiationModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
        <div style="background: white; padding: 2rem; border-radius: 10px; width: 90%; max-width: 500px;">
            <h3 style="margin-bottom: 20px;">معالجة طلب التفاوض</h3>
            <form method="POST" id="negotiationForm">
                <input type="hidden" name="negotiation_id" id="modal_negotiation_id">
                
                <div class="form-group">
                    <label>الحالة:</label>
                    <select name="status" id="modal_status">
                        <option value="pending">معلق</option>
                        <option value="accepted">مقبول</option>
                        <option value="rejected">مرفوض</option>
                        <option value="counter_offer">عرض مضاد</option>
                    </select>
                </div>
                
                <div class="form-group" style="display: none;" id="counter_price_container">
                    <label>السعر المضاد:</label>
                    <input type="number" name="counter_price" id="modal_counter_price" step="0.01">
                </div>
                
                <div class="form-group">
                    <label>ملاحظات إدارية:</label>
                    <textarea name="admin_notes" id="modal_admin_notes" rows="4"></textarea>
                </div>
                
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" onclick="closeNegotiationModal()" class="btn btn-secondary">إلغاء</button>
                    <button type="submit" name="update_negotiation" class="btn btn-primary">حفظ التغييرات</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function openTab(tabName) {
        // إخفاء جميع المحتويات
        document.querySelectorAll('.tab-content').forEach(tab => {
            tab.classList.remove('active');
        });
        
        // إلغاء تنشيط جميع الأزرار
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        
        // إظهار المحتوى المطلوب
        document.getElementById(tabName).classList.add('active');
        
        // تنشيط الزر المطلوب
        event.currentTarget.classList.add('active');
    }
    
    function openNegotiationModal(negotiationId) {
        document.getElementById('modal_negotiation_id').value = negotiationId;
        document.getElementById('negotiationModal').style.display = 'flex';
    }
    
    function closeNegotiationModal() {
        document.getElementById('negotiationModal').style.display = 'none';
    }
    
    document.getElementById('modal_status').addEventListener('change', function() {
        const counterContainer = document.getElementById('counter_price_container');
        counterContainer.style.display = this.value === 'counter_offer' ? 'block' : 'none';
    });
    
    // إغلاق المودال عند النقر خارجها
    document.getElementById('negotiationModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeNegotiationModal();
        }
    });
    </script>
</body>
</html>