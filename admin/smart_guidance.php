<?php
session_start();
require_once '../config.php';
require_once '../functions.php';

if (!isset($_SESSION['customer_id']) || !isMerchant($_SESSION['customer_id'])) {
    header('Location: login.php');
    exit;
}

$merchant_id = $_SESSION['customer_id'];
$page_title = "الاسترشاد الذكي لإدارة العروض";

// معالجة طلب التحليل
if ($_POST['action'] == 'analyze') {
    $capital_data = [
        'capital_amount' => $_POST['capital_amount'],
        'purchase_date' => $_POST['purchase_date'],
        'purchase_price' => $_POST['purchase_price'],
        'loss_tolerance' => $_POST['loss_tolerance']
    ];
    
    $analysis = analyzeProductForOffers($_POST['product_id'], $merchant_id, $capital_data);
    
    if ($analysis) {
        saveSmartGuidance($merchant_id, $_POST['product_id'], $capital_data, $analysis);
        $success_message = "تم تحليل المنتج بنجاح وتوليد التوصيات الذكية";
    }
}

// جلب منتجات التاجر
$products = getMerchantProducts($merchant_id);
// جلب التوصيات السابقة
$previous_recommendations = getMerchantRecommendations($merchant_id);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .smart-guidance-container {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin: 2rem 0;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .analysis-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .capital-input-form {
            background: #f8fafc;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #374151;
        }
        
        .form-group input, .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
        }
        
        .recommendations-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }
        
        .recommendation-card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 1.5rem;
            transition: all 0.3s ease;
        }
        
        .recommendation-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .offer-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        
        .badge-buy2get1 { background: #dcfce7; color: #166534; }
        .badge-coupon { background: #fef3c7; color: #92400e; }
        .badge-qr { background: #dbeafe; color: #1e40af; }
        .badge-points { background: #fce7f3; color: #be185d; }
        .badge-flash { background: #fef3c7; color: #92400e; }
        .badge-bundle { background: #fee2e2; color: #991b1b; }
        
        .effectiveness-meter {
            height: 8px;
            background: #e5e7eb;
            border-radius: 10px;
            margin: 1rem 0;
            overflow: hidden;
        }
        
        .effectiveness-fill {
            height: 100%;
            border-radius: 10px;
            transition: width 0.3s ease;
        }
        
        .fill-high { background: #10b981; }
        .fill-medium { background: #f59e0b; }
        .fill-low { background: #ef4444; }
        
        .profit-loss-indicator {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 600;
            margin: 0.5rem 0;
        }
        
        .profit { color: #10b981; }
        .loss { color: #ef4444; }
        
        .action-buttons {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }
        
        .btn-activate {
            background: #10b981;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            flex: 1;
        }
        
        .btn-details {
            background: #3b82f6;
            color: white;
            border: none;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            cursor: pointer;
        }
        
        .storage-warning {
            background: #fef3c7;
            border: 1px solid #f59e0b;
            border-radius: 8px;
            padding: 1rem;
            margin: 1rem 0;
            color: #92400e;
        }
        
        .risk-indicator {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin: 0.5rem 0;
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <div class="main-content">
        <div class="container">
            <div class="page-header">
                <h1><i class="fas fa-robot"></i> الاسترشاد الذكي لإدارة العروض</h1>
                <p>تحليل ذكي لمنتجاتك وتوصيات مبنية على رأس المال وفترة التخزين</p>
            </div>

            <?php if (isset($success_message)): ?>
                <div class="alert alert-success">
                    <?= $success_message ?>
                </div>
            <?php endif; ?>

            <div class="smart-guidance-container">
                <div class="analysis-header">
                    <h2><i class="fas fa-chart-line"></i> تحليل العروض الذكي</h2>
                    <p>أدخل بيانات رأس المال للحصول على توصيات مخصصة لمنتجاتك</p>
                </div>

                <!-- نموذج إدخال بيانات رأس المال -->
                <form method="POST" class="capital-input-form">
                    <input type="hidden" name="action" value="analyze">
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="product_id"><i class="fas fa-box"></i> اختر المنتج</label>
                            <select name="product_id" id="product_id" required>
                                <option value="">اختر المنتج المراد تحليله</option>
                                <?php foreach ($products as $product): ?>
                                    <option value="<?= $product['id'] ?>">
                                        <?= htmlspecialchars($product['title']) ?> - <?= formatPrice($product['final_price']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="capital_amount"><i class="fas fa-money-bill-wave"></i> رأس المال المستثمر</label>
                            <input type="number" name="capital_amount" id="capital_amount" step="0.01" required 
                                   placeholder="إجمالي رأس المال للمنتج">
                        </div>
                        
                        <div class="form-group">
                            <label for="purchase_price"><i class="fas fa-tag"></i> سعر الشراء للقطعة</label>
                            <input type="number" name="purchase_price" id="purchase_price" step="0.01" required 
                                   placeholder="سعر شراء القطعة الواحدة">
                        </div>
                        
                        <div class="form-group">
                            <label for="purchase_date"><i class="fas fa-calendar"></i> تاريخ الشراء</label>
                            <input type="date" name="purchase_date" id="purchase_date" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="loss_tolerance"><i class="fas fa-exclamation-triangle"></i> سماحية الخسارة (%)</label>
                            <input type="number" name="loss_tolerance" id="loss_tolerance" value="10" step="0.1" min="0" max="100"
                                   placeholder="أقصى نسبة خسارة مقبولة">
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-brain"></i> بدء التحليل الذكي
                    </button>
                </form>

                <!-- عرض التوصيات -->
                <?php if (isset($analysis)): ?>
                    <div class="analysis-results">
                        <h3><i class="fas fa-lightbulb"></i> التوصيات الذكية</h3>
                        
                        <!-- تحذير فترة التخزين -->
                        <?php if ($analysis['storage_duration'] > 90): ?>
                            <div class="storage-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                                <strong>تنبيه:</strong> المنتج مخزن منذ <?= $analysis['storage_duration'] ?> يوم - يوصى بعروض سريعة لتفريغ المخزون
                            </div>
                        <?php endif; ?>
                        
                        <!-- مؤشر الخسارة الحالية -->
                        <div class="risk-indicator">
                            <i class="fas fa-chart-line"></i>
                            الخسارة الحالية: 
                            <span class="<?= $analysis['current_loss_rate'] > 0 ? 'loss' : 'profit' ?>">
                                <?= number_format(abs($analysis['current_loss_rate']), 2) ?>%
                                <?= $analysis['current_loss_rate'] > 0 ? 'خسارة' : 'ربح' ?>
                            </span>
                        </div>

                        <div class="recommendations-grid">
                            <?php foreach ($analysis['recommended_offers'] as $offer): 
                                $offer_analysis = $analysis['analysis'][$offer['type']];
                                $effectiveness_class = $offer_analysis['effectiveness'] > 70 ? 'fill-high' : 
                                                     ($offer_analysis['effectiveness'] > 40 ? 'fill-medium' : 'fill-low');
                            ?>
                                <div class="recommendation-card">
                                    <div class="offer-badge badge-<?= $offer['type'] ?>">
                                        <?= getOfferTypeLabel($offer['type']) ?>
                                    </div>
                                    
                                    <h4><?= getOfferTitle($offer['type']) ?></h4>
                                    <p class="offer-reason"><?= $offer['reason'] ?></p>
                                    
                                    <div class="profit-loss-indicator <?= $offer_analysis['profit_loss_rate'] >= 0 ? 'profit' : 'loss' ?>">
                                        <i class="fas <?= $offer_analysis['profit_loss_rate'] >= 0 ? 'fa-arrow-up' : 'fa-arrow-down' ?>"></i>
                                        <?= number_format($offer_analysis['profit_loss_rate'], 2) ?>%
                                        (<?= formatPrice($offer_analysis['profit_loss']) ?>)
                                    </div>
                                    
                                    <div class="effectiveness-meter">
                                        <div class="effectiveness-fill <?= $effectiveness_class ?>" 
                                             style="width: <?= $offer_analysis['effectiveness'] ?>%"></div>
                                    </div>
                                    <div style="font-size: 0.875rem; color: #6b7280;">
                                        فعالية العرض: <?= number_format($offer_analysis['effectiveness'], 1) ?>%
                                    </div>
                                    
                                    <div class="action-buttons">
                                        <button class="btn-activate" onclick="activateOffer('<?= $offer['type'] ?>', <?= $analysis['product']['id'] ?>)">
                                            <i class="fas fa-play-circle"></i> تفعيل العرض
                                        </button>
                                        <button class="btn-details" onclick="showOfferDetails('<?= $offer['type'] ?>')">
                                            <i class="fas fa-info-circle"></i>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- التوصيات السابقة -->
            <?php if (!empty($previous_recommendations)): ?>
                <div class="smart-guidance-container">
                    <h3><i class="fas fa-history"></i> التوصيات السابقة</h3>
                    <div class="previous-recommendations">
                        <?php foreach ($previous_recommendations as $recommendation): ?>
                            <div class="recommendation-summary">
                                <h4><?= htmlspecialchars($recommendation['product_title']) ?></h4>
                                <p>فترة التخزين: <?= $recommendation['storage_duration'] ?> يوم</p>
                                <!-- يمكن إضافة المزيد من التفاصيل -->
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
    function activateOffer(offerType, productId) {
        // هنا يمكنك إضافة منطق تفعيل العرض
        alert(`سيتم تفعيل عرض ${getOfferTypeLabel(offerType)} على المنتج`);
        
        // مثال: إعادة توجيه لصفحة تفعيل العرض
        window.location.href = `activate_offer.php?type=${offerType}&product_id=${productId}`;
    }
    
    function showOfferDetails(offerType) {
        const details = {
            'buy2_get1': 'عرض اشتري 2 واحصل على 1 مجاناً - مثالي لتفريغ المخزون السريع',
            'coupon': 'كوبون خصم - يناسب المنتجات ذات الحركة المتوسطة',
            'qr_code': 'كود QR للتخفيض - تفاعلي وجاذب للعملاء',
            'points': 'عرض النقاط - يزيد من ولاء العملاء',
            'flash_sale': 'عرض سريع - لتحفيز المبيعات الفورية',
            'bundle': 'عرض حزمة - للحد من الخسائر الكبيرة'
        };
        
        alert(details[offerType] || 'تفاصيل العرض غير متاحة');
    }
    
    function getOfferTypeLabel(type) {
        const labels = {
            'buy2_get1': '2+1',
            'coupon': 'كوبون',
            'qr_code': 'QR',
            'points': 'نقاط',
            'flash_sale': 'سريع',
            'bundle': 'حزمة'
        };
        return labels[type] || type;
    }
    </script>
</body>
</html>