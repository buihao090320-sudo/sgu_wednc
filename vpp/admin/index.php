<?php
// admin/index.php
require_once '../includes/config.php';
$page_title = 'Tổng quan';
include 'includes/header.php';

$total_sp   = $conn->query("SELECT COUNT(*) as c FROM san_pham")->fetch_assoc()['c'];
$total_user = $conn->query("SELECT COUNT(*) as c FROM nguoi_dung WHERE vai_tro='khach_hang'")->fetch_assoc()['c'];
$cho_xu_ly  = $conn->query("SELECT COUNT(*) as c FROM don_hang WHERE trang_thai='cho_xu_ly'")->fetch_assoc()['c'];
$doanh_thu  = $conn->query("SELECT COALESCE(SUM(tong_tien),0) as s FROM don_hang WHERE trang_thai!='da_huy'")->fetch_assoc()['s'];

// Sản phẩm sắp hết (≤20)
$low_stock  = $conn->query("SELECT id,ten,icon,so_luong_ton FROM san_pham WHERE so_luong_ton<=20 AND trang_thai='hien' ORDER BY so_luong_ton ASC LIMIT 10")->fetch_all(MYSQLI_ASSOC);

// Đơn hàng mới nhất
$recent_orders = $conn->query("SELECT d.*,n.ho_ten FROM don_hang d JOIN nguoi_dung n ON d.nguoi_dung_id=n.id ORDER BY d.created_at DESC LIMIT 6")->fetch_all(MYSQLI_ASSOC);
$stLabel = ['cho_xu_ly'=>'Chờ xử lý','da_xac_nhan'=>'Đã xác nhận','da_giao'=>'Đã giao','da_huy'=>'Đã huỷ'];
$stClass = ['cho_xu_ly'=>'badge-pending','da_xac_nhan'=>'badge-confirmed','da_giao'=>'badge-delivered','da_huy'=>'badge-cancelled'];
?>
<div class="stat-cards">
  <div class="stat-card"><div class="sc-icon">📦</div><div class="sc-val"><?= $total_sp ?></div><div class="sc-lbl">Sản phẩm</div></div>
  <div class="stat-card"><div class="sc-icon">📋</div><div class="sc-val"><?= $cho_xu_ly ?></div><div class="sc-lbl">Đơn chờ xử lý</div></div>
  <div class="stat-card"><div class="sc-icon">👥</div><div class="sc-val"><?= $total_user ?></div><div class="sc-lbl">Khách hàng</div></div>
  <div class="stat-card"><div class="sc-icon">💰</div><div class="sc-val"><?= formatGia($doanh_thu) ?></div><div class="sc-lbl">Doanh thu</div></div>
</div>
<div class="dash-grid">
  <div class="dash-card">
    <h3>⚠️ Sản phẩm sắp hết hàng (≤ 20)</h3>
    <?php if (empty($low_stock)): ?>
      <p style="color:#94a3b8;font-size:.85rem">Không có sản phẩm sắp hết hàng</p>
    <?php else: ?>
      <?php foreach ($low_stock as $sp): ?>
      <div class="low-stock-item">
        <span><?= htmlspecialchars($sp['icon'].' '.$sp['ten']) ?></span>
        <span style="color:#dc2626;font-weight:700"><?= $sp['so_luong_ton'] ?> còn lại</span>
      </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
  <div class="dash-card">
    <h3>🕐 Đơn hàng mới nhất</h3>
    <?php if (empty($recent_orders)): ?>
      <p style="color:#94a3b8;font-size:.85rem">Chưa có đơn hàng</p>
    <?php else: ?>
      <?php foreach ($recent_orders as $o): ?>
      <div class="recent-order-item">
        <div>
          <div style="font-weight:600;font-size:.82rem"><a href="don-hang.php?xem=<?= $o['id'] ?>" style="color:#1B4F9B">#<?= substr($o['ma_don'],-10) ?></a></div>
          <div style="font-size:.75rem;color:#94a3b8"><?= date('d/m/Y H:i',strtotime($o['created_at'])) ?> – <?= htmlspecialchars($o['ho_ten']) ?></div>
        </div>
        <div style="text-align:right">
          <div style="font-weight:700;color:#1B4F9B;font-size:.85rem"><?= formatGia($o['tong_tien']) ?></div>
          <span class="badge <?= $stClass[$o['trang_thai']] ?>"><?= $stLabel[$o['trang_thai']] ?></span>
        </div>
      </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>
<?php include 'includes/footer.php'; ?>
