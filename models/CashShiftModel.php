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
        if ((float)$opening_cash < 0) {
            return false;
        }
        $ownTx = !$this->conn->inTransaction();
        if ($ownTx) $this->conn->beginTransaction();
        try {
            $chk = $this->conn->query("SELECT id FROM cash_shifts WHERE status = 'Open' LIMIT 1 FOR UPDATE");
            if ($chk->fetch()) {
                if ($ownTx) $this->conn->rollBack();
                return false;
            }
            $stmt = $this->conn->prepare("
                INSERT INTO cash_shifts (cashier_id, opened_at, opening_cash, expected_closing_cash, note, status)
                VALUES (?, NOW(), ?, ?, ?, 'Open')
            ");
            $ok = $stmt->execute([(int)$cashier_id, (float)$opening_cash, (float)$opening_cash, $note]);
            if ($ownTx) $this->conn->commit();
            return $ok;
        } catch (Exception $e) {
            if ($ownTx && $this->conn->inTransaction()) $this->conn->rollBack();
            return false;
        }
    }

    public function closeShift($shift_id, $actual_closing_cash, $note = '') {
        if ((float)$actual_closing_cash < 0) {
            return false;
        }
        $ownTx = !$this->conn->inTransaction();
        if ($ownTx) $this->conn->beginTransaction();
        try {
            $stmt = $this->conn->prepare("SELECT * FROM cash_shifts WHERE id = ? FOR UPDATE");
            $stmt->execute([(int)$shift_id]);
            $shift = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$shift || $shift['status'] !== 'Open') {
                if ($ownTx) $this->conn->rollBack();
                return false;
            }

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
                WHERE id = ? AND status = 'Open'
            ");
            $ok = $upd->execute([$expected, (float)$actual_closing_cash, $discrepancy, $finalNote, (int)$shift_id]);
            if ($ownTx) $this->conn->commit();
            return $ok;
        } catch (Exception $e) {
            if ($ownTx && $this->conn->inTransaction()) $this->conn->rollBack();
            return false;
        }
    }

    public function recordSaleTransaction($cashier_id, $amount, $type = 'sales') {
        if ((float)$amount <= 0) return false;
        $shift = $this->getCurrentOpenShift($cashier_id);
        if (!$shift) return false;

        $upd = $this->conn->prepare("
            UPDATE cash_shifts 
            SET total_cash_sales = total_cash_sales + ?,
                expected_closing_cash = opening_cash + (total_cash_sales + ?) + total_cash_in - total_cash_out
            WHERE id = ? AND status = 'Open'
        ");
        return $upd->execute([(float)$amount, (float)$amount, (int)$shift['id']]);
    }

    public function createTransaction($cashier_id, $type, $amount, $note = '') {
        if ((float)$amount <= 0 || !in_array($type, ['IN', 'OUT'], true)) {
            return false;
        }
        $ownTx = !$this->conn->inTransaction();
        if ($ownTx) $this->conn->beginTransaction();
        try {
            $stmt = $this->conn->query("SELECT * FROM cash_shifts WHERE status = 'Open' ORDER BY id DESC LIMIT 1 FOR UPDATE");
            $shift = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$shift) {
                if ($ownTx) $this->conn->rollBack();
                return false;
            }

            $creator = $_SESSION['user']['full_name'] ?? 'Thu ngân';
            $ins = $this->conn->prepare("
                INSERT INTO cash_transactions (shift_id, type, amount, note, creator, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $ins->execute([(int)$shift['id'], $type, (float)$amount, $note, $creator]);

            if ($type === 'IN') {
                $upd = $this->conn->prepare("
                    UPDATE cash_shifts 
                    SET total_cash_in = total_cash_in + ?,
                        expected_closing_cash = opening_cash + total_cash_sales + (total_cash_in + ?) - total_cash_out
                    WHERE id = ?
                ");
                $upd->execute([(float)$amount, (float)$amount, (int)$shift['id']]);
            } else {
                $upd = $this->conn->prepare("
                    UPDATE cash_shifts 
                    SET total_cash_out = total_cash_out + ?,
                        expected_closing_cash = opening_cash + total_cash_sales + total_cash_in - (total_cash_out + ?)
                    WHERE id = ?
                ");
                $upd->execute([(float)$amount, (float)$amount, (int)$shift['id']]);
            }

            if ($ownTx) $this->conn->commit();
            return true;
        } catch (Exception $e) {
            if ($ownTx && $this->conn->inTransaction()) $this->conn->rollBack();
            return false;
        }
    }

    public function getTransactionsByShift($shift_id) {
        $stmt = $this->conn->prepare("SELECT * FROM cash_transactions WHERE shift_id = ? ORDER BY id DESC");
        $stmt->execute([(int)$shift_id]);
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