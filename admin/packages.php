<?php
/**
 * صفحة إدارة الباقات في لوحة التحكم
 */
session_start();
require_once '../config.php';
require_once '../functions.php';

// التحقق من تسجيل الدخول
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// معالجة العمليات
$message = '';
$error = '';

// إضافة باقة جديدة
if ($_POST && isset($_POST['add_package'])) {
    $name = cleanInput($_POST['name']);
    $description = cleanInput($_POST['description']);
    $price = (float)$_POST['price'];
    $points = (int)$_POST['points'];
    $bonus_points = (int)$_POST['bonus_points'];
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $display_order = (int)$_POST['display_order'];

    try {
        $stmt = $pdo->prepare("
            INSERT INTO packages (name, description, price, points, bonus_points, is_featured, is_active, display_order)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$name, $description, $price, $points, $bonus_points, $is_featured, $is_active, $display_order]);
        
        $message = "تم إضافة الباقة بنجاح";
    } catch (Exception $e) {
        $error = "حدث خطأ أثناء إضافة الباقة: " . $e->getMessage();
    }
}

// تحديث باقة
if ($_POST && isset($_POST['update_package'])) {
    $id = (int)$_POST['package_id'];
    $name = cleanInput($_POST['name']);
    $description = cleanInput($_POST['description']);
    $price = (float)$_POST['price'];
    $points = (int)$_POST['points'];
    $bonus_points = (int)$_POST['bonus_points'];
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $display_order = (int)$_POST['display_order'];

    try {
        $stmt = $pdo->prepare("
            UPDATE packages 
            SET name = ?, description = ?, price = ?, points = ?, bonus_points = ?, 
                is_featured = ?, is_active = ?, display_order = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$name, $description, $price, $points, $bonus_points, $is_featured, $is_active, $display_order, $id]);
        
        $message = "تم تحديث الباقة بنجاح";
    } catch (Exception $e) {
        $error = "حدث خطأ أثناء تحديث الباقة: " . $e->getMessage();
    }
}

// حذف باقة
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    try {
        // التحقق من عدم وجود طلبات مرتبطة بالباقة
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM package_orders WHERE package_id = ?");
        $stmt->execute([$id]);
        $order_count = $stmt->fetchColumn();
        
        if ($order_count > 0) {
            $error = "لا يمكن حذف الباقة لأنها مرتبطة بطلبات سابقة";
        } else {
            $stmt = $pdo->prepare("DELETE FROM packages WHERE id = ?");
            $stmt->execute([$id]);
            $message = "تم حذف الباقة بنجاح";
        }
    } catch (Exception $e) {
        $error = "حدث خطأ أثناء حذف الباقة: " . $e->getMessage();
    }
}

// جلب جميع الباقات
$stmt = $pdo->query("SELECT * FROM packages ORDER BY display_order ASC, created_at DESC");
$packages = $stmt->fetchAll();

// جلب إحصائيات الباقات
$stats_stmt = $pdo->query("
    SELECT 
        COUNT(*) as total_packages,
        SUM(points) as total_points_value,
        SUM(price) as total_packages_value,
        COUNT(CASE WHEN is_featured = 1 THEN 1 END) as featured_packages,
        COUNT(CASE WHEN is_active = 1 THEN 1 END) as active_packages
    FROM packages
");
$stats = $stats_stmt->fetch();

// جلب إحصائيات طلبات الباقات
$orders_stats_stmt = $pdo->query("
    SELECT 
        COUNT(*) as total_orders,
        SUM(price) as total_revenue,
        COUNT(CASE WHEN payment_status = 'paid' THEN 1 END) as paid_orders,
        COUNT(CASE WHEN payment_status = 'pending' THEN 1 END) as pending_orders
    FROM package_orders
");
$orders_stats = $orders_stats_stmt->fetch();
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
                    <h1>إدارة الباقات</h1>
                    <p>إدارة باقات النقاط والعروض</p>
                </div>
                <button class="btn btn-primary" onclick="openModal('addPackageModal')">
                    <i class="fas fa-plus"></i> إضافة باقة جديدة
                </button>
            </div>

            <?php if ($message): ?>
                <div style="background: #d1e7dd; color: #0f5132; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                    <?= $message ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div style="background: #f8d7da; color: #721c24; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                    <?= $error ?>
                </div>
            <?php endif; ?>

            <!-- إحصائيات سريعة -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?= $stats['total_packages'] ?></div>
                    <div>إجمالي الباقات</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= number_format($stats['total_points_value']) ?></div>
                    <div>إجمالي النقاط</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= formatPrice($stats['total_packages_value']) ?></div>
                    <div>قيمة الباقات</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $orders_stats['total_orders'] ?></div>
                    <div>طلبات الباقات</div>
                </div>
            </div>

            <!-- جدول الباقات -->
            <div class="card">
                <div class="card-header">
                    <h2>قائمة الباقات</h2>
                </div>
                
                <?php if (empty($packages)): ?>
                    <p style="text-align: center; padding: 2rem; color: #6b7280;">لا توجد باقات</p>
                <?php else: ?>
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr>
                                <th style="padding: 1rem; text-align: right; background: #f8fafc;">الاسم</th>
                                <th style="padding: 1rem; text-align: center; background: #f8fafc;">السعر</th>
                                <th style="padding: 1rem; text-align: center; background: #f8fafc;">النقاط</th>
                                <th style="padding: 1rem; text-align: center; background: #f8fafc;">المكافأة</th>
                                <th style="padding: 1rem; text-align: center; background: #f8fafc;">الحالة</th>
                                <th style="padding: 1rem; text-align: center; background: #f8fafc;">ترتيب العرض</th>
                                <th style="padding: 1rem; text-align: center; background: #f8fafc;">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($packages as $package): ?>
                                <tr style="border-bottom: 1px solid #e5e7eb;">
                                    <td style="padding: 1rem;">
                                        <strong><?= htmlspecialchars($package['name']) ?></strong>
                                        <?php if ($package['is_featured']): ?>
                                            <span class="badge badge-warning" style="margin-right: 0.5rem;">مميز</span>
                                        <?php endif; ?>
                                        <?php if ($package['description']): ?>
                                            <p style="margin: 0.25rem 0 0 0; color: #6b7280; font-size: 0.875rem;">
                                                <?= htmlspecialchars($package['description']) ?>
                                            </p>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 1rem; text-align: center; font-weight: 600; color: #059669;">
                                        <?= formatPrice($package['price']) ?>
                                    </td>
                                    <td style="padding: 1rem; text-align: center;">
                                        <?= number_format($package['points']) ?>
                                    </td>
                                    <td style="padding: 1rem; text-align: center; color: #dc2626;">
                                        +<?= number_format($package['bonus_points']) ?>
                                    </td>
                                    <td style="padding: 1rem; text-align: center;">
                                        <span class="badge <?= $package['is_active'] ? 'badge-success' : 'badge-danger' ?>">
                                            <?= $package['is_active'] ? 'نشط' : 'غير نشط' ?>
                                        </span>
                                    </td>
                                    <td style="padding: 1rem; text-align: center;">
                                        <?= $package['display_order'] ?>
                                    </td>
                                    <td style="padding: 1rem; text-align: center;">
                                        <div class="table-actions">
                                            <button class="btn btn-sm btn-primary" 
                                                    onclick="editPackage(<?= htmlspecialchars(json_encode($package)) ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <a href="?delete=<?= $package['id'] ?>" 
                                               class="btn btn-sm btn-danger"
                                               onclick="return confirm('هل أنت متأكد من حذف هذه الباقة؟')">
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

            <!-- طلبات الباقات -->
            <div class="card" style="margin-top: 2rem;">
                <div class="card-header">
                    <h2>طلبات الباقات</h2>
                </div>
                
                <?php
                $orders_stmt = $pdo->query("
                    SELECT po.*, p.name as package_name, c.first_name, c.last_name, c.email
                    FROM package_orders po
                    JOIN packages p ON po.package_id = p.id
                    JOIN customers c ON po.customer_id = c.id
                    ORDER BY po.created_at DESC
                    LIMIT 10
                ");
                $recent_orders = $orders_stmt->fetchAll();
                ?>
                
                <?php if (empty($recent_orders)): ?>
                    <p style="text-align: center; padding: 2rem; color: #6b7280;">لا توجد طلبات باقات</p>
                <?php else: ?>
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr>
                                <th style="padding: 1rem; text-align: right; background: #f8fafc;">العميل</th>
                                <th style="padding: 1rem; text-align: center; background: #f8fafc;">الباقة</th>
                                <th style="padding: 1rem; text-align: center; background: #f8fafc;">المبلغ</th>
                                <th style="padding: 1rem; text-align: center; background: #f8fafc;">النقاط</th>
                                <th style="padding: 1rem; text-align: center; background: #f8fafc;">حالة الدفع</th>
                                <th style="padding: 1rem; text-align: center; background: #f8fafc;">التاريخ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_orders as $order): ?>
                                <tr style="border-bottom: 1px solid #e5e7eb;">
                                    <td style="padding: 1rem;">
                                        <strong><?= htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) ?></strong>
                                        <p style="margin: 0.25rem 0 0 0; color: #6b7280; font-size: 0.875rem;">
                                            <?= $order['email'] ?>
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
                                        $badge_class = 'badge-warning';
                                        if ($order['payment_status'] === 'paid') {
                                            $badge_class = 'badge-success';
                                        } elseif ($order['payment_status'] === 'failed') {
                                            $badge_class = 'badge-danger';
                                        }
                                        ?>
                                        <span class="badge <?= $badge_class ?>">
                                            <?= $order['payment_status'] === 'paid' ? 'مدفوع' : ($order['payment_status'] === 'pending' ? 'قيد الانتظار' : 'فاشل') ?>
                                        </span>
                                    </td>
                                    <td style="padding: 1rem; text-align: center;">
                                        <?= date('Y-m-d H:i', strtotime($order['created_at'])) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <div style="text-align: center; padding: 1rem;">
                        <a href="package_orders.php" class="btn btn-outline-primary">عرض جميع الطلبات</a>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Modal إضافة باقة -->
    <div id="addPackageModal" class="modal">
        <div class="modal-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h2>إضافة باقة جديدة</h2>
                <button onclick="closeModal('addPackageModal')" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
            </div>
            
            <form method="post">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="name">اسم الباقة *</label>
                        <input type="text" id="name" name="name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="price">السعر (ج.م) *</label>
                        <input type="number" id="price" name="price" step="0.01" min="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="points">النقاط الأساسية *</label>
                        <input type="number" id="points" name="points" min="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="bonus_points">نقاط المكافأة</label>
                        <input type="number" id="bonus_points" name="bonus_points" min="0" value="0">
                    </div>
                    
                    <div class="form-group">
                        <label for="display_order">ترتيب العرض</label>
                        <input type="number" id="display_order" name="display_order" min="0" value="0">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="description">الوصف</label>
                    <textarea id="description" name="description" rows="3"></textarea>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" id="is_featured" name="is_featured" value="1">
                            <label for="is_featured">باقة مميزة</label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" id="is_active" name="is_active" value="1" checked>
                            <label for="is_active">الباقة نشطة</label>
                        </div>
                    </div>
                </div>
                
                <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addPackageModal')">إلغاء</button>
                    <button type="submit" name="add_package" class="btn btn-primary">إضافة الباقة</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal تعديل باقة -->
    <div id="editPackageModal" class="modal">
        <div class="modal-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h2>تعديل الباقة</h2>
                <button onclick="closeModal('editPackageModal')" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
            </div>
            
            <form method="post">
                <input type="hidden" id="edit_package_id" name="package_id">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="edit_name">اسم الباقة *</label>
                        <input type="text" id="edit_name" name="name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_price">السعر (ج.م) *</label>
                        <input type="number" id="edit_price" name="price" step="0.01" min="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_points">النقاط الأساسية *</label>
                        <input type="number" id="edit_points" name="points" min="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_bonus_points">نقاط المكافأة</label>
                        <input type="number" id="edit_bonus_points" name="bonus_points" min="0" value="0">
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_display_order">ترتيب العرض</label>
                        <input type="number" id="edit_display_order" name="display_order" min="0" value="0">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="edit_description">الوصف</label>
                    <textarea id="edit_description" name="description" rows="3"></textarea>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" id="edit_is_featured" name="is_featured" value="1">
                            <label for="edit_is_featured">باقة مميزة</label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" id="edit_is_active" name="is_active" value="1">
                            <label for="edit_is_active">الباقة نشطة</label>
                        </div>
                    </div>
                </div>
                
                <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editPackageModal')">إلغاء</button>
                    <button type="submit" name="update_package" class="btn btn-primary">تحديث الباقة</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        function editPackage(package) {
            document.getElementById('edit_package_id').value = package.id;
            document.getElementById('edit_name').value = package.name;
            document.getElementById('edit_price').value = package.price;
            document.getElementById('edit_points').value = package.points;
            document.getElementById('edit_bonus_points').value = package.bonus_points;
            document.getElementById('edit_description').value = package.description || '';
            document.getElementById('edit_display_order').value = package.display_order;
            document.getElementById('edit_is_featured').checked = package.is_featured == 1;
            document.getElementById('edit_is_active').checked = package.is_active == 1;
            
            openModal('editPackageModal');
        }

        // إغلاق المودال عند النقر خارج المحتوى
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>