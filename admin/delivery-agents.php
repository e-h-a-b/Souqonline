<?php
/**
 * صفحة إدارة مندوبي التوصيل
 */
session_start();
require_once '../config.php';
require_once '../functions.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// معالجة حذف المندوب
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $agent_id = $_GET['delete'];
    
    try {
        // التحقق من وجود طلبات مرتبطة
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM agent_orders WHERE agent_id = ?");
        $stmt->execute([$agent_id]);
        $orders_count = $stmt->fetchColumn();
        
        if ($orders_count > 0) {
            header('Location: delivery-agents.php?error=' . urlencode('لا يمكن حذف المندوب لأنه مرتبط بطلبات سابقة'));
            exit;
        }
        
        $stmt = $pdo->prepare("DELETE FROM delivery_agents WHERE id = ?");
        $stmt->execute([$agent_id]);
        
        logActivity('delivery_agent_deleted', "تم حذف المندوب #$agent_id", $_SESSION['admin_id']);
        header('Location: delivery-agents.php?success=' . urlencode('تم حذف المندوب بنجاح'));
        exit;
        
    } catch (Exception $e) {
        header('Location: delivery-agents.php?error=' . urlencode('حدث خطأ أثناء الحذف'));
        exit;
    }
}

// جلب المندوبين
$stmt = $pdo->query("
    SELECT da.*, 
           (SELECT COUNT(*) FROM agent_orders WHERE agent_id = da.id) as total_orders,
           (SELECT COUNT(*) FROM agent_orders WHERE agent_id = da.id AND status = 'delivered') as delivered_orders
    FROM delivery_agents da 
    ORDER BY da.created_at DESC
");
$agents = $stmt->fetchAll();

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

// إضافة بيانات تجريبية إذا لم يكن هناك مندوبين (للتجربة فقط)
if (empty($agents)) {
    try {
        $sample_agents = [
            [
                'name' => 'أحمد محمد',
                'phone' => '01116030798',
                'email' => 'ahmed@example.com',
                'vehicle_type' => 'motorcycle',
                'vehicle_number' => 'م أ 1234',
                'salary_type' => 'mixed',
                'fixed_salary' => 2000,
                'commission_rate' => 5,
                'area' => 'القاهرة',
                'is_active' => 1
            ],
            [
                'name' => 'محمود علي',
                'phone' => '01116030799',
                'email' => 'mahmoud@example.com',
                'vehicle_type' => 'car',
                'vehicle_number' => 'م ب 5678',
                'salary_type' => 'commission',
                'fixed_salary' => 0,
                'commission_rate' => 7,
                'area' => 'الجيزة',
                'is_active' => 1
            ]
        ];
        
        foreach ($sample_agents as $agent_data) {
            $stmt = $pdo->prepare("
                INSERT INTO delivery_agents (name, phone, email, vehicle_type, vehicle_number, salary_type, fixed_salary, commission_rate, area, is_active) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $agent_data['name'],
                $agent_data['phone'],
                $agent_data['email'],
                $agent_data['vehicle_type'],
                $agent_data['vehicle_number'],
                $agent_data['salary_type'],
                $agent_data['fixed_salary'],
                $agent_data['commission_rate'],
                $agent_data['area'],
                $agent_data['is_active']
            ]);
        }
        
        // إعادة تحميل الصفحة لعرض البيانات الجديدة
        header('Location: delivery-agents.php');
        exit;
        
    } catch (Exception $e) {
        // تجاهل الخطأ إذا كانت البيانات موجودة مسبقاً
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة مندوبي التوصيل - لوحة التحكم</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* نفس أنماط الصفحات الأخرى مع تعديلات */
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
        .btn-primary { background: #2563eb; color: #fff; }
        .btn-primary:hover { background: #1d4ed8; }
        .btn-danger { background: #dc2626; color: #fff; }
        .btn-danger:hover { background: #b91c1c; }
        .btn-secondary { background: #64748b; color: #fff; }
        .btn-secondary:hover { background: #475569; }
        .btn-warning { background: #d97706; color: #fff; }
        .btn-warning:hover { background: #b45309; }
        
        .card {
            background: #fff;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            overflow: hidden;
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
        .badge-info { background: #dbeafe; color: #1e40af; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        
        .actions {
            display: flex;
            gap: 8px;
        }
        .action-btn {
            padding: 6px 10px;
            border-radius: 6px;
            font-size: 12px;
            text-decoration: none;
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
        
        .agent-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: #fff;
            font-size: 16px;
        }
        
        .agent-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .agent-details {
            display: flex;
            flex-direction: column;
        }
        .agent-name {
            font-weight: 600;
        }
        .agent-contact {
            font-size: 12px;
            color: #64748b;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .stat-item {
            background: #f8fafc;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }
        .stat-number {
            font-size: 24px;
            font-weight: 700;
            color: #1e293b;
        }
        .stat-label {
            font-size: 12px;
            color: #64748b;
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
                    <h1>إدارة مندوبي التوصيل</h1>
                    <p style="color: #64748b; margin-top: 5px;">إدارة مندوبي التوصيل وتتبع أدائهم</p>
                </div>
                <a href="delivery-agent-form.php" class="btn btn-success">
                    <i class="fas fa-plus"></i>
                    إضافة مندوب جديد
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

            <!-- Quick Stats -->
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-number"><?= count($agents) ?></div>
                    <div class="stat-label">إجمالي المندوبين</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">
                        <?= array_sum(array_column($agents, 'total_orders')) ?>
                    </div>
                    <div class="stat-label">إجمالي الطلبات</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">
                        <?= array_sum(array_column($agents, 'delivered_orders')) ?>
                    </div>
                    <div class="stat-label">طلبات مكتملة</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">
                        <?= count(array_filter($agents, function($agent) { return $agent['is_active']; })) ?>
                    </div>
                    <div class="stat-label">مندوبين نشطين</div>
                </div>
            </div>

            <!-- Agents Table -->
            <div class="card">
                <div class="card-header">
                    <h2>مندوبي التوصيل (<?= count($agents) ?>)</h2>
                </div>

                <?php if (empty($agents)): ?>
                    <p style="text-align: center; color: #64748b; padding: 40px;">لا توجد مندوبين توصيل</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>المندوب</th>
                                <th>معلومات الاتصال</th>
                                <th>المركبة</th>
                                <th>نظام الراتب</th>
                                <th>الطلبات</th>
                                <th>المعدل</th>
                                <th>الحالة</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($agents as $agent): ?>
                                <tr>
                                    <td>
                                        <div class="agent-info">
                                            <div class="agent-avatar">
                                                <?= mb_substr($agent['name'], 0, 1) ?>
                                            </div>
                                            <div class="agent-details">
                                                <span class="agent-name"><?= htmlspecialchars($agent['name']) ?></span>
                                                <span class="agent-contact">
                                                    <?= date('Y-m-d', strtotime($agent['created_at'])) ?>
                                                </span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="agent-details">
                                            <span class="agent-contact"><?= htmlspecialchars($agent['phone']) ?></span>
                                            <?php if ($agent['email']): ?>
                                                <span class="agent-contact"><?= htmlspecialchars($agent['email']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php
                                        $vehicle_types = [
                                            'motorcycle' => 'موتوسيكل',
                                            'car' => 'سيارة',
                                            'bicycle' => 'دراجة',
                                            'truck' => 'تريلك'
                                        ];
                                        echo $vehicle_types[$agent['vehicle_type']] ?? $agent['vehicle_type'];
                                        ?>
                                        <?php if ($agent['vehicle_number']): ?>
                                            <br><small style="color: #64748b;"><?= htmlspecialchars($agent['vehicle_number']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $salary_types = [
                                            'fixed' => 'ثابت',
                                            'commission' => 'عمولة',
                                            'mixed' => 'مختلط'
                                        ];
                                        echo $salary_types[$agent['salary_type']] ?? $agent['salary_type'];
                                        ?>
                                        <?php if ($agent['salary_type'] == 'fixed' && $agent['fixed_salary'] > 0): ?>
                                            <br><small style="color: #64748b;"><?= formatPrice($agent['fixed_salary']) ?></small>
                                        <?php elseif ($agent['salary_type'] == 'commission' && $agent['commission_rate'] > 0): ?>
                                            <br><small style="color: #64748b;"><?= $agent['commission_rate'] ?>%</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="agent-details">
                                            <span class="agent-contact">إجمالي: <?= $agent['total_orders'] ?></span>
                                            <span class="agent-contact">مكتمل: <?= $agent['delivered_orders'] ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($agent['total_orders'] > 0): ?>
                                            <?php $rate = ($agent['delivered_orders'] / $agent['total_orders']) * 100; ?>
                                            <span class="badge <?= $rate >= 80 ? 'badge-success' : ($rate >= 60 ? 'badge-warning' : 'badge-danger') ?>">
                                                <?= number_format($rate, 1) ?>%
                                            </span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary">0%</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($agent['is_active']): ?>
                                            <span class="badge badge-success">نشط</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">غير نشط</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="actions">
                                            <a href="delivery-agent-form.php?id=<?= $agent['id'] ?>" class="action-btn btn-primary" title="تعديل">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="agent-orders.php?agent_id=<?= $agent['id'] ?>" class="action-btn btn-info" title="طلبات المندوب">
                                                <i class="fas fa-list"></i>
                                            </a>
                                            <a href="agent-salary.php?agent_id=<?= $agent['id'] ?>" class="action-btn btn-warning" title="الرواتب">
                                                <i class="fas fa-money-bill"></i>
                                            </a>
                                            <a href="delivery-agents.php?delete=<?= $agent['id'] ?>" class="action-btn btn-danger" title="حذف" onclick="return confirm('هل أنت متأكد من حذف هذا المندوب؟')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>