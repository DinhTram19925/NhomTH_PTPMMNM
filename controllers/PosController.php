<?php
require_once __DIR__ . '/../models/OrderModel.php';

class PosController {
    private $orderModel;

    const LOGIN_URL = 'index.php?c=auth&a=login';

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $this->orderModel = new OrderModel();
    }

    private function json($data, $code = 200) {
        if (ob_get_length()) ob_clean();
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }

    private function input() {
        $d = json_decode(file_get_contents('php://input'), true);
        return is_array($d) ? $d : [];
    }

    // Bắt buộc đăng nhập
    private function guard($api = true) {
        if (!empty($_SESSION['user']['id'])) return;
        if ($api) $this->json(['status' => 'error', 'message' => 'Phiên đăng nhập đã hết, vui lòng đăng nhập lại.'], 401);
        header('Location: ' . self::LOGIN_URL);
        exit;
    }

    private function guardPost() {
        $this->guard();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['status' => 'error', 'message' => 'Phương thức không được hỗ trợ.'], 405);
        }
    }
    private function uid() { return (int)$_SESSION['user']['id']; }

    private function date($v) {
        return (is_string($v) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) && strtotime($v) !== false) ? $v : null;
    }

    public function index() {
        $this->guard(false);
        require_once __DIR__ . '/../views/pos/index.php';
    }

    public function report() {
        $this->guard(false);
        require_once __DIR__ . '/../views/pos/report.php';
    }

    public function printBill() {
        $this->guard(false);
        $billData = $this->orderModel->getBillData((int)($_GET['order_id'] ?? 0));
        if (!$billData) {
            echo '<h3>Không tìm thấy dữ liệu hóa đơn!</h3>';
            exit;
        }
        $order   = $billData['order'];
        $details = $billData['details'];
        require_once __DIR__ . '/../views/pos/print_bill.php';
    }

    public function createOrder() {
        $this->guardPost();
        $d = $this->input();
        $this->json($this->orderModel->createOrAddOrder(
            (int)($d['table_id'] ?? 0),
            $this->uid(),
            $d['items'] ?? []
        ));
    }

    public function cancelItem() {
        $this->guardPost();
        $d = $this->input();
        $this->json($this->orderModel->cancelOrderItem(
            (int)($d['order_detail_id'] ?? 0),
            $this->uid(),
            $d['reason'] ?? ''
        ));
    }

    // API Chuyển / Gộp bàn theo Table ID.
    public function transferTable() {
        $this->guardPost();
        $d = $this->input();
        $mode = in_array($d['mode'] ?? '', ['transfer', 'merge'], true) ? $d['mode'] : '';
        $this->json($this->orderModel->transferTableByTableId(
            (int)($d['from_table_id'] ?? 0),
            (int)($d['to_table_id'] ?? 0),
            $this->uid(),
            $mode
        ));
    }

    // Đóng bàn khi đơn đã hủy hết món (0đ)
    public function closeEmptyOrder() {
        $this->guardPost();
        $d = $this->input();
        $this->json($this->orderModel->closeEmptyOrder((int)($d['order_id'] ?? 0), $this->uid()));
    }

    public function getKds() {
        $this->guard();
        $this->json($this->orderModel->getKDSList());
    }

    // Waiting -> Cooking -> Ready -> Served 
    public function updateKds() {
        $this->guardPost();
        $d = $this->input();
        $id = (int)($d['order_detail_id'] ?? 0);
        $status = $d['status'] ?? '';
        if (!$id || !in_array($status, ['Cooking', 'Ready', 'Served'], true)) {
            $this->json(['status' => 'error', 'message' => 'Dữ liệu không hợp lệ']);
        }
      
        $this->json($this->orderModel->updateKdsStatus($id, $status, !empty($d['takeaway'])));
    }

    // Xem trước hóa đơn 
    public function previewBill() {
        $this->guard();
        $bill = $this->orderModel->calcBill(
            (int)($_GET['order_id'] ?? 0),
            $_GET['customer_phone'] ?? '',
            $_GET['voucher_code'] ?? ''
        );
        if (!$bill) $this->json(['status' => 'error', 'message' => 'Không tìm thấy đơn hàng!']);
        $this->json(['status' => 'success', 'data' => $bill]);
    }

    // Xác nhận đã thu tiền. Số tiền do server tính
    public function checkout() {
        $this->guardPost();
        $d = $this->input();
        $this->json($this->orderModel->checkoutOrder(
            (int)($d['order_id'] ?? 0),
            $this->uid(),
            $d['payment_method'] ?? '',
            $d['customer_phone'] ?? '',
            $d['voucher_code'] ?? '',
            $d['transaction_ref'] ?? ''
        ));
        if (isset($result['success']) && $result['success'] == true) {
            $order_id = $data['order_id'];
            $cashier_id = $data['cashier_id'] ?? ($_SESSION['user']['id'] ?? 2);
            
            // 1. Tự động cộng dồn vào "Tổng bán hàng" (Nếu thanh toán bằng tiền mặt)
            $payment_method = $data['payment_method'] ?? 'cash';
            // Lấy tổng tiền thanh toán thực tế của đơn hàng từ kết quả trả về hoặc request
            $total_paid = $result['total_amount'] ?? ($data['total_amount'] ?? 0); 

            if ($payment_method === 'cash' && $total_paid > 0) {
                require_once __DIR__ . '/../../models/CashShiftModel.php'; // Điều chỉnh đường dẫn thư mục cho đúng nếu cần
                $cashModel = new CashShiftModel();
                $cashModel->recordSaleTransaction($cashier_id, $total_paid, 'sales');
            }

            // 2. Tự động trừ kho theo định lượng BOM và cảnh báo đỏ
            require_once __DIR__ . '/../../models/StockModel.php';
            $stockModel = new StockModel();
            $bomResult = $stockModel->deductStockByOrder($order_id, $cashier_id);
        }
    }
    // Báo cáo lọc theo ngày
    
    public function getReportData() {
        $this->guard();
        $from = $this->date($_GET['from_date'] ?? null);
        $to   = $this->date($_GET['to_date'] ?? null);
        if (!$from || !$to) $from = $to = null;

        $this->json([
            'payment_methods' => $this->orderModel->getReportByPaymentMethod($from, $to),
            'time_shifts'     => $this->orderModel->getTimeShiftReport($from, $to),
            'cancel_logs'     => $this->orderModel->getCancelLogsReport($from, $to),
            'move_logs'       => $this->orderModel->getMoveLogsReport($from, $to),
            'shifts'          => $this->orderModel->getShiftRevenueReport($from, $to)
        ]);
    }
}