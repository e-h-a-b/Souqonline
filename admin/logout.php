<?php
/**
 * تسجيل خروج المسؤول
 */
session_start();
require_once '../functions.php';

// تسجيل النشاط
if (isset($_SESSION['admin_id'])) {
    logActivity('logout', 'تسجيل خروج', $_SESSION['admin_id']);
}

// حذف جميع بيانات الجلسة
session_unset();
session_destroy();

// حذف كوكيز الجلسة
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// إعادة التوجيه لصفحة تسجيل الدخول
header('Location: login.php');
exit;