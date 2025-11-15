<?php
/**
 * صفحة إدارة الطلبات
 */
session_start();
require_once '../config.php';
require_once '../functions.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// البحث والتصفية
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$payment_status = $_GET['payment_status'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(order_number LIKE ? OR customer_name LIKE ? OR customer_phone LIKE ? OR customer_email LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

if (!empty($status) && $status != 'all') {
    $where[] = "status = ?";
    $params[] = $status;
}

if (!empty($payment_status) && $payment_status != 'all') {
    $where[] = "payment_status = ?";
    $params[] = $payment_status;
}

if (!empty($date_from)) {
    $where[] = "DATE(created_at) >= ?";
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $where[] = "DATE(created_at) <= ?";
    $params[] = $date_to;
}

$where_sql = '';
if (!empty($where)) {
    $where_sql = 'WHERE ' . implode(' AND ', $where);
}

// جلب الطلبات
$stmt = $pdo->prepare("
    SELECT * FROM orders 
    $where_sql 
    ORDER BY created_at DESC
");
$stmt->execute($params);
$orders = $stmt->fetchAll();

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';
// تحديث حالة الطلب
if (isset($_POST['update_status'])) {
    $order_id = (int)$_POST['order_id'];
    $new_status = $_POST['status'];
    $admin_notes = $_POST['admin_notes'] ?? '';
    
    try {
        $pdo->beginTransaction();
        
        // تحديث حالة الطلب
        $stmt = $pdo->prepare("UPDATE orders SET status = ?, admin_notes = CONCAT(COALESCE(admin_notes, ''), ?), updated_at = NOW() WHERE id = ?");
        $stmt->execute([$new_status, ($admin_notes ? "\n" . date('Y-m-d H:i') . ": " . $admin_notes : ""), $order_id]);
        
        // إضافة إلى سجل الحالات
        $stmt = $pdo->prepare("INSERT INTO order_status_history (order_id, old_status, new_status, comment, created_by) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$order_id, $_POST['old_status'], $new_status, $admin_notes, $_SESSION['admin_id']]);
        
        // إذا كانت الحالة "تم الشحن"، إضافة وقت الشحن
        if ($new_status == 'shipped') {
            $tracking_number = $_POST['tracking_number'] ?? '';
            $stmt = $pdo->prepare("UPDATE orders SET shipped_at = NOW(), tracking_number = ? WHERE id = ?");
            $stmt->execute([$tracking_number, $order_id]);
        }
        
        // إذا كانت الحالة "تم التوصيل"، إضافة وقت التوصيل
        if ($new_status == 'delivered') {
            $stmt = $pdo->prepare("UPDATE orders SET delivered_at = NOW() WHERE id = ?");
            $stmt->execute([$order_id]);
        }
        
        $pdo->commit();
        
        // تحديث إحصائيات العميل إذا كان الطلب مكتملاً ومدفوعاً
        if ($new_status == 'delivered') {
            $stmt = $pdo->prepare("SELECT customer_id, total FROM orders WHERE id = ?");
            $stmt->execute([$order_id]);
            $order_info = $stmt->fetch();
            
            if ($order_info && $order_info['customer_id']) {
                $stmt = $pdo->prepare("UPDATE customers SET orders_count = orders_count + 1, total_spent = total_spent + ?, last_order_date = NOW() WHERE id = ?");
                $stmt->execute([$order_info['total'], $order_info['customer_id']]);
            }
        }
        
        logActivity('order_status_updated', "تم تحديث حالة الطلب #{$order_id} إلى {$new_status}", $_SESSION['admin_id']);
        header('Location: orders.php?success=' . urlencode('تم تحديث حالة الطلب بنجاح'));
        exit;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        header('Location: orders.php?error=' . urlencode('حدث خطأ أثناء تحديث حالة الطلب'));
        exit;
    }
}

// تحديث حالة الدفع
if (isset($_POST['update_payment_status'])) {
    $order_id = (int)$_POST['order_id'];
    $payment_status = $_POST['payment_status'];
    $transaction_id = $_POST['transaction_id'] ?? '';
    
    try {
        $stmt = $pdo->prepare("UPDATE orders SET payment_status = ?, payment_transaction_id = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$payment_status, $transaction_id, $order_id]);
        
        logActivity('payment_status_updated', "تم تحديث حالة الدفع للطلب #{$order_id} إلى {$payment_status}", $_SESSION['admin_id']);
        header('Location: orders.php?success=' . urlencode('تم تحديث حالة الدفع بنجاح'));
        exit;
        
    } catch (Exception $e) {
        header('Location: orders.php?error=' . urlencode('حدث خطأ أثناء تحديث حالة الدفع'));
        exit;
    }
}

// إضافة ملاحظة إدارية
if (isset($_POST['add_note'])) {
    $order_id = (int)$_POST['order_id'];
    $note = $_POST['note'];
    
    try {
        $stmt = $pdo->prepare("UPDATE orders SET admin_notes = CONCAT(COALESCE(admin_notes, ''), '\n', ?), updated_at = NOW() WHERE id = ?");
        $stmt->execute([date('Y-m-d H:i') . ": " . $note, $order_id]);
        
        logActivity('admin_note_added', "تم إضافة ملاحظة إدارية للطلب #{$order_id}", $_SESSION['admin_id']);
        header('Location: orders.php?success=' . urlencode('تم إضافة الملاحظة بنجاح'));
        exit;
        
    } catch (Exception $e) {
        header('Location: orders.php?error=' . urlencode('حدث خطأ أثناء إضافة الملاحظة'));
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الطلبات - لوحة التحكم</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
	
    <style>
        /* نفس أنماط الصفحات السابقة مع تعديلات */
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
            flex-wrap: wrap;
        }
        .form-group {
            flex: 1;
            min-width: 200px;
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
        .btn-warning { background: #d97706; color: #fff; }
        .btn-warning:hover { background: #b45309; }
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
        
        .customer-info {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .customer-name {
            font-weight: 600;
        }
        .customer-contact {
            font-size: 12px;
            color: #64748b;
        }
		/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}
.modal-content {
    background-color: white;
    margin: 5% auto;
    padding: 0;
    border-radius: 12px;
    width: 500px;
    max-width: 90%;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
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
    justify-content: flex-start;
    gap: 10px;
}
.close {
    color: #64748b;
    font-size: 24px;
    font-weight: bold;
    cursor: pointer;
}
.close:hover { color: #374151; }

/* Quick Actions */
.quick-actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}
.action-btn-small {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 11px;
    text-decoration: none;
    border: 1px solid #e2e8f0;
    background: white;
    cursor: pointer;
}
.action-btn-small:hover {
    background: #f8fafc;
}

/* Order Details */
.order-details-popup {
    max-height: 400px;
    overflow-y: auto;
}
.detail-row {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid #f1f5f9;
}
.detail-label {
    font-weight: 600;
    color: #475569;
}
.detail-value {
    color: #334155;
}
/* Quick Actions Styles */
.quick-actions {
    display: flex;
    gap: 4px;
    flex-wrap: wrap;
    margin-top: 5px;
}

.action-btn-small {
    padding: 6px 8px;
    border-radius: 6px;
    font-size: 12px;
    text-decoration: none;
    border: none;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    transition: all 0.3s ease;
}

.action-btn-small:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

.action-btn-small.btn-success { 
    background: #16a34a; 
    color: #fff; 
}
.action-btn-small.btn-success:hover { 
    background: #15803d; 
}

.action-btn-small.btn-info { 
    background: #0ea5e9; 
    color: #fff; 
}
.action-btn-small.btn-info:hover { 
    background: #0284c7; 
}

.action-btn-small.btn-warning { 
    background: #d97706; 
    color: #fff; 
}
.action-btn-small.btn-warning:hover { 
    background: #b45309; 
}

.action-btn-small.btn-danger { 
    background: #dc2626; 
    color: #fff; 
}
.action-btn-small.btn-danger:hover { 
    background: #b91c1c; 
}

.action-btn-small.btn-secondary { 
    background: #64748b; 
    color: #fff; 
}
.action-btn-small.btn-secondary:hover { 
    background: #475569; 
}

.action-btn-small i {
    font-size: 14px;
}

/* تحسين عرض الأزرار في الجدول */
.actions {
    display: flex;
    flex-direction: column;
    gap: 8px;
    align-items: flex-start;
}

.action-btn {
    padding: 6px 10px;
    border-radius: 6px;
    font-size: 12px;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 4px;
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
                    <h1>إدارة الطلبات</h1>
                    <p style="color: #64748b; margin-top: 5px;">عرض وإدارة جميع طلبات المتجر</p>
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
                <form method="get" action="orders.php">
                    <div class="filter-row">
                        <div class="form-group">
                            <label for="search">بحث</label>
                            <input type="text" id="search" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="ابحث برقم الطلب، اسم العميل، الهاتف...">
                        </div>
                        <div class="form-group">
                            <label for="status">حالة الطلب</label>
                            <select id="status" name="status">
                                <option value="all">جميع الحالات</option>
                                <option value="pending" <?= $status == 'pending' ? 'selected' : '' ?>>قيد الانتظار</option>
                                <option value="confirmed" <?= $status == 'confirmed' ? 'selected' : '' ?>>مؤكد</option>
                                <option value="processing" <?= $status == 'processing' ? 'selected' : '' ?>>قيد التجهيز</option>
                                <option value="shipped" <?= $status == 'shipped' ? 'selected' : '' ?>>تم الشحن</option>
                                <option value="delivered" <?= $status == 'delivered' ? 'selected' : '' ?>>تم التوصيل</option>
                                <option value="cancelled" <?= $status == 'cancelled' ? 'selected' : '' ?>>ملغي</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="payment_status">حالة الدفع</label>
                            <select id="payment_status" name="payment_status">
                                <option value="all">جميع الحالات</option>
                                <option value="pending" <?= $payment_status == 'pending' ? 'selected' : '' ?>>قيد الانتظار</option>
                                <option value="paid" <?= $payment_status == 'paid' ? 'selected' : '' ?>>مدفوع</option>
                                <option value="failed" <?= $payment_status == 'failed' ? 'selected' : '' ?>>فشل</option>
                                <option value="refunded" <?= $payment_status == 'refunded' ? 'selected' : '' ?>>تم الاسترجاع</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="date_from">من تاريخ</label>
                            <input type="date" id="date_from" name="date_from" value="<?= htmlspecialchars($date_from) ?>">
                        </div>
                        <div class="form-group">
                            <label for="date_to">إلى تاريخ</label>
                            <input type="date" id="date_to" name="date_to" value="<?= htmlspecialchars($date_to) ?>">
                        </div>
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter"></i>
                                تصفية
                            </button>
                            <a href="orders.php" class="btn btn-secondary">
                                <i class="fas fa-refresh"></i>
                                إعادة تعيين
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Orders Table -->
            <div class="card">
                <div class="card-header">
                    <h2>الطلبات (<?= count($orders) ?>)</h2>
                </div>

                <?php if (empty($orders)): ?>
                    <p style="text-align: center; color: #64748b; padding: 40px;">لا توجد طلبات</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>رقم الطلب</th>
                                <th>العميل</th>
                                <th>المبلغ</th>
                                <th>طريقة الدفع</th>
                                <th>حالة الطلب</th>
                                <th>حالة الدفع</th>
                                <th>التاريخ</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($order['order_number']) ?></strong>
                                    </td>
                                    <td>
                                        <div class="customer-info">
                                            <span class="customer-name"><?= htmlspecialchars($order['customer_name']) ?></span>
                                            <span class="customer-contact"><?= htmlspecialchars($order['customer_phone']) ?></span>
                                            <?php if ($order['customer_email']): ?>
                                                <span class="customer-contact"><?= htmlspecialchars($order['customer_email']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <strong><?= formatPrice($order['total']) ?></strong>
                                    </td>
                                    <td>
                                        <?php
                                        $payment_methods = [
                                            'cod' => 'الدفع عند الاستلام',
                                            'visa' => 'فيزا / ماستركارد',
                                            'instapay' => 'انستاباي',
                                            'vodafone_cash' => 'فودافون كاش',
                                            'fawry' => 'فوري'
                                        ];
                                        echo $payment_methods[$order['payment_method']] ?? $order['payment_method'];
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        $status_badge = [
                                            'pending' => ['badge-warning', 'قيد الانتظار'],
                                            'confirmed' => ['badge-info', 'مؤكد'],
                                            'processing' => ['badge-info', 'قيد التجهيز'],
                                            'shipped' => ['badge-info', 'تم الشحن'],
                                            'delivered' => ['badge-success', 'تم التوصيل'],
                                            'cancelled' => ['badge-danger', 'ملغي'],
                                            'returned' => ['badge-danger', 'مرتجع']
                                        ];
                                        $badge = $status_badge[$order['status']] ?? ['badge-secondary', $order['status']];
                                        ?>
                                        <span class="badge <?= $badge[0] ?>"><?= $badge[1] ?></span>
                                    </td>
                                    <td>
                                        <?php
                                        $payment_badge = [
                                            'pending' => ['badge-warning', 'قيد الانتظار'],
                                            'paid' => ['badge-success', 'مدفوع'],
                                            'failed' => ['badge-danger', 'فشل'],
                                            'refunded' => ['badge-secondary', 'تم الاسترجاع']
                                        ];
                                        $p_badge = $payment_badge[$order['payment_status']] ?? ['badge-secondary', $order['payment_status']];
                                        ?>
                                        <span class="badge <?= $p_badge[0] ?>"><?= $p_badge[1] ?></span>
                                    </td>
                                    <td>
                                        <?= date('Y-m-d', strtotime($order['created_at'])) ?>
                                        <br>
                                        <small style="color: #64748b;"><?= date('H:i', strtotime($order['created_at'])) ?></small>
                                    </td>
<td>
    <div class="actions">
        <!-- زر التفاصيل الرئيسي -->
        <a href="../order-details.php?id=<?= $order['id'] ?>" class="action-btn btn-primary" title="تفاصيل الطلب" style="margin-bottom: 8px;">
            <i class="fas fa-eye"></i> عرض
        </a>
        
        <!-- أزرار المتابعة السريعة -->
        <div class="quick-actions">
            <?php if ($order['status'] == 'pending'): ?>
                <button type="button" onclick="updateStatus(<?= $order['id'] ?>, 'confirmed', '<?= $order['status'] ?>')" 
                        class="action-btn-small btn-success" title="تأكيد الطلب">
                    <i class="fas fa-check"></i>
                </button>
            <?php endif; ?>
            
            <?php if (in_array($order['status'], ['confirmed', 'processing'])): ?>
                <button type="button" onclick="updateStatus(<?= $order['id'] ?>, 'processing', '<?= $order['status'] ?>')" 
                        class="action-btn-small btn-info" title="تجهيز الطلب">
                    <i class="fas fa-cogs"></i>
                </button>
            <?php endif; ?>
            
            <?php if (in_array($order['status'], ['confirmed', 'processing'])): ?>
                <button type="button" onclick="showShippingModal(<?= $order['id'] ?>, '<?= $order['status'] ?>')" 
                        class="action-btn-small btn-warning" title="تحديث الشحن">
                    <i class="fas fa-shipping-fast"></i>
                </button>
            <?php endif; ?>
            
            <?php if (in_array($order['status'], ['shipped'])): ?>
                <button type="button" onclick="updateStatus(<?= $order['id'] ?>, 'delivered', '<?= $order['status'] ?>')" 
                        class="action-btn-small btn-success" title="تم التوصيل">
                    <i class="fas fa-home"></i>
                </button>
            <?php endif; ?>
            
            <?php if ($order['status'] != 'cancelled' && $order['status'] != 'delivered'): ?>
                <button type="button" onclick="updateStatus(<?= $order['id'] ?>, 'cancelled', '<?= $order['status'] ?>')" 
                        class="action-btn-small btn-danger" title="إلغاء الطلب">
                    <i class="fas fa-ban"></i>
                </button>
            <?php endif; ?>
            
            <?php if ($order['payment_status'] != 'paid'): ?>
                <button type="button" onclick="showPaymentModal(<?= $order['id'] ?>)" 
                        class="action-btn-small btn-success" title="تحديث الدفع">
                    <i class="fas fa-credit-card"></i>
                </button>
            <?php endif; ?>
            
            <button type="button" onclick="showNoteModal(<?= $order['id'] ?>)" 
                    class="action-btn-small btn-secondary" title="إضافة ملاحظة">
                <i class="fas fa-comment"></i>
            </button>
        </div>
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
	<!-- Modal لتحديث حالة الطلب -->
<div id="statusModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>تحديث حالة الطلب</h3>
            <span class="close">&times;</span>
        </div>
        <form method="post" id="statusForm">
            <div class="modal-body">
                <input type="hidden" name="order_id" id="status_order_id">
                <input type="hidden" name="old_status" id="old_status">
                <input type="hidden" name="update_status" value="1">
                
                <div class="form-group">
                    <label>الحالة الجديدة:</label>
                    <select name="status" id="status_select" class="form-control" required>
                        <option value="pending">قيد الانتظار</option>
                        <option value="confirmed">مؤكد</option>
                        <option value="processing">قيد التجهيز</option>
                        <option value="shipped">تم الشحن</option>
                        <option value="delivered">تم التوصيل</option>
                        <option value="cancelled">ملغي</option>
                    </select>
                </div>
                
                <div class="form-group" id="tracking_field" style="display: none;">
                    <label>رقم التتبع:</label>
                    <input type="text" name="tracking_number" class="form-control" placeholder="أدخل رقم التتبع">
                </div>
                
                <div class="form-group">
                    <label>ملاحظات إضافية:</label>
                    <textarea name="admin_notes" class="form-control" rows="3" placeholder="ملاحظات حول تحديث الحالة..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">تحديث الحالة</button>
                <button type="button" class="btn btn-secondary" onclick="closeModal('statusModal')">إلغاء</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal لتحديث حالة الدفع -->
<div id="paymentModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>تحديث حالة الدفع</h3>
            <span class="close">&times;</span>
        </div>
        <form method="post" id="paymentForm">
            <div class="modal-body">
                <input type="hidden" name="order_id" id="payment_order_id">
                <input type="hidden" name="update_payment_status" value="1">
                
                <div class="form-group">
                    <label>حالة الدفع:</label>
                    <select name="payment_status" class="form-control" required>
                        <option value="pending">قيد الانتظار</option>
                        <option value="paid">مدفوع</option>
                        <option value="failed">فشل</option>
                        <option value="refunded">تم الاسترجاع</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>رقم المعاملة (اختياري):</label>
                    <input type="text" name="transaction_id" class="form-control" placeholder="رقم المعاملة أو المرجع">
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">تحديث الدفع</button>
                <button type="button" class="btn btn-secondary" onclick="closeModal('paymentModal')">إلغاء</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal لإضافة ملاحظة -->
<div id="noteModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>إضافة ملاحظة إدارية</h3>
            <span class="close">&times;</span>
        </div>
        <form method="post" id="noteForm">
            <div class="modal-body">
                <input type="hidden" name="order_id" id="note_order_id">
                <input type="hidden" name="add_note" value="1">
                
                <div class="form-group">
                    <label>الملاحظة:</label>
                    <textarea name="note" class="form-control" rows="4" placeholder="أدخل ملاحظتك هنا..." required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">إضافة الملاحظة</button>
                <button type="button" class="btn btn-secondary" onclick="closeModal('noteModal')">إلغاء</button>
            </div>
        </form>
    </div>
</div>

<script>
// وظائف JavaScript للتحكم في النماذج المنبثقة
function updateStatus(orderId, status, oldStatus) {
    document.getElementById('status_order_id').value = orderId;
    document.getElementById('old_status').value = oldStatus;
    document.getElementById('status_select').value = status;
    
    if (status === 'shipped') {
        document.getElementById('tracking_field').style.display = 'block';
    } else {
        document.getElementById('tracking_field').style.display = 'none';
    }
    
    document.getElementById('statusModal').style.display = 'block';
}

function showShippingModal(orderId, oldStatus) {
    document.getElementById('status_order_id').value = orderId;
    document.getElementById('old_status').value = oldStatus;
    document.getElementById('status_select').value = 'shipped';
    document.getElementById('tracking_field').style.display = 'block';
    document.getElementById('statusModal').style.display = 'block';
}

function showPaymentModal(orderId) {
    document.getElementById('payment_order_id').value = orderId;
    document.getElementById('paymentModal').style.display = 'block';
}

function showNoteModal(orderId) {
    document.getElementById('note_order_id').value = orderId;
    document.getElementById('noteModal').style.display = 'block';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

// إغلاق النماذج عند النقر خارجها
window.onclick = function(event) {
    const modals = document.getElementsByClassName('modal');
    for (let modal of modals) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }
}

// إغلاق النماذج عند النقر على زر الإغلاق
document.querySelectorAll('.close').forEach(closeBtn => {
    closeBtn.onclick = function() {
        this.closest('.modal').style.display = 'none';
    }
});
</script>
</body>
</html>