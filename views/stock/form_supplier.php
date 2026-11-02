<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title><?= $id > 0 ? 'Cập nhật' : 'Thêm' ?> Nhà Cung Cấp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; }
        .lux-card { background: #fff; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: none; }
        .lux-header { background: #1a1a1a; color: #d4af37; padding: 15px 20px; border-radius: 12px 12px 0 0; border-bottom: 3px solid #c9a050; }
        
        .btn-gold-lux { background-color: #c9a050; color: #fff; border: none; transition: 0.3s; font-weight: bold; }
        .btn-gold-lux:hover { background-color: #b58e45; color: #fff; }
        
        .form-label { font-weight: 600; color: #495057; }
        .form-control, .form-select { border-radius: 8px; border: 1px solid #ced4da; padding: 10px 15px; }
        .form-control:focus, .form-select:focus { border-color: #c9a050; box-shadow: 0 0 0 0.2rem rgba(201, 160, 80, 0.25); }
        
        /* Trạng thái mặc định: Từ trắng (Nền trắng, chữ đen) */
        .btn-back-lux {
            background-color: #ffffff; 
            color: #1a1a1a; 
            border: 1px solid #ffffff; 
            transition: all 0.3s ease-in-out;
            font-weight: 600;
        }
        
        /* Khi hover: Sang đen (Nền trong suốt lộ màu đen của header, chữ/viền vàng đồng) */
        .btn-back-lux:hover {
            background-color: transparent; 
            color: #d4af37; 
            border-color: #d4af37; 
        }
    </style>
</head>
<body>
<div class="container mt-5" style="max-width: 800px;">
    <div class="lux-card">
        <div class="lux-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold text-uppercase">
                <?= $id > 0 ? '✏️ CẬP NHẬT NHÀ CUNG CẤP' : '➕ THÊM NHÀ CUNG CẤP MỚI' ?>
            </h5>
            <!-- Đã đổi class thành btn-back-lux -->
            <a href="index.php?c=stock&a=index&tab=suppliers" class="btn btn-sm btn-back-lux rounded-pill px-3">Quay lại</a>
        </div>
        
        <div class="p-4">
            <form method="POST" action="">
                <div class="row g-3">
                    <div class="col-md-12">
                        <label class="form-label">Tên Nhà Cung Cấp <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" required 
                               value="<?= htmlspecialchars($supplier['name'] ?? '') ?>" placeholder="Ví dụ: Công ty Thực phẩm Gofood">
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Người liên hệ</label>
                        <input type="text" class="form-control" name="contact_person" 
                               value="<?= htmlspecialchars($supplier['contact_person'] ?? '') ?>" placeholder="Tên người đại diện">
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Số điện thoại</label>
                        <input type="text" class="form-control" name="phone" 
                               value="<?= htmlspecialchars($supplier['phone'] ?? '') ?>" placeholder="Nhập số điện thoại">
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">Địa chỉ</label>
                        <input type="text" class="form-control" name="address" 
                               value="<?= htmlspecialchars($supplier['address'] ?? '') ?>" placeholder="Nhập địa chỉ nhà cung cấp">
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Công nợ ban đầu (VNĐ)</label>
                        <input type="number" step="1000" class="form-control fw-bold text-danger" name="debt_balance" 
                               value="<?= htmlspecialchars($supplier['debt_balance'] ?? '0') ?>">
                        <small class="text-muted">Chỉ nhập nếu NCC này đang có dư nợ cũ.</small>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Trạng thái hợp tác</label>
                        <select class="form-select fw-bold" name="status">
                            <option value="active" class="text-success" <?= (isset($supplier['status']) && $supplier['status'] == 'active') ? 'selected' : '' ?>>✅ Đang hợp tác</option>
                            <option value="inactive" class="text-secondary" <?= (isset($supplier['status']) && $supplier['status'] == 'inactive') ? 'selected' : '' ?>>⛔ Ngừng hợp tác</option>
                        </select>
                    </div>
                </div>

                <div class="mt-4 pt-3 border-top text-end">
                    <a href="index.php?c=stock&a=index&tab=suppliers" class="btn btn-secondary rounded-3 px-4 me-2">Hủy bỏ</a>
                    <button type="submit" class="btn btn-gold-lux rounded-3 px-5">💾 Lưu Thông Tin</button>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>