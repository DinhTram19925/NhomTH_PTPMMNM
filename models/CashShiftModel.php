<?php
require_once __DIR__ . '/../config/Database.php';

class CashShiftModel {
    private $conn;

    public function __construct() {
        $this->conn = Database::getConnection();
    }

    public function getConnection() {
        return $this->conn;
    }

    public function getCurrentOpenShift($cashier_id) {
        $stmt = $this->conn->prepare("SELECT * FROM cash_shifts WHERE cashier_id = :cashier_id AND status = 'Open' LIMIT 1");
        $stmt->execute([':cashier_id' => $cashier_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function openShift($cashier_id, $opening_cash, $note) {
        $sql = "INSERT INTO cash_shifts (cashier_id, opening_cash, total_cash_sales, total_cash_in, total_cash_out, note, status, opened_at) 
                VALUES (:cashier_id, :opening_cash, 0, 0, 0, :note, 'Open', NOW())";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ':cashier_id'   => $cashier_id,
            ':opening_cash' => $opening_cash,
            ':note'         => $note
        ]);
    }

    public function closeShift($shift_id, $actual_closing_cash, $note) {
        $stmt = $this->conn->prepare("SELECT opening_cash, total_cash_sales, total_cash_in, total_cash_out, note FROM cash_shifts WHERE id = :id");
        $stmt->execute([':id' => $shift_id]);
        $shift = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($shift) {
            $expected_closing_cash = $shift['opening_cash'] + $shift['total_cash_sales'] + $shift['total_cash_in'] - $shift['total_cash_out'];
            
            $discrepancy = $actual_closing_cash - $expected_closing_cash;
            
            $new_note = $shift['note'];
            if (!empty($note)) {
                $new_note = $shift['note'] ? $shift['note'] . " | [Đóng ca]: " . $note : "[Đóng ca]: " . $note;
            }

            $sql = "UPDATE cash_shifts 
                    SET expected_closing_cash = :expected, 
                        actual_closing_cash = :actual, 
                        discrepancy = :discrepancy, 
                        closed_at = NOW(), 
                        note = :note,
                        status = 'Closed' 
                    WHERE id = :id";
            
            $updateStmt = $this->conn->prepare($sql);
            return $updateStmt->execute([
                ':expected'    => $expected_closing_cash,
                ':actual'      => $actual_closing_cash,
                ':discrepancy' => $discrepancy,
                ':note'        => $new_note,
                ':id'          => $shift_id
            ]);
        }
        return false;
    }

    public function updateShiftTransaction($shift_id, $amount, $type = 'sales') {
        if ($type == 'sales') {
            $sql = "UPDATE cash_shifts SET total_cash_sales = total_cash_sales + :amount WHERE id = :id";
        } elseif ($type == 'in') {
            $sql = "UPDATE cash_shifts SET total_cash_in = total_cash_in + :amount WHERE id = :id";
        } else {
            $sql = "UPDATE cash_shifts SET total_cash_out = total_cash_out + :amount WHERE id = :id";
        }
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([':amount' => $amount, ':id' => $shift_id]);
    }
    public function recordSaleTransaction($cashier_id, $amount, $type = 'sales') {
        $openShift = $this->getCurrentOpenShift($cashier_id);
        if ($openShift) {
            return $this->updateShiftTransaction($openShift['id'], $amount, $type);
        }
        return false; 
    }
    public function createTransaction($cashier_id, $type, $amount, $note) {
        $shift = $this->getCurrentOpenShift($cashier_id);
        if (!$shift) {
            return false; 
        }

        $stmtUser = $this->conn->prepare("SELECT full_name FROM users WHERE id = :id");
        $stmtUser->execute([':id' => $cashier_id]);
        $user = $stmtUser->fetch(PDO::FETCH_ASSOC);
        $creator = $user ? $user['full_name'] : 'Nhân viên #' . $cashier_id;

        $sql = "INSERT INTO cash_transactions (shift_id, type, amount, note, creator, created_at) 
                VALUES (:shift_id, :type, :amount, :note, :creator, NOW())";
        $stmt = $this->conn->prepare($sql);
        $inserted = $stmt->execute([
            ':shift_id' => $shift['id'],
            ':type'     => $type,
            ':amount'   => $amount,
            ':note'     => $note,
            ':creator'  => $creator
        ]);

        if ($inserted) {
            $updateType = ($type === 'IN') ? 'in' : 'out';
            $this->updateShiftTransaction($shift['id'], $amount, $updateType);
            return true;
        }
        return false;
    }
}
?>