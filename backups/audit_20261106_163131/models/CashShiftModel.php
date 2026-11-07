<?php
require_once __DIR__ . '/../config/Database.php';

class CashShiftModel
{
    private PDO $conn;

    public function __construct()
    {
        $this->conn = Database::getConnection();
    }

    public function getConnection(): PDO
    {
        return $this->conn;
    }

    public function getCurrentOpenShift($cashier_id = null)
    {
        // The current project uses one active cash shift for the whole register.
        // Keep the optional argument for backward compatibility with callers.
        $stmt = $this->conn->query("
            SELECT cs.*, u.full_name AS cashier_name
            FROM cash_shifts cs
            LEFT JOIN users u ON u.id = cs.cashier_id
            WHERE cs.status = 'Open'
            ORDER BY cs.id DESC
            LIMIT 1
        ");

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * A named lock enforces the existing one-open-shift-for-the-register rule,
     * including the case where no open row exists yet.
     */
    public function openShift($cashier_id, $opening_cash, $note = '')
    {
        $cashierId = (int)$cashier_id;
        $openingCash = (float)$opening_cash;

        if ($cashierId <= 0 || $openingCash < 0) {
            return false;
        }

        $lockName = 'rd_cash_shift_open_' . substr(hash('sha256', Database::getDatabaseName()), 0, 16);
        $lockStmt = $this->conn->prepare("SELECT GET_LOCK(?, 5)");
        $lockStmt->execute([$lockName]);
        if ((int)$lockStmt->fetchColumn() !== 1) {
            return false;
        }

        $ownTx = !$this->conn->inTransaction();
        try {
            if ($ownTx) {
                $this->conn->beginTransaction();
            }

            $check = $this->conn->prepare("
                SELECT id
                FROM cash_shifts
                WHERE status = 'Open'
                ORDER BY id DESC
                LIMIT 1
                FOR UPDATE
            ");
            $check->execute();

            if ($check->fetch(PDO::FETCH_ASSOC)) {
                if ($ownTx) $this->conn->rollBack();
                return false;
            }

            $insert = $this->conn->prepare("
                INSERT INTO cash_shifts
                    (cashier_id, opened_at, opening_cash, expected_closing_cash, note, status)
                VALUES (?, NOW(), ?, ?, ?, 'Open')
            ");
            $ok = $insert->execute([$cashierId, $openingCash, $openingCash, trim((string)$note)]);

            if ($ownTx) {
                $this->conn->commit();
            }
            return $ok;
        } catch (Throwable $e) {
            if ($ownTx && $this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            error_log('[CashShiftModel::openShift] ' . $e->getMessage());
            return false;
        } finally {
            $release = $this->conn->prepare("SELECT RELEASE_LOCK(?)");
            $release->execute([$lockName]);
        }
    }

    public function closeShift($shift_id, $actual_closing_cash, $note = '')
    {
        $shiftId = (int)$shift_id;
        $actualCash = (float)$actual_closing_cash;

        if ($shiftId <= 0 || $actualCash < 0) {
            return false;
        }

        $ownTx = !$this->conn->inTransaction();
        if ($ownTx) {
            $this->conn->beginTransaction();
        }

        try {
            $stmt = $this->conn->prepare("
                SELECT *
                FROM cash_shifts
                WHERE id = ?
                FOR UPDATE
            ");
            $stmt->execute([$shiftId]);
            $shift = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$shift || $shift['status'] !== 'Open') {
                if ($ownTx) $this->conn->rollBack();
                return false;
            }

            $expected = (float)$shift['opening_cash']
                + (float)$shift['total_cash_sales']
                + (float)$shift['total_cash_in']
                - (float)$shift['total_cash_out'];

            $discrepancy = $actualCash - $expected;
            $finalNote = trim(
                (string)($shift['note'] ?? '')
                . ($note !== '' ? ' | Chốt ca: ' . trim((string)$note) : '')
            );

            $update = $this->conn->prepare("
                UPDATE cash_shifts
                SET closed_at = NOW(),
                    expected_closing_cash = ?,
                    actual_closing_cash = ?,
                    discrepancy = ?,
                    note = ?,
                    status = 'Closed'
                WHERE id = ? AND status = 'Open'
            ");
            $update->execute([
                $expected,
                $actualCash,
                $discrepancy,
                $finalNote,
                $shiftId
            ]);

            if ($update->rowCount() !== 1) {
                throw new RuntimeException('Ca đã được đóng bởi một thao tác khác.');
            }

            if ($ownTx) {
                $this->conn->commit();
            }
            return true;
        } catch (Throwable $e) {
            if ($ownTx && $this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            error_log('[CashShiftModel::closeShift] ' . $e->getMessage());
            return false;
        }
    }

    public function recordSaleTransaction($cashier_id, $amount, $type = 'sales')
    {
        $cashierId = (int)$cashier_id;
        $saleAmount = (float)$amount;

        if ($cashierId <= 0 || $saleAmount <= 0) {
            return false;
        }

        $ownTx = !$this->conn->inTransaction();
        if ($ownTx) {
            $this->conn->beginTransaction();
        }

        try {
            $stmt = $this->conn->prepare("
                SELECT id
                FROM cash_shifts
                WHERE status = 'Open'
                ORDER BY id DESC
                LIMIT 1
                FOR UPDATE
            ");
            $stmt->execute();
            $shiftId = (int)$stmt->fetchColumn();

            if ($shiftId <= 0) {
                if ($ownTx) $this->conn->rollBack();
                return false;
            }

            $update = $this->conn->prepare("
                UPDATE cash_shifts
                SET expected_closing_cash = expected_closing_cash + ?,
                    total_cash_sales = total_cash_sales + ?
                WHERE id = ? AND status = 'Open'
            ");
            $update->execute([$saleAmount, $saleAmount, $shiftId]);

            if ($update->rowCount() !== 1) {
                throw new RuntimeException('Không thể ghi nhận doanh thu vào ca đang mở.');
            }

            if ($ownTx) {
                $this->conn->commit();
            }
            return true;
        } catch (Throwable $e) {
            if ($ownTx && $this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            error_log('[CashShiftModel::recordSaleTransaction] ' . $e->getMessage());
            return false;
        }
    }

    public function createTransaction($cashier_id, $type, $amount, $note = '')
    {
        $cashierId = (int)$cashier_id;
        $amount = (float)$amount;

        if ($cashierId <= 0 || $amount <= 0 || !in_array($type, ['IN', 'OUT'], true)) {
            return false;
        }

        $ownTx = !$this->conn->inTransaction();
        if ($ownTx) {
            $this->conn->beginTransaction();
        }

        try {
            $stmt = $this->conn->prepare("
                SELECT *
                FROM cash_shifts
                WHERE status = 'Open'
                ORDER BY id DESC
                LIMIT 1
                FOR UPDATE
            ");
            $stmt->execute();
            $shift = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$shift) {
                if ($ownTx) $this->conn->rollBack();
                return false;
            }

            $creator = $_SESSION['user']['full_name'] ?? 'Thu ngân';
            $insert = $this->conn->prepare("
                INSERT INTO cash_transactions (shift_id, type, amount, note, creator, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $insert->execute([
                (int)$shift['id'],
                $type,
                $amount,
                trim((string)$note),
                $creator
            ]);

            if ($type === 'IN') {
                $update = $this->conn->prepare("
                    UPDATE cash_shifts
                    SET expected_closing_cash = expected_closing_cash + ?,
                        total_cash_in = total_cash_in + ?
                    WHERE id = ? AND status = 'Open'
                ");
            } else {
                $update = $this->conn->prepare("
                    UPDATE cash_shifts
                    SET expected_closing_cash = expected_closing_cash - ?,
                        total_cash_out = total_cash_out + ?
                    WHERE id = ? AND status = 'Open'
                ");
            }

            $update->execute([$amount, $amount, (int)$shift['id']]);

            if ($update->rowCount() !== 1) {
                throw new RuntimeException('Không thể cập nhật số dư ca làm việc.');
            }

            if ($ownTx) {
                $this->conn->commit();
            }
            return true;
        } catch (Throwable $e) {
            if ($ownTx && $this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            error_log('[CashShiftModel::createTransaction] ' . $e->getMessage());
            return false;
        }
    }

    public function getTransactionsByShift($shift_id)
    {
        $stmt = $this->conn->prepare("
            SELECT *
            FROM cash_transactions
            WHERE shift_id = ?
            ORDER BY id DESC
        ");
        $stmt->execute([(int)$shift_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getShiftHistory($limit = 20)
    {
        $limit = max(1, min(200, (int)$limit));
        $stmt = $this->conn->prepare("
            SELECT cs.*, u.full_name AS cashier_name
            FROM cash_shifts cs
            LEFT JOIN users u ON cs.cashier_id = u.id
            ORDER BY cs.id DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}