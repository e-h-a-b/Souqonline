<?php
/**
 * معالج التثبيت التلقائي للمتجر الإلكتروني
 * قم بحذف هذا الملف بعد اكتمال التثبيت
 */

session_start();

$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$errors = [];
$success = false;

// معالجة خطوة 1 - فحص المتطلبات
if ($step === 1 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $step = 2;
}

// معالجة خطوة 2 - قاعدة البيانات
if ($step === 2 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $db_host = $_POST['db_host'] ?? 'localhost';
    $db_name = $_POST['db_name'] ?? 'ecommerce_pro';
    $db_user = $_POST['db_user'] ?? 'root';
    $db_pass = $_POST['db_pass'] ?? '';
    
    try {
        // اختبار الاتصال
        $pdo = new PDO("mysql:host=$db_host", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // إنشاء قاعدة البيانات
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `$db_name`");
        
        // قراءة وتنفيذ ملف SQL
        if (file_exists('db.sql')) {
            $sql = file_get_contents('db.sql');
            $pdo->exec($sql);
        }
        
        // حفظ بيانات الاتصال
        $_SESSION['db_config'] = [
            'host' => $db_host,
            'name' => $db_name,
            'user' => $db_user,
            'pass' => $db_pass
        ];
        
        $step = 3;
    } catch (Exception $e) {
        $errors[] = 'خطأ في الاتصال: ' . $e->getMessage();
    }
}

// معالجة خطوة 3 - إعدادات المتجر
if ($step === 3 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $store_name = $_POST['store_name'] ?? '';
    $store_email = $_POST['store_email'] ?? '';
    $store_phone = $_POST['store_phone'] ?? '';
    $admin_username = $_POST['admin_username'] ?? '';
    $admin_password = $_POST['admin_password'] ?? '';
    $admin_email = $_POST['admin_email'] ?? '';
    
    if (empty($store_name) || empty($admin_username) || empty($admin_password)) {
        $errors[] = 'يرجى ملء جميع الحقول المطلوبة';
    } else {
        try {
            $db = $_SESSION['db_config'];
            $pdo = new PDO("mysql:host={$db['host']};dbname={$db['name']};charset=utf8mb4", 
                          $db['user'], $db['pass']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // تحديث إعدادات المتجر
            $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
            $stmt->execute([$store_name, 'store_name']);
            $stmt->execute([$store_email, 'store_email']);
            $stmt->execute([$store_phone, 'store_phone']);
            
            // إنشاء حساب المسؤول
            $hashed_password = password_hash($admin_password, PASSWORD_BCRYPT, ['cost' => 12]);
            $stmt = $pdo->prepare("UPDATE admins SET username = ?, password = ?, email = ? WHERE id = 1");
            $stmt->execute([$admin_username, $hashed_password, $admin_email]);
            
            // إنشاء ملف config.php
            $config_content = "<?php\n";
            $config_content .= "// ملف الإعدادات - تم إنشاؤه تلقائياً\n\n";
            $config_content .= "define('DB_HOST', '{$db['host']}');\n";
            $config_content .= "define('DB_NAME', '{$db['name']}');\n";
            $config_content .= "define('DB_USER', '{$db['user']}');\n";
            $config_content .= "define('DB_PASS', '{$db['pass']}');\n\n";
            $config_content .= "// ... بقية الإعدادات من config.php الأصلي\n";
            
            file_put_contents('config_generated.php', $config_content);
            
            $step = 4;
            $success = true;
        } catch (Exception $e) {
            $errors[] = 'خطأ في الحفظ: ' . $e->getMessage();
        }
    }
}

// فحص المتطلبات
function checkRequirements() {
    $requirements = [
        'PHP Version >= 8.0' => version_compare(PHP_VERSION, '8.0.0', '>='),
        'PDO Extension' => extension_loaded('pdo'),
        'PDO MySQL Driver' => extension_loaded('pdo_mysql'),
        'JSON Extension' => extension_loaded('json'),
        'GD Extension' => extension_loaded('gd'),
        'uploads/ writable' => is_writable('uploads/') || @mkdir('uploads/', 0755, true)
    ];
    return $requirements;
}

$requirements = checkRequirements();
$all_requirements_met = !in_array(false, $requirements, true);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تثبيت المتجر الإلكتروني</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }
        .container {
            max-width: 700px;
            margin: 0 auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            padding: 30px;
            text-align: center;
        }
        .header h1 { font-size: 32px; margin-bottom: 10px; }
        .header p { opacity: 0.9; }
        .content { padding: 40px; }
        .steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 40px;
            position: relative;
        }
        .steps::before {
            content: '';
            position: absolute;
            top: 20px;
            right: 0;
            left: 0;
            height: 2px;
            background: #e2e8f0;
            z-index: 0;
        }
        .step {
            flex: 1;
            text-align: center;
            position: relative;
            z-index: 1;
        }
        .step-number {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e2e8f0;
            color: #94a3b8;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-bottom: 8px;
        }
        .step.active .step-number {
            background: #667eea;
            color: #fff;
        }
        .step.completed .step-number {
            background: #10b981;
            color: #fff;
        }
        .step-label {
            font-size: 14px;
            color: #64748b;
        }
        .form-group {
            margin-bottom: 25px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #334155;
        }
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s;
        }
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .requirement {
            display: flex;
            justify-content: space-between;
            padding: 12px;
            margin-bottom: 10px;
            background: #f8fafc;
            border-radius: 6px;
        }
        .requirement.pass { background: #f0fdf4; color: #166534; }
        .requirement.fail { background: #fef2f2; color: #991b1b; }
        .btn {
            display: inline-block;
            padding: 14px 30px;
            background: #667eea;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            width: 100%;
        }
        .btn:hover { background: #5568d3; transform: translateY(-2px); }
        .btn:disabled {
            background: #cbd5e1;
            cursor: not-allowed;
            transform: none;
        }
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .alert-error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        .alert-success {
            background: #f0fdf4;
            color: #166534;
            border: 1px solid #bbf7d0;
        }
        .success-icon {
            font-size: 80px;
            color: #10b981;
            text-align: center;
            margin-bottom: 20px;
        }
        .text-center { text-align: center; }
        .mt-20 { margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🛒 تثبيت المتجر الإلكتروني</h1>
            <p>معالج التثبيت السريع</p>
        </div>

        <div class="content">
            <div class="steps">
                <div class="step <?= $step >= 1 ? 'active' : '' ?> <?= $step > 1 ? 'completed' : '' ?>">
                    <div class="step-number">1</div>
                    <div class="step-label">المتطلبات</div>
                </div>
                <div class="step <?= $step >= 2 ? 'active' : '' ?> <?= $step > 2 ? 'completed' : '' ?>">
                    <div class="step-number">2</div>
                    <div class="step-label">قاعدة البيانات</div>
                </div>
                <div class="step <?= $step >= 3 ? 'active' : '' ?> <?= $step > 3 ? 'completed' : '' ?>">
                    <div class="step-number">3</div>
                    <div class="step-label">إعدادات المتجر</div>
                </div>
                <div class="step <?= $step >= 4 ? 'active' : '' ?>">
                    <div class="step-number">4</div>
                    <div class="step-label">اكتمل</div>
                </div>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <strong>حدثت أخطاء:</strong>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if ($step === 1): ?>
                <h2>فحص المتطلبات</h2>
                <div style="margin: 30px 0;">
                    <?php foreach ($requirements as $name => $status): ?>
                        <div class="requirement <?= $status ? 'pass' : 'fail' ?>">
                            <span><?= $name ?></span>
                            <span><?= $status ? '✅ متوفر' : '❌ غير متوفر' ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <form method="post">
                    <button type="submit" class="btn" <?= !$all_requirements_met ? 'disabled' : '' ?>>
                        <?= $all_requirements_met ? 'التالي' : 'يجب توفير جميع المتطلبات أولاً' ?>
                    </button>
                </form>

            <?php elseif ($step === 2): ?>
                <h2>إعدادات قاعدة البيانات</h2>
                <form method="post">
                    <div class="form-group">
                        <label>عنوان الخادم *</label>
                        <input type="text" name="db_host" value="localhost" required>
                    </div>
                    <div class="form-group">
                        <label>اسم قاعدة البيانات *</label>
                        <input type="text" name="db_name" placeholder="ecommerce_pro" required>
                    </div>
                    <div class="form-group">
                        <label>اسم المستخدم *</label>
                        <input type="text" name="db_user" value="root" required>
                    </div>
                    <div class="form-group">
                        <label>كلمة المرور</label>
                        <input type="password" name="db_pass" placeholder="اتركه فارغاً إذا لم يكن هناك كلمة مرور">
                    </div>
                    <button type="submit" class="btn">التالي</button>
                </form>

            <?php elseif ($step === 3): ?>
                <h2>إعدادات المتجر والمسؤول</h2>
                <form method="post">
                    <div class="form-group">
                        <label>اسم المتجر *</label>
                        <input type="text" name="store_name" placeholder="متجري الإلكتروني" required>
                    </div>
                    <div class="form-group">
                        <label>بريد المتجر الإلكتروني</label>
                        <input type="email" name="store_email" placeholder="info@store.com">
                    </div>
                    <div class="form-group">
                        <label>هاتف المتجر</label>
                        <input type="tel" name="store_phone" placeholder="01234567890">
                    </div>
                    
                    <hr style="margin: 30px 0; border: 1px solid #e2e8f0;">
                    <h3 style="margin-bottom: 20px;">حساب المسؤول</h3>
                    
                    <div class="form-group">
                        <label>اسم المستخدم *</label>
                        <input type="text" name="admin_username" placeholder="admin" required>
                    </div>
                    <div class="form-group">
                        <label>كلمة المرور *</label>
                        <input type="password" name="admin_password" placeholder="كلمة مرور قوية" required>
                    </div>
                    <div class="form-group">
                        <label>البريد الإلكتروني</label>
                        <input type="email" name="admin_email" placeholder="admin@store.com">
                    </div>
                    <button type="submit" class="btn">إتمام التثبيت</button>
                </form>

            <?php elseif ($step === 4 && $success): ?>
                <div class="text-center">
                    <div class="success-icon">✅</div>
                    <h2>تم التثبيت بنجاح!</h2>
                    <p style="margin: 20px 0; color: #64748b;">
                        تم إنشاء المتجر وإعداده بنجاح. يمكنك الآن البدء في استخدامه.
                    </p>
                    
                    <div class="alert alert-success mt-20">
                        <strong>⚠️ مهم جداً:</strong><br>
                        قم بحذف ملف <code>install.php</code> من الخادم فوراً لأسباب أمنية!
                    </div>

                    <div style="margin-top: 30px; display: flex; gap: 15px;">
                        <a href="index.php" class="btn" style="text-decoration: none;">
                            عرض المتجر
                        </a>
                        <a href="admin/index.php" class="btn" style="text-decoration: none; background: #10b981;">
                            لوحة التحكم
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>