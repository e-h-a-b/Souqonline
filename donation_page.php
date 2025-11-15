<?php
require_once 'donation_system.php';

// جلب المتاجر المتاحة
$stores = DonationSystem::getStoresForDonation();

// جلب إحصائيات عامة
$generalStats = DonationSystem::getDonationStats();

// جلب آخر التبرعات
$recentDonations = DonationSystem::getRecentDonations(5);

// معالجة طلب التبرع
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'make_donation') {
    $donorData = [
        'name' => $_POST['donor_name'],
        'type' => $_POST['donor_type'],
        'email' => $_POST['donor_email'] ?? '',
        'phone' => $_POST['donor_phone'] ?? ''
    ];
    
    $amount = floatval($_POST['amount']);
    $storeId = !empty($_POST['store_id']) ? intval($_POST['store_id']) : null;
    $distributionMethod = $_POST['distribution_method'] ?? 'equal';
    
    $result = DonationSystem::recordDonation($donorData, $amount, $storeId, $distributionMethod);
    
    if ($result['success']) {
        $successMessage = "شكراً لتبرعك! تم تطبيق الخصومات على المنتجات بنجاح.";
        // تحديث الإحصائيات بعد التبرع
        $generalStats = DonationSystem::getDonationStats();
        $recentDonations = DonationSystem::getRecentDonations(5);
    } else {
        $errorMessage = $result['message'];
    }
}

// حساب القيمة المطلوبة عند اختيار متجر
$requiredDonation = null;
if (isset($_GET['store_id'])) {
    $storeId = $_GET['store_id'] ?: null;
    $requiredDonation = DonationSystem::calculateRequiredDonation($storeId);
} else {
    // قيمة افتراضية للمتجر العام
    $requiredDonation = DonationSystem::calculateRequiredDonation();
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نظام التبرعات - تخفيض أسعار المنتجات</title>
    <style>
        :root {
            --primary: #2E86AB;
            --secondary: #A23B72;
            --success: #28a745;
            --warning: #ffc107;
            --danger: #dc3545;
            --light: #f8f9fa;
            --dark: #343a40;
        }
        
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
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .header {
            text-align: center;
            color: white;
            margin-bottom: 40px;
        }
        
        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        
        .header p {
            font-size: 1.2rem;
            opacity: 0.9;
        }
        
        .donation-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 20px;
            border-radius: 15px;
            text-align: center;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark);
        }
        
        input, select, textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(46, 134, 171, 0.1);
        }
        
        .btn {
            padding: 15px 30px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            display: inline-block;
            text-decoration: none;
        }
        
        .btn-primary {
            background: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            background: #1a6a8a;
            transform: translateY(-2px);
        }
        
        .stores-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .store-card {
            background: var(--light);
            border: 2px solid #e1e5e9;
            border-radius: 15px;
            padding: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .store-card:hover, .store-card.selected {
            border-color: var(--primary);
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .store-card.selected {
            background: linear-gradient(135deg, #e3f2fd, #bbdefb);
        }
        
        .store-name {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--primary);
        }
        
        .store-info {
            display: flex;
            justify-content: space-between;
            color: var(--dark);
            font-size: 0.9rem;
            margin-bottom: 10px;
        }
        
        .store-required {
            text-align: center;
            padding: 10px;
            background: var(--warning);
            border-radius: 8px;
            margin-top: 10px;
        }
        
        .required-amount {
            background: var(--warning);
            color: var(--dark);
            padding: 15px;
            border-radius: 10px;
            text-align: center;
            margin: 20px 0;
        }
        
        .required-amount .amount {
            font-size: 1.8rem;
            font-weight: bold;
            margin: 10px 0;
        }
        
        .impact-level {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--primary);
        }
        
        .alert {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .distribution-preview {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
            max-height: 300px;
            overflow-y: auto;
        }
        
        .distribution-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #dee2e6;
        }
        
        .recent-donations {
            margin-top: 40px;
        }
        
        .donation-item {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 10px;
            border-left: 4px solid var(--primary);
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .stores-grid {
                grid-template-columns: 1fr;
            }
            
            .header h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚀 نظام التبرعات لتخفيض الأسعار</h1>
            <p>تبرع وساعد في تخفيض أسعار المنتجات للمستهلكين</p>
        </div>

        <?php if (isset($successMessage)): ?>
            <div class="alert alert-success">
                <?php echo $successMessage; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($errorMessage)): ?>
            <div class="alert alert-danger">
                <?php echo $errorMessage; ?>
            </div>
        <?php endif; ?>

        <!-- إحصائيات عامة -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $generalStats['total_donations'] ?? 0; ?></div>
                <div>إجمالي التبرعات</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($generalStats['total_amount'] ?? 0); ?> ج.م</div>
                <div>إجمالي المبالغ</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $generalStats['unique_donors'] ?? 0; ?></div>
                <div>متبرعين فريدين</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $generalStats['affected_products'] ?? 0; ?></div>
                <div>منتج مستفيد</div>
            </div>
        </div>

        <div class="donation-card">
            <h2 style="margin-bottom: 20px; color: var(--primary);">اختر متجر للتبرع</h2>
            
            <div class="stores-grid" id="storesContainer">
                <?php foreach ($stores as $store): ?>
                    <div class="store-card" data-store-id="<?php echo $store['store_id'] ?? ''; ?>">
                        <div class="store-name"><?php echo $store['store_name']; ?></div>
                        <div class="store-info">
                            <span><?php echo $store['product_count']; ?> منتج</span>
                            <span>~<?php echo number_format($store['avg_price'] ?? 0); ?> ج.م متوسط</span>
                        </div>
                        <div class="store-required">
                            <small><?php echo $store['calculation_note'] ?? ''; ?></small>
                            <div style="font-weight: bold; margin-top: 5px;">
                                مطلوب: <?php echo number_format($store['required_amount']); ?> ج.م
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div id="requiredAmount" style="display: none;">
                <div class="required-amount">
                    <div>القيمة المطلوبة للتبرع:</div>
                    <div class="amount" id="amountDisplay"></div>
                    <div class="impact-level" id="impactLevel"></div>
                    <div id="calculationDetails" style="font-size: 0.9rem; margin-top: 10px;"></div>
                </div>
            </div>

            <form method="POST" id="donationForm">
                <input type="hidden" name="action" value="make_donation">
                <input type="hidden" name="store_id" id="storeId" value="">
                
                <div class="form-group">
                    <label>طريقة توزيع التبرع:</label>
                    <select name="distribution_method" id="distributionMethod">
                        <option value="equal">توزيع متساوي</option>
                        <option value="popularity">حسب الشعبية</option>
                        <option value="price">حسب السعر</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>مبلغ التبرع (ج.م):</label>
                    <input type="number" name="amount" id="amount" min="1" step="1" required 
                           placeholder="أدخل المبلغ المراد التبرع به">
                </div>

                <div class="form-group">
                    <label>نوع المتبرع:</label>
                    <select name="donor_type" required>
                        <option value="individual">فرد</option>
                        <option value="company">شركة</option>
                        <option value="organization">مؤسسة</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>اسم المتبرع:</label>
                    <input type="text" name="donor_name" required placeholder="اسم المتبرع أو المؤسسة">
                </div>

                <div class="form-group">
                    <label>البريد الإلكتروني (اختياري):</label>
                    <input type="email" name="donor_email" placeholder="email@example.com">
                </div>

                <div class="form-group">
                    <label>رقم الهاتف (اختياري):</label>
                    <input type="tel" name="donor_phone" placeholder="01XXXXXXXXX">
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    تأكيد التبرع وتطبيق الخصومات
                </button>
            </form>

            <div id="distributionPreview" class="distribution-preview" style="display: none;">
                <h4>معاينة توزيع الخصومات:</h4>
                <div id="previewContent"></div>
            </div>
        </div>

        <!-- قسم آخر التبرعات -->
        <?php if (!empty($recentDonations)): ?>
        <div class="recent-donations">
            <div class="donation-card">
                <h3 style="margin-bottom: 20px; color: var(--primary);">آخر التبرعات</h3>
                <?php foreach ($recentDonations as $donation): ?>
                    <div class="donation-item">
                        <div style="display: flex; justify-content: between; align-items: center;">
                            <div>
                                <strong><?php echo $donation['donor_name']; ?></strong>
                                (<?php echo $donation['donor_type']; ?>)
                            </div>
                            <div style="font-weight: bold; color: var(--primary);">
                                <?php echo number_format($donation['amount']); ?> ج.م
                            </div>
                        </div>
                        <div style="font-size: 0.9rem; color: #666; margin-top: 5px;">
                            <?php echo date('Y-m-d', strtotime($donation['created_at'])); ?>
                            • <?php echo $donation['affected_products']; ?> منتج مستفيد
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script>
        // اختيار المتجر
        document.querySelectorAll('.store-card').forEach(card => {
            card.addEventListener('click', function() {
                // إزالة التحديد من جميع البطاقات
                document.querySelectorAll('.store-card').forEach(c => {
                    c.classList.remove('selected');
                });
                
                // تحديد البطاقة المختارة
                this.classList.add('selected');
                
                const storeId = this.dataset.storeId;
                document.getElementById('storeId').value = storeId;
                
                // جلب القيمة المطلوبة
                fetch(`donation_ajax.php?action=get_required_amount&store_id=${storeId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            document.getElementById('requiredAmount').style.display = 'block';
                            updateAmountDisplay(data.required_amount, data);
                            
                            // تعيين المبلغ المطلوب كقيمة افتراضية
                            document.getElementById('amount').value = Math.round(data.required_amount);
                            
                            // معاينة التوزيع
                            previewDistribution();
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
            });
        });

        // معاينة التوزيع
        document.getElementById('distributionMethod').addEventListener('change', previewDistribution);
        document.getElementById('amount').addEventListener('input', previewDistribution);

        function previewDistribution() {
            const storeId = document.getElementById('storeId').value;
            const amount = document.getElementById('amount').value;
            const method = document.getElementById('distributionMethod').value;
            
            if (!storeId || !amount || amount < 1) return;
            
            fetch(`donation_ajax.php?action=preview_distribution&store_id=${storeId}&amount=${amount}&method=${method}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('distributionPreview').style.display = 'block';
                        
                        let previewHTML = '';
                        data.distribution.forEach((item, index) => {
                            previewHTML += `
                                <div class="distribution-item">
                                    <div style="flex: 1;">${item.product_title || 'منتج ' + (index + 1)}</div>
                                    <div style="text-align: left;">
                                        ${item.original_price} → <strong>${item.new_price} ج.م</strong>
                                        <br><small>خصم ${item.discount_percentage}%</small>
                                    </div>
                                </div>
                            `;
                        });
                        
                        document.getElementById('previewContent').innerHTML = previewHTML;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        }

        function updateAmountDisplay(amount, details) {
            const amountDisplay = document.getElementById('amountDisplay');
            const impactLevel = document.getElementById('impactLevel');
            const calculationDetails = document.getElementById('calculationDetails');
            
            amountDisplay.textContent = formatPrice(amount);
            impactLevel.textContent = `تأثير: ${getImpactLevel(amount)}`;
            
            calculationDetails.innerHTML = `
                <strong>تفاصيل الحساب:</strong><br>
                • عدد المنتجات: ${details.product_count}<br>
                • متوسط السعر: ${formatPrice(details.avg_price)}<br>
                • القيمة الإجمالية: ${formatPrice(details.total_value)}<br>
                • عامل التقييم: ${details.rating_factor?.toFixed(2) || '1.00'}<br>
                • عامل الطلب: ${details.demand_factor?.toFixed(2) || '1.00'}
            `;
        }

        function formatPrice(amount) {
            if (amount >= 1000) {
                return (amount / 1000).toFixed(1) + ' ألف ج.م';
            }
            return amount.toFixed(0) + ' ج.م';
        }

        function getImpactLevel(amount) {
            if (amount < 100) return 'منخفض';
            if (amount < 500) return 'متوسط';
            if (amount < 1000) return 'جيد';
            return 'عالي';
        }

        // اختيار المتجر العام افتراضياً
        document.querySelector('.store-card').click();
    </script>
</body>
</html>