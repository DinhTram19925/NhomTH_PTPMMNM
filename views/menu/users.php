<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="fw-bold mb-0">👥 Quản Lý Nhân Sự & Phân Quyền (RBAC)</h3>
    <span class="badge bg-dark p-2">Chỉ dành cho ADMIN (Leader)</span>
</div>

<?php if (!empty($_SESSION['flash_msg'])): ?>
    <div class="alert alert-success py-2"><?= $_SESSION['flash_msg']; unset($_SESSION['flash_msg']); ?></div>
<?php endif; ?>
<?php if (!empty($_SESSION['flash_err'])): ?>
    <div class="alert alert-danger py-2"><?= $_SESSION['flash_err']; unset($_SESSION['flash_err']); ?></div>
<?php endif; ?>

<!-- Form Thêm Tài Khoản Nhân Viên Mới -->
<div class="card p-3 mb-4 shadow-sm border-0">
    <h6 class="fw-bold text-primary mb-3">➕ Cấp Tài Khoản Nhân Viên Mới</h6>
    <form method="POST" action="index.php?c=menu&a=saveUser" class="row g-2">
        <div class="col-md-3">
            <input type="text" name="full_name" class="form-control" placeholder="Họ và tên nhân viên" required>
        </div>
        <div class="col-md-2">
            <input type="text" name="username" class="form-control" placeholder="Tên đăng nhập" required>
        </div>
        <div class="col-md-2">
            <input type="password" name="password" class="form-control" value="123456" placeholder="Mật khẩu" required>
        </div>
        <div class="col-md-2">
            <input type="text" name="phone" class="form-control" placeholder="Số điện thoại">
        </div>
        <div class="col-md-2">
            <select name="role_id" class="form-select">
                <?php foreach($roles as $r): ?>
                    <option value="<?= $r['id'] ?>"><?= $r['role_code'] ?> - <?= $r['role_name'] ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-1">
            <button type="submit" class="btn btn-warning w-100 fw-bold">Tạo</button>
        </div>
    </form>
</div>

<!-- Bảng Danh Sách Nhân Viên -->
<table class="table table-bordered table-hover bg-white shadow-sm align-middle">
    <thead class="table-dark">
        <tr>
            <th>ID</th>
            <th>Họ và tên</th>
            <th>Tài khoản</th>
            <th>Mã Quyền</th>
            <th>Vai trò đảm nhiệm</th>
            <th>Số điện thoại</th>
            <th>Trạng thái</th>
            <th class="text-center" style="width: 260px;">Thao tác quản trị</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach($users as $u): ?>
        <tr class="<?= $u['is_active'] ? '' : 'table-secondary text-muted' ?>">
            <td><?= $u['id'] ?></td>
            <td class="fw-bold"><?= htmlspecialchars($u['full_name']) ?></td>
            <td><code><?= htmlspecialchars($u['username']) ?></code></td>
            <td><span class="badge bg-warning text-dark"><?= $u['role_code'] ?></span></td>
            <td><?= htmlspecialchars($u['role_name']) ?></td>
            <td><?= htmlspecialchars($u['phone']) ?></td>
            <td>
                <?php if ($u['is_active']): ?>
                    <span class="badge bg-success">Đang hoạt động</span>
                <?php else: ?>
                    <span class="badge bg-danger">Đã khóa</span>
                <?php endif; ?>
            </td>
            <td class="text-center">
                <a href="index.php?c=menu&a=resetPass&id=<?= $u['id'] ?>" 
                   class="btn btn-sm btn-outline-primary"
                   onclick="return confirm('Đặt lại mật khẩu của [<?= htmlspecialchars($u['full_name']) ?>] về 123456?')">🔑 Reset MK</a>
                
                <?php if ((int)$u['id'] !== (int)$_SESSION['user']['id']): ?>
                    <a href="index.php?c=menu&a=toggleUser&id=<?= $u['id'] ?>" 
                       class="btn btn-sm <?= $u['is_active'] ? 'btn-outline-danger' : 'btn-success' ?>"
                       onclick="return confirm('Thay đổi trạng thái khóa/mở khóa tài khoản này?')">
                       <?= $u['is_active'] ? '🔒 Khóa TK' : '🔓 Mở khóa' ?>
                    </a>
                <?php else: ?>
                    <span class="badge bg-info text-dark">Tài khoản của bạn</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
