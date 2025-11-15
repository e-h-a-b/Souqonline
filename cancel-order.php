<?php
/**
 * صفحة إلغاء الطلب
 */
session_start();
require_once 'config.php';
require_once 'functions.php';// التحقق من وضع الصيانة
if (getSetting('maintenance_mode', '0') == '1' && !isset($_SESSION['admin_id'])) {
    header('Location: maintenance.php');
    exit;
}

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$order_id = $_GET['id'] ?? 0;
$error = '';
$success = '';

// جلب بيانات الطلب
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$order_id]);
$order = $stmt->fetch();

if (!$order) {
    $error = 'الطلب غير موجود';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $order) {
    $reason = cleanInput($_POST['reason'] ?? '');
    
    if (empty($reason)) {
        $error = 'يرجى إدخال سبب الإلغاء';
    } else {
        try {
            $pdo->beginTransaction();
            
            // تحديث حالة الطلب
            $stmt = $pdo->prepare("UPDATE orders SET status = 'cancelled', cancelled_at = NOW(), admin_notes = CONCAT(COALESCE(admin_notes, ''), ?) WHERE id = ?");
            $stmt->execute(["\nسبب الإلغاء: " . $reason, $order_id]);
            
            // تسجيل في سجل الحالات
            $stmt = $pdo->prepare("INSERT INTO order_status_history (order_id, old_status, new_status, comment, created_by) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$order_id, $order['status'], 'cancelled', 'تم إلغاء الطلب: ' . $reason, $_SESSION['admin_id']]);
            
            // إرجاع الكميات للمخزون
            $stmt = $pdo->prepare("SELECT product_id, qty FROM order_items WHERE order_id = ?");
            $stmt->execute([$order_id]);
            $items = $stmt->fetchAll();
            
            foreach ($items as $item) {
                $stmt = $pdo->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
                $stmt->execute([$item['qty'], $item['product_id']]);
            }
            
            $pdo->commit();
            
            // تسجيل النشاط
            logActivity('order_cancelled', "تم إلغاء الطلب #{$order['order_number']}", $_SESSION['admin_id']);
            
            $success = 'تم إلغاء الطلب بنجاح';
            header('Location: orders.php?success=' . urlencode($success));
            exit;
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'حدث خطأ أثناء إلغاء الطلب: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إلغاء الطلب - لوحة التحكم</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8fafc;
            color: #334155;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .cancel-container {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            padding: 40px;
            max-width: 500px;
            width: 100%;
        }
        .cancel-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .cancel-header i {
            font-size: 60px;
            color: #ef4444;
            margin-bottom: 15px;
        }
        .cancel-header h1 {
            font-size: 24px;
            color: #1e293b;
            margin-bottom: 8px;
        }
        .cancel-header p {
            color: #64748b;
        }
        .order-info {
            background: #f8fafc;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        .info-row:last-child {
            margin-bottom: 0;
        }
        .info-label {
            color: #64748b;
        }
        .info-value {
            font-weight: 600;
            color: #1e293b;
        }
        .form-group {
            margin-bottom: 25px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #334155;
        }
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 15px;
            resize: vertical;
            min-height: 100px;
            transition: all 0.3s;
        }
        .form-group textarea:focus {
            outline: none;
            border-color: #ef4444;
            box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.1);
        }
        .error-message {
            background: #fef2f2;
            color: #dc2626;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #fecaca;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .button-group {
            display: flex;
            gap: 15px;
        }
        .btn {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .btn-danger {
            background: #dc2626;
            color: #fff;
        }
        .btn-danger:hover {
            background: #b91c1c;
            transform: translateY(-2px);
        }
        .btn-secondary {
            background: #64748b;
            color: #fff;
        }
        .btn-secondary:hover {
            background: #475569;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="cancel-container">
        <div class="cancel-header">
            <i class="fas fa-exclamation-triangle"></i>
            <h1>إلغاء الطلب</h1>
            <p>هل أنت متأكد من رغبتك في إلغاء هذا الطلب؟</p>
        </div>

        <?php if ($error): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($order): ?>
            <div class="order-info">
                <div class="info-row">
                    <span class="info-label">رقم الطلب:</span>
                    <span class="info-value"><?= htmlspecialchars($order['order_number']) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">العميل:</span>
                    <span class="info-value"><?= htmlspecialchars($order['customer_name']) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">الإجمالي:</span>
                    <span class="info-value"><?= formatPrice($order['total']) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">الحالة الحالية:</span>
                    <span class="info-value"><?= getOrderStatusText($order['status']) ?></span>
                </div>
            </div>

            <form method="post" action="cancel-order.php?id=<?= $order_id ?>">
                <div class="form-group">
                    <label for="reason">سبب الإلغاء *</label>
                    <textarea id="reason" name="reason" placeholder="أدخل سبب إلغاء الطلب..." required><?= $_POST['reason'] ?? '' ?></textarea>
                </div>

                <div class="button-group">
                    <a href="orders.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-right"></i>
                        رجوع
                    </a>
                    <button type="submit" class="btn btn-danger" onclick="return confirm('هل أنت متأكد من إلغاء الطلب؟ لا يمكن التراجع عن هذا الإجراء.')">
                        <i class="fas fa-ban"></i>
                        تأكيد الإلغاء
                    </button>
                </div>
            </form>
        <?php else: ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <?= htmlspecialchars($error) ?>
            </div>
            <div class="button-group">
                <a href="orders.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-right"></i>
                    العودة للطلبات
                </a>
            </div>
        <?php endif; ?>
    </div>
<button class="back-to-top show" aria-label="العودة للأعلى"><i class="fas fa-arrow-up"></i></button>
</body>
</html>