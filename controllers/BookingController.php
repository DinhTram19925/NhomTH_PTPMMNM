<?php
require_once __DIR__ . '/../models/TableModel.php';
require_once __DIR__ . '/../models/ReservationModel.php';

class BookingController extends Controller
{
    private function isAdminRole(): bool
    {
        $role = strtoupper($_SESSION['user']['role_code'] ?? '');
        return $role === 'ADMIN';
    }

    public function index()
    {
        $this->requireRole();
        $model = new TableModel();
        $reservationModel = new ReservationModel();
        $isAdmin = $this->isAdminRole();

        $selectedFloor = isset($_GET['floor']) ? (int)$_GET['floor'] : 1;
        $selectedAreaId = isset($_GET['area_id']) ? (int)$_GET['area_id'] : 0;
        $selectedDate = trim($_GET['date'] ?? date('Y-m-d'));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate) || strtotime($selectedDate) === false) {
            $selectedDate = date('Y-m-d');
        }

        $floorsSummary = $model->getFloorsSummary();
        $availableFloorNums = array_map(fn($f) => (int)$f['floor_number'], $floorsSummary);
        if ($selectedFloor !== 0 && !in_array($selectedFloor, $availableFloorNums, true)) {
            $selectedFloor = $availableFloorNums[0] ?? 1;
        }

        $tables = $model->getAllTables(
            $selectedFloor > 0 ? $selectedFloor : null,
            $selectedAreaId > 0 ? $selectedAreaId : null,
            $selectedDate
        );
        $statistics = $model->getTableStatistics($selectedFloor > 0 ? $selectedFloor : null);
        $areas = $model->getAreas();
        $reservedTableIds = $reservationModel->getUpcomingReservedTableIds();

        $flashSuccess = $_SESSION['flash_msg'] ?? '';
        $flashError   = $_SESSION['flash_err'] ?? '';
        unset($_SESSION['flash_msg'], $_SESSION['flash_err']);

        $this->render('booking/index', compact(
            'tables',
            'statistics',
            'floorsSummary',
            'areas',
            'reservedTableIds',
            'selectedFloor',
            'selectedAreaId',
            'selectedDate',
            'isAdmin',
            'flashSuccess',
            'flashError'
        ));
    }

    public function manage()
    {
        $this->requireRole(['ADMIN', 'HOSTESS', 'WAITER']);
        $model = new TableModel();
        $error = '';
        $isAdmin = $this->isAdminRole();
        $redirectTo = ($_POST['redirect_to'] ?? '') === 'index' ? 'index' : 'manage';
        $returnFloor = (int)($_POST['return_floor'] ?? 1);
        $returnDate = trim($_POST['return_date'] ?? date('Y-m-d'));

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            try {
                $adminOnlyActions = ['floor_sync', 'area_add', 'area_update', 'area_delete', 'add', 'update', 'delete'];
                if (in_array($action, $adminOnlyActions, true) && !$isAdmin) {
                    throw new InvalidArgumentException('Chỉ ADMIN mới có quyền tạo thêm, chỉnh sửa hoặc xóa bàn/phòng và tầng!');
                }

                if ($action === 'floor_sync') {
                    $totalFloors = (int)($_POST['total_floors'] ?? 1);
                    $model->syncTotalFloors($totalFloors);
                    $this->writeAuditLog('SYNC_FLOORS', 'Setup Sơ đồ bàn', "Cập nhật số tầng: $totalFloors tầng", 'WARNING');
                    $_SESSION['flash_msg'] = "Đã cập nhật tổng số tầng thành $totalFloors tầng!";
                } elseif ($action === 'area_add') {
                    $name = trim($_POST['name'] ?? '');
                    $floorNumber = (int)($_POST['floor_number'] ?? 1);
                    $surcharge = (float)($_POST['surcharge_percent'] ?? 0);
                    $model->addArea($name, $surcharge, $floorNumber);
                    $this->writeAuditLog('ADD_AREA', 'Setup Sơ đồ bàn', "Thêm khu vực '$name' (Tầng $floorNumber)", 'INFO');
                    $_SESSION['flash_msg'] = "Đã thêm khu vực '$name' tại Tầng $floorNumber!";
                } elseif ($action === 'area_update') {
                    $id = (int)($_POST['id'] ?? 0);
                    $name = trim($_POST['name'] ?? '');
                    $floorNumber = (int)($_POST['floor_number'] ?? 1);
                    $surcharge = (float)($_POST['surcharge_percent'] ?? 0);
                    $model->updateArea($id, $name, $surcharge, $floorNumber);
                    $this->writeAuditLog('UPDATE_AREA', 'Setup Sơ đồ bàn', "Sửa khu vực #$id '$name'", 'INFO');
                    $_SESSION['flash_msg'] = "Đã cập nhật khu vực '$name'!";
                } elseif ($action === 'area_delete') {
                    $id = (int)($_POST['id'] ?? 0);
                    $model->deleteArea($id);
                    $this->writeAuditLog('DELETE_AREA', 'Setup Sơ đồ bàn', "Xóa khu vực #$id", 'WARNING');
                    $_SESSION['flash_msg'] = "Đã xóa khu vực thành công!";
                } elseif ($action === 'add' || $action === 'update') {
                    $areaId = (int)($_POST['area_id'] ?? 0);
                    $name = trim($_POST['table_name'] ?? '');
                    $capacity = (int)($_POST['capacity'] ?? 4);
                    $tableType = trim($_POST['table_type'] ?? 'Standard');

                    if ($action === 'add') {
                        $model->addTable($areaId, $name, $capacity, $tableType);
                        $this->writeAuditLog('ADD_TABLE', 'Setup Sơ đồ bàn', "Tạo bàn/phòng '$name' ($tableType - $capacity ghế)", 'INFO');
                        $_SESSION['flash_msg'] = "Đã tạo bàn/phòng '$name' ($capacity ghế) thành công!";
                    } else {
                        $id = (int)($_POST['id'] ?? 0);
                        $model->updateTable($id, $areaId, $name, $capacity, $tableType);
                        $this->writeAuditLog('UPDATE_TABLE', 'Setup Sơ đồ bàn', "Cập nhật bàn/phòng '$name' (#$id)", 'INFO');
                        $_SESSION['flash_msg'] = "Đã cập nhật thông tin '$name' thành công!";
                    }
                } elseif ($action === 'delete') {
                    $id = (int)($_POST['id'] ?? 0);
                    $model->deleteTable($id);
                    $this->writeAuditLog('DELETE_TABLE', 'Setup Sơ đồ bàn', "Xóa bàn/phòng #$id", 'WARNING');
                    $_SESSION['flash_msg'] = "Đã xóa bàn/phòng khỏi sơ đồ!";
                } elseif ($action === 'status') {
                    $id = (int)($_POST['id'] ?? 0);
                    $status = $_POST['status'] ?? '';
                    if ($id <= 0 || !$model->findTable($id)) {
                        throw new InvalidArgumentException('Bàn/Phòng không tồn tại.');
                    }
                    if (!$model->updateStatus($id, $status)) {
                        throw new InvalidArgumentException('Trạng thái bàn không hợp lệ.');
                    }
                    $_SESSION['flash_msg'] = "Đã cập nhật trạng thái bàn!";
                } else {
                    throw new InvalidArgumentException('Thao tác không hợp lệ.');
                }

                if ($redirectTo === 'index') {
                    header("Location: index.php?c=booking&a=index&floor={$returnFloor}&date=" . urlencode($returnDate));
                } else {
                    header('Location: index.php?c=booking&a=manage&success=1');
                }
                exit;
            } catch (InvalidArgumentException $e) {
                $error = $e->getMessage();
            } catch (PDOException $e) {
                $error = 'Không thể lưu dữ liệu bàn hoặc khu vực.';
            }

            if ($redirectTo === 'index' && $error !== '') {
                $_SESSION['flash_err'] = $error;
                header("Location: index.php?c=booking&a=index&floor={$returnFloor}&date=" . urlencode($returnDate));
                exit;
            }
        }

        $allTablesRaw = $model->getAllTables();
        $validTables = array_values(array_filter($allTablesRaw, fn($r) => !empty($r['table_id'])));

        $limit = 8;
        $page_tbl = isset($_GET['page_tbl']) ? max(1, (int)$_GET['page_tbl']) : 1;
        $total_tbl = count($validTables);
        $maxPage_tbl = max(1, (int)ceil($total_tbl / $limit));
        if ($page_tbl > $maxPage_tbl) $page_tbl = $maxPage_tbl;
        $offset_tbl = ($page_tbl - 1) * $limit;
        $tables = array_slice($validTables, $offset_tbl, $limit);

        $areas = $model->getAreas();
        $floorsSummary = $model->getFloorsSummary();
        $this->render('booking/manage', compact('tables', 'areas', 'floorsSummary', 'error', 'isAdmin', 'page_tbl', 'maxPage_tbl', 'total_tbl'));
    }

    private function handleReservationPost(ReservationModel $model, string $defaultReturnPage): array
    {
        $error = '';
        $success = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            $returnPage = ($_POST['return_page'] ?? $defaultReturnPage) === 'report' ? 'report' : 'vip';
            try {
                if ($action === 'create') {
                    $name = trim($_POST['full_name'] ?? '');
                    $phone = trim($_POST['phone'] ?? '');
                    $email = trim($_POST['email'] ?? '');
                    $tableId = (int)($_POST['table_id'] ?? 0);
                    $guestCount = (int)($_POST['guest_count'] ?? 0);
                    $bookingTime = trim($_POST['booking_time'] ?? '');
                    $deposit = (float)($_POST['deposit_amount'] ?? 0);
                    $request = trim($_POST['special_request'] ?? '');

                    if ($name === '' || !preg_match('/^0[0-9]{9,10}$/', $phone) || $tableId <= 0 || $guestCount < 1 || $deposit < 0 || $bookingTime === '') {
                        throw new InvalidArgumentException('Vui lòng nhập đầy đủ thông tin và số điện thoại hợp lệ.');
                    }

                    $date = DateTime::createFromFormat('Y-m-d\TH:i', $bookingTime);
                    if (!$date || $date <= new DateTime('-5 minutes')) {
                        throw new InvalidArgumentException('Thời gian đặt bàn phải nằm trong tương lai.');
                    }

                    $bookingTimeSql = $date->format('Y-m-d H:i:s');
                    $code = $model->createReservation([
                        'full_name'       => $name,
                        'phone'           => $phone,
                        'email'           => $email,
                        'table_id'        => $tableId,
                        'guest_count'     => $guestCount,
                        'booking_time'    => $bookingTimeSql,
                        'deposit_amount'  => $deposit,
                        'special_request' => $request
                    ]);

                    header('Location: index.php?c=booking&a=' . $returnPage . '&created=' . urlencode($code));
                    exit;
                } elseif ($action === 'status') {
                    $id = (int)($_POST['id'] ?? 0);
                    $status = $_POST['status'] ?? '';
                    $actorId = (int)($_SESSION['user']['id'] ?? 1);

                    if ($id <= 0 || !in_array($status, ['Confirmed', 'Cancelled', 'CheckedIn'], true)) {
                        throw new InvalidArgumentException('Trạng thái đặt bàn không hợp lệ.');
                    }

                    if (!$model->updateReservationStatus($id, $status, $actorId)) {
                        throw new InvalidArgumentException('Không thể cập nhật trạng thái đặt bàn.');
                    }
                    header('Location: index.php?c=booking&a=' . $returnPage . '&updated=1');
                    exit;
                }
            } catch (Throwable $e) {
                $error = $e->getMessage();
            }
        }

        if (isset($_GET['created'])) {
            $success = 'Đã tạo đặt bàn mã ' . htmlspecialchars($_GET['created']);
        } elseif (isset($_GET['updated'])) {
            $success = 'Đã cập nhật trạng thái và đồng bộ POS';
        }

        return [$error, $success];
    }

    public function vip()
    {
        $this->requireRole(['ADMIN', 'HOSTESS', 'WAITER', 'CASHIER']);
        $model = new ReservationModel();
        $tableModel = new TableModel();

        [$error, $success] = $this->handleReservationPost($model, 'vip');

        $reservations = $model->getAllReservations();
        $bookingStats = $model->getBookingStatistics();
        $tables = $model->getAvailableTables();
        $allTables = $tableModel->getAllTables();
        $floorsSummary = $tableModel->getFloorsSummary();
        $areas = $tableModel->getAreas();
        $statistics = $tableModel->getTableStatistics();
        $reservedTableIds = $model->getUpcomingReservedTableIds();
        $kdsMap = $model->getTableKdsProgressMap();

        $this->render('booking/vip', compact(
            'reservations', 'bookingStats', 'tables', 'allTables', 'floorsSummary',
            'areas', 'statistics', 'reservedTableIds', 'kdsMap', 'error', 'success'
        ));
    }

    public function report(): void
    {
        $this->requireRole(['ADMIN', 'HOSTESS', 'WAITER', 'CASHIER']);
        $model = new ReservationModel();
        $tableModel = new TableModel();

        [$error, $success] = $this->handleReservationPost($model, 'report');

        $reservations = $model->getAllReservations();
        $bookingStats = $model->getBookingStatistics();
        $topCustomers = $model->getTopVipCustomers();
        $tables = $model->getAvailableTables();
        $allTables = $tableModel->getAllTables();
        $floorsSummary = $tableModel->getFloorsSummary();
        $areas = $tableModel->getAreas();
        $statistics = $tableModel->getTableStatistics();
        $reservedTableIds = $model->getUpcomingReservedTableIds();
        $kdsMap = $model->getTableKdsProgressMap();

        $this->render('booking/report', compact(
            'reservations', 'bookingStats', 'topCustomers', 'tables', 'allTables',
            'floorsSummary', 'areas', 'statistics', 'reservedTableIds', 'kdsMap', 'error', 'success'
        ));
    }

    public function customer(): void
    {
        $tableModel = new TableModel();
        $model = new ReservationModel();

        $selectedDate = trim($_POST['booking_date'] ?? $_GET['date'] ?? date('Y-m-d'));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate) || strtotime($selectedDate) === false) {
            $selectedDate = date('Y-m-d');
        }
        $selectedFloor = isset($_GET['floor']) ? (int)$_GET['floor'] : 1;
        $selectedAreaId = isset($_GET['area_id']) ? (int)$_GET['area_id'] : 0;
        $guestCount = (int)($_POST['guest_count'] ?? $_GET['guest_count'] ?? 4);
        $error = '';

        $floorsSummary = $tableModel->getFloorsSummary();
        $availableFloorNums = array_map(fn($f) => (int)$f['floor_number'], $floorsSummary);
        if ($selectedFloor !== 0 && !in_array($selectedFloor, $availableFloorNums, true)) {
            $selectedFloor = $availableFloorNums[0] ?? 1;
        }
        $areas = $tableModel->getAreas();

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'confirm_test_deposit') {
            $bookingCode = trim($_POST['booking_code'] ?? '');
            if ($bookingCode !== '' && $model->confirmTestDeposit($bookingCode)) {
                header('Location: index.php?c=booking&a=customer&created=' . urlencode($bookingCode) . '&test_paid=1');
                exit;
            }
            $error = 'Không thể xác nhận thanh toán đặt cọc mô phỏng.';
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
            $fullName = trim($_POST['full_name'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $tableId = (int)($_POST['table_id'] ?? 0);
            $bookingTime = trim($_POST['booking_time'] ?? '');
            $specialRequest = trim($_POST['special_request'] ?? '');
            $date = DateTime::createFromFormat('Y-m-d\TH:i', $bookingTime);

            if ($fullName === '') $error = 'Vui lòng nhập họ và tên.';
            elseif (!preg_match('/^0[0-9]{9,10}$/', $phone)) $error = 'Số điện thoại không hợp lệ (Phải bắt đầu bằng số 0 và có 10-11 chữ số).';
            elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $error = 'Email không hợp lệ.';
            elseif (!$date || $date <= new DateTime('-5 minutes')) $error = 'Thời gian đặt bàn phải nằm trong tương lai.';
            elseif ($guestCount < 1 || $guestCount > 100) $error = 'Số lượng khách không hợp lệ.';
            elseif ($tableId <= 0) $error = 'Vui lòng chọn bàn hoặc phòng trống.';
            else {
                try {
                    $selectedTable = $model->findTable($tableId);
                    if (!$selectedTable) {
                        throw new InvalidArgumentException('Bàn/Phòng đã chọn không tồn tại.');
                    }

                    $depositAmount = method_exists('TableModel', 'calculateDepositByTable')
                        ? TableModel::calculateDepositByTable($selectedTable, $guestCount)
                        : 50000;

                    $bookingCode = $model->createReservation([
                        'full_name' => $fullName,
                        'phone' => $phone,
                        'email' => $email,
                        'table_id' => $tableId,
                        'booking_time' => $date->format('Y-m-d H:i:s'),
                        'guest_count' => $guestCount,
                        'deposit_amount' => $depositAmount,
                        'special_request' => $specialRequest
                    ]);
                    header('Location: index.php?c=booking&a=customer&created=' . urlencode($bookingCode) . '&guest_count=' . $guestCount);
                    exit;
                } catch (Throwable $e) {
                    $error = $e->getMessage();
                }
            }
        }

        $tables = $tableModel->getAllTables(
            $selectedFloor > 0 ? $selectedFloor : null,
            $selectedAreaId > 0 ? $selectedAreaId : null,
            $selectedDate
        );

        $success = trim($_GET['created'] ?? '');
        $createdReservation = ($success !== '' && method_exists($model, 'getReservationByCode'))
            ? $model->getReservationByCode($success)
            : null;
        $depositAmount = $createdReservation ? (float)$createdReservation['deposit_amount'] : 50000;

        $this->render('booking/customer', compact(
            'tables',
            'floorsSummary',
            'areas',
            'selectedFloor',
            'selectedAreaId',
            'selectedDate',
            'guestCount',
            'error',
            'success',
            'depositAmount',
            'createdReservation'
        ));
    }
}