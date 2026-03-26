<?php
// admin/nguoi-dung.php
require_once '../includes/config.php';
$page_title = 'Quản lý người dùng';

// Khoá / Mở khoá
if (isset($_GET['khoa'])) {
    $uid = (int)$_GET['khoa'];
    $conn->query("UPDATE nguoi_dung SET trang_thai=1-trang_thai WHERE id=$uid AND vai_tro='khach_hang'");
    $_SESSION['admin_msg']='✅ Đã cập nhật trạng thái tài khoản!';
    header('Location: nguoi-dung.php'); exit;
}

// Thêm tài khoản
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action_them'])) {
    $ten   = trim($_POST['ho_ten']??'');
    $email = trim($_POST['email']??'');
    $pass  = trim($_POST['mat_khau']??'');
    $sdt   = trim($_POST['so_dien_thoai']??'');
    $dc    = trim($_POST['dia_chi']??'');
    $errors = [];
    if (mb_strlen($ten)<2) $errors[]='Họ tên tối thiểu 2 ký tự';
    if (!filter_var($email,FILTER_VALIDATE_EMAIL)) $errors[]='Email không hợp lệ';
    if (strlen($pass)<4) $errors[]='Mật khẩu tối thiểu 4 ký tự';
    if (empty($errors)) {
        $chk = $conn->prepare("SELECT id FROM nguoi_dung WHERE email=?");
        $chk->bind_param('s',$email); $chk->execute();
        if ($chk->get_result()->num_rows>0) $errors[]='Email đã tồn tại!';
        else {
            $hash = md5($pass);
            $ins = $conn->prepare("INSERT INTO nguoi_dung (ho_ten,email,mat_khau,so_dien_thoai,dia_chi) VALUES (?,?,?,?,?)");
            $ins->bind_param('sssss',$ten,$email,$hash,$sdt,$dc);
            $ins->execute();
            $_SESSION['admin_msg']='✅ Đã tạo tài khoản!';
            header('Location: nguoi-dung.php'); exit;
        }
    }
}

// Reset mật khẩu
if (isset($_GET['reset'])) {
    $uid = (int)$_GET['reset'];
    $newpass = 'Pass'.rand(1000,9999);
    $hash = md5($newpass);
    $conn->query("UPDATE nguoi_dung SET mat_khau='$hash' WHERE id=$uid AND vai_tro='khach_hang'");
    $_SESSION['admin_msg']="✅ Mật khẩu mới: <strong>$newpass</strong>";
    header('Location: nguoi-dung.php'); exit;
}

$q = trim($_GET['q']??'');
$where = "vai_tro='khach_hang'";
if ($q !== '') $where .= " AND (ho_ten LIKE '%".$conn->real_escape_string($q)."%' OR email LIKE '%".$conn->real_escape_string($q)."%')";
$users = $conn->query("SELECT * FROM nguoi_dung WHERE $where ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);
$errors = $errors ?? [];
include 'includes/header.php';
?>
<div class="admin-2col">
  <!-- FORM THÊM -->
  <div class="admin-form-card">
    <h3>Thêm tài khoản</h3>
    <?php if (!empty($errors)): ?><div class="alert alert-danger"><?= implode('<br>',$errors) ?></div><?php endif; ?>
    <form method="POST" novalidate id="nuForm">
      <input type="hidden" name="action_them" value="1">
      <div class="form-group"><label>Họ và tên <span class="req">*</span></label><input type="text" name="ho_ten" value="<?= htmlspecialchars($_POST['ho_ten']??'') ?>" placeholder="Họ và tên" id="nuTen"><span class="err" id="nuTenErr"></span></div>
      <div class="form-group"><label>Email <span class="req">*</span></label><input type="email" name="email" value="<?= htmlspecialchars($_POST['email']??'') ?>" placeholder="email@example.com" id="nuEmail"><span class="err" id="nuEmailErr"></span></div>
      <div class="form-group"><label>Mật khẩu khởi tạo <span class="req">*</span></label><input type="text" name="mat_khau" placeholder="Mật khẩu mặc định" id="nuPass"><span class="err" id="nuPassErr"></span></div>
      <div class="form-group"><label>Số điện thoại</label><input type="tel" name="so_dien_thoai" value="<?= htmlspecialchars($_POST['so_dien_thoai']??'') ?>" placeholder="0901234567"></div>
      <div class="form-group"><label>Địa chỉ</label><input type="text" name="dia_chi" value="<?= htmlspecialchars($_POST['dia_chi']??'') ?>" placeholder="Địa chỉ đầy đủ"></div>
      <button type="submit" class="btn-admin-primary" onclick="return validateNu()">Tạo tài khoản</button>
    </form>
  </div>
  <!-- TABLE -->
  <div>
    <div class="page-toolbar" style="margin-bottom:12px">
      <form method="GET" style="display:flex;gap:10px">
        <input type="text" name="q" class="admin-search" placeholder="🔍 Tìm người dùng..." value="<?= htmlspecialchars($q) ?>">
        <button type="submit" class="btn-admin-primary">Tìm</button>
        <a href="nguoi-dung.php" class="btn-admin-secondary">Xóa</a>
      </form>
    </div>
    <div class="table-wrap">
      <table class="admin-table">
        <thead><tr><th>ID</th><th>Họ tên</th><th>Email</th><th>SĐT</th><th>Trạng thái</th><th>Thao tác</th></tr></thead>
        <tbody>
        <?php if (empty($users)): ?>
          <tr><td colspan="6" style="text-align:center;padding:24px;color:#94a3b8">Chưa có người dùng</td></tr>
        <?php else: ?>
        <?php foreach ($users as $u): ?>
        <tr>
          <td><?= $u['id'] ?></td>
          <td style="font-weight:600"><?= htmlspecialchars($u['ho_ten']) ?></td>
          <td><?= htmlspecialchars($u['email']) ?></td>
          <td><?= htmlspecialchars($u['so_dien_thoai']??'—') ?></td>
          <td><span class="badge badge-<?= $u['trang_thai']?'active':'cancelled' ?>"><?= $u['trang_thai']?'Hoạt động':'Bị khoá' ?></span></td>
          <td class="actions-cell">
            <a href="?khoa=<?= $u['id'] ?>" class="btn-<?= $u['trang_thai']?'danger':'success' ?>-sm"><?= $u['trang_thai']?'🔒 Khoá':'🔓 Mở khoá' ?></a>
            <a href="?reset=<?= $u['id'] ?>" class="btn-edit-sm" onclick="return confirm('Reset mật khẩu tài khoản này?')">🔑 Reset MK</a>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<script>
function validateNu() {
    const re=/^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    let ok=true;
    [['nuTen',v=>v.length>=2,'Họ tên tối thiểu 2 ký tự'],
     ['nuEmail',v=>re.test(v),'Email không hợp lệ'],
     ['nuPass',v=>v.length>=4,'Mật khẩu tối thiểu 4 ký tự']
    ].forEach(([id,fn,msg])=>{
        const v=document.getElementById(id).value.trim();
        const e=document.getElementById(id+'Err');
        if(!fn(v)){e.textContent=msg;ok=false;}else e.textContent='';
    });
    return ok;
}
</script>
<?php include 'includes/footer.php'; ?>
