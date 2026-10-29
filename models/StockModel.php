<?php
require_once __DIR__ . '/../config/Database.php';

class StockModel {
    private $conn;

    public function __construct() {
        $this->conn = Database::getConnection();
    }

    // ==========================================
    // QUẢN LÝ NGUYÊN VẬT LIỆU (INGREDIENTS)
    // ==========================================

    public function getIngredients($search = '', $group_filter = '', $unit_filter = '', $status_filter = '', $limit = 10, $offset = 0) {
        $query = "SELECT * FROM ingredients WHERE (name LIKE :search1 OR ingredient_code LIKE :search2)";
        $params = [
            ':search1' => "%$search%",
            ':search2' => "%$search%"
        ];

        if (!empty($group_filter)) {
            $query .= " AND group_name = :group";
            $params[':group'] = $group_filter;
        }

        if (!empty($unit_filter)) {
            $query .= " AND unit = :unit";
            $params[':unit'] = $unit_filter;
        }

        if ($status_filter === 'low') {
            $query .= " AND stock_quantity <= min_stock_level";
        } elseif ($status_filter === 'safe') {
            $query .= " AND stock_quantity > min_stock_level";
        }

        $query .= " ORDER BY name ASC LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val, PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countIngredients($search = '', $group_filter = '', $unit_filter = '', $status_filter = '') {
        $query = "SELECT COUNT(*) as total FROM ingredients WHERE (name LIKE :search1 OR ingredient_code LIKE :search2)";
        $params = [
            ':search1' => "%$search%",
            ':search2' => "%$search%"
        ];

        if (!empty($group_filter)) {
            $query .= " AND group_name = :group";
            $params[':group'] = $group_filter;
        }
        
        if (!empty($unit_filter)) {
            $query .= " AND unit = :unit";
            $params[':unit'] = $unit_filter;
        }
        
        if ($status_filter === 'low') {
            $query .= " AND stock_quantity <= min_stock_level";
        } elseif ($status_filter === 'safe') {
            $query .= " AND stock_quantity > min_stock_level";
        }

        $stmt = $this->conn->prepare($query);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val, PDO::PARAM_STR);
        }
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }


    // ==========================================
    // QUẢN LÝ NHÀ CUNG CẤP (SUPPLIERS)
    // ==========================================

    public function getSuppliers($search = '', $status_filter = '', $limit = 10, $offset = 0) {
        $query = "SELECT * FROM suppliers WHERE (name LIKE :search1 OR phone LIKE :search2)";
        $params = [
            ':search1' => "%$search%",
            ':search2' => "%$search%"
        ];

        if (!empty($status_filter)) {
            $query .= " AND status = :status";
            $params[':status'] = $status_filter;
        }

        $query .= " ORDER BY name ASC LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($query);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val, PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countSuppliers($search = '', $status_filter = '') {
        $query = "SELECT COUNT(*) as total FROM suppliers WHERE (name LIKE :search1 OR phone LIKE :search2)";
        $params = [
            ':search1' => "%$search%",
            ':search2' => "%$search%"
        ];

        if (!empty($status_filter)) {
            $query .= " AND status = :status";
            $params[':status'] = $status_filter;
        }

        $stmt = $this->conn->prepare($query);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val, PDO::PARAM_STR);
        }
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }
    // ==========================================
    // TỔNG QUAN KHO (DASHBOARD)
    // ==========================================

    // 1. Lấy số liệu cho 4 thẻ thống kê KPI
    public function getDashboardKPIs() {
        $kpis = [
            'total_ingredients' => 0,
            'low_stock' => 0,
            'out_of_stock' => 0,
            'today_receipts' => 0
        ];

        // Đếm tổng số nguyên vật liệu
        $stmt = $this->conn->query("SELECT COUNT(*) as count FROM ingredients");
        $kpis['total_ingredients'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        // Đếm NVL sắp hết (tồn kho nhỏ hơn mức min, nhưng vẫn lớn hơn 0)
        $stmt = $this->conn->query("SELECT COUNT(*) as count FROM ingredients WHERE stock_quantity <= min_stock_level AND stock_quantity > 0");
        $kpis['low_stock'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        // Đếm NVL đã cạn kho (tồn kho <= 0)
        $stmt = $this->conn->query("SELECT COUNT(*) as count FROM ingredients WHERE stock_quantity <= 0");
        $kpis['out_of_stock'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        // Đếm tổng số Phiếu nhập/xuất được tạo trong ngày hôm nay
        $stmt = $this->conn->query("SELECT COUNT(*) as count FROM stock_receipts WHERE DATE(created_at) = CURDATE()");
        $kpis['today_receipts'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        return $kpis;
    }

    // 2. Lấy danh sách NVL cần cảnh báo (Tồn kho <= Min)
    public function getLowStockAlerts($limit = 5) {
        $query = "SELECT ingredient_code, name, stock_quantity, min_stock_level 
                  FROM ingredients 
                  WHERE stock_quantity <= min_stock_level 
                  ORDER BY stock_quantity ASC LIMIT :limit";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 3. Lấy lịch sử giao dịch gần nhất
    public function getRecentActivities($limit = 5) {
        // Lấy 5 phiếu nhập/xuất mới nhất
        $query = "SELECT receipt_code, receipt_type, created_at, user_id 
                  FROM stock_receipts 
                  ORDER BY created_at DESC LIMIT :limit";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ==========================================
    // 4. XỬ LÝ LƯU PHIẾU NHẬP KHO (IMPORT STOCK)
    // ==========================================

    public function createImportReceipt($receiptData, $detailsData) {
        try {
            // Bắt đầu Giao dịch (Transaction) - Đảm bảo an toàn dữ liệu tuyệt đối
            $this->conn->beginTransaction();

            // 1. Thêm mới dữ liệu vào bảng stock_receipts (Phiếu chung)
            $sqlReceipt = "INSERT INTO stock_receipts (receipt_code, supplier_id, user_id, receipt_type, total_amount, note, created_at) 
                           VALUES (:receipt_code, :supplier_id, :user_id, 'Import', :total_amount, :note, :created_at)";
            $stmtReceipt = $this->conn->prepare($sqlReceipt);
            $stmtReceipt->execute([
                ':receipt_code' => $receiptData['receipt_code'],
                ':supplier_id'  => $receiptData['supplier_id'],
                ':user_id'      => $receiptData['user_id'],
                ':total_amount' => $receiptData['total_amount'],
                ':note'         => $receiptData['note'],
                ':created_at'   => $receiptData['created_at']
            ]);

            // Lấy ID của phiếu vừa được tạo
            $receiptId = $this->conn->lastInsertId();

            // Cấu hình lệnh Thêm chi tiết và Cập nhật kho
            $sqlDetail = "INSERT INTO stock_receipt_details (stock_receipt_id, ingredient_id, quantity, unit_cost) 
                          VALUES (:stock_receipt_id, :ingredient_id, :quantity, :unit_cost)";
            $stmtDetail = $this->conn->prepare($sqlDetail);

            // Cộng số lượng tồn kho mới và Cập nhật lại giá vốn (nếu cần)
            $sqlUpdateStock = "UPDATE ingredients 
                               SET stock_quantity = stock_quantity + :quantity, cost_price = :unit_cost 
                               WHERE id = :ingredient_id";
            $stmtUpdateStock = $this->conn->prepare($sqlUpdateStock);

            // 2. Chạy vòng lặp để lưu từng dòng hàng hóa
            foreach ($detailsData as $detail) {
                // Lưu vào chi tiết phiếu
                $stmtDetail->execute([
                    ':stock_receipt_id' => $receiptId,
                    ':ingredient_id'    => $detail['ingredient_id'],
                    ':quantity'         => $detail['quantity'],
                    ':unit_cost'        => $detail['unit_cost']
                ]);

                // Cập nhật tồn kho ở bảng Nguyên liệu
                $stmtUpdateStock->execute([
                    ':quantity'      => $detail['quantity'],
                    ':unit_cost'     => $detail['unit_cost'],
                    ':ingredient_id' => $detail['ingredient_id']
                ]);
            }

            // Mọi thứ thành công, chốt Giao dịch
            $this->conn->commit();
            return true;

        } catch (PDOException $e) {
            // Có lỗi xảy ra, hoàn tác lại toàn bộ để kho không bị lệch số
            $this->conn->rollBack();
            error_log("Lỗi Lưu Phiếu Nhập: " . $e->getMessage());
            return false;
        }
    }
    // ==========================================
    // 5. LẤY CHI TIẾT PHIẾU NHẬP/XUẤT (XEM CHỨNG TỪ)
    // ==========================================

    public function getReceiptByCode($code) {$query = "SELECT sr.*, u.full_name as creator_name, s.name as supplier_name, s.phone as supplier_phone, s.address as supplier_address 
                  FROM stock_receipts sr 
                  LEFT JOIN users u ON sr.user_id = u.id 
                  LEFT JOIN suppliers s ON sr.supplier_id = s.id 
                  WHERE sr.receipt_code = :code";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':code' =>$code]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getReceiptDetails($receiptId) {$query = "SELECT srd.*, i.ingredient_code, i.name as ingredient_name, i.unit 
                  FROM stock_receipt_details srd 
                  JOIN ingredients i ON srd.ingredient_id = i.id 
                  WHERE srd.stock_receipt_id = :receipt_id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':receipt_id' =>$receiptId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>