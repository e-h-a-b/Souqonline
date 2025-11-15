<?php
session_start();
require_once '../config.php';
require_once '../functions.php';

// التحقق من الصلاحيات
if (!isset($_SESSION['admin_id']) || $_SESSION['admin_role'] != 'super_admin') {
    header('Location: login.php');
    exit;
}

// جلب قائمة المشرفين
$stmt = $pdo->query("
    SELECT a.*, r.name as role_name 
    FROM admins a 
    LEFT JOIN admin_roles r ON a.role_id = r.id 
    ORDER BY a.created_at DESC
");
$admins = $stmt->fetchAll();

// جلب الأدوار
$stmt = $pdo->query("SELECT * FROM admin_roles WHERE is_active = 1");
$roles = $stmt->fetchAll();

// معالجة إضافة مشرف جديد
if ($_POST['action'] ?? '' == 'add_admin') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role_id = $_POST['role_id'];
    
    try {
        $stmt = $pdo->prepare("INSERT INTO admins (username, email, password, role_id, role) VALUES (?, ?, ?, ?, 'admin')");
        $stmt->execute([$username, $email, $password, $role_id]);
        
        $_SESSION['success'] = "تم إضافة المشرف بنجاح";
        header('Location: admins.php');
        exit;
    } catch (PDOException $e) {
        $_SESSION['error'] = "خطأ في إضافة المشرف: " . $e->getMessage();
    }
}

// معالجة حذف مشرف
if ($_GET['action'] ?? '' == 'delete' && isset($_GET['id'])) {
    $admin_id = $_GET['id'];
    
    // منع حذف المشرف الحالي
    if ($admin_id == $_SESSION['admin_id']) {
        $_SESSION['error'] = "لا يمكن حذف حسابك الشخصي";
    } else {
        $stmt = $pdo->prepare("DELETE FROM admins WHERE id = ?");
        $stmt->execute([$admin_id]);
        $_SESSION['success'] = "تم حذف المشرف بنجاح";
    }
    header('Location: admins.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة المشرفين</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* إضافة الأنماط من index.php */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8fafc; color: #334155; }
        .admin-wrapper { display: flex; min-height: 100vh; }
        .main-content { flex: 1; margin-right: 260px; padding: 30px; }
        .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; background: #fff; padding: 20px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .card { background: #fff; border-radius: 12px; padding: 25px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .btn { padding: 10px 20px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: all 0.3s; }
        .btn-primary { background: #2563eb; color: #fff; }
        .btn-danger { background: #dc2626; color: #fff; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: right; border-bottom: 1px solid #e2e8f0; }
        th { background: #f8fafc; font-weight: 600; color: #475569; }
        .form-group { margin-bottom: 15px; }
        .form-control { width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px; }
 
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
                <h1>إدارة المشرفين</h1>
            </div>

            <!-- نموذج إضافة مشرف -->
            <div class="card">
                <h2 style="margin-bottom: 20px;">إضافة مشرف جديد</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="add_admin">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                        <div class="form-group">
                            <label>اسم المستخدم</label>
                            <input type="text" name="username" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>البريد الإلكتروني</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>كلمة المرور</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>الدور</label>
                            <select name="role_id" class="form-control" required>
                                <?php foreach ($roles as $role): ?>
                                    <option value="<?= $role['id'] ?>"><?= $role['name'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary" style="margin-top: 15px;">
                        <i class="fas fa-plus"></i> إضافة مشرف
                    </button>
                </form>
            </div>

            <!-- قائمة المشرفين -->
            <div class="card">
                <h2 style="margin-bottom: 20px;">قائمة المشرفين</h2>
                <table>
                    <thead>
                        <tr>
                            <th>اسم المستخدم</th>
                            <th>البريد الإلكتروني</th>
                            <th>الدور</th>
                            <th>آخر دخول</th>
                            <th>الحالة</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($admins as $admin): ?>
                            <tr>
                                <td><?= htmlspecialchars($admin['username']) ?></td>
                                <td><?= htmlspecialchars($admin['email']) ?></td>
                                <td><?= htmlspecialchars($admin['role_name']) ?></td>
                                <td><?= $admin['last_login'] ? date('Y-m-d H:i', strtotime($admin['last_login'])) : 'لم يسجل دخول' ?></td>
                                <td>
                                    <span class="badge <?= $admin['is_active'] ? 'badge-success' : 'badge-danger' ?>">
                                        <?= $admin['is_active'] ? 'نشط' : 'غير نشط' ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($admin['id'] != $_SESSION['admin_id']): ?>
                                        <a href="admins.php?action=delete&id=<?= $admin['id'] ?>" 
                                           class="btn btn-danger" 
                                           onclick="return confirm('هل أنت متأكد من حذف هذا المشرف؟')">
                                            <i class="fas fa-trash"></i> حذف
                                        </a>
                                    <?php else: ?>
                                        <span style="color: #64748b;">الحساب الحالي</span>
                                    <?php endif; ?>
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