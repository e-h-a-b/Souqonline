<?php
/**
 * صفحة إدارة الفئات
 */
session_start();
require_once '../config.php';
require_once '../functions.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// معالجة حذف الفئة
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $category_id = $_GET['delete'];
    
    try {
        // التحقق من وجود منتجات مرتبطة
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
        $stmt->execute([$category_id]);
        $product_count = $stmt->fetchColumn();
        
        if ($product_count > 0) {
            header('Location: categories.php?error=' . urlencode('لا يمكن حذف الفئة لأنها تحتوي على منتجات'));
            exit;
        }
        
        // التحقق من وجود فئات فرعية
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE parent_id = ?");
        $stmt->execute([$category_id]);
        $child_count = $stmt->fetchColumn();
        
        if ($child_count > 0) {
            header('Location: categories.php?error=' . urlencode('لا يمكن حذف الفئة لأنها تحتوي على فئات فرعية'));
            exit;
        }
        
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$category_id]);
        
        logActivity('category_deleted', "تم حذف الفئة #$category_id", $_SESSION['admin_id']);
        header('Location: categories.php?success=' . urlencode('تم حذف الفئة بنجاح'));
        exit;
        
    } catch (Exception $e) {
        header('Location: categories.php?error=' . urlencode('حدث خطأ أثناء الحذف'));
        exit;
    }
}

// جلب الفئات
$stmt = $pdo->query("
    SELECT c1.*, c2.name as parent_name, 
           (SELECT COUNT(*) FROM products WHERE category_id = c1.id) as products_count
    FROM categories c1 
    LEFT JOIN categories c2 ON c1.parent_id = c2.id 
    ORDER BY c1.parent_id, c1.display_order
");
$categories = $stmt->fetchAll();

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الفئات - لوحة التحكم</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* نفس أنماط products.php مع تعديلات بسيطة */
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
        
        .category-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
        }
        
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
        
        .child-category {
            padding-right: 30px;
            color: #64748b;
        }
        .child-category::before {
            content: "↳ ";
            margin-left: 5px;
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
                    <h1>إدارة الفئات</h1>
                    <p style="color: #64748b; margin-top: 5px;">تنظيم وإدارة فئات المنتجات</p>
                </div>
                <a href="category-form.php" class="btn btn-success">
                    <i class="fas fa-plus"></i>
                    إضافة فئة جديدة
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

            <!-- Categories Table -->
            <div class="card">
                <div class="card-header">
                    <h2>الفئات (<?= count($categories) ?>)</h2>
                </div>

                <?php if (empty($categories)): ?>
                    <p style="text-align: center; color: #64748b; padding: 40px;">لا توجد فئات</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>الصورة</th>
                                <th>اسم الفئة</th>
                                <th>الفئة الرئيسية</th>
                                <th>عدد المنتجات</th>
                                <th>ترتيب العرض</th>
                                <th>الحالة</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $category): ?>
                                <tr class="<?= $category['parent_id'] ? 'child-category' : '' ?>">
                                    <td>
                                        <?php if ($category['image']): ?>
                                            <img src="../<?= htmlspecialchars($category['image']) ?>" alt="<?= htmlspecialchars($category['name']) ?>" class="category-image">
                                        <?php else: ?>
                                            <div style="width: 60px; height: 60px; background: #f1f5f9; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #94a3b8;">
                                                <i class="fas fa-folder"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($category['name']) ?></strong>
                                        <?php if ($category['description']): ?>
                                            <br><small style="color: #64748b;"><?= htmlspecialchars($category['description']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($category['parent_name'] ?? '-') ?></td>
                                    <td>
                                        <span class="badge badge-secondary"><?= $category['products_count'] ?></span>
                                    </td>
                                    <td><?= $category['display_order'] ?></td>
                                    <td>
                                        <?php if ($category['is_active']): ?>
                                            <span class="badge badge-success">نشط</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">غير نشط</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="actions">
                                            <a href="category-form.php?id=<?= $category['id'] ?>" class="action-btn btn-primary" title="تعديل">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="categories.php?delete=<?= $category['id'] ?>" class="action-btn btn-danger" title="حذف" onclick="return confirm('هل أنت متأكد من حذف هذه الفئة؟')">
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