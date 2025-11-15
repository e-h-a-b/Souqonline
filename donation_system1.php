<?php
require_once 'functions.php';
// إضافة الإعدادات الخاصة بالتبرعات
$donationSettings = [
    'donation_enabled' => '1',
    'donation_min_amount' => '10',
    'donation_max_discount' => '50',
    'donation_calculation_percentage' => '1'
];

foreach ($donationSettings as $key => $value) {
    if (!getSetting($key)) {
        updateSetting($key, $value);
    }
}
class DonationSystem {
    
    /**
     * حساب القيمة الإجمالية المطلوبة للتبرع
     */
    public static function calculateRequiredDonation($storeId = null) {
        global $pdo;
        
        try {
            // حساب القيمة الإجمالية للمنتجات
            $sql = "SELECT SUM(price * stock) as total_value, COUNT(*) as product_count 
                    FROM products 
                    WHERE is_active = 1";
            
            $params = [];
            
            if ($storeId) {
                $sql .= " AND created_by = ?";
                $params[] = $storeId;
            }
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $totalValue = $result['total_value'] ?? 0;
            $productCount = $result['product_count'] ?? 0;
            
            if ($totalValue == 0 || $productCount == 0) {
                return 0;
            }
            
            // حساب القيمة المطلوبة بناء على متوسط التقييمات والطلبات
            $ratingFactor = self::getRatingFactor($storeId);
            $demandFactor = self::getDemandFactor($storeId);
            
            // الصيغة: 1% من القيمة الإجمالية مضروبة في عوامل التقييم والطلب
            $requiredDonation = $totalValue * 0.01 * $ratingFactor * $demandFactor;
            
            return [
                'required_amount' => round($requiredDonation, 2),
                'total_value' => $totalValue,
                'product_count' => $productCount,
                'rating_factor' => $ratingFactor,
                'demand_factor' => $demandFactor
            ];
            
        } catch (Exception $e) {
            error_log("Donation calculation error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * عامل التقييم بناء على آراء المستخدمين
     */
    private static function getRatingFactor($storeId = null) {
        global $pdo;
        
        $sql = "SELECT AVG(r.rating) as avg_rating, COUNT(r.id) as review_count 
                FROM reviews r 
                JOIN products p ON r.product_id = p.id 
                WHERE r.is_approved = 1";
        
        $params = [];
        
        if ($storeId) {
            $sql .= " AND p.created_by = ?";
            $params[] = $storeId;
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $avgRating = $result['avg_rating'] ?? 3;
        $reviewCount = $result['review_count'] ?? 0;
        
        // عامل التقييم: كلما زاد التقييم وعدد المراجعات، قل المطلوب
        $ratingFactor = max(0.5, 1.5 - ($avgRating / 10));
        
        // تعديل بناء على عدد المراجعات
        if ($reviewCount > 100) {
            $ratingFactor *= 0.8;
        } elseif ($reviewCount > 50) {
            $ratingFactor *= 0.9;
        }
        
        return $ratingFactor;
    }
    
    /**
     * عامل الطلب بناء على المبيعات والمشاهدات
     */
    private static function getDemandFactor($storeId = null) {
        global $pdo;
        
        $sql = "SELECT SUM(orders_count) as total_orders, SUM(views) as total_views, 
                       AVG(orders_count) as avg_orders 
                FROM products 
                WHERE is_active = 1";
        
        $params = [];
        
        if ($storeId) {
            $sql .= " AND created_by = ?";
            $params[] = $storeId;
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $totalOrders = $result['total_orders'] ?? 0;
        $totalViews = $result['total_views'] ?? 0;
        $avgOrders = $result['avg_orders'] ?? 0;
        
        // عامل الطلب: كلما زاد الطلب، قل المطلوب (لأن المنتج مطلوب بالفعل)
        $conversionRate = ($totalViews > 0) ? ($totalOrders / $totalViews) : 0;
        $demandFactor = max(0.7, 1.3 - ($conversionRate * 10));
        
        return $demandFactor;
    }
    
    /**
     * توزيع التبرع على المنتجات
     */
    public static function distributeDonation($donationAmount, $storeId = null, $method = 'equal') {
        global $pdo;
        
        try {
            // جلب المنتجات النشطة
            $sql = "SELECT id, price, stock, orders_count, views, rating_avg 
                    FROM products 
                    WHERE is_active = 1";
            
            $params = [];
            
            if ($storeId) {
                $sql .= " AND created_by = ?";
                $params[] = $storeId;
            }
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($products)) {
                return ['success' => false, 'message' => 'لا توجد منتجات نشطة'];
            }
            
            $distribution = [];
            $totalWeight = 0;
            
            // حساب الأوزان بناء على طريقة التوزيع
            foreach ($products as $product) {
                switch ($method) {
                    case 'popularity':
                        $weight = ($product['orders_count'] * 0.5) + ($product['views'] * 0.3) + ($product['rating_avg'] * 0.2);
                        break;
                    case 'price':
                        $weight = $product['price'] * $product['stock'];
                        break;
                    case 'equal':
                    default:
                        $weight = 1;
                        break;
                }
                
                $distribution[$product['id']] = [
                    'product' => $product,
                    'weight' => $weight,
                    'original_price' => $product['price']
                ];
                
                $totalWeight += $weight;
            }
            
            // توزيع المبلغ على المنتجات
            $remainingAmount = $donationAmount;
            $results = [];
            
            foreach ($distribution as $productId => $data) {
                $percentage = $data['weight'] / $totalWeight;
                $allocatedAmount = $donationAmount * $percentage;
                
                // حساب نسبة الخصم (لا تتجاوز 50%)
                $discountPercentage = min(50, ($allocatedAmount / $data['original_price']) * 100);
                $newPrice = $data['original_price'] * (1 - ($discountPercentage / 100));
                
                $results[$productId] = [
                    'original_price' => $data['original_price'],
                    'discount_percentage' => round($discountPercentage, 2),
                    'new_price' => round($newPrice, 2),
                    'allocated_amount' => round($allocatedAmount, 2),
                    'weight' => round($data['weight'], 2),
                    'percentage' => round($percentage * 100, 2)
                ];
                
                $remainingAmount -= $allocatedAmount;
            }
            
            // توزيع أي مبلغ متبقي
            if ($remainingAmount > 0.01) {
                $productsCount = count($results);
                $extraPerProduct = $remainingAmount / $productsCount;
                
                foreach ($results as $productId => &$result) {
                    $result['allocated_amount'] += $extraPerProduct;
                    
                    // إعادة حساب الخصم
                    $discountPercentage = min(50, ($result['allocated_amount'] / $result['original_price']) * 100);
                    $result['discount_percentage'] = round($discountPercentage, 2);
                    $result['new_price'] = round($result['original_price'] * (1 - ($discountPercentage / 100)), 2);
                }
            }
            
            return [
                'success' => true,
                'distribution' => $results,
                'total_products' => count($products),
                'total_donation' => $donationAmount,
                'distribution_method' => $method
            ];
            
        } catch (Exception $e) {
            error_log("Donation distribution error: " . $e->getMessage());
            return ['success' => false, 'message' => 'خطأ في توزيع التبرع'];
        }
    }
    
    /**
     * تطبيق الخصومات على المنتجات
     */
    public static function applyDonationDiscounts($distribution, $donationId) {
        global $pdo;
        
        try {
            $pdo->beginTransaction();
            
            foreach ($distribution as $productId => $data) {
                // تحديث سعر المنتج
                $stmt = $pdo->prepare("
                    UPDATE products 
                    SET price = ?, discount_percentage = ?, final_price = ?,
                        donation_discount_applied = 1, donation_id = ?
                    WHERE id = ?
                ");
                
                $stmt->execute([
                    $data['original_price'],
                    $data['discount_percentage'],
                    $data['new_price'],
                    $donationId,
                    $productId
                ]);
                
                // تسجيل عملية الخصم
                $stmt = $pdo->prepare("
                    INSERT INTO donation_discounts 
                    (donation_id, product_id, original_price, discount_percentage, 
                     new_price, allocated_amount, applied_at) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW())
                ");
                
                $stmt->execute([
                    $donationId,
                    $productId,
                    $data['original_price'],
                    $data['discount_percentage'],
                    $data['new_price'],
                    $data['allocated_amount']
                ]);
            }
            
            $pdo->commit();
            return true;
            
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Apply donation discounts error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * تسجيل التبرع
     */
    public static function recordDonation($donorData, $amount, $storeId = null, $distributionMethod = 'equal') {
        global $pdo;
        
        try {
            $pdo->beginTransaction();
            
            // إنشاء رقم تبرع فريد
            $donationNumber = 'DON' . date('Ymd') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
            // تسجيل التبرع
            $stmt = $pdo->prepare("
                INSERT INTO donations 
                (donation_number, donor_name, donor_type, donor_email, donor_phone, 
                 amount, store_id, distribution_method, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
            ");
            
            $stmt->execute([
                $donationNumber,
                $donorData['name'],
                $donorData['type'],
                $donorData['email'] ?? null,
                $donorData['phone'] ?? null,
                $amount,
                $storeId,
                $distributionMethod
            ]);
            
            $donationId = $pdo->lastInsertId();
            
            // توزيع التبرع
            $distribution = self::distributeDonation($amount, $storeId, $distributionMethod);
            
            if (!$distribution['success']) {
                throw new Exception($distribution['message']);
            }
            
            // تطبيق الخصومات
            $applied = self::applyDonationDiscounts($distribution['distribution'], $donationId);
            
            if (!$applied) {
                throw new Exception('فشل في تطبيق الخصومات');
            }
            
            // تحديث حالة التبرع
            $stmt = $pdo->prepare("UPDATE donations SET status = 'completed' WHERE id = ?");
            $stmt->execute([$donationId]);
            
            $pdo->commit();
            
            return [
                'success' => true,
                'donation_id' => $donationId,
                'donation_number' => $donationNumber,
                'distribution' => $distribution
            ];
            
        } catch (Exception $e) {
            $pdo->rollBack();
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * الحصول على المتاجر المتاحة للتبرع
     */
    public static function getStoresForDonation() {
        global $pdo;
        
        try {
            $stmt = $pdo->prepare("
                SELECT DISTINCT p.created_by as store_id, 
                       CONCAT(c.first_name, ' ', c.last_name) as store_name,
                       c.email as store_email,
                       COUNT(p.id) as product_count,
                       SUM(p.price * p.stock) as total_value
                FROM products p
                JOIN customers c ON p.created_by = c.id
                WHERE p.is_active = 1 AND p.store_type = 'customer'
                GROUP BY p.created_by
                HAVING product_count > 0
                ORDER BY total_value DESC
            ");
            
            $stmt->execute();
            $stores = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // إضافة المتجر العام (جميع المتاجر)
            $generalStore = [
                'store_id' => null,
                'store_name' => 'جميع المتاجر (عام)',
                'store_email' => null,
                'product_count' => array_sum(array_column($stores, 'product_count')),
                'total_value' => array_sum(array_column($stores, 'total_value'))
            ];
            
            array_unshift($stores, $generalStore);
            
            return $stores;
            
        } catch (Exception $e) {
            error_log("Get stores for donation error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * الحصول على إحصائيات التبرعات
     */
    public static function getDonationStats($storeId = null) {
        global $pdo;
        
        try {
            $sql = "SELECT 
                    COUNT(*) as total_donations,
                    SUM(amount) as total_amount,
                    AVG(amount) as avg_donation,
                    MAX(amount) as max_donation,
                    MIN(amount) as min_donation,
                    COUNT(DISTINCT donor_email) as unique_donors
                    FROM donations 
                    WHERE status = 'completed'";
            
            $params = [];
            
            if ($storeId) {
                $sql .= " AND store_id = ?";
                $params[] = $storeId;
            }
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // إحصائيات المنتجات المتأثرة
            $sql = "SELECT COUNT(DISTINCT product_id) as affected_products 
                    FROM donation_discounts 
                    JOIN donations ON donation_discounts.donation_id = donations.id 
                    WHERE donations.status = 'completed'";
            
            if ($storeId) {
                $sql .= " AND donations.store_id = ?";
            }
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $productsStats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $stats['affected_products'] = $productsStats['affected_products'] ?? 0;
            
            return $stats;
            
        } catch (Exception $e) {
            error_log("Get donation stats error: " . $e->getMessage());
            return [];
        }
    }
}

// إنشاء الجداول المطلوبة إذا لم تكن موجودة
function createDonationTables() {
    global $pdo;
    
    $tables = [
        "CREATE TABLE IF NOT EXISTS donations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            donation_number VARCHAR(50) UNIQUE NOT NULL,
            donor_name VARCHAR(255) NOT NULL,
            donor_type ENUM('individual', 'company', 'organization') NOT NULL,
            donor_email VARCHAR(255),
            donor_phone VARCHAR(50),
            amount DECIMAL(10,2) NOT NULL,
            store_id INT NULL,
            distribution_method ENUM('equal', 'popularity', 'price') DEFAULT 'equal',
            status ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending',
            created_at DATETIME,
            updated_at DATETIME,
            FOREIGN KEY (store_id) REFERENCES customers(id) ON DELETE SET NULL
        )",
        
        "CREATE TABLE IF NOT EXISTS donation_discounts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            donation_id INT NOT NULL,
            product_id INT NOT NULL,
            original_price DECIMAL(10,2) NOT NULL,
            discount_percentage DECIMAL(5,2) NOT NULL,
            new_price DECIMAL(10,2) NOT NULL,
            allocated_amount DECIMAL(10,2) NOT NULL,
            applied_at DATETIME,
            FOREIGN KEY (donation_id) REFERENCES donations(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
        )",
        
        "ALTER TABLE products 
         ADD COLUMN IF NOT EXISTS donation_discount_applied BOOLEAN DEFAULT 0,
         ADD COLUMN IF NOT EXISTS donation_id INT NULL,
         ADD FOREIGN KEY IF NOT EXISTS (donation_id) REFERENCES donations(id) ON DELETE SET NULL"
    ];
    
    foreach ($tables as $tableSql) {
        try {
            $pdo->exec($tableSql);
        } catch (Exception $e) {
            error_log("Create table error: " . $e->getMessage());
        }
    }
}

// استدعاء إنشاء الجداول عند تحميل الملف
createDonationTables();
?>