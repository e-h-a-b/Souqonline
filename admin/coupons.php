<?php
/**
 * صفحة إدارة كوبونات الخصم
 */
session_start();
require_once '../config.php';
require_once '../functions.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// معالجة حذف الكوبون
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $coupon_id = $_GET['delete'];
    
    try {
        $stmt = $pdo->prepare("DELETE FROM coupons WHERE id = ?");
        $stmt->execute([$coupon_id]);
        
        logActivity('coupon_deleted', "تم حذف الكوبون #$coupon_id", $_SESSION['admin_id']);
        header('Location: coupons.php?success=' . urlencode('تم حذف الكوبون بنجاح'));
        exit;
        
    } catch (Exception $e) {
        header('Location: coupons.php?error=' . urlencode('حدث خطأ أثناء الحذف'));
        exit;
    }
}

// جلب الكوبونات
$stmt = $pdo->query("SELECT * FROM coupons ORDER BY created_at DESC");
$coupons = $stmt->fetchAll();

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة كوبونات الخصم - لوحة التحكم</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* نفس أنماط الصفحات السابقة */
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
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        .badge-info { background: #dbeafe; color: #1e40af; }
        .badge-secondary { background: #f1f5f9; color: #475569; }
        
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
        
        .coupon-code {
            font-family: 'Courier New', monospace;
            font-weight: bold;
            background: #f1f5f9;
            padding: 4px 8px;
            border-radius: 4px;
            border: 1px dashed #cbd5e1;
        }
        
        .discount-value {
            font-weight: bold;
            color: #16a34a;
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
                    <h1>إدارة كوبونات الخصم</h1>
                    <p style="color: #64748b; margin-top: 5px;">إنشاء وإدارة كوبونات الخصم والعروض</p>
                </div>
                <a href="coupon-form.php" class="btn btn-success">
                    <i class="fas fa-plus"></i>
                    إضافة كوبون جديد
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

            <!-- Coupons Table -->
            <div class="card">
                <div class="card-header">
                    <h2>كوبونات الخصم (<?= count($coupons) ?>)</h2>
                </div>

                <?php if (empty($coupons)): ?>
                    <p style="text-align: center; color: #64748b; padding: 40px;">لا توجد كوبونات خصم</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>الكوبون</th>
                                <th>نوع الخصم</th>
                                <th>قيمة الخصم</th>
                                <th>الاستخدام</th>
                                <th>فعال من</th>
                                <th>فعال إلى</th>
                                <th>الحالة</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($coupons as $coupon): ?>
                                <tr>
                                    <td>
                                        <span class="coupon-code"><?= htmlspecialchars($coupon['code']) ?></span>
                                        <?php if ($coupon['description']): ?>
                                            <br><small style="color: #64748b;"><?= htmlspecialchars($coupon['description']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($coupon['discount_type'] == 'percentage'): ?>
                                            <span class="badge badge-info">نسبة مئوية</span>
                                        <?php else: ?>
                                            <span class="badge badge-success">مبلغ ثابت</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="discount-value">
                                            <?php if ($coupon['discount_type'] == 'percentage'): ?>
                                                <?= $coupon['discount_value'] ?>%
                                            <?php else: ?>
                                                <?= formatPrice($coupon['discount_value']) ?>
                                            <?php endif; ?>
                                        </span>
                                        <?php if ($coupon['min_order_amount'] > 0): ?>
                                            <br><small style="color: #64748b;">الحد الأدنى: <?= formatPrice($coupon['min_order_amount']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= $coupon['usage_count'] ?> / 
                                        <?= $coupon['usage_limit'] ?: '∞' ?>
                                    </td>
                                    <td>
                                        <?php if ($coupon['valid_from']): ?>
                                            <?= date('Y-m-d', strtotime($coupon['valid_from'])) ?>
                                        <?php else: ?>
                                            <span style="color: #64748b;">غير محدد</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($coupon['valid_until']): ?>
                                            <?= date('Y-m-d', strtotime($coupon['valid_until'])) ?>
                                        <?php else: ?>
                                            <span style="color: #64748b;">غير محدد</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $now = new DateTime();
                                        $valid_until = $coupon['valid_until'] ? new DateTime($coupon['valid_until']) : null;
                                        
                                        if (!$coupon['is_active']): ?>
                                            <span class="badge badge-danger">غير فعال</span>
                                        <?php elseif ($valid_until && $valid_until < $now): ?>
                                            <span class="badge badge-warning">منتهي</span>
                                        <?php elseif ($coupon['usage_limit'] && $coupon['usage_count'] >= $coupon['usage_limit']): ?>
                                            <span class="badge badge-warning">مستنفذ</span>
                                        <?php else: ?>
                                            <span class="badge badge-success">فعال</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="actions">
                                            <a href="coupon-form.php?id=<?= $coupon['id'] ?>" class="action-btn btn-primary" title="تعديل">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="coupons.php?delete=<?= $coupon['id'] ?>" class="action-btn btn-danger" title="حذف" onclick="return confirm('هل أنت متأكد من حذف هذا الكوبون؟')">
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