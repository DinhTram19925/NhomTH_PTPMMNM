<?php
class Controller {
    protected $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    protected function render($viewPath, $data = [], $useLayout = true) {
        extract($data);
        $viewFile = __DIR__ . "/../views/{$viewPath}.php";
        if (!file_exists($viewFile)) {
            die("Không tìm thấy giao diện: {$viewPath}");
        }

        if ($useLayout) {
            require_once __DIR__ . "/../views/layouts/header.php";
            require_once $viewFile;
            require_once __DIR__ . "/../views/layouts/footer.php";
        } else {
            require_once $viewFile;
        }
    }

    protected function json($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }

    protected function requireRole($allowedRoles = []) {
        if (session_status() === PHP_SESSION_NONE) session_start();

        if (!isset($_SESSION['user'])) {
            header("Location: index.php?c=auth&a=login");
            exit;
        }

        $userRole = $_SESSION['user']['role_code'];
        if (!empty($allowedRoles) && !in_array($userRole, $allowedRoles)) {
            http_response_code(403);
            die("<h2>403 - Truy cập bị từ chối!</h2><p>Vai trò <b>{$userRole}</b> không có quyền thực hiện chức năng này.</p>");
        }
    }
}
