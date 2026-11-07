<?php
$flashErr = $_SESSION['crm_flash_error'] ?? '';
unset($_SESSION['crm_flash_error']);
if ($flashErr !== '' && empty($error)) {
    $error = $flashErr;
}

$totalCustomers = (int)($statistics['total_customers'] ?? 0);
$standardCount  = (int)($statistics['standard_count'] ?? 0);
$silverCount    = (int)($statistics['silver_count'] ?? 0);
$goldCount      = (int)($statistics['gold_count'] ?? 0);
$diamondCount   = (int)($statistics['diamond_count'] ?? 0);
$totalRevenue   = (float)($statistics['total_revenue'] ?? 0);
?>

<style>
.crm-page-wrap {
    font-family: 'Be Vietnam Pro', sans-serif;
    color: #1e293b;
}

.crm-header-bar {
    background: #ffffff;
    border: 1px solid #dce3ec;
    border-left: 5px solid #17243b;
    border-radius: 12px;
    padding: 18px 22px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    margin-bottom: 18px;
    box-shadow: 0 2px 10px rgba(15, 23, 42, 0.04);
}
.crm-title {
    font-size: 20px;
    font-weight: 800;
    color: #17243b;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    margin: 0;
}
.btn-crm-gold {
    background: #c69b45;
    border: 1px solid #c69b45;
    color: #17243b;
    font-weight: 800;
    font-size: 13.5px;
    padding: 9px 18px;
    border-radius: 8px;
    cursor: pointer;
    transition: 0.18s;
}
.btn-crm-gold:hover {
    background: #b28735;
    border-color: #b28735;
    color: #ffffff;
}

.crm-kpi-grid {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 12px;
    margin-bottom: 18px;
}
@media (max-width: 1100px) {
    .crm-kpi-grid { grid-template-columns: repeat(3, 1fr); }
}
@media (max-width: 576px) {
    .crm-kpi-grid { grid-template-columns: repeat(2, 1fr); }
}
.crm-kpi-box {
    background: #ffffff;
    border: 1px solid #dce3ec;
    border-top: 3px solid #17243b;
    border-radius: 10px;
    padding: 14px 16px;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.03);
}
.crm-kpi-label {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    color: #64748b;
    margin-bottom: 6px;
}
.crm-kpi-num {
    font-size: 22px;
    font-weight: 800;
    line-height: 1.1;
    color: #17243b;
}

.crm-main-card {
    background: #ffffff;
    border: 1px solid #dce3ec;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 14px rgba(15, 23, 42, 0.04);
}
.crm-navy-strip {
    background: #17243b;
    color: #e5bd69;
    font-size: 13px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 13px 20px;
    border-bottom: 2px solid #c69b45;
}
.crm-card-body {
    padding: 18px 20px;
}
.crm-search-row {
    display: flex;
    gap: 10px;
    margin-bottom: 16px;
}
.crm-search-input {
    flex: 1;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    padding: 9px 14px;
    font-size: 13.5px;
}
.crm-search-input:focus {
    outline: none;
    border-color: #c69b45;
    box-shadow: 0 0 0 3px rgba(198, 155, 69, 0.15);
}
.btn-crm-search {
    background: #17243b;
    color: #e5bd69;
    border: 1px solid #17243b;
    border-radius: 8px;
    padding: 9px 26px;
    font-weight: 700;
    font-size: 13.5px;
    min-width: 140px;
    transition: 0.18s;
}
.btn-crm-search:hover {
    background: #0f172a;
    color: #ffffff;
}

.crm-table {
    width: 100%;
    border-collapse: collapse;
}
.crm-table th {
    background: #f8fafc;
    font-size: 11.5px;
    font-weight: 800;
    text-transform: uppercase;
    color: #17243b;
    padding: 12px 10px;
    border-top: 1px solid #e2e8f0;
    border-bottom: 1px solid #e2e8f0;
}
.crm-table td {
    padding: 13px 10px;
    border-bottom: 1px solid #f1f5f9;
    font-size: 13.5px;
    vertical-align: middle;
}
.crm-table tr:hover td {
    background: #fffdf9;
}
.crm-tier-pill {
    display: inline-block;
    padding: 3px 11px;
    border-radius: 6px;
    font-size: 11.5px;
    font-weight: 700;
}
.tier-Standard { background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; }
.tier-Silver   { background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }
.tier-Gold     { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
.tier-Diamond  { background: #17243b; color: #e5bd69; border: 1px solid #c69b45; }

.btn-crm-action {
    border: 1px solid #cbd5e1;
    background: #ffffff;
    color: #17243b;
    font-size: 12.5px;
    font-weight: 700;
    padding: 5px 12px;
    border-radius: 6px;
    text-decoration: none;
    display: inline-block;
    transition: 0.15s;
    cursor: pointer;
}
.btn-crm-action:hover {
    border-color: #17243b;
    background: #17243b;
    color: #ffffff;
}
.btn-crm-edit {
    background: #fffbeb;
    border-color: #c69b45;
    color: #92400e;
}
.btn-crm-edit:hover {
    background: #c69b45;
    border-color: #c69b45;
    color: #17243b;
}

/* POPUP MODAL 2 CỘT CHUẨN CUKCUK */
.crm-modal-backdrop {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.55);
    z-index: 1055;
    align-items: center;
    justify-content: center;
    padding: 16px;
}
.crm-modal-backdrop.on {
    display: flex;
}
.crm-modal-dialog {
    background: #ffffff;
    border: 1px solid #dce3ec;
    border-radius: 14px;
    width: min(820px, 100%);
    overflow: hidden;
    box-shadow: 0 20px 45px rgba(15, 23, 42, 0.22);
}
.crm-modal-header {
    background: #17243b;
    color: #e5bd69;
    padding: 14px 24px;
    font-weight: 800;
    font-size: 14px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 2px solid #c69b45;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.crm-modal-body {
    padding: 24px 28px;
}
.crm-form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 18px;
}
@media (max-width: 640px) {
    .crm-form-grid { grid-template-columns: 1fr; }
}
.crm-field-label {
    display: block;
    font-size: 13px;
    font-weight: 700;
    color: #17243b;
    margin-bottom: 6px;
}
.crm-field-input {
    width: 100%;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    padding: 10px 13px;
    font-size: 13.5px;
    color: #0f172a;
}
.crm-field-input:focus {
    outline: none;
    border-color: #c69b45;
    box-shadow: 0 0 0 3px rgba(198, 155, 69, 0.16);
}
.crm-modal-footer {
    border-top: 1px solid #e2e8f0;
    margin-top: 22px;
    padding-top: 16px;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}
.btn-crm-cancel {
    background: #ffffff;
    border: 1px solid #94a3b8;
    color: #334155;
    font-weight: 600;
    font-size: 13px;
    padding: 8px 18px;
    border-radius: 7px;
    cursor: pointer;
}
.btn-crm-cancel:hover {
    background: #f1f5f9;
}
</style>

<div class="crm-page-wrap">
    <div class="crm-header-bar">
        <h2 class="crm-title">Quản lý Khách hàng thành viên</h2>
        <button type="button" class="btn-crm-gold" onclick="openCrmCustomerModal()">
            Thêm khách hàng mới
        </button>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger py-2 fw-semibold"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if (!empty($success)): ?>
        <div class="alert alert-success py-2 fw-semibold"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <div class="crm-kpi-grid">
        <div class="crm-kpi-box">
            <div class="crm-kpi-label">Tổng khách hàng</div>
            <div class="crm-kpi-num"><?= $totalCustomers ?></div>
        </div>
        <div class="crm-kpi-box">
            <div class="crm-kpi-label">Hạng Standard</div>
            <div class="crm-kpi-num text-secondary"><?= $standardCount ?></div>
        </div>
        <div class="crm-kpi-box">
            <div class="crm-kpi-label">Hạng Silver · Giảm 3%</div>
            <div class="crm-kpi-num text-info"><?= $silverCount ?></div>
        </div>
        <div class="crm-kpi-box">
            <div class="crm-kpi-label">Hạng Gold · Giảm 5%</div>
            <div class="crm-kpi-num text-warning"><?= $goldCount ?></div>
        </div>
        <div class="crm-kpi-box">
            <div class="crm-kpi-label">Hạng Diamond · Giảm 10%</div>
            <div class="crm-kpi-num" style="color:#b45309;"><?= $diamondCount ?></div>
        </div>
        <div class="crm-kpi-box">
            <div class="crm-kpi-label">Tổng chi tiêu</div>
            <div class="crm-kpi-num"><?= number_format($totalRevenue, 0, ',', '.') ?> ₫</div>
        </div>
    </div>

    <div class="crm-main-card">
        <div class="crm-navy-strip">Danh sách hội viên và tích điểm lũy kế</div>
        <div class="crm-card-body">
            <form method="GET" action="index.php" class="crm-search-row">
                <input type="hidden" name="c" value="customer">
                <input type="hidden" name="a" value="index">
                <input type="text" name="keyword" class="crm-search-input"
                       placeholder="Tìm theo họ tên, số điện thoại hoặc email..."
                       value="<?= htmlspecialchars($keyword ?? '') ?>">
                <button type="submit" class="btn-crm-search">Tìm kiếm</button>
            </form>

            <div class="table-responsive">
                <table class="crm-table">
                    <thead>
                        <tr>
                            <th style="width:60px;">STT</th>
                            <th>Khách hàng</th>
                            <th>Số điện thoại</th>
                            <th class="text-center">Hạng thẻ</th>
                            <th class="text-end">Điểm tích lũy</th>
                            <th class="text-end">Tổng chi tiêu</th>
                            <th class="text-end" style="width:150px;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($customers)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">Không tìm thấy khách hàng nào.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($customers as $idx => $c): ?>
                                <?php
                                $tier = $c['tier'] ?? 'Standard';
                                $email = trim((string)($c['email'] ?? ''));
                                ?>
                                <tr>
                                    <td class="text-muted fw-semibold"><?= $idx + 1 ?></td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($c['full_name']) ?></div>
                                        <?php if ($email !== ''): ?>
                                            <div class="small text-muted"><?= htmlspecialchars($email) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="fw-bold"><?= htmlspecialchars($c['phone']) ?></td>
                                    <td class="text-center">
                                        <span class="crm-tier-pill tier-<?= htmlspecialchars($tier) ?>">
                                            <?= htmlspecialchars($tier) ?>
                                        </span>
                                    </td>
                                    <td class="text-end fw-bold" style="color:#17243b;">
                                        <?= number_format((int)$c['points'], 0, ',', '.') ?> điểm
                                    </td>
                                    <td class="text-end fw-bold" style="color:#b45309;">
                                        <?= number_format((float)$c['total_spent'], 0, ',', '.') ?> ₫
                                    </td>
                                    <td class="text-end">
                                        <a href="index.php?c=customer&a=detail&id=<?= (int)$c['id'] ?>" class="btn-crm-action">Hồ sơ</a>
                                        <button type="button" class="btn-crm-action btn-crm-edit"
                                                onclick='openCrmCustomerModal(<?= htmlspecialchars(json_encode([
                                                    "id"        => (int)$c["id"],
                                                    "full_name" => $c["full_name"],
                                                    "phone"     => $c["phone"],
                                                    "email"     => $email
                                                ], JSON_UNESCAPED_UNICODE), ENT_QUOTES) ?>)'>
                                            Sửa
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- POPUP THÊM / SỬA KHÁCH HÀNG 2 CỘT ĐỒNG BỘ POS & CRM -->
<div class="crm-modal-backdrop" id="crmCustomerModal">
    <div class="crm-modal-dialog">
        <div class="crm-modal-header">
            <span id="crmModalHeaderTitle">Tạo hồ sơ khách hàng thành viên</span>
        </div>
        <div class="crm-modal-body">
            <form method="POST" action="index.php?c=customer&a=index">
                <input type="hidden" name="customer_id" id="crmModalCustomerId" value="">

                <div class="crm-form-grid">
                    <div>
                        <label class="crm-field-label">Họ và tên khách hàng *</label>
                        <input type="text" name="full_name" id="crmModalFullName" class="crm-field-input"
                               placeholder="Nhập đầy đủ họ và tên khách hàng" maxlength="100" required>
                    </div>
                    <div>
                        <label class="crm-field-label">Số điện thoại di động *</label>
                        <input type="tel" name="phone" id="crmModalPhone" class="crm-field-input"
                               placeholder="Nhập số điện thoại 9-11 chữ số" pattern="[0-9]{9,11}" maxlength="11" required>
                    </div>
                </div>

                <div style="margin-top: 16px;">
                    <label class="crm-field-label">Địa chỉ Email</label>
                    <input type="email" name="email" id="crmModalEmail" class="crm-field-input"
                           placeholder="vidu@email.com" maxlength="100">
                </div>

                <div class="crm-modal-footer">
                    <button type="button" class="btn-crm-cancel" onclick="closeCrmCustomerModal()">Hủy bỏ</button>
                    <button type="submit" class="btn-crm-gold" id="crmModalSubmitBtn">Tạo hồ sơ khách hàng</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openCrmCustomerModal(data = null) {
    const modal       = document.getElementById('crmCustomerModal');
    const headerTitle = document.getElementById('crmModalHeaderTitle');
    const idInput     = document.getElementById('crmModalCustomerId');
    const nameInput   = document.getElementById('crmModalFullName');
    const phoneInput  = document.getElementById('crmModalPhone');
    const emailInput  = document.getElementById('crmModalEmail');
    const submitBtn   = document.getElementById('crmModalSubmitBtn');

    if (data && data.id) {
        headerTitle.textContent = 'Cập nhật hồ sơ khách hàng thành viên';
        idInput.value           = data.id;
        nameInput.value         = data.full_name || '';
        phoneInput.value        = data.phone || '';
        emailInput.value        = data.email || '';
        submitBtn.textContent   = 'Lưu cập nhật';
    } else {
        headerTitle.textContent = 'Tạo hồ sơ khách hàng thành viên';
        idInput.value           = '';
        nameInput.value         = data && data.full_name ? data.full_name : '';
        phoneInput.value        = data && data.phone ? data.phone : '';
        emailInput.value        = data && data.email ? data.email : '';
        submitBtn.textContent   = 'Tạo hồ sơ khách hàng';
    }

    modal.classList.add('on');
    setTimeout(() => nameInput.focus(), 50);
}

function closeCrmCustomerModal() {
    document.getElementById('crmCustomerModal').classList.remove('on');
}

document.getElementById('crmCustomerModal').addEventListener('click', function (e) {
    if (e.target === this) {
        closeCrmCustomerModal();
    }
});

document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        closeCrmCustomerModal();
    }
});
document.addEventListener('DOMContentLoaded', function () {
    <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($error)): ?>
        openCrmCustomerModal(<?= json_encode([
            'id'        => !empty($_POST['customer_id']) ? (int)$_POST['customer_id'] : null,
            'full_name' => trim((string)($_POST['full_name'] ?? '')),
            'phone'     => trim((string)($_POST['phone'] ?? '')),
            'email'     => trim((string)($_POST['email'] ?? ''))
        ], JSON_UNESCAPED_UNICODE) ?>);
    <?php elseif (!empty($_GET['edit_id'])): ?>
        <?php
        $editTarget = null;
        foreach ($customers as $item) {
            if ((int)$item['id'] === (int)$_GET['edit_id']) {
                $editTarget = [
                    'id'        => (int)$item['id'],
                    'full_name' => $item['full_name'],
                    'phone'     => $item['phone'],
                    'email'     => trim((string)($item['email'] ?? ''))
                ];
                break;
            }
        }
        ?>
        <?php if ($editTarget): ?>
            openCrmCustomerModal(<?= json_encode($editTarget, JSON_UNESCAPED_UNICODE) ?>);
        <?php endif; ?>
    <?php endif; ?>
});
</script>