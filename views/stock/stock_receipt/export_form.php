<title>Lập Phiếu Xuất Kho - RoyalDine</title>
<style>
    :root {
        --gold-primary: #c9a050;
        --gold-dark: #a8823b;
        --gold-light: #fdf8ee;
        --charcoal: #1a1d20;
    }
    
    /* Bao bọc trang để cách ly CSS */
    .export-page-wrapper {
        background-color: #f8f9fa;
        min-height: 100vh;
        padding: 24px 32px;
        font-family: system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif;
    }

    /* Layout tùy chỉnh không phụ thuộc Bootstrap */
    .lux-header-top {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 24px;
    }
    .lux-row {
        display: flex;
        flex-wrap: wrap;
        margin-left: -12px;
        margin-right: -12px;
    }
    .lux-col-4 {
        flex: 0 0 33.3333%;
        max-width: 33.3333%;
        padding-left: 12px;
        padding-right: 12px;
        box-sizing: border-box;
    }
    .lux-col-12 {
        flex: 0 0 100%;
        max-width: 100%;
        padding-left: 12px;
        padding-right: 12px;
        box-sizing: border-box;
    }

    /* Card Style */
    .lux-card {
        background: #ffffff;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        border: 1px solid #e9ecef;
        margin-bottom: 24px;
        overflow: hidden;
    }
    .card-header-lux {
        background-color: var(--charcoal);
        color: var(--gold-primary);
        font-weight: bold;
        padding: 14px 20px;
        text-transform: uppercase;
        font-size: 0.9rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    /* Form Inputs */
    .form-group-lux {
        margin-bottom: 16px;
    }
    .form-label-lux {
        display: block;
        font-size: 0.8rem;
        font-weight: 700;
        color: #343a40;
        margin-bottom: 6px;
        text-transform: uppercase;
    }
    .form-control-lux {
        display: block;
        width: 100%;
        padding: 8px 12px;
        font-size: 0.9rem;
        color: #495057;
        background-color: #fff;
        border: 1px solid #ced4da;
        border-radius: 6px;
        box-sizing: border-box;
        font-family: inherit;
    }
    .form-control-lux:disabled, .form-control-lux[readonly] {
        background-color: #f1f3f5;
        color: #6c757d;
    }

    /* Bảng nguyên liệu */
    .table-responsive-lux {
        width: 100%;
        overflow-x: auto;
    }
    .table-export {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 0;
    }
    .table-export th {
        background-color: #f8f9fa;
        color: #212529;
        text-transform: uppercase;
        font-size: 0.75rem;
        font-weight: bold;
        padding: 12px 10px;
        border-bottom: 2px solid #dee2e6;
        text-align: left;
    }
    .table-export td {
        padding: 10px;
        border-bottom: 1px solid #e9ecef;
        vertical-align: middle;
        color: #495057;
    }
    .table-export tr:hover td {
        background-color: #f8f9fa;
    }

    /* === NÚT QUAY LẠI MỚI === */
    .btn-quay-lai {
        display: inline-block;
        padding: 6px 16px;
        font-size: 0.85rem;
        font-weight: 600;
        color: var(--gold-dark);
        background-color: #fff;
        border: 1px solid var(--gold-dark);
        border-radius: 6px;
        text-decoration: none;
        box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        transition: all 0.2s ease-in-out;
    }
    .btn-quay-lai:hover {
        background-color: var(--gold-light);
        color: var(--gold-dark);
    }

    /* Nút Xác nhận */
    .btn-lux-submit {
        background-color: var(--charcoal);
        color: var(--gold-primary);
        font-weight: bold;
        border: none;
        padding: 10px 24px;
        border-radius: 6px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        cursor: pointer;
        font-size: 0.9rem;
    }
    .btn-lux-submit:hover {
        background-color: #2c3238;
    }

    /* === THANH PHÂN TRANG MỚI === */
    .lux-pagination-wrapper {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 20px;
        background-color: #fff;
    }
    .lux-pagination {
        display: flex;
        list-style: none;
        padding: 0;
        margin: 0;
        border-radius: 4px;
        overflow: hidden;
        border: 1px solid #dee2e6;
    }
    .lux-pagination li {
        margin: 0;
    }
    .lux-pagination a {
        display: block;
        padding: 8px 14px;
        color: #212529;
        background-color: #fff;
        text-decoration: none;
        font-size: 0.85rem;
        font-weight: 500;
        border-right: 1px solid #dee2e6;
    }
    .lux-pagination li:last-child a {
        border-right: none;
    }
    .lux-pagination li.active a {
        background-color: var(--gold-dark);
        color: #fff;
        border-color: var(--gold-dark);
    }
    .lux-pagination li.disabled a {
        color: #212529;
        pointer-events: none;
        background-color: #e9ecef;
    }
    .lux-pagination a:hover:not(.active) {
        background-color: #f1f3f5;
    }

    /* Footer Xác nhận */
    .lux-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 16px 20px;
        background-color: #f8f9fa;
        border-top: 1px solid #e9ecef;
    }
</style>

<div class="export-page-wrapper">
    <!-- HEADER -->
    <div class="lux-header-top">
        <div>
            <h3 style="margin: 0; font-weight: bold; color: #212529; font-size: 1.5rem; letter-spacing: 0.5px;">LẬP PHIẾU XUẤT KHO</h3>
            <div style="font-size: 0.85rem; color: #6c757d; margin-top: 6px;">
                Chi nhánh: <span style="font-weight: 600; color: #0d6efd;">Chi nhánh Trung tâm</span> | Ngày lập: <span style="font-weight: 600; color: #212529;"><?= date('d/m/Y') ?></span>
            </div>
        </div>
        <a href="index.php?c=stock&a=index&tab=history" class="btn-quay-lai">Quay lại</a>
    </div>

    <form action="index.php?c=stock_receipt&a=create&type=export" method="POST" id="exportForm">
        
        <!-- KHỐI 1: THÔNG TIN CHỨNG TỪ -->
        <div class="lux-card">
            <div class="card-header-lux">
                <span>1. THÔNG TIN CHỨNG TỪ</span>
            </div>
            <div style="padding: 20px;">
                <div class="lux-row">
                    <div class="lux-col-4">
                        <div class="form-group-lux">
                            <label class="form-label-lux">Mã phiếu xuất <span style="color: #dc3545;">*</span></label>
                            <?php $autoCode = 'VX' . date('ymdHis'); ?>
                            <input type="text" name="receipt_code" class="form-control-lux" style="font-weight: bold; color: #0d6efd;" value="<?= $autoCode ?>" readonly>
                        </div>
                    </div>

                    <div class="lux-col-4">
                        <div class="form-group-lux">
                            <label class="form-label-lux">Ngày xuất <span style="color: #dc3545;">*</span></label>
                            <input type="text" class="form-control-lux" value="<?= date('d/m/Y') ?>" disabled>
                            <input type="hidden" name="created_at" value="<?= date('Y-m-d') ?>">
                        </div>
                    </div>

                    <div class="lux-col-4">
                        <div class="form-group-lux">
                            <label class="form-label-lux">Loại phiếu</label>
                            <select class="form-control-lux" style="font-weight: bold; color: #dc3545;" disabled>
                                <option selected>-- Xuất kho --</option>
                            </select>
                        </div>
                    </div>

                    <div class="lux-col-12">
                        <div class="form-group-lux" style="margin-bottom: 0;">
                            <label class="form-label-lux">Ghi chú / Diễn giải</label>
                            <textarea name="note" class="form-control-lux" rows="2" placeholder="Ví dụ: Xuất hủy hàng tồn, xuất bếp chế biến đầu tuần..."></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- KHỐI 2: CHI TIẾT HÀNG HÓA -->
        <div class="lux-card">
            <div class="card-header-lux">
                <span>2. CHI TIẾT HÀNG HÓA</span>
                <span style="font-size: 0.75rem; color: #ffc107; text-transform: none;">⚠️ Không thể xuất vượt quá tồn kho</span>
            </div>
            
            <div class="table-responsive-lux">
                <table class="table-export">
                    <thead>
                        <tr>
                            <th width="5%" style="text-align: center;">Chọn</th>
                            <th width="15%">Mã NVL</th>
                            <th>Tên Nguyên Liệu</th>
                            <th width="12%" style="text-align: center;">Tồn Kho</th>
                            <th width="10%" style="text-align: center;">ĐVT</th>
                            <th width="15%" style="text-align: center;">Số Lượng Xuất</th>
                        </tr>
                    </thead>
                    <tbody id="ingredientTableBody">
                        <?php if(empty($ingredients)): ?>
                            <tr><td colspan="6" style="text-align: center; padding: 40px; color: #6c757d;">Không có nguyên liệu nào trong kho.</td></tr>
                        <?php else: foreach($ingredients as $ing): 
                            $maxStock = $ing['stock_quantity'];
                            $costPrice = $ing['cost_price'] ?? 0;
                        ?>
                        <tr class="ing-row">
                            <td style="text-align: center;">
                                <input type="checkbox" class="item-checkbox" name="ingredient_id[]" value="<?= (int)$ing['id'] ?>" style="width: 18px; height: 18px; cursor: pointer;">
                            </td>
                            <td style="font-family: monospace; color: #6c757d; font-weight: 600;">#<?= htmlspecialchars($ing['ingredient_code']) ?></td>
                            <td style="font-weight: bold; color: #212529;"><?= htmlspecialchars($ing['name']) ?></td>
                            <td style="text-align: center; font-weight: bold; color: <?= $maxStock <= 0 ? '#dc3545' : '#198754' ?>;">
                                <?= number_format($maxStock, 2) ?>
                            </td>
                            <td style="text-align: center; color: #6c757d;"><?= htmlspecialchars($ing['unit']) ?></td>
                            <td style="text-align: center;">
                                <input type="number" 
                                       name="quantity[]" 
                                       class="form-control-lux quantity-input" 
                                       style="text-align: center; font-weight: bold; color: #0d6efd; max-width: 100px; margin: 0 auto; display: inline-block;"
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

            <!-- PHÂN TRANG -->
            <div class="lux-pagination-wrapper">
                <span style="font-size: 0.85rem; color: #6c757d;">
                    * Tổng số mặt hàng trong kho: <strong style="color: #212529;"><?= count($ingredients ?? []) ?></strong>
                </span>
                <ul class="lux-pagination" id="paginationControls">
                    <!-- Javascript render -->
                </ul>
            </div>

            <!-- FOOTER XÁC NHẬN -->
            <div class="lux-footer">
                <div style="font-size: 0.85rem; color: #6c757d; font-style: italic;">
                    Lưu ý: Dữ liệu được tích chọn ở các trang khác nhau vẫn sẽ được lưu trữ đầy đủ.
                </div>
                <button type="submit" class="btn-lux-submit">
                    💾 XÁC NHẬN XUẤT KHO
                </button>
            </div>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const checkboxes = document.querySelectorAll('.item-checkbox');
        const exportForm = document.getElementById('exportForm');

        // TÍCH CHỌN DÒNG
        checkboxes.forEach(chk => {
            chk.addEventListener('change', function () {
                const row = this.closest('tr');
                const qtyInput = row.querySelector('.quantity-input');
                const costInput = row.querySelector('.cost-input');

                qtyInput.disabled = !this.checked;
                costInput.disabled = !this.checked;

                if (this.checked) {
                    qtyInput.focus();
                    row.style.backgroundColor = '#fff8e1';
                } else {
                    qtyInput.value = '1';
                    row.style.backgroundColor = '';
                }
            });
        });

        // PHÂN TRANG 10 DÒNG
        const rowsPerPage = 10;
        const tableBody = document.getElementById('ingredientTableBody');
        const tableRows = Array.from(tableBody.querySelectorAll('tr.ing-row'));
        const paginationControls = document.getElementById('paginationControls');

        function displayPage(page) {
            const start = (page - 1) * rowsPerPage;
            const end = start + rowsPerPage;

            tableRows.forEach((row, index) => {
                if (index >= start && index < end) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
            updatePaginationUI(page);
        }

        function updatePaginationUI(page) {
            const totalPages = Math.ceil(tableRows.length / rowsPerPage);
            paginationControls.innerHTML = '';

            if (totalPages <= 1) return;

            // Nút "Trước"
            const prevLi = document.createElement('li');
            prevLi.className = page === 1 ? 'disabled' : '';
            prevLi.innerHTML = `<a href="#">Trước</a>`;
            prevLi.addEventListener('click', (e) => { 
                e.preventDefault(); if (page > 1) displayPage(page - 1); 
            });
            paginationControls.appendChild(prevLi);

            // Nút Số
            for (let i = 1; i <= totalPages; i++) {
                const li = document.createElement('li');
                li.className = i === page ? 'active' : '';
                li.innerHTML = `<a href="#">${i}</a>`;
                li.addEventListener('click', (e) => { 
                    e.preventDefault(); displayPage(i); 
                });
                paginationControls.appendChild(li);
            }

            // Nút "Sau"
            const nextLi = document.createElement('li');
            nextLi.className = page === totalPages ? 'disabled' : '';
            nextLi.innerHTML = `<a href="#">Sau</a>`;
            nextLi.addEventListener('click', (e) => { 
                e.preventDefault(); if (page < totalPages) displayPage(page + 1); 
            });
            paginationControls.appendChild(nextLi);
        }

        if (tableRows.length > 0) displayPage(1);

        // SUBMIT FORM
        exportForm.addEventListener('submit', function (e) {
            const checkedBoxes = document.querySelectorAll('.item-checkbox:checked');
            if (checkedBoxes.length === 0) {
                alert('Vui lòng tích chọn ít nhất một nguyên liệu để xuất kho!');
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
                    row.style.display = ''; 
                    qtyInput.reportValidity();
                }
            });

            if (!valid) {
                e.preventDefault();
            }
        });
    });
</script>