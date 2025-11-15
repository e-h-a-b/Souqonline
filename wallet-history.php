<?php
session_start();
require_once 'config.php';
require_once 'functions.php';

// التحقق من تسجيل دخول العميل
if (!isset($_SESSION['customer_id'])) {
    header('Location: login.php');
    exit;
}

$customer_id = $_SESSION['customer_id'];

// جلب بيانات العميل
$stmt = $pdo->prepare("SELECT first_name, last_name, email FROM customers WHERE id = ?");
$stmt->execute([$customer_id]);
$customer = $stmt->fetch();

if (!$customer) {
    header('Location: logout.php');
    exit;
}

// جلب بيانات المحفظة
$wallet_data = getCustomerWallet($customer_id);

// معاملات البحث والتصفية
$type_filter = $_GET['type'] ?? 'all';
$status_filter = $_GET['status'] ?? 'all';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

// بناء استعلام المعاملات
$where_conditions = ["wt.customer_id = ?"];
$params = [$customer_id];

if ($type_filter !== 'all') {
    $where_conditions[] = "wt.type = ?";
    $params[] = $type_filter;
}

if ($status_filter !== 'all') {
    $where_conditions[] = "wt.status = ?";
    $params[] = $status_filter;
}

if (!empty($start_date)) {
    $where_conditions[] = "DATE(wt.transaction_date) >= ?";
    $params[] = $start_date;
}

if (!empty($end_date)) {
    $where_conditions[] = "DATE(wt.transaction_date) <= ?";
    $params[] = $end_date;
}

$where_sql = "WHERE " . implode(" AND ", $where_conditions);

// جلب المعاملات
$stmt = $pdo->prepare("
    SELECT wt.* 
    FROM wallet_transactions wt
    $where_sql
    ORDER BY wt.transaction_date DESC
    LIMIT $limit OFFSET $offset
");
$stmt->execute($params);
$transactions = $stmt->fetchAll();

// جلب إجمالي عدد المعاملات للترقيم
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total 
    FROM wallet_transactions wt
    $where_sql
");
$stmt->execute($params);
$total_transactions = $stmt->fetchColumn();
$total_pages = ceil($total_transactions / $limit);

// جلب إحصائيات المعاملات
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_count,
        SUM(CASE WHEN type = 'deposit' THEN amount ELSE 0 END) as total_deposits,
        SUM(CASE WHEN type = 'withdrawal' THEN amount ELSE 0 END) as total_withdrawals,
        SUM(CASE WHEN type = 'refund' THEN amount ELSE 0 END) as total_refunds,
        SUM(CASE WHEN type = 'bonus' THEN amount ELSE 0 END) as total_bonuses
    FROM wallet_transactions 
    WHERE customer_id = ?
");
$stmt->execute([$customer_id]);
$transaction_stats = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>سجل معاملات المحفظة - <?= htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .header h1 {
            color: #1e293b;
            margin-bottom: 0.5rem;
        }
        
        .header p {
            color: #64748b;
            font-size: 1.1rem;
        }
        
        .wallet-summary {
            background: linear-gradient(135deg, #10b981, #34d399);
            color: white;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
        }
        
        .wallet-summary h2 {
            margin-bottom: 1rem;
            font-size: 1.5rem;
        }
        
        .balance-amount {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .stat-number {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .search-filters {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .filter-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            align-items: end;
        }
        
        .form-group {
            margin-bottom: 0;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #374151;
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 1rem;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s;
            font-size: 1rem;
        }
        
        .btn-primary {
            background: #2563eb;
            color: white;
        }
        
        .btn-primary:hover {
            background: #1d4ed8;
        }
        
        .btn-outline {
            background: transparent;
            border: 2px solid #2563eb;
            color: #2563eb;
        }
        
        .btn-outline:hover {
            background: #2563eb;
            color: white;
        }
        
        .transactions-table {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 1rem;
            text-align: right;
            border-bottom: 1px solid #e5e7eb;
        }
        
        th {
            background: #f8fafc;
            font-weight: 600;
            color: #374151;
        }
        
        .badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
        }
        
        .badge-success {
            background: #dcfce7;
            color: #166534;
        }
        
        .badge-warning {
            background: #fef3c7;
            color: #92400e;
        }
        
        .badge-danger {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .badge-info {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 2rem;
        }
        
        .page-link {
            padding: 0.5rem 1rem;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            text-decoration: none;
            color: #374151;
            transition: all 0.3s;
        }
        
        .page-link:hover {
            background: #f3f4f6;
        }
        
        .page-link.active {
            background: #2563eb;
            color: white;
            border-color: #2563eb;
        }
        
        .no-transactions {
            text-align: center;
            padding: 3rem;
            color: #6b7280;
        }
        
        .export-section {
            text-align: center;
            margin-top: 2rem;
            padding: 1.5rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .filter-form {
                grid-template-columns: 1fr;
            }
            
            .balance-amount {
                font-size: 2rem;
            }
            
            th, td {
                padding: 0.5rem;
                font-size: 0.875rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- رأس الصفحة -->
        <div class="header">
            <h1><i class="fas fa-history"></i> سجل معاملات المحفظة</h1>
            <p>تتبع جميع معاملاتك المالية في مكان واحد</p>
            <div style="margin-top: 1rem;">
                <a href="account.php" class="btn btn-outline">
                    <i class="fas fa-arrow-right"></i> العودة لصفحة الحساب
                </a>
            </div>
        </div>

        <!-- ملخص المحفظة -->
        <div class="wallet-summary">
            <h2><i class="fas fa-wallet"></i> رصيدك الحالي</h2>
            <div class="balance-amount">
                <?= number_format($wallet_data['balance'], 2) ?> ج.م
            </div>
            <p>آخر تحديث: <?= date('Y-m-d H:i') ?></p>
        </div>

        <!-- إحصائيات المعاملات -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number" style="color: #10b981;">
                    <?= number_format($transaction_stats['total_deposits'] ?? 0, 2) ?> ج.م
                </div>
                <p>إجمالي الإيداعات</p>
            </div>
            <div class="stat-card">
                <div class="stat-number" style="color: #ef4444;">
                    <?= number_format($transaction_stats['total_withdrawals'] ?? 0, 2) ?> ج.م
                </div>
                <p>إجمالي السحوبات</p>
            </div>
            <div class="stat-card">
                <div class="stat-number" style="color: #8b5cf6;">
                    <?= number_format($transaction_stats['total_refunds'] ?? 0, 2) ?> ج.م
                </div>
                <p>إجمالي الاستردادات</p>
            </div>
            <div class="stat-card">
                <div class="stat-number" style="color: #f59e0b;">
                    <?= number_format($transaction_stats['total_bonuses'] ?? 0, 2) ?> ج.م
                </div>
                <p>إجمالي المكافآت</p>
            </div>
        </div>

        <!-- نموذج البحث والتصفية -->
        <div class="search-filters">
            <h3 style="margin-bottom: 1rem; color: #374151;">بحث وتصفية المعاملات</h3>
            <form method="GET" class="filter-form">
                <div class="form-group">
                    <label>نوع المعاملة</label>
                    <select name="type" class="form-control">
                        <option value="all">جميع الأنواع</option>
                        <option value="deposit" <?= $type_filter === 'deposit' ? 'selected' : '' ?>>إيداع</option>
                        <option value="withdrawal" <?= $type_filter === 'withdrawal' ? 'selected' : '' ?>>سحب</option>
                        <option value="refund" <?= $type_filter === 'refund' ? 'selected' : '' ?>>استرداد</option>
                        <option value="bonus" <?= $type_filter === 'bonus' ? 'selected' : '' ?>>مكافأة</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>حالة المعاملة</label>
                    <select name="status" class="form-control">
                        <option value="all">جميع الحالات</option>
                        <option value="completed" <?= $status_filter === 'completed' ? 'selected' : '' ?>>مكتمل</option>
                        <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>قيد المعالجة</option>
                        <option value="failed" <?= $status_filter === 'failed' ? 'selected' : '' ?>>فاشل</option>
                        <option value="cancelled" <?= $status_filter === 'cancelled' ? 'selected' : '' ?>>ملغي</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>من تاريخ</label>
                    <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($start_date) ?>">
                </div>
                
                <div class="form-group">
                    <label>إلى تاريخ</label>
                    <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($end_date) ?>">
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary" style="width: 100%;">
                        <i class="fas fa-search"></i> بحث
                    </button>
                </div>
            </form>
        </div>

        <!-- جدول المعاملات -->
        <div class="transactions-table">
            <?php if (empty($transactions)): ?>
                <div class="no-transactions">
                    <i class="fas fa-receipt" style="font-size: 4rem; color: #d1d5db; margin-bottom: 1rem;"></i>
                    <h3 style="color: #6b7280; margin-bottom: 1rem;">لا توجد معاملات</h3>
                    <p style="color: #6b7280;">لم تقم بأي معاملات في محفظتك حتى الآن.</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>نوع المعاملة</th>
                            <th>المبلغ</th>
                            <th>الوصف</th>
                            <th>الحالة</th>
                            <th>التاريخ والوقت</th>
                            <th>رقم المرجع</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $transaction): ?>
                            <tr>
                                <td>
                                    <span class="badge 
                                        <?= $transaction['type'] === 'deposit' || $transaction['type'] === 'refund' || $transaction['type'] === 'bonus' ? 'badge-success' : 'badge-danger' ?>">
                                        <?php
                                        $type_names = [
                                            'deposit' => 'إيداع',
                                            'withdrawal' => 'سحب',
                                            'refund' => 'استرداد',
                                            'bonus' => 'مكافأة'
                                        ];
                                        echo $type_names[$transaction['type']] ?? $transaction['type'];
                                        ?>
                                    </span>
                                </td>
                                <td style="font-weight: 700; color: 
                                    <?= $transaction['type'] === 'deposit' || $transaction['type'] === 'refund' || $transaction['type'] === 'bonus' ? '#10b981' : '#ef4444' ?>;">
                                    <?= $transaction['type'] === 'deposit' || $transaction['type'] === 'refund' || $transaction['type'] === 'bonus' ? '+' : '-' ?>
                                    <?= number_format($transaction['amount'], 2) ?> ج.م
                                </td>
                                <td>
                                    <?= htmlspecialchars($transaction['description']) ?>
                                    <?php if ($transaction['reference_type']): ?>
                                        <br>
                                        <small style="color: #6b7280;">
                                            <?= $transaction['reference_type'] === 'order' ? 'مرتبط بطلب' : 'يدوي' ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge 
                                        <?= $transaction['status'] === 'completed' ? 'badge-success' : 
                                           ($transaction['status'] === 'pending' ? 'badge-warning' : 
                                           ($transaction['status'] === 'failed' ? 'badge-danger' : 'badge-info')) ?>">
                                        <?php
                                        $status_names = [
                                            'completed' => 'مكتمل',
                                            'pending' => 'قيد المعالجة',
                                            'failed' => 'فاشل',
                                            'cancelled' => 'ملغي'
                                        ];
                                        echo $status_names[$transaction['status']] ?? $transaction['status'];
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <?= date('Y-m-d', strtotime($transaction['transaction_date'])) ?>
                                    <br>
                                    <small style="color: #6b7280;">
                                        <?= date('H:i', strtotime($transaction['transaction_date'])) ?>
                                    </small>
                                </td>
                                <td>
                                    <small style="color: #6b7280; font-family: monospace;">
                                        #<?= str_pad($transaction['id'], 6, '0', STR_PAD_LEFT) ?>
                                    </small>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- الترقيم -->
        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" class="page-link">
                        <i class="fas fa-chevron-right"></i> السابق
                    </a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" 
                       class="page-link <?= $i == $page ? 'active' : '' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" class="page-link">
                        التالي <i class="fas fa-chevron-left"></i>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- قسم التصدير -->
        <div class="export-section">
            <h3 style="margin-bottom: 1rem; color: #374151;">تصدير سجل المعاملات</h3>
            <p style="color: #6b7280; margin-bottom: 1rem;">يمكنك تصدير سجل معاملاتك إلى ملف Excel أو PDF</p>
            <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                <a href="wallet-export.php?format=excel&<?= http_build_query($_GET) ?>" class="btn btn-outline">
                    <i class="fas fa-file-excel"></i> تصدير Excel
                </a>
                <a href="wallet-export.php?format=pdf&<?= http_build_query($_GET) ?>" class="btn btn-outline">
                    <i class="fas fa-file-pdf"></i> تصدير PDF
                </a>
                <a href="wallet-export.php?format=print&<?= http_build_query($_GET) ?>" class="btn btn-outline" target="_blank">
                    <i class="fas fa-print"></i> طباعة
                </a>
            </div>
        </div>
    </div>

    <script>
    // إضافة بعض التفاعلية
    document.addEventListener('DOMContentLoaded', function() {
        // التأكيد على تواريخ البحث
        const startDate = document.querySelector('input[name="start_date"]');
        const endDate = document.querySelector('input[name="end_date"]');
        
        if (startDate && endDate) {
            startDate.addEventListener('change', function() {
                endDate.min = this.value;
            });
            
            endDate.addEventListener('change', function() {
                if (startDate.value && this.value < startDate.value) {
                    alert('تاريخ النهاية يجب أن يكون بعد تاريخ البداية');
                    this.value = '';
                }
            });
        }
        
        // رسالة تحميل عند البحث
        const filterForm = document.querySelector('.filter-form');
        if (filterForm) {
            filterForm.addEventListener('submit', function() {
                const submitBtn = this.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جاري البحث...';
                    submitBtn.disabled = true;
                }
            });
        }
    });
    </script>
</body>
</html>