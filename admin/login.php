<?php
/**
 * صفحة تسجيل دخول لوحة التحكم
 */
session_start();
require_once '../config.php';
require_once '../functions.php';  // أضف ده هنا

// إعادة توجيه إذا كان مسجل دخول
if (isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = cleanInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'يرجى إدخال اسم المستخدم وكلمة المرور';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ? AND is_active = 1");
            $stmt->execute([$username]);
            $admin = $stmt->fetch();
            
            if ($admin && password_verify($password, $admin['password'])) {
                // تسجيل الدخول ناجح
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_name'] = $admin['username'];
                $_SESSION['admin_role'] = $admin['role'];
                
                // تحديث آخر تسجيل دخول
                $stmt = $pdo->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?");
                $stmt->execute([$admin['id']]);
                
                // تسجيل النشاط
                logActivity('login', 'تسجيل دخول ناجح', $admin['id']);
                
                header('Location: index.php');
                exit;
            } else {
                $error = 'اسم المستخدم أو كلمة المرور غير صحيحة';
                logActivity('failed_login', "محاولة دخول فاشلة: $username");
            }
        } catch (Exception $e) {
            $error = 'حدث خطأ، يرجى المحاولة مرة أخرى';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول - لوحة التحكم</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-container {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
            max-width: 450px;
            width: 100%;
        }
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            padding: 40px;
            text-align: center;
        }
        .login-header i {
            font-size: 60px;
            margin-bottom: 15px;
            opacity: 0.9;
        }
        .login-header h1 {
            font-size: 28px;
            margin-bottom: 8px;
        }
        .login-header p {
            opacity: 0.9;
            font-size: 15px;
        }
        .login-body {
            padding: 40px;
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
        .input-group {
            position: relative;
        }
        .input-group i {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
        }
        .form-group input {
            width: 100%;
            padding: 14px 45px 14px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s;
        }
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }
        .error-message {
            background: #fef2f2;
            color: #991b1b;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #fecaca;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        .remember-me {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
        }
        .remember-me input {
            width: auto;
        }
        .login-footer {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
            color: #64748b;
            font-size: 14px;
        }
        .login-footer a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        .login-footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <i class="fas fa-shield-alt"></i>
            <h1>لوحة التحكم</h1>
            <p>تسجيل الدخول للمسؤولين</p>
        </div>

        <div class="login-body">
            <?php if ($error): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="post" action="login.php">
                <div class="form-group">
                    <label for="username">اسم المستخدم</label>
                    <div class="input-group">
                        <input type="text" id="username" name="username" 
                               placeholder="أدخل اسم المستخدم" required autofocus>
                        <i class="fas fa-user"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">كلمة المرور</label>
                    <div class="input-group">
                        <input type="password" id="password" name="password" 
                               placeholder="أدخل كلمة المرور" required>
                        <i class="fas fa-lock"></i>
                    </div>
                </div>

                <div class="remember-me">
                    <input type="checkbox" id="remember" name="remember">
                    <label for="remember" style="font-weight: normal; margin: 0;">تذكرني</label>
                </div>

                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i>
                    تسجيل الدخول
                </button>

                <div class="login-footer">
                    <a href="../index.php">
                        <i class="fas fa-home"></i>
                        العودة للمتجر
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
        // إضافة تأثير إظهار/إخفاء كلمة المرور
        const passwordInput = document.getElementById('password');
        const passwordIcon = passwordInput.parentElement.querySelector('i');
        
        passwordIcon.style.cursor = 'pointer';
        passwordIcon.addEventListener('click', function() {
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                this.classList.remove('fa-lock');
                this.classList.add('fa-lock-open');
            } else {
                passwordInput.type = 'password';
                this.classList.remove('fa-lock-open');
                this.classList.add('fa-lock');
            }
        });
    </script>
</body>
</html>