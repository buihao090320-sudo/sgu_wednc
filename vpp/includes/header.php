<?php
// includes/header.php
$cart_count = soLuongGioHang($conn);
$base = '';
// detect if inside /admin subfolder
if (strpos($_SERVER['PHP_SELF'], '/admin/') !== false) $base = '../';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($page_title) ? htmlspecialchars($page_title) . ' – ' : '' ?><?= SITE_NAME ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= $base ?>assets/css/style.css">
</head>
<body>
<header class="header">
  <div class="header-inner">
    <a href="<?= $base ?>index.php" class="logo">
      <img src="<?= $base ?>assets/img/logo.png" alt="Saigon" class="logo-img">
      <div class="logo-text">
        <span class="logo-main">Văn phòng Phẩm</span>
        <span class="logo-tagline">Sài Gòn</span>
      </div>
    </a>
    <nav class="nav">
      <a href="<?= $base ?>index.php">Trang chủ</a>
      <a href="<?= $base ?>san-pham.php">Sản phẩm</a>
    </nav>
    <div class="header-actions">
      <form action="<?= $base ?>san-pham.php" method="GET" class="search-bar">
        <input type="text" name="q" placeholder="Tìm sản phẩm..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
        <button type="submit">🔍</button>
      </form>
      <?php if (!empty($_SESSION['user_id'])): ?>
        <span class="user-greet">👤 <?= htmlspecialchars($_SESSION['user_name']) ?></span>
        <a href="<?= $base ?>gio-hang.php" class="btn-cart">🛒 <span><?= $cart_count ?></span></a>
        <a href="<?= $base ?>don-hang.php" class="btn-text-nav">Đơn hàng</a>
        <a href="<?= $base ?>dang-xuat.php" class="btn-danger">Đăng xuất</a>
      <?php else: ?>
        <a href="<?= $base ?>dang-nhap.php" class="btn-outline">Đăng nhập</a>
        <a href="<?= $base ?>dang-ky.php" class="btn-primary">Đăng ký</a>
      <?php endif; ?>
    </div>
  </div>
</header>
