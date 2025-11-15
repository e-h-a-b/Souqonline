// معالجة حفظ الإعدادات
if ($_POST && isset($_POST['save_settings'])) {
    $settings = [
        'points_enabled' => $_POST['points_enabled'] ?? '0',
        'points_earn_rate' => (float)$_POST['points_earn_rate'],
        'points_currency_rate' => (int)$_POST['points_currency_rate'],
        'points_min_redeem' => (int)$_POST['points_min_redeem'],
        'points_expire_days' => (int)$_POST['points_expire_days']
    ];
    
    foreach ($settings as $key => $value) {
        updateSetting($key, $value);
    }
    
    $message = "تم حفظ الإعدادات بنجاح";
}