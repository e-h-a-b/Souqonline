<?php
/**
 * الصفحة الرئيسية للمتجر
 */
require_once 'functions.php';
// التحقق من وضع الصيانة
if (getSetting('maintenance_mode', '0') == '1' && !isset($_SESSION['admin_id'])) {
    header('Location: maintenance.php');
    exit;
} 
 // نظام النقاط اليومية
if (isset($_SESSION['customer_id'])) {
    require_once 'daily_points.php';
    
    $customer_id = $_SESSION['customer_id'];
    
    // منح النقاط اليومية
    $points_awarded = awardDailyVisitPoints($customer_id);
    
    if ($points_awarded) {
        $_SESSION['daily_points_message'] = getSetting('daily_visit_points_message', '🎉 مبروك! لقد حصلت على 5 نقاط مكافأة لزيارتك اليومية');
    }
    
    // الحصول على إحصائيات الزيارات
    $visit_stats = getVisitStats($customer_id);
}
$cartCount = getCartCount();
// الإعدادات
$storeName = getSetting('store_name', 'متجر إلكتروني');
$storeDescription = getSetting('store_description', '');

// جلب البيانات
$featuredProducts = getFeaturedProducts(8);
$topViewed = getTopViewedProducts(5);
$topOrdered = getTopOrderedProducts(5);
$categories = getCategories();

// البحث والفلاتر
$search = isset($_GET['search']) ? cleanInput($_GET['search']) : null;
$categoryId = isset($_GET['category']) ? (int)$_GET['category'] : null;
$sort = isset($_GET['sort']) ? cleanInput($_GET['sort']) : 'newest';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = getSetting('items_per_page', 12);

// جلب المنتجات حسب الفلاتر
// جلب المنتجات وتطبيق الخصومات والعروض
$productsData = getProducts([
    'limit' => $perPage,
    'offset' => ($page - 1) * $perPage,
    'category_id' => $categoryId,
    'search' => $search,
    'sort' => $sort
]);

// تطبيق جميع العروض على المنتجات
$products = array_map('applyBlackFridayDiscount', $productsData);
$products = array_map('applyCashbackToProduct', $products); // 🔥 تطبيق الكاشباك

// وكذلك للمنتجات المميزة
$featuredProducts = array_map('applyBlackFridayDiscount', $featuredProducts);
$featuredProducts = array_map('applyCashbackToProduct', $featuredProducts);

// عدد عناصر السلة
$cartCount = getCartCount();

// في ملف index.php أو wherever you get products
$stmt = $pdo->prepare("
    SELECT p.*, c.first_name, c.last_name 
    FROM products p 
    LEFT JOIN customers c ON p.created_by = c.id 
    WHERE p.is_active = 1 
    ORDER BY p.created_at DESC 
    LIMIT 20
");
// تفعيل خصومات الجمعة البيضاء تلقائياً
autoApplyBlackFridayDiscounts();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= htmlspecialchars($storeDescription) ?>">
 
<meta name="keywords" content="<?= htmlspecialchars(getSetting('meta_keywords', 'متجر, تسوق, شراء, عروض')) ?>">
   
    <title><?= htmlspecialchars($storeName) ?> - الصفحة الرئيسية</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
	<!-- مكتبة jsQR للمسح الضوئي -->
<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.js"></script>
 
<!-- مكتبة توليد QR Code -->
<script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
    <script src="assets/js/app.js" defer></script>
	<script src="assets/js/scripts.js" defer></script>
	<script>
	    window.customerData = {
        isLoggedIn: <?= isset($_SESSION['customer_id']) ? 'true' : 'false' ?>,
        customerId: <?= isset($_SESSION['customer_id']) ? $_SESSION['customer_id'] : '0' ?>
    };
	// إنشاء روابط صديقة للسيو
function generateSeoUrl($title, $id) {
    $slug = preg_replace('/[^\p{Arabic}\p{L}\p{N}\s]/u', '', $title);
    $slug = preg_replace('/\s+/', '-', $slug);
    $slug = trim($slug, '-');
  //  return $slug . '-' . $id;
}
	</script>
	<!-- إضافة rel="canonical" --> 

<!-- إضافة breadcrumbs -->
 
<STYLE>
/* أنماط نظام الكاشباك */
.cashback-badge {
    position: absolute;
    top: 200px;
    left: 10px;
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    padding: 0.4rem 0.7rem;
    border-radius: 15px;
    font-size: 0.7rem;
    font-weight: 700;
    z-index: 10;
    box-shadow: 0 3px 10px rgba(16, 185, 129, 0.3);
    animation: pulseCashback 2s infinite;
    display: flex;
    align-items: center;
    gap: 0.25rem;
    cursor: pointer;
    border: 1px solid rgba(255, 255, 255, 0.3);
}

@keyframes pulseCashback {
    0%, 100% { 
        transform: scale(1); 
        box-shadow: 0 3px 10px rgba(16, 185, 129, 0.3);
    }
    50% { 
        transform: scale(1.05); 
        box-shadow: 0 5px 15px rgba(16, 185, 129, 0.5);
    }
}

.cashback-amount {
    color: #10b981;
    font-weight: 700;
    font-size: 0.9rem;
    margin-top: 0.25rem;
}

.cashback-percentage {
    background: #10b981;
    color: white;
    padding: 0.2rem 0.5rem;
    border-radius: 10px;
    font-size: 0.7rem;
    font-weight: 600;
    margin-left: 0.5rem;
}

/* نافذة الكاشباك */
.cashback-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.8);
    backdrop-filter: blur(10px);
    z-index: 9999;
    align-items: center;
    justify-content: center;
    padding: 20px;
    box-sizing: border-box;
    animation: fadeIn 0.3s ease;
}

.cashback-content {
    background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
    border-radius: 20px;
    width: 100%;
    max-width: 400px;
    max-height: 90vh;
    overflow: hidden;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    border: 1px solid rgba(255, 255, 255, 0.2);
    animation: slideUp 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.cashback-header {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    padding: 1.5rem;
    position: relative;
    overflow: hidden;
    text-align: center;
}

.cashback-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="none"><path d="M0,0 L100,0 L100,100 Z" fill="rgba(255,255,255,0.1)"/></svg>');
    background-size: cover;
}

.cashback-header h3 {
    margin: 0;
    font-size: 1.3rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
    position: relative;
    z-index: 1;
}

.cashback-body {
    padding: 1.5rem;
    text-align: center;
}

.cashback-icon {
    font-size: 3rem;
    color: #10b981;
    margin-bottom: 1rem;
}

.cashback-title {
    font-size: 1.3rem;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 0.5rem;
}

.cashback-description {
    color: #64748b;
    font-size: 0.9rem;
    line-height: 1.5;
    margin-bottom: 1rem;
}

.cashback-details {
    background: #f1f5f9;
    padding: 1rem;
    border-radius: 10px;
    margin-bottom: 1.5rem;
    border-right: 4px solid #10b981;
}

.cashback-amount-large {
    font-size: 2rem;
    font-weight: 700;
    color: #10b981;
    margin-bottom: 0.5rem;
}

.cashback-info {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.cashback-info-item {
    background: white;
    padding: 1rem;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    text-align: center;
}

.info-label {
    font-size: 0.8rem;
    color: #64748b;
    margin-bottom: 0.25rem;
}

.info-value {
    font-size: 1rem;
    font-weight: 600;
    color: #1e293b;
}

.cashback-benefits {
    text-align: right;
    margin-bottom: 1.5rem;
}

.benefit-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
    color: #475569;
    font-size: 0.9rem;
}

.benefit-item i {
    color: #10b981;
}

.cashback-actions {
    padding: 1rem 1.5rem;
    background: #f8fafc;
    display: flex;
    gap: 1rem;
    border-top: 1px solid #e2e8f0;
}

.btn-close-cashback {
    flex: 1;
    background: white;
    color: #64748b;
    border: 2px solid #e2e8f0;
    padding: 0.75rem 1rem;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-close-cashback:hover {
    background: #f1f5f9;
    border-color: #cbd5e1;
}

.btn-learn-more {
    flex: 2;
    background: #10b981;
    color: white;
    border: none;
    padding: 0.75rem 1rem;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.btn-learn-more:hover {
    background: #059669;
    transform: translateY(-2px);
}
/* عداد التنازل للجمعة البيضاء */
.black-friday-countdown {
    background: darkcyan;
    color: white;
    padding: 1rem;
    text-align: center;
    margin: 1rem 0;
    border-radius: 10px;
    box-shadow: 0 4px 15px rgba(255, 68, 68, 0.3);
}

.countdown-title {
    font-size: 1.2rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.countdown-timer1 {
    display: flex;
    justify-content: center;
    gap: 1rem;
    font-size: 1.5rem;
    font-weight: 700;
}

.countdown-unit {
    background: rgba(255, 255, 255, 0.2);
    padding: 0.5rem 1rem;
    border-radius: 5px;
    min-width: 60px;
}

.countdown-label {
    font-size: 0.8rem;
    opacity: 0.8;
    margin-top: 0.25rem;
}

/* شارة الجمعة البيضاء المحسنة */
.black-friday-badge {
    position: absolute;
    top: 10px;
    left: 10px;
    background: linear-gradient(135deg, #000000, #ff4444);
    color: white;
    padding: 0.5rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 700;
    z-index: 10;
    box-shadow: 0 4px 12px rgba(255, 68, 68, 0.3);
    animation: pulseBlackFriday 2s infinite;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.black-friday-price {
    color: #ff4444 !important;
    font-weight: 700;
}

.original-price-strikethrough {
    text-decoration: line-through;
    opacity: 0.6;
    margin-left: 0.5rem;
}
/* شارة الجمعة البيضاء */
.black-friday-badge {
    position: absolute;
    top: 200px;
    left: 10px;
    background: darkcyan;
    color: white;
    padding: 0.5rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 700;
    z-index: 10;
    box-shadow: 0 4px 12px rgba(255, 68, 68, 0.3);
    animation: pulseBlackFriday 2s infinite;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

@keyframes pulseBlackFriday {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

.black-friday-ribbon {
    position: absolute;
    top: 20px;
    right: -30px;
    background: #ff4444;
    color: white;
    padding: 0.5rem 3rem;
    transform: rotate(45deg);
    font-weight: 700;
    font-size: 0.8rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.3);
    z-index: 100;
}
.product-image { 
    padding-top: 100%; 
}
.negotiation-btn {
    position: absolute;
    top: 60px;
    left: 10px;
    background: rgba(255, 193, 7, 0.9);
    border: none;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 1.2rem;
    transition: all 0.3s ease;
    z-index: 10;
}

.negotiation-btn:hover {
    background: #ffc107;
    transform: scale(1.1);
}

.negotiation-btn.negotiated {
    background: #28a745;
}

.negotiation-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
    align-items: center;
    justify-content: center;
}

.negotiation-content {
    background: white;
    padding: 2rem;
    border-radius: 10px;
    width: 90%;
    max-width: 400px;
    text-align: center;
    animation: slideIn 0.3s ease;
}

@keyframes slideIn {
    from { transform: translateY(-50px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

.negotiation-price {
    font-size: 1.5rem;
    color: #ffc107;
    margin: 1rem 0;
    font-weight: bold;
}

.negotiation-offer {
    margin: 1rem 0;
}

.negotiation-offer input {
    width: 100%;
    padding: 0.75rem;
    border: 2px solid #ddd;
    border-radius: 5px;
    text-align: center;
    font-size: 1.1rem;
}

.negotiation-min-price {
    color: #666;
    font-size: 0.9rem;
    margin-bottom: 1rem;
}

.negotiation-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
}
/* أنماط كارت الخربشة */
.scratch-card-container {
    position: relative;
    width: 300px;
    height: 200px;
    margin: 20px auto;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
}

.scratch-card {
    width: 100%;
    height: 100%;
    position: relative;
    cursor: crosshair;
}

.scratch-card-surface {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(45deg, #ff6b6b, #feca57, #48dbfb, #ff9ff3);
    background-size: 400% 400%;
    animation: shimmer 3s ease infinite;
}

.scratch-card-content {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    color: white;
    text-align: center;
    padding: 20px;
    box-sizing: border-box;
}

.scratch-card-reward {
    font-size: 1.5rem;
    font-weight: bold;
    margin-bottom: 10px;
}

.scratch-card-description {
    font-size: 0.9rem;
    opacity: 0.9;
}

.claim-reward-btn {
    background: #feca57;
    color: #000;
    border: none;
    padding: 10px 20px;
    border-radius: 25px;
    font-weight: bold;
    cursor: pointer;
    margin-top: 15px;
    transition: all 0.3s ease;
}

.claim-reward-btn:hover {
    background: #ff9ff3;
    transform: scale(1.05);
}

@keyframes shimmer {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}

.scratch-card-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.8);
    z-index: 2000;
    align-items: center;
    justify-content: center;
}

.scratch-card-modal-content {
    background: white;
    padding: 2rem;
    border-radius: 15px;
    text-align: center;
    max-width: 90%;
    max-height: 90vh;
    overflow-y: auto;
}

.scratch-cards-list {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.no-cards-message {
    text-align: center;
    padding: 2rem;
    color: #666;
}
/* أنماط كارت الخربشة */
.scratch-card-container {
    position: relative;
    width: 300px;
    height: 200px;
    margin: 20px auto;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
}

.scratch-card {
    width: 100%;
    height: 100%;
    position: relative;
    cursor: crosshair;
}

.scratch-card-surface {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(45deg, #ff6b6b, #feca57, #48dbfb, #ff9ff3);
    background-size: 400% 400%;
    animation: shimmer 3s ease infinite;
}

.scratch-card-content {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    color: white;
    text-align: center;
    padding: 20px;
    box-sizing: border-box;
}

.scratch-card-reward {
    font-size: 1.5rem;
    font-weight: bold;
    margin-bottom: 10px;
}

.scratch-card-description {
    font-size: 0.9rem;
    opacity: 0.9;
}

.claim-reward-btn {
    background: #feca57;
    color: #000;
    border: none;
    padding: 10px 20px;
    border-radius: 25px;
    font-weight: bold;
    cursor: pointer;
    margin-top: 15px;
    transition: all 0.3s ease;
}

.claim-reward-btn:hover {
    background: #ff9ff3;
    transform: scale(1.05);
}

@keyframes shimmer {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}

.scratch-card-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.8);
    z-index: 2000;
    align-items: center;
    justify-content: center;
}

.scratch-card-modal-content {
    background: white;
    padding: 2rem;
    border-radius: 15px;
    text-align: center;
    max-width: 90%;
    max-height: 90vh;
    overflow-y: auto;
}

.scratch-cards-list {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.no-cards-message {
    text-align: center;
    padding: 2rem;
    color: #666;
}
/* أنماط نافذة المزاد - تصميم حديث */
.auction-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.8);
    backdrop-filter: blur(10px);
    z-index: 9999;
    align-items: center;
    justify-content: center;
    padding: 20px;
    box-sizing: border-box;
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.auction-content {
    background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
    border-radius: 20px;
    width: 100%;
    max-width: 800px;
    max-height: 90vh;
    overflow: hidden;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    border: 1px solid rgba(255, 255, 255, 0.2);
    animation: slideUp 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(30px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

/* الهيدر */
.auction-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2rem;
    position: relative;
    overflow: hidden;
}

.auction-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="none"><path d="M0,0 L100,0 L100,100 Z" fill="rgba(255,255,255,0.1)"/></svg>');
    background-size: cover;
}

.auction-header h3 {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    position: relative;
    z-index: 1;
}

.auction-header h3 i {
    font-size: 1.75rem;
    color: #fbbf24;
}

.close-auction {
    position: absolute;
    top: 1.5rem;
    left: 1.5rem;
    background: rgba(255, 255, 255, 0.2);
    border: none;
    color: white;
    font-size: 1.5rem;
    cursor: pointer;
    padding: 0;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
    z-index: 1;
}

.close-auction:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: rotate(90deg);
}

/* جسم النافذة */
.auction-body {
    padding: 2rem;
    max-height: 60vh;
    overflow-y: auto;
    background: #ffffff;
}

/* معلومات المنتج */
.product-auction-info {
    display: flex;
    gap: 1.5rem;
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border-radius: 15px;
    border: 1px solid #e2e8f0;
    align-items: center;
}

.product-auction-info img {
    width: 100px;
    height: 100px;
    object-fit: cover;
    border-radius: 12px;
    border: 3px solid white;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.auction-details h4 {
    margin: 0 0 0.5rem 0;
    color: #1e293b;
    font-size: 1.25rem;
    font-weight: 600;
}

.current-bid {
    font-size: 1.5rem;
    color: #10b981;
    font-weight: 700;
    margin: 0.5rem 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.current-bid::before {
    content: '💰';
    font-size: 1.25rem;
}

.time-left {
    color: #64748b;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: #f1f5f9;
    padding: 0.5rem 1rem;
    border-radius: 25px;
    width: fit-content;
}

.time-left::before {
    content: '⏰';
    font-size: 0.8rem;
}

/* إحصائيات المزاد */
.bid-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
    margin-bottom: 2rem;
}

.stat-item {
    background: white;
    padding: 1.5rem;
    border-radius: 12px;
    text-align: center;
    border: 1px solid #e2e8f0;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.stat-item::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.stat-item:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
}

.stat-number {
    display: block;
    font-size: 1.75rem;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 0.5rem;
}

.stat-label {
    font-size: 0.85rem;
    color: #64748b;
    font-weight: 500;
}

/* قسم المزايدة */
.bid-section {
    background: white;
    padding: 1.5rem;
    border-radius: 15px;
    border: 1px solid #e2e8f0;
    margin-bottom: 2rem;
}

.bid-input-group {
    display: flex;
    gap: 1rem;
    margin-bottom: 1rem;
    align-items: center;
}

.bid-input-group input {
    flex: 1;
    padding: 1rem 1.5rem;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    font-size: 1.1rem;
    font-weight: 600;
    text-align: center;
    transition: all 0.3s ease;
    background: #f8fafc;
}

.bid-input-group input:focus {
    border-color: #667eea;
    background: white;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    outline: none;
}

.bid-input-group input::placeholder {
    color: #94a3b8;
}

.bid-hint {
    color: #64748b;
    font-size: 0.85rem;
    text-align: center;
    background: #f1f5f9;
    padding: 0.75rem;
    border-radius: 8px;
    border-right: 4px solid #10b981;
}

/* قائمة المشاركين */
.participants-list {
    background: white;
    border-radius: 15px;
    border: 1px solid #e2e8f0;
    overflow: hidden;
}

.participants-list h5 {
    margin: 0;
    padding: 1.25rem 1.5rem;
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    color: #1e293b;
    font-size: 1.1rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    border-bottom: 1px solid #e2e8f0;
}

.participants-list h5 i {
    color: #667eea;
}

#participants-container {
    max-height: 300px;
    overflow-y: auto;
}

/* عناصر المشاركين */
.participant-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid #f1f5f9;
    transition: all 0.3s ease;
    position: relative;
}

.participant-item:last-child {
    border-bottom: none;
}

.participant-item:hover {
    background: #f8fafc;
    transform: translateX(5px);
}

.participant-item::before {
    content: '';
    position: absolute;
    right: 0;
    top: 50%;
    transform: translateY(-50%);
    width: 4px;
    height: 0;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    transition: height 0.3s ease;
    border-radius: 2px;
}

.participant-item:hover::before {
    height: 60%;
}

.participant-info {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex: 1;
}

.participant-avatar {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    color: white;
    font-size: 1.1rem;
    position: relative;
    overflow: hidden;
}

.participant-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 50%;
}

.participant-details {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.participant-name {
    font-weight: 600;
    color: #1e293b;
    font-size: 1rem;
}

.participant-time {
    font-size: 0.8rem;
    color: #64748b;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.participant-bid {
    text-align: right;
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 0.25rem;
}

.bid-amount {
    font-weight: 700;
    color: #10b981;
    font-size: 1.1rem;
}

.bid-status {
    font-size: 0.75rem;
    font-weight: 600;
    padding: 0.25rem 0.75rem;
    border-radius: 15px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.bid-status.leading {
    background: #dcfce7;
    color: #166534;
}

.bid-status.outbid {
    background: #fef3c7;
    color: #92400e;
}

/* أزرار الإجراءات */
.auction-actions {
    padding: 1.5rem 2rem;
    background: #f8fafc;
    display: flex;
    gap: 1rem;
    border-top: 1px solid #e2e8f0;
}

.btn-bid {
    flex: 2;
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    border: none;
    padding: 1.25rem 2rem;
    border-radius: 12px;
    font-weight: 700;
    font-size: 1.1rem;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
    position: relative;
    overflow: hidden;
}

.btn-bid::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
    transition: left 0.5s;
}

.btn-bid:hover::before {
    left: 100%;
}

.btn-bid:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px -5px rgba(16, 185, 129, 0.4);
}

.btn-bid:active {
    transform: translateY(0);
}

.btn-bid:disabled {
    background: #94a3b8;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

.btn-bid:disabled:hover::before {
    left: -100%;
}

.btn-close {
    flex: 1;
    background: white;
    color: #64748b;
    border: 2px solid #e2e8f0;
    padding: 1.25rem 2rem;
    border-radius: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.btn-close:hover {
    background: #f1f5f9;
    border-color: #94a3b8;
    color: #475569;
}

/* شريط التمرير المخصص */
#participants-container::-webkit-scrollbar {
    width: 6px;
}

#participants-container::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 3px;
}

#participants-container::-webkit-scrollbar-thumb {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 3px;
}

#participants-container::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(135deg, #5a67d8 0%, #6b46c1 100%);
}

/* تصميم متجاوب */
@media (max-width: 768px) {
    .auction-modal {
        padding: 10px;
    }
    
    .auction-content {
        max-height: 95vh;
        border-radius: 15px;
    }
    
    .auction-header {
        padding: 1.5rem;
    }
    
    .auction-header h3 {
        font-size: 1.25rem;
    }
    
    .auction-body {
        padding: 1.5rem;
        max-height: 70vh;
    }
    
    .product-auction-info {
        flex-direction: column;
        text-align: center;
        gap: 1rem;
        padding: 1rem;
    }
    
    .bid-stats {
        grid-template-columns: 1fr;
        gap: 0.75rem;
    }
    
    .stat-item {
        padding: 1rem;
    }
    
    .bid-input-group {
        flex-direction: column;
    }
    
    .auction-actions {
        flex-direction: column-reverse;
        padding: 1.25rem;
    }
    
    .participant-item {
        padding: 1rem;
    }
    
    .participant-info {
        gap: 0.75rem;
    }
    
    .participant-avatar {
        width: 40px;
        height: 40px;
        font-size: 1rem;
    }
}

@media (max-width: 480px) {
    .auction-header {
        padding: 1.25rem;
    }
    
    .auction-body {
        padding: 1rem;
    }
    
    .close-auction {
        top: 1rem;
        left: 1rem;
        width: 35px;
        height: 35px;
    }
    
    .current-bid {
        font-size: 1.25rem;
    }
    
    .bid-input-group input {
        padding: 0.875rem 1rem;
        font-size: 1rem;
    }
}

/* رسوم متحركة إضافية */
@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

.pulse {
    animation: pulse 2s infinite;
}

/* تأثيرات خاصة للفائز */
.winning-bid {
    background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%) !important;
    border: 2px solid #f59e0b !important;
}

.winning-bid .bid-amount {
    color: #d97706 !important;
}

/* حالة المزاد المنتهي */
.auction-ended {
    opacity: 0.7;
}

.auction-ended .current-bid {
    color: #ef4444 !important;
}

/* تحسينات للوضع الليلي */
@media (prefers-color-scheme: dark) {
    .auction-content {
        background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
        color: #e2e8f0;
    }
    
    .auction-body {
        background: #1e293b;
    }
    
    .product-auction-info {
        background: linear-gradient(135deg, #334155 0%, #1e293b 100%);
        border-color: #475569;
    }
    
    .stat-item {
        background: #334155;
        border-color: #475569;
        color: #e2e8f0;
    }
    
    .bid-section {
        background: #334155;
        border-color: #475569;
    }
    
    .bid-input-group input {
        background: #475569;
        border-color: #64748b;
        color: #e2e8f0;
    }
    
    .participants-list {
        background: #334155;
        border-color: #475569;
    }
    
    .participants-list h5 {
        background: linear-gradient(135deg, #475569 0%, #334155 100%);
        color: #e2e8f0;
        border-color: #64748b;
    }
    
    .participant-item {
        border-color: #475569;
        color: #e2e8f0;
    }
    
    .participant-item:hover {
        background: #475569;
    }
    
    .auction-actions {
        background: #1e293b;
        border-color: #475569;
    }
    
    .btn-close {
        background: #475569;
        color: #e2e8f0;
        border-color: #64748b;
    }
    
    .btn-close:hover {
        background: #64748b;
    }
}
/* إضافة لأيقونة التاج للمزايدة الأعلى */
.participant-avatar {
    position: relative;
}

.participant-avatar .crown {
    position: absolute;
    top: -5px;
    right: -5px;
    background: gold;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
    border: 2px solid white;
}

.leading-bid {
    background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%) !important;
    border-left: 4px solid #f59e0b !important;
}

.leading-bid::before {
    background: #f59e0b !important;
}
/* أنماط عرض اشتري 2 واحصل على 1 مجاناً */
.buy2-get1-offer {
    position: absolute;
    top: 10px;
    left: 120px;
    background: linear-gradient(135deg, #ff6b6b, #ee5a52);
    border: none;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1rem;
    transition: all 0.3s ease;
    z-index: 10;
    box-shadow: 0 4px 12px rgba(255, 107, 107, 0.3);
    animation: pulseOffer 2s infinite;
}

@keyframes pulseOffer {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}

.buy2-get1-offer:hover {
    background: linear-gradient(135deg, #ff5252, #e53935);
    transform: scale(1.15);
    box-shadow: 0 6px 20px rgba(255, 107, 107, 0.4);
}

.buy2-get1-offer::after {
    content: '';
    position: absolute;
    top: -2px;
    left: -2px;
    right: -2px;
    bottom: -2px;
    border-radius: 50%;
    background: linear-gradient(135deg, #ff6b6b, #ffa726);
    z-index: -1;
    animation: rotate 3s linear infinite;
}

@keyframes rotate {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.buy2-get1-badge {
    position: absolute;
    top: 100px;
    left: 120px;
    background: linear-gradient(135deg, #ff6b6b, #ee5a52);
    color: white;
    padding: 0.4rem 0.8rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    z-index: 10;
    box-shadow: 0 4px 12px rgba(255, 107, 107, 0.3);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    display: flex;
    align-items: center;
    gap: 0.25rem;
    animation: slideInRight 0.5s ease;
}

@keyframes slideInRight {
    from {
        opacity: 0;
        transform: translateX(-20px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.buy2-get1-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.8);
    backdrop-filter: blur(10px);
    z-index: 9999;
    align-items: center;
    justify-content: center;
    padding: 20px;
    box-sizing: border-box;
    animation: fadeIn 0.3s ease;
}

.buy2-get1-content {
    background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
    border-radius: 20px;
    width: 100%;
    max-width: 500px;
    max-height: 90vh;
    overflow: hidden;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    border: 1px solid rgba(255, 255, 255, 0.2);
    animation: slideUp 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.buy2-get1-header {
    background: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%);
    color: white;
    padding: 2rem;
    position: relative;
    overflow: hidden;
    text-align: center;
}

.buy2-get1-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="none"><path d="M0,0 L100,0 L100,100 Z" fill="rgba(255,255,255,0.1)"/></svg>');
    background-size: cover;
}

.buy2-get1-header h3 {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
    position: relative;
    z-index: 1;
}

.buy2-get1-body {
    padding: 2rem;
    text-align: center;
}

.offer-icon-large {
    font-size: 4rem;
    color: #ff6b6b;
    margin-bottom: 1rem;
    animation: bounce 2s infinite;
}

@keyframes bounce {
    0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
    40% { transform: translateY(-10px); }
    60% { transform: translateY(-5px); }
}

.offer-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 1rem;
}

.offer-description {
    color: #64748b;
    font-size: 1rem;
    line-height: 1.6;
    margin-bottom: 1.5rem;
}

.offer-details {
    background: linear-gradient(135deg, #fff5f5 0%, #fed7d7 100%);
    padding: 1.5rem;
    border-radius: 15px;
    border: 2px dashed #fc8181;
    margin-bottom: 2rem;
}

.offer-steps {
    display: flex;
    justify-content: center;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.offer-step {
    text-align: center;
    flex: 1;
}

.step-number {
    width: 40px;
    height: 40px;
    background: #ff6b6b;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    margin: 0 auto 0.5rem;
}

.step-text {
    font-size: 0.85rem;
    color: #64748b;
    font-weight: 500;
}

.offer-price-example {
    background: white;
    padding: 1rem;
    border-radius: 10px;
    border: 1px solid #e2e8f0;
    margin-bottom: 1.5rem;
}

.price-example {
    display: flex;
    justify-content: center;
    gap: 1rem;
    align-items: center;
    font-size: 1.1rem;
    font-weight: 600;
}

.original-price {
    color: #64748b;
    text-decoration: line-through;
}

.final-price {
    color: #10b981;
    font-size: 1.25rem;
}

.savings {
    color: #ff6b6b;
    font-weight: 700;
}

.buy2-get1-actions {
    padding: 1.5rem 2rem;
    background: #f8fafc;
    display: flex;
    gap: 1rem;
    border-top: 1px solid #e2e8f0;
}

.btn-add-with-offer {
    flex: 2;
    background: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%);
    color: white;
    border: none;
    padding: 1.25rem 2rem;
    border-radius: 12px;
    font-weight: 700;
    font-size: 1.1rem;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
}

.btn-add-with-offer:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px -5px rgba(255, 107, 107, 0.4);
}

/* قسم المنتجات ذات العرض */
.buy2-get1-section {
    margin: 3rem 0;
    padding: 2rem 0;
    background: linear-gradient(135deg, #fff5f5 0%, #fed7d7 100%);
    border-radius: 20px;
}

.section-header-offer {
    text-align: center;
    margin-bottom: 2rem;
}

.section-header-offer h2 {
    color: #dc2626;
    font-size: 2rem;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1rem;
}

.section-header-offer p {
    color: #64748b;
    font-size: 1.1rem;
}

/* تصميم متجاوب */
@media (max-width: 768px) {
    .buy2-get1-offer {
        top: 10px;
        left: 60px;
        width: 35px;
        height: 35px;
        font-size: 0.9rem;
    }
    
    .buy2-get1-badge {
        top: 100px;
        left: 60px;
        font-size: 0.7rem;
        padding: 0.3rem 0.6rem;
    }
    
    .offer-steps {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .buy2-get1-actions {
        flex-direction: column;
    }
    
    .section-header-offer h2 {
        font-size: 1.5rem;
    }
}

/* تأثيرات خاصة للعرض في السلة */
.cart-offer-badge {
    background: linear-gradient(135deg, #ff6b6b, #ee5a52);
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: 15px;
    font-size: 0.75rem;
    font-weight: 600;
    margin-right: 0.5rem;
    animation: pulse 2s infinite;
}
.buy2-get1-offer {
    position: absolute;
    top: 37%;
    left: 120px;
    background: linear-gradient(135deg, #ff6b6b, #ee5a52);
    border: none;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    cursor: pointer;
    display: flex !important; /* تأكد من الإظهار */
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1rem;
    transition: all 0.3s ease;
    z-index: 10;
    box-shadow: 0 4px 12px rgba(255, 107, 107, 0.3);
    animation: pulseOffer 2s infinite;
}

.buy2-get1-badge {
    position: absolute;
    top: 37%;
    left: 160px;
    background: linear-gradient(135deg, #ff6b6b, #ee5a52);
    color: white;
    padding: 0.4rem 0.8rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    z-index: 10;
    box-shadow: 0 4px 12px rgba(255, 107, 107, 0.3);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    display: flex !important; /* تأكد من الإظهار */
    align-items: center;
    gap: 0.25rem;
    animation: slideInRight 0.5s ease;
}
/* تنسيقات النقاط اليومية */
.daily-points-alert {
    padding: 1rem 0;
    background: linear-gradient(135deg, #10b981, #059669);
}

.daily-points-alert .alert {
    background: rgba(255,255,255,0.95);
    color: #065f46;
    padding: 1rem 1.5rem;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
    border-right: 4px solid #10b981;
}

.daily-points-alert .alert i.fa-gift {
    color: #f59e0b;
    margin-left: 0.5rem;
}

.close-alert {
    background: none;
    border: none;
    color: #64748b;
    cursor: pointer;
    padding: 0.25rem;
    border-radius: 50%;
    transition: all 0.3s ease;
}

.close-alert:hover {
    background: #f1f5f9;
    color: #374151;
}

/* بطاقة إحصائيات الزيارات */
.visit-stats-card {
    background: linear-gradient(135deg, #fef3c7, #fde68a);
    border: 2px solid #f59e0b;
    border-radius: 15px;
    padding: 1.5rem;
    margin: 1rem 0;
    text-align: center;
}

.visit-stats-card h4 {
    color: #92400e;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
    margin-top: 1rem;
}

.stat-item {
    background: white;
    padding: 1rem;
    border-radius: 10px;
    border: 1px solid #fbbf24;
}

.stat-number {
    font-size: 1.5rem;
    font-weight: 700;
    color: #d97706;
    display: block;
}

.stat-label {
    font-size: 0.8rem;
    color: #92400e;
    margin-top: 0.25rem;
}
</style>
<style>
/* تنسيقات إضافية للهيدر */
.header-actions {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.wallet-section {
    position: relative;
}

.wallet-balance {
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}

.wallet-balance:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.wallet-charge-btn {
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}

.wallet-charge-btn:hover {
    background: #eab308 !important;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.packages-btn:hover,
.points-btn:hover,
.wishlist-btn:hover,
.cart-btn:hover,
.user-btn:hover {
    background: #e5e7eb !important;
    transform: translateY(-2px);
}

/* تصميم متجاوب */
@media (max-width: 768px) {
    .header-actions {
        gap: 5px;
    }
    
    .wallet-section {
        order: -1;
        width: 100%;
        justify-content: center;
        margin-bottom: 10px;
    }
    
    .wallet-balance,
    .wallet-charge-btn,
    .packages-btn,
    .points-btn,
    .wishlist-btn,
    .cart-btn,
    .user-btn {
        font-size: 12px;
        padding: 6px 10px;
    }
    
    .wallet-balance span,
    .points-count,
    .wishlist-count,
    .cart-count {
        font-size: 11px;
    }
}
</style>
<style>
/* تنسيقات خاصة بمنتجات متاجر المستخدمين */
.customer-store-product {
    position: relative;
    transition: all 0.3s ease;
}

.customer-store-product:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(139, 92, 246, 0.15);
    border-color: #7c3aed !important;
}

.customer-store-badge {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}

/* تحسين التصميم للهواتف */
@media (max-width: 768px) {
    .customer-store-badge {
        font-size: 0.7rem;
        padding: 0.4rem 0.6rem;
    }
    
    .store-owner {
        font-size: 0.8rem;
    }
}
</style>
<style>
/* تنسيقات الـ Tooltip */
.points-container {
    position: relative;
    display: inline-block;
}

.points-tooltip {
    position: absolute;
    top: 100%;
    left: 50%;
    transform: translateX(-50%) translateY(10px);
    background: linear-gradient(135deg, #ffffff, #f8fafc);
    border: 1px solid #e2e8f0;
    border-radius: 15px;
    padding: 1.25rem;
    width: 280px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
    z-index: 1000;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    backdrop-filter: blur(10px);
}

.points-tooltip::before {
    content: '';
    position: absolute;
    top: -8px;
    left: 50%;
    transform: translateX(-50%) rotate(45deg);
    width: 16px;
    height: 16px;
    background: white;
    border-left: 1px solid #e2e8f0;
    border-top: 1px solid #e2e8f0;
}

.points-tooltip.show {
    opacity: 1;
    visibility: visible;
    transform: translateX(-50%) translateY(5px);
}

/* هيدر الـ Tooltip */
.tooltip-header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1rem;
    padding-bottom: 0.75rem;
    border-bottom: 2px solid #f1f5f9;
}

.tooltip-header h4 {
    margin: 0;
    color: #1e293b;
    font-size: 1.1rem;
    font-weight: 700;
}

.tooltip-header i {
    color: #f59e0b;
    font-size: 1.2rem;
}

/* إحصائيات الـ Tooltip */
.tooltip-stats {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.stat-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
}

.stat-row:not(:last-child) {
    border-bottom: 1px solid #f1f5f9;
}

.stat-row.highlight {
    background: linear-gradient(135deg, #fffbeb, #fef3c7);
    margin: 0.5rem -1rem;
    padding: 0.75rem 1rem;
    border-radius: 10px;
    border: 1px solid #fde68a;
}

.stat-label {
    color: #64748b;
    font-size: 0.85rem;
    font-weight: 500;
}

.stat-value {
    color: #1e293b;
    font-weight: 600;
    font-size: 0.9rem;
}

.points-available {
    color: #d97706;
    font-weight: 700;
}

.today-visit.visited {
    color: #10b981;
    font-weight: 700;
}

.today-visit.not-visited {
    color: #ef4444;
    font-weight: 700;
}

.monthly-visits {
    color: #8b5cf6;
}

.visit-points {
    color: #059669;
    font-weight: 700;
}

.next-reward {
    color: #dc2626;
    font-weight: 700;
}

/* فوتر الـ Tooltip */
.tooltip-footer {
    margin-top: 1rem;
    padding-top: 0.75rem;
    border-top: 1px solid #f1f5f9;
    text-align: center;
}

.tooltip-footer small {
    color: #94a3b8;
    font-size: 0.75rem;
}

/* تأثيرات الـ Hover */
.points-btn {
    position: relative;
    transition: all 0.3s ease;
}

.points-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
}

/* تحسينات للهواتف */
@media (max-width: 768px) {
    .points-tooltip {
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%) scale(0.9);
        width: 90%;
        max-width: 300px;
    }
    
    .points-tooltip.show {
        transform: translate(-50%, -50%) scale(1);
    }
    
    .points-tooltip::before {
        display: none;
    }
}
</style>
<style>
/* أيقونة QR Code */
.qr-discount-btn {
    position: absolute;
    top: 150px;
    right: 10px;
    background: rgba(34, 197, 94, 0.9);
    border: none;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
    transition: all 0.3s ease;
    z-index: 10;
    box-shadow: 0 4px 12px rgba(34, 197, 94, 0.3);
}

.qr-discount-btn:hover {
    background: #22c55e;
    transform: scale(1.1);
    box-shadow: 0 6px 20px rgba(34, 197, 94, 0.4);
}

 

#qrModal { 
    display:none;
    position: fixed;
    top: 0%;
    right: 30%;
    width: 50%;
    height: 100%;
    overflow: auto;
    background: #1f2937;
    color: white;
    padding: 0.75rem 1rem;
    border-radius: 8px;
    font-size: 0.8rem;
    transition: all 0.3s ease;
    z-index: 1000;
    align-items: center;
    justify-content: center;
}

 

/* نافذة QR Code */
.qr-modal .modal-content {
    max-width: 500px;
    text-align: center;
}

.qr-content {
    padding: 2rem;
}

.qr-code-image {
    margin: 1rem 0;
    padding: 1rem;
    background: white;
    border-radius: 10px;
    border: 2px dashed #e5e7eb;
}

.qr-instructions {
    background: #f8fafc;
    padding: 1rem;
    border-radius: 8px;
    margin: 1rem 0;
    text-align: right;
}

.qr-instructions h4 {
    color: #374151;
    margin-bottom: 0.5rem;
}

.qr-instructions ol {
    text-align: right;
    padding-right: 1.5rem;
}

.qr-details {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin: 1rem 0;
}

.qr-detail-item {
    background: white;
    padding: 1rem;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
}

.detail-label {
    font-weight: 600;
    color: #6b7280;
    font-size: 0.875rem;
}

.detail-value {
    font-weight: 700;
    color: #1f2937;
    font-size: 1rem;
}

/* نافذة الماسح الضوئي */
.qr-scanner-modal .modal-content {
    max-width: 600px;
}

.scanner-instructions {
    background: #f0f9ff;
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1rem;
    border-right: 4px solid #3b82f6;
}

.scanner-instructions h4 {
    color: #1e40af;
    margin-bottom: 0.5rem;
}

.scanner-instructions ol {
    text-align: right;
    padding-right: 1.5rem;
}

.scanner-area {
    margin: 1rem 0;
    position: relative;
}

.scanner-result {
    margin-top: 1rem;
    padding: 1rem;
    border-radius: 8px;
    text-align: center;
    font-weight: 600;
}

.scanner-result.valid {
    background: #dcfce7;
    color: #166534;
    border: 1px solid #bbf7d0;
}

.scanner-result.invalid {
    background: #fef2f2;
    color: #991b1b;
    border: 1px solid #fecaca;
}

.manual-input {
    margin-top: 1rem;
    padding: 1rem;
    background: #f8fafc;
    border-radius: 8px;
}

.manual-input label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: #374151;
}

.manual-input input {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    margin-bottom: 0.5rem;
}

/* تصميم متجاوب */
@media (max-width: 768px) {
    .qr-details {
        grid-template-columns: 1fr;
    }
    
    .qr-discount-btn {
        top: 5px;
        left: 5px;
        width: 35px;
        height: 35px;
        font-size: 1rem;
    }
     
}
/* الإشعارات */
.notification {
    position: fixed;
    top: 20px;
    right: 20px;
    background: white;
    border-radius: 8px;
    padding: 1rem 1.5rem;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    border-left: 4px solid #3b82f6;
    transform: translateX(400px);
    transition: transform 0.3s ease;
    z-index: 10000;
    max-width: 350px;
}

.notification.show {
    transform: translateX(0);
}

.notification-success {
    border-left-color: #10b981;
}

.notification-error {
    border-left-color: #ef4444;
}

.notification-content {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.notification-content i {
    font-size: 1.25rem;
}

.notification-success .notification-content i {
    color: #10b981;
}

.notification-error .notification-content i {
    color: #ef4444;
}

.notification-content span {
    color: #374151;
    font-weight: 500;
}

/* تحسين النافذة */
.qr-header {
    text-align: center;
    margin-bottom: 1.5rem;
}

.qr-header i {
    font-size: 2rem;
    color: #3b82f6;
    margin-bottom: 0.5rem;
}

.qr-header h4 {
    margin: 0;
    color: #1f2937;
    font-size: 1.25rem;
}

/* تصميم متجاوب */
@media (max-width: 768px) {
    .qr-details {
        grid-template-columns: 1fr !important;
    }
    
    .qr-actions {
        flex-direction: column;
    }
    
    .notification {
        right: 10px;
        left: 10px;
        max-width: none;
    }
}
</style>
<style>
/* أنماط العروض الذكية في الصفحة الرئيسية */
.smart-offers-badges {
    position: absolute;
    top: 10px;
    left: 10px;
    display: flex;
    flex-direction: column;
    gap: 5px;
    z-index: 20;
}

.smart-offer-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 0.7rem;
    font-weight: 600;
    text-align: center;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}

.offer-buy2_get1 {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
}

.offer-coupon {
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: white;
}

.offer-qr_code {
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    color: white;
}

.offer-points {
    background: linear-gradient(135deg, #8b5cf6, #7c3aed);
    color: white;
}

.offer-flash_sale {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white;
}

.offer-bundle {
    background: linear-gradient(135deg, #7dd3fc, #0ea5e9);
    color: white;
}
</style>
<script>
// تحديث عداد النقاط في الهيدر
function updatePointsCount() {
    fetch('ajax/get_points.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const pointsCount = document.getElementById('points-count');
                if (pointsCount) {
                    pointsCount.textContent = data.formatted_points;
                }
            }
        })
        .catch(error => console.error('Error updating points count:', error));
}

// تحديث جميع العدادات
function updateAllCounters() {
    updatePointsCount();
    updateWishlistCount();
    updateCartCount();
}

// تحديث تلقائي للعدادات كل 30 ثانية
setInterval(updateAllCounters, 30000);

// تحديث عند تحميل الصفحة
document.addEventListener('DOMContentLoaded', function() {
    updateAllCounters();
});

// دوال المفضلة والسلة الموجودة...
function toggleWishlist(productId) {
    console.log('Toggle wishlist called for product:', productId);
    
    <?php if (!isset($_SESSION['customer_id'])): ?>
        showToast('يجب تسجيل الدخول لإضافة المنتج إلى المفضلة', 'warning');
        setTimeout(() => {
            window.location.href = 'account.php?redirect=' + encodeURIComponent(window.location.href);
        }, 2000);
        return;
    <?php endif; ?>
    
    const wishlistBtn = document.getElementById('wishlist-btn-' + productId);
    const wishlistIcon = document.getElementById('wishlist-icon-' + productId);
    const wishlistText = document.getElementById('wishlist-text-' + productId);
    
    wishlistBtn.disabled = true;
    wishlistIcon.className = 'fas fa-spinner fa-spin';
    
    fetch('ajax/wishlist.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'toggle',
            product_id: productId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (data.in_wishlist) {
                wishlistBtn.classList.add('in-wishlist');
                wishlistIcon.className = 'fas fa-heart';
                wishlistText.textContent = 'في المفضلة';
                showToast('تمت إضافة المنتج إلى المفضلة', 'success');
            } else {
                wishlistBtn.classList.remove('in-wishlist');
                wishlistIcon.className = 'far fa-heart';
                wishlistText.textContent = 'أضف إلى المفضلة';
                showToast('تمت إزالة المنتج من المفضلة', 'info');
            }
            
            updateWishlistCount();
        } else {
            showToast(data.message || 'حدث خطأ', 'error');
            resetWishlistButton(productId);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('حدث خطأ في الاتصال', 'error');
        resetWishlistButton(productId);
    })
    .finally(() => {
        wishlistBtn.disabled = false;
    });
}

// دوال أخرى...
function changeImage(src) {
    document.getElementById('main-product-image').src = src;
    document.querySelectorAll('.thumb').forEach(t => t.classList.remove('active'));
    event.target.classList.add('active');
}

function openTab(tabName) {
    document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById(tabName).classList.add('active');
    event.target.classList.add('active');
}

function increaseQty(max) {
    const input = document.getElementById('product-quantity');
    if (parseInt(input.value) < max) {
        input.value = parseInt(input.value) + 1;
    }
}

function decreaseQty() {
    const input = document.getElementById('product-quantity');
    if (parseInt(input.value) > 1) {
        input.value = parseInt(input.value) - 1;
    }
}

function addToCartFromDetail(productId) {
    const qty = parseInt(document.getElementById('product-quantity').value);
    addToCart(productId, qty);
}


// دوال التفاوض
function openNegotiation(productId, currentPrice) {
    if (!window.customerData.isLoggedIn) {
        showToast('يجب تسجيل الدخول للتفاوض على السعر', 'warning');
        setTimeout(() => {
            window.location.href = 'account.php?redirect=' + encodeURIComponent(window.location.href);
        }, 2000);
        return;
    }

    const modal = document.getElementById('negotiation-modal');
    const productIdElem = document.getElementById('negotiation-product-id');
    const currentPriceElem = document.getElementById('negotiation-current-price');
    const minPriceElem = document.getElementById('negotiation-min-price');
    const offerInput = document.getElementById('negotiation-offer');
    
    productIdElem.value = productId;
    currentPriceElem.textContent = formatPrice(currentPrice);
    
    // حساب الحد الأدنى للتفاوض (70% من السعر)
    const minPrice = currentPrice * 0.7;
    minPriceElem.textContent = formatPrice(minPrice);
    
    // تعيين القيمة الافتراضية
    offerInput.value = Math.round(minPrice);
    offerInput.min = Math.round(minPrice);
    offerInput.max = Math.round(currentPrice - 1);
    
    modal.style.display = 'flex';
}

function closeNegotiation() {
    const modal = document.getElementById('negotiation-modal');
    modal.style.display = 'none';
}

function submitNegotiation() {
    const productId = document.getElementById('negotiation-product-id').value;
    const offeredPrice = document.getElementById('negotiation-offer').value;
    const notes = document.getElementById('negotiation-notes').value;
    const submitBtn = document.getElementById('negotiation-submit');
    
    if (!offeredPrice || offeredPrice <= 0) {
        showToast('يرجى إدخال سعر مقترح', 'error');
        return;
    }
    
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جاري الإرسال...';
    
    fetch('ajax/negotiate.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `product_id=${productId}&offered_price=${offeredPrice}&notes=${encodeURIComponent(notes)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            closeNegotiation();
            
            // تحديث زر التفاوض
            const negotiateBtn = document.getElementById(`negotiate-btn-${productId}`);
            if (negotiateBtn) {
                negotiateBtn.classList.add('negotiated');
                negotiateBtn.innerHTML = '<i class="fas fa-check"></i>';
                negotiateBtn.title = 'تم إرسال طلب التفاوض';
                
                // إظهار السعر المقترح
                const priceDisplay = document.getElementById(`negotiated-price-${productId}`);
                if (priceDisplay) {
                    priceDisplay.textContent = `مقترح: ${formatPrice(offeredPrice)}`;
                    priceDisplay.style.display = 'block';
                }
            }
        } else {
            if (data.redirect) {
                window.location.href = data.redirect;
            } else {
                showToast(data.message, 'error');
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('حدث خطأ في الاتصال', 'error');
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-handshake"></i> إرسال التفاوض';
    });
}

// دالة مساعدة لتنسيق السعر
function formatPrice(price) {
    return new Intl.NumberFormat('ar-EG', {
        style: 'currency',
        currency: 'EGP'
    }).format(price);
}

// إغلاق النافذة عند النقر خارجها
document.addEventListener('click', function(event) {
    const modal = document.getElementById('negotiation-modal');
    if (event.target === modal) {
        closeNegotiation();
    }
});

// تحديث السعر المقترح أثناء الكتابة
document.addEventListener('input', function(event) {
    if (event.target.id === 'negotiation-offer') {
        const offer = event.target.value;
        const currentPrice = parseFloat(document.getElementById('negotiation-current-price').textContent.replace(/[^\d.]/g, ''));
        const minPrice = currentPrice * 0.7;
        
        if (offer < minPrice) {
            event.target.style.borderColor = '#dc3545';
        } else if (offer >= currentPrice) {
            event.target.style.borderColor = '#dc3545';
        } else {
            event.target.style.borderColor = '#28a745';
        }
    }
});
</script>
</head>
<body>

<!-- Header -->
<header class="site-header">

    <div class="container">
        <div class="header-content">
            <div class="logo">
                <a href="index.php">
                    <h1><?= htmlspecialchars(getSetting('seo_h1', $storeName)) ?></h1>
                </a>
            </div>
            
            <div class="header-search">
                <form action="index.php" method="get" class="search-form">
                    <input type="text" name="search" placeholder="ابحث عن منتج..." 
                           value="<?= htmlspecialchars($search ?? '') ?>">
                    <button type="submit"><i class="fas fa-search"></i></button>
                </form>
            </div>
            
            <div class="header-actions">
                <!-- رصيد المحفظة وزر الشحن -->
                <?php if (isset($_SESSION['customer_id']) && getSetting('wallet_enabled', '1') == '1'): ?>
                    <?php 
                    $wallet_data = getCustomerWallet($_SESSION['customer_id']);
                    $wallet_balance = $wallet_data['balance'] ?? 0;
                    ?>
                    <div class="wallet-section" style="display: flex; align-items: center; gap: 10px; margin-left: 15px;">
                        <!-- عرض الرصيد -->
                        <div class="wallet-balance" style="background: linear-gradient(135deg, #10b981, #34d399); color: white; padding: 8px 12px; border-radius: 20px; font-weight: 600; display: flex; align-items: center; gap: 5px;">
                            
                        <!-- زر شحن المحفظة -->
                        <a href="account.php?tab=wallet" class="wallet-charge-btn" style=" color: white; padding: 8px 12px; border-radius: 20px; text-decoration: none; font-weight: 600; display: flex; align-items: center; gap: 5px; font-size: 14px; transition: all 0.3s;">
                            <i class="fas fa-plus-circle"></i>
                            <span></span>
                        </a>
						<i class="fas fa-wallet" style="font-size: 14px;"></i>
                            <span id="wallet-balance"><?= number_format($wallet_balance, 2) ?></span>
                            <span style="font-size: 12px;">ج.م</span>
                        </div>
                        
                    </div>
                <?php endif; ?>

                <!-- باقات النقاط -->
                <?php if (isset($_SESSION['customer_id']) && getSetting('points_enabled', '1') == '1'): ?>
                    <a href="packages.php" class="packages-btn" title="باقات النقاط" style="display: flex; align-items: center; gap: 5px; text-decoration: none; color: #374151; padding: 8px 12px; border-radius: 20px; background: #f3f4f6; transition: all 0.3s;">
                        <i class="fas fa-crown" style="color: #f59e0b;"></i>
                        <span style="font-weight: 600;">الباقات</span>
                    </a>
                <?php endif; ?>

<!-- نقاطي -->
<?php if (isset($_SESSION['customer_id']) && getSetting('points_enabled', '1') == '1'): ?>
    <?php 
    $customer_points = getCustomerPoints($_SESSION['customer_id']);
    $available_points = $customer_points['available_points'] ?? 0;
    $visit_stats = getVisitStats($_SESSION['customer_id']);
    ?>
    <div class="points-container" id="points-tooltip-container">
        <a href="account.php?tab=points" class="points-btn" id="points-trigger" 
           title="نقاطي" 
           style="display: flex; align-items: center; gap: 5px; text-decoration: none; color: #374151; padding: 8px 12px; border-radius: 20px; background: linear-gradient(135deg, #fef3c7, #fde68a); transition: all 0.3s;">
            <i class="fas fa-coins" style="color: #d97706;"></i>
            <span class="points-count" id="points-count" style="font-weight: 600;">
                <?= number_format($available_points) ?>
            </span>
        </a>
        
        <!-- Tooltip -->
        <div class="points-tooltip" id="points-tooltip">
            <div class="tooltip-header">
                <i class="fas fa-chart-line"></i>
                <h4>إحصائيات نقاطك</h4>
            </div>
            
            <div class="tooltip-stats">
                <div class="stat-row">
                    <span class="stat-label">النقاط المتاحة:</span>
                    <span class="stat-value points-available"><?= number_format($available_points) ?></span>
                </div>
                
                <div class="stat-row">
                    <span class="stat-label">زيارة اليوم:</span>
                    <span class="stat-value today-visit <?= $visit_stats['today_visited'] ? 'visited' : 'not-visited' ?>">
                        <?= $visit_stats['today_visited'] ? '✅ تمت' : '⏳ لم تتم' ?>
                    </span>
                </div>
                
                <div class="stat-row">
                    <span class="stat-label">الزيارات هذا الشهر:</span>
                    <span class="stat-value monthly-visits"><?= $visit_stats['monthly_visits'] ?></span>
                </div>
                
                <div class="stat-row">
                    <span class="stat-label">النقاط من الزيارات:</span>
                    <span class="stat-value visit-points">+<?= $visit_stats['total_points_earned'] ?></span>
                </div>
                
                <div class="stat-row highlight">
                    <span class="stat-label">مكافأة الغد:</span>
                    <span class="stat-value next-reward">+5 نقاط</span>
                </div>
            </div>
            
            <div class="tooltip-footer">
                <small>⏳ تنتهي بعض النقاط بعد 30 يوم</small>
            </div>
        </div>
    </div>
<?php endif; ?>
                <a href="market-survey.php"   class="wishlist-btn"  style="display: flex; align-items: center; gap: 5px; text-decoration: none; color: #374151; padding: 8px 12px; border-radius: 20px; background: #f3f4f6; transition: all 0.3s;">
                 <i class='fas fa-th-list' style='font-size:24px'></i>
                </a>
                <!-- المفضلة -->
                <a href="wishlist.php" class="wishlist-btn" style="display: flex; align-items: center; gap: 5px; text-decoration: none; color: #374151; padding: 8px 12px; border-radius: 20px; background: #f3f4f6; transition: all 0.3s;">
                    <i class="fas fa-heart" style="color: #ef4444;"></i>
                    <span class="wishlist-count" id="wishlist-count" style="font-weight: 600;">
                        <?= getWishlistCount() ?>
                    </span>
                </a>

                <!-- السلة -->
                <a href="cart.php" class="cart-btn" style="display: flex; align-items: center; gap: 5px; text-decoration: none; color: #374151; padding: 8px 12px; border-radius: 20px; background: #f3f4f6; transition: all 0.3s;">
                    <i class="fas fa-shopping-cart" style="color: #2563eb;"></i>
                    <span class="cart-count" id="cart-count" style="font-weight: 600;"><?= $cartCount ?></span>
                     
                </a>
                <!-- التبرع -->
                <a href="donation_page.php" class="user-btn" style="display: flex; align-items: center; gap: 5px; text-decoration: none; color: #374151; padding: 8px 12px; border-radius: 20px; background: #f3f4f6; transition: all 0.3s;">
                     <i class="fa-solid fa-hand-holding-hand"></i>
                </a>
                <!-- حسابي -->
                <a href="account.php" class="user-btn" style="display: flex; align-items: center; gap: 5px; text-decoration: none; color: #374151; padding: 8px 12px; border-radius: 20px; background: #f3f4f6; transition: all 0.3s;">
                    <i class="fas fa-user" style="color: #8b5cf6;"></i> 
                </a>
            </div>
        </div>
        
        <!-- Navigation -->
        <nav class="main-nav">
            <ul>
                <li><a href="index.php" class="active">الرئيسية</a></li>
                <?php foreach ($categories as $cat): ?>
                    <li>
                        <a href="index.php?category=<?= $cat['id'] ?>">
                            <?= htmlspecialchars($cat['name']) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
                <li><a href="about.php">من نحن</a></li>
                <li><a href="contact.php">اتصل بنا</a></li>
								<!-- أضف هذا الزر في بداية قسم المحتوى الرئيسي -->
<button class="sidebar-toggle" id="sidebarToggle" title="إظهار/إخفاء الشريط الجانبي">
    <i class="fas fa-bars"></i>
</button>
            </ul>
        </nav>
    </div>
</header>
<!-- عرض رسالة النقاط اليومية -->
<?php if (isset($_SESSION['daily_points_message'])): ?>
<div class="daily-points-alert">
    <div class="container">
        <div class="alert alert-success">
            <i class="fas fa-gift"></i>
            <?= $_SESSION['daily_points_message'] ?>
            <button type="button" class="close-alert" onclick="this.parentElement.style.display='none'">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
</div>
<?php 
unset($_SESSION['daily_points_message']);
endif; ?>


<?php require 'Product-items.php' ?>

<?php require 'ads.php' ?>
	<?php if (!$search && !$categoryId): ?>

    <!-- Hero Section -->
   
    <section class="hero-slider">
        <div class="hero-slide active">
            <div class="container">
                <div class="hero-content">
                    <h2 class="hero-title">عروض حصرية تصل إلى 50%</h2>
                    <p class="hero-subtitle">اكتشف أحدث المنتجات بأفضل الأسعار</p>
                    <a href="#products" class="btn btn-primary">تسوق الآن</a>
                </div>
            </div>
        </div>
    </section>

	<!-- Featured Products -->
    <?php if (!empty($featuredProducts)): ?>
    <section class="featured-section">
        <div class="container">
            <div class="section-header">
                <h2>المنتجات المميزة</h2>
                <p>اختيارات خاصة من فريقنا</p>
            </div>
            <div class="products-grid">
                <?php foreach ($featuredProducts as $product): ?>
                    <div class="product-card featured">
                        <?php if ($product['discount_percentage'] > 0): ?>
                            <span class="badge-discount">-<?= $product['discount_percentage'] ?>%</span>
                        <?php endif; ?>
                        <a href="product.php?id=<?= $product['id'] ?>" class="product-image">
                            <img src="<?= htmlspecialchars($product['main_image'] ?: 'assets/images/placeholder.jpg') ?>" 
                                 alt="<?= htmlspecialchars($product['title']) ?>">
                        </a>
                        <div class="product-info">
                            <h3 class="product-title">
                                <a href="product.php?id=<?= $product['id'] ?>">
                                    <?= htmlspecialchars($product['title']) ?>
                                </a>
                            </h3>
                            <div class="product-rating">
                                <?php 
                                $rating = $product['rating_avg'];
                                for ($i = 1; $i <= 5; $i++): 
                                    if ($i <= $rating): ?>
                                        <i class="fas fa-star"></i>
                                    <?php elseif ($i - 0.5 <= $rating): ?>
                                        <i class="fas fa-star-half-alt"></i>
                                    <?php else: ?>
                                        <i class="far fa-star"></i>
                                    <?php endif;
                                endfor; ?>
                                <span>(<?= $product['rating_count'] ?>)</span>
                            </div>
                            <div class="product-price">
                                <?php if ($product['discount_percentage'] > 0): ?>
                                    <span class="price-old"><?= formatPrice($product['price']) ?></span>
                                    <span class="price-new"><?= formatPrice($product['final_price']) ?></span>
                                <?php else: ?>
                                    <span class="price-new"><?= formatPrice($product['price']) ?></span>
                                <?php endif; ?>
                            </div>
                            <button class="btn btn-add-cart" onclick="addToCart(<?= $product['id'] ?>)">
                                <i class="fas fa-cart-plus"></i> أضف للسلة
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <!-- بعد قسم المنتجات المميزة في index.php -->
<?php
// جلب المنتجات التي بها عرض اشتري 2 واحصل على 1
$buy2Get1Products = getBuyTwoGetOneProducts(8);
?>

<?php if (!empty($buy2Get1Products)): ?>
<section class="buy2-get1-section">
    <div class="container">
        <div class="section-header-offer">
            <h2>
                <i class="fas fa-crown"></i>
                عروض خاصة
                <i class="fas fa-crown"></i>
            </h2>
            <p>اشتري قطعتين واحصل على الثالثة مجاناً</p>
        </div>
        
        <div class="products-grid">
            <?php foreach ($buy2Get1Products as $product): ?>
                <div class="product-card">
                    <!-- أيقونة العرض -->
                    <button class="buy2-get1-offer" 
                            onclick="openBuy2Get1Offer(<?= $product['id'] ?>, '<?= addslashes($product['title']) ?>', <?= $product['final_price'] ?>, '<?= $product['main_image'] ?>')"
                            title="اشتري قطعتين واحصل على الثالثة مجاناً">
                        <i class="fas fa-gift"></i>
                    </button>
                    
                    <div class="buy2-get1-badge">
                        <i class="fas fa-crown"></i>
                        2+1 مجاناً
                    </div>

                    <!-- باقي كرت المنتج -->
                    <?php if ($product['discount_percentage'] > 0): ?>
                        <span class="badge-discount">-<?= $product['discount_percentage'] ?>%</span>
                    <?php endif; ?>
                    
                    <a href="product.php?id=<?= $product['id'] ?>" class="product-image">
                        <img src="<?= htmlspecialchars($product['main_image'] ?: 'assets/images/placeholder.jpg') ?>" 
                             alt="<?= htmlspecialchars($product['title']) ?>">
                    </a>
                    
                    <div class="product-info">
                        <h3 class="product-title">
                            <a href="product.php?id=<?= $product['id'] ?>">
                                <?= htmlspecialchars($product['title']) ?>
                            </a>
                        </h3>
                        
<div class="product-price">
    <?php if (isset($product['is_black_friday']) && $product['is_black_friday']): ?>
        <span class="price-old original-price-strikethrough">
            <?= formatPrice($product['black_friday_original_price'] ?? $product['price']) ?>
        </span>
        <span class="price-new black-friday-price">
            <?= formatPrice($product['final_price']) ?>
        </span>
        <div style="color: #ff4444; font-size: 0.8rem; margin-top: 0.25rem;">
            وفر <?= $product['discount_percentage'] ?>%
        </div>
    <?php elseif ($product['discount_percentage'] > 0): ?>
        <span class="price-old"><?= formatPrice($product['price']) ?></span>
        <span class="price-new"><?= formatPrice($product['final_price']) ?></span>
    <?php else: ?>
        <span class="price-new"><?= formatPrice($product['price']) ?></span>
    <?php endif; ?>
</div>
                        
                        <button class="btn btn-add-cart" onclick="addThreeWithOffer(<?= $product['id'] ?>)">
                            <i class="fas fa-gift"></i> احصل على العرض
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>
		</div>
    </section>
    <?php endif; ?> 
    <?php endif; ?> 


    <!-- Footer -->
    <footer class="site-footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3><?= htmlspecialchars($storeName) ?></h3>
                    <p><?= htmlspecialchars($storeDescription) ?></p>
                    <div class="social-links">
                        <?php if ($fb = getSetting('facebook_url')): ?>
                            <a href="<?= htmlspecialchars($fb) ?>" target="_blank"><i class="fab fa-facebook"></i></a>
                        <?php endif; ?>
                        <?php if ($ig = getSetting('instagram_url')): ?>
                            <a href="<?= htmlspecialchars($ig) ?>" target="_blank"><i class="fab fa-instagram"></i></a>
                        <?php endif; ?>
                        <?php if ($tw = getSetting('twitter_url')): ?>
                            <a href="<?= htmlspecialchars($tw) ?>" target="_blank"><i class="fab fa-twitter"></i></a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="footer-section">
                    <h4>روابط سريعة</h4>
                    <ul>
                        <li><a href="index.php">الرئيسية</a></li>
                        <li><a href="about.php">من نحن</a></li>
                        <li><a href="contact.php">اتصل بنا</a></li>
                        <li><a href="privacy.php">سياسة الخصوصية</a></li>
                        <li><a href="terms.php">الشروط والأحكام</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>خدمة العملاء</h4>
                    <ul>
                        <li><a href="faq.php">الأسئلة الشائعة</a></li>
                        <li><a href="shipping.php">سياسة الشحن</a></li>
                        <li><a href="returns.php">سياسة الاسترجاع</a></li>
                        <li><a href="track.php">تتبع الطلب</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>تواصل معنا</h4>
                    <ul class="contact-info">
                        <li><i class="fas fa-phone"></i> <?= getSetting('store_phone', '') ?></li>
                        <li><i class="fas fa-envelope"></i> <?= getSetting('store_email', '') ?></li>
                        <?php if ($whatsapp = getSetting('whatsapp_number')): ?>
                            <li>
                                <a href="https://wa.me/<?= $whatsapp ?>" target="_blank">
                                    <i class="fab fa-whatsapp"></i> تواصل واتساب
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?= date('Y') ?> <?= htmlspecialchars($storeName) ?>. جميع الحقوق محفوظة.</p>
            </div>
        </div>
    </footer>

    <!-- Notification Toast -->
    <div id="toast" class="toast"></div>
<!-- نافذة التفاوض -->
<div id="negotiation-modal" class="negotiation-modal">
    <div class="negotiation-content">
        <h3><i class="fas fa-handshake"></i> التفاوض على السعر</h3>
        
        <div class="negotiation-price">
            السعر الحالي: <span id="negotiation-current-price">0</span>
        </div>
        
        <div class="negotiation-offer">
            <input type="number" id="negotiation-offer" placeholder="أدخل السعر المقترح">
            <div class="negotiation-min-price">
                الحد الأدنى: <span id="negotiation-min-price">0</span>
            </div>
        </div>
        
        <div class="negotiation-notes">
            <textarea id="negotiation-notes" placeholder="ملاحظات إضافية (اختياري)" 
                     rows="3" style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 5px;"></textarea>
        </div>
        
        <div class="negotiation-actions">
            <button type="button" class="btn btn-secondary" onclick="closeNegotiation()">
                <i class="fas fa-times"></i> إلغاء
            </button>
            <button type="button" class="btn btn-warning" id="negotiation-submit" onclick="submitNegotiation()">
                <i class="fas fa-handshake"></i> إرسال التفاوض
            </button>
        </div>
    </div>
    
    <input type="hidden" id="negotiation-product-id">
</div>
<!-- تضمين السطر الذكي الجانبي -->
<?php include 'smart_command_sidebar.php'; ?>

<!-- زر تشغيل السطر الذكي -->
<button class="smart-command-trigger">
    <i class="fas fa-robot"></i>
</button>
<script>
  












// دوال كروت الخربشة
function openScratchCard(productId) {
    if (!window.customerData.isLoggedIn) {
        showToast('يجب تسجيل الدخول للمشاركة في خربش واكسب', 'warning');
        setTimeout(() => {
            window.location.href = 'account.php?redirect=' + encodeURIComponent(window.location.href);
        }, 2000);
        return;
    }

    fetch('ajax/scratch_card.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=get_cards&product_id=${productId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showScratchCardsModal(data.cards, productId);
        } else {
            showToast(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('حدث خطأ في الاتصال', 'error');
    });
}

function showScratchCardsModal(cards, productId) {
    const modal = document.createElement('div');
    modal.className = 'scratch-card-modal';
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.8);
        z-index: 2000;
        display: flex;
        align-items: center;
        justify-content: center;
    `;

    let cardsHTML = '';
    if (cards.length > 0) {
        cards.forEach(card => {
            cardsHTML += `
                <div class="scratch-card-container">
                    <div class="scratch-card" id="scratch-card-${card.id}">
                        <div class="scratch-card-surface" id="scratch-surface-${card.id}"></div>
                        <div class="scratch-card-content" style="display: none;" id="reward-content-${card.id}">
                            <div class="scratch-card-reward">
                                ${getRewardText(card)}
                            </div>
                            <div class="scratch-card-description">
                                ${card.reward_description || 'مبروك! لقد فزت بهذه الجائزة'}
                            </div>
                            <button class="claim-reward-btn" onclick="claimReward(${card.id})">
                                المطالبة بالجائزة
                            </button>
                        </div>
                    </div>
                </div>
            `;
        });
    } else {
        cardsHTML = `
            <div class="no-cards-message">
                <i class="fas fa-gift" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                <h3>لا توجد كروت خربشة متاحة حالياً</h3>
                <p>تابع متجرنا للحصول على عروض جديدة!</p>
            </div>
        `;
    }

    modal.innerHTML = `
        <div class="scratch-card-modal-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h3><i class="fas fa-gift"></i> خربش واكسب</h3>
                <button onclick="this.closest('.scratch-card-modal').remove()" 
                        style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">×</button>
            </div>
            
            <div class="scratch-cards-list">
                ${cardsHTML}
            </div>
            
            <div style="margin-top: 1rem; color: #666;">
                <small>• قم بخربشة السطح الملون لإظهار الجائزة</small><br>
                <small>• كل كارت يمكن استخدامه مرة واحدة فقط</small>
            </div>
        </div>
    `;

    document.body.appendChild(modal);

    // تهيئة كروت الخربشة
    if (cards.length > 0) {
        initializeScratchCards();
    }
}

function initializeScratchCards() {
    // استخدام مكتبة Scratchcard.js أو تنفيذ مخصص
    // هذا مثال مبسط باستخدام canvas
    document.querySelectorAll('.scratch-card').forEach(card => {
        const cardId = card.id.replace('scratch-card-', '');
        initScratchCard(cardId);
    });
}

function initScratchCard(cardId) {
    const canvas = document.createElement('canvas');
    const container = document.getElementById(`scratch-card-${cardId}`);
    const surface = document.getElementById(`scratch-surface-${cardId}`);
    
    // إعداد canvas للخربشة
    canvas.width = container.offsetWidth;
    canvas.height = container.offsetHeight;
    canvas.style.position = 'absolute';
    canvas.style.top = '0';
    canvas.style.left = '0';
    canvas.style.cursor = 'crosshair';
    
    surface.appendChild(canvas);
    
    const ctx = canvas.getContext('2d');
    ctx.fillStyle = '#f39c12';
    ctx.fillRect(0, 0, canvas.width, canvas.height);
    
    // إضافة نص "اخربش هنا"
    ctx.fillStyle = '#fff';
    ctx.font = 'bold 20px Arial';
    ctx.textAlign = 'center';
    ctx.fillText('اخربش هنا', canvas.width / 2, canvas.height / 2);
    
    let isDrawing = false;
    let percentScratched = 0;
    
    canvas.addEventListener('mousedown', startScratching);
    canvas.addEventListener('mousemove', scratch);
    canvas.addEventListener('mouseup', stopScratching);
    canvas.addEventListener('touchstart', startScratching);
    canvas.addEventListener('touchmove', scratch);
    canvas.addEventListener('touchend', stopScratching);
    
    function startScratching(e) {
        isDrawing = true;
        scratch(e);
    }
    
    function scratch(e) {
        if (!isDrawing) return;
        
        const rect = canvas.getBoundingClientRect();
        const x = (e.clientX || e.touches[0].clientX) - rect.left;
        const y = (e.clientY || e.touches[0].clientY) - rect.top;
        
        // رسم دائرة شفافة مكان الخربشة
        ctx.globalCompositeOperation = 'destination-out';
        ctx.beginPath();
        ctx.arc(x, y, 20, 0, Math.PI * 2);
        ctx.fill();
        
        // حساب النسبة المئوية للمخدوش
        checkScratchProgress();
    }
    
    function stopScratching() {
        isDrawing = false;
    }
    
    function checkScratchProgress() {
        const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
        const pixels = imageData.data;
        let transparentPixels = 0;
        
        for (let i = 0; i < pixels.length; i += 4) {
            if (pixels[i + 3] === 0) {
                transparentPixels++;
            }
        }
        
        percentScratched = (transparentPixels / (pixels.length / 4)) * 100;
        
        // إذا تم خربشة أكثر من 60%، إظهار الجائزة
        if (percentScratched > 60) {
            revealReward(cardId);
        }
    }
    
    function revealReward(cardId) {
        // إخفاء surface وإظهار المحتوى
        surface.style.display = 'none';
        document.getElementById(`reward-content-${cardId}`).style.display = 'flex';
        
        // تسجيل الخربشة في السيرفر
        fetch('ajax/scratch_card.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=scratch&card_id=${cardId}`
        });
    }
}

function getRewardText(card) {
    switch (card.reward_type) {
        case 'points':
            return `${card.reward_value} نقطة`;
        case 'discount':
            return `خصم ${card.reward_value}%`;
        case 'gift':
            return `هدية: ${card.reward_description}`;
        case 'cash':
            return `${card.reward_value} جنيه`;
        default:
            return 'جائزة خاصة';
    }
}

function claimReward(cardId) {
    fetch('ajax/scratch_card.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=claim&card_id=${cardId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('تمت المطالبة بالجائزة بنجاح!', 'success');
            // تحديث عرض النقاط إذا كانت الجائزة نقاط
            if (data.reward.reward_type === 'points') {
                updatePointsCount();
            }
            // إغلاق النافذة بعد ثانيتين
            setTimeout(() => {
                const modal = document.querySelector('.scratch-card-modal');
                if (modal) modal.remove();
            }, 2000);
        } else {
            showToast(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('حدث خطأ في الاتصال', 'error');
    });
}


// دوال المزاد - الإصاح المحسّن
function openAuctionModal(productId) {
    console.log('🔄 فتح نافذة المزاد للمنتج:', productId);
    
    if (!window.customerData || !window.customerData.isLoggedIn) {
        showToast('يجب تسجيل الدخول للمشاركة في المزاد', 'warning');
        setTimeout(() => {
            window.location.href = 'account.php?redirect=' + encodeURIComponent(window.location.href);
        }, 2000);
        return;
    }

    // إظهار رسالة تحميل
    showToast('جاري تحميل بيانات المزاد...', 'info');

    // إضافة مؤشر تحميل
    const loadingSpinner = document.createElement('div');
    loadingSpinner.className = 'loading-spinner';
    loadingSpinner.innerHTML = `
        <div style="position:fixed; top:50%; left:50%; transform:translate(-50%,-50%); 
                   background:white; padding:2rem; border-radius:10px; z-index:10000;
                   box-shadow:0 10px 30px rgba(0,0,0,0.3); text-align:center;">
            <i class="fas fa-spinner fa-spin fa-2x" style="color:#667eea; margin-bottom:1rem;"></i>
            <p>جاري تحميل بيانات المزاد...</p>
        </div>
    `;
    document.body.appendChild(loadingSpinner);

    fetch(`ajax/get_auction.php?product_id=${productId}&t=${Date.now()}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`خطأ في الشبكة: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('📊 بيانات المزاد المستلمة:', data);
            loadingSpinner.remove();
            
            if (data.success) {
                showAuctionParticipants(data);
            } else {
                showToast('❌ ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('❌ خطأ في جلب بيانات المزاد:', error);
            loadingSpinner.remove();
            showToast('❌ حدث خطأ في تحميل بيانات المزاد: ' + error.message, 'error');
        });
}

function showAuctionParticipants(auctionData) {
    console.log('🎨 عرض نافذة المزاد:', auctionData);
    
    // إغلاق أي نافذة مزاد مفتوحة مسبقاً
    closeAuctionModal();

    const modal = document.createElement('div');
    modal.className = 'auction-modal';
    modal.style.display = 'flex'; // إضافة هذه السطر المهم
    modal.innerHTML = `
        <div class="auction-content">
            <div class="auction-header">
                <h3><i class="fas fa-gavel"></i> المزاد العلني</h3>
                <button class="close-auction" onclick="closeAuctionModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="auction-body">
                <div class="product-auction-info">
                    <img src="${auctionData.product_image}" alt="${auctionData.product_title}" 
                         onerror="this.src='assets/images/placeholder.jpg'">
                    <div class="auction-details">
                        <h4>${auctionData.product_title || 'منتج بدون عنوان'}</h4>
                        <div class="current-bid">
                            <i class="fas fa-tag"></i>
                            ${formatPrice(auctionData.current_bid || 0)}
                        </div>
                        <div class="time-left auction-timer" id="modal-auction-timer">
                            <i class="fas fa-clock"></i>
                            ${auctionData.time_left || 'غير محدد'}
                        </div>
                    </div>
                </div>

                <div class="bid-stats">
                    <div class="stat-item">
                        <span class="stat-number">${auctionData.stats?.total_bids || 0}</span>
                        <span class="stat-label">عدد المزايدات</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number">${auctionData.stats?.total_bidders || 0}</span>
                        <span class="stat-label">عدد المزايدين</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number">${formatPrice(auctionData.stats?.average_bid || auctionData.current_bid || 0)}</span>
                        <span class="stat-label">متوسط المزايدات</span>
                    </div>
                </div>

                <div class="bid-section">
                    <div class="bid-input-group">
                        <input type="number" id="bid-amount" 
                               min="${auctionData.min_bid || 1}" 
                               value="${auctionData.min_bid || 1}"
                               placeholder="أدخل مبلغ المزايدة"
                               step="1">
                    </div>
                    <div class="bid-hint">
                        <i class="fas fa-info-circle"></i>
                        الحد الأدنى للمزايدة: ${formatPrice(auctionData.min_bid || 1)}
                    </div>
                </div>

                <div class="participants-list">
                    <h5><i class="fas fa-users"></i> قائمة المزايدين (${auctionData.participants?.length || 0})</h5>
                    <div id="participants-container">
                        ${renderParticipants(auctionData.participants || [])}
                    </div>
                </div>
            </div>

            <div class="auction-actions">
                <button class="btn-close" onclick="closeAuctionModal()">
                    <i class="fas fa-times"></i> إغلاق
                </button>
                <button class="btn-bid" onclick="submitBid(${auctionData.product_id})" id="submit-bid-btn">
                    <i class="fas fa-gavel"></i> تقديم المزايدة
                </button>
            </div>
        </div>
    `;

    document.body.appendChild(modal);
    console.log('✅ نافذة المزاد معروضة بنجاح');
    
    // بدء التحديث التلقائي
    startAuctionAutoRefresh(auctionData.product_id);
}

function renderParticipants(participants) {
    if (!participants || participants.length === 0) {
        return `
            <div class="no-participants" style="text-align: center; padding: 3rem; color: #666;">
                <i class="fas fa-users-slash fa-3x" style="margin-bottom: 1rem; opacity: 0.5;"></i>
                <h4 style="margin: 0 0 0.5rem 0;">لا توجد مزايدات حتى الآن</h4>
                <p style="margin: 0; font-size: 0.9rem;">كن أول المزايدين على هذا المنتج!</p>
            </div>
        `;
    }

    return participants.map((participant, index) => {
        const avatarContent = participant.avatar ? 
            `<img src="${participant.avatar}" alt="${participant.first_name}" 
                  style="width:100%;height:100%;border-radius:50%;object-fit:cover;">` : 
            `<span>${(participant.first_name?.charAt(0) || 'م')}</span>`;
        
        const fullName = `${participant.first_name || 'مستخدم'} ${participant.last_name || ''}`.trim();
        const isLeading = index === 0;
        
        return `
            <div class="participant-item ${isLeading ? 'leading-bid' : ''}">
                <div class="participant-info">
                    <div class="participant-avatar">
                        ${avatarContent}
                        ${isLeading ? '<div class="crown">👑</div>' : ''}
                    </div>
                    <div class="participant-details">
                        <span class="participant-name">${fullName}</span>
                        <span class="participant-time">
                            <i class="fas fa-clock"></i>
                            ${participant.time_ago || 'الآن'}
                        </span>
                    </div>
                </div>
                <div class="participant-bid">
                    <div class="bid-amount">${formatPrice(participant.bid_amount)}</div>
                    <div class="bid-status ${isLeading ? 'leading' : 'outbid'}">
                        ${isLeading ? 'المزايدة الأعلى' : 'مزايدة سابقة'}
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

function startAuctionAutoRefresh(productId) {
    // إيقاف أي تحديث سابق
    if (window.auctionRefreshInterval) {
        clearInterval(window.auctionRefreshInterval);
    }

    // تحديث كل 5 ثواني
    window.auctionRefreshInterval = setInterval(() => {
        if (!document.querySelector('.auction-modal')) {
            clearInterval(window.auctionRefreshInterval);
            return;
        }
        
        refreshAuctionData(productId);
    }, 5000);
}

function refreshAuctionData(productId) {
    fetch(`ajax/get_auction.php?product_id=${productId}&refresh=true&t=${Date.now()}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateAuctionModal(data);
            }
        })
        .catch(error => {
            console.error('خطأ في تحديث بيانات المزاد:', error);
        });
}

function updateAuctionModal(auctionData) {
    // تحديث السعر الحالي
    const currentBidElement = document.querySelector('.current-bid');
    if (currentBidElement) {
        currentBidElement.innerHTML = `<i class="fas fa-tag"></i> ${formatPrice(auctionData.current_bid || 0)}`;
    }

    // تحديث العداد
    const timerElement = document.getElementById('modal-auction-timer');
    if (timerElement) {
        timerElement.innerHTML = `<i class="fas fa-clock"></i> ${auctionData.time_left || 'غير محدد'}`;
    }

    // تحديث الإحصائيات
    const statsElements = document.querySelectorAll('.stat-number');
    if (statsElements.length >= 3) {
        statsElements[0].textContent = auctionData.stats?.total_bids || 0;
        statsElements[1].textContent = auctionData.stats?.total_bidders || 0;
        statsElements[2].textContent = formatPrice(auctionData.stats?.average_bid || auctionData.current_bid || 0);
    }

    // تحديث الحد الأدنى
    const bidInput = document.getElementById('bid-amount');
    if (bidInput) {
        bidInput.min = auctionData.min_bid || 1;
        if (!bidInput.value || parseFloat(bidInput.value) < auctionData.min_bid) {
            bidInput.value = auctionData.min_bid || 1;
        }
    }

    const hintElement = document.querySelector('.bid-hint');
    if (hintElement) {
        hintElement.innerHTML = `<i class="fas fa-info-circle"></i> الحد الأدنى للمزايدة: ${formatPrice(auctionData.min_bid || 1)}`;
    }

    // تحديث قائمة المشاركين
    const participantsContainer = document.getElementById('participants-container');
    if (participantsContainer) {
        participantsContainer.innerHTML = renderParticipants(auctionData.participants || []);
    }

    // تحديث عنوان قائمة المشاركين
    const participantsTitle = document.querySelector('.participants-list h5');
    if (participantsTitle) {
        participantsTitle.innerHTML = `<i class="fas fa-users"></i> قائمة المزايدين (${auctionData.participants?.length || 0})`;
    }
}

function submitBid(productId) {
    const bidInput = document.getElementById('bid-amount');
    const bidAmount = bidInput ? parseFloat(bidInput.value) : 0;
    const submitBtn = document.getElementById('submit-bid-btn');
    
    if (!bidAmount || bidAmount <= 0) {
        showToast('يرجى إدخال مبلغ المزايدة', 'error');
        return;
    }

    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جاري التقديم...';

    fetch('ajax/submit_bid.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `product_id=${productId}&bid_amount=${bidAmount}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('✅ ' + data.message, 'success');
            // تحديث البيانات بعد المزايدة الناجحة
            refreshAuctionData(productId);
            // مسح حقل الإدخال
            if (bidInput) {
                bidInput.value = data.new_bid ? data.new_bid + 1 : bidAmount + 1;
            }
        } else {
            showToast('❌ ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('خطأ في تقديم المزايدة:', error);
        showToast('❌ حدث خطأ في الاتصال', 'error');
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-gavel"></i> تقديم المزايدة';
    });
}

function closeAuctionModal() {
    const modal = document.querySelector('.auction-modal');
    if (modal) {
        modal.remove();
        console.log('🔒 نافذة المزاد أغلقت');
    }
    
    // إيقاف التحديث التلقائي
    if (window.auctionRefreshInterval) {
        clearInterval(window.auctionRefreshInterval);
        window.auctionRefreshInterval = null;
    }
}

// إغلاق النافذة عند النقر خارجها أو بالزر ESC
document.addEventListener('click', function(event) {
    const modal = document.querySelector('.auction-modal');
    if (event.target === modal) {
        closeAuctionModal();
    }
});

document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeAuctionModal();
    }
});

// دالة مساعدة للتأكد من تحميل الصفحة
document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ صفحة المزاد جاهزة');
});
// دوال عرض اشتري 2 واحصل على 1 مجاناً
function openBuy2Get1Offer(productId, productTitle, productPrice, productImage) {
    console.log('🎁 فتح نافذة العرض للمنتج:', productId);
    
    // إغلاق أي نافذة مفتوحة مسبقاً
    closeBuy2Get1Offer();
    
    const modal = document.createElement('div');
    modal.className = 'buy2-get1-modal';
    modal.style.display = 'flex';
    modal.innerHTML = `
        <div class="buy2-get1-content">
            <div class="buy2-get1-header">
                <h3>
                    <i class="fas fa-gift"></i>
                    عرض خاص
                </h3>
            </div>
            
            <div class="buy2-get1-body">
                <div class="offer-icon-large">
                    <i class="fas fa-gift"></i>
                </div>
                
                <h3 class="offer-title">اشتري 2 واحصل على 1 مجاناً!</h3>
                
                <p class="offer-description">
                    احصل على قطعة مجانية عند شراء قطعتين من "${productTitle}"
                </p>

                <div class="offer-details">
                    <div class="offer-steps">
                        <div class="offer-step">
                            <div class="step-number">1</div>
                            <div class="step-text">اشتري قطعتين</div>
                        </div>
                        <div class="offer-step">
                            <div class="step-number">2</div>
                            <div class="step-text">أضف للسلة</div>
                        </div>
                        <div class="offer-step">
                            <div class="step-number">3</div>
                            <div class="step-text">احصل على قطعة مجانية</div>
                        </div>
                    </div>

                    <div class="offer-price-example">
                        <div class="price-example">
                            <span class="original-price">${formatPrice(productPrice * 3)}</span>
                            <span>→</span>
                            <span class="final-price">${formatPrice(productPrice * 2)}</span>
                        </div>
                        <div class="savings">
                            وفر ${formatPrice(productPrice)}!
                        </div>
                    </div>
                </div>

                <div style="display: flex; gap: 1rem; justify-content: center; margin-bottom: 1.5rem;">
                    <button class="btn-add-three" onclick="addThreeWithOffer(${productId})" 
                            style="background: linear-gradient(135deg, #10b981, #059669); color: white; border: none; padding: 1rem 2rem; border-radius: 10px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-cart-plus"></i>
                        أضف 3 قطع
                    </button>
                </div>

                <div style="color: #64748b; font-size: 0.9rem;">
                    <i class="fas fa-info-circle"></i>
                    سيتم تطبيق العرض تلقائياً عند إضافة 3 قطع للسلة
                </div>
            </div>

            <div class="buy2-get1-actions">
                <button class="btn-close" onclick="closeBuy2Get1Offer()" style="flex: 1;">
                    <i class="fas fa-times"></i> إغلاق
                </button>
                <button class="btn-add-with-offer" onclick="addThreeWithOffer(${productId})">
                    <i class="fas fa-cart-plus"></i> أضف 3 قطع مع العرض
                </button>
            </div>
        </div>
    `;

    document.body.appendChild(modal);
}

function closeBuy2Get1Offer() {
    const modal = document.querySelector('.buy2-get1-modal');
    if (modal) {
        modal.remove();
    }
}

function addThreeWithOffer(productId) {
    console.log('🛒 إضافة 3 قطع مع العرض للمنتج:', productId);
    
    // إضافة 3 قطع للسلة
    addToCart(productId, 3);
    
    // إغلاق النافذة
    closeBuy2Get1Offer();
    
    // إظهار رسالة نجاح
    showToast('🎉 تمت إضافة 3 قطع إلى السلة! سيتم تطبيق العرض تلقائياً', 'success');
}

// إغلاق النافذة عند النقر خارجها
document.addEventListener('click', function(event) {
    const modal = document.querySelector('.buy2-get1-modal');
    if (event.target === modal) {
        closeBuy2Get1Offer();
    }
});

document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeBuy2Get1Offer();
    }
}); 
// تحديث الرصيد ديناميكياً (إذا كان هناك تحديث في الوقت الحقيقي)
function updateWalletBalance(newBalance) {
    const walletBalance = document.getElementById('wallet-balance');
    if (walletBalance) {
        walletBalance.textContent = newBalance.toFixed(2);
    }
}

// تحديث النقاط ديناميكياً
function updatePointsCount(newPoints) {
   // const pointsCount = document.getElementById('points-count');
   // if (pointsCount) {
   //     pointsCount.textContent = newPoints.toLocaleString();
	//	
   // }
	
	    const pointsElement = document.getElementById('points-count');
    if (!pointsElement) {
        console.warn('عنصر النقاط غير موجود');
        return;
    }
    
    const pointsText = pointsElement.innerText || '0';
    const pointsNumber = parseInt(pointsText) || 0;
    
    try {
        pointsElement.innerText = pointsNumber.toLocaleString();
    } catch (e) {
        pointsElement.innerText = pointsNumber.toString();
    }
}

 
</script>



 <!-- إضافة CSS للنافذة المنبثقة -->
<style>
/* نافذة متجر المستخدم */
.customer-store-popup {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.8);
    z-index: 9999;
    align-items: center;
    justify-content: center;
    padding: 20px;
    box-sizing: border-box;
}

.customer-store-popup-content {
    background: white;
    border-radius: 20px;
    width: 100%;
    max-width: 1200px;
    max-height: 90vh;
    overflow: hidden;
    box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);
    animation: slideUp 0.4s ease;
}

.customer-store-header {
    background: linear-gradient(135deg, #8b5cf6, #7c3aed);
    color: white;
    padding: 2rem;
    position: relative;
    text-align: center;
}

.customer-store-header h3 {
    margin: 0;
    font-size: 1.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1rem;
}

.close-popup {
    position: absolute;
    top: 1.5rem;
    left: 1.5rem;
    background: rgba(255,255,255,0.2);
    border: none;
    color: white;
    font-size: 1.5rem;
    cursor: pointer;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
}

.close-popup:hover {
    background: rgba(255,255,255,0.3);
    transform: rotate(90deg);
}

.customer-store-body {
    padding: 2rem;
    max-height: 60vh;
    overflow-y: auto;
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1.5rem;
}

.customer-store-product-popup {
    background: white;
    border-radius: 15px;
    border: 2px solid #f1f5f9;
    overflow: hidden;
    transition: all 0.3s ease;
    position: relative;
}

.customer-store-product-popup:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(139, 92, 246, 0.2);
    border-color: #8b5cf6;
}

.customer-store-product-popup img {
    width: 100%;
    height: 200px;
    object-fit: cover;
}

.customer-store-product-info {
    padding: 1rem;
}

.customer-store-product-title {
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 0.5rem;
    font-size: 1rem;
    line-height: 1.4;
}

.customer-store-product-price {
    color: #10b981;
    font-weight: 700;
    font-size: 1.1rem;
    margin-bottom: 1rem;
}

.customer-store-product-actions {
    display: flex;
    gap: 0.5rem;
}

.btn-view-product {
    flex: 2;
    background: #8b5cf6;
    color: white;
    border: none;
    padding: 0.75rem;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    text-align: center;
    font-size: 0.9rem;
}

.btn-view-product:hover {
    background: #7c3aed;
    transform: translateY(-2px);
}

.btn-add-cart-popup {
    flex: 1;
    background: #10b981;
    color: white;
    border: none;
    padding: 0.75rem;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.btn-add-cart-popup:hover {
    background: #059669;
    transform: translateY(-2px);
}

/* تحسينات للهواتف */
@media (max-width: 768px) {
    .customer-store-body {
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 1rem;
        padding: 1rem;
    }
    
    .customer-store-header {
        padding: 1.5rem;
    }
    
    .customer-store-header h3 {
        font-size: 1.25rem;
    }
}

/* زر عرض متجر المستخدم */
.view-store-btn {
    position: absolute;
    top: 50%;
    right: 10px;
    background: rgba(139, 92, 246, 0.9);
    color: white;
    border: none;
    border-radius: 20px;
    padding: 0.4rem 0.8rem;
    font-size: 0.75rem;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.25rem;
    transition: all 0.3s ease;
    z-index: 10;
    backdrop-filter: blur(10px);
}

.view-store-btn:hover {
    background: #7c3aed;
    transform: scale(1.05);
}
</style>

<!-- إضافة النافذة المنبثقة في نهاية body -->
<div id="customer-store-popup" class="customer-store-popup">
    <div class="customer-store-popup-content">
        <div class="customer-store-header">
            <button class="close-popup" onclick="closeCustomerStorePopup()">
                <i class="fas fa-times"></i>
            </button>
            <h3>
                <i class="fas fa-store"></i>
                <span id="popup-store-name">متجر المستخدم</span>
            </h3>
            <p id="popup-store-description">جميع منتجات هذا المتجر</p>
        </div>
        <div class="customer-store-body" id="customer-store-products">
            <!-- سيتم ملء المنتجات هنا عبر JavaScript -->
        </div>
    </div>
</div>

<script>
// دوال النافذة المنبثقة لمتجر المستخدم
let currentStoreOwnerId = null;
let currentStoreProducts = [];

function openCustomerStorePopup(ownerId, ownerName) {
    console.log('فتح متجر المستخدم:', ownerId, ownerName);
    
    if (!ownerId) {
        showToast('❌ لا يمكن فتح المتجر: بيانات غير كافية', 'error');
        return;
    }

    // إظهار مؤشر تحميل
    const popup = document.getElementById('customer-store-popup');
    const productsContainer = document.getElementById('customer-store-products');
    
    productsContainer.innerHTML = `
        <div style="grid-column: 1/-1; text-align: center; padding: 3rem;">
            <i class="fas fa-spinner fa-spin fa-2x" style="color: #8b5cf6; margin-bottom: 1rem;"></i>
            <p>جاري تحميل منتجات المتجر...</p>
        </div>
    `;
    
    popup.style.display = 'flex';
    document.getElementById('popup-store-name').textContent = `متجر ${ownerName}`;
    document.getElementById('popup-store-description').textContent = `استعرض جميع منتجات ${ownerName}`;
    
    currentStoreOwnerId = ownerId;
    
    // جلب منتجات المتجر
    fetch(`ajax/get_customer_store.php?customer_id=${ownerId}&t=${Date.now()}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`خطأ في الشبكة: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                currentStoreProducts = data.products;
                renderCustomerStoreProducts(data.products);
            } else {
                productsContainer.innerHTML = `
                    <div style="grid-column: 1/-1; text-align: center; padding: 3rem; color: #dc3545;">
                        <i class="fas fa-exclamation-triangle fa-3x" style="margin-bottom: 1rem;"></i>
                        <p>${data.message || 'تعذر تحميل المنتجات'}</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('خطأ في جلب منتجات المتجر:', error);
            productsContainer.innerHTML = `
                <div style="grid-column: 1/-1; text-align: center; padding: 3rem; color: #dc3545;">
                    <i class="fas fa-wifi fa-slash fa-3x" style="margin-bottom: 1rem;"></i>
                    <p>حدث خطأ في الاتصال: ${error.message}</p>
                </div>
            `;
        });
}

function closeCustomerStorePopup() {
    const popup = document.getElementById('customer-store-popup');
    popup.style.display = 'none';
    currentStoreOwnerId = null;
    currentStoreProducts = [];
}

function renderCustomerStoreProducts(products) {
    const container = document.getElementById('customer-store-products');
    
    if (!products || products.length === 0) {
        container.innerHTML = `
            <div style="grid-column: 1/-1; text-align: center; padding: 3rem;">
                <i class="fas fa-store-slash fa-3x" style="color: #9ca3af; margin-bottom: 1rem;"></i>
                <h4 style="color: #6b7280; margin-bottom: 0.5rem;">لا توجد منتجات في هذا المتجر</h4>
                <p style="color: #9ca3af;">لم يقم هذا البائع بإضافة أي منتجات بعد</p>
            </div>
        `;
        return;
    }
    
    container.innerHTML = products.map(product => `
        <div class="customer-store-product-popup">
            <img src="${product.main_image || 'assets/images/placeholder.jpg'}" 
                 alt="${product.title}"
                 onerror="this.src='assets/images/placeholder.jpg'">
            
            <div class="customer-store-product-info">
                <h4 class="customer-store-product-title">${product.title}</h4>
                
                <div class="customer-store-product-price">
                    ${formatPrice(product.final_price || product.price)}
                </div>
                
                <div class="customer-store-product-actions">
                    <a href="product.php?id=${product.id}" class="btn-view-product">
                        <i class="fas fa-eye"></i> عرض
                    </a>
                    <button class="btn-add-cart-popup" onclick="addToCart(${product.id}, 1)">
                        <i class="fas fa-cart-plus"></i>
                    </button>
                </div>
            </div>
        </div>
    `).join('');
}

// إغلاق النافذة عند النقر خارجها
document.addEventListener('click', function(event) {
    const popup = document.getElementById('customer-store-popup');
    if (event.target === popup) {
        closeCustomerStorePopup();
    }
});

// إغلاق بالزر ESC
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeCustomerStorePopup();
    }
});

// دوال نظام الإحالات
let currentReferralLink = '';

function openReferralModal(productId, productTitle) {
    if (!window.customerData.isLoggedIn) {
        showToast('يجب تسجيل الدخول لاستخدام نظام الإحالات', 'warning');
        setTimeout(() => {
            window.location.href = 'account.php?redirect=' + encodeURIComponent(window.location.href);
        }, 2000);
        return;
    }

    document.getElementById('referral-product-id').value = productId;
    document.getElementById('referral-product-title').textContent = productTitle;
    
    const modal = document.getElementById('referral-modal');
    modal.style.display = 'flex';
    
    // إنشاء رابط الإحالة
    generateReferralLink(productId);
}

function closeReferralModal() {
    const modal = document.getElementById('referral-modal');
    modal.style.display = 'none';
}

function generateReferralLink(productId) {
    console.log('🔄 جاري إنشاء رابط إحالة للمنتج:', productId);
    
    const linkTextElement = document.getElementById('referral-link-text');
    if (linkTextElement) {
        linkTextElement.textContent = 'جاري إنشاء الرابط...';
        linkTextElement.style.color = '#666';
    }

    fetch('ajax/generate_referral_link.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `product_id=${productId}`
    })
    .then(response => {
        console.log('📥 استجابة السيرفر:', response);
        
        // التحقق من نوع المحتوى أولاً
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            return response.text().then(text => {
                console.error('❌ السيرفر لم يرجع JSON:', text);
                throw new Error('استجابة غير صحيحة من السيرفر: ' + text.substring(0, 100));
            });
        }
        return response.json();
    })
    .then(data => {
        console.log('📊 بيانات JSON المستلمة:', data);
        
        if (data.success) {
            currentReferralLink = data.referral_link;
            
            if (linkTextElement) {
                linkTextElement.textContent = data.referral_link;
                linkTextElement.style.color = '#000';
            }
            
            // تحديث رابط واتساب
            const productTitle = document.getElementById('referral-product-title').textContent;
            const whatsappMessage = `🔗 شاهد هذا المنتج الرائع: ${productTitle}\n\n${data.referral_link}\n\nاشتري عبر هذا الرابط واحصل على نقاط مجانية! 🎁`;
            window.whatsappShareUrl = `https://wa.me/?text=${encodeURIComponent(whatsappMessage)}`;
            
            showToast('✅ ' + data.message, 'success');
        } else {
            showToast('❌ ' + (data.message || 'فشل في إنشاء الرابط'), 'error');
            if (linkTextElement) {
                linkTextElement.textContent = 'فشل في إنشاء الرابط';
                linkTextElement.style.color = '#dc3545';
            }
        }
    })
    .catch(error => {
        console.error('❌ خطأ في إنشاء رابط الإحالة:', error);
        
        let errorMessage = 'حدث خطأ في الاتصال';
        if (error.message.includes('JSON')) {
            errorMessage = 'خطأ في استجابة السيرفر - تأكد من أن الملف يعمل بشكل صحيح';
        }
        
        showToast('❌ ' + errorMessage, 'error');
        
        if (linkTextElement) {
            linkTextElement.textContent = 'فشل في إنشاء الرابط - ' + error.message.substring(0, 50);
            linkTextElement.style.color = '#dc3545';
        }
    });
}
function copyReferralLink() {
    if (!currentReferralLink) {
        showToast('الرابط غير متاح', 'error');
        return;
    }

    navigator.clipboard.writeText(currentReferralLink).then(() => {
        showToast('✅ تم نسخ الرابط بنجاح', 'success');
    }).catch(() => {
        // طريقة بديلة للنسخ
        const textArea = document.createElement('textarea');
        textArea.value = currentReferralLink;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        showToast('✅ تم نسخ الرابط بنجاح', 'success');
    });
}

function shareOnWhatsApp() {
    if (!currentReferralLink) {
        showToast('الرابط غير متاح', 'error');
        return;
    }

    const productTitle = document.getElementById('referral-product-title').textContent;
    const message = `🔗 شاهد هذا المنتج الرائع: ${productTitle}\n\n${currentReferralLink}\n\nاشتري عبر هذا الرابط واحصل على نقاط مجانية! 🎁`;
    
    const whatsappUrl = `https://wa.me/?text=${encodeURIComponent(message)}`;
    window.open(whatsappUrl, '_blank');
}

// إغلاق النافذة عند النقر خارجها
document.addEventListener('click', function(event) {
    const modal = document.getElementById('referral-modal');
    if (event.target === modal) {
        closeReferralModal();
    }
});

document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeReferralModal();
    }
});

// دوال نظام الكاشباك
let currentCashbackProductId = null;

function openCashbackModal(productId, productTitle, cashbackAmount, cashbackPercentage, formattedAmount) {
    currentCashbackProductId = productId;
    
    document.getElementById('cashback-product-title').textContent = productTitle;
    document.getElementById('cashback-amount-large').textContent = formattedAmount;
    document.getElementById('cashback-percentage-text').textContent = cashbackPercentage + '% استرجاع نقدي';
    document.getElementById('cashback-amount-info').textContent = formattedAmount;
    document.getElementById('cashback-percentage-info').textContent = cashbackPercentage + '%';
    document.getElementById('cashback-product-id').value = productId;
    
    const modal = document.getElementById('cashback-modal');
    modal.style.display = 'flex';
}

function closeCashbackModal() {
    const modal = document.getElementById('cashback-modal');
    modal.style.display = 'none';
    currentCashbackProductId = null;
}

function addToCartWithCashback() {
    if (currentCashbackProductId) {
        addToCart(currentCashbackProductId, 1);
        closeCashbackModal();
        showToast('🎉 تمت إضافة المنتج للسلة! ستستلم الكاشباك بعد الشراء', 'success');
    }
}

// إغلاق النافذة عند النقر خارجها
document.addEventListener('click', function(event) {
    const modal = document.getElementById('cashback-modal');
    if (event.target === modal) {
        closeCashbackModal();
    }
});

document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeCashbackModal();
    }
});
// تحديث عداد النقاط
function updatePointsDisplay() {
    const pointsCount = document.getElementById('points-count');
    if (pointsCount) {
        // يمكنك إضافة AJAX call هنا لتحديث النقاط في الوقت الحقيقي
        console.log('Updating points display...');
    }
}

// إغلاق التنبيه تلقائياً بعد 5 ثوان
document.addEventListener('DOMContentLoaded', function() {
    const alert = document.querySelector('.daily-points-alert .alert');
    if (alert) {
        setTimeout(() => {
            alert.style.display = 'none';
        }, 5000);
    }
});
</script>

<script>
// التحكم في tooltip النقاط
document.addEventListener('DOMContentLoaded', function() {
    const pointsContainer = document.getElementById('points-tooltip-container');
    const pointsTrigger = document.getElementById('points-trigger');
    const pointsTooltip = document.getElementById('points-tooltip');
    
    if (!pointsContainer || !pointsTrigger || !pointsTooltip) return;
    
    let tooltipTimeout;
    let isTooltipVisible = false;
    
    // إظهار الـ Tooltip
    function showTooltip() {
        clearTimeout(tooltipTimeout);
        pointsTooltip.classList.add('show');
        isTooltipVisible = true;
    }
    
    // إخفاء الـ Tooltip
    function hideTooltip() {
        tooltipTimeout = setTimeout(() => {
            pointsTooltip.classList.remove('show');
            isTooltipVisible = false;
        }, 300);
    }
    
    // أحداث الـ Hover
    pointsTrigger.addEventListener('mouseenter', function() {
        showTooltip();
    });
    
    pointsTrigger.addEventListener('mouseleave', function() {
        hideTooltip();
    });
    
    pointsTooltip.addEventListener('mouseenter', function() {
        clearTimeout(tooltipTimeout);
        showTooltip();
    });
    
    pointsTooltip.addEventListener('mouseleave', function() {
        hideTooltip();
    });
    
    // إغلاق الـ Tooltip بالضغط على ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && isTooltipVisible) {
            pointsTooltip.classList.remove('show');
            isTooltipVisible = false;
        }
    });
    
    // إغلاق الـ Tooltip بالضغط خارجها (للجوال)
    document.addEventListener('click', function(e) {
        if (isTooltipVisible && !pointsContainer.contains(e.target)) {
            pointsTooltip.classList.remove('show');
            isTooltipVisible = false;
        }
    });
    
    // تحديث الـ Tooltip تلقائياً
    function updatePointsTooltip() {
        fetch('ajax/get_points_stats.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // تحديث عداد النقاط
                    const pointsCount = document.getElementById('points-count');
                    if (pointsCount) {
                        pointsCount.textContent = data.formatted_points;
                    }
                    
                    // تحديث الـ Tooltip
                    updateTooltipContent(data.stats);
                }
            })
            .catch(error => console.error('Error updating points tooltip:', error));
    }
    
    // تحديث محتوى الـ Tooltip
    function updateTooltipContent(stats) {
        const elements = {
            pointsAvailable: document.querySelector('.points-available'),
            todayVisit: document.querySelector('.today-visit'),
            monthlyVisits: document.querySelector('.monthly-visits'),
            visitPoints: document.querySelector('.visit-points')
        };
        
        if (elements.pointsAvailable) {
            elements.pointsAvailable.textContent = stats.formatted_points || '0';
        }
        
        if (elements.todayVisit) {
            elements.todayVisit.textContent = stats.today_visited ? '✅ تمت' : '⏳ لم تتم';
            elements.todayVisit.className = `today-visit ${stats.today_visited ? 'visited' : 'not-visited'}`;
        }
        
        if (elements.monthlyVisits) {
            elements.monthlyVisits.textContent = stats.monthly_visits || '0';
        }
        
        if (elements.visitPoints) {
            elements.visitPoints.textContent = `+${stats.total_points_earned || '0'}`;
        }
    }
    
    // تحديث كل 30 ثانية إذا كان الـ Tooltip مفتوح
    setInterval(() => {
        if (isTooltipVisible) {
            updatePointsTooltip();
        }
    }, 30000);
});
// إضافة تأثيرات إضافية
function addTooltipAnimations() {
    const tooltip = document.getElementById('points-tooltip');
    if (!tooltip) return;
    
    // تأثير عند الظهور
    tooltip.addEventListener('animationend', function() {
        this.style.animation = 'none';
    });
    
    // تحديث الوقت المتبقي للزيارة التالية
    function updateNextVisitTime() {
        const now = new Date();
        const tomorrow = new Date(now);
        tomorrow.setDate(tomorrow.getDate() + 1);
        tomorrow.setHours(0, 0, 0, 0);
        
        const timeLeft = tomorrow - now;
        const hours = Math.floor(timeLeft / (1000 * 60 * 60));
        const minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
        
        const nextRewardElement = document.querySelector('.next-reward');
        if (nextRewardElement) {
            nextRewardElement.textContent = `+5 نقاط (${hours}س ${minutes}د)`;
        }
    }
    
    updateNextVisitTime();
    setInterval(updateNextVisitTime, 60000); // تحديث كل دقيقة
}

// استدعاء الدالة عند تحميل الصفحة
document.addEventListener('DOMContentLoaded', addTooltipAnimations);
</script>
<!-- نافذة الإحالات -->
<div id="referral-modal" class="referral-modal">
    <div class="referral-content">
        <div class="referral-header">
            <h3>
                <i class="fas fa-share-alt"></i>
                انشر المنتج واكسب نقاط
            </h3>
        </div>
        
        <div class="referral-body">
            <div class="referral-icon">
                <i class="fas fa-gift"></i>
            </div>
            
            <h3 class="referral-title" id="referral-product-title">اسم المنتج</h3>
            
            <p class="referral-description">
                شارك هذا المنتج مع أصدقائك واكسب نقاط عند كل عملية شراء عبر رابطك
            </p>

            <div class="referral-link-container">
                <div class="referral-link" id="referral-link-text">
                    جاري إنشاء الرابط...
                </div>
                
                <div class="referral-benefits">
                    <div class="benefit-item">
                        <div class="benefit-icon">
                            <i class="fas fa-coins"></i>
                        </div>
                        <div class="benefit-text">
                            اكسب <?= getSetting('referral_points_referrer', 500) ?> نقطة
                        </div>
                    </div>
                    <div class="benefit-item">
                        <div class="benefit-icon">
                            <i class="fas fa-user-plus"></i>
                        </div>
                        <div class="benefit-text">
                            صديقك يحصل على <?= getSetting('referral_points_referred', 300) ?> نقطة
                        </div>
                    </div>
                </div>
            </div>

            <div class="referral-actions">
                <button class="btn-copy-link" onclick="copyReferralLink()">
                    <i class="fas fa-copy"></i>
                    نسخ الرابط
                </button>
                <button class="btn-share-whatsapp" onclick="shareOnWhatsApp()">
                    <i class="fab fa-whatsapp"></i>
                    مشاركة واتساب
                </button>
            </div>
        </div>

        <div class="referral-actions" style="padding: 1.5rem 2rem; background: #f8fafc; border-top: 1px solid #e2e8f0;">
            <button class="btn-close" onclick="closeReferralModal()" style="flex: 1;">
                <i class="fas fa-times"></i> إغلاق
            </button>
        </div>
    </div>
</div>

<input type="hidden" id="referral-product-id">
<input type="hidden" id="referral-customer-id" value="<?= $_SESSION['customer_id'] ?? 0 ?>">
<!-- نافذة الكاشباك -->
<div id="cashback-modal" class="cashback-modal">
    <div class="cashback-content">
        <div class="cashback-header">
            <h3>
                <i class="fas fa-money-bill-wave"></i>
                استرجاع نقدي (Cashback)
            </h3>
        </div>
        
        <div class="cashback-body">
            <div class="cashback-icon">
                💰
            </div>
            
            <h3 class="cashback-title" id="cashback-product-title">اسم المنتج</h3>
            
            <p class="cashback-description">
                احصل على استرجاع نقدي عند شراء هذا المنتج!
            </p>

            <div class="cashback-details">
                <div class="cashback-amount-large" id="cashback-amount-large">0 ج.م</div>
                <div id="cashback-percentage-text" style="color: #64748b;">0% استرجاع نقدي</div>
            </div>

            <div class="cashback-info">
                <div class="cashback-info-item">
                    <div class="info-label">مبلغ الكاشباك</div>
                    <div class="info-value" id="cashback-amount-info">0 ج.م</div>
                </div>
                <div class="cashback-info-item">
                    <div class="info-label">نسبة الاسترجاع</div>
                    <div class="info-value" id="cashback-percentage-info">0%</div>
                </div>
            </div>

            <div class="cashback-benefits">
                <div class="benefit-item">
                    <i class="fas fa-check-circle"></i>
                    <span>استرجاع نقدي حقيقي لمحفظتك</span>
                </div>
                <div class="benefit-item">
                    <i class="fas fa-check-circle"></i>
                    <span>يمكن استخدامه في المشتريات القادمة</span>
                </div>
                <div class="benefit-item">
                    <i class="fas fa-check-circle"></i>
                    <span>صالح لمدة 90 يوم</span>
                </div>
                <div class="benefit-item">
                    <i class="fas fa-check-circle"></i>
                    <span>لا شروط خفية</span>
                </div>
            </div>
        </div>

        <div class="cashback-actions">
            <button class="btn-close-cashback" onclick="closeCashbackModal()">
                <i class="fas fa-times"></i> إغلاق
            </button>
            <button class="btn-learn-more" onclick="addToCartWithCashback()">
                <i class="fas fa-cart-plus"></i> أضف للسلة واستفد
            </button>
        </div>
    </div>
</div>

<input type="hidden" id="cashback-product-id">


<!-- نافذة QR Code -->
<div id="qrModal" class="modal qr-modal"> 
            <div id="qrContent">
    انقر لإنشاء كود QR للتخفيض. استخدمه عند زيارة المتجر للحصول على خصم حصري!
                <!-- سيتم تعبئة المحتوى هنا بالجافاسكريبت -->
            </div>
            <span class="close" onclick="closeQRModal()">&times;</span>
 
</div>
 
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "WebSite",
  "name": "<?= htmlspecialchars($storeName) ?>",
  "description": "<?= htmlspecialchars($storeDescription) ?>",
  "url": "<?= getBaseUrl() ?>",
  "potentialAction": {
    "@type": "SearchAction",
    "target": "<?= getBaseUrl() ?>index.php?search={search_term_string}",
    "query-input": "required name=search_term_string"
  }
}
</script>
<script>
// دمج نظام الأوامر الذكية مع الصفحة الرئيسية
document.addEventListener('DOMContentLoaded', function() {
    // إضافة أمر سريع للمنتجات
    document.querySelectorAll('.product-card').forEach(card => {
        const productName = card.querySelector('.product-title').textContent.trim();
        const productPrice = card.querySelector('.price-new').textContent.trim();
        const productId = card.querySelector('.btn-add-cart')?.getAttribute('onclick')?.match(/\d+/)?.[0];
        
        if (productId) {
            const quickCommandBtn = document.createElement('button');
            quickCommandBtn.className = 'quick-command-btn';
            quickCommandBtn.innerHTML = '<i class="fas fa-bolt"></i>';
            quickCommandBtn.title = 'أضف أمراً ذكياً لهذا المنتج';
            quickCommandBtn.style.cssText = `
                position: absolute;
                top: 10px;
                left: 10px;
                background: #10b981;
                color: white;
                border: none;
                width: 35px;
                height: 35px;
                border-radius: 50%;
                cursor: pointer;
                z-index: 5;
                display: flex;
                align-items: center;
                justify-content: center;
            `;
            
            quickCommandBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                
                const command = `تتبع سعر ${productName} عندما يصبح أقل من ${productPrice}`;
                document.querySelector('.command-input').value = command;
                document.querySelector('.smart-command-sidebar').classList.add('active');
            });
            
            card.style.position = 'relative';
            card.appendChild(quickCommandBtn);
        }
    });
});
</script>
</body>
</html>