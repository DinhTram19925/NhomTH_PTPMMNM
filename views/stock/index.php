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
        /* 1. HIỆU ỨNG MÀU SẮC ĐỂ BẢNG KHÔNG BỊ TRẮNG ĐEN */
        .lux-table tbody tr:nth-child(even) td {
            background-color: #fcfbf9; /* Nền be nhạt cho dòng chẵn */
        }
        .lux-table tbody tr:hover td {
            background-color: #fdf8ee !important; /* Nền vàng nhạt khi hover */
            color: #a8823b; /* Chữ chuyển màu vàng kim */
        }

        /* 2. CHỮ IN ĐẬM VÀ MÀU ĐẶC TRƯNG CHO MÃ VÀ TÊN */
        .item-name {
            font-size: 1.05rem;
            font-weight: 700;
            color: #1a1d20;
        }

        /* 3. TÍNH NĂNG CUỘN CHUỘT CỐ ĐỊNH TIÊU ĐỀ (STICKY HEADER) */
        .table-responsive {
            max-height: 550px; /* Giới hạn chiều cao để kích hoạt thanh cuộn */
            overflow-y: auto;
        }
        .lux-table thead th {
            position: sticky; /* Giữ cố định header khi cuộn chuột */
            top: 0;
            z-index: 10;
            background: #1a1d20 !important; /* Đảm bảo header không bị trong suốt khi lướt lên */
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
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
        /* MÀU SẮC CHO TỪNG NHÓM NGUYÊN LIỆU (PASTEL SANG TRỌNG) */
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
        
        <!-- Đã sửa lại thẻ Lịch sử cho đồng bộ cấu trúc với các thẻ trên -->
        <a href="index.php?c=stock&a=index&tab=history" class="btn tab-btn flex-fill <?= $active_tab == 'history' ? 'active-tab' : '' ?>">
            <span class="tab-icon">📜</span> Lịch sử
        </a>
        
        <a href="index.php?c=cash_shift&a=index" class="btn tab-btn flex-fill">
            <span class="tab-icon">💰</span> Quỹ & Ca
        </a>
        
        <a href="index.php?c=report&a=stock" class="btn tab-btn flex-fill">
            <span class="tab-icon">📈</span> Báo cáo
        </a>
    </div>

    <!-- NẠP NỘI DUNG TỪNG TAB -->
    <?php 
        if ($active_tab == 'dashboard') {
            require_once __DIR__ . '/dashboard_view.php';
        } elseif ($active_tab == 'ingredients') {
            require_once __DIR__ . '/ingredients_view.php';
        } elseif ($active_tab == 'suppliers') {
            require_once __DIR__ . '/suppliers_view.php';
        } elseif ($active_tab == 'history') { 
            require_once 'history_view.php'; 
        }
    ?>

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