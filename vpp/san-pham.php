<?php
require_once 'includes/config.php';
require_once 'includes/image_helper.php';
$page_title = 'Sản phẩm';
$base = '';
 
// Tham số tìm kiếm / lọc
$q       = trim($_GET['q'] ?? '');
$dm_id   = (int)($_GET['dm'] ?? 0);
$gia_min = (int)($_GET['gia_min'] ?? 0);
$gia_max = (int)($_GET['gia_max'] ?? 0);
$page    = max(1, (int)($_GET['page'] ?? 1));
$per_page = 9;
 
// Xây WHERE
$where = "s.trang_thai='hien'";
$params = [];
$types  = '';
 
if ($q !== '') {
    $where .= " AND s.ten LIKE ?";
    $params[] = "%$q%"; $types .= 's';
}
if ($dm_id > 0) {
    $where .= " AND s.danh_muc_id=?";
    $params[] = $dm_id; $types .= 'i';
}
 
// Đếm tổng
$sql_all = "SELECT s.*, d.ten as ten_danh_muc FROM san_pham s JOIN danh_muc d ON s.danh_muc_id=d.id WHERE $where ORDER BY s.id DESC";
$stmt = $conn->prepare($sql_all);
if ($types) $stmt->bind_param($types, ...$params);
$stmt->execute();
$all_result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
 
// Lọc theo khoảng giá
if ($gia_min > 0 || $gia_max > 0) {
    $all_result = array_filter($all_result, function($sp) use ($gia_min, $gia_max) {
        $gb = giaBan($sp['gia_nhap'], $sp['ti_le_loi_nhuan']);
        if ($gia_min > 0 && $gb < $gia_min) return false;
        if ($gia_max > 0 && $gb > $gia_max) return false;
        return true;
    });
    $all_result = array_values($all_result);
}
 
$total = count($all_result);
$total_pages = max(1, ceil($total / $per_page));
$page = min($page, $total_pages);
$products = array_slice($all_result, ($page - 1) * $per_page, $per_page);
 
// Danh mục cho sidebar
$dm_res = $conn->query("SELECT * FROM danh_muc ORDER BY id");
 
function buildQuery($extra = []) {
    $params = array_merge($_GET, $extra);
    unset($params['page']);
    $qs = http_build_query(array_filter($params, fn($v) => $v !== '' && $v !== '0'));
    return $qs ? "?$qs&page=" : "?page=";
}
 
include 'includes/header.php';
?>
 
<div class="page-layout">
  <!-- SIDEBAR LỌC -->
  <aside class="sidebar">
    <div class="sidebar-title">🔍 Bộ lọc & Tìm kiếm</div>
    <form method="GET" action="san-pham.php" novalidate id="filterForm">
      <div class="filter-group">
        <label>Tên sản phẩm</label>
        <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Nhập tên...">
      </div>
      <div class="filter-group">
        <label>Danh mục</label>
        <select name="dm">
          <option value="">Tất cả</option>
          <?php $dm_res->data_seek(0); while ($dm = $dm_res->fetch_assoc()): ?>
          <option value="<?= $dm['id'] ?>" <?= $dm_id == $dm['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($dm['icon'] . ' ' . $dm['ten']) ?>
          </option>
          <?php endwhile; ?>
        </select>
      </div>
      <div class="filter-group">
        <label>Khoảng giá (VNĐ)</label>
        <div class="price-range">
          <input type="number" name="gia_min" placeholder="Từ" value="<?= $gia_min ?: '' ?>" min="0">
          <span class="dash">–</span>
          <input type="number" name="gia_max" placeholder="Đến" value="<?= $gia_max ?: '' ?>" min="0">
        </div>
        <div class="err" id="giaErr" style="display:none">Khoảng giá không hợp lệ</div>
      </div>
      <button type="submit" class="btn-primary full" onclick="return validateFilter()">🔍 Tìm kiếm</button>
      <a href="san-pham.php" class="btn-outline full" style="margin-top:8px;display:block;text-align:center;text-decoration:none">Xóa bộ lọc</a>
    </form>
  </aside>
 
  <!-- DANH SÁCH SẢN PHẨM -->
  <div class="main-content">
    <div class="content-hd">
      <h2>
        <?php if ($q): echo 'Kết quả: "' . htmlspecialchars($q) . '"';
        elseif ($dm_id && ($dm_found = $conn->query("SELECT ten,icon FROM danh_muc WHERE id=$dm_id")->fetch_assoc())): echo htmlspecialchars($dm_found['icon'] . ' ' . $dm_found['ten']);
        else: echo 'Tất cả sản phẩm'; endif; ?>
      </h2>
      <span class="result-count"><?= $total ?> sản phẩm</span>
    </div>
 
    <?php if (empty($products)): ?>
      <div class="empty-state">Không tìm thấy sản phẩm nào phù hợp.</div>
    <?php else: ?>
    <div class="product-grid">
      <?php foreach ($products as $sp):
        $gia = giaBan($sp['gia_nhap'], $sp['ti_le_loi_nhuan']); ?>
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
            <input type="hidden" name="redirect" value="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>">
            <button type="submit" class="btn-primary p-btn" <?= $sp['so_luong_ton'] == 0 ? 'disabled' : '' ?>>
              <?= $sp['so_luong_ton'] > 0 ? '+ Thêm vào giỏ' : 'Hết hàng' ?>
            </button>
          </form>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
 
    <!-- PHÂN TRANG -->
    <?php if ($total_pages > 1): ?>
    <div class="pagination">
      <?php $qs = buildQuery(); ?>
      <?php if ($page > 1): ?><a href="<?= $qs . ($page-1) ?>" class="pg-btn">‹</a><?php endif; ?>
      <?php for ($i = 1; $i <= $total_pages; $i++): ?>
        <a href="<?= $qs . $i ?>" class="pg-btn <?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
      <?php endfor; ?>
      <?php if ($page < $total_pages): ?><a href="<?= $qs . ($page+1) ?>" class="pg-btn">›</a><?php endif; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>
  </div>
</div>
 
<script>
function validateFilter() {
    const min = parseInt(document.querySelector('[name=gia_min]').value) || 0;
    const max = parseInt(document.querySelector('[name=gia_max]').value) || 0;
    const err = document.getElementById('giaErr');
    if (min > 0 && max > 0 && min > max) {
        err.style.display = 'block';
        return false;
    }
    err.style.display = 'none';
    return true;
}
</script>
<?php include 'includes/footer.php'; ?>