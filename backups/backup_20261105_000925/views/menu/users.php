<?php
$activeTab = $_GET['tab'] ?? 'staff';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h3 class="fw-bold mb-0">👥 Quản Trị Nhân Sự, Ca Làm Việc & Phân Quyền (Phân Hệ I)</h3>
        <small class="text-muted">Quản lý hồ sơ nhân viên, ma trận RBAC, chấm công theo ca và kiểm soát sai lệch chuẩn CUKCUK</small>
    </div>
    <button class="btn btn-warning fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#userModal" onclick="openAddUser()">
        ➕ Thêm Nhân Viên Mới
    </button>
</div>

<?php if (!empty($_SESSION['flash_msg'])): ?>
    <div class="alert alert-success py-2 alert-dismissible fade show">
        ✅ <?= $_SESSION['flash_msg']; unset($_SESSION['flash_msg']); ?>
    </div>
<?php endif; ?>
<?php if (!empty($_SESSION['flash_err'])): ?>
    <div class="alert alert-danger py-2 alert-dismissible fade show">
        ⚠️ <?= $_SESSION['flash_err']; unset($_SESSION['flash_err']); ?>
    </div>
<?php endif; ?>

<!-- Thanh điều hướng 4 Tab của Phân hệ I -->
<ul class="nav nav-tabs mb-4 fw-semibold">
    <li class="nav-item">
        <a class="nav-link <?= $activeTab === 'staff' ? 'active bg-dark text-warning' : 'text-dark' ?>" href="index.php?c=menu&a=users&tab=staff">
            👤 1. Hồ sơ & Tài khoản (<?= count($users) ?>)
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $activeTab === 'rbac' ? 'active bg-dark text-warning' : 'text-dark' ?>" href="index.php?c=menu&a=users&tab=rbac">
            🛡️ 2. Vai trò & Ma trận Phân quyền (RBAC)
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $activeTab === 'shifts' ? 'active bg-dark text-warning' : 'text-dark' ?>" href="index.php?c=menu&a=users&tab=shifts">
            ⏰ 3. Ca làm việc & Chấm công
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $activeTab === 'logs' ? 'active bg-dark text-warning' : 'text-dark' ?>" href="index.php?c=menu&a=users&tab=logs">
            🔍 4. Lịch sử thao tác & Kiểm soát sai lệch
        </a>
    </li>
</ul>

<!-- ==================== TAB 1: HỒ SƠ NHÂN VIÊN & TÀI KHOẢN ==================== -->
<?php if ($activeTab === 'staff'): ?>
<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <table class="table table-bordered table-hover align-middle mb-0 bg-white">
            <thead class="table-dark">
                <tr>
                    <th>Mã NV</th>
                    <th>Họ và tên</th>
                    <th>Tài khoản</th>
                    <th>Mã Quyền</th>
                    <th>Vai trò đảm nhiệm</th>
                    <th>Số điện thoại</th>
                    <th>Trạng thái</th>
                    <th class="text-center">Thao tác quản trị</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($users as $u): ?>
                <tr>
                    <td><b>#NV0<?= $u['id'] ?></b></td>
                    <td class="fw-bold"><?= htmlspecialchars($u['full_name']) ?></td>
                    <td><code><?= htmlspecialchars($u['username']) ?></code></td>
                    <td><span class="badge bg-warning text-dark"><?= $u['role_code'] ?></span></td>
                    <td><?= htmlspecialchars($u['role_name']) ?></td>
                    <td><?= htmlspecialchars($u['phone'] ?? 'Chưa cập nhật') ?></td>
                    <td>
                        <?php if ($u['is_active']): ?>
                            <span class="badge bg-success">Đang làm việc</span>
                        <?php else: ?>
                            <span class="badge bg-danger">Đã khóa TK</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-outline-primary" 
                            onclick='openEditUser(<?= json_encode($u, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                            ✏️ Sửa / Đổi quyền
                        </button>
                        <a href="index.php?c=menu&a=resetPassword&id=<?= $u['id'] ?>" 
                           class="btn btn-sm btn-outline-secondary"
                           onclick="return confirm('Đặt lại mật khẩu của <?= htmlspecialchars($u['full_name']) ?> về mặc định 123456?')">
                            🔑 Reset MK
                        </a>
                        <?php if ($u['id'] != $_SESSION['user']['id']): ?>
                            <a href="index.php?c=menu&a=toggleUserStatus&id=<?= $u['id'] ?>" 
                               class="btn btn-sm <?= $u['is_active'] ? 'btn-outline-danger' : 'btn-outline-success' ?>"
                               onclick="return confirm('Xác nhận thay đổi trạng thái hoạt động của tài khoản này?')">
                                <?= $u['is_active'] ? '🔒 Khóa' : '🔓 Mở khóa' ?>
                            </a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- ==================== TAB 2: VAI TRÒ & MA TRẬN PHÂN QUYỀN (RBAC) ==================== -->
<?php if ($activeTab === 'rbac'): ?>
<div class="row g-3">
    <div class="col-md-12">
        <div class="card shadow-sm border-0 p-3">
            <h5 class="fw-bold mb-2">🛡️ Ma Trận Phân Quyền Nghiệp Vụ Chuẩn CUKCUK (RBAC Matrix)</h5>
            <p class="text-muted small mb-3">
                Hệ thống kiểm soát chặt chẽ từng hành động qua hàm <code>$this->requireRole([...])</code> tại Controller để ngăn chặn gian lận (tự ý hủy món, sửa bill hoặc can thiệp tồn kho).
            </p>
            <table class="table table-bordered text-center align-middle">
                <thead class="table-dark">
                    <tr>
                        <th class="text-start">Nghiệp vụ / Chức năng kiểm soát</th>
                        <th>ADMIN<br><small class="text-warning">Quản lý / Leader</small></th>
                        <th>HOSTESS<br><small class="text-info">Lễ tân & CRM</small></th>
                        <th>WAITER<br><small class="text-success">Phục vụ & Thu ngân</small></th>
                        <th>CHEF<br><small class="text-danger">Bếp trưởng (KDS)</small></th>
                        <th>STOCK<br><small class="text-secondary">Thủ kho & Quỹ</small></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="text-start fw-semibold">1. Quản trị Nhân sự, Phân ca & Xem Nhật ký sai lệch (Phân hệ I)</td>
                        <td>✅ Toàn quyền</td><td>❌ Chặn (403)</td><td>❌ Chặn (403)</td><td>❌ Chặn (403)</td><td>❌ Chặn (403)</td>
                    </tr>
                    <tr>
                        <td class="text-start fw-semibold">2. Thiết lập Thực đơn 5 sao, Giá bán & Định lượng BOM (TV1)</td>
                        <td>✅ Toàn quyền</td><td>❌ Chặn (403)</td><td>👁️ Chỉ xem giá</td><td>✅ Cập nhật món/BOM</td><td>👁️ Đối chiếu BOM</td>
                    </tr>
                    <tr>
                        <td class="text-start fw-semibold">3. Xếp bàn VIP, Nhận cọc Booking & Quản lý Hạng thẻ/Voucher (TV2)</td>
                        <td>✅ Toàn quyền</td><td>✅ Toàn quyền</td><td>👁️ Xem bàn mở</td><td>❌ Chặn (403)</td><td>❌ Chặn (403)</td>
                    </tr>
                    <tr>
                        <td class="text-start fw-semibold">4. Ghi Order, Chuyển/Gộp bàn, Giảm giá & Thanh toán VietQR (TV3)</td>
                        <td>✅ Toàn quyền</td><td>❌ Chặn (403)</td><td>✅ Toàn quyền</td><td>❌ Chặn (403)</td><td>❌ Chặn (403)</td>
                    </tr>
                    <tr>
                        <td class="text-start fw-semibold">5. Hủy món đã gửi Bếp / Chỉnh sửa hóa đơn (Kiểm soát gian lận)</td>
                        <td>✅ Duyệt hủy</td><td>❌ Chặn (403)</td><td>⚠️ Bắt buộc ghi Log lý do</td><td>✅ Báo hết món</td><td>❌ Chặn (403)</td>
                    </tr>
                    <tr>
                        <td class="text-start fw-semibold">6. Điều phối màn hình Bếp KDS (Chờ chế biến → Hoàn thành) (TV3)</td>
                        <td>✅ Toàn quyền</td><td>❌ Chặn (403)</td><td>👁️ Xem tiến độ món</td><td>✅ Toàn quyền</td><td>❌ Chặn (403)</td>
                    </tr>
                    <tr>
                        <td class="text-start fw-semibold">7. Nhập/Xuất kho, Kiểm kê & Bàn giao ca tiền mặt (TV4)</td>
                        <td>✅ Toàn quyền</td><td>❌ Chặn (403)</td><td>⚠️ Bàn giao tiền ca</td><td>❌ Chặn (403)</td><td>✅ Toàn quyền</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ==================== TAB 3: CA LÀM VIỆC & CHẤM CÔNG ==================== -->
<?php if ($activeTab === 'shifts'): ?>
<div class="row g-3 mb-4">
    <?php foreach($shifts as $s): ?>
    <div class="col-md-4">
        <div class="card p-3 shadow-sm border-start border-warning border-4 bg-white">
            <div class="d-flex justify-content-between">
                <h6 class="fw-bold text-dark mb-1"><?= htmlspecialchars($s['shift_name']) ?></h6>
                <span class="badge bg-dark"><?= $s['shift_code'] ?></span>
            </div>
            <div class="text-danger fw-bold my-1">
                🕒 <?= substr($s['start_time'], 0, 5) ?> - <?= substr($s['end_time'], 0, 5) ?>
                <span class="text-muted fw-normal">| Phụ cấp: <?= number_format($s['allowance']) ?>đ</span>
            </div>
            <small class="text-muted"><?= htmlspecialchars($s['description']) ?></small>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="card p-3 mb-4 shadow-sm border-0">
    <h6 class="fw-bold text-primary mb-3">📅 Phân Ca Làm Việc Cho Nhân Viên</h6>
    <form method="POST" action="index.php?c=menu&a=assignShift" class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label small fw-semibold">Chọn nhân viên</label>
            <select name="user_id" class="form-select" required>
                <?php foreach($users as $u): if($u['is_active']): ?>
                    <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['full_name']) ?> (<?= $u['role_code'] ?>)</option>
                <?php endif; endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-semibold">Ca làm việc</label>
            <select name="shift_id" class="form-select" required>
                <?php foreach($shifts as $s): ?>
                    <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['shift_name']) ?> (<?= substr($s['start_time'],0,5) ?>-<?= substr($s['end_time'],0,5) ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-semibold">Ngày làm việc</label>
            <input type="date" name="work_date" class="form-control" value="<?= htmlspecialchars($filterDate) ?>" required>
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-semibold">Ghi chú vị trí</label>
            <input type="text" name="note" class="form-control" placeholder="VD: Trực sảnh VIP...">
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-warning w-100 fw-bold">➕ Phân Ca</button>
        </div>
    </form>
</div>

<div class="card shadow-sm border-0 p-3 bg-white">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold mb-0">📋 Bảng Chấm Công & Theo Dõi Hoạt Động Theo Ca (Ngày: <?= date('d/m/Y', strtotime($filterDate)) ?>)</h6>
        <form method="GET" class="d-flex gap-2">
            <input type="hidden" name="c" value="menu">
            <input type="hidden" name="a" value="users">
            <input type="hidden" name="tab" value="shifts">
            <input type="date" name="date" class="form-control form-control-sm" value="<?= htmlspecialchars($filterDate) ?>">
            <button class="btn btn-sm btn-dark">Xem ngày</button>
        </form>
    </div>
    <table class="table table-bordered align-middle mb-0">
        <thead class="table-dark">
            <tr>
                <th>Nhân viên</th>
                <th>Vai trò</th>
                <th>Ca phân công</th>
                <th>Giờ chuẩn</th>
                <th>Check-in</th>
                <th>Check-out</th>
                <th>Trạng thái chấm công</th>
                <th>Ghi chú</th>
                <th class="text-center">Thao tác chấm công</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($schedules)): ?>
                <tr><td colspan="9" class="text-center text-muted py-3">Chưa có lịch phân ca nào trong ngày <?= date('d/m/Y', strtotime($filterDate)) ?>.</td></tr>
            <?php else: foreach($schedules as $sc): ?>
                <tr>
                    <td class="fw-bold"><?= htmlspecialchars($sc['full_name']) ?></td>
                    <td><span class="badge bg-warning text-dark"><?= $sc['role_code'] ?></span></td>
                    <td><?= htmlspecialchars($sc['shift_name']) ?></td>
                    <td><code><?= substr($sc['start_time'],0,5) ?> - <?= substr($sc['end_time'],0,5) ?></code></td>
                    <td class="fw-bold text-success"><?= $sc['check_in'] ?: '--:--' ?></td>
                    <td class="fw-bold text-primary"><?= $sc['check_out'] ?: '--:--' ?></td>
                    <td>
                        <?php if ($sc['attendance_status'] === 'OnTime'): ?>
                            <span class="badge bg-success">Đúng giờ</span>
                        <?php elseif ($sc['attendance_status'] === 'Late'): ?>
                            <span class="badge bg-warning text-dark">Đi muộn</span>
                        <?php elseif ($sc['attendance_status'] === 'Absent'): ?>
                            <span class="badge bg-danger">Vắng mặt</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Chờ vào ca</span>
                        <?php endif; ?>
                    </td>
                    <td><small><?= htmlspecialchars($sc['note'] ?? '') ?></small></td>
                    <td class="text-center">
                        <?php if (!$sc['check_in'] && $sc['attendance_status'] !== 'Absent'): ?>
                            <a href="index.php?c=menu&a=attendance&id=<?= $sc['id'] ?>&type=in&date=<?= $filterDate ?>" class="btn btn-sm btn-success">✅ Check-in</a>
                            <a href="index.php?c=menu&a=attendance&id=<?= $sc['id'] ?>&type=absent&date=<?= $filterDate ?>" class="btn btn-sm btn-outline-danger">❌ Vắng</a>
                        <?php elseif ($sc['check_in'] && !$sc['check_out']): ?>
                            <a href="index.php?c=menu&a=attendance&id=<?= $sc['id'] ?>&type=out&date=<?= $filterDate ?>" class="btn btn-sm btn-primary">🏁 Check-out</a>
                        <?php else: ?>
                            <span class="text-muted small">Đã hoàn tất</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- ==================== TAB 4: LỊCH SỬ THAO TÁC & KIỂM SOÁT SAI LỆCH ==================== -->
<?php if ($activeTab === 'logs'): ?>
<div class="card shadow-sm border-0 p-3 bg-white">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h6 class="fw-bold mb-0">🔍 Nhật Ký Thao Tác Hệ Thống & Kiểm Soát Sai Lệch (Audit Trail)</h6>
            <small class="text-muted">Ghi nhận tự động các thao tác nhạy cảm: phân quyền, khóa tài khoản, chấm công trễ, hủy món, chỉnh sửa giá thực đơn.</small>
        </div>
        <div class="btn-group btn-group-sm">
            <a href="index.php?c=menu&a=users&tab=logs" class="btn <?= $severityFilter === '' ? 'btn-dark' : 'btn-outline-dark' ?>">Tất cả</a>
            <a href="index.php?c=menu&a=users&tab=logs&severity=INFO" class="btn <?= $severityFilter === 'INFO' ? 'btn-info' : 'btn-outline-info' ?>">INFO</a>
            <a href="index.php?c=menu&a=users&tab=logs&severity=WARNING" class="btn <?= $severityFilter === 'WARNING' ? 'btn-warning' : 'btn-outline-warning' ?>">WARNING (Cảnh báo)</a>
            <a href="index.php?c=menu&a=users&tab=logs&severity=CRITICAL" class="btn <?= $severityFilter === 'CRITICAL' ? 'btn-danger' : 'btn-outline-danger' ?>">CRITICAL (Nghiêm trọng)</a>
        </div>
    </div>
    <table class="table table-bordered table-hover align-middle mb-0">
        <thead class="table-dark">
            <tr>
                <th>Thời gian</th>
                <th>Nhân viên thực hiện</th>
                <th>Vai trò</th>
                <th>Phân hệ</th>
                <th>Mã thao tác</th>
                <th>Chi tiết nội dung kiểm soát</th>
                <th>Mức độ</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($auditLogs as $log): ?>
            <tr>
                <td><small class="text-muted"><?= $log['created_at'] ?></small></td>
                <td class="fw-bold"><?= htmlspecialchars($log['full_name']) ?> (<code><?= $log['username'] ?></code>)</td>
                <td><span class="badge bg-warning text-dark"><?= $log['role_code'] ?></span></td>
                <td><?= htmlspecialchars($log['module_name']) ?></td>
                <td><code><?= htmlspecialchars($log['action_type']) ?></code></td>
                <td><?= htmlspecialchars($log['description']) ?></td>
                <td>
                    <?php if ($log['severity'] === 'CRITICAL'): ?>
                        <span class="badge bg-danger">CRITICAL</span>
                    <?php elseif ($log['severity'] === 'WARNING'): ?>
                        <span class="badge bg-warning text-dark">WARNING</span>
                    <?php else: ?>
                        <span class="badge bg-info text-dark">INFO</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- Modal Thêm / Sửa Hồ Sơ Nhân Viên -->
<div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="index.php?c=menu&a=saveUser" class="modal-content">
            <div class="modal-header bg-dark text-warning">
                <h5 class="modal-title fw-bold" id="userModalTitle">➕ Thêm Nhân Viên Mới</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="user_id" id="modal_user_id" value="0">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Họ và tên nhân viên</label>
                    <input type="text" name="full_name" id="modal_full_name" class="form-control" required>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Tài khoản đăng nhập</label>
                        <input type="text" name="username" id="modal_username" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Số điện thoại</label>
                        <input type="text" name="phone" id="modal_phone" class="form-control" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Vai trò & Phân quyền (RBAC)</label>
                    <select name="role_id" id="modal_role_id" class="form-select" required>
                        <?php foreach($roles as $r): ?>
                            <option value="<?= $r['id'] ?>"><?= $r['role_code'] ?> - <?= htmlspecialchars($r['role_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-2" id="password_group">
                    <label class="form-label fw-semibold">Mật khẩu khởi tạo</label>
                    <input type="text" name="password" class="form-control" value="123456">
                    <small class="text-muted">Mật khẩu tự động mã hóa chuẩn Bcrypt khi lưu vào MySQL.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                <button type="submit" class="btn btn-warning fw-bold">💾 Lưu Hồ Sơ</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function openAddUser() {
    document.getElementById('userModalTitle').innerText = '➕ Thêm Nhân Viên Mới';
    document.getElementById('modal_user_id').value = '0';
    document.getElementById('modal_full_name').value = '';
    document.getElementById('modal_username').value = '';
    document.getElementById('modal_username').readOnly = false;
    document.getElementById('modal_phone').value = '';
    document.getElementById('password_group').style.display = 'block';
}

function openEditUser(user) {
    document.getElementById('userModalTitle').innerText = '✏️ Cập Nhật Hồ Sơ & Phân Quyền: ' + user.full_name;
    document.getElementById('modal_user_id').value = user.id;
    document.getElementById('modal_full_name').value = user.full_name;
    document.getElementById('modal_username').value = user.username;
    document.getElementById('modal_username').readOnly = true;
    document.getElementById('modal_phone').value = user.phone || '';
    document.getElementById('modal_role_id').value = user.role_id;
    document.getElementById('password_group').style.display = 'none';
    var modal = new bootstrap.Modal(document.getElementById('userModal'));
    modal.show();
}
</script>
