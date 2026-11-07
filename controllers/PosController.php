<?php
require_once __DIR__ . '/../models/OrderModel.php';

class PosController extends Controller {
    private $orderModel;

    const LOGIN_URL = 'index.php?c=auth&a=login';

    public function __construct() {
        parent::__construct();
        if (session_status() === PHP_SESSION_NONE) session_start();
        $this->orderModel = new OrderModel();
    }

    private function input() {
        $d = json_decode(file_get_contents('php://input'), true);
        return is_array($d) ? $d : [];
    }

    private function guard(bool $api = true, array $allowedRoles = ['ADMIN', 'WAITER', 'CASHIER', 'CHEF', 'BAR', 'HOSTESS']) {
        $this->requireRole($allowedRoles, $api);
    }

    private function guardPost(array $allowedRoles = ['ADMIN', 'WAITER', 'CASHIER']) {
        $this->guard(true, $allowedRoles);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['status' => 'error', 'message' => 'Phương thức không được hỗ trợ.'], 405);
        }
    }

    private function uid() { return (int)$_SESSION['user']['id']; }

    private function date($v) {
        return (is_string($v) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) && strtotime($v) !== false) ? $v : null;
    }

    public function index() {
        $this->guard(false, ['ADMIN', 'WAITER', 'CASHIER', 'CHEF', 'BAR', 'HOSTESS']);

        // 1. Tự động sửa lỗi font chữ món ăn làm vỡ JSON JavaScript trên POS
        $this->db->exec("
            UPDATE menu_items 
            SET name = 'Rượu vang đỏ Château Margaux (Ly)', unit = 'Ly' 
            WHERE name LIKE '%?%' OR item_code = 'RD-WINE01'
        ");

        // 2. Tự động giải phóng các đơn Open bị rỗng (không có món nào) hoặc bàn bị lệch trạng thái
        $this->db->exec("
            UPDATE orders o
            LEFT JOIN order_details od ON od.order_id = o.id AND od.kds_status <> 'Cancelled'
            SET o.status = 'Cancelled', o.closed_at = NOW()
            WHERE o.status = 'Open' AND od.id IS NULL AND o.reservation_id IS NULL
        ");
        $this->db->exec("
            UPDATE dining_tables dt
            LEFT JOIN orders o ON o.table_id = dt.id AND o.status = 'Open'
            SET dt.status = IF(o.id IS NULL, 'Available', 'Occupied')
            WHERE dt.status IN ('Available', 'Occupied')
        ");

        $this->render('pos/index');
    }
    public function report() {
        $this->guard(false, ['ADMIN', 'WAITER', 'CASHIER']);
        $this->render('pos/report');
    }

    public function printBill() {
        $this->guard(false, ['ADMIN', 'WAITER', 'CASHIER']);
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
        $this->guardPost(['ADMIN', 'WAITER', 'CASHIER']);
        $d = $this->input();
        $this->json($this->orderModel->createOrAddOrder(
            (int)($d['table_id'] ?? 0),
            $this->uid(),
            $d['items'] ?? []
        ));
    }

    public function cancelItem() {
        $this->guardPost(['ADMIN', 'WAITER', 'CASHIER', 'CHEF']);
        $d = $this->input();
        $this->json($this->orderModel->cancelOrderItem(
            (int)($d['order_detail_id'] ?? 0),
            $this->uid(),
            $d['reason'] ?? ''
        ));
    }

    public function transferTable() {
        $this->guardPost(['ADMIN', 'WAITER', 'CASHIER']);
        $d = $this->input();
        $mode = in_array($d['mode'] ?? '', ['transfer', 'merge'], true) ? $d['mode'] : '';
        $this->json($this->orderModel->transferTableByTableId(
            (int)($d['from_table_id'] ?? 0),
            (int)($d['to_table_id'] ?? 0),
            $this->uid(),
            $mode
        ));
    }

    public function closeEmptyOrder() {
        $this->guardPost(['ADMIN', 'WAITER', 'CASHIER']);
        $d = $this->input();
        $this->json($this->orderModel->closeEmptyOrder((int)($d['order_id'] ?? 0), $this->uid()));
    }

    public function getKds() {
        $this->guard(true, ['ADMIN', 'WAITER', 'CASHIER', 'CHEF', 'BAR']);
        $this->json($this->orderModel->getKDSList());
    }

    public function updateKds() {
        $this->guardPost(['ADMIN', 'CHEF', 'BAR', 'WAITER', 'CASHIER']);
        $d = $this->input();
        $id = (int)($d['order_detail_id'] ?? 0);
        $status = $d['status'] ?? '';
        if (!$id || !in_array($status, ['Cooking', 'Ready', 'Served'], true)) {
            $this->json(['status' => 'error', 'message' => 'Dữ liệu không hợp lệ']);
        }
        $this->json($this->orderModel->updateKdsStatus($id, $status, !empty($d['takeaway'])));
    }

    public function previewBill() {
        $this->guard(true, ['ADMIN', 'WAITER', 'CASHIER']);
        $bill = $this->orderModel->calcBill(
            (int)($_GET['order_id'] ?? 0),
            $_GET['customer_phone'] ?? '',
            $_GET['voucher_code'] ?? ''
        );
        if (!$bill) $this->json(['status' => 'error', 'message' => 'Không tìm thấy đơn hàng!']);
        $this->json(['status' => 'success', 'data' => $bill]);
    }

    public function checkout() {
        $this->guardPost(['ADMIN', 'WAITER', 'CASHIER']);
        $d = $this->input();
        $order_id = (int)($d['order_id'] ?? 0);
        $cashier_id = $this->uid();
        $payment_method = $d['payment_method'] ?? 'Cash';

        $result = $this->orderModel->checkoutOrder(
            $order_id,
            $cashier_id,
            $payment_method,
            $d['customer_phone'] ?? '',
            $d['voucher_code'] ?? '',
            $d['transaction_ref'] ?? ''
        );

        $this->json($result);
    }

    public function getReportData() {
        $this->guard(true, ['ADMIN', 'WAITER', 'CASHIER']);
        $from = $this->date($_GET['from_date'] ?? null);
        $to   = $this->date($_GET['to_date'] ?? null);
        if (!$from || !$to) $from = $to = null;

        $this->json([
            'payment_methods' => $this->orderModel->getReportByPaymentMethod($from, $to),
            'time_shifts'     => $this->orderModel->getTimeShiftReport($from, $to),
            'paid_orders'     => $this->orderModel->getPaidOrdersReport($from, $to),
            'cancel_logs'     => $this->orderModel->getCancelLogsReport($from, $to),
            'move_logs'       => $this->orderModel->getMoveLogsReport($from, $to),
            'shifts'          => $this->orderModel->getShiftRevenueReport($from, $to)
        ]);
    }
    public function quickCreateCustomer() {
        $this->guardPost(['ADMIN', 'WAITER', 'CASHIER', 'HOSTESS']);
        $d = $this->input();

        require_once __DIR__ . '/../models/CustomerModel.php';
        $customerModel = new CustomerModel();

        try {
            $fullName = trim((string)($d['full_name'] ?? ''));
            $phone    = preg_replace('/\D/', '', (string)($d['phone'] ?? ''));
            $email    = trim((string)($d['email'] ?? ''));

            $customerModel->saveCustomer([
                'full_name' => $fullName,
                'phone'     => $phone,
                'email'     => $email
            ]);

            $this->writeAuditLog('CREATE_CUSTOMER_POS', 'CRM & POS', "Tạo nhanh hội viên mới từ POS: {$fullName} ({$phone})", 'INFO');

            $this->json([
                'status'  => 'success',
                'message' => 'Đã tạo hồ sơ khách hàng thành công!',
                'phone'   => $phone,
                'name'    => $fullName
            ]);
        } catch (Throwable $e) {
            $this->json([
                'status'  => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
}