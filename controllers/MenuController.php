<?php
class MenuController extends Controller {
    public function dashboard() {
        $this->requireRole();
        $totalItems = $this->db->query("SELECT COUNT(*) FROM menu_items")->fetchColumn();
        $totalTables = $this->db->query("SELECT COUNT(*) FROM dining_tables")->fetchColumn();
        $totalUsers = $this->db->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $this->render('menu/dashboard', compact('totalItems', 'totalTables', 'totalUsers'));
    }

    public function index() {
        $this->requireRole(['ADMIN', 'CHEF']);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $stmt = $this->db->prepare("INSERT INTO menu_items (category_id, item_code, name, unit, price, status) VALUES (?, ?, ?, ?, ?, 'Available')");
            $stmt->execute([$_POST['category_id'], $_POST['item_code'], $_POST['name'], $_POST['unit'], $_POST['price']]);
            header("Location: index.php?c=menu&a=index");
            exit;
        }
        $items = $this->db->query("
            SELECT m.*, c.name AS category_name,
            (SELECT GROUP_CONCAT(CONCAT(i.name, ' (', b.quantity_required, i.unit, ')') SEPARATOR ', ')
             FROM recipes_bom b JOIN ingredients i ON b.ingredient_id = i.id WHERE b.menu_item_id = m.id) AS bom_list
            FROM menu_items m JOIN categories c ON m.category_id = c.id ORDER BY m.id DESC
        ")->fetchAll();
        $categories = $this->db->query("SELECT * FROM categories")->fetchAll();
        $this->render('menu/index', compact('items', 'categories'));
    }

    public function users() {
        $this->requireRole(['ADMIN']);
        $users = $this->db->query("SELECT u.*, r.role_code, r.role_name FROM users u JOIN roles r ON u.role_id = r.id")->fetchAll();
        $this->render('menu/users', compact('users'));
    }
}
