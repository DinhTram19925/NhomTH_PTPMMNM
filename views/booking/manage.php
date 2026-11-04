
<?php
$statusLabels = [
    'Available' => 'Bàn trống',
    'Occupied'  => 'Đang phục vụ',
    'Reserved'  => 'Đã đặt',
    'Cleaning'  => 'Đang dọn'
];

$statusColors = [
    'Available' => '#168653',
    'Occupied'  => '#bd343e',
    'Reserved'  => '#d99b32',
    'Cleaning'  => '#64748b'
];
?>

<style>
/* ROYALDINE 5S - TABLE MANAGEMENT */

.manage-page {
    --gold: #d8b66c;
    --gold-light: #f3dfaa;
    --bg: #111925;
    --panel: #1a2737;
    --panel2: #202e40;
    --border: #354458;
    --text: #f4f5f7;
    --muted: #a4b0c1;

    box-sizing: border-box;
    min-height: 700px;
    padding: 28px;
    color: var(--text);
    background:
        radial-gradient(ellipse at 90% 0%, #29374a 0%, transparent 38%),
        var(--bg);
    border-radius: 14px;
    font-family: "Segoe UI", Arial, sans-serif;
}

.manage-page *,
.manage-page *::before,
.manage-page *::after {
    box-sizing: border-box;
}

.manage-page h2 {
    margin: 0 0 8px;
    color: var(--gold-light);
    font: 500 clamp(27px, 3vw, 36px) Georgia, "Times New Roman", serif;
}

.manage-page > p {
    margin: 0 0 25px;
    color: var(--muted);
    font-size: 13px;
    line-height: 1.7;
}

.manage-page h3 {
    margin: 0 0 18px;
    color: var(--gold-light);
    font: 500 21px Georgia, "Times New Roman", serif;
}

/* CARD */

.manage-card {
    margin-bottom: 22px;
    padding: 22px;
    border: 1px solid var(--border);
    border-radius: 13px;
    background: linear-gradient(145deg, #202e40, #192434);
    box-shadow: 0 10px 28px #00000018;
}

/* FORM THÊM BÀN */

.manage-form {
    display: grid;
    grid-template-columns: 1.2fr 1.2fr 1.2fr 1.2fr;
    gap: 12px;
}

.manage-form input,
.manage-form select,
.manage-table select,
.manage-table input,
.area-add-form input,
.area-row input {
    width: 100%;
    min-width: 0;
    min-height: 42px;
    padding: 10px 12px;
    border: 1px solid #46566c;
    border-radius: 8px;
    outline: none;
    color: var(--text);
    background: #121d2b;
    font: 13px "Segoe UI", Arial, sans-serif;
    transition: .2s;
}

.manage-page input:focus,
.manage-page select:focus {
    border-color: var(--gold);
    box-shadow: 0 0 0 3px #d8b66c18;
}

/* BUTTON */

.manage-page button {
    border: 0;
    border-radius: 8px;
    padding: 10px 14px;
    color: #171b21;
    background: linear-gradient(135deg, #f1dba3, #c8a458);
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
    transition: .2s;
}

.manage-page button:hover {
    filter: brightness(1.1);
    transform: translateY(-1px);
}

.manage-form button {
    min-height: 42px;
}

/* AREA MANAGEMENT */

.area-add-form {
    display: grid;
    grid-template-columns: 2fr 1fr 150px;
    gap: 12px;
    margin: 16px 0 20px;
}

.area-list {
    overflow: hidden;
    border: 1px solid var(--border);
    border-radius: 9px;
}

.area-header,
.area-row {
    display: grid;
    grid-template-columns: 2fr 1fr 100px 180px;
    gap: 14px;
    align-items: center;
    padding: 12px 14px;
}

.area-header {
    color: var(--gold-light);
    background: var(--panel2);
    font-size: 11px;
    font-weight: 700;
}

.area-row {
    border-top: 1px solid var(--border);
    color: var(--text);
}

.area-row input {
    min-height: 38px;
}

.area-count {
    color: var(--muted);
    font-size: 12px;
}

.area-actions {
    display: flex;
    gap: 7px;
}

.area-actions button {
    flex: 1;
    white-space: nowrap;
}

.area-actions .delete-btn {
    color: #ffc1c6;
    background: #49252d;
    border: 1px solid #8e424b;
}

/* TABLE */

.manage-table-wrap {
    width: 100%;
    overflow-x: auto;
    border: 1px solid var(--border);
    border-radius: 9px;
}

.manage-table {
    width: 100%;
    min-width: 900px;
    border-collapse: collapse;
    background: #182434;
}

.manage-table th,
.manage-table td {
    padding: 13px 12px;
    border-bottom: 1px solid var(--border);
    color: #dce3ec;
    text-align: left;
    vertical-align: middle;
    font-size: 12px;
}

.manage-table th {
    color: var(--gold-light);
    background: var(--panel2);
    font-size: 11px;
    font-weight: 700;
    white-space: nowrap;
}

.manage-table tbody tr {
    transition: background .2s;
}

.manage-table tbody tr:hover {
    background: #ffffff08;
}

.manage-table tbody tr:last-child td {
    border-bottom: 0;
}

.manage-table strong {
    color: var(--gold-light);
}

.manage-table select {
    min-width: 125px;
    min-height: 38px;
}

/* STATUS */

.status-badge {
    display: inline-block;
    padding: 6px 10px;
    border: 1px solid #ffffff20;
    border-radius: 20px;
    color: #fff;
    font-size: 11px;
    font-weight: 700;
    white-space: nowrap;
}

/* TABLE FORMS */

.manage-table form {
    display: flex;
    gap: 7px;
    align-items: center;
    flex-wrap: wrap;
}

.manage-table details summary {
    cursor: pointer;
    color: var(--gold);
    font-weight: 600;
}

.manage-table details form {
    display: grid;
    gap: 8px;
    margin-top: 10px;
    min-width: 160px;
}

.btn-edit {
    background: linear-gradient(135deg, #f1dba3, #c8a458);
}

.action-btn {
    display: inline-block;
    padding: 9px 12px;
    border: 0;
    border-radius: 7px;
    color: #fff !important;
    font-size: 11px;
    font-weight: 700;
    white-space: nowrap;
}

.btn-cleaning {
    background: #b96b16 !important;
}

.btn-available {
    background: #168653 !important;
}

/* NOTIFICATIONS */

.manage-error,
.manage-success {
    margin-bottom: 16px;
    padding: 13px 15px;
    border-radius: 8px;
    font-size: 13px;
}

.manage-error {
    border: 1px solid #8e424b;
    color: #ffc1c6;
    background: #49252d;
}

.manage-success {
    border: 1px solid #287b61;
    color: #9ce8c7;
    background: #164333;
}

/* BACK LINK */

.manage-back {
    display: inline-block;
    margin-top: 5px;
    color: var(--gold);
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
}

.manage-back:hover {
    color: var(--gold-light);
}

/* RESPONSIVE */

@media (max-width: 950px) {
    .manage-form {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .area-header,
    .area-row {
        grid-template-columns: 1.5fr 1fr 70px 150px;
    }
}

@media (max-width: 700px) {
    .manage-page {
        padding: 15px;
    }

    .manage-card {
        padding: 15px;
    }

    .manage-form,
    .area-add-form {
        grid-template-columns: 1fr;
    }

    .area-header {
        display: none;
    }

    .area-row {
        grid-template-columns: 1fr 1fr;
        gap: 10px;
    }

    .area-actions {
        grid-column: 1 / -1;
    }
}
</style>

<div class="manage-page">

    <h2>Quản lý bàn nhà hàng</h2>

    <p>
        Quản lý khu vực, sức chứa và trạng thái phục vụ của từng bàn.
    </p>

    <?php if (!empty($error)): ?>
        <div class="manage-error">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['success'])): ?>
        <div class="manage-success">
            ✓ Thành công! Dữ liệu đã được cập nhật.
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


    <!-- QUẢN LÝ KHU VỰC -->

    <section class="manage-card">

        <h3>＋ Quản lý khu vực</h3>

        <form method="POST" class="area-add-form">

            <input type="hidden" name="action" value="area_add">

            <input
                type="text"
                name="name"
                placeholder="Tên khu vực"
                maxlength="100"
                required
            >

            <input
                type="number"
                name="surcharge_percent"
                placeholder="Phụ thu (%)"
                min="0"
                max="100"
                step="0.01"
                value="0"
                required
            >

            <button type="submit">＋ Thêm khu vực</button>

        </form>

        <div class="area-list">

            <div class="area-header">
                <span>Tên khu vực</span>
                <span>Phụ thu (%)</span>
                <span>Số bàn</span>
                <span>Thao tác</span>
            </div>

            <?php foreach ($areas as $area): ?>

                <div class="area-row">

                    <form
                        method="POST"
                        id="area-form-<?= (int)$area['id'] ?>"
                        style="display:contents"
                    >

                        <input type="hidden" name="action" value="area_update">

                        <input
                            type="hidden"
                            name="id"
                            value="<?= (int)$area['id'] ?>"
                        >

                        <input
                            type="text"
                            name="name"
                            value="<?= htmlspecialchars($area['name']) ?>"
                            required
                            maxlength="100"
                        >

                        <input
                            type="number"
                            name="surcharge_percent"
                            value="<?= htmlspecialchars($area['surcharge_percent']) ?>"
                            min="0"
                            max="100"
                            step="0.01"
                            required
                        >

                        <span class="area-count">
                            <?= (int)($area['table_count'] ?? 0) ?> bàn
                        </span>

                    </form>

                    <div class="area-actions">

                        <button
                            type="submit"
                            form="area-form-<?= (int)$area['id'] ?>"
                        >
                            Lưu
                        </button>

                        <?php if ((int)($area['table_count'] ?? 0) === 0): ?>

                            <form
                                method="POST"
                                onsubmit="return confirm('Xác nhận xóa khu vực?')"
                            >

                                <input type="hidden" name="action" value="area_delete">

                                <input
                                    type="hidden"
                                    name="id"
                                    value="<?= (int)$area['id'] ?>"
                                >

                                <button type="submit" class="delete-btn">
                                    Xóa
                                </button>

                            </form>

                        <?php endif; ?>

                    </div>

                </div>

            <?php endforeach; ?>

            <?php if (empty($areas)): ?>

                <p style="padding:15px;color:#a4b0c1">
                    Chưa có khu vực nào.
                </p>

            <?php endif; ?>

        </div>

    </section>


    <!-- DANH SÁCH BÀN -->

    <section class="manage-card">

        <h3>Danh sách bàn</h3>

        <div class="manage-table-wrap">

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

                                <summary>
                                    ✎ Sửa
                                </summary>

                                <form method="POST">

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

                                <form
                                    method="POST"
                                    onsubmit="return confirm('Xác nhận khách đã rời bàn và bắt đầu dọn?')"
                                >

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

                                <form
                                    method="POST"
                                    onsubmit="return confirm('Xác nhận đã dọn vệ sinh xong bàn này?')"
                                >

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

                                <span style="color:#a4b0c1;font-size:12px">
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
