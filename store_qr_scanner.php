<?php
session_start();
require_once 'config.php';
require_once 'functions.php';

if (!isset($_SESSION['customer_id'])) {
    header('Location: login.php');
    exit;
}

$page_title = "مسح كود QR - المتجر";
//include 'includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1><i class="fas fa-qrcode"></i> مسح كود QR للتخفيضات</h1>
        <p>استخدم هذه الصفحة لمسح أكواد QR التي يعرضها العملاء للحصول على تخفيضات</p>
    </div>

    <div class="scanner-container">
        <div class="scanner-card">
            <div class="scanner-header">
                <h3>الماسح الضوئي</h3>
                <button onclick="startScanner()" class="btn btn-primary" id="startScannerBtn">
                    <i class="fas fa-camera"></i> بدء المسح
                </button>
            </div>

            <div class="scanner-area">
                <div id="scannerPlaceholder" class="scanner-placeholder">
                    <i class="fas fa-qrcode"></i>
                    <p>انقر على "بدء المسح" لتفعيل الكاميرا</p>
                </div>
                <video id="qrVideo" style="display: none;"></video>
                <canvas id="qrCanvas" style="display: none;"></canvas>
            </div>

            <div id="scannerResult" class="scanner-result"></div>

            <div class="manual-section">
                <h4>أو أدخل الكود يدوياً</h4>
                <div class="input-group">
                    <input type="text" id="manualCode" placeholder="أدخل كود QR هنا" class="form-control">
                    <button onclick="validateManualCode()" class="btn btn-secondary">تحقق</button>
                </div>
            </div>
        </div>

        <div class="recent-scans">
            <h3>آخر المسوحات</h3>
            <div id="recentScansList">
                <!-- سيتم تعبئتها بالجافاسكريبت -->
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.js"></script>
<script>
// كود الجافاسكريبت للماسح الضوئي (مشابه للكود السابق)
</script>

<?php include 'includes/footer.php'; ?>