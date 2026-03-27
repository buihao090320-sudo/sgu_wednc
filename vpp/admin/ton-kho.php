<?php
// admin/ton-kho.php
require_once '../includes/config.php';
$page_title = 'Tồn kho & Báo cáo';

// LẤY DANH SÁCH SP - Sửa icon thành image theo Database
$sp_list = $conn->query("SELECT id, ten, image FROM san_pham ORDER BY ten ASC")->fetch_all(MYSQLI_ASSOC);

// Tra cứu tồn kho tại thời điểm
$inv_result = null;
if (isset($_GET['tra_cuu'])) {
    $sp_id   = (int)$_GET['sp_id_tc'];
    $thoidan = $_GET['thoi_diem'];
    if ($sp_id && $thoidan) {
        $sp      = $conn->query("SELECT * FROM san_pham WHERE id=$sp_id")->fetch_assoc();
        $dt      = $conn->real_escape_string($thoidan);
        $nhap    = $conn->query("SELECT COALESCE(SUM(c.so_luong),0) as tong FROM chi_tiet_nhap c JOIN phieu_nhap p ON c.phieu_nhap_id=p.id WHERE c.sp_id=$sp_id AND p.trang_thai='hoan_thanh' AND p.created_at<='$dt'")->fetch_assoc()['tong'];
        $ban     = $conn->query("SELECT COALESCE(SUM(c.so_luong),0) as tong FROM chi_tiet_don_hang c JOIN don_hang d ON c.don_hang_id=d.id WHERE c.sp_id=$sp_id AND d.trang_thai!='da_huy' AND d.created_at<='$dt'")->fetch_assoc()['tong'];
        $inv_result = ['sp'=>$sp,'nhap'=>$nhap,'ban'=>$ban,'ton'=>$nhap-$ban,'thoi_diem'=>$thoidan];
    }
}

// Báo cáo nhập-xuất
$report_result = null;
if (isset($_GET['bao_cao'])) {
    $from = $conn->real_escape_string($_GET['bc_from']);
    $to   = $conn->real_escape_string($_GET['bc_to']);
    if ($from && $to) {
        $nhap_data = $conn->query("SELECT c.sp_id, SUM(c.so_luong) as sl_nhap, SUM(c.so_luong*c.gia_nhap) as tien_nhap FROM chi_tiet_nhap c JOIN phieu_nhap p ON c.phieu_nhap_id=p.id WHERE p.trang_thai='hoan_thanh' AND p.ngay_nhap BETWEEN '$from' AND '$to' GROUP BY c.sp_id")->fetch_all(MYSQLI_ASSOC);
        $ban_data  = $conn->query("SELECT c.sp_id, SUM(c.so_luong) as sl_ban, SUM(c.so_luong*c.gia_ban) as tien_ban FROM chi_tiet_don_hang c JOIN don_hang d ON c.don_hang_id=d.id WHERE d.trang_thai!='da_huy' AND DATE(d.created_at) BETWEEN '$from' AND '$to' GROUP BY c.sp_id")->fetch_all(MYSQLI_ASSOC);
        
        $nhap_map = array_column($nhap_data, null, 'sp_id');
        $ban_map  = array_column($ban_data, null, 'sp_id');
        $all_ids  = array_unique(array_merge(array_column($nhap_data,'sp_id'), array_column($ban_data,'sp_id')));
        
        $report_result = [];
        foreach ($all_ids as $sid) {
            // Sửa icon thành image
            $sp = $conn->query("SELECT ten, image FROM san_pham WHERE id=$sid")->fetch_assoc();
            $report_result[] = [
                'ten' => $sp['ten'],
                'image' => $sp['image'],
                'sl_nhap' => $nhap_map[$sid]['sl_nhap']??0,
                'tien_nhap' => $nhap_map[$sid]['tien_nhap']??0,
                'sl_ban' => $ban_map[$sid]['sl_ban']??0,
                'tien_ban' => $ban_map[$sid]['tien_ban']??0
            ];
        }
    }
}

// Cảnh báo sắp hết
$nguong = (int)($_GET['nguong'] ?? 20);
$low_stock = $conn->query("SELECT * FROM san_pham WHERE so_luong_ton <= $nguong ORDER BY so_luong_ton ASC")->fetch_all(MYSQLI_ASSOC);

include 'includes/header.php';
?>

<div class="inv-cards">

  <div class="inv-card">
    <h3>📦 Tra cứu tồn kho tại thời điểm</h3>
    <form method="GET">
      <input type="hidden" name="tra_cuu" value="1">
      <div class="form-group">
        <label>Chọn sản phẩm</label>
        <select name="sp_id_tc" class="admin-select full-w" required>
          <option value="">-- Chọn sản phẩm --</option>
          <?php foreach ($sp_list as $s): ?>
          <option value="<?= $s['id'] ?>" <?= ($_GET['sp_id_tc']??'')==$s['id']?'selected':'' ?>>
            <?= htmlspecialchars($s['ten']) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Thời điểm</label>
        <input type="datetime-local" name="thoi_diem" class="admin-input full-w" value="<?= htmlspecialchars($_GET['thoi_diem']??date('Y-m-d\TH:i')) ?>">
      </div>
      <button type="submit" class="btn-admin-primary">Tra cứu</button>
    </form>

    <?php if ($inv_result): ?>
    <div class="inv-result" style="display:flex; align-items:center; gap:12px; margin-top:15px; background:#f0f7ff; padding:15px; border-radius:8px">
      <img src="../images/products/<?= htmlspecialchars($inv_result['sp']['image'] ?: 'default.png') ?>" style="width:50px; height:50px; object-fit:cover; border-radius:4px">
      <div>
        <strong><?= htmlspecialchars($inv_result['sp']['ten']) ?></strong><br>
        <small>Tính đến: <?= date('d/m/Y H:i', strtotime($inv_result['thoi_diem'])) ?></small><br>
        <span style="color:#1B4F9B; font-weight:800; font-size:1.1rem">Tồn kho: <?= number_format($inv_result['ton']) ?> cái</span>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <div class="inv-card" style="grid-column: span 2">
    <h3>📊 Báo cáo nhập – xuất theo khoảng thời gian</h3>
    <form method="GET" style="display:flex; gap:10px; align-items:flex-end; margin-bottom:15px">
      <input type="hidden" name="bao_cao" value="1">
      <div class="form-group" style="margin-bottom:0"><label>Từ ngày</label><input type="date" name="bc_from" class="admin-input" value="<?= htmlspecialchars($_GET['bc_from']??'') ?>" required></div>
      <div class="form-group" style="margin-bottom:0"><label>Đến ngày</label><input type="date" name="bc_to" class="admin-input" value="<?= htmlspecialchars($_GET['bc_to']??'') ?>" required></div>
      <button type="submit" class="btn-admin-primary">Xem báo cáo</button>
    </form>

    <?php if ($report_result !== null): ?>
      <?php if (empty($report_result)): ?>
        <p style="color:#94a3b8; margin-top:12px">Không có dữ liệu trong khoảng thời gian này.</p>
      <?php else: ?>
      <div style="margin-top:12px; overflow-x:auto">
        <table class="admin-table">
          <thead>
            <tr>
              <th>Sản phẩm</th>
              <th style="text-align:center">SL nhập</th>
              <th>Tiền nhập</th>
              <th style="text-align:center">SL bán</th>
              <th>Tiền bán</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($report_result as $r): ?>
            <tr>
              <td>
                <div style="display:flex; align-items:center; gap:8px">
                    <img src="../images/products/<?= htmlspecialchars($r['image'] ?: 'default.png') ?>" style="width:30px; height:30px; object-fit:cover; border-radius:4px">
                    <span><?= htmlspecialchars($r['ten']) ?></span>
                </div>
              </td>
              <td style="text-align:center"><?= number_format($r['sl_nhap']) ?></td>
              <td><?= formatGia($r['tien_nhap']) ?></td>
              <td style="text-align:center"><?= number_format($r['sl_ban']) ?></td>
              <td style="font-weight:700; color:#1B4F9B"><?= formatGia($r['tien_ban']) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>

  <div class="inv-card">
    <h3>⚠️ Cảnh báo sắp hết hàng</h3>
    <form method="GET" style="display:flex; gap:8px; align-items:flex-end; margin-bottom:12px">
      <div class="form-group" style="margin-bottom:0">
        <label>Ngưỡng (tồn kho ≤)</label>
        <input type="number" name="nguong" class="admin-input" value="<?= $nguong ?>" min="0" style="width:100px">
      </div>
      <button type="submit" class="btn-admin-primary">Kiểm tra</button>
    </form>

    <?php if (empty($low_stock)): ?>
      <p style="color:#16a34a">✅ Tất cả sản phẩm đều đủ hàng.</p>
    <?php else: ?>
    <div class="table-wrap">
      <table class="admin-table">
        <thead><tr><th>Sản phẩm</th><th style="text-align:center">Tồn kho</th><th>Trạng thái</th></tr></thead>
        <tbody>
          <?php foreach ($low_stock as $sp): ?>
          <tr>
            <td>
                <div style="display:flex; align-items:center; gap:8px">
                    <img src="../images/products/<?= htmlspecialchars($sp['image'] ?: 'default.png') ?>" style="width:30px; height:30px; object-fit:cover; border-radius:4px">
                    <span><?= htmlspecialchars($sp['ten']) ?></span>
                </div>
            </td>
            <td style="text-align:center; font-weight:700; color:<?= $sp['so_luong_ton']==0?'#dc2626':'#d97706' ?>">
                <?= number_format($sp['so_luong_ton']) ?>
            </td>
            <td><?= $sp['so_luong_ton']==0?'<span class="badge badge-cancelled">Hết hàng</span>':'<span class="badge badge-pending">Sắp hết</span>' ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>

</div>

<?php include 'includes/footer.php'; ?>