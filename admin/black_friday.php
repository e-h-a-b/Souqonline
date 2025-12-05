<?php
require_once '../config.php';
//require_once 'admin_auth.php';
 
require_once '../functions.php';
 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// جلب الفئات
function getCategories1() {
    global $pdo;
    $stmt = $pdo->prepare("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// تحديث الإعدادات
function updateSetting1($key, $value) {
    global $pdo;
    $stmt = $pdo->prepare("
        INSERT INTO settings (setting_key, setting_value) 
        VALUES (?, ?) 
        ON DUPLICATE KEY UPDATE setting_value = ?
    ");
    return $stmt->execute([$key, $value, $value]);
}

// جلب الإعدادات
function getSetting1($key, $default = '') {
    global $pdo;
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['setting_value'] : $default;
}


if ($_POST) {
    // حفظ إعدادات الجمعة البيضاء
    updateSetting1('black_friday_enabled', isset($_POST['enabled']) ? '1' : '0');
    updateSetting1('black_friday_start_date', $_POST['start_date']);
    updateSetting1('black_friday_duration_days', $_POST['duration_days']);
    updateSetting1('black_friday_discount_percentage', $_POST['discount_percentage']);
    updateSetting1('black_friday_test_mode', isset($_POST['test_mode']) ? '1' : '0');
    updateSetting1('black_friday_test_date', $_POST['test_date'] ?: null);
    
    // معالجة الفئات
    $categories = isset($_POST['categories']) ? $_POST['categories'] : [];
    updateSetting1('black_friday_categories', json_encode($categories));
    
    // تطبيق الخصومات فوراً إذا طلب المستخدم
    if (isset($_POST['apply_now'])) {
        require_once '../functions.php';
        $updatedCount = autoApplyBlackFridayDiscounts();
        $message = "تم تطبيق الخصم على {$updatedCount} منتج";
    } else {
        $message = "تم حفظ الإعدادات بنجاح";
    }
    
    header('Location: black_friday.php?success=1&message=' . urlencode($message));
    exit;
}

// جلب الإعدادات الحالية
$enabled = getSetting1('black_friday_enabled') == '1';
$startDate = getSetting1('black_friday_start_date', '11-24');
$durationDays = getSetting1('black_friday_duration_days', '3');
$discountPercentage = getSetting1('black_friday_discount_percentage', '50');
$testMode = getSetting1('black_friday_test_mode') == '1';
$testDate = getSetting1('black_friday_test_date');
$categoriesJson = getSetting1('black_friday_categories', '[]');
$selectedCategories = json_decode($categoriesJson, true) ?: [];
 
$allCategories = getCategories1();
$status = getBlackFridayStatus();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إعدادات الجمعة البيضاء</title>
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
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, select, textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 5px; }
        .checkbox-group { display: flex; align-items: center; gap: 10px; }
        .checkbox-group input { width: auto; }
        .btn { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
        .btn:hover { background: #0056b3; }
        .status-box { background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px; border-right: 4px solid #007bff; }
        .success { background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 15px; }
        .category-list { max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; border-radius: 5px; }
    </style>
</head>
<body>
        <!-- Sidebar -->
        <?php include 'sidebar.php'; ?>
    <div class="container">
        <h1>🎉 إعدادات الجمعة البيضاء</h1>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="success">
                ✅ <?= htmlspecialchars($_GET['message'] ?? 'تم حفظ الإعدادات بنجاح') ?>
            </div>
        <?php endif; ?>

        <!-- حالة النظام -->
        <div class="status-box">
            <h3>حالة النظام الحالية:</h3>
            <p><strong>النظام:</strong> <?= $status['enabled'] ? '✅ مفعل' : '❌ معطل' ?></p>
            <p><strong>العروض نشطة:</strong> <?= $status['active'] ? '✅ نعم' : '❌ لا' ?></p>
            <p><strong>وضع الاختبار:</strong> <?= $status['test_mode'] ? '✅ مفعل' : '❌ معطل' ?></p>
            <p><strong>نسبة الخصم:</strong> <?= $status['discount_percentage'] ?>%</p>
            <p><strong>مدة العرض:</strong> <?= $status['duration_days'] ?> أيام</p>
            <p><strong>الفئات المشمولة:</strong> <?= $status['categories_count'] ?> فئة</p>
            
            <?php if ($status['remaining_time']): ?>
                <p><strong>الوقت المتبقي:</strong> 
                    <?= $status['remaining_time']['days'] ?> أيام, 
                    <?= $status['remaining_time']['hours'] ?> ساعات, 
                    <?= $status['remaining_time']['minutes'] ?> دقائق
                </p>
            <?php endif; ?>
        </div>

        <form method="post">
            <!-- الإعدادات الأساسية -->
            <div class="form-group checkbox-group">
                <input type="checkbox" id ="enabled" name="enabled" value="1" <?= $enabled ? 'checked' : '' ?> id="enabled">
                <label for="enabled">تفعيل نظام الجمعة البيضاء</label>
            </div>

            <div class="form-group">
                <label for="start_date">تاريخ البداية (شهر-يوم)</label>
                <input type="text" id="start_date" name="start_date" value="<?= htmlspecialchars($startDate) ?>" placeholder="11-24" required>
                <small>الصيغة: MM-DD (مثال: 11-24)</small>
            </div>

            <div class="form-group">
                <label for="duration_days">مدة العرض (أيام)</label>
                <input type="number" id ="duration_days" name="duration_days" value="<?= htmlspecialchars($durationDays) ?>" min="1" max="30" required>
                <small>من 1 إلى 30 يوم</small>
            </div>

            <div class="form-group">
                <label for="discount_percentage">نسبة الخصم (%)</label>
                <input type="number" id="discount_percentage" name="discount_percentage" value="<?= htmlspecialchars($discountPercentage) ?>" min="1" max="90" required>
                <small>من 1% إلى 90%</small>
            </div>

            <!-- الفئات المشمولة -->
            <div class="form-group">
                <label>الفئات المشمولة بالخصم:</label>
                <div class="category-list">
                    <?php foreach ($allCategories as $cat): ?>
                        <div class="checkbox-group">
                            <input type="checkbox" name="categories[]" value="<?= $cat['id'] ?>" 
                                   <?= in_array($cat['id'], $selectedCategories) ? 'checked' : '' ?> id="cat_<?= $cat['id'] ?>">
                            <label for="cat_<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></label>
                        </div>
                    <?php endforeach; ?>
                </div>
                <small>اتركها فارغة لتطبيق الخصم على جميع الفئات</small>
            </div>

            <!-- وضع الاختبار -->
            <div class="form-group checkbox-group">
                <input type="checkbox" name="test_mode" value="1" <?= $testMode ? 'checked' : '' ?> id="test_mode">
                <label for="test_mode">تفعيل وضع الاختبار</label>
            </div>

            <div class="form-group">
                <label for="test_date">تاريخ الاختبار</label>
                <input type="date" name="test_date" value="<?= htmlspecialchars($testDate) ?>">
                <small>لاختبار النظام بتاريخ معين</small>
            </div>

            <!-- تطبيق فوري -->
            <div class="form-group checkbox-group">
                <input type="checkbox" name="apply_now" value="1" id="apply_now">
                <label for="apply_now">تطبيق الخصومات فوراً</label>
            </div>

            <button type="submit" class="btn">💾 حفظ الإعدادات</button>
        </form>

        <!-- روابط سريعة -->
        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;">
            <h3>روابط سريعة:</h3>
            <p>
                <a href="../test_black_friday.php" target="_blank">🧪 اختبار النظام</a> | 
                <a href="index.php">🏠 العودة للوحة التحكم</a>
            </p>
        </div>
    </div>
</body>
</html>