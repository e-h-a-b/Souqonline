<?php
session_start();
require_once '../config.php';
require_once '../functions.php';
require_once '../qr_functions.php';

if (!isset($_SESSION['customer_id'])) {
    header('Location: login.php');
    exit;
}

$customer_id = $_SESSION['customer_id'];
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// جلب QR Codes الخاصة بالمستخدم
$user_qr_codes = getUserQRCodes($customer_id, $limit, $offset);

// جلب إحصائيات عامة
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_codes,
        SUM(CASE WHEN is_used = 1 THEN 1 ELSE 0 END) as used_codes,
        SUM(CASE WHEN is_valid = 1 AND is_used = 0 AND expires_at > NOW() THEN 1 ELSE 0 END) as active_codes,
        SUM(CASE WHEN expires_at < NOW() THEN 1 ELSE 0 END) as expired_codes
    FROM store_qr_codes 
    WHERE customer_id = ?
");
$stmt->execute([$customer_id]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

$page_title = "إدارة أكواد QR - المتجر";
//include 'includes/header.php';
?>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة المنتجات - لوحة التحكم</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
/* ===== الأنماط الأساسية ===== */
* { 
    margin: 0; 
    padding: 0; 
    box-sizing: border-box; 
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: #f8fafc;
    color: #334155;
    line-height: 1.6;
}

.admin-wrapper { 
    display: flex; 
    min-height: 100vh; 
}

/* ===== الهيكل الرئيسي ===== */
.main-content {
    flex: 1;
    margin-right: 260px;
    padding: 30px;
}

.container {
    max-width: 1200px;
    margin: 0 auto;
}

/* ===== رأس الصفحة ===== */
.page-header {
    background: #fff;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    margin-bottom: 30px;
}

.page-header h1 {
    font-size: 28px;
    color: #1e293b;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.page-header h1 i {
    color: #2563eb;
}

.page-header p {
    color: #64748b;
    font-size: 16px;
}

/* ===== بطاقات الإحصائيات ===== */
.qr-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 1.5rem;
    margin: 2rem 0;
}

.stat-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    gap: 1rem;
    border: 1px solid #e5e7eb;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 15px -3px rgba(0, 0, 0, 0.1);
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: white;
    flex-shrink: 0;
}

.stat-icon.total { 
    background: linear-gradient(135deg, #3b82f6, #1d4ed8); 
}
.stat-icon.active { 
    background: linear-gradient(135deg, #10b981, #059669); 
}
.stat-icon.used { 
    background: linear-gradient(135deg, #f59e0b, #d97706); 
}
.stat-icon.expired { 
    background: linear-gradient(135deg, #6b7280, #4b5563); 
}

.stat-info h3 {
    margin: 0;
    font-size: 1.8rem;
    font-weight: 700;
    color: #1f2937;
}

.stat-info p {
    margin: 0.25rem 0 0 0;
    color: #6b7280;
    font-size: 0.9rem;
}

/* ===== الفلاتر والبحث ===== */
.qr-filters {
    background: white;
    padding: 1.5rem;
    border-radius: 12px;
    margin: 1.5rem 0;
    border: 1px solid #e5e7eb;
    display: flex;
    gap: 1.5rem;
    align-items: end;
    flex-wrap: wrap;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    flex: 1;
    min-width: 200px;
}

.filter-group label {
    font-weight: 600;
    color: #374151;
    font-size: 0.9rem;
}

.filter-group select,
.filter-group input {
    padding: 0.75rem;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    font-size: 0.9rem;
    transition: border-color 0.3s ease;
}

.filter-group select:focus,
.filter-group input:focus {
    outline: none;
    border-color: #2563eb;
}

/* ===== الأزرار ===== */
.btn {
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
    font-size: 0.9rem;
    text-align: center;
    justify-content: center;
}

.btn-primary { 
    background: #2563eb; 
    color: #fff; 
}
.btn-primary:hover { 
    background: #1d4ed8; 
    transform: translateY(-1px);
}

.btn-success { 
    background: #16a34a; 
    color: #fff; 
}
.btn-success:hover { 
    background: #15803d; 
}

.btn-danger { 
    background: #dc2626; 
    color: #fff; 
}
.btn-danger:hover { 
    background: #b91c1c; 
}

.btn-secondary { 
    background: #64748b; 
    color: #fff; 
}
.btn-secondary:hover { 
    background: #475569; 
}

.btn-warning {
    background: #f59e0b;
    color: #fff;
}
.btn-warning:hover {
    background: #d97706;
}

/* ===== قائمة QR Codes ===== */
.qr-codes-list {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}

.qr-code-item {
    background: white;
    border-radius: 12px;
    border: 1px solid #e5e7eb;
    overflow: hidden;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.qr-code-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 15px -3px rgba(0, 0, 0, 0.1);
}

.qr-item-header {
    padding: 1.5rem;
    border-bottom: 1px solid #f3f4f6;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    background: #f8fafc;
    gap: 1rem;
}

.qr-info h4 {
    margin: 0 0 0.5rem 0;
    color: #1f2937;
    font-size: 1.125rem;
    font-weight: 600;
}

.qr-meta {
    display: flex;
    gap: 1.5rem;
    font-size: 0.875rem;
    color: #6b7280;
    flex-wrap: wrap;
}

.qr-meta span {
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.status-badge {
    padding: 0.4rem 1rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-badge.active { 
    background: #dcfce7; 
    color: #166534; 
}
.status-badge.used { 
    background: #fef3c7; 
    color: #92400e; 
}
.status-badge.expired { 
    background: #f3f4f6; 
    color: #6b7280; 
}

/* ===== جسم العنصر ===== */
.qr-item-body {
    padding: 1.5rem;
}

.qr-details-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.detail {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid #f3f4f6;
}

.detail label {
    font-weight: 600;
    color: #374151;
    font-size: 0.875rem;
}

.detail span {
    color: #6b7280;
    font-size: 0.875rem;
}

.detail .discounted {
    color: #10b981;
    font-weight: 600;
}

.detail .percentage {
    color: #ef4444;
    font-weight: 600;
}

.detail .expired {
    color: #ef4444;
    font-weight: 600;
}

.qr-analytics {
    display: flex;
    gap: 2rem;
    padding-top: 1rem;
    border-top: 1px solid #f3f4f6;
}

.analytics-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #6b7280;
    font-size: 0.875rem;
}

.analytics-item i {
    color: #3b82f6;
}

/* ===== أزرار الإجراءات ===== */
.qr-item-actions {
    padding: 1.25rem 1.5rem;
    background: #f8fafc;
    border-top: 1px solid #e5e7eb;
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.qr-item-actions .btn {
    flex: 1;
    min-width: 120px;
    max-width: 200px;
}

/* ===== الحالة الفارغة ===== */
.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    color: #6b7280;
    background: white;
    border-radius: 12px;
    border: 2px dashed #e5e7eb;
}

.empty-state i {
    font-size: 4rem;
    color: #d1d5db;
    margin-bottom: 1.5rem;
}

.empty-state h3 {
    color: #374151;
    margin-bottom: 0.75rem;
    font-size: 1.25rem;
}

.empty-state p {
    margin-bottom: 2rem;
    font-size: 1rem;
}

/* ===== الترقيم ===== */
.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 0.75rem;
    margin-top: 2.5rem;
    padding: 1.5rem;
}

.page-link {
    padding: 0.75rem 1rem;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    text-decoration: none;
    color: #475569;
    font-weight: 500;
    transition: all 0.3s ease;
    min-width: 44px;
    text-align: center;
}

.page-link:hover {
    background: #f1f5f9;
    border-color: #cbd5e1;
}

.page-link.active {
    background: #2563eb;
    color: white;
    border-color: #2563eb;
}

.page-current {
    color: #64748b;
    font-weight: 500;
    padding: 0 1rem;
}

/* ===== النافذة المنبثقة ===== */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
    backdrop-filter: blur(4px);
}

.modal-content {
    background-color: #fff;
    margin: 2% auto;
    padding: 0;
    border-radius: 12px;
    width: 90%;
    max-width: 700px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);
    animation: modalSlideIn 0.3s ease;
}

@keyframes modalSlideIn {
    from {
        opacity: 0;
        transform: translateY(-50px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.modal-header {
    padding: 1.5rem 2rem;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #f8fafc;
    border-radius: 12px 12px 0 0;
}

.modal-header h3 {
    margin: 0;
    color: #1f2937;
    font-size: 1.25rem;
}

.close {
    color: #6b7280;
    font-size: 1.5rem;
    font-weight: bold;
    cursor: pointer;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 6px;
    transition: all 0.3s ease;
}

.close:hover {
    background: #f3f4f6;
    color: #374151;
}

.modal-body {
    padding: 2rem;
}

/* ===== تفاصيل QR Code ===== */
.qr-details-view {
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 2rem;
}

.qr-image-section {
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1rem;
}

.qr-image-large {
    width: 200px;
    height: 200px;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 1rem;
    background: white;
    box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
}

.qr-code-text {
    font-family: 'Courier New', monospace;
    font-size: 0.8rem;
    color: #6b7280;
    word-break: break-all;
    background: #f8fafc;
    padding: 0.5rem;
    border-radius: 6px;
    border: 1px solid #e5e7eb;
}

.details-grid {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.detail-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid #f3f4f6;
}

.detail-row label {
    font-weight: 600;
    color: #374151;
    font-size: 0.9rem;
}

.detail-row span {
    color: #6b7280;
    font-size: 0.9rem;
}

.analytics-section {
    grid-column: 1 / -1;
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid #e5e7eb;
}

.analytics-section h4 {
    margin-bottom: 1rem;
    color: #1f2937;
    font-size: 1.1rem;
}

.analytics-grid {
    display: flex;
    gap: 2rem;
}

.analytic-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    color: #374151;
    font-weight: 500;
    font-size: 0.9rem;
}

.analytic-item i {
    color: #3b82f6;
    font-size: 1.1rem;
}

/* ===== التجاوب للشاشات الصغيرة ===== */
@media (max-width: 768px) {
    .main-content {
        margin-right: 0;
        padding: 1rem;
    }
    
    .qr-stats-grid {
        grid-template-columns: 1fr;
    }
    
    .qr-filters {
        flex-direction: column;
        align-items: stretch;
        gap: 1rem;
    }
    
    .filter-group {
        min-width: auto;
    }
    
    .qr-item-header {
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start;
    }
    
    .qr-details-grid {
        grid-template-columns: 1fr;
    }
    
    .qr-details-view {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
    
    .qr-item-actions {
        flex-direction: column;
    }
    
    .qr-item-actions .btn {
        max-width: none;
    }
    
    .analytics-grid {
        flex-direction: column;
        gap: 1rem;
    }
    
    .modal-content {
        width: 95%;
        margin: 5% auto;
    }
    
    .pagination {
        flex-wrap: wrap;
    }
}

@media (max-width: 480px) {
    .page-header {
        padding: 1.5rem;
    }
    
    .page-header h1 {
        font-size: 1.5rem;
    }
    
    .stat-card {
        padding: 1.25rem;
    }
    
    .stat-icon {
        width: 50px;
        height: 50px;
        font-size: 1.25rem;
    }
    
    .stat-info h3 {
        font-size: 1.5rem;
    }
}
.sidebar {
    position: fixed;
    right: 0;
    top: 0;
    direction: rtl !important;
    height: 100vh;
    width: 280px;
    background: #2c3e50;
    transition: all 0.3s ease;
    z-index: 1000;
    overflow-y: auto;
}
</style>
</head> 
    <div class="admin-wrapper">
        <!-- Sidebar -->
                <!-- تضمين القائمة الجانبية -->
        <?php include 'sidebar.php'; ?>
<div class="container">
    <div class="page-header">
        <h1><i class="fas fa-qrcode"></i> إدارة أكواد QR</h1>
        <p>إدارة وتتبع جميع أكواد QR التي قمت بإنشائها</p>
    </div>

    <!-- إحصائيات سريعة -->
    <div class="qr-stats-grid">
        <div class="stat-card">
            <div class="stat-icon total">
                <i class="fas fa-qrcode"></i>
            </div>
            <div class="stat-info">
                <h3><?= $stats['total_codes'] ?></h3>
                <p>إجمالي الأكواد</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon active">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-info">
                <h3><?= $stats['active_codes'] ?></h3>
                <p>أكواد نشطة</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon used">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <div class="stat-info">
                <h3><?= $stats['used_codes'] ?></h3>
                <p>أكواد مستخدمة</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon expired">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-info">
                <h3><?= $stats['expired_codes'] ?></h3>
                <p>أكواد منتهية</p>
            </div>
        </div>
    </div>

    <!-- فلترة البحث -->
    <div class="qr-filters">
        <div class="filter-group">
            <label>الحالة:</label>
            <select id="statusFilter" onchange="filterQRCodes()">
                <option value="all">جميع الأكواد</option>
                <option value="active">نشطة فقط</option>
                <option value="used">مستخدمة</option>
                <option value="expired">منتهية</option>
            </select>
        </div>
        
        <div class="filter-group">
            <label>البحث:</label>
            <input type="text" id="searchFilter" placeholder="ابحث باسم المنتج أو كود QR..." onkeyup="filterQRCodes()">
        </div>
        
        <div class="filter-group">
            <button class="btn btn-secondary" onclick="resetFilters()">
                <i class="fas fa-redo"></i> إعادة تعيين
            </button>
        </div>
    </div>

    <!-- قائمة QR Codes -->
    <div class="qr-codes-list" id="qrCodesList">
        <?php if (empty($user_qr_codes)): ?>
            <div class="empty-state">
                <i class="fas fa-qrcode"></i>
                <h3>لا توجد أكواد QR</h3>
                <p>لم تقم بإنشاء أي أكواد QR بعد</p>
                <a href="index.php" class="btn btn-primary">
                    <i class="fas fa-shopping-bag"></i> تصفح المنتجات
                </a>
            </div>
        <?php else: ?>
            <?php foreach ($user_qr_codes as $qr): ?>
                <div class="qr-code-item" data-status="<?= getQRStatus($qr) ?>" data-product="<?= htmlspecialchars($qr['product_title']) ?>">
                    <div class="qr-item-header">
                        <div class="qr-info">
                            <h4><?= htmlspecialchars($qr['product_title']) ?></h4>
                            <div class="qr-meta">
                                <span class="qr-code">كود: <?= $qr['qr_code'] ?></span>
                                <span class="store-name">المتجر: <?= htmlspecialchars($qr['store_owner_name']) ?></span>
                            </div>
                        </div>
                        <div class="qr-status <?= getQRStatus($qr) ?>">
                            <span class="status-badge"><?= getQRStatusText($qr) ?></span>
                        </div>
                    </div>
                    
                    <div class="qr-item-body">
                        <div class="qr-details-grid">
                            <div class="detail">
                                <label>السعر الأصلي:</label>
                                <span><?= formatPrice($qr['original_price']) ?></span>
                            </div>
                            <div class="detail">
                                <label>السعر بعد الخصم:</label>
                                <span class="discounted"><?= formatPrice($qr['discounted_price']) ?></span>
                            </div>
                            <div class="detail">
                                <label>نسبة الخصم:</label>
                                <span class="percentage"><?= $qr['discount_percentage'] ?>%</span>
                            </div>
                            <div class="detail">
                                <label>تاريخ الإنشاء:</label>
                                <span><?= formatDate($qr['created_at']) ?></span>
                            </div>
                            <div class="detail">
                                <label>ينتهي في:</label>
                                <span class="<?= isQRExpired($qr) ? 'expired' : '' ?>"><?= formatDate($qr['expires_at']) ?></span>
                            </div>
                        </div>
                        
                        <div class="qr-analytics">
                            <div class="analytics-item">
                                <i class="fas fa-camera"></i>
                                <span><?= $qr['scan_count'] ?> مسح</span>
                            </div>
                            <div class="analytics-item">
                                <i class="fas fa-check-circle"></i>
                                <span><?= $qr['use_count'] ?> استخدام</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="qr-item-actions">
                        <button class="btn btn-primary" onclick="viewQRCode('<?= $qr['qr_code'] ?>')">
                            <i class="fas fa-eye"></i> عرض التفاصيل
                        </button>
                        <button class="btn btn-secondary" onclick="downloadQRCode('<?= $qr['qr_code'] ?>', '<?= htmlspecialchars($qr['product_title']) ?>')">
                            <i class="fas fa-download"></i> تحميل
                        </button>
                        <?php if (!$qr['is_used'] && !isQRExpired($qr)): ?>
                        <button class="btn btn-warning" onclick="shareQRCode('<?= $qr['qr_code'] ?>', '<?= htmlspecialchars($qr['product_title']) ?>')">
                            <i class="fas fa-share-alt"></i> مشاركة
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- الترقيم -->
    <?php if (!empty($user_qr_codes)): ?>
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 ?>" class="page-link">السابق</a>
        <?php endif; ?>
        
        <span class="page-current">صفحة <?= $page ?></span>
        
        <a href="?page=<?= $page + 1 ?>" class="page-link">التالي</a>
    </div>
    <?php endif; ?>
</div>

<!-- نافذة عرض تفاصيل QR Code -->
<div id="qrDetailsModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>تفاصيل كود QR</h3>
            <span class="close" onclick="closeQRDetailsModal()">&times;</span>
        </div>
        <div class="modal-body" id="qrDetailsContent">
            <!-- سيتم تعبئة المحتوى هنا -->
        </div>
    </div>
</div>

<script>
// دوال الجافاسكريبت للإدارة
function filterQRCodes() {
    const statusFilter = document.getElementById('statusFilter').value;
    const searchFilter = document.getElementById('searchFilter').value.toLowerCase();
    const items = document.querySelectorAll('.qr-code-item');
    
    items.forEach(item => {
        const status = item.getAttribute('data-status');
        const product = item.getAttribute('data-product').toLowerCase();
        
        const statusMatch = statusFilter === 'all' || status === statusFilter;
        const searchMatch = product.includes(searchFilter);
        
        if (statusMatch && searchMatch) {
            item.style.display = 'block';
        } else {
            item.style.display = 'none';
        }
    });
}

function resetFilters() {
    document.getElementById('statusFilter').value = 'all';
    document.getElementById('searchFilter').value = '';
    filterQRCodes();
}

function viewQRCode(qrCode) {
    // جلب تفاصيل QR Code عبر AJAX
    fetch('ajax/get_qr_details.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ qr_code: qrCode })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showQRDetails(data.data);
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('حدث خطأ في جلب التفاصيل');
    });
}

function showQRDetails(qrData) {
    const content = document.getElementById('qrDetailsContent');
    content.innerHTML = `
        <div class="qr-details-view">
            <div class="qr-image-section">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(qrData.qr_code)}" 
                     alt="QR Code" class="qr-image-large">
                <div class="qr-code-text">${qrData.qr_code}</div>
            </div>
            
            <div class="details-grid">
                <div class="detail-row">
                    <label>المنتج:</label>
                    <span>${qrData.product_title}</span>
                </div>
                <div class="detail-row">
                    <label>المتجر:</label>
                    <span>${qrData.store_owner_name}</span>
                </div>
                <div class="detail-row">
                    <label>الحالة:</label>
                    <span class="status-badge ${getQRStatus(qrData)}">${getQRStatusText(qrData)}</span>
                </div>
                <div class="detail-row">
                    <label>السعر الأصلي:</label>
                    <span>${qrData.original_price}</span>
                </div>
                <div class="detail-row">
                    <label>السعر بعد الخصم:</label>
                    <span class="discounted">${qrData.discounted_price}</span>
                </div>
                <div class="detail-row">
                    <label>نسبة الخصم:</label>
                    <span class="percentage">${qrData.discount_percentage}%</span>
                </div>
                <div class="detail-row">
                    <label>تاريخ الإنشاء:</label>
                    <span>${qrData.created_at}</span>
                </div>
                <div class="detail-row">
                    <label>ينتهي في:</label>
                    <span class="${isQRExpired(qrData) ? 'expired' : ''}">${qrData.expires_at}</span>
                </div>
                ${qrData.is_used ? `
                <div class="detail-row">
                    <label>تم الاستخدام في:</label>
                    <span>${qrData.used_at}</span>
                </div>
                ` : ''}
            </div>
            
            <div class="analytics-section">
                <h4>الإحصائيات</h4>
                <div class="analytics-grid">
                    <div class="analytic-item">
                        <i class="fas fa-camera"></i>
                        <span>${qrData.scan_count || 0} عملية مسح</span>
                    </div>
                    <div class="analytic-item">
                        <i class="fas fa-check-circle"></i>
                        <span>${qrData.use_count || 0} عملية استخدام</span>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    document.getElementById('qrDetailsModal').style.display = 'block';
}

function closeQRDetailsModal() {
    document.getElementById('qrDetailsModal').style.display = 'none';
}

// دوال مساعدة
function getQRStatus(qrData) {
    if (qrData.is_used) return 'used';
    if (new Date(qrData.expires_at) < new Date()) return 'expired';
    return 'active';
}

function getQRStatusText(qrData) {
    if (qrData.is_used) return 'مستخدم';
    if (new Date(qrData.expires_at) < new Date()) return 'منتهي';
    return 'نشط';
}

function isQRExpired(qrData) {
    return new Date(qrData.expires_at) < new Date();
}
</script>

<style>
/* أنماط صفحة الإدارة */
.qr-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin: 2rem 0;
}

.stat-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    gap: 1rem;
    border: 1px solid #e5e7eb;
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: white;
}

.stat-icon.total { background: linear-gradient(135deg, #3b82f6, #1d4ed8); }
.stat-icon.active { background: linear-gradient(135deg, #10b981, #059669); }
.stat-icon.used { background: linear-gradient(135deg, #f59e0b, #d97706); }
.stat-icon.expired { background: linear-gradient(135deg, #6b7280, #4b5563); }

.stat-info h3 {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 700;
    color: #1f2937;
}

.stat-info p {
    margin: 0.25rem 0 0 0;
    color: #6b7280;
    font-size: 0.875rem;
}

.qr-filters {
    background: white;
    padding: 1.5rem;
    border-radius: 12px;
    margin: 1.5rem 0;
    border: 1px solid #e5e7eb;
    display: flex;
    gap: 1rem;
    align-items: center;
    flex-wrap: wrap;
}

.filter-group {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.filter-group label {
    font-weight: 600;
    color: #374151;
    white-space: nowrap;
}

.filter-group select,
.filter-group input {
    padding: 0.5rem 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 0.875rem;
}

.qr-codes-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.qr-code-item {
    background: white;
    border-radius: 12px;
    border: 1px solid #e5e7eb;
    overflow: hidden;
}

.qr-item-header {
    padding: 1.5rem;
    border-bottom: 1px solid #f3f4f6;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    background: #f8fafc;
}

.qr-info h4 {
    margin: 0 0 0.5rem 0;
    color: #1f2937;
    font-size: 1.125rem;
}

.qr-meta {
    display: flex;
    gap: 1rem;
    font-size: 0.875rem;
    color: #6b7280;
}

.qr-status .status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.status-badge.active { background: #dcfce7; color: #166534; }
.status-badge.used { background: #fef3c7; color: #92400e; }
.status-badge.expired { background: #f3f4f6; color: #6b7280; }

.qr-item-body {
    padding: 1.5rem;
}

.qr-details-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 1rem;
}

.detail {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
    border-bottom: 1px solid #f3f4f6;
}

.detail label {
    font-weight: 600;
    color: #374151;
    font-size: 0.875rem;
}

.detail span {
    color: #6b7280;
    font-size: 0.875rem;
}

.detail .discounted {
    color: #10b981;
    font-weight: 600;
}

.detail .percentage {
    color: #ef4444;
    font-weight: 600;
}

.detail .expired {
    color: #ef4444;
}

.qr-analytics {
    display: flex;
    gap: 1.5rem;
}

.analytics-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #6b7280;
    font-size: 0.875rem;
}

.analytics-item i {
    color: #3b82f6;
}

.qr-item-actions {
    padding: 1rem 1.5rem;
    background: #f8fafc;
    border-top: 1px solid #e5e7eb;
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    color: #6b7280;
}

.empty-state i {
    font-size: 4rem;
    color: #d1d5db;
    margin-bottom: 1rem;
}

.empty-state h3 {
    color: #374151;
    margin-bottom: 0.5rem;
}

/* نافذة التفاصيل */
.qr-details-view {
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 2rem;
}

.qr-image-section {
    text-align: center;
}

.qr-image-large {
    max-width: 200px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 1rem;
    background: white;
}

.qr-code-text {
    margin-top: 0.5rem;
    font-family: monospace;
    font-size: 0.75rem;
    color: #6b7280;
    word-break: break-all;
}

.details-grid {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.detail-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid #f3f4f6;
}

.detail-row label {
    font-weight: 600;
    color: #374151;
}

.analytics-section {
    grid-column: 1 / -1;
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #e5e7eb;
}

.analytics-grid {
    display: flex;
    gap: 2rem;
}

.analytic-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #374151;
    font-weight: 500;
}

@media (max-width: 768px) {
    .qr-stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .qr-filters {
        flex-direction: column;
        align-items: stretch;
    }
    
    .filter-group {
        justify-content: space-between;
    }
    
    .qr-details-view {
        grid-template-columns: 1fr;
    }
    
    .qr-item-header {
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start;
    }
    
    .qr-details-grid {
        grid-template-columns: 1fr;
    }
    
    .qr-item-actions {
        flex-direction: column;
    }
    
    .analytics-grid {
        flex-direction: column;
        gap: 1rem;
    }
}
</style>

<?php //include 'includes/footer.php'; ?>