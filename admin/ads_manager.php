<?php
require_once '../functions.php';

// التحقق من تسجيل الدخول
if (!isset($_SESSION['customer_id'])) {
    header('Location: ../login.php');
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الإعلانات - <?= getSetting('store_name') ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .ads-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .form-section, .ads-list-section {
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #374151;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #3b82f6;
        }
        
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: #2563eb;
            color: white;
        }
        
        .btn-primary:hover {
            background: #1d4ed8;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .table th,
        .table td {
            padding: 12px;
            text-align: right;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .table th {
            background: #f9fafb;
            font-weight: 600;
            color: #374151;
        }
        
        .badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-success { background: #dcfce7; color: #166534; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }
        
        .alert-danger {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        
        .action-links a {
            margin: 0 5px;
            padding: 6px 12px;
            border-radius: 4px;
            text-decoration: none;
            transition: background 0.3s;
        }
        
        .action-links a:hover {
            background: #f3f4f6;
        }
    </style>
</head>
<body>
    <!-- تضمين القائمة الجانبية -->
    <?php include 'sidebar.php'; ?>
    
    <div class="main-content">
        <div class="ads-container">
            <div class="page-header">
                <h1><i class="fas fa-ad"></i> إدارة الإعلانات</h1>
                <p>إنشاء وإدارة إعلاناتك الترويجية</p>
            </div>
            
            <?php if (isset($success)) echo $success; ?>
            <?php if (isset($error)) echo $error; ?>
            
            <!-- قسم إنشاء إعلان جديد -->
            <div class="form-section">
                <h2><i class="fas fa-plus-circle"></i> إنشاء إعلان جديد</h2>
                <form method="POST" enctype="multipart/form-data" class="form-grid">
                    <div class="form-group">
                        <label for="type">نوع الإعلان:</label>
                        <select name="type" id="type" required>
                            <option value="image">صورة</option>
                            <option value="video">فيديو</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="content">الملف:</label>
                        <input type="file" name="content" id="content" required accept="image/*,video/*">
                    </div>
                    
                    <div class="form-group">
                        <label for="title">العنوان:</label>
                        <input type="text" name="title" id="title" placeholder="أدخل عنوان الإعلان">
                    </div>
                    
                    <div class="form-group">
                        <label for="description">الوصف:</label>
                        <textarea name="description" id="description" rows="3" placeholder="أدخل وصف الإعلان"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="product_id">المنتج (اختياري):</label>
                        <select name="product_id" id="product_id">
                            <option value="">لا يوجد</option>
                            <?php foreach ($products as $p): ?>
                                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="discount_increase">زيادة الخصم (%):</label>
                        <input type="number" name="discount_increase" id="discount_increase" min="0" max="100" placeholder="0">
                    </div>
                    
                    <div class="form-group">
                        <label for="points_cost">تكلفة النقاط:</label>
                        <input type="number" name="points_cost" id="points_cost" min="0" placeholder="0">
                    </div>
                    
                    <div class="form-group">
                        <label for="wallet_cost">تكلفة المحفظة:</label>
                        <input type="number" name="wallet_cost" id="wallet_cost" min="0" step="0.01" placeholder="0.00">
                    </div>
                    
                    <div class="form-group">
                        <label for="payment_method">طريقة الدفع:</label>
                        <select name="payment_method" id="payment_method" required>
                            <option value="discount_increase">زيادة خصم</option>
                            <option value="points">نقاط</option>
                            <option value="wallet">محفظة</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="position">الموقع:</label>
                        <select name="position" id="position" required>
                            <option value="between_products">بين المنتجات</option>
                            <option value="popup">نافذة منبثقة</option>
                            <option value="side_button">زر جانبي</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="start_date">تاريخ البدء:</label>
                        <input type="datetime-local" name="start_date" id="start_date">
                    </div>
                    
                    <div class="form-group">
                        <label for="end_date">تاريخ الانتهاء:</label>
                        <input type="datetime-local" name="end_date" id="end_date">
                    </div>
                    
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus"></i> إنشاء إعلان
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- قسم قائمة الإعلانات -->
            <div class="ads-list-section">
                <h2><i class="fas fa-list"></i> إعلاناتك</h2>
                
                <?php if (empty($ads)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> لا توجد إعلانات حالياً
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
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
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ads as $ad): ?>
                                    <tr>
                                        <td><?= $ad['id'] ?></td>
                                        <td><?= htmlspecialchars($ad['title'] ?? 'بدون عنوان') ?></td>
                                        <td>
                                            <?php if ($ad['type'] == 'image'): ?>
                                                <span class="badge badge-info">صورة</span>
                                            <?php else: ?>
                                                <span class="badge badge-warning">فيديو</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($ad['position']) ?></td>
                                        <td>
                                            <?php 
                                                $statusBadge = [
                                                    'pending' => 'badge-warning',
                                                    'approved' => 'badge-success', 
                                                    'rejected' => 'badge-danger'
                                                ];
                                            ?>
                                            <span class="badge <?= $statusBadge[$ad['status']] ?? 'badge-info' ?>">
                                                <?= $ad['status'] ?>
                                            </span>
                                        </td>
                                        <td><?= $ad['views'] ?></td>
                                        <td><?= $ad['clicks'] ?></td>
                                        <td class="action-links">
                                            <a href="ads_manager.php?edit=<?= $ad['id'] ?>" title="تحرير">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="ads_manager.php?delete=<?= $ad['id'] ?>" 
                                               onclick="return confirm('هل أنت متأكد من الحذف؟')" 
                                               title="حذف" style="color: #ef4444;">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        // تحديث القبول بناءً على نوع الملف
        document.getElementById('type').addEventListener('change', function() {
            const fileInput = document.getElementById('content');
            if (this.value === 'image') {
                fileInput.accept = 'image/*';
                fileInput.setAttribute('data-max-size', '10');
            } else {
                fileInput.accept = 'video/*';
                fileInput.setAttribute('data-max-size', '50');
            }
        });
    </script>
</body>
</html>