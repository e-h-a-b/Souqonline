<?php
session_start();
require_once 'config.php';
require_once 'functions.php';

if (!isset($_SESSION['customer_id'])) {
    header('Location: login.php');
    exit;
}

$customer_id = $_SESSION['customer_id'];
$format = $_GET['format'] ?? 'excel';

// نفس منطق البحث من wallet-history.php
$type_filter = $_GET['type'] ?? 'all';
$status_filter = $_GET['status'] ?? 'all';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

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

$stmt = $pdo->prepare("
    SELECT wt.*, c.first_name, c.last_name 
    FROM wallet_transactions wt
    JOIN customers c ON wt.customer_id = c.id
    $where_sql
    ORDER BY wt.transaction_date DESC
");
$stmt->execute($params);
$transactions = $stmt->fetchAll();

// جلب بيانات العميل
$stmt = $pdo->prepare("SELECT first_name, last_name, email FROM customers WHERE id = ?");
$stmt->execute([$customer_id]);
$customer = $stmt->fetch();

if ($format === 'excel') {
    // تصدير Excel
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="wallet-transactions-' . date('Y-m-d') . '.xls"');
    
    echo "<table border='1'>";
    echo "<tr><th colspan='6'>سجل معاملات المحفظة - " . htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']) . "</th></tr>";
    echo "<tr><th>نوع المعاملة</th><th>المبلغ</th><th>الوصف</th><th>الحالة</th><th>التاريخ</th><th>الوقت</th></tr>";
    
    foreach ($transactions as $transaction) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($transaction['type']) . "</td>";
        echo "<td>" . number_format($transaction['amount'], 2) . " ج.م</td>";
        echo "<td>" . htmlspecialchars($transaction['description']) . "</td>";
        echo "<td>" . htmlspecialchars($transaction['status']) . "</td>";
        echo "<td>" . date('Y-m-d', strtotime($transaction['transaction_date'])) . "</td>";
        echo "<td>" . date('H:i', strtotime($transaction['transaction_date'])) . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
} elseif ($format === 'pdf') {
    // تصدير PDF (يتطلب مكتبة مثل TCPDF أو FPDF)
    // هذا مثال مبسط - تحتاج لتنفيذ حقيقي مع مكتبة PDF
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="wallet-transactions-' . date('Y-m-d') . '.pdf"');
    
    // هنا يمكنك استخدام مكتبة PDF لإنشاء ملف PDF
    echo "PDF export feature will be implemented with a PDF library";
    
} elseif ($format === 'print') {
    // واجهة الطباعة
    ?>
    <!DOCTYPE html>
    <html lang="ar" dir="rtl">
    <head>
        <meta charset="UTF-8">
        <title>طباعة سجل المعاملات</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            th, td { border: 1px solid #000; padding: 8px; text-align: right; }
            th { background: #f0f0f0; }
            @media print {
                body { margin: 0; }
                .no-print { display: none; }
            }
        </style>
    </head>
    <body>
        <div class="no-print" style="margin-bottom: 20px;">
            <button onclick="window.print()" style="padding: 10px 20px;">طباعة</button>
            <button onclick="window.close()" style="padding: 10px 20px; margin-right: 10px;">إغلاق</button>
        </div>
        
        <h1>سجل معاملات المحفظة</h1>
        <p><strong>العميل:</strong> <?= htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']) ?></p>
        <p><strong>تاريخ التقرير:</strong> <?= date('Y-m-d H:i') ?></p>
        
        <table>
            <thead>
                <tr>
                    <th>نوع المعاملة</th>
                    <th>المبلغ</th>
                    <th>الوصف</th>
                    <th>الحالة</th>
                    <th>التاريخ</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transactions as $transaction): ?>
                    <tr>
                        <td><?= htmlspecialchars($transaction['type']) ?></td>
                        <td><?= number_format($transaction['amount'], 2) ?> ج.م</td>
                        <td><?= htmlspecialchars($transaction['description']) ?></td>
                        <td><?= htmlspecialchars($transaction['status']) ?></td>
                        <td><?= date('Y-m-d H:i', strtotime($transaction['transaction_date'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <script>
            window.onload = function() {
                window.print();
            };
        </script>
    </body>
    </html>
    <?php
}