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

        // NẾU NGƯỜI DÙNG BẤM XÁC NHẬN (LƯU DỮ LIỆU POST)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            
            // 1. Nhận thông tin chung của Phiếu
            $receipt_code = trim($_POST['receipt_code']);
            $supplier_id  = ($type === 'import') ? ($_POST['supplier_id'] ?? null) : null; 
            $note         = trim($_POST['note']);
            $created_at   = ($_POST['created_at'] ?? date('Y-m-d')) . ' ' . date('H:i:s'); 
            
            // Mảng dữ liệu chi tiết hàng hóa (Bắt theo dạng mảng tuyến tính từ form gửi lên)
            $ingredient_ids = $_POST['ingredient_id'] ?? [];
            $quantities     = $_POST['quantity'] ?? [];
            $unit_costs     = $_POST['unit_cost'] ?? [];

            $total_amount = 0;
            $detailsData = [];

            for ($i = 0; $i < count($ingredient_ids); $i++) {
                $ing_id = (int)$ingredient_ids[$i];
                $qty    = (float)($quantities[$i] ?? 0);
                $cost   = (float)($unit_costs[$i] ?? 0);

                if ($ing_id > 0 && $qty > 0) {
                    $detailsData[] = [
                        'ingredient_id' => $ing_id,
                        'quantity'      => $qty,
                        'unit_cost'     => $cost
                    ];
                    $total_amount += $qty * $cost;
                }
            }

            // Tự động lấy ID người dùng từ Session
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $current_user_id = $_SESSION['user_id'] ?? 2; 

            // Đóng gói Data Phiếu chung
            $receiptData = [
                'receipt_code' => $receipt_code,
                'supplier_id'  => $supplier_id,
                'user_id'      => $current_user_id,
                'total_amount' => $total_amount,
                'note'         => $note,
                'created_at'   => $created_at
            ];

            // 3. Thực thi lưu trữ phân nhánh Nhập hay Xuất
            if (!empty($detailsData)) {
                if ($type === 'import') {
                    $isSaved = $this->stockModel->createImportReceipt($receiptData, $detailsData);
                    $msgType = 'nhập kho';
                } else {
                    // Gọi hàm xử lý xuất kho và trừ tồn an toàn
                    $isSaved = $this->stockModel->createExportReceipt($receiptData, $detailsData);
                    $msgType = 'xuất kho';
                }
                
                if ($isSaved) {
                    echo "<script>
                        alert('✅ Lưu phiếu $msgType thành công! Kho hàng đã được cập nhật.');
                        window.location.href = 'index.php?c=stock&a=index&tab=dashboard';
                    </script>";
                    exit;
                } else {
                    echo "<script>alert('❌ Lỗi hệ thống: Không đủ số lượng trong kho hoặc không thể lưu phiếu lúc này!');</script>";
                }
            } else {
                echo "<script>alert('❌ Vui lòng chọn ít nhất một nguyên vật liệu và nhập số lượng!');</script>";
            }
        }

        // 4. Gọi View Form Lập Phiếu tương ứng nếu là phương thức GET (Chỉ gọi 1 lần duy nhất)
        if ($type === 'import') {
            require_once __DIR__ . '/../views/stock/stock_receipt/import_form.php';
        } else {
            require_once __DIR__ . '/../views/stock/stock_receipt/export_form.php';
        }
    }
    // Hàm xử lý riêng cho tính năng Xem chi tiết Lịch sử phiếu
    // Hàm xử lý Xem chi tiết (Phân luồng 3 loại phiếu)
    public function show() {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $code = isset($_GET['code']) ? trim($_GET['code']) : '';
        
        $receipt = null;
        if ($id > 0) {
            $receipt = $this->stockModel->getReceiptById($id);
        } elseif (!empty($code)) {
            $receipt = $this->stockModel->getReceiptByCode($code);
        }

        if (!$receipt) {
            echo "<script>alert('Mã phiếu không hợp lệ hoặc không tìm thấy!'); window.location.href='index.php?c=stock&a=index&tab=history';</script>";
            exit;
        }

        $details = $this->stockModel->getReceiptDetails($receipt['id']);

        // PHÂN LUỒNG GIAO DIỆN THEO MÃ PHIẾU:
        if (strpos($receipt['receipt_code'], 'PN') === 0) {
            // Nếu mã bắt đầu bằng PN -> Gọi form Phiếu Nhập
            require_once __DIR__ . '/../views/stock/stock_receipt/show.php';
            
        } elseif (strpos($receipt['receipt_code'], 'KK') === 0) {
            // Nếu mã bắt đầu bằng KK -> Gọi form Phiếu Kiểm Kê
            require_once __DIR__ . '/../views/stock/stock_receipt/show_audit.php';
            
        } else {
            // Còn lại (VX) -> Gọi form Phiếu Xuất
            require_once __DIR__ . '/../views/stock/stock_receipt/show_receipt.php';
        }
    }
    public function audit() {
        // Lấy toàn bộ danh sách nguyên vật liệu để nhân viên đếm
        $ingredients = $this->stockModel->getIngredients('', '', '', '', 1000, 0);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            
            $user_id = $_SESSION['user_id'] ?? 2;
            $receipt_code = trim($_POST['receipt_code'] ?? 'KK' . date('ymdHis'));
            $note         = trim($_POST['note'] ?? 'Phiếu điều chỉnh kiểm kê định kỳ');
            $created_at   = date('Y-m-d H:i:s');

            $ingredient_ids = $_POST['ingredient_id'] ?? [];
            $current_qtys   = $_POST['current_qty'] ?? [];
            $actual_qtys    = $_POST['actual_qty'] ?? [];
            $unit_costs     = $_POST['unit_cost'] ?? [];
            $reasons        = $_POST['reason'] ?? []; // <-- BỔ SUNG DÒNG NÀY ĐỂ HỨNG DỮ LIỆU LÝ DO

            $auditDetails = [];
            for ($i = 0; $i < count($ingredient_ids); $i++) {
                if (isset($actual_qtys[$i]) && $actual_qtys[$i] !== '') {
                    $actual_qty  = (float)$actual_qtys[$i];
                    $current_qty = (float)$current_qtys[$i];
                    $diff        = $actual_qty - $current_qty;

                    if ($diff != 0) {
                        $auditDetails[] = [
                            'ingredient_id' => (int)$ingredient_ids[$i],
                            'actual_qty'    => $actual_qty,
                            'difference'    => $diff, 
                            'unit_cost'     => (float)($unit_costs[$i] ?? 0),
                            'reason'        => trim($reasons[$i] ?? '') // Bây giờ biến $reasons đã tồn tại và lấy đúng dữ liệu
                        ];
                    }
                }
            }

            if (!empty($auditDetails)) {$receiptData = [
                    'receipt_code' => $receipt_code,
                    'user_id'      => $user_id,
                    'note'         => $note,
                    'created_at'   => $created_at
                ];

                $receiptId =$this->stockModel->createAuditReceipt($receiptData,$auditDetails);
                
                if ($receiptId) {
                    echo "<script>
                        alert('✅ Kiểm kê thành công! Kho đã được cân bằng thực tế.'); 
                        window.location.href='index.php?c=stock_receipt&a=show&id=" . $receiptId . "&from=dashboard';
                    </script>";
                    exit;
                } else {
                    echo "<script>alert('❌ Lỗi hệ thống: Không thể lưu phiếu kiểm kê!');</script>";
                }
            } else {
                echo "<script>alert('✅ Khớp 100%! Không có chênh lệch nào cần điều chỉnh.'); window.location.href='index.php?c=stock&a=index&tab=dashboard';</script>";
                exit;
            }
        }

        // Render View Giao diện Kiểm kê
        require_once __DIR__ . '/../views/stock/stock_receipt/audit_form.php';
    }
}
?>