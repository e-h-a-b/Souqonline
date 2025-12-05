<?php
session_start();
require_once 'config.php';

class SmartCommands {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    // الحصول على الأوامر النشطة للمستخدم
    public function getUserCommands($userId) {
        $stmt = $this->db->prepare("
            SELECT * FROM smart_commands 
            WHERE user_id = ? AND status IN ('active', 'pending')
            ORDER BY created_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // إنشاء أمر جديد
    public function createCommand($data) {
        $stmt = $this->db->prepare("
            INSERT INTO smart_commands 
            (user_id, command_text, command_type, product_ids, target_price, schedule_time, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        return $stmt->execute([
            $data['user_id'],
            $data['command_text'],
            $data['command_type'],
            $data['product_ids'],
            $data['target_price'],
            $data['schedule_time'],
            'active'
        ]);
    }
    
    // تحديث أمر
    public function updateCommand($id, $data) {
        $stmt = $this->db->prepare("
            UPDATE smart_commands 
            SET command_text = ?, command_type = ?, product_ids = ?, target_price = ?, schedule_time = ?
            WHERE id = ? AND user_id = ?
        ");
        
        return $stmt->execute([
            $data['command_text'],
            $data['command_type'],
            $data['product_ids'],
            $data['target_price'],
            $data['schedule_time'],
            $id,
            $_SESSION['customer_id']
        ]);
    }
    
    // حذف أمر
    public function deleteCommand($id) {
        $stmt = $this->db->prepare("
            UPDATE smart_commands SET status = 'cancelled' 
            WHERE id = ? AND user_id = ?
        ");
        return $stmt->execute([$id, $_SESSION['customer_id']]);
    }
    
    // الحصول على إحصائيات
    public function getStats($userId) {
        $stats = [];
        
        // عدد الأوامر النشطة
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count FROM smart_commands 
            WHERE user_id = ? AND status = 'active'
        ");
        $stmt->execute([$userId]);
        $stats['active_commands'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // عدد عمليات الشراء الناجحة
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count FROM command_executions 
            WHERE status = 'success'
        ");
        $stmt->execute();
        $stats['successful_purchases'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // إجمالي التوفير (محاكاة)
        $stats['total_savings'] = rand(500, 2500);
        
        return $stats;
    }
}

// التحقق من تسجيل الدخول
if (!isset($_SESSION['customer_id'])) {
    die("يجب تسجيل الدخول لاستخدام هذه الميزة");
}

$commandsManager = new SmartCommands($pdo);
$userId = $_SESSION['customer_id'];

// معالجة طلبات AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create_command':
                $result = $commandsManager->createCommand([
                    'user_id' => $userId,
                    'command_text' => $_POST['command_text'],
                    'command_type' => $_POST['command_type'],
                    'product_ids' => $_POST['product_ids'],
                    'target_price' => $_POST['target_price'],
                    'schedule_time' => $_POST['schedule_time']
                ]);
                echo json_encode(['success' => $result]);
                break;
                
            case 'delete_command':
                $result = $commandsManager->deleteCommand($_POST['command_id']);
                echo json_encode(['success' => $result]);
                break;
                
            case 'get_commands':
                $commands = $commandsManager->getUserCommands($userId);
                echo json_encode($commands);
                break;
        }
    }
    exit;
}

// الحصول على البيانات للعرض
$userCommands = $commandsManager->getUserCommands($userId);
$stats = $commandsManager->getStats($userId);
?>