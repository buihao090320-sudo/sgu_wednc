<?php
// admin/nhap-hang.php – Danh sách phiếu nhập
require_once '../includes/config.php';
$page_title = 'Quản lý nhập hàng';

$q    = trim($_GET['q'] ?? '');
$date = trim($_GET['date'] ?? '');
$where = '1=1';
if ($q !== '')   $where .= " AND p.ma_phieu LIKE '%" . $conn->real_escape_string($q) . "%'";
if ($date !== '') $where .= " AND p.ngay_nhap='$date'";

$phieu_list = $conn->query("SELECT p.*, COUNT(c.id) as so_sp, SUM(c.so_luong*c.gia_nhap) as tong FROM phieu_nhap p LEFT JOIN chi_tiet_nhap c ON c.phieu_nhap_id=p.id WHERE $where GROUP BY p.id ORDER BY p.created_at DESC")->fetch_all(MYSQLI_ASSOC);
$topbar_actions = '<a href="them-phieu-nhap.php" class="btn-admin-primary">+ Tạo phiếu nhập</a>';
include 'includes/header.php';
?>
<div class="page-toolbar">
  <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap">
    <input type="text" name="q" class="admin-search" placeholder="🔍 Tìm mã phiếu..." value="<?= htmlspecialchars($q) ?>">
    <input type="date" name="date" class="admin-input" value="<?= htmlspecialchars($date) ?>">
    <button type="submit" class="btn-admin-primary">Lọc</button>
    <a href="nhap-hang.php" class="btn-admin-secondary">Xóa lọc</a>
  </form>
</div>
<div class="table-wrap">
  <table class="admin-table">
    <thead><tr><th>Mã phiếu</th><th>Ngày nhập</th><th>Số SP</th><th>Tổng tiền</th><th>Trạng thái</th><th>Thao tác</th></tr></thead>
    <tbody>
    <?php if (empty($phieu_list)): ?>
      <tr><td colspan="6" style="text-align:center;padding:32px;color:#94a3b8">Chưa có phiếu nhập</td></tr>
    <?php else: ?>
      <?php foreach ($phieu_list as $p): ?>
      <tr>
        <td><code><?= htmlspecialchars($p['ma_phieu']) ?></code></td>
        <td><?= date('d/m/Y', strtotime($p['ngay_nhap'])) ?></td>
        <td><?= $p['so_sp'] ?> sản phẩm</td>
        <td style="font-weight:700"><?= formatGia($p['tong'] ?? 0) ?></td>
        <td><span class="badge badge-<?= $p['trang_thai']==='hoan_thanh'?'delivered':'draft' ?>"><?= $p['trang_thai']==='hoan_thanh'?'Hoàn thành':'Nháp' ?></span></td>
        <td class="actions-cell">
          <a href="xem-phieu-nhap.php?id=<?= $p['id'] ?>" class="btn-edit-sm">👁 Chi tiết</a>
          <?php if ($p['trang_thai'] === 'nhap'): ?>
          <a href="them-phieu-nhap.php?sua=<?= $p['id'] ?>" class="btn-warn-sm">✏️ Sửa</a>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
  </table>
</div>
<?php include 'includes/footer.php'; ?>
