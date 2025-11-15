<?php
require_once 'functions.php';
$storeName = getSetting('store_name', 'متجر إلكتروني');
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>المتجر في وضع الصيانة - <?= htmlspecialchars($storeName) ?></title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8fafc;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 20px;
            color: #334155;
        }
        .maintenance-container {
            max-width: 600px;
            background: #fff;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .maintenance-container i {
            font-size: 64px;
            color: #667eea;
            margin-bottom: 20px;
        }
        .maintenance-container h1 {
            font-size: 32px;
            margin-bottom: 20px;
        }
        .maintenance-container p {
            font-size: 18px;
            line-height: 1.6;
            color: #64748b;
            margin-bottom: 20px;
        }
        .btn-home {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 24px;
            background: #667eea;
            color: #fff;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
        }
        .btn-home:hover {
            background: #5a4ed1;
        }
    </style>
</head>
<body>
    <div class="maintenance-container">
        <i class="fas fa-tools"></i>
        <h1>المتجر في وضع الصيانة</h1>
        <p>نعتذر عن الإزعاج، المتجر تحت الصيانة حاليًا. سنعود قريبًا!</p>
        <a href="index.php" class="btn-home"><i class="fas fa-home"></i> العودة للرئيسية</a>
    </div>
</body>
</html>