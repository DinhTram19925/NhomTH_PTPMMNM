<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lập Phiếu Kiểm Kê Kho</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

    <style>
        body { background-color: #f8f9fa; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        
        .section-header {
            background-color: #212529; 
            color: #c9a050; 
            padding: 12px 20px;
            font-weight: bold;
            border-radius: 8px 8px 0 0;
            text-transform: uppercase;
            font-size: 1rem;
        }
        .section-body {
            background-color: #ffffff;
            padding: 20px;
            border: 1px solid #dee2e6;
            border-top: none;
            border-radius: 0 0 8px 8px;
            margin-bottom: 24px;
        }
        .form-label { font-weight: 600; font-size: 0.85rem; color: #333; }
        
        .btn-gold-submit { background-color: #212529; color: #c9a050; border: none; font-weight: bold; transition: 0.3s; }
        .btn-gold-submit:hover { background-color: #343a40; color: #ffdf8a; }
        
        .btn-outline-gold {
            background-color: #ffffff;
            color: #c9a050;
            border: 1px solid #c9a050;
            transition: 0.3s;
        }
        .btn-outline-gold:hover {
            background-color: #fffaf0;
            color: #b38f46;
            border-color: #b38f46;
        }

        .btn-outline-excel {
            background-color: #ffffff;
            color: #28a745;
            border: 1px solid #28a745;
            transition: 0.3s;
        }
        .btn-outline-excel:hover {
            background-color: #f4fdf6;
            color: #218838;
            border-color: #218838;
        }
        
        .pagination-custom .page-link { color: #212529; cursor: pointer; }
        .pagination-custom .page-item.active .page-link { background-color: #c9a050; border-color: #c9a050; color: #fff; }
    </style>
</head>
<body>

<div class="container-fluid py-4 px-4" style="max-width: 1400px;">
    <!-- Tiêu đề trang -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-uppercase mb-1">LẬP PHIẾU KIỂM KÊ KHO</h3>
            <p class="text-muted small mb-0">Chi nhánh: <span class="text-primary fw-bold">Chi nhánh Trung tâm</span> | Ngày lập: <?= date('d/m/Y') ?></p>
        </div>
        
        <div class="d-flex gap-3">
            <button type="button" class="btn btn-outline-excel rounded-3 px-3 shadow-sm d-flex align-items-center gap-2 fw-bold" onclick="exportToExcel()">
                <svg width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5v2z"/>
                    <path d="M4.5 10.5a.5.5 0 0 1 .5-.5h6a.5.5 0 0 1 0 1H5a.5.5 0 0 1-.5-.5zm0-2a.5.5 0 0 1 .5-.5h6a.5.5 0 0 1 0 1H5a.5.5 0 0 1-.5-.5zm0-2a.5.5 0 0 1 .5-.5h6a.5.5 0 0 1 0 1H5a.5.5 0 0 1-.5-.5z"/>
                </svg>
                Xuất Excel
            </button>
            <a href="index.php?c=stock&a=index&tab=dashboard" class="btn btn-outline-gold rounded-3 px-4 shadow-sm fw-bold">Quay lại</a>
        </div>
    </div>

    <form method="POST" action="index.php?c=stock_receipt&a=audit" id="auditForm">
        
        <!-- ================= 1. THÔNG TIN CHỨNG TỪ ================= -->
        <div class="section-header">1. THÔNG TIN CHỨNG TỪ</div>
        <div class="section-body shadow-sm">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Mã phiếu kiểm kê <span class="text-danger">*</span></label>
                    <input type="text" class="form-control fw-bold text-primary bg-light" name="receipt_code" value="KK<?= date('ymdHis') ?>" readonly>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Ngày kiểm kê <span class="text-danger">*</span></label>
                    <input type="text" class="form-control bg-light" value="<?= date('d/m/Y H:i') ?>" readonly>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <label class="form-label">Ghi chú / Diễn giải chung</label>
                    <textarea class="form-control" name="note" rows="2" placeholder="Ví dụ: Kiểm kê kho định kỳ cuối tháng...">Kiểm kê định kỳ tháng <?= date('m/Y') ?></textarea>
                </div>
            </div>
        </div>

        <!-- ================= 2. CHI TIẾT HÀNG HÓA ================= -->
        <div class="section-header d-flex justify-content-between align-items-center">
            <span>2. BẢNG KIỂM ĐẾM HÀNG HÓA</span>
            <span class="badge bg-warning text-dark">Lưu ý: Điền số lượng thực tế & lý do nếu có chênh lệch</span>
        </div>
        <div class="section-body p-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="auditTable">
                    <thead class="table-light border-bottom">
                        <tr>
                            <th class="ps-4 py-3" style="width: 100px;">MÃ NVL</th>
                            <th class="py-3">TÊN NGUYÊN LIỆU</th>
                            <th class="text-center py-3" style="width: 130px;">TỒN KHO</th>
                            <th class="text-center py-3" style="width: 70px;">ĐVT</th>
                            <th class="text-center py-3" style="width: 150px;">THỰC TẾ</th>
                            <th class="text-center py-3" style="width: 130px;">ĐỘ LỆCH</th>
                            <th class="py-3" style="width: 250px;">LÝ DO / GHI CHÚ</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        <?php foreach ($ingredients as $index => $item): ?>
                        <tr class="audit-row" style="display: none;">
                            <td class="ps-4 fw-bold text-muted"><?= htmlspecialchars($item['ingredient_code']) ?></td>
                            <td class="fw-bold text-dark"><?= htmlspecialchars($item['name']) ?></td>
                            
                            <td class="text-center fw-bold text-success">
                                <?= number_format($item['stock_quantity'], 2) ?>
                                <input type="hidden" name="ingredient_id[]" value="<?= $item['id'] ?>">
                                <input type="hidden" name="current_qty[]" value="<?= $item['stock_quantity'] ?>">
                                <input type="hidden" name="unit_cost[]" value="<?= $item['cost_price'] ?>">
                            </td>
                            
                            <td class="text-center text-muted"><?= htmlspecialchars($item['unit']) ?></td>
                            
                            <td class="text-center p-2">
                                <input type="number" step="0.01" min="0" class="form-control form-control-sm text-center border-warning actual-input" 
                                       name="actual_qty[]" placeholder="Nhập số..." data-current="<?= $item['stock_quantity'] ?>">
                            </td>
                            
                            <td class="text-center diff-col fw-bold bg-light"><span class="text-muted">-</span></td>
                            
                            <!-- CỘT LÝ DO MỚI THÊM -->
                            <td class="p-2">
                                <input type="text" class="form-control form-control-sm" name="reason[]" placeholder="Nhập lý do lệch (nếu có)...">
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- THANH PHÂN TRANG -->
            <div class="d-flex justify-content-between align-items-center p-3 bg-light border-top">
                <span class="text-muted small fw-bold" id="pageInfo">Đang tải...</span>
                <nav>
                    <ul class="pagination pagination-sm m-0 shadow-sm pagination-custom" id="paginationControls">
                    </ul>
                </nav>
            </div>
        </div>

        <!-- ================= NÚT XÁC NHẬN ================= -->
        <div class="d-flex justify-content-end mb-5 mt-4">
            <button type="submit" class="btn btn-gold-submit px-5 py-3 rounded-3 d-flex align-items-center gap-2 fs-5 shadow">
                💾 XÁC NHẬN KIỂM KÊ KHO
            </button>
        </div>
    </form>
</div>

<script>
const rowsPerPage = 10;
const rows = document.querySelectorAll('.audit-row');
const totalPages = Math.ceil(rows.length / rowsPerPage);
let currentPage = 1;

function renderTable(page) {
    if (page < 1 || page > totalPages) return;
    currentPage = page;
    let start = (page - 1) * rowsPerPage;
    let end = start + rowsPerPage;

    rows.forEach((row, index) => {
        row.style.display = (index >= start && index < end) ? '' : 'none';
    });

    let endInfo = end > rows.length ? rows.length : end;
    document.getElementById('pageInfo').innerText = `Hiển thị ${start + 1} đến ${endInfo} trên tổng ${rows.length} mặt hàng`;
    renderPagination();
}

function renderPagination() {
    const ul = document.getElementById('paginationControls');
    ul.innerHTML = '';
    ul.innerHTML += `<li class="page-item ${currentPage === 1 ? 'disabled' : ''}"><a class="page-link" onclick="renderTable(${currentPage - 1})">Trước</a></li>`;
    for (let i = 1; i <= totalPages; i++) {
        ul.innerHTML += `<li class="page-item ${currentPage === i ? 'active' : ''}"><a class="page-link" onclick="renderTable(${i})">${i}</a></li>`;
    }
    ul.innerHTML += `<li class="page-item ${currentPage === totalPages ? 'disabled' : ''}"><a class="page-link" onclick="renderTable(${currentPage + 1})">Sau</a></li>`;
}

document.addEventListener("DOMContentLoaded", function() {
    if (rows.length > 0) renderTable(1);
    else document.getElementById('pageInfo').innerText = "Không có dữ liệu nguyên vật liệu.";
});

// Tính lệch tự động
document.querySelectorAll('.actual-input').forEach(input => {
    input.addEventListener('input', function() {
        let currentQty = parseFloat(this.getAttribute('data-current'));
        let actualQty = parseFloat(this.value);
        let diffCol = this.closest('tr').querySelector('.diff-col');
        
        if (isNaN(actualQty)) {
            diffCol.innerHTML = '<span class="text-muted">-</span>';
        } else {
            let diff = actualQty - currentQty;
            if (diff > 0) diffCol.innerHTML = `<span class="text-success">+${diff.toFixed(2)} (Dư)</span>`;
            else if (diff < 0) diffCol.innerHTML = `<span class="text-danger">${diff.toFixed(2)} (Hụt)</span>`;
            else diffCol.innerHTML = `<span class="text-secondary">Khớp 100%</span>`;
        }
    });
});

// Xuất Excel
function exportToExcel() {
    var wb = XLSX.utils.book_new();
    var ws_data = [
        ["BIÊN BẢN KIỂM ĐẾM KHO", "", "", "", "", ""],
        ["Ngày xuất phiếu:", new Date().toLocaleDateString('vi-VN'), "", "", "", ""],
        [""],
        ["MÃ NVL", "TÊN HÀNG HÓA", "ĐVT", "TỒN HỆ THỐNG", "SỐ LƯỢNG THỰC TẾ (ĐIỀN TAY)", "LÝ DO / GHI CHÚ LỆCH"]
    ];
    
    rows.forEach(row => {
        let code = row.cells[0].innerText.trim();
        let name = row.cells[1].innerText.trim();
        let stock = row.cells[2].innerText.trim();
        let unit = row.cells[3].innerText.trim();
        ws_data.push([code, name, unit, stock, "", ""]); 
    });

    var ws = XLSX.utils.aoa_to_sheet(ws_data);
    ws['!cols'] = [{wch: 12}, {wch: 40}, {wch: 10}, {wch: 15}, {wch: 32}, {wch: 30}];
    XLSX.utils.book_append_sheet(wb, ws, "PhieuKiemKe");
    XLSX.writeFile(wb, "Phieu_Kiem_Ke_Kho_" + new Date().toISOString().slice(0,10) + ".xlsx");
}
</script>
</body>
</html>