<?php
// ملف السطر الذكي الجانبي
?>
<!-- سطر الأوامر الذكي الجانبي -->
<div class="smart-command-sidebar">
    <div class="command-header">
        <i class="fas fa-robot"></i>
        <span>المساعد الذكي</span>
        <button class="close-sidebar">&times;</button>
    </div>
    
    <div class="command-conversation">
        <div class="message bot-message">
            <div class="message-content">
                مرحبا! أنا المساعد الذكي للمشتريات. كيف يمكنني مساعدتك؟
                <div class="message-time">الآن</div>
            </div>
        </div>
    </div>
     
    <div class="quick-suggestions">
    <div class="suggestion" data-command="تتبع سعر iPhone 15 عندما يصبح أقل من 3000">تتبع انخفاض السعر</div>
    <div class="suggestion" data-command="اشترِ حليب كل أسبوع">جدولة أسبوعية</div>
    <div class="suggestion" data-command="اشترِ قائمة المشتريات الشهرية">قوائم المشتريات</div>
    <div class="suggestion" data-command="اعرض إحصائيات المشتريات">الإحصائيات</div>
</div>
    <div class="command-input-container">
        <input type="text" class="command-input" placeholder="اكتب أمرك هنا...">
        <button class="send-command">
            <i class="fas fa-paper-plane"></i>
        </button>
    </div>
</div>

<style>
.smart-command-sidebar {
    position: fixed;
    top: 0;
    left: -400px;
    width: 380px;
    height: 100vh;
    background: white;
    box-shadow: 2px 0 15px rgba(0,0,0,0.1);
    z-index: 10000;
    transition: left 0.3s ease;
    display: flex;
    flex-direction: column;
}

.smart-command-sidebar.active {
    left: 0;
}

.command-header {
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    color: white;
    padding: 1rem 1.5rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-weight: 600;
}

.close-sidebar {
    background: none;
    border: none;
    color: white;
    font-size: 1.5rem;
    cursor: pointer;
}

.command-conversation {
    flex: 1;
    padding: 1rem;
    overflow-y: auto;
    background: #f8fafc;
}

.message {
    margin-bottom: 1rem;
    display: flex;
}

.bot-message {
    justify-content: flex-start;
}

.user-message {
    justify-content: flex-end;
}

.message-content {
    max-width: 80%;
    padding: 0.75rem 1rem;
    border-radius: 18px;
    position: relative;
}

.bot-message .message-content {
    background: white;
    border: 1px solid #e2e8f0;
}

.user-message .message-content {
    background: #3b82f6;
    color: white;
}

.message-time {
    font-size: 0.7rem;
    opacity: 0.7;
    margin-top: 0.25rem;
}

.quick-suggestions {
    padding: 1rem;
    border-top: 1px solid #e2e8f0;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.suggestion {
    padding: 0.5rem 1rem;
    background: #f1f5f9;
    border-radius: 20px;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.2s;
    text-align: center;
}

.suggestion:hover {
    background: #e2e8f0;
    transform: translateY(-1px);
}

.command-input-container {
    padding: 1rem;
    border-top: 1px solid #e2e8f0;
    display: flex;
    gap: 0.5rem;
}

.command-input {
    flex: 1;
    padding: 0.75rem 1rem;
    border: 1px solid #d1d5db;
    border-radius: 25px;
    outline: none;
}

.command-input:focus {
    border-color: #3b82f6;
}

.send-command {
    background: #3b82f6;
    color: white;
    border: none;
    width: 45px;
    height: 45px;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.send-command:hover {
    background: #2563eb;
    transform: scale(1.05);
}

/* زر فتح السطر الذكي */
.smart-command-trigger {
    position: fixed;
    bottom: 90px;
    left: 25px;
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    color: white;
    border: none;
    border-radius: 50%;
    cursor: pointer;
    box-shadow: 0 4px 20px rgba(59, 130, 246, 0.4);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    transition: all 0.3s ease;
}

.smart-command-trigger:hover {
    transform: scale(1.1);
    box-shadow: 0 6px 25px rgba(59, 130, 246, 0.6);
}

@media (max-width: 768px) {
    .smart-command-sidebar {
        width: 100vw;
        left: -100vw;
    }
    
    .smart-command-trigger {
        bottom: 20px;
        left: 20px;
        width: 50px;
        height: 50px;
        font-size: 1.2rem;
    }
}
</style>

<script>
// إدارة السطر الذكي الجانبي
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.querySelector('.smart-command-sidebar');
    const trigger = document.querySelector('.smart-command-trigger');
    const closeBtn = document.querySelector('.close-sidebar');
    const commandInput = document.querySelector('.command-input');
    const sendBtn = document.querySelector('.send-command');
    const conversation = document.querySelector('.command-conversation');
    const suggestions = document.querySelectorAll('.suggestion');
    
    // فتح وإغلاق السطر الذكي
    trigger.addEventListener('click', () => {
        sidebar.classList.add('active');
    });
    
    closeBtn.addEventListener('click', () => {
        sidebar.classList.remove('active');
    });
    
    // إرسال أمر
    function sendCommand(commandText) {
        if (!commandText.trim()) return;
        
        // إضافة رسالة المستخدم
        addMessage(commandText, 'user');
        
        // مسح حقل الإدخال
        commandInput.value = '';
        
        // محاكاة استجابة الذكاء الاصطناعي
        setTimeout(() => {
            const response = processCommand(commandText);
            addMessage(response, 'bot');
        }, 1000);
    }
    
    // إرسال بالزر
    sendBtn.addEventListener('click', () => {
        sendCommand(commandInput.value);
    });
    
    // إرسال بالإنتر
    commandInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            sendCommand(commandInput.value);
        }
    });
    
    // الاقتراحات السريعة
    suggestions.forEach(suggestion => {
        suggestion.addEventListener('click', () => {
            sendCommand(suggestion.getAttribute('data-command'));
        });
    });
    
    // إضافة رسالة للمحادثة
    function addMessage(text, sender) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${sender}-message`;
        
        const contentDiv = document.createElement('div');
        contentDiv.className = 'message-content';
        contentDiv.innerHTML = `
            ${text}
            <div class="message-time">${new Date().toLocaleTimeString('ar-EG', {hour: '2-digit', minute:'2-digit'})}</div>
        `;
        
        messageDiv.appendChild(contentDiv);
        conversation.appendChild(messageDiv);
        
        // Scroll to bottom
        conversation.scrollTop = conversation.scrollHeight;
    }
    
    // معالجة الأوامر (محاكاة الذكاء الاصطناعي)
    function processCommand(command) {
        command = command.toLowerCase();
        
        if (command.includes('تتبع') || command.includes('سعر') || command.includes('انخفاض')) {
            return `فهمت أنك تريد تتبع سعر منتج. سأقوم بمراقبة السعر وإعلامك عند انخفاضه. هل تريد إضافة أي شروط أخرى؟`;
        }
        else if (command.includes('اشتر') || command.includes('شراء') || command.includes('اطلب')) {
            return `تم فهم طلب الشراء. سأقوم بتنفيذه حسب الشروط المحددة. هل تريد جدولته أم تنفيذه فوراً؟`;
        }
        else if (command.includes('جدول') || command.includes('موعد') || command.includes('وقت')) {
            return `ممتاز! سأقوم بجدولة عملية الشراء في الوقت المحدد. الرجاء تأكيد التفاصيل.`;
        }
        else {
            return `فهمت أنك تريد: "${command}". سأقوم بتحليل طلبك وإنشاء الأمر المناسب. هل يمكنك تقديم المزيد من التفاصيل؟`;
        }
    }
    
    // إنشاء زر التشغيل إذا لم يكن موجوداً
    if (!trigger) {
        const triggerBtn = document.createElement('button');
        triggerBtn.className = 'smart-command-trigger';
        triggerBtn.innerHTML = '<i class="fas fa-robot"></i>';
        document.body.appendChild(triggerBtn);
        
        triggerBtn.addEventListener('click', () => {
            sidebar.classList.add('active');
        });
    }
	// معالجة الأوامر المتقدمة
function processAdvancedCommand(command) {
    command = command.toLowerCase();
    
    // إرسال الأمر للمعالجة في الخادم
    fetch('smart_command_handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'command=' + encodeURIComponent(command)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            addMessage(data.message, 'bot');
            
            // إذا تم إضافة منتج للسلة، تحديث عداد السلة
            if (data.cart_updated) {
                updateCartCounter();
            }
        } else {
            addMessage('❌ ' + data.message, 'bot');
        }
    })
    .catch(error => {
        addMessage('❌ حدث خطأ في معالجة الأمر', 'bot');
        console.error('Error:', error);
    });
}

// تحديث معالجة الأوامر الحالية
function processCommand(command) {
    command = command.toLowerCase();
    
    // الأوامر البسيطة الحالية
    const simpleCommands = {
        'تتبع': `فهمت أنك تريد تتبع سعر منتج. سأقوم بمراقبة السعر وإعلامك عند انخفاضه.`,
        'اشتر': `تم فهم طلب الشراء. سأقوم بتنفيذه حسب الشروط المحددة.`,
        'جدول': `ممتاز! سأقوم بجدولة عملية الشراء في الوقت المحدد.`
    };
    
    // إذا كان الأمر معقداً، استخدم المعالج المتقدم
    if (command.includes('عندما') || command.includes('إذا') || 
        command.includes('كل') || command.includes('قائمة') ||
        command.match(/\d+/)) {
        return processAdvancedCommand(command);
    }
    
    // المعالجة البسيطة للأوامر القصيرة
    for (const [key, response] of Object.entries(simpleCommands)) {
        if (command.includes(key)) {
            return response;
        }
    }
    
    return `فهمت أنك تريد: "${command}". سأقوم بتحليل طلبك وإنشاء الأمر المناسب. هل يمكنك تقديم المزيد من التفاصيل؟`;
}

// تحديث عداد السلة
function updateCartCounter() {
    // كود تحديث عداد السلة حسب نظامك
    console.log('Cart updated - refresh counter');
}
});
</script>