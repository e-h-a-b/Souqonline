<?php
/**
 * دوال إدارة QR Codes
 */

/**
 * تسجيل تحليلات QR Code
 */
function logQRAnalytics($qr_code_id, $action, $customer_id = null, $store_owner_id = null) {
    global $pdo;
    
    $stmt = $pdo->prepare("INSERT INTO qr_code_analytics (qr_code_id, action, customer_id, store_owner_id, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $qr_code_id,
        $action,
        $customer_id,
        $store_owner_id,
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT']
    ]);
    
    return $pdo->lastInsertId();
}

/**
 * جلب إحصائيات QR Code
 */
function getQRAnalytics($qr_code_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT 
            action,
            COUNT(*) as count,
            MAX(created_at) as last_action
        FROM qr_code_analytics 
        WHERE qr_code_id = ? 
        GROUP BY action
    ");
    $stmt->execute([$qr_code_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * جلب جميع QR Codes للمستخدم
 */
function getUserQRCodes($customer_id, $limit = 50, $offset = 0) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT 
            sqc.*,
            p.title as product_title,
            p.main_image as product_image,
            store.first_name as store_owner_name,
            (SELECT COUNT(*) FROM qr_code_analytics WHERE qr_code_id = sqc.id AND action = 'scanned') as scan_count,
            (SELECT COUNT(*) FROM qr_code_analytics WHERE qr_code_id = sqc.id AND action = 'used') as use_count
        FROM store_qr_codes sqc
        LEFT JOIN products p ON sqc.product_id = p.id
        LEFT JOIN customers store ON sqc.store_owner_id = store.id
        WHERE sqc.customer_id = ?
        ORDER BY sqc.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$customer_id, $limit, $offset]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * جلب QR Codes لمتجر المستخدم
 */
function getStoreQRCodes($store_owner_id, $limit = 50, $offset = 0) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT 
            sqc.*,
            p.title as product_title,
            p.main_image as product_image,
            c.first_name as customer_name,
            c.phone as customer_phone,
            (SELECT COUNT(*) FROM qr_code_analytics WHERE qr_code_id = sqc.id AND action = 'scanned') as scan_count,
            (SELECT COUNT(*) FROM qr_code_analytics WHERE qr_code_id = sqc.id AND action = 'used') as use_count
        FROM store_qr_codes sqc
        LEFT JOIN products p ON sqc.product_id = p.id
        LEFT JOIN customers c ON sqc.customer_id = c.id
        WHERE sqc.store_owner_id = ?
        ORDER BY sqc.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$store_owner_id, $limit, $offset]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * تحديث حالة QR Code
 */
function updateQRCodeStatus($qr_code_id, $status_updates) {
    global $pdo;
    
    $allowed_fields = ['is_valid', 'is_used', 'used_at'];
    $set_parts = [];
    $params = [];
    
    foreach ($status_updates as $field => $value) {
        if (in_array($field, $allowed_fields)) {
            $set_parts[] = "$field = ?";
            $params[] = $value;
        }
    }
    
    if (empty($set_parts)) {
        return false;
    }
    
    $params[] = $qr_code_id;
    $sql = "UPDATE store_qr_codes SET " . implode(', ', $set_parts) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute($params);
}

/**
 * جلب تفاصيل QR Code
 */
function getQRCodeDetails($qr_code_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT 
            sqc.*,
            p.title as product_title,
            p.description as product_description,
            p.main_image as product_image,
            c.first_name as customer_name,
            c.email as customer_email,
            c.phone as customer_phone,
            store.first_name as store_owner_name,
            store.email as store_owner_email,
            store.phone as store_owner_phone
        FROM store_qr_codes sqc
        LEFT JOIN products p ON sqc.product_id = p.id
        LEFT JOIN customers c ON sqc.customer_id = c.id
        LEFT JOIN customers store ON sqc.store_owner_id = store.id
        WHERE sqc.id = ?
    ");
    $stmt->execute([$qr_code_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * التحقق من صلاحية QR Code مع التسجيل
 */
function validateQRCodeWithLogging($qr_code) {
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
    
    // تسجيل عملية المسح
    logQRAnalytics($qr_data['id'], 'scanned', $qr_data['customer_id'], $qr_data['store_owner_id']);
    
    return ['valid' => true, 'data' => $qr_data];
}

/**
 * استخدام QR Code مع التسجيل
 */
function useQRCodeWithLogging($qr_code, $store_owner_id) {
    global $pdo;
    
    // التحقق من صحة الكود أولاً
    $validation = validateQRCodeWithLogging($qr_code);
    
    if (!$validation['valid']) {
        return ['success' => false, 'message' => $validation['message']];
    }
    
    // استخدام الكود
    $stmt = $pdo->prepare("UPDATE store_qr_codes SET is_used = 1, used_at = NOW() WHERE qr_code = ? AND store_owner_id = ?");
    $result = $stmt->execute([$qr_code, $store_owner_id]);
    
    if ($result) {
        // تسجيل عملية الاستخدام
        logQRAnalytics($validation['data']['id'], 'used', $validation['data']['customer_id'], $store_owner_id);
        return ['success' => true, 'message' => 'تم استخدام الكود وتطبيق الخصم بنجاح'];
    } else {
        return ['success' => false, 'message' => 'فشل في استخدام الكود'];
    }
}
?>