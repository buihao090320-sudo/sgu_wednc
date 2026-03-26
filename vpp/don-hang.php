<?php
// don-hang.php – Lịch sử mua hàng
require_once 'includes/config.php';
requireLogin();
$base = '';
$page_title = 'Lịch sử mua hàng';
$uid = (int)$_SESSION['user_id'];

$orders = $conn->query("SELECT * FROM don_hang WHERE nguoi_dung_id=$uid ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
$pmLabel = ['tien_mat'=>'💵 COD','chuyen_khoan'=>'🏦 Chuyển khoản','truc_tuyen'=>'💳 Trực tuyến'];
$stLabel = ['cho_xu_ly'=>'Chờ xử lý','da_xac_nhan'=>'Đã xác nhận','da_giao'=>'Đã giao','da_huy'=>'Đã huỷ'];
$stClass = ['cho_xu_ly'=>'badge-pending','da_xac_nhan'=>'badge-confirmed','da_giao'=>'badge-delivered','da_huy'=>'badge-cancelled'];

include 'includes/header.php';
?>
<div class="order-hist-wrap">
  <h2>📋 Lịch sử mua hàng</h2>
  <?php if (empty($orders)): ?>
    <div class="no-orders">Bạn chưa có đơn hàng nào. <a href="san-pham.php">Mua sắm ngay →</a></div>
  <?php else: ?>
    <?php foreach ($orders as $o):
      $items = $conn->query("SELECT * FROM chi_tiet_don_hang WHERE don_hang_id={$o['id']}")->fetch_all(MYSQLI_ASSOC);
    ?>
    <div class="order-card">
      <div class="order-top">
        <div>
          <div class="order-id">#<?= htmlspecialchars($o['ma_don']) ?></div>
          <div class="order-date">🕐 <?= date('d/m/Y H:i', strtotime($o['created_at'])) ?></div>
          <div class="order-addr-line">📍 <?= htmlspecialchars($o['dia_chi_giao']) ?></div>
          <div style="font-size:.78rem;color:#94a3b8;margin-top:2px"><?= $pmLabel[$o['phuong_thuc_tt']] ?></div>
        </div>
        <div style="text-align:right">
          <span class="badge <?= $stClass[$o['trang_thai']] ?>"><?= $stLabel[$o['trang_thai']] ?></span>
        </div>
      </div>
      <div class="order-items-list">
        <?php foreach ($items as $it): ?>
        <div class="oi-row">
          <span><?= htmlspecialchars($it['ten_sp']) ?> × <?= $it['so_luong'] ?></span>
          <span><?= formatGia($it['gia_ban'] * $it['so_luong']) ?></span>
        </div>
        <?php endforeach; ?>
      </div>
      <div class="order-total-line">Tổng thanh toán: <?= formatGia($o['tong_tien']) ?></div>
    </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>
<?php include 'includes/footer.php'; ?>
