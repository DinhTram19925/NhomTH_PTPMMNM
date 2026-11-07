<?php
require_once __DIR__ . '/../models/StockModel.php';

class StockController extends Controller {
    private $stockModel;

    public function __construct() { parent::__construct();
        $this->stockModel = new StockModel();
    }

    private function checkAccess($allowedRoles) {
        $currentRole = $_SESSION['user']['role_code'] ?? '';
        
        if ($currentRole === 'ADMIN') return true;

        if (!in_array($currentRole, $allowedRoles)) {
            echo "<script>alert('âŒ Báº N KHÃ”NG CÃ“ QUYá»€N TRUY Cáº¬P CHá»¨C NÄ‚NG NÃ€Y!'); window.location.href='index.php?c=menu&a=dashboard';</script>";
            exit;
        }
    }

    public function index() {
        $active_tab = isset($_REQUEST['tab']) ? trim($_REQUEST['tab']) : 'dashboard'; 
        $search = isset($_REQUEST['search']) ? trim($_REQUEST['search']) : '';
        
        $group_filter = isset($_REQUEST['group_filter']) ? trim($_REQUEST['group_filter']) : '';
        $unit_filter = isset($_REQUEST['unit_filter']) ? trim($_REQUEST['unit_filter']) : '';
        $status_filter = isset($_REQUEST['status_filter']) ? trim($_REQUEST['status_filter']) : '';
        
        $limit = 10; 
        
        $ingredients = [];
        $suppliers = [];
        $history_list = []; 
        $maxPage_ing = 0;
        $maxPage_sup = 0;
        $maxPage_hist = 0; 
        
        $kpis = [];
        $alerts = [];
        $activities = [];
        
        if ($active_tab == 'dashboard') {
            
            $kpis = $this->stockModel->getDashboardKPIs();
            $alerts = $this->stockModel->getLowStockAlerts(5); 
            
            $page_trans = isset($_REQUEST['page_trans']) ? (int)$_REQUEST['page_trans'] : 1;
            $page_trans = max(1, $page_trans);
            $limit_trans = 5; 
            $offset_trans = ($page_trans - 1) * $limit_trans;
            
            $total_trans = $this->stockModel->countRecentActivities();
            $maxPage_trans = ceil($total_trans / $limit_trans);
            $activities = $this->stockModel->getRecentActivities($limit_trans, $offset_trans);
            
        } elseif ($active_tab == 'ingredients') {
            
            $page_ing = isset($_GET['page_ing']) ? (int)$_GET['page_ing'] : 1;
            $page_ing = max(1, $page_ing); 
            $offset_ing = ($page_ing - 1) * $limit;
            
            $total_ing = $this->stockModel->countIngredients($search, $group_filter, $unit_filter, $status_filter);
            $maxPage_ing = ceil($total_ing / $limit);
            $ingredients = $this->stockModel->getIngredients($search, $group_filter, $unit_filter, $status_filter, $limit, $offset_ing);
            
        } elseif ($active_tab == 'suppliers') {
            
            $page_sup = isset($_GET['page_sup']) ? (int)$_GET['page_sup'] : 1;
            $page_sup = max(1, $page_sup);
            $offset_sup = ($page_sup - 1) * $limit;
            
            $total_sup = $this->stockModel->countSuppliers($search, $status_filter);
            $maxPage_sup = ceil($total_sup / $limit);
            $suppliers = $this->stockModel->getSuppliers($search, $status_filter, $limit, $offset_sup);
            
        } elseif ($active_tab == 'history') {
            $search_hist = isset($_GET['search_hist']) ? trim($_GET['search_hist']) : '';
            $type_hist   = isset($_GET['type_hist']) ? trim($_GET['type_hist']) : '';
            $date_filter = isset($_GET['date_filter']) ? trim($_GET['date_filter']) : '';

            $page_hist = isset($_GET['page_hist']) ? (int)$_GET['page_hist'] : 1;
            $limit_hist = 10;
            $offset_hist = ($page_hist - 1) * $limit_hist;

            $total_hist = $this->stockModel->countReceiptHistory($search_hist, $type_hist, $date_filter);
            $maxPage_hist = ceil($total_hist / $limit_hist);
            $history_list = $this->stockModel->getReceiptHistory($search_hist, $type_hist, $limit_hist, $offset_hist, $date_filter);
        } elseif ($active_tab == 'cash_shift') {
            
        } elseif ($active_tab == 'report') {
            $startDate = $_GET['start_date'] ?? date('Y-m-01 00:00:00');
            $endDate = $_GET['end_date'] ?? date('Y-m-t 23:59:59');
            $reportData = $this->stockModel->getInventoryAndProfitReport($startDate, $endDate);
        }
        
        require_once __DIR__ . '/../views/layouts/header.php'; require_once __DIR__ . '/../views/stock/index.php'; require_once __DIR__ . '/../views/layouts/footer.php';
    }

    public function create_receipt() {
        $this->checkAccess(['ADMIN', 'STOCK']);
        $type = isset($_GET['type']) ? $_GET['type'] : 'import'; 
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $receiptData = [
                'receipt_code' => trim($_POST['receipt_code']),
                'user_id'      => 1,
                'total_amount' => isset($_POST['total_amount']) ? (float)$_POST['total_amount'] : 0,
                'note'         => trim($_POST['note']),
                'created_at'   => date('Y-m-d H:i:s')
            ];

            $detailsData = [];
            if (isset($_POST['ingredients']) && is_array($_POST['ingredients'])) {
                foreach ($_POST['ingredients'] as $id => $detail) {
                    if ((float)$detail['quantity'] > 0) {
                        $detailsData[] = [
                            'ingredient_id' => $id,
                            'quantity'      => (float)$detail['quantity'],
                            'unit_cost'     => (float)$detail['unit_cost']
                        ];
                    }
                }
            }

            if (empty($detailsData)) {
                echo "<script>alert('âŒ Lá»—i: Báº¡n chÆ°a chá»n hÃ ng hÃ³a nÃ o Ä‘á»ƒ láº­p phiáº¿u!'); history.back();</script>";
                exit;
            }

            if ($type == 'import') {
                $receiptData['supplier_id'] = !empty($_POST['supplier_id']) ? $_POST['supplier_id'] : null;
                $result = $this->stockModel->createImportReceipt($receiptData, $detailsData);
                $msg = 'Nháº­p kho';
            } else {
                $result = $this->stockModel->createExportReceipt($receiptData, $detailsData);
                $msg = 'Xuáº¥t kho';
            }

            if ($result) {
                echo "<script>alert('âœ… Táº¡o phiáº¿u $msg thÃ nh cÃ´ng!'); window.location.href='index.php?c=stock&a=index&tab=history';</script>";
            } else {
                echo "<script>alert('âŒ Lá»—i há»‡ thá»‘ng: KhÃ´ng Ä‘á»§ hÃ ng trong kho hoáº·c cÃ³ lá»—i xáº£y ra!'); history.back();</script>";
            }
            exit;
        }

        $ingredients = $this->stockModel->getIngredients('', '', '', '', 1000, 0); 
        if ($type == 'import') {
            $suppliers = $this->stockModel->getSuppliers('', 'active', 1000, 0);
            require_once __DIR__ . '/../views/stock/stock_receipt/import_form.php';
        } else {
            require_once __DIR__ . '/../views/stock/stock_receipt/export_form.php'; 
        }
    }

    public function form_ingredient() {
        $this->checkAccess(['ADMIN', 'STOCK']);
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $ingredient = null;
        
        if ($id > 0) {
            $ingredient = $this->stockModel->getIngredientById($id);
            if (!$ingredient) {
                echo "<script>alert('KhÃ´ng tÃ¬m tháº¥y nguyÃªn liá»‡u!'); history.back();</script>"; exit;
            }
        }
        $suppliers = $this->stockModel->getSuppliers('', 'active', 1000, 0);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'name'            => trim($_POST['name']),
                'group_name'      => trim($_POST['group_name']),
                'unit'            => trim($_POST['unit']),
                'min_stock_level' => (float)$_POST['min_stock_level'],
                'cost_price'      => (float)$_POST['cost_price'],
                'supplier_id'     => !empty($_POST['supplier_id']) ? $_POST['supplier_id'] : null
            ];
            if ($id > 0) {
                $this->stockModel->updateIngredient($id, $data);
                echo "<script>alert('âœ… Cáº­p nháº­t nguyÃªn liá»‡u thÃ nh cÃ´ng!'); window.location.href='index.php?c=stock&a=index&tab=ingredients';</script>";
            } else {
                $data['ingredient_code'] = $this->stockModel->generateNextIngredientCode();
                $result = $this->stockModel->insertIngredient($data);
                if ($result) {
                    echo "<script>alert('âœ… ThÃªm nguyÃªn váº­t liá»‡u má»›i thÃ nh cÃ´ng!'); window.location.href='index.php?c=stock&a=index&tab=ingredients';</script>";
                } else {
                    echo "<script>alert('âŒ Lá»—i há»‡ thá»‘ng: KhÃ´ng thá»ƒ thÃªm nguyÃªn liá»‡u!'); history.back();</script>";
                }
            }
            exit;
        }
        require_once __DIR__ . '/../views/layouts/header.php'; require_once __DIR__ . '/../views/stock/form_ingredient.php'; require_once __DIR__ . '/../views/layouts/footer.php';
    }

    public function delete_ingredient() {
        $this->checkAccess(['ADMIN']);
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id > 0) {
            $result = $this->stockModel->deleteIngredient($id);
            if ($result) {
                echo "<script>alert('âœ… ÄÃ£ xÃ³a nguyÃªn liá»‡u thÃ nh cÃ´ng!');</script>";
            } else {
                echo "<script>alert('âŒ Tá»ª CHá»I XÃ“A: NguyÃªn liá»‡u nÃ y Ä‘ang Ä‘Æ°á»£c dÃ¹ng trong CÃ´ng thá»©c (BOM) hoáº·c Phiáº¿u nháº­p. Vui lÃ²ng báº£o toÃ n dá»¯ liá»‡u!');</script>";
            }
        }
        echo "<script>window.location.href='index.php?c=stock&a=index&tab=ingredients';</script>";
    }

    public function export_excel() {
        $this->checkAccess(['ADMIN', 'STOCK', 'CHEF']);
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        $group_filter = isset($_GET['group_filter']) ? trim($_GET['group_filter']) : '';
        $unit_filter = isset($_GET['unit_filter']) ? trim($_GET['unit_filter']) : '';
        $status_filter = isset($_GET['status_filter']) ? trim($_GET['status_filter']) : '';
        
        $ingredients = $this->stockModel->getIngredients($search, $group_filter, $unit_filter, $status_filter, 10000, 0);
        
        header("Content-Type: application/vnd.ms-excel; charset=utf-8");
        header("Content-Disposition: attachment; filename=Bao_Cao_Nguyen_Vat_Lieu_" . date('dmY_Hi') . ".xls");
        header("Pragma: no-cache");
        header("Expires: 0");
        
        echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel"><head><meta charset="utf-8"></head><body><table border="1">';
        echo '<tr style="background-color: #c9a050; color: #ffffff; font-weight: bold; text-align: center;">
                <th>STT</th><th>MÃ£ NVL</th><th>TÃªn NguyÃªn Váº­t Liá»‡u</th><th>NhÃ³m HÃ ng</th><th>ÄVT</th>
                <th>Tá»“n Hiá»‡n Táº¡i</th><th>Tá»“n Tá»‘i Thiá»ƒu</th><th>GiÃ¡ Vá»‘n (VNÄ)</th><th>NhÃ  Cung Cáº¥p</th></tr>';
        
        if(!empty($ingredients)) {
            $stt = 1;
            foreach($ingredients as $item) {
                $isLowStock = $item['stock_quantity'] <= $item['min_stock_level'];
                $stockColor = $isLowStock ? 'color: red; font-weight: bold;' : '';
                echo '<tr>';
                echo '<td style="text-align: center;">' . $stt++ . '</td>';
                echo '<td style="text-align: center;">#' . htmlspecialchars($item['ingredient_code']) . '</td>';
                echo '<td>' . htmlspecialchars($item['name']) . '</td>';
                echo '<td style="text-align: center;">' . htmlspecialchars($item['group_name'] ?? 'ChÆ°a phÃ¢n nhÃ³m') . '</td>';
                echo '<td style="text-align: center;">' . htmlspecialchars($item['unit']) . '</td>';
                echo '<td style="text-align: right; ' . $stockColor . '">' . number_format($item['stock_quantity'], 2) . '</td>';
                echo '<td style="text-align: right;">' . number_format($item['min_stock_level'], 2) . '</td>';
                echo '<td style="text-align: right;">' . number_format($item['cost_price'], 0, ',', '.') . '</td>';
                echo '<td>' . htmlspecialchars($item['supplier_name'] ?? 'ChÆ°a xÃ¡c Ä‘á»‹nh') . '</td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="9" style="text-align: center;">KhÃ´ng cÃ³ dá»¯ liá»‡u phÃ¹ há»£p</td></tr>';
        }
        echo '</table></body></html>';
        exit; 
    }

    public function form_supplier() {
        $this->checkAccess(['ADMIN', 'STOCK']);
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $supplier = null;
        if ($id > 0) {
            $supplier = $this->stockModel->getSupplierById($id);
            if (!$supplier) {
                echo "<script>alert('KhÃ´ng tÃ¬m tháº¥y nhÃ  cung cáº¥p!'); history.back();</script>"; exit;
            }
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'name'           => trim($_POST['name']),
                'contact_person' => trim($_POST['contact_person']),
                'phone'          => trim($_POST['phone']),
                'address'        => trim($_POST['address']),
                'debt_balance'   => (float)$_POST['debt_balance'],
                'status'         => trim($_POST['status'])
            ];
            if ($id > 0) {
                $this->stockModel->updateSupplier($id, $data);
                echo "<script>alert('âœ… Cáº­p nháº­t NhÃ  cung cáº¥p thÃ nh cÃ´ng!'); window.location.href='index.php?c=stock&a=index&tab=suppliers';</script>";
            } else {
                $result = $this->stockModel->insertSupplier($data);
                if ($result) {
                    echo "<script>alert('âœ… ThÃªm NhÃ  cung cáº¥p má»›i thÃ nh cÃ´ng!'); window.location.href='index.php?c=stock&a=index&tab=suppliers';</script>";
                } else {
                    echo "<script>alert('âŒ Lá»—i há»‡ thá»‘ng: KhÃ´ng thá»ƒ thÃªm NCC!'); history.back();</script>";
                }
            }
            exit;
        }
        require_once __DIR__ . '/../views/layouts/header.php'; require_once __DIR__ . '/../views/stock/form_supplier.php'; require_once __DIR__ . '/../views/layouts/footer.php';
    }

    public function delete_supplier() {
        $this->checkAccess(['ADMIN']);
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        
        if ($id > 0) {
            $result = $this->stockModel->deleteSupplier($id);
            
            if ($result) {
                echo "<script>alert('âœ… ÄÃ£ xÃ³a nhÃ  cung cáº¥p thÃ nh cÃ´ng!');</script>";
            } else {
                echo "<script>alert('âŒ Tá»ª CHá»I XÃ“A: NhÃ  cung cáº¥p nÃ y Ä‘Ã£ phÃ¡t sinh Phiáº¿u nháº­p kho. Äá»ƒ báº£o toÃ n dá»¯ liá»‡u káº¿ toÃ¡n, báº¡n chá»‰ cÃ³ thá»ƒ chuyá»ƒn tráº¡ng thÃ¡i sang \"Ngá»«ng há»£p tÃ¡c\"!');</script>";
            }
        }
        echo "<script>window.location.href='index.php?c=stock&a=index&tab=suppliers';</script>";
        exit;
    }

    public function export_suppliers_excel() {
        $this->checkAccess(['ADMIN', 'STOCK']);
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        $status_filter = isset($_GET['status_filter']) ? trim($_GET['status_filter']) : '';
        
        $suppliers = $this->stockModel->getSuppliers($search, $status_filter, 10000, 0);
        
        header("Content-Type: application/vnd.ms-excel; charset=utf-8");
        header("Content-Disposition: attachment; filename=Danh_Sach_NCC_" . date('dmY_Hi') . ".xls");
        header("Pragma: no-cache");
        header("Expires: 0");
        
        echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel"><head><meta charset="utf-8"></head><body><table border="1">';
        echo '<tr style="background-color: #c9a050; color: #ffffff; font-weight: bold; text-align: center;">
                <th>STT</th><th>TÃªn NhÃ  Cung Cáº¥p</th><th>NgÆ°á»i LiÃªn Há»‡</th><th>Sá»‘ Äiá»‡n Thoáº¡i</th>
                <th>Äá»‹a Chá»‰</th><th>CÃ´ng Ná»£ (VNÄ)</th><th>Tráº¡ng ThÃ¡i</th></tr>';
        
        if(!empty($suppliers)) {
            $stt = 1;
            foreach($suppliers as $sup) {
                $debtColor = $sup['debt_balance'] > 0 ? 'color: red; font-weight: bold;' : 'color: green;';
                $statusText = $sup['status'] === 'active' ? 'Äang há»£p tÃ¡c' : 'Ngá»«ng há»£p tÃ¡c';
                
                echo '<tr>';
                echo '<td style="text-align: center;">' . $stt++ . '</td>';
                echo '<td style="font-weight: bold;">' . htmlspecialchars($sup['name']) . '</td>';
                echo '<td>' . htmlspecialchars($sup['contact_person'] ?? '') . '</td>';
                echo '<td style="text-align: center; mso-number-format:\'\@\';">' . htmlspecialchars($sup['phone'] ?? '') . '</td>';
                echo '<td>' . htmlspecialchars($sup['address'] ?? '') . '</td>';
                echo '<td style="text-align: right; ' . $debtColor . '">' . number_format($sup['debt_balance'], 0, ',', '.') . '</td>';
                echo '<td style="text-align: center;">' . $statusText . '</td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="7" style="text-align: center;">KhÃ´ng cÃ³ dá»¯ liá»‡u phÃ¹ há»£p</td></tr>';
        }
        echo '</table></body></html>';
        exit;
    }
}
?>
