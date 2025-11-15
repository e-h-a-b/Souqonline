<?php
/**
 * صفحة إدارة التقييمات
 */
session_start();
require_once '../config.php';
require_once '../functions.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// معالجة الموافقة على التقييم
if (isset($_GET['approve']) && is_numeric($_GET['approve'])) {
    $review_id = $_GET['approve'];
    
    try {
        $stmt = $pdo->prepare("UPDATE reviews SET is_approved = 1 WHERE id = ?");
        $stmt->execute([$review_id]);
        
        // تحديث تقييم المنتج
        $stmt = $pdo->prepare("SELECT product_id FROM reviews WHERE id = ?");
        $stmt->execute([$review_id]);
        $review = $stmt->fetch();
        
        if ($review) {
            $stmt = $pdo->prepare("CALL update_product_rating(?)");
            $stmt->execute([$review['product_id']]);
        }
        
        logActivity('review_approved', "تمت الموافقة على التقييم #$review_id", $_SESSION['admin_id']);
        header('Location: reviews.php?success=' . urlencode('تمت الموافقة على التقييم بنجاح'));
        exit;
        
    } catch (Exception $e) {
        header('Location: reviews.php?error=' . urlencode('حدث خطأ أثناء الموافقة على التقييم'));
        exit;
    }
}

// معالجة حذف التقييم
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $review_id = $_GET['delete'];
    
    try {
        $stmt = $pdo->prepare("SELECT product_id FROM reviews WHERE id = ?");
        $stmt->execute([$review_id]);
        $review = $stmt->fetch();
        
        $stmt = $pdo->prepare("DELETE FROM reviews WHERE id = ?");
        $stmt->execute([$review_id]);
        
        // تحديث تقييم المنتج
        if ($review) {
            $stmt = $pdo->prepare("CALL update_product_rating(?)");
            $stmt->execute([$review['product_id']]);
        }
        
        logActivity('review_deleted', "تم حذف التقييم #$review_id", $_SESSION['admin_id']);
        header('Location: reviews.php?success=' . urlencode('تم حذف التقييم بنجاح'));
        exit;
        
    } catch (Exception $e) {
        header('Location: reviews.php?error=' . urlencode('حدث خطأ أثناء الحذف'));
        exit;
    }
}

// البحث والتصفية
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$rating = $_GET['rating'] ?? '';

$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(r.comment LIKE ? OR p.title LIKE ? OR c.first_name LIKE ? OR c.last_name LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

if (!empty($status) && $status != 'all') {
    if ($status == 'approved') {
        $where[] = "r.is_approved = 1";
    } elseif ($status == 'pending') {
        $where[] = "r.is_approved = 0";
    }
}

if (!empty($rating) && $rating != 'all') {
    $where[] = "r.rating = ?";
    $params[] = $rating;
}

$where_sql = '';
if (!empty($where)) {
    $where_sql = 'WHERE ' . implode(' AND ', $where);
}

// جلب التقييمات
$stmt = $pdo->prepare("
    SELECT r.*, p.title as product_title, p.main_image as product_image,
           CONCAT(c.first_name, ' ', c.last_name) as customer_name,
           c.email as customer_email
    FROM reviews r
    LEFT JOIN products p ON r.product_id = p.id
    LEFT JOIN customers c ON r.customer_id = c.id
    $where_sql
    ORDER BY r.created_at DESC
");
$stmt->execute($params);
$reviews = $stmt->fetchAll();

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة التقييمات - لوحة التحكم</title>
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
        .btn-sm { padding: 6px 12px; font-size: 13px; }
        
        .card {
            background: #fff;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            overflow: hidden;
        }
        
        .review-item {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
            background: #fff;
        }
        
        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
        }
        
        .reviewer-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .reviewer-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: #fff;
            font-size: 16px;
        }
        
        .review-details {
            flex: 1;
        }
        
        .reviewer-name {
            font-weight: 600;
            margin-bottom: 4px;
        }
        
        .review-product {
            color: #2563eb;
            text-decoration: none;
            font-weight: 500;
        }
        
        .review-product:hover {
            text-decoration: underline;
        }
        
        .review-rating {
            color: #fbbf24;
            margin-bottom: 8px;
        }
        
        .review-title {
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 16px;
        }
        
        .review-comment {
            color: #475569;
            line-height: 1.6;
            margin-bottom: 15px;
        }
        
        .review-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 14px;
            color: #64748b;
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
        
        .verified-badge {
            background: #16a34a;
            color: white;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 10px;
            margin-right: 5px;
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
                    <h1>إدارة التقييمات</h1>
                    <p style="color: #64748b; margin-top: 5px;">عرض ومراجعة تقييمات العملاء</p>
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

            <!-- Filters -->
            <div class="filters">
                <form method="get" action="reviews.php">
                    <div class="filter-row">
                        <div class="form-group">
                            <label for="search">بحث</label>
                            <input type="text" id="search" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="ابحث في التعليقات أو أسماء العملاء...">
                        </div>
                        <div class="form-group">
                            <label for="status">الحالة</label>
                            <select id="status" name="status">
                                <option value="all">جميع التقييمات</option>
                                <option value="approved" <?= $status == 'approved' ? 'selected' : '' ?>>مقبول</option>
                                <option value="pending" <?= $status == 'pending' ? 'selected' : '' ?>>قيد المراجعة</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="rating">التقييم</label>
                            <select id="rating" name="rating">
                                <option value="all">جميع التقييمات</option>
                                <option value="5" <?= $rating == '5' ? 'selected' : '' ?>>5 نجوم</option>
                                <option value="4" <?= $rating == '4' ? 'selected' : '' ?>>4 نجوم</option>
                                <option value="3" <?= $rating == '3' ? 'selected' : '' ?>>3 نجوم</option>
                                <option value="2" <?= $rating == '2' ? 'selected' : '' ?>>2 نجوم</option>
                                <option value="1" <?= $rating == '1' ? 'selected' : '' ?>>1 نجمة</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter"></i>
                                تصفية
                            </button>
                            <a href="reviews.php" class="btn btn-secondary">
                                <i class="fas fa-refresh"></i>
                                إعادة تعيين
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Reviews List -->
            <div class="card">
                <div class="card-header">
                    <h2>التقييمات (<?= count($reviews) ?>)</h2>
                </div>

                <?php if (empty($reviews)): ?>
                    <p style="text-align: center; color: #64748b; padding: 40px;">لا توجد تقييمات</p>
                <?php else: ?>
                    <?php foreach ($reviews as $review): ?>
                        <div class="review-item">
                            <div class="review-header">
                                <div class="reviewer-info">
                                    <div class="reviewer-avatar">
                                        <?= mb_substr($review['customer_name'] ?: 'زائر', 0, 1) ?>
                                    </div>
                                    <div class="review-details">
                                        <div class="reviewer-name">
                                            <?= htmlspecialchars($review['customer_name'] ?: 'زائر') ?>
                                            <?php if ($review['is_verified_purchase']): ?>
                                                <span class="verified-badge" title="شراء موثق">
                                                    <i class="fas fa-check"></i>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <a href="../product.php?id=<?= $review['product_id'] ?>" class="review-product" target="_blank">
                                            <?= htmlspecialchars($review['product_title']) ?>
                                        </a>
                                        <div class="review-rating">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star<?= $i <= $review['rating'] ? '' : '-o' ?>"></i>
                                            <?php endfor; ?>
                                            <span style="color: #64748b; margin-right: 5px;">(<?= $review['rating'] ?>)</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="review-status">
                                    <?php if ($review['is_approved']): ?>
                                        <span class="badge badge-success">مقبول</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">قيد المراجعة</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <?php if ($review['title']): ?>
                                <div class="review-title"><?= htmlspecialchars($review['title']) ?></div>
                            <?php endif; ?>

                            <?php if ($review['comment']): ?>
                                <div class="review-comment"><?= nl2br(htmlspecialchars($review['comment'])) ?></div>
                            <?php endif; ?>

                            <div class="review-meta">
                                <span><?= date('Y-m-d H:i', strtotime($review['created_at'])) ?></span>
                                <div class="actions">
                                    <?php if (!$review['is_approved']): ?>
                                        <a href="reviews.php?approve=<?= $review['id'] ?>" class="action-btn btn-success" title="قبول التقييم">
                                            <i class="fas fa-check"></i> قبول
                                        </a>
                                    <?php endif; ?>
                                    <a href="reviews.php?delete=<?= $review['id'] ?>" class="action-btn btn-danger" title="حذف التقييم" onclick="return confirm('هل أنت متأكد من حذف هذا التقييم؟')">
                                        <i class="fas fa-trash"></i> حذف
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>