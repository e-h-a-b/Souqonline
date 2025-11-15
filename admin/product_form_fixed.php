<?php
/**
 * صفحة إضافة/تعديل المنتج - نسخة محسّنة
 */
session_start();
require_once '../config.php';
require_once '../functions.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// التحقق من إمكانية الكتابة في مجلدات الرفع
$upload_dirs = ['../uploads/', '../uploads/products/', '../uploads/products/gallery/'];
$upload_errors = [];

foreach ($upload_dirs as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }
    if (!is_writable($dir)) {
        $upload_errors[] = "المجلد $dir غير قابل للكتابة. يرجى تعديل الصلاحيات إلى 755 أو 777";
    }
}

$product_id = $_GET['id'] ?? 0;
$product = null;
$categories = [];
$error = '';
$success = '';

// جلب الفئات
$stmt = $pdo->query("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name");
$categories = $stmt->fetchAll();

// جلب بيانات المنتج إذا كان تعديل
if ($product_id) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
    
    if (!$product) {
        $error = 'المنتج غير موجود';
    } else {
        // جلب صور المعرض
        $gallery_stmt = $pdo->prepare("
            SELECT * FROM product_images 
            WHERE product_id = ? AND is_main = 0
            ORDER BY display_order ASC
        ");
        $gallery_stmt->execute([$product_id]);
        $product['gallery'] = $gallery_stmt->fetchAll();
    }
}

// معالجة النموذج
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = cleanInput($_POST['title'] ?? '');
    $slug = cleanInput($_POST['slug'] ?? '');
    $category_id = $_POST['category_id'] ?? null;
    $description = cleanInput($_POST['description'] ?? '');
    $short_description = cleanInput($_POST['short_description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $discount_percentage = floatval($_POST['discount_percentage'] ?? 0);
    $discount_amount = floatval($_POST['discount_amount'] ?? 0);
    $stock = intval($_POST['stock'] ?? 0);
    $sku = cleanInput($_POST['sku'] ?? '');
    $weight = $_POST['weight'] ? floatval($_POST['weight']) : null;
    $dimensions = cleanInput($_POST['dimensions'] ?? '');
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $meta_title = cleanInput($_POST['meta_title'] ?? '');
    $meta_description = cleanInput($_POST['meta_description'] ?? '');
    
    // التحقق من البيانات
    if (empty($title) || empty($price)) {
        $error = 'يرجى ملء الحقول المطلوبة (العنوان والسعر)';
    } elseif (empty($slug)) {
        $error = 'يرجى إدخال رابط SEO';
    } else {
        try {
            // المتغيرات لحفظ أسماء الصور
            $main_image_name = $product['main_image'] ?? '';
            $gallery_images = [];
            
            // **1. معالجة الصورة الرئيسية أولاً**
            if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] === UPLOAD_ERR_OK) {
                $main_image_result = handleImageUpload($_FILES['main_image'], 'products');
                if ($main_image_result['success']) {
                    // حذف الصورة القديمة إذا كانت موجودة
                    if (!empty($product['main_image'])) {
                        $old_image_path = '../uploads/products/' . $product['main_image'];
                        if (file_exists($old_image_path)) {
                            unlink($old_image_path);
                        }
                    }
                    $main_image_name = 'uploads/products/' . $main_image_result['file_name'];
                } else {
                    $upload_errors[] = $main_image_result['error'];
                }
            }
            // حذف الصورة الرئيسية إذا طلب المستخدم ذلك
            elseif (isset($_POST['delete_main_image']) && $_POST['delete_main_image'] == 1) {
                if (!empty($product['main_image'])) {
                    $old_image_path = '../uploads/products/' . $product['main_image'];
                    if (file_exists($old_image_path)) {
                        unlink($old_image_path);
                    }
                }
                $main_image_name = '';
            }
            
            // **2. معالجة صور المعرض**
            if (isset($_FILES['gallery_images']) && !empty($_FILES['gallery_images']['name'][0])) {
                $gallery_count = count($_FILES['gallery_images']['name']);
                $max_gallery_images = 5;
                
                if ($gallery_count > $max_gallery_images) {
                    $upload_errors[] = "يمكنك رفع حد أقصى $max_gallery_images صور للمعرض";
                } else {
                    foreach ($_FILES['gallery_images']['tmp_name'] as $key => $tmp_name) {
                        if ($_FILES['gallery_images']['error'][$key] === UPLOAD_ERR_OK) {
                            $gallery_file = [
                                'name' => $_FILES['gallery_images']['name'][$key],
                                'type' => $_FILES['gallery_images']['type'][$key],
                                'tmp_name' => $tmp_name,
                                'error' => $_FILES['gallery_images']['error'][$key],
                                'size' => $_FILES['gallery_images']['size'][$key]
                            ];
                            
                            $gallery_result = handleImageUpload($gallery_file, 'products/gallery');
                            if ($gallery_result['success']) {
                                $gallery_images[] = 'uploads/products/gallery/' . $gallery_result['file_name'];
                            } else {
                                $upload_errors[] = $gallery_result['error'];
                            }
                        }
                    }
                }
            }
            
            // **3. التحقق من وجود أخطاء في رفع الصور**
            if (!empty($upload_errors)) {
                throw new Exception(implode('<br>', $upload_errors));
            }
            
            // **4. حفظ البيانات في قاعدة البيانات**
            if ($product_id && $product) {
                // تحديث المنتج
                $stmt = $pdo->prepare("
                    UPDATE products SET 
                        category_id = ?, title = ?, slug = ?, description = ?, short_description = ?,
                        price = ?, discount_percentage = ?, discount_amount = ?, stock = ?, sku = ?,
                        weight = ?, dimensions = ?, main_image = ?, is_featured = ?, is_active = ?,
                        meta_title = ?, meta_description = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                
                $stmt->execute([
                    $category_id, $title, $slug, $description, $short_description,
                    $price, $discount_percentage, $discount_amount, $stock, $sku,
                    $weight, $dimensions, $main_image_name, $is_featured, $is_active,
                    $meta_title, $meta_description, $product_id
                ]);
                
                // تحديث الصورة الرئيسية في جدول product_images
                if (!empty($main_image_name)) {
                    // حذف الصورة الرئيسية القديمة من الجدول
                    $pdo->prepare("DELETE FROM product_images WHERE product_id = ? AND is_main = 1")
                        ->execute([$product_id]);
                    
                    // إضافة الصورة الرئيسية الجديدة
                    $pdo->prepare("
                        INSERT INTO product_images (product_id, image_path, display_order, is_main) 
                        VALUES (?, ?, 0, 1)
                    ")->execute([$product_id, $main_image_name]);
                } elseif (isset($_POST['delete_main_image']) && $_POST['delete_main_image'] == 1) {
                    $pdo->prepare("DELETE FROM product_images WHERE product_id = ? AND is_main = 1")
                        ->execute([$product_id]);
                }
                
                // إضافة صور المعرض الجديدة
                if (!empty($gallery_images)) {
                    $order_stmt = $pdo->prepare("
                        SELECT COALESCE(MAX(display_order), 0) as max_order 
                        FROM product_images WHERE product_id = ?
                    ");
                    $order_stmt->execute([$product_id]);
                    $max_order = $order_stmt->fetch()['max_order'];
                    
                    $gallery_stmt = $pdo->prepare("
                        INSERT INTO product_images (product_id, image_path, display_order, is_main) 
                        VALUES (?, ?, ?, 0)
                    ");
                    
                    $display_order = $max_order + 1;
                    foreach ($gallery_images as $gallery_image) {
                        $gallery_stmt->execute([$product_id, $gallery_image, $display_order]);
                        $display_order++;
                    }
                }
                
                $action = 'updated';
                $message = 'تم تحديث المنتج بنجاح';
                
            } else {
                // إضافة منتج جديد
                $stmt = $pdo->prepare("
                    INSERT INTO products (
                        category_id, title, slug, description, short_description,
                        price, discount_percentage, discount_amount, stock, sku,
                        weight, dimensions, main_image, is_featured, is_active,
                        meta_title, meta_description
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $category_id, $title, $slug, $description, $short_description,
                    $price, $discount_percentage, $discount_amount, $stock, $sku,
                    $weight, $dimensions, $main_image_name, $is_featured, $is_active,
                    $meta_title, $meta_description
                ]);
                
                $product_id = $pdo->lastInsertId();
                
                // إضافة الصورة الرئيسية في جدول product_images
                if (!empty($main_image_name)) {
                    $pdo->prepare("
                        INSERT INTO product_images (product_id, image_path, display_order, is_main) 
                        VALUES (?, ?, 0, 1)
                    ")->execute([$product_id, $main_image_name]);
                }
                
                // إضافة صور المعرض
                if (!empty($gallery_images)) {
                    $gallery_stmt = $pdo->prepare("
                        INSERT INTO product_images (product_id, image_path, display_order, is_main) 
                        VALUES (?, ?, ?, 0)
                    ");
                    
                    $display_order = 1;
                    foreach ($gallery_images as $gallery_image) {
                        $gallery_stmt->execute([$product_id, $gallery_image, $display_order]);
                        $display_order++;
                    }
                }
                
                $action = 'created';
                $message = 'تم إضافة المنتج بنجاح';
            }
            
            // حذف صور المعرض المحددة
            if (isset($_POST['delete_gallery_images']) && is_array($_POST['delete_gallery_images'])) {
                foreach ($_POST['delete_gallery_images'] as $image_id) {
                    $stmt = $pdo->prepare("SELECT image_path FROM product_images WHERE id = ? AND is_main = 0");
                    $stmt->execute([$image_id]);
                    $image = $stmt->fetch();
                    
                    if ($image) {
                        $image_path = '../uploads/products/gallery/' . $image['image_path'];
                        if (file_exists($image_path)) {
                            unlink($image_path);
                        }
                        
                        $pdo->prepare("DELETE FROM product_images WHERE id = ?")
                            ->execute([$image_id]);
                    }
                }
            }
            
            logActivity("product_{$action}", $message, $_SESSION['admin_id']);
            $success = $message;
            
            // إعادة توجيه بعد النجاح
            header('Location: product-form.php?id=' . $product_id . '&success=' . urlencode($message));
            exit;
            
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                $error = 'رابط SEO أو SKU مستخدم مسبقاً';
            } else {
                $error = 'حدث خطأ: ' . $e->getMessage();
            }
        }
    }
}

// جلب بيانات المنتج مرة أخرى بعد التعديل
if ($product_id && !$product) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
}

$success = $_GET['success'] ?? $success;
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $product ? 'تعديل المنتج' : 'إضافة منتج جديد' ?> - لوحة التحكم</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
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
        
        .card {
            background: #fff;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 20px;
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
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .form-help {
            font-size: 12px;
            color: #64748b;
            margin-top: 5px;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .checkbox-group input[type="checkbox"] {
            width: auto;
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
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .price-calculator {
            background: #f8fafc;
            padding: 15px;
            border-radius: 8px;
            margin-top: 10px;
        }
        .price-result {
            font-weight: 600;
            color: #16a34a;
            margin-top: 10px;
        }
        
        .form-group input[type="file"] {
            padding: 8px;
            background: #f8fafc;
            border: 2px dashed #e2e8f0;
        }
        
        .form-group input[type="file"]:focus {
            border-color: #2563eb;
            background: #fff;
        }
        
        .image-preview {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 15px;
        }
        
        .image-preview-item {
            position: relative;
            text-align: center;
        }
        
        .image-preview img {
            max-width: 150px;
            max-height: 150px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .image-preview img:hover {
            border-color: #2563eb;
        }
        
        .delete-image-checkbox {
            margin-top: 5px;
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
                <a href="products.php" class="menu-item active">
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
                    <h1><?= $product ? 'تعديل المنتج' : 'إضافة منتج جديد' ?></h1>
                </div>
                <div>
                    <a href="products.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-right"></i>
                        رجوع
                    </a>
                    <button type="submit" form="product-form" class="btn btn-success">
                        <i class="fas fa-save"></i>
                        حفظ المنتج
                    </button>
                </div>
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

            <form id="product-form" method="post" enctype="multipart/form-data">
                <div class="form-grid">
                    <!-- المعلومات الأساسية -->
                    <div class="card">
                        <h2 style="margin-bottom: 20px;">المعلومات الأساسية</h2>
                        
                        <div class="form-group">
                            <label for="title">عنوان المنتج *</label>
                            <input type="text" id="title" name="title" 
                                   value="<?= htmlspecialchars($product['title'] ?? '') ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="slug">رابط SEO *</label>
                            <input type="text" id="slug" name="slug" 
                                   value="<?= htmlspecialchars($product['slug'] ?? '') ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="category_id">الفئة</label>
                            <select id="category_id" name="category_id">
                                <option value="">بدون فئة</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= $category['id'] ?>" 
                                        <?= ($product['category_id'] ?? '') == $category['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($category['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="short_description">الوصف المختصر</label>
                            <textarea id="short_description" name="short_description"><?= htmlspecialchars($product['short_description'] ?? '') ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="description">الوصف الكامل</label>
                            <textarea id="description" name="description" rows="6"><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <!-- السعر والمخزون -->
                    <div class="card">
                        <h2 style="margin-bottom: 20px;">السعر والمخزون</h2>
                        
                        <div class="form-group">
                            <label for="price">السعر الأساسي (ج.م) *</label>
                            <input type="number" id="price" name="price" 
                                   value="<?= htmlspecialchars($product['price'] ?? '') ?>" 
                                   step="0.01" min="0" required>
                        </div>

                        <div class="form-group">
                            <label for="discount_percentage">نسبة الخصم (%)</label>
                            <input type="number" id="discount_percentage" name="discount_percentage" 
                                   value="<?= htmlspecialchars($product['discount_percentage'] ?? '') ?>" 
                                   step="0.01" min="0" max="100">
                        </div>

                        <div class="form-group">
                            <label for="discount_amount">مبلغ الخصم الثابت (ج.م)</label>
                            <input type="number" id="discount_amount" name="discount_amount" 
                                   value="<?= htmlspecialchars($product['discount_amount'] ?? '') ?>" 
                                   step="0.01" min="0">
                        </div>

                        <div class="price-calculator">
                            <strong>السعر النهائي: </strong>
                            <span class="price-result" id="final-price">0 ج.م</span>
                        </div>

                        <div class="form-group">
                            <label for="stock">الكمية في المخزون</label>
                            <input type="number" id="stock" name="stock" 
                                   value="<?= htmlspecialchars($product['stock'] ?? 0) ?>" 
                                   min="0">
                        </div>

                        <div class="form-group">
                            <label for="sku">كود المنتج (SKU)</label>
                            <input type="text" id="sku" name="sku" 
                                   value="<?= htmlspecialchars($product['sku'] ?? '') ?>">
                        </div>
                    </div>

                    <!-- معلومات إضافية -->
                    <div class="card">
                        <h2 style="margin-bottom: 20px;">معلومات إضافية</h2>
                        
                        <div class="form-group">
                            <label for="weight">الوزن (كجم)</label>
                            <input type="number" id="weight" name="weight" 
                                   value="<?= htmlspecialchars($product['weight'] ?? '') ?>" 
                                   step="0.01" min="0">
                        </div>

                        <div class="form-group">
                            <label for="dimensions">الأبعاد</label>
                            <input type="text" id="dimensions" name="dimensions" 
                                   value="<?= htmlspecialchars($product['dimensions'] ?? '') ?>"
                                   placeholder="الطول × العرض × الارتفاع">
                        </div>

                        <div class="form-group">
                            <div class="checkbox-group">
                                <input type="checkbox" id="is_featured" name="is_featured" 
                                       value="1" <?= ($product['is_featured'] ?? 0) ? 'checked' : '' ?>>
                                <label for="is_featured">منتج مميز</label>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="checkbox-group">
                                <input type="checkbox" id="is_active" name="is_active" 
                                       value="1" <?= ($product['is_active'] ?? 1) ? 'checked' : '' ?>>
                                <label for="is_active">منتج نشط</label>
                            </div>
                        </div>
                    </div>

                    <!-- صور المنتج -->
                    <div class="card" style="grid-column: 1 / -1;">
                        <h2 style="margin-bottom: 20px;">صور المنتج</h2>
                        
                        <div class="form-group">
                            <label for="main_image">الصورة الرئيسية</label>
                            <input type="file" id="main_image" name="main_image" accept="image/*">
                            <div class="form-help">الصورة الرئيسية التي تظهر في قائمة المنتجات (الحد الأقصى: 5MB)</div>
                            
                            <?php if (!empty($product['main_image']) && file_exists('../uploads/products/' . $product['main_image'])): ?>
                                <div class="image-preview">
                                    <div class="image-preview-item">
                                        <img src="../<?= htmlspecialchars($product['main_image']) ?>" 
                                             alt="الصورة الحالية">
                                        <div class="delete-image-checkbox">
                                            <label>
                                                <input type="checkbox" name="delete_main_image" value="1">
                                                حذف الصورة
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label for="gallery_images">صور المعرض</label>
                            <input type="file" id="gallery_images" name="gallery_images[]" multiple accept="image/*">
                            <div class="form-help">يمكنك اختيار حتى 5 صور للمعرض (الحد الأقصى لكل صورة: 5MB)</div>
                            
                            <?php if (!empty($product['gallery']) && count($product['gallery']) > 0): ?>
                                <div class="image-preview">
                                    <?php foreach ($product['gallery'] as $image): ?>
                                        <div class="image-preview-item">
                                            <img src="../<?= htmlspecialchars($image['image_path']) ?>" 
                                                 alt="صورة المعرض">
                                            <div class="delete-image-checkbox">
                                                <label>
                                                    <input type="checkbox" name="delete_gallery_images[]" value="<?= $image['id'] ?>">
                                                    حذف
                                                </label>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- SEO -->
                    <div class="card" style="grid-column: 1 / -1;">
                        <h2 style="margin-bottom: 20px;">تحسين محركات البحث (SEO)</h2>
                        
                        <div class="form-group">
                            <label for="meta_title">عنوان SEO</label>
                            <input type="text" id="meta_title" name="meta_title" 
                                   value="<?= htmlspecialchars($product['meta_title'] ?? '') ?>">
                            <div class="form-help">عنوان الصفحة في محركات البحث</div>
                        </div>

                        <div class="form-group">
                            <label for="meta_description">وصف SEO</label>
                            <textarea id="meta_description" name="meta_description"><?= htmlspecialchars($product['meta_description'] ?? '') ?></textarea>
                            <div class="form-help">وصف الصفحة في محركات البحث</div>
                        </div>
                    </div>
                </div>
            </form>
        </main>
    </div>

    <script>
        // حساب السعر النهائي
        function calculateFinalPrice() {
            const price = parseFloat(document.getElementById('price').value) || 0;
            const discountPercentage = parseFloat(document.getElementById('discount_percentage').value) || 0;
            const discountAmount = parseFloat(document.getElementById('discount_amount').value) || 0;
            
            const percentageDiscount = price * (discountPercentage / 100);
            const finalDiscount = Math.max(percentageDiscount, discountAmount);
            const finalPrice = price - finalDiscount;
            
            document.getElementById('final-price').textContent = finalPrice.toFixed(2) + ' ج.م';
        }

        // تحديث السعر عند تغيير القيم
        document.getElementById('price').addEventListener('input', calculateFinalPrice);
        document.getElementById('discount_percentage').addEventListener('input', calculateFinalPrice);
        document.getElementById('discount_amount').addEventListener('input', calculateFinalPrice);

        // حساب السعر الأولي
        calculateFinalPrice();

        // إنشاء رابط SEO تلقائياً من العنوان
        document.getElementById('title').addEventListener('input', function() {
            const slugInput = document.getElementById('slug');
            if (!slugInput.value || slugInput.dataset.manual !== 'true') {
                const slug = this.value
                    .toLowerCase()
                    .replace(/[^a-z0-9\u0600-\u06FF\s]/g, '')
                    .replace(/\s+/g, '-')
                    .replace(/-+/g, '-')
                    .replace(/^-+|-+$/g, '');
                slugInput.value = slug;
            }
        });

        // تحديد إذا كان المستخدم يدخل الرابط يدوياً
        document.getElementById('slug').addEventListener('input', function() {
            this.dataset.manual = 'true';
        });

        // التحقق من صحة النموذج
        document.getElementById('product-form').addEventListener('submit', function(e) {
            let isValid = true;
            
            const requiredFields = this.querySelectorAll('[required]');
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.style.borderColor = '#dc2626';
                } else {
                    field.style.borderColor = '#e2e8f0';
                }
            });
            
            const price = parseFloat(document.getElementById('price').value);
            if (price <= 0) {
                isValid = false;
                document.getElementById('price').style.borderColor = '#dc2626';
            }
            
            if (!isValid) {
                e.preventDefault();
                alert('يرجى ملء جميع الحقول المطلوبة بشكل صحيح');
            }
        });
        
        // التحقق من حجم الصور قبل الرفع
        document.getElementById('main_image').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file && file.size > 5 * 1024 * 1024) {
                alert('حجم الصورة كبير جداً. الحد الأقصى 5MB');
                this.value = '';
            }
        });
        
        document.getElementById('gallery_images').addEventListener('change', function(e) {
            const files = e.target.files;
            if (files.length > 5) {
                alert('يمكنك رفع 5 صور كحد أقصى');
                this.value = '';
                return;
            }
            
            for (let i = 0; i < files.length; i++) {
                if (files[i].size > 5 * 1024 * 1024) {
                    alert('حجم الصورة ' + files[i].name + ' كبير جداً. الحد الأقصى 5MB');
                    this.value = '';
                    return;
                }
            }
        });
    </script>
</body>
</html>