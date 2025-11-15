<?php
session_start();
require_once '../functions.php';

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'غير مصرح']);
    exit;
}

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'معرف العرض مطلوب']);
    exit;
}

$offerId = (int)$_GET['id'];
$offer = getProductOffer($offerId);
$products = getProducts(['limit' => 1000]);

if (!$offer) {
    echo json_encode(['success' => false, 'message' => 'العرض غير موجود']);
    exit;
}

ob_start();
?>
<div class="row">
    <div class="col-md-6">
        <div class="mb-3">
            <label class="form-label">المنتج</label>
            <select name="product_id" class="form-select" required>
                <option value="">اختر المنتج</option>
                <?php foreach ($products as $product): ?>
                    <option value="<?= $product['id'] ?>" <?= $product['id'] == $offer['product_id'] ? 'selected' : '' ?>>
                        <?= $product['title'] ?> - <?= formatPrice($product['final_price']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <label class="form-label">نوع العرض</label>
            <select name="offer_type" class="form-select" required>
                <option value="buy2_get1" <?= $offer['offer_type'] == 'buy2_get1' ? 'selected' : '' ?>>اشتري 2 واحصل على 1 مجاناً</option>
                <option value="discount" <?= $offer['offer_type'] == 'discount' ? 'selected' : '' ?>>خصم</option>
                <option value="free_shipping" <?= $offer['offer_type'] == 'free_shipping' ? 'selected' : '' ?>>شحن مجاني</option>
            </select>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="mb-3">
            <label class="form-label">عنوان العرض</label>
            <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($offer['title']) ?>" required>
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <label class="form-label">الحد الأدنى للكمية</label>
            <input type="number" name="min_quantity" class="form-control" 
                   value="<?= $offer['min_quantity'] ?>" min="3" required>
        </div>
    </div>
</div>

<div class="mb-3">
    <label class="form-label">وصف العرض</label>
    <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($offer['description']) ?></textarea>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="mb-3">
            <label class="form-label">تاريخ البداية</label>
            <input type="datetime-local" name="start_date" class="form-control" 
                   value="<?= $offer['start_date'] ? date('Y-m-d\TH:i', strtotime($offer['start_date'])) : '' ?>">
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <label class="form-label">تاريخ النهاية</label>
            <input type="datetime-local" name="end_date" class="form-control"
                   value="<?= $offer['end_date'] ? date('Y-m-d\TH:i', strtotime($offer['end_date'])) : '' ?>">
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="mb-3">
            <label class="form-label">الحد الأقصى للاستخدام</label>
            <input type="number" name="usage_limit" class="form-control" 
                   value="<?= $offer['usage_limit'] ?>">
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <label class="form-label">الحالة</label>
            <select name="is_active" class="form-select">
                <option value="1" <?= $offer['is_active'] ? 'selected' : '' ?>>نشط</option>
                <option value="0" <?= !$offer['is_active'] ? 'selected' : '' ?>>غير نشط</option>
            </select>
        </div>
    </div>
</div>
<?php
$html = ob_get_clean();

echo json_encode([
    'success' => true,
    'offer' => $offer,
    'html' => $html
]);