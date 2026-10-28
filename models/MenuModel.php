<?php
class MenuModel {
    private $db;

    public function __construct($db) {
        $this->db =$db;
    }

    public function getAllCategories() {
        return $this->db->query("SELECT * FROM categories ORDER BY display_order ASC")->fetchAll();
    }

    public function getAllItemsWithBom() {
        $sql = "
            SELECT m.*, c.name AS category_name, c.course_type,
            (SELECT GROUP_CONCAT(CONCAT(i.name, ' (', TRIM(TRAILING '.00' FROM b.quantity_required), i.unit, ')') SEPARATOR ', ')
             FROM recipes_bom b JOIN ingredients i ON b.ingredient_id = i.id 
             WHERE b.menu_item_id = m.id) AS bom_summary,
            (SELECT COALESCE(SUM(b.quantity_required * (1 + b.wastage_percent/100) * i.cost_price), 0)
             FROM recipes_bom b JOIN ingredients i ON b.ingredient_id = i.id 
             WHERE b.menu_item_id = m.id) AS food_cost
            FROM menu_items m
            JOIN categories c ON m.category_id = c.id
            ORDER BY m.id DESC
        ";
        return $this->db->query($sql)->fetchAll();
    }

    public function getItemById($id) {
        $stmt =$this->db->prepare("SELECT m.*, c.name AS category_name FROM menu_items m JOIN categories c ON m.category_id = c.id WHERE m.id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function createItem($data) {
        $stmt =$this->db->prepare("
            INSERT INTO menu_items (category_id, item_code, name, description, unit, price, status)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([$data['category_id'],
            strtoupper(trim($data['item_code'])),
            trim($data['name']),
            trim($data['description'] ?? ''),
            trim($data['unit'] ?: 'Phần'),
            $data['price'],$data['status'] ?? 'Available'
        ]);
    }

    public function updateItem($id,$data) {
        $stmt =$this->db->prepare("
            UPDATE menu_items 
            SET category_id = ?, item_code = ?, name = ?, description = ?, unit = ?, price = ?, status = ?
            WHERE id = ?
        ");
        return $stmt->execute([$data['category_id'],
            strtoupper(trim($data['item_code'])),
            trim($data['name']),
            trim($data['description'] ?? ''),
            trim($data['unit']),$data['price'],
            $data['status'],$id
        ]);
    }

    public function deleteItem($id) {
        try {
            $stmt =$this->db->prepare("DELETE FROM menu_items WHERE id = ?");
            $stmt->execute([$id]);
            return 'deleted';
        } catch (PDOException $e) {
            // Nếu món đã từng có trong hóa đơn (order_details), chuyển sang Ngừng kinh doanh để bảo toàn lịch sử
            $stmt =$this->db->prepare("UPDATE menu_items SET status = 'Discontinued' WHERE id = ?");
            $stmt->execute([$id]);
            return 'discontinued';
        }
    }

    // --- NGHIỆP VỤ ĐỊNH LƯỢNG NGUYÊN VẬT LIỆU (BOM) ---
    public function getBomByMenuItem($menuItemId) {
        $stmt =$this->db->prepare("
            SELECT b.*, i.ingredient_code, i.name AS ingredient_name, i.unit, i.cost_price, i.stock_quantity,
                   (b.quantity_required * (1 + b.wastage_percent/100) * i.cost_price) AS line_cost
            FROM recipes_bom b
            JOIN ingredients i ON b.ingredient_id = i.id
            WHERE b.menu_item_id = ?
            ORDER BY b.id ASC
        ");
        $stmt->execute([$menuItemId]);
        return $stmt->fetchAll();
    }

    public function getAllIngredients() {
        return $this->db->query("SELECT * FROM ingredients ORDER BY name ASC")->fetchAll();
    }

    public function saveBomIngredient($menuItemId,$ingredientId, $quantity,$wastage) {
        // Kiểm tra xem nguyên liệu này đã có trong công thức của món chưa
        $check =$this->db->prepare("SELECT id FROM recipes_bom WHERE menu_item_id = ? AND ingredient_id = ?");
        $check->execute([$menuItemId,$ingredientId]);
        $existing =$check->fetch();

        if ($existing) {
            $stmt =$this->db->prepare("UPDATE recipes_bom SET quantity_required = ?, wastage_percent = ? WHERE id = ?");
            return $stmt->execute([$quantity, $wastage,$existing['id']]);
        } else {
            $stmt =$this->db->prepare("INSERT INTO recipes_bom (menu_item_id, ingredient_id, quantity_required, wastage_percent) VALUES (?, ?, ?, ?)");
            return $stmt->execute([$menuItemId,$ingredientId, $quantity,$wastage]);
        }
    }

    public function removeBomItem($bomId) {
        $stmt =$this->db->prepare("DELETE FROM recipes_bom WHERE id = ?");
        return $stmt->execute([$bomId]);
    }
}
