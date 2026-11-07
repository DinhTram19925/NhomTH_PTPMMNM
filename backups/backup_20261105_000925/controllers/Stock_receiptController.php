<?php
require_once __DIR__ . '/../models/StockModel.php';

class Stock_receiptController extends Controller {
    private $stockModel;

    public function __construct() { parent::__construct();
        $this->stockModel = new StockModel();
    }

    private function checkAccess($allowedRoles) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $currentRole = $_SESSION['user']['role_code'] ?? '';
        
        if ($currentRole === 'ADMIN') return true;
        if (!in_array($currentRole, $allowedRoles)) {
            echo "<script>alert('âŒ Báº N KHÃ”NG CÃ“ QUYá»€N TRUY Cáº¬P CHá»¨C NÄ‚NG NÃ€Y!'); window.location.href='index.php?c=stock&tab=history';</script>";
            exit;
        }
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
            $detailsData = [];

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

            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $current_user_id = ($_SESSION['user']['id'] ?? 1); 

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
                    $msgType = 'nháº­p kho';
                } else {
                    $isSaved = $this->stockModel->createExportReceipt($receiptData, $detailsData);
                    $msgType = 'xuáº¥t kho';
                }
                
                if ($isSaved) {
                    echo "<script>
                        alert('âœ… LÆ°u phiáº¿u $msgType thÃ nh cÃ´ng! Kho hÃ ng Ä‘Ã£ Ä‘Æ°á»£c cáº­p nháº­t.');
                        window.location.href = 'index.php?c=stock&a=index&tab=dashboard';
                    </script>";
                    exit;
                } else {
                    echo "<script>alert('âŒ Lá»—i há»‡ thá»‘ng: KhÃ´ng Ä‘á»§ sá»‘ lÆ°á»£ng trong kho hoáº·c khÃ´ng thá»ƒ lÆ°u phiáº¿u lÃºc nÃ y!');</script>";
                }
            } else {
                echo "<script>alert('âŒ Vui lÃ²ng chá»n Ã­t nháº¥t má»™t nguyÃªn váº­t liá»‡u vÃ  nháº­p sá»‘ lÆ°á»£ng!');</script>";
            }
        }

        if ($type === 'import') {
            require_once __DIR__ . '/../views/layouts/header.php'; require_once __DIR__ . '/../views/stock/stock_receipt/import_form.php'; require_once __DIR__ . '/../views/layouts/footer.php';
        } else {
            require_once __DIR__ . '/../views/layouts/header.php'; require_once __DIR__ . '/../views/stock/stock_receipt/export_form.php'; require_once __DIR__ . '/../views/layouts/footer.php';
        }
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
            echo "<script>alert('MÃ£ phiáº¿u khÃ´ng há»£p lá»‡ hoáº·c khÃ´ng tÃ¬m tháº¥y!'); window.location.href='index.php?c=stock&a=index&tab=history';</script>";
            exit;
        }

        $details = $this->stockModel->getReceiptDetails($receipt['id']);

        if (strpos($receipt['receipt_code'], 'PN') === 0) {
            require_once __DIR__ . '/../views/layouts/header.php'; require_once __DIR__ . '/../views/stock/stock_receipt/show.php'; require_once __DIR__ . '/../views/layouts/footer.php';
            
        } elseif (strpos($receipt['receipt_code'], 'KK') === 0) {
            require_once __DIR__ . '/../views/layouts/header.php'; require_once __DIR__ . '/../views/stock/stock_receipt/show_audit.php'; require_once __DIR__ . '/../views/layouts/footer.php';
            
        } else {
            require_once __DIR__ . '/../views/layouts/header.php'; require_once __DIR__ . '/../views/stock/stock_receipt/show_receipt.php'; require_once __DIR__ . '/../views/layouts/footer.php';
        }
    }
    public function audit() {
        $this->checkAccess(['ADMIN', 'STOCK']);
        $ingredients = $this->stockModel->getIngredients('', '', '', '', 1000, 0);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            
            $user_id = ($_SESSION['user']['id'] ?? 1);
            $receipt_code = trim($_POST['receipt_code'] ?? 'KK' . date('ymdHis'));
            $note         = trim($_POST['note'] ?? 'Phiáº¿u Ä‘iá»u chá»‰nh kiá»ƒm kÃª Ä‘á»‹nh ká»³');
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

            if (!empty($auditDetails)) {$receiptData = [
                    'receipt_code' => $receipt_code,
                    'user_id'      => $user_id,
                    'note'         => $note,
                    'created_at'   => $created_at
                ];

                $receiptId =$this->stockModel->createAuditReceipt($receiptData,$auditDetails);
                
                if ($receiptId) {
                    echo "<script>
                        alert('âœ… Kiá»ƒm kÃª thÃ nh cÃ´ng! Kho Ä‘Ã£ Ä‘Æ°á»£c cÃ¢n báº±ng thá»±c táº¿.'); 
                        window.location.href='index.php?c=stock_receipt&a=show&id=" . $receiptId . "&from=dashboard';
                    </script>";
                    exit;
                } else {
                    echo "<script>alert('âŒ Lá»—i há»‡ thá»‘ng: KhÃ´ng thá»ƒ lÆ°u phiáº¿u kiá»ƒm kÃª!');</script>";
                }
            } else {
                echo "<script>alert('âœ… Khá»›p 100%! KhÃ´ng cÃ³ chÃªnh lá»‡ch nÃ o cáº§n Ä‘iá»u chá»‰nh.'); window.location.href='index.php?c=stock&a=index&tab=dashboard';</script>";
                exit;
            }
        }

        require_once __DIR__ . '/../views/layouts/header.php'; require_once __DIR__ . '/../views/stock/stock_receipt/audit_form.php'; require_once __DIR__ . '/../views/layouts/footer.php';
    }
    public function delete() {
        $this->checkAccess(['ADMIN', 'STOCK']);

        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id > 0) {
            $receipt = $this->stockModel->getReceiptById($id);
            if ($receipt) {
                $result = $this->stockModel->deleteReceiptWithRevert($id, $receipt['receipt_type']);
                if ($result) {
                    echo "<script>alert('âœ… ÄÃ£ há»§y phiáº¿u vÃ  hoÃ n tráº£ sá»‘ lÆ°á»£ng kho thÃ nh cÃ´ng!');</script>";
                } else {
                    echo "<script>alert('âŒ Lá»–I: KhÃ´ng thá»ƒ há»§y phiáº¿u. Dá»¯ liá»‡u Ä‘ang Ä‘Æ°á»£c rÃ ng buá»™c!');</script>";
                }
            } else {
                echo "<script>alert('âŒ KhÃ´ng tÃ¬m tháº¥y phiáº¿u nÃ y!');</script>";
            }
        }
        echo "<script>window.location.href='index.php?c=stock&tab=history';</script>";
        exit;
    }
}
?>

