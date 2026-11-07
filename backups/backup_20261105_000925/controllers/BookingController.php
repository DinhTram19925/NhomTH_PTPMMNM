
<?php

require_once __DIR__ . '/../models/TableModel.php';
require_once __DIR__ . '/../models/ReservationModel.php';

class BookingController extends Controller
{
public function index()
{
    $this->requireRole();

    $model = new TableModel();
    $reservationModel = new ReservationModel();

    $tables = $model->getAllTables();
    $statistics = $model->getTableStatistics();

    $reservedTableIds = $reservationModel->getUpcomingReservedTableIds();

    $this->render(
        'booking/index',
        compact('tables', 'statistics', 'reservedTableIds')
    );
}

    // QUẢN LÝ BÀN
    public function manage()
{
    $this->requireRole(['ADMIN','WAITER']);

    $model = new TableModel();
    $error = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';

        try {
            // =========================
            // QUẢN LÝ KHU VỰC
            // =========================
            if ($action === 'area_add') {
                $name = trim($_POST['name'] ?? '');
                $surcharge = (float)($_POST['surcharge_percent'] ?? 0);

                if ($name === '' || $surcharge < 0 || $surcharge > 100) {
                    throw new InvalidArgumentException(
                        'Tên khu vực hoặc phụ thu không hợp lệ.'
                    );
                }

                $model->addArea($name, $surcharge);

            } elseif ($action === 'area_update') {
                $id = (int)($_POST['id'] ?? 0);
                $name = trim($_POST['name'] ?? '');
                $surcharge = (float)($_POST['surcharge_percent'] ?? 0);

                if ($id <= 0 || $name === '' ||
                    $surcharge < 0 || $surcharge > 100) {
                    throw new InvalidArgumentException(
                        'Thông tin khu vực không hợp lệ.'
                    );
                }

                $model->updateArea($id, $name, $surcharge);

            } elseif ($action === 'area_delete') {
                $id = (int)($_POST['id'] ?? 0);

                if ($id <= 0) {
                    throw new InvalidArgumentException(
                        'Khu vực không hợp lệ.'
                    );
                }

                $model->deleteArea($id);

            // =========================
            // THÊM / SỬA BÀN
            // =========================
            } elseif ($action === 'add' || $action === 'update') {
                $areaId = (int)($_POST['area_id'] ?? 0);
                $name = trim($_POST['table_name'] ?? '');
                $capacity = (int)($_POST['capacity'] ?? 0);

                if ($areaId <= 0 || $name === '' ||
                    $capacity < 1 || $capacity > 100) {
                    throw new InvalidArgumentException(
                        'Thông tin bàn không hợp lệ.'
                    );
                }

                if ($action === 'add') {
                    $model->addTable($areaId, $name, $capacity);
                } else {
                    $id = (int)($_POST['id'] ?? 0);

                    if ($id <= 0 || !$model->findTable($id)) {
                        throw new InvalidArgumentException(
                            'Bàn cần sửa không tồn tại.'
                        );
                    }

                    $model->updateTable($id, $areaId, $name, $capacity);
                }

            // =========================
            // CẬP NHẬT TRẠNG THÁI BÀN
            // =========================
            } elseif ($action === 'status') {
                $id = (int)($_POST['id'] ?? 0);
                $status = $_POST['status'] ?? '';

                if ($id <= 0 || !$model->findTable($id)) {
                    throw new InvalidArgumentException(
                        'Bàn không tồn tại.'
                    );
                }

                if (!$model->updateStatus($id, $status)) {
                    throw new InvalidArgumentException(
                        'Trạng thái bàn không hợp lệ.'
                    );
                }

            } else {
                throw new InvalidArgumentException(
                    'Thao tác không hợp lệ.'
                );
            }

            header('Location: index.php?c=booking&a=manage&success=1');
            exit;

        } catch (InvalidArgumentException $e) {
            $error = $e->getMessage();

        } catch (PDOException $e) {
            $error = 'Không thể lưu dữ liệu bàn hoặc khu vực.';
        }
    }

    $tables = $model->getAllTables();
    $areas = $model->getAreas();

    $this->render(
        'booking/manage',
        compact('tables', 'areas', 'error')
    );
}
    // ĐẶT BÀN VIP
    public function vip()
    {
        $this->requireRole(['ADMIN', 'HOSTESS','WAITER', 'CASHIER']);

        $model = new ReservationModel();
        $error = '';
        $success = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';

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

                    if (
                        $name === '' ||
                        !preg_match('/^[0-9]{9,11}$/', $phone) ||
                        $tableId <= 0 ||
                        $guestCount < 1 ||
                        $deposit < 0 ||
                        $bookingTime === ''
                    ) {
                        throw new InvalidArgumentException(
                            'Vui lòng nhập đầy đủ thông tin hợp lệ.'
                        );
                    }

                    $date = DateTime::createFromFormat(
                        'Y-m-d\TH:i',
                        $bookingTime
                    );

                    if (
                        !$date ||
                        $date->format('Y-m-d\TH:i') !== $bookingTime ||
                        $date <= new DateTime()
                    ) {
                        throw new InvalidArgumentException(
                            'Thời gian đặt bàn phải hợp lệ và nằm trong tương lai.'
                        );
                    }

                    $table = $model->findTable($tableId);

                    if (!$table || $table['status'] !== 'Available') {
                        throw new InvalidArgumentException(
                            'Bàn không tồn tại hoặc hiện không trống.'
                        );
                    }

                    if ($guestCount > (int)$table['capacity']) {
                        throw new InvalidArgumentException(
                            'Số khách vượt quá sức chứa bàn.'
                        );
                    }

                    $bookingTimeSql = $date->format('Y-m-d H:i:s');

                    if (!$model->isTableAvailable($tableId, $bookingTimeSql)) {
                        throw new InvalidArgumentException(
                            'Bàn đã có lịch đặt trùng thời gian.'
                        );
                    }

                    $code = $model->createReservation([
                        'full_name' => $name,
                        'phone' => $phone,
                        'email' => $email,
                        'table_id' => $tableId,
                        'guest_count' => $guestCount,
                        'booking_time' => $bookingTimeSql,
                        'deposit_amount' => $deposit,
                        'special_request' => $request
                    ]);

                    header(
                        'Location: index.php?c=booking&a=vip&created=' .
                        urlencode($code)
                    );
                    exit;

                } elseif ($action === 'status') {
                    $id = (int)($_POST['id'] ?? 0);
                    $status = $_POST['status'] ?? '';

                    if ($id <= 0) {
                        throw new InvalidArgumentException(
                            'Mã đặt bàn không hợp lệ.'
                        );
                    }

                    if (!in_array(
                        $status,
                        ['Confirmed', 'Cancelled', 'CheckedIn'],
                        true
                    )) {
                        throw new InvalidArgumentException(
                            'Trạng thái đặt bàn không hợp lệ.'
                        );
                    }

                    if (!$model->updateReservationStatus($id, $status)) {
                        throw new InvalidArgumentException(
                            'Không thể cập nhật. Đặt bàn có thể đã được xử lý hoặc bàn không khả dụng.'
                        );
                    }
// if ($status === 'CheckedIn') {
//                         // Lấy thông tin order_id hoặc gắn kết tương ứng từ reservation id $id
//                         // (Giả sử model của bạn có hàm lấy order_id hoặc mã đơn từ reservation, 
//                         // hoặc nếu hệ thống dùng trực tiếp order_id, bạn điều chỉnh biến cho khớp nhé)
//                         $order_id = $model->getOrderIdByReservationId($id) ?? $id; 
                        
//                         require_once __DIR__ . '/../../models/StockModel.php';
//                         $stockModel = new StockModel();
//                         $current_user_id = $_SESSION['user']['id'] ?? 2;

//                         $bomResult = $stockModel->deductStockByOrder($order_id, $current_user_id);
                        
//                         // Nếu kho chạm mức tối thiểu, có thể lưu cảnh báo vào Session để hiển thị ra màn hình
//                         if ($bomResult['success'] && !empty($bomResult['warnings'])) {
//                             $_SESSION['stock_warnings'] = $bomResult['warnings'];
//                         }
//                     }
            
//             // Nếu có cảnh báo nguyên liệu sắp hết, gộp chung vào mảng kết quả trả về cho giao diện POS hiển thị
//             if (!empty($bomResult['warnings'])) {
//                 $result['stock_warnings'] = $bomResult['warnings'];
//             }
//         }
                    header('Location: index.php?c=booking&a=vip&updated=1');
                    exit;

                } else {
                    throw new InvalidArgumentException(
                        'Thao tác không hợp lệ.'
                    );
                }

            } catch (InvalidArgumentException $e) {
                $error = $e->getMessage();
            } catch (PDOException $e) {
                $error = 'Không thể lưu đặt bàn. Vui lòng kiểm tra dữ liệu.';
            }
        }

        $reservations = $model->getAllReservations();
        $tables = $model->getAvailableTables();

        if (isset($_GET['created'])) {
            $success = 'Đã tạo đặt bàn: ' .
                htmlspecialchars($_GET['created']);
        } elseif (isset($_GET['updated'])) {
            $success = 'Đã cập nhật trạng thái đặt bàn.';
        }

        $this->render(
            'booking/vip',
            compact('reservations', 'tables', 'error', 'success')
        );
    }

    
    // BAO CAO BOOKING VA CRM
    public function report(): void
    {
        $this->requireRole(['ADMIN', 'HOSTESS','WAITER', 'CASHIER']);

        $model = new ReservationModel();

        $bookingStats = $model->getBookingStatistics();
        $topCustomers = $model->getTopVipCustomers();

        $this->render('booking/report', [
            'bookingStats' => $bookingStats,
            'topCustomers' => $topCustomers
        ]);
    }
    // Trang xem bàn dành cho khách hàng

public function customer(): void
{
    $model = new ReservationModel();

    $bookingTime = trim($_POST['booking_time'] ?? $_GET['booking_time'] ?? '');
    $guestCount = (int)($_POST['guest_count'] ?? $_GET['guest_count'] ?? 1);

    $tables = [];
    $error = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST'
        && ($_POST['action'] ?? '') === 'create') {

        $fullName = trim($_POST['full_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $tableId = (int)($_POST['table_id'] ?? 0);
        $specialRequest = trim($_POST['special_request'] ?? '');

        $date = DateTime::createFromFormat('Y-m-d\TH:i', $bookingTime);

        if ($fullName === '') {
            $error = 'Vui lòng nhập họ và tên.';
        } elseif (!preg_match('/^[0-9]{9,11}$/', $phone)) {
            $error = 'Số điện thoại không hợp lệ.';
        } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Email không hợp lệ.';
        } elseif (!$date || $date->format('Y-m-d\TH:i') !== $bookingTime
            || $date <= new DateTime()) {
            $error = 'Thời gian đặt bàn không hợp lệ.';
        } elseif ($guestCount < 1 || $guestCount > 100) {
            $error = 'Số lượng khách không hợp lệ.';
        } elseif ($tableId <= 0) {
            $error = 'Vui lòng chọn bàn.';
        } else {
            $table = $model->findTable($tableId);

            if (!$table || $table['status'] !== 'Available') {
                $error = 'Bàn không còn khả dụng.';
            } elseif ((int)$table['capacity'] < $guestCount) {
                $error = 'Bàn không đủ sức chứa số lượng khách.';
            } elseif (!$model->isTableAvailable(
                $tableId,
                $date->format('Y-m-d H:i:s')
            )) {
                $error = 'Bàn đã có người đặt trong thời gian này.';
            } else {
                // Tính tiền cọc theo số lượng khách
                $depositAmount = match (true) {
                    $guestCount <= 4  => 50000,
                    $guestCount <= 8  => 100000,
                    $guestCount <= 12 => 150000,
                    $guestCount <= 20 => 200000,
                    default           => 300000
                };

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

              header(
    'Location: index.php?c=booking&a=customer&created=' .
    urlencode($bookingCode) .
    '&guest_count=' . $guestCount
);
exit;
            }
        }
    }

    if ($bookingTime !== '') {
        $date = DateTime::createFromFormat('Y-m-d\TH:i', $bookingTime);

        if (!$date || $date->format('Y-m-d\TH:i') !== $bookingTime
            || $date <= new DateTime()) {
            if ($error === '') {
                $error = 'Vui lòng chọn thời gian đặt bàn hợp lệ.';
            }
        } elseif ($guestCount < 1 || $guestCount > 100) {
            if ($error === '') {
                $error = 'Số lượng khách không hợp lệ.';
            }
        } else {
            $tables = $model->getAvailableTablesForBooking(
                $date->format('Y-m-d H:i:s'),
                $guestCount
            );
        }
    }

    $success = trim($_GET['created'] ?? '');

    $depositAmount = match (true) {
        $guestCount <= 4  => 50000,
        $guestCount <= 8  => 100000,
        $guestCount <= 12 => 150000,
        $guestCount <= 20 => 200000,
        default           => 300000
    };

    $this->render('booking/customer', compact(
        'tables',
        'bookingTime',
        'guestCount',
        'error',
        'success',
        'depositAmount'
    ));
}

}
