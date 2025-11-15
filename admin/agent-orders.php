<?php
/**
 * صفحة طلبات المندوب
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

// جلب طلبات المندوب
$stmt = $pdo->prepare("
    SELECT ao.*, o.order_number, o.customer_name, o.total, o.shipping_address, o.created_at as order_date
    FROM agent_orders ao
    JOIN orders o ON ao.order_id = o.id
    WHERE ao.agent_id = ?
    ORDER BY ao.assigned_at DESC
");
$stmt->execute([$agent_id]);
$orders = $stmt->fetchAll();

// إحصائيات الطلبات
$stats = [
    'total' => count($orders),
    'assigned' => 0,
    'picked_up' => 0,
    'on_way' => 0,
    'delivered' => 0,
    'cancelled' => 0
];

foreach ($orders as $order) {
    if (isset($order['status']) && isset($stats[$order['status']])) {
        $stats[$order['status']]++;
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
    <title>طلبات المندوب - لوحة التحكم</title>
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
        .btn-secondary { background: #64748b; color: #fff; }
        .btn-secondary:hover { background: #475569; }
        
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
        .badge-info { background: #dbeafe; color: #1e40af; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-primary { background: #e0e7ff; color: #3730a3; }
        
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
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
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
                    <h1>طلبات المندوب</h1>
                    <p style="color: #64748b; margin-top: 5px;">عرض وتتبع طلبات مندوب التوصيل</p>
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
                            <?= htmlspecialchars($agent['phone']) ?>
                            <?php if ($agent['email']): ?>
                                • <?= htmlspecialchars($agent['email']) ?>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-number"><?= $stats['total'] ?></div>
                    <div class="stat-label">إجمالي الطلبات</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?= $stats['assigned'] ?></div>
                    <div class="stat-label">مُعينة</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?= $stats['picked_up'] ?></div>
                    <div class="stat-label">تم الاستلام</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?= $stats['on_way'] ?></div>
                    <div class="stat-label">في الطريق</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?= $stats['delivered'] ?></div>
                    <div class="stat-label">مكتملة</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?= $stats['cancelled'] ?></div>
                    <div class="stat-label">ملغاة</div>
                </div>
            </div>

            <!-- Orders Table -->
            <div class="card">
                <div class="card-header">
                    <h2>طلبات المندوب (<?= count($orders) ?>)</h2>
                </div>

                <?php if (empty($orders)): ?>
                    <p style="text-align: center; color: #64748b; padding: 40px;">لا توجد طلبات لهذا المندوب</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>رقم الطلب</th>
                                <th>العميل</th>
                                <th>المبلغ</th>
                                <th>العنوان</th>
                                <th>تاريخ التعيين</th>
                                <th>الحالة</th>
                                <th>العمولة</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td>
                                        <strong>#<?= htmlspecialchars($order['order_number']) ?></strong>
                                        <br>
                                        <small style="color: #64748b;">
                                            <?= date('Y-m-d', strtotime($order['order_date'])) ?>
                                        </small>
                                    </td>
                                    <td><?= htmlspecialchars($order['customer_name']) ?></td>
                                    <td><?= formatPrice($order['total']) ?></td>
                                    <td>
                                        <small><?= htmlspecialchars($order['shipping_address']) ?></small>
                                    </td>
                                    <td><?= date('Y-m-d H:i', strtotime($order['assigned_at'])) ?></td>
                                    <td>
                                        <?php
                                        $status_badges = [
                                            'assigned' => ['badge-secondary', 'مُعينة'],
                                            'picked_up' => ['badge-info', 'تم الاستلام'],
                                            'on_way' => ['badge-primary', 'في الطريق'],
                                            'delivered' => ['badge-success', 'مكتملة'],
                                            'cancelled' => ['badge-danger', 'ملغاة']
                                        ];
                                        $status = $order['status'] ?? 'assigned';
                                        $badge = $status_badges[$status] ?? ['badge-secondary', $status];
                                        ?>
                                        <span class="badge <?= $badge[0] ?>">
                                            <?= $badge[1] ?>
                                        </span>
                                        <?php if ($order['delivered_at']): ?>
                                            <br>
                                            <small style="color: #64748b;">
                                                <?= date('Y-m-d H:i', strtotime($order['delivered_at'])) ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($order['commission_amount'] > 0): ?>
                                            <?= formatPrice($order['commission_amount']) ?>
                                        <?php else: ?>
                                            <span style="color: #64748b;">-</span>
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
</body>
</html>