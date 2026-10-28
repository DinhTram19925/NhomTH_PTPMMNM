<h3 class="fw-bold mb-3">👥 Danh Sách Nhân Sự & Phân Quyền (RBAC)</h3>
<table class="table table-bordered bg-white shadow-sm">
    <thead class="table-dark"><tr><th>ID</th><th>Họ và tên</th><th>Tài khoản</th><th>Mã Quyền</th><th>Vai trò đảm nhiệm</th><th>SĐT</th></tr></thead>
    <tbody>
        <?php foreach($users as $u): ?>
        <tr>
            <td><?= $u['id'] ?></td>
            <td class="fw-bold"><?= $u['full_name'] ?></td>
            <td><code><?= $u['username'] ?></code></td>
            <td><span class="badge bg-warning text-dark"><?= $u['role_code'] ?></span></td>
            <td><?= $u['role_name'] ?></td>
            <td><?= $u['phone'] ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
