<!-- نموذج إضافة رصيد -->
<div id="addBalanceModal" class="modal">
    <div class="modal-content">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h3 style="margin: 0;">إضافة رصيد للمحفظة</h3>
            <span onclick="closeAddBalanceModal()" style="cursor: pointer; font-size: 1.5rem;">&times;</span>
        </div>

        <form method="POST" action="wallets.php">
            <!-- === بادئة add_ === -->
            <input type="hidden" name="add_action" value="add_balance">

            <div class="form-group">
                <label>اختر العميل</label>
                <select name="add_customer_id" id="add_customer_id" class="form-control" required>
                    <option value="">اختر عميل...</option>
                    <?php foreach ($customers as $customer): ?>
                        <option value="<?= $customer['id'] ?>">
                            <?= htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']) ?>
                            (<?= htmlspecialchars($customer['email']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>المبلغ (ج.م)</label>
                <input type="number" name="add_amount" class="form-control" step="0.01" min="0.01" required
                       placeholder="أدخل المبلغ المراد إضافته">
            </div>

            <div class="form-group">
                <label>الوصف</label>
                <input type="text" name="add_description" class="form-control" required
                       placeholder="سبب إضافة الرصيد (مثال: شحن يدوي، مكافأة)">
            </div>

            <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                <button type="button" onclick="closeAddBalanceModal()" class="btn btn-secondary" style="flex: 1;">إلغاء</button>
                <button type="submit" class="btn btn-primary" style="flex: 1;">
                    إضافة الرصيد
                </button>
            </div>
        </form>
    </div>
</div>


<!-- نموذج خصم رصيد (غرامة) -->
<div id="deductBalanceModal" class="modal">
    <div class="modal-content">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h3 style="margin: 0; color: #dc2626;">خصم رصيد من المحفظة (غرامة)</h3>
            <span onclick="closeDeductBalanceModal()" style="cursor: pointer; font-size: 1.5rem;">&times;</span>
        </div>

        <form method="POST" action="wallets.php">
            <!-- === بادئة deduct_ === -->
            <input type="hidden" name="deduct_action" value="deduct_balance">

            <div class="form-group">
                <label>اختر العميل</label>
                <select name="deduct_customer_id" id="deduct_customer_id" class="form-control" required>
                    <option value="">اختر عميل...</option>
                    <?php foreach ($customers as $customer): ?>
                        <option value="<?= $customer['id'] ?>">
                            <?= htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']) ?>
                            (<?= htmlspecialchars($customer['email']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label style="color: #dc2626;">المبلغ المراد خصمه (ج.م)</label>
                <input type="number" name="deduct_amount" class="form-control" step="0.01" min="0.01" required
                       placeholder="أدخل مبلغ الغرامة">
            </div>

            <div class="form-group">
                <label>سبب الخصم/الغرامة</label>
                <input type="text" name="deduct_description" class="form-control" required
                       placeholder="مثال: غرامة تأخير، مخالفة، تعويض">
            </div>

            <div style="background: #fee2e2; padding: 15px; border-radius: 8px; margin: 15px 0;">
                <p style="margin: 0; color: #991b1b; font-size: 14px;">
                    تنبيه: سيتم خصم المبلغ من رصيد العميل
                </p>
            </div>

            <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                <button type="button" onclick="closeDeductBalanceModal()" class="btn btn-secondary" style="flex: 1;">إلغاء</button>
                <button type="submit" class="btn btn-danger" style="flex: 1;">
                    تأكيد الخصم
                </button>
            </div>
        </form>
    </div>
</div>