<?php
// admin/danh-muc.php
require_once '../includes/config.php';
$page_title = 'Quản lý danh mục';

// Xử lý thêm/sửa
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id   = (int)($_POST['id'] ?? 0);
    $ten  = trim($_POST['ten'] ?? '');
    $icon = trim($_POST['icon'] ?? '📦');
    $errors = [];
    if (mb_strlen($ten) < 2) $errors[] = 'Tên tối thiểu 2 ký tự';
    if (!$icon) $errors[] = 'Nhập icon emoji';

    if (empty($errors)) {
        if ($id) {
            $s = $conn->prepare("UPDATE danh_muc SET ten=?,icon=? WHERE id=?");
            $s->bind_param('ssi', $ten, $icon, $id);
        } else {
            $s = $conn->prepare("INSERT INTO danh_muc (ten,icon) VALUES (?,?)");
            $s->bind_param('ss', $ten, $icon);
        }
        $s->execute();
        $_SESSION['admin_msg'] = $id ? 'Đã cập nhật danh mục!' : 'Đã thêm danh mục!';
        header('Location: danh-muc.php'); exit;
    }
}

$edit = null;
if (isset($_GET['sua'])) $edit = $conn->query("SELECT * FROM danh_muc WHERE id=".(int)$_GET['sua'])->fetch_assoc();
if (isset($_GET['xoa'])) {
    $xid = (int)$_GET['xoa'];
    $conn->query("DELETE FROM danh_muc WHERE id=$xid");
    $_SESSION['admin_msg'] = 'Đã xóa danh mục!';
    header('Location: danh-muc.php'); exit;
}

$topbar_actions = '<a href="danh-muc.php" class="btn-admin-primary">+ Thêm danh mục</a>';
include 'includes/header.php';
$list = $conn->query("SELECT d.*,COUNT(s.id) as so_sp FROM danh_muc d LEFT JOIN san_pham s ON s.danh_muc_id=d.id GROUP BY d.id ORDER BY d.id")->fetch_all(MYSQLI_ASSOC);
?>
<div class="admin-2col">
  <!-- FORM -->
  <div class="admin-form-card">
    <h3><?= $edit ? 'Sửa danh mục' : 'Thêm danh mục' ?></h3>
    <?php if (!empty($errors)): ?><div class="alert alert-danger"><?= implode('<br>',$errors) ?></div><?php endif; ?>
    <form method="POST" novalidate id="dmForm">
      <input type="hidden" name="id" value="<?= $edit['id'] ?? '' ?>">
      <div class="form-group">
        <label>Icon (emoji) <span class="req">*</span></label>
        <input type="text" name="icon" value="<?= htmlspecialchars($edit['icon'] ?? $_POST['icon'] ?? '') ?>" placeholder="🗂️" id="dmIcon">
        <span class="err" id="dmIconErr"></span>
      </div>
      <div class="form-group">
        <label>Tên danh mục <span class="req">*</span></label>
        <input type="text" name="ten" value="<?= htmlspecialchars($edit['ten'] ?? $_POST['ten'] ?? '') ?>" placeholder="Tên danh mục" id="dmTen">
        <span class="err" id="dmTenErr"></span>
      </div>
      <div style="display:flex;gap:10px;margin-top:4px">
        <button type="submit" class="btn-admin-primary" onclick="return validateDm()"><?= $edit ? 'Cập nhật' : 'Thêm mới' ?></button>
        <?php if ($edit): ?><a href="danh-muc.php" class="btn-admin-secondary">Huỷ</a><?php endif; ?>
      </div>
    </form>
  </div>
  <!-- TABLE -->
  <div>
    <div class="table-wrap">
      <table class="admin-table">
        <thead><tr><th>ID</th><th>Icon</th><th>Tên danh mục</th><th>Số SP</th><th>Thao tác</th></tr></thead>
        <tbody>
          <?php foreach ($list as $dm): ?>
          <tr>
            <td><?= $dm['id'] ?></td>
            <td style="font-size:1.5rem"><?= htmlspecialchars($dm['icon']) ?></td>
            <td style="font-weight:600"><?= htmlspecialchars($dm['ten']) ?></td>
            <td><?= $dm['so_sp'] ?> sản phẩm</td>
            <td class="actions-cell">
              <a href="?sua=<?= $dm['id'] ?>" class="btn-edit-sm">✏️ Sửa</a>
              <?php if ($dm['so_sp'] == 0): ?>
              <a href="?xoa=<?= $dm['id'] ?>" class="btn-danger-sm" onclick="return confirm('Xóa danh mục này?')">🗑 Xóa</a>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<script>
function validateDm() {
    const icon = document.getElementById('dmIcon').value.trim();
    const ten  = document.getElementById('dmTen').value.trim();
    let ok = true;
    document.getElementById('dmIconErr').textContent = icon ? '' : 'Nhập icon'; if (!icon) ok=false;
    document.getElementById('dmTenErr').textContent  = ten.length>=2 ? '' : 'Tên tối thiểu 2 ký tự'; if (ten.length<2) ok=false;
    return ok;
}
</script>
<?php include 'includes/footer.php'; ?>
