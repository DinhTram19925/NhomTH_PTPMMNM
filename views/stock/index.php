<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Trung Tâm Điều Hành Kho - RoyalDine 5S</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --gold-primary: #c9a050;
            --gold-dark: #a8823b;
            --gold-light: #fdf8ee;
            --charcoal: #1a1d20;
            --gray-light: #f8f9fa;
            
            --btn-import: #2c3e50;
            --btn-import-hover: #1a252f;
            --btn-export: #8b2635; 
            --btn-export-hover: #611a25;
            --btn-audit: #b89043;  
            --btn-audit-hover: #9c7834;
        }
        body { 
            background-color: var(--gray-light); 
            color: #333;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; 
        }
        
        .lux-card {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.06);
            border: 1px solid rgba(201, 160, 80, 0.15);
            overflow: hidden;
        }

        /* BẢNG DỮ LIỆU CHUẨN 5 SAO */
        .lux-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        .lux-table thead th {
            background: linear-gradient(to right, #1a1d20, #2c3136) !important;
            color: #d4af37 !important;
            font-weight: 700 !important;
            font-size: 0.85rem !important;
            letter-spacing: 1px;
            text-transform: uppercase;
            padding: 18px 12px !important;
            border: none !important;
            border-bottom: 3px solid #c9a050 !important;
            white-space: nowrap;
        }
        
        .lux-table tbody tr {
            background-color: #ffffff;
            transition: background-color 0.2s ease;
        }
        .lux-table tbody tr:nth-child(even) {
            background-color: #fcfbf9;
        }
        .lux-table tbody tr:hover {
            background-color: #fdf8ee !important; 
        }
        
        .lux-table td {
            padding: 16px 12px;
            vertical-align: middle;
            border-bottom: 1px solid #f0eaeb;
        }

        /* ĐIỂM NHẤN DỮ LIỆU QUAN TRỌNG */
        .badge-code {
            background: #f0f0f0;
            color: #555;
            border: 1px solid #e0e0e0;
            font-family: monospace;
            font-size: 0.85rem;
            padding: 4px 8px;
            border-radius: 6px;
            font-weight: 600;
        }
        .item-name {
            font-size: 1.05rem;
            font-weight: 700;
            color: #212529;
        }
        .highlight-price {
            color: #a8823b;
            font-weight: 700;
            font-size: 1rem;
        }
        .stock-safe {
            color: #1b5e20; 
            font-weight: 800;
            font-size: 1.1rem;
        }
        .stock-danger {
            color: #b71c1c; 
            font-weight: 800;
            font-size: 1.1rem;
        }
        
        /* KPI Cards */
        .kpi-card { background: #fff; border-radius: 12px; padding: 20px; border: 1px solid rgba(0,0,0,0.05); box-shadow: 0 4px 12px rgba(0,0,0,0.02); display: flex; align-items: center; gap: 15px; }
        .kpi-icon { width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }

        /* BUTTONS */
        .btn-gold-lux { background: linear-gradient(135deg, #c9a050 0%, #a8823b 100%); color: #fff; font-weight: 600; border: none; box-shadow: 0 4px 12px rgba(201, 160, 80, 0.2); }
        .btn-gold-lux:hover { background: linear-gradient(135deg, #d6b05e, #b89043); color: #fff; }
        .btn-import-lux { background-color: var(--btn-import); color: #fff; font-weight: 600; border: none; }
        .btn-import-lux:hover { background-color: var(--btn-import-hover); color: #fff; }
        .btn-export-lux { background-color: var(--btn-export); color: #fff; font-weight: 600; border: none; }
        .btn-export-lux:hover { background-color: var(--btn-export-hover); color: #fff; }
        .btn-audit-lux { background-color: var(--btn-audit); color: #fff; font-weight: 600; border: none; box-shadow: 0 4px 10px rgba(184, 144, 67, 0.3); }
        .btn-audit-lux:hover { background-color: var(--btn-audit-hover); color: #fff; }
        .btn-outline-excel { border: 1px solid #28a745; color: #28a745; background: #fff; font-weight: 600; }
        .btn-outline-excel:hover { background: #28a745; color: #fff; }

        /* TABS TRẢI DÀI */
        .tab-btn { font-weight: 600; border-radius: 8px !important; padding: 10px 12px; font-size: 0.9rem; color: #6c757d; border: 1px solid #dee2e6; background-color: #ffffff; text-align: center; white-space: nowrap; }
        .tab-btn.active-tab { background: linear-gradient(135deg, #c9a050 0%, #a8823b 100%) !important; color: #fff !important; border: none; }
        .tab-btn:hover:not(.active-tab) { background-color: var(--gold-light); color: var(--gold-dark); }
        .tab-icon { margin-right: 6px; font-size: 1.05rem; }

        /* FORM & BADGES */
        .form-control, .form-select { border-color: rgba(0, 0, 0, 0.15) !important; }
        .form-control:focus, .form-select:focus { box-shadow: 0 0 0 0.2rem rgba(201, 160, 80, 0.2) !important; border-color: var(--gold-primary) !important; }

        .badge-safe { background-color: #e8f5e9 !important; color: #2e7d32 !important; border: 1px solid #c8e6c9 !important; font-weight: 600; }
        .badge-danger-lux { background-color: #ffebee !important; color: #c62828 !important; border: 1px solid #ffcdd2 !important; font-weight: 600; }
        .badge-group { background-color: #f8f9fa; color: #6c757d; border: 1px solid #dee2e6; font-weight: 500; }

        /* SVG ICONS THAO TÁC */
        .icon-action { width: 18px; height: 18px; stroke-width: 2; stroke: currentColor; fill: none; stroke-linecap: round; stroke-linejoin: round; }
        .btn-icon { padding: 5px 8px; border-radius: 6px; border: 1px solid #dee2e6; background: #fff; color: #6c757d; transition: all 0.2s; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; text-decoration: none;}
        .btn-icon:hover.btn-edit { background: var(--gold-primary); color: #fff; border-color: var(--gold-primary); }
        .btn-icon:hover.btn-delete { background: #dc3545; color: #fff; border-color: #dc3545; }

        /* PHÂN TRANG & NÚT QUAY LẠI CUỐI TRANG */
        .pagination .page-link { color: var(--charcoal); border-color: #dee2e6; font-weight: 500; }
        .pagination .page-item.active .page-link { background-color: var(--gold-primary); border-color: var(--gold-primary); color: #fff; }
        .btn-return-lux { background-color: #ffffff; color: #495057; border: 1.5px solid rgba(201, 160, 80, 0.4); font-weight: 600; transition: all 0.2s ease; }
        .btn-return-lux:hover { background-color: var(--gold-light); color: var(--gold-dark); border-color: var(--gold-primary); }
    </style>
</head>
<body>

<?php $active_tab = $_GET['tab'] ?? 'dashboard'; ?>

<div class="container-fluid py-4 px-4">

    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom" style="border-color: rgba(201, 160, 80, 0.3) !important;">
        <div>
            <h3 class="fw-bold m-0" style="color: var(--charcoal); letter-spacing: 0.5px; text-transform: uppercase;">
                TRUNG TÂM ĐIỀU HÀNH KHO
            </h3>
            <div class="text-muted fw-semibold mt-1" style="font-size: 0.9rem;">
                Chi nhánh: <span class="text-primary">Chi nhánh Trung tâm</span> | Ngày xem: <?= date('d/m/Y') ?>
            </div>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <a href="index.php?c=stock_receipt&a=create&type=import" class="btn btn-import-lux btn-sm px-4 py-2 rounded-3">Tạo Phiếu Nhập</a>
            <a href="index.php?c=stock_receipt&a=create&type=export" class="btn btn-export-lux btn-sm px-4 py-2 rounded-3">Tạo Phiếu Xuất</a>
            <a href="index.php?c=inventory&a=audit" class="btn btn-audit-lux btn-sm px-4 py-2 rounded-3">Kiểm Kê</a>
        </div>
    </div>

    <!-- TABS TRẢI DÀI -->
    <div class="d-flex w-100 gap-2 mb-4 overflow-auto">
        <a href="index.php?c=stock&a=index&tab=dashboard" class="btn tab-btn flex-fill <?= $active_tab == 'dashboard' ? 'active-tab' : '' ?>">
            <span class="tab-icon">📊</span> Tổng quan
        </a>
        <a href="index.php?c=stock&a=index&tab=ingredients" class="btn tab-btn flex-fill <?= $active_tab == 'ingredients' ? 'active-tab' : '' ?>">
            <span class="tab-icon">🥩</span> Nguyên liệu
        </a>
        <a href="index.php?c=stock&a=index&tab=suppliers" class="btn tab-btn flex-fill <?= $active_tab == 'suppliers' ? 'active-tab' : '' ?>">
            <span class="tab-icon">🏢</span> Nhà cung cấp
        </a>
        <a href="index.php?c=stock_receipt&a=history" class="btn tab-btn flex-fill">
            <span class="tab-icon">📜</span> Lịch sử
        </a>
        <a href="index.php?c=cash_shift&a=index" class="btn tab-btn flex-fill">
            <span class="tab-icon">💰</span> Quỹ & Ca
        </a>
        <a href="index.php?c=inventory&a=audit" class="btn tab-btn flex-fill">
            <span class="tab-icon">📝</span> Kiểm kê
        </a>
        <a href="index.php?c=report&a=stock" class="btn tab-btn flex-fill">
            <span class="tab-icon">📈</span> Báo cáo
        </a>
    </div>

    <?php if ($active_tab == 'dashboard'): ?>
    <!-- ================= TỔNG QUAN KHO ================= -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="kpi-card"><div class="kpi-icon bg-primary bg-opacity-10 text-primary">📦</div>
            <div>
                <div class="text-muted small fw-bold text-uppercase">Tổng loại nguyên liệu</div>
                <h4 class="mb-0 fw-bold"><?= isset($kpis['total_ingredients']) ? number_format($kpis['total_ingredients']) : 0 ?></h4>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="kpi-card"><div class="kpi-icon bg-warning bg-opacity-10 text-warning">⚠️</div>
            <div>
                <div class="text-muted small fw-bold text-uppercase">Tồn kho mức thấp</div>
                <h4 class="mb-0 fw-bold text-warning"><?= isset($kpis['low_stock']) ? number_format($kpis['low_stock']) : 0 ?></h4>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="kpi-card"><div class="kpi-icon bg-danger bg-opacity-10 text-danger">🛑</div>
            <div>
                <div class="text-muted small fw-bold text-uppercase">Đã hết hàng</div>
                <h4 class="mb-0 fw-bold text-danger"><?= isset($kpis['out_of_stock']) ? number_format($kpis['out_of_stock']) : 0 ?></h4>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="kpi-card"><div class="kpi-icon bg-success bg-opacity-10 text-success">📄</div>
            <div>
                <div class="text-muted small fw-bold text-uppercase">Phiếu tạo trong ngày</div>
                <h4 class="mb-0 fw-bold text-success"><?= isset($kpis['today_receipts']) ? number_format($kpis['today_receipts']) : 0 ?></h4>
            </div></div>
        </div>
    </div>

    <!-- Bảng Cảnh báo & Hoạt động -->
    <div class="row g-4">
        <div class="col-md-8">
            <div class="lux-card h-100">
                <div class="p-3 border-bottom bg-light d-flex justify-content-between align-items-center">
                    <h6 class="m-0 fw-bold text-danger">🚨 CẢNH BÁO TỒN KHO THẤP</h6>
                    <a href="index.php?c=stock_receipt&a=create&type=import" class="btn btn-sm btn-outline-danger px-3">Tạo phiếu nhập</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle m-0 text-sm">
                        <thead class="table-light text-muted">
                            <tr>
                                <th class="ps-4">Mã NVL</th>
                                <th>Tên Nguyên Liệu</th>
                                <th class="text-end">Tồn hiện tại</th>
                                <th class="text-end pe-4">Mức tối thiểu</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- ĐÃ SỬA THÔNG BÁO TẠI ĐÂY -->
                            <?php if (empty($alerts)): ?>
                                <tr><td colspan="4" class="text-center py-4 text-muted" style="font-size: 0.9rem;">Chưa có dữ liệu cảnh báo.</td></tr>
                            <?php else: foreach ($alerts as $al): ?>
                                <tr>
                                    <td class="ps-4 fw-bold font-monospace text-secondary">#<?= htmlspecialchars($al['ingredient_code']) ?></td>
                                    <td class="fw-bold"><?= htmlspecialchars($al['name']) ?></td>
                                    <td class="text-end fw-bold <?= $al['stock_quantity'] <= 0 ? 'text-danger' : 'text-warning' ?>"><?= number_format($al['stock_quantity'], 2) ?></td>
                                    <td class="text-end pe-4 text-muted"><?= number_format($al['min_stock_level'], 2) ?></td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!-- CỘT GIAO DỊCH GẦN ĐÂY -->
        <div class="col-md-4">
            <div class="lux-card h-100">
                <div class="p-3 border-bottom bg-light">
                    <h6 class="m-0 fw-bold text-dark">🕒 GIAO DỊCH GẦN ĐÂY</h6>
                </div>
                <div class="p-3">
                    <style>
                        /* CSS tạo hiệu ứng mượt mà khi hover vào giao dịch */
                        .act-link { transition: all 0.2s ease; border-radius: 8px; padding: 8px; margin-left: -8px; margin-right: -8px; }
                        .act-link:hover { background-color: #fdf8ee; }
                        .act-link:hover .act-title { color: #a8823b !important; }
                        .act-arrow { opacity: 0; transition: opacity 0.2s, transform 0.2s; transform: translateX(-5px); }
                        .act-link:hover .act-arrow { opacity: 1; transform: translateX(0); }
                    </style>

                    <?php if (empty($activities)): ?>
                        <div class="text-center text-muted py-3" style="font-size: 0.9rem;">Chưa có giao dịch nhập/xuất nào.</div>
                    <?php else: foreach ($activities as $act): 
                        $isImport = ($act['receipt_type'] == 'Import');
                        // Tạo đường dẫn truyền mã phiếu để sau này làm trang xem chi tiết
                        $detailUrl = "index.php?c=stock_receipt&a=show&code=" . htmlspecialchars($act['receipt_code']);
                    ?>
                        <a href="<?= $detailUrl ?>" class="d-flex gap-3 mb-2 border-bottom pb-2 text-decoration-none act-link">
                            <div class="<?= $isImport ? 'text-success' : 'text-danger' ?> fw-bold text-center" style="font-size: 0.9rem; min-width: 45px;">
                                <?= date('H:i', strtotime($act['created_at'])) ?><br>
                                <small class="text-muted" style="font-size: 0.75rem;"><?= date('d/m', strtotime($act['created_at'])) ?></small>
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-bold text-dark act-title" style="font-size: 0.95rem;">
                                    <?= $isImport ? 'Nhập kho' : 'Xuất kho' ?> (<?= htmlspecialchars($act['receipt_code']) ?>)
                                </div>
                                <small class="text-muted">Người lập ID: <?= $act['user_id'] ?></small>
                            </div>
                            <!-- Icon mũi tên nhỏ hiện ra khi hover -->
                            <div class="align-self-center text-muted act-arrow">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>
                            </div>
                        </a>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <?php elseif ($active_tab == 'ingredients'): ?>
    <!-- ================= NGUYÊN VẬT LIỆU ================= -->
    <div class="lux-card p-3 mb-4 d-flex justify-content-between align-items-center">
        <!-- Ràng buộc pattern -->
        <form method="GET" action="index.php" class="d-flex gap-2 w-75" onsubmit="return validateSearch(this);">
            <input type="hidden" name="c" value="stock">
            <input type="hidden" name="a" value="index">
            <input type="hidden" name="tab" value="ingredients">
            
            <input class="form-control form-control-sm rounded-3 w-25" type="search" name="search" id="searchInput" 
                   pattern="^[a-zA-Z0-9\sÀ-ỹ-]+$" title="Chỉ nhập chữ, số và dấu gạch ngang" 
                   placeholder="Mã hoặc Tên NVL..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
            
            <select class="form-select form-select-sm rounded-3 w-25" name="group_filter">
                <option value="">- Tất cả nhóm NVL -</option>
            </select>
            <select class="form-select form-select-sm rounded-3 w-25" name="unit_filter">
                <option value="">- Tất cả đơn vị tính -</option>
            </select>
            <select class="form-select form-select-sm rounded-3 w-25" name="status_filter">
                <option value="">- Trạng thái tồn -</option>
                <option value="low" <?= (isset($_GET['status_filter']) && $_GET['status_filter'] == 'low') ? 'selected' : '' ?>>Cảnh báo tồn thấp</option>
                <option value="safe" <?= (isset($_GET['status_filter']) && $_GET['status_filter'] == 'safe') ? 'selected' : '' ?>>An toàn</option>
            </select>
            <button class="btn btn-gold-lux btn-sm rounded-3 px-4" type="submit">Lọc</button>
        </form>
        <div class="d-flex gap-2">
            <a href="index.php?c=stock&a=form_ingredient" class="btn btn-gold-lux btn-sm px-4 rounded-3">+ Thêm nguyên liệu</a>
            <button class="btn btn-outline-excel btn-sm px-3 rounded-3">Xuất Excel</button>
        </div>
    </div>

    <!-- BẢNG DỮ LIỆU -->
    <div class="lux-card mb-4">
        <div class="table-responsive m-0">
            <table class="table lux-table align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">Mã NVL</th>
                        <th>Tên nguyên liệu</th>
                        <th class="text-center">Nhóm</th>
                        <th class="text-center">ĐVT</th>
                        <th class="text-end">Tồn hiện tại</th>
                        <th class="text-end">Tồn tối thiểu</th>
                        <th class="text-end">Giá vốn</th>
                        <th>Nhà cung cấp</th>
                        <th class="text-center">Trạng thái</th>
                        <th class="text-center pe-4">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($ingredients)): ?>
                        <tr><td colspan="10" class="text-center py-5 text-muted fw-bold">Chưa có dữ liệu nguyên vật liệu.</td></tr>
                    <?php else: foreach ($ingredients as $item): 
                        $isLowStock = $item['stock_quantity'] <= $item['min_stock_level'];
                    ?>
                    <tr>
                        <td class="ps-4"><span class="badge-code">#<?= htmlspecialchars($item['ingredient_code']) ?></span></td>
                        <td class="item-name"><?= htmlspecialchars($item['name']) ?></td>
                        <td class="text-center"><span class="badge badge-group px-2 py-1 rounded-2"><?= htmlspecialchars($item['group_name'] ?? 'Chưa phân nhóm') ?></span></td>
                        <td class="text-center fw-semibold text-muted"><?= htmlspecialchars($item['unit']) ?></td>
                        
                        <!-- Số lượng được làm nổi bật cực mạnh -->
                        <td class="text-end <?= $isLowStock ? 'stock-danger' : 'stock-safe' ?>"><?= number_format($item['stock_quantity'], 2) ?></td>
                        <td class="text-end text-muted" style="font-size: 0.9rem;"><?= number_format($item['min_stock_level'], 2) ?></td>
                        
                        <!-- Giá vốn Vàng Đồng -->
                        <td class="text-end highlight-price"><?= number_format($item['cost_price'], 0, ',', '.') ?> đ</td>
                        <td class="text-muted" style="font-size: 0.85rem;"><?= htmlspecialchars($item['supplier_name'] ?? 'Chưa xác định') ?></td>
                        
                        <td class="text-center">
                            <?php if($isLowStock): ?>
                                <span class="badge badge-danger-lux px-3 py-1 rounded-pill">Tồn thấp</span>
                            <?php else: ?>
                                <span class="badge badge-safe px-3 py-1 rounded-pill">An toàn</span>
                            <?php endif; ?>
                        </td>
                        
                        <!-- Icon thao tác -->
                        <td class="text-center pe-4 d-flex justify-content-center gap-1">
                            <a href="index.php?c=stock&a=form_ingredient&id=<?= $item['id'] ?>" class="btn-icon btn-edit" title="Sửa">
                                <svg class="icon-action" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                            </a>
                            <button class="btn-icon btn-delete" title="Ngừng sử dụng">
                                <svg class="icon-action" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line></svg>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- PHÂN TRANG (TỰ ĐỘNG THEO PHP) -->
        <?php if(!empty($ingredients) && isset($maxPage_ing) && $maxPage_ing > 1): 
            $page_ing = isset($_GET['page_ing']) ? (int)$_GET['page_ing'] : 1;
            $queryParams = $_GET;
            unset($queryParams['page_ing']);
            $queryString = http_build_query($queryParams);
        ?>
        <div class="p-3 border-top d-flex justify-content-end bg-white">
            <nav>
                <ul class="pagination pagination-sm m-0">
                    <li class="page-item <?= ($page_ing <= 1) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?<?= $queryString ?>&page_ing=<?= $page_ing - 1 ?>">Trước</a>
                    </li>
                    <?php for($i = 1; $i <= $maxPage_ing; $i++): ?>
                        <li class="page-item <?= ($i == $page_ing) ? 'active' : '' ?>">
                            <a class="page-link" href="?<?= $queryString ?>&page_ing=<?= $i ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= ($page_ing >= $maxPage_ing) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?<?= $queryString ?>&page_ing=<?= $page_ing + 1 ?>">Sau</a>
                    </li>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>

    <?php elseif ($active_tab == 'suppliers'): ?>
    <!-- ================= NHÀ CUNG CẤP ================= -->
    <div class="lux-card p-3 mb-4 bg-white d-flex justify-content-between align-items-center">
        <form method="GET" action="index.php" class="d-flex gap-2 w-50" onsubmit="return validateSearch(this);">
            <input type="hidden" name="c" value="stock">
            <input type="hidden" name="a" value="index">
            <input type="hidden" name="tab" value="suppliers">
            <input class="form-control form-control-sm rounded-3" type="search" name="search" pattern="^[a-zA-Z0-9\sÀ-ỹ-]+$" placeholder="Mã, Tên, SĐT..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
            <button class="btn btn-gold-lux btn-sm rounded-3 px-4" type="submit">Tìm</button>
        </form>
        <div class="d-flex gap-2">
            <button class="btn btn-gold-lux btn-sm px-4 rounded-3">+ Thêm nhà cung cấp</button>
            <button class="btn btn-outline-excel btn-sm px-3 rounded-3">Xuất Excel</button>
        </div>
    </div>

    <div class="lux-card mb-4">
        <div class="table-responsive m-0">
            <table class="table lux-table align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">Mã NCC</th>
                        <th>Tên nhà cung cấp</th>
                        <th>Liên hệ</th>
                        <th>Trạng thái</th>
                        <th class="text-end pe-4">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($suppliers)): ?>
                        <tr><td colspan="5" class="text-center py-5 text-muted fw-bold">Chưa có dữ liệu nhà cung cấp.</td></tr>
                    <?php else: foreach ($suppliers as $sup): ?>
                        <!-- Lặp Nhà Cung Cấp -->
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

        <!-- PHÂN TRANG NHÀ CUNG CẤP -->
        <?php if(!empty($suppliers) && isset($maxPage_sup) && $maxPage_sup > 1): 
            $page_sup = isset($_GET['page_sup']) ? (int)$_GET['page_sup'] : 1;
            $queryParams = $_GET;
            unset($queryParams['page_sup']);
            $queryString = http_build_query($queryParams);
        ?>
        <div class="p-3 border-top d-flex justify-content-end bg-white">
            <nav>
                <ul class="pagination pagination-sm m-0">
                    <li class="page-item <?= ($page_sup <= 1) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?<?= $queryString ?>&page_sup=<?= $page_sup - 1 ?>">Trước</a>
                    </li>
                    <?php for($i = 1; $i <= $maxPage_sup; $i++): ?>
                        <li class="page-item <?= ($i == $page_sup) ? 'active' : '' ?>">
                            <a class="page-link" href="?<?= $queryString ?>&page_sup=<?= $i ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= ($page_sup >= $maxPage_sup) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?<?= $queryString ?>&page_sup=<?= $page_sup + 1 ?>">Sau</a>
                    </li>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- NÚT ĐIỀU HƯỚNG CUỐI TRANG WEB -->
    <div class="d-flex justify-content-center gap-3 pb-5 mt-4">
        <button onclick="history.back()" class="btn btn-return-lux px-4 py-2 shadow-sm rounded-3">
            ⬅️ Quay lại trang trước
        </button>
        <button onclick="history.forward()" class="btn btn-return-lux px-4 py-2 shadow-sm rounded-3">
            Đến trang sau ➡️
        </button>
    </div>

</div>

<!-- LOGIC VALIDATE TÌM KIẾM -->
<script>
function validateSearch(form) {
    const searchInput = form.querySelector('input[name="search"]');
    if(searchInput) {
        let val = searchInput.value.trim();
        val = val.replace(/[^a-zA-Z0-9\sÀ-ỹ-]/g, ""); 
        searchInput.value = val;
        if (val === "") { searchInput.removeAttribute("name"); }
    }

    const selects = form.querySelectorAll('select');
    selects.forEach(select => {
        if (select.value === "") select.removeAttribute("name");
    });
    
    return true;
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>