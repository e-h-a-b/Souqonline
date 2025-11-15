<?php
require_once 'config.php';
require_once 'functions.php';

if (isset($_GET['test_date'])) {
    header('Content-Type: application/json');
    $testDate = $_GET['test_date'];
    $result = testBlackFridaySystem($testDate);
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>اختبار نظام الجمعة البيضاء</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .test-form { margin: 20px 0; padding: 15px; background: #f8f9fa; border-radius: 5px; }
        input, button { padding: 8px 12px; margin: 5px; }
        .result { background: #e9ecef; padding: 15px; border-radius: 5px; margin-top: 15px; white-space: pre-wrap; }
        .active { color: green; font-weight: bold; }
        .inactive { color: red; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 اختبار نظام الجمعة البيضاء</h1>
        
        <div class="test-form">
            <h3>اختبار بتاريخ معين:</h3>
            <form method="get">
                <input type="date" name="test_date" value="<?= date('Y-m-d') ?>">
                <button type="submit">اختبار</button>
                <a href="test_black_friday.php"><button type="button">الاختبار بالتاريخ الحالي</button></a>
            </form>
        </div>

        <?php
        $result = testBlackFridaySystem();
        echo "<div class='result'>";
        echo "🎯 نتائج اختبار نظام الجمعة البيضاء\n";
        echo "================================\n";
        echo "التاريخ الحالي: " . $result['current_date'] . "\n";
        echo "التاريخ المستخدم في الاختبار: " . $result['test_date'] . "\n";
        echo "حالة النظام: <span class='" . ($result['is_active'] ? 'active' : 'inactive') . "'>" . 
             ($result['is_active'] ? '✅ نشط' : '❌ غير نشط') . "</span>\n";
        echo "وضع الاختبار: " . ($result['settings']['test_mode'] ? '✅ مفعل' : '❌ معطل') . "\n";
        echo "نسبة الخصم: " . $result['settings']['discount_percentage'] . "%\n";
        echo "مدة العرض: " . $result['settings']['duration_days'] . " أيام\n";
        echo "عدد الفئات المشمولة: " . count($result['settings']['categories']) . "\n";
        
        if ($result['is_active'] && $result['remaining_time']) {
            echo "الوقت المتبقي: " . 
                 $result['remaining_time']['days'] . " أيام, " .
                 $result['remaining_time']['hours'] . " ساعات, " .
                 $result['remaining_time']['minutes'] . " دقائق\n";
        } elseif (!$result['is_active']) {
            echo "💡 التوصية: تفعيل النظام من لوحة التحكم\n";
        }
        
        echo "</div>";
        ?>
        
        <div style="margin-top: 20px;">
            <a href="admin/black_friday.php">⚙️ الذهاب لإعدادات الجمعة البيضاء</a> | 
            <a href="index.php">🏠 الصفحة الرئيسية</a>
        </div>
    </div>
</body>
</html>