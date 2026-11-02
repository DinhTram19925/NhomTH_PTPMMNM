<style>
    :root {
        --gold-primary: #d4af37;
        --gold-hover: #b89628;
        --dark-bg: #121416;
        --card-bg: #1a1d20;
    }
    body { background-color: #0f1012; color: #e1e1e1; }
    .card-custom { background-color: var(--card-bg); border: 1px solid #2d3238; border-radius: 12px; }
    .text-gold { color: var(--gold-primary) !important; }
    .btn-gold { background-color: var(--gold-primary); color: #000; font-weight: 600; border: none; }
    .btn-gold:hover { background-color: var(--gold-hover); color: #000; }
    .nav-pills .nav-link { color: #aaa; border-radius: 8px; font-weight: 500; }
    .nav-pills .nav-link.active { background-color: var(--gold-primary); color: #000; font-weight: bold; }
    .kds-card { border-left: 4px solid var(--gold-primary); background: #22262b; transition: transform 0.2s; }
    .kds-card:hover { transform: translateY(-3px); }
    .badge-status { font-size: 0.8rem; padding: 6px 12px; border-radius: 20px; }
</style>

<div class="container-fluid py-3">
    <!-- Header Phân Hệ -->
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom border-secondary">
        <div>
            <h3 class="fw-bold m-0 text-gold">🌟 RoyalDine 5S - Bán Hàng POS & Điều Phối Bếp KDS</h3>
            <small class="text-muted">Phân hệ TV3: Quản lý Order, KDS Điều phối bếp và Thanh toán chuẩn 5 sao</small>
        </div>
        <!-- Thanh chuyển Tab POS / KDS -->
        <ul class="nav nav-pills bg-dark p-1 border border-secondary rounded-3" id="posTab" role="tablist">
            <li class="nav-item">
                <button class="nav-link active" id="pos-tab" data-bs-toggle="tab" data-bs-target="#pos-panel">💻 Gọi Món (POS)</button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="kds-tab" data-bs-toggle="tab" data-bs-target="#kds-panel" onclick="loadKDS()">👨‍🍳 Bếp (KDS) <span class="badge bg-danger ms-1" id="kdsCount">0</span></button>
            </li>
        </ul>
    </div>

    <div class="tab-content" id="posTabContent">
        <!-- TAB 1: POS GỌI MÓN & THANH TOÁN -->
        <div class="tab-pane fade show active" id="pos-panel">
            <div class="row g-4">
                <!-- Chọn bàn & Thực đơn -->
                <div class="col-lg-7">
                    <div class="card card-custom p-3 shadow-sm h-100">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="text-gold m-0">🍽️ Chọn Bàn & Thực Đơn</h5>
                            <div class="d-flex gap-2">
                                <button class="btn btn-outline-warning text-gold border-secondary btn-sm" onclick="showTransferModal()">🔄 Chuyển Bàn</button>
                                <button class="btn btn-outline-warning text-gold border-secondary btn-sm" onclick="showMergeModal()">🔀 Gộp Bàn</button>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label text-muted small">Bàn Phục Vụ</label>
                                <select id="table_id" class="form-select bg-dark text-white border-secondary" onchange="renderCart()">
                                    <option value="1" data-surcharge="0">Bàn T-101 [ID: 1] (Sảnh Symphony - Surcharge 0%)</option>
                                    <option value="2" data-surcharge="0">Bàn T-102 [ID: 2] (Sảnh Symphony - Surcharge 0%)</option>
                                    <option value="3" data-surcharge="10">Phòng VIP-01 [ID: 3] (Hầm Rượu VIP - Surcharge 10%)</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted small">Nhân Viên Order</label>
                                <input type="text" class="form-control bg-dark text-white border-secondary" value="<?= $_SESSION['user']['full_name'] ?? 'Thu ngân POS' ?>" readonly>
                            </div>
                        </div>

                        <!-- Danh sách 4 món ăn theo SQL -->
                        <label class="form-label text-muted small">Chọn Món Ăn Thượng Hạng</label>
                        <div class="row g-2 mb-3">
                            <!-- Món 1 -->
                            <div class="col-md-6">
                                <div class="p-3 border border-secondary rounded-3 bg-dark d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="fw-bold text-white">Sò điệp Hokkaido áp chảo</div>
                                        <small class="text-gold">450,000 VNĐ</small>
                                    </div>
                                    <button class="btn btn-sm btn-gold" onclick="addItemToCart(1, 'Sò điệp Hokkaido áp chảo', 450000)">+ Thêm</button>
                                </div>
                            </div>
                            <!-- Món 2 -->
                            <div class="col-md-6">
                                <div class="p-3 border border-secondary rounded-3 bg-dark d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="fw-bold text-white">Thăn bò Wagyu A5 đá muối</div>
                                        <small class="text-gold">1,850,000 VNĐ</small>
                                    </div>
                                    <button class="btn btn-sm btn-gold" onclick="addItemToCart(2, 'Thăn bò Wagyu A5 đá muối', 1850000)">+ Thêm</button>
                                </div>
                            </div>
                            <!-- Món 3 -->
                            <div class="col-md-6">
                                <div class="p-3 border border-secondary rounded-3 bg-dark d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="fw-bold text-white">Bánh Tiramisu phủ vàng 24K</div>
                                        <small class="text-gold">290,000 VNĐ</small>
                                    </div>
                                    <button class="btn btn-sm btn-gold" onclick="addItemToCart(3, 'Bánh Tiramisu phủ vàng 24K', 290000)">+ Thêm</button>
                                </div>
                            </div>
                            <!-- Món 4 -->
                            <div class="col-md-6">
                                <div class="p-3 border border-secondary rounded-3 bg-dark d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="fw-bold text-white">Rượu vang Château Margaux</div>
                                        <small class="text-gold">650,000 VNĐ</small>
                                    </div>
                                    <button class="btn btn-sm btn-gold" onclick="addItemToCart(4, 'Rượu vang Château Margaux', 650000)">+ Thêm</button>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-muted small">Ghi Chú / Độ Chín / Dị Ứng</label>
                            <input type="text" id="modifier_notes" class="form-control bg-dark text-white border-secondary" placeholder="Ví dụ: Tái vừa (Medium Rare), không bỏ tỏi...">
                        </div>
                    </div>
                </div>

                <!-- Giỏ hàng & Thanh toán -->
                <div class="col-lg-5">
                    <div class="card card-custom p-3 shadow-sm h-100 d-flex flex-column justify-content-between">
                        <div>
                            <h5 class="text-gold mb-3">🛒 Chi Tiết Đơn Hàng</h5>
                            <div id="cartList" class="mb-3" style="min-height: 150px;">
                                <p class="text-muted text-center py-4">Chưa có món nào được chọn</p>
                            </div>
                        </div>

                        <div>
                            <div class="border-top border-secondary pt-3 mb-3">
                                <div class="d-flex justify-content-between text-muted mb-1">
                                    <span>Tạm tính:</span>
                                    <span id="subtotalText">0 VNĐ</span>
                                </div>
                                <div class="d-flex justify-content-between text-muted mb-1">
                                    <span>Phụ thu khu vực:</span>
                                    <span id="surchargeText">0 VNĐ</span>
                                </div>
                                <div class="d-flex justify-content-between text-muted mb-1">
                                    <span>Phí dịch vụ (5%):</span>
                                    <span id="serviceChargeText">0 VNĐ</span>
                                </div>
                                <div class="d-flex justify-content-between text-gold fw-bold fs-5 mt-2">
                                    <span>TỔNG CỘNG:</span>
                                    <span id="totalText">0 VNĐ</span>
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                            <button onclick="submitOrder()" class="btn btn-gold py-2">🔥 Gửi Order Cho Bếp (KDS)</button>
                            <button onclick="checkoutAction()" class="btn btn-outline-success py-2">💳 Thanh Toán (Checkout VietQR)</button>
                            <button onclick="printBillAction()" class="btn btn-outline-info py-2">🖨️ In Hóa Đơn (HTML/PDF)</button>
                        </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 2: MÀN HÌNH ĐIỀU PHỐI BẾP (KDS) -->
        <div class="tab-pane fade" id="kds-panel">
            <div class="card card-custom p-3 shadow-sm">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="text-gold m-0">👨‍🍳 Danh Sách Chế Biến Realtime (KDS)</h5>
                    <button onclick="loadKDS()" class="btn btn-sm btn-outline-warning">🔄 Làm mới danh sách</button>
                </div>
                <div id="kdsContainer" class="row g-3">
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let currentCart = [];

function addItemToCart(id, name, price) {
    const existing = currentCart.find(i => i.menu_item_id === id);
    if (existing) {
        existing.quantity += 1;
    } else {
        currentCart.push({ menu_item_id: id, name: name, unit_price: price, quantity: 1 });
    }
    renderCart();
}

function renderCart() {
    const container = document.getElementById('cartList');
    if (currentCart.length === 0) {
        container.innerHTML = '<p class="text-muted text-center py-4">Chưa có món nào được chọn</p>';
        document.getElementById('subtotalText').innerText = '0 VNĐ';
        document.getElementById('surchargeText').innerText = '0 VNĐ';
        document.getElementById('serviceChargeText').innerText = '0 VNĐ';
        document.getElementById('totalText').innerText = '0 VNĐ';
        return;
    }

    let html = '<ul class="list-group list-group-flush bg-transparent">';
    let subtotal = 0;

    currentCart.forEach(item => {
        const itemTotal = item.unit_price * item.quantity;
        subtotal += itemTotal;
        html += `
            <li class="list-group-item bg-transparent text-white border-secondary d-flex justify-content-between align-items-center px-0">
                <div>
                    <div><b>${item.name}</b></div>
                    <small class="text-muted">${item.unit_price.toLocaleString()} đ x ${item.quantity}</small>
                </div>
                <span class="text-gold fw-bold">${itemTotal.toLocaleString()} đ</span>
            </li>
        `;
    });
    html += '</ul>';
    container.innerHTML = html;

    const tableSelect = document.getElementById('table_id');
    const surchargePercent = parseFloat(tableSelect.options[tableSelect.selectedIndex].getAttribute('data-surcharge') || 0);
    
    const surchargeAmount = subtotal * (surchargePercent / 100);
    const serviceCharge = (subtotal + surchargeAmount) * 0.05;
    const total = subtotal + surchargeAmount + serviceCharge;

    document.getElementById('subtotalText').innerText = subtotal.toLocaleString() + ' VNĐ';
    document.getElementById('surchargeText').innerText = surchargeAmount.toLocaleString() + ' VNĐ (' + surchargePercent + '%)';
    document.getElementById('serviceChargeText').innerText = serviceCharge.toLocaleString() + ' VNĐ';
    document.getElementById('totalText').innerText = total.toLocaleString() + ' VNĐ';
}

function submitOrder() {
    if (currentCart.length === 0) {
        alert('Vui lòng chọn ít nhất 1 món ăn!');
        return;
    }

    const modifierNotes = document.getElementById('modifier_notes').value;
    const cartData = currentCart.map(item => ({
        menu_item_id: item.menu_item_id,
        quantity: item.quantity,
        unit_price: item.unit_price,
        modifier_notes: modifierNotes
    }));

    const data = {
        table_id: document.getElementById('table_id').value,
        waiter_id: 1,
        items: cartData
    };

    fetch('index.php?c=pos&a=createOrder', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(res => res.json())
    .then(res => {
        if (res.status === 'success') {
            alert('🎉 ' + res.message);
            currentCart = [];
            document.getElementById('modifier_notes').value = '';
            renderCart();
            loadKDS();
        } else {
            alert('❌ Lỗi: ' + res.message);
        }
    });
}

function loadKDS() {
    fetch('index.php?c=pos&a=getKds')
    .then(res => res.json())
    .then(data => {
        const container = document.getElementById('kdsContainer');
        document.getElementById('kdsCount').innerText = data.length;

        if (!data || data.length === 0) {
            container.innerHTML = '<div class="col-12 text-center py-5 text-muted">Hiện tại không có món nào cần chế biến.</div>';
            return;
        }

        let html = '';
        data.forEach(item => {
            html += `
                <div class="col-md-4">
                    <div class="p-3 rounded-3 kds-card">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="badge bg-gold text-dark fw-bold">${item.table_name} (Đơn ID: ${item.order_id})</span>
                            <span class="badge bg-warning text-dark badge-status">${item.kds_status}</span>
                        </div>
                        <h5 class="fw-bold text-white mb-1">${item.item_name} <span class="text-gold">x${item.quantity}</span></h5>
                        <p class="small text-muted mb-1">Ghi chú: ${item.modifier_notes || 'Không có'}</p>
                        <div class="small text-secondary mb-2">Món ID: ${item.id} | Gọi lúc: ${item.ordered_at}</div>
                        <button class="btn btn-sm btn-outline-danger w-100" onclick="cancelItemAction(${item.id})">❌ Hủy Món</button>
                    </div>
                </div>
            `;
        });
        container.innerHTML = html;
    });
}

function showTransferModal() {
    const fromTableSelect = document.getElementById('table_id');
    const fromTableId = fromTableSelect.value;
    const fromTableName = fromTableSelect.options[fromTableSelect.selectedIndex].text;

    const toTableId = prompt(`Bạn đang chọn: [${fromTableName}]\nNhập ID BÀN MỚI muốn chuyển/gộp đến (Ví dụ: 1, 2 hoặc 3):`);
    if (!toTableId) return;

    fetch('index.php?c=pos&a=transferTable', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ 
            from_table_id: parseInt(fromTableId), 
            to_table_id: parseInt(toTableId) 
        })
    })
    .then(res => res.json())
    .then(res => {
        alert(res.message);
        if(res.status === 'success') location.reload();
    });
}

function showMergeModal() {
    showTransferModal(); 
}

function cancelItemAction(orderDetailId) {
    const reason = prompt("Nhập lý do hủy món (bắt buộc để ghi log):");
    if (!reason) return;

    fetch('index.php?c=pos&a=cancelItem', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ order_detail_id: orderDetailId, reason: reason })
    })
    .then(res => res.json())
    .then(res => {
        alert(res.message);
        if(res.status === 'success') loadKDS();
    });
}
// Hàm in hóa đơn trực tiếp bằng cách nhập ID Đơn hàng
function printBillAction() {
    const orderId = prompt("Nhập ID Đơn hàng cần in hóa đơn (Xem ID Đơn tại tab Bếp KDS):");
    if (!orderId) return;
    window.open(`index.php?c=pos&a=printBill&order_id=${orderId}`, '_blank', 'width=450,height=700');
}

// Cập nhật hàm Thanh toán -> Tự động mở Bill in sau khi thanh toán xong
function checkoutAction() {
    const orderId = prompt("Nhập ID Đơn hàng cần thanh toán (Xem tại tab Bếp KDS):");
    if (!orderId) return;

    fetch('index.php?c=pos&a=checkout', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            order_id: parseInt(orderId),
            cashier_id: 1,
            payment_method: 'VietQR',
            discount_amount: 0
        })
    })
    .then(res => res.json())
    .then(res => {
        if (res.status === 'success') {
            alert(`🎉 Thanh toán thành công! Tổng tiền: ${res.total_amount.toLocaleString()} VNĐ`);
            
            // 1. Tự động bật cửa sổ in Hóa đơn HTML/PDF
            window.open(`index.php?c=pos&a=printBill&order_id=${orderId}`, '_blank', 'width=450,height=700');
            
            // 2. Mở VietQR nếu có
            if (res.vietqr_url) window.open(res.vietqr_url, '_blank');
            
            location.reload();
        } else {
            alert('❌ Lỗi thanh toán: ' + res.message);
        }
    });
}
</script>