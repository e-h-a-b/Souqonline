<aside class="sidebar">
    <!-- زر إظهار/إخفاء القائمة -->
    <div class="sidebar-toggle">
        <i class="fas fa-bars"></i>
    </div>
    
    <div class="sidebar-header">
        <h2><i class="fas fa-store"></i> لوحة التحكم</h2>
    </div>
    <nav class="sidebar-menu">
        <?php
        // تحديد الصفحة النشطة من معامل URL أو الصفحة الحالية
        $active_page = isset($_GET['page']) ? $_GET['page'] : basename($_SERVER['PHP_SELF']);
        
        // الاتصال بقاعدة البيانات
        require_once '../config.php'; 
        require_once '../functions.php';
        // استعلامات للحصول على العدادات
        try {
            //$pdo = Database::connect();
            
            // عدادات إدارة المتجر
            $products_count = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
            $categories_count = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
            $orders_count = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
            $customers_count = $pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn();
            
            // عدادات عروض البيع
            $coupons_count = $pdo->query("SELECT COUNT(*) FROM coupons")->fetchColumn();
            $points_count = $pdo->query("SELECT COUNT(*) FROM customer_points WHERE points > 0")->fetchColumn();
            $scratch_cards_count = $pdo->query("SELECT COUNT(*) FROM scratch_cards")->fetchColumn();
            $features_count = $pdo->query("SELECT COUNT(*) FROM products WHERE is_featured = 1")->fetchColumn();
            $offers_count = $pdo->query("SELECT COUNT(*) FROM product_offers")->fetchColumn();
            $QRCodes_count = $pdo->query("SELECT COUNT(*) FROM store_qr_codes WHERE is_valid = 1")->fetchColumn();
			
            // عدادات الباقات والإشتراكات
            $packages_count = $pdo->query("SELECT COUNT(*) FROM packages")->fetchColumn();
            $referrals_count = $pdo->query("SELECT COUNT(*) FROM referrals")->fetchColumn();
            
            // عدادات الطاقم الإداري
            $admins_count = $pdo->query("SELECT COUNT(*) FROM admins")->fetchColumn();
            $delivery_agents_count = $pdo->query("SELECT COUNT(*) FROM delivery_agents")->fetchColumn();
            $partners_count = $pdo->query("SELECT COUNT(*) FROM partners")->fetchColumn();
            $wholesalers_count = $pdo->query("SELECT COUNT(*) FROM wholesalers")->fetchColumn();
            
            // عدادات المتابعة والتقارير
            $reviews_count = $pdo->query("SELECT COUNT(*) FROM reviews")->fetchColumn();
            $reports_count = $pdo->query("SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()")->fetchColumn();
            $wallets_count = $pdo->query("SELECT COUNT(*) FROM customer_wallets WHERE balance > 0")->fetchColumn();
            
			    // عدادات جديدة للجمعة البيضاء واستعادة المال
    $black_friday_count = $pdo->query("SELECT COUNT(*) FROM black_friday_discounts WHERE is_active = 1")->fetchColumn();
    $cashback_count = $pdo->query("SELECT COUNT(*) FROM product_cashback WHERE is_active = '1'")->fetchColumn();
	
            // عدادات الإعدادات
            $settings_count = $pdo->query("SELECT COUNT(*) FROM settings")->fetchColumn();
            $reset_count = 0; // عداد لإعادة الضبط
            
        } catch (PDOException $e) {
    // في حالة خطأ في قاعدة البيانات، تعيين القيم إلى صفر
    $products_count = $categories_count = $orders_count = $customers_count = 0;
    $coupons_count = $points_count = $scratch_cards_count = $features_count = $offers_count = 0;
    $black_friday_count = $cashback_count = 0; // إضافة المتغيرات الجديدة
    $packages_count = $referrals_count = 0;
    $admins_count = $delivery_agents_count = $partners_count = $wholesalers_count = 0;
    $reviews_count = $reports_count = $wallets_count = 0;
    $settings_count = $reset_count = 0;
        }

        $menu_items = [
            'index.php' => ['icon' => 'fas fa-home', 'text' => 'الرئيسية'],
        ];

        // القوائم المفرعة
        $submenus = [
            'إدارة المتجر' => [
                'icon' => 'fas fa-store',
                'items' => [
                    'products.php' => ['icon' => 'fas fa-box', 'text' => 'المنتجات', 'count' => $products_count],
                    'categories.php' => ['icon' => 'fas fa-th-large', 'text' => 'الفئات', 'count' => $categories_count],
                    'orders.php' => ['icon' => 'fas fa-shopping-cart', 'text' => 'الطلبات', 'count' => $orders_count],
                    'customers.php' => ['icon' => 'fas fa-users', 'text' => 'العملاء', 'count' => $customers_count]
                ]
            ],
            'عروض البيع' => [
                'icon' => 'fas fa-tags',
                'items' => [
                    'smart_guidance.php' => ['icon' => 'fas fa-brain', 'text' => 'بدء التحليل الذكي', 'count' => $points_count],
                    'coupons.php' => ['icon' => 'fas fa-ticket-alt', 'text' => 'كوبونات الخصم', 'count' => $coupons_count],
                    'points.php' => ['icon' => 'fas fa-coins', 'text' => 'نظام النقاط', 'count' => $points_count],
                    'scratch_cards.php' => ['icon' => 'fas fa-gift', 'text' => 'كروت الخربشة', 'count' => $scratch_cards_count],
                    'features.php' => ['icon' => 'fas fa-star', 'text' => 'الخصائص المتقدمة', 'count' => $features_count],
                    'offers.php' => ['icon' => 'fas fa-gift', 'text' => 'عرض اتنين + واحد', 'count' => $offers_count],
                    'black_friday.php' => ['icon' => 'fas fa-bolt', 'text' => 'الجمعة البيضاء', 'count' => $black_friday_count],
                    'cashback.php' => ['icon' => 'fa-solid fa-sack-dollar', 'text' => 'استعادة جزء من المال', 'count' => $cashback_count],
                    'qr_management.php' => ['icon' => 'fas fa-qrcode', 'text' => 'إدارة QR Codesل', 'count' => $QRCodes_count]
 
                ]
            ],
            'الباقات والإشتراكات' => [
                'icon' => 'fas fa-cubes',
                'items' => [
                    'packages.php' => ['icon' => 'fas fa-cube', 'text' => 'الباقات', 'count' => $packages_count],
                    'referrals.php' => ['icon' => 'fas fa-share-alt', 'text' => 'نظام الإحالات', 'count' => $referrals_count]
                ]
            ],
            'الطاقم الإداري' => [
                'icon' => 'fas fa-users-cog',
                'items' => [
                    'admins.php' => ['icon' => 'fas fa-user-shield', 'text' => 'إدارة المشرفين', 'count' => $admins_count],
                    'delivery-agents.php' => ['icon' => 'fas fa-motorcycle', 'text' => 'مندوبي التوصيل', 'count' => $delivery_agents_count],
                    'partners.php' => ['icon' => 'fas fa-handshake', 'text' => 'الشركاء', 'count' => $partners_count],
                    'wholesalers.php' => ['icon' => 'fas fa-industry', 'text' => 'تجار الجملة', 'count' => $wholesalers_count]
                ]
            ],
            'المتابعة والتقارير' => [
                'icon' => 'fas fa-chart-line',
                'items' => [
                    'reviews.php' => ['icon' => 'fas fa-star', 'text' => 'التقييمات', 'count' => $reviews_count],
                    'reports.php' => ['icon' => 'fas fa-chart-bar', 'text' => 'التقارير', 'count' => $reports_count],
                    'wallets.php' => ['icon' => 'fas fa-wallet', 'text' => 'محافظ العملاء', 'count' => $wallets_count]
                ]
            ],
            'الإعدادات' => [
                'icon' => 'fas fa-cogs',
                'items' => [
                    'settings.php' => ['icon' => 'fas fa-cog', 'text' => 'ضبط المتجر', 'count' => $settings_count],
                    'reset.php' => ['icon' => 'fas fa-redo-alt', 'text' => 'إعادة الضبط', 'count' => $reset_count]
                ]
            ]
        ];

        // عرض القوائم الرئيسية
        foreach ($menu_items as $page => $item) {
            $active_class = ($active_page == $page) ? 'active' : '';
            echo '<a href="'.$page.'" class="menu-item '.$active_class.'">
                    <i class="'.$item['icon'].'"></i>
                    <span>'.$item['text'].'</span>
                  </a>';
        }

        // عرض القوائم المفرعة
        foreach ($submenus as $submenu_title => $submenu_data) {
            $has_active = false;
            
            // التحقق إذا كانت أي من الصفحات الفرعية نشطة
            foreach ($submenu_data['items'] as $page => $item) {
                if ($active_page == $page) {
                    $has_active = true;
                    break;
                }
            }
            
            $submenu_active_class = $has_active ? 'active' : '';
            
            echo '<div class="submenu '.$submenu_active_class.'">';
            echo '<div class="submenu-header">';
            echo '<i class="'.$submenu_data['icon'].'"></i>';
            echo '<span>'.$submenu_title.'</span>';
            echo '<i class="fas fa-chevron-down arrow"></i>';
            echo '</div>';
            echo '<div class="submenu-items">';
            
            foreach ($submenu_data['items'] as $page => $item) {
                $active_class = ($active_page == $page) ? 'active' : '';
                echo '<a href="'.$page.'" class="submenu-item '.$active_class.'">';
                echo '<i class="'.$item['icon'].'"></i>';
                echo '<span>'.$item['text'].'</span>';
                if (isset($item['count']) && $item['count'] > 0) {
                    echo '<span class="menu-counter">'.$item['count'].'</span>';
                }
                echo '</a>';
            }
            
            echo '</div>';
            echo '</div>';
        }
        ?>
         
        <!-- الروابط الثابتة -->
        <a href="../index.php" class="menu-item" target="_blank">
            <i class="fas fa-external-link-alt"></i>
            <span>عرض المتجر</span>
        </a>
        <a href="logout.php" class="menu-item">
            <i class="fas fa-sign-out-alt"></i>
            <span>تسجيل الخروج</span>
        </a>
    </nav>
</aside>

<style>
.sidebar {
    position: fixed;
    right: 0;
    top: 0;
    height: 100vh;
    width: 280px;
    background: #2c3e50;
    transition: all 0.3s ease;
    z-index: 1000;
    overflow-y: auto;
}

.sidebar.collapsed {
    width: 70px;
}

.sidebar.collapsed .sidebar-header h2 span,
.sidebar.collapsed .menu-item span,
.sidebar.collapsed .submenu-header span {
    display: none;
}

.sidebar.collapsed .submenu-items {
    display: none !important;
}

.sidebar.collapsed .arrow {
    display: none;
}

.sidebar.collapsed .menu-counter {
    display: none;
}

/* زر التبديل */
.sidebar-toggle {
    /*position: absolute;*/
    left: -40px;
    top: 15px;
    background: #2c3e50;
    color: white;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    border-radius: 5px 0 0 5px;
    transition: all 0.3s;
}

.sidebar-toggle:hover {
    background: #34495e;
}

.sidebar-header {
    padding: 20px 15px;
    border-bottom: 1px solid #34495e;
    text-align: center;
}

.sidebar-header h2 {
    color: white;
    margin: 0;
    font-size: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    flex-direction: row;
    flex-wrap: wrap;
}

.menu-item {
    display: flex;
    align-items: center;
    padding: 12px 15px;
    color: #ecf0f1;
    text-decoration: none;
    transition: all 0.3s;
    border-right: 3px solid transparent;
    position: relative;
}

.menu-item:hover {
    background-color: #34495e;
    color: white;
}

.menu-item.active {
    background-color: #3498db;
    color: white;
    border-right-color: #2980b9;
}

.menu-item i {
    margin-left: 10px;
    width: 20px;
    text-align: center;
}

/* عداد القوائم */
.menu-counter {
    background: #e74c3c;
    color: white;
    border-radius: 10px;
    padding: 2px 8px;
    font-size: 11px;
    font-weight: bold;
    margin-right: auto;
    min-width: 20px;
    text-align: center;
}

.submenu-item .menu-counter {
    background: #e74c3c;
    font-size: 10px;
    padding: 1px 6px;
}

.submenu {
    border-bottom: 1px solid #34495e;
}

.submenu-header {
    display: flex;
    align-items: center;
    padding: 12px 15px;
    cursor: pointer;
    transition: all 0.3s;
    color: #ecf0f1;
    position: relative;
}

.submenu-header:hover {
    background-color: #34495e;
}

.submenu-header i:first-child {
    margin-left: 10px;
}

.submenu-header .arrow {
    margin-right: auto;
    transition: transform 0.3s;
    font-size: 12px;
}

.submenu.active .submenu-header .arrow {
    transform: rotate(180deg);
}

.submenu-items {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease;
    background-color: #34495e;
}

.submenu.active .submenu-items {
    max-height: 500px;
}

.submenu-item {
    display: flex;
    align-items: center;
    padding: 10px 15px 10px 40px;
    color: #bdc3c7;
    text-decoration: none;
    transition: all 0.3s;
    border-right: 3px solid transparent;
    font-size: 14px;
    position: relative;
}

.submenu-item:hover {
    background-color: #3d566e;
    color: white;
}

.submenu-item.active {
    background-color: #3498db;
    color: white;
    border-right-color: #2980b9;
}

.submenu-item i {
    margin-left: 10px;
    width: 16px;
    text-align: center;
    font-size: 14px;
}

/* تحسينات للتصميم المتجاوب */
@media (max-width: 768px) {
    .sidebar {
        width: 70px;
    }
    
    .sidebar:not(.collapsed) {
        width: 280px;
    }
    
    .sidebar-toggle {
        left: -40px;
    }
    
    .menu-counter {
        font-size: 9px;
        padding: 1px 5px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.querySelector('.sidebar');
    const toggleBtn = document.querySelector('.sidebar-toggle');
    const submenuHeaders = document.querySelectorAll('.submenu-header');
    
    // تبديل القائمة الجانبية
    toggleBtn.addEventListener('click', function() {
        sidebar.classList.toggle('collapsed');
        
        // تغيير الأيقونة
        const icon = this.querySelector('i');
        if (sidebar.classList.contains('collapsed')) {
            icon.className = 'fas fa-bars';
        } else {
            icon.className = 'fas fa-times';
        }
    });
    
    // فتح/إغلاق القوائم المفرعة
    submenuHeaders.forEach(header => {
        header.addEventListener('click', function() {
            if (!sidebar.classList.contains('collapsed')) {
                const submenu = this.parentElement;
                submenu.classList.toggle('active');
            }
        });
    });
    
    // فتح القائمة المفرعة النشطة تلقائياً
    const activeSubmenus = document.querySelectorAll('.submenu.active');
    activeSubmenus.forEach(submenu => {
        submenu.classList.add('active');
    });
    
    // إغلاق القائمة تلقائياً على الشاشات الصغيرة
    if (window.innerWidth <= 768) {
        sidebar.classList.add('collapsed');
        toggleBtn.querySelector('i').className = 'fas fa-bars';
    }
});
</script> 