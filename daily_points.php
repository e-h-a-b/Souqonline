<?php
// daily_points.php

/**
 * منح نقاط الزيارة اليومية للمستخدم
 */
function awardDailyVisitPoints($customer_id) {
    global $pdo;
    
    // التحقق من تفعيل النظام
    if (getSetting('daily_visit_points_enabled', '1') != '1') {
        return false;
    }
    
    // عدد النقاط الممنوحة
    $points_amount = (int)getSetting('daily_visit_points_amount', 5);
    
    // تاريخ اليوم
    $today = date('Y-m-d');
    
    try {
        // التحقق مما إذا كان المستخدم قد حصل على النقاط اليوم
        $stmt = $pdo->prepare("
            SELECT id FROM daily_visits 
            WHERE customer_id = ? AND visit_date = ?
        ");
        $stmt->execute([$customer_id, $today]);
        
        if ($stmt->rowCount() > 0) {
            return false; // Already awarded today
        }
        
        // بدء transaction
        $pdo->beginTransaction();
        
        // تسجيل الزيارة
        $stmt = $pdo->prepare("
            INSERT INTO daily_visits (customer_id, visit_date, points_awarded, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $customer_id, 
            $today, 
            $points_amount,
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
        
        // إضافة النقاط للمستخدم
        addCustomerPoints($customer_id, $points_amount, 'daily_visit', 'مكافأة زيارة يومية');
        
        // commit transaction
        $pdo->commit();
        
        return true;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Daily points error: " . $e->getMessage());
        return false;
    }
}

/**
 * الحصول على عدد الزيارات هذا الشهر
 */
function getMonthlyVisits($customer_id) {
    global $pdo;
    
    $current_month = date('Y-m');
    
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as visit_count 
        FROM daily_visits 
        WHERE customer_id = ? AND DATE_FORMAT(visit_date, '%Y-%m') = ?
    ");
    $stmt->execute([$customer_id, $current_month]);
    
    return $stmt->fetch(PDO::FETCH_ASSOC)['visit_count'] ?? 0;
}

/**
 * الحصول على إحصائيات الزيارات
 */
function getVisitStats($customer_id) {
    global $pdo;
    
    $stats = [
        'today_visited' => false,
        'monthly_visits' => 0,
        'total_points_earned' => 0
    ];
    
    try {
        // التحقق من زيارة اليوم
        $today = date('Y-m-d');
        $stmt = $pdo->prepare("
            SELECT points_awarded FROM daily_visits 
            WHERE customer_id = ? AND visit_date = ?
        ");
        $stmt->execute([$customer_id, $today]);
        
        if ($stmt->rowCount() > 0) {
            $stats['today_visited'] = true;
        }
        
        // عدد الزيارات الشهرية
        $current_month = date('Y-m');
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as visit_count, SUM(points_awarded) as total_points
            FROM daily_visits 
            WHERE customer_id = ? AND DATE_FORMAT(visit_date, '%Y-%m') = ?
        ");
        $stmt->execute([$customer_id, $current_month]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $stats['monthly_visits'] = $result['visit_count'] ?? 0;
        $stats['total_points_earned'] = $result['total_points'] ?? 0;
        
    } catch (Exception $e) {
        error_log("Visit stats error: " . $e->getMessage());
    }
    
    return $stats;
}
?>