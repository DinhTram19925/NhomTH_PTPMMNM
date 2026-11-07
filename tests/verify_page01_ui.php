<?php
$_SESSION['user'] = [
    'id' => 1,
    'full_name' => 'Đinh Thị Mai Trâm',
    'username' => 'admin',
    'role_code' => 'ADMIN',
    'role_name' => 'Quản trị hệ thống'
];
$_GET['c'] = 'menu';
$_GET['a'] = 'dashboard';

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../controllers/MenuController.php';

ob_start();
$ctrl = new MenuController();
$ctrl->dashboard();
$html = ob_get_clean();

// Loại bỏ thẻ <script> và <style> để chỉ kiểm tra nội dung hiển thị thực tế với người dùng
$visibleHtml = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $html);
$visibleHtml = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $visibleHtml);
$visibleText = html_entity_decode(strip_tags($visibleHtml), ENT_QUOTES, 'UTF-8');

$hasParentheses = (strpos($visibleText, '(') !== false || strpos($visibleText, ')') !== false);
$hasIconClasses = (preg_match('/\bbi-[a-z0-9_-]+|\bfa-[a-z0-9_-]+|<svg\b/i', $visibleHtml) === 1);
$hasCorruptedText = (strpos($visibleText, '的交') !== false);

// Kiểm tra thêm trong DB bảng areas
$db = Database::getConnection();
$corruptedAreaCount = (int)$db->query("SELECT COUNT(*) FROM areas WHERE name LIKE '%的交%'")->fetchColumn();

echo "=== KET QUA KIEM TRA UI PAGE 01 (TONG QUAN HOAT DONG) ===\n";
echo "1. Kiem tra dau ngoac don '(' hoac ')' trong UI text : " . ($hasParentheses ? "FAIL" : "PASS (0 dau ngoac don)") . "\n";
echo "2. Kiem tra Icon class / SVG trong HTML             : " . ($hasIconClasses ? "FAIL" : "PASS (0 icon)") . "\n";
echo "3. Kiem tra chuoi loi 'Sảnh的交 hưởng' (UI + DB)    : " . (($hasCorruptedText || $corruptedAreaCount > 0) ? "FAIL" : "PASS (Da chuan hoa 'Sảnh Giao Hưởng')") . "\n";

if ($hasParentheses) {
    $lines = explode("\n", $visibleText);
    foreach ($lines as $line) {
        if (strpos($line, '(') !== false || strpos($line, ')') !== false) {
            echo "   -> Phat hien ngoac don tai: " . trim($line) . "\n";
        }
    }
    exit(1);
}
if ($hasIconClasses || $hasCorruptedText || $corruptedAreaCount > 0) {
    exit(1);
}
exit(0);