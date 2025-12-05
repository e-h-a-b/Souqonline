<!-- CSS خاص بـ Babylon.js -->
<style>
/* اجبار النموذج ثلاثي الأبعاد على الظهور حتى لو مش active */
.media-item[data-type="3d_model"] {
    display: block !important;
    opacity: 1 !important;
    position: relative !important;
    z-index: 10 !important;
}

.main-media-wrapper > .media-item:not(.active) {
    display: none;
}

/* لكن الـ 3D model يفضل مفتوح دايمًا */
.main-media-wrapper > .media-item[data-type="3d_model"] {
    display: block !important;
}
    /* حاوية Babylon.js */
    .babylon-container {
        width: 100%;
        height: 300px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        position: relative;
        border-radius: 10px;
        overflow: hidden;
    }

    .babylon-canvas {
        width: 100%;
        height: 100%;
        outline: none;
    }

    /* تحميل Babylon.js */
    .babylon-loading {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        z-index: 10;
    }

    .loading-spinner {
        width: 50px;
        height: 50px;
        border: 4px solid rgba(255,255,255,0.3);
        border-top: 4px solid white;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    /* أزرار التحكم */
    .babylon-controls {
        position: absolute;
        bottom: 15px;
        left: 15px;
        display: flex;
        gap: 8px;
        z-index: 20;
    }

    .babylon-controls button {
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

    .babylon-controls button:hover {
        background: rgba(0,0,0,0.9);
        transform: scale(1.1);
    }

    /* شارة معلومات النموذج */
    .babylon-info {
        position: absolute;
        top: 10px;
        left: 10px;
        background: rgba(0,0,0,0.7);
        color: white;
        padding: 5px 10px;
        border-radius: 5px;
        font-size: 0.75rem;
        z-index: 20;
    }

    /* البديل المحسن */
    .enhanced-fallback {
        background: white;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        height: 100%;
        display: flex;
        flex-direction: column;
    }

    /* باقي الأنماط تبقى كما هي */
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

    /* باقي الأنماط تبقى كما هي */
    /* ... */
</style>
  
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
	float: inline-start;
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
</style>

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

<!-- تحميل مكتبة Babylon.js -->
<script src="https://cdn.babylonjs.com/babylon.js"></script>
<script src="https://cdn.babylonjs.com/loaders/babylonjs.loaders.min.js"></script>
<script src="https://cdn.babylonjs.com/inspector/babylon.inspector.bundle.js"></script>

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

    <!-- قسم المنتجات مع Babylon.js -->
                        <div class="products-grid" id="productsGrid">
                            <?php foreach ($filteredProducts as $product): ?>
                                <div class="product-card <?= $product['store_type'] === 'customer' ? 'customer-store-product' : '' ?>" 
                                     style="<?= $product['store_type'] === 'customer' ? 'border: 3px solid #8b5cf6; background: linear-gradient(135deg, #faf5ff, #f3e8ff);' : '' ?>">
                                    <?php 
// جلب وسائط المنتج
$productMedia = getProductMedia($product['id']);
// للتصحيح: اطبع الوسائط لتتأكد
// التحقق من وجود الملفات
foreach ($productMedia as $index => $media) {
    if ($media['media_type'] === '3d_model') {
        $filePath = $media['media_url'];
        $fullPath = $_SERVER['DOCUMENT_ROOT'] . '/' . $filePath;
        $fileExists = file_exists($fullPath);
        
        echo "<!-- DEBUG: 3D Model Check -->";
        echo "<!-- File: {$filePath} -->";
        echo "<!-- Full Path: {$fullPath} -->";
        echo "<!-- Exists: " . ($fileExists ? 'YES' : 'NO') . " -->";
        echo "<!-- File Size: " . ($fileExists ? filesize($fullPath) : '0') . " bytes -->";
    }
}
?>
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
                
                <!-- قسم الوسائط المتعددة مع Babylon.js -->
<!-- في قسم الوسائط المتعددة -->
<div class="product-media-container">
    <?php if (!empty($productMedia)): ?>
<!-- داخل حلقة عرض المنتجات - بعد جلب الوسائط -->
<?php 
$mediaList = getProductMedia($product['id']);
// لو مفيش وسائط، نستخدم الصورة الرئيسية كبديل
if (empty($mediaList)) {
    $mediaList = [['media_type' => 'image', 'media_url' => $product['main_image'] ?? 'assets/images/placeholder.jpg']];
}
?>

<div class="product-media-gallery">
    <div class="main-media-wrapper">
<?php foreach ($mediaList as $index => $media): 
    // لو الوسيط 3D model → نفعّله فورًا حتى لو مش الأول
    $isActive = ($index === 0 || $media['media_type'] === '3d_model') ? 'active' : '';
?>
    <div class="media-item <?= $isActive ?>"
         data-type="<?= $media['media_type'] ?>"
         data-src="<?= htmlspecialchars($media['media_url']) ?>">
                 
                <?php if ($media['media_type'] === 'image'): ?>
                    <img src="<?= htmlspecialchars($media['media_url']) ?>" 
                         alt="<?= htmlspecialchars($product['title']) ?>" loading="lazy">

                <?php elseif ($media['media_type'] === 'video'): ?>
                    <video <?= $media['autoplay'] ? 'autoplay' : '' ?> 
                           <?= $media['loop'] ? 'loop' : '' ?> 
                           <?= $media['controls'] ? 'controls' : '' ?> 
                           muted playsinline>
                        <source src="<?= htmlspecialchars($media['media_url']) ?>" type="video/mp4">
                        المتصفح لا يدعم الفيديو.
                    </video>

                <?php elseif ($media['media_type'] === 'gif'): ?>
                    <img src="<?= htmlspecialchars($media['media_url']) ?>" 
                         alt="GIF" class="gif-player">

                <?php elseif ($media['media_type'] === '3d_model'): ?>
                    <!-- هنا اللي كان ناقصك بالضبط! -->
                    <div class="babylon-container" id="babylon-<?= $product['id'] ?>-<?= $index ?>">
                        <div class="babylon-loading">
                            <div class="loading-spinner"></div>
                            <p>جاري تحميل النموذج ثلاثي الأبعاد...</p>
                        </div>
                        <canvas class="babylon-canvas" touch-action="none"></canvas>

                        <!-- أزرار التحكم -->
                        <div class="babylon-controls">
                            <button onclick="rotateBabylonModel(<?= $product['id'] ?>, <?= $index ?>)">
                                Rotate
                            </button>
                            <button onclick="resetBabylonModel(<?= $product['id'] ?>, <?= $index ?>)">
                                Reset
                            </button>
                            <button id="babylon-rotate-btn-<?= $product['id'] ?>-<?= $index ?>" 
                                    onclick="toggleBabylonAutoRotate(<?= $product['id'] ?>, <?= $index ?>)">
                                Play
                            </button>
                        </div>

                         
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- الثومبنيلز (اختياري) -->
    <?php if (count($mediaList) > 1): ?>
    <div class="thumbnails">
        <?php foreach ($mediaList as $index => $media): ?>
            <div class="thumbnail-item <?= $index === 0 ? 'active' : '' ?>" 
                 onclick="switchMedia(this, <?= $index ?>)">
                <img src="<?= htmlspecialchars($media['thumbnail_url'] ?? $media['media_url']) ?>" 
                     alt="thumb">
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
    <?php else: ?>
        <!-- الصورة الافتراضية -->
        <a href="product.php?id=<?= $product['id'] ?>" class="product-image">
            <img src="<?= htmlspecialchars($product['main_image'] ?: 'assets/images/placeholder.jpg') ?>" 
                 alt="<?= htmlspecialchars($product['title']) ?>"
                 onerror="this.src='assets/images/placeholder.jpg'">
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


<!-- JavaScript الخاص بـ Babylon.js -->
<script>
// ✅ نظام Babylon.js المتكامل مع تصحيح الأخطاء
console.log('🚀 Starting Babylon.js System...');

// حالة التطبيق العالمية
window.babylonState = {
    engines: {},
    scenes: {},
    autoRotate: {},
    models: {},
    initialized: false
};

// ========================
// 1. دالة التهيئة المحسنة
// ========================
function initializeBabylonEngine(containerId) {
    console.log('🔄 Initializing Babylon engine for:', containerId);
    
    const container = document.getElementById(containerId);
    if (!container) {
        console.error('❌ Container not found:', containerId);
        return null;
    }

    const canvas = container.querySelector('.babylon-canvas');
    const loading = container.querySelector('.babylon-loading');
    
    if (!canvas) {
        console.error('❌ Canvas not found in container:', containerId);
        return null;
    }

    try {
        // إنشاء محرك Babylon.js
        const engine = new BABYLON.Engine(canvas, true, {
            preserveDrawingBuffer: true,
            stencil: true,
            antialias: true
        });

        // إنشاء المشهد
        const scene = new BABYLON.Scene(engine);
        scene.clearColor = new BABYLON.Color4(0.1, 0.1, 0.15, 1.0);

        // إضاءة محيطة
        const hemisphericLight = new BABYLON.HemisphericLight("light1", 
            new BABYLON.Vector3(0, 1, 0), scene);
        hemisphericLight.intensity = 0.7;

        // إضاءة اتجاهية
        const directionalLight = new BABYLON.DirectionalLight("light2", 
            new BABYLON.Vector3(0, -1, 0), scene);
        directionalLight.intensity = 0.5;

        // كاميرا ArcRotate
        const camera = new BABYLON.ArcRotateCamera("camera", 
            -Math.PI / 2, Math.PI / 2.5, 5, 
            BABYLON.Vector3.Zero(), scene);
        camera.attachControl(canvas, true);
        camera.lowerRadiusLimit = 1;
        camera.upperRadiusLimit = 20;
        camera.wheelPrecision = 50;

        // حفظ الحالة
        window.babylonState.engines[containerId] = engine;
        window.babylonState.scenes[containerId] = scene;
        window.babylonState.autoRotate[containerId] = false;

        // render loop
        engine.runRenderLoop(function () {
            scene.render();
        });

        // إعادة التحجيم
        window.addEventListener("resize", function () {
            engine.resize();
        });

        console.log('✅ Babylon engine initialized for:', containerId);
        return { engine, scene, camera };
        
    } catch (error) {
        console.error('❌ Error initializing Babylon.js:', error);
        if (loading) {
            loading.innerHTML = `
                <div style="text-align: center; color: white;">
                    <i class="fas fa-exclamation-triangle fa-2x"></i>
                    <p>خطأ في تحميل النموذج</p>
                    <small>${error.message}</small>
                </div>
            `;
        }
        return null;
    }
}

// ========================
// 2. تحميل النماذج مع تصحيح الأخطاء
// ========================
function loadBabylonModel(containerId, modelUrl) {
    console.log('📦 Loading model for:', containerId, 'URL:', modelUrl);
    
    const scene = window.babylonState.scenes[containerId];
    if (!scene) {
        console.error('❌ Scene not found for:', containerId);
        return;
    }

    const loading = document.querySelector(`#${containerId} .babylon-loading`);

    // استخدام SceneLoader لتحميل النموذج
    BABYLON.SceneLoader.ImportMesh("", modelUrl, "", scene, 
        function (meshes) {
            console.log('✅ Model loaded successfully:', meshes.length, 'meshes for', containerId);
            
            if (loading) {
                loading.style.display = 'none';
            }

            // حفظ المرجع للنموذج
            window.babylonState.models[containerId] = meshes[0];
            
            // ضبط النموذج
            if (meshes.length > 0) {
                const rootMesh = meshes[0];
                
                // حساب الحجم المناسب
                const boundingInfo = rootMesh.getBoundingInfo();
                const boundingBox = boundingInfo.boundingBox;
                const size = boundingBox.maximum.subtract(boundingBox.minimum);
                const maxDimension = Math.max(size.x, size.y, size.z);
                
                if (maxDimension > 0) {
                    const scale = 3 / maxDimension;
                    rootMesh.scaling = new BABYLON.Vector3(scale, scale, scale);
                }
                
                rootMesh.position = BABYLON.Vector3.Zero();
            }

        }, 
        function (progress) {
            if (progress.lengthComputable) {
                const percent = (progress.loaded / progress.total * 100).toFixed(0);
                console.log(`📊 Loading progress for ${containerId}: ${percent}%`);
                
                if (loading) {
                    loading.querySelector('p').textContent = `جاري تحميل النموذج... ${percent}%`;
                }
            }
        },
        function (scene, message, exception) {
            console.error('❌ Error loading model for', containerId, ':', message, exception);
            showBabylonError(containerId, 'فشل تحميل النموذج: ' + message);
        }
    );
}

// ========================
// 3. دوال التحكم مع التدوير التلقائي الافتراضي
// ========================
function rotateBabylonModel(productId, index) {
    const containerId = `babylon-${productId}-${index}`;
    const mesh = window.babylonState.models[containerId];
    if (mesh) {
        mesh.rotation.y += Math.PI / 4;
    }
}

function resetBabylonModel(productId, index) {
    const containerId = `babylon-${productId}-${index}`;
    const scene = window.babylonState.scenes[containerId];
    if (!scene) return;

    const camera = scene.activeCamera;
    const mesh = window.babylonState.models[containerId];

    if (camera && camera instanceof BABYLON.ArcRotateCamera) {
        camera.alpha = -Math.PI / 2;
        camera.beta = Math.PI / 2.5;
        camera.radius = 5;
    }
    if (mesh) {
        mesh.rotation = BABYLON.Vector3.Zero();
    }
}

function toggleBabylonAutoRotate(productId, index) {
    const containerId = `babylon-${productId}-${index}`;
    const btn = document.getElementById(`babylon-rotate-btn-${productId}-${index}`);
    const scene = window.babylonState.scenes[containerId];

    if (!scene) return;

    // تبديل الحالة
    const current = window.babylonState.autoRotate[containerId] || false;
    window.babylonState.autoRotate[containerId] = !current;

    // تحديث أيقونة الزر
    if (btn) {
        btn.innerHTML = !current 
            ? '<i class="fas fa-pause"></i>' 
            : '<i class="fas fa-play"></i>';
    }

    // إزالة أي دوران سابق
    scene.onBeforeRenderObservable.clear();

    // إذا مفعّل → ندور تلقائيًا
    if (!current) {
        scene.onBeforeRenderObservable.add(() => {
            const mesh = window.babylonState.models[containerId];
            if (mesh) {
                mesh.rotation.y += 0.008; // سرعة ناعمة جدًا
            }
        });
    }
}

// ========================
// تحميل النموذج + تفعيل التدوير التلقائي تلقائيًا
// ========================
function loadBabylonModel(containerId, modelUrl) {
    console.log('تحميل النموذج:', containerId, modelUrl);

    const scene = window.babylonState.scenes[containerId];
    if (!scene) return;

    const loading = document.querySelector(`#${containerId} .babylon-loading`);

    BABYLON.SceneLoader.ImportMesh("", modelUrl, "", scene, 
        function (meshes) {
            console.log('تم تحميل النموذج بنجاح:', containerId);

            if (loading) loading.style.display = 'none';

            if (meshes.length > 0) {
                const rootMesh = meshes[0];
                window.babylonState.models[containerId] = rootMesh;

                // ضبط الحجم
                const boundingInfo = rootMesh.getBoundingInfo();
                const size = boundingInfo.boundingBox.maximum.subtract(boundingInfo.boundingBox.minimum);
                const maxDimension = Math.max(size.x, size.y, size.z);
                if (maxDimension > 0) {
                    const scale = 3 / maxDimension;
                    rootMesh.scaling = new BABYLON.Vector3(scale, scale, scale);
                }
                rootMesh.position = BABYLON.Vector3.Zero();
            }

            // تفعيل التدوير التلقائي فورًا من غير ما يعتمد على زر
            startAutoRotate(containerId);
        },
        null,
        function (scene, message) {
            console.error('فشل تحميل النموذج:', message);
            showBabylonError(containerId, 'فشل تحميل النموذج');
        }
    );
}

// دالة منفصلة لتشغيل التدوير التلقائي (نستدعيها في كل مكان)
function startAutoRotate(containerId) {
    const scene = window.babylonState.scenes[containerId];
    const mesh = window.babylonState.models[containerId];
    if (!scene || !mesh) return;

    // نوقف أي دوران سابق (آمن)
    scene.onBeforeRenderObservable.clear();

    // نبدأ دوران جديد
    scene.onBeforeRenderObservable.add(() => {
        mesh.rotation.y += 0.008; // دوران سلس
    });

    // نحفظ الحالة + نغيّر أيقونة الزر إلى Pause
    window.babylonState.autoRotate[containerId] = true;
    const btn = document.getElementById(`babylon-rotate-btn-${containerId.split('-')[1]}-${containerId.split('-')[2]}`);
    if (btn) btn.innerHTML = '<i class="fas fa-pause"></i>';
}

// دالة إيقاف التدوير
function stopAutoRotate(containerId) {
    const scene = window.babylonState.scenes[containerId];
    if (scene) {
        scene.onBeforeRenderObservable.clear();
    }
    window.babylonState.autoRotate[containerId] = false;
    const btn = document.getElementById(`babylon-rotate-btn-${containerId.split('-')[1]}-${containerId.split('-')[2]}`);
    if (btn) btn.innerHTML = '<i class="fas fa-play"></i>';
}

// تبديل التدوير (Play / Pause)
function toggleBabylonAutoRotate(productId, index) {
    const containerId = `babylon-${productId}-${index}`;
    const currentlyRotating = window.babylonState.autoRotate[containerId] || false;

    if (currentlyRotating) {
        stopAutoRotate(containerId);
    } else {
        startAutoRotate(containerId);
    }
}

// Reset بدون ما يمسح التدوير التلقائي
function resetBabylonModel(productId, index) {
    const containerId = `babylon-${productId}-${index}`;
    const scene = window.babylonState.scenes[containerId];
    if (!scene) return;

    const camera = scene.activeCamera;
    const mesh = window.babylonState.models[containerId];

    if (camera && camera instanceof BABYLON.ArcRotateCamera) {
        camera.alpha = -Math.PI / 2;
        camera.beta = Math.PI / 2.5;
        camera.radius = 5;
    }
    if (mesh) {
        mesh.rotation = BABYLON.Vector3.Zero();
    }

    // نعيد تشغيل التدوير بعد الـ Reset
    setTimeout(() => startAutoRotate(containerId), 100);
}
// ========================
// 4. التهيئة التلقائية المحسنة
// ========================
function initializeAllBabylonModels() {
    console.log('🔍 Searching for Babylon.js containers...');
    
    const modelContainers = document.querySelectorAll('.babylon-container');
    console.log(`🎨 Found ${modelContainers.length} Babylon.js containers`);
    
    // للتصحيح: إظهار جميع الحاويات
    modelContainers.forEach(container => {
        console.log('📦 Container found:', container.id, container);
        
        const mediaItem = container.closest('.media-item');
        const modelUrl = mediaItem?.getAttribute('data-src');
        const mediaType = mediaItem?.getAttribute('data-type');
        
        console.log('📊 Container details:', {
            id: container.id,
            mediaType: mediaType,
            modelUrl: modelUrl,
            hasMediaItem: !!mediaItem
        });
        
        if (mediaType === '3d_model' && modelUrl) {
            console.log('🚀 Initializing 3D model:', container.id);
            
            // تهيئة المحرك
            const engineInfo = initializeBabylonEngine(container.id);
            
            if (engineInfo) {
                // تحميل النموذج
                setTimeout(() => {
                    loadBabylonModel(container.id, modelUrl);
                }, 1000);
            }
        } else {
            console.warn('⚠️ Skipping container - not a 3D model:', container.id);
        }
    });
    
    window.babylonState.initialized = true;
    console.log('✅ Babylon.js initialization completed');
}

// ========================
// 5. بدء النظام
// ========================
// بدلاً من الاعتماد على DOMContentLoaded + setTimeout
// نستخدم MutationObserver عشان نراقب إضافة المنتجات ديناميكيًا
document.addEventListener('DOMContentLoaded', function() {
    console.log('جاري مراقبة إضافة النماذج ثلاثية الأبعاد...');

    const observer = new MutationObserver(function(mutations) {
        let shouldInit = false;
        
        mutations.forEach(mutation => {
            mutation.addedNodes.forEach(node => {
                if (node.nodeType === 1) { // Element node
                    if (node.matches?.('.babylon-container') || node.querySelector?.('.babylon-container')) {
                        shouldInit = true;
                    }
                }
            });
        });

        if (shouldInit) {
            console.log('تم اكتشاف حاوية Babylon.js جديدة! جاري التهيئة...');
            setTimeout(initializeAllBabylonModels, 500);
        }
    });

    // نراقب الـ products-grid لأنه هو اللي بيضاف فيه المنتجات
    const productsGrid = document.getElementById('productsGrid');
    if (productsGrid) {
        observer.observe(productsGrid, {
            childList: true,
            subtree: true
        });
    }

    // تهيئة أولية في حالة كانت المنتجات موجودة من الأول
    setTimeout(initializeAllBabylonModels, 1000);
});

// دالة تحميل النموذج
function downloadModel(url, filename) {
    if (!url) {
        alert('❌ لا يوجد رابط للتحميل');
        return;
    }
    
    const link = document.createElement('a');
    link.href = url;
    link.download = (filename || 'model_3d') + '.glb';
    link.target = '_blank';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    console.log('📥 Download started:', filename);
}

// معالج الأخطاء
function showBabylonError(containerId, message) {
    const container = document.getElementById(containerId);
    if (!container) return;
    
    container.innerHTML = `
        <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #ff6b6b, #ee5a52); color: white; text-align: center; padding: 20px; border-radius: 10px;">
            <div>
                <i class="fas fa-exclamation-triangle fa-3x" style="margin-bottom: 15px;"></i>
                <p style="font-size: 1.1rem; font-weight: 600; margin: 10px 0;">${message}</p>
                <button onclick="location.reload()" style="background: white; color: #ee5a52; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; font-weight: 600;">
                    <i class="fas fa-redo"></i> إعادة المحاولة
                </button>
            </div>
        </div>
    `;
}

// دالة تبديل الوسائط
function switchMedia(thumbnail, index) {
    const gallery = thumbnail.closest('.product-media-gallery');
    const mediaItems = gallery.querySelectorAll('.media-item');
    const thumbnails = gallery.querySelectorAll('.thumbnail-item');
    
    // إخفاء جميع الوسائط
    mediaItems.forEach(item => item.classList.remove('active'));
    thumbnails.forEach(thumb => thumb.classList.remove('active'));
    
    // إظهار الوسيط المحدد
    mediaItems[index].classList.add('active');
    thumbnail.classList.add('active');
}

// التصدير للنطاق العام
window.rotateBabylonModel = rotateBabylonModel;
window.resetBabylonModel = resetBabylonModel;
window.toggleBabylonAutoRotate = toggleBabylonAutoRotate;
window.downloadModel = downloadModel;
window.switchMedia = switchMedia;
window.initializeAllBabylonModels = initializeAllBabylonModels;

console.log('✅ Babylon.js System Script Loaded Successfully');
</script>