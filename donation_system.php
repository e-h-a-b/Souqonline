<?php
require_once 'functions.php';

class DonationSystem {
    
    /**
     * حساب القيمة الإجمالية المطلوبة للتبرع (مصحح)
     */
    public static function calculateRequiredDonation($storeId = null) {
        global $pdo;
        
        try {
            // حساب القيمة الإجمالية للمنتجات بشكل واقعي
            $sql = "SELECT 
                    COUNT(*) as product_count,
                    AVG(price) as avg_price,
                    SUM(price * stock) as total_value,
                    SUM(stock) as total_stock
                    FROM products 
                    WHERE is_active = 1 AND stock > 0";
            
            $params = [];
            
            if ($storeId) {
                $sql .= " AND created_by = ?";
                $params[] = $storeId;
            }
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $productCount = $result['product_count'] ?? 0;
            $avgPrice = $result['avg_price'] ?? 0;
            $totalValue = $result['total_value'] ?? 0;
            $totalStock = $result['total_stock'] ?? 0;
            
            if ($productCount == 0) {
                return [
                    'required_amount' => 0,
                    'total_value' => 0,
                    'product_count' => 0,
                    'avg_price' => 0,
                    'calculation_method' => 'no_products'
                ];
            }
            
            // طرق حساب أكثر واقعية:
            $calculationMethods = [
                // 1. نسبة صغيرة من القيمة الإجمالية (0.1% - 0.5%)
                'percentage' => $totalValue * 0.002, // 0.2%
                
                // 2. حسب عدد المنتجات ومتوسط السعر
                'per_product' => $productCount * $avgPrice * 0.05, // 5% من متوسط السعر لكل منتج
                
                // 3. حسب المخزون الكلي
                'per_stock' => min($totalStock * 2, 10000), // 2 جنيه لكل قطعة بحد أقصى 10,000
                
                // 4. مبلغ ثابت حسب عدد المنتجات
                'fixed' => min($productCount * 100, 5000) // 100 جنيه لكل منتج بحد أقصى 5,000
            ];
            
            // أخذ متوسط الطرق المختلفة
            $requiredDonation = array_sum($calculationMethods) / count($calculationMethods);
            
            // تطبيق عوامل التعديل
            $ratingFactor = self::getRatingFactor($storeId);
            $demandFactor = self::getDemandFactor($storeId);
            
            $finalAmount = $requiredDonation * $ratingFactor * $demandFactor;
            
            // تحديد حدود واقعية
            $minAmount = 50; // حد أدنى 50 جنيه
            $maxAmount = 10000; // حد أقصى 10,000 جنيه
            
            $finalAmount = max($minAmount, min($maxAmount, $finalAmount));
            
            return [
                'required_amount' => round($finalAmount, 2),
                'total_value' => round($totalValue, 2),
                'product_count' => $productCount,
                'avg_price' => round($avgPrice, 2),
                'total_stock' => $totalStock,
                'rating_factor' => $ratingFactor,
                'demand_factor' => $demandFactor,
                'calculation_methods' => $calculationMethods,
                'calculation_method' => 'weighted_average'
            ];
            
        } catch (Exception $e) {
            error_log("Donation calculation error: " . $e->getMessage());
            return [
                'required_amount' => 500, // قيمة افتراضية معقولة
                'total_value' => 0,
                'product_count' => 0,
                'calculation_method' => 'error_default'
            ];
        }
    }
    
    /**
     * عامل التقييم (مصحح)
     */
    private static function getRatingFactor($storeId = null) {
        global $pdo;
        
        $sql = "SELECT 
                AVG(r.rating) as avg_rating, 
                COUNT(r.id) as review_count,
                COUNT(DISTINCT r.customer_id) as unique_reviewers
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
        
        $avgRating = $result['avg_rating'] ?? 3.0;
        $reviewCount = $result['review_count'] ?? 0;
        $uniqueReviewers = $result['unique_reviewers'] ?? 0;
        
        // عامل التقييم: كلما زاد التقييم قل المطلوب (المنتجات الجيدة تحتاج دعم أقل)
        $baseFactor = 1.0;
        
        // تعديل حسب متوسط التقييم
        if ($avgRating >= 4.5) {
            $baseFactor *= 0.6; // منتجات ممتازة - تحتاج دعم أقل
        } elseif ($avgRating >= 4.0) {
            $baseFactor *= 0.8;
        } elseif ($avgRating >= 3.0) {
            $baseFactor *= 1.0;
        } else {
            $baseFactor *= 1.2; // منتجات ضعيفة التقييم - تحتاج دعم أكثر
        }
        
        // تعديل حسب عدد المراجعات
        if ($reviewCount > 100) {
            $baseFactor *= 0.7; // منتجات مشهورة - تحتاج دعم أقل
        } elseif ($reviewCount > 50) {
            $baseFactor *= 0.85;
        } elseif ($reviewCount > 10) {
            $baseFactor *= 0.95;
        }
        
        return max(0.3, min(2.0, $baseFactor)); // حدود معقولة
    }
    
    /**
     * عامل الطلب (مصحح)
     */
    private static function getDemandFactor($storeId = null) {
        global $pdo;
        
        $sql = "SELECT 
                SUM(orders_count) as total_orders, 
                SUM(views) as total_views,
                AVG(orders_count) as avg_orders,
                COUNT(*) as product_count
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
        $productCount = $result['product_count'] ?? 0;
        
        // حساب معدل التحويل
        $conversionRate = ($totalViews > 0) ? ($totalOrders / $totalViews) : 0;
        
        $baseFactor = 1.0;
        
        // المنتجات ذات التحويل العالي تحتاج دعم أقل
        if ($conversionRate > 0.1) {
            $baseFactor *= 0.6; // طلب عالي
        } elseif ($conversionRate > 0.05) {
            $baseFactor *= 0.8;
        } elseif ($conversionRate > 0.01) {
            $baseFactor *= 1.0;
        } else {
            $baseFactor *= 1.3; // طلب منخفض - تحتاج دعم أكثر
        }
        
        // تعديل حسب متوسط الطلبات لكل منتج
        if ($avgOrders > 50) {
            $baseFactor *= 0.7;
        } elseif ($avgOrders > 20) {
            $baseFactor *= 0.85;
        } elseif ($avgOrders > 5) {
            $baseFactor *= 0.95;
        }
        
        return max(0.4, min(1.8, $baseFactor));
    }
    
    /**
     * توزيع التبرع على المنتجات (مصحح)
     */
    public static function distributeDonation($donationAmount, $storeId = null, $method = 'equal') {
        global $pdo;
        
        try {
            // جلب المنتجات النشطة
            $sql = "SELECT id, title, price, stock, orders_count, views, rating_avg 
                    FROM products 
                    WHERE is_active = 1 AND stock > 0";
            
            $params = [];
            
            if ($storeId) {
                $sql .= " AND created_by = ?";
                $params[] = $storeId;
            }
            
            // حد أقصى 50 منتج لتجنب التوزيع على عدد كبير جداً
            $sql .= " ORDER BY orders_count DESC, views DESC LIMIT 50";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($products)) {
                return ['success' => false, 'message' => 'لا توجد منتجات نشطة'];
            }
            
            $distribution = [];
            $totalWeight = 0;
            
            foreach ($products as $product) {
                switch ($method) {
                    case 'popularity':
                        // الوزن حسب الشعبية (طلبات + مشاهدات + تقييم)
                        $weight = ($product['orders_count'] * 0.5) + 
                                 (log($product['views'] + 1) * 0.3) + 
                                 ($product['rating_avg'] * 0.2);
                        break;
                        
                    case 'price':
                        // الوزن حسب السعر والمخزون
                        $weight = $product['price'] * log($product['stock'] + 1);
                        break;
                        
                    case 'equal':
                    default:
                        $weight = 1;
                        break;
                }
                
                // تجنب الأوزان الصغيرة جداً
                $weight = max(0.1, $weight);
                
                $distribution[$product['id']] = [
                    'product' => $product,
                    'weight' => $weight,
                    'original_price' => $product['price']
                ];
                
                $totalWeight += $weight;
            }
            
            // توزيع المبلغ على المنتجات
            $results = [];
            $totalDiscount = 0;
            
            foreach ($distribution as $productId => $data) {
                $percentage = $data['weight'] / $totalWeight;
                $allocatedAmount = $donationAmount * $percentage;
                
                // حساب نسبة الخصم (بحدود واقعية)
                $maxReasonableDiscount = $data['original_price'] * 0.3; // لا يزيد عن 30%
                $calculatedDiscount = min($allocatedAmount, $maxReasonableDiscount);
                
                // التأكد من أن الخصم معقول
                $discountPercentage = ($calculatedDiscount / $data['original_price']) * 100;
                $discountPercentage = min(30, max(1, $discountPercentage)); // بين 1% و 30%
                
                $newPrice = $data['original_price'] * (1 - ($discountPercentage / 100));
                $actualAllocated = $data['original_price'] * ($discountPercentage / 100);
                
                $results[$productId] = [
                    'product_title' => $data['product']['title'],
                    'original_price' => $data['original_price'],
                    'discount_percentage' => round($discountPercentage, 1),
                    'new_price' => round($newPrice, 2),
                    'allocated_amount' => round($actualAllocated, 2),
                    'discount_amount' => round($data['original_price'] - $newPrice, 2),
                    'weight' => round($data['weight'], 2),
                    'percentage' => round($percentage * 100, 1)
                ];
                
                $totalDiscount += $actualAllocated;
            }
            
            // إذا كان هناك توفير من الحد الأقصى للخصم، أعد توزيعه
            $remainingAmount = $donationAmount - $totalDiscount;
            if ($remainingAmount > 1) {
                $redistributed = self::redistributeRemainingAmount($results, $remainingAmount);
                $results = $redistributed['results'];
                $totalDiscount = $redistributed['total_discount'];
            }
            
            return [
                'success' => true,
                'distribution' => $results,
                'total_products' => count($products),
                'total_donation' => $donationAmount,
                'total_discount' => $totalDiscount,
                'remaining_amount' => $donationAmount - $totalDiscount,
                'distribution_method' => $method,
                'avg_discount' => $totalDiscount / count($products)
            ];
            
        } catch (Exception $e) {
            error_log("Donation distribution error: " . $e->getMessage());
            return ['success' => false, 'message' => 'خطأ في توزيع التبرع'];
        }
    }
    
    /**
     * إعادة توزيع المبلغ المتبقي
     */
    private static function redistributeRemainingAmount($results, $remainingAmount) {
        $redistribution = $results;
        $totalDiscount = 0;
        
        foreach ($redistribution as $productId => &$data) {
            // إضافة جزء من المبلغ المتبقي لكل منتج
            $extraAmount = $remainingAmount / count($redistribution);
            $newAllocated = $data['allocated_amount'] + $extraAmount;
            
            // حساب نسبة الخصم الجديدة
            $maxDiscount = $data['original_price'] * 0.3; // لا يزيد عن 30%
            $actualAllocated = min($newAllocated, $maxDiscount);
            
            $discountPercentage = ($actualAllocated / $data['original_price']) * 100;
            $discountPercentage = min(30, $discountPercentage);
            
            $data['discount_percentage'] = round($discountPercentage, 1);
            $data['new_price'] = round($data['original_price'] * (1 - ($discountPercentage / 100)), 2);
            $data['allocated_amount'] = round($actualAllocated, 2);
            $data['discount_amount'] = round($data['original_price'] - $data['new_price'], 2);
            
            $totalDiscount += $actualAllocated;
        }
        
        return [
            'results' => $redistribution,
            'total_discount' => $totalDiscount
        ];
    }
    
    // باقي الدوال تبقى كما هي مع تعديلات بسيطة...
    
    /**
     * الحصول على المتاجر المتاحة للتبرع (مصحح)
     */
    public static function getStoresForDonation() {
        global $pdo;
        
        try {
            $stmt = $pdo->prepare("
                SELECT DISTINCT p.created_by as store_id, 
                       CONCAT(c.first_name, ' ', c.last_name) as store_name,
                       c.email as store_email,
                       COUNT(p.id) as product_count,
                       AVG(p.price) as avg_price,
                       SUM(p.price * p.stock) as total_value
                FROM products p
                JOIN customers c ON p.created_by = c.id
                WHERE p.is_active = 1 AND p.stock > 0
                GROUP BY p.created_by
                HAVING product_count > 0 AND total_value > 0
                ORDER BY product_count DESC
                LIMIT 20
            ");
            
            $stmt->execute();
            $stores = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // حساب القيمة المطلوبة لكل متجر
            foreach ($stores as &$store) {
                $required = self::calculateRequiredDonation($store['store_id']);
                $store['required_amount'] = $required['required_amount'];
                $store['calculation_note'] = self::getCalculationNote($required);
            }
            
            // إضافة المتجر العام (جميع المتاجر)
            $generalRequired = self::calculateRequiredDonation();
            $generalStore = [
                'store_id' => null,
                'store_name' => 'جميع المتاجر (عام)',
                'store_email' => null,
                'product_count' => array_sum(array_column($stores, 'product_count')),
                'total_value' => array_sum(array_column($stores, 'total_value')),
                'required_amount' => $generalRequired['required_amount'],
                'calculation_note' => self::getCalculationNote($generalRequired)
            ];
            
            array_unshift($stores, $generalStore);
            
            return $stores;
            
        } catch (Exception $e) {
            error_log("Get stores for donation error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * الحصول على ملاحظة توضيحية للحساب
     */
    private static function getCalculationNote($calculationData) {
        $productCount = $calculationData['product_count'];
        $avgPrice = $calculationData['avg_price'];
        
        if ($productCount <= 10) {
            return "متجر صغير - {$productCount} منتجات";
        } elseif ($productCount <= 30) {
            return "متجر متوسط - {$productCount} منتجات";
        } else {
            return "متجر كبير - {$productCount} منتجات";
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
                $params[] = $storeId;
            }
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($storeId ? [$storeId] : []);
            $productsStats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $stats['affected_products'] = $productsStats['affected_products'] ?? 0;
            
            return $stats;
            
        } catch (Exception $e) {
            error_log("Get donation stats error: " . $e->getMessage());
            return [
                'total_donations' => 0,
                'total_amount' => 0,
                'avg_donation' => 0,
                'max_donation' => 0,
                'min_donation' => 0,
                'unique_donors' => 0,
                'affected_products' => 0
            ];
        }
    }

    /**
     * الحصول على آخر التبرعات
     */
    public static function getRecentDonations($limit = 5) {
        global $pdo;
        
        try {
            $stmt = $pdo->prepare("
                SELECT d.*, c.first_name, c.last_name, 
                       COUNT(dd.id) as affected_products
                FROM donations d
                LEFT JOIN customers c ON d.store_id = c.id
                LEFT JOIN donation_discounts dd ON d.id = dd.donation_id
                WHERE d.status = 'completed'
                GROUP BY d.id
                ORDER BY d.created_at DESC
                LIMIT ?
            ");
            
            $stmt->execute([$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Get recent donations error: " . $e->getMessage());
            return [];
        }
    }
}

// دوال مساعدة
function formatDonationAmount($amount) {
    if ($amount >= 1000) {
        return number_format($amount / 1000, 1) . ' ألف';
    }
    return number_format($amount, 0);
}

function getDonationImpactLevel($amount) {
    if ($amount < 100) return 'منخفض';
    if ($amount < 500) return 'متوسط';
    if ($amount < 1000) return 'جيد';
    return 'عالي';
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    ];
    
    foreach ($tables as $tableSql) {
        try {
            $pdo->exec($tableSql);
        } catch (Exception $e) {
            error_log("Create table error: " . $e->getMessage());
        }
    }
    
    // إضافة الحقول إذا لم تكن موجودة
    try {
        $pdo->exec("ALTER TABLE products 
            ADD COLUMN IF NOT EXISTS donation_discount_applied BOOLEAN DEFAULT 0,
            ADD COLUMN IF NOT EXISTS donation_id INT NULL");
    } catch (Exception $e) {
        // تجاهل الخطأ إذا كانت الحقول موجودة مسبقاً
    }
}

 
?>