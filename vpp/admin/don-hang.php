<?php
// admin/don-hang.php
require_once '../includes/config.php';
$page_title = 'Quản lý đơn đặt hàng';

// Cập nhật trạng thái
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['don_id'])) {
    $did  = (int)$_POST['don_id'];
    $tt   = $_POST['trang_thai'];
    $allowed = ['cho_xu_ly','da_xac_nhan','da_giao','da_huy'];
    if (in_array($tt,$allowed)) $conn->query("UPDATE don_hang SET trang_thai='$tt' WHERE id=$did");
    $_SESSION['admin_msg']='✅ Đã cập nhật trạng thái!';
    header('Location: don-hang.php?' . http_build_query(array_diff_key($_GET,['xem'=>1]))); exit;
}

// Xem chi tiết
$xem_don = null;
if (isset($_GET['xem'])) {
    $did = (int)$_GET['xem'];
    $xem_don = $conn->query("SELECT d.*,n.ho_ten,n.email FROM don_hang d JOIN nguoi_dung n ON d.nguoi_dung_id=n.id WHERE d.id=$did")->fetch_assoc();
    if ($xem_don) $xem_items = $conn->query("SELECT * FROM chi_tiet_don_hang WHERE don_hang_id=$did")->fetch_all(MYSQLI_ASSOC);
}

// Lọc
$tt_filter   = $_GET['tt'] ?? '';
$date_from   = $_GET['tu'] ?? '';
$date_to     = $_GET['den'] ?? '';
$sort_by     = $_GET['sort'] ?? 'date';
$allowed_tt  = ['cho_xu_ly','da_xac_nhan','da_giao','da_huy'];
$where = '1=1';
if ($tt_filter && in_array($tt_filter,$allowed_tt)) $where .= " AND d.trang_thai='$tt_filter'";
if ($date_from) $where .= " AND DATE(d.created_at)>='$date_from'";
if ($date_to)   $where .= " AND DATE(d.created_at)<='$date_to'";
$order_by = $sort_by === 'address' ? "d.quan_huyen ASC, d.tinh_thanh ASC" : "d.created_at DESC";

$orders = $conn->query("SELECT d.*,n.ho_ten FROM don_hang d JOIN nguoi_dung n ON d.nguoi_dung_id=n.id WHERE $where ORDER BY $order_by")->fetch_all(MYSQLI_ASSOC);
$stLabel = ['cho_xu_ly'=>'Chờ xử lý','da_xac_nhan'=>'Đã xác nhận','da_giao'=>'Đã giao','da_huy'=>'Đã huỷ'];
$stClass = ['cho_xu_ly'=>'badge-pending','da_xac_nhan'=>'badge-confirmed','da_giao'=>'badge-delivered','da_huy'=>'badge-cancelled'];
$pmLabel = ['tien_mat'=>'💵 COD','chuyen_khoan'=>'🏦 CK','truc_tuyen'=>'💳 TT'];
include 'includes/header.php';
?>

<?php if ($xem_don): ?>
<!-- CHI TIẾT ĐƠN HÀNG -->
<div class="admin-form-card" style="max-width:760px;margin-bottom:24px">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
    <h3>Chi tiết đơn: <?= htmlspecialchars($xem_don['ma_don']) ?></h3>
    <a href="don-hang.php" class="btn-admin-secondary btn-sm">✕ Đóng</a>
  </div>
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;font-size:.875rem;margin-bottom:16px">
    <div><strong>Khách hàng:</strong> <?= htmlspecialchars($xem_don['ho_ten']) ?></div>
    <div><strong>Ngày đặt:</strong> <?= date('d/m/Y H:i',strtotime($xem_don['created_at'])) ?></div>
    <div><strong>Người nhận:</strong> <?= htmlspecialchars($xem_don['ten_nguoi_nhan']) ?> – <?= htmlspecialchars($xem_don['so_dien_thoai']) ?></div>
    <div><strong>Thanh toán:</strong> <?= $pmLabel[$xem_don['phuong_thuc_tt']] ?></div>
    <div style="grid-column:1/-1"><strong>Địa chỉ:</strong> <?= htmlspecialchars($xem_don['dia_chi_giao']) ?></div>
  </div>
  <div class="table-wrap">
    <table class="admin-table">
      <thead><tr><th>Sản phẩm</th><th>SL</th><th>Đơn giá</th><th>Thành tiền</th></tr></thead>
      <tbody>
        <?php foreach ($xem_items as $it): ?>
        <tr><td><?= htmlspecialchars($it['ten_sp']) ?></td><td><?= $it['so_luong'] ?></td><td><?= formatGia($it['gia_ban']) ?></td><td style="font-weight:700"><?= formatGia($it['gia_ban']*$it['so_luong']) ?></td></tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr><td colspan="3" style="text-align:right;padding:8px 14px">Tạm tính:</td><td style="padding:8px 14px"><?= formatGia($xem_don['tam_tinh']) ?></td></tr>
        <tr><td colspan="3" style="text-align:right;padding:8px 14px">Phí ship:</td><td style="padding:8px 14px"><?= formatGia($xem_don['phi_giao_hang']) ?></td></tr>
        <tr><td colspan="3" style="text-align:right;font-weight:800;padding:10px 14px;border-top:2px solid #e2e8f0">Tổng cộng:</td><td style="font-weight:800;color:#1B4F9B;padding:10px 14px;border-top:2px solid #e2e8f0"><?= formatGia($xem_don['tong_tien']) ?></td></tr>
      </tfoot>
    </table>
  </div>
</div>
<?php endif; ?>

<!-- BỘ LỌC -->
<div class="page-toolbar">
  <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap">
    <input type="date" name="tu" class="admin-input" value="<?= htmlspecialchars($date_from) ?>" placeholder="Từ ngày">
    <span style="color:#64748b;line-height:38px">→</span>
    <input type="date" name="den" class="admin-input" value="<?= htmlspecialchars($date_to) ?>" placeholder="Đến ngày">
    <select name="tt" class="admin-select">
      <option value="">Tất cả trạng thái</option>
      <?php foreach ($stLabel as $k=>$v): ?><option value="<?= $k ?>" <?= $tt_filter===$k?'selected':'' ?>><?= $v ?></option><?php endforeach; ?>
    </select>
    <select name="sort" class="admin-select">
      <option value="date" <?= $sort_by==='date'?'selected':'' ?>>Theo ngày đặt</option>
      <option value="address" <?= $sort_by==='address'?'selected':'' ?>>Theo địa chỉ (phường)</option>
    </select>
    <button type="submit" class="btn-admin-primary">🔍 Lọc</button>
    <a href="don-hang.php" class="btn-admin-secondary">Xóa lọc</a>
  </form>
</div>

<div class="table-wrap">
  <table class="admin-table">
    <thead><tr><th>Mã đơn</th><th>Ngày đặt</th><th>Khách hàng</th><th>Địa chỉ giao</th><th>Tổng tiền</th><th>TT</th><th>Trạng thái</th><th>Thao tác</th></tr></thead>
    <tbody>
    <?php if (empty($orders)): ?>
      <tr><td colspan="8" style="text-align:center;padding:32px;color:#94a3b8">Không có đơn hàng</td></tr>
    <?php else: ?>
    <?php foreach ($orders as $o): ?>
    <tr>
      <td><code style="font-size:.75rem"><?= substr($o['ma_don'],-12) ?></code></td>
      <td><?= date('d/m/Y H:i',strtotime($o['created_at'])) ?></td>
      <td><?= htmlspecialchars($o['ho_ten']) ?></td>
      <td style="max-width:160px;font-size:.8rem"><?= htmlspecialchars($o['quan_huyen'].', '.$o['tinh_thanh']) ?></td>
      <td style="font-weight:700"><?= formatGia($o['tong_tien']) ?></td>
      <td><?= $pmLabel[$o['phuong_thuc_tt']] ?></td>
      <td>
        <form method="POST" style="display:flex;gap:4px">
          <input type="hidden" name="don_id" value="<?= $o['id'] ?>">
          <select name="trang_thai" class="admin-select" style="padding:4px 6px;font-size:.78rem">
            <?php foreach ($stLabel as $k=>$v): ?><option value="<?= $k ?>" <?= $o['trang_thai']===$k?'selected':'' ?>><?= $v ?></option><?php endforeach; ?>
          </select>
          <button type="submit" class="btn-admin-primary btn-sm">✓</button>
        </form>
      </td>
      <td><a href="?xem=<?= $o['id'] ?>&<?= http_build_query(array_diff_key($_GET,['xem'=>1])) ?>" class="btn-edit-sm">👁 Xem</a></td>
    </tr>
    <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
  </table>
</div>
<?php include 'includes/footer.php'; ?>
