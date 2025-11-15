-- ===================================
-- قاعدة بيانات متجر إلكتروني احترافي
-- ===================================

CREATE DATABASE IF NOT EXISTS ecommerce_pro CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ecommerce_pro;

-- جدول المسؤولين
CREATE TABLE IF NOT EXISTS admins (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  email VARCHAR(255),
  role ENUM('super_admin','admin','editor') DEFAULT 'admin',
  last_login TIMESTAMP NULL,
  is_active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- جدول الإعدادات
CREATE TABLE IF NOT EXISTS settings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  setting_key VARCHAR(100) UNIQUE NOT NULL,
  setting_value TEXT,
  setting_type ENUM('text','number','boolean','json') DEFAULT 'text',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- جدول الفئات
CREATE TABLE IF NOT EXISTS categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  slug VARCHAR(255) UNIQUE NOT NULL,
  description TEXT,
  image VARCHAR(255),
  parent_id INT NULL,
  display_order INT DEFAULT 0,
  is_active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- جدول المنتجات المحسّن
CREATE TABLE IF NOT EXISTS products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  category_id INT,
  title VARCHAR(255) NOT NULL,
  slug VARCHAR(255) UNIQUE NOT NULL,
  description TEXT,
  short_description VARCHAR(500),
  price DECIMAL(10,2) NOT NULL,
  discount_percentage DECIMAL(5,2) DEFAULT 0.00,
  discount_amount DECIMAL(10,2) DEFAULT 0.00,
  final_price DECIMAL(10,2) GENERATED ALWAYS AS (
    price - GREATEST(discount_amount, price * discount_percentage / 100)
  ) STORED,
  stock INT DEFAULT 0,
  sku VARCHAR(100) UNIQUE,
  weight DECIMAL(8,2),
  dimensions VARCHAR(100),
  main_image VARCHAR(255),
  views INT DEFAULT 0,
  orders_count INT DEFAULT 0,
  rating_avg DECIMAL(3,2) DEFAULT 0.00,
  rating_count INT DEFAULT 0,
  is_featured TINYINT(1) DEFAULT 0,
  is_active TINYINT(1) DEFAULT 1,
  meta_title VARCHAR(255),
  meta_description TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
  INDEX idx_category (category_id),
  INDEX idx_featured (is_featured),
  INDEX idx_price (final_price),
  FULLTEXT idx_search (title, description)
) ENGINE=InnoDB;

-- جدول صور المنتجات
CREATE TABLE IF NOT EXISTS product_images (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  image_path VARCHAR(255) NOT NULL,
  display_order INT DEFAULT 0,
  is_main TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- جدول العملاء
CREATE TABLE IF NOT EXISTS customers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(255) UNIQUE,
  phone VARCHAR(50) UNIQUE,
  password VARCHAR(255),
  first_name VARCHAR(100),
  last_name VARCHAR(100),
  is_verified TINYINT(1) DEFAULT 0,
  verification_token VARCHAR(255),
  reset_token VARCHAR(255),
  reset_token_expire TIMESTAMP NULL,
  orders_count INT DEFAULT 0,
  total_spent DECIMAL(12,2) DEFAULT 0.00,
  last_order_date TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_email (email),
  INDEX idx_phone (phone)
) ENGINE=InnoDB;

-- جدول عناوين العملاء
CREATE TABLE IF NOT EXISTS customer_addresses (
  id INT AUTO_INCREMENT PRIMARY KEY,
  customer_id INT NOT NULL,
  address_type ENUM('home','work','other') DEFAULT 'home',
  full_name VARCHAR(255),
  phone VARCHAR(50),
  governorate VARCHAR(100),
  city VARCHAR(100),
  area VARCHAR(100),
  street_address TEXT,
  building_number VARCHAR(50),
  floor_number VARCHAR(50),
  apartment_number VARCHAR(50),
  landmark TEXT,
  is_default TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
  INDEX idx_customer (customer_id)
) ENGINE=InnoDB;

-- جدول الطلبات المحسّن
CREATE TABLE IF NOT EXISTS orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_number VARCHAR(50) UNIQUE NOT NULL,
  customer_id INT,
  customer_name VARCHAR(255) NOT NULL,
  customer_phone VARCHAR(50) NOT NULL,
  customer_email VARCHAR(255),
  shipping_address TEXT NOT NULL,
  governorate VARCHAR(100),
  city VARCHAR(100),
  payment_method ENUM('cod','visa','instapay','vodafone_cash','fawry') DEFAULT 'cod',
  payment_status ENUM('pending','paid','failed','refunded') DEFAULT 'pending',
  payment_transaction_id VARCHAR(255),
  subtotal DECIMAL(12,2) NOT NULL,
  shipping_cost DECIMAL(10,2) DEFAULT 0.00,
  discount_amount DECIMAL(10,2) DEFAULT 0.00,
  tax_amount DECIMAL(10,2) DEFAULT 0.00,
  total DECIMAL(12,2) NOT NULL,
  status ENUM('pending','confirmed','processing','shipped','delivered','cancelled','returned') DEFAULT 'pending',
  notes TEXT,
  admin_notes TEXT,
  tracking_number VARCHAR(100),
  shipped_at TIMESTAMP NULL,
  delivered_at TIMESTAMP NULL,
  cancelled_at TIMESTAMP NULL,
  ip_address VARCHAR(45),
  user_agent TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
  INDEX idx_order_number (order_number),
  INDEX idx_customer (customer_id),
  INDEX idx_status (status),
  INDEX idx_payment_status (payment_status),
  INDEX idx_created (created_at)
) ENGINE=InnoDB;

-- جدول عناصر الطلب
CREATE TABLE IF NOT EXISTS order_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  product_id INT NOT NULL,
  product_title VARCHAR(255) NOT NULL,
  product_sku VARCHAR(100),
  qty INT NOT NULL,
  unit_price DECIMAL(10,2) NOT NULL,
  discount_amount DECIMAL(10,2) DEFAULT 0.00,
  total_price DECIMAL(12,2) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- جدول سجل حالة الطلبات
CREATE TABLE IF NOT EXISTS order_status_history (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  old_status VARCHAR(50),
  new_status VARCHAR(50) NOT NULL,
  comment TEXT,
  created_by INT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (created_by) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- جدول كوبونات الخصم
CREATE TABLE IF NOT EXISTS coupons (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(50) UNIQUE NOT NULL,
  description TEXT,
  discount_type ENUM('percentage','fixed') DEFAULT 'percentage',
  discount_value DECIMAL(10,2) NOT NULL,
  min_order_amount DECIMAL(10,2) DEFAULT 0.00,
  max_discount_amount DECIMAL(10,2),
  usage_limit INT,
  usage_count INT DEFAULT 0,
  valid_from TIMESTAMP NULL,
  valid_until TIMESTAMP NULL,
  is_active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_code (code)
) ENGINE=InnoDB;

-- جدول التقييمات والمراجعات
CREATE TABLE IF NOT EXISTS reviews (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  customer_id INT,
  order_id INT,
  rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
  title VARCHAR(255),
  comment TEXT,
  is_verified_purchase TINYINT(1) DEFAULT 0,
  is_approved TINYINT(1) DEFAULT 0,
  admin_reply TEXT,
  helpful_count INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL,
  INDEX idx_product (product_id),
  INDEX idx_approved (is_approved)
) ENGINE=InnoDB;

-- جدول قائمة الرغبات
CREATE TABLE IF NOT EXISTS wishlists (
  id INT AUTO_INCREMENT PRIMARY KEY,
  customer_id INT NOT NULL,
  product_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  UNIQUE KEY unique_wishlist (customer_id, product_id)
) ENGINE=InnoDB;

-- جدول النشرة البريدية
CREATE TABLE IF NOT EXISTS newsletter_subscribers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(255) UNIQUE NOT NULL,
  is_active TINYINT(1) DEFAULT 1,
  subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  unsubscribed_at TIMESTAMP NULL,
  INDEX idx_email (email)
) ENGINE=InnoDB;

-- جدول سجل الأنشطة
CREATE TABLE IF NOT EXISTS activity_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  admin_id INT,
  action VARCHAR(100) NOT NULL,
  description TEXT,
  ip_address VARCHAR(45),
  user_agent TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE SET NULL,
  INDEX idx_admin (admin_id),
  INDEX idx_created (created_at)
) ENGINE=InnoDB;

-- ===================================
-- البيانات الافتراضية
-- ===================================

-- مسؤول افتراضي (كلمة المرور: admin123)
INSERT INTO admins (username, password, email, role) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@store.com', 'super_admin');

-- الإعدادات الأساسية
INSERT INTO settings (setting_key, setting_value, setting_type) VALUES 
('store_name', 'متجر إلكتروني احترافي', 'text'),
('store_description', 'أفضل الأسعار وأعلى جودة', 'text'),
('store_email', 'info@store.com', 'text'),
('store_phone', '+20 100 000 0000', 'text'),
('currency', 'EGP', 'text'),
('currency_symbol', 'ج.م', 'text'),
('tax_rate', '0', 'number'),
('shipping_cost_cairo', '30', 'number'),
('shipping_cost_giza', '30', 'number'),
('shipping_cost_alex', '50', 'number'),
('shipping_cost_other', '70', 'number'),
('free_shipping_threshold', '500', 'number'),
('order_prefix', 'ORD', 'text'),
('items_per_page', '12', 'number'),
('maintenance_mode', '0', 'boolean'),
('google_analytics_id', '', 'text'),
('facebook_pixel_id', '', 'text'),
('whatsapp_number', '', 'text'),
('facebook_url', '', 'text'),
('instagram_url', '', 'text'),
('twitter_url', '', 'text');

-- فئات افتراضية
INSERT INTO categories (name, slug, description, display_order, is_active) VALUES 
('إلكترونيات', 'electronics', 'أحدث الأجهزة الإلكترونية', 1, 1),
('ملابس', 'clothing', 'أزياء عصرية للرجال والنساء', 2, 1),
('منزل ومطبخ', 'home-kitchen', 'مستلزمات المنزل والمطبخ', 3, 1),
('رياضة', 'sports', 'معدات ومستلزمات رياضية', 4, 1),
('جمال وعناية', 'beauty', 'منتجات التجميل والعناية الشخصية', 5, 1);

-- منتجات تجريبية
INSERT INTO products (category_id, title, slug, description, short_description, price, discount_percentage, stock, sku, is_featured, is_active) VALUES 
(1, 'سماعات لاسلكية عالية الجودة', 'wireless-headphones-pro', 'سماعات بلوتوث مع خاصية إلغاء الضوضاء وبطارية تدوم 30 ساعة. صوت نقي وتصميم مريح للاستخدام الطويل.', 'سماعات بلوتوث احترافية مع إلغاء الضوضاء', 1299.00, 15.00, 50, 'WH-PRO-001', 1, 1),
(1, 'ساعة ذكية رياضية', 'smartwatch-sport', 'ساعة ذكية متطورة لتتبع اللياقة البدنية مع شاشة AMOLED ومقاومة للماء حتى 50 متر', 'ساعة ذكية للرياضة وتتبع الصحة', 2499.00, 20.00, 30, 'SW-SPORT-001', 1, 1),
(2, 'قميص قطني رجالي', 'mens-cotton-shirt', 'قميص قطني 100% بألوان متعددة ومقاسات من S إلى XXL. مناسب للارتداء اليومي', 'قميص قطني مريح وعصري', 299.00, 0, 100, 'SHIRT-M-001', 0, 1),
(3, 'طقم أواني طهي غير لاصقة', 'cookware-set-nonstick', 'طقم من 7 قطع بطبقة غير لاصقة ومقابض مقاومة للحرارة. آمن للاستخدام اليومي', 'طقم أواني طهي 7 قطع', 899.00, 10.00, 25, 'COOK-SET-001', 0, 1),
(4, 'حبل قفز رياضي احترافي', 'jump-rope-pro', 'حبل قفز قابل للتعديل مع عداد رقمي ومقابض مريحة. مثالي للياقة البدنية', 'حبل قفز مع عداد رقمي', 149.00, 0, 75, 'ROPE-001', 0, 1),
(5, 'كريم واقي من الشمس SPF 50', 'sunscreen-spf50', 'كريم حماية من أشعة الشمس بعامل حماية 50 ومقاوم للماء لمدة 80 دقيقة', 'واقي شمس للحماية الكاملة', 179.00, 5.00, 60, 'SUN-001', 0, 1);

-- كوبونات تجريبية
INSERT INTO coupons (code, description, discount_type, discount_value, min_order_amount, usage_limit, valid_until, is_active) VALUES 
('WELCOME10', 'خصم 10% للعملاء الجدد', 'percentage', 10.00, 200.00, 100, DATE_ADD(NOW(), INTERVAL 30 DAY), 1),
('SAVE50', 'خصم 50 جنيه على طلبات أكثر من 500 جنيه', 'fixed', 50.00, 500.00, 50, DATE_ADD(NOW(), INTERVAL 15 DAY), 1);

-- ===================================
-- Views للتقارير
-- ===================================

-- عرض إحصائيات المبيعات اليومية
CREATE OR REPLACE VIEW daily_sales_stats AS
SELECT 
  DATE(created_at) as sale_date,
  COUNT(*) as orders_count,
  SUM(total) as total_revenue,
  AVG(total) as avg_order_value,
  SUM(CASE WHEN payment_status = 'paid' THEN total ELSE 0 END) as paid_revenue
FROM orders
WHERE status NOT IN ('cancelled', 'returned')
GROUP BY DATE(created_at)
ORDER BY sale_date DESC;

-- عرض أفضل المنتجات مبيعاً
CREATE OR REPLACE VIEW top_selling_products AS
SELECT 
  p.id,
  p.title,
  p.price,
  p.final_price,
  p.stock,
  p.orders_count,
  SUM(oi.qty) as total_sold,
  SUM(oi.total_price) as total_revenue
FROM products p
LEFT JOIN order_items oi ON p.id = oi.product_id
LEFT JOIN orders o ON oi.order_id = o.id
WHERE o.status NOT IN ('cancelled', 'returned')
GROUP BY p.id
ORDER BY total_sold DESC
LIMIT 50;

-- ===================================
-- إجراءات مخزنة للعمليات المتكررة
-- ===================================

DELIMITER //

-- إجراء لتحديث متوسط التقييم
CREATE PROCEDURE update_product_rating(IN p_product_id INT)
BEGIN
  UPDATE products p
  SET 
    rating_avg = (SELECT COALESCE(AVG(rating), 0) FROM reviews WHERE product_id = p_product_id AND is_approved = 1),
    rating_count = (SELECT COUNT(*) FROM reviews WHERE product_id = p_product_id AND is_approved = 1)
  WHERE id = p_product_id;
END//

-- إجراء لإنشاء رقم طلب فريد
CREATE PROCEDURE generate_order_number(OUT order_num VARCHAR(50))
BEGIN
  DECLARE prefix VARCHAR(10);
  DECLARE seq INT;
  
  SELECT setting_value INTO prefix FROM settings WHERE setting_key = 'order_prefix';
  SELECT COALESCE(MAX(CAST(SUBSTRING(order_number, LENGTH(prefix) + 1) AS UNSIGNED)), 0) + 1 
  INTO seq FROM orders;
  
  SET order_num = CONCAT(prefix, LPAD(seq, 8, '0'));
END//

DELIMITER ;

-- ===================================
-- Triggers للعمليات التلقائية
-- ===================================

DELIMITER //

-- تحديث إحصائيات العميل عند إضافة طلب
CREATE TRIGGER after_order_insert
AFTER INSERT ON orders
FOR EACH ROW
BEGIN
  IF NEW.customer_id IS NOT NULL AND NEW.payment_status = 'paid' THEN
    UPDATE customers 
    SET 
      orders_count = orders_count + 1,
      total_spent = total_spent + NEW.total,
      last_order_date = NEW.created_at
    WHERE id = NEW.customer_id;
  END IF;
END//

-- تسجيل تغيير حالة الطلب
CREATE TRIGGER after_order_status_update
AFTER UPDATE ON orders
FOR EACH ROW
BEGIN
  IF OLD.status != NEW.status THEN
    INSERT INTO order_status_history (order_id, old_status, new_status, comment)
    VALUES (NEW.id, OLD.status, NEW.status, CONCAT('تم تحديث الحالة من ', OLD.status, ' إلى ', NEW.status));
  END IF;
END//

DELIMITER ;

-- ===================================
-- فهارس إضافية لتحسين الأداء
-- ===================================

ALTER TABLE orders ADD INDEX idx_payment_method (payment_method);
ALTER TABLE orders ADD INDEX idx_created_status (created_at, status);
ALTER TABLE order_items ADD INDEX idx_product (product_id);
ALTER TABLE products ADD INDEX idx_active_featured (is_active, is_featured);

-- ===================================
-- منح الصلاحيات (اختياري)
-- ===================================
-- GRANT ALL PRIVILEGES ON ecommerce_pro.* TO 'ecommerce_user'@'localhost' IDENTIFIED BY 'SecurePassword123!';
-- FLUSH PRIVILEGES;