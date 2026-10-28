<?php
class AuthController extends Controller {
    public function index() {
        if (isset($_SESSION['user'])) {
            header("Location: index.php?c=menu&a=dashboard");
            exit;
        }
        $this->login();
    }

    public function login() {
        $error = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';

            $stmt = $this->db->prepare("
                SELECT u.*, r.role_code, r.role_name 
                FROM users u 
                JOIN roles r ON u.role_id = r.id 
                WHERE u.username = ? AND u.is_active = 1
            ");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['user'] = $user;
                header("Location: index.php?c=menu&a=dashboard");
                exit;
            } else {
                $error = 'Sai tên đăng nhập hoặc mật khẩu!';
            }
        }
        $this->render('auth_login', ['error' => $error], false);
    }

    public function logout() {
        session_destroy();
        header("Location: index.php?c=auth&a=login");
        exit;
    }
}
