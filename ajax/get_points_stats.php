<?php
require_once '../config.php';
require_once '../daily_points.php';

header('Content-Type: application/json');

if (isset($_SESSION['customer_id'])) {
    $customer_id = $_SESSION['customer_id'];
    
    // الحصول على النقاط
    $customer_points = getCustomerPoints($customer_id);
    $available_points = $customer_points['available_points'] ?? 0;
    
    // الحصول على إحصائيات الزيارات
    $visit_stats = getVisitStats($customer_id);
    
    echo json_encode([
        'success' => true,
        'points' => $available_points,
        'formatted_points' => number_format($available_points),
        'stats' => [
            'today_visited' => $visit_stats['today_visited'] ?? false,
            'monthly_visits' => $visit_stats['monthly_visits'] ?? 0,
            'total_points_earned' => $visit_stats['total_points_earned'] ?? 0,
            'formatted_points' => number_format($available_points)
        ]
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'يجب تسجيل الدخول'
    ]);
}
?>