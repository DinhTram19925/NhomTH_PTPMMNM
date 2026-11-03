
<?php
$promotion = $promotion ?? null;
$isEdit = !empty($promotion);

function promotionFormEscape($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$action = $isEdit
    ? 'index.php?c=promotion&a=update'
    : 'index.php?c=promotion&a=store';
?>

<style>
.promotion-form-page {
    max-width: 850px;
    margin: 20px auto;
    padding: 20px;
    color: #292929;
}

.promotion-form-card {
    background: #fff;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 3px 15px rgba(0,0,0,.06);
}

.promotion-form-page h2 {
    margin: 0 0 8px;
}

.promotion-form-description {
    color: #777;
    margin-bottom: 25px;
}

.promotion-form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 18px;
}

.promotion-form-group {
    display: flex;
    flex-direction: column;
    gap: 7px;
}

.promotion-form-group.full {
    grid-column: 1 / -1;
}

.promotion-form-group label {
    font-weight: 600;
    font-size: 14px;
}

.promotion-form-group input,
.promotion-form-group select {
    width: 100%;
    box-sizing: border-box;
    padding: 11px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
}

.promotion-form-group input:focus,
.promotion-form-group select:focus {
    outline: none;
    border-color: #d6a12a;
}

.promotion-form-hint {
    font-size: 12px;
    color: #888;
}

.promotion-form-checkbox {
    display: flex;
    align-items: center;
    gap: 9px;
    margin-top: 20px;
}

.promotion-form-checkbox input {
    width: 17px;
    height: 17px;
}

.promotion-form-actions {
    display: flex;
    gap: 10px;
    margin-top: 25px;
}

.promotion-form-btn {
    display: inline-block;
    padding: 11px 20px;
    border: none;
    border-radius: 6px;
    text-decoration: none;
    cursor: pointer;
    font-size: 14px;
}

.promotion-form-save {
    background: #d6a12a;
    color: #fff;
    font-weight: 600;
}

.promotion-form-cancel {
    background: #eee;
    color: #333;
}

@media(max-width: 600px) {
    .promotion-form-grid {
        grid-template-columns: 1fr;
    }

    .promotion-form-group.full {
        grid-column: auto;
    }
}
</style>

<div class="promotion-form-page">

    <div class="promotion-form-card">

        <h2>
            <?= $isEdit ? '✏️ Chỉnh sửa voucher' : '🎟️ Thêm voucher mới' ?>
        </h2>

        <p class="promotion-form-description">
            Nhập thông tin chương trình khuyến mãi.
        </p>

        <form method="POST" action="<?= $action ?>">

            <?php if ($isEdit): ?>
                <input type="hidden"
                       name="id"
                       value="<?= (int)$promotion['id'] ?>">
            <?php endif; ?>

            <div class="promotion-form-grid">

                <div class="promotion-form-group">
                    <label>Mã voucher *</label>
                    <input type="text"
                           name="code"
                           required
                           maxlength="30"
                           pattern="[A-Za-z0-9_-]{3,30}"
                           placeholder="VD: SALE10"
                           value="<?= promotionFormEscape($promotion['code'] ?? '') ?>">
                    <span class="promotion-form-hint">
                        3-30 ký tự, không dấu, không khoảng trắng.
                    </span>
                </div>

                <div class="promotion-form-group">
                    <label>Tên chương trình *</label>
                    <input type="text"
                           name="name"
                           required
                           maxlength="255"
                           placeholder="VD: Khuyến mãi cuối tuần"
                           value="<?= promotionFormEscape($promotion['name'] ?? '') ?>">
                </div>

                <div class="promotion-form-group">
                    <label>Loại giảm giá *</label>
                    <select name="discount_type" id="discount_type" required>
                        <option value="Percent"
                            <?= ($promotion['discount_type'] ?? 'Percent') === 'Percent' ? 'selected' : '' ?>>
                            Giảm theo phần trăm (%)
                        </option>
                        <option value="Fixed"
                            <?= ($promotion['discount_type'] ?? '') === 'Fixed' ? 'selected' : '' ?>>
                            Giảm số tiền cố định (VNĐ)
                        </option>
                    </select>
                </div>

                <div class="promotion-form-group">
                    <label>Giá trị giảm *</label>
                    <input type="number"
                           name="discount_value"
                           required
                           min="0.01"
                           step="0.01"
                           value="<?= promotionFormEscape($promotion['discount_value'] ?? '') ?>">
                    <span class="promotion-form-hint">
                        Nếu chọn phần trăm, nhập từ 0 đến 100.
                    </span>
                </div>

                <div class="promotion-form-group">
                    <label>Giảm tối đa (VNĐ)</label>
                    <input type="number"
                           name="max_discount"
                           min="0"
                           step="1000"
                           placeholder="Để trống nếu không giới hạn"
                           value="<?= promotionFormEscape($promotion['max_discount'] ?? '') ?>">
                </div>

                <div class="promotion-form-group">
                    <label>Giá trị đơn hàng tối thiểu (VNĐ)</label>
                    <input type="number"
                           name="min_order_value"
                           min="0"
                           step="1000"
                           value="<?= promotionFormEscape($promotion['min_order_value'] ?? '0') ?>">
                </div>

                <div class="promotion-form-group">
                    <label>Hạng thành viên tối thiểu *</label>
                    <select name="min_tier" required>
                        <?php
                        $tiers = ['Standard', 'Silver', 'Gold', 'Diamond'];
                        $selectedTier = $promotion['min_tier'] ?? 'Standard';
                        ?>

                        <?php foreach ($tiers as $tier): ?>
                            <option value="<?= $tier ?>"
                                <?= $selectedTier === $tier ? 'selected' : '' ?>>
                                <?= $tier ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="promotion-form-group">
                    <label>Ngày bắt đầu *</label>
                    <input type="date"
                           name="start_date"
                           required
                           value="<?= promotionFormEscape($promotion['start_date'] ?? date('Y-m-d')) ?>">
                </div>

                <div class="promotion-form-group">
                    <label>Ngày kết thúc *</label>
                    <input type="date"
                           name="end_date"
                           required
                           min="<?= date('Y-m-d') ?>"
                           value="<?= promotionFormEscape($promotion['end_date'] ?? '') ?>">
                </div>

            </div>

            <label class="promotion-form-checkbox">
                <input type="checkbox"
                       name="is_active"
                       value="1"
                       <?= !isset($promotion['is_active']) || (int)$promotion['is_active'] === 1 ? 'checked' : '' ?>>
                Kích hoạt voucher ngay
            </label>

            <div class="promotion-form-actions">

                <button type="submit" class="promotion-form-btn promotion-form-save">
                    <?= $isEdit ? 'Lưu thay đổi' : 'Tạo voucher' ?>
                </button>

                <a href="index.php?c=promotion&a=index"
                   class="promotion-form-btn promotion-form-cancel">
                    Quay lại
                </a>

            </div>

        </form>
    </div>
</div>
