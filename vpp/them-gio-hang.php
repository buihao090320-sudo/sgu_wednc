<?php
// them-gio-hang.php – Thêm vào giỏ hàng
require_once 'includes/config.php';
requireLogin();
$sp_id    = (int)($_POST['sp_id'] ?? 0);
$so_luong = (int)($_POST['so_luong'] ?? 1);
$redirect = $_POST['redirect'] ?? 'gio-hang.php';
$allowed = ['index.php','san-pham.php','gio-hang.php'];
$is_chi_tiet = preg_match('/^chi-tiet\.php\?id=\d+$/', $redirect);
if (!in_array($redirect, $allowed) && !$is_chi_tiet) {
    $redirect = 'index.php';
}
if ($so_luong < 1) $so_luong = 1;
$uid = (int)$_SESSION['user_id'];

// Kiểm tra sản phẩm tồn tại
$sp = $conn->query("SELECT * FROM san_pham WHERE id=$sp_id AND trang_thai='hien'")->fetch_assoc();
if (!$sp) { $_SESSION['msg'] = 'Sản phẩm không tồn tại!'; $_SESSION['msg_type'] = 'danger'; header('Location: ' . $redirect); exit; }
if ($sp['so_luong_ton'] < $so_luong) { $_SESSION['msg'] = 'Không đủ số lượng tồn kho!'; $_SESSION['msg_type'] = 'danger'; header('Location: ' . $redirect); exit; }

// Thêm hoặc cập nhật giỏ hàng
$chk = $conn->prepare("SELECT id, so_luong FROM gio_hang WHERE nguoi_dung_id=? AND sp_id=?");
$chk->bind_param('ii', $uid, $sp_id);
$chk->execute();
$existing = $chk->get_result()->fetch_assoc();
if ($existing) {
    $new_qty = min($sp['so_luong_ton'], $existing['so_luong'] + $so_luong);
    $upd = $conn->prepare("UPDATE gio_hang SET so_luong=? WHERE id=?");
    $upd->bind_param('ii', $new_qty, $existing['id']);
    $upd->execute();
} else {
    $ins = $conn->prepare("INSERT INTO gio_hang (nguoi_dung_id,sp_id,so_luong) VALUES (?,?,?)");
    $ins->bind_param('iii', $uid, $sp_id, $so_luong);
    $ins->execute();
}
$_SESSION['msg'] = '✅ Đã thêm vào giỏ hàng!';
$_SESSION['msg_type'] = 'success';
header('Location: ' . $redirect);
exit;
