<?php
session_start();
require_once '../config.php';
require_once '../functions.php';

// التحقق من تسجيل الدخول
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// جلب محافظ العملاء مع البحث والتصفية
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? 'all';
$min_balance = $_GET['min_balance'] ?? '';
$max_balance = $_GET['max_balance'] ?? '';

// بناء استعلام البحث
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(c.first_name LIKE ? OR c.last_name LIKE ? OR c.email LIKE ? OR c.phone LIKE ?)";
    $search_term = "%$search%";
    $params = array_merge($params, [$search_term, $search_term, $search_term, $search_term]);
}

if ($status_filter === 'with_balance') {
    $where_conditions[] = "w.balance > 0";
} elseif ($status_filter === 'no_balance') {
    $where_conditions[] = "(w.balance = 0 OR w.balance IS NULL)";
}

if (!empty($min_balance)) {
    $where_conditions[] = "w.balance >= ?";
    $params[] = $min_balance;
}

if (!empty($max_balance)) {
    $where_conditions[] = "w.balance <= ?";
    $params[] = $max_balance;
}

$where_sql = '';
if (!empty($where_conditions)) {
    $where_sql = "WHERE " . implode(" AND ", $where_conditions);
}

$stmt = $pdo->prepare("
    SELECT w.*, c.first_name, c.last_name, c.email, c.phone 
    FROM customer_wallets w 
    JOIN customers c ON w.customer_id = c.id 
    $where_sql
    ORDER BY w.balance DESC
");
$stmt->execute($params);
$wallets = $stmt->fetchAll();

// جلب العملاء للقائمة المنسدلة
$stmt = $pdo->query("SELECT id, first_name, last_name, email FROM customers ORDER BY first_name");
$customers = $stmt->fetchAll();
 
// ====== كود تشخيص مؤقت - احذفه بعد حل المشكلة ======
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    file_put_contents('debug_wallet.txt', 
        date('Y-m-d H:i:s') . "\n" .
        "Action: " . ($_POST['action'] ?? 'NOT SET') . "\n" .
        "Customer ID: " . ($_POST['customer_id'] ?? 'NOT SET') . "\n" .
        "Amount: " . ($_POST['amount'] ?? 'NOT SET') . "\n" .
        "Description: " . ($_POST['description'] ?? 'NOT SET') . "\n" .
        "====================\n\n",
        FILE_APPEND
    );
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
 
    // === 1. إضافة رصيد ===
    if (isset($_POST['add_action']) && $_POST['add_action'] === 'add_balance') {
        $customer_id = $_POST['add_customer_id'];
        $amount      = floatval($_POST['add_amount']);
        $description = trim($_POST['add_description']);
    
    if ($amount <= 0) {
        $_SESSION['error'] = "المبلغ يجب أن يكون أكبر من الصفر";
        header('Location: wallets.php');
        exit;
    }
    
    try {
        $pdo->beginTransaction();
        
        // تحديث رصيد المحفظة
        $stmt = $pdo->prepare("
            UPDATE customer_wallets 
            SET balance = balance + ?, 
                total_deposited = total_deposited + ?,
                updated_at = NOW()
            WHERE customer_id = ?
        ");
        $stmt->execute([$amount, $amount, $customer_id]);
        
        // إذا لم توجد محفظة، أنشئها
        if ($stmt->rowCount() === 0) {
            $stmt = $pdo->prepare("
                INSERT INTO customer_wallets (customer_id, balance, total_deposited) 
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$customer_id, $amount, $amount]);
        }
        
        // تسجيل المعاملة
        $stmt = $pdo->prepare("
            INSERT INTO wallet_transactions 
            (customer_id, amount, type, description, reference_type, status, transaction_date) 
            VALUES (?, ?, 'deposit', ?, 'manual', 'completed', NOW())
        ");
        $stmt->execute([$customer_id, $amount, $description]);
        
        $pdo->commit();
        $_SESSION['success'] = "✅ تم إضافة الرصيد بنجاح: " . number_format($amount, 2) . " ج.م";
        
        logActivity('wallet_deposit', "إضافة رصيد للمحفظة: $amount ج.م للعميل #$customer_id - $description", $_SESSION['admin_id']);
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "خطأ في إضافة الرصيد: " . $e->getMessage();
    }
    
    header('Location: wallets.php');
    exit;
}
// معالجة خصم رصيد
// === 2. خصم رصيد (غرامة) ===
    elseif (isset($_POST['deduct_action']) && $_POST['deduct_action'] === 'deduct_balance') {
        $customer_id = $_POST['deduct_customer_id'];
        $amount      = floatval($_POST['deduct_amount']);
        $description = trim($_POST['deduct_description']);

    if ($amount <= 0) {
        $_SESSION['error'] = "المبلغ يجب أن يكون أكبر من الصفر";
        header('Location: wallets.php');
        exit;
    }

    try {
        $pdo->beginTransaction();

        /* ---------- 1. جلب الرصيد الحالي مع قفل السجل ---------- */
        $stmt = $pdo->prepare("SELECT balance FROM customer_wallets WHERE customer_id = ? FOR UPDATE");
        $stmt->execute([$customer_id]);
        $current_balance = $stmt->fetchColumn();

        if ($current_balance === false) {               // لا توجد محفظة
            $stmt = $pdo->prepare(
                "INSERT INTO customer_wallets (customer_id, balance, total_deposited, total_withdrawn)
                 VALUES (?, 0, 0, 0)"
            );
            $stmt->execute([$customer_id]);
            $current_balance = 0;
        } else {
            $current_balance = floatval($current_balance);
        }

        /* ---------- 2. تحقق من كفاية الرصيد ---------- */
        if ($current_balance < $amount) {
            throw new Exception(
                "الرصيد غير كافٍ. الحالي: " . number_format($current_balance, 2) .
                " ج.م، المطلوب: " . number_format($amount, 2) . " ج.م"
            );
        }

        /* ---------- 3. خصم الرصيد (دالة موحدة) ---------- */
        // هذه الدالة تقوم بـ UPDATE وتُعيد true/false
        $updated = updateWalletBalance($customer_id, $amount, 'withdrawal');

        if (!$updated) {
            throw new Exception("فشل تحديث رصيد المحفظة");
        }

        /* ---------- 4. جلب الرصيد الجديد للعرض ---------- */
        $stmt = $pdo->prepare("SELECT balance FROM customer_wallets WHERE customer_id = ?");
        $stmt->execute([$customer_id]);
        $new_balance = $stmt->fetchColumn();

        /* ---------- 5. تسجيل المعاملة ---------- */
        $stmt = $pdo->prepare("
            INSERT INTO wallet_transactions
                (customer_id, amount, type, description, reference_type, status, transaction_date)
            VALUES (?, ?, 'withdrawal', ?, 'manual', 'completed', NOW())
        ");
        $stmt->execute([$customer_id, $amount, $description]);

        $pdo->commit();

        $_SESSION['success'] = "تم خصم الرصيد بنجاح: " .
            number_format($amount, 2) . " ج.م | الرصيد الجديد: " .
            number_format($new_balance, 2) . " ج.م";

        logActivity(
            'wallet_deduction',
            "خصم رصيد: $amount ج.م من العميل #$customer_id - $description",
            $_SESSION['admin_id']
        );

    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "خطأ في الخصم: " . $e->getMessage();
    }

    header('Location: wallets.php');
    exit;
}
// === 3. معالجة طلبات السحب (قبول/رفض) ===
    elseif (isset($_POST['wd_action']) && $_POST['wd_action'] === 'process_withdrawal') {
        $withdrawal_id = $_POST['wd_withdrawal_id'];
        $action_type   = $_POST['wd_action_type']; // approve | reject

    try {
        $pdo->beginTransaction();

        if ($action_type === 'approve') {
            // جلب بيانات السحب
            $stmt = $pdo->prepare(
                "SELECT customer_id, amount FROM wallet_transactions
                 WHERE id = ? AND status = 'pending' AND type = 'withdrawal'"
            );
            $stmt->execute([$withdrawal_id]);
            $wd = $stmt->fetch();

            if (!$wd) {
                throw new Exception('طلب السحب غير موجود أو تمت معالجته');
            }

            // خصم الرصيد
            $ok = updateWalletBalance($wd['customer_id'], $wd['amount'], 'withdrawal');
            if (!$ok) {
                throw new Exception('فشل خصم الرصيد');
            }

            // تحديث الحالة
            $stmt = $pdo->prepare("UPDATE wallet_transactions SET status = 'completed' WHERE id = ?");
            $stmt->execute([$withdrawal_id]);

            $_SESSION['success'] = "تمت الموافقة على السحب وخصم " .
                number_format($wd['amount'], 2) . " ج.م";

        } elseif ($action_type === 'reject') {
            // لا نلمس الرصيد أبدًا
            $stmt = $pdo->prepare("UPDATE wallet_transactions SET status = 'cancelled' WHERE id = ?");
            $stmt->execute([$withdrawal_id]);

            $_SESSION['success'] = "تم رفض طلب السحب (لم يتم خصم الرصيد)";
        }

        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = $e->getMessage();
    }

    header('Location: wallets.php');
    exit;
}
else {
        $_SESSION['error'] = "عملية غير معروفة أو غير مدعومة.";
        header('Location: wallets.php');
        exit;
    }
}
// جلب طلبات السحب المعلقة
$stmt = $pdo->query("
    SELECT wt.*, c.first_name, c.last_name, c.email, c.phone
    FROM wallet_transactions wt
    JOIN customers c ON wt.customer_id = c.id
    WHERE wt.type = 'withdrawal' AND wt.status = 'pending'
    ORDER BY wt.transaction_date DESC
");
$pending_withdrawals = $stmt->fetchAll();

// جلب إحصائيات المحفظة
$stmt = $pdo->query("
    SELECT 
        COUNT(*) as total_wallets,
        SUM(balance) as total_balance,
        SUM(total_deposited) as total_deposited,
        SUM(total_withdrawn) as total_withdrawn,
        AVG(balance) as avg_balance
    FROM customer_wallets
");
$wallet_stats = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة محافظ العملاء</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8fafc; color: #334155; }
        .admin-wrapper { display: flex; min-height: 100vh; }
        .main-content { flex: 1; margin-right: 260px; padding: 30px; }
        .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; background: #fff; padding: 20px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .card { background: #fff; border-radius: 12px; padding: 25px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .btn { padding: 10px 20px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: all 0.3s; }
        .btn-primary { background: #2563eb; color: #fff; }
        .btn-danger { background: #dc2626; color: #fff; }
        .btn-success { background: #16a34a; color: #fff; }
        .btn-warning { background: #d97706; color: #fff; }
        .btn-outline { background: transparent; border: 2px solid #2563eb; color: #2563eb; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: right; border-bottom: 1px solid #e2e8f0; }
        th { background: #f8fafc; font-weight: 600; color: #475569; }
        .form-group { margin-bottom: 15px; }
        .form-control { width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 20px; }
        .stat-card { background: #fff; padding: 20px; border-radius: 12px; text-align: center; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .stat-number { font-size: 2rem; font-weight: 700; margin-bottom: 5px; }
        .badge { padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .badge-success { background: #dcfce7; color: #166534; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        .search-form { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px; }
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); }
        .modal-content { background-color: white; margin: 5% auto; padding: 2rem; border-radius: 12px; width: 90%; max-width: 500px; }
    </style>
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
			<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i> <?= $_SESSION['success'] ?>
    </div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-error">
        <i class="fas fa-exclamation-circle"></i> <?= $_SESSION['error'] ?>
    </div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>
                <h1>إدارة محافظ العملاء</h1>
            </div>

            <!-- إحصائيات المحفظة -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number" style="color: #2563eb;">
                        <?= number_format($wallet_stats['total_wallets']) ?>
                    </div>
                    <p>إجمالي المحافظ</p>
                </div>
                <div class="stat-card">
                    <div class="stat-number" style="color: #16a34a;">
                        <?= number_format($wallet_stats['total_balance'] ?? 0, 2) ?> ج.م
                    </div>
                    <p>إجمالي الرصيد</p>
                </div>
                <div class="stat-card">
                    <div class="stat-number" style="color: #d97706;">
                        <?= number_format($wallet_stats['total_deposited'] ?? 0, 2) ?> ج.م
                    </div>
                    <p>إجمالي الإيداعات</p>
                </div>
                <div class="stat-card">
                    <div class="stat-number" style="color: #dc2626;">
                        <?= number_format($wallet_stats['total_withdrawn'] ?? 0, 2) ?> ج.م
                    </div>
                    <p>إجمالي السحوبات</p>
                </div>
            </div>

            <!-- نموذج البحث والتصفية -->
            <div class="card">
                <h3 style="margin-bottom: 15px;">بحث وتصفية</h3>
                <form method="GET" class="search-form">
                    <div class="form-group">
                        <input type="text" name="search" class="form-control" placeholder="بحث بالاسم، البريد، الهاتف..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="form-group">
                        <select name="status" class="form-control">
                            <option value="all">جميع المحافظ</option>
                            <option value="with_balance" <?= $status_filter === 'with_balance' ? 'selected' : '' ?>>بها رصيد</option>
                            <option value="no_balance" <?= $status_filter === 'no_balance' ? 'selected' : '' ?>>بدون رصيد</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <input type="number" name="min_balance" class="form-control" placeholder="الحد الأدنى للرصيد" value="<?= htmlspecialchars($min_balance) ?>" step="0.01">
                    </div>
                    <div class="form-group">
                        <input type="number" name="max_balance" class="form-control" placeholder="الحد الأقصى للرصيد" value="<?= htmlspecialchars($max_balance) ?>" step="0.01">
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary" style="width: 100%;">
                            <i class="fas fa-search"></i> بحث
                        </button>
                    </div>
                </form>
            </div>

            <!-- أزرار الإجراءات -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px;">
                <button onclick="showAddBalanceModal()" class="btn btn-primary">
                    <i class="fas fa-plus-circle"></i> إضافة رصيد
                </button>
                <button onclick="showDeductBalanceModal()" class="btn btn-danger">
                    <i class="fas fa-minus-circle"></i> خصم رصيد
                </button>
                <a href="wallet-transactions.php" class="btn btn-outline">
                    <i class="fas fa-list"></i> سجل المعاملات
                </a>
            </div>

            <!-- قائمة محافظ العملاء -->
            <div class="card">
                <h2 style="margin-bottom: 20px;">محافظ العملاء</h2>
                <table>
                    <thead>
                        <tr>
                            <th>العميل</th>
                            <th>البريد الإلكتروني</th>
                            <th>الهاتف</th>
                            <th>الرصيد الحالي</th>
                            <th>إجمالي الإيداعات</th>
                            <th>إجمالي السحوبات</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($wallets as $wallet): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($wallet['first_name'] . ' ' . $wallet['last_name']) ?></strong>
                                </td>
                                <td><?= htmlspecialchars($wallet['email']) ?></td>
                                <td><?= htmlspecialchars($wallet['phone']) ?></td>
                                <td style="font-weight: bold; color: #16a34a;">
                                    <?= number_format($wallet['balance'] ?? 0, 2) ?> ج.م
                                </td>
                                <td><?= number_format($wallet['total_deposited'] ?? 0, 2) ?> ج.م</td>
                                <td><?= number_format($wallet['total_withdrawn'] ?? 0, 2) ?> ج.م</td>
                                <td>
                                    <div style="display: flex; gap: 5px;">
                                        <button onclick="showCustomerTransactions(<?= $wallet['customer_id'] ?>)" 
                                                class="btn btn-outline" style="padding: 5px 10px; font-size: 12px;">
                                            <i class="fas fa-history"></i>
                                        </button>
                                        <button onclick="showAddBalanceModal(<?= $wallet['customer_id'] ?>)" 
                                                class="btn btn-success" style="padding: 5px 10px; font-size: 12px;">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                        <button onclick="showDeductBalanceModal(<?= $wallet['customer_id'] ?>)" 
                                                class="btn btn-danger" style="padding: 5px 10px; font-size: 12px;">
                                            <i class="fas fa-minus"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- طلبات السحب المعلقة -->
            <?php if (!empty($pending_withdrawals)): ?>
            <div class="card">
                <h2 style="margin-bottom: 20px; color: #d97706;">
                    <i class="fas fa-clock"></i> طلبات السحب المعلقة
                </h2>
                <table>
                    <thead>
                        <tr>
                            <th>العميل</th>
                            <th>المبلغ</th>
                            <th>طريقة السحب</th>
                            <th>معلومات الاستلام</th>
                            <th>التاريخ</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending_withdrawals as $withdrawal): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($withdrawal['first_name'] . ' ' . $withdrawal['last_name']) ?></strong>
                                    <br><small><?= htmlspecialchars($withdrawal['email']) ?></small>
                                </td>
                                <td style="font-weight: bold; color: #dc2626;">
                                    <?= number_format($withdrawal['amount'], 2) ?> ج.م
                                </td>
                                <td>
                                    <?= $withdrawal['description'] ?>
                                </td>
                                <td>
                                    <small><?= extractReceiverInfo($withdrawal['description']) ?></small>
                                </td>
                                <td>
                                    <?= date('Y-m-d H:i', strtotime($withdrawal['transaction_date'])) ?>
                                </td>
                                <td>
                                    <form method="POST" style="display: flex; gap: 5px;">
                                        <input type="hidden" name="action" value="process_withdrawal">
                                        <input type="hidden" name="withdrawal_id" value="<?= $withdrawal['id'] ?>">
                                        <button type="submit" name="action_type" value="approve" 
                                                class="btn btn-success" style="padding: 5px 10px; font-size: 12px;">
                                            <i class="fas fa-check"></i> قبول
                                        </button>
                                        <button type="submit" name="action_type" value="reject" 
                                                class="btn btn-danger" style="padding: 5px 10px; font-size: 12px;"
                                                onclick="return confirm('هل أنت متأكد من رفض طلب السحب؟')">
                                            <i class="fas fa-times"></i> رفض
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- نماذج الإجراءات -->
    <?php include 'wallet-modals.php'; ?>

    <script>
    function showAddBalanceModal(customerId = '') {
        if (customerId) {
            document.getElementById('add_customer_id').value = customerId;
        }
        document.getElementById('addBalanceModal').style.display = 'block';
    }

    function closeAddBalanceModal() {
        document.getElementById('addBalanceModal').style.display = 'none';
    }

    function showDeductBalanceModal(customerId = '') {
        if (customerId) {
            document.getElementById('deduct_customer_id').value = customerId;
        }
        document.getElementById('deductBalanceModal').style.display = 'block';
    }

    function closeDeductBalanceModal() {
        document.getElementById('deductBalanceModal').style.display = 'none';
    }

    function showCustomerTransactions(customerId) {
        window.open(`wallet-transactions.php?customer_id=${customerId}`, '_blank');
    }

    // إغلاق النماذج عند النقر خارجها
    window.onclick = function(event) {
        const addModal = document.getElementById('addBalanceModal');
        const deductModal = document.getElementById('deductBalanceModal');
        
        if (event.target == addModal) closeAddBalanceModal();
        if (event.target == deductModal) closeDeductBalanceModal();
    }
	
	// التأكد من إرسال القيمة الصحيحة
    document.addEventListener('DOMContentLoaded', function() {
    // عند فتح نموذج الخصم
    const deductModal = document.getElementById('deductBalanceModal');
    if (deductModal) {
        const deductForm = deductModal.querySelector('form');
        deductForm.addEventListener('submit', function(e) {
            const actionInput = this.querySelector('input[name="action"]');
            if (actionInput.value !== 'deduct_balance') {
                console.error('خطأ: قيمة action خاطئة:', actionInput.value);
                actionInput.value = 'deduct_balance';
                console.log('تم تصحيح القيمة إلى: deduct_balance');
            }
        });
    }
});
    </script>
</body>
</html>