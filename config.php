<?php
/**
 * ملف الإعدادات الرئيسي للمتجر الإلكتروني
 * @version 2.0
 * @author Professional E-Commerce System
 */

// إعدادات الأمان
define('SECURE_MODE', true);
define('SESSION_LIFETIME', 7200); // ساعتان
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_TIMEOUT', 900); // 15 دقيقة

// إعدادات المحفظة
define('WALLET_MIN_DEPOSIT', 10);
define('WALLET_MAX_DEPOSIT', 10000);
define('WALLET_ENABLED', true);
 
define('POINTS_ENABLED', true);
// بدء الجلسة بإعدادات آمنة
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', SECURE_MODE ? 1 : 0);
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

// إعدادات قاعدة البيانات
define('DB_HOST', 'localhost');
define('DB_NAME', 'ecommerce_pro');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// إعدادات التطبيق
define('SITE_URL', 'http://localhost/v1.0');
define('ADMIN_URL', SITE_URL . '/admin');
define('UPLOAD_PATH', __DIR__ . '/uploads/');
define('UPLOAD_URL', SITE_URL . '/uploads/');
define('MAX_UPLOAD_SIZE', 5242880); // 5MB

// إعدادات البريد الإلكتروني (للتحديث حسب الخادم)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your-email@gmail.com');
define('SMTP_PASS', 'your-app-password');
define('SMTP_FROM', 'noreply@yourstore.com');
define('SMTP_NAME', 'متجر إلكتروني');

// مفاتيح التشفير (غيّرها في الإنتاج!)
define('ENCRYPTION_KEY', 'your-secret-encryption-key-change-in-production');
define('JWT_SECRET', 'your-jwt-secret-key-change-in-production');

// إعدادات الصور
define('THUMB_WIDTH', 300);
define('THUMB_HEIGHT', 300);
define('MEDIUM_WIDTH', 600);
define('MEDIUM_HEIGHT', 600);
define('ALLOWED_IMAGES', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

// الاتصال بقاعدة البيانات
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ]
    );
} catch (PDOException $e) {
    // في الإنتاج: سجل الخطأ ولا تعرضه
    if (ini_get('display_errors')) {
        die('خطأ في الاتصال بقاعدة البيانات: ' . $e->getMessage());
    } else {
        die('عذراً، حدث خطأ في النظام. يرجى المحاولة لاحقاً.');
    }
}

/**
 * دالة التنظيف من XSS
 */
function cleanInput($data) {
    if (is_array($data)) {
        return array_map('cleanInput', $data);
    }
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * دالة التحقق من CSRF Token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * دالة تشفير كلمة المرور
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * دالة التحقق من كلمة المرور
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

?>