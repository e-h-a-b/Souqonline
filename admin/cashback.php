<?php
require_once '../config.php';
//require_once 'admin_auth.php';
require_once '../functions.php';
// دوال إدارة الكاشباك
function getCashbackProducts1() {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT p.id, p.title, p.price, p.final_price, 
               pc.cashback_percentage, pc.cashback_amount, pc.is_active,
               pc.start_date, pc.end_date, pc.created_at
        FROM products p
        LEFT JOIN product_cashback pc ON p.id = pc.product_id AND pc.is_active = 1
        WHERE p.is_active = 1
        ORDER BY pc.cashback_percentage DESC, p.title
    ");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getCashbackStats1() {
    global $pdo;
    
    $stats = [];
    
    // إحصائيات عامة
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT product_id) as total_products_with_cashback,
            AVG(cashback_percentage) as avg_percentage,
            SUM(cashback_amount) as total_cashback_amount
        FROM product_cashback 
        WHERE is_active = 1
    ");
    $stmt->execute();
    $stats['products'] = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // إحصائيات المعاملات
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_transactions,
            SUM(amount) as total_amount,
            AVG(amount) as avg_amount,
            SUM(CASE WHEN status = 'approved' THEN amount ELSE 0 END) as approved_amount,
            SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as pending_amount
        FROM cashback_transactions
    ");
    $stmt->execute();
    $stats['transactions'] = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $stats;
}

function updateProductCashback1($productId, $percentage, $amount, $startDate, $endDate) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        INSERT INTO product_cashback 
        (product_id, cashback_percentage, cashback_amount, start_date, end_date, is_active) 
        VALUES (?, ?, ?, ?, ?, 1)
        ON DUPLICATE KEY UPDATE 
        cashback_percentage = VALUES(cashback_percentage),
        cashback_amount = VALUES(cashback_amount),
        start_date = VALUES(start_date),
        end_date = VALUES(end_date),
        is_active = 1
    ");
    
    return $stmt->execute([$productId, $percentage, $amount ?: 0, $startDate ?: null, $endDate ?: null]);
}

function deactivateProductCashback1($productId) {
    global $pdo;
    
    $stmt = $pdo->prepare("UPDATE product_cashback SET is_active = 0 WHERE product_id = ?");
    return $stmt->execute([$productId]);
}

// معالجة النماذج
if ($_POST) {
    if (isset($_POST['update_settings'])) {
        // تحديث الإعدادات العامة
        updateSetting('cashback_enabled', isset($_POST['enabled']) ? '1' : '0');
        updateSetting('cashback_percentage', $_POST['percentage']);
        updateSetting('cashback_min_amount', $_POST['min_amount']);
        updateSetting('cashback_max_amount', $_POST['max_amount']);
        
        $categories = isset($_POST['categories']) ? $_POST['categories'] : [];
        updateSetting('cashback_categories', json_encode($categories));
        
        $message = "تم تحديث الإعدادات العامة بنجاح";
        
    } elseif (isset($_POST['add_cashback'])) {
        // إضافة كاشباك لمنتج
        $productId = $_POST['product_id'];
        $percentage = $_POST['percentage'];
        $amount = $_POST['amount'];
        $startDate = $_POST['start_date'] ?: null;
        $endDate = $_POST['end_date'] ?: null;
        
        if (updateProductCashback1($productId, $percentage, $amount, $startDate, $endDate)) {
            $message = "تم إضافة الكاشباك للمنتج بنجاح";
        } else {
            $error = "حدث خطأ أثناء إضافة الكاشباك";
        }
        
    } elseif (isset($_POST['bulk_apply'])) {
        // تطبيق جماعي على فئة
        $categoryId = $_POST['bulk_category'];
        $percentage = $_POST['bulk_percentage'];
        
        global $pdo;
        $stmt = $pdo->prepare("SELECT id FROM products WHERE category_id = ? AND is_active = 1");
        $stmt->execute([$categoryId]);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $updated = 0;
        foreach ($products as $product) {
            if (updateProductCashback1($product['id'], $percentage, 0, null, null)) {
                $updated++;
            }
        }
        
        $message = "تم تطبيق الكاشباك على {$updated} منتج";
        
    } elseif (isset($_GET['deactivate'])) {
        // تعطيل كاشباك منتج
        if (deactivateProductCashback($_GET['deactivate'])) {
            $message = "تم تعطيل الكاشباك بنجاح";
        }
    }
    
    header('Location: cashback.php?success=1&message=' . urlencode($message ?? ''));
    exit;
}

// جلب البيانات
$settings = getCashbackSettings();
$products = getCashbackProducts1();
$stats = getCashbackStats1();
$categories = getCategories();
$allProducts = getProducts(['limit' => 1000]); // جميع المنتجات
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة نظام الكاشباك</title>
		    <title>إدارة الخصائص - <?= getSetting('store_name') ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - <?= getSetting('store_name') ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <style>
	.container, .container-fluid, .container-lg, .container-md, .container-sm, .container-xl, .container-xxl{
		width: 90%  !important;
		padding-right: 200px !important;
	}
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
    <style>
        .product-thumb {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border-radius: 5px;
        }
        .stats-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        .stats-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding: 1rem 0;
            border-bottom: 1px solid #eee;
        }
    </style>

    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f6fa; color: #333; line-height: 1.6; }
        
        .container { max-width: 1400px; margin: 0 auto; padding: 20px; }
        
        .header { 
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(16, 185, 129, 0.3);
        }
        
        .stats-grid { 
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            border-right: 4px solid #10b981;
            text-align: center;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #10b981;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: #64748b;
            font-size: 0.9rem;
        }
        
        .tabs { 
            display: flex;
            background: white;
            border-radius: 12px;
            padding: 0.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .tab { 
            padding: 1rem 2rem;
            cursor: pointer;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-weight: 600;
        }
        
        .tab.active {
            background: #10b981;
            color: white;
        }
        
        .tab-content { 
            display: none;
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .tab-content.active { display: block; }
        
        .form-group { margin-bottom: 1.5rem; }
        
        label { 
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #374151;
        }
        
        input, select, textarea {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #10b981;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .checkbox-group input { width: auto; }
        
        .btn {
            background: #10b981;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn:hover {
            background: #059669;
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: #6b7280;
        }
        
        .btn-secondary:hover {
            background: #4b5563;
        }
        
        .btn-danger {
            background: #ef4444;
        }
        
        .btn-danger:hover {
            background: #dc2626;
        }
        
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }
        
        .product-card {
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 1.5rem;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .product-card:hover {
            border-color: #10b981;
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(16, 185, 129, 0.1);
        }
        
        .product-card.has-cashback {
            border-color: #10b981;
            background: linear-gradient(135deg, #f0fdf4, #dcfce7);
        }
        
        .cashback-badge {
            position: absolute;
            top: -10px;
            left: -10px;
            background: #10b981;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }
        
        .product-title {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #1f2937;
        }
        
        .product-price {
            color: #10b981;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .cashback-info {
            background: #f8fafc;
            padding: 0.75rem;
            border-radius: 8px;
            margin-top: 0.5rem;
            border-right: 3px solid #10b981;
        }
        
        .success-message {
            background: #d1fae5;
            color: #065f46;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            border-right: 4px solid #10b981;
        }
        
        .error-message {
            background: #fee2e2;
            color: #991b1b;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            border-right: 4px solid #ef4444;
        }
        
        .table {
            width: 100%;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .table th,
        .table td {
            padding: 1rem;
            text-align: right;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .table th {
            background: #f8fafc;
            font-weight: 600;
            color: #374151;
        }
        
        .table tr:hover {
            background: #f8fafc;
        }
    </style>
</head>
<body>
        <!-- Sidebar -->
        <?php include 'sidebar.php'; ?>
    <div class="container">
        <!-- الهيدر -->
        <div class="header">
            <h1><i class="fas fa-money-bill-wave"></i> إدارة نظام الكاشباك</h1>
            <p>إدارة نظام الاسترجاع النقدي والعروض الترويجية</p>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="success-message">
                ✅ <?= htmlspecialchars($_GET['message'] ?? 'تمت العملية بنجاح') ?>
            </div>
        <?php endif; ?>

        <!-- الإحصائيات -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?= $stats['products']['total_products_with_cashback'] ?? 0 ?></div>
                <div class="stat-label">منتج به كاشباك</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= number_format($stats['products']['avg_percentage'] ?? 0, 1) ?>%</div>
                <div class="stat-label">متوسط نسبة الكاشباك</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['transactions']['total_transactions'] ?? 0 ?></div>
                <div class="stat-label">معاملة كاشباك</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= formatPrice($stats['transactions']['total_amount'] ?? 0) ?></div>
                <div class="stat-label">إجمالي الكاشباك</div>
            </div>
        </div>

        <!-- التبويبات -->
        <div class="tabs">
            <div class="tab active" onclick="openTab('settings')">الإعدادات العامة</div>
            <div class="tab" onclick="openTab('products')">إدارة المنتجات</div>
            <div class="tab" onclick="openTab('bulk')">التطبيق الجماعي</div>
            <div class="tab" onclick="openTab('transactions')">المعاملات</div>
        </div>

        <!-- تبويب الإعدادات العامة -->
        <div id="settings" class="tab-content active">
            <h2><i class="fas fa-cog"></i> الإعدادات العامة</h2>
            <form method="post">
                <div class="form-group checkbox-group">
                    <input type="checkbox" name="enabled" value="1" id="enabled" <?= $settings['enabled'] ? 'checked' : '' ?>>
                    <label for="enabled">تفعيل نظام الكاشباك</label>
                </div>

                <div class="form-group">
                    <label for="percentage">نسبة الكاشباك الافتراضية (%)</label>
                    <input type="number" name="percentage" id="percentage" 
                           value="<?= $settings['percentage'] ?>" min="0" max="50" step="0.1" required>
                </div>

                <div class="form-group">
                    <label for="min_amount">الحد الأدنى لمبلغ الكاشباك</label>
                    <input type="number" name="min_amount" id="min_amount" 
                           value="<?= $settings['min_amount'] ?>" min="0" step="0.01">
                </div>

                <div class="form-group">
                    <label for="max_amount">الحد الأقصى لمبلغ الكاشباك</label>
                    <input type="number" name="max_amount" id="max_amount" 
                           value="<?= $settings['max_amount'] ?>" min="0" step="0.01">
                </div>

                <div class="form-group">
                    <label>الفئات المشمولة بالكاشباك الافتراضي:</label>
                    <div style="max-height: 200px; overflow-y: auto; border: 1px solid #e5e7eb; padding: 1rem; border-radius: 8px;">
                        <?php foreach ($categories as $cat): ?>
                            <div class="checkbox-group">
                                <input type="checkbox" name="categories[]" value="<?= $cat['id'] ?>" 
                                       id="cat_<?= $cat['id'] ?>" 
                                       <?= in_array($cat['id'], $settings['categories']) ? 'checked' : '' ?>>
                                <label for="cat_<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <small>اتركها فارغة لتطبيق الكاشباك على جميع الفئات</small>
                </div>

                <button type="submit" name="update_settings" class="btn">
                    <i class="fas fa-save"></i> حفظ الإعدادات
                </button>
            </form>
        </div>

        <!-- تبويب إدارة المنتجات -->
        <div id="products" class="tab-content">
            <h2><i class="fas fa-boxes"></i> إدارة كاشباك المنتجات</h2>
            
            <!-- نموذج إضافة كاشباك -->
            <div style="background: #f8fafc; padding: 1.5rem; border-radius: 12px; margin-bottom: 2rem;">
                <h3>إضافة كاشباك لمنتج</h3>
                <form method="post">
                    <div class="form-group">
                        <label for="product_id">اختر المنتج</label>
                        <select name="product_id" id="product_id" required>
                            <option value="">-- اختر المنتج --</option>
                            <?php foreach ($allProducts as $product): ?>
                                <option value="<?= $product['id'] ?>">
                                    <?= htmlspecialchars($product['title']) ?> - <?= formatPrice($product['price']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="percentage">نسبة الكاشباك (%)</label>
                        <input type="number" name="percentage" id="percentage" 
                               min="0" max="50" step="0.1" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="amount">مبلغ ثابت (اختياري)</label>
                        <input type="number" name="amount" id="amount" 
                               min="0" step="0.01" placeholder="اتركه فارغاً لاستخدام النسبة">
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label for="start_date">تاريخ البداية</label>
                            <input type="date" name="start_date" id="start_date">
                        </div>
                        <div class="form-group">
                            <label for="end_date">تاريخ النهاية</label>
                            <input type="date" name="end_date" id="end_date">
                        </div>
                    </div>
                    
                    <button type="submit" name="add_cashback" class="btn">
                        <i class="fas fa-plus"></i> إضافة الكاشباك
                    </button>
                </form>
            </div>

            <!-- قائمة المنتجات -->
            <h3>المنتجات مع الكاشباك</h3>
            <div class="products-grid">
                <?php foreach ($products as $product): ?>
                    <div class="product-card <?= $product['cashback_percentage'] ? 'has-cashback' : '' ?>">
                        <?php if ($product['cashback_percentage']): ?>
                            <div class="cashback-badge">
                                <?= $product['cashback_percentage'] ?>%
                            </div>
                        <?php endif; ?>
                        
                        <div class="product-title"><?= htmlspecialchars($product['title']) ?></div>
                        <div class="product-price"><?= formatPrice($product['final_price']) ?></div>
                        
                        <?php if ($product['cashback_percentage']): ?>
                            <div class="cashback-info">
                                <div><strong>نسبة الكاشباك:</strong> <?= $product['cashback_percentage'] ?>%</div>
                                <?php if ($product['cashback_amount'] > 0): ?>
                                    <div><strong>مبلغ ثابت:</strong> <?= formatPrice($product['cashback_amount']) ?></div>
                                <?php endif; ?>
                                <?php if ($product['start_date']): ?>
                                    <div><strong>من:</strong> <?= $product['start_date'] ?></div>
                                <?php endif; ?>
                                <?php if ($product['end_date']): ?>
                                    <div><strong>إلى:</strong> <?= $product['end_date'] ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <div style="margin-top: 1rem; display: flex; gap: 0.5rem;">
                                <a href="?deactivate=<?= $product['id'] ?>" class="btn btn-danger" 
                                   onclick="return confirm('هل أنت متأكد من تعطيل الكاشباك؟')">
                                    <i class="fas fa-times"></i> تعطيل
                                </a>
                            </div>
                        <?php else: ?>
                            <div style="color: #6b7280; font-size: 0.9rem; margin-top: 1rem;">
                                لا يوجد كاشباك
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- تبويب التطبيق الجماعي -->
        <div id="bulk" class="tab-content">
            <h2><i class="fas fa-layer-group"></i> التطبيق الجماعي</h2>
            <form method="post">
                <div class="form-group">
                    <label for="bulk_category">الفئة</label>
                    <select name="bulk_category" id="bulk_category" required>
                        <option value="">-- اختر الفئة --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="bulk_percentage">نسبة الكاشباك (%)</label>
                    <input type="number" name="bulk_percentage" id="bulk_percentage" 
                           min="0" max="50" step="0.1" required>
                </div>
                
                <button type="submit" name="bulk_apply" class="btn" 
                        onclick="return confirm('هل أنت متأكد من تطبيق الكاشباك على جميع منتجات هذه الفئة؟')">
                    <i class="fas fa-bolt"></i> تطبيق جماعي
                </button>
            </form>
        </div>

        <!-- تبويب المعاملات -->
        <div id="transactions" class="tab-content">
            <h2><i class="fas fa-exchange-alt"></i> معاملات الكاشباك</h2>
            
            <?php
            $transactions = getCashbackTransactions1();
            if ($transactions): 
            ?>
                <div class="table">
                    <table style="width: 100%;">
                        <thead>
                            <tr>
                                <th>العميل</th>
                                <th>المنتج</th>
                                <th>المبلغ</th>
                                <th>النسبة</th>
                                <th>الحالة</th>
                                <th>التاريخ</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $transaction): ?>
                                <tr>
                                    <td>#<?= $transaction['customer_id'] ?></td>
                                    <td><?= htmlspecialchars($transaction['product_title']) ?></td>
                                    <td><?= formatPrice($transaction['amount']) ?></td>
                                    <td><?= $transaction['percentage'] ?>%</td>
                                    <td>
                                        <span style="padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.8rem; 
                                              background: <?= getStatusColor($transaction['status']) ?>; color: white;">
                                            <?= getStatusText($transaction['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= $transaction['transaction_date'] ?></td>
                                    <td>
                                        <?php if ($transaction['status'] == 'pending'): ?>
                                            <a href="?approve=<?= $transaction['id'] ?>" class="btn" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;">
                                                <i class="fas fa-check"></i> قبول
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 3rem; color: #6b7280;">
                    <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                    <p>لا توجد معاملات كاشباك حتى الآن</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function openTab(tabName) {
            // إخفاء جميع المحتويات
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // إلغاء تفعيل جميع التبويبات
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // إظهار المحتوى المطلوب
            document.getElementById(tabName).classList.add('active');
            
            // تفعيل التبويب المطلوب
            event.target.classList.add('active');
        }
        
        // دوال مساعدة للعرض
        function getStatusColor(status) {
            const colors = {
                'pending': '#f59e0b',
                'approved': '#10b981',
                'rejected': '#ef4444',
                'paid': '#3b82f6'
            };
            return colors[status] || '#6b7280';
        }
        
        function getStatusText(status) {
            const texts = {
                'pending': 'قيد الانتظار',
                'approved': 'مقبول',
                'rejected': 'مرفوض',
                'paid': 'تم الدفع'
            };
            return texts[status] || status;
        }
    </script>
</body>
</html>

<?php
// دوال مساعدة إضافية
function getCashbackTransactions1($limit = 50) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT ct.*, p.title as product_title, c.first_name, c.last_name
        FROM cashback_transactions ct
        LEFT JOIN products p ON ct.product_id = p.id
        LEFT JOIN customers c ON ct.customer_id = c.id
        ORDER BY ct.transaction_date DESC
        LIMIT ?
    ");
    $stmt->execute([$limit]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getStatusColor($status) {
    $colors = [
        'pending' => '#f59e0b',
        'approved' => '#10b981', 
        'rejected' => '#ef4444',
        'paid' => '#3b82f6'
    ];
    return $colors[$status] ?? '#6b7280';
}

function getStatusText($status) {
    $texts = [
        'pending' => 'قيد الانتظار',
        'approved' => 'مقبول',
        'rejected' => 'مرفوض',
        'paid' => 'تم الدفع'
    ];
    return $texts[$status] ?? $status;
}
?>