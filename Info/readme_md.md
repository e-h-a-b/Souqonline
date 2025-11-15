# 🛒 متجر إلكتروني احترافي - نظام متكامل

نظام متجر إلكتروني احترافي متكامل مبني بـ PHP و MySQL مع واجهة عصرية وميزات متقدمة.

## 📋 المميزات

### للعملاء
- ✅ تصفح المنتجات مع فلاتر متقدمة (فئات، سعر، تقييم)
- ✅ نظام سلة مشتريات ديناميكي
- ✅ دعم كوبونات الخصم
- ✅ طرق دفع متعددة (COD، Visa، InstaPay، Vodafone Cash، Fawry)
- ✅ حساب تكلفة الشحن تلقائياً حسب المحافظة
- ✅ تتبع الطلبات
- ✅ نظام تقييمات ومراجعات
- ✅ قائمة المفضلة
- ✅ بحث سريع ذكي
- ✅ تصميم متجاوب (Responsive) لجميع الأجهزة

### للإدارة
- ✅ لوحة تحكم سهلة الاستخدام
- ✅ إدارة المنتجات (إضافة، تعديل، حذف)
- ✅ إدارة الفئات
- ✅ إدارة الطلبات وتتبع حالتها
- ✅ إدارة العملاء
- ✅ إدارة الكوبونات والخصومات
- ✅ تقارير المبيعات
- ✅ إعدادات المتجر (اسم، شعار، معلومات الاتصال)
- ✅ سجل الأنشطة

## 🔧 المتطلبات

- PHP 8.0 أو أحدث
- MySQL 8.0 أو MariaDB 10.5+
- Apache أو Nginx
- PDO Extension
- JSON Extension
- GD Library (لمعالجة الصور)

## 📦 التثبيت

### 1. تحميل الملفات

```bash
# استنساخ المشروع أو تحميله
git clone [repository-url]
cd ecommerce-pro
```

### 2. إعداد قاعدة البيانات

```bash
# إنشاء قاعدة البيانات
mysql -u root -p

# داخل MySQL
CREATE DATABASE ecommerce_pro CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
exit;

# استيراد البيانات
mysql -u root -p ecommerce_pro < db.sql
```

### 3. تكوين الاتصال

قم بتعديل ملف `config.php`:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'ecommerce_pro');
define('DB_USER', 'root');
define('DB_PASS', 'your_password');
```

### 4. إعدادات الخادم

#### Apache
تأكد من تفعيل `mod_rewrite`:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ index.php [QSA,L]
</IfModule>
```

#### Nginx
أضف هذا إلى تكوين الموقع:

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}

location ~ \.php$ {
    fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
    fastcgi_index index.php;
    include fastcgi_params;
}
```

### 5. الصلاحيات

```bash
# منح صلاحيات الكتابة لمجلد uploads
chmod -R 755 uploads/
chown -R www-data:www-data uploads/

# إنشاء مجلدات الرفع
mkdir -p uploads/products
mkdir -p uploads/categories
```

## 🔐 الأمان

### تغيير بيانات المسؤول الافتراضية

```sql
-- اسم المستخدم: admin
-- كلمة المرور: admin123

-- لتغيير كلمة المرور:
UPDATE admins SET password = '$2y$10$YOUR_NEW_HASHED_PASSWORD' WHERE username = 'admin';
```

### توليد كلمة مرور جديدة بـ PHP:

```php
<?php
echo password_hash('your_new_password', PASSWORD_BCRYPT, ['cost' => 12]);
?>
```

### تحديث مفاتيح التشفير

في `config.php`، غيّر:

```php
define('ENCRYPTION_KEY', 'your-unique-secret-key-here');
define('JWT_SECRET', 'your-unique-jwt-secret-here');
```

## 🎨 التخصيص

### تغيير الألوان

في `assets/css/styles.css`:

```css
:root {
    --primary-color: #2563eb;    /* اللون الأساسي */
    --primary-dark: #1e40af;     /* اللون الأساسي الداكن */
    --secondary-color: #64748b;  /* اللون الثانوي */
    /* ... */
}
```

### إضافة شعار

1. ضع ملف الشعار في `assets/images/logo.png`
2. عدّل `index.php` في قسم الـ Header:

```html
<div class="logo">
    <a href="index.php">
        <img src="assets/images/logo.png" alt="Logo">
    </a>
</div>
```

## 💳 تكامل بوابات الدفع

### Paytabs
في `checkout.php`، أضف:

```php
if ($paymentMethod === 'visa') {
    // Paytabs Integration
    $payment = new PaytabsAPI();
    $payment->setMerchantId('YOUR_MERCHANT_ID');
    $payment->setSecretKey('YOUR_SECRET_KEY');
    // ... المزيد من الإعدادات
}
```

### Fawry
```php
if ($paymentMethod === 'fawry') {
    // Fawry API Integration
    $fawry = new FawryAPI();
    // ... الإعدادات
}
```

## 📧 إعدادات البريد الإلكتروني

في `config.php`:

```php
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your-email@gmail.com');
define('SMTP_PASS', 'your-app-passwor