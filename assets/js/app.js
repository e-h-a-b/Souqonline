/**
 * ملف JavaScript الرئيسي للمتجر الإلكتروني
 * @version 2.0
 */

// =========================
// دوال السلة
// =========================

/**
 * إضافة منتج إلى السلة
 */
async function addToCart(productId, quantity = 1) {
    try {
        const response = await fetch('api/cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'add',
                product_id: productId,
                quantity: quantity
            })
        });

        const data = await response.json();

        if (data.success) {
            updateCartCount(data.cart_count);
            showToast('تمت إضافة المنتج إلى السلة', 'success');
            
            // تأثير بصري على زر السلة
            animateCartIcon();
        } else {
            showToast(data.message || 'حدث خطأ أثناء الإضافة', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('حدث خطأ في الاتصال', 'error');
    }
}

/**
 * تحديث عدد عناصر السلة
 */
function updateCartCount(count) {
    const cartCountElements = document.querySelectorAll('.cart-count, #cart-count');
    cartCountElements.forEach(el => {
        el.textContent = count;
        if (count > 0) {
            el.classList.add('has-items');
        } else {
            el.classList.remove('has-items');
        }
    });
}

/**
 * تحريك أيقونة السلة عند الإضافة
 */
function animateCartIcon() {
    const cartIcon = document.querySelector('.cart-btn');
    if (cartIcon) {
        cartIcon.classList.add('cart-bounce');
        setTimeout(() => {
            cartIcon.classList.remove('cart-bounce');
        }, 600);
    }
}

// =========================
// Toast Notifications
// =========================

/**
 * عرض رسالة منبثقة
 */
function showToast(message, type = 'info') {
    const toast = document.getElementById('toast');
    if (!toast) return;

    toast.textContent = message;
    toast.className = 'toast show ' + type;

    setTimeout(() => {
        toast.classList.remove('show');
    }, 3000);
}

// =========================
// دوال المساعدة
// =========================

/**
 * تحديث Query String في URL
 */
function updateQueryString(key, value) {
    const url = new URL(window.location);
    url.searchParams.set(key, value);
    return url.toString();
}

/**
 * التحقق من صحة البريد الإلكتروني
 */
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

/**
 * التحقق من صحة رقم الهاتف المصري
 */
function validatePhone(phone) {
    const re = /^(01)[0-9]{9}$/;
    return re.test(phone);
}

/**
 * تنسيق السعر
 */
function formatPrice(price) {
    return parseFloat(price).toFixed(2) + ' ج.م';
}

// =========================
// Image Lazy Loading
// =========================

document.addEventListener('DOMContentLoaded', function() {
    // Lazy loading للصور
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    if (img.dataset.src) {
                        img.src = img.dataset.src;
                        img.removeAttribute('data-src');
                        observer.unobserve(img);
                    }
                }
            });
        });

        document.querySelectorAll('img[data-src]').forEach(img => {
            imageObserver.observe(img);
        });
    }

    // تحميل عدد السلة عند تحميل الصفحة
    loadCartCount();
});

/**
 * تحميل عدد عناصر السلة
 */
async function loadCartCount() {
    try {
        const response = await fetch('api/cart.php?action=count');
        const data = await response.json();
        if (data.success) {
            updateCartCount(data.cart_count);
        }
    } catch (error) {
        console.error('Error loading cart count:', error);
    }
}

// =========================
// Search Functionality
// =========================

/**
 * البحث التلقائي (Auto-complete)
 */
let searchTimeout;
const searchInput = document.querySelector('.search-form input[name="search"]');

if (searchInput) {
    searchInput.addEventListener('input', function(e) {
        clearTimeout(searchTimeout);
        const query = e.target.value.trim();

        if (query.length >= 2) {
            searchTimeout = setTimeout(() => {
                performSearch(query);
            }, 500);
        } else {
            hideSearchResults();
        }
    });

    // إخفاء النتائج عند الضغط خارج صندوق البحث
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.header-search')) {
            hideSearchResults();
        }
    });
}

/**
 * تنفيذ البحث
 */
async function performSearch(query) {
    try {
        const response = await fetch(`api/search.php?q=${encodeURIComponent(query)}`);
        const data = await response.json();

        if (data.success && data.products.length > 0) {
            displaySearchResults(data.products);
        } else {
            hideSearchResults();
        }
    } catch (error) {
        console.error('Search error:', error);
    }
}

/**
 * عرض نتائج البحث
 */
function displaySearchResults(products) {
    let resultsContainer = document.querySelector('.search-results');
    
    if (!resultsContainer) {
        resultsContainer = document.createElement('div');
        resultsContainer.className = 'search-results';
        document.querySelector('.header-search').appendChild(resultsContainer);
    }

    let html = '<div class="search-results-list">';
    products.slice(0, 5).forEach(product => {
        html += `
            <a href="product.php?id=${product.id}" class="search-result-item">
                <img src="${product.main_image || 'assets/images/placeholder.jpg'}" alt="${product.title}">
                <div class="result-info">
                    <h4>${product.title}</h4>
                    <span class="result-price">${formatPrice(product.final_price)}</span>
                </div>
            </a>
        `;
    });
    html += '</div>';

    resultsContainer.innerHTML = html;
    resultsContainer.style.display = 'block';
}

/**
 * إخفاء نتائج البحث
 */
function hideSearchResults() {
    const resultsContainer = document.querySelector('.search-results');
    if (resultsContainer) {
        resultsContainer.style.display = 'none';
    }
}

// =========================
// Product Quick View (Modal)
// =========================

/**
 * عرض سريع للمنتج في Modal
 */
function quickView(productId) {
    // يمكن إضافة وظيفة Quick View هنا
    window.location.href = `product.php?id=${productId}`;
}

// =========================
// Wishlist Functions
// =========================

 // دالة المفضلة المحسنة
function toggleWishlist(productId, element) {
    if (!isLoggedIn()) {
        showToast('يجب تسجيل الدخول لإضافة المنتج إلى المفضلة', 'warning');
        window.location.href = 'account.php?redirect=' + encodeURIComponent(window.location.href);
        return;
    }
    
    fetch('ajax/wishlist.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'toggle',
            product_id: productId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // تبديل حالة الزر
            const icon = element.querySelector('i');
            if (data.in_wishlist) {
                element.classList.add('active');
                icon.className = 'fas fa-heart';
                showToast('تمت إضافة المنتج إلى المفضلة', 'success');
            } else {
                element.classList.remove('active');
                icon.className = 'far fa-heart';
                showToast('تمت إزالة المنتج من المفضلة', 'info');
            }
            
            // تحديث العداد في الهيدر
            updateWishlistCount();
        } else {
            showToast(data.message || 'حدث خطأ', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('حدث خطأ في الاتصال', 'error');
    });
}

// تحديث عدد المفضلة في الهيدر
function updateWishlistCount() {
    if (!isLoggedIn()) return;
    
    fetch('ajax/wishlist_count.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const wishlistCount = document.getElementById('wishlist-count');
                if (wishlistCount) {
                    wishlistCount.textContent = data.count;
                }
            }
        })
        .catch(error => console.error('Error updating wishlist count:', error));
}

// التحقق من تسجيل الدخول
function isLoggedIn() {
    return window.customerData && window.customerData.isLoggedIn === true;
}
 

// دوال التقييمات
function voteHelpful(reviewId) {
    fetch('ajax/reviews.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'vote_helpful',
            review_id: reviewId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const helpfulElement = document.querySelector(`[data-review="${reviewId}"] .helpful-count`);
            if (helpfulElement) {
                helpfulElement.textContent = data.new_count;
            }
            showToast('شكراً لك على التصويت', 'success');
        } else {
            showToast(data.message || 'حدث خطأ', 'error');
        }
    });
}

// =========================
// Form Validation
// =========================

/**
 * التحقق من صحة النماذج
 */
document.querySelectorAll('form[data-validate]').forEach(form => {
    form.addEventListener('submit', function(e) {
        let isValid = true;
        const requiredFields = form.querySelectorAll('[required]');

        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                isValid = false;
                field.classList.add('error');
                
                // إضافة رسالة خطأ
                let errorMsg = field.nextElementSibling;
                if (!errorMsg || !errorMsg.classList.contains('error-message')) {
                    errorMsg = document.createElement('span');
                    errorMsg.className = 'error-message';
                    errorMsg.textContent = 'هذا الحقل مطلوب';
                    field.parentNode.insertBefore(errorMsg, field.nextSibling);
                }
            } else {
                field.classList.remove('error');
                const errorMsg = field.nextElementSibling;
                if (errorMsg && errorMsg.classList.contains('error-message')) {
                    errorMsg.remove();
                }
            }
        });

        // التحقق من البريد الإلكتروني
        const emailFields = form.querySelectorAll('input[type="email"]');
        emailFields.forEach(field => {
            if (field.value && !validateEmail(field.value)) {
                isValid = false;
                field.classList.add('error');
                showToast('البريد الإلكتروني غير صحيح', 'error');
            }
        });

        // التحقق من رقم الهاتف
        const phoneFields = form.querySelectorAll('input[type="tel"]');
        phoneFields.forEach(field => {
            if (field.value && !validatePhone(field.value)) {
                isValid = false;
                field.classList.add('error');
                showToast('رقم الهاتف يجب أن يبدأ بـ 01 ويتكون من 11 رقم', 'error');
            }
        });

        if (!isValid) {
            e.preventDefault();
        }
    });
});

// =========================
// Mobile Menu Toggle
// =========================

const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
const mainNav = document.querySelector('.main-nav');

if (mobileMenuBtn) {
    mobileMenuBtn.addEventListener('click', function() {
        mainNav.classList.toggle('active');
        this.classList.toggle('active');
    });
}

// =========================
// Smooth Scroll
// =========================

document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
        const href = this.getAttribute('href');
        if (href !== '#' && document.querySelector(href)) {
            e.preventDefault();
            document.querySelector(href).scrollIntoView({
                behavior: 'smooth'
            });
        }
    });
});

// =========================
// Back to Top Button
// =========================

const backToTopBtn = document.createElement('button');
backToTopBtn.className = 'back-to-top';
backToTopBtn.innerHTML = '<i class="fas fa-arrow-up"></i>';
backToTopBtn.setAttribute('aria-label', 'العودة للأعلى');
document.body.appendChild(backToTopBtn);

window.addEventListener('scroll', function() {
    if (window.pageYOffset > 300) {
        backToTopBtn.classList.add('show');
    } else {
        backToTopBtn.classList.remove('show');
    }
});

backToTopBtn.addEventListener('click', function() {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
});

// =========================
// Image Zoom on Product Page
// =========================

const mainProductImage = document.getElementById('main-product-image');
if (mainProductImage) {
    mainProductImage.addEventListener('mousemove', function(e) {
        const rect = this.getBoundingClientRect();
        const x = ((e.clientX - rect.left) / rect.width) * 100;
        const y = ((e.clientY - rect.top) / rect.height) * 100;
        this.style.transformOrigin = `${x}% ${y}%`;
    });

    mainProductImage.addEventListener('mouseenter', function() {
        this.style.transform = 'scale(1.5)';
    });

    mainProductImage.addEventListener('mouseleave', function() {
        this.style.transform = 'scale(1)';
    });
}

// =========================
// Newsletter Subscription
// =========================

const newsletterForm = document.querySelector('.newsletter-form');
if (newsletterForm) {
    newsletterForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const email = this.querySelector('input[name="email"]').value;
        
        if (!validateEmail(email)) {
            showToast('البريد الإلكتروني غير صحيح', 'error');
            return;
        }

        try {
            const response = await fetch('api/newsletter.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ email })
            });

            const data = await response.json();

            if (data.success) {
                showToast('تم الاشتراك بنجاح في النشرة البريدية', 'success');
                this.reset();
            } else {
                showToast(data.message || 'حدث خطأ', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showToast('حدث خطأ في الاتصال', 'error');
        }
    });
}

// =========================
// Print Order
// =========================

function printOrder() {
    window.print();
}

// =========================
// Console Warning
// =========================

console.log('%cتحذير!', 'color: red; font-size: 40px; font-weight: bold;');
console.log('%cهذه ميزة للمطورين فقط. لا تقم بلصق أي كود هنا إلا إذا كنت تعرف ما تفعله.', 'font-size: 16px;');
// تحديث عدد المفضلة
 
 

// تحديث العدادات عند تحميل الصفحة
document.addEventListener('DOMContentLoaded', function() {
    updateWishlistCount();
});
 