<?php
/**
 * صفحة إدارة المنتجات
 */
session_start();
require_once '../config.php';
require_once '../functions.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// معالجة حذف المنتج
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $product_id = $_GET['delete'];
    
    try {
        $pdo->beginTransaction();
        
        // التحقق من وجود الطلبات المرتبطة
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM order_items WHERE product_id = ?");
        $stmt->execute([$product_id]);
        $order_count = $stmt->fetchColumn();
        
        if ($order_count > 0) {
            // إذا كان هناك طلبات، نقوم بتعطيل المنتج فقط
            $stmt = $pdo->prepare("UPDATE products SET is_active = 0 WHERE id = ?");
            $stmt->execute([$product_id]);
            $message = "تم تعطيل المنتج لأنه مرتبط بطلبات سابقة";
        } else {
            // إذا لم يكن هناك طلبات، يمكن حذفه
            $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$product_id]);
            $message = "تم حذف المنتج بنجاح";
        }
        
        $pdo->commit();
        logActivity('product_deleted', $message, $_SESSION['admin_id']);
        header('Location: products.php?success=' . urlencode($message));
        exit;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        header('Location: products.php?error=' . urlencode('حدث خطأ أثناء الحذف'));
        exit;
    }
}

// البحث والتصفية
// البحث والتصفية
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$status = $_GET['status'] ?? '';

$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(p.title LIKE ? OR p.description LIKE ? OR p.sku LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

if (!empty($category) && $category != 'all') {
    $where[] = "p.category_id = ?";
    $params[] = $category;
}

if (!empty($status) && $status != 'all') {
    if ($status == 'active') {
        $where[] = "p.is_active = 1";
    } elseif ($status == 'inactive') {
        $where[] = "p.is_active = 0";
    } elseif ($status == 'featured') {
        $where[] = "p.is_featured = 1";
    } elseif ($status == 'outofstock') {
        $where[] = "p.stock = 0";
    } elseif ($status == 'lowstock') {
        $where[] = "p.stock > 0 AND p.stock < 10";
    }
}

// بناء الاستعلام الآمن - استخدام SELECT * بدون تحديد أعمدة غير موجودة
$sql = "SELECT p.*, c.name as category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE p.is_active = 1";

// إضافة شروط البحث إذا وجدت
if (!empty($where)) {
    $sql .= " AND " . implode(' AND ', $where);
}

// إضافة ORDER BY
$sql .= " ORDER BY p.created_at DESC";

// تحضير وتنفيذ الاستعلام
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll();
} catch (PDOException $e) {
    die("خطأ في قاعدة البيانات: " . $e->getMessage());
}

// جلب الفئات للفلتر
try {
    $stmt = $pdo->query("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name");
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    $categories = [];
}

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة المنتجات - لوحة التحكم</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* إضافة الأنماط من index.php مع تعديلات بسيطة */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8fafc;
            color: #334155;
        }
        .admin-wrapper { display: flex; min-height: 100vh; }
        
        /* Sidebar - نفس الأنماط */
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
        
        /* Filters */
        .filters {
            background: #fff;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .filter-row {
            display: flex;
            gap: 15px;
            align-items: end;
        }
        .form-group {
            flex: 1;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #334155;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 10px 12px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
        }
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
        .btn-success { background: #16a34a; color: #fff; }
        .btn-success:hover { background: #15803d; }
        .btn-danger { background: #dc2626; color: #fff; }
        .btn-danger:hover { background: #b91c1c; }
        .btn-secondary { background: #64748b; color: #fff; }
        .btn-secondary:hover { background: #475569; }
        
        /* Table */
        .card {
            background: #fff;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            overflow: hidden;
        }
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .card-header h2 { font-size: 20px; color: #1e293b; }
        
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
        
        .product-image {
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
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-top: 20px;
        }
        .page-link {
            padding: 8px 12px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            text-decoration: none;
            color: #475569;
        }
        .page-link.active {
            background: #2563eb;
            color: white;
            border-color: #2563eb;
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
                    <h1>إدارة المنتجات</h1>
                    <p style="color: #64748b; margin-top: 5px;">إدارة وعرض جميع منتجات المتجر</p>
                </div>
                <a href="product-form.php" class="btn btn-success">
                    <i class="fas fa-plus"></i>
                    إضافة منتج جديد
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

            <!-- Filters -->
            <div class="filters">
                <form method="get" action="products.php">
                    <div class="filter-row">
                        <div class="form-group">
                            <label for="search">بحث</label>
                            <input type="text" id="search" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="ابحث بالاسم، الوصف، أو SKU...">
                        </div>
                        <div class="form-group">
                            <label for="category">الفئة</label>
                            <select id="category" name="category">
                                <option value="all">جميع الفئات</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" <?= $category == $cat['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="status">الحالة</label>
                            <select id="status" name="status">
                                <option value="all">جميع الحالات</option>
                                <option value="active" <?= $status == 'active' ? 'selected' : '' ?>>نشط</option>
                                <option value="inactive" <?= $status == 'inactive' ? 'selected' : '' ?>>غير نشط</option>
                                <option value="featured" <?= $status == 'featured' ? 'selected' : '' ?>>مميز</option>
                                <option value="outofstock" <?= $status == 'outofstock' ? 'selected' : '' ?>>نفذ من المخزون</option>
                                <option value="lowstock" <?= $status == 'lowstock' ? 'selected' : '' ?>>مخزون منخفض</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter"></i>
                                تصفية
                            </button>
                            <a href="products.php" class="btn btn-secondary">
                                <i class="fas fa-refresh"></i>
                                إعادة تعيين
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Products Table -->
            <div class="card">
                <div class="card-header">
                    <h2>المنتجات (<?= count($products) ?>)</h2>
                </div>

                <?php if (empty($products)): ?>
                    <p style="text-align: center; color: #64748b; padding: 40px;">لا توجد منتجات</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>الصورة</th>
                                <th>المنتج</th>
                                <th>الفئة</th>
                                <th>السعر</th>
                                <th>المخزون</th>
                                <th>المبيعات</th>
                                <th>الحالة</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <td>
                                        <?php if ($product['main_image']): ?>
                                            <img src="../<?= htmlspecialchars($product['main_image']) ?>" alt="<?= htmlspecialchars($product['title']) ?>" class="product-image">
                                        <?php else: ?>
                                            <div style="width: 60px; height: 60px; background: #f1f5f9; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #94a3b8;">
                                                <i class="fas fa-image"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($product['title']) ?></strong>
                                        <?php if ($product['sku']): ?>
                                            <br><small style="color: #64748b;"><?= htmlspecialchars($product['sku']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($product['category_name'] ?? 'بدون فئة') ?></td>
                                    <td>
                                        <strong><?= formatPrice($product['final_price']) ?></strong>
                                        <?php if ($product['discount_percentage'] > 0): ?>
                                            <br><small style="color: #dc2626; text-decoration: line-through;"><?= formatPrice($product['price']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($product['stock'] == 0): ?>
                                            <span class="badge badge-danger">نفذ</span>
                                        <?php elseif ($product['stock'] < 10): ?>
                                            <span class="badge badge-warning"><?= $product['stock'] ?></span>
                                        <?php else: ?>
                                            <span class="badge badge-success"><?= $product['stock'] ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $product['orders_count'] ?></td>
                                    <td>
                                        <?php if ($product['is_active']): ?>
                                            <span class="badge badge-success">نشط</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">غير نشط</span>
                                        <?php endif; ?>
                                        <?php if ($product['is_featured']): ?>
                                            <span class="badge badge-info" style="margin-top: 4px;">مميز</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="actions">
                                            <a href="../product.php?id=<?= $product['id'] ?>" target="_blank" class="action-btn btn-secondary" title="عرض">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="product-form.php?id=<?= $product['id'] ?>" class="action-btn btn-primary" title="تعديل">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="products.php?delete=<?= $product['id'] ?>" class="action-btn btn-danger" title="حذف" onclick="return confirm('هل أنت متأكد من حذف هذا المنتج؟')">
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