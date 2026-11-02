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
        .lux-table tbody tr:nth-child(even) td {
            background-color: #fcfbf9; 
        }
        .lux-table tbody tr:hover td {
            background-color: #fdf8ee !important; 
            color: #a8823b; 
        }
        .table-responsive {
            max-height: 550px; 
            overflow-y: auto;
        }
        .lux-table thead th {
            position: sticky; 
            top: 0;
            z-index: 10;
            background: #1a1d20 !important; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .kpi-card { background: #fff; border-radius: 12px; padding: 20px; border: 1px solid rgba(0,0,0,0.05); box-shadow: 0 4px 12px rgba(0,0,0,0.02); display: flex; align-items: center; gap: 15px; }
        .kpi-icon { width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
        .btn-gold-lux { background: linear-gradient(135deg, #c9a050 0%, #a8823b 100%); color: #fff; font-weight: 600; border: none; box-shadow: 0 4px 12px rgba(201, 160, 80, 0.2); }
        .btn-gold-lux:hover { background: linear-gradient(135deg, #d6b05e, #b89043); color: #fff; }
        
        .btn-import-lux { background-color: var(--btn-import) !important; color: #fff !important; font-weight: 600; border: none; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .btn-import-lux:hover { background-color: var(--btn-import-hover) !important; color: #fff !important; }
        
        .btn-export-lux { background-color: var(--btn-export) !important; color: #fff !important; font-weight: 600; border: none; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .btn-export-lux:hover { background-color: var(--btn-export-hover) !important; color: #fff !important; }
        
        .btn-audit-lux { background-color: var(--btn-audit) !important; color: #fff !important; font-weight: 600; border: none; box-shadow: 0 4px 10px rgba(184, 144, 67, 0.3); }
        .btn-audit-lux:hover { background-color: var(--btn-audit-hover) !important; color: #fff !important; }
        
        .btn-outline-excel { border: 1px solid #28a745; color: #28a745; background: #fff; font-weight: 600; }
        .btn-outline-excel:hover { background: #28a745; color: #fff; }
        .tab-btn { 
        font-weight: 600 !important; 
        border-radius: 8px !important; 
        padding: 10px 12px !important; 
        font-size: 0.9rem !important; 
        color: #6c757d !important; 
        border: 1px solid #dee2e6 !important; 
        background-color: #ffffff !important; 
        text-align: center !important; 
        white-space: nowrap !important; 
        text-decoration: none !important;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05); /* Thêm chút bóng mờ cho nút nổi bật hơn */
    }
    .tab-btn.active-tab { 
        background: linear-gradient(135deg, #c9a050 0%, #a8823b 100%) !important; 
        color: #fff !important; 
        border: 1px solid #a8823b !important; 
    }
    .tab-btn:hover:not(.active-tab) { 
        background-color: var(--gold-light) !important; 
        color: var(--gold-dark) !important; 
        border-color: var(--gold-primary) !important;
    }
        .tab-icon { margin-right: 6px; font-size: 1.05rem; }
        .form-control, .form-select { border-color: rgba(0, 0, 0, 0.15) !important; }
        .form-control:focus, .form-select:focus { box-shadow: 0 0 0 0.2rem rgba(201, 160, 80, 0.2) !important; border-color: var(--gold-primary) !important; }
        .badge-safe { background-color: #e8f5e9 !important; color: #2e7d32 !important; border: 1px solid #c8e6c9 !important; font-weight: 600; }
        .badge-danger-lux { background-color: #ffebee !important; color: #c62828 !important; border: 1px solid #ffcdd2 !important; font-weight: 600; }
        .badge-group { background-color: #f8f9fa; color: #6c757d; border: 1px solid #dee2e6; font-weight: 500; }
        .icon-action { width: 18px; height: 18px; stroke-width: 2; stroke: currentColor; fill: none; stroke-linecap: round; stroke-linejoin: round; }
        .btn-icon { padding: 5px 8px; border-radius: 6px; border: 1px solid #dee2e6; background: #fff; color: #6c757d; transition: all 0.2s; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; text-decoration: none;}
        .btn-icon:hover.btn-edit { background: var(--gold-primary); color: #fff; border-color: var(--gold-primary); }
        .btn-icon:hover.btn-delete { background: #dc3545; color: #fff; border-color: #dc3545; }
        .pagination .page-link { color: var(--charcoal); border-color: #dee2e6; font-weight: 500; }
        .pagination .page-item.active .page-link { background-color: var(--gold-primary); border-color: var(--gold-primary); color: #fff; }
        /* Nút Quay lại / Đến trang sau */
    .btn-return-lux { 
        background-color: #ffffff !important; 
        color: #495057 !important; 
        border: 1.5px solid rgba(201, 160, 80, 0.4) !important; 
        font-weight: 600 !important; 
        transition: all 0.2s ease !important; 
    }
    
    .btn-return-lux:hover { 
        background-color: var(--gold-light) !important; 
        color: var(--gold-dark) !important; 
        border-color: var(--gold-primary) !important; 
    }
        .badge-meat { background-color: #fce4ec !important; color: #c2185b !important; border: 1px solid #f8bbd0 !important; }
        .badge-seafood { background-color: #e3f2fd !important; color: #1976d2 !important; border: 1px solid #bbdefb !important; }
        .badge-dairy { background-color: #fff3e0 !important; color: #e65100 !important; border: 1px solid #ffe0b2 !important; }
        .badge-veg { background-color: #e8f5e9 !important; color: #2e7d32 !important; border: 1px solid #c8e6c9 !important; }
        .badge-alcohol { background-color: #f3e5f5 !important; color: #7b1fa2 !important; border: 1px solid #e1bee7 !important; }
        .badge-drink { background-color: #e0f7fa !important; color: #00838f !important; border: 1px solid #b2ebf2 !important; }
        .badge-default { background-color: #f5f5f5 !important; color: #616161 !important; border: 1px solid #e0e0e0 !important; }
    </style>
</head>
<body>
<?php 
    require_once __DIR__ . '/../layouts/header.php';
    
    $active_tab = $_GET['tab'] ?? 'dashboard'; 
    
    $role = $_SESSION['user']['role_code'] ?? '';
?>
<div class="container-fluid py-4 px-4">
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
            <?php if (in_array($role, ['ADMIN', 'STOCK'])): ?>
                <a href="index.php?c=stock_receipt&a=create&type=import" class="btn btn-import-lux btn-sm px-4 py-2 rounded-3">Tạo Phiếu Nhập</a>
                <a href="index.php?c=stock_receipt&a=create&type=export" class="btn btn-export-lux btn-sm px-4 py-2 rounded-3">Tạo Phiếu Xuất</a>
                <a href="index.php?c=stock_receipt&a=audit" class="btn btn-audit-lux btn-sm px-4 py-2 rounded-3">Kiểm Kê</a>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="d-flex w-100 gap-2 mb-4 overflow-auto">
        <!-- TỔNG QUAN: ADMIN, STOCK, WAITER, CHEF -->
        <?php if (in_array($role, ['ADMIN', 'STOCK', 'WAITER', 'CHEF'])): ?>
        <a href="index.php?c=stock&a=index&tab=dashboard" class="btn tab-btn flex-fill <?= $active_tab == 'dashboard' ? 'active-tab' : '' ?>">
            <span class="tab-icon">📊</span> Tổng quan
        </a>
        <?php endif; ?>
        
        <!-- NGUYÊN LIỆU: ADMIN, STOCK, CHEF -->
        <?php if (in_array($role, ['ADMIN', 'STOCK', 'CHEF'])): ?>
        <a href="index.php?c=stock&a=index&tab=ingredients" class="btn tab-btn flex-fill <?= $active_tab == 'ingredients' ? 'active-tab' : '' ?>">
            <span class="tab-icon">🥩</span> Nguyên liệu
        </a>
        <?php endif; ?>
        
        <!-- NHÀ CUNG CẤP: ADMIN, STOCK -->
        <?php if (in_array($role, ['ADMIN', 'STOCK'])): ?>
        <a href="index.php?c=stock&a=index&tab=suppliers" class="btn tab-btn flex-fill <?= $active_tab == 'suppliers' ? 'active-tab' : '' ?>">
            <span class="tab-icon">🏢</span> Nhà cung cấp
        </a>
        <?php endif; ?>
        
        <!-- LỊCH SỬ KHO: ADMIN, STOCK, CHEF -->
        <?php if (in_array($role, ['ADMIN', 'STOCK', 'CHEF'])): ?>
        <a href="index.php?c=stock&a=index&tab=history" class="btn tab-btn flex-fill <?= $active_tab == 'history' ? 'active-tab' : '' ?>">
            <span class="tab-icon">📜</span> Lịch sử
        </a>
        <?php endif; ?>
        
        <!-- QUỸ & CA: ADMIN, STOCK, WAITER -->
        <?php if (in_array($role, ['ADMIN', 'STOCK', 'WAITER'])): ?>
        <a href="index.php?c=stock&a=index&tab=cash_shift" class="btn tab-btn flex-fill <?= $active_tab == 'cash_shift' ? 'active-tab' : '' ?>">
            <span class="tab-icon">💰</span> Quỹ & Ca
        </a>
        <?php endif; ?>
        
        <!-- BÁO CÁO KHO: ADMIN, STOCK, CHEF -->
        <?php if (in_array($role, ['ADMIN', 'STOCK', 'CHEF'])): ?>
        <a href="index.php?c=stock&tab=report" class="btn tab-btn flex-fill <?= (isset($_GET['tab']) && $_GET['tab'] == 'report') ? 'active-tab' : '' ?>">
            <span class="tab-icon">📈</span> Báo cáo
        </a>
        <?php endif; ?>
    </div>

    <?php 
        if ($active_tab == 'dashboard' && in_array($role, ['ADMIN', 'STOCK', 'WAITER', 'CHEF'])) {
            require_once __DIR__ . '/dashboard_view.php';
        } elseif ($active_tab == 'ingredients' && in_array($role, ['ADMIN', 'STOCK', 'CHEF'])) {
            require_once __DIR__ . '/ingredients_view.php';
        } elseif ($active_tab == 'suppliers' && in_array($role, ['ADMIN', 'STOCK'])) {
            require_once __DIR__ . '/suppliers_view.php';
        } elseif ($active_tab == 'history' && in_array($role, ['ADMIN', 'STOCK', 'CHEF'])) { 
            require_once 'history_view.php'; 
        } elseif ($active_tab == 'cash_shift' && in_array($role, ['ADMIN', 'STOCK', 'WAITER'])) { 
            require_once __DIR__ . '/cash_shift_view.php'; 
        } elseif ($active_tab == 'report' && in_array($role, ['ADMIN', 'STOCK', 'CHEF'])) {
            include __DIR__ . '/report_view.php';    
        } else {
            echo '<div class="alert alert-danger text-center fw-bold p-4">❌ BẠN KHÔNG CÓ QUYỀN TRUY CẬP CHỨC NĂNG NÀY!</div>';
        }
    ?>
    
    <div class="d-flex justify-content-center gap-3 pb-5 mt-4">
        <button onclick="history.back()" class="btn btn-return-lux px-4 py-2 shadow-sm rounded-3">
            ⬅️ Quay lại trang trước
        </button>
        <button onclick="history.forward()" class="btn btn-return-lux px-4 py-2 shadow-sm rounded-3">
            Đến trang sau ➡️
        </button>
    </div>
</div>
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