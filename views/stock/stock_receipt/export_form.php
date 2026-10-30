<title>Lập Phiếu Xuất Kho - RoyalDine</title>
<style>
    :root {
        --gold-primary: #c9a050;
        --gold-dark: #a8823b;
        --charcoal: #1a1d20;
    }
    .lux-card {
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.04);
        border: 1px solid #e9ecef;
        margin-bottom: 24px;
    }
    .card-header-lux {
        background-color: var(--charcoal);
        color: var(--gold-primary);
        font-weight: bold;
        padding: 12px 20px;
        border-top-left-radius: 8px;
        border-top-right-radius: 8px;
        text-transform: uppercase;
        font-size: 0.9rem;
        letter-spacing: 0.5px;
    }
    .table-export th {
        background-color: #f8f9fa !important;
        color: #212529 !important;
        text-transform: uppercase;
        font-size: 0.75rem;
        font-weight: bold;
        vertical-align: middle;
        padding: 12px 10px;
        border-bottom: 2px solid #dee2e6;
    }
    .table-export td {
        vertical-align: middle;
        padding: 10px;
    }
    .btn-lux-submit {
        background-color: var(--charcoal);
        color: var(--gold-primary);
        font-weight: bold;
        border: none;
        padding: 10px 24px;
        border-radius: 6px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    .btn-lux-submit:hover {
        background-color: #2c3238;
        color: var(--gold-primary);
    }
</style>

<div class="container-fluid py-4 px-4" style="background-color: #f8f9fa; min-height: 100vh;">
    
    <!-- HEADER TIÊU ĐỀ TRANG & NÚT QUAY LẠI CHUẨN MẪU -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3 class="fw-bold text-dark m-0" style="letter-spacing: 0.5px;">LẬP PHIẾU XUẤT KHO</h3>
            <div class="text-muted small mt-1">
                Chi nhánh: <span class="fw-semibold text-dark">Chi nhánh Trung tâm</span> | Ngày lập: <span class="fw-semibold text-dark"><?= date('d/m/Y') ?></span>
            </div>
        </div>
        <a href="index.php?c=stock&a=index&tab=history" class="btn btn-outline-secondary btn-sm fw-semibold rounded-2 px-3 py-1 bg-white text-dark shadow-sm" style="border-color: #ced4da;">
            Quay lại
        </a>
    </div>

    <form action="index.php?c=stock_receipt&a=create&type=export" method="POST" id="exportForm">
        
        <!-- PHẦN 1: THÔNG TIN CHỨNG TỪ -->
        <div class="lux-card">
            <div class="card-header-lux">
                <span>1. THÔNG TIN CHỨNG TỪ</span>
            </div>
            <div class="p-4">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-uppercase text-muted">Mã phiếu xuất <span class="text-danger">*</span></label>
                        <?php $autoCode = 'VX' . date('ymdHis'); ?>
                        <input type="text" name="receipt_code" class="form-control fw-bold text-primary bg-light" value="<?= $autoCode ?>" readonly>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-uppercase text-muted">Ngày xuất <span class="text-danger">*</span></label>
                        <input type="text" class="form-control bg-light" value="<?= date('d/m/Y') ?>" disabled>
                        <input type="hidden" name="created_at" value="<?= date('Y-m-d') ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-uppercase text-muted">Loại phiếu</label>
                        <input type="text" class="form-control fw-bold text-danger bg-light" value="Xuất kho" disabled>
                    </div>

                    <div class="col-12">
                        <label class="form-label small fw-bold text-uppercase text-muted">Ghi chú / Diễn giải</label>
                        <textarea name="note" class="form-control" rows="2" placeholder="Ví dụ: Xuất hủy hàng tồn, xuất bếp chế biến..."></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- PHẦN 2: CHI TIẾT HÀNG HÓA -->
        <div class="lux-card">
            <div class="card-header-lux d-flex justify-content-between align-items-center">
                <span>2. CHI TIẾT HÀNG HÓA</span>
                <span class="small text-warning fw-normal" style="font-size: 0.8rem;">⚠️ Không thể xuất vượt quá số lượng tồn kho</span>
            </div>
            <div class="p-4">
                <div class="table-responsive" style="max-height: 480px; overflow-y: auto;">
                    <table class="table table-bordered table-hover table-export align-middle">
                        <thead>
                            <tr>
                                <th width="6%" class="text-center">Chọn</th>
                                <th width="14%">Mã NVL</th>
                                <th>Tên Nguyên Liệu</th>
                                <th width="15%" class="text-center">Tồn kho hiện tại</th>
                                <th width="12%" class="text-center">ĐVT</th>
                                <th width="20%" class="text-end">Số lượng xuất</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($ingredients)): ?>
                                <tr><td colspan="6" class="text-center py-4 text-muted">Không có nguyên liệu nào trong kho.</td></tr>
                            <?php else: foreach($ingredients as $ing): 
                                $maxStock = $ing['stock_quantity'];
                                $costPrice = $ing['cost_price'] ?? 0;
                            ?>
                            <tr>
                                <td class="text-center">
                                    <input class="form-check-input item-checkbox shadow-sm" type="checkbox" name="ingredient_id[]" value="<?= (int)$ing['id'] ?>" style="width: 18px; height: 18px; cursor: pointer;">
                                </td>
                                <td class="font-monospace text-secondary text-center">#<?= htmlspecialchars($ing['ingredient_code']) ?></td>
                                <td class="fw-bold text-dark"><?= htmlspecialchars($ing['name']) ?></td>
                                <td class="text-center fw-bold <?= $maxStock <= 0 ? 'text-danger' : 'text-success' ?>">
                                    <?= number_format($maxStock, 2) ?>
                                </td>
                                <td class="text-center text-muted"><?= htmlspecialchars($ing['unit']) ?></td>
                                <td>
                                    <input type="number" 
                                           name="quantity[]" 
                                           class="form-control form-control-sm text-end fw-bold text-primary quantity-input" 
                                           value="1" 
                                           min="0.01" 
                                           max="<?= htmlspecialchars($maxStock) ?>" 
                                           step="any" 
                                           disabled required>
                                    
                                    <input type="hidden" name="unit_cost[]" value="<?= htmlspecialchars($costPrice) ?>" disabled class="cost-input">
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- THANH TỔNG KẾT & NÚT XÁC NHẬN GÓC DƯỚI PHẢI -->
                <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                    <div class="text-muted small">
                        * Tích chọn các mặt hàng cần xuất và điền chính xác số lượng thực tế.
                    </div>
                    <div>
                        <button type="submit" class="btn-lux-submit">
                            💾 XÁC NHẬN XUẤT KHO
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </form>
</div>

<!-- Script tương tác bật/tắt ô nhập và kiểm tra dữ liệu -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const checkboxes = document.querySelectorAll('.item-checkbox');
        const exportForm = document.getElementById('exportForm');

        checkboxes.forEach(chk => {
            chk.addEventListener('change', function () {
                const row = this.closest('tr');
                const qtyInput = row.querySelector('.quantity-input');
                const costInput = row.querySelector('.cost-input');

                qtyInput.disabled = !this.checked;
                costInput.disabled = !this.checked;

                if (this.checked) {
                    qtyInput.focus();
                    row.style.backgroundColor = '#fdf8ee';
                } else {
                    qtyInput.value = '1';
                    row.style.backgroundColor = '';
                }
            });
        });

        exportForm.addEventListener('submit', function (e) {
            const checkedBoxes = document.querySelectorAll('.item-checkbox:checked');
            if (checkedBoxes.length === 0) {
                alert('Vui lòng chọn ít nhất một nguyên liệu để xuất kho!');
                e.preventDefault();
                return;
            }

            let valid = true;
            checkedBoxes.forEach(chk => {
                const row = chk.closest('tr');
                const qtyInput = row.querySelector('.quantity-input');
                const quantity = parseFloat(qtyInput.value);
                const maxStock = parseFloat(qtyInput.max);

                if (!Number.isFinite(quantity) || quantity <= 0 || quantity > maxStock) {
                    valid = false;
                    qtyInput.reportValidity();
                }
            });

            if (!valid) {
                e.preventDefault();
            }
        });
    });
</script>