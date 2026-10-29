
<?php
$statusLabels = [
    'Available' => 'Bàn trống',
    'Occupied' => 'Đang phục vụ',
    'Reserved' => 'Đã đặt',
    'Cleaning' => 'Đang dọn'
];
?>

<style>
.manage-page { padding: 24px; color: #1f2937; }
.manage-card {
    background: white; padding: 20px; margin-bottom: 20px;
    border: 1px solid #e5e7eb; border-radius: 12px;
}
.manage-form {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 12px;
}
.manage-form input, .manage-form select, .manage-form button,
.manage-table select {
    width: 100%; padding: 10px; border: 1px solid #d1d5db;
    border-radius: 7px; box-sizing: border-box;
}
.manage-form button, .manage-table button {
    background: #d4af37; border: 0; cursor: pointer;
    font-weight: bold;
}
.manage-table { width: 100%; border-collapse: collapse; }
.manage-table th, .manage-table td {
    padding: 12px 8px; text-align: left;
    border-bottom: 1px solid #e5e7eb;
}
.manage-table form { display: flex; gap: 6px; }
.manage-table form select { min-width: 130px; }
.manage-error {
    background: #fee2e2; padding: 12px; border-radius: 8px;
    margin-bottom: 15px; color: #991b1b;
}
.manage-success {
    background: #dcfce7; padding: 12px; border-radius: 8px;
    margin-bottom: 15px;
}
@media(max-width: 700px) {
    .manage-table { font-size: 13px; }
    .manage-table th, .manage-table td { padding: 8px 4px; }
}
</style>

<div class="manage-page">
    <h2>Quản lý bàn</h2>
    <p>Thêm bàn, sửa thông tin và cập nhật trạng thái phục vụ.</p>

    <?php if (!empty($error)): ?>
        <div class="manage-error">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['success'])): ?>
        <div class="manage-success">Lưu dữ liệu thành công!</div>
    <?php endif; ?>

    <section class="manage-card">
        <h3>Thêm bàn mới</h3>

        <form method="POST" class="manage-form">
            <input type="hidden" name="action" value="add">

            <select name="area_id" required>
                <option value="">-- Chọn khu vực --</option>
                <?php foreach ($areas as $area): ?>
                    <option value="<?= (int)$area['id'] ?>">
                        <?= htmlspecialchars($area['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <input name="table_name" required maxlength="50"
                   placeholder="Tên bàn, ví dụ T-103">

            <input type="number" name="capacity" required
                   min="1" max="100" value="4"
                   placeholder="Sức chứa">

            <button type="submit">+ Thêm bàn</button>
        </form>
    </section>

    <section class="manage-card">
        <h3>Danh sách bàn</h3>

        <div style="overflow-x:auto">
            <table class="manage-table">
                <thead>
                    <tr>
                        <th>Bàn</th>
                        <th>Khu vực</th>
                        <th>Sức chứa</th>
                        <th>Sửa thông tin</th>
                        <th>Trạng thái</th>
                    </tr>
                </thead>

                <tbody>
                <?php foreach ($tables as $table): ?>
                    <?php if ($table['table_id'] === null) continue; ?>

                    <tr>
                        <td>
                            <strong>
                                <?= htmlspecialchars($table['table_name']) ?>
                            </strong>
                        </td>

                        <td><?= htmlspecialchars($table['area_name']) ?></td>
                        <td><?= (int)$table['capacity'] ?> khách</td>

                        <td>
                            <details>
                                <summary style="cursor:pointer">
                                    Sửa
                                </summary>

                                <form method="POST"
                                      style="display:grid;gap:6px;margin-top:8px">
                                    <input type="hidden" name="action"
                                           value="update">
                                    <input type="hidden" name="id"
                                           value="<?= (int)$table['table_id'] ?>">

                                    <select name="area_id" required>
                                        <?php foreach ($areas as $area): ?>
                                            <option
                                                value="<?= (int)$area['id'] ?>"
                                                <?= (int)$area['id'] === (int)$table['area_id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($area['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>

                                    <input name="table_name" required
                                           maxlength="50"
                                           value="<?= htmlspecialchars($table['table_name']) ?>">

                                    <input type="number" name="capacity"
                                           min="1" max="100" required
                                           value="<?= (int)$table['capacity'] ?>">

                                    <button type="submit">Lưu sửa đổi</button>
                                </form>
                            </details>
                        </td>

                        <td>
                            <form method="POST">
                                <input type="hidden" name="action"
                                       value="status">
                                <input type="hidden" name="id"
                                       value="<?= (int)$table['table_id'] ?>">

                                <select name="status">
                                    <?php foreach ($statusLabels as $key => $label): ?>
                                        <option value="<?= $key ?>"
                                            <?= $table['status'] === $key ? 'selected' : '' ?>>
                                            <?= $label ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                                <button type="submit">Lưu</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <a href="index.php?c=booking&a=index">
        ← Quay lại sơ đồ bàn
    </a>
</div>
