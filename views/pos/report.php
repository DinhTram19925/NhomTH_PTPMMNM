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

    <!-- 1. Báo cáo tổng quan thanh toán -->
    <div class="row g-4 mb-4" id="paymentSummaryCards">
        <!-- Render bằng JS -->
    </div>

    <div class="row g-4">
        <!-- 2. Bảng Nhật ký Hủy Món (Giám sát) -->
        <div class="col-lg-7">
            <div class="card card-custom p-3 shadow-sm">
                <h5 class="text-gold mb-3">⚠️ Nhật Ký Hủy Món (Chống Gian Lận)</h5>
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
    fetch('index.php?c=pos&a=getReportData')
    .then(res => res.json())
    .then(data => {
        // Render 1. Doanh thu theo pttt
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
        summaryCards.innerHTML = cardHtml || '<div class="col-12 text-muted">Chưa có giao dịch thanh toán nào.</div>';

        // Render 2. Nhật ký hủy món
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

        // Render 3. Ca làm việc
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
});
</script>