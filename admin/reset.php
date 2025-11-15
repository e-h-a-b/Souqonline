<?php
session_start();
require_once '../config.php';
require_once '../functions.php';

// التحقق من صلاحيات المشرف
if (!isset($_SESSION['admin_id']) || $_SESSION['admin_role'] != 'super_admin') {
    header('Location: login.php');
    exit;
}

$page_title = "إعادة الضبط المتقدم - لوحة التحكم";
$success_message = '';
$error_message = '';

// معالجة طلبات Reset
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $confirmation = $_POST['confirmation'] ?? '';
    
    if ($confirmation !== 'RESET') {
        $error_message = "يرجى كتابة كلمة 'RESET' للتأكيد";
    } else {
        try {
            $reset_type = $_POST['reset_type'];
            $result = null;
            
            switch ($reset_type) {
                case 'orders':
                    $result = resetTestOrders();
                    break;
                case 'customers':
                    $result = resetCustomers();
                    break;
                case 'products':
                    $result = resetProducts();
                    break;
                case 'categories':
                    $result = resetCategories();
                    break;
                case 'coupons':
                    $result = resetCoupons();
                    break;
                case 'reviews':
                    $result = resetReviews();
                    break;
                case 'activity_logs':
                    $result = resetActivityLogs();
                    break;
                case 'partners_wholesalers':
                    $result = resetPartnersWholesalers();
                    break;
                case 'delivery_agents':
                    $result = resetDeliveryAgents();
                    break;
                case 'customer_wallets':
                    $result = resetCustomerWallets();
                    break;
                case 'wallet_transactions':
                    $result = resetWalletTransactions();
                    break;
                case 'cache':
                    resetCache();
                    $result = ['success' => true, 'message' => 'تم مسح الكاش بنجاح'];
                    break;
                case 'statistics':
                    resetStatistics();
                    $result = ['success' => true, 'message' => 'تم إعادة ضبط الإحصائيات بنجاح'];
                    break;
                case 'full_database':
                    // تأكيد ثلاثي للمسح الكامل
                    $conf1 = $_POST['confirmation1'] ?? '';
                    $conf2 = $_POST['confirmation2'] ?? '';
                    
                    if ($conf1 !== 'DELETE ALL DATA' || $conf2 !== 'I UNDERSTAND') {
                        throw new Exception('التأكيدات غير صحيحة للمسح الكامل');
                    }
                    
                    $result = resetFullDatabase();
                    if ($result['success']) {
                        createDefaultData();
                    }
                    break;
                default:
                    throw new Exception('نوع إعادة الضبط غير معروف');
            }
            
            if ($result && $result['success']) {
                $success_message = $result['message'];
                logAdminActivity($_SESSION['admin_id'], 'reset_system', "إعادة ضبط: $reset_type");
            } else {
                throw new Exception($result['message'] ?? 'حدث خطأ غير متوقع');
            }
            
        } catch (Exception $e) {
            $error_message = "خطأ: " . $e->getMessage();
        }
    }
}

// الحصول على إحصائيات الجداول
$stats = getTablesStats();

// معالجة إنشاء البيانات الافتراضية
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_default_data'])) {
    $confirmation = $_POST['confirmation_default'] ?? '';
    
    if ($confirmation !== 'CREATE') {
        $error_message = "يرجى كتابة كلمة 'CREATE' للتأكيد";
    } else {
        try {
            $result = createComprehensiveDefaultData();
            
            if ($result['success']) {
                $success_message = $result['message'];
                if (isset($result['stats'])) {
                    $success_message .= "<br><strong>الإحصائيات:</strong><br>";
                    $success_message .= "- الفئات: " . $result['stats']['categories'] . "<br>";
                    $success_message .= "- المنتجات: " . $result['stats']['products'] . "<br>";
                    $success_message .= "- العملاء: " . $result['stats']['customers'] . "<br>";
                    $success_message .= "- الكوبونات: " . $result['stats']['coupons'] . "<br>";
                    $success_message .= "- المندوبين: " . $result['stats']['agents'] . "<br>";
                    $success_message .= "- الشركاء: " . $result['stats']['partners'];
                }
                logAdminActivity($_SESSION['admin_id'], 'create_default_data', "تم إنشاء بيانات افتراضية شاملة");
            } else {
                throw new Exception($result['message']);
            }
            
        } catch (Exception $e) {
            $error_message = "خطأ: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .header {
            background: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }
        
        .header h1 {
            color: #2d3748;
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .header p {
            color: #718096;
            font-size: 16px;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideIn 0.3s ease;
        }
        
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 2px solid #10b981;
        }
        
        .alert-danger {
            background: #fee2e2;
            color: #991b1b;
            border: 2px solid #ef4444;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card .icon {
            font-size: 40px;
            margin-bottom: 15px;
            color: #667eea;
        }
        
        .stat-card .count {
            font-size: 36px;
            font-weight: bold;
            color: #2d3748;
            margin-bottom: 5px;
        }
        
        .stat-card .label {
            color: #718096;
            font-size: 14px;
        }
        
        .reset-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
        }
        
        .reset-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        
        .reset-card:hover {
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .reset-card.danger {
            border: 3px solid #ef4444;
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
        }
        
        .reset-card h3 {
            font-size: 20px;
            margin-bottom: 15px;
            color: #2d3748;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .reset-card p {
            color: #718096;
            margin-bottom: 20px;
            line-height: 1.6;
        }
        
        .reset-card .warning {
            background: #fff3cd;
            border: 1px solid #ffc107;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 15px;
            font-size: 13px;
            color: #856404;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2d3748;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
        }
        
        .btn-warning {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
        }
        
        .btn-back {
            background: linear-gradient(135deg, #6b7280, #4b5563);
            color: white;
            margin-bottom: 20px;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        .pulse {
            animation: pulse 2s infinite;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php" class="btn btn-back">
            <i class="fas fa-arrow-right"></i> العودة للوحة التحكم
        </a>
        
        <div class="header">
            <h1><i class="fas fa-sync-alt"></i> نظام إعادة الضبط المتقدم</h1>
            <p>إدارة شاملة لحذف البيانات التجريبية وإعادة ضبط النظام</p>
        </div>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle" style="font-size: 24px;"></i>
                <span><?= $success_message ?></span>
            </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle" style="font-size: 24px;"></i>
                <span><?= $error_message ?></span>
            </div>
        <?php endif; ?>
        <!-- إنشاء بيانات افتراضية -->
<div class="reset-card" style="border: 3px solid #10b981; background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);">
    <h3 style="color: #065f46;">
        <i class="fas fa-database"></i> إنشاء بيانات افتراضية
    </h3>
    <p style="color: #047857;">
        إنشاء مجموعة شاملة من البيانات الافتراضية للتجربة والاختبار
    </p>
    <div class="warning" style="background: #bbf7d0; border-color: #10b981;">
        <i class="fas fa-info-circle"></i>
        سيتم إنشاء: فئات، منتجات، عملاء، كوبونات، مندوبين، شركاء، وإعدادات
    </div>
    
    <?php if (hasExistingData()): ?>
    <div class="warning" style="background: #fef3c7; border-color: #f59e0b;">
        <i class="fas fa-exclamation-triangle"></i>
        <strong>تنبيه:</strong> يوجد بيانات حالية في النظام. قد يؤدي هذا إلى تكرار البيانات.
    </div>
    <?php endif; ?>
    
    <form method="POST" onsubmit="return confirmCreateDefaultData()">
        <input type="hidden" name="create_default_data" value="1">
        <div class="form-group">
            <label style="color: #065f46;">تأكيد الإجراء (اكتب: CREATE)</label>
            <input type="text" name="confirmation_default" class="form-control" required 
                   style="border-color: #10b981;">
        </div>
        <button type="submit" class="btn" style="background: linear-gradient(135deg, #10b981, #059669); color: white;">
            <i class="fas fa-plus-circle"></i> إنشاء بيانات افتراضية
        </button>
    </form>
</div>
        <!-- إحصائيات الجداول -->
        <div class="stats-grid">
            <?php foreach ($stats as $table => $data): ?>
                <div class="stat-card">
                    <div class="icon">
                        <i class="fas fa-database"></i>
                    </div>
                    <div class="count"><?= number_format($data['count']) ?></div>
                    <div class="label"><?= $data['label'] ?></div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- خيارات إعادة الضبط -->
        <div class="reset-grid">
            <!-- مسح الطلبات -->
            <div class="reset-card">
                <h3><i class="fas fa-shopping-cart"></i> مسح الطلبات</h3>
                <p>حذف جميع الطلبات وعناصرها والسجلات المرتبطة بها</p>
                <div class="warning">
                    <i class="fas fa-exclamation-circle"></i>
                    سيتم حذف: الطلبات، عناصر الطلبات، سجل الحالات
                </div>
                <form method="POST" onsubmit="return confirmReset('الطلبات')">
                    <input type="hidden" name="action" value="1">
                    <input type="hidden" name="reset_type" value="orders">
                    <div class="form-group">
                        <label>تأكيد الإجراء (اكتب: RESET)</label>
                        <input type="text" name="confirmation" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash"></i> مسح الطلبات
                    </button>
                </form>
            </div>
            
            <!-- مسح العملاء -->
            <div class="reset-card">
                <h3><i class="fas fa-users"></i> مسح العملاء</h3>
                <p>حذف جميع بيانات العملاء والبيانات المرتبطة بهم</p>
                <div class="warning">
                    <i class="fas fa-exclamation-circle"></i>
                    سيتم حذف: العملاء، العناوين، النقاط، التقييمات، المفاوضات
                </div>
                <form method="POST" onsubmit="return confirmReset('العملاء')">
                    <input type="hidden" name="action" value="1">
                    <input type="hidden" name="reset_type" value="customers">
                    <div class="form-group">
                        <label>تأكيد الإجراء (اكتب: RESET)</label>
                        <input type="text" name="confirmation" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-user-times"></i> مسح العملاء
                    </button>
                </form>
            </div>
            
            <!-- مسح المنتجات -->
            <div class="reset-card">
                <h3><i class="fas fa-boxes"></i> مسح المنتجات</h3>
                <p>حذف جميع المنتجات والصور والعروض الخاصة بها</p>
                <div class="warning">
                    <i class="fas fa-exclamation-circle"></i>
                    سيتم حذف: المنتجات، الصور، العروض، العد التنازلي
                </div>
                <form method="POST" onsubmit="return confirmReset('المنتجات')">
                    <input type="hidden" name="action" value="1">
                    <input type="hidden" name="reset_type" value="products">
                    <div class="form-group">
                        <label>تأكيد الإجراء (اكتب: RESET)</label>
                        <input type="text" name="confirmation" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-box-open"></i> مسح المنتجات
                    </button>
                </form>
            </div>
            
            <!-- مسح الفئات -->
            <div class="reset-card">
                <h3><i class="fas fa-tags"></i> مسح الفئات</h3>
                <p>حذف جميع فئات المنتجات</p>
                <form method="POST" onsubmit="return confirmReset('الفئات')">
                    <input type="hidden" name="action" value="1">
                    <input type="hidden" name="reset_type" value="categories">
                    <div class="form-group">
                        <label>تأكيد الإجراء (اكتب: RESET)</label>
                        <input type="text" name="confirmation" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-tag"></i> مسح الفئات
                    </button>
                </form>
            </div>
            
            <!-- مسح الكوبونات -->
            <div class="reset-card">
                <h3><i class="fas fa-ticket-alt"></i> مسح الكوبونات</h3>
                <p>حذف جميع كوبونات الخصم</p>
                <form method="POST" onsubmit="return confirmReset('الكوبونات')">
                    <input type="hidden" name="action" value="1">
                    <input type="hidden" name="reset_type" value="coupons">
                    <div class="form-group">
                        <label>تأكيد الإجراء (اكتب: RESET)</label>
                        <input type="text" name="confirmation" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-percent"></i> مسح الكوبونات
                    </button>
                </form>
            </div>
            
            <!-- مسح التقييمات -->
            <div class="reset-card">
                <h3><i class="fas fa-star"></i> مسح التقييمات</h3>
                <p>حذف جميع تقييمات المنتجات</p>
                <form method="POST" onsubmit="return confirmReset('التقييمات')">
                    <input type="hidden" name="action" value="1">
                    <input type="hidden" name="reset_type" value="reviews">
                    <div class="form-group">
                        <label>تأكيد الإجراء (اكتب: RESET)</label>
                        <input type="text" name="confirmation" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-star-half-alt"></i> مسح التقييمات
                    </button>
                </form>
            </div>
            
            <!-- مسح سجلات النشاط -->
            <div class="reset-card">
                <h3><i class="fas fa-history"></i> مسح السجلات</h3>
                <p>حذف سجلات نشاط المسؤولين</p>
                <form method="POST" onsubmit="return confirmReset('السجلات')">
                    <input type="hidden" name="action" value="1">
                    <input type="hidden" name="reset_type" value="activity_logs">
                    <div class="form-group">
                        <label>تأكيد الإجراء (اكتب: RESET)</label>
                        <input type="text" name="confirmation" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-file-alt"></i> مسح السجلات
                    </button>
                </form>
            </div>
            
            <!-- مسح الشركاء والموزعين -->
            <div class="reset-card">
                <h3><i class="fas fa-handshake"></i> الشركاء والموزعين</h3>
                <p>حذف بيانات الشركاء وتجار الجملة</p>
                <form method="POST" onsubmit="return confirmReset('الشركاء')">
                    <input type="hidden" name="action" value="1">
                    <input type="hidden" name="reset_type" value="partners_wholesalers">
                    <div class="form-group">
                        <label>تأكيد الإجراء (اكتب: RESET)</label>
                        <input type="text" name="confirmation" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-building"></i> مسح البيانات
                    </button>
                </form>
            </div>
            
            <!-- مسح المندوبين -->
            <div class="reset-card">
                <h3><i class="fas fa-truck"></i> مسح المندوبين</h3>
                <p>حذف بيانات مندوبي التوصيل</p>
                <form method="POST" onsubmit="return confirmReset('المندوبين')">
                    <input type="hidden" name="action" value="1">
                    <input type="hidden" name="reset_type" value="delivery_agents">
                    <div class="form-group">
                        <label>تأكيد الإجراء (اكتب: RESET)</label>
                        <input type="text" name="confirmation" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-user-times"></i> مسح المندوبين
                    </button>
                </form>
            </div>
            <!-- مسح محافظ العملاء -->
<div class="reset-card">
    <h3><i class="fas fa-wallet"></i> مسح محافظ العملاء</h3>
    <p>حذف جميع محافظ العملاء ومعاملاتهم المالية</p>
    <div class="warning">
        <i class="fas fa-exclamation-circle"></i>
        سيتم حذف: المحافظ، المعاملات، الأرصدة، السجلات المالية
    </div>
    <form method="POST" onsubmit="return confirmReset('محافظ العملاء')">
        <input type="hidden" name="action" value="1">
        <input type="hidden" name="reset_type" value="customer_wallets">
        <div class="form-group">
            <label>تأكيد الإجراء (اكتب: RESET)</label>
            <input type="text" name="confirmation" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-danger">
            <i class="fas fa-money-bill-wave"></i> مسح المحافظ
        </button>
    </form>
</div>

<!-- مسح معاملات المحافظ -->
<div class="reset-card">
    <h3><i class="fas fa-exchange-alt"></i> مسح معاملات المحافظ</h3>
    <p>حذف جميع المعاملات المالية وإعادة تعيين الأرصدة</p>
    <div class="warning">
        <i class="fas fa-exclamation-circle"></i>
        سيتم حذف: جميع المعاملات، وإعادة تعيين الأرصدة إلى الصفر
    </div>
    <form method="POST" onsubmit="return confirmReset('معاملات المحافظ')">
        <input type="hidden" name="action" value="1">
        <input type="hidden" name="reset_type" value="wallet_transactions">
        <div class="form-group">
            <label>تأكيد الإجراء (اكتب: RESET)</label>
            <input type="text" name="confirmation" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-warning">
            <i class="fas fa-history"></i> مسح المعاملات
        </button>
    </form>
</div>
            <!-- المسح الكامل -->
            <div class="reset-card danger pulse">
                <h3 style="color: #dc2626;">
                    <i class="fas fa-skull-crossbones"></i> مسح كامل قاعدة البيانات
                </h3>
                <p style="color: #991b1b; font-weight: bold;">
                    ⚠️ تحذير شديد الخطورة! سيتم حذف جميع البيانات نهائيًا
                </p>
                <div class="warning" style="background: #fecaca; border-color: #ef4444;">
                    <strong>سيتم الاحتفاظ فقط بـ:</strong>
                    <ul style="margin: 10px 0 0 20px;">
                        <li>حسابات المسؤولين</li>
                        <li>إعدادات النظام</li>
                    </ul>
                </div>
                <form method="POST" onsubmit="return confirmFullReset()" id="fullResetForm">
                    <input type="hidden" name="action" value="1">
                    <input type="hidden" name="reset_type" value="full_database">
                    <div class="form-group">
                        <label style="color: #dc2626;">التأكيد الأول (اكتب: DELETE ALL DATA)</label>
                        <input type="text" name="confirmation1" class="form-control" required 
                               style="border-color: #ef4444;">
                    </div>
                    <div class="form-group">
                        <label style="color: #dc2626;">التأكيد الثاني (اكتب: I UNDERSTAND)</label>
                        <input type="text" name="confirmation2" class="form-control" required
                               style="border-color: #ef4444;">
                    </div>
                    <div class="form-group">
                        <label style="color: #dc2626;">التأكيد النهائي (اكتب: RESET)</label>
                        <input type="text" name="confirmation" class="form-control" required
                               style="border-color: #ef4444;">
                    </div>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-bomb"></i> مسح كامل قاعدة البيانات
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <script>
function confirmCreateDefaultData() {
    const confirmation = document.querySelector('input[name="confirmation_default"]').value;
    
    if (confirmation !== 'CREATE') {
        alert('يرجى كتابة كلمة CREATE للتأكيد');
        return false;
    }
    
    return confirm(`هل أنت متأكد من إنشاء البيانات الافتراضية؟

سيتم إنشاء بيانات جديدة فريدة:
• فئات منتجات
• منتجات متنوعة  
• عملاء افتراضيين
• كوبونات خصم
• مندوبي توصيل
• شركاء

سيتم تجنب التكرار مع البيانات الحالية.`);
}
    function confirmReset(type) {
        return confirm(`هل أنت متأكد من مسح ${type}؟\n\nهذا الإجراء لا يمكن التراجع عنه!`);
    }
    
    function confirmFullReset() {
        const conf1 = document.querySelector('input[name="confirmation1"]').value;
        const conf2 = document.querySelector('input[name="confirmation2"]').value;
        const conf3 = document.querySelector('input[name="confirmation"]').value;
        
        if (conf1 !== 'DELETE ALL DATA') {
            alert('التأكيد الأول غير صحيح');
            return false;
        }
        
        if (conf2 !== 'I UNDERSTAND') {
            alert('التأكيد الثاني غير صحيح');
            return false;
        }
        
        if (conf3 !== 'RESET') {
            alert('التأكيد النهائي غير صحيح');
            return false;
        }
        
        return confirm(`⚠️ تحذير نهائي ⚠️

سيتم حذف جميع البيانات بشكل نهائي:
• جميع المنتجات والفئات
• جميع العملاء والطلبات
• جميع المحافظ والنقاط
• جميع السجلات والتاريخ

هذا الإجراء لا يمكن التراجع عنه!

هل أنت متأكد تماماً من المتابعة؟`);
    }
    </script>
</body>
</html>