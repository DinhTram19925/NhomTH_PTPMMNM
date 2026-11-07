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

    public function getCurrentOpenShift($cashier_id = null) {
        $stmt = $this->conn->query("
            SELECT cs.*, u.full_name AS cashier_name 
            FROM cash_shifts cs 
            LEFT JOIN users u ON cs.cashier_id = u.id 
            WHERE cs.status = 'Open' 
            ORDER BY cs.id DESC LIMIT 1
        ");
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function openShift($cashier_id, $opening_cash, $note = '') {
        $stmt = $this->conn->prepare("
            INSERT INTO cash_shifts (cashier_id, opened_at, opening_cash, expected_closing_cash, note, status)
            VALUES (?, NOW(), ?, ?, ?, 'Open')
        ");
        return $stmt->execute([$cashier_id, $opening_cash, $opening_cash, $note]);
    }

    public function closeShift($shift_id, $actual_closing_cash, $note = '') {
        $stmt = $this->conn->prepare("SELECT * FROM cash_shifts WHERE id = ?");
        $stmt->execute([$shift_id]);
        $shift = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$shift) return false;

        $expected = (float)$shift['opening_cash'] + (float)$shift['total_cash_sales'] + (float)$shift['total_cash_in'] - (float)$shift['total_cash_out'];
        $discrepancy = (float)$actual_closing_cash - $expected;
        $finalNote = trim(($shift['note'] ?? '') . ($note ? ' | Chốt ca: ' . $note : ''));

        $upd = $this->conn->prepare("
            UPDATE cash_shifts 
            SET closed_at = NOW(),
                expected_closing_cash = ?,
                actual_closing_cash = ?,
                discrepancy = ?,
                note = ?,
                status = 'Closed'
            WHERE id = ?
        ");
        return $upd->execute([$expected, $actual_closing_cash, $discrepancy, $finalNote, $shift_id]);
    }

    public function recordSaleTransaction($cashier_id, $amount, $type = 'sales') {
        $shift = $this->getCurrentOpenShift($cashier_id);
        if (!$shift) return false;

        $upd = $this->conn->prepare("
            UPDATE cash_shifts 
            SET total_cash_sales = total_cash_sales + ?,
                expected_closing_cash = opening_cash + (total_cash_sales + ?) + total_cash_in - total_cash_out
            WHERE id = ?
        ");
        return $upd->execute([$amount, $amount, $shift['id']]);
    }

    public function createTransaction($cashier_id, $type, $amount, $note = '') {
        $shift = $this->getCurrentOpenShift($cashier_id);
        if (!$shift) return false;

        $this->conn->beginTransaction();
        try {
            $creator = $_SESSION['user']['full_name'] ?? 'Thu ngân';
            $ins = $this->conn->prepare("
                INSERT INTO cash_transactions (shift_id, type, amount, note, creator, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $ins->execute([$shift['id'], $type, $amount, $note, $creator]);

            if ($type === 'IN') {
                $upd = $this->conn->prepare("
                    UPDATE cash_shifts 
                    SET total_cash_in = total_cash_in + ?,
                        expected_closing_cash = opening_cash + total_cash_sales + (total_cash_in + ?) - total_cash_out
                    WHERE id = ?
                ");
                $upd->execute([$amount, $amount, $shift['id']]);
            } else {
                $upd = $this->conn->prepare("
                    UPDATE cash_shifts 
                    SET total_cash_out = total_cash_out + ?,
                        expected_closing_cash = opening_cash + total_cash_sales + total_cash_in - (total_cash_out + ?)
                    WHERE id = ?
                ");
                $upd->execute([$amount, $amount, $shift['id']]);
            }

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    public function getTransactionsByShift($shift_id) {
        $stmt = $this->conn->prepare("SELECT * FROM cash_transactions WHERE shift_id = ? ORDER BY id DESC");
        $stmt->execute([$shift_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getShiftHistory($limit = 20) {
        $stmt = $this->conn->prepare("
            SELECT cs.*, u.full_name AS cashier_name 
            FROM cash_shifts cs 
            LEFT JOIN users u ON cs.cashier_id = u.id 
            ORDER BY cs.id DESC LIMIT :limit
        ");
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
