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

    // Hiá»ƒn thá»‹ giao diá»‡n Quá»¹ & Ca chÃ­nh
    public function index() {
        header("Location: index.php?c=stock&tab=cash_shift");
        exit;
    }

    // Xá»­ lÃ½ Form Má»Ÿ ca (Kiá»ƒm tra cháº·t cháº½ khÃ´ng cho má»Ÿ chá»“ng chÃ©o)
    public function open() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $cashier_id = ($_SESSION['user']['id'] ?? 1);
            $opening_cash = (float)str_replace(',', '', $_POST['opening_cash'] ?? 0);
            $note = trim($_POST['note'] ?? '');

            // Kiá»ƒm tra xem nhÃ¢n viÃªn Ä‘Ã£ cÃ³ ca nÃ o Ä‘ang má»Ÿ chÆ°a
            $checkOpen = $this->cashModel->getCurrentOpenShift($cashier_id);
            if ($checkOpen) {
                echo "<script>alert('Báº¡n Ä‘ang cÃ³ má»™t ca lÃ m viá»‡c chÆ°a Ä‘Ã³ng! Vui lÃ²ng Ä‘Ã³ng ca hiá»‡n táº¡i trÆ°á»›c.'); window.location.href='index.php?c=stock&tab=cash_shift';</script>";
                exit;
            }

            if ($this->cashModel->openShift($cashier_id, $opening_cash, $note)) {
                echo "<script>alert('Má»Ÿ ca thÃ nh cÃ´ng!'); window.location.href='index.php?c=stock&tab=cash_shift';</script>";
            } else {
                echo "<script>alert('Lá»—i khi má»Ÿ ca!'); window.location.href='index.php?c=stock&tab=cash_shift';</script>";
            }
        }
    }

    // Xá»­ lÃ½ Form ÄÃ³ng ca (Chá»‘t sá»• tÃ i chÃ­nh vÃ  lÆ°u lá»‹ch sá»­)
    public function close() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $shift_id = (int)$_POST['shift_id'];
            $actual_closing_cash = (float)str_replace(',', '', $_POST['actual_closing_cash'] ?? 0);
            $note = trim($_POST['close_note'] ?? '');

            if ($this->cashModel->closeShift($shift_id, $actual_closing_cash, $note)) {
                echo "<script>alert('ÄÃ³ng ca vÃ  bÃ n giao thÃ nh cÃ´ng!'); window.location.href='index.php?c=stock&tab=cash_shift';</script>";
            } else {
                echo "<script>alert('Lá»—i há»‡ thá»‘ng khi Ä‘Ã³ng ca!'); window.location.href='index.php?c=stock&tab=cash_shift';</script>";
            }
        }
    }
    // --- MáºªU TÃCH Há»¢P VÃ€O CONTROLLER BÃN HÃ€NG ---
    public function completeOrder() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // 1. Láº¥y thÃ´ng tin thanh toÃ¡n cá»§a Ä‘Æ¡n hÃ ng
            $cashier_id = ($_SESSION['user']['id'] ?? 1); // ID nhÃ¢n viÃªn Ä‘ang thao tÃ¡c
            $total_paid = (float)($_POST['total_amount'] ?? 0); // Sá»‘ tiá»n khÃ¡ch tráº£ báº±ng tiá»n máº·t
            $payment_method = $_POST['payment_method'] ?? 'cash'; // PhÆ°Æ¡ng thá»©c thanh toÃ¡n

            // ... (CÃ¡c logic lÆ°u hÃ³a Ä‘Æ¡n, trá»« kho nguyÃªn liá»‡u cá»§a báº¡n á»Ÿ Ä‘Ã¢y) ...

            // 2. Náº¾U THANH TOÃN Báº°NG TIá»€N Máº¶T -> Cáº¬P NHáº¬T VÃ€O QUá»¸ & CA
            if ($payment_method === 'cash' && $total_paid > 0) {
                require_once __DIR__ . '/../models/CashShiftModel.php';
                $cashModel = new CashShiftModel();
                
                // Tá»± Ä‘á»™ng cá»™ng dá»“n vÃ o má»¥c "Tá»•ng bÃ¡n hÃ ng" cá»§a ca Ä‘ang má»Ÿ
                $cashModel->recordSaleTransaction($cashier_id, $total_paid, 'sales');
            }

            // ThÃ´ng bÃ¡o thÃ nh cÃ´ng
            echo "<script>alert('Thanh toÃ¡n Ä‘Æ¡n hÃ ng thÃ nh cÃ´ng!'); window.location.href='index.php?c=pos';</script>";
        }
    }
    // Xá»­ lÃ½ láº­p Phiáº¿u Thu / Phiáº¿u Chi
    public function transaction() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $cashier_id = ($_SESSION['user']['id'] ?? 1);
            $type = $_POST['trans_type'] ?? 'IN';
            $amount = (float)str_replace(',', '', $_POST['amount'] ?? 0);
            
            // Láº¥y thÃªm thÃ´ng tin ngÆ°á»i ná»™p/nháº­n vÃ  ghi chÃº
            $target_person = trim($_POST['target_person'] ?? '');
            $note_input = trim($_POST['note'] ?? '');
            
            // Gá»™p thÃ´ng minh thÃ nh 1 dÃ²ng Ghi chÃº hoÃ n chá»‰nh lÆ°u vÃ o Database
            $final_note = "";
            if (!empty($target_person)) {
                $prefix = ($type == 'IN') ? "NgÆ°á»i ná»™p: " : "NgÆ°á»i nháº­n: ";
                $final_note = $prefix . $target_person . " - LÃ½ do: " . $note_input;
            } else {
                $final_note = $note_input;
            }

            if ($amount <= 0) {
                echo "<script>alert('Sá»‘ tiá»n khÃ´ng há»£p lá»‡!'); window.location.href='index.php?c=stock&tab=cash_shift&subtab=fund';</script>";
                exit;
            }

            if ($this->cashModel->createTransaction($cashier_id, $type, $amount, $final_note)) {
                echo "<script>alert('Láº­p phiáº¿u thÃ nh cÃ´ng!'); window.location.href='index.php?c=stock&tab=cash_shift&subtab=fund';</script>";
            } else {
                echo "<script>alert('Lá»—i: Báº¡n pháº£i Má»ž CA LÃ€M VIá»†C trÆ°á»›c khi láº­p phiáº¿u thu/chi!'); window.location.href='index.php?c=stock&tab=cash_shift&subtab=fund';</script>";
            }
        }
    }
    // In bÃ¡o cÃ¡o chá»‘t ca (Khá»• K80)
    public function printShift() {
        if (!isset($_GET['id'])) {
            die('Lá»—i: KhÃ´ng tÃ¬m tháº¥y ID ca lÃ m viá»‡c.');
        }
        $shift_id = (int)$_GET['id'];
        
        require_once __DIR__ . '/../models/CashShiftModel.php';
        $dbConn = $this->cashModel->getConnection();
        
        // Láº¥y thÃ´ng tin ca vÃ  tÃªn nhÃ¢n viÃªn
        $stmt = $dbConn->prepare("SELECT cs.*, u.full_name as cashier_name 
                                  FROM cash_shifts cs 
                                  LEFT JOIN users u ON cs.cashier_id = u.id 
                                  WHERE cs.id = :id");
        $stmt->execute([':id' => $shift_id]);
        $shiftData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$shiftData) {
            die('Lá»—i: Ca lÃ m viá»‡c khÃ´ng tá»“n táº¡i.');
        }

        // Gá»i ra file Giao diá»‡n hÃ³a Ä‘Æ¡n in nhiá»‡t
        require_once __DIR__ . '/../views/stock/print_shift.php';
    }
}
?>
