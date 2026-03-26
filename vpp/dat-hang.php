<?php
// dat-hang.php – Xử lý đặt hàng
require_once 'includes/config.php';
requireLogin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: gio-hang.php'); exit; }

$uid = (int)$_SESSION['user_id'];
$user = $conn->query("SELECT * FROM nguoi_dung WHERE id=$uid")->fetch_assoc();

// Địa chỉ giao hàng
$addr_opt = $_POST['addr_opt'] ?? 'saved';
if ($addr_opt === 'saved') {
    $ten_nn  = $user['ho_ten'];
    $sdt_nn  = $user['so_dien_thoai'];
    $dia_chi = $user['dia_chi'];
    $quan    = $user['quan_huyen'];
    $tinh    = $user['tinh_thanh'];
} else {
    $ten_nn  = trim($_POST['ten_nn'] ?? '');
    $sdt_nn  = trim($_POST['sdt_nn'] ?? '');
    $dia_chi = trim($_POST['dia_chi_nn'] ?? '');
    $quan    = trim($_POST['quan_nn'] ?? '');
    $tinh    = trim($_POST['tinh_nn'] ?? '');
    // Validate
    if (!$ten_nn || !preg_match('/^[0-9]{10,11}$/', $sdt_nn) || !$tinh || !$quan || strlen($dia_chi) < 5) {
        $_SESSION['msg'] = 'Vui lòng nhập đầy đủ địa chỉ giao hàng!';
        $_SESSION['msg_type'] = 'danger';
        header('Location: gio-hang.php'); exit;
    }
}

$phuong_thuc = $_POST['phuong_thuc_tt'] ?? 'tien_mat';
$allowed_tt  = ['tien_mat','chuyen_khoan','truc_tuyen'];
if (!in_array($phuong_thuc, $allowed_tt)) $phuong_thuc = 'tien_mat';

// Lấy giỏ hàng
$gh_res = $conn->query("SELECT g.*, s.ten, s.gia_nhap, s.ti_le_loi_nhuan, s.so_luong_ton FROM gio_hang g JOIN san_pham s ON g.sp_id=s.id WHERE g.nguoi_dung_id=$uid");
$items = $gh_res->fetch_all(MYSQLI_ASSOC);
if (empty($items)) { header('Location: gio-hang.php'); exit; }

// Kiểm tra tồn kho
foreach ($items as $it) {
    if ($it['so_luong'] > $it['so_luong_ton']) {
        $_SESSION['msg'] = "Sản phẩm \"{$it['ten']}\" không đủ tồn kho!";
        $_SESSION['msg_type'] = 'danger';
        header('Location: gio-hang.php'); exit;
    }
}

$subtotal = 0;
foreach ($items as $it) $subtotal += giaBan($it['gia_nhap'],$it['ti_le_loi_nhuan']) * $it['so_luong'];
$phi_gh = PHI_GIAO_HANG;
$tong   = $subtotal + $phi_gh;
$ma_don = 'VPP' . date('YmdHis') . rand(10,99);
$dia_chi_giao = "$dia_chi, $quan, $tinh";

// Tạo đơn hàng
$ins = $conn->prepare("INSERT INTO don_hang (ma_don,nguoi_dung_id,ten_nguoi_nhan,so_dien_thoai,dia_chi_giao,quan_huyen,tinh_thanh,phuong_thuc_tt,tam_tinh,phi_giao_hang,tong_tien) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
$ins->bind_param('sissssssddd', $ma_don,$uid,$ten_nn,$sdt_nn,$dia_chi_giao,$quan,$tinh,$phuong_thuc,$subtotal,$phi_gh,$tong);
$ins->execute();
$don_id = $conn->insert_id;

// Thêm chi tiết đơn hàng + trừ tồn kho
foreach ($items as $it) {
    $gia = giaBan($it['gia_nhap'], $it['ti_le_loi_nhuan']);
    $thanh_tien = $gia * $it['so_luong'];
    $ins2 = $conn->prepare("INSERT INTO chi_tiet_don_hang (don_hang_id,sp_id,ten_sp,so_luong,gia_ban,thanh_tien) VALUES (?,?,?,?,?,?)");
    $ins2->bind_param('iisidd', $don_id, $it['sp_id'], $it['ten'], $it['so_luong'], $gia, $thanh_tien);
    $ins2->execute();
    $conn->query("UPDATE san_pham SET so_luong_ton=so_luong_ton-{$it['so_luong']} WHERE id={$it['sp_id']}");
}

// Xóa giỏ hàng
$conn->query("DELETE FROM gio_hang WHERE nguoi_dung_id=$uid");

$_SESSION['last_order'] = $don_id;
header('Location: dat-hang-thanh-cong.php');
exit;
