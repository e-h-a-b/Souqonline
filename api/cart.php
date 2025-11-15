<?php
/**
 * API لإدارة سلة المشتريات
 */
require_once '../functions.php';
// التحقق من وضع الصيانة
if (getSetting('maintenance_mode', '0') == '1' && !isset($_SESSION['admin_id'])) {
    header('Location: maintenance.php');
    exit;
} 
header('Content-Type: application/json');

// GET Request - جلب عدد العناصر
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['action']) && $_GET['action'] === 'count') {
        echo json_encode([
            'success' => true,
            'cart_count' => getCartCount()
        ]);
        exit;
    }
}

// POST Request - تحديث السلة
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['action'])) {
        echo json_encode(['success' => false, 'message' => 'طلب غير صالح']);
        exit;
    }
    
    $action = $input['action'];
    
    switch ($action) {
        case 'add':
            $productId = (int)($input['product_id'] ?? 0);
            $quantity = (int)($input['quantity'] ?? 1);
            
            if ($productId <= 0 || $quantity <= 0) {
                echo json_encode(['success' => false, 'message' => 'بيانات غير صحيحة']);
                exit;
            }
            
            $result = addToCart($productId, $quantity);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'تمت الإضافة بنجاح',
                    'cart_count' => getCartCount()
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'المنتج غير متوفر أو الكمية غير كافية']);
            }
            break;
            
        case 'update':
            $productId = (int)($input['product_id'] ?? 0);
            $quantity = (int)($input['quantity'] ?? 0);
            
            $result = updateCartItem($productId, $quantity);
            
            echo json_encode([
                'success' => $result,
                'cart_count' => getCartCount(),
                'cart_total' => getCartTotal()
            ]);
            break;
            
        case 'remove':
            $productId = (int)($input['product_id'] ?? 0);
            
            $result = removeFromCart($productId);
            
            echo json_encode([
                'success' => $result,
                'cart_count' => getCartCount(),
                'cart_total' => getCartTotal()
            ]);
            break;
            
        case 'clear':
            clearCart();
            
            echo json_encode([
                'success' => true,
                'cart_count' => 0,
                'cart_total' => 0
            ]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'عملية غير معروفة']);
            break;
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'طريقة طلب غير مدعومة']);