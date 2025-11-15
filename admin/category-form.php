<?php
/**
 * صفحة إضافة/تعديل الفئة
 */
session_start();
require_once '../config.php';
require_once '../functions.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$category_id = $_GET['id'] ?? 0;
$category = null;
$parent_categories = [];
$error = '';
$success = '';

// جلب الفئات الرئيسية
$stmt = $pdo->query("SELECT id, name FROM categories WHERE parent_id IS NULL AND is_active = 1 ORDER BY name");
$parent_categories = $stmt->fetchAll();

// جلب بيانات الفئة إذا كان تعديل
if ($category_id) {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$category_id]);
    $category = $stmt->fetch();
    
    if (!$category) {
        $error = 'الفئة غير موجودة';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = cleanInput($_POST['name'] ?? '');
    $slug = cleanInput($_POST['slug'] ?? '');
    $description = cleanInput($_POST['description'] ?? '');
    $parent_id = $_POST['parent_id'] ? intval($_POST['parent_id']) : null;
    $display_order = intval($_POST['display_order'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // التحقق من البيانات
    if (empty($name) || empty($slug)) {
        $error = 'يرجى ملء الحقول المطلوبة (الاسم ورابط SEO)';
    } else {
        try {
            if ($category_id && $category) {
                // تحديث الفئة
                $stmt = $pdo->prepare("
                    UPDATE categories SET 
                        name = ?, slug = ?, description = ?, parent_id = ?, 
                        display_order = ?, is_active = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $name, $slug, $description, $parent_id, 
                    $display_order, $is_active, $category_id
                ]);
                
                $action = 'updated';
                $message = 'تم تحديث الفئة بنجاح';
            } else {
                // إضافة فئة جديدة
                $stmt = $pdo->prepare("
                    INSERT INTO categories (
                        name, slug, description, parent_id, display_order, is_active
                    ) VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $name, $slug, $description, $parent_id, $display_order, $is_active
                ]);
                
                $category_id = $pdo->lastInsertId();
                $action = 'created';
                $message = 'تم إضافة الفئة بنجاح';
            }
            
            logActivity("category_{$action}", $message, $_SESSION['admin_id']);
            $success = $message;
            
            // إعادة توجيه بعد النجاح
            header('Location: category-form.php?id=' . $category_id . '&success=' . urlencode($message));
            exit;
            
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                $error = 'رابط SEO مستخدم مسبقاً';
            } else {
                $error = 'حدث خطأ أثناء حفظ الفئة: ' . $e->getMessage();
            }
        }
    }
}

// جلب بيانات الفئة مرة أخرى بعد التعديل
if ($category_id && !$category) {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$category_id]);
    $category = $stmt->fetch();
}

$success = $_GET['success'] ?? $success;
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $category ? 'تعديل الفئة' : 'إضافة فئة جديدة' ?> - لوحة التحكم</title>
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
            max-width: 600px;
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
            max-width: 600px;
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
                <a href="categories.php" class="menu-item active">
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
                    <h1><?= $category ? 'تعديل الفئة' : 'إضافة فئة جديدة' ?></h1>
                    <p style="color: #64748b; margin-top: 5px;"><?= $category ? 'تعديل بيانات الفئة' : 'إضافة فئة جديدة للمتجر' ?></p>
                </div>
                <div>
                    <a href="categories.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-right"></i>
                        رجوع
                    </a>
                    <button type="submit" form="category-form" class="btn btn-success">
                        <i class="fas fa-save"></i>
                        حفظ الفئة
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

            <form id="category-form" method="post" action="category-form.php<?= $category_id ? '?id=' . $category_id : '' ?>">
                <div class="card">
                    <h2 style="margin-bottom: 20px;">معلومات الفئة</h2>
                    
                    <div class="form-group">
                        <label for="name">اسم الفئة *</label>
                        <input type="text" id="name" name="name" 
                               value="<?= htmlspecialchars($category['name'] ?? ($_POST['name'] ?? '')) ?>" required>
                        <div class="form-help">اسم الفئة كما يظهر للعملاء</div>
                    </div>

                    <div class="form-group">
                        <label for="slug">رابط SEO *</label>
                        <input type="text" id="slug" name="slug" 
                               value="<?= htmlspecialchars($category['slug'] ?? ($_POST['slug'] ?? '')) ?>" required>
                        <div class="form-help">رابط فريد للفئة في محركات البحث</div>
                    </div>

                    <div class="form-group">
                        <label for="parent_id">الفئة الرئيسية</label>
                        <select id="parent_id" name="parent_id">
                            <option value="">فئة رئيسية</option>
                            <?php foreach ($parent_categories as $parent): ?>
                                <?php if (!($category_id && $parent['id'] == $category_id)): ?>
                                    <option value="<?= $parent['id'] ?>" 
                                        <?= ($category['parent_id'] ?? ($_POST['parent_id'] ?? '')) == $parent['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($parent['name']) ?>
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-help">اختياري - يمكن جعل هذه الفئة فرعية لفئة أخرى</div>
                    </div>

                    <div class="form-group">
                        <label for="description">الوصف</label>
                        <textarea id="description" name="description" rows="4"><?= htmlspecialchars($category['description'] ?? ($_POST['description'] ?? '')) ?></textarea>
                        <div class="form-help">وصف الفئة يظهر في صفحة الفئة</div>
                    </div>

                    <div class="form-group">
                        <label for="display_order">ترتيب العرض</label>
                        <input type="number" id="display_order" name="display_order" 
                               value="<?= htmlspecialchars($category['display_order'] ?? ($_POST['display_order'] ?? 0)) ?>" 
                               min="0">
                        <div class="form-help">رقم يحدد ترتيب ظهور الفئة (الأصغر يظهر أولاً)</div>
                    </div>

                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" id="is_active" name="is_active" 
                                   value="1" <?= ($category['is_active'] ?? ($_POST['is_active'] ?? 1)) ? 'checked' : '' ?>>
                            <label for="is_active">فئة نشطة</label>
                        </div>
                        <div class="form-help">عرض الفئة للعملاء</div>
                    </div>
                </div>
            </form>
        </main>
    </div>

    <script>
        // إنشاء رابط SEO تلقائياً من الاسم
        document.getElementById('name').addEventListener('input', function() {
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
        document.getElementById('category-form').addEventListener('submit', function(e) {
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
            
            if (!isValid) {
                e.preventDefault();
                alert('يرجى ملء جميع الحقول المطلوبة');
            }
        });
    </script>
</body>
</html>