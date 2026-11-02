<?php
require_once __DIR__ . '/../models/OrderModel.php';

class PosController {
    private $orderModel;

    public function __construct() {

        
        $this->orderModel = new OrderModel(); 

    }

    public function index() {
        require_once __DIR__ . '/../views/pos/index.php';
    }

    public function createOrder() {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents('php://input'), true);
        
        $result = $this->orderModel->createOrAddOrder(
            $data['table_id'], 
            $data['waiter_id'], 
            $data['items']
        );
        echo json_encode($result);
        exit;
    }

    public function getKds() {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');
        echo json_encode($this->orderModel->getKDSList());
        exit;
    }

    public function checkout() {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents('php://input'), true);

        $result = $this->orderModel->checkoutOrder(
            $data['order_id'],
            $data['cashier_id'],
            $data['payment_method'],
            $data['discount_amount'] ?? 0
        );
        echo json_encode($result);
        exit;
    }

  
    // API Chuyển / Gộp bàn theo Table ID
    public function transferTable() {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents('php://input'), true);

        $user_id = $_SESSION['user']['id'] ?? 1;
        $result = $this->orderModel->transferTableByTableId($data['from_table_id'], $data['to_table_id'], $user_id);
        echo json_encode($result);
        exit;
    }


    // API Hủy món
    public function cancelItem() {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents('php://input'), true);

        $user_id = $_SESSION['user']['id'] ?? 1;
        $result = $this->orderModel->cancelOrderItem($data['order_detail_id'], $user_id, $data['reason']);
        echo json_encode($result);
        exit;
    }

    public function printBill() {
        $order_id = $_GET['order_id'] ?? 0;
        $billData = $this->orderModel->getBillData($order_id);

        if (!$billData) {
            echo "<h3>Không tìm thấy dữ liệu hóa đơn!</h3>";
            exit;
        }

        $order = $billData['order'];
        $details = $billData['details'];
        require_once __DIR__ . '/../views/pos/print_bill.php';
    }
}