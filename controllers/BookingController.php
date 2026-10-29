
<?php

require_once __DIR__ . '/../models/TableModel.php';

class BookingController extends Controller
{
    public function index()
    {
        $this->requireRole();

        $model = new TableModel();
        $tables = $model->getAllTables();
        $statistics = $model->getTableStatistics();

        $this->render(
            'booking/index',
            compact('tables', 'statistics')
        );
    }

    public function manage()
    {
        $this->requireRole(['ADMIN']);

        $model = new TableModel();
        $error = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';

            try {
                if ($action === 'add' || $action === 'update') {
                    $areaId = (int)($_POST['area_id'] ?? 0);
                    $name = trim($_POST['table_name'] ?? '');
                    $capacity = (int)($_POST['capacity'] ?? 0);

                    if ($areaId <= 0 || $name === ''
                        || $capacity < 1 || $capacity > 100) {
                        throw new InvalidArgumentException(
                            'Vui lòng nhập khu vực, tên bàn và sức chứa hợp lệ.'
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

                        $model->updateTable(
                            $id, $areaId, $name, $capacity
                        );
                    }
                } elseif ($action === 'status') {
                    $id = (int)($_POST['id'] ?? 0);
                    $status = $_POST['status'] ?? '';

                    if ($id <= 0 || !$model->findTable($id)) {
                        throw new InvalidArgumentException(
                            'Bàn cần cập nhật không tồn tại.'
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
                $error = 'Không thể lưu dữ liệu. Tên bàn có thể đã tồn tại.';
            }
        }

        $tables = $model->getAllTables();
        $areas = $model->getAreas();

        $this->render(
            'booking/manage',
            compact('tables', 'areas', 'error')
        );
    }
}
