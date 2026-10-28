<?php
require_once __DIR__ . '/../config/Database.php';

class StockModel {
    private $conn;

    public function __construct() {
        $this->conn = Database::getConnection();
    }

    // Lấy NVL có phân trang và tìm kiếm
    public function getIngredients($search = '', $limit = 10, $offset = 0) {
        $query = "SELECT * FROM ingredients 
                  WHERE name LIKE :search1 OR ingredient_code LIKE :search2 
                  ORDER BY name ASC LIMIT :limit OFFSET :offset";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':search1', "%$search%", PDO::PARAM_STR);
        $stmt->bindValue(':search2', "%$search%", PDO::PARAM_STR);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Đếm tổng số NVL để chia trang
    public function countIngredients($search = '') {
        $query = "SELECT COUNT(*) as total FROM ingredients WHERE name LIKE :search1 OR ingredient_code LIKE :search2";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':search1', "%$search%", PDO::PARAM_STR);
        $stmt->bindValue(':search2', "%$search%", PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch()['total'];
    }

    // Lấy NCC có phân trang và tìm kiếm
    public function getSuppliers($search = '', $limit = 10, $offset = 0) {
        $query = "SELECT * FROM suppliers 
                  WHERE name LIKE :search1 OR phone LIKE :search2 
                  ORDER BY name ASC LIMIT :limit OFFSET :offset";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':search1', "%$search%", PDO::PARAM_STR);
        $stmt->bindValue(':search2', "%$search%", PDO::PARAM_STR);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Đếm tổng số NCC để chia trang
    public function countSuppliers($search = '') {
        $query = "SELECT COUNT(*) as total FROM suppliers WHERE name LIKE :search1 OR phone LIKE :search2";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':search1', "%$search%", PDO::PARAM_STR);
        $stmt->bindValue(':search2', "%$search%", PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch()['total'];
    }
}
?>