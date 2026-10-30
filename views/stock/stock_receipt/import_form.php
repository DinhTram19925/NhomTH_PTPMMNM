<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Lập Phiếu Nhập Kho - RoyalDine 5S</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --gold-primary: #c9a050;
            --gold-dark: #a8823b;
            --charcoal: #1a1d20;
            --gray-light: #f8f9fa;
            --table-border: #f0eaeb;
        }
        
        body { 
            background-color: var(--gray-light); 
            color: #333; 
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; 
        }

        .lux-card { 
            background: #ffffff; 
            border-radius: 12px; 
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.06); 
            border: 1px solid rgba(201, 160, 80, 0.15); 
            margin-bottom: 24px;
            overflow: hidden;
        }
        
        .card-header-lux { 
            background: linear-gradient(to right, #1a1d20, #2c3136);
            color: #d4af37;
            padding: 16px 20px; 
            font-weight: 700; 
            font-size: 0.95rem;
            text-transform: uppercase; 
            letter-spacing: 1px; 
            border-bottom: 3px solid var(--gold-primary);
        }
        
        .form-label { font-weight: 600; color: #495057; font-size: 0.9rem; margin-bottom: 8px;}
        .form-control, .form-select { 
            border-radius: 8px; 
            border-color: rgba(0, 0, 0, 0.15); 
            padding: 10px 12px;
        }
        .form-control:focus, .form-select:focus { 
            border-color: var(--gold-primary); 
            box-shadow: 0 0 0 0.2rem rgba(201, 160, 80, 0.2); 
        }
        
        /* Chặn nhập các phím không hợp lệ (e, E, -, +) vào ô số */
        input[type=number]::-webkit-inner-spin-button, 
        input[type=number]::-webkit-outer-spin-button { 
            -webkit-appearance: none; 
            margin: 0; 
        }
        input[type=number] { -moz-appearance: textfield; }

        .lux-table { width: 100%; border-collapse: separate; border-spacing: 0; }
        .lux-table thead th { 
            background-color: #fcfbf9; 
            color: var(--charcoal); 
            font-weight: 700; 
            font-size: 0.85rem; 
            text-transform: uppercase; 
            padding: 14px 12px; 
            border-bottom: 1px solid var(--table-border);
        }
        .lux-table tbody tr { transition: background-color 0.2s ease; }
        .lux-table tbody tr:hover { background-color: #fdf8ee; }
        .lux-table td { padding: 12px; vertical-align: middle; border-bottom: 1px solid var(--table-border); }
        
        /* Nút Xác nhận đồng bộ màu Vàng Kim - Đen Than */
        .btn-submit-lux { 
            background: linear-gradient(to right, #1a1d20, #2c3136);
            color: #d4af37; 
            font-weight: 700; 
            letter-spacing: 1px;
            border: 2px solid #1a1d20; 
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .btn-submit-lux:hover:not(:disabled) { 
            background: #1a1d20; 
            color: #fff;
            border-color: var(--gold-primary);
            box-shadow: 0 4px 15px rgba(201, 160, 80, 0.4);
        }
        .btn-submit-lux:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }
        
        .btn-add-row { background-color: #ffffff; color: #495057; border: 1.5px solid rgba(201, 160, 80, 0.4); font-weight: 600; border-radius: 8px; transition: all 0.2s ease; }
        .btn-add-row:hover { background-color: var(--gold-light); color: var(--gold-dark); border-color: var(--gold-primary); }

        .btn-return-lux { background-color: #ffffff; color: #495057; border: 1.5px solid rgba(201, 160, 80, 0.4); font-weight: 600; transition: all 0.2s ease; }
        .btn-return-lux:hover { background-color: var(--gold-light); color: var(--gold-dark); border-color: var(--gold-primary); }

        .btn-remove { color: #dc3545; background: none; border: none; font-weight: bold; cursor: pointer; font-size: 1.2rem; padding: 4px 8px; border-radius: 6px; }
        .btn-remove:hover { color: #fff; background-color: #dc3545; }

        .total-box { font-size: 1.5rem; font-weight: 800; color: var(--gold-dark); }
        .row-total { color: var(--gold-dark); font-weight: 700;}
    </style>
</head>
<body>

<div class="container-fluid py-4 px-4" style="max-width: 1400px;">
    
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom" style="border-color: rgba(201, 160, 80, 0.3) !important;">
        <div>
            <h3 class="fw-bold m-0" style="color: var(--charcoal); letter-spacing: 0.5px; text-transform: uppercase;">
                LẬP PHIẾU NHẬP KHO
            </h3>
            <div class="text-muted fw-semibold mt-1" style="font-size: 0.9rem;">
                Chi nhánh: <span class="text-primary">Chi nhánh Trung tâm</span> | Ngày lập: <?= date('d/m/Y') ?>
            </div>
        </div>
        <button onclick="history.back()" class="btn btn-return-lux px-4 py-2 shadow-sm rounded-3">
             Quay lại 
        </button>
    </div>

    <!-- Gọi hàm onSubmit logic kiểm tra chặt chẽ -->
    <form method="POST" action="index.php?c=stock_receipt&a=create&type=import" id="importForm" onsubmit="return validateForm()">
        
        <!-- THÔNG TIN CHUNG CỦA PHIẾU -->
        <div class="lux-card">
            <div class="card-header-lux">1. Thông tin chứng từ</div>
            <div class="p-4 row g-4">
                <div class="col-md-3">
                    <label class="form-label">Mã phiếu nhập <span class="text-danger">*</span></label>
                    <input type="text" class="form-control fw-bold text-primary bg-light" name="receipt_code" value="PN<?= date('ymdHis') ?>" readonly>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Ngày nhập <span class="text-danger">*</span></label>
                    <input type="date" class="form-control fw-bold" name="created_at" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Nhà cung cấp <span class="text-danger">*</span></label>
                    <select class="form-select fw-semibold text-dark" name="supplier_id" required>
                        <option value="">-- Chọn đối tác cấp hàng --</option>
                        <?php foreach($suppliers as $sup): ?>
                            <option value="<?= $sup['id'] ?>"><?= htmlspecialchars($sup['name']) ?> (<?= htmlspecialchars($sup['phone']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-12">
                    <label class="form-label">Ghi chú / Diễn giải</label>
                    <!-- Giới hạn độ dài ghi chú để tránh lỗi DB -->
                    <input type="text" class="form-control" name="note" maxlength="255" placeholder="Ví dụ: Nhập hàng tươi sống đầu tuần...">
                </div>
            </div>
        </div>

        <!-- CHI TIẾT NGUYÊN VẬT LIỆU -->
        <div class="lux-card">
            <div class="card-header-lux d-flex justify-content-between align-items-center">
                <span>2. Chi tiết hàng hóa</span>
                <button type="button" class="btn btn-add-row btn-sm px-3 py-1" onclick="addRow()">+ Thêm dòng</button>
            </div>
            <div class="table-responsive m-0">
                <table class="table lux-table mb-0" id="detailTable">
                    <thead>
                        <tr>
                            <th width="30%" class="ps-4">Tên nguyên liệu</th>
                            <th width="10%" class="text-center">ĐVT</th>
                            <th width="15%">Số lượng</th>
                            <th width="20%">Đơn giá nhập (đ)</th>
                            <th width="20%" class="text-end">Thành tiền (đ)</th>
                            <th width="5%" class="text-center pe-4">Xóa</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="ps-4">
                                <select class="form-select ingredient-select fw-semibold text-dark" name="ingredient_id[]" onchange="updateUnit(this)" required>
                                    <option value="">- Chọn NVL -</option>
                                    <?php foreach($ingredients as $ing): ?>
                                        <option value="<?= $ing['id'] ?>" data-unit="<?= htmlspecialchars($ing['unit']) ?>" data-price="<?= $ing['cost_price'] ?>">
                                            #<?= htmlspecialchars($ing['ingredient_code']) ?> - <?= htmlspecialchars($ing['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td class="text-center unit-display fw-bold text-muted">-</td>
                            <!-- Chặn nhập số âm, ký tự e bằng onkeydown -->
                            <td><input type="number" step="0.01" min="0.01" class="form-control qty-input text-center fw-bold text-primary" name="quantity[]" value="1" oninput="calcRow(this)" onkeydown="return event.keyCode !== 69 && event.keyCode !== 189" required></td>
                            <td><input type="number" step="1" min="0" class="form-control price-input text-end fw-bold" name="unit_cost[]" value="0" oninput="calcRow(this)" onkeydown="return event.keyCode !== 69 && event.keyCode !== 189" required></td>
                            <td class="text-end row-total">0</td>
                            <td class="text-center pe-4"><button type="button" class="btn-remove" onclick="removeRow(this)">×</button></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="p-4 bg-light d-flex justify-content-between align-items-center border-top">
                <div class="text-muted fw-semibold">Tổng số loại hàng: <span id="totalLines" class="badge bg-secondary rounded-pill px-3">1</span></div>
                <div class="d-flex align-items-center gap-3">
                    <span class="text-uppercase fw-bold text-muted" style="letter-spacing: 1px;">Tổng cộng:</span>
                    <span class="total-box" id="grandTotal">0</span> <span class="total-box">VNĐ</span>
                </div>
            </div>
        </div>

        <!-- NÚT XÁC NHẬN (Đen Than + Vàng Kim) -->
        <div class="text-end mb-5">
            <button type="submit" id="btnSubmit" class="btn btn-submit-lux px-5 py-3 fs-6">
                💾 XÁC NHẬN NHẬP KHO
            </button>
        </div>

    </form>
</div>

<script>
    const ingredients = <?php echo json_encode($ingredients); ?>;
    
    function addRow() {
        let optionsHtml = '<option value="">- Chọn NVL -</option>';
        ingredients.forEach(ing => {
            optionsHtml += `<option value="${ing.id}" data-unit="${ing.unit}" data-price="${ing.cost_price}">#${ing.ingredient_code} - ${ing.name}</option>`;
        });

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td class="ps-4"><select class="form-select ingredient-select fw-semibold text-dark" name="ingredient_id[]" onchange="updateUnit(this)" required>${optionsHtml}</select></td>
            <td class="text-center unit-display fw-bold text-muted">-</td>
            <td><input type="number" step="0.01" min="0.01" class="form-control qty-input text-center fw-bold text-primary" name="quantity[]" value="1" oninput="calcRow(this)" onkeydown="return event.keyCode !== 69 && event.keyCode !== 189" required></td>
            <td><input type="number" step="1" min="0" class="form-control price-input text-end fw-bold" name="unit_cost[]" value="0" oninput="calcRow(this)" onkeydown="return event.keyCode !== 69 && event.keyCode !== 189" required></td>
            <td class="text-end row-total">0</td>
            <td class="text-center pe-4"><button type="button" class="btn-remove" onclick="removeRow(this)">×</button></td>
        `;
        document.querySelector('#detailTable tbody').appendChild(tr);
        updateTotals();
    }

    function removeRow(btn) {
        const tbody = document.querySelector('#detailTable tbody');
        if (tbody.children.length > 1) {
            btn.closest('tr').remove();
            updateTotals();
        } else {
            alert("Phiếu nhập phải có ít nhất 1 dòng nguyên vật liệu!");
        }
    }

    function updateUnit(selectElem) {
        const selectedOption = selectElem.options[selectElem.selectedIndex];
        const unit = selectedOption.getAttribute('data-unit') || '-';
        const price = selectedOption.getAttribute('data-price') || 0;
        
        const tr = selectElem.closest('tr');
        tr.querySelector('.unit-display').textContent = unit;
        
        const priceInput = tr.querySelector('.price-input');
        if (priceInput.value == 0 || priceInput.value === "") {
            priceInput.value = parseFloat(price).toFixed(0);
        }
        calcRow(selectElem);
    }

    function calcRow(elem) {
        const tr = elem.closest('tr');
        // Ràng buộc cứng: Nếu số lượng/đơn giá nhập vào < 0 thì ép về 0
        let qty = parseFloat(tr.querySelector('.qty-input').value);
        let price = parseFloat(tr.querySelector('.price-input').value);
        
        if(qty < 0) { tr.querySelector('.qty-input').value = 1; qty = 1; }
        if(price < 0) { tr.querySelector('.price-input').value = 0; price = 0; }
        
        qty = qty || 0; price = price || 0;
        const total = qty * price;
        tr.querySelector('.row-total').textContent = total.toLocaleString('vi-VN');
        updateTotals();
    }

    function updateTotals() {
        const tbody = document.querySelector('#detailTable tbody');
        document.getElementById('totalLines').textContent = tbody.children.length;
        
        let grandTotal = 0;
        document.querySelectorAll('.row-total').forEach(td => {
            let val = td.textContent.replace(/\./g, '').replace(/,/g, '');
            grandTotal += parseFloat(val) || 0;
        });
        document.getElementById('grandTotal').textContent = grandTotal.toLocaleString('vi-VN');
    }

    // HÀM KIỂM TRA CHẶT CHẼ TRƯỚC KHI GỬI DỮ LIỆU
    function validateForm() {
        const tbody = document.querySelector('#detailTable tbody');
        const rows = tbody.querySelectorAll('tr');
        
        // 1. Kiểm tra xem có dòng dữ liệu nào không
        if(rows.length === 0) {
            alert("Vui lòng thêm ít nhất một loại nguyên liệu để nhập kho!");
            return false;
        }

        // 2. Kiểm tra từng dòng
        for (let i = 0; i < rows.length; i++) {
            const select = rows[i].querySelector('.ingredient-select');
            const qty = parseFloat(rows[i].querySelector('.qty-input').value);
            
            if (select.value === "") {
                alert(`Dòng thứ ${i+1} chưa chọn Nguyên vật liệu. Vui lòng kiểm tra lại!`);
                select.focus();
                return false;
            }
            if (isNaN(qty) || qty <= 0) {
                alert(`Dòng thứ ${i+1} có số lượng không hợp lệ (phải lớn hơn 0). Vui lòng kiểm tra lại!`);
                rows[i].querySelector('.qty-input').focus();
                return false;
            }
        }

        // 3. Khóa nút submit để chống double-click (Chống Tester spam click)
        const btn = document.getElementById('btnSubmit');
        btn.disabled = true;
        btn.innerHTML = '⏳ ĐANG XỬ LÝ...';
        
        return true;
    }
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>