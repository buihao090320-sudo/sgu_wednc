<?php
// admin/them-phieu-nhap.php – Tạo / Sửa phiếu nhập
require_once '../includes/config.php';
$edit_id = (int)($_GET['sua'] ?? 0);
$phieu = null; $chi_tiet = [];

if ($edit_id) {
    $phieu = $conn->query("SELECT * FROM phieu_nhap WHERE id=$edit_id AND trang_thai='nhap'")->fetch_assoc();
    if (!$phieu) { $_SESSION['admin_msg']='Không thể sửa phiếu đã hoàn thành!'; header('Location: nhap-hang.php'); exit; }
    $chi_tiet = $conn->query("SELECT c.*,s.ten,s.icon FROM chi_tiet_nhap c JOIN san_pham s ON c.sp_id=s.id WHERE c.phieu_nhap_id=$edit_id")->fetch_all(MYSQLI_ASSOC);
}
$page_title = $phieu ? 'Sửa phiếu nhập' : 'Tạo phiếu nhập';
$sp_list = $conn->query("SELECT id,ten,icon FROM san_pham WHERE trang_thai='hien' ORDER BY ten")->fetch_all(MYSQLI_ASSOC);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action     = $_POST['action'] ?? 'save';
    $ngay_nhap  = $_POST['ngay_nhap'] ?? date('Y-m-d');
    $ghi_chu    = trim($_POST['ghi_chu'] ?? '');
    $sp_ids     = $_POST['sp_id'] ?? [];
    $so_luongs  = $_POST['so_luong'] ?? [];
    $gia_nhaps  = $_POST['gia_nhap'] ?? [];

    if (!$ngay_nhap) $errors[] = 'Chọn ngày nhập';
    if (empty($sp_ids)) $errors[] = 'Thêm ít nhất 1 sản phẩm';

    $items_clean = [];
    foreach ($sp_ids as $i => $sid) {
        $sid = (int)$sid; $sl = (int)($so_luongs[$i] ?? 0); $gia = (float)($gia_nhaps[$i] ?? 0);
        if (!$sid) continue;
        if ($action === 'complete' && ($sl <= 0 || $gia <= 0)) { $errors[] = "Số lượng và giá nhập phải > 0"; break; }
        if ($sl <= 0) $sl = 1;
        $items_clean[] = ['sp_id'=>$sid,'so_luong'=>$sl,'gia_nhap'=>$gia];
    }

    if (empty($errors)) {
        $tong = array_sum(array_map(fn($i)=>$i['so_luong']*$i['gia_nhap'], $items_clean));
        $tt   = $action === 'complete' ? 'hoan_thanh' : 'nhap';

        if ($phieu) {
            // Cập nhật phiếu nháp
            $upd = $conn->prepare("UPDATE phieu_nhap SET ngay_nhap=?,ghi_chu=?,trang_thai=?,tong_tien=? WHERE id=?");
            $upd->bind_param('sssdi', $ngay_nhap,$ghi_chu,$tt,$tong,$edit_id);
            $upd->execute();
            $conn->query("DELETE FROM chi_tiet_nhap WHERE phieu_nhap_id=$edit_id");
            foreach ($items_clean as $it) {
                $ins = $conn->prepare("INSERT INTO chi_tiet_nhap (phieu_nhap_id,sp_id,so_luong,gia_nhap,thanh_tien) VALUES (?,?,?,?,?)");
                $tt_nh = $it['so_luong'] * $it['gia_nhap'];
                $ins->bind_param('iiidd',$edit_id,$it['sp_id'],$it['so_luong'],$it['gia_nhap'],$tt_nh);
                $ins->execute();
            }
            $pid = $edit_id;
        } else {
            $ma = 'PN' . date('YmdHis');
            $ins = $conn->prepare("INSERT INTO phieu_nhap (ma_phieu,ngay_nhap,ghi_chu,trang_thai,tong_tien,nguoi_tao) VALUES (?,?,?,?,?,?)");
            $ins->bind_param('ssssdi',$ma,$ngay_nhap,$ghi_chu,$tt,$tong,$_SESSION['admin_id']);
            $ins->execute();
            $pid = $conn->insert_id;
            foreach ($items_clean as $it) {
                $ins2 = $conn->prepare("INSERT INTO chi_tiet_nhap (phieu_nhap_id,sp_id,so_luong,gia_nhap,thanh_tien) VALUES (?,?,?,?,?)");
                $tt_nh2 = $it['so_luong'] * $it['gia_nhap'];
                $ins2->bind_param('iiidd',$pid,$it['sp_id'],$it['so_luong'],$it['gia_nhap'],$tt_nh2);
                $ins2->execute();
            }
        }

        // Nếu hoàn thành → cập nhật tồn kho + giá nhập bình quân
        if ($tt === 'hoan_thanh') {
            foreach ($items_clean as $it) {
                capNhatGiaNhapBinhQuan($conn, $it['sp_id'], $it['so_luong'], $it['gia_nhap']);
            }
        }
        $_SESSION['admin_msg'] = $tt==='hoan_thanh' ? '✅ Phiếu nhập đã hoàn thành!' : '💾 Đã lưu nháp!';
        header('Location: nhap-hang.php'); exit;
    }
}
include 'includes/header.php';
?>
<div style="max-width:860px">
  <a href="nhap-hang.php" class="btn-admin-secondary btn-sm" style="display:inline-flex;gap:6px;margin-bottom:16px">← Quay lại</a>
  <?php if (!empty($errors)): ?><div class="alert alert-danger"><?= implode('<br>',$errors) ?></div><?php endif; ?>
  <div class="admin-form-card" style="max-width:100%">
    <form method="POST" novalidate id="pnForm">
      <div class="form-row-2">
        <div class="form-group">
          <label>Ngày nhập <span class="req">*</span></label>
          <input type="date" name="ngay_nhap" value="<?= htmlspecialchars($phieu['ngay_nhap']??date('Y-m-d')) ?>" id="pnDate">
          <span class="err" id="pnDateErr"></span>
        </div>
        <div class="form-group">
          <label>Ghi chú</label>
          <input type="text" name="ghi_chu" value="<?= htmlspecialchars($phieu['ghi_chu']??'') ?>" placeholder="Ghi chú phiếu nhập">
        </div>
      </div>

      <!-- Tìm kiếm sản phẩm để thêm -->
      <div class="form-group">
        <label>Tìm sản phẩm để thêm</label>
        <input type="text" id="spSearchInput" class="admin-search" style="width:100%" placeholder="🔍 Nhập tên sản phẩm..." oninput="searchSp()">
        <div id="spSearchResults" class="import-search-results"></div>
      </div>

      <!-- Bảng sản phẩm nhập -->
      <div class="import-items-wrap">
        <strong>Danh sách sản phẩm nhập</strong>
        <table class="admin-table" style="margin-top:10px" id="itemsTable">
          <thead><tr><th>Sản phẩm</th><th>Số lượng</th><th>Giá nhập (VNĐ)</th><th>Thành tiền</th><th></th></tr></thead>
          <tbody id="itemsTbody">
            <?php foreach ($chi_tiet as $ct): ?>
            <tr data-sp-id="<?= $ct['sp_id'] ?>">
              <td><?= htmlspecialchars($ct['icon'].' '.$ct['ten']) ?><input type="hidden" name="sp_id[]" value="<?= $ct['sp_id'] ?>"></td>
              <td><input type="number" name="so_luong[]" value="<?= $ct['so_luong'] ?>" min="1" class="admin-input" style="width:80px" onchange="calcTotal()"></td>
              <td><input type="number" name="gia_nhap[]" value="<?= $ct['gia_nhap'] ?>" min="0" class="admin-input" style="width:120px" onchange="calcTotal()"></td>
              <td class="row-total" style="font-weight:700"><?= formatGia($ct['so_luong']*$ct['gia_nhap']) ?></td>
              <td><button type="button" class="btn-danger-sm" onclick="removeRow(this)">✕</button></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot><tr><td colspan="3" style="text-align:right;font-weight:700;padding:10px 14px">Tổng tiền:</td><td id="grandTotal" style="font-weight:800;color:#1B4F9B;padding:10px 14px">0đ</td><td></td></tr></tfoot>
        </table>
      </div>

      <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:8px">
        <button type="submit" name="action" value="save" class="btn-admin-secondary">💾 Lưu nháp</button>
        <button type="submit" name="action" value="complete" class="btn-admin-primary" onclick="return validatePn()">✅ Hoàn thành phiếu nhập</button>
        <a href="nhap-hang.php" class="btn-admin-secondary">Huỷ</a>
      </div>
    </form>
  </div>
</div>

<script>
const spList = <?= json_encode($sp_list) ?>;
const addedIds = new Set(<?= json_encode(array_column($chi_tiet,'sp_id')) ?>);

function searchSp() {
    const q = document.getElementById('spSearchInput').value.toLowerCase().trim();
    const res = document.getElementById('spSearchResults');
    if (!q) { res.innerHTML=''; return; }
    const found = spList.filter(s => s.ten.toLowerCase().includes(q) && !addedIds.has(s.id));
    res.className = 'import-search-results' + (found.length?' has-results':'');
    res.innerHTML = found.slice(0,8).map(s =>
        `<div class="import-prod-chip" onclick="addItem(${s.id},'${s.icon} ${s.ten.replace(/'/g,"\\'")}')">
            ${s.icon} ${s.ten}
         </div>`).join('');
}
function addItem(id, name) {
    if (addedIds.has(id)) return;
    addedIds.add(id);
    const tbody = document.getElementById('itemsTbody');
    const tr = document.createElement('tr');
    tr.dataset.spId = id;
    tr.innerHTML = `
        <td>${name}<input type="hidden" name="sp_id[]" value="${id}"></td>
        <td><input type="number" name="so_luong[]" value="1" min="1" class="admin-input" style="width:80px" onchange="calcTotal()"></td>
        <td><input type="number" name="gia_nhap[]" value="0" min="0" class="admin-input" style="width:120px" onchange="calcTotal()"></td>
        <td class="row-total" style="font-weight:700">0đ</td>
        <td><button type="button" class="btn-danger-sm" onclick="removeRow(this)">✕</button></td>`;
    tbody.appendChild(tr);
    document.getElementById('spSearchInput').value = '';
    document.getElementById('spSearchResults').innerHTML = '';
    calcTotal();
}
function removeRow(btn) {
    const tr = btn.closest('tr');
    const sid = parseInt(tr.dataset.spId);
    addedIds.delete(sid);
    tr.remove();
    calcTotal();
}
function calcTotal() {
    let grand = 0;
    document.querySelectorAll('#itemsTbody tr').forEach(tr => {
        const sl  = parseFloat(tr.querySelector('[name="so_luong[]"]').value)||0;
        const gia = parseFloat(tr.querySelector('[name="gia_nhap[]"]').value)||0;
        const sub = sl * gia;
        grand += sub;
        tr.querySelector('.row-total').textContent = sub.toLocaleString('vi-VN') + 'đ';
    });
    document.getElementById('grandTotal').textContent = grand.toLocaleString('vi-VN') + 'đ';
}
function validatePn() {
    const rows = document.querySelectorAll('#itemsTbody tr');
    if (rows.length === 0) { alert('Thêm ít nhất 1 sản phẩm!'); return false; }
    let ok = true;
    rows.forEach(tr => {
        const sl  = parseFloat(tr.querySelector('[name="so_luong[]"]').value)||0;
        const gia = parseFloat(tr.querySelector('[name="gia_nhap[]"]').value)||0;
        if (sl <= 0 || gia <= 0) { ok = false; }
    });
    if (!ok) { alert('Số lượng và giá nhập phải > 0 cho tất cả sản phẩm!'); return false; }
    return true;
}
calcTotal();
</script>
<?php include 'includes/footer.php'; ?>
