<?php
/**
 * صفحة إدارة تجار الجملة
 */
session_start();
require_once '../config.php';
require_once '../functions.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// معالجة العمليات عبر AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    try {
        switch ($_POST['action']) {
            case 'add_wholesaler':
                $name = cleanInput($_POST['name']);
                $email = cleanInput($_POST['email']);
                $phone = cleanInput($_POST['phone']);
                $company = cleanInput($_POST['company']);
                $tax_number = cleanInput($_POST['tax_number']);
                $address = cleanInput($_POST['address']);
                $specialty = cleanInput($_POST['specialty']);
                $discount_rate = floatval($_POST['discount_rate']);
                $credit_limit = floatval($_POST['credit_limit']);
                $payment_terms = cleanInput($_POST['payment_terms']);
                $contact_person = cleanInput($_POST['contact_person']);
                $notes = cleanInput($_POST['notes']);
                $status = cleanInput($_POST['status']);
                
                $stmt = $pdo->prepare("
                    INSERT INTO wholesalers (name, email, phone, company, tax_number, address, specialty, 
                                           discount_rate, credit_limit, payment_terms, contact_person, notes, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $name, $email, $phone, $company, $tax_number, $address, $specialty,
                    $discount_rate, $credit_limit, $payment_terms, $contact_person, $notes, $status
                ]);
                
                echo json_encode(['success' => true, 'message' => 'تم إضافة تاجر الجملة بنجاح']);
                break;
                
            case 'update_wholesaler':
                $id = intval($_POST['id']);
                $name = cleanInput($_POST['name']);
                $email = cleanInput($_POST['email']);
                $phone = cleanInput($_POST['phone']);
                $company = cleanInput($_POST['company']);
                $tax_number = cleanInput($_POST['tax_number']);
                $address = cleanInput($_POST['address']);
                $specialty = cleanInput($_POST['specialty']);
                $discount_rate = floatval($_POST['discount_rate']);
                $credit_limit = floatval($_POST['credit_limit']);
                $payment_terms = cleanInput($_POST['payment_terms']);
                $contact_person = cleanInput($_POST['contact_person']);
                $notes = cleanInput($_POST['notes']);
                $status = cleanInput($_POST['status']);
                
                $stmt = $pdo->prepare("
                    UPDATE wholesalers SET 
                        name = ?, email = ?, phone = ?, company = ?, tax_number = ?, address = ?, specialty = ?,
                        discount_rate = ?, credit_limit = ?, payment_terms = ?, contact_person = ?, notes = ?, status = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $name, $email, $phone, $company, $tax_number, $address, $specialty,
                    $discount_rate, $credit_limit, $payment_terms, $contact_person, $notes, $status, $id
                ]);
                
                echo json_encode(['success' => true, 'message' => 'تم تحديث بيانات تاجر الجملة بنجاح']);
                break;
                
            case 'delete_wholesaler':
                $id = intval($_POST['id']);
                
                $stmt = $pdo->prepare("DELETE FROM wholesalers WHERE id = ?");
                $stmt->execute([$id]);
                
                echo json_encode(['success' => true, 'message' => 'تم حذف تاجر الجملة بنجاح']);
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => 'عملية غير معروفة']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'حدث خطأ: ' . $e->getMessage()]);
    }
    exit;
}

// جلب تجار الجملة
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';

$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(name LIKE ? OR email LIKE ? OR phone LIKE ? OR company LIKE ?)";
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

$where_sql = '';
if (!empty($where)) {
    $where_sql = 'WHERE ' . implode(' AND ', $where);
}

$stmt = $pdo->prepare("SELECT * FROM wholesalers $where_sql ORDER BY created_at DESC");
$stmt->execute($params);
$wholesalers = $stmt->fetchAll();

// إحصائيات
$stats = [
    'total' => count($wholesalers),
    'active' => 0,
    'inactive' => 0,
    'pending' => 0
];

foreach ($wholesalers as $wholesaler) {
    $stats[$wholesaler['status']]++;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة تجار الجملة - لوحة التحكم</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
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
        .btn-warning { background: #d97706; color: #fff; }
        .btn-warning:hover { background: #b45309; }
        
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
        .badge-danger { background: #fee2e2; color: #991b1b; }
        .badge-secondary { background: #f1f5f9; color: #475569; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        
        .actions {
            display: flex;
            gap: 8px;
        }
        .action-btn {
            padding: 6px 10px;
            border-radius: 6px;
            font-size: 12px;
            text-decoration: none;
            cursor: pointer;
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
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .stat-item {
            background: #f8fafc;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }
        .stat-number {
            font-size: 24px;
            font-weight: 700;
            color: #1e293b;
        }
        .stat-label {
            font-size: 12px;
            color: #64748b;
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
            align-items: center;
            justify-content: center;
        }
        .modal-content {
            background: #fff;
            border-radius: 12px;
            width: 600px;
            max-width: 90%;
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
        
        .form-row {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }
        .form-col {
            flex: 1;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #334155;
        }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 10px 12px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
        }
        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }
        
        .wholesaler-avatar {
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
        
        .wholesaler-info {
            display: flex;
            align-items: center;
            gap: 12px;
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
                    <h1>إدارة تجار الجملة</h1>
                    <p style="color: #64748b; margin-top: 5px;">إدارة تجار الجملة والموزعين للمنتجات</p>
                </div>
                <button type="button" class="btn btn-success" onclick="openWholesalerModal()">
                    <i class="fas fa-plus"></i>
                    إضافة تاجر جملة
                </button>
            </div>

            <!-- Quick Stats -->
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-number"><?= $stats['total'] ?></div>
                    <div class="stat-label">إجمالي التجار</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?= $stats['active'] ?></div>
                    <div class="stat-label">نشطين</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?= $stats['inactive'] ?></div>
                    <div class="stat-label">غير نشطين</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?= $stats['pending'] ?></div>
                    <div class="stat-label">قيد المراجعة</div>
                </div>
            </div>

            <!-- Filters -->
            <div class="filters">
                <form method="get" action="wholesalers.php">
                    <div class="filter-row">
                        <div class="form-group">
                            <label for="search">بحث</label>
                            <input type="text" id="search" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="ابحث بالاسم، البريد، الهاتف، الشركة...">
                        </div>
                        <div class="form-group">
                            <label for="status">الحالة</label>
                            <select id="status" name="status">
                                <option value="all">جميع الحالات</option>
                                <option value="active" <?= $status == 'active' ? 'selected' : '' ?>>نشط</option>
                                <option value="inactive" <?= $status == 'inactive' ? 'selected' : '' ?>>غير نشط</option>
                                <option value="pending" <?= $status == 'pending' ? 'selected' : '' ?>>قيد المراجعة</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter"></i>
                                تصفية
                            </button>
                            <a href="wholesalers.php" class="btn btn-secondary">
                                <i class="fas fa-refresh"></i>
                                إعادة تعيين
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Wholesalers Table -->
            <div class="card">
                <div class="card-header">
                    <h2>تجار الجملة (<?= count($wholesalers) ?>)</h2>
                </div>

                <?php if (empty($wholesalers)): ?>
                    <p style="text-align: center; color: #64748b; padding: 40px;">لا توجد تجار جملة</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>التاجر</th>
                                <th>معلومات الاتصال</th>
                                <th>الشركة</th>
                                <th>التخصص</th>
                                <th>نسبة الخصم</th>
                                <th>الرصيد</th>
                                <th>الحالة</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($wholesalers as $wholesaler): ?>
                                <tr>
                                    <td>
                                        <div class="wholesaler-info">
                                            <div class="wholesaler-avatar">
                                                <?= mb_substr($wholesaler['name'], 0, 1) ?>
                                            </div>
                                            <div>
                                                <strong><?= htmlspecialchars($wholesaler['name']) ?></strong>
                                                <?php if ($wholesaler['contact_person']): ?>
                                                    <br><small style="color: #64748b;"><?= htmlspecialchars($wholesaler['contact_person']) ?></small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <span><?= htmlspecialchars($wholesaler['phone']) ?></span>
                                            <?php if ($wholesaler['email']): ?>
                                                <br><small style="color: #64748b;"><?= htmlspecialchars($wholesaler['email']) ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($wholesaler['company']): ?>
                                            <?= htmlspecialchars($wholesaler['company']) ?>
                                            <?php if ($wholesaler['tax_number']): ?>
                                                <br><small style="color: #64748b;">الرقم الضريبي: <?= htmlspecialchars($wholesaler['tax_number']) ?></small>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span style="color: #64748b;">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($wholesaler['specialty']) ?: '-' ?></td>
                                    <td>
                                        <?php if ($wholesaler['discount_rate'] > 0): ?>
                                            <span class="badge badge-success"><?= $wholesaler['discount_rate'] ?>%</span>
                                        <?php else: ?>
                                            <span style="color: #64748b;">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div>
                                            <strong><?= formatPrice($wholesaler['current_balance']) ?></strong>
                                            <?php if ($wholesaler['credit_limit'] > 0): ?>
                                                <br><small style="color: #64748b;">الحد: <?= formatPrice($wholesaler['credit_limit']) ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php
                                        $status_badges = [
                                            'active' => ['badge-success', 'نشط'],
                                            'inactive' => ['badge-danger', 'غير نشط'],
                                            'pending' => ['badge-warning', 'قيد المراجعة']
                                        ];
                                        $badge = $status_badges[$wholesaler['status']] ?? ['badge-secondary', $wholesaler['status']];
                                        ?>
                                        <span class="badge <?= $badge[0] ?>"><?= $badge[1] ?></span>
                                    </td>
                                    <td>
                                        <div class="actions">
                                            <button type="button" class="action-btn btn-primary" onclick="editWholesaler(<?= htmlspecialchars(json_encode($wholesaler)) ?>)" title="تعديل">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="action-btn btn-danger" onclick="deleteWholesaler(<?= $wholesaler['id'] ?>)" title="حذف">
                                                <i class="fas fa-trash"></i>
                                            </button>
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

    <!-- Add/Edit Wholesaler Modal -->
    <div id="wholesalerModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">إضافة تاجر جملة جديد</h3>
                <span class="close" onclick="closeWholesalerModal()">&times;</span>
            </div>
            <form id="wholesalerForm">
                <div class="modal-body">
                    <input type="hidden" id="wholesaler_id" name="id">
                    <input type="hidden" id="form_action" name="action" value="add_wholesaler">
                    
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="name">اسم التاجر *</label>
                                <input type="text" id="name" name="name" required>
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label for="company">اسم الشركة</label>
                                <input type="text" id="company" name="company">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="email">البريد الإلكتروني</label>
                                <input type="email" id="email" name="email">
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label for="phone">رقم الهاتف *</label>
                                <input type="tel" id="phone" name="phone" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="tax_number">الرقم الضريبي</label>
                                <input type="text" id="tax_number" name="tax_number">
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label for="specialty">التخصص</label>
                                <input type="text" id="specialty" name="specialty" placeholder="مثال: إلكترونيات، ملابس، إلخ">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="discount_rate">نسبة الخصم (%)</label>
                                <input type="number" id="discount_rate" name="discount_rate" step="0.01" min="0" max="100" value="0">
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label for="credit_limit">حد الائتمان</label>
                                <input type="number" id="credit_limit" name="credit_limit" step="0.01" min="0" value="0">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="address">العنوان</label>
                        <textarea id="address" name="address" rows="3"></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="payment_terms">شروط الدفع</label>
                                <input type="text" id="payment_terms" name="payment_terms" placeholder="مثال: 30 يوم صافي">
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label for="contact_person">الشخص المسؤول</label>
                                <input type="text" id="contact_person" name="contact_person">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="status">الحالة</label>
                        <select id="status" name="status" required>
                            <option value="active">نشط</option>
                            <option value="inactive">غير نشط</option>
                            <option value="pending">قيد المراجعة</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="notes">ملاحظات</label>
                        <textarea id="notes" name="notes" rows="3" placeholder="ملاحظات إضافية..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeWholesalerModal()">إلغاء</button>
                    <button type="submit" class="btn btn-success">حفظ</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // فتح نافذة إضافة تاجر جديد
        function openWholesalerModal() {
            document.getElementById('modalTitle').textContent = 'إضافة تاجر جملة جديد';
            document.getElementById('form_action').value = 'add_wholesaler';
            document.getElementById('wholesalerForm').reset();
            document.getElementById('wholesaler_id').value = '';
            document.getElementById('wholesalerModal').style.display = 'flex';
        }
        
        // فتح نافذة تعديل تاجر
        function editWholesaler(wholesaler) {
            document.getElementById('modalTitle').textContent = 'تعديل تاجر الجملة';
            document.getElementById('form_action').value = 'update_wholesaler';
            document.getElementById('wholesaler_id').value = wholesaler.id;
            document.getElementById('name').value = wholesaler.name;
            document.getElementById('email').value = wholesaler.email || '';
            document.getElementById('phone').value = wholesaler.phone;
            document.getElementById('company').value = wholesaler.company || '';
            document.getElementById('tax_number').value = wholesaler.tax_number || '';
            document.getElementById('address').value = wholesaler.address || '';
            document.getElementById('specialty').value = wholesaler.specialty || '';
            document.getElementById('discount_rate').value = wholesaler.discount_rate || 0;
            document.getElementById('credit_limit').value = wholesaler.credit_limit || 0;
            document.getElementById('payment_terms').value = wholesaler.payment_terms || '';
            document.getElementById('contact_person').value = wholesaler.contact_person || '';
            document.getElementById('status').value = wholesaler.status;
            document.getElementById('notes').value = wholesaler.notes || '';
            document.getElementById('wholesalerModal').style.display = 'flex';
        }
        
        // إغلاق النافذة
        function closeWholesalerModal() {
            document.getElementById('wholesalerModal').style.display = 'none';
        }
        
        // حذف تاجر
        function deleteWholesaler(id) {
            if (confirm('هل أنت متأكد من حذف هذا التاجر؟')) {
                const formData = new FormData();
                formData.append('action', 'delete_wholesaler');
                formData.append('id', id);
                
                fetch('wholesalers.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        location.reload();
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    alert('حدث خطأ أثناء الحذف');
                });
            }
        }
        
        // معالجة نموذج الحفظ
        document.getElementById('wholesalerForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const action = formData.get('action');
            
            fetch('wholesalers.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    closeWholesalerModal();
                    location.reload();
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                alert('حدث خطأ أثناء الحفظ');
            });
        });
        
        // إغلاق النافذة عند النقر خارجها
        document.getElementById('wholesalerModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeWholesalerModal();
            }
        });
    </script>
</body>
</html>