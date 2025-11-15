<?php
/**
 * API لإدارة الطلبات
 */
require_once '../functions.php';
// التحقق من وضع الصيانة
if (getSetting('maintenance_mode', '0') == '1' && !isset($_SESSION['admin_id'])) {
    header('Location: maintenance.php');
    exit;
} 
header('Content-Type: application/json');

// معالجة POST Request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['action'])) {
        echo json_encode(['success' => false, 'message' => 'طلب غير صالح']);
        exit;
    }
    
    $action = $input['action'];
    
    switch ($action) {
        case 'cancel':
            $orderId = (int)($input['order_id'] ?? 0);
            
            if ($orderId <= 0) {
                echo json_encode(['success' => false, 'message' => 'رقم طلب غير صحيح']);
                exit;
            }
            
            try {
                // جلب معلومات الطلب
                $order = getOrder($orderId);
                
                if (!$order) {
                    echo json_encode(['success' => false, 'message' => 'الطلب غير موجود']);
                    exit;
                }
                
                // التحقق من إمكانية الإلغاء
                if (!in_array($order['status'], ['pending', 'confirmed'])) {
                    echo json_encode([
                        'success' => false, 
                        'message' => 'لا يمكن إلغاء الطلب في هذه المرحلة'
                    ]);
                    exit;
                }
                
                // إلغاء الطلب
                $stmt = $pdo->prepare("
                    UPDATE orders 
                    SET status = 'cancelled',
                        cancelled_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$orderId]);
                
                // إعادة المنتجات للمخزون
                $stmt = $pdo->prepare("
                    SELECT product_id, qty FROM order_items WHERE order_id = ?
                ");
                $stmt->execute([$orderId]);
                $items = $stmt->fetchAll();
                
                $updateStock = $pdo->prepare("
                    UPDATE products 
                    SET stock = stock + ?,
                        orders_count = orders_count - ?
                    WHERE id = ?
                ");
                
                foreach ($items as $item) {
                    $updateStock->execute([$item['qty'], $item['qty'], $item['product_id']]);
                }
                
                // تسجيل النشاط
                logActivity('order_cancelled', "تم إلغاء الطلب #{$order['order_number']}");
                
                // إرسال إشعار للعميل
                if (!empty($order['customer_email'])) {
                    $subject = "إلغاء الطلب #" . $order['order_number'];
                    $message = "
                        <h2>تم إلغاء طلبك</h2>
                        <p>عزيزي {$order['customer_name']},</p>
                        <p>تم إلغاء طلبك رقم: <strong>{$order['order_number']}</strong></p>
                        <p>إذا كان لديك أي استفسار، يرجى التواصل معنا.</p>
                    ";
                    sendEmail($order['customer_email'], $subject, $message);
                }
                
                echo json_encode([
                    'success' => true,
                    'message' => 'تم إلغاء الطلب بنجاح'
                ]);
                
            } catch (Exception $e) {
                echo json_encode([
                    'success' => false,
                    'message' => 'حدث خطأ أثناء إلغاء الطلب: ' . $e->getMessage()
                ]);
            }
            break;
            
        case 'track':
            $orderId = (int)($input['order_id'] ?? 0);
            $orderNumber = cleanInput($input['order_number'] ?? '');
            
            try {
                if ($orderId > 0) {
                    $order = getOrder($orderId);
                } elseif ($orderNumber) {
                    $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_number = ?");
                    $stmt->execute([$orderNumber]);
                    $order = $stmt->fetch();
                } else {
                    echo json_encode(['success' => false, 'message' => 'يرجى إدخال رقم الطلب']);
                    exit;
                }
                
                if (!$order) {
                    echo json_encode(['success' => false, 'message' => 'الطلب غير موجود']);
                    exit;
                }
                
                // جلب تاريخ الحالات
                $stmt = $pdo->prepare("
                    SELECT * FROM order_status_history 
                    WHERE order_id = ? 
                    ORDER BY created_at ASC
                ");
                $stmt->execute([$order['id']]);
                $history = $stmt->fetchAll();
                
                echo json_encode([
                    'success' => true,
                    'order' => [
                        'order_number' => $order['order_number'],
                        'status' => $order['status'],
                        'tracking_number' => $order['tracking_number'],
                        'created_at' => $order['created_at'],
                        'total' => $order['total']
                    ],
                    'history' => $history
                ]);
                
            } catch (Exception $e) {
                echo json_encode([
                    'success' => false,
                    'message' => 'حدث خطأ: ' . $e->getMessage()
                ]);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'عملية غير معروفة']);
            break;
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'طريقة طلب غير مدعومة']);