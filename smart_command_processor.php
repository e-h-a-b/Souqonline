<?php
class SmartCommandProcessor {
    private $db;
    private $userId;
    
    public function __construct($database, $userId) {
        $this->db = $database;
        $this->userId = $userId;
    }
    
    // معالجة الأوامر النصية
public function processCommand($commandText) {
    $commandText = trim($commandText);
    $response = "";
    $actionTaken = false;
    
    // بدء الجلسة إذا لم تكن بدأت
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    // تحليل الأمر وتحديد النوع
    $commandType = $this->analyzeCommand($commandText);
    
    switch($commandType['type']) {
        case 'price_track':
            $result = $this->handlePriceTracking($commandType['data']);
            $response = $result['message'];
            $actionTaken = $result['success'];
            break;
                
            case 'schedule':
                $result = $this->handleScheduling($commandType['data']);
                $response = $result['message'];
                $actionTaken = $result['success'];
                break;
                
            case 'availability':
                $result = $this->handleAvailability($commandType['data']);
                $response = $result['message'];
                $actionTaken = $result['success'];
                break;
                
            case 'bundle':
                $result = $this->handleBundle($commandType['data']);
                $response = $result['message'];
                $actionTaken = $result['success'];
                break;
                
            case 'quick_purchase':
                $result = $this->handleQuickPurchase($commandType['data']);
                $response = $result['message'];
                $actionTaken = $result['success'];
                break;
                
            case 'list_management':
                $result = $this->handleListManagement($commandType['data']);
                $response = $result['message'];
                $actionTaken = $result['success'];
                break;
                
            case 'stats':
                $result = $this->handleStats($commandType['data']);
                $response = $result['message'];
                $actionTaken = $result['success'];
                break;
                
            default:
                $response = "لم أفهم الأمر بشكل كامل. يمكنك صياغته بشكل أوضح مثل: 'تتبع سعر iPhone 15 عندما يصبح أقل من 3000'";
        }
        
        // تسجيل تنفيذ الأمر
        if ($actionTaken && isset($result['command_id'])) {
            $this->logExecution($result['command_id'], $response);
        }
        
        return $response;
    }
    
    // تحليل النص وتحديد نوع الأمر
    private function analyzeCommand($text) {
        $text = mb_strtolower($text, 'UTF-8');
        
        // 1- أوامر تتبع الأسعار
        if (preg_match('/(تتبع|راقب|اشترِ|اطلب).*سعر.*(عندما|إذا|حين).*(أقل|ينخفض|يصبح).*(\d+)/u', $text) || 
            preg_match('/(تتبع|راقب).*سعر.*(\d+)/u', $text)) {
            return $this->parsePriceTracking($text);
        }
        
        // 2- أوامر الجدولة الزمنية
        if (preg_match('/(اشترِ|اطلب).*(كل|يوم|أسبوع|شهر|في)/u', $text)) {
            return $this->parseScheduling($text);
        }
        
        // 3- أوامر التوفر والمخزون
        if (preg_match('/(اشترِ|اطلب).*(عندما|إذا).*(يتوفر|متاح|مخزون)/u', $text)) {
            return $this->parseAvailability($text);
        }
        
        // 4- أوامر قوائم المشتريات
        if (preg_match('/(قائمة|لائحة).*(اشترِ|نفذ|اطلب)/u', $text)) {
            return $this->parseListManagement($text);
        }
        
        // 5- أوامر الشراء السريع
        if (preg_match('/(اشترِ|اطلب).*(الآن|فوراً|سريع)/u', $text)) {
            return $this->parseQuickPurchase($text);
        }
        
        // 6- أوامر الإحصائيات
        if (preg_match('/(اعرض|أظهر|ما هي).*(إحصائيات|صفقات|توفير|أوامر)/u', $text)) {
            return $this->parseStats($text);
        }
        
        return ['type' => 'unknown', 'data' => []];
    }
    
    // معالجة تتبع الأسعار
private function handlePriceTracking($data) {
    $productName = $data['product'];
    $targetPrice = $data['target_price'];
    
    // البحث عن المنتج في قاعدة البيانات
    $product = $this->findProduct($productName);
    
    if (!$product) {
        return [
            'success' => false,
            'message' => "❌ لم أجد المنتج '$productName'. يمكنك البحث يدوياً وإعادة المحاولة."
        ];
    }
    
    // حفظ الأمر في قاعدة البيانات
    $commandId = $this->saveCommand([
        'type' => 'price_track',
        'product_name' => $productName,
        'product_ids' => $product['id'],
        'target_price' => $targetPrice,
        'conditions' => json_encode(['price_condition' => 'less_than'])
    ]);
    
    // التحقق من السعر الحالي
    $currentPrice = $product['final_price'] ?? $product['price'];
    $meetsCondition = $currentPrice <= $targetPrice;
    
    if ($meetsCondition) {
        // إضافة المنتج للسلة مباشرة
        $added = $this->addToCart($product['id'], 1);
        if ($added) {
            $message = "🎉 تم العثور على {$product['title']} بالسعر " . formatPrice($currentPrice) . " وهو أقل من " . formatPrice($targetPrice) . ". تمت إضافته إلى سلة التسوق!";
        } else {
            $message = "⚠️ تعذر إضافة المنتج إلى السلة. قد يكون غير متوفر في المخزون.";
        }
    } else {
        $message = "✅ تم تفعيل تتبع سعر {$product['title']}. سأقوم بمراقبة السعر وإعلامك عندما يصبح " . formatPrice($targetPrice) . " أو أقل. السعر الحالي: " . formatPrice($currentPrice);
    }
    
    return [
        'success' => true,
        'command_id' => $commandId,
        'message' => $message
    ];
}
    // معالجة الجدولة الزمنية
    private function handleScheduling($data) {
        $productName = $data['product'];
        $scheduleType = $data['schedule_type'];
        $scheduleValue = $data['schedule_value'];
        
        $product = $this->findProduct($productName);
        if (!$product) {
            return [
                'success' => false,
                'message' => "لم أجد المنتج '$productName'"
            ];
        }
        
        $nextRun = $this->calculateNextRun($scheduleType, $scheduleValue);
        
        $commandId = $this->saveCommand([
            'type' => 'schedule',
            'product_name' => $productName,
            'product_ids' => $product['id'],
            'schedule_time' => $nextRun,
            'schedule_frequency' => $scheduleType,
            'schedule_days' => $scheduleType == 'custom' ? $scheduleValue : null
        ]);
        
        return [
            'success' => true,
            'command_id' => $commandId,
            'message' => "✅ تم جدولة شراء $productName $scheduleType. سيتم الشراء تلقائياً في الوقت المحدد."
        ];
    }
    
    // معالجة طلبات التوفر
    private function handleAvailability($data) {
        $productName = $data['product'];
        
        $product = $this->findProduct($productName);
        if (!$product) {
            return [
                'success' => false,
                'message' => "لم أجد المنتج '$productName'"
            ];
        }
        
        $commandId = $this->saveCommand([
            'type' => 'availability',
            'product_name' => $productName,
            'product_ids' => $product['id'],
            'conditions' => json_encode(['check_stock' => true])
        ]);
        
        // التحقق من التوفر الحالي
        $isAvailable = $product['stock'] > 0;
        
        if ($isAvailable) {
            $this->addToCart($product['id'], 1);
            $message = "🎉 المنتج $productName متاح حالياً! تمت إضافته إلى سلة التسوق.";
        } else {
            $message = "🔔 تم تفعيل مراقبة توفر $productName. سأقوم بإعلامك فور توافره بالمخزون.";
        }
        
        return [
            'success' => true,
            'command_id' => $commandId,
            'message' => $message
        ];
    }
    
    // البحث عن المنتج في قاعدة البيانات
private function findProduct($productName) {
    $stmt = $this->db->prepare("
        SELECT id, title, price, final_price, stock, main_image, discount_percentage 
        FROM products 
        WHERE (title LIKE ? OR description LIKE ?) 
        AND is_active = 1 
        AND stock > 0
        ORDER BY 
            CASE 
                WHEN title = ? THEN 1
                WHEN title LIKE ? THEN 2
                ELSE 3
            END,
            stock DESC,
            final_price ASC
        LIMIT 1
    ");
    
    $exactMatch = $productName;
    $partialMatch = "%$productName%";
    
    $stmt->execute([$partialMatch, $partialMatch, $exactMatch, $partialMatch]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
    // إضافة منتج للسلة
 // في ملف smart_command_processor.php - أضف هذه الدالة داخل الكلاس
private function addToCart($productId, $quantity = 1) {
    // استخدام دالة addToCart الموجودة في functions.php
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    // جلب بيانات المنتج
    $product = $this->findProductById($productId);
    
    if (!$product || $product['stock'] < $quantity) {
        return false;
    }
    
    if (isset($_SESSION['cart'][$productId])) {
        $_SESSION['cart'][$productId]['qty'] += $quantity;
    } else {
        $_SESSION['cart'][$productId] = [
            'id' => $product['id'],
            'title' => $product['title'],
            'price' => $product['final_price'],
            'image' => $product['main_image'] ?? 'assets/images/placeholder.jpg',
            'qty' => $quantity,
            'stock' => $product['stock']
        ];
    }
    
    return true;
}

// دالة مساعدة للبحث عن المنتج بال ID
private function findProductById($productId) {
    $stmt = $this->db->prepare("
        SELECT id, title, price, final_price, stock, main_image, discount_percentage 
        FROM products 
        WHERE id = ? AND is_active = 1
    ");
    
    $stmt->execute([$productId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
    // حفظ الأمر في قاعدة البيانات
    private function saveCommand($data) {
        $stmt = $this->db->prepare("
            INSERT INTO smart_commands 
            (user_id, command_text, command_type, product_name, product_ids, target_price, 
             schedule_time, schedule_frequency, schedule_days, conditions, status, next_check) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', ?)
        ");
        
        $nextCheck = $this->calculateNextCheck($data['type']);
        
        $stmt->execute([
            $this->userId,
            $data['product_name'] ?? '',
            $data['type'],
            $data['product_name'] ?? null,
            $data['product_ids'] ?? null,
            $data['target_price'] ?? null,
            $data['schedule_time'] ?? null,
            $data['schedule_frequency'] ?? null,
            $data['schedule_days'] ?? null,
            $data['conditions'] ?? null,
            $nextCheck
        ]);
        
        return $this->db->lastInsertId();
    }
    
    // تسجيل تنفيذ الأمر
    private function logExecution($commandId, $result) {
        $stmt = $this->db->prepare("
            INSERT INTO command_executions 
            (command_id, execution_time, action_taken, result, status) 
            VALUES (?, NOW(), 'notified', ?, 'success')
        ");
        
        $stmt->execute([$commandId, $result]);
    }
    
    // دوال مساعدة للتحليل...
    private function parsePriceTracking($text) {
        preg_match('/(تتبع|راقب|اشترِ).*سعر.*?(.*?)(عندما|إذا|حين).*(أقل|ينخفض|يصبح).*?(\d+)/u', $text, $matches);
        
        if (count($matches) >= 6) {
            return [
                'type' => 'price_track',
                'data' => [
                    'product' => trim($matches[2]),
                    'target_price' => floatval($matches[5])
                ]
            ];
        }
        
        // نمط سريع: "تتبع سعر iPhone 15"
        preg_match('/(تتبع|راقب).*سعر.*?(.*)/u', $text, $matches);
        if (count($matches) >= 3) {
            return [
                'type' => 'price_track',
                'data' => [
                    'product' => trim($matches[2]),
                    'target_price' => null // سيطلب السعر لاحقاً
                ]
            ];
        }
        
        return ['type' => 'unknown', 'data' => []];
    }
    
    private function parseScheduling($text) {
        // تحليل الجدولة - يمكن توسيع هذا حسب الحاجة
        return ['type' => 'schedule', 'data' => ['product' => 'منتج', 'schedule_type' => 'weekly']];
    }
    
    // ... باقي دوال التحليل بنفس المنطق
    
    private function calculateNextCheck($commandType) {
        $nextCheck = new DateTime();
        
        switch($commandType) {
            case 'price_track':
                $nextCheck->modify('+1 hour');
                break;
            case 'availability':
                $nextCheck->modify('+30 minutes');
                break;
            default:
                $nextCheck->modify('+1 day');
        }
        
        return $nextCheck->format('Y-m-d H:i:s');
    }
    
    private function calculateNextRun($scheduleType, $value) {
        $nextRun = new DateTime();
        
        switch($scheduleType) {
            case 'daily':
                $nextRun->modify('+1 day');
                break;
            case 'weekly':
                $nextRun->modify('+1 week');
                break;
            case 'monthly':
                $nextRun->modify('+1 month');
                break;
            case 'custom':
                $nextRun->modify("+$value days");
                break;
        }
        
        return $nextRun->format('Y-m-d H:i:s');
    }
}
?>