<?php
class AuthController extends Controller {
    public function index() {
        $stmt = $this->db->query("SELECT * FROM roles");
        $roles = $stmt->fetchAll();

        echo "<div style='font-family: Arial, sans-serif; padding: 30px;'>";
        echo "<h1 style='color: #b8860b;'>🌟 RoyalDine 5S - Hệ Thống Quản Lý Nhà Hàng 5 Sao</h1>";
        echo "<p><b>Trạng thái:</b> Kết nối CSDL <code>royaldine_5s</code> thành công!</p>";
        echo "<h3>Danh sách phân quyền trong hệ thống:</h3><ul>";
        foreach ($roles as $r) {
            echo "<li><b>{$r['role_code']}</b>: {$r['role_name']}</li>";
        }
        echo "</ul></div>";
    }
}
