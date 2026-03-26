<?php
// admin/quan-ly-gia.php
require_once '../includes/config.php';
$page_title = 'Quản lý giá bán';

// Sửa tỉ lệ lợi nhuận
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['sp_id'])) {
    $sid = (int)$_POST['sp_id'];
    $tl  = (float)$_POST['ti_le'];
    if ($tl < 0) { $_SESSION['admin_msg']='Tỉ lệ không hợp lệ!'; $_SESSION['admin_msg_type']='danger'; }
    else {
        $conn->query("UPDATE san_pham SET ti_le_loi_nhuan=$tl WHERE id=$sid");
        $_SESSION['admin_msg']='✅ Đã cập nhật tỉ lệ lợi nhuận!';
    }
    header('Location: quan-ly-gia.php'); exit;
}

$q = trim($_GET['q'] ?? '');
$where = "trang_thai='hien'";
if ($q !== '') $where .= " AND ten LIKE '%".$conn->real_escape_string($q)."%'";
$products = $conn->query("SELECT * FROM san_pham WHERE $where ORDER BY id")->fetch_all(MYSQLI_ASSOC);
include 'includes/header.php';
?>
<div class="page-toolbar">
  <form method="GET" style="display:flex;gap:10px">
    <input type="text" name="q" class="admin-search" placeholder="🔍 Tìm sản phẩm..." value="<?= htmlspecialchars($q) ?>">
    <button type="submit" class="btn-admin-primary">Tìm</button>
    <a href="quan-ly-gia.php" class="btn-admin-secondary">Xóa</a>
  </form>
</div>
<div class="table-wrap">
  <table class="admin-table">
    <thead><tr><th>Sản phẩm</th><th>Giá nhập BQ</th><th>Tỉ lệ LN (%)</th><th>Giá bán hiện tại</th><th>Lịch sử nhập</th><th>Sửa tỉ lệ</th></tr></thead>
    <tbody>
    <?php foreach ($products as $sp): $gb = giaBan($sp['gia_nhap'],$sp['ti_le_loi_nhuan']); ?>
    <tr>
      <td style="font-weight:600"><?= htmlspecialchars($sp['icon'].' '.$sp['ten']) ?></td>
      <td><?= formatGia($sp['gia_nhap']) ?></td>
      <td style="font-weight:700;color:#16a34a"><?= $sp['ti_le_loi_nhuan'] ?>%</td>
      <td style="font-weight:800;color:#1B4F9B"><?= formatGia($gb) ?></td>
      <td><a href="lich-su-nhap.php?sp_id=<?= $sp['id'] ?>" class="btn-edit-sm">📜 Xem</a></td>
      <td>
        <form method="POST" style="display:flex;gap:6px;align-items:center">
          <input type="hidden" name="sp_id" value="<?= $sp['id'] ?>">
          <input type="number" name="ti_le" value="<?= $sp['ti_le_loi_nhuan'] ?>" min="0" step="0.1" class="admin-input" style="width:80px" required>
          <button type="submit" class="btn-admin-primary btn-sm">Lưu</button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php include 'includes/footer.php'; ?>
