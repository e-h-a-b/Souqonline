<?php
/**
 * إدارة الإحالات - لوحة التحكم
 */
session_start();
require_once '../config.php';
require_once '../functions.php';
require_once '../referral_functions.php';

// التحقق من تسجيل الدخول
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// جلب إحصائيات الإحالة
$stmt = $pdo->query("
    SELECT 
        COUNT(DISTINCT rl.customer_id) as total_referrers,
        COUNT(DISTINCT r.referred_id) as total_referred,
        SUM(r.points_earned) as total_points_earned,
        COUNT(DISTINCT CASE WHEN r.status = 'completed_order' THEN r.id END) as successful_referrals
    FROM referral_links rl
    LEFT JOIN referrals r ON rl.referral_code = r.referral_code
");
$referral_stats = $stmt->fetch();

// جلب أفضل المحيلين
$stmt = $pdo->query("
    SELECT 
        c.first_name,
        c.email,
        rl.signups,
        rl.completed_orders,
        rl.total_earned_points
    FROM referral_links rl
    JOIN customers c ON rl.customer_id = c.id
    ORDER BY rl.total_earned_points DESC
    LIMIT 10
");
$top_referrers = $stmt->fetchAll();

// جلب أحدث الإحالات
$stmt = $pdo->query("
    SELECT 
        r.*,
        c1.first_name as referrer_name,
        c2.first_name as referred_name,
        c2.email as referred_email
    FROM referrals r
    JOIN customers c1 ON r.referrer_id = c1.id
    JOIN customers c2 ON r.referred_id = c2.id
    ORDER BY r.created_at DESC
    LIMIT 20
");
$recent_referrals = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الإحالات - لوحة التحكم</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* إضافة الأنماط من ملف index.php */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: #fff; padding: 25px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .card { background: #fff; border-radius: 12px; padding: 25px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: right; border-bottom: 1px solid #e2e8f0; }
        th { background: #f8fafc; font-weight: 600; color: #475569; }
        .badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .badge-success { background: #dcfce7; color: #166534; }
        .badge-warning { background: #fef3c7; color: #92400e; }
    </style> 
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
        <?php include 'sidebar.php'; ?>
        
        <main class="main-content">
            <div class="top-bar">
                <div class="page-title">
                    <h1>إدارة نظام الإحالات</h1>
                    <p style="color: #64748b; margin-top: 5px;">إحصائيات وتقارير برنامج الإحالات</p>
                </div>
            </div>

            <!-- إحصائيات الإحالة -->
            <div class="stats-grid">
                <div class="stat-card">
                    <h3 style="color: #64748b; margin-bottom: 8px;">إجمالي المحيلين</h3>
                    <div style="font-size: 32px; font-weight: 700; color: #2563eb;">
                        <?= number_format($referral_stats['total_referrers']) ?>
                    </div>
                </div>

                <div class="stat-card">
                    <h3 style="color: #64748b; margin-bottom: 8px;">إجمالي المُحالين</h3>
                    <div style="font-size: 32px; font-weight: 700; color: #16a34a;">
                        <?= number_format($referral_stats['total_referred']) ?>
                    </div>
                </div>

                <div class="stat-card">
                    <h3 style="color: #64748b; margin-bottom: 8px;">إحالات ناجحة</h3>
                    <div style="font-size: 32px; font-weight: 700; color: #f59e0b;">
                        <?= number_format($referral_stats['successful_referrals']) ?>
                    </div>
                </div>

                <div class="stat-card">
                    <h3 style="color: #64748b; margin-bottom: 8px;">إجمالي النقاط</h3>
                    <div style="font-size: 32px; font-weight: 700; color: #9333ea;">
                        <?= number_format($referral_stats['total_points_earned']) ?>
                    </div>
                </div>
            </div>

            <!-- أفضل المحيلين -->
            <div class="card">
                <h2>أفضل المحيلين</h2>
                <table>
                    <thead>
                        <tr>
                            <th>العميل</th>
                            <th>البريد الإلكتروني</th>
                            <th>الاشتراكات</th>
                            <th>الطلبات المكتملة</th>
                            <th>النقاط المكتسبة</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_referrers as $referrer): ?>
                            <tr>
                                <td><?= htmlspecialchars($referrer['first_name']) ?></td>
                                <td><?= htmlspecialchars($referrer['email']) ?></td>
                                <td><?= $referrer['signups'] ?></td>
                                <td><?= $referrer['completed_orders'] ?></td>
                                <td style="color: #16a34a; font-weight: 600;"><?= number_format($referrer['total_earned_points']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- أحدث الإحالات -->
            <div class="card">
                <h2>أحدث الإحالات</h2>
                <table>
                    <thead>
                        <tr>
                            <th>المُحيل</th>
                            <th>المُحال</th>
                            <th>البريد الإلكتروني</th>
                            <th>الحالة</th>
                            <th>النقاط</th>
                            <th>التاريخ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_referrals as $referral): ?>
                            <tr>
                                <td><?= htmlspecialchars($referral['referrer_name']) ?></td>
                                <td><?= htmlspecialchars($referral['referred_name']) ?></td>
                                <td><?= htmlspecialchars($referral['referred_email']) ?></td>
                                <td>
                                    <span class="badge <?= $referral['status'] === 'completed_order' ? 'badge-success' : 'badge-warning' ?>">
                                        <?= $referral['status'] === 'completed_order' ? 'مكتمل' : 'مسجل' ?>
                                    </span>
                                </td>
                                <td style="color: #16a34a; font-weight: 600;"><?= number_format($referral['points_earned']) ?></td>
                                <td><?= date('Y-m-d H:i', strtotime($referral['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>