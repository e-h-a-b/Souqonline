
<?php
require_once '../functions.php';

// التحقق من تسجيل الدخول
if (!isset($_SESSION['customer_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

// التحقق من صلاحيات التاجر
if (!canCreateAd($_SESSION['customer_id'])) {
    echo '<div class="alert alert-danger">ليس لديك صلاحية لإنشاء إعلانات. يجب أن تكون تاجراً.</div>';
    exit;
}

$customerId = $_SESSION['customer_id'];
$ads = getUserAds($customerId);

// معالجة النموذج لإنشاء إعلان جديد
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // رفع الملف
    $contentUrl = uploadFile($_FILES['content']);
    if (!$contentUrl) {
        $error = '<div class="alert alert-danger">فشل رفع الملف. يجب أن يكون صورة (jpg, png, gif, webp) أو فيديو (mp4, webm, ogg) وحجمه لا يتجاوز 10 ميجا للصور أو 50 ميجا للفيديو.</div>';
    } else {
        $data = [
            'owner_id' => $customerId,
            'type' => $_POST['type'],
            'content_url' => $contentUrl,
            'title' => cleanInput($_POST['title']),
            'description' => cleanInput($_POST['description']),
            'product_id' => !empty($_POST['product_id']) ? (int)$_POST['product_id'] : null,
            'discount_increase' => !empty($_POST['discount_increase']) ? (float)$_POST['discount_increase'] : 0,
            'points_cost' => !empty($_POST['points_cost']) ? (int)$_POST['points_cost'] : 0,
            'wallet_cost' => !empty($_POST['wallet_cost']) ? (float)$_POST['wallet_cost'] : 0,
            'payment_method' => $_POST['payment_method'],
            'position' => $_POST['position'],
            'start_date' => $_POST['start_date'] ?: null,
            'end_date' => $_POST['end_date'] ?: null
        ];
        
        $result = createAd($data);
        if ($result['success']) {
            $success = '<div class="alert alert-success">تم إنشاء الإعلان بنجاح! سيتم مراجعته قريباً.</div>';
            // تحديث قائمة الإعلانات
            $ads = getUserAds($customerId);
        } else {
            $error = '<div class="alert alert-danger">' . htmlspecialchars($result['message']) . '</div>';
        }
    }
}

$products = getMerchantProducts($customerId);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>إدارة الإعلانات</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <div class="container">
        <h1>إدارة إعلاناتك</h1>
        
        <!-- نموذج إنشاء إعلان -->
        <form method="POST" enctype="multipart/form-data">
            <label>نوع الإعلان:</label>
            <select name="type" required>
                <option value="image">صورة</option>
                <option value="video">فيديو</option>
            </select>
            
            <label>الملف:</label>
            <input type="file" name="content" required>
            
            <label>العنوان:</label>
            <input type="text" name="title">
            
            <label>الوصف:</label>
            <textarea name="description"></textarea>
            
            <label>المنتج (اختياري):</label>
            <select name="product_id">
                <option value="">لا يوجد</option>
                <?php foreach ($products as $p): ?>
                    <option value="<?= $p['id'] ?>"><?= $p['title'] ?></option>
                <?php endforeach; ?>
            </select>
            
            <label>زيادة الخصم (%):</label>
            <input type="number" name="discount_increase" min="0" max="100">
            
            <label>تكلفة النقاط:</label>
            <input type="number" name="points_cost" min="0">
            
            <label>تكلفة المحفظة:</label>
            <input type="number" name="wallet_cost" min="0" step="0.01">
            
            <label>طريقة الدفع:</label>
            <select name="payment_method" required>
                <option value="discount_increase">زيادة خصم</option>
                <option value="points">نقاط</option>
                <option value="wallet">محفظة</option>
            </select>
            
            <label>الموقع:</label>
            <select name="position" required>
                <option value="between_products">بين المنتجات</option>
                <option value="popup">نافذة منبثقة</option>
                <option value="side_button">زر جانبي</option>
            </select>
            
            <label>تاريخ البدء:</label>
            <input type="datetime-local" name="start_date">
            
            <label>تاريخ الانتهاء:</label>
            <input type="datetime-local" name="end_date">
            
            <button type="submit" class="btn btn-primary">إنشاء إعلان</button>
        </form>
        
        <!-- قائمة الإعلانات -->
        <h2>إعلاناتك</h2>
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>العنوان</th>
                    <th>النوع</th>
                    <th>الموقع</th>
                    <th>الحالة</th>
                    <th>مشاهدات</th>
                    <th>نقرات</th>
                    <th>إجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ads as $ad): ?>
                    <tr>
                        <td><?= $ad['id'] ?></td>
                        <td><?= $ad['title'] ?></td>
                        <td><?= $ad['type'] ?></td>
                        <td><?= $ad['position'] ?></td>
                        <td><?= $ad['status'] ?></td>
                        <td><?= $ad['views'] ?></td>
                        <td><?= $ad['clicks'] ?></td>
                        <td>
                            <a href="ads_manager.php?edit=<?= $ad['id'] ?>">تحرير</a> |
                            <a href="ads_manager.php?delete=<?= $ad['id'] ?>" onclick="return confirm('حذف؟')">حذف</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>