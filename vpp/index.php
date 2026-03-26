
Copier

<?php
require_once 'includes/config.php';
require_once 'includes/image_helper.php';
$page_title = 'Trang chủ';
$base = '';
include 'includes/header.php';
 
// Lấy danh mục + số sản phẩm
$dm_res = $conn->query("SELECT d.*, COUNT(s.id) as so_sp FROM danh_muc d LEFT JOIN san_pham s ON s.danh_muc_id=d.id AND s.trang_thai='hien' GROUP BY d.id ORDER BY d.id");
 
// Sản phẩm nổi bật (8 sp mới nhất đang bán)
$sp_res = $conn->query("SELECT s.*, d.ten as ten_danh_muc FROM san_pham s JOIN danh_muc d ON s.danh_muc_id=d.id WHERE s.trang_thai='hien' AND s.so_luong_ton>0 ORDER BY s.id DESC LIMIT 8");
?>
 
<section class="hero">
  <div class="hero-content">
    <div class="hero-badge">✏️ Hơn 500+ sản phẩm văn phòng phẩm</div>
    <h1>Mua sắm văn phòng<br><em>phẩm dễ dàng hơn</em></h1>
    <p>Đa dạng sản phẩm bút, vở, dụng cụ văn phòng từ các thương hiệu uy tín. Giao hàng nhanh toàn quốc.</p>
    <div class="hero-ctas">
      <a href="san-pham.php" class="btn-hero">Mua ngay →</a>
      <a href="san-pham.php" class="btn-hero-ghost">Xem danh mục</a>
    </div>
  </div>
  <div class="hero-deco">
    <div class="deco-items">
      <span>✏️</span><span>📒</span><span>📐</span>
      <span>🖊️</span><span>📎</span><span>🗂️</span>
    </div>
  </div>
</section>
 
<div class="trust-strip">
  <div class="trust-strip-inner">
    <span>🚚 Giao hàng toàn quốc</span>
    <span>✅ Hàng chính hãng</span>
    <span>🔄 Đổi trả 7 ngày</span>
    <span>💬 Hỗ trợ 24/7</span>
  </div>
</div>
 
<div class="home-wrap">
  <div class="section-head">
    <h2>Danh mục sản phẩm</h2>
    <a href="san-pham.php" class="see-all">Xem tất cả →</a>
  </div>
  <div class="cat-grid">
    <?php while ($dm = $dm_res->fetch_assoc()): ?>
    <a href="san-pham.php?dm=<?= $dm['id'] ?>" class="cat-card">
      <div class="c-icon"><?= htmlspecialchars($dm['icon']) ?></div>
      <div class="c-name"><?= htmlspecialchars($dm['ten']) ?></div>
      <div class="c-count"><?= $dm['so_sp'] ?> sản phẩm</div>
    </a>
    <?php endwhile; ?>
  </div>
 
  <div class="section-head" style="margin-top:56px">
    <h2>Sản phẩm nổi bật</h2>
    <a href="san-pham.php" class="see-all">Xem tất cả →</a>
  </div>
  <div class="product-grid">
    <?php while ($sp = $sp_res->fetch_assoc()):
      $gia = giaBan($sp['gia_nhap'], $sp['ti_le_loi_nhuan']);
    ?>
    <div class="product-card" onclick="location.href='chi-tiet.php?id=<?= $sp['id'] ?>'">
      <div class="p-img">
        <div class="p-badge"><?= htmlspecialchars($sp['ten_danh_muc']) ?></div>
        <?= renderProductImage($sp['image'] ?? null, $sp['icon'] ?? '📦', '') ?>
      </div>
      <div class="p-info">
        <div class="p-cat"><?= htmlspecialchars($sp['ten_danh_muc']) ?></div>
        <div class="p-name"><?= htmlspecialchars($sp['ten']) ?></div>
        <div class="p-price"><?= formatGia($gia) ?></div>
        <div class="p-stock <?= $sp['so_luong_ton'] == 0 ? 'out' : '' ?>">
          <?= $sp['so_luong_ton'] > 0 ? '✓ Còn ' . $sp['so_luong_ton'] . ' sp' : '✗ Hết hàng' ?>
        </div>
        <form action="them-gio-hang.php" method="POST" onclick="event.stopPropagation()">
          <input type="hidden" name="sp_id" value="<?= $sp['id'] ?>">
          <input type="hidden" name="redirect" value="index.php">
          <button type="submit" class="btn-primary p-btn" <?= $sp['so_luong_ton'] == 0 ? 'disabled' : '' ?>>
            <?= $sp['so_luong_ton'] > 0 ? '+ Thêm vào giỏ' : 'Hết hàng' ?>
          </button>
        </form>
      </div>
    </div>
    <?php endwhile; ?>
  </div>
</div>
 
<?php include 'includes/footer.php'; ?>