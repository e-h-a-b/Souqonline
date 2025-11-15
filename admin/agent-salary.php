<?php
/**
 * صفحة رواتب المندوب
 */
session_start();
require_once '../config.php';
require_once '../functions.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$agent_id = $_GET['agent_id'] ?? 0;
if (!$agent_id) {
    header('Location: delivery-agents.php');
    exit;
}

// جلب بيانات المندوب
$stmt = $pdo->prepare("SELECT * FROM delivery_agents WHERE id = ?");
$stmt->execute([$agent_id]);
$agent = $stmt->fetch();

if (!$agent) {
    header('Location: delivery-agents.php?error=' . urlencode('المندوب غير موجود'));
    exit;
}

// جلب سجل الرواتب
$stmt = $pdo->prepare("
    SELECT * FROM agent_salaries 
    WHERE agent_id = ? 
    ORDER BY month DESC
");
$stmt->execute([$agent_id]);
$salaries = $stmt->fetchAll();

// معالجة دفع الراتب
if (isset($_POST['pay_salary'])) {
    $salary_id = $_POST['salary_id'];
    $payment_method = cleanInput($_POST['payment_method']);
    $notes = cleanInput($_POST['notes']);
    
    try {
        $stmt = $pdo->prepare("
            UPDATE agent_salaries 
            SET status = 'paid', paid_at = NOW(), payment_method = ?, notes = ?
            WHERE id = ? AND agent_id = ?
        ");
        $stmt->execute([$payment_method, $notes, $salary_id, $agent_id]);
        
        logActivity('agent_salary_paid', "تم دفع راتب المندوب #$agent_id", $_SESSION['admin_id']);
        header('Location: agent-salary.php?agent_id=' . $agent_id . '&success=' . urlencode('تم تسجيل دفع الراتب بنجاح'));
        exit;
        
    } catch (Exception $e) {
        header('Location: agent-salary.php?agent_id=' . $agent_id . '&error=' . urlencode('حدث خطأ أثناء تسجيل الدفع'));
        exit;
    }
}

// معالجة إنشاء راتب جديد
if (isset($_POST['create_salary'])) {
    $month = $_POST['month'];
    $notes = cleanInput($_POST['notes']);
    
    try {
        // حساب الراتب بناءً على الطلبات المكتملة
        $start_date = date('Y-m-01', strtotime($month));
        $end_date = date('Y-m-t', strtotime($month));
        
        // جلب الطلبات المكتملة لهذا الشهر
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total_orders, SUM(commission_amount) as total_commission
            FROM agent_orders 
            WHERE agent_id = ? 
            AND status = 'delivered'
            AND DATE(delivered_at) BETWEEN ? AND ?
        ");
        $stmt->execute([$agent_id, $start_date, $end_date]);
        $orders_data = $stmt->fetch();
        
        $total_orders = $orders_data['total_orders'] ?? 0;
        $commission_amount = $orders_data['total_commission'] ?? 0;
        $fixed_salary = $agent['fixed_salary'];
        $total_salary = $fixed_salary + $commission_amount;
 
        
        // التحقق من عدم وجود راتب لهذا الشهر
        $stmt = $pdo->prepare("SELECT id FROM agent_salaries WHERE agent_id = ? AND month = ?");
        $stmt->execute([$agent_id, $month]);
        
        if ($stmt->fetch()) {
            header('Location: agent-salary.php?agent_id=' . $agent_id . '&error=' . urlencode('تم إنشاء راتب لهذا الشهر مسبقاً'));
            exit;
        }
        
        // إنشاء راتب جديد
        $stmt = $pdo->prepare("
            INSERT INTO agent_salaries (
                agent_id, month, total_orders, fixed_salary, commission_amount, total_salary, notes
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $agent_id, $month, $total_orders, $fixed_salary, $commission_amount, $total_salary, $notes
        ]);
        
        logActivity('agent_salary_created', "تم إنشاء راتب للمندوب #$agent_id", $_SESSION['admin_id']);
        header('Location: agent-salary.php?agent_id=' . $agent_id . '&success=' . urlencode('تم إنشاء الراتب بنجاح'));
        exit;
        
    } catch (Exception $e) {
        header('Location: agent-salary.php?agent_id=' . $agent_id . '&error=' . urlencode('حدث خطأ أثناء إنشاء الراتب'));
        exit;
    }
}

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>رواتب المندوب - لوحة التحكم</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* نفس أنماط الصفحات الأخرى */
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
        .btn-primary { background: #2563eb; color: #fff; }
        .btn-primary:hover { background: #1d4ed8; }
        
        .card {
            background: #fff;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
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
        .badge-danger { background: #fee2e2; color: #991b1b; }
        .badge-secondary { background: #f1f5f9; color: #475569; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        
        .agent-info {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
        }
        .agent-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: #fff;
            font-size: 18px;
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
        
        .salary-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .summary-item {
            background: #f8fafc;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }
        .summary-number {
            font-size: 20px;
            font-weight: 700;
            color: #1e293b;
        }
        .summary-label {
            font-size: 12px;
            color: #64748b;
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
                    <h1>رواتب المندوب</h1>
                    <p style="color: #64748b; margin-top: 5px;">إدارة وتتبع رواتب مندوب التوصيل</p>
                </div>
                <a href="delivery-agents.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-right"></i>
                    رجوع
                </a>
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

            <!-- Agent Info -->
            <div class="card">
                <div class="agent-info">
                    <div class="agent-avatar">
                        <?= mb_substr($agent['name'], 0, 1) ?>
                    </div>
                    <div>
                        <h2><?= htmlspecialchars($agent['name']) ?></h2>
                        <p style="color: #64748b; margin-top: 5px;">
                            نظام الراتب: 
                            <?php
                            $salary_types = [
                                'fixed' => 'ثابت',
                                'commission' => 'عمولة',
                                'mixed' => 'مختلط'
                            ];
                            echo $salary_types[$agent['salary_type']] ?? $agent['salary_type'];
                            ?>
                            <?php if ($agent['salary_type'] == 'fixed' && $agent['fixed_salary'] > 0): ?>
                                - <?= formatPrice($agent['fixed_salary']) ?>
                            <?php elseif ($agent['salary_type'] == 'commission' && $agent['commission_rate'] > 0): ?>
                                - <?= $agent['commission_rate'] ?>%
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Create New Salary -->
            <div class="card">
                <h2 style="margin-bottom: 20px;">إنشاء راتب جديد</h2>
                <form method="POST">
                    <div class="form-group">
                        <label for="month">الشهر</label>
                        <input type="month" id="month" name="month" value="<?= date('Y-m') ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="notes">ملاحظات</label>
                        <textarea id="notes" name="notes" placeholder="ملاحظات حول الراتب..."></textarea>
                    </div>
                    <button type="submit" name="create_salary" class="btn btn-primary">
                        <i class="fas fa-plus"></i>
                        إنشاء الراتب
                    </button>
                </form>
            </div>

            <!-- Salary History -->
            <div class="card">
                <div class="card-header">
                    <h2>سجل الرواتب (<?= count($salaries) ?>)</h2>
                </div>

                <?php if (empty($salaries)): ?>
                    <p style="text-align: center; color: #64748b; padding: 40px;">لا توجد رواتب مسجلة</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>الشهر</th>
                                <th>الطلبات</th>
                                <th>الراتب الثابت</th>
                                <th>العمولة</th>
                                <th>الإجمالي</th>
                                <th>الحالة</th>
                                <th>تاريخ الدفع</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($salaries as $salary): ?>
                                <tr>
                                    <td>
                                        <strong><?= date('Y-m', strtotime($salary['month'])) ?></strong>
                                    </td>
                                    <td><?= $salary['total_orders'] ?></td>
                                    <td><?= formatPrice($salary['fixed_salary']) ?></td>
                                    <td><?= formatPrice($salary['commission_amount']) ?></td>
                                    <td>
                                        <strong><?= formatPrice($salary['total_salary']) ?></strong>
                                    </td>
                                    <td>
                                        <?php if ($salary['status'] == 'paid'): ?>
                                            <span class="badge badge-success">مدفوع</span>
                                        <?php else: ?>
                                            <span class="badge badge-warning">معلق</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($salary['paid_at']): ?>
                                            <?= date('Y-m-d H:i', strtotime($salary['paid_at'])) ?>
                                        <?php else: ?>
                                            <span style="color: #64748b;">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($salary['status'] == 'pending'): ?>
                                            <button type="button" class="btn btn-success" onclick="openPaymentModal(<?= $salary['id'] ?>)">
                                                <i class="fas fa-money-bill"></i>
                                                تسديد
                                            </button>
                                        <?php else: ?>
                                            <span style="color: #64748b;">تم التسديد</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Payment Modal -->
    <div id="paymentModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
        <div style="background: #fff; padding: 30px; border-radius: 12px; width: 400px; max-width: 90%;">
            <h2 style="margin-bottom: 20px;">تسديد الراتب</h2>
            <form id="paymentForm" method="POST">
                <input type="hidden" name="salary_id" id="modal_salary_id">
                <div class="form-group">
                    <label for="payment_method">طريقة الدفع</label>
                    <select id="payment_method" name="payment_method" required>
                        <option value="cash">نقداً</option>
                        <option value="bank_transfer">تحويل بنكي</option>
                        <option value="mobile_wallet">محفظة إلكترونية</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="payment_notes">ملاحظات</label>
                    <textarea id="payment_notes" name="notes" placeholder="ملاحظات حول عملية الدفع..."></textarea>
                </div>
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" class="btn btn-secondary" onclick="closePaymentModal()">إلغاء</button>
                    <button type="submit" name="pay_salary" class="btn btn-success">تأكيد الدفع</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openPaymentModal(salaryId) {
            document.getElementById('modal_salary_id').value = salaryId;
            document.getElementById('paymentModal').style.display = 'flex';
        }
        
        function closePaymentModal() {
            document.getElementById('paymentModal').style.display = 'none';
        }
        
        // إغلاق النافذة عند النقر خارجها
        document.getElementById('paymentModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closePaymentModal();
            }
        });
    </script>
</body>
</html>