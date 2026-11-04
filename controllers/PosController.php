<?php
require_once __DIR__ . '/../models/OrderModel.php';

class PosController {
    private $orderModel;

    public function __construct() {
        // Đã sửa $this.orderModel thành $this->orderModel
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
}