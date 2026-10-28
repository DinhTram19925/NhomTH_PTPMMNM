<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý Kho - RoyalDine 5S</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --gold-primary: #c9a050;
            --gold-dark: #a8823b;
            --gold-light: #fdf8ee;
            --charcoal: #212529;
        }

        body { 
            background-color: #f8f9fa; 
            color: var(--charcoal);
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; 
        }
        
        /* Thẻ Card Sáng Sủa, Sang Trọng */
        .lux-card {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(201, 160, 80, 0.1);
            border: 1px solid rgba(201, 160, 80, 0.25);
            overflow: hidden;
        }

        /* TIÊU ĐỀ BẢNG (HEADER) ĐƯỢC LÀM NỔI BẬT RÕ RỆT */
        .lux-table-header {
            background-color: #1a1d20 !important;
            color: var(--gold-primary) !important;
            border-bottom: 4px solid var(--gold-primary);
        }
        .lux-table-header th {
            font-weight: 700 !important;
            font-size: 0.9rem !important;
            letter-spacing: 1px;
            text-transform: uppercase;
            padding-top: 16px !important;
            padding-bottom: 16px !important;
        }

        /* Màu sắc các dòng dữ liệu xen kẽ nhau (Zebra Striping tinh tế) */
        .table-striped tbody tr:nth-of-type(odd) {
            background-color: #ffffff;
        }
        .table-striped tbody tr:nth-of-type(even) {
            background-color: #fafbfc;
        }
        .table-hover tbody tr:hover {
            background-color: var(--gold-light) !important;
        }

        /* Nút bấm Vàng Kim Chủ Đạo */
        .btn-gold-lux {
            background: linear-gradient(135deg, #c9a050 0%, #a8823b 100%);
            color: #fff;
            font-weight: 600;
            border: none;
            box-shadow: 0 4px 12px rgba(201, 160, 80, 0.25);
            transition: all 0.3s ease;
        }
        .btn-gold-lux:hover {
            background: linear-gradient(135deg, #d6b05e, #b89043);
            color: #fff;
            box-shadow: 0 6px 16px rgba(201, 160, 80, 0.35);
        }

        /* Nút Quay Lại Tinh Tế */
        .btn-return-lux {
            background-color: #ffffff;
            color: #495057;
            border: 1px solid #ced4da;
            font-weight: 600;
            padding: 8px 28px;
            border-radius: 8px;
            transition: all 0.2s ease;
            box-shadow: 0 2px 5px rgba(0,0,0,0.02);
        }
        .btn-return-lux:hover {
            background-color: var(--gold-light);
            color: var(--gold-dark);
            border-color: var(--gold-primary);
        }

        /* Tab chuyển đổi */
        .tab-btn {
            font-weight: 600;
            border-radius: 8px !important;
            padding: 8px 22px;
            transition: all 0.25s;
            font-size: 0.95rem;
        }
        .tab-btn.active-tab {
            background: linear-gradient(135deg, #c9a050 0%, #a8823b 100%) !important;
            color: #fff !important;
            box-shadow: 0 4px 10px rgba(201, 160, 80, 0.25);
            border: none;
        }
        .tab-btn.inactive-tab {
            background-color: #ffffff;
            color: #6c757d;
            border: 1px solid #dee2e6;
        }
        .tab-btn.inactive-tab:hover {
            background-color: var(--gold-light);
            color: var(--gold-dark);
            border-color: rgba(201, 160, 80, 0.4);
        }
        
        .form-control {
            background-color: #fff !important;
            border-color: rgba(201, 160, 80, 0.4) !important;
            color: var(--charcoal) !important;
            border-radius: 8px 0 0 8px;
        }
        .form-control:focus {
            box-shadow: 0 0 0 0.2rem rgba(201, 160, 80, 0.2) !important;
            border-color: var(--gold-primary) !important;
        }
    </style>
</head>
<body>

<div class="container py-4" style="max-width: 1250px;">
    
    <!-- Tiêu đề & Nút thao tác phía trên -->
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom" style="border-color: rgba(201, 160, 80, 0.3) !important;">
        <h3 class="fw-bold m-0" style="color: var(--charcoal); letter-spacing: 0.5px;">
            QUẢN LÝ KHO & DANH MỤC
        </h3>
        <div class="d-flex gap-2">
            <a href="index.php?c=stock&a=import" class="btn btn-gold-lux btn-sm px-4 py-2" style="border-radius: 8px;">
                Lập Phiếu Nhập
            </a>
            <a href="index.php?c=stock&a=export" class="btn btn-sm px-4 py-2 fw-bold text-white shadow-sm" style="border-radius: 8px; background: linear-gradient(135deg, #dc3545 0%, #b02a37 100%); border: none;">
                Lập Phiếu Xuất
            </a>
        </div>
    </div>

    <!-- Thanh Điều hướng Tab & Tìm Kiếm -->
    <div class="row align-items-center mb-4">
        <!-- Tabs -->
        <div class="col-md-5">
            <div class="d-flex gap-2">
                <a href="index.php?c=stock&a=index&tab=ingredients" class="btn tab-btn <?= $active_tab == 'ingredients' ? 'active-tab' : 'inactive-tab' ?>">
                    Nguyên vật liệu
                </a>
                <a href="index.php?c=stock&a=index&tab=suppliers" class="btn tab-btn <?= $active_tab == 'suppliers' ? 'active-tab' : 'inactive-tab' ?>">
                    Nhà cung cấp
                </a>
            </div>
        </div>

        <!-- Form Tìm Kiếm -->
        <div class="col-md-7">
            <form method="GET" action="index.php" class="d-flex justify-content-end" onsubmit="return validateSearch(this);">
                <input type="hidden" name="c" value="stock">
                <input type="hidden" name="a" value="index">
                <input type="hidden" name="tab" value="<?= htmlspecialchars($active_tab) ?>">
                <div class="input-group shadow-sm" style="max-width: 400px;">
                    <input class="form-control form-control-sm px-3" type="search" name="search" id="searchInput" value="<?= htmlspecialchars($search) ?>" placeholder="Nhập từ khóa tìm kiếm..." maxlength="50">
                    <button class="btn btn-gold-lux btn-sm px-4 fw-bold" type="submit" style="border-radius: 0 8px 8px 0;">Tìm kiếm</button>
                    <?php if(!empty($search)): ?>
                        <a href="index.php?c=stock&a=index&tab=<?= htmlspecialchars($active_tab) ?>" class="btn btn-outline-secondary btn-sm d-flex align-items-center ms-1" style="border-radius: 8px; border-color: #dee2e6;" title="Xóa tìm kiếm">✕</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- KHUNG HIỂN THỊ DỮ LIỆU -->
    <div class="lux-card mb-4">
        
        <?php if ($active_tab == 'ingredients'): ?>
        <!-- ================= TAB 1: NGUYÊN VẬT LIỆU ================= -->
        <div class="table-responsive m-0">
            <table class="table table-striped table-hover align-middle mb-0" style="font-size: 0.95rem;">
                <thead class="lux-table-header">
                    <tr>
                        <th class="py-3 ps-4">Mã NVL</th>
                        <th class="py-3">Tên nguyên liệu</th>
                        <th class="py-3 text-center">ĐVT</th>
                        <th class="py-3 text-end">Tồn kho</th>
                        <th class="py-3 text-end">Mức tối thiểu</th>
                        <th class="py-3 text-center pe-4">Trạng thái</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($ingredients)): ?>
                        <tr><td colspan="6" class="text-center py-5 text-muted fw-bold">Không tìm thấy nguyên vật liệu phù hợp!</td></tr>
                    <?php else: foreach ($ingredients as $item): 
                        $isLowStock = $item['stock_quantity'] <= $item['min_stock_level'];
                    ?>
                    <tr class="<?= $isLowStock ? 'table-danger' : '' ?>">
                        <td class="ps-4 fw-bold text-secondary font-monospace">#<?= htmlspecialchars($item['ingredient_code']) ?></td>
                        <td class="fw-bold text-dark"><?= htmlspecialchars($item['name']) ?></td>
                        <td class="text-center"><span class="badge bg-light text-dark border px-2 py-1"><?= htmlspecialchars($item['unit']) ?></span></td>
                        <td class="text-end fw-bold <?= $isLowStock ? 'text-danger' : 'text-success' ?>">
                            <?= number_format($item['stock_quantity'], 2) ?>
                        </td>
                        <td class="text-end text-muted"><?= number_format($item['min_stock_level'], 2) ?></td>
                        <td class="text-center pe-4">
                            <?php if($isLowStock): ?>
                                <span class="badge bg-danger px-3 py-2 rounded-pill shadow-sm text-white">Cần nhập hàng</span>
                            <?php else: ?>
                                <span class="badge bg-success px-3 py-2 rounded-pill shadow-sm text-white" style="background-color: #198754 !important;">An toàn</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Phân trang Nguyên vật liệu -->
        <?php if ($maxPage_ing > 1): ?>
        <div class="card-footer bg-white py-3 border-top" style="border-color: rgba(201, 160, 80, 0.2) !important;">
            <nav>
                <ul class="pagination pagination-sm justify-content-end mb-0">
                    <?php for ($i = 1; $i <= $maxPage_ing; $i++): ?>
                        <li class="page-item <?= $i == $page_ing ? 'active' : '' ?>">
                            <a class="page-link <?= $i == $page_ing ? 'text-white border-0 fw-bold' : 'text-dark bg-white border' ?>" style="<?= $i == $page_ing ? 'background-color: var(--gold-primary) !important;' : '' ?>" href="index.php?c=stock&a=index&tab=ingredients&search=<?= urlencode($search) ?>&page_ing=<?= $i ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        </div>
        <?php endif; ?>

        <?php else: ?>
        <!-- ================= TAB 2: NHÀ CUNG CẤP ================= -->
        <div class="table-responsive m-0">
            <table class="table table-striped table-hover align-middle mb-0" style="font-size: 0.95rem;">
                <thead class="lux-table-header">
                    <tr>
                        <th class="py-3 ps-4">Tên đối tác</th>
                        <th class="py-3">Người liên hệ</th>
                        <th class="py-3 text-center">Số điện thoại</th>
                        <th class="py-3">Địa chỉ</th>
                        <th class="py-3 text-end pe-4">Công nợ hiện tại</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($suppliers)): ?>
                        <tr><td colspan="5" class="text-center py-5 text-muted fw-bold">Không tìm thấy nhà cung cấp phù hợp!</td></tr>
                    <?php else: foreach ($suppliers as $sup): ?>
                    <tr>
                        <td class="ps-4 fw-bold text-dark"><?= htmlspecialchars($sup['name']) ?></td>
                        <td class="text-secondary"><?= htmlspecialchars($sup['contact_person']) ?></td>
                        <td class="text-center text-primary fw-semibold"><?= htmlspecialchars($sup['phone']) ?></td>
                        <td class="text-muted small"><?= htmlspecialchars($sup['address']) ?></td>
                        <td class="text-end fw-bold pe-4" style="font-size: 1.05rem; color: var(--gold-dark);">
                            <?= number_format($sup['debt_balance'], 0, ',', '.') ?> đ
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Phân trang Nhà cung cấp -->
        <?php if ($maxPage_sup > 1): ?>
        <div class="card-footer bg-white py-3 border-top" style="border-color: rgba(201, 160, 80, 0.2) !important;">
            <nav>
                <ul class="pagination pagination-sm justify-content-end mb-0">
                    <?php for ($i = 1; $i <= $maxPage_sup; $i++): ?>
                        <li class="page-item <?= $i == $page_sup ? 'active' : '' ?>">
                            <a class="page-link <?= $i == $page_sup ? 'text-white border-0 fw-bold' : 'text-dark bg-white border' ?>" style="<?= $i == $page_sup ? 'background-color: var(--gold-primary) !important;' : '' ?>" href="index.php?c=stock&a=index&tab=suppliers&search=<?= urlencode($search) ?>&page_sup=<?= $i ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        </div>
        <?php endif; ?>

        <?php endif; ?>

    </div>

    <!-- KHU VỰC CUỐI TRANG: NÚT QUAY LẠI TINH TẾ -->
    <div class="text-center pb-4">
        <button onclick="history.back()" class="btn btn-return-lux shadow-sm">
            Quay lại
        </button>
    </div>

</div>

<!-- Script Ràng buộc logic Form Tìm Kiếm -->
<script>
function validateSearch(form) {
    const input = form.querySelector('input[name="search"]');
    const value = input.value.trim();
    if (value === "" && input.value !== "") {
        input.value = "";
    }
    return true;
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>