// فتح نافذة QR Code
// فتح نافذة QR Code - معدل
function openQRModal(productId, storeOwnerId, productTitle) {
	    // إغلاق الـ popup أولاً
    closeQRPopup(productId);
    
    // باقي الكود كما هو...
    console.log('Opening QR Modal for product:', productId, storeOwnerId, productTitle);
    
    // إظهار نافذة التحميل
    document.getElementById('qrContent').innerHTML = `
        <div class="loading" style="text-align: center; padding: 2rem;">
            <i class="fas fa-spinner fa-spin fa-2x" style="color: #3b82f6;"></i>
            <p style="margin-top: 1rem;">جاري إنشاء كود QR...</p>
        </div>
    `;
 
	
    document.getElementById('qrModal').style.display = 'block';
    console.log('Opening QR Modal for product:', productId, storeOwnerId, productTitle);
    
    // إظهار نافذة التحميل
    document.getElementById('qrContent').innerHTML = `
        <div class="loading" style="text-align: center; padding: 2rem;">
            <i class="fas fa-spinner fa-spin fa-2x" style="color: #3b82f6;"></i>
            <p style="margin-top: 1rem;">جاري إنشاء كود QR...</p>
        </div>
    `;
    
    document.getElementById('qrModal').style.display = 'block';
    
    // طلب إنشاء QR Code من السيرفر
    fetch('ajax/generate_qr_code.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            product_id: parseInt(productId),
            store_owner_id: parseInt(storeOwnerId),
            product_title: productTitle
        })
    })
    .then(response => {
        console.log('Response status:', response.status);
        if (!response.ok) {
            throw new Error('Network response was not ok: ' + response.status);
        }
        return response.text(); // استخدام text() أولاً للتحقق من المحتوى
    })
    .then(text => {
        console.log('Raw response:', text);
        try {
            const data = JSON.parse(text);
            return data;
        } catch (e) {
            console.error('JSON parse error:', e);
            throw new Error('Invalid JSON response: ' + text.substring(0, 100));
        }
    })
    .then(data => {
        console.log('Parsed data:', data);
        if (data.success) {
            document.getElementById('qrContent').innerHTML = `
                <div class="qr-content">
                    <h4>كود التخفيض لـ: ${data.product_title}</h4>
                    
                    <div class="qr-code-image">
                        <img src="${data.qr_image_url}" alt="QR Code" style="max-width: 200px; border: 1px solid #ddd; padding: 10px; background: white;">
                        <p style="font-size: 0.8rem; color: #666; margin-top: 0.5rem;">كود: ${data.qr_code}</p>
                    </div>
                    
                    <div class="qr-details">
                        <div class="qr-detail-item">
                            <div class="detail-label">السعر الأصلي</div>
                            <div class="detail-value">${data.original_price}</div>
                        </div>
                        <div class="qr-detail-item">
                            <div class="detail-label">السعر بعد الخصم</div>
                            <div class="detail-value" style="color: #22c55e;">${data.discounted_price}</div>
                        </div>
                        <div class="qr-detail-item">
                            <div class="detail-label">نسبة الخصم</div>
                            <div class="detail-value" style="color: #ef4444;">${data.discount_percentage}%</div>
                        </div>
                        <div class="qr-detail-item">
                            <div class="detail-label">ينتهي في</div>
                            <div class="detail-value">${data.expires_at}</div>
                        </div>
                    </div>
                    
                    <div class="qr-instructions">
                        <h4>🛍️ كيفية الاستخدام:</h4>
                        <ol>
                            <li>احفظ صورة QR Code على هاتفك</li>
                            <li>اذهب إلى المتجر الفعلي للمنتج</li>
                            <li>اعرض الكود لصاحب المتجر</li>
                            <li>سيقوم بمسح الكود للتأكد من صحته</li>
                            <li>احصل على الخصم فوراً!</li>
                        </ol>
                    </div>
                    
                    <div class="qr-actions" style="margin-top: 1rem; display: flex; gap: 0.5rem; justify-content: center;">
                        <button onclick="downloadQRCode('${data.qr_image_url}')" class="btn btn-primary">
                            <i class="fas fa-download"></i> حفظ الصورة
                        </button>
                        <button onclick="shareQRCode('${data.qr_data}')" class="btn btn-secondary">
                            <i class="fas fa-share"></i> مشاركة
                        </button>
                    </div>
                </div>
            `;
        } else {
            document.getElementById('qrContent').innerHTML = `
                <div class="error-message" style="text-align: center; padding: 2rem; color: #ef4444;">
                    <i class="fas fa-exclamation-triangle fa-2x" style="margin-bottom: 1rem;"></i>
                    <p style="font-size: 1.1rem; margin-bottom: 1rem;">${data.message}</p>
                    <button onclick="closeQRModal()" class="btn btn-primary">إغلاق</button>
                </div>
            `;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('qrContent').innerHTML = `
            <div class="error-message" style="text-align: center; padding: 2rem; color: #ef4444;">
                <i class="fas fa-exclamation-triangle fa-2x" style="margin-bottom: 1rem;"></i>
                <p style="font-size: 1.1rem; margin-bottom: 1rem;">حدث خطأ في إنشاء كود QR</p>
                <p style="font-size: 0.9rem; color: #666; margin-bottom: 1rem;">${error.message}</p>
                <button onclick="closeQRModal()" class="btn btn-primary">إغلاق</button>
            </div>
        `;
    });
}
// إغلاق نافذة QR Code
function closeQRModal() {
    document.getElementById('qrModal').style.display = 'none';
}

// فتح نافذة الماسح الضوئي للتجار
// فتح نافذة الماسح الضوئي مع التحقق
function openQRScannerModal() {
    document.getElementById('qrScannerModal').style.display = 'block';
    document.getElementById('scannerResult').innerHTML = '';
    document.getElementById('manualQRCode').value = '';
    
    // إظهار تعليمات الاستخدام
    showScannerInstructions();
    
    // بدء الماسح الضوئي بعد تأخير بسيط
    setTimeout(() => {
        startQRScanner();
    }, 500);
}

// عرض تعليمات الماسح الضوئي
function showScannerInstructions() {
    const resultDiv = document.getElementById('scannerResult');
    resultDiv.innerHTML = `
        <div class="scanner-instructions-active">
            <i class="fas fa-camera"></i>
            <h4>جاري إعداد الماسح الضوئي</h4>
            <p>يرجى السماح باستخدام الكاميرا عند الطلب</p>
            <div class="loading-spinner"></div>
        </div>
    `;
}

// إغلاق نافذة الماسح الضوئي
function closeQRScannerModal() {
    document.getElementById('qrScannerModal').style.display = 'none';
    stopQRScanner();
}

// بدء الماسح الضوئي مع التحقق من التوفر
function startQRScanner() {
    const video = document.getElementById('qrScanner');
    const resultDiv = document.getElementById('scannerResult');
    
    // إخفاء العنصر النائب وإظهار الفيديو
    const placeholder = document.querySelector('.scanner-placeholder');
    if (placeholder) {
        placeholder.style.display = 'none';
    }
    video.style.display = 'block';
    
    // التحقق من دعم MediaDevices
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        showScannerError('المتصفح لا يدعم الوصول إلى الكاميرا. تأكد من استخدام HTTPS.');
        return;
    }
    
    // طلب الإذن للكاميرا
    navigator.mediaDevices.getUserMedia({ 
        video: { 
            facingMode: "environment",
            width: { ideal: 1280 },
            height: { ideal: 720 }
        } 
    })
    .then(function(stream) {
        video.srcObject = stream;
        video.setAttribute("playsinline", true);
        
        // الانتظار حتى يتم تحميل الفيديو
        video.onloadedmetadata = function() {
            video.play().then(() => {
                // بدء مسح QR بعد تشغيل الفيديو
                scanQRCode(video, resultDiv, stream);
            }).catch(err => {
                showScannerError('تعذر تشغيل الكاميرا: ' + err.message);
            });
        };
    })
    .catch(function(err) {
        console.error('Error accessing camera:', err);
        let errorMessage = 'تعذر الوصول إلى الكاميرا: ';
        
        switch (err.name) {
            case 'NotAllowedError':
                errorMessage += 'تم رفض الإذن. يرجى السماح باستخدام الكاميرا.';
                break;
            case 'NotFoundError':
                errorMessage += 'لم يتم العثور على كاميرا.';
                break;
            case 'NotSupportedError':
                errorMessage += 'المتصفح لا يدعم الكاميرا.';
                break;
            case 'NotReadableError':
                errorMessage += 'الكاميرا مستخدمة من قبل تطبيق آخر.';
                break;
            default:
                errorMessage += err.message;
        }
        
        showScannerError(errorMessage);
    });
}

// مسح QR Code مع معالجة الأخطاء
function scanQRCode(video, resultDiv, stream) {
    const canvas = document.createElement('canvas');
    const context = canvas.getContext('2d');
    let scanning = true;
    
    function tick() {
        if (!scanning) return;
        
        if (video.readyState === video.HAVE_ENOUGH_DATA) {
            try {
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                context.drawImage(video, 0, 0, canvas.width, canvas.height);
                
                const imageData = context.getImageData(0, 0, canvas.width, canvas.height);
                
                // التحقق من توفر مكتبة jsQR
                if (typeof jsQR !== 'undefined') {
                    const code = jsQR(imageData.data, imageData.width, imageData.height);
                    
                    if (code) {
                        scanning = false; // توقف عن المسح عند العثور على كود
                        validateQRCode(code.data);
                        return;
                    }
                } else {
                    showScannerError('مكتبة المسح غير متوفرة');
                    scanning = false;
                    return;
                }
            } catch (error) {
                console.error('Scanning error:', error);
                showScannerError('خطأ في عملية المسح: ' + error.message);
                scanning = false;
                return;
            }
        }
        
        if (scanning) {
            requestAnimationFrame(tick);
        }
    }
    
    tick();
    
    // إرجاع دالة لإيقاف المسح
    return function() {
        scanning = false;
    };
}

// إيقاف الماسح الضوئي
function stopQRScanner() {
    const video = document.getElementById('qrScanner');
    if (video.srcObject) {
        const tracks = video.srcObject.getTracks();
        tracks.forEach(track => track.stop());
        video.srcObject = null;
    }
    
    // إعادة عرض العنصر النائب
    const placeholder = document.querySelector('.scanner-placeholder');
    if (placeholder) {
        placeholder.style.display = 'block';
    }
    video.style.display = 'none';
}

// عرض خطأ الماسح الضوئي
function showScannerError(message) {
    const resultDiv = document.getElementById('scannerResult');
    resultDiv.innerHTML = `
        <div class="scanner-result invalid">
            <i class="fas fa-exclamation-triangle"></i>
            <h4>خطأ في الماسح الضوئي</h4>
            <p>${message}</p>
            <div style="margin-top: 1rem;">
                <button onclick="retryScanner()" class="btn btn-primary" style="margin-right: 0.5rem;">
                    <i class="fas fa-redo"></i> إعادة المحاولة
                </button>
                <button onclick="useManualInput()" class="btn btn-secondary">
                    <i class="fas fa-keyboard"></i> الإدخال اليدوي
                </button>
            </div>
        </div>
    `;
}

// إعادة محاولة المسح
function retryScanner() {
    document.getElementById('scannerResult').innerHTML = '';
    stopQRScanner();
    setTimeout(() => {
        startQRScanner();
    }, 500);
}

// استخدام الإدخال اليدوي
function useManualInput() {
    document.getElementById('scannerResult').innerHTML = '';
    document.querySelector('.manual-input').style.display = 'block';
    stopQRScanner();
}
// التحقق من كود QR
function validateQRCode(qrCode) {
    const resultDiv = document.getElementById('scannerResult');
    
    resultDiv.innerHTML = `<div class="loading">جاري التحقق من الكود...</div>`;
    
    fetch('ajax/validate_qr_code.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ qr_code: qrCode })
    })
    .then(response => response.json())
    .then(data => {
        if (data.valid) {
            resultDiv.innerHTML = `
                <div class="scanner-result valid">
                    <i class="fas fa-check-circle"></i>
                    <h4>الكود صالح ✓</h4>
                    <p><strong>المنتج:</strong> ${data.data.product_title}</p>
                    <p><strong>العميل:</strong> ${data.data.customer_name}</p>
                    <p><strong>السعر الأصلي:</strong> ${data.data.original_price}</p>
                    <p><strong>السعر بعد الخصم:</strong> ${data.data.discounted_price}</p>
                    <button onclick="confirmQRUsage('${qrCode}')" class="btn btn-success">
                        <i class="fas fa-check"></i> تأكيد الاستخدام
                    </button>
                </div>
            `;
        } else {
            resultDiv.innerHTML = `
                <div class="scanner-result invalid">
                    <i class="fas fa-times-circle"></i>
                    <h4>الكود غير صالح ✗</h4>
                    <p>${data.message}</p>
                </div>
            `;
        }
    });
}

// التحقق يدوياً من كود QR
function validateManualQRCode() {
    const manualCode = document.getElementById('manualQRCode').value;
    if (manualCode.trim()) {
        validateQRCode(manualCode);
    }
}

// تأكيد استخدام كود QR
function confirmQRUsage(qrCode) {
    fetch('ajax/use_qr_code.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ qr_code: qrCode })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('scannerResult').innerHTML = `
                <div class="scanner-result valid">
                    <i class="fas fa-check-circle"></i>
                    <h4>تم تأكيد العملية بنجاح ✓</h4>
                    <p>تم استخدام الكود وتطبيق الخصم بنجاح</p>
                    <button onclick="closeQRScannerModal()" class="btn btn-primary">إغلاق</button>
                </div>
            `;
        } else {
            document.getElementById('scannerResult').innerHTML = `
                <div class="scanner-result invalid">
                    <i class="fas fa-times-circle"></i>
                    <h4>فشل في تأكيد العملية</h4>
                    <p>${data.message}</p>
                </div>
            `;
        }
    });
}

// تحميل صورة QR Code
function downloadQRCode(imageUrl) {
    const link = document.createElement('a');
    link.href = imageUrl;
    link.download = 'qr_code_discount.png';
    link.click();
}

// مشاركة كود QR
function shareQRCode(qrData) {
    if (navigator.share) {
        navigator.share({
            title: 'كود تخفيض للمنتج',
            text: 'استخدم هذا الكود للحصول على خصم حصري!',
            url: window.location.href
        });
    } else {
        // نسخ البيانات إلى الحافظة
        navigator.clipboard.writeText(qrData).then(function() {
            alert('تم نسخ بيانات الكود إلى الحافظة');
        });
    }
}

// دالة مساعدة للتحقق من اتصال AJAX
function checkAjaxEndpoint(url) {
    return fetch(url, {
        method: 'HEAD'
    })
    .then(response => response.ok)
    .catch(() => false);
}


// متغير لتتبع الـ Popup المفتوح حالياً
let currentPopup = null;

// تبديل فتح/إغلاق الـ Popup
function toggleQRPopup(productId, storeOwnerId, productTitle, button) {
    const popupId = `qrPopup-${productId}`;
    const popup = document.getElementById(popupId);
    
    // إذا كان هناك popup مفتوح آخر، أغلقه أولاً
    if (currentPopup && currentPopup !== popupId) {
        closeCurrentPopup();
    }
    
    if (popup.classList.contains('show')) {
        // إذا كان الـ popup مفتوحاً، أغلقه
        closeQRPopup(productId);
    } else {
        // إذا كان مغلقاً، افتحه
        openQRPopup(productId, storeOwnerId, productTitle, button);
    }
}

// فتح الـ Popup
function openQRPopup(productId, storeOwnerId, productTitle, button) {
    const popupId = `qrPopup-${productId}`;
    const popup = document.getElementById(popupId);
    
    // إغلاق أي popup مفتوح حالياً
    closeCurrentPopup();
    
    // فتح الـ popup الحالي
    popup.classList.add('show');
    button.classList.add('active');
    
    // تعيين الـ popup الحالي
    currentPopup = popupId;
    
    // إضافة حدث لإغلاق الـ popup عند النقر خارجها
    setTimeout(() => {
        document.addEventListener('click', closePopupOnClickOutside);
    }, 100);
    
    // تتبع التحليلات
    trackQRAnalytics('popup_opened', productId);
}

// إغلاق الـ Popup
function closeQRPopup(productId) {
    const popupId = `qrPopup-${productId}`;
    const popup = document.getElementById(popupId);
    const button = popup?.previousElementSibling;
    
    if (popup) {
        popup.classList.remove('show');
    }
    
    if (button) {
        button.classList.remove('active');
    }
    
    if (currentPopup === popupId) {
        currentPopup = null;
    }
    
    // إزالة حدث النقر خارج الـ popup
    document.removeEventListener('click', closePopupOnClickOutside);
}

// إغلاق الـ Popup الحالي
function closeCurrentPopup() {
    if (currentPopup) {
        const popup = document.getElementById(currentPopup);
        const button = popup?.previousElementSibling;
        
        if (popup) {
            popup.classList.remove('show');
        }
        
        if (button) {
            button.classList.remove('active');
        }
        
        currentPopup = null;
        document.removeEventListener('click', closePopupOnClickOutside);
    }
}

// إغلاق الـ Popup عند النقر خارجها
function closePopupOnClickOutside(event) {
    if (!currentPopup) return;
    
    const popup = document.getElementById(currentPopup);
    const button = popup?.previousElementSibling;
    
    // التحقق إذا كان النقر خارج الـ popup والأيقونة
    if (popup && !popup.contains(event.target) && !button.contains(event.target)) {
        closeCurrentPopup();
    }
}

// إغلاق جميع الـ Popups عند التمرير
window.addEventListener('scroll', function() {
    closeCurrentPopup();
});

// إغلاق الـ Popups عند تغيير حجم النافذة
window.addEventListener('resize', function() {
    closeCurrentPopup();
});

// التحقق من وجود ملفات AJAX عند تحميل الصفحة
document.addEventListener('DOMContentLoaded', function() {
    checkAjaxEndpoint('ajax/generate_qr_code.php').then(exists => {
        if (!exists) {
            console.error('QR code endpoint not found');
        }
    });
});
// إغلاق النوافذ عند النقر خارجها
window.onclick = function(event) {
    const qrModal = document.getElementById('qrModal');
    const scannerModal = document.getElementById('qrScannerModal');
    
    if (event.target === qrModal) {
        closeQRModal();
    }
    if (event.target === scannerModal) {
        closeQRScannerModal();
    }
}