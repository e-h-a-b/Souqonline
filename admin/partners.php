<?php
/**
 * صفحة إدارة الشركاء
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
            case 'add_partner':
                $name = cleanInput($_POST['name']);
                $email = cleanInput($_POST['email']);
                $phone = cleanInput($_POST['phone']);
                $company = cleanInput($_POST['company']);
                $partnership_type = cleanInput($_POST['partnership_type']);
                $investment_amount = floatval($_POST['investment_amount']);
                $profit_share = floatval($_POST['profit_share']);
                $start_date = cleanInput($_POST['start_date']);
                $end_date = cleanInput($_POST['end_date']);
                $responsibilities = cleanInput($_POST['responsibilities']);
                $contact_person = cleanInput($_POST['contact_person']);
                $notes = cleanInput($_POST['notes']);
                $status = cleanInput($_POST['status']);
                
                $stmt = $pdo->prepare("
                    INSERT INTO partners (name, email, phone, company, partnership_type, investment_amount, 
                                        profit_share, start_date, end_date, responsibilities, contact_person, notes, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $name, $email, $phone, $company, $partnership_type, $investment_amount,
                    $profit_share, $start_date, $end_date, $responsibilities, $contact_person, $notes, $status
                ]);
                
                echo json_encode(['success' => true, 'message' => 'تم إضافة الشريك بنجاح']);
                break;
                
            case 'update_partner':
                $id = intval($_POST['id']);
                $name = cleanInput($_POST['name']);
                $email = cleanInput($_POST['email']);
                $phone = cleanInput($_POST['phone']);
                $company = cleanInput($_POST['company']);
                $partnership_type = cleanInput($_POST['partnership_type']);
                $investment_amount = floatval($_POST['investment_amount']);
                $profit_share = floatval($_POST['profit_share']);
                $start_date = cleanInput($_POST['start_date']);
                $end_date = cleanInput($_POST['end_date']);
                $responsibilities = cleanInput($_POST['responsibilities']);
                $contact_person = cleanInput($_POST['contact_person']);
                $notes = cleanInput($_POST['notes']);
                $status = cleanInput($_POST['status']);
                
                $stmt = $pdo->prepare("
                    UPDATE partners SET 
                        name = ?, email = ?, phone = ?, company = ?, partnership_type = ?, investment_amount = ?,
                        profit_share = ?, start_date = ?, end_date = ?, responsibilities = ?, contact_person = ?, notes = ?, status = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $name, $email, $phone, $company, $partnership_type, $investment_amount,
                    $profit_share, $start_date, $end_date, $responsibilities, $contact_person, $notes, $status, $id
                ]);
                
                echo json_encode(['success' => true, 'message' => 'تم تحديث بيانات الشريك بنجاح']);
                break;
                
            case 'delete_partner':
                $id = intval($_POST['id']);
                
                $stmt = $pdo->prepare("DELETE FROM partners WHERE id = ?");
                $stmt->execute([$id]);
                
                echo json_encode(['success' => true, 'message' => 'تم حذف الشريك بنجاح']);
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => 'عملية غير معروفة']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'حدث خطأ: ' . $e->getMessage()]);
    }
    exit;
}

// جلب الشركاء
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$type = $_GET['type'] ?? '';

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

if (!empty($type) && $type != 'all') {
    $where[] = "partnership_type = ?";
    $params[] = $type;
}

$where_sql = '';
if (!empty($where)) {
    $where_sql = 'WHERE ' . implode(' AND ', $where);
}

$stmt = $pdo->prepare("SELECT * FROM partners $where_sql ORDER BY created_at DESC");
$stmt->execute($params);
$partners = $stmt->fetchAll();

// إحصائيات
$stats = [
    'total' => count($partners),
    'active' => 0,
    'inactive' => 0,
    'pending' => 0
];

foreach ($partners as $partner) {
    $stats[$partner['status']]++;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الشركاء - لوحة التحكم</title>
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
        
        .partner-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #10b981, #059669);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: #fff;
            font-size: 16px;
        }
        
        .investment-badge {
            background: #fef3c7;
            color: #92400e;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
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
                    <h1>إدارة الشركاء</h1>
                    <p style="color: #64748b; margin-top: 5px;">إدارة شركاء العمل والمستثمرين</p>
                </div>
                <button type="button" class="btn btn-success" onclick="openPartnerModal()">
                    <i class="fas fa-plus"></i>
                    إضافة شريك جديد
                </button>
            </div>

            <!-- Quick Stats -->
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-number"><?= $stats['total'] ?></div>
                    <div class="stat-label">إجمالي الشركاء</div>
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
                <form method="get" action="partners.php">
                    <div class="filter-row">
                        <div class="form-group">
                            <label for="search">بحث</label>
                            <input type="text" id="search" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="ابحث بالاسم، البريد، الهاتف، الشركة...">
                        </div>
                        <div class="form-group">
                            <label for="type">نوع الشراكة</label>
                            <select id="type" name="type">
                                <option value="all">جميع الأنواع</option>
                                <option value="supplier" <?= $type == 'supplier' ? 'selected' : '' ?>>مورد</option>
                                <option value="investor" <?= $type == 'investor' ? 'selected' : '' ?>>مستثمر</option>
                                <option value="distributor" <?= $type == 'distributor' ? 'selected' : '' ?>>موزع</option>
                                <option value="strategic" <?= $type == 'strategic' ? 'selected' : '' ?>>استراتيجي</option>
                            </select>
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
                            <a href="partners.php" class="btn btn-secondary">
                                <i class="fas fa-refresh"></i>
                                إعادة تعيين
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Partners Table -->
            <div class="card">
                <div class="card-header">
                    <h2>الشركاء (<?= count($partners) ?>)</h2>
                </div>

                <?php if (empty($partners)): ?>
                    <p style="text-align: center; color: #64748b; padding: 40px;">لا توجد شركاء</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>الشريك</th>
                                <th>معلومات الاتصال</th>
                                <th>الشركة</th>
                                <th>نوع الشراكة</th>
                                <th>رأس المال</th>
                                <th>نسبة الأرباح</th>
                                <th>مدة الشراكة</th>
                                <th>الحالة</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($partners as $partner): ?>
                                <tr>
                                    <td>
                                        <div class="wholesaler-info">
                                            <div class="partner-avatar">
                                                <?= mb_substr($partner['name'], 0, 1) ?>
                                            </div>
                                            <div>
                                                <strong><?= htmlspecialchars($partner['name']) ?></strong>
                                                <?php if ($partner['contact_person']): ?>
                                                    <br><small style="color: #64748b;"><?= htmlspecialchars($partner['contact_person']) ?></small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <span><?= htmlspecialchars($partner['phone']) ?></span>
                                            <?php if ($partner['email']): ?>
                                                <br><small style="color: #64748b;"><?= htmlspecialchars($partner['email']) ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($partner['company']) ?: '-' ?>
                                    </td>
                                    <td>
                                        <?php
                                        $partnership_types = [
                                            'supplier' => 'مورد',
                                            'investor' => 'مستثمر',
                                            'distributor' => 'موزع',
                                            'strategic' => 'استراتيجي'
                                        ];
                                        echo $partnership_types[$partner['partnership_type']] ?? $partner['partnership_type'];
                                        ?>
                                    </td>
                                    <td>
                                        <?php if ($partner['investment_amount'] > 0): ?>
                                            <span class="investment-badge"><?= formatPrice($partner['investment_amount']) ?></span>
                                        <?php else: ?>
                                            <span style="color: #64748b;">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($partner['profit_share'] > 0): ?>
                                            <span class="badge badge-success"><?= $partner['profit_share'] ?>%</span>
                                        <?php else: ?>
                                            <span style="color: #64748b;">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($partner['start_date']): ?>
                                            <?= date('Y-m-d', strtotime($partner['start_date'])) ?>
                                            <?php if ($partner['end_date']): ?>
                                                <br><small style="color: #64748b;">إلى <?= date('Y-m-d', strtotime($partner['end_date'])) ?></small>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span style="color: #64748b;">غير محدد</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $status_badges = [
                                            'active' => ['badge-success', 'نشط'],
                                            'inactive' => ['badge-danger', 'غير نشط'],
                                            'pending' => ['badge-warning', 'قيد المراجعة']
                                        ];
                                        $badge = $status_badges[$partner['status']] ?? ['badge-secondary', $partner['status']];
                                        ?>
                                        <span class="badge <?= $badge[0] ?>"><?= $badge[1] ?></span>
                                    </td>
                                    <td>
                                        <div class="actions">
                                            <button type="button" class="action-btn btn-primary" onclick="editPartner(<?= htmlspecialchars(json_encode($partner)) ?>)" title="تعديل">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="action-btn btn-danger" onclick="deletePartner(<?= $partner['id'] ?>)" title="حذف">
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

    <!-- Add/Edit Partner Modal -->
    <div id="partnerModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="partnerModalTitle">إضافة شريك جديد</h3>
                <span class="close" onclick="closePartnerModal()">&times;</span>
            </div>
            <form id="partnerForm">
                <div class="modal-body">
                    <input type="hidden" id="partner_id" name="id">
                    <input type="hidden" id="partner_form_action" name="action" value="add_partner">
                    
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="partner_name">اسم الشريك *</label>
                                <input type="text" id="partner_name" name="name" required>
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label for="partner_company">اسم الشركة</label>
                                <input type="text" id="partner_company" name="company">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="partner_email">البريد الإلكتروني</label>
                                <input type="email" id="partner_email" name="email">
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label for="partner_phone">رقم الهاتف *</label>
                                <input type="tel" id="partner_phone" name="phone" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="partnership_type">نوع الشراكة</label>
                                <select id="partnership_type" name="partnership_type" required>
                                    <option value="supplier">مورد</option>
                                    <option value="investor">مستثمر</option>
                                    <option value="distributor">موزع</option>
                                    <option value="strategic">استراتيجي</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label for="contact_person">الشخص المسؤول</label>
                                <input type="text" id="contact_person" name="contact_person">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="investment_amount">رأس المال</label>
                                <input type="number" id="investment_amount" name="investment_amount" step="0.01" min="0" value="0">
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label for="profit_share">نسبة الأرباح (%)</label>
                                <input type="number" id="profit_share" name="profit_share" step="0.01" min="0" max="100" value="0">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="start_date">تاريخ البدء</label>
                                <input type="date" id="start_date" name="start_date">
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label for="end_date">تاريخ الانتهاء</label>
                                <input type="date" id="end_date" name="end_date">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="responsibilities">المسؤوليات</label>
                        <textarea id="responsibilities" name="responsibilities" rows="3" placeholder="مسؤوليات الشريك في العمل..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="partner_status">الحالة</label>
                        <select id="partner_status" name="status" required>
                            <option value="active">نشط</option>
                            <option value="inactive">غير نشط</option>
                            <option value="pending">قيد المراجعة</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="partner_notes">ملاحظات</label>
                        <textarea id="partner_notes" name="notes" rows="3" placeholder="ملاحظات إضافية..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closePartnerModal()">إلغاء</button>
                    <button type="submit" class="btn btn-success">حفظ</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // فتح نافذة إضافة شريك جديد
        function openPartnerModal() {
            document.getElementById('partnerModalTitle').textContent = 'إضافة شريك جديد';
            document.getElementById('partner_form_action').value = 'add_partner';
            document.getElementById('partnerForm').reset();
            document.getElementById('partner_id').value = '';
            document.getElementById('partnerModal').style.display = 'flex';
        }
        
        // فتح نافذة تعديل شريك
        function editPartner(partner) {
            document.getElementById('partnerModalTitle').textContent = 'تعديل الشريك';
            document.getElementById('partner_form_action').value = 'update_partner';
            document.getElementById('partner_id').value = partner.id;
            document.getElementById('partner_name').value = partner.name;
            document.getElementById('partner_email').value = partner.email || '';
            document.getElementById('partner_phone').value = partner.phone;
            document.getElementById('partner_company').value = partner.company || '';
            document.getElementById('partnership_type').value = partner.partnership_type;
            document.getElementById('investment_amount').value = partner.investment_amount || 0;
            document.getElementById('profit_share').value = partner.profit_share || 0;
            document.getElementById('start_date').value = partner.start_date || '';
            document.getElementById('end_date').value = partner.end_date || '';
            document.getElementById('responsibilities').value = partner.responsibilities || '';
            document.getElementById('contact_person').value = partner.contact_person || '';
            document.getElementById('partner_status').value = partner.status;
            document.getElementById('partner_notes').value = partner.notes || '';
            document.getElementById('partnerModal').style.display = 'flex';
        }
        
        // إغلاق النافذة
        function closePartnerModal() {
            document.getElementById('partnerModal').style.display = 'none';
        }
        
        // حذف شريك
        function deletePartner(id) {
            if (confirm('هل أنت متأكد من حذف هذا الشريك؟')) {
                const formData = new FormData();
                formData.append('action', 'delete_partner');
                formData.append('id', id);
                
                fetch('partners.php', {
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
        document.getElementById('partnerForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const action = formData.get('action');
            
            fetch('partners.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    closePartnerModal();
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
        document.getElementById('partnerModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closePartnerModal();
            }
        });
    </script>
</body>
</html>