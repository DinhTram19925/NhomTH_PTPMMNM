<?php

require_once __DIR__ . '/../models/CustomerModel.php';

class CustomerController extends Controller
{
    private CustomerModel $customerModel;

    public function __construct()
    {
        parent::__construct();
        $this->customerModel = new CustomerModel();
    }

    public function index()
    {
        $this->requireRole(['ADMIN', 'HOSTESS']);

        $keyword = trim($_GET['keyword'] ?? '');
        $customers = $this->customerModel->getAllCustomers($keyword);
        $statistics = $this->customerModel->getCustomerStatistics();

        $success = isset($_GET['success'])
            ? 'Thao tác thành công.'
            : '';

        $error = '';

        $this->render(
            'customer/index',
            compact('customers', 'statistics', 'keyword', 'success', 'error')
        );
    }

    public function create()
    {
        $this->requireRole(['ADMIN', 'HOSTESS']);

        $error = '';
        $customer = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $this->customerModel->saveCustomer($_POST);
                header('Location: index.php?c=customer&a=index&success=1');
                exit;
            } catch (Throwable $e) {
                $error = $e->getMessage();
            }
        }

        $this->render('customer/form', compact('customer', 'error'));
    }

    public function edit()
    {
        $this->requireRole(['ADMIN', 'HOSTESS']);

        $id = (int)($_GET['id'] ?? 0);
        $customer = $this->customerModel->findCustomer($id);

        if (!$customer) {
            http_response_code(404);
            die('Khong tim thay khach hang.');
        }

        $error = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $this->customerModel->saveCustomer($_POST, $id);
                header('Location: index.php?c=customer&a=index&success=1');
                exit;
            } catch (Throwable $e) {
                $error = $e->getMessage();
            }
        }

        $this->render('customer/form', compact('customer', 'error'));
    }

    public function detail()
    {
        $this->requireRole(['ADMIN', 'HOSTESS']);

        $id = (int)($_GET['id'] ?? 0);
        $customer = $this->customerModel->findCustomer($id);

        if (!$customer) {
            http_response_code(404);
            die('Khong tim thay khach hang.');
        }

        $this->render('customer/detail', compact('customer'));
    }
}
