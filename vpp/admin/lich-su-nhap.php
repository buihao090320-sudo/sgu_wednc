<?php
// admin/lich-su-nhap.php
require_once '../includes/config.php';
$sp_id = (int)($_GET['sp_id']??0);
$sp = $conn->query("SELECT * FROM san_pham WHERE id=$sp_id")->fetch_assoc();
if (!$sp) { header('Location: quan-ly-gia.php'); exit; }
$page_title = 'Lịch sử nhập – '.$sp['ten'];
$history = $conn->query("SELECT p.ngay_nhap,p.ma_phieu,c.so_luong,c.gia_nhap,c.so_luong*c.gia_nhap as thanh_tien FROM chi_tiet_nhap c JOIN phieu_nhap p ON c.phieu_nhap_id=p.id WHERE c.sp_id=$sp_id AND p.trang_thai='hoan_thanh' ORDER BY p.ngay_nhap ASC")->fetch_all(MYSQLI_ASSOC);
include 'includes/header.php';
?>
<a href="quan-ly-gia.php" class="btn-admin-secondary btn-sm" style="display:inline-flex;gap:6px;margin-bottom:16px">← Quay lại</a>
<div class="admin-form-card" style="max-width:700px">
  <h3 style="margin-bottom:8px"><?= htmlspecialchars($sp['icon'].' '.$sp['ten']) ?></h3>
  <div style="font-size:.875rem;color:#64748b;margin-bottom:16px">Giá nhập bình quân hiện tại: <strong style="color:#1B4F9B"><?= formatGia($sp['gia_nhap']) ?></strong></div>
  <?php if (empty($history)): ?>
    <p style="color:#94a3b8">Chưa có lịch sử nhập hàng.</p>
  <?php else: ?>
  <div class="table-wrap">
    <table class="admin-table">
      <thead><tr><th>Mã phiếu</th><th>Ngày nhập</th><th>SL nhập</th><th>Giá nhập</th><th>Thành tiền</th></tr></thead>
      <tbody>
        <?php foreach ($history as $h): ?>
        <tr>
          <td><code><?= htmlspecialchars($h['ma_phieu']) ?></code></td>
          <td><?= date('d/m/Y',strtotime($h['ngay_nhap'])) ?></td>
          <td><?= $h['so_luong'] ?></td>
          <td><?= formatGia($h['gia_nhap']) ?></td>
          <td style="font-weight:700"><?= formatGia($h['thanh_tien']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
<?php include 'includes/footer.php'; ?>
