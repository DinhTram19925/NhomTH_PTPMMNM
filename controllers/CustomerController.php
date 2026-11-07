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

    private function isAjaxRequest(): bool
    {
        return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || !empty($_POST['ajax']);
    }

    public function index()
    {
        $this->requireRole(['ADMIN', 'HOSTESS', 'WAITER', 'CASHIER']);

        $error = '';
        $success = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = !empty($_POST['customer_id']) ? (int)$_POST['customer_id'] : null;
            try {
                $this->customerModel->saveCustomer($_POST, $id);
                $msg = $id
                    ? 'Đã cập nhật hồ sơ khách hàng.'
                    : 'Đã tạo hồ sơ khách hàng mới.';

                if ($this->isAjaxRequest()) {
                    $this->json([
                        'status'  => 'success',
                        'message' => $msg,
                        'phone'   => preg_replace('/\D/', '', (string)($_POST['phone'] ?? ''))
                    ]);
                }

                $_SESSION['crm_flash_success'] = $msg;
                header('Location: index.php?c=customer&a=index');
                exit;
            } catch (Throwable $e) {
                if ($this->isAjaxRequest()) {
                    $this->json([
                        'status'  => 'error',
                        'message' => $e->getMessage()
                    ], 400);
                }
                $error = $e->getMessage();
            }
        }

        if (!empty($_SESSION['crm_flash_success'])) {
            $success = $_SESSION['crm_flash_success'];
            unset($_SESSION['crm_flash_success']);
        } elseif (isset($_GET['success'])) {
            $success = 'Thao tác thành công.';
        }

        $keyword = trim($_GET['keyword'] ?? '');
        $customers = $this->customerModel->getAllCustomers($keyword);
        $statistics = $this->customerModel->getCustomerStatistics();

        $this->render(
            'customer/index',
            compact('customers', 'statistics', 'keyword', 'success', 'error')
        );
    }

    public function create()
    {
        $this->requireRole(['ADMIN', 'HOSTESS', 'WAITER', 'CASHIER']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $this->customerModel->saveCustomer($_POST);
                $msg = 'Đã tạo hồ sơ khách hàng mới.';

                if ($this->isAjaxRequest()) {
                    $this->json([
                        'status'  => 'success',
                        'message' => $msg,
                        'phone'   => preg_replace('/\D/', '', (string)($_POST['phone'] ?? ''))
                    ]);
                }

                $_SESSION['crm_flash_success'] = $msg;
                header('Location: index.php?c=customer&a=index');
                exit;
            } catch (Throwable $e) {
                if ($this->isAjaxRequest()) {
                    $this->json([
                        'status'  => 'error',
                        'message' => $e->getMessage()
                    ], 400);
                }
                $_SESSION['crm_flash_error'] = $e->getMessage();
                header('Location: index.php?c=customer&a=index');
                exit;
            }
        }

        header('Location: index.php?c=customer&a=index');
        exit;
    }

    public function edit()
    {
        $this->requireRole(['ADMIN', 'HOSTESS', 'WAITER', 'CASHIER']);

        $id = (int)($_GET['id'] ?? $_POST['customer_id'] ?? 0);
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id > 0) {
            try {
                $this->customerModel->saveCustomer($_POST, $id);
                $_SESSION['crm_flash_success'] = 'Đã cập nhật hồ sơ khách hàng.';
                header('Location: index.php?c=customer&a=index');
                exit;
            } catch (Throwable $e) {
                $_SESSION['crm_flash_error'] = $e->getMessage();
                header('Location: index.php?c=customer&a=index');
                exit;
            }
        }

        if ($id > 0) {
            header('Location: index.php?c=customer&a=index&edit_id=' . $id);
            exit;
        }

        header('Location: index.php?c=customer&a=index');
        exit;
    }

    public function detail()
    {
        $this->requireRole(['ADMIN', 'HOSTESS', 'WAITER', 'CASHIER']);

        $id = (int)($_GET['id'] ?? 0);
        $customer = $this->customerModel->findCustomer($id);

        if (!$customer) {
            http_response_code(404);
            die('Không tìm thấy khách hàng.');
        }

        $this->render('customer/detail', compact('customer'));
    }
}