<?php
/**
 * نموذج إضافة تقييم للمنتج
 */
require_once 'functions.php';

// التحقق من وضع الصيانة
if (getSetting('maintenance_mode', '0') == '1' && !isset($_SESSION['admin_id'])) {
    header('Location: maintenance.php');
    exit;
}

$productId = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
$product = getProduct($productId);

$storeDescription = getSetting('store_description', '');
if (!$product) {
    header('Location: index.php');
    exit;
}

// معالجة إرسال النموذج
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
    $firstName = cleanInput($_POST['first_name'] ?? '');
    $email = cleanInput($_POST['email'] ?? '');
    $title = cleanInput($_POST['title'] ?? '');
    $comment = cleanInput($_POST['comment'] ?? '');
    
    // التحقق من صحة البيانات
    $errors = [];
    
    if ($rating < 1 || $rating > 5) {
        $errors[] = 'الرجاء اختيار تقييم صحيح بين 1 و 5 نجوم';
    }
    
    if (empty($firstName)) {
        $errors[] = 'الرجاء إدخال الاسم الأول';
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'الرجاء إدخال بريد إلكتروني صحيح';
    }
    
    if (empty($title)) {
        $errors[] = 'الرجاء إدخال عنوان للتقييم';
    }
    
    if (empty($comment)) {
        $errors[] = 'الرجاء إدخال تعليق للتقييم';
    }
    
    // التحقق من عدم وجود تقييم سابق بنفس البريد الإلكتروني
    if (empty($errors) && !canAddReview($productId, null, $email)) {
        $errors[] = 'لقد قمت بتقييم هذا المنتج مسبقاً';
    }
    
    if (empty($errors)) {
        $reviewData = [
            'first_name' => $firstName,
            'email' => $email,
            'rating' => $rating,
            'title' => $title,
            'comment' => $comment,
            'customer_id' => $_SESSION['customer_id'] ?? null,
            'is_verified_purchase' => 0 // يمكن تطوير النظام للتحقق من الشراء
        ];
        
        if (addProductReview($productId, $reviewData)) {
            $_SESSION['success_message'] = 'شكراً لك! تم إرسال تقييمك بنجاح وسيظهر بعد المراجعة.';
            header('Location: product.php?id=' . $productId);
            exit;
        } else {
            $errors[] = 'حدث خطأ أثناء إضافة التقييم. الرجاء المحاولة مرة أخرى.';
        }
    }
}

$storeName = getSetting('store_name', 'متجر إلكتروني');
$cartCount = getCartCount();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إضافة تقييم - <?= htmlspecialchars($product['title']) ?> - <?= htmlspecialchars($storeName) ?></title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .rating-stars {
            direction: ltr;
            text-align: right;
        }
        .rating-stars input {
            display: none;
        }
        .rating-stars label {
            font-size: 2rem;
            color: #ddd;
            cursor: pointer;
            transition: color 0.2s;
        }
        .rating-stars label:hover,
        .rating-stars input:checked ~ label {
            color: #ffc107;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
        }
        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 0.375rem;
            font-size: 1rem;
        }
        .form-control:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 2px rgba(0,123,255,0.25);
        }
        .btn-submit {
            background: #007bff;
            color: white;
            padding: 0.75rem 2rem;
            border: none;
            border-radius: 0.375rem;
            font-size: 1.1rem;
            cursor: pointer;
            transition: background 0.3s;
        }
        .btn-submit:hover {
            background: #0056b3;
        }
        .error-message {
            color: #dc3545;
            background: #f8d7da;
            padding: 0.75rem;
            border-radius: 0.375rem;
            margin-bottom: 1rem;
        }
        .success-message {
            color: #155724;
            background: #d4edda;
            padding: 0.75rem;
            border-radius: 0.375rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="site-header">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <a href="index.php"><h1><?= htmlspecialchars($storeName) ?></h1></a>
                </div>
                <div class="header-actions">
                    <a href="cart.php" class="cart-btn">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="cart-count"><?= $cartCount ?></span>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Breadcrumb -->
    <div class="breadcrumb">
        <div class="container">
            <a href="index.php">الرئيسية</a>
            <i class="fas fa-chevron-left"></i>
            <a href="product.php?id=<?= $product['id'] ?>"><?= htmlspecialchars($product['title']) ?></a>
            <i class="fas fa-chevron-left"></i>
            <span>إضافة تقييم</span>
        </div>
    </div>

    <!-- Review Form -->
    <main class="review-page">
        <div class="container">
            <div class="review-form-wrapper">
                <div class="product-info-review">
                    <img src="<?= htmlspecialchars($product['main_image'] ?: 'assets/images/placeholder.jpg') ?>" 
                         alt="<?= htmlspecialchars($product['title']) ?>" class="product-image-review">
                    <div class="product-details-review">
                        <h2><?= htmlspecialchars($product['title']) ?></h2>
                        <div class="product-price">
                            <?= formatPrice($product['final_price']) ?>
                        </div>
                    </div>
                </div>

                <form method="POST" class="review-form">
                    <h2>أضف تقييمك للمنتج</h2>
                    
                    <?php if (!empty($errors)): ?>
                        <div class="error-message">
                            <?php foreach ($errors as $error): ?>
                                <p><?= htmlspecialchars($error) ?></p>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label class="form-label">التقييم *</label>
                        <div class="rating-stars">
                            <input type="radio" id="star5" name="rating" value="5" required>
                            <label for="star5"><i class="fas fa-star"></i></label>
                            <input type="radio" id="star4" name="rating" value="4">
                            <label for="star4"><i class="fas fa-star"></i></label>
                            <input type="radio" id="star3" name="rating" value="3">
                            <label for="star3"><i class="fas fa-star"></i></label>
                            <input type="radio" id="star2" name="rating" value="2">
                            <label for="star2"><i class="fas fa-star"></i></label>
                            <input type="radio" id="star1" name="rating" value="1">
                            <label for="star1"><i class="fas fa-star"></i></label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="first_name" class="form-label">الاسم الأول *</label>
                        <input type="text" id="first_name" name="first_name" class="form-control" 
                               value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="email" class="form-label">البريد الإلكتروني *</label>
                        <input type="email" id="email" name="email" class="form-control" 
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="title" class="form-label">عنوان التقييم *</label>
                        <input type="text" id="title" name="title" class="form-control" 
                               value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" 
                               placeholder="اكتب عنواناً مختصراً للتقييم" required>
                    </div>

                    <div class="form-group">
                        <label for="comment" class="form-label">التعليق *</label>
                        <textarea id="comment" name="comment" class="form-control" rows="6" 
                                  placeholder="اكتب تجربتك مع المنتج، ما الذي أعجبك أو لم يعجبك..." required><?= htmlspecialchars($_POST['comment'] ?? '') ?></textarea>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-submit">
                            <i class="fas fa-paper-plane"></i> إرسال التقييم
                        </button>
                        <a href="product.php?id=<?= $product['id'] ?>" class="btn-cancel">
                            إلغاء
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </main>

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


    <script>
        // تفعيل النجوم عند النقر
        document.querySelectorAll('.rating-stars label').forEach(star => {
            star.addEventListener('click', function() {
                const rating = this.previousElementSibling.value;
                // إزالة التحديد من جميع النجوم
                document.querySelectorAll('.rating-stars input').forEach(input => {
                    input.checked = false;
                });
                // تحديد النجم المناسب وجميع النجوم الأكبر
                let current = this;
                while (current) {
                    if (current.previousElementSibling) {
                        current.previousElementSibling.checked = true;
                        current = current.previousElementSibling.previousElementSibling;
                    } else {
                        break;
                    }
                }
            });
        });
    </script>
</body>
</html>