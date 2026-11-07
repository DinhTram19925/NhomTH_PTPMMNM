<?php
require_once __DIR__ . '/../models/CashShiftModel.php';

class CashShiftController extends Controller {
    private $cashModel;

    public function __construct() {
        parent::__construct();
        $this->requireRole(['ADMIN', 'STOCK', 'WAITER', 'CASHIER']);
        $this->cashModel = new CashShiftModel();
    }

    public function index() {
        header("Location: index.php?c=stock&tab=cash_shift");
        exit;
    }

    public function open() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $cashier_id = (int)($_SESSION['user']['id'] ?? 1);
            $opening_cash = (float)str_replace(',', '', $_POST['opening_cash'] ?? 0);
            $note = trim($_POST['note'] ?? '');

            $checkOpen = $this->cashModel->getCurrentOpenShift($cashier_id);
            if ($checkOpen) {
                echo "<script>alert('Đang có một ca làm việc chưa đóng! Vui lòng đóng ca hiện tại trước.'); window.location.href='index.php?c=stock&tab=cash_shift';</script>";
                exit;
            }

            if ($this->cashModel->openShift($cashier_id, $opening_cash, $note)) {
                echo "<script>alert('Mở ca thành công!'); window.location.href='index.php?c=stock&tab=cash_shift';</script>";
            } else {
                echo "<script>alert('Lỗi khi mở ca!'); window.location.href='index.php?c=stock&tab=cash_shift';</script>";
            }
        }
    }

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

    public function transaction() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $cashier_id = (int)($_SESSION['user']['id'] ?? 1);
            $type = $_POST['trans_type'] ?? 'IN';
            $amount = (float)str_replace(',', '', $_POST['amount'] ?? 0);
            $target_person = trim($_POST['target_person'] ?? '');
            $note_input = trim($_POST['note'] ?? '');

            $final_note = !empty($target_person)
                ? (($type == 'IN' ? "Người nộp: " : "Người nhận: ") . $target_person . " - Lý do: " . $note_input)
                : $note_input;

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

    public function printShift() {
        if (!isset($_GET['id'])) {
            die('Lỗi: Không tìm thấy ID ca làm việc.');
        }
        $shift_id = (int)$_GET['id'];
        $dbConn = $this->cashModel->getConnection();
        $stmt = $dbConn->prepare("SELECT cs.*, u.full_name as cashier_name FROM cash_shifts cs LEFT JOIN users u ON cs.cashier_id = u.id WHERE cs.id = :id");
        $stmt->execute([':id' => $shift_id]);
        $shiftData = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$shiftData) {
            die('Lỗi: Ca làm việc không tồn tại.');
        }
        require_once __DIR__ . '/../views/stock/print_shift.php';
    }
}