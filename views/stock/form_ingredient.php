<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Cập nhật Nguyên vật liệu - RoyalDine 5S</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { --gold-primary: #c9a050; --charcoal: #1a1d20; --gray-light: #f8f9fa; }
        body { background-color: var(--gray-light); color: #333; font-family: 'Segoe UI', sans-serif; }
        .lux-card { background: #ffffff; border-radius: 12px; box-shadow: 0 4px 24px rgba(0, 0, 0, 0.06); border: 1px solid rgba(201, 160, 80, 0.15); max-width: 800px; margin: 40px auto; overflow: hidden; }
        .card-header-lux { background: linear-gradient(to right, #1a1d20, #2c3136); color: #d4af37; padding: 16px 20px; font-weight: 700; font-size: 1.1rem; text-transform: uppercase; letter-spacing: 1px; border-bottom: 3px solid var(--gold-primary); }
        .form-label { font-weight: 600; color: #495057; font-size: 0.9rem; margin-bottom: 8px;}
        .form-control, .form-select { border-radius: 8px; border-color: rgba(0, 0, 0, 0.15); padding: 10px 12px; }
        .form-control:focus, .form-select:focus { border-color: var(--gold-primary); box-shadow: 0 0 0 0.2rem rgba(201, 160, 80, 0.2); }
        .btn-submit-lux { background: linear-gradient(to right, #1a1d20, #2c3136); color: #d4af37; font-weight: 700; border: 2px solid #1a1d20; border-radius: 8px; transition: all 0.3s ease; }
        .btn-submit-lux:hover { background: #1a1d20; color: #fff; border-color: var(--gold-primary); }
    </style>
</head>
<body>

<div class="container py-4">
    <div class="lux-card">
        <div class="card-header-lux">
            <?= isset($ingredient) ? '✏️ Sửa Thông Tin Nguyên Liệu' : '✨ Thêm Nguyên Liệu Mới' ?>
        </div>
        
        <form method="POST" action="" class="p-4 row g-4">
            <?php if(isset($ingredient)): ?>
            <div class="col-md-12">
                <div class="alert alert-warning border-warning fw-bold py-2 mb-0" style="background-color: #fff8e1; font-size: 0.9rem;">
                    💡 Lưu ý: Số lượng tồn kho hiện tại (<?= number_format($ingredient['stock_quantity'],2) ?> <?= $ingredient['unit'] ?>) không thể sửa thủ công. Vui lòng lập Phiếu Nhập/Xuất để điều chỉnh số lượng!
                </div>
            </div>
            
            <div class="col-md-4">
                <label class="form-label">Mã NVL (Cố định)</label>
                <input type="text" class="form-control fw-bold text-secondary bg-light" value="#<?= htmlspecialchars($ingredient['ingredient_code']) ?>" readonly>
            </div>
            <?php endif; ?>

            <div class="col-md-8">
                <label class="form-label">Tên Nguyên vật liệu <span class="text-danger">*</span></label>
                <input type="text" class="form-control fw-bold" name="name" value="<?= htmlspecialchars($ingredient['name'] ?? '') ?>" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">Nhóm hàng hóa</label>
                <select class="form-select fw-semibold" name="group_name">
                    <?php 
                    $groups = ['Thịt Cao Cấp', 'Hải Sản Tươi', 'Bơ Sữa & Gia Vị', 'Nấm & Rau củ', 'Đồ Uống Có Cồn', 'Đồ Uống Không Cồn', 'Chưa phân nhóm'];
                    $currentGroup = $ingredient['group_name'] ?? 'Chưa phân nhóm';
                    foreach($groups as $grp): 
                    ?>
                        <option value="<?= $grp ?>" <?= $currentGroup == $grp ? 'selected' : '' ?>><?= $grp ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label">Đơn vị tính <span class="text-danger">*</span></label>
                <select class="form-select fw-semibold" name="unit" required>
                    <option value="">-- Chọn đơn vị --</option>
                    <?php 
                    // Danh sách các đơn vị tính chuẩn của nhà hàng RoyalDine 5S
                    $units = ['kg', 'g', 'lít', 'ml', 'chai', 'lon', 'hộp', 'thùng', 'bó', 'lá', 'con', 'cái', 'phần'];
                    $currentUnit = $ingredient['unit'] ?? '';
                    foreach($units as $u): 
                    ?>
                        <option value="<?= $u ?>" <?= $currentUnit == $u ? 'selected' : '' ?>><?= $u ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label">Tồn tối thiểu (Cảnh báo hết hàng) <span class="text-danger">*</span></label>
                <input type="number" step="0.01" min="0" class="form-control text-danger fw-bold" name="min_stock_level" value="<?= $ingredient['min_stock_level'] ?? '0' ?>" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">Giá vốn mặc định (VNĐ) <span class="text-danger">*</span></label>
                <input type="number" step="1" min="0" class="form-control text-primary fw-bold" name="cost_price" value="<?= $ingredient['cost_price'] ?? '0' ?>" required>
            </div>

            <div class="col-md-12">
                <label class="form-label">Nhà cung cấp thường xuyên</label>
                <select class="form-select fw-semibold" name="supplier_id">
                    <option value="">-- Chọn Nhà Cung Cấp --</option>
                    <?php 
                    $currentSupId = $ingredient['supplier_id'] ?? null;
                    // Kiểm tra xem biến $suppliers có tồn tại hay không để tránh lỗi vòng lặp
                    if (isset($suppliers) && is_array($suppliers)):
                        foreach($suppliers as $sup): 
                    ?>
                            <option value="<?= $sup['id'] ?>" <?= $currentSupId == $sup['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($sup['name']) ?>
                            </option>
                    <?php 
                        endforeach; 
                    endif; 
                    ?>
                </select>
            </div>

            <div class="col-12 text-end mt-4 pt-3 border-top">
                <button type="button" class="btn btn-light px-4 py-2 fw-bold border me-2" onclick="history.back()">Hủy bỏ</button>
                <button type="submit" class="btn btn-submit-lux px-5 py-2">💾 LƯU THAY ĐỔI</button>
            </div>
        </form>
    </div>
</div>

</body>
</html>