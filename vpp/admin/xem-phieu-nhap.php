<?php
// admin/xem-phieu-nhap.php
require_once '../includes/config.php';
$page_title = 'Chi tiết phiếu nhập';
$id = (int)($_GET['id']??0);
$phieu = $conn->query("SELECT * FROM phieu_nhap WHERE id=$id")->fetch_assoc();
if (!$phieu) { header('Location: nhap-hang.php'); exit; }
$items = $conn->query("SELECT c.*,s.ten,s.icon FROM chi_tiet_nhap c JOIN san_pham s ON c.sp_id=s.id WHERE c.phieu_nhap_id=$id")->fetch_all(MYSQLI_ASSOC);
include 'includes/header.php';
?>
<a href="nhap-hang.php" class="btn-admin-secondary btn-sm" style="display:inline-flex;gap:6px;margin-bottom:16px">← Quay lại</a>
<div class="admin-form-card" style="max-width:700px">
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:16px;font-size:.875rem">
    <div><strong>Mã phiếu:</strong> <?= htmlspecialchars($phieu['ma_phieu']) ?></div>
    <div><strong>Ngày nhập:</strong> <?= date('d/m/Y',strtotime($phieu['ngay_nhap'])) ?></div>
    <div><strong>Trạng thái:</strong> <span class="badge badge-<?= $phieu['trang_thai']==='hoan_thanh'?'delivered':'draft' ?>"><?= $phieu['trang_thai']==='hoan_thanh'?'Hoàn thành':'Nháp' ?></span></div>
    <div><strong>Ghi chú:</strong> <?= htmlspecialchars($phieu['ghi_chu']??'—') ?></div>
  </div>
  <div class="table-wrap">
    <table class="admin-table">
      <thead><tr><th>Sản phẩm</th><th>SL nhập</th><th>Giá nhập</th><th>Thành tiền</th></tr></thead>
      <tbody>
        <?php foreach ($items as $it): ?>
        <tr>
          <td><?= htmlspecialchars($it['icon'].' '.$it['ten']) ?></td>
          <td><?= $it['so_luong'] ?></td>
          <td><?= formatGia($it['gia_nhap']) ?></td>
          <td style="font-weight:700"><?= formatGia($it['so_luong']*$it['gia_nhap']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot><tr><td colspan="3" style="text-align:right;font-weight:700;padding:10px 14px">Tổng tiền:</td><td style="font-weight:800;color:#1B4F9B;padding:10px 14px"><?= formatGia($phieu['tong_tien']) ?></td></tr></tfoot>
    </table>
  </div>
</div>
<?php include 'includes/footer.php'; ?>
