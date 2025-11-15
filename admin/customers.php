<?php
/**
 * صفحة إدارة العملاء
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
$verified = $_GET['verified'] ?? '';

$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(email LIKE ? OR phone LIKE ? OR first_name LIKE ? OR last_name LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

if (!empty($verified) && $verified != 'all') {
    if ($verified == 'verified') {
        $where[] = "is_verified = 1";
    } elseif ($verified == 'unverified') {
        $where[] = "is_verified = 0";
    }
}

$where_sql = '';
if (!empty($where)) {
    $where_sql = 'WHERE ' . implode(' AND ', $where);
}

// جلب العملاء
$stmt = $pdo->prepare("
    SELECT * FROM customers 
    $where_sql 
    ORDER BY created_at DESC
");
$stmt->execute($params);
$customers = $stmt->fetchAll();

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة العملاء - لوحة التحكم</title>
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
        
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-success { background: #dcfce7; color: #166534; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-secondary { background: #f1f5f9; color: #475569; }
        
        .customer-avatar {
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
        
        .customer-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .customer-details {
            display: flex;
            flex-direction: column;
        }
        .customer-name {
            font-weight: 600;
        }
        .customer-contact {
            font-size: 12px;
            color: #64748b;
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
                    <h1>إدارة العملاء</h1>
                    <p style="color: #64748b; margin-top: 5px;">عرض وإدارة عملاء المتجر</p>
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
                <form method="get" action="customers.php">
                    <div class="filter-row">
                        <div class="form-group">
                            <label for="search">بحث</label>
                            <input type="text" id="search" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="ابحث بالاسم، البريد الإلكتروني، أو الهاتف...">
                        </div>
                        <div class="form-group">
                            <label for="verified">حالة التحقق</label>
                            <select id="verified" name="verified">
                                <option value="all">جميع العملاء</option>
                                <option value="verified" <?= $verified == 'verified' ? 'selected' : '' ?>>موثق</option>
                                <option value="unverified" <?= $verified == 'unverified' ? 'selected' : '' ?>>غير موثق</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter"></i>
                                تصفية
                            </button>
                            <a href="customers.php" class="btn btn-secondary">
                                <i class="fas fa-refresh"></i>
                                إعادة تعيين
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Customers Table -->
            <div class="card">
                <div class="card-header">
                    <h2>العملاء (<?= count($customers) ?>)</h2>
                </div>

                <?php if (empty($customers)): ?>
                    <p style="text-align: center; color: #64748b; padding: 40px;">لا توجد عملاء</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>العميل</th>
                                <th>معلومات الاتصال</th>
                                <th>عدد الطلبات</th>
                                <th>إجمالي المشتريات</th>
                                <th>آخر طلب</th>
                                <th>الحالة</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($customers as $customer): ?>
                                <tr>
                                    <td>
                                        <div class="customer-info">
                                            <div class="customer-avatar">
                                                <?= mb_substr($customer['first_name'] ?? $customer['email'], 0, 1) ?>
                                            </div>
                                            <div class="customer-details">
                                                <span class="customer-name">
                                                    <?= htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']) ?>
                                                </span>
                                                <span class="customer-contact">
                                                    <?= date('Y-m-d', strtotime($customer['created_at'])) ?>
                                                </span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="customer-details">
                                            <span class="customer-contact"><?= htmlspecialchars($customer['email']) ?></span>
                                            <span class="customer-contact"><?= htmlspecialchars($customer['phone']) ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-secondary"><?= $customer['orders_count'] ?></span>
                                    </td>
                                    <td>
                                        <strong><?= formatPrice($customer['total_spent']) ?></strong>
                                    </td>
                                    <td>
                                        <?php if ($customer['last_order_date']): ?>
                                            <?= date('Y-m-d', strtotime($customer['last_order_date'])) ?>
                                        <?php else: ?>
                                            <span style="color: #64748b;">لا توجد طلبات</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($customer['is_verified']): ?>
                                            <span class="badge badge-success">موثق</span>
                                        <?php else: ?>
                                            <span class="badge badge-warning">غير موثق</span>
                                        <?php endif; ?>
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