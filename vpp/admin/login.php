<?php
// admin/login.php
require_once '../includes/config.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['mat_khau'] ?? '';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $error = 'Email không hợp lệ'; }
    elseif (strlen($pass) < 6) { $error = 'Mật khẩu ít nhất 6 ký tự'; }
    else {
        $stmt = $conn->prepare("SELECT * FROM nguoi_dung WHERE email=? AND vai_tro='admin' AND trang_thai=1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $admin = $stmt->get_result()->fetch_assoc();
        if (!$admin || $admin['mat_khau'] !== md5($pass)) {
            $error = 'Tài khoản hoặc mật khẩu không đúng!';
        } else {
            $_SESSION['admin_id']   = $admin['id'];
            $_SESSION['admin_name'] = $admin['ho_ten'];
            header('Location: index.php'); exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin Login – VPP Sài Gòn</title>
<link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
<div class="admin-login-wrap">
  <div class="admin-login-card">
    <img src="../assets/img/logo.png" alt="Logo" style="height:50px;margin-bottom:20px">
    <h2>Quản trị hệ thống</h2>
    <p class="login-sub">Đăng nhập bằng tài khoản Admin</p>
    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="POST" novalidate id="adminLoginForm">
      <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="admin@vppsaigon.vn" id="aEmail">
        <span class="err" id="aEmailErr"></span>
      </div>
      <div class="form-group">
        <label>Mật khẩu</label>
        <input type="password" name="mat_khau" placeholder="••••••" id="aPass">
        <span class="err" id="aPassErr"></span>
      </div>
      <button type="submit" class="btn-admin-primary full" style="margin-top:10px" onclick="return validateAdminLogin()">Đăng nhập</button>
    </form>
    <p style="margin-top:12px;font-size:.78rem;color:#94a3b8">Mặc định: <strong>admin@vppsaigon.vn</strong> / <strong>admin123</strong></p>
  </div>
</div>
<script>
function validateAdminLogin() {
    const email = document.getElementById('aEmail').value.trim();
    const pass  = document.getElementById('aPass').value;
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    let ok = true;
    document.getElementById('aEmailErr').textContent = re.test(email) ? '' : 'Email không hợp lệ'; if (!re.test(email)) ok=false;
    document.getElementById('aPassErr').textContent  = pass.length >= 6 ? '' : 'Mật khẩu ít nhất 6 ký tự'; if (pass.length < 6) ok=false;
    return ok;
}
</script>
</body></html>
