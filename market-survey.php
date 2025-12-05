<?php
// بدء الجلسة واستدعاء الإعدادات
require_once 'config.php';
require_once 'functions.php';

// منع التكرار: مرة واحدة لكل جهاز/متصفح
$session_key = 'survey_2025_completed';
if (isset($_SESSION[$session_key])) {
    header('Location: survey-thanks.php');
    exit;
}

// معالجة الإرسال
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // التحقق من CSRF Token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = "رمز الأمان غير صالح. يرجى تحديث الصفحة والمحاولة مرة أخرى.";
    } else {
        $session_id = session_id();
        $customer_id = $_SESSION['customer_id'] ?? null;
        $is_merchant = (isset($_POST['user_type']) && $_POST['user_type'] === 'merchant') ? 1 : 0;

        try {
            // تنظيف المدخلات
            $gender = cleanInput($_POST['gender'] ?? '');
            $age_group = cleanInput($_POST['age_group'] ?? '');
            $education = cleanInput($_POST['education'] ?? '');
            $income = cleanInput($_POST['income'] ?? '');
            $shopping_frequency = cleanInput($_POST['shopping_frequency'] ?? '');
            $avg_spend = cleanInput($_POST['avg_spend'] ?? '');
            $product_type = cleanInput($_POST['product_type'] ?? '');

            $stmt = $pdo->prepare("INSERT INTO market_survey_responses (
                customer_id, session_id, is_merchant,
                gender, age_group, education, income,
                online_shopping_frequency, avg_spend, purchase_factors,
                interested_categories, product_type_preference,
                online_challenges, attractive_features,
                preferred_offers, loyalty_programs,
                missing_products, suggestions,
                merchant_challenges, needed_services,
                interested_products
            ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");

            $stmt->execute([
                $customer_id,
                $session_id,
                $is_merchant,
                $gender,
                $age_group,
                $education,
                $income,
                $shopping_frequency,
                $avg_spend,
                json_encode($_POST['purchase_factors'] ?? [], JSON_UNESCAPED_UNICODE),
                json_encode($_POST['categories'] ?? [], JSON_UNESCAPED_UNICODE),
                $product_type,
                cleanInput($_POST['challenge'] ?? ''),
                cleanInput($_POST['attractive'] ?? ''),
                cleanInput($_POST['offer'] ?? ''),
                cleanInput($_POST['loyalty'] ?? ''),
                cleanInput($_POST['missing'] ?? ''),
                cleanInput($_POST['suggestions'] ?? ''),
                $is_merchant ? json_encode($_POST['merchant_challenges'] ?? [], JSON_UNESCAPED_UNICODE) : null,
                $is_merchant ? json_encode($_POST['needed_services'] ?? [], JSON_UNESCAPED_UNICODE) : null,
                json_encode($_POST['products'] ?? [], JSON_UNESCAPED_UNICODE)
            ]);

            // منح 50 نقطة مكافأة لمن يجاوب (إذا كان النظام مفعل)
            if ($customer_id && POINTS_ENABLED) {
                try {
                    // التحقق من وجود سجل النقاط أولاً
                    $check_stmt = $pdo->prepare("SELECT id FROM customer_points WHERE customer_id = ?");
                    $check_stmt->execute([$customer_id]);
                    
                    if ($check_stmt->rowCount() === 0) {
                        // إنشاء سجل جديد إذا لم يكن موجوداً
                        $insert_stmt = $pdo->prepare("INSERT INTO customer_points (customer_id, points, total_earned) VALUES (?, 50, 50)");
                        $insert_stmt->execute([$customer_id]);
                    } else {
                        // تحديث السجل الموجود
                        $update_stmt = $pdo->prepare("UPDATE customer_points SET points = points + 50, total_earned = total_earned + 50 WHERE customer_id = ?");
                        $update_stmt->execute([$customer_id]);
                    }
                    
                    // تسجيل المعاملة
                    $transaction_stmt = $pdo->prepare("INSERT INTO point_transactions (customer_id, points, type, description, reference_type, expires_at) VALUES (?, ?, 'earn', 'مكافأة إجابة استبيان دراسة السوق', 'reward', DATE_ADD(NOW(), INTERVAL 365 DAY))");
                    $transaction_stmt->execute([$customer_id, 50]);
                    
                } catch (Exception $points_error) {
                    // تجاهل خطأ النقاط واكمل العملية
                    error_log("خطأ في منح نقاط الاستبيان: " . $points_error->getMessage());
                }
            }

            $_SESSION[$session_key] = true;
            header('Location: survey-thanks.php');
            exit;
        } catch(Exception $e) {
            $error = "حدث خطأ في حفظ البيانات، يرجى المحاولة لاحقاً.";
            error_log("خطأ في استبيان السوق: " . $e->getMessage());
        }
    }
}

// توليد CSRF Token
$csrf_token = generateCSRFToken();

// أيقونات المنتجات
$product_icons = [
    'أحذية الأطفال' => '👟',
    'أحذية النساء' => '👠',
    'أحذية رجالية' => '👞',
    'أدوات وتحسينات المنزل' => '🏠',
    'أزياء الاطفال' => '👶',
    'الآلات الموسيقية' => '🎵',
    'الأجهزة المنزلية' => '🔌',
    'الأعمال والصناعة والعلوم' => '🔬',
    'الأمهات والرضع' => '🤱',
    'الإكسسوارات والجوالات' => '📱',
    'الإلكترونيات' => '💻',
    'البيت والمطبخ' => '🍳',
    'الجمال والصحة' => '💄',
    'الحقائب وأمتعة السفر' => '🎒',
    'الدمى والألعاب' => '🧸',
    'الرياضة وأنشطة الهواء الطلق' => '⚽',
    'السيارات' => '🚗',
    'الصحة والأسرة' => '❤️',
    'الفناء والحديقة والبستان' => '🌳',
    'الفنون والحرف اليدوية والخياطة' => '🎨',
    'الكتب ووسائل الإعلام' => '📚',
    'اللوازم المكتبية والمدرسية' => '📎',
    'المجوهرات والاكسسوارات' => '💎',
    'طعام ومواد غذائية' => '🍎',
    'مستلزمات البيت الذكي' => '🏡',
    'مستلزمات الحيوانات الأليفة' => '🐕',
    'ملابس الشاطئ' => '🏖️',
    'ملابس داخلية وملابس النوم رجالي' => '🩲',
    'ملابس داخلية وملابس للنوم للنساء' => '👙',
    'ملابس رجالي بمقاسات كبيرة' => '👔',
    'ملابس رجالية' => '👕',
    'ملابس نسائية' => '👗',
    'ملابس نسائية بمقاسات كبيرة' => '👚'
];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>استبيان دراسة السوق 2025 - <?= getSetting('store_name') ?? 'متجرنا' ?></title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .survey-container { 
            max-width: 900px; 
            margin: 30px auto; 
            background: white; 
            border-radius: 20px; 
            overflow: hidden; 
            box-shadow: 0 20px 50px rgba(0,0,0,0.1); 
        }
        
        .survey-header { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            color: white; 
            padding: 40px 30px; 
            text-align: center; 
        }
        
        .survey-header h1 { 
            font-size: 2.2rem; 
            margin-bottom: 15px; 
        }
        
        .survey-header p { 
            font-size: 1.2rem; 
            opacity: 0.95; 
            line-height: 1.6;
        }
        
        .survey-body { 
            padding: 40px; 
        }
        
        .section { 
            margin-bottom: 40px; 
            padding: 25px; 
            background: #f8fafc; 
            border-radius: 15px; 
            border-right: 5px solid #667eea; 
        }
        
        .section h3 { 
            color: #1e293b; 
            margin-bottom: 20px; 
            font-size: 1.4rem; 
            display: flex; 
            align-items: center; 
            gap: 10px; 
        }
        
        .options { 
            display: grid; 
            gap: 15px; 
        }
        
        .option { 
            background: white; 
            padding: 18px; 
            border-radius: 12px; 
            border: 2px solid #e2e8f0; 
            cursor: pointer; 
            transition: all 0.3s; 
            display: flex;
            align-items: center;
        }
        
        .option:hover { 
            border-color: #667eea; 
            transform: translateY(-3px); 
            box-shadow: 0 10px 20px rgba(102,126,234,0.1); 
        }
        
        .option input { 
            margin-left: 12px; 
            transform: scale(1.3); 
        }
        
        .checkbox-group { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); 
            gap: 12px; 
        }
        
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .product-card {
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .product-card:hover {
            border-color: #667eea;
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(102,126,234,0.15);
        }
        
        .product-card.selected {
            border-color: #10b981;
            background: linear-gradient(135deg, #f0fdf4, #dcfce7);
            transform: scale(1.02);
        }
        
        .product-icon {
            font-size: 2.5rem;
            margin-bottom: 12px;
            display: block;
        }
        
        .product-name {
            font-weight: 600;
            color: #1e293b;
            font-size: 0.95rem;
            line-height: 1.4;
        }
        
        .product-checkbox {
            position: absolute;
            top: 10px;
            left: 10px;
            transform: scale(1.2);
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .product-card:hover .product-checkbox,
        .product-card.selected .product-checkbox {
            opacity: 1;
        }
        
        textarea { 
            width: 100%; 
            padding: 15px; 
            border: 2px solid #e2e8f0; 
            border-radius: 12px; 
            font-family: inherit; 
            min-height: 120px; 
            resize: vertical;
        }
        
        .btn-submit { 
            background: linear-gradient(135deg, #10b981, #059669); 
            color: white; 
            padding: 18px 50px; 
            font-size: 1.3rem; 
            border: none; 
            border-radius: 50px; 
            cursor: pointer; 
            width: 100%; 
            margin-top: 30px; 
            transition: all 0.3s; 
            font-weight: bold;
        }
        
        .btn-submit:hover { 
            transform: translateY(-3px); 
            box-shadow: 0 15px 30px rgba(16,185,129,0.3); 
        }
        
        .progress { 
            height: 8px; 
            background: #e2e8f0; 
            border-radius: 4px; 
            margin-bottom: 30px; 
            overflow: hidden; 
        }
        
        .progress-bar { 
            height: 100%; 
            background: linear-gradient(90deg, #667eea, #764ba2); 
            width: 0%; 
            transition: width 0.5s; 
        }
        
        .form-control { 
            width: 100%; 
            padding: 15px; 
            border: 2px solid #e2e8f0; 
            border-radius: 12px; 
            font-family: inherit; 
            font-size: 16px;
        }
        
        .error { 
            color: #dc2626; 
            background: #fef2f2; 
            padding: 15px; 
            border-radius: 8px; 
            margin-bottom: 20px; 
            text-align: center; 
            border: 1px solid #fecaca;
        }
        
        .success { 
            color: #059669; 
            background: #f0fdf4; 
            padding: 15px; 
            border-radius: 8px; 
            margin-bottom: 20px; 
            text-align: center; 
            border: 1px solid #bbf7d0;
        }
        
        .required::after {
            content: " *";
            color: #dc2626;
        }
        
        .search-box {
            position: relative;
            margin-bottom: 20px;
        }
        
        .search-box input {
            width: 100%;
            padding: 15px 45px 15px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s;
        }
        
        .search-box input:focus {
            border-color: #667eea;
            outline: none;
            box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
        }
        
        .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            font-size: 1.2rem;
        }
        
        .selected-count {
            background: #667eea;
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 15px;
            display: inline-block;
        }
        
        @media (max-width: 768px) {
            .survey-container {
                margin: 10px;
                border-radius: 15px;
            }
            
            .survey-body {
                padding: 20px;
            }
            
            .section {
                padding: 20px;
            }
            
            .survey-header h1 {
                font-size: 1.8rem;
            }
            
            .checkbox-group {
                grid-template-columns: 1fr;
            }
            
            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
                gap: 10px;
            }
            
            .product-card {
                padding: 15px;
            }
            
            .product-icon {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>

<div class="survey-container">
    <div class="survey-header">
        <h1>📊 استبيان دراسة السوق الكبرى 2025</h1>
        <p>ساعدينا نفهم احتياجاتك بشكل أفضل ونقدملك تجربة تسوق مثالية<br>
        <strong>مدة الاستبيان: 3 دقايق فقط • مكافأة 50 نقطة فورية عند الإكمال</strong></p>
    </div>

    <div class="survey-body">
        <?php if (isset($error)): ?>
            <div class="error"><?= $error ?></div>
        <?php endif; ?>
        
        <div class="progress">
            <div class="progress-bar" id="progress"></div>
        </div>

        <form method="post" id="surveyForm">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            
            <!-- نوع المستخدم -->
            <div class="section">
                <h3>👤 أنت...</h3>
                <div class="options">
                    <label class="option">
                        <input type="radio" name="user_type" value="customer" checked> 
                        عميل / زائر عادي
                    </label>
                    <label class="option">
                        <input type="radio" name="user_type" value="merchant"> 
                        صاحب متجر أو تاجر
                    </label>
                </div>
            </div>

            <!-- القسم 1 -->
            <div class="section">
                <h3>📋 معلومات أساسية <span class="required"></span></h3>
                <div class="options">
                    <label class="option">
                        <input type="radio" name="gender" value="ذكر" required> 
                        ذكر
                    </label>
                    <label class="option">
                        <input type="radio" name="gender" value="أنثى"> 
                        أنثى
                    </label>
                </div>
                
                <div style="margin-top: 20px;">
                    <select name="age_group" class="form-control" required>
                        <option value="">اختر الفئة العمرية</option>
                        <option value="أقل من 18 سنة">أقل من 18 سنة</option>
                        <option value="18-25 سنة">18-25 سنة</option>
                        <option value="26-35 سنة">26-35 سنة</option>
                        <option value="36-45 سنة">36-45 سنة</option>
                        <option value="أكثر من 45 سنة">أكثر من 45 سنة</option>
                    </select>
                </div>
                
                <div style="margin-top: 15px;">
                    <select name="education" class="form-control" required>
                        <option value="">المستوى التعليمي</option>
                        <option value="ثانوية عامة">ثانوية عامة</option>
                        <option value="دبلوم">دبلوم</option>
                        <option value="بكالوريوس">بكالوريوس</option>
                        <option value="ماجستير">ماجستير</option>
                        <option value="دكتوراه">دكتوراه</option>
                    </select>
                </div>
                
                <div style="margin-top: 15px;">
                    <select name="income" class="form-control" required>
                        <option value="">الدخل الشهري</option>
                        <option value="أقل من 3000 جنيه">أقل من 3000 جنيه</option>
                        <option value="3000 - 6000 جنيه">3000 - 6000 جنيه</option>
                        <option value="6000 - 10000 جنيه">6000 - 10000 جنيه</option>
                        <option value="10000 - 15000 جنيه">10000 - 15000 جنيه</option>
                        <option value="أكثر من 15000 جنيه">أكثر من 15000 جنيه</option>
                    </select>
                </div>
            </div>

            <!-- القسم 2 -->
            <div class="section">
                <h3>🛒 عادات التسوق <span class="required"></span></h3>
                <div style="margin-bottom: 15px;">
                    <select name="shopping_frequency" class="form-control" required>
                        <option value="">كم مرة تتسوق عبر الإنترنت؟</option>
                        <option value="يومياً">يومياً</option>
                        <option value="أسبوعياً">أسبوعياً</option>
                        <option value="شهرياً">شهرياً</option>
                        <option value="نادراً">نادراً</option>
                    </select>
                </div>
                
                <div style="margin-bottom: 15px;">
                    <select name="avg_spend" class="form-control" required>
                        <option value="">متوسط إنفاقك في كل عملية شراء</option>
                        <option value="أقل من 100 جنيه">أقل من 100 جنيه</option>
                        <option value="100 - 300 جنيه">100 - 300 جنيه</option>
                        <option value="300 - 500 جنيه">300 - 500 جنيه</option>
                        <option value="500 - 1000 جنيه">500 - 1000 جنيه</option>
                        <option value="أكثر من 1000 جنيه">أكثر من 1000 جنيه</option>
                    </select>
                </div>
                
                <div style="margin-top: 20px;">
                    <label style="display: block; margin-bottom: 10px; font-weight: bold;">العوامل المؤثرة في قرار الشراء:</label>
                    <div class="checkbox-group">
                        <label class="option">
                            <input type="checkbox" name="purchase_factors[]" value="السعر"> السعر
                        </label>
                        <label class="option">
                            <input type="checkbox" name="purchase_factors[]" value="الجودة"> الجودة
                        </label>
                        <label class="option">
                            <input type="checkbox" name="purchase_factors[]" value="العلامة التجارية"> العلامة التجارية
                        </label>
                        <label class="option">
                            <input type="checkbox" name="purchase_factors[]" value="التوصيل السريع"> التوصيل السريع
                        </label>
                        <label class="option">
                            <input type="checkbox" name="purchase_factors[]" value="التقييمات والمراجعات"> التقييمات والمراجعات
                        </label>
                        <label class="option">
                            <input type="checkbox" name="purchase_factors[]" value="خدمة العملاء"> خدمة العملاء
                        </label>
                    </div>
                </div>
            </div>

            <!-- القسم 3 - المنتجات -->
            <div class="section">
                <h3>🛍️ المنتجات التي تهمك</h3>
                <p style="margin-bottom: 20px; color: #64748b;">اختر الفئات والمنتجات التي تهمك أو تبحث عنها:</p>
                
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="productSearch" placeholder="ابحث عن منتج معين...">
                </div>
                
                <div class="selected-count" id="selectedCount">0 منتج مختار</div>
                
                <div class="products-grid" id="productsGrid">
                    <?php foreach ($product_icons as $product => $icon): ?>
                        <label class="product-card" data-product="<?= htmlspecialchars(strtolower($product)) ?>">
                            <input type="checkbox" name="products[]" value="<?= htmlspecialchars($product) ?>" class="product-checkbox">
                            <span class="product-icon"><?= $icon ?></span>
                            <span class="product-name"><?= $product ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- القسم 4 -->
            <div class="section">
                <h3>📦 اهتماماتك التسوقية</h3>
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 10px; font-weight: bold;">الفئات التي تهمك:</label>
                    <div class="checkbox-group">
                        <label class="option">
                            <input type="checkbox" name="categories[]" value="إلكترونيات"> إلكترونيات
                        </label>
                        <label class="option">
                            <input type="checkbox" name="categories[]" value="ملابس وأزياء"> ملابس وأزياء
                        </label>
                        <label class="option">
                            <input type="checkbox" name="categories[]" value="منتجات المنزل"> منتجات المنزل
                        </label>
                        <label class="option">
                            <input type="checkbox" name="categories[]" value="الجمال والعناية"> الجمال والعناية
                        </label>
                        <label class="option">
                            <input type="checkbox" name="categories[]" value="الرياضة"> الرياضة
                        </label>
                        <label class="option">
                            <input type="checkbox" name="categories[]" value="كتب وقرطاسية"> كتب وقرطاسية
                        </label>
                    </div>
                </div>
                
                <div style="margin-top: 15px;">
                    <select name="product_type" class="form-control">
                        <option value="">تفضيلك في نوع المنتجات</option>
                        <option value="منتجات محلية">منتجات محلية</option>
                        <option value="منتجات عالمية">منتجات عالمية</option>
                        <option value="منتجات مستعملة">منتجات مستعملة</option>
                        <option value="لا يهم">لا يهم</option>
                    </select>
                </div>
            </div>

            <!-- القسم 5 -->
            <div class="section">
                <h3>💡 آراؤك وتوقعاتك</h3>
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 10px; font-weight: bold;">ما هي أكبر تحديات التسوق عبر الإنترنت؟</label>
                    <textarea name="challenge" placeholder="اكتب رأيك هنا..."></textarea>
                </div>
                
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 10px; font-weight: bold;">ما الذي يجذبك في متجر إلكتروني؟</label>
                    <textarea name="attractive" placeholder="اكتب رأيك هنا..."></textarea>
                </div>
                
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 10px; font-weight: bold;">ما هي العروض التي تفضل؟</label>
                    <textarea name="offer" placeholder="اكتب رأيك هنا..."></textarea>
                </div>
                
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 10px; font-weight: bold;">هل تشارك في برامج الولاء؟</label>
                    <textarea name="loyalty" placeholder="اكتب رأيك هنا..."></textarea>
                </div>
                
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 10px; font-weight: bold;">ما المنتجات التي تفتقدها في المتاجر الإلكترونية؟</label>
                    <textarea name="missing" placeholder="اكتب رأيك هنا..."></textarea>
                </div>
                
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 10px; font-weight: bold;">اقتراحاتك لتحسين تجربة التسوق:</label>
                    <textarea name="suggestions" placeholder="اكتب اقتراحاتك هنا..."></textarea>
                </div>
            </div>

            <button type="submit" class="btn-submit">
                <i class="fas fa-paper-plane"></i> إرسال الإجابات واستلام 50 نقطة الآن
            </button>
        </form>
    </div>
</div>

<script>
// تحديث شريط التقدم
function updateProgress() {
    const requiredFields = document.querySelectorAll('input[required], select[required]');
    const total = requiredFields.length;
    
    let filled = 0;
    requiredFields.forEach(field => {
        if (field.type === 'radio') {
            const name = field.name;
            if (document.querySelector(`input[name="${name}"]:checked`)) {
                filled++;
            }
        } else if (field.type === 'select-one') {
            if (field.value !== '') {
                filled++;
            }
        } else {
            if (field.value !== '') {
                filled++;
            }
        }
    });
    
    const progress = (filled / total) * 100;
    document.getElementById('progress').style.width = progress + '%';
}

// تحديث عدد المنتجات المختارة
function updateSelectedCount() {
    const selectedProducts = document.querySelectorAll('input[name="products[]"]:checked').length;
    document.getElementById('selectedCount').textContent = `${selectedProducts} منتج مختار`;
}

// البحث في المنتجات
document.getElementById('productSearch').addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const productCards = document.querySelectorAll('.product-card');
    
    productCards.forEach(card => {
        const productName = card.getAttribute('data-product');
        if (productName.includes(searchTerm)) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
});

// تفاعل اختيار المنتجات
document.querySelectorAll('.product-card').forEach(card => {
    const checkbox = card.querySelector('.product-checkbox');
    
    card.addEventListener('click', function(e) {
        if (e.target !== checkbox) {
            checkbox.checked = !checkbox.checked;
        }
        
        if (checkbox.checked) {
            card.classList.add('selected');
        } else {
            card.classList.remove('selected');
        }
        
        updateSelectedCount();
    });
});

// إضافة مستمعات الأحداث
document.querySelectorAll('input, select, textarea').forEach(el => {
    el.addEventListener('change', updateProgress);
    el.addEventListener('input', updateProgress);
});

// تهيئة شريط التقدم عند التحميل
document.addEventListener('DOMContentLoaded', function() {
    updateProgress();
    updateSelectedCount();
});

// التحقق من النموذج قبل الإرسال
document.getElementById('surveyForm').addEventListener('submit', function(e) {
    const requiredFields = document.querySelectorAll('input[required], select[required]');
    let valid = true;
    
    requiredFields.forEach(field => {
        if (field.type === 'radio') {
            const name = field.name;
            if (!document.querySelector(`input[name="${name}"]:checked`)) {
                valid = false;
                field.closest('.section').scrollIntoView({ behavior: 'smooth' });
            }
        } else if (field.value === '') {
            valid = false;
            field.scrollIntoView({ behavior: 'smooth' });
            field.focus();
        }
    });
    
    if (!valid) {
        e.preventDefault();
        alert('يرجى ملء جميع الحقول الإلزامية المشار إليها بعلامة النجمة (*)');
    }
});
</script>
</body>
</html>