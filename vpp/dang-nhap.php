<?php
// dang-nhap.php
require_once 'includes/config.php';
$base = '';
$page_title = 'Đăng nhập';
$error = '';
$redirect = $_GET['redirect'] ?? 'index.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['mat_khau'] ?? '';
    $err_client = false;

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $error = 'Email không hợp lệ'; $err_client = true; }
    elseif (strlen($pass) < 6) { $error = 'Mật khẩu ít nhất 6 ký tự'; $err_client = true; }

    if (!$err_client) {
        $stmt = $conn->prepare("SELECT * FROM nguoi_dung WHERE email=? AND vai_tro='khach_hang'");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        if (!$user || $user['mat_khau'] !== md5($pass)) {
            $error = 'Email hoặc mật khẩu không đúng!';
        } elseif ($user['trang_thai'] == 0) {
            $error = 'Tài khoản đã bị khoá. Vui lòng liên hệ hỗ trợ.';
        } else {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_name'] = $user['ho_ten'];
            header('Location: ' . $redirect);
            exit;
        }
    }
}
include 'includes/header.php';
?>
<div class="auth-page">
  <div class="auth-card">
    <img src="assets/img/logo.png" alt="Logo" class="auth-logo-img">
    <h2>Đăng nhập</h2>
    <p class="auth-sub">Chào mừng quay lại!</p>
    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="POST" novalidate id="loginForm">
      <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>">
      <div class="form-group">
        <label>Email <span class="req">*</span></label>
        <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="email@example.com" id="loginEmail">
        <span class="err" id="loginEmailErr"></span>
      </div>
      <div class="form-group">
        <label>Mật khẩu <span class="req">*</span></label>
        <input type="password" name="mat_khau" placeholder="Ít nhất 6 ký tự" id="loginPass">
        <span class="err" id="loginPassErr"></span>
      </div>
      <button type="submit" class="btn-primary full" style="margin-top:10px" onclick="return validateLogin()">Đăng nhập</button>
    </form>
    <p class="auth-switch">Chưa có tài khoản? <a href="dang-ky.php">Đăng ký ngay</a></p>
  </div>
</div>
<script>
function validateLogin() {
    let ok = true;
    const email = document.getElementById('loginEmail').value.trim();
    const pass  = document.getElementById('loginPass').value;
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    document.getElementById('loginEmailErr').textContent = re.test(email) ? '' : 'Email không hợp lệ';
    document.getElementById('loginPassErr').textContent  = pass.length >= 6  ? '' : 'Mật khẩu ít nhất 6 ký tự';
    if (!re.test(email) || pass.length < 6) ok = false;
    return ok;
}
</script>
<?php include 'includes/footer.php'; ?>
