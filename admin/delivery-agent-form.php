<?php
/**
 * صفحة إضافة/تعديل مندوب التوصيل
 */
session_start();
require_once '../config.php';
require_once '../functions.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$agent_id = $_GET['id'] ?? 0;
$agent = null;
$error = '';
$success = '';

// جلب بيانات المندوب إذا كان تعديل
if ($agent_id) {
    $stmt = $pdo->prepare("SELECT * FROM delivery_agents WHERE id = ?");
    $stmt->execute([$agent_id]);
    $agent = $stmt->fetch();
    
    if (!$agent) {
        $error = 'المندوب غير موجود';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = cleanInput($_POST['name'] ?? '');
    $phone = cleanInput($_POST['phone'] ?? '');
    $email = cleanInput($_POST['email'] ?? '');
    $vehicle_type = $_POST['vehicle_type'] ?? 'motorcycle';
    $vehicle_number = cleanInput($_POST['vehicle_number'] ?? '');
    $salary_type = $_POST['salary_type'] ?? 'fixed';
    $fixed_salary = floatval($_POST['fixed_salary'] ?? 0);
    $commission_rate = floatval($_POST['commission_rate'] ?? 0);
    $area = cleanInput($_POST['area'] ?? '');
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // التحقق من البيانات
    if (empty($name) || empty($phone)) {
        $error = 'يرجى ملء الحقول المطلوبة (الاسم والهاتف)';
    } else {
        try {
            if ($agent_id && $agent) {
                // تحديث المندوب
                $stmt = $pdo->prepare("
                    UPDATE delivery_agents SET 
                        name = ?, phone = ?, email = ?, vehicle_type = ?, vehicle_number = ?,
                        salary_type = ?, fixed_salary = ?, commission_rate = ?, area = ?, is_active = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $name, $phone, $email, $vehicle_type, $vehicle_number,
                    $salary_type, $fixed_salary, $commission_rate, $area, $is_active, $agent_id
                ]);
                
                $action = 'updated';
                $message = 'تم تحديث بيانات المندوب بنجاح';
            } else {
                // إضافة مندوب جديد
                $stmt = $pdo->prepare("
                    INSERT INTO delivery_agents (
                        name, phone, email, vehicle_type, vehicle_number,
                        salary_type, fixed_salary, commission_rate, area, is_active
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $name, $phone, $email, $vehicle_type, $vehicle_number,
                    $salary_type, $fixed_salary, $commission_rate, $area, $is_active
                ]);
                
                $agent_id = $pdo->lastInsertId();
                $action = 'created';
                $message = 'تم إضافة المندوب بنجاح';
            }
            
            logActivity("delivery_agent_{$action}", $message, $_SESSION['admin_id']);
            $success = $message;
            
            // إعادة توجيه بعد النجاح
            header('Location: delivery-agent-form.php?id=' . $agent_id . '&success=' . urlencode($message));
            exit;
            
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                $error = 'رقم الهاتف مستخدم مسبقاً';
            } else {
                $error = 'حدث خطأ أثناء حفظ البيانات: ' . $e->getMessage();
            }
        }
    }
}

// جلب بيانات المندوب مرة أخرى بعد التعديل
if ($agent_id && !$agent) {
    $stmt = $pdo->prepare("SELECT * FROM delivery_agents WHERE id = ?");
    $stmt->execute([$agent_id]);
    $agent = $stmt->fetch();
}

$success = $_GET['success'] ?? $success;
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $agent ? 'تعديل مندوب التوصيل' : 'إضافة مندوب جديد' ?> - لوحة التحكم</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* نفس أنماط category-form.php مع تعديلات */
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
        
        .salary-fields {
            background: #f8fafc;
            padding: 15px;
            border-radius: 8px;
            margin-top: 10px;
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
                <a href="delivery-agents.php" class="menu-item active">
                    <i class="fas fa-motorcycle"></i>
                    <span>مندوبي التوصيل</span>
                </a>
                <a href="coupons.php" class="menu-item">
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
                    <h1><?= $agent ? 'تعديل مندوب التوصيل' : 'إضافة مندوب جديد' ?></h1>
                    <p style="color: #64748b; margin-top: 5px;"><?= $agent ? 'تعديل بيانات المندوب' : 'إضافة مندوب توصيل جديد' ?></p>
                </div>
                <div>
                    <a href="delivery-agents.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-right"></i>
                        رجوع
                    </a>
                    <button type="submit" form="agent-form" class="btn btn-success">
                        <i class="fas fa-save"></i>
                        حفظ المندوب
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

            <!-- Agent Form -->
            <div class="card">
                <form id="agent-form" method="POST">
                    <div class="form-group">
                        <label for="name">اسم المندوب *</label>
                        <input type="text" id="name" name="name" value="<?= htmlspecialchars($agent['name'] ?? '') ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="phone">رقم الهاتف *</label>
                        <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($agent['phone'] ?? '') ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="email">البريد الإلكتروني</label>
                        <input type="email" id="email" name="email" value="<?= htmlspecialchars($agent['email'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="vehicle_type">نوع المركبة</label>
                        <select id="vehicle_type" name="vehicle_type">
                            <option value="motorcycle" <?= ($agent['vehicle_type'] ?? 'motorcycle') == 'motorcycle' ? 'selected' : '' ?>>موتوسيكل</option>
                            <option value="car" <?= ($agent['vehicle_type'] ?? '') == 'car' ? 'selected' : '' ?>>سيارة</option>
                            <option value="bicycle" <?= ($agent['vehicle_type'] ?? '') == 'bicycle' ? 'selected' : '' ?>>دراجة</option>
                            <option value="truck" <?= ($agent['vehicle_type'] ?? '') == 'truck' ? 'selected' : '' ?>>تريلك</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="vehicle_number">رقم المركبة</label>
                        <input type="text" id="vehicle_number" name="vehicle_number" value="<?= htmlspecialchars($agent['vehicle_number'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="salary_type">نظام الراتب</label>
                        <select id="salary_type" name="salary_type" onchange="toggleSalaryFields()">
                            <option value="fixed" <?= ($agent['salary_type'] ?? 'fixed') == 'fixed' ? 'selected' : '' ?>>راتب ثابت</option>
                            <option value="commission" <?= ($agent['salary_type'] ?? '') == 'commission' ? 'selected' : '' ?>>عمولة</option>
                            <option value="mixed" <?= ($agent['salary_type'] ?? '') == 'mixed' ? 'selected' : '' ?>>مختلط (ثابت + عمولة)</option>
                        </select>
                    </div>

                    <div id="salary_fields">
                        <div class="form-group" id="fixed_salary_field" style="display: <?= in_array($agent['salary_type'] ?? 'fixed', ['fixed', 'mixed']) ? 'block' : 'none' ?>">
                            <label for="fixed_salary">الراتب الثابت</label>
                            <input type="number" id="fixed_salary" name="fixed_salary" step="0.01" min="0" value="<?= $agent['fixed_salary'] ?? 0 ?>">
                        </div>

                        <div class="form-group" id="commission_rate_field" style="display: <?= in_array($agent['salary_type'] ?? 'fixed', ['commission', 'mixed']) ? 'block' : 'none' ?>">
                            <label for="commission_rate">نسبة العمولة (%)</label>
                            <input type="number" id="commission_rate" name="commission_rate" step="0.01" min="0" max="100" value="<?= $agent['commission_rate'] ?? 0 ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="area">المنطقة</label>
                        <input type="text" id="area" name="area" value="<?= htmlspecialchars($agent['area'] ?? '') ?>">
                        <div class="form-help">المنطقة الجغرافية التي يغطيها المندوب</div>
                    </div>

                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" id="is_active" name="is_active" value="1" <?= ($agent['is_active'] ?? 1) ? 'checked' : '' ?>>
                            <label for="is_active">مندوب نشط</label>
                        </div>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
        function toggleSalaryFields() {
            const salaryType = document.getElementById('salary_type').value;
            const fixedSalaryField = document.getElementById('fixed_salary_field');
            const commissionField = document.getElementById('commission_rate_field');
            
            if (salaryType === 'fixed') {
                fixedSalaryField.style.display = 'block';
                commissionField.style.display = 'none';
            } else if (salaryType === 'commission') {
                fixedSalaryField.style.display = 'none';
                commissionField.style.display = 'block';
            } else if (salaryType === 'mixed') {
                fixedSalaryField.style.display = 'block';
                commissionField.style.display = 'block';
            }
        }
    </script>
</body>
</html>