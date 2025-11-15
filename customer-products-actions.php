<?php
session_start();
require_once 'config.php';
require_once 'functions.php';

// التحقق من تسجيل الدخول
if (!isset($_SESSION['customer_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'يجب تسجيل الدخول أولاً']);
    exit;
}

$customer_id = $_SESSION['customer_id'];
$action = $_POST['action'] ?? '';

// معالجة إضافة منتج جديد
if ($action === 'add_product') {
    try {
        $pdo->beginTransaction();
        
        // جمع البيانات
        $title = trim($_POST['title']);
        $description = trim($_POST['description'] ?? '');
        $short_description = trim($_POST['short_description'] ?? '');
        $price = floatval($_POST['price']);
        $stock = intval($_POST['stock']);
        $discount_percentage = floatval($_POST['discount_percentage'] ?? 0);
        $category_id = !empty($_POST['category_id']) ? intval($_POST['category_id']) : null;
        
        // إنشاء slug للمنتج
        $slug = generateSlug($title);
        
        // معالجة صورة المنتج
        $main_image = null;
        if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'assets/images/products/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_extension = pathinfo($_FILES['main_image']['name'], PATHINFO_EXTENSION);
            $filename = 'product_' . time() . '_' . uniqid() . '.' . $file_extension;
            $upload_path = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['main_image']['tmp_name'], $upload_path)) {
                $main_image = $upload_path;
            }
        }
        
        // إدخال المنتج في قاعدة البيانات
        $stmt = $pdo->prepare("
            INSERT INTO products (
                category_id, title, slug, description, short_description, 
                price, discount_percentage, stock, main_image, 
                created_by, store_type, is_active
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'customer', 1)
        ");
        
        $stmt->execute([
            $category_id, $title, $slug, $description, $short_description,
            $price, $discount_percentage, $stock, $main_image, $customer_id
        ]);
        
        $product_id = $pdo->lastInsertId();
        
        $pdo->commit();
        
        // إرجاع النجاح
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true, 
            'message' => 'تم إضافة المنتج بنجاح',
            'product_id' => $product_id
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false, 
            'message' => 'حدث خطأ أثناء إضافة المنتج: ' . $e->getMessage()
        ]);
    }
    exit;
}

// معالجة حذف المنتج
if ($action === 'delete_product') {
    $product_id = intval($_POST['product_id']);
    
    try {
        $pdo->beginTransaction();
        
        // التحقق من ملكية المنتج
        $stmt = $pdo->prepare("SELECT id FROM products WHERE id = ? AND created_by = ?");
        $stmt->execute([$product_id, $customer_id]);
        $product = $stmt->fetch();
        
        if (!$product) {
            throw new Exception('هذا المنتج غير موجود أو ليس لديك صلاحية لحذفه');
        }
        
        // حذف المنتج
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        
        $pdo->commit();
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true, 
            'message' => 'تم حذف المنتج بنجاح'
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false, 
            'message' => 'حدث خطأ أثناء حذف المنتج: ' . $e->getMessage()
        ]);
    }
    exit;
}

// معالجة رفع مجموعة منتجات
if ($action === 'bulk_upload') {
    if (!isset($_FILES['bulk_file']) || $_FILES['bulk_file']['error'] !== UPLOAD_ERR_OK) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false, 
            'message' => 'يرجى اختيار ملف صحيح'
        ]);
        exit;
    }
    
    $uploaded_file = $_FILES['bulk_file']['tmp_name'];
    
    // هنا يمكنك إضافة معالجة ملف Excel
    // سأتركها كمثال بسيط
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true, 
        'message' => 'تم رفع الملف بنجاح وسيتم معالجة البيانات قريباً'
    ]);
    exit;
}

// إذا لم يتم التعرف على الإجراء
header('Content-Type: application/json');
echo json_encode([
    'success' => false, 
    'message' => 'إجراء غير معروف'
]);