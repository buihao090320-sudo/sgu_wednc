<?php
// gio-hang.php
require_once 'includes/config.php';
requireLogin();
$base = '';
$page_title = 'Giỏ hàng';
$uid = (int)$_SESSION['user_id'];

// Xử lý cập nhật / xóa
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $gh_id  = (int)($_POST['gh_id'] ?? 0);
    $sl     = (int)($_POST['so_luong'] ?? 1);

    if ($action === 'update' && $gh_id) {
        if ($sl < 1) $sl = 1;
        // Kiểm tra tồn kho
        $sp_row = $conn->query("SELECT s.so_luong_ton FROM gio_hang g JOIN san_pham s ON g.sp_id=s.id WHERE g.id=$gh_id AND g.nguoi_dung_id=$uid")->fetch_assoc();
        if ($sp_row) {
            $sl = min($sl, $sp_row['so_luong_ton']);
            $conn->query("UPDATE gio_hang SET so_luong=$sl WHERE id=$gh_id AND nguoi_dung_id=$uid");
        }
    }
    if ($action === 'delete' && $gh_id) {
        $conn->query("DELETE FROM gio_hang WHERE id=$gh_id AND nguoi_dung_id=$uid");
    }
}

// Lấy giỏ hàng
$gh_res = $conn->query("SELECT g.*, s.ten, s.icon, s.gia_nhap, s.ti_le_loi_nhuan, s.so_luong_ton, s.don_vi_tinh FROM gio_hang g JOIN san_pham s ON g.sp_id=s.id WHERE g.nguoi_dung_id=$uid ORDER BY g.id");
$items  = $gh_res->fetch_all(MYSQLI_ASSOC);
$subtotal = 0;
foreach ($items as &$it) {
    $it['gia_ban'] = giaBan($it['gia_nhap'], $it['ti_le_loi_nhuan']);
    $subtotal += $it['gia_ban'] * $it['so_luong'];
}
unset($it);
$total = $subtotal + PHI_GIAO_HANG;

// Thông tin người dùng
$user = $conn->query("SELECT * FROM nguoi_dung WHERE id=$uid")->fetch_assoc();

// Tỉnh/quận
$tinh_thanh_list = ['Hồ Chí Minh','Hà Nội','Đà Nẵng','Cần Thơ','Hải Phòng','Bình Dương','Đồng Nai'];
$quan_huyen_map  = ['Hồ Chí Minh'=>['Quận 1','Quận 2','Quận 3','Quận 4','Quận 5','Bình Thạnh','Gò Vấp','Tân Bình','Thủ Đức'],'Hà Nội'=>['Ba Đình','Hoàn Kiếm','Cầu Giấy','Đống Đa','Thanh Xuân','Hà Đông'],'Đà Nẵng'=>['Hải Châu','Thanh Khê','Sơn Trà'],'Cần Thơ'=>['Ninh Kiều','Bình Thủy'],'Hải Phòng'=>['Hồng Bàng','Ngô Quyền'],'Bình Dương'=>['Thủ Dầu Một','Thuận An','Dĩ An'],'Đồng Nai'=>['Biên Hòa','Long Khánh']];

$msg = $_SESSION['msg'] ?? ''; $msg_type = $_SESSION['msg_type'] ?? '';
unset($_SESSION['msg'], $_SESSION['msg_type']);

include 'includes/header.php';
?>
<div class="cart-wrap">
  <h2 class="cart-title">🛒 Giỏ hàng</h2>
  <?php if ($msg): ?><div class="alert alert-<?= $msg_type ?>"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

  <?php if (empty($items)): ?>
  <div class="empty-cart">
    <div class="e-icon">🛒</div>
    <p>Giỏ hàng của bạn đang trống</p>
    <a href="san-pham.php" class="btn-primary">Khám phá sản phẩm</a>
  </div>
  <?php else: ?>
  <div class="cart-layout">
    <!-- GIỎ HÀNG -->
    <div class="cart-items-col">
      <?php foreach ($items as $it): ?>
      <div class="cart-item">
        <div class="ci-img"><?= htmlspecialchars($it['icon'] ?? '📦') ?></div>
        <div>
          <div class="ci-name"><a href="chi-tiet.php?id=<?= $it['sp_id'] ?>"><?= htmlspecialchars($it['ten']) ?></a></div>
          <div class="ci-unit"><?= formatGia($it['gia_ban']) ?> / <?= htmlspecialchars($it['don_vi_tinh']) ?></div>
          <form method="POST" class="ci-qty" onsubmit="return false">
            <input type="hidden" name="gh_id" value="<?= $it['id'] ?>">
            <input type="hidden" name="action" value="update">
            <button type="button" class="qty-b" onclick="updateQty(this,-1,<?= $it['so_luong_ton'] ?>)">−</button>
            <input type="number" name="so_luong" value="<?= $it['so_luong'] ?>" min="1" max="<?= $it['so_luong_ton'] ?>" class="ci-qty-input" onchange="submitQtyForm(this)">
            <button type="button" class="qty-b" onclick="updateQty(this,1,<?= $it['so_luong_ton'] ?>)">+</button>
          </form>
        </div>
        <div class="ci-right">
          <div class="ci-total"><?= formatGia($it['gia_ban'] * $it['so_luong']) ?></div>
          <form method="POST">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="gh_id" value="<?= $it['id'] ?>">
            <button type="submit" class="ci-rm" title="Xóa">🗑</button>
          </form>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- THANH TOÁN -->
    <div class="cart-aside">
      <form action="dat-hang.php" method="POST" id="checkoutForm" novalidate>
        <div class="checkout-box">
          <h3>Thông tin đặt hàng</h3>

          <!-- ĐỊA CHỈ -->
          <div class="form-group">
            <label>Địa chỉ giao hàng</label>
            <div class="addr-choice">
              <label class="radio-inline"><input type="radio" name="addr_opt" value="saved" checked onchange="toggleAddr()"> Từ tài khoản</label>
              <label class="radio-inline"><input type="radio" name="addr_opt" value="new" onchange="toggleAddr()"> Nhập mới</label>
            </div>
          </div>
          <div id="savedAddr" class="addr-box">
            <strong><?= htmlspecialchars($user['ho_ten']) ?></strong><br>
            📞 <?= htmlspecialchars($user['so_dien_thoai'] ?? '—') ?><br>
            📍 <?= htmlspecialchars(($user['dia_chi'] ?? '') . ', ' . ($user['quan_huyen'] ?? '') . ', ' . ($user['tinh_thanh'] ?? '')) ?>
          </div>
          <div id="newAddrForm" style="display:none">
            <div class="form-group"><label>Họ tên người nhận <span class="req">*</span></label><input type="text" name="ten_nn" id="nTen" placeholder="Họ và tên"><span class="err" id="nTenErr"></span></div>
            <div class="form-group"><label>Số điện thoại <span class="req">*</span></label><input type="tel" name="sdt_nn" id="nSdt" placeholder="0901234567"><span class="err" id="nSdtErr"></span></div>
            <div class="form-group">
              <label>Tỉnh/Thành phố <span class="req">*</span></label>
              <select name="tinh_nn" id="nTinh" onchange="updateQuanNew()">
                <option value="">Chọn tỉnh/thành</option>
                <?php foreach ($tinh_thanh_list as $t): ?><option value="<?= htmlspecialchars($t) ?>"><?= htmlspecialchars($t) ?></option><?php endforeach; ?>
              </select>
              <span class="err" id="nTinhErr"></span>
            </div>
            <div class="form-group"><label>Quận/Huyện <span class="req">*</span></label><select name="quan_nn" id="nQuan"><option value="">Chọn quận/huyện</option></select><span class="err" id="nQuanErr"></span></div>
            <div class="form-group"><label>Địa chỉ cụ thể <span class="req">*</span></label><input type="text" name="dia_chi_nn" id="nDiaChi" placeholder="Số nhà, tên đường"><span class="err" id="nDiaChiErr"></span></div>
          </div>

          <div class="co-divider"></div>

          <!-- THANH TOÁN -->
          <div class="form-group">
            <label>Phương thức thanh toán</label>
            <div class="pay-opts">
              <label class="pay-opt"><input type="radio" name="phuong_thuc_tt" value="tien_mat" checked onchange="showTT()"><span>💵 Tiền mặt COD</span></label>
              <label class="pay-opt"><input type="radio" name="phuong_thuc_tt" value="chuyen_khoan" onchange="showTT()"><span>🏦 Chuyển khoản</span></label>
              <label class="pay-opt"><input type="radio" name="phuong_thuc_tt" value="truc_tuyen" onchange="showTT()"><span>💳 Thanh toán trực tuyến</span></label>
            </div>
          </div>
          <div id="ttInfo"></div>

          <div class="co-divider"></div>
          <div class="summary-rows">
            <div class="sum-row"><span>Tạm tính</span><span><?= formatGia($subtotal) ?></span></div>
            <div class="sum-row"><span>Phí giao hàng</span><span><?= formatGia(PHI_GIAO_HANG) ?></span></div>
            <div class="sum-row sum-total"><span>Tổng cộng</span><span><?= formatGia($total) ?></span></div>
          </div>
          <button type="submit" class="btn-primary full btn-place-order" onclick="return validateCheckout()">Đặt hàng ngay →</button>
        </div>
      </form>
    </div>
  </div>
  <?php endif; ?>
</div>

<script>
const quanHuyenMap = <?= json_encode($quan_huyen_map) ?>;

function toggleAddr() {
    const v = document.querySelector('input[name=addr_opt]:checked').value;
    document.getElementById('savedAddr').style.display    = v === 'saved' ? 'block' : 'none';
    document.getElementById('newAddrForm').style.display  = v === 'new'   ? 'block' : 'none';
}
function updateQuanNew() {
    const tinh = document.getElementById('nTinh').value;
    const sel  = document.getElementById('nQuan');
    sel.innerHTML = '<option value="">Chọn quận/huyện</option>';
    (quanHuyenMap[tinh]||[]).forEach(q => { const o=document.createElement('option'); o.value=o.textContent=q; sel.appendChild(o); });
}
function showTT() {
    const v = document.querySelector('input[name=phuong_thuc_tt]:checked').value;
    const box = document.getElementById('ttInfo');
    if (v === 'chuyen_khoan') {
        box.innerHTML = '<div class="pay-info-box">🏦 <strong>Vietcombank</strong><br>STK: <strong>1234 5678 9012 3456</strong><br>CTK: <strong>CONG TY VPP SAI GON</strong><br>ND: <em>Mã đơn hàng sau khi đặt</em></div>';
    } else if (v === 'truc_tuyen') {
        box.innerHTML = '<div class="pay-info-box" style="background:#eff6ff;border-color:#93c5fd">💳 Chức năng thanh toán trực tuyến đang được tích hợp.</div>';
    } else box.innerHTML = '';
}
function updateQty(btn, delta, max) {
    const form  = btn.closest('form');
    const input = form.querySelector('.ci-qty-input');
    const newV  = Math.max(1, Math.min(max, parseInt(input.value)+delta));
    input.value = newV;
    submitQtyForm(input);
}
function submitQtyForm(input) {
    const form = input.closest('form');
    const formData = new FormData(form);
    fetch('gio-hang.php', { method:'POST', body: formData })
      .then(() => location.reload());
}
function validateCheckout() {
    const isNew = document.querySelector('input[name=addr_opt]:checked')?.value === 'new';
    if (!isNew) return true;
    let ok = true;
    [['nTen',v=>v.length>=2,'Họ tên tối thiểu 2 ký tự'],
     ['nSdt',v=>/^[0-9]{10,11}$/.test(v),'SĐT 10-11 số'],
     ['nTinh',v=>v!=='','Chọn tỉnh/thành'],
     ['nQuan',v=>v!=='','Chọn quận/huyện'],
     ['nDiaChi',v=>v.length>=5,'Địa chỉ tối thiểu 5 ký tự']
    ].forEach(([id,fn,msg])=>{ const v=document.getElementById(id).value.trim(); const e=document.getElementById(id+'Err'); if(!fn(v)){e.textContent=msg;ok=false;}else e.textContent=''; });
    return ok;
}
</script>
<?php include 'includes/footer.php'; ?>
