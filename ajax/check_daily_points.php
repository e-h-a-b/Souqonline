<?php
require_once '../config.php';
require_once '../functions.php';
require_once '../daily_points.php';

if (isset($_SESSION['customer_id'])) {
    $customer_id = $_SESSION['customer_id'];
    $visit_stats = getVisitStats($customer_id);
    
    echo json_encode([
        'success' => true,
        'stats' => $visit_stats,
        'points' => getCustomerPoints($customer_id)['available_points']
    ]);
} else {
    echo json_encode(['success' => false]);
}
?>