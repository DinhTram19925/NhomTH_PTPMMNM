<?php
require_once __DIR__ . '/../models/StockModel.php';

class StockController {
    private $stockModel;

    public function __construct() {
        $this->stockModel = new StockModel();
    }

    public function index() {
        // 1. Nhận dữ liệu từ URL (GET)
        $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'dashboard'; // Mặc định mở Tổng quan
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        
        // Bắt thêm các bộ lọc từ giao diện
        $group_filter = isset($_GET['group_filter']) ? $_GET['group_filter'] : '';
        $unit_filter = isset($_GET['unit_filter']) ? $_GET['unit_filter'] : '';
        $status_filter = isset($_GET['status_filter']) ? $_GET['status_filter'] : '';
        
        $limit = 10; // Cố định 10 dòng trên 1 trang

        // Khởi tạo các biến rỗng để View không bị lỗi undefine variable
        $ingredients = [];
        $suppliers = [];
        $maxPage_ing = 0;
        $maxPage_sup = 0;
        
        // CÁC BIẾN DÀNH CHO TAB DASHBOARD
        $kpis = [];
        $alerts = [];
        $activities = [];

        // 2. CHỈ XỬ LÝ PHÂN TRANG / TRUY VẤN KHI ĐANG Ở TAB TƯƠNG ỨNG
        if ($active_tab == 'dashboard') {
            
            // Gọi hàm lấy dữ liệu cho trang Tổng quan kho
            $kpis = $this->stockModel->getDashboardKPIs();
            $alerts = $this->stockModel->getLowStockAlerts(5); // Lấy 5 cảnh báo tồn kho thấp
            $activities = $this->stockModel->getRecentActivities(5); // Lấy 5 hoạt động gần nhất

        } elseif ($active_tab == 'ingredients') {
            
            $page_ing = isset($_GET['page_ing']) ? (int)$_GET['page_ing'] : 1;
            $page_ing = max(1, $page_ing); // Đảm bảo trang luôn >= 1
            $offset_ing = ($page_ing - 1) * $limit;
            
            // Đếm tổng số dòng có tính các bộ lọc
            $total_ing = $this->stockModel->countIngredients($search, $group_filter, $unit_filter, $status_filter);
            $maxPage_ing = ceil($total_ing / $limit);
            
            // Lấy dữ liệu 10 dòng cho trang hiện tại
            $ingredients = $this->stockModel->getIngredients($search, $group_filter, $unit_filter, $status_filter, $limit, $offset_ing);

        } elseif ($active_tab == 'suppliers') {
            
            $page_sup = isset($_GET['page_sup']) ? (int)$_GET['page_sup'] : 1;
            $page_sup = max(1, $page_sup);
            $offset_sup = ($page_sup - 1) * $limit;
            
            // Truyền status_filter (Đang hợp tác/Ngừng) cho NCC
            $total_sup = $this->stockModel->countSuppliers($search, $status_filter);
            $maxPage_sup = ceil($total_sup / $limit);
            
            $suppliers = $this->stockModel->getSuppliers($search, $status_filter, $limit, $offset_sup);
        }

        // Gọi View để hiển thị
        require_once __DIR__ . '/../views/stock/index.php';
    }
}
?>