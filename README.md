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

<img width="1024" height="1024" alt="ChatGPT Image 15 نوفمبر0000 2025، 11_48_59 م" src="https://github.com/user-attachments/assets/04b9e60b-f8ce-43b4-acbd-a34de40a0353" />
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
define('SMTP_PASS', 'your-app-password');
define('SMTP_FROM', 'noreply@yourstore.com');
```

## 📱 API Endpoints

### Cart API (`api/cart.php`)

```javascript
// إضافة منتج
POST /api/cart.php
{
    "action": "add",
    "product_id": 1,
    "quantity": 2
}

// تحديث كمية
POST /api/cart.php
{
    "action": "update",
    "product_id": 1,
    "quantity": 3
}

// حذف منتج
POST /api/cart.php
{
    "action": "remove",
    "product_id": 1
}

// عدد العناصر
GET /api/cart.php?action=count
```

## 🗂️ هيكل المشروع

```
ecommerce-pro/
├── api/
│   ├── cart.php          # API السلة
│   ├── search.php        # API البحث
│   ├── wishlist.php      # API المفضلة
│   └── newsletter.php    # API النشرة البريدية
├── admin/
│   ├── index.php         # لوحة التحكم
│   ├── products.php      # إدارة المنتجات
│   ├── orders.php        # إدارة الطلبات
│   ├── customers.php     # إدارة العملاء
│   ├── coupons.php       # إدارة الكوبونات
│   └── settings.php      # الإعدادات
├── assets/
│   ├── css/
│   │   └── styles.css    # الأنماط الرئيسية
│   ├── js/
│   │   └── app.js        # JavaScript الرئيسي
│   └── images/           # الصور
├── uploads/              # الملفات المرفوعة
│   ├── products/
│   └── categories/
├── config.php            # إعدادات الاتصال
├── functions.php         # الدوال المساعدة
├── index.php            # الصفحة الرئيسية
├── product.php          # صفحة المنتج
├── cart.php             # السلة
├── checkout.php         # إتمام الطلب
├── db.sql               # قاعدة البيانات
└── README.md            # هذا الملف
```

## 🚀 النشر على خادم الإنتاج

### 1. تحضير الملفات

```bash
# ضغط الملفات
tar -czf ecommerce-pro.tar.gz *

# أو باستخدام zip
zip -r ecommerce-pro.zip *
```

### 2. رفع إلى الخادم

```bash
# باستخدام SCP
scp ecommerce-pro.tar.gz user@your-server.com:/var/www/html/

# أو استخدم FTP/SFTP Client مثل FileZilla
```

### 3. فك الضغط على الخادم

```bash
ssh user@your-server.com
cd /var/www/html/
tar -xzf ecommerce-pro.tar.gz
```

### 4. تأمين الإنتاج

في `config.php`:

```php
// تعطيل عرض الأخطاء
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

// تفعيل الوضع الآمن
define('SECURE_MODE', true);
```

### 5. تثبيت SSL

```bash
# باستخدام Let's Encrypt
sudo certbot --apache -d yourstore.com -d www.yourstore.com
```

## ⚙️ الإعدادات المتقدمة

### تحسين الأداء

#### 1. تفعيل Caching

```php
// في config.php
$pdo->setAttribute(PDO::ATTR_PERSISTENT, true);
```

#### 2. ضغط CSS/JS

استخدم أدوات مثل:
- UglifyJS للـ JavaScript
- CleanCSS للـ CSS

```bash
npm install -g uglify-js clean-css-cli

uglifyjs assets/js/app.js -o assets/js/app.min.js -c -m
cleancss assets/css/styles.css -o assets/css/styles.min.css
```

#### 3. تحسين الصور

```bash
# باستخدام ImageMagick
mogrify -resize 800x800 -quality 85 uploads/products/*.jpg
```

### النسخ الاحتياطي التلقائي

```bash
#!/bin/bash
# backup.sh

DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/backups/ecommerce"

# نسخ قاعدة البيانات
mysqldump -u root -p'password' ecommerce_pro > $BACKUP_DIR/db_$DATE.sql

# نسخ الملفات
tar -czf $BACKUP_DIR/files_$DATE.tar.gz /var/www/html/ecommerce-pro

# حذف النسخ الأقدم من 30 يوم
find $BACKUP_DIR -mtime +30 -delete
```

أضف إلى Cron:
```bash
crontab -e
# إضافة السطر التالي
0 2 * * * /path/to/backup.sh
```

## 🔍 استكشاف الأخطاء

### خطأ في الاتصال بقاعدة البيانات

```
الحل:
1. تحقق من بيانات الاتصال في config.php
2. تأكد من تشغيل MySQL
3. تحقق من صلاحيات المستخدم
```

### الصور لا تظهر

```
الحل:
1. تحقق من صلاحيات مجلد uploads/
   chmod -R 755 uploads/
2. تأكد من وجود الصور في المسار الصحيح
3. تحقق من إعدادات upload_max_filesize في php.ini
```

### السلة لا تعمل

```
الحل:
1. تأكد من تفعيل Sessions في PHP
2. تحقق من صلاحيات مجلد session في الخادم
3. تأكد من وجود ملف api/cart.php
```

## 📊 قاعدة البيانات

### الجداول الرئيسية

| الجدول | الوصف |
|--------|-------|
| admins | بيانات المسؤولين |
| settings | إعدادات المتجر |
| categories | الفئات |
| products | المنتجات |
| product_images | صور المنتجات |
| customers | العملاء |
| customer_addresses | عناوين العملاء |
| orders | الطلبات |
| order_items | عناصر الطلبات |
| order_status_history | سجل حالات الطلبات |
| coupons | كوبونات الخصم |
| reviews | التقييمات والمراجعات |
| wishlists | قائمة الرغبات |
| newsletter_subscribers | مشتركي النشرة |
| activity_logs | سجل الأنشطة |

### Views المتاحة

- `daily_sales_stats` - إحصائيات المبيعات اليومية
- `top_selling_products` - المنتجات الأكثر مبيعاً

## 🧪 الاختبار

### اختبار المنتج

```php
// test-product.php
require_once 'functions.php';

$product = getProduct(1);
var_dump($product);
```

### اختبار السلة

```javascript
// في Console المتصفح
addToCart(1, 2).then(data => console.log(data));
```

## 📈 التحديثات المستقبلية

- [ ] دعم لغات متعددة (i18n)
- [ ] تطبيق موبايل (React Native / Flutter)
- [ ] دعم المنتجات الرقمية
- [ ] نظام نقاط الولاء
- [ ] تكامل مع منصات التواصل الاجتماعي
- [ ] دعم البيع بالجملة (Wholesale)
- [ ] نظام تابعين (Affiliates)

## 🤝 المساهمة

نرحب بأي مساهمات! إذا وجدت خطأ أو لديك اقتراح:

1. Fork المشروع
2. أنشئ فرع للميزة (`git checkout -b feature/AmazingFeature`)
3. Commit التغييرات (`git commit -m 'Add some AmazingFeature'`)
4. Push للفرع (`git push origin feature/AmazingFeature`)
5. افتح Pull Request

## 📄 الترخيص

هذا المشروع مرخص تحت [MIT License](LICENSE)

## 💡 الدعم

- 📧 البريد الإلكتروني: support@yourstore.com
- 💬 الدعم الفني: [رابط الدعم]
- 📖 التوثيق: [رابط التوثيق]

## 🎓 الموارد

- [PHP Documentation](https://www.php.net/docs.php)
- [MySQL Documentation](https://dev.mysql.com/doc/)
- [MDN Web Docs](https://developer.mozilla.org/)

## ⭐ شكر خاص

شكراً لجميع المساهمين والمطورين الذين ساعدوا في تطوير هذا المشروع.

---

صُنع بـ ❤️ في مصر

**نسخة:** 2.0  
**آخر تحديث:** 2025  
**الحالة:** جاهز للإنتاج ✅
