
<?php
require_once __DIR__ . '/../models/PromotionModel.php';

class PromotionController extends Controller
{
    private PromotionModel $promotionModel;

    public function __construct()
    {
        parent::__construct();
        $this->promotionModel = new PromotionModel();
    }

    private function checkAccess(): void
    {
        $this->requireRole(['ADMIN']);
    }

    // Danh sach voucher
    public function index(): void
    {
        $this->checkAccess();

        $keyword = trim($_GET['keyword'] ?? '');
        $promotions = $this->promotionModel->getAllPromotions($keyword);

        $this->render('promotion/index', [
            'promotions' => $promotions,
            'keyword' => $keyword
        ]);
    }

    // Form them voucher
    public function create(): void
    {
        $this->checkAccess();

        $this->render('promotion/form', [
            'promotion' => null
        ]);
    }

    // Luu voucher moi
    public function store(): void
    {
        $this->checkAccess();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?c=promotion&a=index');
            exit;
        }

        try {
            $this->promotionModel->savePromotion($_POST);
            $_SESSION['promotion_message'] = 'Thêm voucher thành công.';
        } catch (Throwable $e) {
            $_SESSION['promotion_error'] = $e->getMessage();
            header('Location: index.php?c=promotion&a=create');
            exit;
        }

        header('Location: index.php?c=promotion&a=index');
        exit;
    }

    // Form sua voucher
    public function edit(): void
    {
        $this->checkAccess();

        $id = (int)($_GET['id'] ?? 0);
        $promotion = $this->promotionModel->findPromotion($id);

        if (!$promotion) {
            http_response_code(404);
            exit('Không tìm thấy voucher.');
        }

        $this->render('promotion/form', [
            'promotion' => $promotion
        ]);
    }

    // Cap nhat voucher
    public function update(): void
    {
        $this->checkAccess();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?c=promotion&a=index');
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);

        try {
            $this->promotionModel->savePromotion($_POST, $id);
            $_SESSION['promotion_message'] = 'Cập nhật voucher thành công.';
        } catch (Throwable $e) {
            $_SESSION['promotion_error'] = $e->getMessage();
            header('Location: index.php?c=promotion&a=edit&id=' . $id);
            exit;
        }

        header('Location: index.php?c=promotion&a=index');
        exit;
    }

    // Bat hoac tat voucher
    public function toggle(): void
    {
        $this->checkAccess();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int)($_POST['id'] ?? 0);
            $this->promotionModel->togglePromotion($id);
        }

        header('Location: index.php?c=promotion&a=index');
        exit;
    }

    // Xoa voucher
    public function delete(): void
    {
        $this->checkAccess();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int)($_POST['id'] ?? 0);
            $this->promotionModel->deletePromotion($id);
        }

        header('Location: index.php?c=promotion&a=index');
        exit;
    }

    // Kiem thu voucher
    public function testVoucher(): void
    {
        $this->checkAccess();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?c=promotion&a=index');
            exit;
        }

        $code = trim($_POST['code'] ?? '');
        $orderValue = (float)($_POST['order_value'] ?? 0);
        $customerTier = $_POST['customer_tier'] ?? 'Standard';

        $allowedTiers = ['Standard', 'Silver', 'Gold', 'Diamond'];

        if (!in_array($customerTier, $allowedTiers, true)) {
            $customerTier = 'Standard';
        }

        if ($code === '' || $orderValue <= 0) {
            $result = [
                'valid' => false,
                'message' => 'Vui lòng nhập mã voucher và giá trị đơn hàng hợp lệ.'
            ];
        } else {
            try {
                $result = $this->promotionModel->validateVoucher(
                    $code,
                    $orderValue,
                    $customerTier
                );
            } catch (Throwable $e) {
                $result = [
                    'valid' => false,
                    'message' => $e->getMessage()
                ];
            }
        }

        $promotions = $this->promotionModel->getAllPromotions();

        $this->render('promotion/index', [
            'promotions' => $promotions,
            'keyword' => '',
            'testResult' => $result,
            'testInput' => [
                'code' => $code,
                'order_value' => $orderValue,
                'customer_tier' => $customerTier
            ]
        ]);
    }
}
