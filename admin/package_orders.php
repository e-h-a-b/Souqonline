<?php
/**
 * صفحة طلبات الباقات في لوحة التحكم
 */
session_start();
require_once '../config.php';
require_once '../functions.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// معالجة تحديث حالة الطلب
if ($_POST && isset($_POST['update_order_status'])) {
    $order_id = (int)$_POST['order_id'];
    $payment_status = cleanInput($_POST['payment_status']);
    
    try {
        $stmt = $pdo->prepare("UPDATE package_orders SET payment_status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$payment_status, $order_id]);
        
        // إذا تم الدفع، إضافة النقاط للعميل
        if ($payment_status === 'paid') {
            processPackagePayment($order_id);
        }
        
        $message = "تم تحديث حالة الطلب بنجاح";
    } catch (Exception $e) {
        $error = "حدث خطأ أثناء تحديث حالة الطلب";
    }
}
// معالجة الإضافة اليدوية للنقاط
if ($_POST && isset($_POST['add_points_manual'])) {
    $order_id = (int)$_POST['order_id'];
    
    try {
        $success = processPackagePayment($order_id);
        
        if ($success) {
            $message = "تم إضافة النقاط للعميل بنجاح";
        } else {
            $error = "فشل في إضافة النقاط للعميل";
        }
    } catch (Exception $e) {
        $error = "حدث خطأ أثناء إضافة النقاط: " . $e->getMessage();
    }
}
// جلب جميع طلبات الباقات
// جلب جميع طلبات الباقات
$stmt = $pdo->query("
    SELECT po.*, p.name as package_name, p.points, p.bonus_points,
           c.first_name, c.last_name, c.email, c.phone
    FROM package_orders po
    JOIN packages p ON po.package_id = p.id
    JOIN customers c ON po.customer_id = c.id
    ORDER BY po.created_at DESC
");
$orders = $stmt->fetchAll();
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
                    <h1>طلبات الباقات</h1>
                    <p>إدارة طلبات شراء الباقات</p>
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

            <div class="card">
                <?php if (empty($orders)): ?>
                    <p style="text-align: center; padding: 2rem; color: #6b7280;">لا توجد طلبات باقات</p>
                <?php else: ?>
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr>
                                <th style="padding: 1rem; text-align: right; background: #f8fafc;">العميل</th>
                                <th style="padding: 1rem; text-align: center; background: #f8fafc;">الباقة</th>
                                <th style="padding: 1rem; text-align: center; background: #f8fafc;">المبلغ</th>
                                <th style="padding: 1rem; text-align: center; background: #f8fafc;">النقاط</th>
                                <th style="padding: 1rem; text-align: center; background: #f8fafc;">طريقة الدفع</th>
                                <th style="padding: 1rem; text-align: center; background: #f8fafc;">حالة الدفع</th>
                                <th style="padding: 1rem; text-align: center; background: #f8fafc;">التاريخ</th>
                                <th style="padding: 1rem; text-align: center; background: #f8fafc;">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr style="border-bottom: 1px solid #e5e7eb;">
                                    <td style="padding: 1rem;">
                                        <strong><?= htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) ?></strong>
                                        <p style="margin: 0.25rem 0 0 0; color: #6b7280; font-size: 0.875rem;">
                                            <?= $order['email'] ?><br>
                                            <?= $order['phone'] ?>
                                        </p>
                                    </td>
                                    <td style="padding: 1rem; text-align: center;">
                                        <?= htmlspecialchars($order['package_name']) ?>
                                    </td>
                                    <td style="padding: 1rem; text-align: center; font-weight: 600;">
                                        <?= formatPrice($order['price']) ?>
                                    </td>
                                    <td style="padding: 1rem; text-align: center; color: #059669;">
                                        <?= number_format($order['points_amount']) ?>
                                    </td>
                                    <td style="padding: 1rem; text-align: center;">
                                        <?php
                                        $payment_methods = [
                                            'cod' => 'الاستلام',
                                            'vodafone_cash' => 'فودافون كاش',
                                            'fawry' => 'فوري'
                                        ];
                                        echo $payment_methods[$order['payment_method']] ?? $order['payment_method'];
                                        ?>
                                    </td>
                                    <td style="padding: 1rem; text-align: center;">
                                        <form method="post" style="display: inline-block;">
                                            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                            <select name="payment_status" onchange="this.form.submit()" 
                                                    style="padding: 0.25rem 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
                                                <option value="pending" <?= $order['payment_status'] === 'pending' ? 'selected' : '' ?>>قيد الانتظار</option>
                                                <option value="paid" <?= $order['payment_status'] === 'paid' ? 'selected' : '' ?>>مدفوع</option>
                                                <option value="failed" <?= $order['payment_status'] === 'failed' ? 'selected' : '' ?>>فاشل</option>
                                            </select>
                                            <noscript>
                                                <button type="submit" name="update_order_status" class="btn btn-sm btn-primary">تحديث</button>
                                            </noscript>
                                        </form>
                                    </td>
                                    <td style="padding: 1rem; text-align: center;">
                                        <?= date('Y-m-d H:i', strtotime($order['created_at'])) ?>
                                    </td> 
<td style="padding: 1rem; text-align: center;">
    <div style="display: flex; flex-direction: column; gap: 5px; align-items: center;">
        <?php if ($order['points_added']): ?>
            <span class="badge badge-success">
                <i class="fas fa-check-circle"></i> تمت إضافة النقاط
            </span>
        <?php else: ?>
            <span class="badge badge-warning">
                <i class="fas fa-clock"></i> بانتظار النقاط
            </span>
            <?php if ($order['payment_status'] === 'paid'): ?>
                <form method="post" style="margin: 0;">
                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                    <button type="submit" name="add_points_manual" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus-circle"></i> إضافة النقاط يدوياً
                    </button>
                </form>
            <?php endif; ?>
        <?php endif; ?>
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