<?php
/**
 * صفحة الإعدادات
 */
session_start();
require_once '../config.php';
require_once '../functions.php';
// في ملف حفظ الإعدادات (مثل save_settings.php)
$paymentSettings = [
    'order_prefix',
    'vodafone_cash_number',
    'vodafone_cash_name',
    'instapay_account',
    'fawry_merchant_code',
    'fawry_secret_key',
    'visa_merchant_email',
    'visa_secret_key',
    'currency',
    'currency_symbol',
    'payment_cod',
    'payment_visa',
    'payment_instapay',
    'payment_vodafone_cash',
    'payment_fawry',
    'sandbox_mode'
];

foreach ($paymentSettings as $setting) {
    $value = $_POST["setting_{$setting}"] ?? '';
    getSetting($setting, $value);
}
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// جلب الإعدادات الحالية
$stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
$settings_data = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$settings = [];
foreach ($settings_data as $key => $value) {
    $settings[$key] = $value;
}
$success_msg = '';
$success = '';
$error = '';
$error_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	// معالجة إضافة منطقة شحن جديدة
    if (isset($_POST['action']) && $_POST['action'] === 'add_shipping_rate') {
        try {
            $region = trim($_POST['region']);
            $cost = floatval($_POST['cost']);
            $delivery_time = trim($_POST['delivery_time']);

            // التحقق من البيانات
            if (empty($region) || $cost < 0 || empty($delivery_time)) {
                throw new Exception('جميع الحقول مطلوبة ويجب أن تكون صحيحة');
            }

            // إدخال المنطقة الجديدة
            $stmt = $pdo->prepare("INSERT INTO shipping_rates (region, cost, delivery_time, is_active) VALUES (?, ?, ?, 1)");
            $stmt->execute([$region, $cost, $delivery_time]);

            // تحديث الإعدادات العامة بناءً على المناطق المضافة
            updateShippingSettings($pdo);

            $success_msg = 'تم إضافة منطقة الشحن بنجاح';
            
            // تسجيل النشاط
            logActivity('shipping_rate_added', "تم إضافة منطقة شحن جديدة: $region", $_SESSION['admin_id']);

        } catch (Exception $e) {
            $error_msg = 'حدث خطأ أثناء إضافة منطقة الشحن: ' . $e->getMessage();
        }
    }

    // معالجة حذف منطقة شحن
    if (isset($_POST['action']) && $_POST['action'] === 'delete_shipping_rate') {
        try {
            $rate_id = intval($_POST['rate_id']);

            // الحذف الناعم (تعطيل بدلاً من الحذف)
            $stmt = $pdo->prepare("UPDATE shipping_rates SET is_active = 0 WHERE id = ?");
            $stmt->execute([$rate_id]);

            // تحديث الإعدادات العامة بعد الحذف
            updateShippingSettings($pdo);

            $success_msg = 'تم حذف منطقة الشحن بنجاح';
            
            // تسجيل النشاط
            logActivity('shipping_rate_deleted', "تم حذف منطقة شحن (ID: $rate_id)", $_SESSION['admin_id']);

        } catch (Exception $e) {
            $error_msg = 'حدث خطأ أثناء حذف منطقة الشحن: ' . $e->getMessage();
        }
    }

    try {
        $pdo->beginTransaction();
        
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'setting_') === 0) {
                $setting_key = substr($key, 8);
                $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
                $stmt->execute([$value, $setting_key]);
            }
        }
        
        // إذا لم يتم إرسال maintenance_mode، قم بتعيينه إلى 0
        $maintenance_mode = isset($_POST['setting_maintenance_mode']) ? '1' : '0';
        $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
        $stmt->execute([$maintenance_mode, 'maintenance_mode']);
        
        $pdo->commit();
        
        logActivity('settings_updated', "تم تحديث إعدادات المتجر", $_SESSION['admin_id']);
        $success = 'تم حفظ الإعدادات بنجاح';
        
        // تحديث الإعدادات المحلية
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
        $settings_data = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        $settings = [];
        foreach ($settings_data as $key => $value) {
            $settings[$key] = $value;
        }
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = 'حدث خطأ أثناء حفظ الإعدادات: ' . $e->getMessage();
    }
}
// دالة لتحديث إعدادات الشحن بناءً على جدول shipping_rates
function updateShippingSettings($pdo) {
    // جلب متوسط تكاليف الشحن من المناطق النشطة
    $stmt = $pdo->query("
        SELECT 
            AVG(CASE WHEN region LIKE '%القاهرة%' THEN cost ELSE NULL END) as avg_cairo,
            AVG(CASE WHEN region LIKE '%الجيزة%' THEN cost ELSE NULL END) as avg_giza,
            AVG(CASE WHEN region LIKE '%الإسكندرية%' THEN cost ELSE NULL END) as avg_alex,
            AVG(CASE WHEN region NOT LIKE '%القاهرة%' AND region NOT LIKE '%الجيزة%' AND region NOT LIKE '%الإسكندرية%' THEN cost ELSE NULL END) as avg_other
        FROM shipping_rates 
        WHERE is_active = 1
    ");
    $averages = $stmt->fetch(PDO::FETCH_ASSOC);

    // تحديث الإعدادات
    $settings_to_update = [
        'shipping_cost_cairo' => $averages['avg_cairo'] ? round($averages['avg_cairo'], 2) : 30,
        'shipping_cost_giza' => $averages['avg_giza'] ? round($averages['avg_giza'], 2) : 30,
        'shipping_cost_alex' => $averages['avg_alex'] ? round($averages['avg_alex'], 2) : 50,
        'shipping_cost_other' => $averages['avg_other'] ? round($averages['avg_other'], 2) : 70
    ];

    foreach ($settings_to_update as $key => $value) {
        $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
        $stmt->execute([$value, $key]);
    }
}

// جلب الإعدادات الحالية (الكود الأصلي)
$stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
$settings_data = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$settings = [];
foreach ($settings_data as $key => $value) {
    $settings[$key] = $value;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الإعدادات - لوحة التحكم</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8fafc;
            color: #334155;
        }
        .admin-wrapper { display: flex; min-height: 100vh; }
        
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
        .btn-success { background: #16a34a; color: #fff; }
        .btn-success:hover { background: #15803d; }
        .btn-secondary { background: #64748b; color: #fff; }
        .btn-secondary:hover { background: #475569; }
        
        .card {
            background: #fff;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #334155;
        }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 10px 12px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
        }
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .form-help {
            font-size: 12px;
            color: #64748b;
            margin-top: 5px;
        }
        
        .settings-tabs {
            display: flex;
            border-bottom: 1px solid #e2e8f0;
            margin-bottom: 25px;
        }
        .tab-btn {
            padding: 12px 24px;
            background: none;
            border: none;
            border-bottom: 3px solid transparent;
            font-weight: 600;
            color: #64748b;
            cursor: pointer;
            transition: all 0.3s;
        }
        .tab-btn.active {
            color: #2563eb;
            border-bottom-color: #2563eb;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .checkbox-group input[type="checkbox"] {
            width: auto;
        }
        
        .alert {
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .alert-success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }
        .alert-error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        
        .settings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
		.table-responsive {
    overflow-x: auto;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
}

table {
    width: 100%;
    border-collapse: collapse;
    background: white;
}

th {
    background: #f8fafc;
    font-weight: 600;
    color: #334155;
}

tr:hover {
    background: #f8fafc;
}

.btn-danger {
    background: #dc2626;
    color: white;
    border: none;
    padding: 6px 12px;
    border-radius: 6px;
    cursor: pointer;
    transition: background 0.3s;
}

.btn-danger:hover {
    background: #b91c1c;
}
    </style>
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
                    <h1>إعدادات المتجر</h1>
                    <p style="color: #64748b; margin-top: 5px;">إدارة إعدادات المتجر العامة</p>
                </div>
                <button type="submit" form="settings-form" class="btn btn-success">
                    <i class="fas fa-save"></i>
                    حفظ الإعدادات
                </button>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form id="settings-form" method="post" action="settings.php">
                <div class="settings-tabs">
                    <button type="button" class="tab-btn active" data-tab="general">عام</button>
                    <button type="button" class="tab-btn" data-tab="shipping"> إعدادات الشحن والتوصيل</button> 
                    <button type="button" class="tab-btn" data-tab="payment">الدفع</button>
                    <button type="button" class="tab-btn" data-tab="social">التواصل الاجتماعي</button>
                </div>

                <!-- General Settings -->
                <div class="tab-content active" id="general-tab">
                    <div class="card">
                        <h2 style="margin-bottom: 20px;">الإعدادات العامة</h2>
                        <div class="settings-grid">
                            <div class="form-group">
                                <label for="setting_store_name">اسم المتجر</label>
                                <input type="text" id="setting_store_name" name="setting_store_name" 
                                       value="<?= htmlspecialchars($settings['store_name'] ?? '') ?>" required>
                                <div class="form-help">اسم المتجر الذي يظهر للعملاء</div>
                            </div>

                            <div class="form-group">
                                <label for="setting_store_description">وصف المتجر</label>
                                <textarea id="setting_store_description" name="setting_store_description"><?= htmlspecialchars($settings['store_description'] ?? '') ?></textarea>
                                <div class="form-help">وصف قصير عن المتجر</div>
                            </div>

                            <div class="form-group">
                                <label for="setting_store_email">البريد الإلكتروني</label>
                                <input type="email" id="setting_store_email" name="setting_store_email" 
                                       value="<?= htmlspecialchars($settings['store_email'] ?? '') ?>" required>
                                <div class="form-help">البريد الإلكتروني الرسمي للمتجر</div>
                            </div>

                            <div class="form-group">
                                <label for="setting_store_phone">رقم الهاتف</label>
                                <input type="text" id="setting_store_phone" name="setting_store_phone" 
                                       value="<?= htmlspecialchars($settings['store_phone'] ?? '') ?>">
                                <div class="form-help">رقم هاتف المتجر للتواصل</div>
                            </div>

                            <div class="form-group">
                                <label for="setting_currency">العملة</label>
                                <input type="text" id="setting_currency" name="setting_currency" 
                                       value="<?= htmlspecialchars($settings['currency'] ?? 'EGP') ?>">
                                <div class="form-help">رمز العملة (مثال: EGP, USD, SAR)</div>
                            </div>

                            <div class="form-group">
                                <label for="setting_currency_symbol">رمز العملة</label>
                                <input type="text" id="setting_currency_symbol" name="setting_currency_symbol" 
                                       value="<?= htmlspecialchars($settings['currency_symbol'] ?? 'ج.م') ?>">
                                <div class="form-help">رمز العملة الذي يظهر للعملاء</div>
                            </div>

                            <div class="form-group">
                                <label for="setting_tax_rate">معدل الضريبة (%)</label>
                                <input type="number" id="setting_tax_rate" name="setting_tax_rate" 
                                       value="<?= htmlspecialchars($settings['tax_rate'] ?? '0') ?>" step="0.01" min="0">
                                <div class="form-help">معدل الضريبة المئوية المطبقة على الطلبات</div>
                            </div>

                            <div class="form-group">
                                <label for="setting_items_per_page">عدد العناصر في الصفحة</label>
                                <input type="number" id="setting_items_per_page" name="setting_items_per_page" 
                                       value="<?= htmlspecialchars($settings['items_per_page'] ?? '12') ?>">
                                <div class="form-help">عدد المنتجات المعروضة في كل صفحة</div>
                            </div>

                            <div class="form-group">
                                <div class="checkbox-group">
                                    <input type="checkbox" id="setting_maintenance_mode" name="setting_maintenance_mode" 
                                           value="1" <?= ($settings['maintenance_mode'] ?? '0') == '1' ? 'checked' : '' ?>>
                                    <label for="setting_maintenance_mode">وضع الصيانة</label>
                                </div>
                                <div class="form-help">عند التفعيل، سيتوقف المتجر عن العمل للزوار</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Shipping Settings --> 
                <div class="tab-content" id="shipping-tab">
                    <div class="card">
                        <h2 style="margin-bottom: 20px;">إدارة مناطق الشحن</h2>
                
                        <!-- رسائل النجاح والخطأ -->
                        <?php if (isset($success_msg)): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle"></i>
                                <?= htmlspecialchars($success_msg) ?>
                            </div>
                        <?php endif; ?>
                
                        <?php if (isset($error_msg)): ?>
                            <div class="alert alert-error">
                                <i class="fas fa-exclamation-circle"></i>
                                <?= htmlspecialchars($error_msg) ?>
                            </div>
                        <?php endif; ?>
                   <!-- إعدادات الشحن الأساسية -->
                        <div class="card">
                            <h3 style="margin-bottom: 15px;">الإعدادات الأساسية للشحن</h3>
                            <div class="settings-grid">
                                <div class="form-group">
                                    <label for="setting_free_shipping_threshold">حد الشحن المجاني (ج.م)</label>
                                    <input type="number" id="setting_free_shipping_threshold" name="setting_free_shipping_threshold" 
                                           value="<?= htmlspecialchars($settings['free_shipping_threshold'] ?? '500') ?>" step="0.01" min="0">
                                    <div class="form-help">الحد الأدنى للطلب للحصول على شحن مجاني</div>
                                </div>
                            </div>
                        </div>
                    
                        <!-- جدول مناطق الشحن -->
                        <div class="card">
                            <h3 style="margin-bottom: 15px;">قائمة مناطق الشحن</h3>
                            
                            <?php
                            // جلب جميع مناطق الشحن
                            $stmt = $pdo->query("SELECT * FROM shipping_rates WHERE is_active = 1 ORDER BY region");
                            $shippingRates = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            ?>
                
                            <?php if (empty($shippingRates)): ?>
                                <div style="text-align: center; padding: 40px; color: #64748b;">
                                    <i class="fas fa-shipping-fast" style="font-size: 48px; margin-bottom: 15px;"></i>
                                    <p>لا توجد مناطق شحن مضافة حالياً</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table style="width: 100%; border-collapse: collapse; background: white;">
                                        <thead>
                                            <tr style="background: #f1f5f9;">
                                                <th style="padding: 12px; text-align: right; border-bottom: 2px solid #e2e8f0;">المنطقة</th>
                                                <th style="padding: 12px; text-align: center; border-bottom: 2px solid #e2e8f0;">التكلفة</th>
                                                <th style="padding: 12px; text-align: center; border-bottom: 2px solid #e2e8f0;">مدة التوصيل</th>
                                                <th style="padding: 12px; text-align: center; border-bottom: 2px solid #e2e8f0;">الإجراءات</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($shippingRates as $rate): ?>
                                                <tr style="border-bottom: 1px solid #e2e8f0;">
                                                    <td style="padding: 12px; text-align: right;">
                                                        <?= htmlspecialchars($rate['region']) ?>
                                                    </td>
                                                    <td style="padding: 12px; text-align: center; font-weight: bold; color: #16a34a;">
                                                        <?= number_format($rate['cost'], 2) ?> ج.م
                                                    </td>
                                                    <td style="padding: 12px; text-align: center;">
                                                        <?= htmlspecialchars($rate['delivery_time']) ?>
                                                    </td>
                                                    <td style="padding: 12px; text-align: center;">
                                                        <form method="post" action="settings.php" style="display: inline;">
                                                            <input type="hidden" name="action" value="delete_shipping_rate">
                                                            <input type="hidden" name="rate_id" value="<?= $rate['id'] ?>">
                                                            <button type="submit" class="btn btn-danger" 
                                                                    onclick="return confirm('هل أنت متأكد من حذف منطقة الشحن هذه؟')"
                                                                    style="padding: 6px 12px; font-size: 12px;">
                                                                <i class="fas fa-trash"></i> حذف
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                
                        <!-- نموذج إضافة منطقة شحن جديدة -->
                        <div class="card" style="margin-bottom: 20px; background: #f8fafc;">
                            <h3 style="margin-bottom: 15px;">إضافة منطقة شحن جديدة</h3>
                            <form method="post" action="settings.php?action=add_shipping_rate">
                                <div class="settings-grid">
                                    <div class="form-group">
                                        <label for="new_region">اسم المنطقة</label>
                                        <input type="text" id="new_region" name="region" required 
                                               placeholder="مثال: القاهرة - مدينة نصر">
                                    </div>
                
                                    <div class="form-group">
                                        <label for="new_cost">تكلفة الشحن (ج.م)</label>
                                        <input type="number" id="new_cost" name="cost" step="0.01" min="0" required
                                               placeholder="مثال: 30.00">
                                    </div>
                
                                    <div class="form-group">
                                        <label for="new_delivery_time">مدة التوصيل</label>
                                        <input type="text" id="new_delivery_time" name="delivery_time" required
                                               placeholder="مثال: 2-3 أيام">
                                    </div>
                
                                    <div class="form-group">
                                        <label style="visibility: hidden;">إضافة</label>
                                        <button type="submit" class="btn btn-success" style="width: 100%;">
                                            <i class="fas fa-plus"></i> إضافة منطقة شحن
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                
                     </div>
                </div>
                <!-- Payment Settings --> 
                <div class="tab-content" id="payment-tab">
    <div class="card">
        <h2 style="margin-bottom: 20px;">إعدادات الدفع</h2>
        <div class="settings-grid">
            <div class="form-group">
                <label for="setting_order_prefix">بادئة أرقام الطلبات</label>
                <input type="text" id="setting_order_prefix" name="setting_order_prefix" 
                       value="<?= htmlspecialchars($settings['order_prefix'] ?? 'ORD') ?>">
                <div class="form-help">البادئة المستخدمة في أرقام الطلبات</div>
            </div>
            
            <!-- إعدادات حسابات الدفع -->
            <div class="form-group full-width">
                <h3 style="border-bottom: 2px solid var(--primary-color); padding-bottom: 10px; margin-bottom: 20px;">حسابات استقبال الأموال</h3>
            </div>

            <!-- Vodafone Cash -->
            <div class="form-group">
                <label for="setting_vodafone_cash_number">رقم Vodafone Cash</label>
                <input type="text" id="setting_vodafone_cash_number" name="setting_vodafone_cash_number" 
                       value="<?= htmlspecialchars($settings['vodafone_cash_number'] ?? '') ?>" 
                       placeholder="01012345678">
                <div class="form-help">رقم الهاتف المرتبط بـ Vodafone Cash</div>
            </div>

            <div class="form-group">
                <label for="setting_vodafone_cash_name">اسم صاحب الحساب (Vodafone)</label>
                <input type="text" id="setting_vodafone_cash_name" name="setting_vodafone_cash_name" 
                       value="<?= htmlspecialchars($settings['vodafone_cash_name'] ?? '') ?>" 
                       placeholder="اسم المتجر">
                <div class="form-help">الاسم الظاهر عند التحويل</div>
            </div>

            <!-- InstaPay -->
            <div class="form-group">
                <label for="setting_instapay_account">حساب InstaPay</label>
                <input type="text" id="setting_instapay_account" name="setting_instapay_account" 
                       value="<?= htmlspecialchars($settings['instapay_account'] ?? '') ?>" 
                       placeholder="example@instapay">
                <div class="form-help">البريد الإلكتروني أو المعرف الخاص بـ InstaPay</div>
            </div>

            <!-- فوري -->
            <div class="form-group">
                <label for="setting_fawry_merchant_code">كود التاجر (فوري)</label>
                <input type="text" id="setting_fawry_merchant_code" name="setting_fawry_merchant_code" 
                       value="<?= htmlspecialchars($settings['fawry_merchant_code'] ?? '') ?>">
                <div class="form-help">كود التاجر الخاص بك في فوري</div>
            </div>

            <div class="form-group">
                <label for="setting_fawry_secret_key">المفتاح السري (فوري)</label>
                <input type="password" id="setting_fawry_secret_key" name="setting_fawry_secret_key" 
                       value="<?= htmlspecialchars($settings['fawry_secret_key'] ?? '') ?>">
                <div class="form-help">المفتاح السري الخاص بك في فوري</div>
            </div>

            <!-- بطاقات الائتمان -->
            <div class="form-group">
                <label for="setting_visa_merchant_email">بريد التاجر (Visa/Mastercard)</label>
                <input type="email" id="setting_visa_merchant_email" name="setting_visa_merchant_email" 
                       value="<?= htmlspecialchars($settings['visa_merchant_email'] ?? '') ?>">
                <div class="form-help">البريد الإلكتروني المسجل في بوابة الدفع</div>
            </div>

            <div class="form-group">
                <label for="setting_visa_secret_key">المفتاح السري (Visa/Mastercard)</label>
                <input type="password" id="setting_visa_secret_key" name="setting_visa_secret_key" 
                       value="<?= htmlspecialchars($settings['visa_secret_key'] ?? '') ?>">
                <div class="form-help">المفتاح السري الخاص ببوابة الدفع</div>
            </div>

            <!-- إعدادات إضافية -->
            <div class="form-group">
                <label for="setting_currency">العملة</label>
                <select id="setting_currency" name="setting_currency">
                    <option value="EGP" <?= ($settings['currency'] ?? 'EGP') === 'EGP' ? 'selected' : '' ?>>جنيه مصري (EGP)</option>
                    <option value="USD" <?= ($settings['currency'] ?? '') === 'USD' ? 'selected' : '' ?>>دولار أمريكي (USD)</option>
                    <option value="SAR" <?= ($settings['currency'] ?? '') === 'SAR' ? 'selected' : '' ?>>ريال سعودي (SAR)</option>
                    <option value="AED" <?= ($settings['currency'] ?? '') === 'AED' ? 'selected' : '' ?>>درهم إماراتي (AED)</option>
                </select>
            </div>

            <div class="form-group">
                <label for="setting_currency_symbol">رمز العملة</label>
                <input type="text" id="setting_currency_symbol" name="setting_currency_symbol" 
                       value="<?= htmlspecialchars($settings['currency_symbol'] ?? 'ج.م') ?>" 
                       placeholder="ج.م">
                <div class="form-help">الرمز المستخدم لعرض الأسعار</div>
            </div>

            <!-- تفعيل طرق الدفع -->
            <div class="form-group full-width">
                <h3 style="border-bottom: 2px solid var(--primary-color); padding-bottom: 10px; margin-bottom: 20px;">تفعيل طرق الدفع</h3>
            </div>

            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="setting_payment_cod" value="1" 
                           <?= ($settings['payment_cod'] ?? '1') === '1' ? 'checked' : '' ?>>
                    <span class="checkmark"></span>
                    تفعيل الدفع عند الاستلام
                </label>
            </div>

            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="setting_payment_visa" value="1" 
                           <?= ($settings['payment_visa'] ?? '1') === '1' ? 'checked' : '' ?>>
                    <span class="checkmark"></span>
                    تفعيل الدفع ببطاقات الائتمان
                </label>
            </div>

            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="setting_payment_instapay" value="1" 
                           <?= ($settings['payment_instapay'] ?? '1') === '1' ? 'checked' : '' ?>>
                    <span class="checkmark"></span>
                    تفعيل InstaPay
                </label>
            </div>

            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="setting_payment_vodafone_cash" value="1" 
                           <?= ($settings['payment_vodafone_cash'] ?? '1') === '1' ? 'checked' : '' ?>>
                    <span class="checkmark"></span>
                    تفعيل Vodafone Cash
                </label>
            </div>

            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="setting_payment_fawry" value="1" 
                           <?= ($settings['payment_fawry'] ?? '1') === '1' ? 'checked' : '' ?>>
                    <span class="checkmark"></span>
                    تفعيل فوري
                </label>
            </div>

            <!-- إعدادات اختبارية -->
            <div class="form-group full-width">
                <h3 style="border-bottom: 2px solid var(--primary-color); padding-bottom: 10px; margin-bottom: 20px;">الإعدادات الاختبارية</h3>
            </div>

            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="setting_sandbox_mode" value="1" 
                           <?= ($settings['sandbox_mode'] ?? '0') === '1' ? 'checked' : '' ?>>
                    <span class="checkmark"></span>
                    وضع التجربة (Sandbox Mode)
                </label>
                <div class="form-help">تفعيل هذا الخيار للاختبار دون عمليات دفع حقيقية</div>
            </div>
        </div>
    </div>
</div>

                <!-- Social Media Settings -->
                <div class="tab-content" id="social-tab">
                    <div class="card">
                        <h2 style="margin-bottom: 20px;">التواصل الاجتماعي</h2>
                        <div class="settings-grid">
                            <div class="form-group">
                                <label for="setting_facebook_url">رابط فيسبوك</label>
                                <input type="url" id="setting_facebook_url" name="setting_facebook_url" 
                                       value="<?= htmlspecialchars($settings['facebook_url'] ?? '') ?>">
                            </div>

                            <div class="form-group">
                                <label for="setting_instagram_url">رابط انستجرام</label>
                                <input type="url" id="setting_instagram_url" name="setting_instagram_url" 
                                       value="<?= htmlspecialchars($settings['instagram_url'] ?? '') ?>">
                            </div>

                            <div class="form-group">
                                <label for="setting_twitter_url">رابط تويتر</label>
                                <input type="url" id="setting_twitter_url" name="setting_twitter_url" 
                                       value="<?= htmlspecialchars($settings['twitter_url'] ?? '') ?>">
                            </div>

                            <div class="form-group">
                                <label for="setting_whatsapp_number">رقم واتساب</label>
                                <input type="text" id="setting_whatsapp_number" name="setting_whatsapp_number" 
                                       value="<?= htmlspecialchars($settings['whatsapp_number'] ?? '') ?>">
                            </div>

                            <div class="form-group">
                                <label for="setting_google_analytics_id">معرف Google Analytics</label>
                                <input type="text" id="setting_google_analytics_id" name="setting_google_analytics_id" 
                                       value="<?= htmlspecialchars($settings['google_analytics_id'] ?? '') ?>">
                            </div>

                            <div class="form-group">
                                <label for="setting_facebook_pixel_id">معرف Facebook Pixel</label>
                                <input type="text" id="setting_facebook_pixel_id" name="setting_facebook_pixel_id" 
                                       value="<?= htmlspecialchars($settings['facebook_pixel_id'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </main>
    </div>

    <script>
        // تبديل التبويبات
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                // إخفاء جميع المحتويات
                document.querySelectorAll('.tab-content').forEach(content => {
                    content.classList.remove('active');
                });
                
                // إلغاء تنشيط جميع الأزرار
                document.querySelectorAll('.tab-btn').forEach(btn => {
                    btn.classList.remove('active');
                });
                
                // تفعيل الزر الحالي
                this.classList.add('active');
                
                // إظهار المحتوى المقابل
                const tabId = this.getAttribute('data-tab');
                document.getElementById(tabId + '-tab').classList.add('active');
            });
        });

        // التحقق من صحة النموذج
        document.getElementById('settings-form').addEventListener('submit', function(e) {
            let isValid = true;
            
            // التحقق من الحقول المطلوبة
            const requiredFields = this.querySelectorAll('[required]');
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.style.borderColor = '#dc2626';
                } else {
                    field.style.borderColor = '#e2e8f0';
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('يرجى ملء جميع الحقول المطلوبة');
            }
        });
    </script>

</body>
</html>