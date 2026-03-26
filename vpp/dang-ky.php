<?php
// dang-ky.php
require_once 'includes/config.php';
$base = '';
$page_title = 'Đăng ký tài khoản';
$error = '';
$success = '';

$tinh_thanh_list = ['Hồ Chí Minh','Hà Nội','Đà Nẵng','Cần Thơ','Hải Phòng','Bình Dương','Đồng Nai'];
$quan_huyen_map = [
    'Hồ Chí Minh' => ['Quận 1','Quận 2','Quận 3','Quận 4','Quận 5','Quận 6','Quận 7','Bình Thạnh','Gò Vấp','Tân Bình','Tân Phú','Thủ Đức'],
    'Hà Nội'      => ['Ba Đình','Hoàn Kiếm','Cầu Giấy','Đống Đa','Hai Bà Trưng','Hoàng Mai','Thanh Xuân','Hà Đông'],
    'Đà Nẵng'     => ['Hải Châu','Thanh Khê','Sơn Trà','Ngũ Hành Sơn','Liên Chiểu'],
    'Cần Thơ'     => ['Ninh Kiều','Bình Thủy','Cái Răng'],
    'Hải Phòng'   => ['Hồng Bàng','Ngô Quyền','Lê Chân'],
    'Bình Dương'  => ['Thủ Dầu Một','Thuận An','Dĩ An'],
    'Đồng Nai'    => ['Biên Hòa','Long Khánh'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ho_ten   = trim($_POST['ho_ten'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $pass     = $_POST['mat_khau'] ?? '';
    $pass2    = $_POST['mat_khau2'] ?? '';
    $sdt      = trim($_POST['so_dien_thoai'] ?? '');
    $tinh     = trim($_POST['tinh_thanh'] ?? '');
    $quan     = trim($_POST['quan_huyen'] ?? '');
    $dia_chi  = trim($_POST['dia_chi'] ?? '');

    // Validate
    $errors = [];
    if (mb_strlen($ho_ten) < 2) $errors[] = 'Họ tên tối thiểu 2 ký tự';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email không hợp lệ';
    if (strlen($pass) < 6) $errors[] = 'Mật khẩu ít nhất 6 ký tự';
    if ($pass !== $pass2) $errors[] = 'Mật khẩu xác nhận không khớp';
    if (!preg_match('/^[0-9]{10,11}$/', $sdt)) $errors[] = 'Số điện thoại phải 10-11 số';
    if (!$tinh) $errors[] = 'Chọn tỉnh/thành phố';
    if (!$quan) $errors[] = 'Chọn quận/huyện';
    if (mb_strlen($dia_chi) < 5) $errors[] = 'Địa chỉ tối thiểu 5 ký tự';

    if (empty($errors)) {
        $chk = $conn->prepare("SELECT id FROM nguoi_dung WHERE email=?");
        $chk->bind_param('s', $email);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) {
            $errors[] = 'Email đã được đăng ký!';
        } else {
            $hash = md5($pass);
            $ins = $conn->prepare("INSERT INTO nguoi_dung (ho_ten,email,mat_khau,so_dien_thoai,dia_chi,quan_huyen,tinh_thanh) VALUES (?,?,?,?,?,?,?)");
            $ins->bind_param('sssssss', $ho_ten, $email, $hash, $sdt, $dia_chi, $quan, $tinh);
            if ($ins->execute()) {
                $_SESSION['msg'] = 'Đăng ký thành công! Vui lòng đăng nhập.';
                header('Location: dang-nhap.php');
                exit;
            } else {
                $errors[] = 'Có lỗi xảy ra, vui lòng thử lại.';
            }
        }
    }
    if (!empty($errors)) $error = implode('<br>', $errors);
}
include 'includes/header.php';
$old = $_POST;
?>
<div class="auth-page" style="align-items:flex-start;padding:40px 24px">
  <div class="auth-card wide">
    <img src="assets/img/logo.png" alt="Logo" class="auth-logo-img">
    <h2>Tạo tài khoản</h2>
    <p class="auth-sub">Điền đầy đủ thông tin để đặt hàng</p>
    <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
    <form method="POST" novalidate id="regForm">
      <div class="form-section-lbl">Thông tin cá nhân</div>
      <div class="form-row">
        <div class="form-group">
          <label>Họ và tên <span class="req">*</span></label>
          <input type="text" name="ho_ten" value="<?= htmlspecialchars($old['ho_ten'] ?? '') ?>" placeholder="Nguyễn Văn A" id="rHoTen">
          <span class="err" id="rHoTenErr"></span>
        </div>
        <div class="form-group">
          <label>Email <span class="req">*</span></label>
          <input type="email" name="email" value="<?= htmlspecialchars($old['email'] ?? '') ?>" placeholder="email@example.com" id="rEmail">
          <span class="err" id="rEmailErr"></span>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Mật khẩu <span class="req">*</span></label>
          <input type="password" name="mat_khau" placeholder="Ít nhất 6 ký tự" id="rPass">
          <span class="err" id="rPassErr"></span>
        </div>
        <div class="form-group">
          <label>Xác nhận mật khẩu <span class="req">*</span></label>
          <input type="password" name="mat_khau2" placeholder="Nhập lại mật khẩu" id="rPass2">
          <span class="err" id="rPass2Err"></span>
        </div>
      </div>
      <div class="form-section-lbl">Địa chỉ giao hàng</div>
      <div class="form-row">
        <div class="form-group">
          <label>Số điện thoại <span class="req">*</span></label>
          <input type="tel" name="so_dien_thoai" value="<?= htmlspecialchars($old['so_dien_thoai'] ?? '') ?>" placeholder="0901234567" id="rSdt">
          <span class="err" id="rSdtErr"></span>
        </div>
        <div class="form-group">
          <label>Tỉnh/Thành phố <span class="req">*</span></label>
          <select name="tinh_thanh" id="rTinh" onchange="updateQuan()">
            <option value="">Chọn tỉnh/thành</option>
            <?php foreach ($tinh_thanh_list as $t): ?>
            <option value="<?= htmlspecialchars($t) ?>" <?= ($old['tinh_thanh'] ?? '') === $t ? 'selected' : '' ?>><?= htmlspecialchars($t) ?></option>
            <?php endforeach; ?>
          </select>
          <span class="err" id="rTinhErr"></span>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Quận/Huyện <span class="req">*</span></label>
          <select name="quan_huyen" id="rQuan">
            <option value="">Chọn quận/huyện</option>
          </select>
          <span class="err" id="rQuanErr"></span>
        </div>
        <div class="form-group">
          <label>Địa chỉ cụ thể <span class="req">*</span></label>
          <input type="text" name="dia_chi" value="<?= htmlspecialchars($old['dia_chi'] ?? '') ?>" placeholder="Số nhà, tên đường" id="rDiaChi">
          <span class="err" id="rDiaChiErr"></span>
        </div>
      </div>
      <button type="submit" class="btn-primary full" style="margin-top:10px" onclick="return validateReg()">Tạo tài khoản</button>
    </form>
    <p class="auth-switch">Đã có tài khoản? <a href="dang-nhap.php">Đăng nhập</a></p>
  </div>
</div>

<script>
const quanHuyenMap = <?= json_encode($quan_huyen_map) ?>;
const oldQuan = "<?= htmlspecialchars($old['quan_huyen'] ?? '') ?>";

function updateQuan() {
    const tinh = document.getElementById('rTinh').value;
    const sel  = document.getElementById('rQuan');
    sel.innerHTML = '<option value="">Chọn quận/huyện</option>';
    (quanHuyenMap[tinh] || []).forEach(q => {
        const o = document.createElement('option');
        o.value = o.textContent = q;
        if (q === oldQuan) o.selected = true;
        sel.appendChild(o);
    });
}
updateQuan();

function validateReg() {
    let ok = true;
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    const fields = [
        ['rHoTen',   v => v.length >= 2,                 'Họ tên tối thiểu 2 ký tự'],
        ['rEmail',   v => re.test(v),                    'Email không hợp lệ'],
        ['rPass',    v => v.length >= 6,                 'Mật khẩu ít nhất 6 ký tự'],
        ['rSdt',     v => /^[0-9]{10,11}$/.test(v),     'SĐT phải 10-11 số'],
        ['rTinh',    v => v !== '',                      'Chọn tỉnh/thành'],
        ['rQuan',    v => v !== '',                      'Chọn quận/huyện'],
        ['rDiaChi',  v => v.length >= 5,                 'Địa chỉ tối thiểu 5 ký tự'],
    ];
    fields.forEach(([id, fn, msg]) => {
        const val = document.getElementById(id).value.trim();
        const err = document.getElementById(id + 'Err');
        if (!fn(val)) { err.textContent = msg; ok = false; } else err.textContent = '';
    });
    const p2 = document.getElementById('rPass2');
    const p2e = document.getElementById('rPass2Err');
    if (p2.value !== document.getElementById('rPass').value) {
        p2e.textContent = 'Mật khẩu không khớp'; ok = false;
    } else p2e.textContent = '';
    return ok;
}
</script>
<?php include 'includes/footer.php'; ?>
