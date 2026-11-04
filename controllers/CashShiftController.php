<?php
require_once __DIR__ . '/../models/CashShiftModel.php';

class CashShiftController {
    private $cashModel;

    public function __construct() {
        $this->cashModel = new CashShiftModel();
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    // Hiển thị giao diện Quỹ & Ca chính
    public function index() {
        header("Location: index.php?c=stock&tab=cash_shift");
        exit;
    }

    // Xử lý Form Mở ca (Kiểm tra chặt chẽ không cho mở chồng chéo)
    public function open() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $cashier_id = $_SESSION['user_id'] ?? 2;
            $opening_cash = (float)str_replace(',', '', $_POST['opening_cash'] ?? 0);
            $note = trim($_POST['note'] ?? '');

            // Kiểm tra xem nhân viên đã có ca nào đang mở chưa
            $checkOpen = $this->cashModel->getCurrentOpenShift($cashier_id);
            if ($checkOpen) {
                echo "<script>alert('Bạn đang có một ca làm việc chưa đóng! Vui lòng đóng ca hiện tại trước.'); window.location.href='index.php?c=stock&tab=cash_shift';</script>";
                exit;
            }

            if ($this->cashModel->openShift($cashier_id, $opening_cash, $note)) {
                echo "<script>alert('Mở ca thành công!'); window.location.href='index.php?c=stock&tab=cash_shift';</script>";
            } else {
                echo "<script>alert('Lỗi khi mở ca!'); window.location.href='index.php?c=stock&tab=cash_shift';</script>";
            }
        }
    }

    // Xử lý Form Đóng ca (Chốt sổ tài chính và lưu lịch sử)
    public function close() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $shift_id = (int)$_POST['shift_id'];
            $actual_closing_cash = (float)str_replace(',', '', $_POST['actual_closing_cash'] ?? 0);
            $note = trim($_POST['close_note'] ?? '');

            if ($this->cashModel->closeShift($shift_id, $actual_closing_cash, $note)) {
                echo "<script>alert('Đóng ca và bàn giao thành công!'); window.location.href='index.php?c=stock&tab=cash_shift';</script>";
            } else {
                echo "<script>alert('Lỗi hệ thống khi đóng ca!'); window.location.href='index.php?c=stock&tab=cash_shift';</script>";
            }
        }
    }
    // --- MẪU TÍCH HỢP VÀO CONTROLLER BÁN HÀNG ---
    public function completeOrder() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // 1. Lấy thông tin thanh toán của đơn hàng
            $cashier_id = $_SESSION['user_id'] ?? 2; // ID nhân viên đang thao tác
            $total_paid = (float)($_POST['total_amount'] ?? 0); // Số tiền khách trả bằng tiền mặt
            $payment_method = $_POST['payment_method'] ?? 'cash'; // Phương thức thanh toán

            // ... (Các logic lưu hóa đơn, trừ kho nguyên liệu của bạn ở đây) ...

            // 2. NẾU THANH TOÁN BẰNG TIỀN MẶT -> CẬP NHẬT VÀO QUỸ & CA
            if ($payment_method === 'cash' && $total_paid > 0) {
                require_once __DIR__ . '/../models/CashShiftModel.php';
                $cashModel = new CashShiftModel();
                
                // Tự động cộng dồn vào mục "Tổng bán hàng" của ca đang mở
                $cashModel->recordSaleTransaction($cashier_id, $total_paid, 'sales');
            }

            // Thông báo thành công
            echo "<script>alert('Thanh toán đơn hàng thành công!'); window.location.href='index.php?c=pos';</script>";
        }
    }
    // Xử lý lập Phiếu Thu / Phiếu Chi
    public function transaction() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $cashier_id = $_SESSION['user_id'] ?? 2;
            $type = $_POST['trans_type'] ?? 'IN';
            $amount = (float)str_replace(',', '', $_POST['amount'] ?? 0);
            
            // Lấy thêm thông tin người nộp/nhận và ghi chú
            $target_person = trim($_POST['target_person'] ?? '');
            $note_input = trim($_POST['note'] ?? '');
            
            // Gộp thông minh thành 1 dòng Ghi chú hoàn chỉnh lưu vào Database
            $final_note = "";
            if (!empty($target_person)) {
                $prefix = ($type == 'IN') ? "Người nộp: " : "Người nhận: ";
                $final_note = $prefix . $target_person . " - Lý do: " . $note_input;
            } else {
                $final_note = $note_input;
            }

            if ($amount <= 0) {
                echo "<script>alert('Số tiền không hợp lệ!'); window.location.href='index.php?c=stock&tab=cash_shift&subtab=fund';</script>";
                exit;
            }

            if ($this->cashModel->createTransaction($cashier_id, $type, $amount, $final_note)) {
                echo "<script>alert('Lập phiếu thành công!'); window.location.href='index.php?c=stock&tab=cash_shift&subtab=fund';</script>";
            } else {
                echo "<script>alert('Lỗi: Bạn phải MỞ CA LÀM VIỆC trước khi lập phiếu thu/chi!'); window.location.href='index.php?c=stock&tab=cash_shift&subtab=fund';</script>";
            }
        }
    }
    // In báo cáo chốt ca (Khổ K80)
    public function printShift() {
        if (!isset($_GET['id'])) {
            die('Lỗi: Không tìm thấy ID ca làm việc.');
        }
        $shift_id = (int)$_GET['id'];
        
        require_once __DIR__ . '/../models/CashShiftModel.php';
        $dbConn = $this->cashModel->getConnection();
        
        // Lấy thông tin ca và tên nhân viên
        $stmt = $dbConn->prepare("SELECT cs.*, u.full_name as cashier_name 
                                  FROM cash_shifts cs 
                                  LEFT JOIN users u ON cs.cashier_id = u.id 
                                  WHERE cs.id = :id");
        $stmt->execute([':id' => $shift_id]);
        $shiftData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$shiftData) {
            die('Lỗi: Ca làm việc không tồn tại.');
        }

        // Gọi ra file Giao diện hóa đơn in nhiệt
        require_once __DIR__ . '/../views/stock/print_shift.php';
    }
}
?>