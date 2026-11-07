<?php
class Controller {
    protected $db;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->db = Database::getConnection();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
    }

    protected function render($viewPath, $data = [], $useLayout = true) {
        $data['csrf_token'] = $_SESSION['csrf_token'] ?? '';
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
        if (ob_get_length()) {
            ob_clean();
        }
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }

    protected function requireRole($allowedRoles = [], bool $isApi = false) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['user']['id'])) {
            if ($isApi) {
                $this->json(['status' => 'error', 'message' => 'Phiên đăng nhập đã hết, vui lòng đăng nhập lại.'], 401);
            }
            header("Location: index.php?c=auth&a=login");
            exit;
        }

        $stmt = $this->db->prepare("
            SELECT u.is_active, r.role_code
            FROM users u
            JOIN roles r ON u.role_id = r.id
            WHERE u.id = ?
        ");
        $stmt->execute([(int)$_SESSION['user']['id']]);
        $liveUser = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$liveUser || (int)$liveUser['is_active'] !== 1) {
            unset($_SESSION['user']);
            if ($isApi) {
                $this->json(['status' => 'error', 'message' => 'Tài khoản đã bị khóa hoặc không còn tồn tại.'], 403);
            }
            header("Location: index.php?c=auth&a=login");
            exit;
        }

        $_SESSION['user']['role_code'] = $liveUser['role_code'];
        $userRole = strtoupper($liveUser['role_code'] ?? '');
        if ($userRole === 'ADMIN' || $userRole === 'MANAGER') {
            return;
        }

        $expandedAllowed = array_map('strtoupper', $allowedRoles);
        if (in_array('WAITER', $expandedAllowed, true)) {
            $expandedAllowed[] = 'CASHIER';
            $expandedAllowed[] = 'SERVER';
        }
        if (in_array('CHEF', $expandedAllowed, true)) {
            $expandedAllowed[] = 'BAR';
        }

        if (!empty($expandedAllowed) && !in_array($userRole, $expandedAllowed, true)) {
            if ($isApi) {
                $this->json(['status' => 'error', 'message' => "Vai trò {$userRole} không có quyền thực hiện chức năng này."], 403);
            }
            http_response_code(403);
            die("<h2>403 - Truy cập bị từ chối!</h2><p>Vai trò <b>" . htmlspecialchars($userRole) . "</b> không có quyền thực hiện chức năng này.</p>");
        }
    }

    protected function writeAuditLog(string $actionType, string $moduleName, string $description, string $severity = 'INFO'): void {
        if (empty($_SESSION['user']['id'])) {
            return;
        }
        try {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $stmt = $this->db->prepare("
                INSERT INTO audit_logs (user_id, action_type, module_name, description, severity, ip_address)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([(int)$_SESSION['user']['id'], $actionType, $moduleName, $description, $severity, $ip]);
        } catch (Throwable $e) {
            error_log("[AuditLog] " . $e->getMessage());
        }
    }
}