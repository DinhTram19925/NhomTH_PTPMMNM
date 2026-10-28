<h3 class="fw-bold mb-4">📊 Tổng Quan Hệ Thống RoyalDine 5S</h3>
<div class="row g-3 mb-4">
    <div class="col-md-4"><div class="card p-3 shadow-sm border-start border-warning border-4"><h6>Món ăn 5 sao trong Menu</h6><h2 class="fw-bold"><?= $totalItems ?> món</h2></div></div>
    <div class="col-md-4"><div class="card p-3 shadow-sm border-start border-success border-4"><h6>Tổng số bàn phục vụ</h6><h2 class="fw-bold"><?= $totalTables ?> bàn</h2></div></div>
    <div class="col-md-4"><div class="card p-3 shadow-sm border-start border-primary border-4"><h6>Nhân sự hệ thống</h6><h2 class="fw-bold"><?= $totalUsers ?> tài khoản</h2></div></div>
</div>
<div class="alert alert-info">✅ Bạn đã đăng nhập thành công với vai trò <b><?= $_SESSION['user']['role_name'] ?></b>. Hãy bấm vào menu bên trái để kiểm tra từng tính năng!</div>
