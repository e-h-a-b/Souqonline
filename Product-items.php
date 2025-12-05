
    <!-- CSS خاص بـ model-viewer -->
    <style>
        model-viewer {
            width: 100%;
            height: 300px;
            background-color: #f8f9fa;
            --poster-color: transparent;
        }
        
        model-viewer::part(default-progress-bar) {
            background-color: #667eea;
        }
		/* أنماط البديل المحسن للنماذج ثلاثية الأبعاد */
.enhanced-fallback {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    height: 100%;
    display: flex;
    flex-direction: column;
}

.enhanced-fallback .fallback-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 1rem 1.5rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.file-badge {
    background: rgba(255,255,255,0.2);
    padding: 0.25rem 0.75rem;
    border-radius: 15px;
    font-size: 0.8rem;
    font-weight: 600;
    backdrop-filter: blur(10px);
}

.enhanced-fallback .fallback-preview {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 2rem;
    background: #f8fafc;
}

.preview-visual {
    margin-bottom: 1.5rem;
}

.cube-animation {
    width: 80px;
    height: 80px;
    position: relative;
    transform-style: preserve-3d;
    animation: cubeRotate 10s infinite linear;
}

.cube-face {
    position: absolute;
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, #667eea, #764ba2);
    border: 2px solid rgba(255,255,255,0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    font-size: 0.8rem;
}

.cube-face.front { transform: translateZ(40px); }
.cube-face.back { transform: translateZ(-40px) rotateY(180deg); }
.cube-face.right { transform: translateX(40px) rotateY(90deg); }
.cube-face.left { transform: translateX(-40px) rotateY(-90deg); }
.cube-face.top { transform: translateY(-40px) rotateX(90deg); }
.cube-face.bottom { transform: translateY(40px) rotateX(-90deg); }

@keyframes cubeRotate {
    0% { transform: rotateY(0) rotateX(0); }
    100% { transform: rotateY(360deg) rotateX(360deg); }
}

.preview-info {
    text-align: center;
}

.file-name {
    font-weight: 600;
    color: #2d3748;
    margin-bottom: 0.25rem;
}

.file-type {
    color: #718096;
    font-size: 0.9rem;
}

.enhanced-actions {
    padding: 1rem 1.5rem;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.75rem;
    border-top: 1px solid #e2e8f0;
}

.btn-action {
    padding: 0.75rem 1rem;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
    font-size: 0.85rem;
}

.btn-action.primary {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    grid-column: 1 / -1;
}

.btn-action.secondary {
    background: #edf2f7;
    color: #4a5568;
    border: 1px solid #e2e8f0;
}

.btn-action:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.btn-action.primary:hover {
    background: linear-gradient(135deg, #059669, #047857);
}

.btn-action.secondary:hover {
    background: #e2e8f0;
}

.model-info-panel {
    background: #f7fafc;
    border-top: 1px solid #e2e8f0;
}

.info-content {
    padding: 1.5rem;
}

.info-content h5 {
    margin: 0 0 1rem 0;
    color: #2d3748;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.info-grid {
    display: grid;
    gap: 0.75rem;
    margin-bottom: 1rem;
}

.info-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
    border-bottom: 1px solid #edf2f7;
}

.info-item label {
    font-weight: 600;
    color: #4a5568;
}

.file-path {
    font-family: monospace;
    font-size: 0.8rem;
    color: #718096;
    max-width: 200px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.file-type-badge {
    background: #bee3f8;
    color: #2c5282;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
}

.status-badge {
    background: #c6f6d5;
    color: #276749;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
}

.info-note {
    background: #fffaf0;
    border: 1px solid #feebc8;
    border-radius: 6px;
    padding: 0.75rem;
    display: flex;
    align-items: flex-start;
    gap: 0.5rem;
    color: #744210;
}

.info-note i {
    color: #d69e2e;
    margin-top: 0.1rem;
}

.info-note small {
    font-size: 0.8rem;
    line-height: 1.4;
}

/* تحسينات للجوال */
@media (max-width: 768px) {
    .enhanced-actions {
        grid-template-columns: 1fr;
    }
    
    .cube-animation {
        width: 60px;
        height: 60px;
    }
    
    .cube-face.front { transform: translateZ(30px); }
    .cube-face.back { transform: translateZ(-30px) rotateY(180deg); }
    .cube-face.right { transform: translateX(30px) rotateY(90deg); }
    .cube-face.left { transform: translateX(-30px) rotateY(-90deg); }
    .cube-face.top { transform: translateY(-30px) rotateX(90deg); }
    .cube-face.bottom { transform: translateY(30px) rotateX(-90deg); }
}
    </style>
<!-- استبدال تحميل المكتبة -->
<!-- تحميل آمن لمكتبة model-viewer -->
<script type="module">
    // التحقق إذا كانت المكتبة محملة مسبقاً
    if (!customElements.get('model-viewer')) {
        import('https://cdn.jsdelivr.net/npm/@google/model-viewer@2.1.1/dist/model-viewer.min.js')
            .then(() => {
                console.log('✅ model-viewer loaded successfully');
                window.modelViewerLoaded = true;
                // سيتم تهيئة النماذج لاحقاً
            })
            .catch(error => {
                console.warn('⚠️ model-viewer failed to load, using fallback:', error);
                window.modelViewerLoaded = false;
                initializeFallback3DViewers();
            });
    } else {
        console.log('✅ model-viewer already loaded');
        window.modelViewerLoaded = true;
    }
</script>
 
<script>
    // التحقق من التحميل التقليدي
    if (typeof modelViewer !== 'undefined') {
        console.log('✅ model-viewer loaded (nomodule)');
        window.modelViewerLoaded = true;
    }
</script>
<style>
									/* أيقونة الإحالات */
.referral-btn {
    position: absolute;
    top: 200px;
    right: 10px;
    background: rgba(59, 130, 246, 0.9);
    border: none;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
    transition: all 0.3s ease;
    z-index: 10;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.referral-btn:hover {
    background: #3b82f6;
    transform: scale(1.1);
    box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4);
}

/* نافذة الإحالات */
.referral-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.8);
    backdrop-filter: blur(10px);
    z-index: 9999;
    align-items: center;
    justify-content: center;
    padding: 20px;
    box-sizing: border-box;
    animation: fadeIn 0.3s ease;
}

.referral-content {
	overflow: auto;
    background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
    border-radius: 20px;
    width: 100%;
    max-width: 500px;
    max-height: 90vh;
    overflow: hidden;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    border: 1px solid rgba(255, 255, 255, 0.2);
    animation: slideUp 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.referral-header {
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    color: white;
    padding: 2rem;
    position: relative;
    overflow: hidden;
    text-align: center;
}

.referral-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="none"><path d="M0,0 L100,0 L100,100 Z" fill="rgba(255,255,255,0.1)"/></svg>');
    background-size: cover;
}

.referral-header h3 {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
    position: relative;
    z-index: 1;
}

.referral-body {
    padding: 2rem;
    text-align: center;
}

.referral-icon {
    font-size: 4rem;
    color: #3b82f6;
    margin-bottom: 1rem;
}

.referral-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 1rem;
}

.referral-description {
    color: #64748b;
    font-size: 1rem;
    line-height: 1.6;
    margin-bottom: 1.5rem;
}

.referral-link-container {
    background: #f8fafc;
    border: 2px dashed #cbd5e1;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    position: relative;
}

.referral-link {
    font-size: 0.9rem;
    color: #475569;
    word-break: break-all;
    padding: 0.75rem;
    background: white;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    margin-bottom: 1rem;
}

.referral-benefits {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
    margin-bottom: 2rem;
}

.benefit-item {
    background: white;
    padding: 1rem;
    border-radius: 10px;
    border: 1px solid #e2e8f0;
    text-align: center;
}

.benefit-icon {
    font-size: 1.5rem;
    color: #10b981;
    margin-bottom: 0.5rem;
}

.benefit-text {
    font-size: 0.85rem;
    color: #475569;
    font-weight: 500;
}

.referral-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
}

.btn-copy-link {
    background: #3b82f6;
    color: white;
    border: none;
    padding: 1rem 2rem;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-copy-link:hover {
    background: #2563eb;
    transform: translateY(-2px);
}

.btn-share-whatsapp {
    background: #10b981;
    color: white;
    border: none;
    padding: 1rem 2rem;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-share-whatsapp:hover {
    background: #059669;
    transform: translateY(-2px);
}

/* تصميم متجاوب */
@media (max-width: 768px) {
    .referral-benefits {
        grid-template-columns: 1fr;
    }
    
    .referral-actions {
        flex-direction: column;
    }
    
    .referral-btn {
        top: 200px;
        right: 10px;
        width: 35px;
        height: 35px;
        font-size: 1rem;
    }
}

/* تنسيقات إضافية للزر الجديد */
.btn-stores-only {
    width: 100%;
    padding: 12px;
    background: linear-gradient(135deg, #8b5cf6, #a78bfa);
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    margin-bottom: 15px;
}

.btn-stores-only:hover {
    background: linear-gradient(135deg, #7c3aed, #8b5cf6);
    transform: translateY(-2px);
}

.btn-shuffle {
    padding: 8px 16px;
    background: #10b981;
    color: white;
    border: none;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    margin-left: 10px;
}

.btn-shuffle:hover {
    background: #059669;
    transform: translateY(-2px);
}

.btn-shuffle.active {
    background: #dc2626;
    animation: pulse 1.5s infinite;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}
/* حاوية الوسائط الرئيسية */
.product-media-container {
    position: relative;
    margin-bottom: 1rem;
}

/* المعرض الرئيسي */
.product-media-gallery {
    position: relative;
}

.main-media-wrapper {
    position: relative;
    height: 300px;
    overflow: hidden;
    border-radius: 12px;
    background: #f8f9fa;
    margin-bottom: 10px;
}

.media-item {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    transition: opacity 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.media-item.active {
    opacity: 1;
    z-index: 2;
}

/* تنسيقات الوسائط المختلفة */
.product-media-image,
.product-media-gif {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}

.product-media-video {
    width: 100%;
    height: 100%;
    object-fit: contain;
    background: #000;
}

.video-controls {
    position: absolute;
    bottom: 15px;
    left: 50%;
    transform: translateX(-50%);
    z-index: 3;
}

.video-play-btn {
    background: rgba(0,0,0,0.7);
    border: none;
    color: white;
    width: 50px;
    height: 50px;
    border-radius: 50%;
    cursor: pointer;
    transition: all 0.3s ease;
}

.video-play-btn:hover {
    background: rgba(0,0,0,0.9);
    transform: scale(1.1);
}

/* نماذج 3D */
.product-3d-model {
    width: 100%;
    height: 100%;
    position: relative;
}

.model-controls {
    position: absolute;
    bottom: 10px;
    right: 10px;
    display: flex;
    gap: 5px;
    z-index: 10;
}

.model-controls button {
    background: rgba(0,0,0,0.7);
    border: none;
    color: white;
    width: 35px;
    height: 35px;
    border-radius: 5px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}

.model-controls button:hover {
    background: rgba(0,0,0,0.9);
}

/* معرض المصغرات */
.media-thumbnails {
    display: flex;
    gap: 8px;
    overflow-x: auto;
    padding: 10px 0;
}

.thumbnail-item {
    width: 60px;
    height: 60px;
    border-radius: 8px;
    overflow: hidden;
    cursor: pointer;
    border: 2px solid transparent;
    transition: all 0.3s ease;
    flex-shrink: 0;
    position: relative;
}

.thumbnail-item.active {
    border-color: #3b82f6;
}

.thumbnail-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.video-thumbnail,
.model-thumbnail {
    position: relative;
    width: 100%;
    height: 100%;
}

.video-thumbnail i,
.model-thumbnail i {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    color: white;
    background: rgba(0,0,0,0.6);
    border-radius: 50%;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
}

/* مؤشر نوع الوسائط */
.media-type-badge {
    position: absolute;
    top: 10px;
    left: 10px;
    background: rgba(0,0,0,0.7);
    color: white;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.75rem;
    z-index: 5;
}

/* تنسيقات للشاشات الصغيرة */
@media (max-width: 768px) {
    .main-media-wrapper {
        height: 250px;
    }
    
    .thumbnail-item {
        width: 50px;
        height: 50px;
    }
    
    .model-controls button {
        width: 30px;
        height: 30px;
    }
}
</style>
<style>
/* تحسينات للنماذج ثلاثية الأبعاد */
product-3d-model {
    position: relative;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

model-viewer {
    --poster-color: #ffffff;
    --progress-bar-color: #3b82f6;
    --progress-bar-height: 3px;
}

model-viewer::part(default-progress-bar) {
    height: 3px;
    background-color: #3b82f6;
}

model-viewer::part(poster) {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.model-controls {
    position: absolute;
    bottom: 15px;
    left: 15px;
    display: flex;
    gap: 8px;
    z-index: 10;
}

.model-controls button {
    background: rgba(0,0,0,0.7);
    border: none;
    color: white;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
}

.model-controls button:hover {
    background: rgba(0,0,0,0.9);
    transform: scale(1.1);
}

/* تحميل سلسل */
@keyframes model-loading {
    0% { opacity: 0; transform: scale(0.95); }
    100% { opacity: 1; transform: scale(1); }
}

model-viewer {
    animation: model-loading 0.5s ease-out;
}
/* زر المقايضة */
.btn-barter {
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: white;
    border: none;
    padding: 0.75rem 1rem;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.3s ease;
    margin-top: 0.5rem;
    position: absolute;
	z-index: 10;
}

.btn-barter:hover {
    background: linear-gradient(135deg, #d97706, #b45309);
    transform: translateY(-2px);
}

/* نافذة المقايضة */
.barter-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.8);
    backdrop-filter: blur(10px);
    z-index: 9999;
    align-items: center;
    justify-content: center;
    padding: 20px;
    animation: fadeIn 0.3s ease;
}

.barter-content {
    background: white;
    border-radius: 20px;
    width: 100%;
    max-width: 900px;
    max-height: 90vh;
    overflow: hidden;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    animation: slideUp 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.barter-header {
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: white;
    padding: 1.5rem 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.barter-header h3 {
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.close-barter {
    background: none;
    border: none;
    color: white;
    font-size: 1.5rem;
    cursor: pointer;
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.barter-body {
    padding: 2rem;
    display: grid;
    grid-template-columns: 1fr auto 1fr;
    gap: 2rem;
    align-items: start;
}

.barter-product {
    text-align: center;
    padding: 1rem;
    border: 2px dashed #e5e7eb;
    border-radius: 12px;
    background: #fafafa;
}

.barter-product img {
    max-width: 100%;
    height: 150px;
    object-fit: contain;
    margin-bottom: 1rem;
}

.barter-arrow {
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    color: #f59e0b;
}

.my-products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 1rem;
    max-height: 400px;
    overflow-y: auto;
    padding: 1rem;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
}

.barter-product-card {
    border: 2px solid transparent;
    border-radius: 8px;
    padding: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
    text-align: center;
    background: white;
}

.barter-product-card:hover {
    border-color: #f59e0b;
    transform: translateY(-2px);
}

.barter-product-card.selected {
    border-color: #f59e0b;
    background: #fef3c7;
}

.barter-product-card img {
    width: 80px;
    height: 80px;
    object-fit: contain;
    margin-bottom: 0.5rem;
}

.barter-actions {
    padding: 1.5rem 2rem;
    border-top: 1px solid #e5e7eb;
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
}

.btn-send-offer {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    border: none;
    padding: 0.75rem 2rem;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-send-offer:hover:not(:disabled) {
    background: linear-gradient(135deg, #059669, #047857);
    transform: translateY(-2px);
}

.btn-send-offer:disabled {
    background: #9ca3af;
    cursor: not-allowed;
    transform: none;
}

.barter-message {
    margin-top: 1rem;
}

.barter-message textarea {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    resize: vertical;
    min-height: 80px;
    font-family: inherit;
}

.no-products {
    text-align: center;
    color: #6b7280;
    padding: 2rem;
}

/* تحسينات للجوال */
@media (max-width: 768px) {
    .barter-body {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .barter-arrow {
        transform: rotate(90deg);
        padding: 1rem 0;
    }
    
    .my-products-grid {
        grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    }
}
</style>
<!-- أضف هذا CSS في قسم الأنماط -->
<!-- أضف هذا CSS في قسم الأنماط -->
<style>
    /* زر التحكم في الشريط الجانبي */
    .sidebar-toggle {
        position: fixed;
        top: 80px;
        left: 20px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        border-radius: 50%;
        width: 50px;
        height: 50px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        z-index: 1000;
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        transition: all 0.3s ease;
    }
    
    .sidebar-toggle:hover {
        transform: scale(1.1);
        box-shadow: 0 6px 16px rgba(0,0,0,0.3);
    }
    
    /* الحاوية الرئيسية */
    .content-wrapper {
        display: flex;
        gap: 2rem;
        transition: all 0.3s ease;
    }
    
    /* الشريط الجانبي */
    .sidebar {
        width: 300px;
        flex-shrink: 0;
        transition: all 0.3s ease;
    }
    
    /* قسم المنتجات */
    .products-section {
        flex: 1;
        transition: all 0.3s ease;
        min-width: 0; /* يسمح بالانكماش */
    }
    
    /* حالة الشريط الجانبي المخفي */
    .sidebar-hidden .sidebar {
        width: 0;
        opacity: 0;
        visibility: hidden;
        margin-right: 0;
    }
    
    .sidebar-hidden .products-section {
        width: 100%;
        margin-left: 0;
        flex: 1 0 100%; /* يأخذ المساحة الكاملة */
    }
    
    .sidebar-hidden .content-wrapper {
        gap: 0;
    }
    
    /* تحسينات للشاشات الصغيرة */
    @media (max-width: 768px) {
        .sidebar-toggle {
            top: 70px;
            left: 10px;
            width: 40px;
            height: 40px;
            font-size: 1rem;
        }
        
        .sidebar {
            width: 280px;
        }
        
        .content-wrapper {
            gap: 1rem;
        }
    }
    
    @media (max-width: 480px) {
        .sidebar {
            width: 100%;
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            z-index: 999;
            background: white;
            overflow-y: auto;
            transform: translateX(-100%);
            transition: transform 0.3s ease;
        }
        
        .sidebar-mobile-open .sidebar {
            transform: translateX(0);
        }
        
        .sidebar-mobile-open::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 998;
        }
    }
</style>
<!-- أضف هذا CSS في قسم الأنماط -->
<style>
    /* نظام الألوان حسب الفئات */
    .product-card.category-1 { background: linear-gradient(135deg, #fef3e6 0%, #fef9f3 100%); }
    .product-card.category-2 { background: linear-gradient(135deg, #e6f3ff 0%, #f3f9ff 100%); }
    .product-card.category-3 { background: linear-gradient(135deg, #e6f7ed 0%, #f3fcf7 100%); }
    .product-card.category-4 { background: linear-gradient(135deg, #f2e6ff 0%, #f9f3ff 100%); }
    .product-card.category-5 { background: linear-gradient(135deg, #fff2e6 0%, #fff9f3 100%); }
    .product-card.category-6 { background: linear-gradient(135deg, #ffe6e6 0%, #fff3f3 100%); }
    .product-card.category-7 { background: linear-gradient(135deg, #fffae6 0%, #fffdf3 100%); }
    .product-card.category-8 { background: linear-gradient(135deg, #e6fff9 0%, #f3fffd 100%); }
    
    /* تأثير التضيء الخفيف */
    @keyframes gentleGlow {
        0%, 100% { 
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            transform: translateY(0);
        }
        50% { 
            box-shadow: 0 4px 20px rgba(0,0,0,0.12);
            transform: translateY(-2px);
        }
    }
    
    .product-card {
        animation: gentleGlow 4s ease-in-out infinite;
        border: 1px solid rgba(0,0,0,0.05);
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }
    
    /* تأخيرات مختلفة لكل منتج */
    .product-card:nth-child(8n+1) { animation-delay: 0s; }
    .product-card:nth-child(8n+2) { animation-delay: 0.5s; }
    .product-card:nth-child(8n+3) { animation-delay: 1s; }
    .product-card:nth-child(8n+4) { animation-delay: 1.5s; }
    .product-card:nth-child(8n+5) { animation-delay: 2s; }
    .product-card:nth-child(8n+6) { animation-delay: 2.5s; }
    .product-card:nth-child(8n+7) { animation-delay: 3s; }
    .product-card:nth-child(8n+8) { animation-delay: 3.5s; }
    
    /* تأثير التوهج الإضافي */
    .product-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, 
            transparent, 
            rgba(255,255,255,0.4), 
            transparent);
        transition: left 0.8s ease;
        z-index: 1;
    }
    
    .product-card:hover::before {
        left: 100%;
    }
    
    /* تحسينات للعناصر الداخلية */
    .product-card .product-info {
        position: relative;
        z-index: 2;
    }
    
    /* تأثيرات إضافية عند التمرير */
    .products-grid {
        perspective: 1000px;
    }
    
    .product-card {
        transform-style: preserve-3d;
    }
</style>
<!-- أضف هذا CSS في قسم الأنماط -->
<style>
    /* نظام إظهار وإخفاء المصغرات */
    .media-thumbnails {
        max-height: 0;
        opacity: 0;
        overflow: hidden;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        transform: translateY(10px);
    }
    
    .media-thumbnails.visible {
        max-height: 80px;
        opacity: 1;
        transform: translateY(0);
        margin-top: 10px;
    }
    
    /* مؤشر التفاعل */
    .hover-indicator {
        position: absolute;
        bottom: 10px;
        left: 50%;
        transform: translateX(-50%);
        background: rgba(0,0,0,0.7);
        color: white;
        padding: 4px 12px;
        border-radius: 15px;
        font-size: 0.75rem;
        opacity: 0;
        transition: all 0.3s ease;
        z-index: 20;
        pointer-events: none;
    }
    
    .hover-indicator.visible {
        opacity: 1;
        bottom: 15px;
    }
    
    /* تحسينات للأداء */
    .main-media-wrapper {
        position: relative;
    }
    
    /* تأثيرات لطيفة للمصغرات */
    .media-thumbnails {
        background: linear-gradient(to top, rgba(255,255,255,0.9), transparent);
        padding: 5px 0;
        backdrop-filter: blur(5px);
    }
</style>
<!-- تعديل كود PHP لعرض المنتجات -->
<?php
// دالة للحصول على لون الفئة
function getCategoryColor($categoryId) {
    $colors = [
        1 => 'category-1',
        2 => 'category-2', 
        3 => 'category-3',
        4 => 'category-4',
        5 => 'category-5',
        6 => 'category-6',
        7 => 'category-7',
        8 => 'category-8'
    ];
    
    // إذا كانت الفئة أكبر من 8، نستخدم نظام دوري
    $colorIndex = ($categoryId - 1) % 8 + 1;
    return $colors[$colorIndex] ?? 'category-1';
}
?>
<!-- Main Content -->
<main class="main-content" id="products">
 
<!-- بانر الجمعة البيضاء -->
<?php if (isBlackFridayPeriod()): ?>
<!-- عداد التنازل للجمعة البيضاء --> 
    <?php $remainingTime = getRemainingBlackFridayTime(); ?>
    <div class="black-friday-countdown">
        <div class="countdown-title">
            🎉 عروض الجمعة البيضاء تنتهي خلال:
        </div>
    <div class="black-friday-banner">
        <div class="banner-content">
            <div class="banner-title">
                🎉 الجمعة البيضاء 🎉
                <span style="color: gold;">خصومات تصل إلى <?= getBlackFridaySettings()['discount_percentage'] ?>%</span>
            </div>
            <div class="banner-subtitle">
                🚀 عروض محدودة! استفد من أفضل العروض قبل انتهاء الوقت
                <?php $remainingTime = getRemainingBlackFridayTime(); ?>
                <?php if ($remainingTime): ?>
                    - ⏳ متبقي: <?= $remainingTime['days'] ?> يوم و <?= $remainingTime['hours'] ?> ساعة
                <?php endif; ?>
            </div>
        </div>
    </div> 
        <div class="countdown-timer1" id="black-friday-countdown">
            <?php if ($remainingTime): ?>
                <div class="countdown-unit">
                    <span id="countdown-days"><?= $remainingTime['days'] ?></span>
                    <div class="countdown-label">أيام</div>
                </div>
                <div class="countdown-unit">
                    <span id="countdown-hours"><?= $remainingTime['hours'] ?></span>
                    <div class="countdown-label">ساعات</div>
                </div>
                <div class="countdown-unit">
                    <span id="countdown-minutes"><?= $remainingTime['minutes'] ?></span>
                    <div class="countdown-label">دقائق</div>
                </div>
                <div class="countdown-unit">
                    <span id="countdown-seconds"><?= $remainingTime['seconds'] ?></span>
                    <div class="countdown-label">ثواني</div>
                </div>
            <?php else: ?>
                <div>انتهت العروض</div>
            <?php endif; ?>
        </div>
    </div>

<?php endif; ?>
    <div class="container">
        <div class="content-wrapper">
            <!-- Sidebar -->
            <aside class="sidebar">
                <!-- Categories -->
                <div class="widget">

                    <h3 class="widget-title">الفئات</h3>
                    <ul class="category-list">
                        <li>
                            <a href="index.php" class="<?= !$categoryId ? 'active' : '' ?>">
                                جميع المنتجات
                            </a>
                        </li>
                        <?php foreach ($categories as $cat): ?>
                            <li>
                                <a href="index.php?category=<?= $cat['id'] ?>" 
                                   class="<?= $categoryId == $cat['id'] ? 'active' : '' ?>">
                                    <?= htmlspecialchars($cat['name']) ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
 
                <!-- زر عرض المتاجر فقط -->
                <div class="widget">
                    <h3 class="widget-title">عرض المتاجر</h3>
                    <button id="showStoresOnly" class="btn-stores-only">
                        <i class="fas fa-store"></i> عرض المتاجر فقط
                    </button>
                </div>

                <!-- Top Viewed -->
                <?php if (!empty($topViewed)): ?>
                <div class="widget">
                    <h3 class="widget-title">الأكثر مشاهدة</h3>
                    <ul class="product-list-mini">
                        <?php foreach ($topViewed as $product): ?>
                            <li>
                                <a href="product.php?id=<?= $product['id'] ?>" class="mini-product">
                                    <img src="<?= htmlspecialchars($product['main_image'] ?: 'assets/images/placeholder.jpg') ?>" 
                                         alt="<?= htmlspecialchars($product['title']) ?>">
                                    <div class="mini-info">
                                        <h4><?= htmlspecialchars($product['title']) ?></h4>
                                        <span class="price"><?= formatPrice($product['final_price']) ?></span>
                                    </div>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <!-- Top Ordered -->
                <?php if (!empty($topOrdered)): ?>
                <div class="widget">
                    <h3 class="widget-title">الأكثر طلباً</h3>
                    <ul class="product-list-mini">
                        <?php foreach ($topOrdered as $product): ?>
                            <li>
                                <a href="product.php?id=<?= $product['id'] ?>" class="mini-product">
                                    <img src="<?= htmlspecialchars($product['main_image'] ?: 'assets/images/placeholder.jpg') ?>" 
                                         alt="<?= htmlspecialchars($product['title']) ?>">
                                    <div class="mini-info">
                                        <h4><?= htmlspecialchars($product['title']) ?></h4>
                                        <span class="price"><?= formatPrice($product['final_price']) ?></span>
                                    </div>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
            </aside>

            <!-- Products Section -->
            <section class="products-section">
                <div class="section-toolbar">
                    <div class="toolbar-left">
                        <h2>
                            <?php if ($search): ?>
                                نتائج البحث عن: "<?= htmlspecialchars($search) ?>"
                            <?php elseif ($categoryId): ?>
                                <?php 
                                $currentCat = array_filter($categories, fn($c) => $c['id'] == $categoryId);
                                echo htmlspecialchars(reset($currentCat)['name'] ?? 'المنتجات');
                                ?>
                            <?php else: ?>
                                جميع المنتجات
                            <?php endif; ?>
                        </h2>
                        <span class="results-count">(<?= count($products) ?> منتج)</span>
                    </div>
                    
                    <div class="toolbar-right">
                        <label for="sort">ترتيب حسب:</label>
                        <select id="sort" name="sort" onchange="window.location.href = updateQueryString('sort', this.value)">
                            <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>الأحدث</option>
                            <option value="price_low" <?= $sort === 'price_low' ? 'selected' : '' ?>>السعر: من الأقل للأعلى</option>
                            <option value="price_high" <?= $sort === 'price_high' ? 'selected' : '' ?>>السعر: من الأعلى للأقل</option>
                            <option value="popular" <?= $sort === 'popular' ? 'selected' : '' ?>>الأكثر شعبية</option>
                            <option value="rating" <?= $sort === 'rating' ? 'selected' : '' ?>>الأعلى تقييماً</option>
                        </select>
                        
                        <!-- زر الخلط التلقائي -->
                        <button id="autoShuffleBtn" class="btn-shuffle" title="خلط المنتجات تلقائياً">
                            <i class="fas fa-random"></i> خلط المنتجات
                        </button> 
                    <button id="showStoresOnly1" class="btn-shuffle">
                        <i class="fas fa-store"></i>  المتاجر فقط
                    </button> 
                    </div>
                </div>

                <?php if (empty($products)): ?>
                    <div class="no-products">
                        <i class="fas fa-box-open"></i>
                        <p>لا توجد منتجات متاحة حالياً</p>
                    </div>
                <?php else: ?>
                    <?php
                    // دالة جديدة لجلب منتج واحد عشوائي من كل متجر مستخدم
                    function getSingleProductPerCustomerStore($products) {
                        $customerStores = [];
                        $filteredProducts = [];
                        
                        foreach ($products as $product) {
                            if ($product['store_type'] === 'customer' && !empty($product['created_by'])) {
                                $storeOwnerId = $product['created_by'];
                                
                                // إذا كان هذا المتجر لم يظهر بعد، نأخذ منتج عشوائي منه
                                if (!isset($customerStores[$storeOwnerId])) {
                                    $customerStores[$storeOwnerId] = $product;
                                    $filteredProducts[] = $product;
                                }
                            } else {
                                // المنتجات الرئيسية نعرضها كلها
                                $filteredProducts[] = $product;
                            }
                        }
                        
                        return $filteredProducts;
                    }

                    // تصفية المنتجات لعرض منتج واحد فقط من كل متجر مستخدم
                    $filteredProducts = getSingleProductPerCustomerStore($products);
                    ?>
                    
                    <?php if (empty($filteredProducts)): ?>
                        <div class="no-products">
                            <i class="fas fa-box-open"></i>
                            <p>لا توجد منتجات متاحة حالياً</p>
                        </div>
                    <?php else: ?>
                        <div class="products-grid" id="productsGrid">
                            <?php foreach ($filteredProducts as $product): ?>
							 <?php
    $categoryColorClass = getCategoryColor($product['category_id'] ?? 1);
    ?>
                                <div class="product-card <?= $product['store_type'] === 'customer' ? 'customer-store-product' : '' ?>" 
                                     style="<?= $product['store_type'] === 'customer' ? 'border: 3px solid #8b5cf6; background: linear-gradient(135deg, #faf5ff, #f3e8ff);' : '' ?>">
                                    <?php 
                                    $customer_id = $_SESSION['customer_id'] ?? 0;
                                    $isInWishlist = isInWishlist($customer_id, $product['id']);
                                    
                                    // الحصول على العد التنازلي النشط
                                    $countdown = getActiveCountdown($product['id']);
                                    
                                    // التحقق من المزايدة النشطة
                                    $isAuctionActive = isAuctionActive($product);
                                    
                                    // التحقق من عرض اشتري 2 واحصل على 1
                                    $hasBuy2Get1 = hasBuyTwoGetOneOffer($product['id']);
                                    
                                    // التحقق إذا كان المنتج من متجر مستخدم
                                    $isCustomerStore = $product['store_type'] === 'customer';
                                    $storeOwnerName = '';
                                    $storeOwnerId = null;
                                    $additionalProductsCount = 0;
                                    
                                    if ($isCustomerStore && !empty($product['created_by'])) {
                                        $storeOwnerName = getCustomerName($product['created_by']);
                                        $storeOwnerId = $product['created_by'];
                                        
                                        // جلب عدد المنتجات الإضافية لهذا المتجر
                                        $additionalProductsCount = getCustomerStoreProductsCount($storeOwnerId) - 1;
                                    }
                                    ?>
									  <!-- أيقونة الكاشباك -->
    <?php if ($product['has_cashback']): ?>
    <div class="cashback-badge" 
         onclick="openCashbackModal(<?= $product['id'] ?>, '<?= addslashes($product['title']) ?>', <?= $product['cashback']['amount'] ?>, <?= $product['cashback']['percentage'] ?>, '<?= $product['cashback']['formatted_amount'] ?>')"
         title="احصل على كاشباك عند الشراء">
        💰 <?= $product['cashback']['percentage'] ?>%
    </div>
    <?php endif; ?>
	<!-- أيقونة QR Code للتخفيض -->
<?php if (isset($_SESSION['customer_id']) && $product['store_type'] === 'customer'): ?>
<button class="qr-discount-btn" 
        onclick="openQRModal(<?= $product['id'] ?>, <?= $product['created_by'] ?>, '<?= addslashes($product['title']) ?>')"
        title="احصل على كود QR للتخفيض عند زيارة المتجر">
    <i class="fas fa-qrcode"></i>
</button>
<?php endif; ?>

									    <?php if (isset($product['is_black_friday']) && $product['is_black_friday']): ?>
        <div class="black-friday-badge">
            <i class="fas fa-bolt"></i>
            الجمعة البيضاء
        </div>
    <?php endif; ?>
									
                                    <!-- أيقونة الإحالات -->
<?php if (isset($_SESSION['customer_id']) && getSetting('referral_system_enabled', '1') == '1'): ?>
<button class="referral-btn" 
        onclick="openReferralModal(<?= $product['id'] ?>, '<?= addslashes($product['title']) ?>')"
        title="انشر المنتج واكسب نقاط">
    <i class="fas fa-share-alt"></i>
</button>
<?php endif; ?>
                                    <!-- شارة متجر المستخدم -->
                                    <?php if ($isCustomerStore): ?>
                                        <div class="customer-store-badge" style="
                                            position: absolute;
                                            top: 40%;
                                            left: 10px;
                                            background: linear-gradient(135deg, #8b5cf6, #a78bfa);
                                            color: white;
                                            padding: 0.5rem 0.75rem;
                                            border-radius: 20px;
                                            font-weight: 600;
                                            font-size: 0.75rem;
                                            z-index: 10;
                                            box-shadow: 0 2px 4px rgba(139, 92, 246, 0.3);
                                        ">
                                            <i class="fas fa-store"></i> متجر شخصي
                                        </div>
<!-- في قسم عرض المنتجات -->
<!-- في قسم عرض المنتجات -->
<?= displaySmartOffersBadges($product) ?>
<?php if ($isCustomerStore && isSmartGuidanceEnabled($product['created_by'])): ?>
    <?php
    $smartOffers = getActiveSmartOffers($product['id']);
    if (!empty($smartOffers)):
    ?>
        <div class="smart-offers-badges">
            <?php foreach ($smartOffers as $offer): ?>
                <span class="smart-offer-badge offer-<?= $offer['type'] ?>">
                    <?= getOfferTypeLabel($offer['type']) ?>
                </span>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>
                                        <!-- زر عرض متجر المستخدم -->
                                        <!-- زر عرض متجر المستخدم -->
<?php 
// التحقق من وجود متجر فعلي للمستخدم
$hasStore = hasCustomerStore($storeOwnerId);
$storeProductsCount = getCustomerStoreProductsCount($storeOwnerId);
?>
<?php if ($hasStore && $storeProductsCount > 1): ?>
    <button class="view-store-btn" 
            onclick="openCustomerStorePopup(<?= $storeOwnerId ?>, '<?= addslashes($storeOwnerName) ?>')"
            title="عرض جميع منتجات متجر <?= htmlspecialchars($storeOwnerName) ?>">
        <i class="fas fa-store"></i>
		<i class="fas fa-truck"></i>
<i class="fas fa-undo"></i>
<i class="fas fa-shield-alt"></i>
          (+<?= $storeProductsCount - 1 ?>)
    </button>
<?php elseif ($hasStore): ?>
    <button class="view-store-btn" 
            onclick="openCustomerStorePopup(<?= $storeOwnerId ?>, '<?= addslashes($storeOwnerName) ?>')"
            title="عرض متجر <?= htmlspecialchars($storeOwnerName) ?>">
        <i class="fas fa-store"></i>
         
    </button>
<?php endif; ?>
                                    <?php endif; ?>
<!-- زر المقايضة -->
<?php if (isset($_SESSION['customer_id']) && $product['created_by'] != $_SESSION['customer_id']): ?>
<button class="btn btn-barter" onclick="openBarterModal(<?= $product['id'] ?>, <?= $product['created_by'] ?>)">
    <i class="fas fa-exchange-alt"></i> 
</button>
<?php endif; ?>
                                    <!-- أيقونة المفضلة -->
                                    <button class="wishlist-btn <?= $isInWishlist ? 'active' : '' ?>" 
                                            onclick="toggleWishlist(<?= $product['id'] ?>, this)">
                                        <i class="<?= $isInWishlist ? 'fas' : 'far' ?> fa-heart"></i>
                                    </button>
									                                    <!-- أيقونة المفضلة -->
                                    <button class="view-store-btn" style="TOP: 50.6%;RIGHT: 50.6%;"> 
                                        <i class="fa-solid fa-hand-holding-droplet"></i>
                                    </button>
                                    <!-- أيقونة حالة المنتج -->
                                    <?php 
                                    $condition = getProductCondition($product['product_condition']);
                                    if ($product['product_condition'] != 'new'): ?>
                                    <div class="product-condition-badge" style="
                                        position: absolute;
                                        top: 10px;
                                        right: 10px;
                                        background: <?= $condition['color'] ?>;
                                        color: white;
                                        padding: 0.25rem 0.5rem;
                                        border-radius: 15px;
                                        font-size: 0.75rem;
                                        z-index: 10;
                                    ">
                                        <i class="<?= $condition['icon'] ?>"></i>
                                        <?= $condition['label'] ?>
                                    </div>
                                    <?php endif; ?>

                                    <!-- أيقونة العرض الخاص -->
                                    <?php 
                                    $specialOffer = getSpecialOfferIcon($product['special_offer_type'], $product['special_offer_value']);
                                    if ($specialOffer): ?>
                                    <div class="special-offer-badge" 
                                         style="position: absolute; top: 45px; right: 10px; background: <?= $specialOffer['color'] ?>; color: white; padding: 0.25rem 0.5rem; border-radius: 15px; font-size: 0.75rem; z-index: 10; cursor: pointer;"
                                         onclick="openScratchCard(<?= $product['id'] ?>)"
                                         title="<?= $specialOffer['text'] ?>">
                                        <i class="<?= $specialOffer['icon'] ?>"></i>
                                        خربش واكسب
                                    </div>
                                    <?php endif; ?>

                                    <!-- زر التفاوض -->
                                    <?php if (isNegotiationEnabled()): ?>
                                    <button class="negotiation-btn <?= hasActiveNegotiation($_SESSION['customer_id'] ?? 0, $product['id']) ? 'negotiated' : '' ?>" 
                                            id="negotiate-btn-<?= $product['id'] ?>"
                                            onclick="openNegotiation(<?= $product['id'] ?>, <?= $product['final_price'] ?>)"
                                            title="تفاوض على السعر">
                                        <i class="fas fa-handshake"></i>
                                    </button>
                                    <?php endif; ?>

                                    <!-- العد التنازلي للسعر -->
                                    <?php if ($countdown): ?>
                                    <div class="countdown-timer" style="
                                        position: absolute;
                                        top: 100px;
                                        left: 10px;
                                        background: #dc3545;
                                        color: white;
                                        padding: 0.5rem;
                                        border-radius: 5px;
                                        font-size: 0.8rem;
                                        z-index: 10;
                                        text-align: center;
                                    " id="countdown-<?= $product['id'] ?>">
                                        <div><i class="fa-solid fa-bomb"></i></div>
                                        <div id="timer-<?= $product['id'] ?>" class="countdown-time">
                                            <?= getAuctionTimeLeft($countdown['countdown_end']) ?>
                                        </div>
                                        <div class="new-price"><?= formatPrice($countdown['new_price']) ?></div>
                                    </div>
                                    <?php endif; ?>

                                    <!-- أيقونة المزاد -->
                                    <?php if ($isAuctionActive): ?>
                                    <button class="auction-icon" onclick="openAuctionModal(<?= $product['id'] ?>)" title="عرض المزاد والمشاركين">
                                        <i class="fas fa-gavel"></i>
                                    </button>
                                    <?php endif; ?>

                                    <?php if ($product['discount_percentage'] > 0): ?>
                                        <span class="badge-discount">-<?= $product['discount_percentage'] ?>%</span>
                                    <?php endif; ?>
                                    
                                    <?php if ($product['stock'] < 5 && $product['stock'] > 0): ?>
                                        <span class="badge-stock">متبقي <?= $product['stock'] ?> فقط</span>
                                    <?php elseif ($product['stock'] == 0): ?>
                                        <span class="badge-out">نفذت الكمية</span>
                                    <?php endif; ?>
                                    
                                    <!-- قسم الوسائط المتعددة -->
<!-- قسم الوسائط المتعددة المُحسّن -->
<div class="product-media-container"  id="productMedia-<?= $product['id'] ?>">
    <?php
    // جلب الوسائط مع التحقق المُحسّن
    $productMedia = getProductMedia($product['id']);
    
    // تسجيل للتصحيح
    error_log("Product {$product['id']} - Media Count: " . count($productMedia));
    
    if (!empty($productMedia)) {
        foreach ($productMedia as $index => $media) {
            error_log("Media {$index}: Type = {$media['media_type']}, URL = {$media['media_url']}");
        }
    }
    ?>
    
    <?php if (!empty($productMedia)): ?>
        <!-- المعرض الرئيسي -->
        <div class="product-media-gallery">
            <div class="main-media-wrapper" 
                         id="mediaWrapper-<?= $product['id'] ?>"
                         onmouseenter="showThumbnails(<?= $product['id'] ?>)"
                         onmouseleave="hideThumbnails(<?= $product['id'] ?>)">
                        <!-- مؤشر الاقتراب -->
                        <div class="hover-trigger" id="hoverTrigger-<?= $product['id'] ?>">
                            <i class="fas fa-images"></i>
                            <span>معرض الوسائط</span>
                        </div>
				<?php foreach ($productMedia as $index => $media): ?>
                    <div class="media-item <?= $index === 0 ? 'active' : '' ?>" 
                         data-type="<?= htmlspecialchars($media['media_type']) ?>" 
                         data-src="<?= htmlspecialchars($media['media_url']) ?>">
                         
                           <?php if ($media['media_type'] === '3d_model'): ?>
    <!-- ✅ عرض النموذج ثلاثي الأبعاد المُحسّن -->
    <div class="product-3d-model" id="model-<?= $product['id'] ?>-<?= $index ?>">
        <model-viewer 
            src="<?= htmlspecialchars($media['media_url']) ?>"
            alt="<?= htmlspecialchars($product['title']) ?> - 3D Model"
            poster="<?= htmlspecialchars($media['thumbnail_url'] ?: 'assets/images/3d-loading.jpg') ?>"
            shadow-intensity="1"
            camera-controls
            touch-action="pan-y"
            auto-rotate
            auto-rotate-delay="0"
            rotation-per-second="30deg"
            environment-image="neutral"
            exposure="1"
            ar
            ar-modes="webxr scene-viewer quick-look"
            style="width: 100%; height: 300px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
            
            <!-- شاشة التحميل -->
            <div slot="progress-bar" class="progress-bar" style="
                position: absolute;
                bottom: 10px;
                left: 50%;
                transform: translateX(-50%);
                width: 80%;
                height: 4px;
                background: rgba(255,255,255,0.3);
                border-radius: 2px;
                overflow: hidden;">
                <div class="update-bar" style="
                    background: #fff;
                    width: 0%;
                    height: 100%;
                    transition: width 0.3s;"></div>
            </div>
            
            <!-- رسالة الخطأ -->
            <div slot="poster" style="
                display: flex;
                align-items: center;
                justify-content: center;
                height: 100%;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                text-align: center;
                padding: 20px;">
                <div>
                    <i class="fas fa-cube fa-3x" style="margin-bottom: 15px; animation: spin 2s linear infinite;"></i>
                    <p style="font-size: 1.1rem; font-weight: 600;">جاري تحميل النموذج ثلاثي الأبعاد...</p>
                    <small style="opacity: 0.8;">استخدم الماوس للتحكم بالنموذج</small>
                </div>
            </div>
            
            <!-- أيقونة AR -->
            <button slot="ar-button" style="
                background: white;
                border: none;
                border-radius: 50%;
                width: 50px;
                height: 50px;
                position: absolute;
                bottom: 20px;
                right: 20px;
                cursor: pointer;
                box-shadow: 0 4px 12px rgba(0,0,0,0.2);">
                <i class="fas fa-mobile-alt" style="color: #667eea;"></i>
            </button>
        </model-viewer>
        
        <!-- أزرار التحكم المُحسّنة -->
        <div class="model-controls" style="
            position: absolute;
            bottom: 15px;
            left: 15px;
            display: flex;
            gap: 8px;
            z-index: 10;">
            
            <button onclick="rotateModel3D(<?= $product['id'] ?>, <?= $index ?>)" 
                    title="تدوير النموذج"
                    style="background: rgba(0,0,0,0.7); border: none; color: white; width: 40px; height: 40px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.3s;">
                <i class="fas fa-redo"></i>
            </button>
            
            <button onclick="resetModel3D(<?= $product['id'] ?>, <?= $index ?>)" 
                    title="إعادة تعيين"
                    style="background: rgba(0,0,0,0.7); border: none; color: white; width: 40px; height: 40px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.3s;">
                <i class="fas fa-home"></i>
            </button>
            
            <button onclick="toggleAutoRotate(<?= $product['id'] ?>, <?= $index ?>)" 
                    title="إيقاف/تشغيل الدوران التلقائي"
                    id="rotate-btn-<?= $product['id'] ?>-<?= $index ?>"
                    style="background: rgba(0,0,0,0.7); border: none; color: white; width: 40px; height: 40px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.3s;">
                <i class="fas fa-pause"></i>
            </button>
            
            <button onclick="downloadModel('<?= htmlspecialchars($media['media_url']) ?>', '<?= htmlspecialchars($product['title']) ?>')" 
                    title="تحميل النموذج"
                    style="background: rgba(0,0,0,0.7); border: none; color: white; width: 40px; height: 40px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.3s;">
                <i class="fas fa-download"></i>
            </button>
        </div>
        
        <!-- معلومات النموذج -->
        <div class="model-info-badge" style="
            position: absolute;
            top: 10px;
            left: 10px;
            background: rgba(0,0,0,0.7);
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 0.75rem;
            z-index: 10;">
            <i class="fas fa-cube"></i> 3D Model
        </div>
    </div>
    
    <style>
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        .model-controls button:hover {
            background: rgba(0,0,0,0.9) !important;
            transform: scale(1.1);
        }
    </style>

 
                        <?php elseif ($media['media_type'] === 'video'): ?>
                            <!-- عرض الفيديو --> 
                                 <!-- عرض الفيديو مع تشغيل تلقائي وإعادة تشغيل مرتين فقط -->
                                 <video class="product-media-video"
                                        autoplay
                                        muted 
                                        playsinline
                                        loop-data="200">
                                     <source src="<?= htmlspecialchars($media['media_url']) ?>" type="video/mp4">
                                     المتصفح لا يدعم تشغيل الفيديو.
                                 </video> 
                        
                        <?php else: ?>
                            <!-- عرض الصور -->
                            <img src="<?= htmlspecialchars($media['media_url']) ?>" 
                                 alt="<?= htmlspecialchars($product['title']) ?>"
                                 class="product-media-image">
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- المصغرات -->
                    <?php if (count($productMedia) > 1): ?>
                    <div class="media-thumbnails-container" id="thumbnailsContainer-<?= $product['id'] ?>">
                        <div class="media-thumbnails" id="thumbnails-<?= $product['id'] ?>"
                             onmouseenter="keepThumbnailsVisible(<?= $product['id'] ?>)"
                             onmouseleave="checkThumbnailsHide(<?= $product['id'] ?>)">
                            <?php foreach ($productMedia as $index => $media): ?>
                                <div class="thumbnail-item <?= $index === 0 ? 'active' : '' ?>" 
                                     onclick="switchToMedia(<?= $index ?>, <?= $product['id'] ?>)"
                                     onmouseenter="highlightThumbnail(this, <?= $product['id'] ?>)">
                                    <?php if ($media['media_type'] === '3d_model'): ?>
                                        <div class="model-thumbnail">
                                            <img src="<?= htmlspecialchars($media['thumbnail_url'] ?: 'assets/images/3d-thumb.jpg') ?>" alt="3D">
                                            <i class="fas fa-cube"></i>
                                        </div>
                                    <?php else: ?>
                                        <img src="<?= htmlspecialchars($media['thumbnail_url'] ?: $media['media_url']) ?>" alt="Thumbnail">
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
        </div>
    
    <?php else: ?>
        <!-- الصورة الافتراضية -->
        <a href="product.php?id=<?= $product['id'] ?>" class="product-image">
            <img src="<?= htmlspecialchars($product['main_image'] ?: 'assets/images/placeholder.jpg') ?>" 
                 alt="<?= htmlspecialchars($product['title']) ?>">
        </a>
    <?php endif; ?>
</div>
                                    
                                    <div class="product-info">
                                        <h3 class="product-title">
                                            <a href="product.php?id=<?= $product['id'] ?>">
                                                <?= htmlspecialchars($product['title']) ?>
                                            </a>
                                        </h3>
                                        
                                        <!-- اسم صاحب المتجر -->
                                        <?php if ($isCustomerStore && $storeOwnerName): ?>
                                        <div class="store-owner" style="
                                            color: #8b5cf6;
                                            font-size: 0.875rem;
                                            font-weight: 600;
                                            margin-bottom: 0.5rem;
                                            display: flex;
                                            align-items: center;
                                            gap: 0.5rem;
                                        ">
                                            <i class="fas fa-user-circle"></i>
                                            من متجر: <?= htmlspecialchars($storeOwnerName) ?>
											<i class="fa-solid fa-location-dot"></i>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <div class="product-rating">
                                            <?php 
                                            $rating = $product['rating_avg'];
                                            for ($i = 1; $i <= 5; $i++): 
                                                if ($i <= $rating): ?>
                                                    <i class="fas fa-star"></i>
                                                <?php elseif ($i - 0.5 <= $rating): ?>
                                                    <i class="fas fa-star-half-alt"></i>
                                                <?php else: ?>
                                                    <i class="far fa-star"></i>
                                                <?php endif;
                                            endfor; ?>
                                            <span>(<?= $product['rating_count'] ?>)</span>
                                        </div>

                                        <!-- معلومات النقاط -->
                                        <?php if (getSetting('points_enabled', '1') == '1'): ?>
                                        <div class="product-points" style="margin: 0.5rem 0; color: #f59e0b; font-size: 0.875rem;">
                                            <i class="fas fa-coins"></i>
                                            اكسب <?= calculatePointsFromPurchase($product['final_price']) ?> نقطة
                                        </div>
                                        <?php endif; ?>

                                        <!-- سعر المنتج -->
                                        <div class="product-price">
                                            <?php if ($isAuctionActive): ?>
                                                <div class="auction-price">
                                                    <span class="price-label">السعر الحالي:</span>
                                                    <span class="price-new"><?= formatPrice(max($product['current_bid'], $product['starting_price'])) ?></span>
                                                    <div class="bid-count">(<?= $product['bid_count'] ?> مزايدة)</div>
                                                </div>
                                            <?php elseif ($product['discount_percentage'] > 0): ?>
                                                <span class="price-old"><?= formatPrice($product['price']) ?></span>
                                                <span class="price-new"><?= formatPrice($product['final_price']) ?></span>
                                            <?php else: ?>
                                                <span class="price-new"><?= formatPrice($product['price']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <!-- أزرار الإجراءات -->
                                        <?php if ($isAuctionActive): ?>
                                            <button class="btn btn-auction" onclick="openAuctionModal(<?= $product['id'] ?>)">
                                                <i class="fas fa-gavel"></i> شارك في المزاد
                                            </button>
                                        <?php elseif ($product['stock'] > 0): ?>
                                            <button class="btn btn-add-cart" onclick="addToCart(<?= $product['id'] ?>)">
                                                <i class="fas fa-cart-plus"></i> أضف للسلة
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-disabled" disabled>
                                                نفذت الكمية
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Pagination -->
                    <?php if (count($products) >= $perPage): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?= $page - 1 ?><?= $categoryId ? '&category=' . $categoryId : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?>&sort=<?= $sort ?>" 
                               class="page-link">السابق</a>
                        <?php endif; ?>
                        
                        <span class="page-current">صفحة <?= $page ?></span>
                        
                        <a href="?page=<?= $page + 1 ?><?= $categoryId ? '&category=' . $categoryId : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?>&sort=<?= $sort ?>" 
                           class="page-link">التالي</a>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </section>
        </div>
    </div>
</main>
<script>
    // تحديث العداد كل ثانية
function updateBlackFridayCountdown() {
        const daysElem = document.getElementById('countdown-days');
        const hoursElem = document.getElementById('countdown-hours');
        const minutesElem = document.getElementById('countdown-minutes');
        const secondsElem = document.getElementById('countdown-seconds');
        
        if (!daysElem) return;
        
        let days = parseInt(daysElem.textContent);
        let hours = parseInt(hoursElem.textContent);
        let minutes = parseInt(minutesElem.textContent);
        let seconds = parseInt(secondsElem.textContent);
        
        seconds--;
        
        if (seconds < 0) {
            seconds = 59;
            minutes--;
            
            if (minutes < 0) {
                minutes = 59;
                hours--;
                
                if (hours < 0) {
                    hours = 23;
                    days--;
                    
                    if (days < 0) {
                        // انتهى الوقت
                        document.querySelector('.black-friday-countdown').innerHTML = 
                            '<div class="countdown-title">انتهت عروض الجمعة البيضاء</div>';
                        return;
                    }
                }
            }
        }
        
        daysElem.textContent = days;
        hoursElem.textContent = hours.toString().padStart(2, '0');
        minutesElem.textContent = minutes.toString().padStart(2, '0');
        secondsElem.textContent = seconds.toString().padStart(2, '0');
        
        setTimeout(updateBlackFridayCountdown, 1000);
    }
    
    // بدء العداد
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(updateBlackFridayCountdown, 1000);
    }); 
// دالة الخلط التلقائي للمنتجات
let autoShuffleInterval = null;
let isShuffling = false;

function shuffleProducts() {
    const productsGrid = document.getElementById('productsGrid');
    if (!productsGrid) return;
    
    const products = Array.from(productsGrid.children);
    
    // خلط المنتجات عشوائياً
    for (let i = products.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        productsGrid.insertBefore(products[j], products[i]);
    }
    
    // إضافة تأثير بصرى للخلط
    productsGrid.style.transition = 'all 0.5s ease';
    setTimeout(() => {
        productsGrid.style.transition = '';
    }, 500);
}

function toggleAutoShuffle() {
    const shuffleBtn = document.getElementById('autoShuffleBtn');
    
    if (isShuffling) {
        // إيقاف الخلط التلقائي
        clearInterval(autoShuffleInterval);
        autoShuffleInterval = null;
        isShuffling = false;
        shuffleBtn.classList.remove('active');
        shuffleBtn.innerHTML = '<i class="fas fa-random"></i> خلط المنتجات';
    } else {
        // بدء الخلط التلقائي
        shuffleProducts(); // خلط فوري أول مرة
        autoShuffleInterval = setInterval(shuffleProducts, 30000); // كل 30 ثانية
        isShuffling = true;
        shuffleBtn.classList.add('active');
        shuffleBtn.innerHTML = '<i class="fas fa-stop"></i> إيقاف الخلط';
    }
}

// دالة عرض المتاجر فقط
function showStoresOnly() {
    const productsGrid = document.getElementById('productsGrid');
    if (!productsGrid) return;
    
    const allProducts = productsGrid.querySelectorAll('.product-card');
    
    allProducts.forEach(product => {
        const isCustomerStore = product.classList.contains('customer-store-product');
        
        if (isCustomerStore) {
            product.style.display = 'block';
        } else {
            product.style.display = 'none';
        }
    });
}

// دالة عرض جميع المنتجات
function showAllProducts() {
    const productsGrid = document.getElementById('productsGrid');
    if (!productsGrid) return;
    
    const allProducts = productsGrid.querySelectorAll('.product-card');
    
    allProducts.forEach(product => {
        product.style.display = 'block';
    });
}

// تهيئة الأحداث عند تحميل الصفحة
document.addEventListener('DOMContentLoaded', function() {
    // زر الخلط التلقائي
    const shuffleBtn = document.getElementById('autoShuffleBtn');
    if (shuffleBtn) {
        shuffleBtn.addEventListener('click', toggleAutoShuffle);
    }
    
    // زر عرض المتاجر فقط
    const storesOnlyBtn = document.getElementById('showStoresOnly');
    if (storesOnlyBtn) {
        let showingStoresOnly = false;
        
        storesOnlyBtn.addEventListener('click', function() {
            if (showingStoresOnly) {
                showAllProducts();
                storesOnlyBtn.innerHTML = '<i class="fas fa-store"></i> عرض المتاجر فقط';
                showingStoresOnly = false;
            } else {
                showStoresOnly();
                storesOnlyBtn.innerHTML = '<i class="fas fa-eye"></i> عرض جميع المنتجات';
                showingStoresOnly = true;
            }
        });
    }
	    const storesOnlyBtn1 = document.getElementById('showStoresOnly1');
    if (storesOnlyBtn1) {
        let showingStoresOnly = false;
        
        storesOnlyBtn1.addEventListener('click', function() {
            if (showingStoresOnly) {
                showAllProducts();
                storesOnlyBtn1.innerHTML = '<i class="fas fa-store"></i> المتاجر فقط';
                showingStoresOnly = false;
            } else {
                showStoresOnly();
                storesOnlyBtn1.innerHTML = '<i class="fas fa-eye"></i>  جميع المنتجات';
                showingStoresOnly = true;
            }
        });
    }
});
// دالة تبديل الوسائط
function switchMedia(index) {
    const mediaItems = document.querySelectorAll('.media-item');
    const thumbnails = document.querySelectorAll('.thumbnail-item');
    
    // إخفاء جميع الوسائط
    mediaItems.forEach(item => {
        item.classList.remove('active');
    });
    
    // إلغاء تنشيط جميع المصغرات
    thumbnails.forEach(thumb => {
        thumb.classList.remove('active');
    });
    
    // إظهار الوسائط المحددة
    mediaItems[index].classList.add('active');
    thumbnails[index].classList.add('active');
    
    // إيقاف جميع الفيديوهات
    document.querySelectorAll('video').forEach(video => {
        video.pause();
    });
}

// دالة التحكم بالفيديو
function toggleVideo(button) {
    const video = button.closest('.media-item').querySelector('video');
    const icon = button.querySelector('i');
    
    if (video.paused) {
        video.play();
        icon.className = 'fas fa-pause';
    } else {
        video.pause();
        icon.className = 'fas fa-play';
    }
}

// دوال التحكم بالنماذج 3D
function rotateModel(modelId, axis, degrees) {
    const modelViewer = document.querySelector(`#${modelId} model-viewer`);
    if (modelViewer) {
        const currentRotation = modelViewer.getAttribute('rotation');
        // تطبيق التدوير - تحتاج لتعديل حسب مكتبة 3D المستخدمة
        console.log(`Rotating model ${modelId} around ${axis} axis by ${degrees} degrees`);
    }
}

function resetModel(modelId) {
    const modelViewer = document.querySelector(`#${modelId} model-viewer`);
    if (modelViewer) {
        // إعادة النموذج لوضعه الافتراضي
        modelViewer.reset();
    }
}

function zoomModel(modelId, direction) {
    const modelViewer = document.querySelector(`#${modelId} model-viewer`);
    if (modelViewer) {
        // تكبير/تصغير النموذج
        console.log(`Zoom ${direction} model ${modelId}`);
    }
}

// تهيئة الأحداث
document.addEventListener('DOMContentLoaded', function() {
    // أحداث النقر على المصغرات
    document.querySelectorAll('.thumbnail-item').forEach((thumb, index) => {
        thumb.addEventListener('click', () => switchMedia(index));
    });
    
    // التحكم التلقائي في GIF
    document.querySelectorAll('.product-media-gif').forEach(gif => {
        gif.addEventListener('load', function() {
            // يمكن إضافة تحكم إضافي للـ GIF هنا
        });
    });
    
// أحداث الفيديو
document.querySelectorAll('video').forEach(video => {
    video.addEventListener('play', function() {
        const mediaItem = this.closest('.media-item');
        if (mediaItem) {
            const buttonIcon = mediaItem.querySelector('.video-play-btn i');
            if (buttonIcon) {
                buttonIcon.className = 'fas fa-pause';
            }
        }
    });
    
    video.addEventListener('pause', function() {
        const mediaItem = this.closest('.media-item');
        if (mediaItem) {
            const buttonIcon = mediaItem.querySelector('.video-play-btn i');
            if (buttonIcon) {
                buttonIcon.className = 'fas fa-play';
            }
        }
    });
});
});

// دالة للعرض الكامل للوسائط
function openMediaFullscreen(mediaElement) {
    if (mediaElement.requestFullscreen) {
        mediaElement.requestFullscreen();
    } else if (mediaElement.webkitRequestFullscreen) {
        mediaElement.webkitRequestFullscreen();
    } else if (mediaElement.msRequestFullscreen) {
        mediaElement.msRequestFullscreen();
    }
}
// تصحيح النماذج ثلاثية الأبعاد
function initialize3DModels() {
    const modelViewers = document.querySelectorAll('model-viewer');
    console.log('Found 3D models:', modelViewers.length);
    
    modelViewers.forEach((viewer, index) => {
        viewer.addEventListener('load', () => {
            console.log('3D model loaded:', viewer.src);
        });
        
        viewer.addEventListener('error', (e) => {
            console.error('3D model error:', viewer.src, e);
            // عرض رسالة خطأ
            viewer.innerHTML = `
                <div style="display: flex; align-items: center; justify-content: center; height: 100%; background: #f8f9fa; color: #666;">
                    <div style="text-align: center;">
                        <i class="fas fa-exclamation-triangle fa-2x" style="margin-bottom: 10px;"></i>
                        <p>تعذر تحميل النموذج ثلاثي الأبعاد</p>
                        <small>${viewer.src}</small>
                    </div>
                </div>
            `;
        });
    });
}

// استدعاء عند تحميل الصفحة
document.addEventListener('DOMContentLoaded', initialize3DModels);
</script>
<script>
// ✅ COMPREHENSIVE 3D MODEL DETECTION & INITIALIZATION SYSTEM

// Global state tracking
window.modelViewerState = {
    loaded: false,
    initialized: false,
    modelsFound: 0,
    retryCount: 0,
    maxRetries: 10
};

// ========================
// 1. LIBRARY LOAD CHECKER
// ========================
function checkModelViewerLibrary() {
    if (customElements.get('model-viewer')) {
        window.modelViewerState.loaded = true;
        console.log('✅ model-viewer library is loaded');
        return true;
    }
    console.log('⏳ Waiting for model-viewer library...');
    return false;
}

// ========================
// 2. DOM MODELS DETECTOR
// ========================
function detectModels() {
    // Try multiple selectors to find models
    const selectors = [
        'model-viewer',
        '[data-type="3d_model"]',
        '.product-3d-model model-viewer',
        '.media-item[data-type="3d_model"] model-viewer'
    ];
    
    let foundModels = [];
    
    selectors.forEach(selector => {
        const elements = document.querySelectorAll(selector);
        if (elements.length > 0) {
            foundModels = [...foundModels, ...Array.from(elements)];
        }
    });
    
    // Remove duplicates
    foundModels = Array.from(new Set(foundModels));
    
    window.modelViewerState.modelsFound = foundModels.length;
    console.log(`🎨 Detected ${foundModels.length} 3D models`);
    
    return foundModels;
}

// ========================
// 3. MODEL INITIALIZER
// ========================
function initializeModels() {
    if (window.modelViewerState.initialized) {
        console.log('⚠️ Models already initialized');
        return;
    }
    
    const models = detectModels();
    
    if (models.length === 0) {
        console.log('⚠️ No 3D models found in DOM');
        
        // Retry detection if we haven't exceeded max retries
        if (window.modelViewerState.retryCount < window.modelViewerState.maxRetries) {
            window.modelViewerState.retryCount++;
            console.log(`🔄 Retry ${window.modelViewerState.retryCount}/${window.modelViewerState.maxRetries}`);
            setTimeout(initializeModels, 500);
        } else {
            console.log('❌ Max retries reached. Models may be loaded dynamically later.');
        }
        return;
    }
    
    window.modelViewerState.initialized = true;
    console.log('🚀 Initializing models...');
    
    models.forEach((viewer, index) => {
        setupModelViewer(viewer, index);
    });
}

// ========================
// 4. INDIVIDUAL MODEL SETUP
// ========================
function setupModelViewer(viewer, index) {
    const modelSrc = viewer.getAttribute('src');
    console.log(`📦 Model ${index + 1}: ${modelSrc}`);
    
    // Load event
    viewer.addEventListener('load', () => {
        console.log(`✅ Model ${index + 1} loaded successfully`);
        viewer.classList.add('model-loaded');
    });
    
    // Error event
    viewer.addEventListener('error', (e) => {
        console.error(`❌ Model ${index + 1} failed to load:`, e);
        showModelError(viewer, 'فشل تحميل النموذج ثلاثي الأبعاد');
    });
    
    // Progress event
    viewer.addEventListener('progress', (e) => {
        const progress = (e.detail.totalProgress * 100).toFixed(0);
        console.log(`📊 Model ${index + 1} progress: ${progress}%`);
        updateProgressBar(viewer, progress);
    });
    
    // Model ready event
    viewer.addEventListener('model-visibility', (e) => {
        if (e.detail.visible) {
            console.log(`👁️ Model ${index + 1} is now visible`);
        }
    });
}

// ========================
// 5. PROGRESS BAR UPDATER
// ========================
function updateProgressBar(viewer, progress) {
    const progressBar = viewer.querySelector('.update-bar');
    if (progressBar) {
        progressBar.style.width = `${progress}%`;
    }
}

// ========================
// 6. ERROR HANDLER
// ========================
function showModelError(viewer, message) {
    const container = viewer.closest('.product-3d-model, .media-item');
    if (!container) return;
    
    container.innerHTML = `
        <div style="
            width: 100%;
            height: 300px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #ff6b6b, #ee5a52);
            color: white;
            text-align: center;
            padding: 20px;
            border-radius: 10px;">
            <div>
                <i class="fas fa-exclamation-triangle fa-3x" style="margin-bottom: 15px;"></i>
                <p style="font-size: 1.1rem; font-weight: 600; margin: 10px 0;">${message}</p>
                <small style="opacity: 0.8;">تحقق من صحة رابط النموذج أو حاول إعادة تحميل الصفحة</small>
                <br><br>
                <button onclick="location.reload()" style="
                    background: white;
                    color: #ee5a52;
                    border: none;
                    padding: 10px 20px;
                    border-radius: 5px;
                    cursor: pointer;
                    font-weight: 600;">
                    <i class="fas fa-redo"></i> إعادة المحاولة
                </button>
            </div>
        </div>
    `;
}

// ========================
// 7. CONTROL FUNCTIONS
// ========================
function rotateModel3D(productId, index) {
    const viewer = document.querySelector(`#model-${productId}-${index} model-viewer`);
    if (!viewer) {
        console.error('Model viewer not found');
        return;
    }
    
    try {
        const orbit = viewer.getCameraOrbit();
        viewer.cameraOrbit = `${parseFloat(orbit.theta) + 45}deg ${orbit.phi} ${orbit.radius}`;
        console.log('🔄 Model rotated');
    } catch (e) {
        console.error('Rotation error:', e);
    }
}

function resetModel3D(productId, index) {
    const viewer = document.querySelector(`#model-${productId}-${index} model-viewer`);
    if (!viewer) return;
    
    viewer.cameraOrbit = '0deg 75deg 105%';
    viewer.fieldOfView = '30deg';
    viewer.resetTurntableRotation?.();
    console.log('🔄 Model reset');
}

function toggleAutoRotate(productId, index) {
    const viewer = document.querySelector(`#model-${productId}-${index} model-viewer`);
    const btn = document.getElementById(`rotate-btn-${productId}-${index}`);
    
    if (!viewer || !btn) return;
    
    viewer.autoRotate = !viewer.autoRotate;
    btn.innerHTML = viewer.autoRotate 
        ? '<i class="fas fa-pause"></i>' 
        : '<i class="fas fa-play"></i>';
}

function downloadModel(url, filename) {
    if (!url) {
        alert('❌ لا يوجد رابط للتحميل');
        return;
    }
    
    const link = document.createElement('a');
    link.href = url;
    link.download = filename || 'model_3d.glb';
    link.target = '_blank';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    console.log('📥 Download started:', filename);
}

// ========================
// 8. INITIALIZATION FLOW
// ========================
function startInitialization() {
    console.log('🎯 Starting 3D Model System Initialization');
    
    // Check if library is loaded
    if (!checkModelViewerLibrary()) {
        // Wait for library
        const checkInterval = setInterval(() => {
            if (checkModelViewerLibrary()) {
                clearInterval(checkInterval);
                initializeModels();
            }
        }, 500);
        
        // Timeout after 10 seconds
        setTimeout(() => {
            clearInterval(checkInterval);
            if (!window.modelViewerState.loaded) {
                console.error('❌ model-viewer library failed to load');
            }
        }, 10000);
    } else {
        // Library already loaded, initialize immediately
        initializeModels();
    }
}

// ========================
// 9. MUTATION OBSERVER FOR DYNAMIC CONTENT
// ========================
function setupMutationObserver() {
    const observer = new MutationObserver((mutations) => {
        let shouldCheck = false;
        
        mutations.forEach((mutation) => {
            if (mutation.addedNodes.length > 0) {
                mutation.addedNodes.forEach((node) => {
                    if (node.nodeType === 1) { // Element node
                        if (node.tagName === 'MODEL-VIEWER' || 
                            node.querySelector?.('model-viewer')) {
                            shouldCheck = true;
                        }
                    }
                });
            }
        });
        
        if (shouldCheck) {
            console.log('🔍 New models detected, reinitializing...');
            setTimeout(initializeModels, 100);
        }
    });
    
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
    
    console.log('👁️ Mutation observer active for dynamic content');
}

// ========================
// 10. EXPORT TO GLOBAL SCOPE
// ========================
window.rotateModel3D = rotateModel3D;
window.resetModel3D = resetModel3D;
window.toggleAutoRotate = toggleAutoRotate;
window.downloadModel = downloadModel;
window.initializeModels = initializeModels;
window.detectModels = detectModels;

// ========================
// 11. AUTO-START
// ========================
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        startInitialization();
        setupMutationObserver();
    });
} else {
    // DOM already loaded
    startInitialization();
    setupMutationObserver();
}

console.log('✅ 3D Model System Script Loaded');
</script>
<script>
// جعل الفيديو يشتغل تلقائيًا ويعاد تشغيله مرتين فقط (3 مرات كاملة)
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('video[loop-data]').forEach(video => {
        let maxLoops = parseInt(video.getAttribute('loop-data')) || 0;
        let playCount = 0;

        if (maxLoops <= 0) return;

        video.addEventListener('ended', function() {
            playCount++;
            if (playCount < maxLoops + 1) { // +1 لأن أول تشغيل مش محسوب في ended
                video.currentTime = 0;
                video.play();
            } else {
                video.pause();
                video.currentTime = 0;
                // اختياري: إظهار زر التشغيل اليدوي بعد الانتهاء
                video.controls = true;
            }
        });

        // تشغيل تلقائي فوري
        video.play().catch(e => {
            console.log("Autoplay منع بسبب سياسة المتصفح (مسموح فقط بعد تفاعل المستخدم)");
        });
    });
});
</script>
<script>
// نظام المقايضة
let selectedBarterProductId = null;
let currentTargetProductId = null;
let currentTargetOwnerId = null;

// فتح نافذة المقايضة
function openBarterModal(targetProductId, targetOwnerId) {
    currentTargetProductId = targetProductId;
    currentTargetOwnerId = targetOwnerId;
    
    // جلب بيانات المنتج المستهدف
    fetch(`api/get_product.php?id=${targetProductId}`)
        .then(response => response.json())
        .then(targetProduct => {
            // جلب منتجات المستخدم للمقايضة
            return fetch(`api/get_my_products.php?user_id=<?= $_SESSION['customer_id'] ?? 0 ?>`)
                .then(response => response.json())
                .then(myProducts => {
                    showBarterModal(targetProduct, myProducts);
                });
        })
        .catch(error => {
            console.error('Error:', error);
            alert('حدث خطأ في تحميل البيانات');
        });
}

// عرض نافذة المقايضة
function showBarterModal(targetProduct, myProducts) {
    const modal = document.createElement('div');
    modal.className = 'barter-modal';
    modal.innerHTML = `
        <div class="barter-content">
            <div class="barter-header">
                <h3><i class="fas fa-exchange-alt"></i> مقايضة السلع</h3>
                <button class="close-barter" onclick="closeBarterModal()">&times;</button>
            </div>
            
            <div class="barter-body">
                <!-- المنتج المستهدف -->
                <div class="barter-product">
                    <h4>المنتج المطلوب</h4>
                    <img src="${targetProduct.main_image || 'assets/images/placeholder.jpg'}" 
                         alt="${targetProduct.title}">
                    <h5>${targetProduct.title}</h5>
                    <p class="price">${targetProduct.final_price} ريال</p>
                    <small>مملوك ل: ${targetProduct.owner_name}</small>
                </div>
                
                <!-- السهم -->
                <div class="barter-arrow">
                    <i class="fas fa-exchange-alt"></i>
                </div>
                
                <!-- منتجاتي للمقايضة -->
                <div class="barter-product">
                    <h4>اختر منتج للمقايضة</h4>
                    ${myProducts.length > 0 ? `
                        <div class="my-products-grid" id="myProductsGrid">
                            ${myProducts.map(product => `
                                <div class="barter-product-card" 
                                     onclick="selectBarterProduct(${product.id})"
                                     id="barterProduct-${product.id}">
                                    <img src="${product.main_image || 'assets/images/placeholder.jpg'}" 
                                         alt="${product.title}">
                                    <h6>${product.title}</h6>
                                    <small>${product.final_price} ريال</small>
                                </div>
                            `).join('')}
                        </div>
                        
                        <div class="barter-message">
                            <label>رسالة للمالك (اختياري):</label>
                            <textarea id="barterMessage" placeholder="اكتب رسالة توضح سبب المقايضة..."></textarea>
                        </div>
                    ` : `
                        <div class="no-products">
                            <i class="fas fa-box-open fa-2x"></i>
                            <p>لا توجد منتجات في حسابك للمقايضة</p>
                            <small>يجب أن تمتلك منتجات في حسابك لتتمكن من المقايضة</small>
                        </div>
                    `}
                </div>
            </div>
            
            <div class="barter-actions">
                <button class="btn btn-secondary" onclick="closeBarterModal()">إلغاء</button>
                <button class="btn-send-offer" id="sendBarterOffer" 
                        onclick="sendBarterOffer()" disabled>
                    <i class="fas fa-paper-plane"></i> إرسال عرض المقايضة
                </button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    modal.style.display = 'flex';
}

// اختيار منتج للمقايضة
function selectBarterProduct(productId) {
    selectedBarterProductId = productId;
    
    // إزالة التحديد من جميع المنتجات
    document.querySelectorAll('.barter-product-card').forEach(card => {
        card.classList.remove('selected');
    });
    
    // إضافة التحديد للمنتج المختار
    const selectedCard = document.getElementById(`barterProduct-${productId}`);
    if (selectedCard) {
        selectedCard.classList.add('selected');
    }
    
    // تفعيل زر الإرسال
    document.getElementById('sendBarterOffer').disabled = false;
}

// إرسال عرض المقايضة
function sendBarterOffer() {
    if (!selectedBarterProductId || !currentTargetProductId) {
        alert('يرجى اختيار منتج للمقايضة');
        return;
    }
    
    const message = document.getElementById('barterMessage')?.value || '';
    
    const offerData = {
        target_product_id: currentTargetProductId,
        target_owner_id: currentTargetOwnerId,
        my_product_id: selectedBarterProductId,
        message: message,
        status: 'pending'
    };
    
    fetch('api/send_barter_offer.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(offerData)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            alert('تم إرسال عرض المقايضة بنجاح');
            closeBarterModal();
            
            // إشعار للمالك
            showNotification('تم إرسال عرض المقايضة بنجاح', 'success');
        } else {
            alert('حدث خطأ: ' + result.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('حدث خطأ في إرسال العرض');
    });
}

// إغلاق نافذة المقايضة
function closeBarterModal() {
    const modal = document.querySelector('.barter-modal');
    if (modal) {
        modal.remove();
    }
    selectedBarterProductId = null;
    currentTargetProductId = null;
    currentTargetOwnerId = null;
}

// إغلاق النافذة بالنقر خارجها
document.addEventListener('click', function(event) {
    if (event.target.classList.contains('barter-modal')) {
        closeBarterModal();
    }
});
</script>
<script>
    // التحكم في إظهار وإخفاء الشريط الجانبي
    document.addEventListener('DOMContentLoaded', function() {
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.querySelector('.sidebar');
        const productsSection = document.querySelector('.products-section');
        const mainContent = document.querySelector('.main-content');
        
        // التحقق من حالة الشريط الجانبي المحفوظة
        const isSidebarHidden = localStorage.getItem('sidebarHidden') === 'true';
        
        if (isSidebarHidden) {
            toggleSidebar(true);
        }
        
        // حدث النقر على زر التحكم
        sidebarToggle.addEventListener('click', function() {
            const currentlyHidden = mainContent.classList.contains('sidebar-hidden');
            toggleSidebar(!currentlyHidden);
        });
        
        function toggleSidebar(hide) {
            if (hide) {
                mainContent.classList.add('sidebar-hidden');
                sidebarToggle.innerHTML = '<i class="fas fa-chevron-right"></i>';
                sidebarToggle.title = 'إظهار الشريط الجانبي';
            } else {
                mainContent.classList.remove('sidebar-hidden');
                sidebarToggle.innerHTML = '<i class="fas fa-bars"></i>';
                sidebarToggle.title = 'إخفاء الشريط الجانبي';
            }
            
            // حفظ الحالة في التخزين المحلي
            localStorage.setItem('sidebarHidden', hide);
        }
        
        // إغلاق الشريط الجانبي تلقائياً على الشاشات الصغيرة
        function handleResponsiveSidebar() {
            if (window.innerWidth <= 768) {
                if (!mainContent.classList.contains('sidebar-hidden')) {
                    toggleSidebar(true);
                }
            }
        }
        
        // التحقق عند تحميل الصفحة وعند تغيير حجم النافذة
        handleResponsiveSidebar();
        window.addEventListener('resize', handleResponsiveSidebar);
    });
</script>
<!-- أضف هذا السكريبت في نهاية الملف -->
<script>
// نظام إدارة الألوان والتأثيرات
document.addEventListener('DOMContentLoaded', function() {
    // تهيئة نظام الألوان للمنتجات
    initializeProductColors();
    
    // نظام التوهج التلقائي
    startAutoGlowSystem();
});

// دالة تهيئة الألوان
function initializeProductColors() {
    const productCards = document.querySelectorAll('.product-card');
    
    productCards.forEach((card, index) => {
        // إضافة فئة اللون إذا لم تكن موجودة
        if (!card.className.includes('category-')) {
            const colorClass = `category-${(index % 8) + 1}`;
            card.classList.add(colorClass);
        }
        
        // إضافة تأثيرات إضافية
        addHoverEffects(card);
    });
}

// دالة إضافة تأثيرات التمرير
function addHoverEffects(card) {
    card.addEventListener('mouseenter', function() {
        this.style.animationPlayState = 'paused';
        this.style.transform = 'translateY(-5px) scale(1.02)';
    });
    
    card.addEventListener('mouseleave', function() {
        this.style.animationPlayState = 'running';
        this.style.transform = 'translateY(0) scale(1)';
    });
}

// نظام التوهج التلقائي المتقدم
function startAutoGlowSystem() {
    const productCards = document.querySelectorAll('.product-card');
    let currentGlowIndex = 0;
    
    // بدء دورة التوهج
    setInterval(() => {
        // إزالة التوهج النشط من جميع البطاقات
        productCards.forEach(card => {
            card.classList.remove('active-glow');
        });
        
        // تطبيق التوهج على البطاقة الحالية
        if (productCards[currentGlowIndex]) {
            productCards[currentGlowIndex].classList.add('active-glow');
            
            // تشغيل تأثير التوهج القوي
            applyStrongGlow(productCards[currentGlowIndex]);
        }
        
        // الانتقال للبطاقة التالية
        currentGlowIndex = (currentGlowIndex + 1) % productCards.length;
        
    }, 2000); // كل ثانيتين
}

// دالة تطبيق توهج قوي مؤقت
function applyStrongGlow(card) {
    // حفظ الخلفية الأصلية
    const originalBg = card.style.background;
    
    // تطبيق توهج قوي
    card.style.background = getStrongGlowColor(card);
    card.style.boxShadow = '0 6px 25px rgba(0,0,0,0.15)';
    card.style.zIndex = '10';
    
    // إعادة الخلفية الأصلية بعد 0.8 ثانية
    setTimeout(() => {
        card.style.background = originalBg;
        card.style.boxShadow = '';
        card.style.zIndex = '';
    }, 800);
}

// دالة للحصول على لون التوهج القوي
function getStrongGlowColor(card) {
    const categoryClass = Array.from(card.classList).find(cls => cls.startsWith('category-'));
    
    const glowColors = {
        'category-1': 'linear-gradient(135deg, #ffedd5 0%, #fff7ed 100%)',
        'category-2': 'linear-gradient(135deg, #dbeafe 0%, #eff6ff 100%)',
        'category-3': 'linear-gradient(135deg, #d1fae5 0%, #ecfdf5 100%)',
        'category-4': 'linear-gradient(135deg, #e9d5ff 0%, #f3e8ff 100%)',
        'category-5': 'linear-gradient(135deg, #fed7aa 0%, #ffedd5 100%)',
        'category-6': 'linear-gradient(135deg, #fecaca 0%, #fef2f2 100%)',
        'category-7': 'linear-gradient(135deg, #fef3c7 0%, #fefce8 100%)',
        'category-8': 'linear-gradient(135deg, #a7f3d0 0%, #d1fae5 100%)'
    };
    
    return glowColors[categoryClass] || glowColors['category-1'];
}

// تأثيرات إضافية عند التمرير
function setupScrollEffects() {
    const productsGrid = document.querySelector('.products-grid');
    
    window.addEventListener('scroll', function() {
        const cards = document.querySelectorAll('.product-card');
        const scrollY = window.scrollY;
        
        cards.forEach((card, index) => {
            const cardTop = card.getBoundingClientRect().top + scrollY;
            const delay = index * 0.1;
            
            if (cardTop < scrollY + window.innerHeight - 100) {
                card.style.opacity = '1';
                card.style.transform = `translateY(0) rotateX(0)`;
            }
        });
    });
}

// تهيئة التأثيرات عند تحميل الصفحة
setupScrollEffects();

// تحديث الألوان عند إضافة منتجات ديناميكياً
function updateProductColors() {
    initializeProductColors();
}

// إمكانية إيقاف/تشغيل نظام التوهج
let glowSystemActive = true;

function toggleGlowSystem() {
    glowSystemActive = !glowSystemActive;
    const productCards = document.querySelectorAll('.product-card');
    
    if (glowSystemActive) {
        productCards.forEach(card => {
            card.style.animationPlayState = 'running';
        });
        startAutoGlowSystem();
    } else {
        productCards.forEach(card => {
            card.style.animationPlayState = 'paused';
            card.classList.remove('active-glow');
        });
    }
}

// إضافة زر للتحكم في نظام التوهج (اختياري)
function addGlowControlButton() {
    const controlBtn = document.createElement('button');
    controlBtn.innerHTML = '🌙 إيقاف التوهج';
    controlBtn.style.position = 'fixed';
    controlBtn.style.bottom = '20px';
    controlBtn.style.right = '20px';
    controlBtn.style.zIndex = '1000';
    controlBtn.style.padding = '10px 15px';
    controlBtn.style.background = '#667eea';
    controlBtn.style.color = 'white';
    controlBtn.style.border = 'none';
    controlBtn.style.borderRadius = '25px';
    controlBtn.style.cursor = 'pointer';
    controlBtn.style.fontSize = '14px';
    
    controlBtn.addEventListener('click', function() {
        toggleGlowSystem();
        this.innerHTML = glowSystemActive ? '🌙 إيقاف التوهج' : '✨ تشغيل التوهج';
    });
    
    document.body.appendChild(controlBtn);
}

// تفعيل زر التحكم (يمكن إزالة هذا إذا لم ترد الزر)
// addGlowControlButton();
</script>

<!-- أضف هذا CSS الإضافي -->
<style>
    /* تأثير التوهج النشط */
    .product-card.active-glow {
        transition: all 0.5s ease !important;
    }
    
    /* تحسينات للشاشات المتحسسة لللمس */
    @media (hover: hover) {
        .product-card:hover {
            animation-play-state: paused;
        }
    }
    
    /* تحسينات الأداء */
    .product-card {
        will-change: transform, box-shadow;
        backface-visibility: hidden;
    }
    
    /* تأثيرات دخول المنتجات */
    .product-card {
        opacity: 0;
        transform: translateY(20px) rotateX(5deg);
        animation: cardEnter 0.6s ease forwards;
    }
    
    @keyframes cardEnter {
        to {
            opacity: 1;
            transform: translateY(0) rotateX(0);
        }
    }
    
    /* تأخيرات دخول متدرجة */
    .product-card:nth-child(1) { animation-delay: 0.1s; }
    .product-card:nth-child(2) { animation-delay: 0.2s; }
    .product-card:nth-child(3) { animation-delay: 0.3s; }
    .product-card:nth-child(4) { animation-delay: 0.4s; }
    .product-card:nth-child(5) { animation-delay: 0.5s; }
    .product-card:nth-child(6) { animation-delay: 0.6s; }
    .product-card:nth-child(7) { animation-delay: 0.7s; }
    .product-card:nth-child(8) { animation-delay: 0.8s; }
	/* CSS المعدل - ضع هذا بدلاً من الـ CSS القديم */
.media-thumbnails-container {
    max-height: 0;
    opacity: 0;
    overflow: hidden;
    transition: max-height 0.4s ease, opacity 0.3s ease;
    transform: translateY(-10px);
    pointer-events: none;
}

.media-thumbnails-container.visible {
    max-height: 80px;
    opacity: 1;
    transform: translateY(0);
    pointer-events: auto;
}

.main-media-wrapper {
    position: relative;
    transition: margin-bottom 0.4s ease;
}

.main-media-wrapper:hover + .media-thumbnails-container,
.main-media-wrapper:hover ~ .media-thumbnails-container,
.media-thumbnails-container:hover {
    max-height: 80px;
    opacity: 1;
    transform: translateY(0);
    pointer-events: auto;
}

/* تحسين ظاهر المؤشر */
.hover-trigger {
    position: absolute;
    bottom: 10px;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(0,0,0,0.8);
    color: white;
    padding: 8px 16px;
    border-radius: 25px;
    font-size: 0.8rem;
    opacity: 0;
    transition: all 0.3s ease;
    z-index: 20;
    pointer-events: none;
    white-space: nowrap;
    backdrop-filter: blur(5px);
    border: 1px solid rgba(255,255,255,0.2);
}

.main-media-wrapper:hover .hover-trigger {
    opacity: 1;
    bottom: 15px;
}
/* ===== الحل البديل الأقوى ===== */
.media-thumbnails {
    display: flex;
    gap: 8px;
    padding: 10px 5px;
    background: rgba(255,255,255,0.95);
    backdrop-filter: blur(10px);
    border-top: 1px solid rgba(0,0,0,0.1);
    max-height: 0;
    opacity: 0;
    overflow: hidden;
    transition: all 0.4s ease;
}

/* عند الظهور */
.main-media-wrapper:hover + .media-thumbnails,
.product-card:hover .media-thumbnails {
    max-height: 100px;
    opacity: 1;
    padding: 10px 5px;
    margin-top: 10px;
}

/* إبقائها ظاهرة عند التمرير فوقها */
.media-thumbnails:hover {
    max-height: 100px;
    opacity: 1;
    padding: 10px 5px;
    margin-top: 10px;
}
</style>
<!-- السكريبت الكامل -->
<script>
// نظام إدارة المصغرات
class ThumbnailsSystem {
    constructor() {
        this.timeouts = new Map();
        this.isVisible = new Map();
        this.init();
    }
    
    init() {
        this.setupEventListeners();
        console.log('✅ Thumbnails System Initialized');
    }
    
    setupEventListeners() {
        // إعداد أحداث لكل منتج
        document.querySelectorAll('.product-card').forEach(card => {
            const productId = this.getProductId(card);
            if (!productId) return;
            
            const mediaWrapper = card.querySelector('.main-media-wrapper');
            const productCard = card;
            
            if (mediaWrapper) {
                // أحداث الفأرة
                mediaWrapper.addEventListener('mouseenter', () => {
                    this.show(productId);
                });
                
                mediaWrapper.addEventListener('mouseleave', (e) => {
                    // التحقق إذا تحرك الماوس للمصغرات
                    const relatedTarget = e.relatedTarget;
                    const thumbnails = document.getElementById(`thumbnails-${productId}`);
                    if (!thumbnails || !thumbnails.contains(relatedTarget)) {
                        this.scheduleHide(productId);
                    }
                });
            }
            
            // أحداث المنتج ككل
            productCard.addEventListener('mouseenter', () => {
                if (!this.isVisible.get(productId)) {
                    this.show(productId);
                }
            });
            
            productCard.addEventListener('mouseleave', (e) => {
                const relatedTarget = e.relatedTarget;
                const productMediaContainer = document.getElementById(`productMedia-${productId}`);
                if (!productMediaContainer || !productMediaContainer.contains(relatedTarget)) {
                    this.scheduleHide(productId);
                }
            });
        });
        
        // كشف اللمس للجوال
        this.setupTouchSupport();
    }
    
    setupTouchSupport() {
        document.querySelectorAll('.main-media-wrapper').forEach(wrapper => {
            let tapTimer;
            let tapCount = 0;
            
            wrapper.addEventListener('touchstart', (e) => {
                tapCount++;
                
                if (tapCount === 1) {
                    tapTimer = setTimeout(() => {
                        tapCount = 0;
                        const productId = this.getProductId(wrapper);
                        if (productId) {
                            this.toggle(productId);
                        }
                    }, 300);
                } else if (tapCount === 2) {
                    clearTimeout(tapTimer);
                    tapCount = 0;
                    // ضغطتين تعني التبديل للوسائط الكاملة
                }
                
                e.preventDefault();
            }, { passive: false });
        });
    }
    
    show(productId) {
        clearTimeout(this.timeouts.get(`hide_${productId}`));
        
        const container = document.getElementById(`thumbnailsContainer-${productId}`);
        const trigger = document.getElementById(`hoverTrigger-${productId}`);
        
        if (container && !container.classList.contains('visible')) {
            container.classList.add('visible');
            if (trigger) trigger.classList.add('visible');
            this.isVisible.set(productId, true);
            
            // إضافة تأثير سلس
            container.style.transition = 'height 0.4s ease, opacity 0.3s ease';
        }
    }
    
    hide(productId) {
        const container = document.getElementById(`thumbnailsContainer-${productId}`);
        const trigger = document.getElementById(`hoverTrigger-${productId}`);
        
        if (container && container.classList.contains('visible')) {
            container.classList.remove('visible');
            if (trigger) trigger.classList.remove('visible');
            this.isVisible.set(productId, false);
        }
    }
    
    scheduleHide(productId) {
        clearTimeout(this.timeouts.get(`hide_${productId}`));
        
        this.timeouts.set(`hide_${productId}`, setTimeout(() => {
            // التحقق إذا كان الماوس لا يزال داخل المنطقة
            const container = document.getElementById(`thumbnailsContainer-${productId}`);
            const wrapper = document.getElementById(`mediaWrapper-${productId}`);
            
            if (container && wrapper) {
                // استخدام العناصر بدلاً من الحالة
                const isMouseOver = this.isElementHovered(container) || this.isElementHovered(wrapper);
                if (!isMouseOver) {
                    this.hide(productId);
                }
            } else {
                this.hide(productId);
            }
        }, 500));
    }
    
    toggle(productId) {
        if (this.isVisible.get(productId)) {
            this.hide(productId);
        } else {
            this.show(productId);
        }
    }
    
    isElementHovered(element) {
        return element.matches(':hover');
    }
    
    getProductId(element) {
        if (element.id && element.id.includes('mediaWrapper-')) {
            return element.id.replace('mediaWrapper-', '');
        }
        
        const wrapper = element.closest('.product-media-container');
        if (wrapper && wrapper.id) {
            return wrapper.id.replace('productMedia-', '');
        }
        
        const card = element.closest('.product-card');
        if (card) {
            const mediaContainer = card.querySelector('.product-media-container');
            if (mediaContainer && mediaContainer.id) {
                return mediaContainer.id.replace('productMedia-', '');
            }
        }
        
        return null;
    }
}

// دوال عامة للاستخدام
function showThumbnails(productId) {
    if (!window.thumbnailsSystem) return;
    window.thumbnailsSystem.show(productId);
}

function hideThumbnails(productId) {
    if (!window.thumbnailsSystem) return;
    window.thumbnailsSystem.scheduleHide(productId);
}

function keepThumbnailsVisible(productId) {
    clearTimeout(window.thumbnailsSystem?.timeouts.get(`hide_${productId}`));
}

function checkThumbnailsHide(productId) {
    if (window.thumbnailsSystem) {
        window.thumbnailsSystem.scheduleHide(productId);
    }
}

function switchToMedia(index, productId) {
    const gallery = document.getElementById(`mediaWrapper-${productId}`)?.parentElement;
    if (!gallery) return;
    
    const mediaItems = gallery.querySelectorAll('.media-item');
    const thumbnails = document.getElementById(`thumbnails-${productId}`);
    
    // إخفاء الكل
    mediaItems.forEach(item => item.classList.remove('active'));
    
    // إظهار المحدد
    if (mediaItems[index]) {
        mediaItems[index].classList.add('active');
        
        // تشغيل الفيديو إذا كان
        const video = mediaItems[index].querySelector('video');
        if (video) {
            video.play().catch(e => console.log('Video play prevented'));
        }
    }
    
    // تحديث المصغرات النشطة
    if (thumbnails) {
        thumbnails.querySelectorAll('.thumbnail-item').forEach((thumb, i) => {
            thumb.classList.toggle('active', i === index);
        });
    }
    
    // عرض المصغرات لمدة إضافية
    showThumbnails(productId);
    setTimeout(() => hideThumbnails(productId), 2000);
}

function highlightThumbnail(thumbnail, productId) {
    const allThumbnails = document.querySelectorAll(`#thumbnails-${productId} .thumbnail-item`);
    allThumbnails.forEach(t => t.classList.remove('highlighted'));
    thumbnail.classList.add('highlighted');
}

// تهيئة النظام عند تحميل الصفحة
document.addEventListener('DOMContentLoaded', function() {
    window.thumbnailsSystem = new ThumbnailsSystem();
    
    // دعم للمحتوى الديناميكي
    const observer = new MutationObserver(() => {
        if (window.thumbnailsSystem) {
            window.thumbnailsSystem.init();
        }
    });
    
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
    
    // إضافة زر تحكم للنظام (للاختبار)
    addControlButton();
});

// زر التحكم للنظام (يمكن إزالته)
function addControlButton() {
    const btn = document.createElement('button');
    btn.innerHTML = '👁️ إظهار/إخفاء المصغرات';
    btn.style.position = 'fixed';
    btn.style.bottom = '60px';
    btn.style.right = '20px';
    btn.style.zIndex = '1000';
    btn.style.padding = '10px 15px';
    btn.style.background = '#3b82f6';
    btn.style.color = 'white';
    btn.style.border = 'none';
    btn.style.borderRadius = '20px';
    btn.style.cursor = 'pointer';
    btn.style.fontSize = '12px';
    btn.style.boxShadow = '0 2px 10px rgba(59, 130, 246, 0.3)';
    
    btn.addEventListener('click', function() {
        const allContainers = document.querySelectorAll('.media-thumbnails-container');
        const shouldShow = !allContainers[0]?.classList.contains('visible');
        
        allContainers.forEach(container => {
            if (shouldShow) {
                container.classList.add('visible');
            } else {
                container.classList.remove('visible');
            }
        });
    });
    
    document.body.appendChild(btn);
}

// CSS اضافي للتحسين
const style = document.createElement('style');
style.textContent = `
    .thumbnail-item.highlighted {
        border-color: #3b82f6 !important;
        box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.3);
    }
    
    .thumbnail-item.active {
        border-color: #10b981 !important;
        position: relative;
    }
    
    .thumbnail-item.active::after {
        content: '';
        position: absolute;
        bottom: -2px;
        left: 50%;
        transform: translateX(-50%);
        width: 20px;
        height: 3px;
        background: #10b981;
        border-radius: 2px;
    }
    
    /* تأثير ظهور المصغرات */
    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .media-thumbnails-container.visible .media-thumbnails {
        animation: slideUp 0.3s ease;
    }
`;
document.head.appendChild(style);
</script>