<?php
require_once __DIR__ . '/../models/StockModel.php';

class StockController {
    private $stockModel;

    public function __construct() {
        $this->stockModel = new StockModel();
    }

    public function index() {
        // Nhận dữ liệu từ URL (GET)
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'ingredients'; // Mặc định mở tab NVL
        
        $limit = 10; // Số dòng trên 1 trang

        // 1. Phân trang Nguyên Vật Liệu
        $page_ing = isset($_GET['page_ing']) ? (int)$_GET['page_ing'] : 1;
        $offset_ing = ($page_ing - 1) * $limit;
        $total_ing = $this->stockModel->countIngredients($search);
        $maxPage_ing = ceil($total_ing / $limit);
        $ingredients = $this->stockModel->getIngredients($search, $limit, $offset_ing);

        // 2. Phân trang Nhà Cung Cấp
        $page_sup = isset($_GET['page_sup']) ? (int)$_GET['page_sup'] : 1;
        $offset_sup = ($page_sup - 1) * $limit;
        $total_sup = $this->stockModel->countSuppliers($search);
        $maxPage_sup = ceil($total_sup / $limit);
        $suppliers = $this->stockModel->getSuppliers($search, $limit, $offset_sup);
        
        require_once __DIR__ . '/../views/stock/index.php';
    }
}
?>