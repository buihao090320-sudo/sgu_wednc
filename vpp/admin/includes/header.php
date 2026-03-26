<?php
// admin/includes/header.php
requireAdmin();
$current = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= isset($page_title) ? htmlspecialchars($page_title).' – ' : '' ?>Admin VPP Sài Gòn</title>
<link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
<div id="adminApp">
<aside class="admin-sidebar">
  <div class="sidebar-brand">
    <img src="../assets/img/logo.png" alt="Logo" style="height:30px;margin-right:8px;">
    <span>VPP Admin</span>
  </div>
  <nav class="admin-nav">
    <a href="index.php" class="nav-item <?= $current==='index.php'?'active':'' ?>"><span class="nav-icon">📊</span> Tổng quan</a>
    <div class="nav-group-label">Quản lý sản phẩm</div>
    <a href="danh-muc.php" class="nav-item <?= $current==='danh-muc.php'?'active':'' ?>"><span class="nav-icon">🗂️</span> Danh mục</a>
    <a href="san-pham.php" class="nav-item <?= $current==='san-pham.php'?'active':'' ?>"><span class="nav-icon">📦</span> Sản phẩm</a>
    <a href="nhap-hang.php" class="nav-item <?= $current==='nhap-hang.php'||$current==='them-phieu-nhap.php'||$current==='sua-phieu-nhap.php'?'active':'' ?>"><span class="nav-icon">📥</span> Nhập hàng</a>
    <a href="quan-ly-gia.php" class="nav-item <?= $current==='quan-ly-gia.php'?'active':'' ?>"><span class="nav-icon">💰</span> Quản lý giá</a>
    <div class="nav-group-label">Kinh doanh</div>
    <a href="don-hang.php" class="nav-item <?= $current==='don-hang.php'?'active':'' ?>"><span class="nav-icon">📋</span> Đơn đặt hàng</a>
    <a href="nguoi-dung.php" class="nav-item <?= $current==='nguoi-dung.php'?'active':'' ?>"><span class="nav-icon">👥</span> Người dùng</a>
    <div class="nav-group-label">Báo cáo</div>
    <a href="ton-kho.php" class="nav-item <?= $current==='ton-kho.php'?'active':'' ?>"><span class="nav-icon">📊</span> Tồn kho & Báo cáo</a>
  </nav>
  <div class="sidebar-footer">
    <span id="adminUserInfo">👤 <?= htmlspecialchars($_SESSION['admin_name'] ?? '') ?></span>
    <a href="logout.php" style="background:rgba(255,255,255,.1);border:none;color:white;padding:7px 12px;border-radius:7px;text-decoration:none;font-size:.8rem;text-align:center">Đăng xuất</a>
  </div>
</aside>
<div class="admin-main">
<div class="admin-topbar">
  <h1><?= isset($page_title) ? htmlspecialchars($page_title) : 'Tổng quan' ?></h1>
  <div><?= $topbar_actions ?? '' ?></div>
</div>
<div class="admin-content">
<?php if (!empty($_SESSION['admin_msg'])): ?>
<div class="alert alert-<?= $_SESSION['admin_msg_type'] ?? 'success' ?>"><?= htmlspecialchars($_SESSION['admin_msg']) ?></div>
<?php unset($_SESSION['admin_msg'], $_SESSION['admin_msg_type']); endif; ?>
