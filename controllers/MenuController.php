<?php
require_once __DIR__ . '/../models/MenuModel.php';
require_once __DIR__ . '/../models/UserModel.php';

class MenuController extends Controller {
    private $menuModel;
    private $userModel;

    public function __construct() {
        parent::__construct();
        $this->menuModel = new MenuModel($this->db);
        $this->userModel = new UserModel($this->db);
    }

    // 1. TRANG TỔNG QUAN (DASHBOARD)
    public function dashboard() {
        $this->requireRole();
        $totalItems  =$this->db->query("SELECT COUNT(*) FROM menu_items WHERE status = 'Available'")->fetchColumn();
        $totalTables =$this->db->query("SELECT COUNT(*) FROM dining_tables")->fetchColumn();
        $totalUsers  =$this->db->query("SELECT COUNT(*) FROM users WHERE is_active = 1")->fetchColumn();
        $lowStock    =$this->db->query("SELECT COUNT(*) FROM ingredients WHERE stock_quantity <= min_stock_level")->fetchColumn();
        $items       =$this->menuModel->getAllItemsWithBom();

        $this->render('menu/dashboard', compact('totalItems', 'totalTables', 'totalUsers', 'lowStock', 'items'));
    }

    // 2. DANH SÁCH & THÊM MÓN ĂN
    public function index() {
        $this->requireRole(['ADMIN', 'CHEF']);$items = $this->menuModel->getAllItemsWithBom();$categories = $this->menuModel->getAllCategories();$this->render('menu/index', compact('items', 'categories'));
    }

    // 3. LƯU MÓN ĂN (THÊM MỚI HOẶC CẬP NHẬT)
    public function save() {
        $this->requireRole(['ADMIN', 'CHEF']);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id =$_POST['id'] ?? '';
            try {
                if (!empty($id)) {$this->menuModel->updateItem($id,$_POST);
                    $_SESSION['flash_msg'] = "✅ Đã cập nhật món ăn <b>{$_POST['name']}</b> thành công!";
                } else {
                    $this->menuModel->createItem($_POST);
                    $_SESSION['flash_msg'] = "✅ Đã thêm món mới <b>{$_POST['name']}</b> vào thực đơn!";
                }
            } catch (PDOException $e) {$_SESSION['flash_err'] = "⚠️ Lỗi: Mã món ăn đã tồn tại hoặc dữ liệu không hợp lệ!";
            }
        }
        header("Location: index.php?c=menu&a=index");
        exit;
    }

    // 4. XÓA MÓN ĂN
    public function delete() {
        $this->requireRole(['ADMIN']);
        $id =$_GET['id'] ?? 0;
        if ($id) {$result = $this->menuModel->deleteItem($id);
            if ($result === 'deleted') {$_SESSION['flash_msg'] = "🗑️ Đã xóa món ăn khỏi hệ thống!";
            } else {
                $_SESSION['flash_msg'] = "ℹ️ Món ăn đã có trong lịch sử hóa đơn nên được chuyển sang trạng thái 'Ngừng kinh doanh' (Discontinued).";
            }
        }
        header("Location: index.php?c=menu&a=index");
        exit;
    }

    // 5. THIẾT LẬP CÔNG THỨC ĐỊNH LƯỢNG (BOM) CHO MÓN ĂN
    public function bom() {
        $this->requireRole(['ADMIN', 'CHEF']);
        $menuItemId =$_GET['id'] ?? 0;
        $item = $this->menuModel->getItemById($menuItemId);
        if (!$item) {
            header("Location: index.php?c=menu&a=index");
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $ingredientId =$_POST['ingredient_id'];
            $quantity     = (float)$_POST['quantity_required'];
            $wastage      = (float)($_POST['wastage_percent'] ?? 0);

            if ($quantity > 0) {
                $this->menuModel->saveBomIngredient($menuItemId, $ingredientId,$quantity, $wastage);$_SESSION['flash_msg'] = "⚖️ Đã cập nhật định lượng nguyên vật liệu cho món <b>{$item['name']}</b>!";
            }
            header("Location: index.php?c=menu&a=bom&id=" . $menuItemId);
            exit;
        }

        $bomList =$this->menuModel->getBomByMenuItem($menuItemId);$ingredients = $this->menuModel->getAllIngredients();$this->render('menu/bom', compact('item', 'bomList', 'ingredients'));
    }

    // 6. XÓA 1 NGUYÊN LIỆU KHỎI CÔNG THỨC BOM
    public function deleteBom() {
        $this->requireRole(['ADMIN', 'CHEF']);
        $bomId =$_GET['bom_id'] ?? 0;
        $menuItemId =$_GET['item_id'] ?? 0;
        if ($bomId) {$this->menuModel->removeBomItem($bomId);$_SESSION['flash_msg'] = "🗑️ Đã gỡ nguyên liệu khỏi công thức định lượng!";
        }
        header("Location: index.php?c=menu&a=bom&id=" . $menuItemId);
        exit;
    }

    // 7. QUẢN LÝ NHÂN SỰ & PHÂN QUYỀN (RBAC)
    public function users() {
        $this->requireRole(['ADMIN']);$users = $this->userModel->getAllUsers();$roles = $this->userModel->getAllRoles();$this->render('menu/users', compact('users', 'roles'));
    }

    // 8. THÊM / SỬA NHÂN VIÊN
    public function saveUser() {
        $this->requireRole(['ADMIN']);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id =$_POST['id'] ?? '';
            try {
                if (!empty($id)) {
                    $this->userModel->updateUser($id, $_POST);$_SESSION['flash_msg'] = "✅ Đã cập nhật thông tin nhân viên <b>{$_POST['full_name']}</b>!";
                } else {
                    $this->userModel->createUser($_POST);$_SESSION['flash_msg'] = "✅ Đã tạo tài khoản mới <b>{$_POST['username']}</b> thành công!";
                }
            } catch (PDOException $e) {
                $_SESSION['flash_err'] = "⚠️ Lỗi: Tên đăng nhập '{$_POST['username']}' đã tồn tại!";
            }
        }
        header("Location: index.php?c=menu&a=users");
        exit;
    }

    // 9. KHÓA / MỞ KHÓA TÀI KHOẢN NHÂN VIÊN
    public function toggleUser() {
        $this->requireRole(['ADMIN']);
        $id = (int)($_GET['id'] ?? 0);
        if ($id === (int)$_SESSION['user']['id']) {$_SESSION['flash_err'] = "⛔ Bạn không thể tự khóa tài khoản Admin đang đăng nhập của chính mình!";
        } elseif ($id > 0) {$this->userModel->toggleActive($id);$_SESSION['flash_msg'] = "🔒 Đã thay đổi trạng thái hoạt động của tài khoản!";
        }
        header("Location: index.php?c=menu&a=users");
        exit;
    }

    // 10. ĐẶT LẠI MẬT KHẨU VỀ 123456
    public function resetPass() {
        $this->requireRole(['ADMIN']);
        $id = (int)($_GET['id'] ?? 0);
        if ($id > 0) {$this->userModel->resetPassword($id, '123456');$_SESSION['flash_msg'] = "🔑 Đã đặt lại mật khẩu của nhân viên về mặc định: <b>123456</b>!";
        }
        header("Location: index.php?c=menu&a=users");
        exit;
    }
}
