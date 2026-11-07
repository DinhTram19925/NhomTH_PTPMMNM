<?php
require_once __DIR__ . '/../config/Database.php';

$db = Database::getConnection();
echo "\n=== BAT DAU SUA DU LIEU TRUC TIEP TRONG CSDL CHINH royaldine_5s ===\n";

// 1. Sửa tên khu vực bị lỗi dấu ? ("S?nh Giao H??ng") và bỏ ngoặc đơn thừa
$db->exec("UPDATE areas SET name = 'Sảnh Giao Hưởng' WHERE id = 1 OR name LIKE '%S?nh%' OR name LIKE '%Giao H%'");
$db->exec("UPDATE areas SET name = 'Phòng VIP Hầm Rượu' WHERE id = 2 OR name LIKE '%Phòng VIP Hầm Rượu%'");
echo " [OK] 1. Da sua ten khu vuc 'S?nh Giao H??ng' -> 'Sảnh Giao Hưởng' va 'Phòng VIP Hầm Rượu'\n";

// 2. Sửa số điện thoại sai chuẩn '1234567890' -> '0905123456' và chuẩn hóa họ tên
$db->exec("UPDATE customers SET phone = '0905123456', full_name = 'Đinh Thị Mai Trâm' WHERE phone = '1234567890'");
echo " [OK] 2. Da chuan hoa so dien thoai '1234567890' -> '0905123456'\n";

// 3. Sửa các khoản tiền đặt cọc phi thực tế (10.000.000.000 đ) về mức hợp lệ (200.000 đ)
$db->exec("UPDATE reservations SET deposit_amount = 200000 WHERE deposit_amount > 5000000");
$db->exec("UPDATE orders SET deposit_deducted = 200000 WHERE deposit_deducted > 5000000");
echo " [OK] 3. Da dieu chinh khoan tien coc 10.000.000.000 đ ve muc hop le 200.000 đ\n";

// 4. Xử lý các phiếu đặt bàn cũ từ ngày hôm trước (05/11/2026) đang bị treo trạng thái CheckedIn trùng trên T-101
$db->exec("
    UPDATE reservations r
    LEFT JOIN orders o ON o.reservation_id = r.id AND o.status = 'Open'
    SET r.status = 'Cancelled',
        r.special_request = CONCAT(COALESCE(r.special_request, ''), ' [Đóng tự động do quá hạn qua ngày]')
    WHERE r.status = 'CheckedIn'
      AND DATE(r.booking_time) < CURDATE()
      AND o.id IS NULL
");
echo " [OK] 4. Da xu ly phieu CheckedIn cu tu ngay hom truoc bi treo tren ban T-101\n";

echo "=== HOAN TAT SUA DU LIEU TRUC TIEP TRONG royaldine_5s! ===\n";