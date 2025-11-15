<?php
session_start();
require_once '../config.php';
require_once '../functions.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$customer_id = $_GET['customer_id'] ?? null;
$search = $_GET['search'] ?? '';
$type_filter = $_GET['type'] ?? 'all';
$status_filter = $_GET['status'] ?? 'all';

// بناء الاستعلام
$where_conditions = [];
$params = [];

if ($customer_id) {
    $where_conditions[] = "wt.customer_id = ?";
    $params[] = $customer_id;
}

if (!empty($search)) {
    $where_conditions[] = "(c.first_name LIKE ? OR c.last_name LIKE ? OR wt.description LIKE ?)";
    $search_term = "%$search%";
    $params = array_merge($params, [$search_term, $search_term, $search_term]);
}

if ($type_filter !== 'all') {
    $where_conditions[] = "wt.type = ?";
    $params[] = $type_filter;
}

if ($status_filter !== 'all') {
    $where_conditions[] = "wt.status = ?";
    $params[] = $status_filter;
}

$where_sql = '';
if (!empty($where_conditions)) {
    $where_sql = "WHERE " . implode(" AND ", $where_conditions);
}

$stmt = $pdo->prepare("
    SELECT wt.*, c.first_name, c.last_name, c.email 
    FROM wallet_transactions wt
    JOIN customers c ON wt.customer_id = c.id
    $where_sql
    ORDER BY wt.transaction_date DESC
");
$stmt->execute($params);
$transactions = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>سجل معاملات المحفظة</title>
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
        <?php include 'sidebar.php'; ?>
        
        <main class="main-content">
            <div class="top-bar">
                <h1>سجل معاملات المحفظة</h1>
                <a href="wallets.php" class="btn btn-outline">
                    <i class="fas fa-arrow-right"></i> العودة للمحافظ
                </a>
            </div>

            <!-- نموذج البحث -->
            <div class="card">
                <form method="GET" class="search-form">
                    <div class="form-group">
                        <input type="text" name="search" class="form-control" placeholder="بحث..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="form-group">
                        <select name="type" class="form-control">
                            <option value="all">جميع الأنواع</option>
                            <option value="deposit" <?= $type_filter === 'deposit' ? 'selected' : '' ?>>إيداع</option>
                            <option value="withdrawal" <?= $type_filter === 'withdrawal' ? 'selected' : '' ?>>سحب</option>
                            <option value="refund" <?= $type_filter === 'refund' ? 'selected' : '' ?>>استرداد</option>
                            <option value="bonus" <?= $type_filter === 'bonus' ? 'selected' : '' ?>>مكافأة</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <select name="status" class="form-control">
                            <option value="all">جميع الحالات</option>
                            <option value="completed" <?= $status_filter === 'completed' ? 'selected' : '' ?>>مكتمل</option>
                            <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>قيد المعالجة</option>
                            <option value="failed" <?= $status_filter === 'failed' ? 'selected' : '' ?>>فاشل</option>
                            <option value="cancelled" <?= $status_filter === 'cancelled' ? 'selected' : '' ?>>ملغي</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary" style="width: 100%;">
                            <i class="fas fa-search"></i> بحث
                        </button>
                    </div>
                </form>
            </div>

            <!-- جدول المعاملات -->
            <div class="card">
                <table>
                    <thead>
                        <tr>
                            <th>العميل</th>
                            <th>النوع</th>
                            <th>المبلغ</th>
                            <th>الوصف</th>
                            <th>الحالة</th>
                            <th>التاريخ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $transaction): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($transaction['first_name'] . ' ' . $transaction['last_name']) ?></strong>
                                    <br><small><?= htmlspecialchars($transaction['email']) ?></small>
                                </td>
                                <td>
                                    <span class="badge <?= $transaction['type'] === 'deposit' ? 'badge-success' : 'badge-danger' ?>">
                                        <?= $transaction['type'] === 'deposit' ? 'إيداع' : 'سحب' ?>
                                    </span>
                                </td>
                                <td style="font-weight: bold; color: <?= $transaction['type'] === 'deposit' ? '#16a34a' : '#dc2626' ?>;">
                                    <?= $transaction['type'] === 'deposit' ? '+' : '-' ?>
                                    <?= number_format($transaction['amount'], 2) ?> ج.م
                                </td>
                                <td><?= htmlspecialchars($transaction['description']) ?></td>
                                <td>
                                    <span class="badge 
                                        <?= $transaction['status'] === 'completed' ? 'badge-success' : 
                                           ($transaction['status'] === 'pending' ? 'badge-warning' : 'badge-danger') ?>">
                                        <?= $transaction['status'] === 'completed' ? 'مكتمل' : 
                                           ($transaction['status'] === 'pending' ? 'قيد المعالجة' : 'فاشل') ?>
                                    </span>
                                </td>
                                <td>
                                    <?= date('Y-m-d H:i', strtotime($transaction['transaction_date'])) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>