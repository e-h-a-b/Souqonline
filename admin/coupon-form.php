<?php
/**
 * صفحة إضافة/تعديل الكوبون
 */
session_start();
require_once '../config.php';
require_once '../functions.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$coupon_id = $_GET['id'] ?? 0;
$coupon = null;
$error = '';
$success = '';

// جلب بيانات الكوبون إذا كان تعديل
if ($coupon_id) {
    $stmt = $pdo->prepare("SELECT * FROM coupons WHERE id = ?");
    $stmt->execute([$coupon_id]);
    $coupon = $stmt->fetch();
    
    if (!$coupon) {
        $error = 'الكوبون غير موجود';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = cleanInput($_POST['code'] ?? '');
    $description = cleanInput($_POST['description'] ?? '');
    $discount_type = $_POST['discount_type'] ?? 'percentage';
    $discount_value = floatval($_POST['discount_value'] ?? 0);
    $min_order_amount = floatval($_POST['min_order_amount'] ?? 0);
    $max_discount_amount = $_POST['max_discount_amount'] ? floatval($_POST['max_discount_amount']) : null;
    $usage_limit = $_POST['usage_limit'] ? intval($_POST['usage_limit']) : null;
    $valid_from = $_POST['valid_from'] ?: null;
    $valid_until = $_POST['valid_until'] ?: null;
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // التحقق من البيانات
    if (empty($code) || empty($discount_value)) {
        $error = 'يرجى ملء الحقول المطلوبة (الكود وقيمة الخصم)';
    } elseif ($discount_type == 'percentage' && ($discount_value <= 0 || $discount_value > 100)) {
        $error = 'نسبة الخصم يجب أن تكون بين 1 و 100';
    } elseif ($discount_type == 'fixed' && $discount_value <= 0) {
        $error = 'قيمة الخصم يجب أن تكون أكبر من صفر';
    } else {
        try {
            if ($coupon_id && $coupon) {
                // تحديث الكوبون
                $stmt = $pdo->prepare("
                    UPDATE coupons SET 
                        code = ?, description = ?, discount_type = ?, discount_value = ?,
                        min_order_amount = ?, max_discount_amount = ?, usage_limit = ?,
                        valid_from = ?, valid_until = ?, is_active = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $code, $description, $discount_type, $discount_value,
                    $min_order_amount, $max_discount_amount, $usage_limit,
                    $valid_from, $valid_until, $is_active, $coupon_id
                ]);
                
                $action = 'updated';
                $message = 'تم تحديث الكوبون بنجاح';
            } else {
                // إضافة كوبون جديد
                $stmt = $pdo->prepare("
                    INSERT INTO coupons (
                        code, description, discount_type, discount_value,
                        min_order_amount, max_discount_amount, usage_limit,
                        valid_from, valid_until, is_active
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $code, $description, $discount_type, $discount_value,
                    $min_order_amount, $max_discount_amount, $usage_limit,
                    $valid_from, $valid_until, $is_active
                ]);
                
                $coupon_id = $pdo->lastInsertId();
                $action = 'created';
                $message = 'تم إضافة الكوبون بنجاح';
            }
            
            logActivity("coupon_{$action}", $message, $_SESSION['admin_id']);
            $success = $message;
            
            // إعادة توجيه بعد النجاح
            header('Location: coupon-form.php?id=' . $coupon_id . '&success=' . urlencode($message));
            exit;
            
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                $error = 'كود الكوبون مستخدم مسبقاً';
            } else {
                $error = 'حدث خطأ أثناء حفظ الكوبون: ' . $e->getMessage();
            }
        }
    }
}

// جلب بيانات الكوبون مرة أخرى بعد التعديل
if ($coupon_id && !$coupon) {
    $stmt = $pdo->prepare("SELECT * FROM coupons WHERE id = ?");
    $stmt->execute([$coupon_id]);
    $coupon = $stmt->fetch();
}

$success = $_GET['success'] ?? $success;
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $coupon ? 'تعديل الكوبون' : 'إضافة كوبون جديد' ?> - لوحة التحكم</title>
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
            max-width: 600px;
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
            min-height: 80px;
        }
        
        .form-help {
            font-size: 12px;
            color: #64748b;
            margin-top: 5px;
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
            max-width: 600px;
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
        
        .discount-preview {
            background: #f8fafc;
            padding: 15px;
            border-radius: 8px;
            margin-top: 10px;
        }
        .discount-example {
            font-weight: 600;
            color: #16a34a;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-store"></i> لوحة التحكم</h2>
            </div>
            <nav class="sidebar-menu">
                <a href="index.php" class="menu-item">
                    <i class="fas fa-home"></i>
                    <span>الرئيسية</span>
                </a>
                <a href="products.php" class="menu-item">
                    <i class="fas fa-box"></i>
                    <span>المنتجات</span>
                </a>
                <a href="categories.php" class="menu-item">
                    <i class="fas fa-th-large"></i>
                    <span>الفئات</span>
                </a>
                <a href="orders.php" class="menu-item">
                    <i class="fas fa-shopping-cart"></i>
                    <span>الطلبات</span>
                </a>
                <a href="customers.php" class="menu-item">
                    <i class="fas fa-users"></i>
                    <span>العملاء</span>
                </a>
                <a href="coupons.php" class="menu-item active">
                    <i class="fas fa-ticket-alt"></i>
                    <span>كوبونات الخصم</span>
                </a>
                <a href="reviews.php" class="menu-item">
                    <i class="fas fa-star"></i>
                    <span>التقييمات</span>
                </a>
                <a href="reports.php" class="menu-item">
                    <i class="fas fa-chart-bar"></i>
                    <span>التقارير</span>
                </a>
                <a href="settings.php" class="menu-item">
                    <i class="fas fa-cog"></i>
                    <span>الإعدادات</span>
                </a>
                <a href="../index.php" class="menu-item" target="_blank">
                    <i class="fas fa-external-link-alt"></i>
                    <span>عرض المتجر</span>
                </a>
                <a href="logout.php" class="menu-item">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>تسجيل الخروج</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="top-bar">
                <div class="page-title">
                    <h1><?= $coupon ? 'تعديل الكوبون' : 'إضافة كوبون جديد' ?></h1>
                    <p style="color: #64748b; margin-top: 5px;"><?= $coupon ? 'تعديل بيانات الكوبون' : 'إنشاء كوبون خصم جديد' ?></p>
                </div>
                <div>
                    <a href="coupons.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-right"></i>
                        رجوع
                    </a>
                    <button type="submit" form="coupon-form" class="btn btn-success">
                        <i class="fas fa-save"></i>
                        حفظ الكوبون
                    </button>
                </div>
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

            <form id="coupon-form" method="post" action="coupon-form.php<?= $coupon_id ? '?id=' . $coupon_id : '' ?>">
                <div class="card">
                    <h2 style="margin-bottom: 20px;">معلومات الكوبون</h2>
                    
                    <div class="form-group">
                        <label for="code">كود الكوبون *</label>
                        <input type="text" id="code" name="code" 
                               value="<?= htmlspecialchars($coupon['code'] ?? ($_POST['code'] ?? '')) ?>" 
                               style="font-family: 'Courier New', monospace; font-weight: bold;" required>
                        <div class="form-help">الكود الذي سيدخله العملاء للحصول على الخصم</div>
                    </div>

                    <div class="form-group">
                        <label for="description">الوصف</label>
                        <textarea id="description" name="description"><?= htmlspecialchars($coupon['description'] ?? ($_POST['description'] ?? '')) ?></textarea>
                        <div class="form-help">وصف الكوبون يظهر للعملاء</div>
                    </div>

                    <div class="form-group">
                        <label for="discount_type">نوع الخصم *</label>
                        <select id="discount_type" name="discount_type" required>
                            <option value="percentage" <?= ($coupon['discount_type'] ?? ($_POST['discount_type'] ?? '')) == 'percentage' ? 'selected' : '' ?>>نسبة مئوية</option>
                            <option value="fixed" <?= ($coupon['discount_type'] ?? ($_POST['discount_type'] ?? '')) == 'fixed' ? 'selected' : '' ?>>مبلغ ثابت</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="discount_value">قيمة الخصم *</label>
                        <input type="number" id="discount_value" name="discount_value" 
                               value="<?= htmlspecialchars($coupon['discount_value'] ?? ($_POST['discount_value'] ?? '')) ?>" 
                               step="0.01" min="0.01" required>
                        <div class="form-help" id="discount_help">
                            <?php if (($coupon['discount_type'] ?? ($_POST['discount_type'] ?? 'percentage')) == 'percentage'): ?>
                                النسبة المئوية للخصم (مثال: 10 لخصم 10%)
                            <?php else: ?>
                                المبلغ الثابت للخصم (مثال: 50 لخصم 50 ج.م)
                            <?php endif; ?>
                        </div>
                        
                        <div class="discount-preview">
                            <strong>مثال تطبيقي:</strong>
                            <div class="discount-example" id="discount_example">
                                على طلب بقيمة 500 ج.م، الخصم سيكون: 0 ج.م
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="min_order_amount">الحد الأدنى لقيمة الطلب</label>
                        <input type="number" id="min_order_amount" name="min_order_amount" 
                               value="<?= htmlspecialchars($coupon['min_order_amount'] ?? ($_POST['min_order_amount'] ?? 0)) ?>" 
                               step="0.01" min="0">
                        <div class="form-help">الحد الأدنى لقيمة الطلب لتطبيق الكوبون (0 يعني لا يوجد حد)</div>
                    </div>

                    <div class="form-group">
                        <label for="max_discount_amount">الحد الأقصى للخصم</label>
                        <input type="number" id="max_discount_amount" name="max_discount_amount" 
                               value="<?= htmlspecialchars($coupon['max_discount_amount'] ?? ($_POST['max_discount_amount'] ?? '')) ?>" 
                               step="0.01" min="0">
                        <div class="form-help">الحد الأقصى لمبلغ الخصم (للكوبونات النسبية فقط)</div>
                    </div>

                    <div class="form-group">
                        <label for="usage_limit">الحد الأقصى لعدد مرات الاستخدام</label>
                        <input type="number" id="usage_limit" name="usage_limit" 
                               value="<?= htmlspecialchars($coupon['usage_limit'] ?? ($_POST['usage_limit'] ?? '')) ?>" 
                               min="1">
                        <div class="form-help">العدد الأقصى لمرات استخدام الكوبون (اتركه فارغاً لاستخدام غير محدود)</div>
                    </div>

                    <div class="form-group">
                        <label for="valid_from">فعال من</label>
                        <input type="datetime-local" id="valid_from" name="valid_from" 
                               value="<?= $coupon['valid_from'] ? date('Y-m-d\TH:i', strtotime($coupon['valid_from'])) : ($_POST['valid_from'] ?? '') ?>">
                        <div class="form-help">تاريخ ووقت بداية فعالية الكوبون</div>
                    </div>

                    <div class="form-group">
                        <label for="valid_until">فعال حتى</label>
                        <input type="datetime-local" id="valid_until" name="valid_until" 
                               value="<?= $coupon['valid_until'] ? date('Y-m-d\TH:i', strtotime($coupon['valid_until'])) : ($_POST['valid_until'] ?? '') ?>">
                        <div class="form-help">تاريخ ووقت نهاية فعالية الكوبون</div>
                    </div>

                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" id="is_active" name="is_active" 
                                   value="1" <?= ($coupon['is_active'] ?? ($_POST['is_active'] ?? 1)) ? 'checked' : '' ?>>
                            <label for="is_active">كوبون فعال</label>
                        </div>
                        <div class="form-help">تفعيل الكوبون للاستخدام</div>
                    </div>
                </div>
            </form>
        </main>
    </div>

    <script>
        // تحديث مثال الخصم
        function updateDiscountExample() {
            const discountType = document.getElementById('discount_type').value;
            const discountValue = parseFloat(document.getElementById('discount_value').value) || 0;
            const orderAmount = 500; // مثال بقيمة 500 ج.م
            
            let discountAmount = 0;
            let exampleText = '';
            
            if (discountType === 'percentage') {
                discountAmount = orderAmount * (discountValue / 100);
                exampleText = `على طلب بقيمة ${orderAmount} ج.م، الخصم سيكون: ${discountAmount.toFixed(2)} ج.م (${discountValue}%)`;
            } else {
                discountAmount = Math.min(discountValue, orderAmount);
                exampleText = `على طلب بقيمة ${orderAmount} ج.م، الخصم سيكون: ${discountAmount.toFixed(2)} ج.م`;
            }
            
            document.getElementById('discount_example').textContent = exampleText;
        }

        // تحديث نص المساعدة بناءً على نوع الخصم
        function updateDiscountHelp() {
            const discountType = document.getElementById('discount_type').value;
            const helpText = discountType === 'percentage' 
                ? 'النسبة المئوية للخصم (مثال: 10 لخصم 10%)'
                : 'المبلغ الثابت للخصم (مثال: 50 لخصم 50 ج.م)';
            
            document.getElementById('discount_help').textContent = helpText;
            updateDiscountExample();
        }

        // إضافة المستمعين للأحداث
        document.getElementById('discount_type').addEventListener('change', updateDiscountHelp);
        document.getElementById('discount_value').addEventListener('input', updateDiscountExample);

        // التهيئة الأولية
        updateDiscountHelp();

        // التحقق من صحة النموذج
        document.getElementById('coupon-form').addEventListener('submit', function(e) {
            let isValid = true;
            
            const requiredFields = this.querySelectorAll('[required]');
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.style.borderColor = '#dc2626';
                } else {
                    field.style.borderColor = '#e2e8f0';
                }
            });
            
            const discountValue = parseFloat(document.getElementById('discount_value').value);
            const discountType = document.getElementById('discount_type').value;
            
            if (discountType === 'percentage' && (discountValue <= 0 || discountValue > 100)) {
                isValid = false;
                document.getElementById('discount_value').style.borderColor = '#dc2626';
                alert('نسبة الخصم يجب أن تكون بين 1 و 100');
            } else if (discountType === 'fixed' && discountValue <= 0) {
                isValid = false;
                document.getElementById('discount_value').style.borderColor = '#dc2626';
                alert('قيمة الخصم يجب أن تكون أكبر من صفر');
            }
            
            if (!isValid) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>