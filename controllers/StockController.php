<?php
require_once __DIR__ . '/../models/StockModel.php';

class StockController {
    private $stockModel;

    public function __construct() {
        $this->stockModel = new StockModel();
    }

    public function index() {
        $active_tab = isset($_REQUEST['tab']) ? trim($_REQUEST['tab']) : 'dashboard'; // Mặc định mở Tổng quan
        $search = isset($_REQUEST['search']) ? trim($_REQUEST['search']) : '';
        
        // Bắt thêm các bộ lọc từ giao diện
        $group_filter = isset($_REQUEST['group_filter']) ? trim($_REQUEST['group_filter']) : '';
        $unit_filter = isset($_REQUEST['unit_filter']) ? trim($_REQUEST['unit_filter']) : '';
        $status_filter = isset($_REQUEST['status_filter']) ? trim($_REQUEST['status_filter']) : '';
        
        $limit = 10; // Cố định 10 dòng trên 1 trang
        
        // Khởi tạo các biến rỗng để View không bị lỗi undefine variable
        $ingredients = [];
        $suppliers = [];
        $history_list = []; // Thêm biến cho tab lịch sử
        $maxPage_ing = 0;
        $maxPage_sup = 0;
        $maxPage_hist = 0; // Thêm biến cho tab lịch sử
        
        // CÁC BIẾN DÀNH CHO TAB DASHBOARD
        $kpis = [];
        $alerts = [];
        $activities = [];
        
        // 2. CHỈ XỬ LÝ PHÂN TRANG / TRUY VẤN KHI ĐANG Ở TAB TƯƠNG ỨNG
        if ($active_tab == 'dashboard') {
            
            // Gọi hàm lấy dữ liệu cho trang Tổng quan kho
            $kpis = $this->stockModel->getDashboardKPIs();
            $alerts = $this->stockModel->getLowStockAlerts(5); 
            
            $page_trans = isset($_REQUEST['page_trans']) ? (int)$_REQUEST['page_trans'] : 1;
            $page_trans = max(1, $page_trans);
            $limit_trans = 5; 
            $offset_trans = ($page_trans - 1) * $limit_trans;
            
            $total_trans = $this->stockModel->countRecentActivities();
            $maxPage_trans = ceil($total_trans / $limit_trans);
            $activities = $this->stockModel->getRecentActivities($limit_trans, $offset_trans);
            
        } elseif ($active_tab == 'ingredients') {
            
            $page_ing = isset($_GET['page_ing']) ? (int)$_GET['page_ing'] : 1;
            $page_ing = max(1, $page_ing); 
            $offset_ing = ($page_ing - 1) * $limit;
            
            $total_ing = $this->stockModel->countIngredients($search, $group_filter, $unit_filter, $status_filter);
            $maxPage_ing = ceil($total_ing / $limit);
            $ingredients = $this->stockModel->getIngredients($search, $group_filter, $unit_filter, $status_filter, $limit, $offset_ing);
            
        } elseif ($active_tab == 'suppliers') {
            
            $page_sup = isset($_GET['page_sup']) ? (int)$_GET['page_sup'] : 1;
            $page_sup = max(1, $page_sup);
            $offset_sup = ($page_sup - 1) * $limit;
            
            $total_sup = $this->stockModel->countSuppliers($search, $status_filter);
            $maxPage_sup = ceil($total_sup / $limit);
            $suppliers = $this->stockModel->getSuppliers($search, $status_filter, $limit, $offset_sup);
            
        } elseif ($active_tab == 'history') {
            // Lấy các tham số tìm kiếm hiện tại
            $search_hist = isset($_GET['search_hist']) ? trim($_GET['search_hist']) : '';
            $type_hist   = isset($_GET['type_hist']) ? trim($_GET['type_hist']) : '';
            // Bắt thêm tham số lọc ngày
            $date_filter = isset($_GET['date_filter']) ? trim($_GET['date_filter']) : '';

            $page_hist = isset($_GET['page_hist']) ? (int)$_GET['page_hist'] : 1;
            $limit_hist = 10;
            $offset_hist = ($page_hist - 1) * $limit_hist;

            // Truyền $date_filter vào model
            $total_hist = $this->stockModel->countReceiptHistory($search_hist, $type_hist, $date_filter);
            $maxPage_hist = ceil($total_hist / $limit_hist);
            $history_list = $this->stockModel->getReceiptHistory($search_hist, $type_hist, $limit_hist, $offset_hist, $date_filter);
        }
        
        // Gọi View để hiển thị
        require_once __DIR__ . '/../views/stock/index.php';
    }

    // ========================================================
    // CHỨC NĂNG MỚI: TẠO PHIẾU NHẬP / XUẤT KHO
    // ========================================================
    public function create_receipt() {
        $type = isset($_GET['type']) ? $_GET['type'] : 'import'; // export hoặc import
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Chuẩn bị dữ liệu chung của phiếu
            $receiptData = [
                'receipt_code' => trim($_POST['receipt_code']),
                'user_id'      => 1, // Fix cứng user_id tạm thời (Nếu có hệ thống login thì lấy từ Session)
                'total_amount' => isset($_POST['total_amount']) ? (float)$_POST['total_amount'] : 0,
                'note'         => trim($_POST['note']),
                'created_at'   => date('Y-m-d H:i:s')
            ];

            // Lấy chi tiết hàng hóa (mảng ingredient_id, quantity, unit_cost)
            $detailsData = [];
            if (isset($_POST['ingredients']) && is_array($_POST['ingredients'])) {
                foreach ($_POST['ingredients'] as $id => $detail) {
                    if ((float)$detail['quantity'] > 0) {
                        $detailsData[] = [
                            'ingredient_id' => $id,
                            'quantity'      => (float)$detail['quantity'],
                            'unit_cost'     => (float)$detail['unit_cost']
                        ];
                    }
                }
            }

            if (empty($detailsData)) {
                echo "<script>alert('❌ Lỗi: Bạn chưa chọn hàng hóa nào để lập phiếu!'); history.back();</script>";
                exit;
            }

            // Phân nhánh lưu Phiếu Nhập hoặc Phiếu Xuất
            if ($type == 'import') {
                $receiptData['supplier_id'] = !empty($_POST['supplier_id']) ? $_POST['supplier_id'] : null;
                $result = $this->stockModel->createImportReceipt($receiptData, $detailsData);
                $msg = 'Nhập kho';
            } else {
                // Phiếu xuất không cần nhà cung cấp
                $result = $this->stockModel->createExportReceipt($receiptData, $detailsData);
                $msg = 'Xuất kho';
            }

            // Thông báo kết quả
            if ($result) {
                echo "<script>alert('✅ Tạo phiếu $msg thành công!'); window.location.href='index.php?c=stock&a=index&tab=history';</script>";
            } else {
                echo "<script>alert('❌ Lỗi hệ thống: Không đủ hàng trong kho hoặc có lỗi xảy ra!'); history.back();</script>";
            }
            exit;
        }

        // Nếu là GET: Đổ dữ liệu cần thiết ra Form
        $ingredients = $this->stockModel->getIngredients('', '', '', '', 1000, 0); // Lấy tất cả nguyên liệu cho Dropdown chọn
        if ($type == 'import') {
            $suppliers = $this->stockModel->getSuppliers('', 'active', 1000, 0);
            require_once __DIR__ . '/../views/stock/stock_receipt/import_form.php';
        } else {
            // Cần tạo 1 file view export_form.php cho giao diện xuất
            require_once __DIR__ . '/../views/stock/stock_receipt/export_form.php'; 
        }
    }


    // XỬ LÝ FORM THÊM / SỬA NGUYÊN LIỆU
    public function form_ingredient() {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $ingredient = null;
        
        if ($id > 0) {
            $ingredient = $this->stockModel->getIngredientById($id);
            if (!$ingredient) {
                echo "<script>alert('Không tìm thấy nguyên liệu!'); history.back();</script>"; exit;
            }
        }
        $suppliers = $this->stockModel->getSuppliers('', 'active', 1000, 0);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'name'            => trim($_POST['name']),
                'group_name'      => trim($_POST['group_name']),
                'unit'            => trim($_POST['unit']),
                'min_stock_level' => (float)$_POST['min_stock_level'],
                'cost_price'      => (float)$_POST['cost_price'],
                'supplier_id'     => !empty($_POST['supplier_id']) ? $_POST['supplier_id'] : null
            ];
            if ($id > 0) {
                $this->stockModel->updateIngredient($id, $data);
                echo "<script>alert('✅ Cập nhật nguyên liệu thành công!'); window.location.href='index.php?c=stock&a=index&tab=ingredients';</script>";
            } else {
                $data['ingredient_code'] = $this->stockModel->generateNextIngredientCode();
                $result = $this->stockModel->insertIngredient($data);
                if ($result) {
                    echo "<script>alert('✅ Thêm nguyên vật liệu mới thành công!'); window.location.href='index.php?c=stock&a=index&tab=ingredients';</script>";
                } else {
                    echo "<script>alert('❌ Lỗi hệ thống: Không thể thêm nguyên liệu!'); history.back();</script>";
                }
            }
            exit;
        }
        require_once __DIR__ . '/../views/stock/form_ingredient.php';
    }

    // XỬ LÝ NÚT XÓA BÊN NGOÀI BẢNG NGUYÊN LIỆU
    public function delete_ingredient() {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id > 0) {
            $result = $this->stockModel->deleteIngredient($id);
            if ($result) {
                echo "<script>alert('✅ Đã xóa nguyên liệu thành công!');</script>";
            } else {
                echo "<script>alert('❌ TỪ CHỐI XÓA: Nguyên liệu này đang được dùng trong Công thức (BOM) hoặc Phiếu nhập. Vui lòng bảo toàn dữ liệu!');</script>";
            }
        }
        echo "<script>window.location.href='index.php?c=stock&a=index&tab=ingredients';</script>";
    }

    // XUẤT EXCEL DANH SÁCH NGUYÊN VẬT LIỆU
    public function export_excel() {
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        $group_filter = isset($_GET['group_filter']) ? trim($_GET['group_filter']) : '';
        $unit_filter = isset($_GET['unit_filter']) ? trim($_GET['unit_filter']) : '';
        $status_filter = isset($_GET['status_filter']) ? trim($_GET['status_filter']) : '';
        
        $ingredients = $this->stockModel->getIngredients($search, $group_filter, $unit_filter, $status_filter, 10000, 0);
        
        header("Content-Type: application/vnd.ms-excel; charset=utf-8");
        header("Content-Disposition: attachment; filename=Bao_Cao_Nguyen_Vat_Lieu_" . date('dmY_Hi') . ".xls");
        header("Pragma: no-cache");
        header("Expires: 0");
        
        echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel"><head><meta charset="utf-8"></head><body><table border="1">';
        echo '<tr style="background-color: #c9a050; color: #ffffff; font-weight: bold; text-align: center;">
                <th>STT</th><th>Mã NVL</th><th>Tên Nguyên Vật Liệu</th><th>Nhóm Hàng</th><th>ĐVT</th>
                <th>Tồn Hiện Tại</th><th>Tồn Tối Thiểu</th><th>Giá Vốn (VNĐ)</th><th>Nhà Cung Cấp</th></tr>';
        
        if(!empty($ingredients)) {
            $stt = 1;
            foreach($ingredients as $item) {
                $isLowStock = $item['stock_quantity'] <= $item['min_stock_level'];
                $stockColor = $isLowStock ? 'color: red; font-weight: bold;' : '';
                echo '<tr>';
                echo '<td style="text-align: center;">' . $stt++ . '</td>';
                echo '<td style="text-align: center;">#' . htmlspecialchars($item['ingredient_code']) . '</td>';
                echo '<td>' . htmlspecialchars($item['name']) . '</td>';
                echo '<td style="text-align: center;">' . htmlspecialchars($item['group_name'] ?? 'Chưa phân nhóm') . '</td>';
                echo '<td style="text-align: center;">' . htmlspecialchars($item['unit']) . '</td>';
                echo '<td style="text-align: right; ' . $stockColor . '">' . number_format($item['stock_quantity'], 2) . '</td>';
                echo '<td style="text-align: right;">' . number_format($item['min_stock_level'], 2) . '</td>';
                echo '<td style="text-align: right;">' . number_format($item['cost_price'], 0, ',', '.') . '</td>';
                echo '<td>' . htmlspecialchars($item['supplier_name'] ?? 'Chưa xác định') . '</td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="9" style="text-align: center;">Không có dữ liệu phù hợp</td></tr>';
        }
        echo '</table></body></html>';
        exit; 
    }

    // XỬ LÝ FORM THÊM / SỬA NHÀ CUNG CẤP
    public function form_supplier() {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $supplier = null;
        if ($id > 0) {
            $supplier = $this->stockModel->getSupplierById($id);
            if (!$supplier) {
                echo "<script>alert('Không tìm thấy nhà cung cấp!'); history.back();</script>"; exit;
            }
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'name'           => trim($_POST['name']),
                'contact_person' => trim($_POST['contact_person']),
                'phone'          => trim($_POST['phone']),
                'address'        => trim($_POST['address']),
                'debt_balance'   => (float)$_POST['debt_balance'],
                'status'         => trim($_POST['status'])
            ];
            if ($id > 0) {
                $this->stockModel->updateSupplier($id, $data);
                echo "<script>alert('✅ Cập nhật Nhà cung cấp thành công!'); window.location.href='index.php?c=stock&a=index&tab=suppliers';</script>";
            } else {
                $result = $this->stockModel->insertSupplier($data);
                if ($result) {
                    echo "<script>alert('✅ Thêm Nhà cung cấp mới thành công!'); window.location.href='index.php?c=stock&a=index&tab=suppliers';</script>";
                } else {
                    echo "<script>alert('❌ Lỗi hệ thống: Không thể thêm NCC!'); history.back();</script>";
                }
            }
            exit;
        }
        require_once __DIR__ . '/../views/stock/form_supplier.php';
    }

    // ĐÃ BỔ SUNG HÀM XÓA NHÀ CUNG CẤP VÀO ĐÂY
    public function delete_supplier() {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        
        if ($id > 0) {
            $result = $this->stockModel->deleteSupplier($id);
            
            if ($result) {
                echo "<script>alert('✅ Đã xóa nhà cung cấp thành công!');</script>";
            } else {
                echo "<script>alert('❌ TỪ CHỐI XÓA: Nhà cung cấp này đã phát sinh Phiếu nhập kho. Để bảo toàn dữ liệu kế toán, bạn chỉ có thể chuyển trạng thái sang \"Ngừng hợp tác\"!');</script>";
            }
        }
        echo "<script>window.location.href='index.php?c=stock&a=index&tab=suppliers';</script>";
        exit;
    }

    // XUẤT EXCEL DANH SÁCH NHÀ CUNG CẤP
    public function export_suppliers_excel() {
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        $status_filter = isset($_GET['status_filter']) ? trim($_GET['status_filter']) : '';
        
        $suppliers = $this->stockModel->getSuppliers($search, $status_filter, 10000, 0);
        
        header("Content-Type: application/vnd.ms-excel; charset=utf-8");
        header("Content-Disposition: attachment; filename=Danh_Sach_NCC_" . date('dmY_Hi') . ".xls");
        header("Pragma: no-cache");
        header("Expires: 0");
        
        echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel"><head><meta charset="utf-8"></head><body><table border="1">';
        echo '<tr style="background-color: #c9a050; color: #ffffff; font-weight: bold; text-align: center;">
                <th>STT</th><th>Tên Nhà Cung Cấp</th><th>Người Liên Hệ</th><th>Số Điện Thoại</th>
                <th>Địa Chỉ</th><th>Công Nợ (VNĐ)</th><th>Trạng Thái</th></tr>';
        
        if(!empty($suppliers)) {
            $stt = 1;
            foreach($suppliers as $sup) {
                $debtColor = $sup['debt_balance'] > 0 ? 'color: red; font-weight: bold;' : 'color: green;';
                $statusText = $sup['status'] === 'active' ? 'Đang hợp tác' : 'Ngừng hợp tác';
                
                echo '<tr>';
                echo '<td style="text-align: center;">' . $stt++ . '</td>';
                echo '<td style="font-weight: bold;">' . htmlspecialchars($sup['name']) . '</td>';
                echo '<td>' . htmlspecialchars($sup['contact_person'] ?? '') . '</td>';
                echo '<td style="text-align: center; mso-number-format:\'\@\';">' . htmlspecialchars($sup['phone'] ?? '') . '</td>';
                echo '<td>' . htmlspecialchars($sup['address'] ?? '') . '</td>';
                echo '<td style="text-align: right; ' . $debtColor . '">' . number_format($sup['debt_balance'], 0, ',', '.') . '</td>';
                echo '<td style="text-align: center;">' . $statusText . '</td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="7" style="text-align: center;">Không có dữ liệu phù hợp</td></tr>';
        }
        echo '</table></body></html>';
        exit;
    }
}
?>