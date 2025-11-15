<?php
session_start();
require_once '../functions.php';

// التحقق من صلاحية المدير
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$page_title = "إدارة العروض الترويجية";

// معالجة النماذج
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_offer'])) {
        $result = addProductOffer($_POST);
        if ($result['success']) {
            $success_message = $result['message'];
        } else {
            $error_message = $result['message'];
        }
    } elseif (isset($_POST['update_offer'])) {
        $result = updateProductOffer($_POST['offer_id'], $_POST);
        if ($result['success']) {
            $success_message = $result['message'];
        } else {
            $error_message = $result['message'];
        }
    } elseif (isset($_POST['delete_offer'])) {
        $result = deleteProductOffer($_POST['offer_id']);
        if ($result['success']) {
            $success_message = $result['message'];
        } else {
            $error_message = $result['message'];
        }
    } elseif (isset($_POST['toggle_offer'])) {
        $result = toggleOfferStatus($_POST['offer_id']);
        if ($result['success']) {
            $success_message = $result['message'];
        } else {
            $error_message = $result['message'];
        }
    }
}

// جلب جميع العروض
$offers = getAllProductOffers();
$products = getProducts(['limit' => 1000]);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl"> 
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الخصائص - <?= getSetting('store_name') ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - <?= getSetting('store_name') ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <style>
	.container, .container-fluid, .container-lg, .container-md, .container-sm, .container-xl, .container-xxl{
		width: 90%  !important;
		padding-right: 200px !important;
	}
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
        
        .feature-section {
            background: #fff;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 20px;
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
        .btn-secondary { background: #6b7280; color: #fff; }
        .btn-secondary:hover { background: #4b5563; }
        .btn-danger { background: #dc2626; color: #fff; }
        .btn-danger:hover { background: #b91c1c; }
        .btn-success { background: #059669; color: #fff; }
        .btn-success:hover { background: #047857; }
        .btn-warning { background: #d97706; color: #fff; }
        .btn-warning:hover { background: #b45309; }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
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
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-accepted { background: #dcfce7; color: #166534; }
        .status-rejected { background: #fee2e2; color: #991b1b; }
        .status-counter { background: #dbeafe; color: #1e40af; }
        .status-new { background: #dcfce7; color: #166534; }
        .status-used { background: #fef3c7; color: #92400e; }
        .status-refurbished { background: #dbeafe; color: #1e40af; }
        .status-needs_repair { background: #fee2e2; color: #991b1b; }
        
        .offer-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            margin: 2px;
        }
        .offer-points { background: #fef3c7; color: #92400e; }
        .offer-coupon { background: #dbeafe; color: #1e40af; }
        .offer-gift { background: #fce7f3; color: #be185d; }
        .offer-discount { background: #dcfce7; color: #166534; }
        
        .success-message {
            background: #dcfce7;
            color: #166534;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #bbf7d0;
        }
        
        .error-message {
            background: #fee2e2;
            color: #991b1b;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #fecaca;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        input, select, textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
        }
        
        .toggle-container {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }
        
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }
        
        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        
        input:checked + .toggle-slider {
            background-color: #2196F3;
        }
        
        input:checked + .toggle-slider:before {
            transform: translateX(26px);
        }
        
        .grid-2 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .grid-3 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .grid-4 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
        }
        
        .tab-container {
            margin-bottom: 20px;
        }
        
        .tab-buttons {
            display: flex;
            background: #f8fafc;
            border-radius: 8px;
            padding: 4px;
            margin-bottom: 20px;
        }
        
        .tab-btn {
            flex: 1;
            padding: 12px 20px;
            text-align: center;
            background: none;
            border: none;
            cursor: pointer;
            border-radius: 6px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .tab-btn.active {
            background: #fff;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .product-card {
            background: #f8fafc;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            border: 1px solid #e2e8f0;
        }
        
        .product-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .product-features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
            margin-top: 10px;
        }
        
        .feature-item {
            background: white;
            padding: 8px 12px;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
        }
    </style>
    <style>
        .product-thumb {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border-radius: 5px;
        }
        .stats-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        .stats-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding: 1rem 0;
            border-bottom: 1px solid #eee;
        }
    </style>
</head>
<body>
        <!-- Sidebar -->
        <?php include 'sidebar.php'; ?>
    <div class="container-fluid">
        <!-- رسائل التنبيه -->
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
                <?= $success_message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
                <?= $error_message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-12">
                <div class="page-header">
                    <h1 class="page-title">
                        <i class="fas fa-gift"></i>
                        إدارة العروض الترويجية
                    </h1>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addOfferModal">
                        <i class="fas fa-plus"></i> إضافة عرض جديد
                    </button>
                </div>
            </div>
        </div>

        <!-- إحصائيات العروض -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon bg-primary">
                        <i class="fas fa-gift"></i>
                    </div>
                    <div class="stats-info">
                        <h3><?= count($offers) ?></h3>
                        <span>إجمالي العروض</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon bg-success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stats-info">
                        <h3><?= count(array_filter($offers, fn($o) => $o['is_active'])) ?></h3>
                        <span>عروض نشطة</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon bg-warning">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stats-info">
                        <h3><?= count(array_filter($offers, fn($o) => !$o['is_active'])) ?></h3>
                        <span>عروض غير نشطة</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon bg-info">
                        <i class="fas fa-fire"></i>
                    </div>
                    <div class="stats-info">
                        <h3><?= array_sum(array_column($offers, 'usage_count')) ?></h3>
                        <span>مرات الاستخدام</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- جدول العروض -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">قائمة العروض</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="offersTable">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>المنتج</th>
                                        <th>نوع العرض</th>
                                        <th>العنوان</th>
                                        <th>الحالة</th>
                                        <th>تاريخ البداية</th>
                                        <th>تاريخ النهاية</th>
                                        <th>مرات الاستخدام</th>
                                        <th>تاريخ الإضافة</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($offers)): ?>
                                        <tr>
                                            <td colspan="10" class="text-center text-muted py-4">
                                                <i class="fas fa-gift fa-2x mb-3"></i>
                                                <br>
                                                لا توجد عروض حالياً
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($offers as $index => $offer): ?>
                                            <?php $product = getProduct($offer['product_id']); ?>
                                            <tr>
                                                <td><?= $index + 1 ?></td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <img src="../<?= $product['main_image'] ?: 'assets/images/placeholder.jpg' ?>" 
                                                             alt="<?= $product['title'] ?>" 
                                                             class="product-thumb me-2">
                                                        <div>
                                                            <strong><?= $product['title'] ?></strong>
                                                            <br>
                                                            <small class="text-muted"><?= formatPrice($product['final_price']) ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info">
                                                        <?= getOfferTypeText($offer['offer_type']) ?>
                                                    </span>
                                                </td>
                                                <td><?= $offer['title'] ?></td>
                                                <td>
                                                    <span class="badge <?= $offer['is_active'] ? 'bg-success' : 'bg-secondary' ?>">
                                                        <?= $offer['is_active'] ? 'نشط' : 'غير نشط' ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?= $offer['start_date'] ? date('Y-m-d', strtotime($offer['start_date'])) : 'لا يوجد' ?>
                                                </td>
                                                <td>
                                                    <?= $offer['end_date'] ? date('Y-m-d', strtotime($offer['end_date'])) : 'لا يوجد' ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-primary"><?= $offer['usage_count'] ?></span>
                                                </td>
                                                <td><?= date('Y-m-d', strtotime($offer['created_at'])) ?></td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <button class="btn btn-outline-primary" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#editOfferModal"
                                                                onclick="loadOfferData(<?= $offer['id'] ?>)">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="offer_id" value="<?= $offer['id'] ?>">
                                                            <button type="submit" name="toggle_offer" 
                                                                    class="btn btn-outline-<?= $offer['is_active'] ? 'warning' : 'success' ?>">
                                                                <i class="fas fa-<?= $offer['is_active'] ? 'pause' : 'play' ?>"></i>
                                                            </button>
                                                        </form>
                                                        <button class="btn btn-outline-danger" 
                                                                onclick="confirmDelete(<?= $offer['id'] ?>, '<?= addslashes($offer['title']) ?>')">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal إضافة عرض -->
    <div class="modal fade" id="addOfferModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-plus"></i> إضافة عرض جديد
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">المنتج</label>
                                    <select name="product_id" class="form-select" required>
                                        <option value="">اختر المنتج</option>
                                        <?php foreach ($products as $product): ?>
                                            <option value="<?= $product['id'] ?>">
                                                <?= $product['title'] ?> - <?= formatPrice($product['final_price']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">نوع العرض</label>
                                    <select name="offer_type" class="form-select" required>
                                        <option value="buy2_get1">اشتري 2 واحصل على 1 مجاناً</option>
                                        <option value="discount">خصم</option>
                                        <option value="free_shipping">شحن مجاني</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">عنوان العرض</label>
                                    <input type="text" name="title" class="form-control" required 
                                           placeholder="مثال: عرض خاص - اشتري 2 واحصل على 1 مجاناً">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">الحد الأدنى للكمية</label>
                                    <input type="number" name="min_quantity" class="form-control" 
                                           value="3" min="3" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">وصف العرض</label>
                            <textarea name="description" class="form-control" rows="3" 
                                      placeholder="وصف تفصيلي للعرض..."></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">تاريخ البداية</label>
                                    <input type="datetime-local" name="start_date" class="form-control">
                                    <div class="form-text">اتركه فارغاً لبدء العرض فوراً</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">تاريخ النهاية</label>
                                    <input type="datetime-local" name="end_date" class="form-control">
                                    <div class="form-text">اتركه فارغاً لعدم وجود نهاية</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">الحد الأقصى للاستخدام</label>
                                    <input type="number" name="usage_limit" class="form-control" 
                                           placeholder="اتركه فارغاً لعدم وجود حد">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">الحالة</label>
                                    <select name="is_active" class="form-select">
                                        <option value="1">نشط</option>
                                        <option value="0">غير نشط</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" name="add_offer" class="btn btn-primary">إضافة العرض</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal تعديل عرض -->
    <div class="modal fade" id="editOfferModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="offer_id" id="edit_offer_id">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-edit"></i> تعديل العرض
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body" id="editOfferContent">
                        <!-- سيتم تحميل المحتوى هنا عبر JavaScript -->
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" name="update_offer" class="btn btn-primary">تحديث العرض</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- نموذج حذف العرض -->
    <form method="POST" id="deleteForm" style="display: none;">
        <input type="hidden" name="offer_id" id="delete_offer_id">
        <input type="hidden" name="delete_offer" value="1">
    </form>

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <script>
    function loadOfferData(offerId) {
        fetch(`ajax/get_offer.php?id=${offerId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('edit_offer_id').value = data.offer.id;
                    document.getElementById('editOfferContent').innerHTML = data.html;
                } else {
                    alert('حدث خطأ في تحميل بيانات العرض');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('حدث خطأ في الاتصال');
            });
    }

    function confirmDelete(offerId, offerTitle) {
        if (confirm(`هل أنت متأكد من حذف العرض "${offerTitle}"؟`)) {
            document.getElementById('delete_offer_id').value = offerId;
            document.getElementById('deleteForm').submit();
        }
    }

    // تفعيل dataTables للجدول
    $(document).ready(function() {
        $('#offersTable').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/ar.json'
            },
            order: [[0, 'desc']]
        });
    });
    </script>
	<script>
$(document).ready(function() {
    $('#offersTable tr').each(function() {
        console.log('Row has', $(this).find('td').length, 'columns');
    });
});
</script>

</body>
</html>