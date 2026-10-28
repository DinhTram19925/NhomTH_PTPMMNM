<?php
class UserModel {
    private $db;

    public function __construct($db) {
        $this->db =$db;
    }

    public function getAllUsers() {
        return $this->db->query("
            SELECT u.*, r.role_code, r.role_name 
            FROM users u 
            JOIN roles r ON u.role_id = r.id 
            ORDER BY u.id ASC
        ")->fetchAll();
    }

    public function getAllRoles() {
        return $this->db->query("SELECT * FROM roles ORDER BY id ASC")->fetchAll();
    }

    public function createUser($data) {
        $hash = password_hash($data['password'], PASSWORD_BCRYPT);
        $stmt =$this->db->prepare("
            INSERT INTO users (role_id, full_name, username, password_hash, phone, is_active)
            VALUES (?, ?, ?, ?, ?, 1)
        ");
        return $stmt->execute([$data['role_id'],
            trim($data['full_name']),
            strtolower(trim($data['username'])),$hash,
            trim($data['phone'])
        ]);
    }

    public function updateUser($id,$data) {
        $stmt =$this->db->prepare("
            UPDATE users SET role_id = ?, full_name = ?, phone = ? WHERE id = ?
        ");
        return $stmt->execute([$data['role_id'],
            trim($data['full_name']),
            trim($data['phone']),$id
        ]);
    }

    public function toggleActive($id) {
        $stmt =$this->db->prepare("UPDATE users SET is_active = IF(is_active = 1, 0, 1) WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function resetPassword($id,$newPass = '123456') {
        $hash = password_hash($newPass, PASSWORD_BCRYPT);
        $stmt =$this->db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        return $stmt->execute([$hash,$id]);
    }
}
