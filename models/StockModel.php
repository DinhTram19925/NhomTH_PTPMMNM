<?php
require_once __DIR__ . '/../config/Database.php';

class StockModel {
    private $conn;

    public function __construct() {
        $this->conn = Database::getConnection();
    }

    public function getIngredients($search = '', $group_filter = '', $unit_filter = '', $status_filter = '', $limit = 10, $offset = 0) {
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
            $query .= " AND ingredients.stock_quantity <= ingredients.min_stock_level AND ingredients.stock_quantity > 0";
        } elseif ($status_filter === 'safe') {
            $query .= " AND ingredients.stock_quantity > ingredients.min_stock_level";
        } elseif ($status_filter === 'out_of_stock') {
        $query .= " AND ingredients.stock_quantity <= 0";
        }

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
            $query .= " AND ingredients.stock_quantity <= ingredients.min_stock_level AND ingredients.stock_quantity > 0";
        } elseif ($status_filter === 'safe') {
            $query .= " AND ingredients.stock_quantity > ingredients.min_stock_level";
        } elseif ($status_filter === 'out_of_stock') {
        $query .= " AND ingredients.stock_quantity <= 0";
        }

        $stmt = $this->conn->prepare($query);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val, PDO::PARAM_STR);
        }
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }

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

    public function getSupplierById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM suppliers WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function insertSupplier($data) {
        $sql = "INSERT INTO suppliers (name, contact_person, phone, address, debt_balance, status) 
                VALUES (:name, :contact_person, :phone, :address, :debt_balance, :status)";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute($data);
    }

    public function updateSupplier($id, $data) {
        $sql = "UPDATE suppliers 
                SET name = :name, contact_person = :contact_person, phone = :phone, 
                    address = :address, debt_balance = :debt_balance, status = :status 
                WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $data['id'] = $id;
        return $stmt->execute($data);
    }

    public function deleteSupplier($id) {
        try {
            $stmt = $this->conn->prepare("DELETE FROM suppliers WHERE id = :id");
            return $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {

            if ($e->getCode() == '23000') { 
                return false; 
            }
            throw $e;
        }
    }

    public function getDashboardKPIs() {
        $kpis = [
            'total_ingredients' => 0,
            'low_stock' => 0,
            'out_of_stock' => 0,
            'today_receipts' => 0
        ];
        
        $stmt = $this->conn->query("SELECT COUNT(*) as count FROM ingredients");
        $kpis['total_ingredients'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        $stmt = $this->conn->query("SELECT COUNT(*) as count FROM ingredients WHERE stock_quantity <= min_stock_level AND stock_quantity > 0");
        $kpis['low_stock'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        $stmt = $this->conn->query("SELECT COUNT(*) as count FROM ingredients WHERE stock_quantity <= 0");
        $kpis['out_of_stock'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        $stmt = $this->conn->query("SELECT COUNT(*) as count FROM stock_receipts WHERE DATE(created_at) = CURDATE()");
        $kpis['today_receipts'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        return $kpis;
    }

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

    public function countRecentActivities() {
        $stmt = $this->conn->query("SELECT COUNT(*) as total FROM stock_receipts");
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }

    public function getRecentActivities($limit = 5, $offset = 0) {
        $query = "SELECT id, receipt_code, receipt_type, created_at, user_id 
                  FROM stock_receipts 
                  ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createImportReceipt($receiptData, $detailsData) {
        try {
            $this->conn->beginTransaction();
            
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
            
            $receiptId = $this->conn->lastInsertId();
            
            $sqlDetail = "INSERT INTO stock_receipt_details (stock_receipt_id, ingredient_id, quantity, unit_cost) 
                          VALUES (:stock_receipt_id, :ingredient_id, :quantity, :unit_cost)";
            $stmtDetail = $this->conn->prepare($sqlDetail);
            
            $sqlUpdateStock = "UPDATE ingredients 
                               SET stock_quantity = stock_quantity + :quantity, cost_price = :unit_cost 
                               WHERE id = :ingredient_id";
            $stmtUpdateStock = $this->conn->prepare($sqlUpdateStock);
            
            foreach ($detailsData as $detail) {
                $stmtDetail->execute([
                    ':stock_receipt_id' => $receiptId,
                    ':ingredient_id'    => $detail['ingredient_id'],
                    ':quantity'         => $detail['quantity'],
                    ':unit_cost'        => $detail['unit_cost']
                ]);
                
                $stmtUpdateStock->execute([
                    ':quantity'      => $detail['quantity'],
                    ':unit_cost'     => $detail['unit_cost'],
                    ':ingredient_id' => $detail['ingredient_id']
                ]);
            }
            
            $this->conn->commit();
            return true;
            
        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log("Lỗi Lưu Phiếu Nhập: " . $e->getMessage());
            return false;
        }
    }

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
    
    public function getReceiptDetails($receiptId) {
        $query = "SELECT srd.*, i.ingredient_code, i.name as ingredient_name, i.unit 
                  FROM stock_receipt_details srd 
                  JOIN ingredients i ON srd.ingredient_id = i.id 
                  WHERE srd.stock_receipt_id = :receipt_id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':receipt_id' =>$receiptId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getIngredientById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM ingredients WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateIngredient($id, $data) {
        $sql = "UPDATE ingredients 
                SET name = :name, group_name = :group_name, unit = :unit, 
                    min_stock_level = :min_stock_level, cost_price = :cost_price, supplier_id = :supplier_id 
                WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $data['id'] = $id;
        return $stmt->execute($data);
    }

    public function deleteIngredient($id) {
        try {
            $stmt = $this->conn->prepare("DELETE FROM ingredients WHERE id = :id");
            return $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            if ($e->getCode() == '23000') { 
                return false; 
            }
            throw $e;
        }
    }

    public function insertIngredient($data) {
        $sql = "INSERT INTO ingredients (ingredient_code, name, group_name, unit, min_stock_level, cost_price, supplier_id, stock_quantity) 
                VALUES (:ingredient_code, :name, :group_name, :unit, :min_stock_level, :cost_price, :supplier_id, 0)";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute($data);
    }

    public function generateNextIngredientCode() {
        $stmt = $this->conn->prepare("SELECT MAX(CAST(SUBSTRING(ingredient_code, 4) AS UNSIGNED)) as max_id FROM ingredients WHERE ingredient_code LIKE 'NVL%'");
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $nextId = ($row['max_id'] ? (int)$row['max_id'] : 0) + 1;
        
        return 'NVL' . str_pad($nextId, 2, '0', STR_PAD_LEFT);
    }

    public function countReceiptHistory($search = '', $type = '', $date_filter = '') {
        $sql = "SELECT COUNT(*) as total 
                FROM stock_receipts r 
                LEFT JOIN users u ON r.user_id = u.id 
                WHERE 1=1";
                
        if (!empty($search)) { 
            $sql .= " AND (r.receipt_code LIKE :search_code OR u.full_name LIKE :search_name)"; 
        }
        
        if (!empty($type)) {
            if ($type === 'Import') {
                $sql .= " AND r.receipt_type = 'Import'";
            } elseif ($type === 'Export' || strtolower($type) === 'export') {
                $sql .= " AND r.receipt_type IN ('Export_Sale', 'Export_Damage')";
            } elseif ($type === 'Audit_Adjust') {
                $sql .= " AND r.receipt_type = 'Audit_Adjust'";
            } else {
                $sql .= " AND r.receipt_type = :type";
            }
        }

        if ($date_filter === 'today') {
            $sql .= " AND DATE(r.created_at) = CURDATE()";
        }
        
        $stmt = $this->conn->prepare($sql);
        
        if (!empty($search)) { 
            $stmt->bindValue(':search_code', "%$search%"); 
            $stmt->bindValue(':search_name', "%$search%"); 
        }
        
        if (!empty($type) && $type !== 'Import' && $type !== 'Export' && strtolower($type) !== 'export' && $type !== 'Audit_Adjust') { 
            $stmt->bindValue(':type', $type); 
        }
        
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }

    public function getReceiptHistory($search = '', $type = '', $limit = 10, $offset = 0, $date_filter = '') {
        $sql = "SELECT r.*, u.full_name AS creator_name 
                FROM stock_receipts r 
                LEFT JOIN users u ON r.user_id = u.id 
                WHERE 1=1";
                
        if (!empty($search)) { 
            $sql .= " AND (r.receipt_code LIKE :search_code OR u.full_name LIKE :search_name)"; 
        }
        
        if (!empty($type)) {
            if ($type === 'Import') {
                $sql .= " AND r.receipt_type = 'Import'";
            } elseif ($type === 'Export' || strtolower($type) === 'export') {
                $sql .= " AND r.receipt_type IN ('Export_Sale', 'Export_Damage')";
            } elseif ($type === 'Audit_Adjust') {
                $sql .= " AND r.receipt_type = 'Audit_Adjust'";
            } else {
                $sql .= " AND r.receipt_type = :type";
            }
        }

        if ($date_filter === 'today') {
            $sql .= " AND DATE(r.created_at) = CURDATE()";
        }
        
        $sql .= " ORDER BY r.created_at DESC LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($sql);
        
        if (!empty($search)) { 
            $stmt->bindValue(':search_code', "%$search%");
            $stmt->bindValue(':search_name', "%$search%");
        }
        
        if (!empty($type) && $type !== 'Import' && $type !== 'Export' && strtolower($type) !== 'export' && $type !== 'Audit_Adjust') { 
            $stmt->bindValue(':type', $type); 
        }

        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getReceiptById($id) {
        $sql = "SELECT r.*, u.full_name AS creator_name, 
                       s.name AS supplier_name, 
                       s.phone AS supplier_phone, 
                       s.address AS supplier_address 
                FROM stock_receipts r 
                LEFT JOIN users u ON r.user_id = u.id 
                LEFT JOIN suppliers s ON r.supplier_id = s.id 
                WHERE r.id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function createExportReceipt($receiptData, $detailsData) {
    try {
        $this->conn->beginTransaction();
        
        $sqlReceipt = "INSERT INTO stock_receipts (receipt_code, supplier_id, user_id, receipt_type, total_amount, note, created_at) 
               VALUES (:receipt_code, NULL, :user_id, :receipt_type, :total_amount, :note, :created_at)";
        $stmtReceipt = $this->conn->prepare($sqlReceipt);
        $stmtReceipt->execute([
            ':receipt_code' => $receiptData['receipt_code'],
            ':user_id'      => $receiptData['user_id'],
            ':receipt_type' => $receiptData['receipt_type'] ?? 'Export_Sale', // Nhận diện đúng giá trị ENUM ('Export_Sale' hoặc 'Export_Damage')
            ':total_amount' => $receiptData['total_amount'],
            ':note'         => $receiptData['note'],
            ':created_at'   => $receiptData['created_at']
        ]);
        
        $receiptId = $this->conn->lastInsertId();
        
        $sqlDetail = "INSERT INTO stock_receipt_details (stock_receipt_id, ingredient_id, quantity, unit_cost) 
                      VALUES (:stock_receipt_id, :ingredient_id, :quantity, :unit_cost)";
        $stmtDetail = $this->conn->prepare($sqlDetail);
        
        $sqlUpdateStock = "UPDATE ingredients 
                           SET stock_quantity = stock_quantity - :qty_sub 
                           WHERE id = :ingredient_id AND stock_quantity >= :qty_check";
        $stmtUpdateStock = $this->conn->prepare($sqlUpdateStock);
        
        foreach ($detailsData as $detail) {
            $stmtUpdateStock->execute([
                ':qty_sub'       => $detail['quantity'],
                ':qty_check'     => $detail['quantity'],
                ':ingredient_id' => $detail['ingredient_id']
            ]);

            if ($stmtUpdateStock->rowCount() == 0) {
                throw new Exception("Lỗi: Nguyên liệu ID " . $detail['ingredient_id'] . " không đủ số lượng trong kho để xuất!");
            }

            $stmtDetail->execute([
                ':stock_receipt_id' => $receiptId,
                ':ingredient_id'    => $detail['ingredient_id'],
                ':quantity'         => $detail['quantity'],
                ':unit_cost'        => $detail['unit_cost'] // Lấy giá vốn hiện tại để tính giá trị xuất
            ]);
        }
        
        $this->conn->commit();
        return true;
        
    } catch (Exception $e) {
        $this->conn->rollBack();
        error_log("Lỗi Lưu Phiếu Xuất: " . $e->getMessage());
        return false;
    }
}

    public function createAuditReceipt($receiptData, $auditDetails) {
        try {
            $this->conn->beginTransaction();

            $sqlReceipt = "INSERT INTO stock_receipts (receipt_code, supplier_id, user_id, receipt_type, total_amount, note, created_at) 
                           VALUES (:receipt_code, NULL, :user_id, 'Audit_Adjust', 0, :note, :created_at)";
            $stmtReceipt = $this->conn->prepare($sqlReceipt);
            $stmtReceipt->execute([
                ':receipt_code' => $receiptData['receipt_code'],
                ':user_id'      => $receiptData['user_id'],
                ':note'         => $receiptData['note'],
                ':created_at'   => $receiptData['created_at']
            ]);
            $receiptId = $this->conn->lastInsertId();

            $sqlUpdateStock = "UPDATE ingredients SET stock_quantity = :actual_qty WHERE id = :ingredient_id";
            $stmtUpdateStock = $this->conn->prepare($sqlUpdateStock);

            $sqlDetail = "INSERT INTO stock_receipt_details (stock_receipt_id, ingredient_id, quantity, unit_cost, reason) 
                          VALUES (:stock_receipt_id, :ingredient_id, :quantity, :unit_cost, :reason)";
            $stmtDetail = $this->conn->prepare($sqlDetail);

            foreach ($auditDetails as $detail) {
                $stmtUpdateStock->execute([
                    ':actual_qty'    => $detail['actual_qty'],
                    ':ingredient_id' => $detail['ingredient_id']
                ]);

                $stmtDetail->execute([
                    ':stock_receipt_id' => $receiptId,
                    ':ingredient_id'    => $detail['ingredient_id'],
                    ':quantity'         => $detail['difference'], 
                    ':unit_cost'        => $detail['unit_cost'],
                    ':reason'           => $detail['reason'] ?? '' 
                ]);
            }

            $this->conn->commit();
            return $receiptId; 
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Lỗi Lưu Phiếu Kiểm Kê: " . $e->getMessage());
            return false;
        }
    }

    public function deductStockByOrder($order_id, $user_id = 2) {
        $dbConn = $this->conn ?? Database::getConnection(); // Sử dụng chung kết nối
        
        try {
            $dbConn->beginTransaction();

            $stmtOrder = $dbConn->prepare("SELECT product_id, quantity FROM order_details WHERE order_id = :order_id");
            $stmtOrder->execute([':order_id' => $order_id]);
            $orderDetails = $stmtOrder->fetchAll(PDO::FETCH_ASSOC);

            if (empty($orderDetails)) {
                $dbConn->rollBack();
                return ['success' => false, 'message' => 'Đơn hàng này trống hoặc không tồn tại.'];
            }

            $ingredientsToDeduct = [];

            $stmtBom = $dbConn->prepare("SELECT ingredient_id, quantity_required FROM recipes_bom WHERE product_id = :product_id");
            
            foreach ($orderDetails as $item) {
                $stmtBom->execute([':product_id' => $item['product_id']]);
                $boms = $stmtBom->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($boms as $bom) {
                    $ing_id = $bom['ingredient_id'];
                    $total_req = $bom['quantity_required'] * $item['quantity'];
                    
                    if (isset($ingredientsToDeduct[$ing_id])) {
                        $ingredientsToDeduct[$ing_id] += $total_req;
                    } else {
                        $ingredientsToDeduct[$ing_id] = $total_req;
                    }
                }
            }

            if (empty($ingredientsToDeduct)) {
                $dbConn->rollBack();
                return ['success' => true, 'message' => 'Các món trong đơn không cần trừ định lượng nguyên liệu.'];
            }

            $receipt_code = 'VX' . date('ymdHis') . rand(10, 99);
            $note = "Xuất kho tự động cho hóa đơn bán hàng #" . $order_id;
            
            $stmtReceipt = $dbConn->prepare("INSERT INTO stock_receipts (receipt_code, user_id, receipt_type, total_amount, note, created_at) 
                                             VALUES (:code, :user_id, 'Export_Sale', 0, :note, NOW())");
            $stmtReceipt->execute([
                ':code'    => $receipt_code,
                ':user_id' => $user_id,
                ':note'    => $note
            ]);
            $receipt_id = $dbConn->lastInsertId();

            $stmtInsertDetail = $dbConn->prepare("INSERT INTO stock_receipt_details (stock_receipt_id, ingredient_id, quantity) VALUES (:receipt_id, :ingredient_id, :quantity)");
            $stmtUpdateStock = $dbConn->prepare("UPDATE ingredients SET stock_quantity = stock_quantity - :deduct_qty WHERE id = :ingredient_id");
            $stmtCheckAlert = $dbConn->prepare("SELECT name, stock_quantity, min_stock_level, unit FROM ingredients WHERE id = :ingredient_id");

            $warnings = []; 

            foreach ($ingredientsToDeduct as $ing_id => $deduct_qty) {
                $stmtInsertDetail->execute([
                    ':receipt_id'    => $receipt_id,
                    ':ingredient_id' => $ing_id,
                    ':quantity'      => $deduct_qty
                ]);

                $stmtUpdateStock->execute([
                    ':deduct_qty'    => $deduct_qty,
                    ':ingredient_id' => $ing_id
                ]);

                $stmtCheckAlert->execute([':ingredient_id' => $ing_id]);
                $ingData = $stmtCheckAlert->fetch(PDO::FETCH_ASSOC);
                
                if ($ingData && $ingData['stock_quantity'] <= $ingData['min_stock_level']) {
                    $warnings[] = "⚠ Nguyên liệu [{$ingData['name']}] sắp hết! (Chỉ còn: {$ingData['stock_quantity']} {$ingData['unit']})";
                }
            }

            $dbConn->commit();
            return [
                'success'      => true, 
                'receipt_code' => $receipt_code,
                'warnings'     => $warnings
            ];

        } catch (Exception $e) {
            $dbConn->rollBack();
            return ['success' => false, 'message' => 'Lỗi hệ thống khi trừ kho: ' . $e->getMessage()];
        }
    }
    public function getInventoryAndProfitReport($startDate, $endDate) {
        $dbConn = $this->conn ?? Database::getConnection();

        $start = !empty($startDate) ? date('Y-m-d H:i:s', strtotime($startDate)) : '2020-01-01 00:00:00';
        $end = !empty($endDate) ? date('Y-m-d H:i:s', strtotime($endDate)) : '2030-12-31 23:59:59';

        $sqlXNT = "SELECT 
                    i.id, i.name, i.unit, i.cost_price,
                    COALESCE(SUM(CASE WHEN sr.receipt_type = 'Import' AND sr.created_at BETWEEN :start1 AND :end1 THEN srd.quantity ELSE 0 END), 0) AS total_imported,
                    COALESCE(SUM(CASE WHEN sr.receipt_type LIKE 'Export%' AND sr.created_at BETWEEN :start2 AND :end2 THEN srd.quantity ELSE 0 END), 0) AS total_exported
                   FROM ingredients i
                   LEFT JOIN stock_receipt_details srd ON i.id = srd.ingredient_id
                   LEFT JOIN stock_receipts sr ON srd.stock_receipt_id = sr.id
                   GROUP BY i.id, i.name, i.unit, i.cost_price";
        
        $stmt = $dbConn->prepare($sqlXNT);
        $stmt->execute([
            ':start1' => $start, 
            ':end1'   => $end,
            ':start2' => $start, 
            ':end2'   => $end
        ]);
        $reportData = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totalCostOfGoodsSold = 0;
        foreach ($reportData as $row) {
            $consumedCost = $row['total_exported'] * $row['cost_price'];
            $totalCostOfGoodsSold += $consumedCost;
        }

        $sqlRevenue = "SELECT SUM(total_cash_sales) as total_revenue FROM cash_shifts WHERE opened_at BETWEEN :start AND :end";
        $stmtRev = $dbConn->prepare($sqlRevenue);
        $stmtRev->execute([':start' => $start, ':end' => $end]);
        $revData = $stmtRev->fetch(PDO::FETCH_ASSOC);
        $totalRevenue = $revData['total_revenue'] ?? 0;

        $grossProfit = $totalRevenue - $totalCostOfGoodsSold;
        $foodCostPercentage = ($totalRevenue > 0) ? ($totalCostOfGoodsSold / $totalRevenue) * 100 : 0;

        return [
            'items' => $reportData,
            'summary' => [
                'total_revenue' => $totalRevenue,
                'total_cogs' => $totalCostOfGoodsSold,
                'gross_profit' => $grossProfit,
                'food_cost_percentage' => round($foodCostPercentage, 2)
            ]
        ];
    }
    public function deleteReceiptWithRevert($receiptId, $receiptType) {
        try {
            $this->conn->beginTransaction();

            $stmt = $this->conn->prepare("SELECT ingredient_id, quantity FROM stock_receipt_details WHERE stock_receipt_id = :id");
            $stmt->execute([':id' => $receiptId]);
            $details = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $sqlUpdateStock = "";
            if ($receiptType === 'Import') {
                $sqlUpdateStock = "UPDATE ingredients SET stock_quantity = stock_quantity - :qty WHERE id = :ing_id";
            } elseif (strpos($receiptType, 'Export') === 0) {
                $sqlUpdateStock = "UPDATE ingredients SET stock_quantity = stock_quantity + :qty WHERE id = :ing_id";
            }

            if (!empty($sqlUpdateStock)) {
                $stmtUpdate = $this->conn->prepare($sqlUpdateStock);
                foreach ($details as $detail) {
                    $stmtUpdate->execute([
                        ':qty' => $detail['quantity'],
                        ':ing_id' => $detail['ingredient_id']
                    ]);
                }
            }

            $stmtDelDetails = $this->conn->prepare("DELETE FROM stock_receipt_details WHERE stock_receipt_id = :id");
            $stmtDelDetails->execute([':id' => $receiptId]);

            $stmtDelReceipt = $this->conn->prepare("DELETE FROM stock_receipts WHERE id = :id");
            $stmtDelReceipt->execute([':id' => $receiptId]);

            $this->conn->commit();
            return true;

        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Lỗi Hủy Phiếu: " . $e->getMessage());
            return false;
        }
    }
}
?>