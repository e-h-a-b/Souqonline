

<aside class="sidebar">
    <div class="sidebar-header">
        <h2><i class="fas fa-store"></i> لوحة التحكم</h2>
    </div>
    <nav class="sidebar-menu">
        <?php
        // تحديد الصفحة النشطة من معامل URL أو الصفحة الحالية
        $active_page = isset($_GET['page']) ? $_GET['page'] : basename($_SERVER['PHP_SELF']);
        
        $menu_items = [
            'reset.php' => ['icon' => 'fas fa-redo-alt', 'text' => 'إعادة الضبط'],
            'index.php' => ['icon' => 'fas fa-home', 'text' => 'الرئيسية'],
            'products.php' => ['icon' => 'fas fa-box', 'text' => 'المنتجات'],
            'categories.php' => ['icon' => 'fas fa-th-large', 'text' => 'الفئات'],
            'orders.php' => ['icon' => 'fas fa-shopping-cart', 'text' => 'الطلبات'],
            'customers.php' => ['icon' => 'fas fa-users', 'text' => 'العملاء'],
            'wholesalers.php' => ['icon' => 'fas fa-industry', 'text' => 'تجار الجملة'],
            'partners.php' => ['icon' => 'fas fa-handshake', 'text' => 'الشركاء'],
            'delivery-agents.php' => ['icon' => 'fas fa-motorcycle', 'text' => 'مندوبي التوصيل'],
            'coupons.php' => ['icon' => 'fas fa-ticket-alt', 'text' => 'كوبونات الخصم'],
            'points.php' => ['icon' => 'fas fa-coins', 'text' => 'نظام النقاط'],
            'packages.php' => ['icon' => 'fas fa-cubes', 'text' => 'إدارة الباقات/الإشتراكات'], 
'referrals.php' => ['icon' => 'fas fa-share-alt', 'text' => 'إدارة الإحالات'],
            'reviews.php' => ['icon' => 'fas fa-star', 'text' => 'التقييمات'],
            'reports.php' => ['icon' => 'fas fa-chart-bar', 'text' => 'التقارير'],
            'settings.php' => ['icon' => 'fas fa-cog', 'text' => 'الإعدادات'],
            'features.php' => ['icon' => 'fas fa-star', 'text' => 'الخصائص المتقدمة'],
            'scratch_cards.php' => ['icon' => 'fas fa-gift', 'text' => 'كروت الخربشة'],
            'offers.php' => ['icon' => 'fas fa-gift', 'text' => 'عرض اتنين + واحد'],
            'admins.php' => ['icon' => 'fas fa-user-shield', 'text' => 'إدارة المشرفين'],
            'wallets.php' => ['icon' => 'fas fa-wallet', 'text' => 'محافظ العملاء']
        ];
 
        // عرض القوائم
        foreach ($menu_items as $page => $item) {
            $active_class = ($active_page == $page) ? 'active' : '';
            echo '<a href="'.$page.'" class="menu-item '.$active_class.'">
                    <i class="'.$item['icon'].'"></i>
                    <span>'.$item['text'].'</span>
                  </a>';
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
</aside>