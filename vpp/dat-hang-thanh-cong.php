<?php
// dat-hang-thanh-cong.php
require_once 'includes/config.php';
requireLogin();
$base = '';
$don_id = (int)($_SESSION['last_order'] ?? 0);
if (!$don_id) { header('Location: index.php'); exit; }
$page_title = 'Đặt hàng thành công';

$don = $conn->query("SELECT * FROM don_hang WHERE id=$don_id")->fetch_assoc();
$items = $conn->query("SELECT * FROM chi_tiet_don_hang WHERE don_hang_id=$don_id")->fetch_all(MYSQLI_ASSOC);
$pmLabel = ['tien_mat'=>'💵 Tiền mặt COD','chuyen_khoan'=>'🏦 Chuyển khoản','truc_tuyen'=>'💳 Trực tuyến'];
include 'includes/header.php';
?>
<div class="auth-page">
  <div class="auth-card">
    <div style="font-size:3.5rem;margin-bottom:12px">✅</div>
    <h2>Đặt hàng thành công!</h2>
    <p class="auth-sub">Cảm ơn bạn đã mua hàng tại Văn phòng Phẩm Sài Gòn</p>
    <div class="ord-details">
      <strong>Mã đơn hàng:</strong> <?= htmlspecialchars($don['ma_don']) ?><br>
      <strong>Ngày đặt:</strong> <?= date('d/m/Y H:i', strtotime($don['created_at'])) ?><br>
      <strong>Địa chỉ giao:</strong> <?= htmlspecialchars($don['ten_nguoi_nhan']) ?> – <?= htmlspecialchars($don['so_dien_thoai']) ?><br>
      <?= htmlspecialchars($don['dia_chi_giao']) ?><br>
      <strong>Thanh toán:</strong> <?= $pmLabel[$don['phuong_thuc_tt']] ?><br>
      <strong>Tổng tiền:</strong> <span style="color:#1B4F9B;font-weight:800"><?= formatGia($don['tong_tien']) ?></span>
    </div>
    <div style="display:flex;gap:10px;justify-content:center;margin-top:16px">
      <a href="san-pham.php" class="btn-primary">Tiếp tục mua</a>
      <a href="don-hang.php" class="btn-outline">Xem đơn hàng</a>
    </div>
  </div>
</div>
<?php include 'includes/footer.php'; ?>
