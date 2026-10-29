<?php
require_once __DIR__ . '/../models/StockModel.php';

class Stock_receiptController {
    private $stockModel;

    public function __construct() {
        $this->stockModel = new StockModel();
    }

    public function create() {
        $type = $_GET['type'] ?? 'import';

        // Lấy danh sách NCC và NVL để đổ vào Dropdown
        $suppliers = $this->stockModel->getSuppliers('', 'active', 1000, 0);
        $ingredients = $this->stockModel->getIngredients('', '', '', '', 1000, 0);

        // NẾU NGƯỜI DÙNG BẤM "XÁC NHẬN NHẬP KHO"
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            
            // 1. Nhận thông tin chung của Phiếu
            $receipt_code = trim($_POST['receipt_code']);
            $supplier_id  = $_POST['supplier_id'];
            $note         = trim($_POST['note']);
            $created_at   = $_POST['created_at'] . ' ' . date('H:i:s'); // Kèm giờ phút giây hiện tại
            
            // Mảng dữ liệu chi tiết hàng hóa
            $ingredient_ids = $_POST['ingredient_id'] ?? [];
            $quantities     = $_POST['quantity'] ?? [];
            $unit_costs     = $_POST['unit_cost'] ?? [];

            $total_amount = 0;
            $detailsData = [];

            // 2. Gộp các dòng chi tiết hàng hóa lại thành 1 mảng để đưa xuống Model
            for ($i = 0; $i < count($ingredient_ids); $i++) {
                $ing_id = (int)$ingredient_ids[$i];
                $qty    = (float)$quantities[$i];
                $cost   = (float)$unit_costs[$i];

                if ($ing_id > 0 && $qty > 0) {
                    $detailsData[] = [
                        'ingredient_id' => $ing_id,
                        'quantity'      => $qty,
                        'unit_cost'     => $cost
                    ];
                    $total_amount += ($qty * $cost); // Cộng dồn tổng tiền phiếu
                }
            }

            // Tự động lấy ID người dùng từ Session đăng nhập của hệ thống
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            
            // Giả sử biến session lưu ID của nhóm bạn là $_SESSION['user_id']
            // Nếu chưa đăng nhập (test độc lập), tạm gán là 2 (Đoàn Bùi Khánh Vy)
            $current_user_id = $_SESSION['user_id'] ?? 2; 

            // Đóng gói Data Phiếu chung
            $receiptData = [
                'receipt_code' => $receipt_code,
                'supplier_id'  => $supplier_id,
                'user_id'      => $current_user_id, // Lấy động theo tài khoản đang đăng nhập
                'total_amount' => $total_amount,
                'note'         => $note,
                'created_at'   => $created_at
            ];

            // 3. Thực thi lưu trữ nếu có hàng hóa
            if (!empty($detailsData)) {
                $isSaved = $this->stockModel->createImportReceipt($receiptData, $detailsData);
                
                if ($isSaved) {
                    // Nếu thành công -> Bật Alert xanh và chuyển hướng về màn hình Tổng quan Dashboard
                    echo "<script>
                        alert('✅ Lưu phiếu nhập kho thành công! Kho hàng đã được cập nhật số lượng.');
                        window.location.href = 'index.php?c=stock&a=index&tab=dashboard';
                    </script>";
                    exit;
                } else {
                    echo "<script>alert('❌ Lỗi hệ thống: Không thể lưu phiếu nhập kho lúc này!');</script>";
                }
            } else {
                echo "<script>alert('❌ Vui lòng chọn ít nhất một nguyên vật liệu!');</script>";
            }
        }

        // Gọi View Form Lập Phiếu nếu là phương thức GET
        if ($type === 'import') {
            require_once __DIR__ . '/../views/stock/stock_receipt/import_form.php';
        } else {
            die("Form xuất kho đang xây dựng...");
        }
    }
    public function show() {
        $code = trim($_GET['code'] ?? '');
        
        if (empty($code)) {
            echo "<script>alert('Mã phiếu không hợp lệ!'); history.back();</script>";
            exit;
        }

        // Lấy thông tin chung của phiếu
        $receipt = $this->stockModel->getReceiptByCode($code);
        
        if (!$receipt) {
            echo "<script>alert('Không tìm thấy chứng từ này trong hệ thống!'); history.back();</script>";
            exit;
        }

        // Lấy danh sách hàng hóa trong phiếu
        $details = $this->stockModel->getReceiptDetails($receipt['id']);

        // Gọi View để hiển thị
        require_once __DIR__ . '/../views/stock/stock_receipt/show.php';
    }
}
?>