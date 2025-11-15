<?php
/**
 * ملف الدوال المساعدة للمتجر الإلكتروني
 * @version 2.0
 */

require_once 'config.php';

// ==================== دوال المنتجات ====================

/**
 * جلب منتجات مع فلاتر متقدمة
 */
/**
 * جلب منتجات مع فلاتر متقدمة
 */
function getProducts($options = []) {
    global $pdo;
    
    $defaults = [
        'limit' => 12,
        'offset' => 0,
        'category_id' => null,
        'is_featured' => null,
        'search' => null,
        'min_price' => null,
        'max_price' => null,
        'sort' => 'newest',
        'is_active' => 1
    ];
    
    $options = array_merge($defaults, $options);
    
    $where = ['p.is_active = :is_active'];
    $params = [':is_active' => $options['is_active']];
    
    if ($options['category_id']) {
        $where[] = 'p.category_id = :category_id';
        $params[':category_id'] = $options['category_id'];
    }
    
    if ($options['is_featured'] !== null) {
        $where[] = 'p.is_featured = :is_featured';
        $params[':is_featured'] = $options['is_featured'];
    }
    
    if ($options['search']) {
        $where[] = '(p.title LIKE :search_title OR p.description LIKE :search_description)';
        $params[':search_title'] = '%' . $options['search'] . '%';
        $params[':search_description'] = '%' . $options['search'] . '%';
    }
    
    if ($options['min_price']) {
        $where[] = 'p.final_price >= :min_price';
        $params[':min_price'] = $options['min_price'];
    }
    
    if ($options['max_price']) {
        $where[] = 'p.final_price <= :max_price';
        $params[':max_price'] = $options['max_price'];
    }
    
    $whereClause = implode(' AND ', $where);
    
    // ترتيب النتائج
    $orderBy = 'p.created_at DESC';
    switch ($options['sort']) {
        case 'price_low':
            $orderBy = 'p.final_price ASC';
            break;
        case 'price_high':
            $orderBy = 'p.final_price DESC';
            break;
        case 'popular':
            $orderBy = 'p.orders_count DESC';
            break;
        case 'rating':
            $orderBy = 'p.rating_avg DESC';
            break;
    }
    
    $sql = "SELECT p.*, c.name as category_name, c.slug as category_slug
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE $whereClause
            ORDER BY $orderBy
            LIMIT :limit OFFSET :offset";
    
    $stmt = $pdo->prepare($sql);
    
    // ربط جميع الباراميترات مع تحديد النوع
    foreach ($params as $key => $value) {
        $paramType = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
        $stmt->bindValue($key, $value, $paramType);
    }
    
    // ربط limit و offset بشكل منفصل
    $stmt->bindValue(':limit', (int)$options['limit'], PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$options['offset'], PDO::PARAM_INT);
    
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
/**
 * جلب منتج واحد مع تفاصيله
 */
function getProduct($id) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT p.*, c.name as category_name, c.slug as category_slug
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.id = ? AND p.is_active = 1
    ");
    $stmt->execute([$id]);
    $product = $stmt->fetch();
    
    if ($product) {
        // جلب صور المنتج
        $stmt = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY display_order");
        $stmt->execute([$id]);
        $product['images'] = $stmt->fetchAll();
    }
    
    return $product;
}

/**
 * زيادة عدد المشاهدات
 */
function increaseView($productId) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE products SET views = views + 1 WHERE id = ?");
    return $stmt->execute([$productId]);
}

/**
 * جلب المنتجات الأكثر مشاهدة
 */
function getTopViewedProducts($limit = 5) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT * FROM products 
        WHERE is_active = 1 AND views > 0
        ORDER BY views DESC 
        LIMIT :limit
    ");
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * جلب المنتجات الأكثر طلباً
 */
function getTopOrderedProducts($limit = 5) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT * FROM products 
        WHERE is_active = 1 AND orders_count > 0
        ORDER BY orders_count DESC 
        LIMIT :limit
    ");
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * جلب المنتجات المميزة
 */
function getFeaturedProducts($limit = 8) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT * FROM products 
        WHERE is_active = 1 AND is_featured = 1
        ORDER BY created_at DESC 
        LIMIT :limit
    ");
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * جلب منتجات ذات صلة
 */
function getRelatedProducts($productId, $categoryId, $limit = 4) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT * FROM products 
        WHERE is_active = 1 
        AND category_id = :category_id 
        AND id != :product_id
        ORDER BY RAND()
        LIMIT :limit
    ");
    $stmt->bindValue(':category_id', (int)$categoryId, PDO::PARAM_INT);
    $stmt->bindValue(':product_id', (int)$productId, PDO::PARAM_INT);
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ==================== دوال الفئات ====================

/**
 * جلب جميع الفئات النشطة
 */
function getCategories($parentId = null) {
    global $pdo;
    
    if ($parentId === null) {
        $stmt = $pdo->query("
            SELECT * FROM categories 
            WHERE is_active = 1 
            ORDER BY display_order ASC
        ");
    } else {
        $stmt = $pdo->prepare("
            SELECT * FROM categories 
            WHERE is_active = 1 AND parent_id = ?
            ORDER BY display_order ASC
        ");
        $stmt->execute([$parentId]);
    }
    
    return $stmt->fetchAll();
}

/**
 * جلب فئة واحدة
 */
function getCategory($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ? AND is_active = 1");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

// ==================== دوال السلة ====================

/**
 * إضافة منتج إلى السلة
 */
/**
 * إضافة منتج إلى السلة مع دعم العروض
 */
function addToCart($productId, $quantity = 1) {
    $product = getProduct($productId);
    
    if (!$product || $product['stock'] < $quantity) {
        return false;
    }
    
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    if (isset($_SESSION['cart'][$productId])) {
        $_SESSION['cart'][$productId]['qty'] += $quantity;
    } else {
        $_SESSION['cart'][$productId] = [
            'id' => $product['id'],
            'title' => $product['title'],
            'price' => $product['final_price'],
            'image' => $product['main_image'],
            'qty' => $quantity,
            'stock' => $product['stock']
        ];
    }
    
    // التحقق من عدم تجاوز المخزون
    if ($_SESSION['cart'][$productId]['qty'] > $product['stock']) {
        $_SESSION['cart'][$productId]['qty'] = $product['stock'];
    }
    
    // تطبيق العروض تلقائياً
    $_SESSION['cart'] = applyBuyTwoGetOneOffer($_SESSION['cart']);
    
    return true;
}

/**
 * تحديث كمية منتج في السلة
 */
function updateCartItem($productId, $quantity) {
    if (!isset($_SESSION['cart'][$productId])) {
        return false;
    }
    
    if ($quantity <= 0) {
        unset($_SESSION['cart'][$productId]);
    } else {
        $_SESSION['cart'][$productId]['qty'] = min($quantity, $_SESSION['cart'][$productId]['stock']);
    }
    
    return true;
}

/**
 * حذف منتج من السلة
 */
function removeFromCart($productId) {
    if (isset($_SESSION['cart'][$productId])) {
        unset($_SESSION['cart'][$productId]);
        return true;
    }
    return false;
}

/**
 * إفراغ السلة
 */
function clearCart() {
    $_SESSION['cart'] = [];
}

/**
 * حساب إجمالي السلة
 */
function getCartTotal() {
    $total = 0;
    if (isset($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $item) {
            $total += $item['price'] * $item['qty'];
        }
    }
    return $total;
}

/**
 * عدد عناصر السلة
 */
function getCartCount() {
    $count = 0;
    if (isset($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $item) {
            $count += $item['qty'];
        }
    }
    return $count;
}

// ==================== دوال الطلبات ====================

/**
 * إنشاء طلب جديد
 */
function createOrder($orderData) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // إنشاء رقم الطلب
        $stmt = $pdo->prepare("CALL generate_order_number(?)");
        $stmt->execute([$orderNumber]);
        $orderNumber = $stmt->fetchColumn();
        
        // إدخال الطلب الرئيسي
        $stmt = $pdo->prepare("
            INSERT INTO orders (
                order_number, customer_name, customer_phone, customer_email,
                shipping_address, governorate, city, payment_method,
                subtotal, shipping_cost, discount_amount, total,
                ip_address, user_agent
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $subtotal = getCartTotal();
        $shippingCost = calculateShipping($orderData['governorate']);
        $discount = $orderData['discount'] ?? 0;
        $total = $subtotal + $shippingCost - $discount;
        
        $stmt->execute([
            $orderNumber,
            $orderData['customer_name'],
            $orderData['customer_phone'],
            $orderData['customer_email'] ?? null,
            $orderData['shipping_address'],
            $orderData['governorate'],
            $orderData['city'] ?? '',
            $orderData['payment_method'],
            $subtotal,
            $shippingCost,
            $discount,
            $total,
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['HTTP_USER_AGENT']
        ]);
        
        $orderId = $pdo->lastInsertId();
        
        // إدخال عناصر الطلب مع معلومات المتجر
        $cart = $_SESSION['cart'] ?? [];
        $storeInfo = []; // يمكنك إضافة استعلام لجلب معلومات المتجر هنا
        
        foreach ($cart as $productId => $item) {
            $storeData = $storeInfo[$productId] ?? ['store_type' => 'main', 'store_name' => 'المتجر الرئيسي'];
            
            $stmt = $pdo->prepare("
                INSERT INTO order_items (
                    order_id, product_id, product_title, product_sku,
                    qty, unit_price, discount_amount, total_price
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $totalPrice = $item['price'] * $item['qty'];
            
            $stmt->execute([
                $orderId,
                $productId,
                $item['title'],
                null, // أو SKU إذا كان متوفراً
                $item['qty'],
                $item['price'],
                0, // خصم العنصر
                $totalPrice
            ]);
            
            // يمكنك إضافة حقل store_type في order_items إذا أردت تخزين معلومات المتجر
        }
        
        $pdo->commit();
        
        return [
            'success' => true,
            'order_id' => $orderId,
            'order_number' => $orderNumber
        ];
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Order creation error: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * حساب تكلفة الشحن
 */
function calculateShipping($governorate) {
    $governorate = strtolower(trim($governorate));
    
    $rates = [
        'القاهرة' => getSetting('shipping_cost_cairo', 30),
        'الجيزة' => getSetting('shipping_cost_giza', 30),
        'الإسكندرية' => getSetting('shipping_cost_alex', 50)
    ];
    
    if (isset($rates[$governorate])) {
        return $rates[$governorate];
    }
    
    return getSetting('shipping_cost_other', 70);
}

/**
 * جلب تفاصيل طلب
 */
function getOrder($orderId) {
    global $pdo;
    
    try {
        // جلب بيانات الطلب الأساسية
        $stmt = $pdo->prepare("
            SELECT o.*, 
                   GROUP_CONCAT(oi.id) as item_ids
            FROM orders o 
            LEFT JOIN order_items oi ON o.id = oi.order_id 
            WHERE o.id = ? 
            GROUP BY o.id
        ");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$order) {
            return null;
        }
        
        // جلب عناصر الطلب مع معلومات المتجر
        $stmt = $pdo->prepare("
            SELECT oi.*, 
                   p.store_type, p.created_by,
                   CASE 
                       WHEN p.store_type = 'customer' THEN CONCAT(c.first_name, ' ', c.last_name) 
                       ELSE 'المتجر الرئيسي' 
                   END as store_name
            FROM order_items oi 
            LEFT JOIN products p ON oi.product_id = p.id 
            LEFT JOIN customers c ON p.created_by = c.id 
            WHERE oi.order_id = ?
        ");
        $stmt->execute([$orderId]);
        $order['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $order;
        
    } catch (Exception $e) {
        error_log("Get order error: " . $e->getMessage());
        return null;
    }
}

/**
 * تحديث حالة الطلب
 */
function updateOrderStatus($orderId, $newStatus, $adminId = null) {
    global $pdo;
    
    $stmt = $pdo->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
    $result = $stmt->execute([$newStatus, $orderId]);
    
    if ($result && $adminId) {
        // تسجيل في السجل
        $stmt = $pdo->prepare("
            INSERT INTO order_status_history (order_id, new_status, created_by)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$orderId, $newStatus, $adminId]);
    }
    
    return $result;
}

// ==================== دوال الكوبونات ====================

/**
 * التحقق من كوبون خصم
 */
function validateCoupon($code, $orderTotal) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT * FROM coupons 
        WHERE code = ? 
        AND is_active = 1
        AND (valid_from IS NULL OR valid_from <= NOW())
        AND (valid_until IS NULL OR valid_until >= NOW())
        AND (usage_limit IS NULL OR usage_count < usage_limit)
    ");
    $stmt->execute([$code]);
    $coupon = $stmt->fetch();
    
    if (!$coupon) {
        return ['valid' => false, 'message' => 'كوبون غير صالح'];
    }
    
    if ($orderTotal < $coupon['min_order_amount']) {
        return [
            'valid' => false, 
            'message' => 'الحد الأدنى للطلب ' . $coupon['min_order_amount'] . ' ج.م'
        ];
    }
    
    $discount = 0;
    if ($coupon['discount_type'] === 'percentage') {
        $discount = ($orderTotal * $coupon['discount_value']) / 100;
        if ($coupon['max_discount_amount']) {
            $discount = min($discount, $coupon['max_discount_amount']);
        }
    } else {
        $discount = $coupon['discount_value'];
    }
    
    return [
        'valid' => true,
        'discount' => $discount,
        'coupon_id' => $coupon['id']
    ];
}

/**
 * استخدام كوبون
 */
function useCoupon($couponId) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE coupons SET usage_count = usage_count + 1 WHERE id = ?");
    return $stmt->execute([$couponId]);
}

// ==================== دوال المراجعات ====================

 

/**
 * إضافة تقييم
 */
function addReview($productId, $customerId, $orderId, $rating, $title, $comment) {
    global $pdo;
    
    // التحقق من عدم وجود تقييم سابق
    $stmt = $pdo->prepare("
        SELECT id FROM reviews 
        WHERE product_id = ? AND customer_id = ? AND order_id = ?
    ");
    $stmt->execute([$productId, $customerId, $orderId]);
    
    if ($stmt->fetch()) {
        return false; // تقييم موجود مسبقاً
    }
    
    $stmt = $pdo->prepare("
        INSERT INTO reviews (
            product_id, customer_id, order_id, rating, 
            title, comment, is_verified_purchase
        ) VALUES (?, ?, ?, ?, ?, ?, 1)
    ");
    
    return $stmt->execute([$productId, $customerId, $orderId, $rating, $title, $comment]);
}

// ==================== دوال المساعدة ====================

if (!function_exists('getSetting')) {
    /**
     * جلب إعداد من قاعدة البيانات
     */
    function getSetting($key, $default = '') {
        global $pdo;
        try {
            $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
            $stmt->execute([$key]);
            $result = $stmt->fetchColumn();
            return $result !== false ? $result : $default;
        } catch (Exception $e) {
            return $default;
        }
    }
} 
if (!function_exists('logActivity')) {
    /**
     * تسجيل النشاط
     */
    function logActivity($action, $description = null, $adminId = null) {
        global $pdo;
        try {
            $stmt = $pdo->prepare("
                INSERT INTO activity_logs (admin_id, action, description, ip_address, user_agent)
                VALUES (?, ?, ?, ?, ?)
            ");
            
            return $stmt->execute([
                $adminId,
                $action,
                $description,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
        } catch (Exception $e) {
            error_log("Failed to log activity: " . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('getOrderStatusText')) {
    /**
     * الحصول على نص حالة الطلب
     */
    function getOrderStatusText($status) {
        $statuses = [
            'pending' => 'قيد المراجعة',
            'confirmed' => 'مؤكد',
            'processing' => 'قيد التجهيز',
            'shipped' => 'تم الشحن',
            'delivered' => 'تم التوصيل',
            'cancelled' => 'ملغي',
            'returned' => 'مرتجع'
        ];
        return $statuses[$status] ?? $status;
    }
}

if (!function_exists('updateQueryString')) {
    /**
     * تحديث سلسلة الاستعلام في URL
     */
    function updateQueryString($key, $value) {
        $query = $_GET;
        $query[$key] = $value;
        return '?' . http_build_query($query);
    }
}

/**
 * تنسيق السعر
 */
function formatPrice($price) {
    return number_format($price, 2) . ' ' . getSetting('currency_symbol', 'ج.م');
}

/**
 * توليد slug من النص العربي
 */
function generateSlug($text) {
    $text = trim($text);
    $text = preg_replace('/[^\p{Arabic}\p{L}\p{N}\s-]/u', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    $text = trim($text, '-');
    return strtolower($text);
}

/**
 * رفع صورة
 */
function uploadImage($file, $folder = 'products') {
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return false;
    }
    
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_IMAGES)) {
        return false;
    }
    
    if ($file['size'] > MAX_UPLOAD_SIZE) {
        return false;
    }
    
    $uploadDir = UPLOAD_PATH . $folder . '/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $filename = uniqid() . '_' . time() . '.' . $ext;
    $filepath = $uploadDir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return $folder . '/' . $filename;
    }
    
    return false;
}

/**
 * إرسال بريد إلكتروني
 */
function sendEmail($to, $subject, $message, $isHtml = true) {
    $headers = "From: " . SMTP_NAME . " <" . SMTP_FROM . ">\r\n";
    if ($isHtml) {
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    }
    
    return mail($to, $subject, $message, $headers);
}

// ==================== دوال التقييمات والمفضلة ====================

/**
 * إضافة تقييم جديد للمنتج
 */
function addProductReview($productId, $data) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO reviews 
            (product_id, customer_id, first_name, email, rating, title, comment, is_verified_purchase, is_approved, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, NOW())
        ");
        
        $result = $stmt->execute([
            $productId,
            $data['customer_id'] ?? null,
            $data['first_name'],
            $data['email'],
            $data['rating'],
            $data['title'],
            $data['comment'],
            $data['is_verified_purchase'] ?? 0
        ]);
        
        if ($result) {
            // تحديث متوسط التقييمات للمنتج
            updateProductRating($productId);
            return $pdo->lastInsertId();
        }
        
        return false;
    } catch (PDOException $e) {
        error_log("Review addition failed: " . $e->getMessage());
        return false;
    }
}

/**
 * تحديث متوسط تقييم المنتج
 */
function updateProductRating($productId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("CALL update_product_rating(?)");
        return $stmt->execute([$productId]);
    } catch (PDOException $e) {
        error_log("Rating update failed: " . $e->getMessage());
        return false;
    }
}

/**
 * جلب تقييمات المنتج مع تفاصيل إضافية
 */
function getProductReviews($productId, $limit = 10, $approvedOnly = true) {
    global $pdo;
    
    $whereClause = "r.product_id = :product_id";
    if ($approvedOnly) {
        $whereClause .= " AND r.is_approved = 1";
    }
    
    $stmt = $pdo->prepare("
        SELECT r.*, 
               COALESCE(CONCAT(c.first_name, ' ', c.last_name), r.first_name) as reviewer_name,
               CASE WHEN r.customer_id IS NOT NULL THEN 1 ELSE 0 END as is_registered_user
        FROM reviews r
        LEFT JOIN customers c ON r.customer_id = c.id
        WHERE $whereClause
        ORDER BY r.created_at DESC
        LIMIT :limit
    ");
    
    $stmt->bindValue(':product_id', (int)$productId, PDO::PARAM_INT);
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * التحقق من إمكانية إضافة تقييم
 */
function canAddReview($productId, $customerId = null, $email = null) {
    global $pdo;
    
    // التحقق من وجود تقييم سابق
    $sql = "SELECT id FROM reviews WHERE product_id = ? AND (customer_id = ? OR email = ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$productId, $customerId, $email]);
    
    return $stmt->fetch() === false;
}

/**
 * إضافة منتج إلى المفضلة
 */
function addToWishlist($customerId, $productId) {
    global $pdo;
    
    try {
        // التحقق من عدم وجود المنتج في المفضلة مسبقاً
        $stmt = $pdo->prepare("SELECT id FROM wishlists WHERE customer_id = ? AND product_id = ?");
        $stmt->execute([$customerId, $productId]);
        
        if ($stmt->fetch()) {
            return false; // المنتج موجود بالفعل
        }
        
        $stmt = $pdo->prepare("INSERT INTO wishlists (customer_id, product_id) VALUES (?, ?)");
        return $stmt->execute([$customerId, $productId]);
    } catch (PDOException $e) {
        error_log("Wishlist addition failed: " . $e->getMessage());
        return false;
    }
}

/**
 * إزالة منتج من المفضلة
 */
function removeFromWishlist($customerId, $productId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("DELETE FROM wishlists WHERE customer_id = ? AND product_id = ?");
        return $stmt->execute([$customerId, $productId]);
    } catch (PDOException $e) {
        error_log("Wishlist removal failed: " . $e->getMessage());
        return false;
    }
}

/**
 * التحقق من وجود منتج في المفضلة
 */
/**
 * التحقق من وجود منتج في المفضلة
 */
function isInWishlist($customerId, $productId) {
    global $pdo;
    
    if (!$customerId) return false;
    
    try {
        $stmt = $pdo->prepare("SELECT id FROM wishlists WHERE customer_id = ? AND product_id = ?");
        $stmt->execute([$customerId, $productId]);
        return $stmt->fetch() !== false;
    } catch (PDOException $e) {
        error_log("Wishlist check failed: " . $e->getMessage());
        return false;
    }
}

/**
 * جلب منتجات المفضلة للعميل
 */
function getWishlistProducts($customerId) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT p.*, w.created_at as added_date
        FROM wishlists w
        JOIN products p ON w.product_id = p.id
        WHERE w.customer_id = ? AND p.is_active = 1
        ORDER BY w.created_at DESC
    ");
    $stmt->execute([$customerId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * جلب إحصائيات التقييمات للمنتج
 */
function getProductRatingStats($productId) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_reviews,
            AVG(rating) as average_rating,
            SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star,
            SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star,
            SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star,
            SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star,
            SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star
        FROM reviews 
        WHERE product_id = ? AND is_approved = 1
    ");
    $stmt->execute([$productId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * التصويت على تقييم (مفيد/غير مفيد)
 */
function voteOnReview($reviewId, $isHelpful = true) {
    global $pdo;
    
    try {
        if ($isHelpful) {
            $stmt = $pdo->prepare("UPDATE reviews SET helpful_count = helpful_count + 1 WHERE id = ?");
        } else {
            $stmt = $pdo->prepare("UPDATE reviews SET helpful_count = GREATEST(0, helpful_count - 1) WHERE id = ?");
        }
        
        return $stmt->execute([$reviewId]);
    } catch (PDOException $e) {
        error_log("Review vote failed: " . $e->getMessage());
        return false;
    }
}

/**
 * عدد عناصر المفضلة للعميل
 */
function getWishlistCount() {
    if (!isset($_SESSION['customer_id'])) {
        return 0;
    }
    
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM wishlists WHERE customer_id = ?");
    $stmt->execute([$_SESSION['customer_id']]);
    return $stmt->fetchColumn();
}
/**
 * معالجة رفع الصور
 */
/**
 * معالجة رفع الصور
 */
function handleImageUpload($file, $subfolder = '') {
    $upload_dir = '../uploads/';
    if (!empty($subfolder)) {
        $upload_dir .= $subfolder . '/';
    }
    
    // إنشاء المجلد إذا لم يكن موجوداً
    if (!file_exists($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
            return ['success' => false, 'error' => 'لا يمكن إنشاء مجلد التحميل'];
        }
        // حماية المجلد بملف htaccess
        file_put_contents($upload_dir . '.htaccess', "Order deny,allow\nDeny from all");
    }
    
    // التحقق من وجود خطأ في الرفع
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $upload_errors = [
            UPLOAD_ERR_INI_SIZE => 'حجم الملف أكبر من المسموح به في السيرفر',
            UPLOAD_ERR_FORM_SIZE => 'حجم الملف أكبر من المسموح به في النموذج',
            UPLOAD_ERR_PARTIAL => 'تم رفع جزء من الملف فقط',
            UPLOAD_ERR_NO_FILE => 'لم يتم اختيار ملف',
            UPLOAD_ERR_NO_TMP_DIR => 'المجلد المؤقت غير موجود',
            UPLOAD_ERR_CANT_WRITE => 'فشل في كتابة الملف على القرص',
            UPLOAD_ERR_EXTENSION => 'رفع الملف متوقف بسبب الامتداد'
        ];
        return ['success' => false, 'error' => $upload_errors[$file['error']] ?? 'خطأ غير معروف في الرفع'];
    }
    
    // التحقق من نوع الملف
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime_type, $allowed_types)) {
        return ['success' => false, 'error' => 'نوع الملف غير مسموح به. المسموح: JPG, PNG, GIF, WebP'];
    }
    
    // التحقق من حجم الملف (5MB كحد أقصى)
    if ($file['size'] > 5 * 1024 * 1024) {
        return ['success' => false, 'error' => 'حجم الملف كبير جداً (الحد الأقصى 5MB)'];
    }
    
    // التحقق من أن الملف صورة حقيقية
    if (!getimagesize($file['tmp_name'])) {
        return ['success' => false, 'error' => 'الملف المختار ليس صورة صالحة'];
    }
    
    // إنشاء اسم فريد وآمن للملف
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $safe_name = preg_replace('/[^a-zA-Z0-9_-]/', '', pathinfo($file['name'], PATHINFO_FILENAME));
    $file_name = $safe_name . '_' . uniqid() . '.' . $file_extension;
    $file_path = $upload_dir . $file_name;
    
    // رفع الملف
    if (move_uploaded_file($file['tmp_name'], $file_path)) {
        // تغيير صلاحيات الملف 
        chmod($file_path, 0644);
        return [
            'success' => true, 
            'file_name' => $file_name, // هذا يجب أن يكون نصاً
            'file_path' => $file_path
        ];
    } else {
        return ['success' => false, 'error' => 'فشل في رفع الملف'];
    }
}

 

/**
 * تسجيل النشاط في النظام
 */
 
 
/**
 * تعيين طلب لمندوب توصيل
 */
 function assignOrderToAgent($order_id, $agent_id) {
    global $pdo;
    
    try {
        // حساب قيمة العمولة
        $stmt = $pdo->prepare("
            SELECT o.total, da.commission_rate 
            FROM orders o, delivery_agents da 
            WHERE o.id = ? AND da.id = ?
        ");
        $stmt->execute([$order_id, $agent_id]);
        $data = $stmt->fetch();
        
        $commission_amount = 0;
        if ($data && $data['commission_rate'] > 0) {
            $commission_amount = ($data['total'] * $data['commission_rate']) / 100;
        }
        
        // تعيين الطلب للمندوب
        $stmt = $pdo->prepare("
            INSERT INTO agent_orders (order_id, agent_id, commission_amount) 
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$order_id, $agent_id, $commission_amount]);
        
        // تحديث حالة الطلب
        $stmt = $pdo->prepare("UPDATE orders SET status = 'processing' WHERE id = ?");
        $stmt->execute([$order_id]);
        
        return true;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * تحديث حالة توصيل الطلب
 */
 function updateOrderDeliveryStatus($order_id, $status, $notes = '', $proof = '') {
    global $pdo;
    
    try {
        $update_data = ['status' => $status];
        
        if ($status === 'delivered') {
            $update_data['delivered_at'] = date('Y-m-d H:i:s');
        }
        
        if (!empty($notes)) {
            $update_data['delivery_notes'] = $notes;
        }
        
        if (!empty($proof)) {
            $update_data['delivery_proof'] = $proof;
        }
        
        $stmt = $pdo->prepare("
            UPDATE agent_orders 
            SET " . implode(', ', array_map(fn($key) => "$key = ?", array_keys($update_data))) . "
            WHERE order_id = ?
        ");
        $stmt->execute(array_merge(array_values($update_data), [$order_id]));
        
        // تحديث حالة الطلب الرئيسي
        $order_status = 'processing';
        if ($status === 'delivered') {
            $order_status = 'delivered';
        } elseif ($status === 'cancelled') {
            $order_status = 'cancelled';
        }
        
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$order_status, $order_id]);
        
        return true;
    } catch (Exception $e) {
        return false;
    }
}
 
 function getShippingCost($region) {
    global $pdo;
    
    // البحث في جدول shipping_rates أولاً
    $stmt = $pdo->prepare("SELECT cost FROM shipping_rates WHERE region = ? AND is_active = 1");
    $stmt->execute([$region]);
    $rate = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($rate) {
        return $rate['cost'];
    }
    
    // إذا لم توجد في shipping_rates، استخدم الإعدادات العامة
    $settings = getSettings();
    
    if (strpos($region, 'القاهرة') !== false) {
        return $settings['shipping_cost_cairo'] ?? 30;
    } elseif (strpos($region, 'الجيزة') !== false) {
        return $settings['shipping_cost_giza'] ?? 30;
    } elseif (strpos($region, 'الإسكندرية') !== false) {
        return $settings['shipping_cost_alex'] ?? 50;
    } else {
        return $settings['shipping_cost_other'] ?? 70;
    }
}


/**
 * دوال نظام النقاط
 */

// الحصول على نقاط العميل
function getCustomerPoints($customer_id) {
    global $pdo;
    
    if (!$customer_id) {
        return [
            'points' => 0,
            'total_earned' => 0,
            'total_spent' => 0,
            'available_points' => 0
        ];
    }
    
    try {
        // التحقق من وجود العميل أولاً
        $stmt = $pdo->prepare("SELECT id FROM customers WHERE id = ?");
        $stmt->execute([$customer_id]);
        if (!$stmt->fetch()) {
            return [
                'points' => 0,
                'total_earned' => 0,
                'total_spent' => 0,
                'available_points' => 0
            ];
        }
        
        $stmt = $pdo->prepare("
            SELECT cp.*, 
                   (SELECT SUM(points) FROM point_transactions 
                    WHERE customer_id = ? AND type = 'earn' AND (expires_at IS NULL OR expires_at > NOW())) as available_points
            FROM customer_points cp 
            WHERE customer_id = ?
        ");
        $stmt->execute([$customer_id, $customer_id]);
        $points = $stmt->fetch();
        
        if (!$points) {
            // إنشاء سجل جديد فقط إذا كان العميل موجوداً
            $stmt = $pdo->prepare("INSERT INTO customer_points (customer_id, points) VALUES (?, 0)");
            $stmt->execute([$customer_id]);
            
            return [
                'points' => 0,
                'total_earned' => 0,
                'total_spent' => 0,
                'available_points' => 0
            ];
        }
        
        return $points;
    } catch (PDOException $e) {
        error_log("Error getting customer points: " . $e->getMessage());
        return [
            'points' => 0,
            'total_earned' => 0,
            'total_spent' => 0,
            'available_points' => 0
        ];
    }
}
// إضافة نقاط للعميل
function addCustomerPoints2($customer_id, $points, $description, $reference_type = null, $reference_id = null, $expire_days = null) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // تحديث النقاط الإجمالية
        $stmt = $pdo->prepare("
            INSERT INTO customer_points (customer_id, points, total_earned) 
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            points = points + VALUES(points),
            total_earned = total_earned + VALUES(total_earned)
        ");
        $stmt->execute([$customer_id, $points, $points]);
        
        // تسجيل المعاملة
        $expires_at = null;
        if ($expire_days) {
            $expires_at = date('Y-m-d H:i:s', strtotime("+$expire_days days"));
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO point_transactions (customer_id, points, type, description, reference_type, reference_id, expires_at)
            VALUES (?, ?, 'earn', ?, ?, ?, ?)
        ");
        $stmt->execute([$customer_id, $points, $description, $reference_type, $reference_id, $expires_at]);
        
        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        return false;
    }
}
/**
 * إضافة نقاط للعميل مع تاريخ انتهاء صلاحية (أيام)
 *
 * @param int    $customerId
 * @param int    $points
 * @param string $description
 * @param string $type        (order|manual|bonus|...)
 * @param int    $orderId     NULL إذا لم يكن مرتبطاً بطلب
 * @param int    $validDays   عدد الأيام التي تظل النقاط صالحة (0 = لا تنتهي)
 * @return bool  true إذا تمت الإضافة/التحديث بنجاح
 */
function addCustomerPoints3(int $customerId,int $points,string $description,string $type = 'manual',int $orderId = null,int $validDays = 0): bool {
    global $pdo;

    if ($points <= 0) return false;

    $expiresAt = $validDays > 0
        ? (new DateTime())->modify("+$validDays days")->format('Y-m-d H:i:s')
        : null;

    // إذا كان السجل موجوداً → UPDATE، غير ذلك → INSERT
    $sql = "
        INSERT INTO customer_points 
            (customer_id, points, total_earned, expires_at)
        VALUES 
            (:cid, :pts, :pts, :exp)
        ON DUPLICATE KEY UPDATE
            points       = points + :pts,
            total_earned = total_earned + :pts,
            expires_at   = IF(:exp IS NULL, expires_at, :exp)
    ";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':cid' => $customerId,
            ':pts' => $points,
            ':exp' => $expiresAt
        ]);

        $rows = $stmt->rowCount();   // 1 أو 2 (INSERT أو UPDATE)

        // سجل الحركة في جدول المعاملات
        $logSql = "
            INSERT INTO customer_points_transactions
                (customer_id, points, type, description, order_id, created_at)
            VALUES
                (:cid, :pts, :type, :desc, :oid, NOW())
        ";
        $logStmt = $pdo->prepare($logSql);
        $logStmt->execute([
            ':cid'  => $customerId,
            ':pts'  => $points,
            ':type' => $type,
            ':desc' => $description,
            ':oid'  => $orderId
        ]);

        return $rows > 0;
    } catch (Throwable $e) {
        // يمكنك تفعيل error_log لتتبع الأخطاء في السيرفر
        error_log('addCustomerPoints error: ' . $e->getMessage());
        return false;
    }
}
// استهلاك نقاط العميل
function spendCustomerPoints($customer_id, $points, $description, $reference_type = null, $reference_id = null) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // التحقق من وجود نقاط كافية
        $current_points = getCustomerPoints($customer_id)['available_points'];
        if ($current_points < $points) {
            throw new Exception("نقاط غير كافية");
        }
        
        // تحديث النقاط
        $stmt = $pdo->prepare("
            UPDATE customer_points 
            SET points = points - ?, total_spent = total_spent + ? 
            WHERE customer_id = ?
        ");
        $stmt->execute([$points, $points, $customer_id]);
        
        // تسجيل المعاملة
        $stmt = $pdo->prepare("
            INSERT INTO point_transactions (customer_id, points, type, description, reference_type, reference_id)
            VALUES (?, ?, 'spend', ?, ?, ?)
        ");
        $stmt->execute([$customer_id, $points, $description, $reference_type, $reference_id]);
        
        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        return false;
    }
}

// حساب النقاط المكتسبة من قيمة الشراء
function calculatePointsFromPurchase($amount) {
    $earn_rate = getSetting('points_earn_rate', 10);
    return floor($amount * $earn_rate / 100);
}

// تحويل النقاط إلى قيمة مالية
function pointsToCurrency($points) {
    $rate = getSetting('points_currency_rate', 100);
    return $points / $rate;
}

// الحصول على معاملات النقاط
function getPointTransactions($customer_id, $limit = 10) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT * FROM point_transactions 
        WHERE customer_id = ? 
        ORDER BY created_at DESC 
        LIMIT ?
    ");
    $stmt->execute([$customer_id, $limit]);
    return $stmt->fetchAll();
}

 

// تحديث نقاط العميل في الوقت الفعلي (لـ AJAX)
function updateCustomerPointsDisplay() {
    if (isset($_SESSION['customer_id'])) {
        $points = getCustomerPoints($_SESSION['customer_id']);
        return [
            'success' => true,
            'points' => $points['available_points'],
            'formatted_points' => number_format($points['available_points'])
        ];
    }
    return ['success' => false, 'points' => 0];
}


/**
 * دوال نظام الباقات
 */

// الحصول على جميع الباقات النشطة
function getActivePackages($limit = null) {
    global $pdo;
    
    $sql = "SELECT * FROM packages WHERE is_active = 1 ORDER BY display_order ASC, created_at DESC";
    if ($limit) {
        $sql .= " LIMIT " . (int)$limit;
    }
    
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll();
}

// الحصول على الباقات المميزة
function getFeaturedPackages($limit = 4) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT * FROM packages 
        WHERE is_active = 1 AND is_featured = 1 
        ORDER BY display_order ASC, created_at DESC 
        LIMIT ?
    ");
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

// الحصول على باقة محددة
function getPackage($package_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM packages WHERE id = ? AND is_active = 1");
    $stmt->execute([$package_id]);
    return $stmt->fetch();
}

// إنشاء طلب باقة
function createPackageOrder($customer_id, $package_id) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // الحصول على بيانات الباقة
        $package = getPackage($package_id);
        if (!$package) {
            throw new Exception("الباقة غير متاحة");
        }
        
        // إنشاء رقم طلب فريد
        $order_number = 'PKG' . date('Ymd') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        // إدخال طلب الباقة
        $stmt = $pdo->prepare("
            INSERT INTO package_orders (order_number, customer_id, package_id, points_amount, price, payment_method, status)
            VALUES (?, ?, ?, ?, ?, 'cod', 'pending')
        ");
        
        $total_points = $package['points'] + $package['bonus_points'];
        $stmt->execute([
            $order_number,
            $customer_id,
            $package_id,
            $total_points,
            $package['price']
        ]);
        
        $order_id = $pdo->lastInsertId();
        $pdo->commit();
        
        return [
            'success' => true,
            'order_id' => $order_id,
            'order_number' => $order_number,
            'package' => $package
        ];
        
    } catch (Exception $e) {
        $pdo->rollBack();
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

// معالجة دفع الباقة
function processPackagePayment($order_id) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // الحصول على بيانات الطلب
        $stmt = $pdo->prepare("
            SELECT po.*, p.name as package_name 
            FROM package_orders po 
            JOIN packages p ON po.package_id = p.id 
            WHERE po.id = ? AND po.payment_status = 'pending'
        ");
        $stmt->execute([$order_id]);
        $order = $stmt->fetch();
        
        if (!$order) {
            throw new Exception("طلب الباقة غير موجود أو مدفوع مسبقاً");
        }
        
        // تحديث حالة الدفع
        $stmt = $pdo->prepare("
            UPDATE package_orders 
            SET payment_status = 'paid', status = 'completed', updated_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$order_id]);
        
        // إضافة النقاط للعميل
        if (addCustomerPoints(
            $order['customer_id'], 
            $order['points_amount'], 
            "شراء باقة {$order['package_name']}",
            'package',
            $order_id,
            getSetting('points_expire_days', 365)
        )) {
            // تحديث حالة إضافة النقاط
            $stmt = $pdo->prepare("UPDATE package_orders SET points_added = 1 WHERE id = ?");
            $stmt->execute([$order_id]);
        }
        
        $pdo->commit();
        return ['success' => true, 'message' => 'تمت عملية الدفع بنجاح وإضافة النقاط'];
        
    } catch (Exception $e) {
        $pdo->rollBack();
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

// الحصول على طلبات الباقات للعميل
function getCustomerPackageOrders($customer_id, $limit = 10) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT po.*, p.name as package_name, p.points as base_points, p.bonus_points
        FROM package_orders po
        JOIN packages p ON po.package_id = p.id
        WHERE po.customer_id = ?
        ORDER BY po.created_at DESC
        LIMIT ?
    ");
    $stmt->execute([$customer_id, $limit]);
    return $stmt->fetchAll();
}

// دالة تحويل حالة الطلب إلى نص عربي
function getOrderStatusText($status) {
    $statuses = [
        'pending' => 'قيد الانتظار',
        'confirmed' => 'تم التأكيد',
        'processing' => 'قيد المعالجة',
        'shipped' => 'تم الشحن',
        'delivered' => 'تم التسليم',
        'cancelled' => 'ملغي',
        'returned' => 'مرتجع'
    ];
    return $statuses[$status] ?? $status;
}

// دالة تحويل طريقة الدفع إلى نص عربي
function getPaymentMethodText($method) {
    $methods = [
        'cash' => 'الدفع عند الاستلام',
        'credit_card' => 'بطاقة ائتمان',
        'bank_transfer' => 'تحويل بنكي',
        'wallet' => 'المحفظة الإلكترونية'
    ];
    return $methods[$method] ?? $method;
}

// دالة تحقق من صلاحية الصورة
function isValidImage($file) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    return in_array($file['type'], $allowed_types);
}

 
 // دوال التفاوض
function isNegotiationEnabled() {
    return getSetting('negotiation_enabled', '1') == '1';
}

function getMinNegotiationPrice($productPrice) {
    $minPercentage = getSetting('negotiation_min_percentage', '70');
    return $productPrice * ($minPercentage / 100);
}

function hasActiveNegotiation($customerId, $productId) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM product_negotiations 
        WHERE customer_id = ? AND product_id = ? AND status = 'pending'
    ");
    $stmt->execute([$customerId, $productId]);
    return $stmt->fetchColumn() > 0;
}

function getCustomerNegotiation($customerId, $productId) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT * FROM product_negotiations 
        WHERE customer_id = ? AND product_id = ? 
        ORDER BY created_at DESC LIMIT 1
    ");
    $stmt->execute([$customerId, $productId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function submitNegotiation($customerId, $productId, $offeredPrice, $notes = '') {
    global $pdo;
    
    try {
        // الحصول على سعر المنتج
        $stmt = $pdo->prepare("SELECT final_price FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$product) {
            return ['success' => false, 'message' => 'المنتج غير موجود'];
        }
        
        $productPrice = $product['final_price'];
        $minPrice = getMinNegotiationPrice($productPrice);
        
        // التحقق من السعر المقبول
        if ($offeredPrice < $minPrice) {
            return [
                'success' => false, 
                'message' => "السعر المقترح أقل من الحد الأدنى للتفاوض (" . formatPrice($minPrice) . ")"
            ];
        }
        
        if ($offeredPrice >= $productPrice) {
            return [
                'success' => false, 
                'message' => 'يمكنك شراء المنتج بالسعر الأصلي'
            ];
        }
        
        // التحقق من وجود تفاوض نشط
        if (hasActiveNegotiation($customerId, $productId)) {
            return ['success' => false, 'message' => 'لديك تفاوض نشط على هذا المنتج بالفعل'];
        }
        
        // إضافة التفاوض
        $stmt = $pdo->prepare("
            INSERT INTO product_negotiations (product_id, customer_id, offered_price, customer_notes, status)
            VALUES (?, ?, ?, ?, 'pending')
        ");
        $stmt->execute([$productId, $customerId, $offeredPrice, $notes]);
        
        return ['success' => true, 'message' => 'تم إرسال طلب التفاوض بنجاح'];
        
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'حدث خطأ في النظام'];
    }
}

// الحصول على حالة المنتج
function getProductCondition($condition) {
    $conditions = [
        'new' => ['label' => 'جديد', 'icon' => 'fas fa-tag', 'color' => '#28a745'],
        'used' => ['label' => 'مستعمل', 'icon' => 'fas fa-redo', 'color' => '#ffc107'],
        'refurbished' => ['label' => 'مجدد', 'icon' => 'fas fa-tools', 'color' => '#17a2b8'],
        'needs_repair' => ['label' => 'يحتاج صيانة', 'icon' => 'fas fa-wrench', 'color' => '#dc3545']
    ];
    return $conditions[$condition] ?? $conditions['new'];
}

// الحصول على أيقونة العرض الخاص
function getSpecialOfferIcon($type, $value) {
    if ($type == 'none') return null;
    $offers = [
        'points' => ['icon' => 'fas fa-coins', 'text' => "اكسب {$value} نقطة", 'color' => '#f59e0b'],
        'coupon' => ['icon' => 'fas fa-ticket-alt', 'text' => "كوبون خصم {$value}%", 'color' => '#10b981'],
        'gift' => ['icon' => 'fas fa-gift', 'text' => "هدية مجانية", 'color' => '#ef4444'],
        'discount' => ['icon' => 'fas fa-bolt', 'text' => "خصم إضافي {$value}%", 'color' => '#8b5cf6']
    ];
    return $offers[$type] ?? null;
}

// التحقق من المزايدة النشطة
 
// الحصول على الوقت المتبقي للمزايدة
 
// الحصول على أعلى مزايدة
 
// الحصول على عدد المزايدات
function getBidCount($productId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) as bid_count FROM product_bids WHERE product_id = ?");
    $stmt->execute([$productId]);
    return $stmt->fetch(PDO::FETCH_ASSOC)['bid_count'] ?? 0;
}

// التحقق من العد التنازلي النشط
function getActiveCountdown($productId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM price_countdowns WHERE product_id = ? AND is_active = 1 AND countdown_end > NOW() ORDER BY countdown_end ASC LIMIT 1");
    $stmt->execute([$productId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
 
 

 /**
 * تحديث إعداد في قاعدة البيانات
 */
function updateSetting($key, $value) {
    global $pdo;
    
    try {
        // التحقق مما إذا كان الإعداد موجوداً
        $stmt = $pdo->prepare("SELECT id FROM settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            // تحديث الإعداد الموجود
            $stmt = $pdo->prepare("UPDATE settings SET setting_value = ?, updated_at = NOW() WHERE setting_key = ?");
            $stmt->execute([$value, $key]);
        } else {
            // إضافة إعداد جديد
            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value, setting_type) VALUES (?, ?, 'text')");
            $stmt->execute([$key, $value]);
        }
        
        return true;
    } catch (PDOException $e) {
        error_log("Error updating setting: " . $e->getMessage());
        return false;
    }
}

/**
 * الحصول على إعداد من قاعدة البيانات
 */
 
 
 
 // الحصول على كروت الخربشة المتاحة للعميل
function getCustomerScratchCards($customer_id, $product_id = null) {
    global $pdo;
    
    $sql = "SELECT sc.*, p.title as product_name 
            FROM scratch_cards sc 
            LEFT JOIN products p ON sc.product_id = p.id 
            WHERE sc.customer_id = ? AND sc.is_scratched = 0 
            AND (sc.expires_at IS NULL OR sc.expires_at > NOW())";
    
    $params = [$customer_id];
    
    if ($product_id) {
        $sql .= " AND sc.product_id = ?";
        $params[] = $product_id;
    }
    
    $sql .= " ORDER BY sc.created_at DESC";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting scratch cards: " . $e->getMessage());
        return [];
    }
}

// إنشاء كارت خربشة جديد
function createScratchCard($customer_id, $product_id, $reward_type, $reward_value, $reward_description = null, $expires_days = 30) {
    global $pdo;
    
    $card_code = generateScratchCardCode();
    $expires_at = date('Y-m-d H:i:s', strtotime("+$expires_days days"));
    
    $sql = "INSERT INTO scratch_cards (customer_id, product_id, card_code, reward_type, reward_value, reward_description, expires_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$customer_id, $product_id, $card_code, $reward_type, $reward_value, $reward_description, $expires_at]);
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        error_log("Error creating scratch card: " . $e->getMessage());
        return false;
    }
}

// توليد كود فريد للكارت
function generateScratchCardCode() {
    return 'SCR' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 10)) . time();
}

// خربشة الكارت
function scratchCard($card_id, $customer_id) {
    global $pdo;
    
    $sql = "UPDATE scratch_cards SET is_scratched = 1, scratched_at = NOW() 
            WHERE id = ? AND customer_id = ? AND is_scratched = 0";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$card_id, $customer_id]);
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        error_log("Error scratching card: " . $e->getMessage());
        return false;
    }
}

// المطالبة بالمكافأة
function claimScratchCardReward($card_id, $customer_id) {
    global $pdo;
    
    // البدء بعملية transaction
    $pdo->beginTransaction();
    
    try {
        // الحصول على بيانات الكارت
        $stmt = $pdo->prepare("SELECT * FROM scratch_cards WHERE id = ? AND customer_id = ? AND is_scratched = 1 AND is_claimed = 0");
        $stmt->execute([$card_id, $customer_id]);
        $card = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$card) {
            throw new Exception("Card not found or already claimed");
        }
        
        // تطبيق المكافأة حسب النوع
        $success = false;
        switch ($card['reward_type']) {
            case 'points':
                $success = addCustomerPoints($customer_id, $card['reward_value'], "Scratch card reward - {$card['card_code']}");
                break;
            case 'discount':
                // إنشاء كوبون خصم
                $coupon_code = 'SCR' . strtoupper(substr(md5(uniqid()), 0, 8));
                $success = createDiscountCoupon($coupon_code, $card['reward_value'], $customer_id);
                break;
            // يمكن إضافة أنواع أخرى من المكافآت
        }
        
        if ($success) {
            // تحديث حالة الكارت كمطالب به
            $stmt = $pdo->prepare("UPDATE scratch_cards SET is_claimed = 1, claimed_at = NOW() WHERE id = ?");
            $stmt->execute([$card_id]);
            
            $pdo->commit();
            return $card;
        } else {
            throw new Exception("Failed to apply reward");
        }
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error claiming scratch card reward: " . $e->getMessage());
        return false;
    }
}

// إنشاء كوبون خصم
function createDiscountCoupon($code, $discount_value, $customer_id) {
    global $pdo;
    
    $sql = "INSERT INTO coupons (code, description, discount_type, discount_value, min_order_amount, usage_limit, valid_until) 
            VALUES (?, ?, 'percentage', ?, 0, 1, DATE_ADD(NOW(), INTERVAL 30 DAY))";
    
    try {
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$code, "Scratch card discount for customer $customer_id", $discount_value]);
    } catch (PDOException $e) {
        error_log("Error creating discount coupon: " . $e->getMessage());
        return false;
    }
}
 
 
 // الحصول على جميع كروت الخربشة مع إمكانية التصفية
function getAllScratchCards($filters = []) {
    global $pdo;
    
    $sql = "SELECT sc.*, c.first_name, c.last_name, c.email, p.title as product_name 
            FROM scratch_cards sc 
            LEFT JOIN customers c ON sc.customer_id = c.id 
            LEFT JOIN products p ON sc.product_id = p.id 
            WHERE 1=1";
    
    $params = [];
    
    if (!empty($filters['customer_id'])) {
        $sql .= " AND sc.customer_id = ?";
        $params[] = $filters['customer_id'];
    }
    
    if (!empty($filters['product_id'])) {
        $sql .= " AND sc.product_id = ?";
        $params[] = $filters['product_id'];
    }
    
    if (!empty($filters['reward_type'])) {
        $sql .= " AND sc.reward_type = ?";
        $params[] = $filters['reward_type'];
    }
    
    if (!empty($filters['is_scratched'])) {
        $sql .= " AND sc.is_scratched = ?";
        $params[] = $filters['is_scratched'];
    }
    
    if (!empty($filters['is_claimed'])) {
        $sql .= " AND sc.is_claimed = ?";
        $params[] = $filters['is_claimed'];
    }
    
    if (!empty($filters['search'])) {
        $sql .= " AND (sc.card_code LIKE ? OR c.first_name LIKE ? OR c.last_name LIKE ? OR p.title LIKE ?)";
        $searchTerm = "%{$filters['search']}%";
        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    }
    
    $sql .= " ORDER BY sc.created_at DESC";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting scratch cards: " . $e->getMessage());
        return [];
    }
}

// إنشاء كروت خربشة جماعية
function createBulkScratchCards($data) {
    global $pdo;
    
    $pdo->beginTransaction();
    
    try {
        $created_count = 0;
        
        foreach ($data['customer_ids'] as $customer_id) {
            for ($i = 0; $i < $data['cards_per_customer']; $i++) {
                $card_code = generateScratchCardCode();
                $expires_at = $data['expires_at'] ?: date('Y-m-d H:i:s', strtotime("+30 days"));
                
                $sql = "INSERT INTO scratch_cards (customer_id, product_id, card_code, reward_type, reward_value, reward_description, expires_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $customer_id,
                    $data['product_id'],
                    $card_code,
                    $data['reward_type'],
                    $data['reward_value'],
                    $data['reward_description'],
                    $expires_at
                ]);
                
                $created_count++;
            }
        }
        
        $pdo->commit();
        return $created_count;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error creating bulk scratch cards: " . $e->getMessage());
        return false;
    }
}

// الحصول على إحصائيات كروت الخربشة
function getScratchCardsStats() {
    global $pdo;
    
    $sql = "SELECT 
            COUNT(*) as total_cards,
            SUM(is_scratched) as scratched_cards,
            SUM(is_claimed) as claimed_cards,
            AVG(CASE WHEN is_scratched = 1 THEN 1 ELSE 0 END) * 100 as scratch_rate,
            AVG(CASE WHEN is_claimed = 1 THEN 1 ELSE 0 END) * 100 as claim_rate,
            SUM(CASE WHEN reward_type = 'points' THEN reward_value ELSE 0 END) as total_points_given,
            SUM(CASE WHEN reward_type = 'discount' THEN reward_value ELSE 0 END) as total_discount_given
            FROM scratch_cards";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting scratch cards stats: " . $e->getMessage());
        return [];
    }
}

// حذف كارت خربشة
function deleteScratchCard($card_id) {
    global $pdo;
    
    $sql = "DELETE FROM scratch_cards WHERE id = ?";
    
    try {
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$card_id]);
    } catch (PDOException $e) {
        error_log("Error deleting scratch card: " . $e->getMessage());
        return false;
    }
}
 
 // تسجيل نشاط المدير
function logAdminActivity($description) {
    global $pdo;
    
    $sql = "INSERT INTO activity_logs (admin_id, action, description, ip_address, user_agent) 
            VALUES (?, 'scratch_card_activity', ?, ?, ?)";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $_SESSION['admin_id'],
            $description,
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['HTTP_USER_AGENT']
        ]);
    } catch (PDOException $e) {
        error_log("Error logging admin activity: " . $e->getMessage());
    }
	     
}


// دوال المزاد
function getAuctionParticipants($productId, $limit = 10) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT 
                pb.*,
                c.first_name,
                c.last_name,
                c.avatar,
                TIMEDIFF(NOW(), pb.created_at) as time_ago
            FROM product_bids pb
            LEFT JOIN customers c ON pb.customer_id = c.id
            WHERE pb.product_id = ?
            ORDER BY pb.bid_amount DESC, pb.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$productId, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting auction participants: " . $e->getMessage());
        return [];
    }
}

function getAuctionStats($productId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_bids,
                COUNT(DISTINCT customer_id) as total_bidders,
                MAX(bid_amount) as highest_bid,
                MIN(bid_amount) as lowest_bid,
                AVG(bid_amount) as average_bid
            FROM product_bids 
            WHERE product_id = ?
        ");
        $stmt->execute([$productId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting auction stats: " . $e->getMessage());
        return null;
    }
}

function placeBid($productId, $customerId, $bidAmount) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // الحصول على أعلى مزايدة حالية
        $currentBid = getHighestBid($productId);
        
        // التحقق من أن المزايدة أعلى من السعر الحالي
        if ($bidAmount <= $currentBid) {
            throw new Exception("يجب أن تكون المزايدة أعلى من السعر الحالي " . formatPrice($currentBid));
        }
        
        // إدخال المزايدة
        $stmt = $pdo->prepare("
            INSERT INTO product_bids (product_id, customer_id, bid_amount)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$productId, $customerId, $bidAmount]);
        
        // تحديث أعلى مزايدة في جدول المنتجات
        $stmt = $pdo->prepare("
            UPDATE products 
            SET current_bid = ?, bid_count = bid_count + 1 
            WHERE id = ?
        ");
        $stmt->execute([$bidAmount, $productId]);
        
        $pdo->commit();
        
        return [
            'success' => true,
            'message' => 'تم تقديم المزايدة بنجاح',
            'new_bid' => $bidAmount
        ];
        
    } catch (Exception $e) {
        $pdo->rollBack();
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

function getHighestBid($productId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT MAX(bid_amount) as max_bid FROM product_bids WHERE product_id = ?");
        $stmt->execute([$productId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['max_bid'] ?? 0;
    } catch (Exception $e) {
        error_log("Error getting highest bid: " . $e->getMessage());
        return 0;
    }
}

function isAuctionActive($product) {
    if (!$product['auction_enabled']) return false;
    if (!$product['auction_end_time']) return false;
    return strtotime($product['auction_end_time']) > time();
}

function getAuctionTimeLeft($endTime) {
    $now = time();
    $end = strtotime($endTime);
    $diff = $end - $now;
    
    if ($diff <= 0) return 'انتهى';
    
    $days = floor($diff / (60 * 60 * 24));
    $hours = floor(($diff % (60 * 60 * 24)) / (60 * 60));
    $minutes = floor(($diff % (60 * 60)) / 60);
    $seconds = $diff % 60;
    
    if ($days > 0) return "{$days} ي {$hours} س";
    if ($hours > 0) return "{$hours} س {$minutes} د";
    return "{$minutes}:{$seconds}";
}
 
 
 // دوال العروض - اشتري قطعتين والثالثة هدية
 
function calculateBuyTwoGetOneDiscount($productPrice, $quantity) {
    if ($quantity < 3) {
        return 0;
    }
    
    // احسب عدد المجموعات (كل 3 قطع تعتبر مجموعة)
    $sets = floor($quantity / 3);
    
    // الخصم = سعر قطعة واحدة لكل مجموعة
    return $sets * $productPrice;
}

function applyBuyTwoGetOneOffer($cartItems) {
    $updatedItems = [];
    
    foreach ($cartItems as $productId => $item) {
        $offer = hasBuyTwoGetOneOffer($productId);
        
        if ($offer && $item['qty'] >= 3) {
            $sets = floor($item['qty'] / 3);
            $discountAmount = $sets * $item['price'];
            
            $updatedItems[$productId] = [
                ...$item,
                'discount' => $discountAmount,
                'final_price' => ($item['price'] * $item['qty']) - $discountAmount,
                'offer_applied' => true,
                'free_items' => $sets
            ];
        } else {
            $updatedItems[$productId] = [
                ...$item,
                'discount' => 0,
                'final_price' => $item['price'] * $item['qty'],
                'offer_applied' => false,
                'free_items' => 0
            ];
        }
    }
    
    return $updatedItems;
}

// دوال العروض - اشتري قطعتين والثالثة هدية
function hasBuyTwoGetOneOffer($productId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM product_offers 
            WHERE product_id = ? 
            AND offer_type = 'buy2_get1' 
            AND is_active = 1 
            AND (start_date IS NULL OR start_date <= NOW()) 
            AND (end_date IS NULL OR end_date >= NOW())
        ");
        $stmt->execute([$productId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error checking buy2get1 offer: " . $e->getMessage());
        return false;
    }
}

function getBuyTwoGetOneProducts($limit = 10) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT p.*, po.title as offer_title, po.description as offer_description
            FROM products p
            INNER JOIN product_offers po ON p.id = po.product_id
            WHERE po.offer_type = 'buy2_get1' 
            AND po.is_active = 1 
            AND p.is_active = 1
            AND p.stock > 0
            AND (po.start_date IS NULL OR po.start_date <= NOW()) 
            AND (po.end_date IS NULL OR po.end_date >= NOW())
            ORDER BY po.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting buy2get1 products: " . $e->getMessage());
        return [];
    }
}

// دوال إدارة العروض
function getAllProductOffers() {
    global $pdo;
    
    try {
        $stmt = $pdo->query("
            SELECT po.*, p.title as product_title, p.main_image as product_image
            FROM product_offers po
            LEFT JOIN products p ON po.product_id = p.id
            ORDER BY po.created_at DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting all offers: " . $e->getMessage());
        return [];
    }
}

function addProductOffer($data) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO product_offers 
            (product_id, offer_type, title, description, is_active, start_date, end_date, min_quantity, usage_limit) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $start_date = !empty($data['start_date']) ? $data['start_date'] : null;
        $end_date = !empty($data['end_date']) ? $data['end_date'] : null;
        $usage_limit = !empty($data['usage_limit']) ? $data['usage_limit'] : null;
        
        $stmt->execute([
            $data['product_id'],
            $data['offer_type'],
            $data['title'],
            $data['description'] ?? null,
            $data['is_active'],
            $start_date,
            $end_date,
            $data['min_quantity'] ?? 3,
            $usage_limit
        ]);
        
        logActivity("add_offer", "تم إضافة عرض جديد: {$data['title']}", $_SESSION['admin_id']);
        
        return [
            'success' => true,
            'message' => 'تم إضافة العرض بنجاح',
            'offer_id' => $pdo->lastInsertId()
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'فشل في إضافة العرض: ' . $e->getMessage()
        ];
    }
}

function updateProductOffer($offerId, $data) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            UPDATE product_offers 
            SET product_id = ?, offer_type = ?, title = ?, description = ?, 
                is_active = ?, start_date = ?, end_date = ?, min_quantity = ?, usage_limit = ?
            WHERE id = ?
        ");
        
        $start_date = !empty($data['start_date']) ? $data['start_date'] : null;
        $end_date = !empty($data['end_date']) ? $data['end_date'] : null;
        $usage_limit = !empty($data['usage_limit']) ? $data['usage_limit'] : null;
        
        $stmt->execute([
            $data['product_id'],
            $data['offer_type'],
            $data['title'],
            $data['description'] ?? null,
            $data['is_active'],
            $start_date,
            $end_date,
            $data['min_quantity'] ?? 3,
            $usage_limit,
            $offerId
        ]);
        
        logActivity("update_offer", "تم تحديث العرض: {$data['title']}", $_SESSION['admin_id']);
        
        return [
            'success' => true,
            'message' => 'تم تحديث العرض بنجاح'
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'فشل في تحديث العرض: ' . $e->getMessage()
        ];
    }
}

function deleteProductOffer($offerId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("DELETE FROM product_offers WHERE id = ?");
        $stmt->execute([$offerId]);
        
        logActivity("delete_offer", "تم حذف العرض رقم: $offerId", $_SESSION['admin_id']);
        
        return [
            'success' => true,
            'message' => 'تم حذف العرض بنجاح'
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'فشل في حذف العرض: ' . $e->getMessage()
        ];
    }
}

function toggleOfferStatus($offerId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            UPDATE product_offers 
            SET is_active = NOT is_active 
            WHERE id = ?
        ");
        $stmt->execute([$offerId]);
        
        // جلب بيانات العرض للتسجيل
        $stmt = $pdo->prepare("SELECT title, is_active FROM product_offers WHERE id = ?");
        $stmt->execute([$offerId]);
        $offer = $stmt->fetch();
        
        $status = $offer['is_active'] ? 'تفعيل' : 'تعطيل';
        logActivity("toggle_offer", "تم $status العرض: {$offer['title']}", $_SESSION['admin_id']);
        
        return [
            'success' => true,
            'message' => "تم {$status} العرض بنجاح"
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'فشل في تغيير حالة العرض: ' . $e->getMessage()
        ];
    }
}

function getOfferTypeText($type) {
    $types = [
        'buy2_get1' => 'اشتري 2 واحصل على 1',
        'discount' => 'خصم',
        'free_shipping' => 'شحن مجاني'
    ];
    return $types[$type] ?? $type;
}

function incrementOfferUsage($offerId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            UPDATE product_offers 
            SET usage_count = usage_count + 1 
            WHERE id = ?
        ");
        return $stmt->execute([$offerId]);
    } catch (Exception $e) {
        error_log("Error incrementing offer usage: " . $e->getMessage());
        return false;
    }
}
 
 function getProductOffer($offerId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT po.*, p.title as product_title 
            FROM product_offers po
            LEFT JOIN products p ON po.product_id = p.id
            WHERE po.id = ?
        ");
        $stmt->execute([$offerId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting product offer: " . $e->getMessage());
        return null;
    }
}
 
 
 
 /**
 * التحقق من صلاحيات المشرف
 */
function checkAdminPermission($permission) {
    if ($_SESSION['admin_role'] == 'super_admin') {
        return true;
    }
    
    if (isset($_SESSION['admin_permissions'])) {
        $permissions = json_decode($_SESSION['admin_permissions'], true);
        return in_array($permission, $permissions) || in_array('all', $permissions);
    }
    
    return false;
}

/**
 * إضافة معاملة للمحفظة
 */
function addWalletTransaction($customer_id, $amount, $type, $description, $reference_type = null, $reference_id = null) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // تحديث رصيد المحفظة
        $stmt = $pdo->prepare("
            INSERT INTO customer_wallets (customer_id, balance, total_deposited, total_withdrawn) 
            VALUES (?, ?, ?, ?) 
            ON DUPLICATE KEY UPDATE 
            balance = balance + VALUES(balance),
            total_deposited = total_deposited + VALUES(total_deposited),
            total_withdrawn = total_withdrawn + VALUES(total_withdrawn)
        ");
        
        $deposited = $type == 'deposit' ? $amount : 0;
        $withdrawn = $type == 'withdrawal' ? $amount : 0;
        $balance = $type == 'deposit' ? $amount : -$amount;
        
        $stmt->execute([$customer_id, $balance, $deposited, $withdrawn]);
        
        // تسجيل المعاملة
        $stmt = $pdo->prepare("
            INSERT INTO wallet_transactions (customer_id, amount, type, description, reference_type, reference_id, status) 
            VALUES (?, ?, ?, ?, ?, ?, 'completed')
        ");
        $stmt->execute([$customer_id, $amount, $type, $description, $reference_type, $reference_id]);
        
        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        $pdo->rollBack();
        return false;
    }
}

/**
 * الحصول على بيانات محفظة العميل
 */
/**
 * الحصول على بيانات محفظة العميل - الإصدار المصحح
 */
function getCustomerWallet($customer_id) {
    global $pdo;
    
    try {
        // إضافة ORDER BY و LIMIT لمنع multiple records
        $stmt = $pdo->prepare("
            SELECT * FROM customer_wallets 
            WHERE customer_id = ? 
            ORDER BY id DESC 
            LIMIT 1
        ");
        $stmt->execute([$customer_id]);
        $wallet = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$wallet) {
            // التحقق أولاً من وجود محفظة أخرى لنفس العميل
            $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM customer_wallets WHERE customer_id = ?");
            $check_stmt->execute([$customer_id]);
            $exists = $check_stmt->fetchColumn();
            
            if (!$exists) {
                $stmt = $pdo->prepare("
                    INSERT INTO customer_wallets (customer_id, balance, total_deposited, total_withdrawn) 
                    VALUES (?, 0, 0, 0)
                ");
                $stmt->execute([$customer_id]);
            }
            
            // إعادة جلب المحفظة بعد الإنشاء
            $stmt->execute([$customer_id]);
            $wallet = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        return $wallet ?: [
            'customer_id' => $customer_id,
            'balance' => 0,
            'total_deposited' => 0,
            'total_withdrawn' => 0
        ];
        
    } catch (PDOException $e) {
        error_log("Error getting customer wallet: " . $e->getMessage());
        return [
            'customer_id' => $customer_id,
            'balance' => 0,
            'total_deposited' => 0,
            'total_withdrawn' => 0
        ];
    }
}

/**
 * الحصول على معاملات المحفظة
 */
function getWalletTransactions($customer_id, $limit = null) {
    global $pdo;
    
    $sql = "
        SELECT * FROM wallet_transactions 
        WHERE customer_id = ? 
        ORDER BY transaction_date DESC
    ";
    
    if ($limit) {
        $sql .= " LIMIT " . intval($limit);
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$customer_id]);
    return $stmt->fetchAll();
}

/**
 * شحن المحفظة
 */
function depositToWallet($customer_id, $amount, $payment_method) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // تحديث رصيد المحفظة
        $stmt = $pdo->prepare("
            INSERT INTO customer_wallets (customer_id, balance, total_deposited) 
            VALUES (?, ?, ?) 
            ON DUPLICATE KEY UPDATE 
            balance = balance + VALUES(balance),
            total_deposited = total_deposited + VALUES(total_deposited)
        ");
        $stmt->execute([$customer_id, $amount, $amount]);
        
        // تسجيل المعاملة
        $stmt = $pdo->prepare("
            INSERT INTO wallet_transactions (customer_id, amount, type, description, reference_type, status) 
            VALUES (?, ?, 'deposit', ?, 'manual', 'pending')
        ");
        $description = "شحن محفظة عبر " . $payment_method;
        $stmt->execute([$customer_id, $amount, $description]);
        
        $transaction_id = $pdo->lastInsertId();
        
        $pdo->commit();
        return $transaction_id;
    } catch (PDOException $e) {
        $pdo->rollBack();
        return false;
    }
}
/**
 * استخراج معلومات المستلم من وصف السحب
 */
function extractReceiverInfo($description) {
    if (strpos($description, 'فودافون كاش') !== false) {
        return 'فودافون كاش';
    } elseif (strpos($description, 'بنك') !== false) {
        return 'تحويل بنكي';
    }
        if (empty($description)) return 'غير متوفر';
    
    // إذا كان الوصف يحتوي على معلومات منسقة
    $parts = explode(' - ', $description);
    return $parts[0] ?? $description;
    // استخراج الرقم من الوصف
    preg_match('/\d+/', $description, $matches);
    return $matches[0] ?? 'معلومات غير محددة';
}

/**
 * الحصول على معاملات محفظة العميل مع إمكانية التصفية
 */
function getCustomerWalletTransactions($customer_id, $filters = []) {
    global $pdo;
    
    $where_conditions = ["customer_id = ?"];
    $params = [$customer_id];
    
    if (isset($filters['type']) && $filters['type'] !== 'all') {
        $where_conditions[] = "type = ?";
        $params[] = $filters['type'];
    }
    
    if (isset($filters['status']) && $filters['status'] !== 'all') {
        $where_conditions[] = "status = ?";
        $params[] = $filters['status'];
    }
    
    if (!empty($filters['start_date'])) {
        $where_conditions[] = "DATE(transaction_date) >= ?";
        $params[] = $filters['start_date'];
    }
    
    if (!empty($filters['end_date'])) {
        $where_conditions[] = "DATE(transaction_date) <= ?";
        $params[] = $filters['end_date'];
    }
    
    $where_sql = implode(" AND ", $where_conditions);
    
    $stmt = $pdo->prepare("
        SELECT * FROM wallet_transactions 
        WHERE $where_sql 
        ORDER BY transaction_date DESC
    ");
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * الحصول على إحصائيات معاملات المحفظة
 */
function getWalletTransactionStats($customer_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_count,
            SUM(CASE WHEN type = 'deposit' THEN amount ELSE 0 END) as total_deposits,
            SUM(CASE WHEN type = 'withdrawal' THEN amount ELSE 0 END) as total_withdrawals,
            SUM(CASE WHEN type = 'refund' THEN amount ELSE 0 END) as total_refunds,
            SUM(CASE WHEN type = 'bonus' THEN amount ELSE 0 END) as total_bonuses,
            MAX(transaction_date) as last_transaction_date
        FROM wallet_transactions 
        WHERE customer_id = ?
    ");
    $stmt->execute([$customer_id]);
    return $stmt->fetch();
}
/**
 * الحصول على رصيد محفظة العميل
 */
 

/**
 * الحصول على اسم العميل من ID
 */
function getCustomerName1($customer_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT first_name, last_name FROM customers WHERE id = ?");
    $stmt->execute([$customer_id]);
    $customer = $stmt->fetch();
    
    if ($customer) {
        return $customer['first_name'] . ' ' . $customer['last_name'];
    }
    
    return 'مستخدم';
}

/**
 * التحقق إذا كان المنتج من متجر مستخدم
 */
function isCustomerStoreProduct($product_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT store_type FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
    
    return $product && $product['store_type'] === 'customer';
}

/**
 * الحصول على معلومات صاحب المتجر للمنتج
 */
function getProductStoreOwner($product_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT c.first_name, c.last_name, c.email 
        FROM products p 
        JOIN customers c ON p.created_by = c.id 
        WHERE p.id = ?
    ");
    $stmt->execute([$product_id]);
    return $stmt->fetch();
}


/**
 * الحصول على عدد البيانات المؤقتة
 */
function getTempDataCount() {
    global $pdo;
    $count = 0;
    $tables = ['temp_orders', 'temp_cart', 'failed_jobs', 'sessions', 'cache_data'];
    
    foreach ($tables as $table) {
        try {
            // التحقق من وجود الجدول أولاً
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() > 0) {
                $count_stmt = $pdo->query("SELECT COUNT(*) FROM $table");
                $count += $count_stmt->fetchColumn();
            }
        } catch (PDOException $e) {
            // تجاهل الجداول غير الموجودة
            continue;
        }
    }
    
    return $count;
}

/**
 * مسح البيانات المؤقتة (محدث)
 */
function resetTempData() {
    global $pdo;
    
    $tables = [
        'sessions', 
        'cache_data',
        'failed_jobs',
        'activity_logs' // يمكن إضافة المزيد حسب الحاجة
    ];
    
    foreach ($tables as $table) {
        try {
            // التحقق من وجود الجدول أولاً
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() > 0) {
                // استخدام DELETE بدلاً من TRUNCATE لتجنب الأخطاء
                $pdo->exec("DELETE FROM $table");
            }
        } catch (PDOException $e) {
            // تسجيل الخطأ والمتابعة
            error_log("Error resetting table $table: " . $e->getMessage());
            continue;
        }
    }
    
    // مسح الجلسات القديمة فقط (أقدم من يوم)
    try {
        $pdo->exec("DELETE FROM sessions WHERE last_activity < UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 1 DAY))");
    } catch (PDOException $e) {
        error_log("Error clearing old sessions: " . $e->getMessage());
    }
}

/**
 * مسح الكاش (محدث)
 */
function resetCache() {
    global $pdo;
    
    // مسح الجلسات القديمة
    try {
        $pdo->exec("DELETE FROM sessions WHERE last_activity < UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 1 DAY))");
    } catch (PDOException $e) {
        // تجاهل الخطأ إذا كان الجدول غير موجود
    }
    
    // مسح بيانات الكاش إذا كان الجدول موجوداً
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'cache_data'");
        if ($stmt->rowCount() > 0) {
            $pdo->exec("DELETE FROM cache_data WHERE expires_at < NOW()");
        }
    } catch (PDOException $e) {
        // تجاهل الخطأ
    }
    
    // مسح الملفات المؤقتة
    $temp_dirs = ['../cache/', '../temp/', '../logs/'];
    foreach ($temp_dirs as $dir) {
        if (is_dir($dir)) {
            $files = glob($dir . '*');
            foreach ($files as $file) {
                if (is_file($file) && basename($file) !== '.gitkeep') {
                    @unlink($file);
                }
            }
        }
    }
}

/**
 * إعادة ضبط الإحصائيات (محدث)
 */
function resetStatistics() {
    global $pdo;
    
    try {
        $pdo->exec("UPDATE products SET views = 0, orders_count = 0");
    } catch (PDOException $e) {
        error_log("Error resetting product statistics: " . $e->getMessage());
    }
    
    try {
        $pdo->exec("UPDATE categories SET products_count = 0");
    } catch (PDOException $e) {
        error_log("Error resetting category statistics: " . $e->getMessage());
    }
    
    // إعادة ضبط daily_sales_stats إذا كان الجدول موجوداً
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'daily_sales_stats'");
        if ($stmt->rowCount() > 0) {
            $pdo->exec("TRUNCATE TABLE daily_sales_stats");
        }
    } catch (PDOException $e) {
        // تجاهل الخطأ إذا كان الجدول غير موجود
    }
}

/**
 * مسح الجلسات (محدث)
 */
function resetSessions() {
    global $pdo;
    
    try {
        $pdo->exec("DELETE FROM sessions");
    } catch (PDOException $e) {
        error_log("Error clearing sessions: " . $e->getMessage());
    }
    
    // مسح جلسة PHP الحالية
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}

/**
 * استعلام مخصص (محدث)
 */
function resetCustom($query) {
    global $pdo;
    
    // التحقق من أن الاستعلام آمن
    $dangerousKeywords = [
        'DROP DATABASE', 'DROP TABLE', 'DELETE FROM', 'TRUNCATE',
        'ALTER TABLE', 'CREATE TABLE', 'RENAME TABLE', 'LOCK TABLES'
    ];
    $queryUpper = strtoupper($query);
    
    foreach ($dangerousKeywords as $keyword) {
        if (strpos($queryUpper, $keyword) !== false) {
            throw new Exception("الاستعلام يحتوي على عمليات خطيرة غير مسموح بها: $keyword");
        }
    }
    
    // التحقق من أن الاستعلام يبدأ بكلمة SELECT أو UPDATE أو INSERT المسموحة
    $allowedStarts = ['SELECT', 'UPDATE', 'INSERT', 'DELETE FROM activity_logs', 'DELETE FROM sessions'];
    $isAllowed = false;
    
    foreach ($allowedStarts as $start) {
        if (strpos($queryUpper, $start) === 0) {
            $isAllowed = true;
            break;
        }
    }
    
    if (!$isAllowed) {
        throw new Exception("نوع الاستعلام غير مسموح به. المسموح: SELECT, UPDATE, INSERT فقط");
    }
    
    // تنفيذ الاستعلام
    try {
        if (strpos($queryUpper, 'SELECT') === 0) {
            $stmt = $pdo->query($query);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $result = $pdo->exec($query);
            return $result;
        }
    } catch (PDOException $e) {
        throw new Exception("خطأ في تنفيذ الاستعلام: " . $e->getMessage());
    }
}

/**
 * مسح كامل لقاعدة البيانات (خطر - لا يمكن التراجع)
 */
function resetFullDatabase1() {
    global $pdo;
    
    // قائمة الجداول التي سيتم مسحها (باستثناء الجداول النظامية)
    $tables = [
        'products',
        'categories', 
        'customers',
        'orders',
        'order_items',
        'order_status_history',
        'coupons',
        'reviews',
        'wishlists',
        'customer_points',
        'point_transactions',
        'customer_wallets',
        'wallet_transactions',
        'product_negotiations',
        'product_bids',
        'price_countdowns',
        'scratch_cards',
        'referrals',
        'referral_links',
        'packages',
        'package_orders',
        'product_offers',
        'offer_conditions',
        'newsletter_subscribers',
        'customer_addresses',
        'product_images',
        'activity_logs',
        'shipping_rates',
        'delivery_agents',
        'agent_orders',
        'agent_salaries',
        'partners',
        'wholesalers',
        'wholesaler_products'
    ];
    
    // تعطيل المفاتيح الخارجية مؤقتاً
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    foreach ($tables as $table) {
        try {
            // التحقق من وجود الجدول أولاً
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() > 0) {
                // TRUNCATE أسرع من DELETE
                $pdo->exec("TRUNCATE TABLE $table");
            }
        } catch (PDOException $e) {
            // إذا فشل TRUNCATE، استخدم DELETE
            try {
                $pdo->exec("DELETE FROM $table");
            } catch (PDOException $e2) {
                error_log("Failed to reset table $table: " . $e2->getMessage());
            }
        }
    }
    
    // إعادة تمكين المفاتيح الخارجية
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    // إعادة تعيين السلسلة التلقائية للجداول الرئيسية
    $auto_increment_tables = [
        'products' => 1,
        'categories' => 1,
        'customers' => 1,
        'orders' => 1,
        'admins' => 2 // الحفاظ على المشرف الرئيسي
    ];
    
    foreach ($auto_increment_tables as $table => $value) {
        try {
            $pdo->exec("ALTER TABLE $table AUTO_INCREMENT = $value");
        } catch (PDOException $e) {
            error_log("Failed to reset auto_increment for $table: " . $e->getMessage());
        }
    }
}

/**
 * إنشاء بيانات افتراضية بعد المسح الكامل
 */
function createDefaultData1() {
    global $pdo;
    
    // إضافة فئات افتراضية
    $default_categories = [
        ['إلكترونيات', 'electronics', 'أحدث الأجهزة الإلكترونية'],
        ['ملابس', 'clothing', 'أزياء عصرية للرجال والنساء'],
        ['منزل ومطبخ', 'home-kitchen', 'مستلزمات المنزل والمطبخ']
    ];
    
    foreach ($default_categories as $category) {
        $stmt = $pdo->prepare("INSERT INTO categories (name, slug, description, display_order, is_active) VALUES (?, ?, ?, ?, 1)");
        $stmt->execute($category);
    }
    
    // إضافة إعدادات افتراضية
    $default_settings = [
        ['store_name', 'متجرنا', 'text'],
        ['store_email', 'admin@store.com', 'text'],
        ['currency', 'EGP', 'text'],
        ['currency_symbol', 'ج.م', 'text']
    ];
    
    foreach ($default_settings as $setting) {
        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value, setting_type) VALUES (?, ?, ?)");
        $stmt->execute($setting);
    }
    
    // إضافة كوبون ترحيبي
    $stmt = $pdo->prepare("INSERT INTO coupons (code, description, discount_type, discount_value, min_order_amount, usage_limit, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)");
    $stmt->execute(['WELCOME10', 'خصم ترحيبي 10%', 'percentage', 10.00, 100.00, 100]);
}
 ?> 
<?php
// في ملف functions.php - أضف هذه الدوال

// 1. مسح بيانات جدول معين
function resetTable($tableName) {
    global $pdo;
    try {
        // التحقق من وجود الجدول
        $stmt = $pdo->query("SHOW TABLES LIKE '$tableName'");
        if ($stmt->rowCount() == 0) {
            return ['success' => false, 'message' => "الجدول $tableName غير موجود"];
        }
        
        // حذف البيانات
        $pdo->exec("DELETE FROM $tableName");
        
        // إعادة تعيين AUTO_INCREMENT
        $pdo->exec("ALTER TABLE $tableName AUTO_INCREMENT = 1");
        
        return ['success' => true, 'message' => "تم حذف بيانات جدول $tableName بنجاح"];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => "خطأ: " . $e->getMessage()];
    }
}

// 2. مسح بيانات الطلبات التجريبية
function resetTestOrders() {
    global $pdo;
    
    // التحقق من وجود transaction نشط
    $start_transaction = !$pdo->inTransaction();
    
    try {
        if ($start_transaction) {
            $pdo->beginTransaction();
        }
        
        // حذف عناصر الطلبات
        $pdo->exec("DELETE FROM order_items WHERE order_id IN (SELECT id FROM orders)");
        
        // حذف تاريخ حالات الطلبات
        $pdo->exec("DELETE FROM order_status_history");
        
        // حذف الطلبات
        $pdo->exec("DELETE FROM orders");
        $pdo->exec("ALTER TABLE orders AUTO_INCREMENT = 1");
        
        if ($start_transaction) {
            $pdo->commit();
        }
        
        return ['success' => true, 'message' => 'تم حذف جميع الطلبات بنجاح'];
    } catch (Exception $e) {
        if ($start_transaction && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

// 3. مسح بيانات العملاء
// تحديث دالة resetCustomers
function resetCustomers() {
    global $pdo;
    
    try {
        // حذف العناوين
        $pdo->exec("DELETE FROM customer_addresses");
        
        // حذف النقاط
        $pdo->exec("DELETE FROM customer_points");
        $pdo->exec("DELETE FROM point_transactions");
        
        // حذف المحافظ والمعاملات
        $pdo->exec("DELETE FROM wallet_transactions");
        $pdo->exec("DELETE FROM customer_wallets");
        
        // حذف قوائم الأمنيات
        $pdo->exec("DELETE FROM wishlists");
        
        // حذف التقييمات
        $pdo->exec("DELETE FROM reviews");
        
        // حذف المفاوضات
        $pdo->exec("DELETE FROM product_negotiations");
        
        // حذف المزايدات
        $pdo->exec("DELETE FROM product_bids");
        
        // حذف بطاقات الخدش
        $pdo->exec("DELETE FROM scratch_cards");
        
        // حذف الإحالات
        $pdo->exec("DELETE FROM referrals");
        $pdo->exec("DELETE FROM referral_links");
        
        // حذف طلبات الباقات
        $pdo->exec("DELETE FROM package_orders");
        
        // حذف العملاء
        $pdo->exec("DELETE FROM customers");
        $pdo->exec("ALTER TABLE customers AUTO_INCREMENT = 1");
        
        return ['success' => true, 'message' => 'تم حذف جميع بيانات العملاء بنجاح'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

// تحديث دالة resetFullDatabase
function resetFullDatabase() {
    global $pdo;
    
    try {
        // تعطيل فحص Foreign Keys مؤقتاً
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
        
        // حذف جميع الجداول بالترتيب الصحيح
        $tables = [
            'order_items',
            'order_status_history',
            'agent_orders',
            'agent_salaries',
            'product_images',
            'product_offers',
            'offer_conditions',
            'price_countdowns',
            'product_negotiations',
            'product_bids',
            'reviews',
            'scratch_cards',
            'wholesaler_products',
            'customer_addresses',
            'wishlists',
            'referrals',
            'referral_links',
            'package_orders',
            'point_transactions',
            'customer_points',
            'wallet_transactions',
            'customer_wallets',
            'orders',
            'products',
            'categories',
            'coupons',
            'customers',
            'wholesalers',
            'partners',
            'delivery_agents',
            'packages',
            'newsletter_subscribers',
            'activity_logs'
        ];
        
        foreach ($tables as $table) {
            try {
                $pdo->exec("DELETE FROM $table");
                $pdo->exec("ALTER TABLE $table AUTO_INCREMENT = 1");
            } catch (Exception $e) {
                // تجاهل الأخطاء إذا كان الجدول غير موجود
                continue;
            }
        }
        
        // إعادة تفعيل فحص Foreign Keys
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
        
        return ['success' => true, 'message' => 'تم مسح كامل قاعدة البيانات بنجاح'];
    } catch (Exception $e) {
        // إعادة تفعيل Foreign Keys في حالة الخطأ
        try {
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
        } catch (Exception $ex) {}
        
        return ['success' => false, 'message' => $e->getMessage()];
    }
}
// 4. مسح بيانات المنتجات
function resetProducts() {
    global $pdo;
    
    $start_transaction = !$pdo->inTransaction();
    
    try {
        if ($start_transaction) {
            $pdo->beginTransaction();
        }
        
        // حذف صور المنتجات
        $pdo->exec("DELETE FROM product_images");
        
        // حذف العروض
        $pdo->exec("DELETE FROM product_offers");
        $pdo->exec("DELETE FROM offer_conditions");
        
        // حذف العد التنازلي للأسعار
        $pdo->exec("DELETE FROM price_countdowns");
        
        // حذف المنتجات
        $pdo->exec("DELETE FROM products");
        $pdo->exec("ALTER TABLE products AUTO_INCREMENT = 1");
        
        if ($start_transaction) {
            $pdo->commit();
        }
        
        return ['success' => true, 'message' => 'تم حذف جميع المنتجات بنجاح'];
    } catch (Exception $e) {
        if ($start_transaction && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

// 5. مسح بيانات الفئات
function resetCategories() {
    global $pdo;
    try {
        $pdo->exec("DELETE FROM categories");
        $pdo->exec("ALTER TABLE categories AUTO_INCREMENT = 1");
        return ['success' => true, 'message' => 'تم حذف جميع الفئات بنجاح'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

// 6. مسح الكوبونات
function resetCoupons() {
    global $pdo;
    try {
        $pdo->exec("DELETE FROM coupons");
        $pdo->exec("ALTER TABLE coupons AUTO_INCREMENT = 1");
        return ['success' => true, 'message' => 'تم حذف جميع الكوبونات بنجاح'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

// 7. مسح التقييمات فقط
function resetReviews() {
    global $pdo;
    try {
        $pdo->exec("DELETE FROM reviews");
        $pdo->exec("ALTER TABLE reviews AUTO_INCREMENT = 1");
        
        // إعادة تعيين متوسط التقييمات للمنتجات
        $pdo->exec("UPDATE products SET rating_avg = 0, rating_count = 0");
        
        return ['success' => true, 'message' => 'تم حذف جميع التقييمات بنجاح'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

// 8. مسح سجلات النشاط
function resetActivityLogs() {
    global $pdo;
    try {
        $pdo->exec("DELETE FROM activity_logs");
        $pdo->exec("ALTER TABLE activity_logs AUTO_INCREMENT = 1");
        return ['success' => true, 'message' => 'تم حذف سجلات النشاط بنجاح'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

// 9. مسح بيانات الشركاء والموزعين
function resetPartnersWholesalers() {
    global $pdo;
    
    $start_transaction = !$pdo->inTransaction();
    
    try {
        if ($start_transaction) {
            $pdo->beginTransaction();
        }
        
        $pdo->exec("DELETE FROM wholesaler_products");
        $pdo->exec("DELETE FROM wholesalers");
        $pdo->exec("DELETE FROM partners");
        
        $pdo->exec("ALTER TABLE wholesalers AUTO_INCREMENT = 1");
        $pdo->exec("ALTER TABLE partners AUTO_INCREMENT = 1");
        
        if ($start_transaction) {
            $pdo->commit();
        }
        
        return ['success' => true, 'message' => 'تم حذف بيانات الشركاء والموزعين بنجاح'];
    } catch (Exception $e) {
        if ($start_transaction && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

// 10. مسح بيانات المندوبين
function resetDeliveryAgents() {
    global $pdo;
    
    $start_transaction = !$pdo->inTransaction();
    
    try {
        if ($start_transaction) {
            $pdo->beginTransaction();
        }
        
        $pdo->exec("DELETE FROM agent_orders");
        $pdo->exec("DELETE FROM agent_salaries");
        $pdo->exec("DELETE FROM delivery_agents");
        
        $pdo->exec("ALTER TABLE delivery_agents AUTO_INCREMENT = 1");
        
        if ($start_transaction) {
            $pdo->commit();
        }
        
        return ['success' => true, 'message' => 'تم حذف بيانات المندوبين بنجاح'];
    } catch (Exception $e) {
        if ($start_transaction && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

// دوال مسح المحافظ والمعاملات

/**
 * مسح محافظ العملاء
 */
// دوال مسح المحافظ والمعاملات - الإصدار المصحح

/**
 * مسح محافظ العملاء
 */
function resetCustomerWallets() {
    global $pdo;
    
    try {
        // حذف معاملات المحافظ أولاً
        $pdo->exec("DELETE FROM wallet_transactions");
        
        // حذف محافظ العملاء
        $pdo->exec("DELETE FROM customer_wallets");
        
        // إعادة تعيين AUTO_INCREMENT
        $pdo->exec("ALTER TABLE wallet_transactions AUTO_INCREMENT = 1");
        $pdo->exec("ALTER TABLE customer_wallets AUTO_INCREMENT = 1");
        
        return ['success' => true, 'message' => 'تم حذف جميع محافظ العملاء والمعاملات بنجاح'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * مسح معاملات المحافظ فقط
 */
function resetWalletTransactions() {
    global $pdo;
    
    try {
        $pdo->exec("DELETE FROM wallet_transactions");
        $pdo->exec("ALTER TABLE wallet_transactions AUTO_INCREMENT = 1");
        
        // إعادة تعيين أرصدة المحافظ
        $pdo->exec("UPDATE customer_wallets SET balance = 0, total_deposited = 0, total_withdrawn = 0");
        
        return ['success' => true, 'message' => 'تم حذف جميع معاملات المحافظ وإعادة تعيين الأرصدة بنجاح'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}
 
// 11. مسح كامل قاعدة البيانات (ما عدا المسؤولين والإعدادات)
function resetFullDatabase2() {
    global $pdo;
    
    $start_transaction = !$pdo->inTransaction();
    
    try {
        if ($start_transaction) {
            $pdo->beginTransaction();
        }
        
        // تعطيل فحص Foreign Keys مؤقتاً
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
        
        // حذف جميع الجداول بالترتيب الصحيح
        $tables = [
            'order_items',
            'order_status_history',
            'agent_orders',
            'agent_salaries',
            'product_images',
            'product_offers',
            'offer_conditions',
            'price_countdowns',
            'product_negotiations',
            'product_bids',
            'reviews',
            'scratch_cards',
            'wholesaler_products',
            'customer_addresses',
            'wishlists',
            'referrals',
            'referral_links',
            'package_orders',
            'point_transactions',
            'customer_points',
    'wallet_transactions', // أضف هذا
    'customer_wallets', // أضف هذا
            'orders',
            'products',
            'categories',
            'coupons',
            'customers',
            'wholesalers',
            'partners',
            'delivery_agents',
            'packages',
            'newsletter_subscribers',
            'activity_logs'
        ];
        
        foreach ($tables as $table) {
            try {
                $pdo->exec("DELETE FROM $table");
                $pdo->exec("ALTER TABLE $table AUTO_INCREMENT = 1");
            } catch (Exception $e) {
                // تجاهل الأخطاء إذا كان الجدول غير موجود
                continue;
            }
        }
        
        // إعادة تفعيل فحص Foreign Keys
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
        
        if ($start_transaction) {
            $pdo->commit();
        }
        
        return ['success' => true, 'message' => 'تم مسح كامل قاعدة البيانات بنجاح'];
    } catch (Exception $e) {
        // إعادة تفعيل Foreign Keys في حالة الخطأ
        try {
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
        } catch (Exception $ex) {}
        
        if ($start_transaction && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

// 12. إنشاء بيانات افتراضية بعد المسح
function createDefaultData() {
    global $pdo;
    try {
        // إنشاء فئات افتراضية
        $categories = [
            ['name' => 'إلكترونيات', 'slug' => 'electronics'],
            ['name' => 'ملابس', 'slug' => 'clothing'],
            ['name' => 'منزل ومطبخ', 'slug' => 'home-kitchen'],
        ];
        
        foreach ($categories as $cat) {
            $stmt = $pdo->prepare("INSERT INTO categories (name, slug) VALUES (?, ?)");
            $stmt->execute([$cat['name'], $cat['slug']]);
        }
        
        // إنشاء كوبون افتراضي
        $stmt = $pdo->prepare("INSERT INTO coupons (code, discount_type, discount_value, is_active) VALUES (?, ?, ?, ?)");
        $stmt->execute(['WELCOME10', 'percentage', 10, 1]);
        
        return ['success' => true, 'message' => 'تم إنشاء البيانات الافتراضية'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

// 13. الحصول على إحصائيات الجداول
function getTablesStats() {
    global $pdo;
    
    $tables = [
        'products' => 'المنتجات',
        'categories' => 'الفئات',
        'orders' => 'الطلبات',
        'customers' => 'العملاء',
        'coupons' => 'الكوبونات',
        'reviews' => 'التقييمات',
        'wishlists' => 'قوائم الأمنيات',
        'customer_wallets' => 'محافظ العملاء',
        'wallet_transactions' => 'معاملات المحافظ',
        'delivery_agents' => 'المندوبين',
        'partners' => 'الشركاء',
        'wholesalers' => 'الموزعين',
        'activity_logs' => 'سجلات النشاط'
    ];
    
    $stats = [];
    foreach ($tables as $table => $label) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
            $count = $stmt->fetchColumn();
            $stats[$table] = [
                'label' => $label,
                'count' => $count
            ];
        } catch (Exception $e) {
            $stats[$table] = [
                'label' => $label,
                'count' => 0
            ];
        }
    }
    
    return $stats;
}

// دوال المساعدة الموجودة أصلاً
function resetCache1() {
    // مسح ملفات الكاش إذا كانت موجودة
    $cache_dir = '../cache/';
    if (is_dir($cache_dir)) {
        $files = glob($cache_dir . '*');
        foreach($files as $file) {
            if(is_file($file)) {
                unlink($file);
            }
        }
    }
    return true;
}

function resetStatistics1() {
    global $pdo;
    // إعادة تعيين الإحصائيات
    $pdo->exec("UPDATE products SET views = 0, orders_count = 0");
    return true;
}

function resetSessions1() {
    // مسح جميع الجلسات
    $session_path = session_save_path();
    if ($session_path && is_dir($session_path)) {
        $files = glob($session_path . '/sess_*');
        foreach($files as $file) {
            if(is_file($file)) {
                unlink($file);
            }
        }
    }
    return true;
}

function resetTempData1() {
    global $pdo;
    // حذف البيانات المؤقتة
    $pdo->exec("DELETE FROM newsletter_subscribers WHERE is_active = 0");
    return true;
}

function getTempDataCount1() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM newsletter_subscribers WHERE is_active = 0");
        return $stmt->fetchColumn();
    } catch (Exception $e) {
        return 0;
    }
}

function resetCustom1($query) {
    global $pdo;
    
    // التحقق من الاستعلام لمنع العمليات الخطيرة
    $dangerous = ['DROP', 'TRUNCATE', 'ALTER', 'CREATE'];
    $query_upper = strtoupper($query);
    
    foreach ($dangerous as $keyword) {
        if (strpos($query_upper, $keyword) !== false) {
            throw new Exception("غير مسموح باستخدام $keyword في الاستعلامات المخصصة");
        }
    }
    
    // تنفيذ الاستعلام
    if (stripos($query, 'SELECT') === 0) {
        $stmt = $pdo->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        return $pdo->exec($query);
    }
}


/**
 * إنشاء بيانات افتراضية شاملة للنظام (مصحح)
 */
function createComprehensiveDefaultData() {
    global $pdo;
    
    try {
        // التحقق من وجود بيانات أولاً لتجنب التكرار
        if (hasExistingData()) {
            return [
                'success' => false,
                'message' => 'يوجد بيانات حالية في النظام. يرجى مسح البيانات أولاً أو استخدام بيانات جديدة.'
            ];
        }
        
        // 1. إنشاء فئات افتراضية (مع التحقق من التكرار)
        $categories = [
            ['name' => 'إلكترونيات', 'slug' => 'electronics', 'description' => 'أحدث الأجهزة الإلكترونية', 'display_order' => 1],
            ['name' => 'هواتف وأجهزة لوحية', 'slug' => 'phones-tablets', 'description' => 'هواتف ذكية وأجهزة لوحية', 'display_order' => 2],
            ['name' => 'أجهزة الكمبيوتر', 'slug' => 'computers', 'description' => 'لابتوبات وأجهزة كمبيوتر', 'display_order' => 3],
            ['name' => 'ملابس', 'slug' => 'clothing', 'description' => 'أزياء عصرية للرجال والنساء', 'display_order' => 4],
            ['name' => 'منزل ومطبخ', 'slug' => 'home-kitchen', 'description' => 'مستلزمات المنزل والمطبخ', 'display_order' => 5],
            ['name' => 'رياضة', 'slug' => 'sports', 'description' => 'معدات ومستلزمات رياضية', 'display_order' => 6],
            ['name' => 'جمال وعناية', 'slug' => 'beauty', 'description' => 'منتجات التجميل والعناية الشخصية', 'display_order' => 7],
            ['name' => 'كتب وقرطاسية', 'slug' => 'books-stationery', 'description' => 'كتب وأدوات قرطاسية', 'display_order' => 8]
        ];
        
        $created_categories = 0;
        foreach ($categories as $cat) {
            // التحقق من عدم وجود الفئة مسبقاً
            $check_stmt = $pdo->prepare("SELECT id FROM categories WHERE slug = ?");
            $check_stmt->execute([$cat['slug']]);
            
            if (!$check_stmt->fetch()) {
                $stmt = $pdo->prepare("INSERT INTO categories (name, slug, description, display_order, is_active) VALUES (?, ?, ?, ?, 1)");
                $stmt->execute([$cat['name'], $cat['slug'], $cat['description'], $cat['display_order']]);
                $created_categories++;
            }
        }
        
        // إذا لم يتم إنشاء أي فئات جديدة، نستخدم الفئات الموجودة
        if ($created_categories === 0) {
            // جلب الفئات الموجودة لاستخدامها في المنتجات
            $stmt = $pdo->query("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY id LIMIT 5");
            $existing_categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            // جلب الفئات الجديدة التي تم إنشاؤها
            $stmt = $pdo->query("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY id DESC LIMIT 8");
            $existing_categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        // 2. إنشاء منتجات افتراضية
        $products = [
            // إلكترونيات
            [
                'title' => 'هاتف سامسونج جالاكسي S23',
                'slug' => 'samsung-galaxy-s23-' . time(),
                'description' => 'هاتف ذكي بشاشة 6.1 بوصة، كاميرا 50 ميجابكسل، معالج سناب دراجون',
                'price' => 25000.00,
                'discount_percentage' => 10.00,
                'stock' => 50,
                'category_id' => $existing_categories[0]['id'] ?? 1,
                'sku' => 'PHN-S23-' . uniqid()
            ],
            [
                'title' => 'لابتوب ديل XPS 13',
                'slug' => 'dell-xps-13-' . time(),
                'description' => 'لابتوب بشاشة 13.4 بوصة، معالج انتل كور i7، ذاكرة 16 جيجابايت',
                'price' => 35000.00,
                'discount_percentage' => 5.00,
                'stock' => 25,
                'category_id' => $existing_categories[2]['id'] ?? 3,
                'sku' => 'LAP-DELL-' . uniqid()
            ],
            [
                'title' => 'سماعات ايربودز برو',
                'slug' => 'airpods-pro-' . time(),
                'description' => 'سماعات لاسلكية مع إلغاء الضوضاء النشط',
                'price' => 6000.00,
                'discount_percentage' => 15.00,
                'stock' => 100,
                'category_id' => $existing_categories[0]['id'] ?? 1,
                'sku' => 'AUD-AP-' . uniqid()
            ],
            // ملابس
            [
                'title' => 'تيشيرت قطني رجالي',
                'slug' => 'cotton-t-shirt-' . time(),
                'description' => 'تيشيرت قطني عالي الجودة بمقاسات مختلفة',
                'price' => 150.00,
                'discount_percentage' => 20.00,
                'stock' => 200,
                'category_id' => $existing_categories[3]['id'] ?? 4,
                'sku' => 'CLO-TSH-' . uniqid()
            ],
            [
                'title' => 'جينز رجالي',
                'slug' => 'mens-jeans-' . time(),
                'description' => 'جينز رجالي بمقاسات وألوان مختلفة',
                'price' => 400.00,
                'discount_percentage' => 10.00,
                'stock' => 150,
                'category_id' => $existing_categories[3]['id'] ?? 4,
                'sku' => 'CLO-JNS-' . uniqid()
            ],
            // منزل ومطبخ
            [
                'title' => 'طقم قدور ستانلس ستيل',
                'slug' => 'stainless-steel-pots-' . time(),
                'description' => 'طقم قدور ستانلس ستيل 7 قطع عالي الجودة',
                'price' => 1200.00,
                'discount_percentage' => 25.00,
                'stock' => 80,
                'category_id' => $existing_categories[4]['id'] ?? 5,
                'sku' => 'HOM-POT-' . uniqid()
            ],
            [
                'title' => 'ماكينة صنع القهوة',
                'slug' => 'coffee-maker-' . time(),
                'description' => 'ماكينة صنع القهوة الأوتوماتيكية',
                'price' => 800.00,
                'discount_percentage' => 0.00,
                'stock' => 60,
                'category_id' => $existing_categories[4]['id'] ?? 5,
                'sku' => 'HOM-CFM-' . uniqid()
            ],
            // رياضة
            [
                'title' => 'حذاء رياضي',
                'slug' => 'sports-shoes-' . time(),
                'description' => 'حذاء رياضي مريح للجري والتمارين',
                'price' => 600.00,
                'discount_percentage' => 30.00,
                'stock' => 120,
                'category_id' => $existing_categories[5]['id'] ?? 6,
                'sku' => 'SPT-SHO-' . uniqid()
            ],
            [
                'title' => 'كرة قدم',
                'slug' => 'football-' . time(),
                'description' => 'كرة قدم رسمية مقاس 5',
                'price' => 200.00,
                'discount_percentage' => 15.00,
                'stock' => 90,
                'category_id' => $existing_categories[5]['id'] ?? 6,
                'sku' => 'SPT-BAL-' . uniqid()
            ],
            // جمال وعناية
            [
                'title' => 'عطر رجالي',
                'slug' => 'mens-perfume-' . time(),
                'description' => 'عطر رجالي برائحة مميزة ودائمة',
                'price' => 350.00,
                'discount_percentage' => 10.00,
                'stock' => 70,
                'category_id' => $existing_categories[6]['id'] ?? 7,
                'sku' => 'BEA-PRF-' . uniqid()
            ]
        ];
        
        $created_products = 0;
        foreach ($products as $product) {
            // التحقق من عدم وجود المنتج مسبقاً
            $check_stmt = $pdo->prepare("SELECT id FROM products WHERE slug = ?");
            $check_stmt->execute([$product['slug']]);
            
            if (!$check_stmt->fetch()) {
                $stmt = $pdo->prepare("INSERT INTO products (category_id, title, slug, description, price, discount_percentage, stock, sku, is_active, is_featured) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, 1)");
                $stmt->execute([
                    $product['category_id'],
                    $product['title'],
                    $product['slug'],
                    $product['description'],
                    $product['price'],
                    $product['discount_percentage'],
                    $product['stock'],
                    $product['sku']
                ]);
                $created_products++;
            }
        }
        
        // 3. إنشاء عملاء افتراضيين
        $customers = [
            ['email' => 'customer1-' . time() . '@example.com', 'phone' => '0101000' . rand(1000, 9999), 'first_name' => 'أحمد', 'last_name' => 'محمد'],
            ['email' => 'customer2-' . time() . '@example.com', 'phone' => '0102000' . rand(1000, 9999), 'first_name' => 'مريم', 'last_name' => 'علي'],
            ['email' => 'customer3-' . time() . '@example.com', 'phone' => '0103000' . rand(1000, 9999), 'first_name' => 'خالد', 'last_name' => 'عبدالله'],
            ['email' => 'customer4-' . time() . '@example.com', 'phone' => '0104000' . rand(1000, 9999), 'first_name' => 'فاطمة', 'last_name' => 'حسن']
        ];
        
        $created_customers = 0;
        foreach ($customers as $customer) {
            // التحقق من عدم وجود العميل مسبقاً
            $check_stmt = $pdo->prepare("SELECT id FROM customers WHERE email = ? OR phone = ?");
            $check_stmt->execute([$customer['email'], $customer['phone']]);
            
            if (!$check_stmt->fetch()) {
                $stmt = $pdo->prepare("INSERT INTO customers (email, phone, first_name, last_name, is_verified) VALUES (?, ?, ?, ?, 1)");
                $stmt->execute([
                    $customer['email'],
                    $customer['phone'],
                    $customer['first_name'],
                    $customer['last_name']
                ]);
                $created_customers++;
            }
        }
        
        // 4. إنشاء كوبونات افتراضية
        $coupons = [
            ['code' => 'WELCOME10-' . rand(100, 999), 'description' => 'خصم ترحيبي 10%', 'discount_type' => 'percentage', 'discount_value' => 10.00, 'min_order_amount' => 100.00],
            ['code' => 'SAVE20-' . rand(100, 999), 'description' => 'خصم 20% على كل المنتجات', 'discount_type' => 'percentage', 'discount_value' => 20.00, 'min_order_amount' => 200.00],
            ['code' => 'FREESHIP-' . rand(100, 999), 'description' => 'شحن مجاني', 'discount_type' => 'fixed', 'discount_value' => 50.00, 'min_order_amount' => 300.00]
        ];
        
        $created_coupons = 0;
        foreach ($coupons as $coupon) {
            // التحقق من عدم وجود الكوبون مسبقاً
            $check_stmt = $pdo->prepare("SELECT id FROM coupons WHERE code = ?");
            $check_stmt->execute([$coupon['code']]);
            
            if (!$check_stmt->fetch()) {
                $stmt = $pdo->prepare("INSERT INTO coupons (code, description, discount_type, discount_value, min_order_amount, usage_limit, is_active) VALUES (?, ?, ?, ?, ?, 100, 1)");
                $stmt->execute([
                    $coupon['code'],
                    $coupon['description'],
                    $coupon['discount_type'],
                    $coupon['discount_value'],
                    $coupon['min_order_amount']
                ]);
                $created_coupons++;
            }
        }
        
        // 5. إنشاء مندوبي توصيل
        $agents = [
            ['name' => 'محمد أحمد', 'phone' => '0111000' . rand(1000, 9999), 'vehicle_type' => 'motorcycle', 'area' => 'القاهرة'],
            ['name' => 'أحمد سعيد', 'phone' => '0112000' . rand(1000, 9999), 'vehicle_type' => 'car', 'area' => 'الجيزة'],
            ['name' => 'محمود علي', 'phone' => '0113000' . rand(1000, 9999), 'vehicle_type' => 'motorcycle', 'area' => 'الإسكندرية']
        ];
        
        $created_agents = 0;
        foreach ($agents as $agent) {
            // التحقق من عدم وجود المندوب مسبقاً
            $check_stmt = $pdo->prepare("SELECT id FROM delivery_agents WHERE phone = ?");
            $check_stmt->execute([$agent['phone']]);
            
            if (!$check_stmt->fetch()) {
                $stmt = $pdo->prepare("INSERT INTO delivery_agents (name, phone, vehicle_type, area, is_active) VALUES (?, ?, ?, ?, 1)");
                $stmt->execute([
                    $agent['name'],
                    $agent['phone'],
                    $agent['vehicle_type'],
                    $agent['area']
                ]);
                $created_agents++;
            }
        }
        
        // 6. إنشاء شركاء وموزعين
        $partners = [
            ['name' => 'شركة التقنية المتطورة', 'phone' => '0221000' . rand(1000, 9999), 'email' => 'tech' . time() . '@example.com', 'partnership_type' => 'supplier'],
            ['name' => 'مؤسسة الأزياء الحديثة', 'phone' => '0222000' . rand(1000, 9999), 'email' => 'fashion' . time() . '@example.com', 'partnership_type' => 'supplier'],
            ['name' => 'شركة المستلزمات المنزلية', 'phone' => '0223000' . rand(1000, 9999), 'email' => 'home' . time() . '@example.com', 'partnership_type' => 'distributor']
        ];
        
        $created_partners = 0;
        foreach ($partners as $partner) {
            // التحقق من عدم وجود الشريك مسبقاً
            $check_stmt = $pdo->prepare("SELECT id FROM partners WHERE phone = ? OR email = ?");
            $check_stmt->execute([$partner['phone'], $partner['email']]);
            
            if (!$check_stmt->fetch()) {
                $stmt = $pdo->prepare("INSERT INTO partners (name, phone, email, partnership_type, status) VALUES (?, ?, ?, ?, 'active')");
                $stmt->execute([
                    $partner['name'],
                    $partner['phone'],
                    $partner['email'],
                    $partner['partnership_type']
                ]);
                $created_partners++;
            }
        }
        
        return [
            'success' => true,
            'message' => 'تم إنشاء البيانات الافتراضية بنجاح!',
            'stats' => [
                'categories' => $created_categories,
                'products' => $created_products,
                'customers' => $created_customers,
                'coupons' => $created_coupons,
                'agents' => $created_agents,
                'partners' => $created_partners
            ]
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'فشل في إنشاء البيانات الافتراضية: ' . $e->getMessage()
        ];
    }
}

/**
 * التحقق من وجود بيانات في النظام (محدث)
 */
function hasExistingData() {
    global $pdo;
    
    $tables = ['products', 'customers', 'categories', 'orders'];
    
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
            $count = $stmt->fetchColumn();
            if ($count > 0) {
                return true;
            }
        } catch (Exception $e) {
            continue;
        }
    }
    
    return false;
}

// إضافة هذه الدوال في ملف functions.php

 

/**
 * الحصول على رصيد محفظة العميل
 */
function getCustomerWalletBalance($customer_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT balance FROM customer_wallets WHERE customer_id = ?");
    $stmt->execute([$customer_id]);
    $result = $stmt->fetch();
    
    return $result ? $result['balance'] : 0;
}
  /**
 * جلب بيانات المحفظة الكاملة للعميل
 */
function getCustomerWalletDetails($customer_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT balance, total_deposited, total_withdrawn 
        FROM customer_wallets 
        WHERE customer_id = ?
    ");
    $stmt->execute([$customer_id]);
    $wallet = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $wallet ?: [
        'balance' => 0.00,
        'total_deposited' => 0.00,
        'total_withdrawn' => 0.00
    ];
}
function updateWalletBalance($customer_id, $amount, $type = 'deposit') {
    global $pdo;
    
    $column = $type === 'deposit' ? 'total_deposited' : 'total_withdrawn';
    $operator = $type === 'deposit' ? '+' : '-';
    
    $stmt = $pdo->prepare("
        UPDATE customer_wallets 
        SET balance = balance $operator ?, 
            $column = $column $operator ?,
            updated_at = NOW()
        WHERE customer_id = ?
    ");
    
    return $stmt->execute([$amount, $amount, $customer_id]);
}
 
// دالة جديدة لحساب عدد منتجات متجر المستخدم
function getCustomerStoreProductsCount($customerId) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM products 
        WHERE created_by = ? 
        AND is_active = 1 
        AND store_type = 'customer'
    ");
    $stmt->execute([$customerId]);
    $result = $stmt->fetch();
    
    return $result['count'] ?? 0;
}

// دالة جديدة لجلب اسم المستخدم
function getCustomerName($customerId) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT CONCAT(first_name, ' ', last_name) as full_name 
        FROM customers 
        WHERE id = ?
    ");
    $stmt->execute([$customerId]);
    $result = $stmt->fetch();
    
    return $result['full_name'] ?? 'مستخدم';
}




// التحقق من وجود متجر للمستخدم
function hasCustomerStore($customerId) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as store_count 
        FROM products 
        WHERE created_by = ? AND store_type = 'customer'
    ");
    $stmt->execute([$customerId]);
    $result = $stmt->fetch();
    
    return ($result['store_count'] > 0);
}

 

// جلب منتجات متجر مستخدم معين
function getCustomerStoreProducts($customerId, $limit = 12) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT 
            p.id,
            p.title,
            p.main_image,
            p.price,
            p.final_price,
            p.discount_percentage
        FROM products p 
        WHERE p.created_by = ? 
          AND p.is_active = 1
        ORDER BY p.created_at DESC 
        LIMIT ?
    ");
    $stmt->execute([$customerId, $limit]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // تنسيق الصور
    foreach ($products as &$p) {
        $p['main_image'] = $p['main_image'] ?: 'assets/images/placeholder.jpg';
        $p['final_price'] = $p['final_price'] ?: $p['price'];
    }

    return $products;
}
function handleReferralPurchase($order_id, $referred_customer_id, $referral_code) {
    global $pdo;
    
    try {
        // البحث عن رابط الإحالة
        $stmt = $pdo->prepare("SELECT * FROM referral_links WHERE referral_code = ?");
        $stmt->execute([$referral_code]);
        $referral_link = $stmt->fetch();
        
        if ($referral_link) {
            $referrer_id = $referral_link['customer_id'];
            
            // منح النقاط للمُحيل
            $referrer_points = getSetting('referral_points_referrer', 500);
            addCustomerPoints($referrer_id, $referrer_points, 'referral_earn', 'مكافأة إحالة - طلب #' . $order_id);
            
            // منح النقاط للمُحال
            $referred_points = getSetting('referral_points_referred', 300);
            addCustomerPoints($referred_customer_id, $referred_points, 'referral_earn', 'مكافأة إحالة - طلب جديد');
            
            // تحديث إحصائيات رابط الإحالة
            $stmt = $pdo->prepare("
                UPDATE referral_links 
                SET completed_orders = completed_orders + 1, 
                    total_earned_points = total_earned_points + ? 
                WHERE id = ?
            ");
            $stmt->execute([$referrer_points, $referral_link['id']]);
            
            // تسجيل عملية الإحالة
            $stmt = $pdo->prepare("
                INSERT INTO referrals 
                (referrer_id, referred_id, referral_code, status, points_earned, completed_order_id) 
                VALUES (?, ?, ?, 'completed_order', ?, ?)
            ");
            $stmt->execute([$referrer_id, $referred_customer_id, $referral_code, $referrer_points, $order_id]);
            
            return true;
        }
    } catch (PDOException $e) {
        error_log("Referral Purchase Error: " . $e->getMessage());
    }
    
    return false;
}
// دوال الجمعة البيضاء - الإصاح المصحح
function getBlackFridayCategories() {
    $categories = getSetting('black_friday_categories', '[]');
    $decoded = json_decode($categories, true);
    return is_array($decoded) ? $decoded : [];
}

function getBlackFridaySettings() {
    return [
        'enabled' => getSetting('black_friday_enabled', '1') == '1',
        'start_date' => getSetting('black_friday_start_date', '11-24'),
        'duration_days' => (int) getSetting('black_friday_duration_days', 3),
        'discount_percentage' => (float) getSetting('black_friday_discount_percentage', 50),
        'categories' => getBlackFridayCategories(),
        'test_mode' => getSetting('black_friday_test_mode', '0') == '1',
        'test_date' => getSetting('black_friday_test_date', null)
    ];
}

function isBlackFridayPeriod() {
    $settings = getBlackFridaySettings();
    
    if (!$settings['enabled']) {
        return false;
    }
    
    // وضع الاختبار
    if ($settings['test_mode'] && $settings['test_date']) {
        $currentDate = date('Y-m-d', strtotime($settings['test_date']));
    } else {
        $currentDate = date('Y-m-d');
    }
    
    $currentYear = date('Y');
    $startDate = $currentYear . '-' . $settings['start_date'];
    $endDate = date('Y-m-d', strtotime($startDate . ' + ' . $settings['duration_days'] . ' days'));
    
    error_log("Black Friday Check: Current: $currentDate, Start: $startDate, End: $endDate");
    
    return ($currentDate >= $startDate && $currentDate <= $endDate);
}

function getRemainingBlackFridayTime() {
    if (!isBlackFridayPeriod()) {
        return null;
    }
    
    $settings = getBlackFridaySettings();
    $currentYear = date('Y');
    $startDate = $currentYear . '-' . $settings['start_date'];
    $endDate = date('Y-m-d', strtotime($startDate . ' + ' . $settings['duration_days'] . ' days'));
    
    $endDateTime = strtotime($endDate . ' 23:59:59');
    $currentTime = time();
    
    $remaining = $endDateTime - $currentTime;
    
    if ($remaining <= 0) {
        return null;
    }
    
    return [
        'days' => floor($remaining / (60 * 60 * 24)),
        'hours' => floor(($remaining % (60 * 60 * 24)) / (60 * 60)),
        'minutes' => floor(($remaining % (60 * 60)) / 60),
        'seconds' => $remaining % 60
    ];
}

function applyBlackFridayDiscount($product) {
    if (!isBlackFridayPeriod()) {
        return $product;
    }
    
    $settings = getBlackFridaySettings();
    $discountPercentage = $settings['discount_percentage'];
    $blackFridayCategories = $settings['categories'];
    
    // إذا كان المنتج في الفئات المحددة أو تم تفعيل الخصم لجميع المنتجات
    if (empty($blackFridayCategories) || in_array($product['category_id'], $blackFridayCategories)) {
        $originalPrice = $product['price'] / (1 - ($discountPercentage / 100));
        $discountAmount = $originalPrice * ($discountPercentage / 100);
        $product['final_price'] = $originalPrice - $discountAmount;
        $product['discount_percentage'] = $discountPercentage;
        $product['is_black_friday'] = true;
        $product['black_friday_original_price'] = $originalPrice;
    }
    
    return $product;
}

function saveBlackFridayDiscount($productId, $originalPrice, $discountPercentage) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO black_friday_discounts (product_id, original_price, discount_percentage) 
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            original_price = VALUES(original_price),
            discount_percentage = VALUES(discount_percentage),
            is_active = 1
        ");
        return $stmt->execute([$productId, $originalPrice, $discountPercentage]);
    } catch (Exception $e) {
        error_log("Error saving black friday discount: " . $e->getMessage());
        return false;
    }
}

function restoreBlackFridayPrices() {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            UPDATE products p
            JOIN black_friday_discounts bfd ON p.id = bfd.product_id
            SET p.price = bfd.original_price,
                p.discount_percentage = 0
            WHERE bfd.is_active = 1
        ");
        $stmt->execute();
        
        // تعطيل الخصومات
        $stmt = $pdo->prepare("UPDATE black_friday_discounts SET is_active = 0");
        $stmt->execute();
        
        error_log("Black Friday: Prices restored successfully");
        return true;
    } catch (Exception $e) {
        error_log("Error restoring black friday prices: " . $e->getMessage());
        return false;
    }
}

function shouldRestorePrices() {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM black_friday_discounts WHERE is_active = 1");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['count'] > 0;
    } catch (Exception $e) {
        error_log("Error checking restore prices: " . $e->getMessage());
        return false;
    }
}

// دالة محسنة لتطبيق الخصومات
function autoApplyBlackFridayDiscounts() {
    $settings = getBlackFridaySettings();
    
    if (!isBlackFridayPeriod()) {
        // إذا انتهت الفترة، استعادة الأسعار
        if (shouldRestorePrices()) {
            restoreBlackFridayPrices();
        }
        return;
    }
    
    $discountPercentage = $settings['discount_percentage'];
    $blackFridayCategories = $settings['categories'];
    
    global $pdo;
    
    try {
        $query = "SELECT id, price, discount_percentage FROM products WHERE is_active = 1";
        $params = [];
        
        if (!empty($blackFridayCategories)) {
            $placeholders = str_repeat('?,', count($blackFridayCategories) - 1) . '?';
            $query .= " AND category_id IN ($placeholders)";
            $params = $blackFridayCategories;
        }
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $updatedCount = 0;
        
        foreach ($products as $product) {
            // إذا كان الخصم مطبقاً مسبقاً، تخطي
            if ($product['discount_percentage'] == $discountPercentage) {
                continue;
            }
            
            // حفظ السعر الأصلي
            saveBlackFridayDiscount($product['id'], $product['price'], $discountPercentage);
            
            // تطبيق الخصم
            $discountAmount = $product['price'] * ($discountPercentage / 100);
            $newPrice = $product['price'] - $discountAmount;
            
            $updateStmt = $pdo->prepare("
                UPDATE products 
                SET discount_percentage = ?, 
                    price = ?
                WHERE id = ?
            ");
            $updateStmt->execute([$discountPercentage, $newPrice, $product['id']]);
            
            $updatedCount++;
        }
        
        error_log("Black Friday: Applied {$discountPercentage}% discount to {$updatedCount} products");
        return $updatedCount;
        
    } catch (Exception $e) {
        error_log("Error applying black friday discounts: " . $e->getMessage());
        return 0;
    }
}

// دالة لاختبار النظام
function testBlackFridaySystem($testDate = null) {
    $settings = getBlackFridaySettings();
    $isActive = isBlackFridayPeriod();
    $remainingTime = getRemainingBlackFridayTime();
    
    $result = [
        'is_active' => $isActive,
        'settings' => $settings,
        'remaining_time' => $remainingTime,
        'test_date' => $testDate ?: date('Y-m-d'),
        'current_date' => date('Y-m-d')
    ];
    
    return $result;
}

// دالة للحصول على حالة النظام
function getBlackFridayStatus() {
    $settings = getBlackFridaySettings();
    $isActive = isBlackFridayPeriod();
    $remainingTime = getRemainingBlackFridayTime();
    
    return [
        'active' => $isActive,
        'enabled' => $settings['enabled'],
        'test_mode' => $settings['test_mode'],
        'discount_percentage' => $settings['discount_percentage'],
        'duration_days' => $settings['duration_days'],
        'categories_count' => count($settings['categories']),
        'remaining_time' => $remainingTime
    ];
}
// ==================== نظام الكاشباك (Cashback) ====================

// الحصول على إعدادات الكاشباك
function getCashbackSettings() {
    return [
        'enabled' => getSetting('cashback_enabled', '1') == '1',
        'percentage' => (float) getSetting('cashback_percentage', '5'),
        'min_amount' => (float) getSetting('cashback_min_amount', '0'),
        'max_amount' => (float) getSetting('cashback_max_amount', '100'),
        'categories' => json_decode(getSetting('cashback_categories', '[]'), true) ?: []
    ];
}

// الحصول على كاشباك منتج معين
function getProductCashback($productId) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT * FROM product_cashback 
        WHERE product_id = ? AND is_active = 1 
        AND (start_date IS NULL OR start_date <= NOW()) 
        AND (end_date IS NULL OR end_date >= NOW())
        ORDER BY created_at DESC LIMIT 1
    ");
    $stmt->execute([$productId]);
    $cashback = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($cashback) {
        return $cashback;
    }
    
    // إذا لم يكن هناك كاشباك مخصص، استخدم الإعدادات العامة
    $settings = getCashbackSettings();
    if ($settings['enabled']) {
        return [
            'cashback_percentage' => $settings['percentage'],
            'cashback_amount' => 0,
            'is_custom' => false
        ];
    }
    
    return null;
}

// حساب قيمة الكاشباك لمنتج
function calculateProductCashback($product) {
    $cashbackData = getProductCashback($product['id']);
    
    if (!$cashbackData) {
        return null;
    }
    
    $settings = getCashbackSettings();
    $productPrice = $product['final_price'] ?? $product['price'];
    
    // حساب قيمة الكاشباك
    if ($cashbackData['cashback_amount'] > 0) {
        $cashbackAmount = $cashbackData['cashback_amount'];
    } else {
        $cashbackAmount = $productPrice * ($cashbackData['cashback_percentage'] / 100);
    }
    
    // تطبيق الحد الأدنى والأقصى
    if ($cashbackAmount < $settings['min_amount']) {
        $cashbackAmount = $settings['min_amount'];
    }
    if ($settings['max_amount'] > 0 && $cashbackAmount > $settings['max_amount']) {
        $cashbackAmount = $settings['max_amount'];
    }
    
    return [
        'amount' => $cashbackAmount,
        'percentage' => $cashbackData['cashback_percentage'],
        'formatted_amount' => formatPrice($cashbackAmount),
        'is_custom' => $cashbackData['is_custom'] ?? false
    ];
}

// تطبيق الكاشباك على المنتج
function applyCashbackToProduct($product) {
    $cashback = calculateProductCashback($product);
    
    if ($cashback) {
        $product['cashback'] = $cashback;
        $product['has_cashback'] = true;
    } else {
        $product['has_cashback'] = false;
    }
    
    return $product;
}

// تسجيل معاملة كاشباك
function recordCashbackTransaction($customerId, $orderId, $productId, $amount, $percentage) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        INSERT INTO cashback_transactions 
        (customer_id, order_id, product_id, amount, percentage, status) 
        VALUES (?, ?, ?, ?, ?, 'pending')
    ");
    
    return $stmt->execute([$customerId, $orderId, $productId, $amount, $percentage]);
}

// الحصول على رصيد الكاشباك للعميل
function getCustomerCashbackBalance($customerId) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT SUM(amount) as total_balance 
        FROM cashback_transactions 
        WHERE customer_id = ? AND status = 'approved'
    ");
    $stmt->execute([$customerId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result['total_balance'] ?? 0;
}

// الحصول على تاريخ الكاشباك للعميل
function getCustomerCashbackHistory($customerId, $limit = 10) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT ct.*, p.title as product_title, o.order_number
        FROM cashback_transactions ct
        LEFT JOIN products p ON ct.product_id = p.id
        LEFT JOIN orders o ON ct.order_id = o.id
        WHERE ct.customer_id = ?
        ORDER BY ct.transaction_date DESC
        LIMIT ?
    ");
    $stmt->execute([$customerId, $limit]);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
// دوال مساعدة لنظام الكاشباك
function getCashbackTransactions($customerId = null, $limit = 50) {
    global $pdo;
    
    $query = "
        SELECT ct.*, p.title as product_title, o.order_number,
               c.first_name, c.last_name, c.email
        FROM cashback_transactions ct
        LEFT JOIN products p ON ct.product_id = p.id
        LEFT JOIN orders o ON ct.order_id = o.id
        LEFT JOIN customers c ON ct.customer_id = c.id
    ";
    
    $params = [];
    
    if ($customerId) {
        $query .= " WHERE ct.customer_id = ?";
        $params[] = $customerId;
    }
    
    $query .= " ORDER BY ct.transaction_date DESC LIMIT ?";
    $params[] = $limit;
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function approveCashbackTransaction($transactionId) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        UPDATE cashback_transactions 
        SET status = 'approved', approved_at = NOW() 
        WHERE id = ?
    ");
    return $stmt->execute([$transactionId]);
}

function getCashbackReport($startDate = null, $endDate = null) {
    global $pdo;
    
    $query = "
        SELECT 
            DATE(ct.transaction_date) as date,
            COUNT(*) as transactions_count,
            SUM(ct.amount) as total_amount,
            AVG(ct.percentage) as avg_percentage,
            COUNT(DISTINCT ct.customer_id) as unique_customers,
            COUNT(DISTINCT ct.product_id) as unique_products
        FROM cashback_transactions ct
        WHERE ct.status = 'approved'
    ";
    
    $params = [];
    
    if ($startDate) {
        $query .= " AND ct.transaction_date >= ?";
        $params[] = $startDate;
    }
    
    if ($endDate) {
        $query .= " AND ct.transaction_date <= ?";
        $params[] = $endDate;
    }
    
    $query .= " GROUP BY DATE(ct.transaction_date) ORDER BY date DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * إضافة نقاط للمستخدم
 */
function addCustomerPoints($customer_id, $points, $type = 'manual', $description = '') {
    global $pdo;
    
    try {
        // تحديث أو إنشاء سجل النقاط
        $stmt = $pdo->prepare("
            INSERT INTO customer_points (customer_id, points, total_earned) 
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            points = points + VALUES(points),
            total_earned = total_earned + VALUES(total_earned)
        ");
        $stmt->execute([$customer_id, $points, $points]);
        
        // تسجيل المعاملة
        $stmt = $pdo->prepare("
            INSERT INTO point_transactions (customer_id, points, type, description, reference_type)
            VALUES (?, ?, 'earn', ?, ?)
        ");
        $stmt->execute([$customer_id, $points, $description, $type]);
        
        return true;
        
    } catch (Exception $e) {
        error_log("Add points error: " . $e->getMessage());
        return false;
    }
}
 
/**
 * الحصول على نقاط المستخدم
 */
function getCustomerPoints1($customer_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT points as available_points, total_earned, total_spent 
        FROM customer_points 
        WHERE customer_id = ?
    ");
    $stmt->execute([$customer_id]);
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$result) {
        return [
            'available_points' => 0,
            'total_earned' => 0,
            'total_spent' => 0
        ];
    }
    
    return $result;
}


function getCartItems() {
    $cart = $_SESSION['cart'] ?? [];
    
    if (empty($cart)) {
        return [];
    }
    
    global $pdo;
    $productIds = array_keys($cart);
    $placeholders = implode(',', array_fill(0, count($productIds), '?'));
    
    $stmt = $pdo->prepare("
        SELECT p.id, p.title, p.price, p.final_price, p.stock, p.main_image,
               p.store_type, p.created_by,
               CASE 
                   WHEN p.store_type = 'customer' THEN CONCAT(c.first_name, ' ', c.last_name) 
                   ELSE 'المتجر الرئيسي' 
               END as store_name
        FROM products p 
        LEFT JOIN customers c ON p.created_by = c.id 
        WHERE p.id IN ($placeholders)
    ");
    $stmt->execute($productIds);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $cartItems = [];
    foreach ($products as $product) {
        $cartItems[$product['id']] = [
            'title' => $product['title'],
            'price' => $product['final_price'] > 0 ? $product['final_price'] : $product['price'],
            'stock' => $product['stock'],
            'image' => $product['main_image'],
            'qty' => $cart[$product['id']]['qty'],
            'store_type' => $product['store_type'],
            'store_name' => $product['store_name'],
            'created_by' => $product['created_by']
        ];
    }
    
    return $cartItems;
}
?>
<?php

/**
 * إنشاء كود QR للتخفيض للمنتج
 */
function generateStoreQRCode($product_id, $customer_id, $store_owner_id) {
    global $pdo;
    
    // جلب بيانات المنتج
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
    
    if (!$product) return false;
    
    // حساب السعر المخفض (نفترض وجود تخفيض 10% كمثال)
    $discount_percentage = 10;
    $original_price = $product['final_price'];
    $discounted_price = $original_price * (1 - ($discount_percentage / 100));
    
    // إنشاء رمز فريد للQR
    $qr_code = 'QR_' . uniqid() . '_' . $product_id;
    
    // تحضير البيانات للQR
    $qr_data = json_encode([
        'product_id' => $product_id,
        'product_title' => $product['title'],
        'customer_id' => $customer_id,
        'store_owner_id' => $store_owner_id,
        'original_price' => $original_price,
        'discounted_price' => $discounted_price,
        'discount_percentage' => $discount_percentage,
        'qr_code' => $qr_code,
        'created_at' => date('Y-m-d H:i:s'),
        'expires_at' => date('Y-m-d H:i:s', strtotime('+24 hours'))
    ]);
    
    // حفظ في قاعدة البيانات
    $stmt = $pdo->prepare("INSERT INTO store_qr_codes (product_id, customer_id, store_owner_id, qr_code, qr_data, original_price, discounted_price, discount_percentage, expires_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $product_id,
        $customer_id,
        $store_owner_id,
        $qr_code,
        $qr_data,
        $original_price,
        $discounted_price,
        $discount_percentage,
        date('Y-m-d H:i:s', strtotime('+24 hours'))
    ]);
    
    return [
        'qr_code' => $qr_code,
        'qr_data' => $qr_data,
        'product' => $product,
        'discount_info' => [
            'original_price' => $original_price,
            'discounted_price' => $discounted_price,
            'discount_percentage' => $discount_percentage
        ]
    ];
}

/**
 * التحقق من صحة كود QR
 */
function validateQRCode($qr_code) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT sqc.*, p.title as product_title, c.first_name as customer_name, c.phone as customer_phone, store.first_name as store_owner_name FROM store_qr_codes sqc 
                          LEFT JOIN products p ON sqc.product_id = p.id 
                          LEFT JOIN customers c ON sqc.customer_id = c.id 
                          LEFT JOIN customers store ON sqc.store_owner_id = store.id 
                          WHERE sqc.qr_code = ? AND sqc.is_valid = 1 AND sqc.expires_at > NOW()");
    $stmt->execute([$qr_code]);
    $qr_data = $stmt->fetch();
    
    if (!$qr_data) {
        return ['valid' => false, 'message' => 'كود QR غير صالح أو منتهي الصلاحية'];
    }
    
    if ($qr_data['is_used']) {
        return ['valid' => false, 'message' => 'كود QR مستخدم مسبقاً'];
    }
    
    return ['valid' => true, 'data' => $qr_data];
}

/**
 * استخدام كود QR
 */
function useQRCode($qr_code, $store_owner_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("UPDATE store_qr_codes SET is_used = 1, used_at = NOW() WHERE qr_code = ? AND store_owner_id = ? AND is_used = 0");
    $result = $stmt->execute([$qr_code, $store_owner_id]);
    
    return $result;
}

/**
 * جلب بيانات المتجر
 */
function getStoreInfo($store_owner_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT id, first_name, last_name, email, phone, created_at FROM customers WHERE id = ?");
    $stmt->execute([$store_owner_id]);
    return $stmt->fetch();
}
?>
<?php
// دوال الاسترشاد الذكي للتجار

// التحقق من تفعيل الاسترشاد الذكي للتاجر
function isSmartGuidanceEnabled($merchant_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM merchant_smart_guidance WHERE merchant_id = ? AND is_active = 1");
    $stmt->execute([$merchant_id]);
    return $stmt->fetchColumn() > 0;
}

// تحليل المنتج وتقديم توصيات العروض
function analyzeProductForOffers($product_id, $merchant_id, $capital_data) {
    global $pdo;
    
    // جلب بيانات المنتج
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND created_by = ?");
    $stmt->execute([$product_id, $merchant_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) return null;
    
    // حساب فترة التخزين
    $storage_duration = (strtotime('now') - strtotime($capital_data['purchase_date'])) / (60 * 60 * 24);
    
    // حساب نسبة الخسارة الحالية
    $current_loss_rate = (($capital_data['purchase_price'] - $product['final_price']) / $capital_data['purchase_price']) * 100;
    
    // تحليل العروض المناسبة
    $recommended_offers = analyzeSuitableOffers($product, $capital_data, $storage_duration, $current_loss_rate);
    
    // حساب التوقعات
    $analysis = calculateProfitLossExpectations($product, $capital_data, $recommended_offers);
    
    return [
        'product' => $product,
        'storage_duration' => floor($storage_duration),
        'current_loss_rate' => $current_loss_rate,
        'recommended_offers' => $recommended_offers,
        'analysis' => $analysis
    ];
}

// تحليل العروض المناسبة
function analyzeSuitableOffers($product, $capital_data, $storage_duration, $current_loss_rate) {
    $offers = [];
    
    // قاعدة القرار بناءً على فترة التخزين ونسبة الخسارة
    if ($storage_duration > 180) { // أكثر من 6 أشهر
        $offers[] = [
            'type' => 'buy2_get1',
            'priority' => 1,
            'discount_rate' => 33.33,
            'reason' => 'فترة تخزين طويلة - تحتاج لتحفيز سريع للمبيعات'
        ];
        $offers[] = [
            'type' => 'qr_discount',
            'priority' => 2,
            'discount_rate' => 25,
            'reason' => 'عرض حصري لتفريغ المخزون'
        ];
    } elseif ($storage_duration > 90) { // 3-6 أشهر
        $offers[] = [
            'type' => 'coupon',
            'priority' => 1,
            'discount_rate' => 20,
            'reason' => 'فترة تخزين متوسطة - خصم معقول'
        ];
        $offers[] = [
            'type' => 'points',
            'priority' => 2,
            'points_rate' => 15,
            'reason' => 'جذب العملاء بنظام المكافآت'
        ];
    } else { // أقل من 3 أشهر
        $offers[] = [
            'type' => 'flash_sale',
            'priority' => 1,
            'discount_rate' => 10,
            'reason' => 'منتج جديد - خصم تشجيعي'
        ];
    }
    
    // تعديل بناءً على نسبة الخسارة
    if ($current_loss_rate > $capital_data['loss_tolerance']) {
        // إضافة عروض إضافية للحد من الخسائر
        array_unshift($offers, [
            'type' => 'bundle',
            'priority' => 0,
            'discount_rate' => 40,
            'reason' => 'عرض حزمة سريع للحد من الخسائر'
        ]);
    }
    
    // إضافة QR Code كعرض إضافي دائماً
    $offers[] = [
        'type' => 'qr_code',
        'priority' => 3,
        'discount_rate' => 15,
        'reason' => 'عرض تفاعلي لجذب العملاء'
    ];
    
    // ترتيب العروض حسب الأولوية
    usort($offers, function($a, $b) {
        return $a['priority'] - $b['priority'];
    });
    
    return $offers;
}

// حساب توقعات الربح والخسارة
function calculateProfitLossExpectations($product, $capital_data, $offers) {
    $analysis = [];
    
    foreach ($offers as $offer) {
        $discount_rate = $offer['discount_rate'] ?? 0;
        $expected_price = $product['final_price'] * (1 - ($discount_rate / 100));
        $profit_loss = $expected_price - $capital_data['purchase_price'];
        $profit_loss_rate = ($profit_loss / $capital_data['purchase_price']) * 100;
        
        $analysis[$offer['type']] = [
            'expected_price' => $expected_price,
            'profit_loss' => $profit_loss,
            'profit_loss_rate' => $profit_loss_rate,
            'effectiveness' => calculateOfferEffectiveness($offer, $profit_loss_rate)
        ];
    }
    
    return $analysis;
}

// حساب فعالية العرض
function calculateOfferEffectiveness($offer, $profit_loss_rate) {
    $effectiveness = 0;
    
    if ($profit_loss_rate >= 0) {
        $effectiveness = 80 + ($profit_loss_rate * 2); // زيادة الفعالية مع زيادة الربح
    } else {
        $effectiveness = max(20, 60 + ($profit_loss_rate * 4)); // تقليل الفعالية مع الخسارة
    }
    
    return min(100, max(0, $effectiveness));
}

// حفظ توصيات الاسترشاد الذكي
function saveSmartGuidance($merchant_id, $product_id, $capital_data, $analysis) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        INSERT INTO merchant_smart_guidance 
        (merchant_id, product_id, capital_amount, purchase_date, purchase_price, current_price, 
         storage_duration, loss_tolerance, recommended_offers, expected_loss_rate, expected_profit_rate, analysis_data)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
        capital_amount = VALUES(capital_amount),
        purchase_date = VALUES(purchase_date),
        purchase_price = VALUES(purchase_price),
        current_price = VALUES(current_price),
        storage_duration = VALUES(storage_duration),
        loss_tolerance = VALUES(loss_tolerance),
        recommended_offers = VALUES(recommended_offers),
        expected_loss_rate = VALUES(expected_loss_rate),
        expected_profit_rate = VALUES(expected_profit_rate),
        analysis_data = VALUES(analysis_data),
        updated_at = CURRENT_TIMESTAMP
    ");
    
    return $stmt->execute([
        $merchant_id,
        $product_id,
        $capital_data['capital_amount'],
        $capital_data['purchase_date'],
        $capital_data['purchase_price'],
        $analysis['product']['final_price'],
        $analysis['storage_duration'],
        $capital_data['loss_tolerance'],
        json_encode($analysis['recommended_offers'], JSON_UNESCAPED_UNICODE),
        $analysis['current_loss_rate'],
        max(array_column($analysis['analysis'], 'profit_loss_rate')),
        json_encode($analysis['analysis'], JSON_UNESCAPED_UNICODE)
    ]);
}
?>
<?php
// دوال مساعدة إضافية

function getOfferTypeLabel($type) {
    $labels = [
        'buy2_get1' => 'اشتري 2 واحصل على 1',
        'coupon' => 'كوبون خصم',
        'qr_code' => 'كود QR للتخفيض',
        'points' => 'عرض النقاط',
        'flash_sale' => 'عرض سريع',
        'bundle' => 'عرض حزمة'
    ];
    return $labels[$type] ?? $type;
}

function getOfferTitle($type) {
    $titles = [
        'buy2_get1' => 'عرض اشتري 2 واحصل على 1 مجاناً',
        'coupon' => 'كوبون خصم حصري',
        'qr_code' => 'تخفيض QR عند الزيارة',
        'points' => 'مكافآت نقاط مضاعفة',
        'flash_sale' => 'عرض لمدة محدودة',
        'bundle' => 'عرض حزمة توفير'
    ];
    return $titles[$type] ?? 'عرض خاص';
}

function getMerchantProducts1($merchant_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM products WHERE created_by = ? AND is_active = 1");
    $stmt->execute([$merchant_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getMerchantRecommendations($merchant_id) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT mg.*, p.title as product_title 
        FROM merchant_smart_guidance mg 
        JOIN products p ON mg.product_id = p.id 
        WHERE mg.merchant_id = ? 
        ORDER BY mg.updated_at DESC 
        LIMIT 10
    ");
    $stmt->execute([$merchant_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function isMerchant1($customer_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE created_by = ?");
    $stmt->execute([$customer_id]);
    return $stmt->fetchColumn() > 0;
}
?>
<?php
// دوال الاسترشاد الذكي للتجار - إضافية

// جلب العروض الذكية النشطة للمنتج
function getActiveSmartOffers($product_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT recommended_offers 
        FROM merchant_smart_guidance 
        WHERE product_id = ? AND is_active = 1
        ORDER BY updated_at DESC 
        LIMIT 1
    ");
    $stmt->execute([$product_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result && $result['recommended_offers']) {
        $offers = json_decode($result['recommended_offers'], true);
        // ترشيح العروض ذات الأولوية العالية فقط (الأولوية 0 أو 1)
        return array_filter($offers, function($offer) {
            return isset($offer['priority']) && $offer['priority'] <= 1;
        });
    }
    
    return [];
}

// تفعيل عرض ذكي
function activateSmartOffer($product_id, $offer_type, $merchant_id) {
    global $pdo;
    
    // التحقق من أن التاجر هو صاحب المنتج
    $stmt = $pdo->prepare("SELECT created_by FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product || $product['created_by'] != $merchant_id) {
        return ['success' => false, 'message' => 'غير مصرح بتفعيل العرض على هذا المنتج'];
    }
    
    try {
        $pdo->beginTransaction();
        
        // تفعيل العرض بناءً على النوع
        switch ($offer_type) {
            case 'buy2_get1':
                // تفعيل عرض اشتري 2 واحصل على 1
                $stmt = $pdo->prepare("
                    INSERT INTO product_offers (product_id, offer_type, title, description, is_active, min_quantity)
                    VALUES (?, 'buy2_get1', 'عرض اشتري 2 واحصل على 1', 'عرض خاص: اشتري 2 واحصل على 1 مجاناً', 1, 3)
                    ON DUPLICATE KEY UPDATE is_active = 1, updated_at = CURRENT_TIMESTAMP
                ");
                $stmt->execute([$product_id]);
                break;
                
            case 'coupon':
                // إنشاء كوبون خصم
                $coupon_code = generateCouponCode();
                $stmt = $pdo->prepare("
                    INSERT INTO coupons (code, description, discount_type, discount_value, min_order_amount, usage_limit, is_active)
                    VALUES (?, 'خصم خاص على المنتج', 'percentage', 20, 0, 100, 1)
                ");
                $stmt->execute([$coupon_code]);
                break;
                
            case 'qr_code':
                // إنشاء كود QR للتخفيض
                $qr_code = generateQRCode($product_id, $merchant_id, 15); // 15% خصم
                break;
                
            case 'points':
                // تفعيل عرض النقاط
                $stmt = $pdo->prepare("
                    UPDATE products 
                    SET special_offer_type = 'points', special_offer_value = ? 
                    WHERE id = ?
                ");
                $points_value = calculatePointsFromPurchase(getProductPrice($product_id)) * 2; // نقاط مضاعفة
                $stmt->execute([$points_value, $product_id]);
                break;
                
            case 'flash_sale':
                // تفعيل عرض سريع
                $stmt = $pdo->prepare("
                    INSERT INTO price_countdowns (product_id, new_price, countdown_end, is_active)
                    VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 24 HOUR), 1)
                ");
                $current_price = getProductPrice($product_id);
                $flash_price = $current_price * 0.9; // 10% خصم
                $stmt->execute([$product_id, $flash_price]);
                break;
        }
        
        $pdo->commit();
        return ['success' => true, 'message' => 'تم تفعيل العرض بنجاح'];
        
    } catch (Exception $e) {
        $pdo->rollBack();
        return ['success' => false, 'message' => 'حدث خطأ في تفعيل العرض: ' . $e->getMessage()];
    }
}

// إنشاء كود كوبون
function generateCouponCode() {
    $prefix = 'SMART';
    $random = strtoupper(substr(md5(uniqid()), 0, 6));
    return $prefix . $random;
}

// إنشاء كود QR للتخفيض
function generateQRCode($product_id, $merchant_id, $discount_percentage) {
    global $pdo;
    
    $qr_code = 'QR_' . $product_id . '_' . $merchant_id . '_' . uniqid();
    
    $stmt = $pdo->prepare("
        INSERT INTO store_qr_codes (customer_id, product_id, qr_code, discount_percentage, expires_at, is_valid)
        VALUES (?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 7 DAY), 1)
    ");
    $stmt->execute([$merchant_id, $product_id, $qr_code, $discount_percentage]);
    
    return $qr_code;
}

// جلب سعر المنتج
function getProductPrice($product_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT final_price FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $product ? $product['final_price'] : 0;
}

// دوال مساعدة للعروض الذكية في الصفحة الرئيسية
function hasSmartOffers($product_id) {
    return !empty(getActiveSmartOffers($product_id));
}

// دالة لتحسين كود الصفحة الرئيسية
function displaySmartOffersBadges($product) {
    if ($product['store_type'] !== 'customer' || empty($product['created_by'])) {
        return '';
    }
    
    if (!isSmartGuidanceEnabled($product['created_by'])) {
        return '';
    }
    
    $smartOffers = getActiveSmartOffers($product['id']);
    if (empty($smartOffers)) {
        return '';
    }
    
    $badges = '';
    foreach ($smartOffers as $offer) {
        $badge_class = 'smart-offer-badge offer-' . $offer['type'];
        $badge_label = getOfferTypeLabel($offer['type']);
        
        $badges .= '<span class="' . $badge_class . '" title="' . ($offer['reason'] ?? 'عرض موصى به') . '">' . $badge_label . '</span>';
    }
    
    return '<div class="smart-offers-badges">' . $badges . '</div>';
}

// دالة لتحسين كود الصفحة الرئيسية - النسخة المحسنة
function getEnhancedProductDisplay($product) {
    $smart_offers_html = displaySmartOffersBadges($product);
    
    // باقي كود عرض المنتج...
    return $smart_offers_html;
}
?>





<?php
// ==================== دوال الإعلانات ====================
/**
 * جلب بيانات عميل بناءً على ID
 * @param int $customerId
 * @return array|false بيانات العميل أو false إذا لم يوجد
 */
function getCustomer($customerId) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ? LIMIT 1");
    $stmt->execute([$customerId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
 
 /**
 * جلب إعلانات نشطة حسب الموقع
 */
/**
 * جلب إعلانات نشطة حسب الموقع
 */
function getActiveAds($position = null) {
    global $pdo;
    
    try {
        $sql = "SELECT * FROM advertisements 
                WHERE status = 'active' 
                AND start_date <= NOW() 
                AND (end_date IS NULL OR end_date >= NOW())";
        
        if ($position) {
            $sql .= " AND position = ?";
        }
        
        $sql .= " ORDER BY RAND() LIMIT 5";
        
        $stmt = $pdo->prepare($sql);
        
        if ($position) {
            $stmt->execute([$position]);
        } else {
            $stmt->execute();
        }
        
        $ads = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // إذا لم توجد إعلانات، إرجاع إعلانات افتراضية
        if (empty($ads)) {
            return getDefaultAds($position);
        }
        
        return $ads;
        
    } catch (Exception $e) {
        error_log("Error getting ads: " . $e->getMessage());
        return getDefaultAds($position);
    }
}

/**
 * إعلانات افتراضية إذا لم توجد إعلانات في قاعدة البيانات
 */
function getDefaultAds($position = null) {
    $defaultAds = [
        [
            'id' => 1,
            'title' => 'عروض خاصة',
            'description' => 'خصم يصل إلى 50% على المنتجات المختارة',
            'content_url' => 'assets/images/placeholder.jpg',
            'type' => 'image',
            'position' => 'between_products',
            'target_url' => 'index.php',
            'status' => 'active'
        ],
        [
            'id' => 2,
            'title' => 'تطبيق الجوال',
            'description' => 'حمل تطبيقنا واحصل على خصم 10%',
            'content_url' => 'assets/images/placeholder.jpg',
            'type' => 'image',
            'position' => 'popup',
            'target_url' => 'index.php',
            'status' => 'active'
        ],
        [
            'id' => 3,
            'title' => 'شحن مجاني',
            'description' => 'شحن مجاني للطلبات فوق 200 جنيه',
            'content_url' => 'assets/images/placeholder.jpg',
            'type' => 'image',
            'position' => 'side_button',
            'target_url' => 'index.php',
            'status' => 'active'
        ]
    ];
    
    if ($position) {
        return array_filter($defaultAds, function($ad) use ($position) {
            return $ad['position'] === $position;
        });
    }
    
    return $defaultAds;
}

/**
 * تحديث عدد المشاهدات أو النقرات
 */
function updateAdStats($adId, $type = 'views') {
    global $pdo;
    $field = $type === 'clicks' ? 'clicks' : 'views';
    $stmt = $pdo->prepare("UPDATE advertisements SET $field = $field + 1 WHERE id = ?");
    $stmt->execute([$adId]);
}

/**
 * زيادة خصم المنتج مؤقتاً
 */
function increaseProductDiscount($productId, $increasePercent, $endDate) {
    global $pdo;
    $stmt = $pdo->prepare("
        UPDATE products 
        SET ad_discount_increase = ?, ad_end_date = ?, final_price = final_price * (1 - ?/100)
        WHERE id = ?
    ");
    $stmt->execute([$increasePercent, $endDate, $increasePercent, $productId]);
}

/**
 * خصم نقاط (تكامل مع point_transactions)
 */
function deductPoints($customerId, $amount, $reason) {
    global $pdo;
    // أضف معاملة إلى point_transactions
    $stmt = $pdo->prepare("INSERT INTO point_transactions (customer_id, points, type, description) VALUES (?, ?, 'debit', ?)");
    $stmt->execute([$customerId, -$amount, $reason]);
    
    // تحديث رصيد النقاط في customers (إذا كان موجوداً)
    $pdo->prepare("UPDATE customers SET points_balance = points_balance - ? WHERE id = ?")->execute([$amount, $customerId]);
}

/**
 * خصم من المحفظة
 */
function deductWallet($customerId, $amount, $reason) {
    global $pdo;
    $pdo->prepare("UPDATE customers SET wallet_balance = wallet_balance - ? WHERE id = ?")->execute([$amount, $customerId]);
    // يمكن إضافة سجل في جدول wallet_transactions إذا كان موجوداً
}

 
/**
 * تحرير إعلان (للإدارة أو الصاحب)
 */
function updateAd($adId, $data) {
    global $pdo;
    // بناء الاستعلام ديناميكياً بناءً على $data
    // مثال بسيط:
    $stmt = $pdo->prepare("UPDATE advertisements SET status = ? WHERE id = ?");
    $stmt->execute([$data['status'], $adId]);
    return true;
}

 ?>  
<?php
// ==================== دوال الإعلانات ====================

/**
 * التحقق من إمكانية إنشاء إعلان (يجب أن يكون merchant)
 */
function canCreateAd($customerId) {
    return isMerchant($customerId);
}

/**
 * التحقق من أن المستخدم تاجر
 */
function isMerchant($customerId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE created_by = ? AND store_type = 'customer' LIMIT 1");
    $stmt->execute([$customerId]);
    return $stmt->fetchColumn() > 0;
}

/**
 * جلب إعلانات المستخدم
 */
function getUserAds($customerId) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT a.*, p.title as product_title 
        FROM ads a 
        LEFT JOIN products p ON a.product_id = p.id 
        WHERE a.owner_id = ? 
        ORDER BY a.created_at DESC
    ");
    $stmt->execute([$customerId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * جلب منتجات التاجر
 */
function getMerchantProducts($customerId) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT id, title 
        FROM products 
        WHERE created_by = ? AND store_type = 'customer' AND is_active = 1
        ORDER BY title
    ");
    $stmt->execute([$customerId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * إنشاء إعلان جديد
 */
function createAd($data) {
    global $pdo;

    try {
        $pdo->beginTransaction();

        // التحقق من الرصيد الكافي
        if ($data['payment_method'] === 'points') {
            $pointsStmt = $pdo->prepare("SELECT points FROM customer_points WHERE customer_id = ?");
            $pointsStmt->execute([$data['owner_id']]);
            $customerPoints = $pointsStmt->fetchColumn();
            
            if ($customerPoints < $data['points_cost']) {
                throw new Exception('النقاط غير كافية.');
            }
        }

        if ($data['payment_method'] === 'wallet') {
            $walletStmt = $pdo->prepare("SELECT balance FROM customer_wallets WHERE customer_id = ?");
            $walletStmt->execute([$data['owner_id']]);
            $walletBalance = $walletStmt->fetchColumn();
            
            if ($walletBalance < $data['wallet_cost']) {
                throw new Exception('رصيد المحفظة غير كافٍ.');
            }
        }

        // إدخال الإعلان
        $sql = "INSERT INTO ads (
            owner_id, type, content_url, title, description, product_id,
            discount_increase, points_cost, wallet_cost, payment_method,
            position, start_date, end_date, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $data['owner_id'],
            $data['type'],
            $data['content_url'],
            $data['title'],
            $data['description'],
            $data['product_id'],
            $data['discount_increase'],
            $data['points_cost'],
            $data['wallet_cost'],
            $data['payment_method'],
            $data['position'],
            $data['start_date'],
            $data['end_date']
        ]);

        $adId = $pdo->lastInsertId();

        // خصم التكلفة إذا كانت مدفوعة بالنقاط أو المحفظة
        if ($data['payment_method'] === 'points' && $data['points_cost'] > 0) {
            // خصم النقاط
            $pdo->prepare("UPDATE customer_points SET points = points - ? WHERE customer_id = ?")
                ->execute([$data['points_cost'], $data['owner_id']]);
                
            // تسجيل معاملة النقاط
            $pdo->prepare("INSERT INTO point_transactions (customer_id, points, type, description) VALUES (?, ?, 'spend', ?)")
                ->execute([$data['owner_id'], $data['points_cost'], 'دفع مقابل إعلان #' . $adId]);
        }

        if ($data['payment_method'] === 'wallet' && $data['wallet_cost'] > 0) {
            // خصم من المحفظة
            $pdo->prepare("UPDATE customer_wallets SET balance = balance - ? WHERE customer_id = ?")
                ->execute([$data['wallet_cost'], $data['owner_id']]);
                
            // تسجيل معاملة المحفظة
            $pdo->prepare("INSERT INTO wallet_transactions (customer_id, amount, type, description, status) VALUES (?, ?, 'withdrawal', ?, 'completed')")
                ->execute([$data['owner_id'], $data['wallet_cost'], 'دفع مقابل إعلان #' . $adId]);
        }

        $pdo->commit();
        return ['success' => true, 'ad_id' => $adId];

    } catch (Exception $e) {
        $pdo->rollBack();
        return ['success' => false, 'message' => 'فشل إنشاء الإعلان: ' . $e->getMessage()];
    }
}

/**
 * رفع ملف الإعلان
 */
function uploadFile($file) {
    // تحديد المجلد المستهدف
    $uploadDir = '../uploads/ads/';
    
    // التأكد من وجود المجلد
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // التحقق من وجود ملف
    if (!isset($file['name']) || empty($file['name'])) {
        return false;
    }
    
    $fileName = basename($file['name']);
    $fileSize = $file['size'];
    $fileTmp = $file['tmp_name'];
    $fileType = $file['type'];
    $fileError = $file['error'];
    
    // التحقق من الأخطاء
    if ($fileError !== UPLOAD_ERR_OK) {
        return false;
    }
    
    // التحقق من نوع الملف
    $allowedImages = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $allowedVideos = ['video/mp4', 'video/webm', 'video/ogg'];
    $allowedTypes = array_merge($allowedImages, $allowedVideos);
    
    if (!in_array($fileType, $allowedTypes)) {
        return false;
    }
    
    // التحقق من حجم الملف
    $maxImageSize = 10 * 1024 * 1024; // 10 MB
    $maxVideoSize = 50 * 1024 * 1024; // 50 MB
    
    $maxSize = in_array($fileType, $allowedImages) ? $maxImageSize : $maxVideoSize;
    
    if ($fileSize > $maxSize) {
        return false;
    }
    
    // إنشاء اسم فريد للملف
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $uniqueName = uniqid('ad_', true) . '.' . $fileExt;
    $uploadPath = $uploadDir . $uniqueName;
    
    // رفع الملف
    if (move_uploaded_file($fileTmp, $uploadPath)) {
        return 'uploads/ads/' . $uniqueName; // إرجاع المسار النسبي
    } else {
        return false;
    }
}

/**
 * تنظيف المدخلات
 */
function cleanInput1($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}
?>