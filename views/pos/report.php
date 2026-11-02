<style>
    :root {
        --gold-primary: #d4af37;
        --card-bg: #1a1d20;
    }
    body { background-color: #0f1012; color: #e1e1e1; }
    .card-custom { background-color: var(--card-bg); border: 1px solid #2d3238; border-radius: 12px; }
    .text-gold { color: var(--gold-primary) !important; }
    .table-dark-custom { background-color: #121416; color: #fff; }
    .table-dark-custom th { color: var(--gold-primary); border-color: #2d3238; }
    .table-dark-custom td { border-color: #2d3238; }
</style>

<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom border-secondary">
        <div>
            <h3 class="fw-bold m-0 text-gold">📊 Báo Cáo Phân Hệ POS & Thu Ngân</h3>
            <small class="text-muted">Doanh thu theo phương thức thanh toán, ca làm việc & nhật ký hủy món</small>
        </div>
        <a href="index.php?c=pos&a=index" class="btn btn-outline-warning">⬅ Quay lại POS</a>
    </div>
    
    <!-- Bộ Lọc Thời Gian & Nút Xuất File -->
    <div class="card card-custom p-3 mb-4">
        <div class="row g-3 align-items-center">
            <div class="col-md-3">
                <label class="form-label text-muted small">Từ ngày</label>
                <input type="date" id="fromDate" class="form-control bg-dark text-white border-secondary" value="<?= date('Y-m-01') ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label text-muted small">Đến ngày</label>
                <input type="date" id="toDate" class="form-control bg-dark text-white border-secondary" value="<?= date('Y-m-d') ?>">
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button onclick="loadReport()" class="btn btn-gold w-100">🔍 Lọc Dữ Liệu</button>
            </div>
            <div class="col-md-3 d-flex align-items-end gap-2">
                <button onclick="window.print()" class="btn btn-outline-light w-50">🖨️ In</button>
                <button onclick="exportExcel()" class="btn btn-outline-success w-50">📥 Excel</button>
            </div>
        </div>
    </div>

    <!-- Thẻ KPI Thống Kê Tổng Quan -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card card-custom p-3 border-start border-warning border-4">
                <small class="text-muted">TỔNG DOANH THU</small>
                <h3 class="text-gold fw-bold m-0 mt-1" id="kpiTotalRevenue">0 VNĐ</h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-custom p-3 border-start border-info border-4">
                <small class="text-muted">TỔNG HÓA ĐƠN ĐÃ THANH TOÁN</small>
                <h3 class="text-info fw-bold m-0 mt-1" id="kpiTotalOrders">0 Đơn</h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-custom p-3 border-start border-danger border-4">
                <small class="text-muted">SỐ LẦN HỦY MÓN</small>
                <h3 class="text-danger fw-bold m-0 mt-1" id="kpiTotalCancels">0 Lần</h3>
            </div>
        </div>
    </div>

    <!-- 1. Báo cáo tổng quan thanh toán -->
    <div class="row g-4 mb-4" id="paymentSummaryCards"></div>

    <div class="row g-4">
        <!-- 2. Bảng Nhật ký Hủy Món -->
        <div class="col-lg-7">
            <div class="card card-custom p-3 shadow-sm">
                <h5 class="text-gold mb-3">⚠️ Nhật Ký Hủy Món </h5>
                <div class="table-responsive">
                    <table class="table table-dark-custom table-hover align-middle">
                        <thead>
                            <tr>
                                <th>#ID</th>
                                <th>Đơn ID</th>
                                <th>Nhân viên</th>
                                <th>Mô tả / Lý do hủy</th>
                                <th>Thời gian</th>
                            </tr>
                        </thead>
                        <tbody id="cancelLogsBody">
                            <tr><td colspan="5" class="text-center text-muted">Đang tải dữ liệu...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 3. Báo cáo Ca Thu Ngân -->
        <div class="col-lg-5">
            <div class="card card-custom p-3 shadow-sm">
                <h5 class="text-gold mb-3">🕒 Bàn Giao Ca Thu Ngân</h5>
                <div class="table-responsive">
                    <table class="table table-dark-custom table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Ca ID</th>
                                <th>Thu ngân</th>
                                <th>Tiền đầu ca</th>
                                <th>Doanh thu TM</th>
                                <th>Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody id="shiftsBody">
                            <tr><td colspan="5" class="text-center text-muted">Đang tải dữ liệu...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    loadReport();
});

// Hàm nạp và lọc dữ liệu báo cáo
function loadReport() {
    const fromDate = document.getElementById('fromDate').value;
    const toDate = document.getElementById('toDate').value;

    fetch(`index.php?c=pos&a=getReportData&from_date=${fromDate}&to_date=${toDate}`)
    .then(res => res.json())
    .then(data => {
        // Tính toán & KPI Tổng quan
        let totalRev = 0;
        let totalOrders = 0;
        data.payment_methods.forEach(pm => {
            totalRev += parseFloat(pm.total_revenue || 0);
            totalOrders += parseInt(pm.transaction_count || 0);
        });
        const totalCancels = data.cancel_logs ? data.cancel_logs.length : 0;

        document.getElementById('kpiTotalRevenue').innerText = totalRev.toLocaleString() + ' VNĐ';
        document.getElementById('kpiTotalOrders').innerText = totalOrders + ' Giao dịch';
        document.getElementById('kpiTotalCancels').innerText = totalCancels + ' Lần';

        // Doanh thu theo Phương thức thanh toán
        const summaryCards = document.getElementById('paymentSummaryCards');
        let cardHtml = '';
        data.payment_methods.forEach(pm => {
            cardHtml += `
                <div class="col-md-4">
                    <div class="card card-custom p-3">
                        <small class="text-muted">Phương thức: <b>${pm.payment_method}</b></small>
                        <h3 class="text-gold mt-2 mb-0">${parseInt(pm.total_revenue).toLocaleString()} VNĐ</h3>
                        <small class="text-secondary mt-1">Số giao dịch: ${pm.transaction_count}</small>
                    </div>
                </div>
            `;
        });
        summaryCards.innerHTML = cardHtml || '<div class="col-12 text-muted">Chưa có giao dịch thanh toán nào trong khoảng thời gian này.</div>';

        // Nhật ký Hủy món
        const cancelBody = document.getElementById('cancelLogsBody');
        let cancelHtml = '';
        data.cancel_logs.forEach(log => {
            cancelHtml += `
                <tr>
                    <td>${log.id}</td>
                    <td><span class="badge bg-secondary">#${log.order_id}</span></td>
                    <td>${log.staff_name || 'N/A'}</td>
                    <td class="text-danger">${log.description}</td>
                    <td><small>${log.created_at}</small></td>
                </tr>
            `;
        });
        cancelBody.innerHTML = cancelHtml || '<tr><td colspan="5" class="text-center text-muted">Không có nhật ký hủy món.</td></tr>';

        // Bàn giao ca thu ngân
        const shiftsBody = document.getElementById('shiftsBody');
        let shiftHtml = '';
        data.shifts.forEach(s => {
            shiftHtml += `
                <tr>
                    <td>#${s.shift_id}</td>
                    <td>${s.cashier_name}</td>
                    <td>${parseInt(s.opening_cash).toLocaleString()} đ</td>
                    <td class="text-gold">${parseInt(s.total_cash_sales || 0).toLocaleString()} đ</td>
                    <td><span class="badge ${s.status === 'Open' ? 'bg-success' : 'bg-secondary'}">${s.status}</span></td>
                </tr>
            `;
        });
        shiftsBody.innerHTML = shiftHtml || '<tr><td colspan="5" class="text-center text-muted">Chưa có thông tin ca.</td></tr>';
    });
}

// Hàm xuất dữ liệu ra file Excel
function exportExcel() {
    const fromDate = document.getElementById('fromDate').value;
    const toDate = document.getElementById('toDate').value;

    fetch(`index.php?c=pos&a=getReportData&from_date=${fromDate}&to_date=${toDate}`)
    .then(res => res.json())
    .then(data => {
        let csvContent = "\uFEFF"; // UTF-8 BOM
        csvContent += "BAO CAO DOANH THU VA NHAT KY HUY MON\n";
        csvContent += `Tu ngay: ${fromDate} - Den ngay: ${toDate}\n\n`;

        csvContent += "--- DOANH THU THEO PHUONG THUC THANH TOAN ---\n";
        csvContent += "Phuong thuc,So giao dich,Tong doanh thu (VND)\n";
        data.payment_methods.forEach(pm => {
            csvContent += `"${pm.payment_method}",${pm.transaction_count},${pm.total_revenue}\n`;
        });

        csvContent += "\n--- NHAT KY HUY MON ---\n";
        csvContent += "ID,Don ID,Nhan vien,Ly do huy,Thoi gian\n";
        data.cancel_logs.forEach(log => {
            csvContent += `${log.id},${log.order_id},"${log.staff_name || 'N/A'}","${log.description}",${log.created_at}\n`;
        });

        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement("a");
        link.href = URL.createObjectURL(blob);
        link.setAttribute("download", `BaoCao_RoyalDine_${fromDate}_to_${toDate}.csv`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });
}
</script>