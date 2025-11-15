<?php
/**
 * صفحة التقارير والإحصائيات الشاملة
 */
session_start();
require_once '../config.php';
require_once '../functions.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// الفترة الزمنية
$period = $_GET['period'] ?? 'month';
$date_from = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
$date_to = $_GET['date_to'] ?? date('Y-m-d');
$report_type = $_GET['report_type'] ?? 'sales';

// إحصائيات شاملة
$sales_stats = [];
$top_products = [];
$daily_sales = [];
$customer_stats = [];
$financial_stats = [];
$points_stats = [];
$partner_stats = [];

try {
    // إحصائيات المبيعات الأساسية
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_orders,
            SUM(total) as total_revenue,
            AVG(total) as avg_order_value,
            SUM(CASE WHEN payment_status = 'paid' THEN total ELSE 0 END) as paid_revenue,
            SUM(CASE WHEN status = 'delivered' THEN total ELSE 0 END) as delivered_revenue,
            COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_orders,
            SUM(CASE WHEN status = 'cancelled' THEN total ELSE 0 END) as cancelled_revenue
        FROM orders 
        WHERE DATE(created_at) BETWEEN ? AND ?
        AND status NOT IN ('returned')
    ");
    $stmt->execute([$date_from, $date_to]);
    $sales_stats = $stmt->fetch();

    // إحصائيات المنتجات
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_products,
            COUNT(CASE WHEN is_active = 1 THEN 1 END) as active_products,
            COUNT(CASE WHEN is_featured = 1 THEN 1 END) as featured_products,
            COUNT(CASE WHEN stock <= 0 THEN 1 END) as out_of_stock,
            SUM(stock) as total_stock,
            SUM(views) as total_views,
            SUM(orders_count) as total_product_orders
        FROM products
    ");
    $stmt->execute();
    $product_stats = $stmt->fetch();

    // إحصائيات العملاء
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_customers,
            COUNT(CASE WHEN is_verified = 1 THEN 1 END) as verified_customers,
            SUM(orders_count) as total_customer_orders,
            SUM(total_spent) as total_customer_spent,
            AVG(total_spent) as avg_customer_spent
        FROM customers
    ");
    $stmt->execute();
    $customer_stats = $stmt->fetch();

    // إحصائيات النقاط
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_points_customers,
            SUM(points) as total_points_balance,
            SUM(total_earned) as total_points_earned,
            SUM(total_spent) as total_points_spent,
            AVG(points) as avg_points_per_customer
        FROM customer_points
    ");
    $stmt->execute();
    $points_stats = $stmt->fetch();

    // إحصائيات الباقات
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_packages,
            SUM(points) as total_package_points,
            SUM(bonus_points) as total_bonus_points,
            COUNT(CASE WHEN is_featured = 1 THEN 1 END) as featured_packages
        FROM packages
        WHERE is_active = 1
    ");
    $stmt->execute();
    $package_stats = $stmt->fetch();

    // طلبات الباقات
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_package_orders,
            SUM(price) as total_package_revenue,
            SUM(points_amount) as total_points_sold,
            COUNT(CASE WHEN payment_status = 'paid' THEN 1 END) as paid_package_orders,
            SUM(CASE WHEN payment_status = 'paid' THEN price ELSE 0 END) as paid_package_revenue
        FROM package_orders
        WHERE DATE(created_at) BETWEEN ? AND ?
    ");
    $stmt->execute([$date_from, $date_to]);
    $package_orders_stats = $stmt->fetch();

    // إحصائيات الشركاء
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_partners,
            SUM(investment_amount) as total_investment,
            AVG(profit_share) as avg_profit_share,
            COUNT(CASE WHEN status = 'active' THEN 1 END) as active_partners
        FROM partners
    ");
    $stmt->execute();
    $partner_stats = $stmt->fetch();

    // توزيع الأرباح على الشركاء
    $stmt = $pdo->prepare("
        SELECT 
            p.name,
            p.company,
            p.investment_amount,
            p.profit_share,
            (SELECT SUM(total) FROM orders 
             WHERE DATE(created_at) BETWEEN ? AND ? 
             AND payment_status = 'paid') * (p.profit_share / 100) as estimated_profit,
            p.contact_person,
            p.phone
        FROM partners p
        WHERE p.status = 'active'
        ORDER BY p.investment_amount DESC
    ");
    $stmt->execute([$date_from, $date_to]);
    $partner_profits = $stmt->fetchAll();

    // المنتجات الأكثر مبيعاً
    $stmt = $pdo->prepare("
        SELECT p.id, p.title, p.price, p.final_price, p.stock, p.views,
               SUM(oi.qty) as total_sold, 
               SUM(oi.total_price) as total_revenue
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        JOIN orders o ON oi.order_id = o.id
        WHERE DATE(o.created_at) BETWEEN ? AND ?
        AND o.status NOT IN ('cancelled', 'returned')
        GROUP BY p.id, p.title, p.price, p.final_price, p.stock, p.views
        ORDER BY total_sold DESC
        LIMIT 10
    ");
    $stmt->execute([$date_from, $date_to]);
    $top_products = $stmt->fetchAll();

    // المبيعات اليومية
    $stmt = $pdo->prepare("
        SELECT DATE(created_at) as sale_date,
               COUNT(*) as orders_count,
               SUM(total) as total_revenue,
               AVG(total) as avg_order_value,
               SUM(CASE WHEN payment_status = 'paid' THEN total ELSE 0 END) as paid_revenue
        FROM orders
        WHERE DATE(created_at) BETWEEN ? AND ?
        AND status NOT IN ('cancelled', 'returned')
        GROUP BY DATE(created_at)
        ORDER BY sale_date ASC
        LIMIT 60
    ");
    $stmt->execute([$date_from, $date_to]);
    $daily_sales = $stmt->fetchAll();

    // العملاء الأكثر شراءً
    $stmt = $pdo->prepare("
        SELECT c.id, c.first_name, c.last_name, c.email, c.phone,
               c.orders_count, c.total_spent, c.last_order_date,
               cp.points as current_points
        FROM customers c
        LEFT JOIN customer_points cp ON c.id = cp.customer_id
        WHERE c.orders_count > 0
        ORDER BY c.total_spent DESC
        LIMIT 10
    ");
    $stmt->execute();
    $top_customers = $stmt->fetchAll();

    // العملاء الأكثر نقاطاً
    $stmt = $pdo->prepare("
        SELECT c.id, c.first_name, c.last_name, c.email, c.phone,
               cp.points, cp.total_earned, cp.total_spent,
               c.orders_count, c.total_spent as total_money_spent
        FROM customer_points cp
        JOIN customers c ON cp.customer_id = c.id
        ORDER BY cp.points DESC
        LIMIT 10
    ");
    $stmt->execute();
    $top_points_customers = $stmt->fetchAll();

    // حركات النقاط
    $stmt = $pdo->prepare("
        SELECT pt.type, pt.description,
               COUNT(*) as transaction_count,
               SUM(pt.points) as total_points
        FROM point_transactions pt
        WHERE DATE(pt.created_at) BETWEEN ? AND ?
        GROUP BY pt.type, pt.description
        ORDER BY total_points DESC
    ");
    $stmt->execute([$date_from, $date_to]);
    $points_transactions = $stmt->fetchAll();

    // طرق الدفع
    $stmt = $pdo->prepare("
        SELECT payment_method,
               COUNT(*) as order_count,
               SUM(total) as total_amount
        FROM orders
        WHERE DATE(created_at) BETWEEN ? AND ?
        AND status NOT IN ('cancelled', 'returned')
        GROUP BY payment_method
        ORDER BY total_amount DESC
    ");
    $stmt->execute([$date_from, $date_to]);
    $payment_methods = $stmt->fetchAll();

    // حالات الطلبات
    $stmt = $pdo->prepare("
        SELECT status,
               COUNT(*) as order_count,
               SUM(total) as total_amount
        FROM orders
        WHERE DATE(created_at) BETWEEN ? AND ?
        GROUP BY status
        ORDER BY order_count DESC
    ");
    $stmt->execute([$date_from, $date_to]);
    $order_statuses = $stmt->fetchAll();

    // المنتجات الأكثر مشاهدة
    $stmt = $pdo->prepare("
        SELECT id, title, price, final_price, views, orders_count, rating_avg
        FROM products
        WHERE is_active = 1
        ORDER BY views DESC
        LIMIT 10
    ");
    $stmt->execute();
    $most_viewed_products = $stmt->fetchAll();

    // الكوبونات المستخدمة
    $stmt = $pdo->prepare("
        SELECT c.code, c.discount_type, c.discount_value,
               c.usage_count, c.usage_limit,
               SUM(o.discount_amount) as total_discount
        FROM coupons c
        LEFT JOIN orders o ON o.id IN (
            SELECT DISTINCT order_id FROM order_items 
            WHERE DATE(created_at) BETWEEN ? AND ?
        )
        WHERE c.usage_count > 0
        GROUP BY c.id, c.code, c.discount_type, c.discount_value, c.usage_count, c.usage_limit
        ORDER BY c.usage_count DESC
    ");
    $stmt->execute([$date_from, $date_to]);
    $coupon_usage = $stmt->fetchAll();

    // التقييمات والمراجعات
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_reviews,
            COUNT(CASE WHEN is_approved = 1 THEN 1 END) as approved_reviews,
            COUNT(CASE WHEN is_verified_purchase = 1 THEN 1 END) as verified_reviews,
            AVG(rating) as avg_rating
        FROM reviews
    ");
    $stmt->execute();
    $review_stats = $stmt->fetch();

} catch (Exception $e) {
    $error = "حدث خطأ في جلب البيانات: " . $e->getMessage();
}

// دالة لتحويل الفترة إلى نص عربي
function getPeriodText($period) {
    $periods = [
        'day' => 'يومي',
        'week' => 'أسبوعي',
        'month' => 'شهري',
        'year' => 'سنوي'
    ];
    return $periods[$period] ?? 'شهري';
}

// دالة حساب إجمالي الأرباح للتوزيع
function calculateTotalProfitForDistribution($paid_revenue, $expenses = 0) {
    return $paid_revenue - $expenses; // يمكن إضافة المصروفات هنا
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>التقارير الشاملة - لوحة التحكم</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8fafc;
            color: #334155;
        }
        .admin-wrapper { display: flex; min-height: 100vh; }
        
        .sidebar {
            width: 260px;
            background: #1e293b;
            color: #fff;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }
        .sidebar-header {
            padding: 25px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .sidebar-header h2 {
            font-size: 20px;
            color: #fff;
        }
        .sidebar-menu { padding: 20px 0; }
        .menu-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 20px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.3s;
        }
        .menu-item:hover, .menu-item.active {
            background: rgba(255,255,255,0.1);
            color: #fff;
        }
        .menu-item i { width: 20px; }
        
        .main-content {
            flex: 1;
            margin-right: 260px;
            padding: 30px;
        }
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .page-title h1 { font-size: 28px; color: #1e293b; }
        
        .filters {
            background: #fff;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .filter-row {
            display: flex;
            gap: 15px;
            align-items: end;
            flex-wrap: wrap;
        }
        .form-group {
            flex: 1;
            min-width: 150px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #334155;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 10px 12px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }
        .btn-primary { background: #2563eb; color: #fff; }
        .btn-primary:hover { background: #1d4ed8; }
        .btn-secondary { background: #64748b; color: #fff; }
        .btn-secondary:hover { background: #475569; }
        .btn-success { background: #16a34a; color: #fff; }
        .btn-success:hover { background: #15803d; }
        .btn-warning { background: #d97706; color: #fff; }
        .btn-warning:hover { background: #b45309; }
        
        .report-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .tab-btn {
            padding: 12px 24px;
            background: #f1f5f9;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }
        .tab-btn.active {
            background: #2563eb;
            color: #fff;
        }
        
        .card {
            background: #fff;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .card-header h2 {
            font-size: 20px;
            color: #1e293b;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: #fff;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: transform 0.3s;
        }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
        .stat-info h3 { font-size: 14px; color: #64748b; margin-bottom: 8px; }
        .stat-info .number { font-size: 32px; font-weight: 700; color: #1e293b; }
        .stat-info .change { font-size: 12px; color: #16a34a; }
        .stat-info .change.negative { color: #dc2626; }
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        .stat-card.blue .stat-icon { background: #dbeafe; color: #2563eb; }
        .stat-card.green .stat-icon { background: #dcfce7; color: #16a34a; }
        .stat-card.orange .stat-icon { background: #fed7aa; color: #ea580c; }
        .stat-card.purple .stat-icon { background: #e9d5ff; color: #9333ea; }
        .stat-card.red .stat-icon { background: #fecaca; color: #dc2626; }
        .stat-card.teal .stat-icon { background: #ccfbf1; color: #0d9488; }
        .stat-card.yellow .stat-icon { background: #fef3c7; color: #d97706; }
        .stat-card.pink .stat-icon { background: #fce7f3; color: #db2777; }
        
        .chart-container {
            background: #fff;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            height: 400px;
        }
        .charts-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .charts-grid .chart-container {
            height: 350px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px;
            text-align: right;
            border-bottom: 1px solid #e2e8f0;
        }
        th {
            background: #f8fafc;
            font-weight: 600;
            color: #475569;
        }
        tr:hover { background: #f8fafc; }
        
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-success { background: #dcfce7; color: #166534; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-danger { background: #fecaca; color: #991b1b; }
        .badge-info { background: #dbeafe; color: #1e40af; }
        .badge-secondary { background: #e2e8f0; color: #475569; }
        
        .alert {
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .alert-error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        
        .progress-bar {
            width: 100%;
            height: 8px;
            background: #e2e8f0;
            border-radius: 4px;
            overflow: hidden;
        }
        .progress-fill {
            height: 100%;
            background: #2563eb;
            border-radius: 4px;
        }
        
        .points-badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .financial-summary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 20px;
        }
        .financial-summary h3 {
            margin-bottom: 20px;
            font-size: 24px;
        }
        .financial-numbers {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        .financial-item {
            text-align: center;
        }
        .financial-item .value {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        .financial-item .label {
            font-size: 14px;
            opacity: 0.9;
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            .charts-grid {
                grid-template-columns: 1fr;
            }
            .filter-row {
                flex-direction: column;
            }
            .form-group {
                min-width: 100%;
            }
            .financial-numbers {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <!-- تضمين القائمة الجانبية -->
        <?php include 'sidebar.php'; ?>
        
        <!-- Main Content -->
        <main class="main-content">
            <div class="top-bar">
                <div class="page-title">
                    <h1>التقارير والإحصائيات الشاملة</h1>
                    <p style="color: #64748b; margin-top: 5px;">تحليل أداء المتجر ومبيعاته - <?= getPeriodText($period) ?></p>
                </div>
                <div>
                    <button class="btn btn-success" onclick="window.print()">
                        <i class="fas fa-print"></i>
                        طباعة التقرير
                    </button>
                </div>
            </div>

            <!-- Filters -->
            <div class="filters">
                <form method="get" action="reports.php">
                    <div class="filter-row">
                        <div class="form-group">
                            <label for="report_type">نوع التقرير</label>
                            <select id="report_type" name="report_type">
                                <option value="sales" <?= $report_type == 'sales' ? 'selected' : '' ?>>المبيعات</option>
                                <option value="products" <?= $report_type == 'products' ? 'selected' : '' ?>>المنتجات</option>
                                <option value="customers" <?= $report_type == 'customers' ? 'selected' : '' ?>>العملاء</option>
                                <option value="points" <?= $report_type == 'points' ? 'selected' : '' ?>>نظام النقاط</option>
                                <option value="partners" <?= $report_type == 'partners' ? 'selected' : '' ?>>النظام المحاسبي</option>
                                <option value="financial" <?= $report_type == 'financial' ? 'selected' : '' ?>>مالي</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="date_from">من تاريخ</label>
                            <input type="date" id="date_from" name="date_from" value="<?= htmlspecialchars($date_from) ?>">
                        </div>
                        <div class="form-group">
                            <label for="date_to">إلى تاريخ</label>
                            <input type="date" id="date_to" name="date_to" value="<?= htmlspecialchars($date_to) ?>">
                        </div>
                        <div class="form-group">
                            <label for="period">الفترة</label>
                            <select id="period" name="period">
                                <option value="day" <?= $period == 'day' ? 'selected' : '' ?>>يومي</option>
                                <option value="week" <?= $period == 'week' ? 'selected' : '' ?>>أسبوعي</option>
                                <option value="month" <?= $period == 'month' ? 'selected' : '' ?>>شهري</option>
                                <option value="year" <?= $period == 'year' ? 'selected' : '' ?>>سنوي</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter"></i>
                                تطبيق
                            </button>
                            <a href="reports.php" class="btn btn-secondary">
                                <i class="fas fa-refresh"></i>
                                إعادة تعيين
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= $error ?>
                </div>
            <?php endif; ?>

            <!-- Report Tabs -->
            <div class="report-tabs">
                <button class="tab-btn <?= $report_type == 'sales' ? 'active' : '' ?>" 
                        onclick="changeReportType('sales')">تقارير المبيعات</button>
                <button class="tab-btn <?= $report_type == 'products' ? 'active' : '' ?>" 
                        onclick="changeReportType('products')">تقارير المنتجات</button>
                <button class="tab-btn <?= $report_type == 'customers' ? 'active' : '' ?>" 
                        onclick="changeReportType('customers')">تقارير العملاء</button>
                <button class="tab-btn <?= $report_type == 'points' ? 'active' : '' ?>" 
                        onclick="changeReportType('points')">نظام النقاط</button>
                <button class="tab-btn <?= $report_type == 'partners' ? 'active' : '' ?>" 
                        onclick="changeReportType('partners')">النظام المحاسبي</button>
                <button class="tab-btn <?= $report_type == 'financial' ? 'active' : '' ?>" 
                        onclick="changeReportType('financial')">تقارير مالية</button>
            </div>

            <!-- Sales Report -->
            <?php if ($report_type == 'sales'): ?>
                <!-- Sales Stats -->
                <div class="stats-grid">
                    <div class="stat-card blue">
                        <div class="stat-info">
                            <h3>إجمالي الطلبات</h3>
                            <div class="number"><?= number_format($sales_stats['total_orders'] ?? 0) ?></div>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                    </div>

                    <div class="stat-card green">
                        <div class="stat-info">
                            <h3>إجمالي المبيعات</h3>
                            <div class="number"><?= number_format($sales_stats['total_revenue'] ?? 0, 0) ?></div>
                            <small style="color: #64748b;">ج.م</small>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                    </div>

                    <div class="stat-card orange">
                        <div class="stat-info">
                            <h3>متوسط قيمة الطلب</h3>
                            <div class="number"><?= number_format($sales_stats['avg_order_value'] ?? 0, 0) ?></div>
                            <small style="color: #64748b;">ج.م</small>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                    </div>

                    <div class="stat-card purple">
                        <div class="stat-info">
                            <h3>المبيعات المدفوعة</h3>
                            <div class="number"><?= number_format($sales_stats['paid_revenue'] ?? 0, 0) ?></div>
                            <small style="color: #64748b;">ج.م</small>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-credit-card"></i>
                        </div>
                    </div>
                </div>

                <!-- Charts -->
                <div class="charts-grid">
                    <div class="chart-container">
                        <h3 style="margin-bottom: 20px;">مبيعات الفترة</h3>
                        <canvas id="salesChart"></canvas>
                    </div>
                    
                    <div class="chart-container">
                        <h3 style="margin-bottom: 20px;">طرق الدفع</h3>
                        <canvas id="paymentChart"></canvas>
                    </div>
                </div>

                <!-- Top Products -->
                <div class="card">
                    <div class="card-header">
                        <h2>المنتجات الأكثر مبيعاً</h2>
                    </div>

                    <?php if (empty($top_products)): ?>
                        <p style="text-align: center; color: #64748b; padding: 40px;">لا توجد بيانات مبيعات</p>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>المنتج</th>
                                    <th>السعر</th>
                                    <th>الكمية المباعة</th>
                                    <th>إجمالي الإيرادات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($top_products as $product): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($product['title']) ?></td>
                                        <td><?= formatPrice($product['final_price']) ?></td>
                                        <td>
                                            <span class="badge badge-success"><?= $product['total_sold'] ?></span>
                                        </td>
                                        <td>
                                            <strong><?= formatPrice($product['total_revenue']) ?></strong>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>

                <!-- Order Statuses -->
                <div class="card">
                    <div class="card-header">
                        <h2>حالات الطلبات</h2>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>الحالة</th>
                                <th>عدد الطلبات</th>
                                <th>إجمالي القيمة</th>
                                <th>النسبة</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($order_statuses as $status): ?>
                                <tr>
                                    <td>
                                        <span class="badge badge-info"><?= getOrderStatusText($status['status']) ?></span>
                                    </td>
                                    <td><?= $status['order_count'] ?></td>
                                    <td><?= formatPrice($status['total_amount']) ?></td>
                                    <td>
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width: <?= ($status['order_count'] / $sales_stats['total_orders']) * 100 ?>%"></div>
                                        </div>
                                        <small><?= round(($status['order_count'] / $sales_stats['total_orders']) * 100, 1) ?>%</small>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            <?php elseif ($report_type == 'products'): ?>
                <!-- Product Stats -->
                <div class="stats-grid">
                    <div class="stat-card blue">
                        <div class="stat-info">
                            <h3>إجمالي المنتجات</h3>
                            <div class="number"><?= number_format($product_stats['total_products'] ?? 0) ?></div>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-cube"></i>
                        </div>
                    </div>

                    <div class="stat-card green">
                        <div class="stat-info">
                            <h3>المنتجات النشطة</h3>
                            <div class="number"><?= number_format($product_stats['active_products'] ?? 0) ?></div>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>

                    <div class="stat-card orange">
                        <div class="stat-info">
                            <h3>المنتجات المميزة</h3>
                            <div class="number"><?= number_format($product_stats['featured_products'] ?? 0) ?></div>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-star"></i>
                        </div>
                    </div>

                    <div class="stat-card red">
                        <div class="stat-info">
                            <h3>منتجات نفذت</h3>
                            <div class="number"><?= number_format($product_stats['out_of_stock'] ?? 0) ?></div>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                    </div>
                </div>

                <!-- Most Viewed Products -->
                <div class="card">
                    <div class="card-header">
                        <h2>المنتجات الأكثر مشاهدة</h2>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>المنتج</th>
                                <th>السعر</th>
                                <th>المشاهدات</th>
                                <th>الطلبات</th>
                                <th>التقييم</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($most_viewed_products as $product): ?>
                                <tr>
                                    <td><?= htmlspecialchars($product['title']) ?></td>
                                    <td><?= formatPrice($product['final_price']) ?></td>
                                    <td><?= number_format($product['views']) ?></td>
                                    <td><?= number_format($product['orders_count']) ?></td>
                                    <td>
                                        <div style="color: #fbbf24;">
                                            <?= str_repeat('★', round($product['rating_avg'])) ?><?= str_repeat('☆', 5 - round($product['rating_avg'])) ?>
                                            <small>(<?= number_format($product['rating_avg'], 1) ?>)</small>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            <?php elseif ($report_type == 'customers'): ?>
                <!-- Customer Stats -->
                <div class="stats-grid">
                    <div class="stat-card blue">
                        <div class="stat-info">
                            <h3>إجمالي العملاء</h3>
                            <div class="number"><?= number_format($customer_stats['total_customers'] ?? 0) ?></div>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>

                    <div class="stat-card green">
                        <div class="stat-info">
                            <h3>العملاء الموثقين</h3>
                            <div class="number"><?= number_format($customer_stats['verified_customers'] ?? 0) ?></div>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-user-check"></i>
                        </div>
                    </div>

                    <div class="stat-card orange">
                        <div class="stat-info">
                            <h3>إجمالي الطلبات</h3>
                            <div class="number"><?= number_format($customer_stats['total_customer_orders'] ?? 0) ?></div>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-shopping-bag"></i>
                        </div>
                    </div>

                    <div class="stat-card purple">
                        <div class="stat-info">
                            <h3>متوسط الإنفاق</h3>
                            <div class="number"><?= number_format($customer_stats['avg_customer_spent'] ?? 0, 0) ?></div>
                            <small style="color: #64748b;">ج.م</small>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                    </div>
                </div>

                <!-- Top Customers -->
                <div class="card">
                    <div class="card-header">
                        <h2>أفضل العملاء</h2>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>العميل</th>
                                <th>البريد الإلكتروني</th>
                                <th>عدد الطلبات</th>
                                <th>إجمالي الإنفاق</th>
                                <th>آخر طلب</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($top_customers as $customer): ?>
                                <tr>
                                    <td>
                                        <?= htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']) ?>
                                    </td>
                                    <td><?= htmlspecialchars($customer['email']) ?></td>
                                    <td><?= $customer['orders_count'] ?></td>
                                    <td><strong><?= formatPrice($customer['total_spent']) ?></strong></td>
                                    <td>
                                        <?= $customer['last_order_date'] ? date('Y-m-d', strtotime($customer['last_order_date'])) : '---' ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            <?php elseif ($report_type == 'financial'): ?>
                <!-- Financial Stats -->
                <div class="stats-grid">
                    <div class="stat-card blue">
                        <div class="stat-info">
                            <h3>إجمالي الإيرادات</h3>
                            <div class="number"><?= number_format($sales_stats['total_revenue'] ?? 0, 0) ?></div>
                            <small style="color: #64748b;">ج.م</small>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-money-bill"></i>
                        </div>
                    </div>

                    <div class="stat-card green">
                        <div class="stat-info">
                            <h3>المبيعات المدفوعة</h3>
                            <div class="number"><?= number_format($sales_stats['paid_revenue'] ?? 0, 0) ?></div>
                            <small style="color: #64748b;">ج.م</small>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-credit-card"></i>
                        </div>
                    </div>

                    <div class="stat-card red">
                        <div class="stat-info">
                            <h3>الطلبات الملغاة</h3>
                            <div class="number"><?= number_format($sales_stats['cancelled_orders'] ?? 0) ?></div>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-times-circle"></i>
                        </div>
                    </div>

                    <div class="stat-card teal">
                        <div class="stat-info">
                            <h3>المبيعات المسلمة</h3>
                            <div class="number"><?= number_format($sales_stats['delivered_revenue'] ?? 0, 0) ?></div>
                            <small style="color: #64748b;">ج.م</small>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-truck"></i>
                        </div>
                    </div>
                </div>

                <!-- Payment Methods -->
                <div class="card">
                    <div class="card-header">
                        <h2>طرق الدفع</h2>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>طريقة الدفع</th>
                                <th>عدد الطلبات</th>
                                <th>إجمالي القيمة</th>
                                <th>النسبة</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payment_methods as $method): ?>
                                <tr>
                                    <td><?= getPaymentMethodText($method['payment_method']) ?></td>
                                    <td><?= $method['order_count'] ?></td>
                                    <td><?= formatPrice($method['total_amount']) ?></td>
                                    <td>
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width: <?= ($method['order_count'] / $sales_stats['total_orders']) * 100 ?>%"></div>
                                        </div>
                                        <small><?= round(($method['order_count'] / $sales_stats['total_orders']) * 100, 1) ?>%</small>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Coupon Usage -->
                <div class="card">
                    <div class="card-header">
                        <h2>استخدام الكوبونات</h2>
                    </div>
                    <?php if (empty($coupon_usage)): ?>
                        <p style="text-align: center; color: #64748b; padding: 40px;">لا توجد كوبونات مستخدمة</p>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>الكود</th>
                                    <th>نوع الخصم</th>
                                    <th>قيمة الخصم</th>
                                    <th>مرات الاستخدام</th>
                                    <th>إجمالي الخصم</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($coupon_usage as $coupon): ?>
                                    <tr>
                                        <td><code><?= $coupon['code'] ?></code></td>
                                        <td><?= $coupon['discount_type'] == 'percentage' ? 'نسبة مئوية' : 'قيمة ثابتة' ?></td>
                                        <td>
                                            <?= $coupon['discount_type'] == 'percentage' ? 
                                                $coupon['discount_value'] . '%' : 
                                                formatPrice($coupon['discount_value']) ?>
                                        </td>
                                        <td>
                                            <?= $coupon['usage_count'] ?>
                                            <?php if ($coupon['usage_limit']): ?>
                                                / <?= $coupon['usage_limit'] ?>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= formatPrice($coupon['total_discount']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>

                <!-- Reviews Stats -->
                <div class="card">
                    <div class="card-header">
                        <h2>التقييمات والمراجعات</h2>
                    </div>
                    <div class="stats-grid">
                        <div class="stat-card blue">
                            <div class="stat-info">
                                <h3>إجمالي التقييمات</h3>
                                <div class="number"><?= number_format($review_stats['total_reviews'] ?? 0) ?></div>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-star"></i>
                            </div>
                        </div>

                        <div class="stat-card green">
                            <div class="stat-info">
                                <h3>التقييمات المعتمدة</h3>
                                <div class="number"><?= number_format($review_stats['approved_reviews'] ?? 0) ?></div>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                        </div>

                        <div class="stat-card orange">
                            <div class="stat-info">
                                <h3>متوسط التقييم</h3>
                                <div class="number"><?= number_format($review_stats['avg_rating'] ?? 0, 1) ?></div>
                                <div style="color: #fbbf24; font-size: 14px;">
                                    <?= str_repeat('★', round($review_stats['avg_rating'] ?? 0)) ?>
                                </div>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-chart-bar"></i>
                            </div>
                        </div>

                        <div class="stat-card purple">
                            <div class="stat-info">
                                <h3>مشتريات موثقة</h3>
                                <div class="number"><?= number_format($review_stats['verified_reviews'] ?? 0) ?></div>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-user-shield"></i>
                            </div>
                        </div>
                    </div>
                </div>

            <!-- Points System Report -->
            <?php elseif ($report_type == 'points'): ?>
                <!-- Points Stats -->
                <div class="stats-grid">
                    <div class="stat-card purple">
                        <div class="stat-info">
                            <h3>إجمالي النقاط المتاحة</h3>
                            <div class="number"><?= number_format($points_stats['total_points_balance'] ?? 0) ?></div>
                            <small style="color: #64748b;">نقطة</small>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-coins"></i>
                        </div>
                    </div>

                    <div class="stat-card green">
                        <div class="stat-info">
                            <h3>إجمالي النقاط المكتسبة</h3>
                            <div class="number"><?= number_format($points_stats['total_points_earned'] ?? 0) ?></div>
                            <small style="color: #64748b;">نقطة</small>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-download"></i>
                        </div>
                    </div>

                    <div class="stat-card orange">
                        <div class="stat-info">
                            <h3>إجمالي النقاط المستخدمة</h3>
                            <div class="number"><?= number_format($points_stats['total_points_spent'] ?? 0) ?></div>
                            <small style="color: #64748b;">نقطة</small>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-upload"></i>
                        </div>
                    </div>

                    <div class="stat-card blue">
                        <div class="stat-info">
                            <h3>متوسط النقاط للعميل</h3>
                            <div class="number"><?= number_format($points_stats['avg_points_per_customer'] ?? 0, 0) ?></div>
                            <small style="color: #64748b;">نقطة</small>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-user"></i>
                        </div>
                    </div>
                </div>

                <!-- Package Stats -->
                <div class="stats-grid">
                    <div class="stat-card teal">
                        <div class="stat-info">
                            <h3>إجمالي الباقات</h3>
                            <div class="number"><?= number_format($package_stats['total_packages'] ?? 0) ?></div>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-box"></i>
                        </div>
                    </div>

                    <div class="stat-card yellow">
                        <div class="stat-info">
                            <h3>إيرادات الباقات</h3>
                            <div class="number"><?= number_format($package_orders_stats['total_package_revenue'] ?? 0, 0) ?></div>
                            <small style="color: #64748b;">ج.م</small>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                    </div>

                    <div class="stat-card pink">
                        <div class="stat-info">
                            <h3>النقاط المباعة</h3>
                            <div class="number"><?= number_format($package_orders_stats['total_points_sold'] ?? 0) ?></div>
                            <small style="color: #64748b;">نقطة</small>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-shopping-bag"></i>
                        </div>
                    </div>

                    <div class="stat-card blue">
                        <div class="stat-info">
                            <h3>طلبات الباقات المدفوعة</h3>
                            <div class="number"><?= number_format($package_orders_stats['paid_package_orders'] ?? 0) ?></div>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                </div>

                <!-- Top Points Customers -->
                <div class="card">
                    <div class="card-header">
                        <h2>العملاء الأكثر نقاطاً</h2>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>العميل</th>
                                <th>البريد الإلكتروني</th>
                                <th>النقاط الحالية</th>
                                <th>إجمالي المكتسب</th>
                                <th>إجمالي المستخدم</th>
                                <th>الطلبات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($top_points_customers as $customer): ?>
                                <tr>
                                    <td><?= htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']) ?></td>
                                    <td><?= htmlspecialchars($customer['email']) ?></td>
                                    <td>
                                        <span class="points-badge"><?= number_format($customer['points']) ?> نقطة</span>
                                    </td>
                                    <td><?= number_format($customer['total_earned']) ?></td>
                                    <td><?= number_format($customer['total_spent']) ?></td>
                                    <td><?= $customer['orders_count'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Points Transactions -->
                <div class="card">
                    <div class="card-header">
                        <h2>حركات النقاط</h2>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>نوع الحركة</th>
                                <th>الوصف</th>
                                <th>عدد الحركات</th>
                                <th>إجمالي النقاط</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($points_transactions as $transaction): ?>
                                <tr>
                                    <td>
                                        <span class="badge <?= $transaction['type'] == 'earn' ? 'badge-success' : ($transaction['type'] == 'spend' ? 'badge-warning' : 'badge-danger') ?>">
                                            <?= $transaction['type'] == 'earn' ? 'اكتساب' : ($transaction['type'] == 'spend' ? 'استخدام' : 'انتهاء') ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($transaction['description']) ?></td>
                                    <td><?= $transaction['transaction_count'] ?></td>
                                    <td>
                                        <span class="<?= $transaction['type'] == 'earn' ? 'text-green-600' : 'text-red-600' ?>">
                                            <?= $transaction['type'] == 'earn' ? '+' : '-' ?><?= number_format($transaction['total_points']) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            <!-- Partners Accounting Report -->
            <?php elseif ($report_type == 'partners'): ?>
                <!-- Partners Stats -->
                <div class="stats-grid">
                    <div class="stat-card blue">
                        <div class="stat-info">
                            <h3>إجمالي الشركاء</h3>
                            <div class="number"><?= number_format($partner_stats['total_partners'] ?? 0) ?></div>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-handshake"></i>
                        </div>
                    </div>

                    <div class="stat-card green">
                        <div class="stat-info">
                            <h3>إجمالي الاستثمارات</h3>
                            <div class="number"><?= number_format($partner_stats['total_investment'] ?? 0, 0) ?></div>
                            <small style="color: #64748b;">ج.م</small>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                    </div>

                    <div class="stat-card orange">
                        <div class="stat-info">
                            <h3>متوسط نسبة الأرباح</h3>
                            <div class="number"><?= number_format($partner_stats['avg_profit_share'] ?? 0, 1) ?></div>
                            <small style="color: #64748b;">%</small>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-percentage"></i>
                        </div>
                    </div>

                    <div class="stat-card purple">
                        <div class="stat-info">
                            <h3>الشركاء النشطين</h3>
                            <div class="number"><?= number_format($partner_stats['active_partners'] ?? 0) ?></div>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-user-check"></i>
                        </div>
                    </div>
                </div>

                <!-- Financial Summary -->
                <div class="financial-summary">
                    <h3>ملخص التوزيع المالي للفترة</h3>
                    <div class="financial-numbers">
                        <div class="financial-item">
                            <div class="value"><?= number_format($sales_stats['paid_revenue'] ?? 0, 0) ?></div>
                            <div class="label">إجمالي الإيرادات</div>
                        </div>
                        <div class="financial-item">
                            <div class="value"><?= number_format(calculateTotalProfitForDistribution($sales_stats['paid_revenue'] ?? 0), 0) ?></div>
                            <div class="label">صافي الأرباح للتوزيع</div>
                        </div>
                        <div class="financial-item">
                            <div class="value"><?= number_format($partner_stats['total_partners'] ?? 0) ?></div>
                            <div class="label">عدد الشركاء</div>
                        </div>
                        <div class="financial-item">
                            <div class="value"><?= number_format($partner_stats['avg_profit_share'] ?? 0, 1) ?>%</div>
                            <div class="label">متوسط الحصص</div>
                        </div>
                    </div>
                </div>

                <!-- Partner Profit Distribution -->
                <div class="card">
                    <div class="card-header">
                        <h2>توزيع الأرباح على الشركاء</h2>
                        <button class="btn btn-warning" onclick="exportPartnerReport()">
                            <i class="fas fa-file-export"></i>
                            تصدير التقرير
                        </button>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>الشريك</th>
                                <th>الشركة</th>
                                <th>قيمة الاستثمار</th>
                                <th>نسبة الربح</th>
                                <th>الأرباح المتوقعة</th>
                                <th>جهة الاتصال</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $total_estimated_profit = 0;
                            foreach ($partner_profits as $partner): 
                                $total_estimated_profit += $partner['estimated_profit'];
                            ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($partner['name']) ?></strong>
                                    </td>
                                    <td><?= htmlspecialchars($partner['company'] ?? '---') ?></td>
                                    <td><?= formatPrice($partner['investment_amount']) ?></td>
                                    <td>
                                        <span class="badge badge-info"><?= $partner['profit_share'] ?>%</span>
                                    </td>
                                    <td>
                                        <strong><?= formatPrice($partner['estimated_profit']) ?></strong>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($partner['contact_person']) ?><br>
                                        <small><?= $partner['phone'] ?></small>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <tr style="background: #f8fafc; font-weight: 600;">
                                <td colspan="4" style="text-align: left;">الإجمالي</td>
                                <td colspan="2">
                                    <strong><?= formatPrice($total_estimated_profit) ?></strong>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Profit Distribution Chart -->
                <div class="chart-container">
                    <h3 style="margin-bottom: 20px;">توزيع الأرباح على الشركاء</h3>
                    <canvas id="partnerChart"></canvas>
                </div>

            <?php endif; ?>
        </main>
    </div>

    <script>
        function changeReportType(type) {
            const url = new URL(window.location.href);
            url.searchParams.set('report_type', type);
            window.location.href = url.toString();
        }

        function exportPartnerReport() {
            // يمكن تطوير هذه الدالة لتصدير تقرير الشركاء لاحقاً
            alert('سيتم تطوير خاصية التصدير في الإصدارات القادمة');
        }

        // Sales Chart
        <?php if (!empty($daily_sales)): ?>
        const salesCtx = document.getElementById('salesChart').getContext('2d');
        const salesChart = new Chart(salesCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode(array_column($daily_sales, 'sale_date')) ?>,
                datasets: [{
                    label: 'المبيعات اليومية',
                    data: <?= json_encode(array_column($daily_sales, 'total_revenue')) ?>,
                    borderColor: '#2563eb',
                    backgroundColor: 'rgba(37, 99, 235, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0,0,0,0.1)'
                        }
                    }
                }
            }
        });
        <?php endif; ?>

        // Payment Methods Chart
        <?php if (!empty($payment_methods)): ?>
        const paymentCtx = document.getElementById('paymentChart').getContext('2d');
        const paymentChart = new Chart(paymentCtx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_column($payment_methods, 'payment_method')) ?>,
                datasets: [{
                    data: <?= json_encode(array_column($payment_methods, 'total_amount')) ?>,
                    backgroundColor: [
                        '#2563eb', '#16a34a', '#ea580c', '#9333ea', '#dc2626'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
        <?php endif; ?>

        // Partner Distribution Chart
        <?php if (!empty($partner_profits)): ?>
        const partnerCtx = document.getElementById('partnerChart').getContext('2d');
        const partnerChart = new Chart(partnerCtx, {
            type: 'pie',
            data: {
                labels: <?= json_encode(array_column($partner_profits, 'name')) ?>,
                datasets: [{
                    data: <?= json_encode(array_column($partner_profits, 'estimated_profit')) ?>,
                    backgroundColor: [
                        '#2563eb', '#16a34a', '#ea580c', '#9333ea', '#dc2626',
                        '#d97706', '#059669', '#7c3aed', '#db2777', '#0d9488'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>