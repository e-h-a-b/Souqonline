<?php
//require_once 'includes/admin_header.php';
require_once '../functions.php';

// التحقق من الصلاحيات
if ($_SESSION['admin_role'] !== 'super_admin' && $_SESSION['admin_role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

$action = $_GET['action'] ?? 'list';
$message = '';

// معالجة الإجراءات
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($_POST['action']) {
        case 'create_bulk':
            $customer_ids = $_POST['customer_ids'] ?? [];
            $product_id = $_POST['product_id'] ?? null;
            $reward_type = $_POST['reward_type'] ?? 'points';
            $reward_value = $_POST['reward_value'] ?? 0;
            $reward_description = $_POST['reward_description'] ?? '';
            $cards_per_customer = $_POST['cards_per_customer'] ?? 1;
            $expires_at = $_POST['expires_at'] ?? '';
            
            if (!empty($customer_ids) && $product_id) {
                $data = [
                    'customer_ids' => $customer_ids,
                    'product_id' => $product_id,
                    'reward_type' => $reward_type,
                    'reward_value' => $reward_value,
                    'reward_description' => $reward_description,
                    'cards_per_customer' => $cards_per_customer,
                    'expires_at' => $expires_at ? date('Y-m-d H:i:s', strtotime($expires_at)) : null
                ];
                
                $created_count = createBulkScratchCards($data);
                if ($created_count) {
                    $message = "تم إنشاء $created_count كارت خربشة بنجاح";
                    logAdminActivity("تم إنشاء $created_count كارت خربشة");
                } else {
                    $message = "فشل في إنشاء كروت الخربشة";
                }
            }
            break;
            
        case 'delete_card':
            $card_id = $_POST['card_id'] ?? 0;
            if (deleteScratchCard($card_id)) {
                $message = "تم حذف الكارت بنجاح";
                logAdminActivity("تم حذف كارت الخربشة #$card_id");
            } else {
                $message = "فشل في حذف الكارت";
            }
            break;
    }
}

// الحصول على البيانات
$filters = [
    'customer_id' => $_GET['customer_id'] ?? '',
    'product_id' => $_GET['product_id'] ?? '',
    'reward_type' => $_GET['reward_type'] ?? '',
    'is_scratched' => $_GET['is_scratched'] ?? '',
    'is_claimed' => $_GET['is_claimed'] ?? '',
    'search' => $_GET['search'] ?? ''
];

$scratch_cards = getAllScratchCards($filters);
$stats = getScratchCardsStats();
$customers = getAllCustomers();
$products = getAllProducts();
$categories = getCategories();
?>

<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الخصائص - <?= getSetting('store_name') ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
	<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
	<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
<!-- أضف في الـ head -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
* { 
    margin: 0; 
    padding: 0; 
    box-sizing: border-box; 
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: #f8fafc;
    color: #334155;
    direction: rtl;
}
.row{
	display:flex;
}
.admin-wrapper { 
    display: flex; 
    min-height: 100vh; 
}

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

.sidebar-menu { 
    padding: 20px 0; 
}

.menu-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 20px;
    color: rgba(255,255,255,0.8);
    text-decoration: none;
    transition: all 0.3s;
}

.menu-item:hover, 
.menu-item.active {
    background: rgba(255,255,255,0.1);
    color: #fff;
}

.menu-item i { 
    width: 20px; 
}

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

.page-title h1 { 
    font-size: 28px; 
    color: #1e293b; 
}

.feature-section {
    background: #fff;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

/* Buttons */
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

.btn-primary { 
    background: #2563eb; 
    color: #fff; 
}

.btn-primary:hover { 
    background: #1d4ed8; 
}

.btn-secondary { 
    background: #6b7280; 
    color: #fff; 
}

.btn-secondary:hover { 
    background: #4b5563; 
}

.btn-danger { 
    background: #dc2626; 
    color: #fff; 
}

.btn-danger:hover { 
    background: #b91c1c; 
}

.btn-success { 
    background: #059669; 
    color: #fff; 
}

.btn-success:hover { 
    background: #047857; 
}

.btn-warning { 
    background: #d97706; 
    color: #fff; 
}

.btn-warning:hover { 
    background: #b45309; 
}

/* Tables */
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

tr:hover { 
    background: #f8fafc; 
}

/* Status Badges */
.status-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.status-pending { 
    background: #fef3c7; 
    color: #92400e; 
}

.status-accepted { 
    background: #dcfce7; 
    color: #166534; 
}

.status-rejected { 
    background: #fee2e2; 
    color: #991b1b; 
}

.status-new { 
    background: #dcfce7; 
    color: #166534; 
}

.status-used { 
    background: #fef3c7; 
    color: #92400e; 
}

.status-refurbished { 
    background: #dbeafe; 
    color: #1e40af; 
}

.status-needs_repair { 
    background: #fee2e2; 
    color: #991b1b; 
}

/* Offer Badges */
.offer-badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 600;
    margin: 2px;
}

.offer-points { 
    background: #fef3c7; 
    color: #92400e; 
}

.offer-coupon { 
    background: #dbeafe; 
    color: #1e40af; 
}

.offer-gift { 
    background: #fce7f3; 
    color: #be185d; 
}

.offer-discount { 
    background: #dcfce7; 
    color: #166534; 
}

/* Messages */
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

/* Form Elements */
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

/* Grid Layouts */
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

/* Tabs */
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

/* Cards */
.card {
    background: #fff;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.card-header {
    padding-bottom: 15px;
    margin-bottom: 15px;
    border-bottom: 1px solid #e2e8f0;
}

.card-header h3 {
    margin: 0;
    color: #1e293b;
}

/* Statistics Cards */
.stats-card {
    background: #fff;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    border-left: 4px solid #2563eb;
}

.stats-card.success {
    border-left-color: #059669;
}

.stats-card.info {
    border-left-color: #0ea5e9;
}

.stats-card.warning {
    border-left-color: #d97706;
}

/* Badges */
.badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.badge-primary { 
    background: #dbeafe; 
    color: #1e40af; 
}

.badge-success { 
    background: #dcfce7; 
    color: #166534; 
}

.badge-warning { 
    background: #fef3c7; 
    color: #92400e; 
}

.badge-secondary { 
    background: #f1f5f9; 
    color: #475569; 
}

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
}

.modal.show { 
    align-items: center;
    justify-content: center;
}

.modal-content {
    background: #fff;
    border-radius: 12px;
    width: 90%;
    max-width: 600px;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-header {
    padding: 20px;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-body {
    padding: 20px;
}

.modal-footer {
    padding: 20px;
    border-top: 1px solid #e2e8f0;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

/* Responsive */
@media (max-width: 768px) {
    .admin-wrapper {
        flex-direction: column;
    }
    
    .sidebar {
        width: 100%;
        height: auto;
        position: static;
    }
    
    .main-content {
        margin-right: 0;
        padding: 20px;
    }
    
    .grid-2,
    .grid-3,
    .grid-4 {
        grid-template-columns: 1fr;
    }
    
    .tab-buttons {
        flex-direction: column;
    }
}
    </style>
</head>
<body>

    <div class="admin-wrapper">
        <!-- Sidebar -->
		
        <!-- Sidebar -->
        <?php include 'sidebar.php'; ?>
		<!-- Main Content -->
        <main class="main-content">
<div class="container-fluid">
    <!-- رأس الصفحة -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-gift fa-fw"></i>
            إدارة كروت الخربشة
        </h1>
        <button class="btn btn-primary" onclick="showCreateModal()">
            <i class="fas fa-plus fa-fw"></i>
            إنشاء كروت جديدة
        </button>
    </div>

    <!-- رسائل التنبيه -->
    <?php if ($message): ?>
    <div class="alert alert-<?= strpos($message, 'نجاح') !== false ? 'success' : 'danger' ?> alert-dismissible fade show">
        <?= $message ?>
        <button type="button" class="close" data-dismiss="alert">
            <span>&times;</span>
        </button>
    </div>
    <?php endif; ?>

    <!-- بطاقات الإحصائيات -->
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                إجمالي الكروت
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= number_format($stats['total_cards'] ?? 0) ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-gift fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                الكروت المخدوشة
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= number_format($stats['scratched_cards'] ?? 0) ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-paint-brush fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                نسبة الخربشة
                            </div>
                            <div class="row no-gutters align-items-center">
                                <div class="col-auto">
                                    <div class="h5 mb-0 mr-3 font-weight-bold text-gray-800">
                                        <?= number_format($stats['scratch_rate'] ?? 0, 1) ?>%
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chart-line fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                النقاط الممنوحة
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= number_format($stats['total_points_given'] ?? 0) ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-coins fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- فلتر البحث -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">فلتر البحث</h6>
        </div>
        <div class="card-body">
            <form method="GET" class="row">
                <div class="col-md-3 mb-3">
                    <label>البحث</label>
                    <input type="text" name="search" class="form-control" value="<?= htmlspecialchars($filters['search']) ?>" 
                           placeholder="ابحث بالكود أو اسم العميل أو المنتج...">
                </div>
                <div class="col-md-2 mb-3">
                    <label>نوع المكافأة</label>
                    <select name="reward_type" class="form-control">
                        <option value="">الكل</option>
                        <option value="points" <?= $filters['reward_type'] === 'points' ? 'selected' : '' ?>>نقاط</option>
                        <option value="discount" <?= $filters['reward_type'] === 'discount' ? 'selected' : '' ?>>خصم</option>
                        <option value="gift" <?= $filters['reward_type'] === 'gift' ? 'selected' : '' ?>>هدية</option>
                        <option value="cash" <?= $filters['reward_type'] === 'cash' ? 'selected' : '' ?>>نقدي</option>
                    </select>
                </div>
                <div class="col-md-2 mb-3">
                    <label>حالة الخربشة</label>
                    <select name="is_scratched" class="form-control">
                        <option value="">الكل</option>
                        <option value="1" <?= $filters['is_scratched'] === '1' ? 'selected' : '' ?>>مخدوش</option>
                        <option value="0" <?= $filters['is_scratched'] === '0' ? 'selected' : '' ?>>غير مخدوش</option>
                    </select>
                </div>
                <div class="col-md-2 mb-3">
                    <label>حالة المطالبة</label>
                    <select name="is_claimed" class="form-control">
                        <option value="">الكل</option>
                        <option value="1" <?= $filters['is_claimed'] === '1' ? 'selected' : '' ?>>مطالب به</option>
                        <option value="0" <?= $filters['is_claimed'] === '0' ? 'selected' : '' ?>>غير مطالب به</option>
                    </select>
                </div>
                <div class="col-md-3 mb-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary mr-2">
                        <i class="fas fa-search fa-fw"></i> بحث
                    </button>
                    <a href="scratch_cards.php" class="btn btn-secondary">
                        <i class="fas fa-redo fa-fw"></i> إعادة تعيين
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- جدول كروت الخربشة -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">كروت الخربشة</h6>
            <span class="badge badge-primary"><?= count($scratch_cards) ?> كارت</span>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="scratchCardsTable" width="100%" cellspacing="0">
                    <thead class="bg-light">
                        <tr>
                            <th>كود الكارت</th>
                            <th>العميل</th>
                            <th>المنتج</th>
                            <th>نوع المكافأة</th>
                            <th>قيمة المكافأة</th>
                            <th>الحالة</th>
                            <th>تاريخ الإنشاء</th>
                            <th>تاريخ الانتهاء</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($scratch_cards)): ?>
                            <tr>
                                <td colspan="9" class="text-center py-4">
                                    <i class="fas fa-gift fa-2x text-gray-300 mb-3"></i>
                                    <p class="text-muted">لا توجد كروت خربشة</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($scratch_cards as $card): ?>
                                <tr>
                                    <td>
                                        <code><?= htmlspecialchars($card['card_code']) ?></code>
                                    </td>
                                    <td>
                                        <?php if ($card['first_name']): ?>
                                            <?= htmlspecialchars($card['first_name'] . ' ' . $card['last_name']) ?>
                                            <br><small class="text-muted"><?= htmlspecialchars($card['email']) ?></small>
                                        <?php else: ?>
                                            <span class="text-muted">غير معين</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($card['product_name'] ?? 'غير معين') ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?= getRewardTypeBadge($card['reward_type']) ?>">
                                            <?= getRewardTypeText($card['reward_type']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($card['reward_value']) ?></strong>
                                        <?php if ($card['reward_type'] === 'points'): ?>
                                            نقطة
                                        <?php elseif ($card['reward_type'] === 'discount'): ?>
                                            %
                                        <?php elseif ($card['reward_type'] === 'cash'): ?>
                                            ج.م
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($card['is_claimed']): ?>
                                            <span class="badge badge-success">تم المطالبة</span>
                                        <?php elseif ($card['is_scratched']): ?>
                                            <span class="badge badge-warning">مخدوش</span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary">غير مخدوش</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= date('Y-m-d H:i', strtotime($card['created_at'])) ?>
                                    </td>
                                    <td>
                                        <?= $card['expires_at'] ? date('Y-m-d H:i', strtotime($card['expires_at'])) : 'لا ينتهي' ?>
                                        <?php if ($card['expires_at'] && strtotime($card['expires_at']) < time()): ?>
                                            <br><small class="text-danger">منتهي</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-info" onclick="viewCardDetails(<?= $card['id'] ?>)" 
                                                    title="عرض التفاصيل">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <?php if (!$card['is_scratched']): ?>
                                                <button class="btn btn-danger" 
                                                        onclick="deleteCard(<?= $card['id'] ?>, '<?= htmlspecialchars($card['card_code']) ?>')"
                                                        title="حذف">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php endif; ?>
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

<!-- مودال إنشاء كروت جديدة -->
<div class="modal fade" id="createModal" tabindex="-1" aria-labelledby="createModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createModalLabel">
                    <i class="fas fa-plus-circle fa-fw"></i>
                    إنشاء كروت خربشة جديدة
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="create_bulk">
                
                <div class="modal-body">
                    <!-- محتوى المودال -->
					<!-- داخل <div class="modal-body"> في مودال الإنشاء -->
<div class="row">
    <div class="col-md-6 mb-3">
        <label>العملاء</label>
        <select name="customer_ids[]" class="form-control select2" multiple required>
            <?php foreach ($customers as $customer): ?>
                <option value="<?= $customer['id'] ?>">
                    <?= htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']) ?> - 
                    <?= htmlspecialchars($customer['email']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    
    <div class="col-md-6 mb-3">
        <label>المنتج</label>
        <select name="product_id" class="form-control" required>
            <option value="">اختر المنتج</option>
            <?php foreach ($products as $product): ?>
                <option value="<?= $product['id'] ?>">
                    <?= htmlspecialchars($product['title']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
</div>

<div class="row">
    <div class="col-md-4 mb-3">
        <label>نوع المكافأة</label>
        <select name="reward_type" class="form-control" onchange="updateRewardFields()" required>
            <option value="points">نقاط</option>
            <option value="discount">خصم</option>
            <option value="gift">هدية</option>
            <option value="cash">نقدي</option>
        </select>
    </div>
    
    <div class="col-md-4 mb-3">
        <label id="reward_value_label">قيمة النقاط</label>
        <input type="number" name="reward_value" class="form-control" step="1" min="1" required>
    </div>
    
    <div class="col-md-4 mb-3">
        <label>عدد الكروت لكل عميل</label>
        <input type="number" name="cards_per_customer" class="form-control" value="1" min="1" max="10" required>
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label>وصف المكافأة</label>
        <textarea name="reward_description" class="form-control" rows="2"></textarea>
    </div>
    
    <div class="col-md-6 mb-3">
        <label>تاريخ الانتهاء</label>
        <input type="datetime-local" name="expires_at" class="form-control">
    </div>
</div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus fa-fw"></i> إنشاء الكروت
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- مودال عرض التفاصيل -->
<div class="modal fade" id="detailsModal" tabindex="-1" aria-labelledby="detailsModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detailsModalLabel">تفاصيل كارت الخربشة</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="cardDetailsContent">
                <!-- سيتم تحميل المحتوى هنا via AJAX -->
            </div>
        </div>
    </div>
</div>

<!-- مودال تأكيد الحذف -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger" id="deleteModalLabel">
                    <i class="fas fa-exclamation-triangle fa-fw"></i>
                    تأكيد الحذف
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>هل أنت متأكد من أنك تريد حذف كارت الخربشة <strong id="deleteCardCode"></strong>؟</p>
                <p class="text-danger">هذا الإجراء لا يمكن التراجع عنه.</p>
            </div>
            <div class="modal-footer">
                <form method="POST" id="deleteForm">
                    <input type="hidden" name="action" value="delete_card">
                    <input type="hidden" name="card_id" id="deleteCardId">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash fa-fw"></i> حذف
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
</div>
</div>
<?php //require_once 'includes/admin_footer.php'; ?>

 

<script>
function showCreateModal() {
    $('#createModal').modal('show');
}

function updateRewardFields() {
    const rewardType = document.querySelector('select[name="reward_type"]').value;
    const label = document.getElementById('reward_value_label');
    const input = document.querySelector('input[name="reward_value"]');
    
    switch(rewardType) {
        case 'points':
            label.textContent = 'قيمة النقاط';
            input.step = '1';
            input.min = '1';
            break;
        case 'discount':
            label.textContent = 'نسبة الخصم %';
            input.step = '1';
            input.min = '1';
            input.max = '100';
            break;
        case 'cash':
            label.textContent = 'المبلغ (ج.م)';
            input.step = '0.01';
            input.min = '0.01';
            break;
        case 'gift':
            label.textContent = 'قيمة الهدية';
            input.step = '1';
            input.min = '1';
            break;
    }
}

function viewCardDetails(cardId) {
    // هنا يمكنك إضافة AJAX لجلب تفاصيل إضافية للكارت
    $('#detailsModal').modal('show');
    $('#cardDetailsContent').html(`
        <div class="text-center">
            <i class="fas fa-spinner fa-spin fa-2x"></i>
            <p>جاري تحميل التفاصيل...</p>
        </div>
    `);
    
    // محاكاة لجلب البيانات (يمكن استبدالها بـ AJAX حقيقي)
    setTimeout(() => {
        $('#cardDetailsContent').html(`
            <div class="text-center">
                <i class="fas fa-gift fa-3x text-primary mb-3"></i>
                <h5>تفاصيل الكارت</h5>
                <p>سيتم عرض التفاصيل الكاملة هنا</p>
            </div>
        `);
    }, 1000);
}

function deleteCard(cardId, cardCode) {
    $('#deleteCardId').val(cardId);
    $('#deleteCardCode').text(cardCode);
    $('#deleteModal').modal('show');
}

// تهيئة Select2
$(document).ready(function() {
    $('.select2').select2({
        width: '100%',
        language: {
            noResults: function() {
                return "لا توجد نتائج";
            }
        }
    });
});
</script>

<?php
// دوال مساعدة
function getRewardTypeBadge($type) {
    switch ($type) {
        case 'points': return 'warning';
        case 'discount': return 'success';
        case 'gift': return 'info';
        case 'cash': return 'primary';
        default: return 'secondary';
    }
}

function getRewardTypeText($type) {
    switch ($type) {
        case 'points': return 'نقاط';
        case 'discount': return 'خصم';
        case 'gift': return 'هدية';
        case 'cash': return 'نقدي';
        default: return 'غير معروف';
    }
} 
function getAllCustomers() {
    global $pdo;
    $stmt = $pdo->query("SELECT id, first_name, last_name, email FROM customers ORDER BY first_name, last_name");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getAllProducts() {
    global $pdo;
    $stmt = $pdo->query("SELECT id, title, price FROM products WHERE is_active = 1 ORDER BY title");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
 
?>

<!-- إضافة مكتبات JavaScript المطلوبة -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />

<script>
function showCreateModal() {
    $('#createModal').modal('show');
}

function updateRewardFields() {
    const rewardType = document.querySelector('select[name="reward_type"]').value;
    const label = document.getElementById('reward_value_label');
    const input = document.querySelector('input[name="reward_value"]');
    
    switch(rewardType) {
        case 'points':
            label.textContent = 'قيمة النقاط';
            input.step = '1';
            input.min = '1';
            input.max = '';
            break;
        case 'discount':
            label.textContent = 'نسبة الخصم %';
            input.step = '1';
            input.min = '1';
            input.max = '100';
            break;
        case 'cash':
            label.textContent = 'المبلغ (ج.م)';
            input.step = '0.01';
            input.min = '0.01';
            input.max = '';
            break;
        case 'gift':
            label.textContent = 'قيمة الهدية';
            input.step = '1';
            input.min = '1';
            input.max = '';
            break;
    }
}

function viewCardDetails(cardId) {
    $('#detailsModal').modal('show');
    $('#cardDetailsContent').html(`
        <div class="text-center">
            <i class="fas fa-spinner fa-spin fa-2x"></i>
            <p>جاري تحميل التفاصيل...</p>
        </div>
    `);
    
    // جلب البيانات الحقيقية عبر AJAX
    fetch(`get_card_details.php?card_id=${cardId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                $('#cardDetailsContent').html(`
                    <div class="row">
                        <div class="col-md-6">
                            <h6>معلومات الكارت</h6>
                            <p><strong>الكود:</strong> <code>${data.card.card_code}</code></p>
                            <p><strong>نوع المكافأة:</strong> ${getRewardTypeText(data.card.reward_type)}</p>
                            <p><strong>قيمة المكافأة:</strong> ${data.card.reward_value}</p>
                            <p><strong>الوصف:</strong> ${data.card.reward_description || 'لا يوجد'}</p>
                        </div>
                        <div class="col-md-6">
                            <h6>معلومات العميل</h6>
                            <p><strong>الاسم:</strong> ${data.customer.first_name} ${data.customer.last_name}</p>
                            <p><strong>البريد:</strong> ${data.customer.email}</p>
                            <p><strong>المنتج:</strong> ${data.product.title}</p>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-12">
                            <h6>حالة الكارت</h6>
                            <p><strong>تم الخربشة:</strong> ${data.card.is_scratched ? 'نعم' : 'لا'}</p>
                            <p><strong>تم المطالبة:</strong> ${data.card.is_claimed ? 'نعم' : 'لا'}</p>
                            <p><strong>تاريخ الإنشاء:</strong> ${data.card.created_at}</p>
                            <p><strong>تاريخ الانتهاء:</strong> ${data.card.expires_at || 'لا ينتهي'}</p>
                        </div>
                    </div>
                `);
            } else {
                $('#cardDetailsContent').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                        ${data.message}
                    </div>
                `);
            }
        })
        .catch(error => {
            $('#cardDetailsContent').html(`
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    حدث خطأ في جلب البيانات
                </div>
            `);
        });
}

function deleteCard(cardId, cardCode) {
    $('#deleteCardId').val(cardId);
    $('#deleteCardCode').text(cardCode);
    $('#deleteModal').modal('show');
}

$(document).ready(function() {
    // تهيئة Select2
    $('.select2').select2({
        width: '100%',
        language: {
            noResults: function() {
                return "لا توجد نتائج";
            }
        },
        placeholder: "اختر العملاء"
    });
    
    // تحديث حقول المكافأة عند التحميل
    updateRewardFields();
});
</script>

<!-- أضف قبل نهاية الـ body -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>