<?php
require_once __DIR__ . '/../models/StockModel.php';

class Stock_receiptController extends Controller {
    private $stockModel;

    public function __construct() {
        parent::__construct();
        $this->stockModel = new StockModel();
    }

    private function checkAccess($allowedRoles) {
        $this->requireRole($allowedRoles);
    }

    public function create() {
        $this->checkAccess(['ADMIN', 'STOCK']);
        $type = $_GET['type'] ?? 'import';

        $suppliers = $this->stockModel->getSuppliers('', 'active', 1000, 0);
        $ingredients = $this->stockModel->getIngredients('', '', '', '', 1000, 0);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $receipt_code = trim($_POST['receipt_code']);
            $supplier_id  = ($type === 'import') ? ($_POST['supplier_id'] ?? null) : null;
            $note         = trim($_POST['note']);
            $created_at   = ($_POST['created_at'] ?? date('Y-m-d')) . ' ' . date('H:i:s');

            $ingredient_ids = $_POST['ingredient_id'] ?? [];
            $quantities     = $_POST['quantity'] ?? [];
            $unit_costs     = $_POST['unit_cost'] ?? [];

            $total_amount = 0;
            $detailsData  = [];

            for ($i = 0; $i < count($ingredient_ids); $i++) {
                $ing_id = (int)$ingredient_ids[$i];
                $qty    = (float)($quantities[$i] ?? 0);
                $cost   = (float)($unit_costs[$i] ?? 0);

                if ($ing_id > 0 && $qty > 0) {
                    $detailsData[] = [
                        'ingredient_id' => $ing_id,
                        'quantity'      => $qty,
                        'unit_cost'     => $cost
                    ];
                    $total_amount += $qty * $cost;
                }
            }

            $current_user_id = (int)($_SESSION['user']['id'] ?? 1);
            $receiptData = [
                'receipt_code' => $receipt_code,
                'supplier_id'  => $supplier_id,
                'user_id'      => $current_user_id,
                'total_amount' => $total_amount,
                'note'         => $note,
                'created_at'   => $created_at
            ];

            if (!empty($detailsData)) {
                if ($type === 'import') {
                    $isSaved = $this->stockModel->createImportReceipt($receiptData, $detailsData);
                    $msgType = 'nhập kho';
                } else {
                    $isSaved = $this->stockModel->createExportReceipt($receiptData, $detailsData);
                    $msgType = 'xuất kho';
                }

                if ($isSaved) {
                    echo "<script>
                        alert('Đã lưu phiếu $msgType thành công! Số lượng tồn kho đã được cập nhật.');
                        window.location.href = 'index.php?c=stock&a=index&tab=dashboard';
                    </script>";
                    exit;
                } else {
                    echo "<script>alert('Không đủ số lượng tồn kho để xuất hoặc lỗi kết nối dữ liệu!');</script>";
                }
            } else {
                echo "<script>alert('Vui lòng chọn ít nhất một nguyên vật liệu và nhập số lượng hợp lệ!');</script>";
            }
        }

        require_once __DIR__ . '/../views/layouts/header.php';
        if ($type === 'import') {
            require_once __DIR__ . '/../views/stock/stock_receipt/import_form.php';
        } else {
            require_once __DIR__ . '/../views/stock/stock_receipt/export_form.php';
        }
        require_once __DIR__ . '/../views/layouts/footer.php';
    }

    public function show() {
        $this->checkAccess(['ADMIN', 'STOCK', 'CHEF']);
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $code = isset($_GET['code']) ? trim($_GET['code']) : '';

        $receipt = null;
        if ($id > 0) {
            $receipt = $this->stockModel->getReceiptById($id);
        } elseif (!empty($code)) {
            $receipt = $this->stockModel->getReceiptByCode($code);
        }

        if (!$receipt) {
            echo "<script>alert('Mã phiếu không hợp lệ hoặc không tồn tại!'); window.location.href='index.php?c=stock&a=index&tab=history';</script>";
            exit;
        }

        $details = $this->stockModel->getReceiptDetails($receipt['id']);

        require_once __DIR__ . '/../views/layouts/header.php';
        if (strpos($receipt['receipt_code'], 'PN') === 0) {
            require_once __DIR__ . '/../views/stock/stock_receipt/show.php';
        } elseif (strpos($receipt['receipt_code'], 'KK') === 0) {
            require_once __DIR__ . '/../views/stock/stock_receipt/show_audit.php';
        } else {
            require_once __DIR__ . '/../views/stock/stock_receipt/show_receipt.php';
        }
        require_once __DIR__ . '/../views/layouts/footer.php';
    }

    public function audit() {
        $this->checkAccess(['ADMIN', 'STOCK']);
        $ingredients = $this->stockModel->getIngredients('', '', '', '', 1000, 0);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $user_id      = (int)($_SESSION['user']['id'] ?? 1);
            $receipt_code = trim($_POST['receipt_code'] ?? 'KK' . date('ymdHis'));
            $note         = trim($_POST['note'] ?? 'Phiếu điều chỉnh kiểm kê định kỳ');
            $created_at   = date('Y-m-d H:i:s');

            $ingredient_ids = $_POST['ingredient_id'] ?? [];
            $current_qtys   = $_POST['current_qty'] ?? [];
            $actual_qtys    = $_POST['actual_qty'] ?? [];
            $unit_costs     = $_POST['unit_cost'] ?? [];
            $reasons        = $_POST['reason'] ?? [];

            $auditDetails = [];
            for ($i = 0; $i < count($ingredient_ids); $i++) {
                if (isset($actual_qtys[$i]) && $actual_qtys[$i] !== '') {
                    $actual_qty  = (float)$actual_qtys[$i];
                    $current_qty = (float)$current_qtys[$i];
                    $diff        = $actual_qty - $current_qty;

                    if ($diff != 0) {
                        $auditDetails[] = [
                            'ingredient_id' => (int)$ingredient_ids[$i],
                            'actual_qty'    => $actual_qty,
                            'difference'    => $diff,
                            'unit_cost'     => (float)($unit_costs[$i] ?? 0),
                            'reason'        => trim($reasons[$i] ?? '')
                        ];
                    }
                }
            }

            if (!empty($auditDetails)) {
                $receiptData = [
                    'receipt_code' => $receipt_code,
                    'user_id'      => $user_id,
                    'note'         => $note,
                    'created_at'   => $created_at
                ];
                $receiptId = $this->stockModel->createAuditReceipt($receiptData, $auditDetails);
                if ($receiptId) {
                    echo "<script>
                        alert('Đã hoàn tất kiểm kê! Số lượng tồn kho thực tế đã được cân bằng.');
                        window.location.href='index.php?c=stock_receipt&a=show&id=" . $receiptId . "&from=dashboard';
                    </script>";
                    exit;
                } else {
                    echo "<script>alert('Không thể lưu phiếu kiểm kê!');</script>";
                }
            } else {
                echo "<script>alert('Số liệu tồn kho khớp 100%, không có chênh lệch cần điều chỉnh.'); window.location.href='index.php?c=stock&a=index&tab=dashboard';</script>";
                exit;
            }
        }

        require_once __DIR__ . '/../views/layouts/header.php';
        require_once __DIR__ . '/../views/stock/stock_receipt/audit_form.php';
        require_once __DIR__ . '/../views/layouts/footer.php';
    }

    public function delete() {
        $this->checkAccess(['ADMIN', 'STOCK']);
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id > 0) {
            $receipt = $this->stockModel->getReceiptById($id);
            if ($receipt) {
                $result = $this->stockModel->deleteReceiptWithRevert($id, $receipt['receipt_type']);
                if ($result) {
                    echo "<script>alert('Đã hủy phiếu và hoàn trả số lượng tồn kho thành công!');</script>";
                } else {
                    echo "<script>alert('Không thể hủy phiếu do dữ liệu đang được ràng buộc!');</script>";
                }
            } else {
                echo "<script>alert('Không tìm thấy phiếu kho này!');</script>";
            }
        }
        echo "<script>window.location.href='index.php?c=stock&tab=history';</script>";
        exit;
    }
}