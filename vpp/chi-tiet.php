
Copier

<?php
require_once 'includes/config.php';
require_once 'includes/image_helper.php';
$base = '';
$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: san-pham.php'); exit; }
 
$stmt = $conn->prepare("SELECT s.*, d.ten as ten_danh_muc FROM san_pham s JOIN danh_muc d ON s.danh_muc_id=d.id WHERE s.id=? AND s.trang_thai='hien'");
$stmt->bind_param('i', $id);
$stmt->execute();
$sp = $stmt->get_result()->fetch_assoc();
if (!$sp) { header('Location: san-pham.php'); exit; }
 
$gia = giaBan($sp['gia_nhap'], $sp['ti_le_loi_nhuan']);
$page_title = $sp['ten'];
 
$ts_res = $conn->query("SELECT * FROM sp_thong_so WHERE sp_id=$id");
 
include 'includes/header.php';
 
$msg = $_SESSION['msg'] ?? ''; $msg_type = $_SESSION['msg_type'] ?? '';
unset($_SESSION['msg'], $_SESSION['msg_type']);
?>
 
<div class="detail-wrap">
  <a href="javascript:history.back()" class="back-link">← Quay lại</a>
 
  <?php if ($msg): ?>
  <div class="alert alert-<?= $msg_type ?>"><?= htmlspecialchars($msg) ?></div>
  <?php endif; ?>
 
  <div class="detail-grid">
    <div>
      <div class="detail-img-box" style="<?= ($sp['image'] ?? '') ? 'font-size:inherit;padding:0;overflow:hidden' : 'font-size:9rem' ?>">
        <?php if (!empty($sp['image'])): ?>
          <?= renderProductImage($sp['image'], $sp['icon'] ?? '📦', '', 'width:100%;height:100%;object-fit:contain;border-radius:16px') ?>
        <?php else: ?>
          <?= htmlspecialchars($sp['icon'] ?? '📦') ?>
        <?php endif; ?>
      </div>
    </div>
    <div class="detail-info">
      <div class="d-cat"><?= htmlspecialchars($sp['ten_danh_muc']) ?></div>
      <h1><?= htmlspecialchars($sp['ten']) ?></h1>
      <div class="d-price"><?= formatGia($gia) ?></div>
      <div class="d-stock <?= $sp['so_luong_ton'] == 0 ? 'out' : '' ?>">
        <?= $sp['so_luong_ton'] > 0 ? '✓ Còn hàng (' . $sp['so_luong_ton'] . ' ' . htmlspecialchars($sp['don_vi_tinh']) . ')' : '✗ Hết hàng' ?>
      </div>
      <p class="d-desc"><?= nl2br(htmlspecialchars($sp['mo_ta'])) ?></p>
 
      <?php if ($ts_res->num_rows > 0): ?>
      <div class="d-specs">
        <h4>Thông số sản phẩm</h4>
        <?php while ($ts = $ts_res->fetch_assoc()): ?>
        <div class="spec-row">
          <span><?= htmlspecialchars($ts['ten_thong_so']) ?></span>
          <span><?= htmlspecialchars($ts['gia_tri']) ?></span>
        </div>
        <?php endwhile; ?>
      </div>
      <?php endif; ?>
 
      <?php if ($sp['so_luong_ton'] > 0): ?>
      <form action="them-gio-hang.php" method="POST" novalidate id="addCartForm">
        <input type="hidden" name="sp_id" value="<?= $id ?>">
        <input type="hidden" name="redirect" value="chi-tiet.php?id=<?= $id ?>">
        <div class="qty-row">
          <span class="qty-lbl">Số lượng:</span>
          <button type="button" class="qty-btn" onclick="changeQty(-1)">−</button>
          <input type="number" name="so_luong" id="qtyInput" value="1" min="1" max="<?= $sp['so_luong_ton'] ?>" style="width:60px;text-align:center;padding:6px;border:1.5px solid #dde3f0;border-radius:8px;font-size:1rem">
          <button type="button" class="qty-btn" onclick="changeQty(1)">+</button>
        </div>
        <span class="err" id="qtyErr"></span>
        <button type="submit" class="btn-primary" style="padding:12px 32px;font-size:.95rem" onclick="return validateQty(<?= $sp['so_luong_ton'] ?>)">
          🛒 Thêm vào giỏ hàng
        </button>
      </form>
      <?php else: ?>
      <button class="btn-primary" disabled style="padding:12px 32px">Hết hàng</button>
      <?php endif; ?>
    </div>
  </div>
</div>
 
<script>
function changeQty(delta) {
    const input = document.getElementById('qtyInput');
    const max = <?= $sp['so_luong_ton'] ?>;
    let val = parseInt(input.value) + delta;
    input.value = Math.max(1, Math.min(max, val));
}
function validateQty(max) {
    const val = parseInt(document.getElementById('qtyInput').value);
    const err = document.getElementById('qtyErr');
    if (!val || val < 1) { err.textContent = 'Số lượng phải ≥ 1'; return false; }
    if (val > max) { err.textContent = 'Vượt quá tồn kho (' + max + ')'; return false; }
    err.textContent = '';
    return true;
}
</script>
<?php include 'includes/footer.php'; ?>