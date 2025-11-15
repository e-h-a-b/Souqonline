<?php
session_start();
require_once '../config.php';
require_once '../functions.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// معالجة إضافة النقاط يدوياً
if ($_POST && isset($_POST['add_points'])) {
    $customer_email = cleanInput($_POST['customer_email']);
    $points = (int)$_POST['points'];
    $description = cleanInput($_POST['description']);
    
    // البحث عن العميل
    $stmt = $pdo->prepare("SELECT id FROM customers WHERE email = ?");
    $stmt->execute([$customer_email]);
    $customer = $stmt->fetch();
    
    if ($customer && $points > 0) {
        if (addCustomerPoints($customer['id'], $points, $description, 'manual', null, 365)) {
            $message = "تم إضافة $points نقطة للعميل بنجاح";
        } else {
            $error = "حدث خطأ أثناء إضافة النقاط";
        }
    } else {
        $error = "العميل غير موجود أو النقاط غير صالحة";
    }
}

// جلب إحصائيات النقاط
$stmt = $pdo->query("
    SELECT COUNT(*) as total_customers,
           SUM(points) as total_points,
           SUM(total_earned) as total_earned,
           SUM(total_spent) as total_spent
    FROM customer_points
");
$points_stats = $stmt->fetch();

// جلب العملاء الأكثر نقاطاً
$stmt = $pdo->query("
    SELECT c.first_name, c.last_name, c.email, cp.points, cp.total_earned
    FROM customer_points cp
    JOIN customers c ON cp.customer_id = c.id
    ORDER BY cp.points DESC
    LIMIT 10
");
$top_customers = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم - <?= getSetting('store_name') ?></title>
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
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: #fff;
        }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: #fff;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: transform 0.3s;
        }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
        .stat-info h3 { font-size: 14px; color: #64748b; margin-bottom: 8px; }
        .stat-info .number { font-size: 32px; font-weight: 700; color: #1e293b; }
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        .stat-card.blue .stat-icon { background: #dbeafe; color: #2563eb; }
        .stat-card.green .stat-icon { background: #dcfce7; color: #16a34a; }
        .stat-card.orange .stat-icon { background: #fed7aa; color: #ea580c; }
        .stat-card.purple .stat-icon { background: #e9d5ff; color: #9333ea; }
        
        /* Tables */
        .card {
            background: #fff;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .card-header h2 { font-size: 20px; color: #1e293b; }
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
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        .badge-info { background: #dbeafe; color: #1e40af; }
        
        /* Charts Placeholder */
        .chart-container {
            height: 300px;
            background: #f8fafc;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #94a3b8;
        }
    </style>
</head>
<body>
    <div class="admin-wrapper"> 
                <!-- Sidebar -->
                <!-- تضمين القائمة الجانبية -->
        <?php include 'sidebar.php'; ?>
        <main class="main-content">
            <div class="top-bar">
                <div class="page-title">
                    <h1>إدارة نظام النقاط</h1>
                    <p>إدارة نقاط العملاء والمكافآت</p>
                </div>
            </div>

            <?php if (isset($message)): ?>
                <div style="background: #d1e7dd; color: #0f5132; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                    <?= $message ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div style="background: #f8d7da; color: #721c24; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                    <?= $error ?>
                </div>
            <?php endif; ?>

            <div class="points-grid">
                <div>
                    <!-- إحصائيات سريعة -->
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; margin-bottom: 2rem;">
                        <div class="stat-card">
                            <h3>إجمالي النقاط</h3>
                            <div class="number"><?= number_format($points_stats['total_points']) ?></div>
                        </div>
                        <div class="stat-card">
                            <h3>العملاء النشطين</h3>
                            <div class="number"><?= number_format($points_stats['total_customers']) ?></div>
                        </div>
                    </div>

                    <!-- العملاء الأكثر نقاطاً -->
                    <div class="card">
                        <h2>أفضل العملاء</h2>
                        <table>
                            <thead>
                                <tr>
                                    <th>العميل</th>
                                    <th>النقاط الحالية</th>
                                    <th>إجمالي المكتسب</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($top_customers as $customer): ?>
                                <tr>
                                    <td>
                                        <?= htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']) ?>
                                        <br><small><?= $customer['email'] ?></small>
                                    </td>
                                    <td><?= number_format($customer['points']) ?></td>
                                    <td><?= number_format($customer['total_earned']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div>
                    <!-- نموذج إضافة النقاط -->
                    <div class="form-card">
                        <h3>إضافة نقاط يدوياً</h3>
                        <form method="post">
                            <div class="form-group">
                                <label>البريد الإلكتروني للعميل</label>
                                <input type="email" name="customer_email" required>
                            </div>
                            <div class="form-group">
                                <label>عدد النقاط</label>
                                <input type="number" name="points" min="1" required>
                            </div>
                            <div class="form-group">
                                <label>الوصف</label>
                                <input type="text" name="description" required>
                            </div>
                            <button type="submit" name="add_points" class="btn btn-primary">
                                إضافة النقاط
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>