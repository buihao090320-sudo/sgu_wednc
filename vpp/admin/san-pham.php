<?php
// admin/san-pham.php
require_once '../includes/config.php';
$page_title = 'Quản lý sản phẩm';

// Xoá sản phẩm
if (isset($_GET['xoa'])) {
    $xid = (int)$_GET['xoa'];
    // Kiểm tra đã từng nhập hàng chưa
    $has = $conn->query("SELECT id FROM chi_tiet_nhap WHERE sp_id=$xid LIMIT 1")->num_rows;
    if ($has) {
        $conn->query("UPDATE san_pham SET trang_thai='an' WHERE id=$xid");
        $_SESSION['admin_msg'] = '⚠️ Sản phẩm đã có lịch sử nhập – đã đặt thành ẩn.';
    } else {
        $conn->query("DELETE FROM san_pham WHERE id=$xid");
        $_SESSION['admin_msg'] = '🗑 Đã xóa sản phẩm!';
    }
    $_SESSION['admin_msg_type'] = 'success';
    header('Location: san-pham.php'); exit;
}

// Tìm kiếm / lọc
$q    = trim($_GET['q'] ?? '');
$dm   = (int)($_GET['dm'] ?? 0);
$where = '1=1';
if ($q !== '') $where .= " AND s.ten LIKE '%" . $conn->real_escape_string($q) . "%'";
if ($dm > 0)   $where .= " AND s.danh_muc_id=$dm";

$products = $conn->query("SELECT s.*,d.ten as ten_dm FROM san_pham s JOIN danh_muc d ON s.danh_muc_id=d.id WHERE $where ORDER BY s.id DESC")->fetch_all(MYSQLI_ASSOC);
$dm_list  = $conn->query("SELECT * FROM danh_muc ORDER BY id")->fetch_all(MYSQLI_ASSOC);

$topbar_actions = '<a href="them-san-pham.php" class="btn-admin-primary">+ Thêm sản phẩm</a>';
include 'includes/header.php';
?>
<div class="page-toolbar">
  <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap">
    <input type="text" name="q" class="admin-search" placeholder="🔍 Tìm sản phẩm..." value="<?= htmlspecialchars($q) ?>">
    <select name="dm" class="admin-select" onchange="this.form.submit()">
      <option value="">Tất cả danh mục</option>
      <?php foreach ($dm_list as $d): ?>
      <option value="<?= $d['id'] ?>" <?= $dm==$d['id']?'selected':'' ?>><?= htmlspecialchars($d['ten']) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn-admin-primary">Lọc</button>
    <a href="san-pham.php" class="btn-admin-secondary">Xóa lọc</a>
  </form>
</div>
<div class="table-wrap">
  <table class="admin-table">
    <thead>
      <tr><th>Mã SP</th><th>Sản phẩm</th><th>Danh mục</th><th>Giá nhập</th><th>TL LN</th><th>Giá bán</th><th>Tồn kho</th><th>Trạng thái</th><th>Thao tác</th></tr>
    </thead>
    <tbody>
      <?php if (empty($products)): ?>
      <tr><td colspan="9" style="text-align:center;padding:32px;color:#94a3b8">Không có sản phẩm</td></tr>
      <?php else: ?>
      <?php foreach ($products as $sp): $gia = giaBan($sp['gia_nhap'],$sp['ti_le_loi_nhuan']); ?>
      <tr>
        <td><code>SP<?= str_pad($sp['id'],3,'0',STR_PAD_LEFT) ?></code></td>
        <td><div style="font-weight:600"><?= htmlspecialchars($sp['icon'].' '.$sp['ten']) ?></div></td>
        <td><?= htmlspecialchars($sp['ten_dm']) ?></td>
        <td><?= formatGia($sp['gia_nhap']) ?></td>
        <td><?= $sp['ti_le_loi_nhuan'] ?>%</td>
        <td style="font-weight:700;color:#1B4F9B"><?= formatGia($gia) ?></td>
        <td><?= $sp['so_luong_ton'] > 0 ? $sp['so_luong_ton'] : '<span style="color:#dc2626">Hết</span>' ?></td>
        <td><span class="badge badge-<?= $sp['trang_thai']==='hien'?'active':'hidden' ?>"><?= $sp['trang_thai']==='hien'?'Hiển thị':'Ẩn' ?></span></td>
        <td class="actions-cell">
          <a href="them-san-pham.php?sua=<?= $sp['id'] ?>" class="btn-edit-sm">✏️ Sửa</a>
          <a href="?xoa=<?= $sp['id'] ?>" class="btn-danger-sm" onclick="return confirm('Xóa/ẩn sản phẩm này?')">🗑</a>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>
<?php include 'includes/footer.php'; ?>
