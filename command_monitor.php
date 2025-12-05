<?php
require_once 'config.php';

class CommandMonitor {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    // مراقبة وتنفيذ الأوامر النشطة
    public function monitorActiveCommands() {
        $now = date('Y-m-d H:i:s');
        
        // الحصول على الأوامر التي تحتاج للفحص
        $stmt = $this->db->prepare("
            SELECT * FROM smart_commands 
            WHERE status = 'active' AND next_check <= ? 
            ORDER BY next_check ASC
        ");
        $stmt->execute([$now]);
        $commands = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($commands as $command) {
            $this->checkCommand($command);
        }
        
        return count($commands);
    }
    
    private function checkCommand($command) {
        switch ($command['command_type']) {
            case 'price_track':
                $this->checkPriceTracking($command);
                break;
            case 'availability':
                $this->checkAvailability($command);
                break;
            case 'schedule':
                $this->checkScheduled($command);
                break;
        }
    }
    
    private function checkPriceTracking($command) {
        $productId = $command['product_ids'];
        $targetPrice = $command['target_price'];
        
        $stmt = $this->db->prepare("SELECT final_price, stock FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($product && $product['final_price'] <= $targetPrice && $product['stock'] > 0) {
            // تنفيذ الشراء أو الإشعار
            $this->executePurchase($command, $product);
        } else {
            // تأجيل الفحص التالي
            $this->rescheduleCheck($command['id'], 'price_track');
        }
    }
    
    private function executePurchase($command, $product) {
        // إضافة للمشتريات أو إرسال إشعار
        $this->logExecution($command['id'], "تم تنفيذ الشراء تلقائياً للمنتج بسعر {$product['final_price']}");
        
        // تحديث حالة الأمر
        $stmt = $this->db->prepare("UPDATE smart_commands SET status = 'executed' WHERE id = ?");
        $stmt->execute([$command['id']]);
        
        // يمكن هنا إرسال إيميل أو إشعار للمستخدم
    }
    
    private function rescheduleCheck($commandId, $type) {
        $nextCheck = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        $stmt = $this->db->prepare("UPDATE smart_commands SET next_check = ? WHERE id = ?");
        $stmt->execute([$nextCheck, $commandId]);
    }
    
    private function logExecution($commandId, $result) {
        $stmt = $this->db->prepare("
            INSERT INTO command_executions 
            (command_id, execution_time, action_taken, result, status) 
            VALUES (?, NOW(), 'auto_purchase', ?, 'success')
        ");
        $stmt->execute([$commandId, $result]);
    }
}

// التشغيل إذا تم استدعاء الملف مباشرة
if (php_sapi_name() === 'cli') {
    $monitor = new CommandMonitor($pdo);
    $processed = $monitor->monitorActiveCommands();
    echo "تم معالجة $processed أمر\n";
}
?>