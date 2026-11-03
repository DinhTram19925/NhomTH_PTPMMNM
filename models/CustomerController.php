
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

    // Danh sách hội viên
    public function index()
    {
        $this->requireRole(['ADMIN', 'HOSTESS']);

        $keyword = trim($_GET['keyword'] ?? '');

        $customers = $this->customerModel->getAllCustomers($keyword);
        $statistics = $this->customerModel->getCustomerStatistics();

        $success = '';
        $error = '';

        if (isset($_GET['success'])) {
            $success = 'Thao tác đã được thực hiện thành công.';
        }

        $this->render(
            'customer/index',
            compact(
                'customers',
                'statistics',
                'keyword',
                'success',
                'error'
            )
        );
    }

    // Thêm hội viên
    public function create()
    {
        $this->requireRole(['ADMIN', 'HOSTESS']);

        $error = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $this->customerModel->saveCustomer($_POST);

                header('Location: index.php?c=customer&a=index&success=1');
                exit;

            } catch (Throwable $e) {
                $error = $e->getMessage();
            }
        }

        $customer = null;

        $this->render(
            'customer/form',
            compact('customer', 'error')
        );
    }

    // Chỉnh sửa hội viên
    public function edit()
    {
        $this->requireRole(['ADMIN', 'HOSTESS']);

        $id = (int)($_GET['id'] ?? 0);

        if ($id <= 0) {
            header('Location: index.php?c=customer&a=index');
            exit;
        }

        $customer = $this->customerModel->findCustomer($id);

        if (!$customer) {
            http_response_code(404);
            die('Không tìm thấy khách hàng.');
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

        $this->render(
            'customer/form',
            compact('customer', 'error')
        );
    }

    // Xem chi tiết hội viên
    public function detail()
    {
        $this->requireRole(['ADMIN', 'HOSTESS']);

        $id = (int)($_GET['id'] ?? 0);

        $customer = $this->customerModel->findCustomer($id);

        if (!$customer) {
            http_response_code(404);
            die('Không tìm thấy khách hàng.');
        }

        $this->render(
            'customer/detail',
            compact('customer')
        );
    }
}
