<?php
/**
 * دوال نظام الإحالات
 */

require_once 'config.php';

/**
 * إنشاء رابط إحالة للعميل
 */
function createReferralLink($customer_id) {
    global $pdo;
    
    // التحقق من وجود رابط مسبق
    $stmt = $pdo->prepare("SELECT * FROM referral_links WHERE customer_id = ?");
    $stmt->execute([$customer_id]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        return $existing;
    }
    
    // إنشاء كود إحالة فريد
    $referral_code = generateReferralCode();
    $referral_url = "https://" . $_SERVER['HTTP_HOST'] . "/register.php?ref=" . $referral_code;
    
    $stmt = $pdo->prepare("
        INSERT INTO referral_links (customer_id, referral_code, referral_url) 
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$customer_id, $referral_code, $referral_url]);
    
    return [
        'referral_code' => $referral_code,
        'referral_url' => $referral_url,
        'customer_id' => $customer_id
    ];
}

/**
 * توليد كود إحالة فريد
 */
function generateReferralCode($length = 8) {
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $code = '';
    
    do {
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= $characters[rand(0, strlen($characters) - 1)];
        }
        
        // التحقق من التكرار
        global $pdo;
        $stmt = $pdo->prepare("SELECT id FROM referral_links WHERE referral_code = ?");
        $stmt->execute([$code]);
    } while ($stmt->fetch());
    
    return $code;
}

/**
 * معالجة الإحالة عند التسجيل
 */
function processReferralSignup($referred_customer_id, $referral_code) {
    if (!getSetting('referral_system_enabled', '1')) {
        return false;
    }
    
    global $pdo;
    
    // العثور على المُحيل
    $stmt = $pdo->prepare("
        SELECT rl.*, c.first_name 
        FROM referral_links rl 
        JOIN customers c ON rl.customer_id = c.id 
        WHERE rl.referral_code = ? AND rl.is_active = 1
    ");
    $stmt->execute([$referral_code]);
    $referrer = $stmt->fetch();
    
    if (!$referrer) {
        return false;
    }
    
    // التحقق من عدم وجود إحالة مسبقة
    $stmt = $pdo->prepare("SELECT id FROM referrals WHERE referred_id = ?");
    $stmt->execute([$referred_customer_id]);
    
    if ($stmt->fetch()) {
        return false;
    }
    
    // حساب تاريخ الانتهاء
    $expiry_days = getSetting('referral_expiry_days', '30');
    $expires_at = date('Y-m-d H:i:s', strtotime("+$expiry_days days"));
    
    // تسجيل الإحالة
    $stmt = $pdo->prepare("
        INSERT INTO referrals (referrer_id, referred_id, referral_code, status, expires_at) 
        VALUES (?, ?, ?, 'signed_up', ?)
    ");
    $stmt->execute([$referrer['customer_id'], $referred_customer_id, $referral_code, $expires_at]);
    
    // تحديث إحصائيات رابط الإحالة
    $stmt = $pdo->prepare("
        UPDATE referral_links 
        SET signups = signups + 1 
        WHERE customer_id = ?
    ");
    $stmt->execute([$referrer['customer_id']]);
    
    // منح نقاط التسجيل للمُحيل إذا كان مسموحاً
    $signup_points = getSetting('referral_points_referrer', '0');
    if ($signup_points > 0) {
        addPoints($referrer['customer_id'], $signup_points, 'referral_signup', 'تم منح نقاط لتسجيل صديق عبر رابط الإحالة');
        
        // تحديث إجمالي النقاط المكتسبة
        $stmt = $pdo->prepare("
            UPDATE referral_links 
            SET total_earned_points = total_earned_points + ? 
            WHERE customer_id = ?
        ");
        $stmt->execute([$signup_points, $referrer['customer_id']]);
    }
    
    return true;
}

/**
 * معالجة الإحالة عند إكمال الطلب
 */
function processReferralOrder($order_id) {
    if (!getSetting('referral_system_enabled', '1')) {
        return false;
    }
    
    global $pdo;
    
    // جلب معلومات الطلب
    $stmt = $pdo->prepare("
        SELECT o.*, c.id as customer_id 
        FROM orders o 
        JOIN customers c ON o.customer_email = c.email 
        WHERE o.id = ? AND o.payment_status = 'paid'
    ");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();
    
    if (!$order) {
        return false;
    }
    
    // العثور على الإحالة
    $stmt = $pdo->prepare("
        SELECT r.*, rl.customer_id as referrer_id 
        FROM referrals r 
        JOIN referral_links rl ON r.referral_code = rl.referral_code 
        WHERE r.referred_id = ? AND r.status = 'signed_up' AND r.expires_at > NOW()
    ");
    $stmt->execute([$order['customer_id']]);
    $referral = $stmt->fetch();
    
    if (!$referral) {
        return false;
    }
    
    $min_order_amount = getSetting('referral_min_order_amount', '100');
    if ($order['total'] < $min_order_amount) {
        return false;
    }
    
    // حساب النقاط بناءً على نوع المكافأة
    $points_system = getSetting('referral_points_enabled', '1');
    $commission_system = getSetting('referral_commission_enabled', '0');
    
    $points_earned = 0;
    
    if ($points_system) {
        $points_earned = getSetting('referral_points_referrer_order', '500');
    } elseif ($commission_system) {
        $commission_rate = getSetting('referral_commission_rate', '5');
        $points_earned = ($order['total'] * $commission_rate) / 100;
        // تحويل العمولة إلى نقاط
        $points_rate = getSetting('points_currency_rate', '100');
        $points_earned = $points_earned * $points_rate;
    }
    
    if ($points_earned > 0) {
        // منح النقاط للمُحيل
        addPoints($referral['referrer_id'], $points_earned, 'referral_order', 'تم منح نقاط لإكمال طلب صديق عبر رابط الإحالة');
        
        // تحديث الإحالة
        $stmt = $pdo->prepare("
            UPDATE referrals 
            SET status = 'completed_order', points_earned = ?, completed_order_id = ? 
            WHERE id = ?
        ");
        $stmt->execute([$points_earned, $order_id, $referral['id']]);
        
        // تحديث إحصائيات رابط الإحالة
        $stmt = $pdo->prepare("
            UPDATE referral_links 
            SET completed_orders = completed_orders + 1, total_earned_points = total_earned_points + ? 
            WHERE customer_id = ?
        ");
        $stmt->execute([$points_earned, $referral['referrer_id']]);
        
        // منح نقاط للعميل المُحال إذا كان مسموحاً
        $referred_points = getSetting('referral_points_referred', '300');
        if ($referred_points > 0) {
            addPoints($order['customer_id'], $referred_points, 'referral_welcome', 'تهانينا! نقاط ترحيبية للتسجيل عبر رابط الإحالة');
        }
        
        return true;
    }
    
    return false;
}

/**
 * جلب إحصائيات الإحالة للعميل
 */
function getReferralStats($customer_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT 
            rl.clicks,
            rl.signups,
            rl.completed_orders,
            rl.total_earned_points,
            rl.referral_code,
            rl.referral_url,
            COUNT(DISTINCT r.id) as total_referrals,
            COUNT(DISTINCT CASE WHEN r.status = 'completed_order' THEN r.id END) as successful_referrals
        FROM referral_links rl
        LEFT JOIN referrals r ON rl.referral_code = r.referral_code
        WHERE rl.customer_id = ?
        GROUP BY rl.id
    ");
    $stmt->execute([$customer_id]);
    $stats = $stmt->fetch();
    
    if (!$stats) {
        // إنشاء رابط إحالة إذا لم يكن موجوداً
        $link = createReferralLink($customer_id);
        $stats = [
            'clicks' => 0,
            'signups' => 0,
            'completed_orders' => 0,
            'total_earned_points' => 0,
            'referral_code' => $link['referral_code'],
            'referral_url' => $link['referral_url'],
            'total_referrals' => 0,
            'successful_referrals' => 0
        ];
    }
    
    return $stats;
}

/**
 * جلب تاريخ الإحالات للعميل
 */
function getReferralHistory($customer_id, $limit = 10) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT 
            r.*,
            c.first_name as referred_name,
            c.email as referred_email,
            o.order_number,
            o.total as order_total
        FROM referrals r
        JOIN customers c ON r.referred_id = c.id
        LEFT JOIN orders o ON r.completed_order_id = o.id
        WHERE r.referrer_id = ?
        ORDER BY r.created_at DESC
        LIMIT ?
    ");
    $stmt->execute([$customer_id, $limit]);
    
    return $stmt->fetchAll();
}
?>