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
        // ĐÃ SỬA DÒNG NÀY: Nối bảng suppliers để lấy tên nhà cung cấp (supplier_name)
        $query = "SELECT ingredients.*, suppliers.name AS supplier_name 
                  FROM ingredients 
                  LEFT JOIN suppliers ON ingredients.supplier_id = suppliers.id 
                  WHERE (ingredients.name LIKE :search1 OR ingredients.ingredient_code LIKE :search2)";
        
        $params = [
            ':search1' => "%$search%",
            ':search2' => "%$search%"
        ];

        if (!empty($group_filter)) {
            $query .= " AND ingredients.group_name = :group";
            $params[':group'] = $group_filter;
        }

        if (!empty($unit_filter)) {
            $query .= " AND ingredients.unit = :unit";
            $params[':unit'] = $unit_filter;
        }

        if ($status_filter === 'low') {
            // Tồn thấp là nhỏ hơn hoặc bằng mức tối thiểu nhưng phải lớn hơn 0
            $query .= " AND ingredients.stock_quantity <= ingredients.min_stock_level AND ingredients.stock_quantity > 0";
        } elseif ($status_filter === 'safe') {
            // An toàn
            $query .= " AND ingredients.stock_quantity > ingredients.min_stock_level";
        } elseif ($status_filter === 'out_of_stock') {
        // Đã hết hàng: Số lượng tồn bằng 0 hoặc âm
        $query .= " AND ingredients.stock_quantity <= 0";
        }

        // Tách lấy phần số phía sau chữ 'NVL' và ép kiểu sang số nguyên để sắp xếp chuẩn xác (1, 2, 3... 10, 11)
        $query .= " ORDER BY CAST(SUBSTRING(ingredients.ingredient_code, 4) AS UNSIGNED) ASC LIMIT :limit OFFSET :offset";
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
            // Tồn thấp là nhỏ hơn hoặc bằng mức tối thiểu nhưng phải lớn hơn 0
            $query .= " AND ingredients.stock_quantity <= ingredients.min_stock_level AND ingredients.stock_quantity > 0";
        } elseif ($status_filter === 'safe') {
            // An toàn
            $query .= " AND ingredients.stock_quantity > ingredients.min_stock_level";
        } elseif ($status_filter === 'out_of_stock') {
        // Đã hết hàng: Số lượng tồn bằng 0 hoặc âm
        $query .= " AND ingredients.stock_quantity <= 0";
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

    // Lấy 1 NCC theo ID để đổ vào Form Sửa
    public function getSupplierById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM suppliers WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Thêm Nhà cung cấp mới
    public function insertSupplier($data) {
        $sql = "INSERT INTO suppliers (name, contact_person, phone, address, debt_balance, status) 
                VALUES (:name, :contact_person, :phone, :address, :debt_balance, :status)";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute($data);
    }

    // Cập nhật thông tin Nhà cung cấp
    public function updateSupplier($id, $data) {
        $sql = "UPDATE suppliers 
                SET name = :name, contact_person = :contact_person, phone = :phone, 
                    address = :address, debt_balance = :debt_balance, status = :status 
                WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $data['id'] = $id;
        return $stmt->execute($data);
    }

    // Xóa nhà cung cấp (Bắt lỗi Foreign Key nếu đã có giao dịch)
    public function deleteSupplier($id) {
        try {
            $stmt = $this->conn->prepare("DELETE FROM suppliers WHERE id = :id");
            return $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            // Lỗi 23000 là lỗi vi phạm ràng buộc khóa ngoại (Constraint Violation)
            // Xảy ra khi Nhà cung cấp này đã có Phiếu nhập kho (stock_receipts)
            if ($e->getCode() == '23000') { 
                return false; 
            }
            throw $e;
        }
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

    // Đếm tổng số giao dịch để tính số trang
    public function countRecentActivities() {
        $stmt = $this->conn->query("SELECT COUNT(*) as total FROM stock_receipts");
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }

    // 3. Lấy lịch sử giao dịch gần nhất (Có hỗ trợ phân trang offset)
    public function getRecentActivities($limit = 5, $offset = 0) {
        $query = "SELECT receipt_code, receipt_type, created_at, user_id 
                  FROM stock_receipts 
                  ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
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
    public function getReceiptByCode($code) {
        $query = "SELECT sr.*, u.full_name as creator_name, s.name as supplier_name, s.phone as supplier_phone, s.address as supplier_address 
                  FROM stock_receipts sr 
                  LEFT JOIN users u ON sr.user_id = u.id 
                  LEFT JOIN suppliers s ON sr.supplier_id = s.id 
                  WHERE sr.receipt_code = :code";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':code' =>$code]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // ĐÂY LÀ HÀM LẤY CHI TIẾT DUY NHẤT (Đã xóa hàm trùng lặp ở cuối file)
    public function getReceiptDetails($receiptId) {
        $query = "SELECT srd.*, i.ingredient_code, i.name as ingredient_name, i.unit 
                  FROM stock_receipt_details srd 
                  JOIN ingredients i ON srd.ingredient_id = i.id 
                  WHERE srd.stock_receipt_id = :receipt_id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':receipt_id' =>$receiptId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ==========================================
    // 6. THAO TÁC CẬP NHẬT & XÓA NGUYÊN VẬT LIỆU
    // ==========================================
    // Lấy 1 nguyên liệu theo ID để đổ vào Form Sửa
    public function getIngredientById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM ingredients WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Cập nhật thông tin (Không cho sửa mã NVL và Số lượng tồn - Số lượng phải nhập qua phiếu)
    public function updateIngredient($id, $data) {
        $sql = "UPDATE ingredients 
                SET name = :name, group_name = :group_name, unit = :unit, 
                    min_stock_level = :min_stock_level, cost_price = :cost_price, supplier_id = :supplier_id 
                WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $data['id'] = $id;
        return $stmt->execute($data);
    }

    // Xóa nguyên liệu (Bắt lỗi Foreign Key)
    public function deleteIngredient($id) {
        try {
            $stmt = $this->conn->prepare("DELETE FROM ingredients WHERE id = :id");
            return $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            // Lỗi 23000 là lỗi vi phạm ràng buộc khóa ngoại (Constraint Violation)
            if ($e->getCode() == '23000') { 
                return false; 
            }
            throw $e;
        }
    }

    // Thêm nguyên vật liệu mới
    public function insertIngredient($data) {
        $sql = "INSERT INTO ingredients (ingredient_code, name, group_name, unit, min_stock_level, cost_price, supplier_id, stock_quantity) 
                VALUES (:ingredient_code, :name, :group_name, :unit, :min_stock_level, :cost_price, :supplier_id, 0)";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute($data);
    }

    // Hàm tự động sinh mã NVL tiếp theo (VD: Đã có NVL15 thì sinh NVL16)
    public function generateNextIngredientCode() {
        // Tìm số lớn nhất phía sau chữ 'NVL'
        $stmt = $this->conn->prepare("SELECT MAX(CAST(SUBSTRING(ingredient_code, 4) AS UNSIGNED)) as max_id FROM ingredients WHERE ingredient_code LIKE 'NVL%'");
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $nextId = ($row['max_id'] ? (int)$row['max_id'] : 0) + 1;
        
        // Đệm thêm số 0 đằng trước cho đồng bộ (VD: NVL01, NVL02... NVL16)
        return 'NVL' . str_pad($nextId, 2, '0', STR_PAD_LEFT);
    }

    // ==========================================
    // 7. LỊCH SỬ KHO (TRANG HISTORY)
    // ==========================================
    // 1. Đếm tổng số phiếu (Đã fix lỗi Invalid parameter number)
    // 1. Đếm tổng số phiếu (Có hỗ trợ lọc theo ngày)
    public function countReceiptHistory($search = '', $type = '', $date_filter = '') {
        $sql = "SELECT COUNT(*) as total 
                FROM stock_receipts r 
                LEFT JOIN users u ON r.user_id = u.id 
                WHERE 1=1";
                
        if (!empty($search)) { 
            $sql .= " AND (r.receipt_code LIKE :search_code OR u.full_name LIKE :search_name)"; 
        }
        if (!empty($type)) { 
            $sql .= " AND r.receipt_type = :type"; 
        }
        // NẾU CÓ LỌC HÔM NAY
        if ($date_filter === 'today') {
            $sql .= " AND DATE(r.created_at) = CURDATE()";
        }
        
        $stmt = $this->conn->prepare($sql);
        
        if (!empty($search)) { 
            $stmt->bindValue(':search_code', "%$search%"); 
            $stmt->bindValue(':search_name', "%$search%"); 
        }
        if (!empty($type)) { 
            $stmt->bindValue(':type', $type); 
        }
        
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }

    // 2. Lấy danh sách lịch sử phiếu (Có hỗ trợ lọc theo ngày)
    public function getReceiptHistory($search = '', $type = '', $limit = 10, $offset = 0, $date_filter = '') {
        $sql = "SELECT r.*, u.full_name AS creator_name 
                FROM stock_receipts r 
                LEFT JOIN users u ON r.user_id = u.id 
                WHERE 1=1";
                
        if (!empty($search)) { 
            $sql .= " AND (r.receipt_code LIKE :search_code OR u.full_name LIKE :search_name)"; 
        }
        if (!empty($type)) { 
            $sql .= " AND r.receipt_type = :type"; 
        }
        // NẾU CÓ LỌC HÔM NAY
        if ($date_filter === 'today') {
            $sql .= " AND DATE(r.created_at) = CURDATE()";
        }
        
        $sql .= " ORDER BY r.created_at DESC LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($sql);
        
        if (!empty($search)) { 
            $stmt->bindValue(':search_code', "%$search%");
            $stmt->bindValue(':search_name', "%$search%");
        }
        if (!empty($type)) { 
            $stmt->bindValue(':type', $type); 
        }
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    // 3. Lấy thông tin chung của phiếu nhập/xuất
    public function getReceiptById($id) {
        $sql = "SELECT r.*, u.full_name AS creator_name, s.name AS supplier_name 
                FROM stock_receipts r 
                LEFT JOIN users u ON r.user_id = u.id 
                LEFT JOIN suppliers s ON r.supplier_id = s.id 
                WHERE r.id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    // ==========================================
    // XỬ LÝ LƯU PHIẾU XUẤT KHO (EXPORT STOCK)
    // ==========================================
    public function createExportReceipt($receiptData, $detailsData) {
        try {
            // Bắt đầu Giao dịch (Transaction)
            $this->conn->beginTransaction();
            
            // 1. Thêm mới dữ liệu vào bảng stock_receipts (Phiếu chung)
            // Lưu ý: Phiếu xuất không có supplier_id, để NULL
            $sqlReceipt = "INSERT INTO stock_receipts (receipt_code, supplier_id, user_id, receipt_type, total_amount, note, created_at) 
                           VALUES (:receipt_code, NULL, :user_id, 'Export', :total_amount, :note, :created_at)";
            $stmtReceipt = $this->conn->prepare($sqlReceipt);
            $stmtReceipt->execute([
                ':receipt_code' => $receiptData['receipt_code'],
                ':user_id'      => $receiptData['user_id'],
                ':total_amount' => $receiptData['total_amount'],
                ':note'         => $receiptData['note'],
                ':created_at'   => $receiptData['created_at']
            ]);
            
            $receiptId = $this->conn->lastInsertId();
            
            // 2. Cấu hình lệnh Thêm chi tiết và TRỪ kho
            $sqlDetail = "INSERT INTO stock_receipt_details (stock_receipt_id, ingredient_id, quantity, unit_cost) 
                          VALUES (:stock_receipt_id, :ingredient_id, :quantity, :unit_cost)";
            $stmtDetail = $this->conn->prepare($sqlDetail);
            
            // TRỪ số lượng tồn kho (Chỉ trừ khi số lượng tồn >= số lượng xuất)
            // Sử dụng 2 tham số riêng biệt :qty_sub và :qty_check để tránh lỗi trùng lặp tham số trong PDO
            $sqlUpdateStock = "UPDATE ingredients 
                               SET stock_quantity = stock_quantity - :qty_sub 
                               WHERE id = :ingredient_id AND stock_quantity >= :qty_check";
            $stmtUpdateStock = $this->conn->prepare($sqlUpdateStock);
            
            // 3. Chạy vòng lặp để lưu và trừ kho từng dòng
            foreach ($detailsData as $detail) {
                // Thực hiện trừ kho trước
                $stmtUpdateStock->execute([
                    ':qty_sub'       => $detail['quantity'],
                    ':qty_check'     => $detail['quantity'],
                    ':ingredient_id' => $detail['ingredient_id']
                ]);

                // Kiểm tra xem lệnh Update có thành công không (nếu = 0 tức là kho không đủ số lượng để trừ)
                if ($stmtUpdateStock->rowCount() == 0) {
                    throw new Exception("Lỗi: Nguyên liệu ID " . $detail['ingredient_id'] . " không đủ số lượng trong kho để xuất!");
                }

                // Nếu trừ kho thành công, lưu vào chi tiết chứng từ
                $stmtDetail->execute([
                    ':stock_receipt_id' => $receiptId,
                    ':ingredient_id'    => $detail['ingredient_id'],
                    ':quantity'         => $detail['quantity'],
                    ':unit_cost'        => $detail['unit_cost'] // Lấy giá vốn hiện tại để tính giá trị xuất
                ]);
            }
            
            // Mọi thứ thành công, chốt Giao dịch
            $this->conn->commit();
            return true;
            
        } catch (Exception $e) {
            // Có lỗi (thiếu kho, sai data...), hoàn tác lại toàn bộ
            $this->conn->rollBack();
            error_log("Lỗi Lưu Phiếu Xuất: " . $e->getMessage());
            return false;
        }
    }

}
?>