
<?php
$statusLabels = [
    'Available' => 'Bàn trống',
    'Occupied'  => 'Đang phục vụ',
    'Reserved'  => 'Đã đặt',
    'Cleaning'  => 'Đang dọn'
];

$statusColors = [
    'Available' => '#16a34a',
    'Occupied'  => '#dc2626',
    'Reserved'  => '#d97706',
    'Cleaning'  => '#64748b'
];
?>

<style>
.manage-page {
    padding: 24px;
    color: #1f2937;
}

.manage-page h2 {
    margin-bottom: 8px;
}

.manage-card {
    background: #fff;
    padding: 22px;
    margin-bottom: 20px;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    box-shadow: 0 3px 12px rgba(0,0,0,.04);
}

.manage-form {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 12px;
}

.manage-form input,
.manage-form select,
.manage-form button,
.manage-table select,
.manage-table button {
    padding: 10px;
    border: 1px solid #d1d5db;
    border-radius: 7px;
    box-sizing: border-box;
}

.manage-form button,
.manage-table button {
    background: #d4af37;
    border: 0;
    cursor: pointer;
    font-weight: bold;
    transition: .2s;
}

.manage-form button:hover,
.manage-table button:hover {
    filter: brightness(.94);
}

.manage-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 850px;
}

.manage-table th,
.manage-table td {
    padding: 13px 9px;
    text-align: left;
    border-bottom: 1px solid #e5e7eb;
    vertical-align: middle;
}

.manage-table th {
    background: #f8fafc;
    color: #475569;
    white-space: nowrap;
}

.manage-table form {
    display: flex;
    gap: 7px;
    align-items: center;
    flex-wrap: wrap;
}

.manage-table form select {
    min-width: 130px;
    background: #fff;
}

.status-badge {
    display: inline-block;
    padding: 6px 10px;
    border-radius: 20px;
    color: #fff;
    font-size: 12px;
    font-weight: 700;
    white-space: nowrap;
}

.action-btn {
    display: inline-block;
    padding: 9px 12px;
    border: 0;
    border-radius: 7px;
    cursor: pointer;
    font-weight: 700;
    color: #fff;
    white-space: nowrap;
}

.btn-cleaning {
    background: #d97706 !important;
}

.btn-available {
    background: #16a34a !important;
}

.btn-edit {
    background: #d4af37 !important;
    color: #1f2937;
}

.manage-error {
    background: #fee2e2;
    padding: 12px;
    border-radius: 8px;
    margin-bottom: 15px;
    color: #991b1b;
}

.manage-success {
    background: #dcfce7;
    padding: 12px;
    border-radius: 8px;
    margin-bottom: 15px;
    color: #166534;
}

.manage-back {
    display: inline-block;
    margin-top: 10px;
    color: #8b6b16;
    text-decoration: none;
    font-weight: 600;
}

.manage-back:hover {
    text-decoration: underline;
}

@media(max-width: 700px) {
    .manage-page {
        padding: 12px;
    }

    .manage-card {
        padding: 14px;
    }

    .manage-table {
        font-size: 13px;
    }
}
</style>

<div class="manage-page">

    <h2>Quản lý bàn nhà hàng</h2>
    <p>Quản lý khu vực, sức chứa và trạng thái phục vụ của từng bàn.</p>

    <?php if (!empty($error)): ?>
        <div class="manage-error">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['success'])): ?>
        <div class="manage-success">
            <strong>✓ Thành công!</strong> Dữ liệu đã được cập nhật.
        </div>
    <?php endif; ?>

    <!-- THÊM BÀN -->
    <section class="manage-card">

        <h3>＋ Thêm bàn mới</h3>

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

            <input
                name="table_name"
                required
                maxlength="50"
                placeholder="Tên bàn, ví dụ T-103"
            >

            <input
                type="number"
                name="capacity"
                required
                min="1"
                max="100"
                value="4"
                placeholder="Sức chứa"
            >

            <button type="submit">＋ Thêm bàn</button>

        </form>
    </section>

    <!-- DANH SÁCH BÀN -->
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
                        <th>Thao tác phục vụ</th>
                    </tr>
                </thead>

                <tbody>

                <?php foreach ($tables as $table): ?>

                    <?php if ($table['table_id'] === null) continue; ?>

                    <?php
                    $tableId = (int)$table['table_id'];
                    $status = $table['status'];
                    $statusLabel = $statusLabels[$status] ?? $status;
                    $statusColor = $statusColors[$status] ?? '#64748b';
                    ?>

                    <tr>

                        <!-- TÊN BÀN -->
                        <td>
                            <strong>
                                <?= htmlspecialchars($table['table_name']) ?>
                            </strong>
                        </td>

                        <!-- KHU VỰC -->
                        <td>
                            <?= htmlspecialchars($table['area_name']) ?>
                        </td>

                        <!-- SỨC CHỨA -->
                        <td>
                            <?= (int)$table['capacity'] ?> khách
                        </td>

                        <!-- SỬA THÔNG TIN -->
                        <td>

                            <details>

                                <summary style="cursor:pointer;color:#a17c19;font-weight:600">
                                    ✎ Sửa
                                </summary>

                                <form
                                    method="POST"
                                    style="display:grid;gap:8px;margin-top:10px"
                                >

                                    <input type="hidden" name="action" value="update">

                                    <input
                                        type="hidden"
                                        name="id"
                                        value="<?= $tableId ?>"
                                    >

                                    <select name="area_id" required>

                                        <?php foreach ($areas as $area): ?>

                                            <option
                                                value="<?= (int)$area['id'] ?>"
                                                <?= (int)$area['id'] === (int)$table['area_id'] ? 'selected' : '' ?>
                                            >
                                                <?= htmlspecialchars($area['name']) ?>
                                            </option>

                                        <?php endforeach; ?>

                                    </select>

                                    <input
                                        name="table_name"
                                        required
                                        maxlength="50"
                                        value="<?= htmlspecialchars($table['table_name']) ?>"
                                    >

                                    <input
                                        type="number"
                                        name="capacity"
                                        min="1"
                                        max="100"
                                        required
                                        value="<?= (int)$table['capacity'] ?>"
                                    >

                                    <button type="submit" class="btn-edit">
                                        Lưu sửa đổi
                                    </button>

                                </form>

                            </details>

                        </td>

                        <!-- TRẠNG THÁI -->
                        <td>

                            <span
                                class="status-badge"
                                style="background:<?= htmlspecialchars($statusColor) ?>"
                            >
                                <?= htmlspecialchars($statusLabel) ?>
                            </span>

                            <form method="POST" style="margin-top:8px">

                                <input type="hidden" name="action" value="status">

                                <input
                                    type="hidden"
                                    name="id"
                                    value="<?= $tableId ?>"
                                >

                                <select name="status">

                                    <?php foreach ($statusLabels as $key => $label): ?>

                                        <option
                                            value="<?= $key ?>"
                                            <?= $status === $key ? 'selected' : '' ?>
                                        >
                                            <?= $label ?>
                                        </option>

                                    <?php endforeach; ?>

                                </select>

                                <button type="submit">Lưu</button>

                            </form>

                        </td>

                        <!-- THAO TÁC PHỤC VỤ -->
                        <td>

                            <?php if ($status === 'Occupied'): ?>

                                <form method="POST"
                                      onsubmit="return confirm('Xác nhận khách đã rời bàn và bắt đầu dọn?')">

                                    <input type="hidden" name="action" value="status">
                                    <input type="hidden" name="id" value="<?= $tableId ?>">
                                    <input type="hidden" name="status" value="Cleaning">

                                    <button
                                        type="submit"
                                        class="action-btn btn-cleaning"
                                    >
                                        Trả bàn – Bắt đầu dọn
                                    </button>

                                </form>

                            <?php elseif ($status === 'Cleaning'): ?>

                                <form method="POST"
                                      onsubmit="return confirm('Xác nhận đã dọn vệ sinh xong bàn này?')">

                                    <input type="hidden" name="action" value="status">
                                    <input type="hidden" name="id" value="<?= $tableId ?>">
                                    <input type="hidden" name="status" value="Available">

                                    <button
                                        type="submit"
                                        class="action-btn btn-available"
                                    >
                                        ✓ Hoàn tất dọn
                                    </button>

                                </form>

                            <?php else: ?>

                                <span style="color:#94a3b8;font-size:12px">
                                    Không có thao tác
                                </span>

                            <?php endif; ?>

                        </td>

                    </tr>

                <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    </section>

    <a class="manage-back" href="index.php?c=booking&a=index">
        ← Quay lại sơ đồ mặt bằng
    </a>

</div>
