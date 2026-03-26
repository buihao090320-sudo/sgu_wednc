<?php
// admin/them-san-pham.php – Thêm / Sửa sản phẩm (có upload ảnh + URL ảnh)
require_once '../includes/config.php';
require_once '../includes/image_helper.php';
define('UPLOAD_DIR', __DIR__ . '/../images/products/');
requireAdmin();
 
$id     = (int)($_GET['id'] ?? 0);
$is_edit = $id > 0;
$error  = '';
$success = '';
 
// Dữ liệu mặc định
$sp = [
    'ten'=>'','danh_muc_id'=>0,'mo_ta'=>'','don_vi_tinh'=>'cái',
    'icon'=>'📦','ti_le_loi_nhuan'=>20,'trang_thai'=>'hien',
    'image'=>'', 'so_luong_ton'=>0, 'gia_nhap'=>0
];
 
// Danh mục
$dm_list = $conn->query("SELECT * FROM danh_muc WHERE trang_thai=1 ORDER BY ten")->fetch_all(MYSQLI_ASSOC);
 
// Nếu sửa: load dữ liệu cũ
if ($is_edit) {
    $row = $conn->query("SELECT * FROM san_pham WHERE id=$id")->fetch_assoc();
    if (!$row) { header('Location: san-pham.php'); exit; }
    $sp = array_merge($sp, $row);
}
 
// Kiểm tra đã có phiếu nhập (nếu đã nhập hàng thì không cho xóa)
$has_import = $is_edit && $conn->query("SELECT id FROM chi_tiet_nhap WHERE sp_id=$id LIMIT 1")->num_rows > 0;
 
// ============================================================
// XỬ LÝ FORM SUBMIT
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ten        = trim($_POST['ten'] ?? '');
    $dm_id      = (int)($_POST['danh_muc_id'] ?? 0);
    $mo_ta      = trim($_POST['mo_ta'] ?? '');
    $dvt        = trim($_POST['don_vi_tinh'] ?? 'cái');
    $icon_val   = trim($_POST['icon'] ?? '📦');
    $ti_le      = (float)($_POST['ti_le_loi_nhuan'] ?? 20);
    $trang_thai = $_POST['trang_thai'] ?? 'hien';
    $image_url  = trim($_POST['image_url'] ?? '');
 
    // Validate cơ bản
    $errs = [];
    if (mb_strlen($ten) < 2)    $errs[] = 'Tên sản phẩm tối thiểu 2 ký tự';
    if ($dm_id <= 0)             $errs[] = 'Chọn danh mục';
    if ($ti_le < 0 || $ti_le > 999) $errs[] = 'Tỉ lệ lợi nhuận không hợp lệ';
    if (!in_array($trang_thai, ['hien','an'])) $trang_thai = 'hien';
 
    if (empty($errs)) {
        // Xử lý ảnh
        $img_result = handleProductImage('image_file', $image_url, $sp['image']);
        if (!$img_result['success']) {
            $errs[] = $img_result['error'];
        } else {
            $new_image = $img_result['image'];
        }
    }
 
    if (empty($errs)) {
        if ($is_edit) {
            $stmt = $conn->prepare("UPDATE san_pham SET ten=?,danh_muc_id=?,mo_ta=?,don_vi_tinh=?,icon=?,ti_le_loi_nhuan=?,trang_thai=?,image=?,updated_at=NOW() WHERE id=?");
            $stmt->bind_param('sississsi', $ten,$dm_id,$mo_ta,$dvt,$icon_val,$ti_le,$trang_thai,$new_image,$id);
        } else {
            $stmt = $conn->prepare("INSERT INTO san_pham (ten,danh_muc_id,mo_ta,don_vi_tinh,icon,ti_le_loi_nhuan,trang_thai,image) VALUES (?,?,?,?,?,?,?,?)");
            $stmt->bind_param('sissssss', $ten,$dm_id,$mo_ta,$dvt,$icon_val,$ti_le,$trang_thai,$new_image);
        }
 
        if ($stmt->execute()) {
            $_SESSION['admin_msg'] = $is_edit ? 'Cập nhật sản phẩm thành công!' : 'Thêm sản phẩm thành công!';
            header('Location: san-pham.php');
            exit;
        } else {
            $error = 'Lỗi database: ' . $conn->error;
        }
    } else {
        $error = implode('<br>', $errs);
        // Giữ lại giá trị người dùng đã nhập
        $sp = array_merge($sp, [
            'ten'=>$ten,'danh_muc_id'=>$dm_id,'mo_ta'=>$mo_ta,
            'don_vi_tinh'=>$dvt,'icon'=>$icon_val,
            'ti_le_loi_nhuan'=>$ti_le,'trang_thai'=>$trang_thai,
        ]);
    }
}
 
$page_title = $is_edit ? 'Sửa sản phẩm' : 'Thêm sản phẩm';
include 'includes/header.php';
?>
 
<div class="admin-content-wrap">
  <div class="page-hd">
    <h1><?= $is_edit ? '✏️ Sửa sản phẩm' : '➕ Thêm sản phẩm mới' ?></h1>
    <a href="san-pham.php" class="btn-outline">← Quay lại</a>
  </div>
 
  <?php if ($error): ?>
  <div class="alert alert-danger"><?= $error ?></div>
  <?php endif; ?>
 
  <form method="POST" enctype="multipart/form-data" novalidate id="spForm">
    <div class="form-grid-2col">
 
      <!-- CỘT TRÁI -->
      <div>
        <div class="card-box">
          <h3 class="box-title">Thông tin cơ bản</h3>
 
          <div class="form-group">
            <label>Tên sản phẩm <span class="req">*</span></label>
            <input type="text" name="ten" value="<?= htmlspecialchars($sp['ten']) ?>" placeholder="VD: Bút bi Thiên Long" id="fTen">
            <span class="err" id="fTenErr"></span>
          </div>
 
          <div class="form-row">
            <div class="form-group">
              <label>Danh mục <span class="req">*</span></label>
              <select name="danh_muc_id" id="fDm">
                <option value="">Chọn danh mục</option>
                <?php foreach ($dm_list as $dm): ?>
                <option value="<?= $dm['id'] ?>" <?= $sp['danh_muc_id'] == $dm['id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($dm['icon'] . ' ' . $dm['ten']) ?>
                </option>
                <?php endforeach; ?>
              </select>
              <span class="err" id="fDmErr"></span>
            </div>
            <div class="form-group">
              <label>Đơn vị tính</label>
              <input type="text" name="don_vi_tinh" value="<?= htmlspecialchars($sp['don_vi_tinh']) ?>" placeholder="cái, hộp, cuốn...">
            </div>
          </div>
 
          <div class="form-row">
            <div class="form-group">
              <label>Icon emoji</label>
              <input type="text" name="icon" value="<?= htmlspecialchars($sp['icon']) ?>" placeholder="📦" style="font-size:1.4rem;width:80px">
            </div>
            <div class="form-group">
              <label>Tỉ lệ lợi nhuận (%) <span class="req">*</span></label>
              <input type="number" name="ti_le_loi_nhuan" value="<?= $sp['ti_le_loi_nhuan'] ?>" min="0" max="999" step="0.01" id="fTiLe">
              <span class="err" id="fTiLeErr"></span>
            </div>
          </div>
 
          <div class="form-group">
            <label>Mô tả</label>
            <textarea name="mo_ta" rows="4" placeholder="Mô tả chi tiết sản phẩm..."><?= htmlspecialchars($sp['mo_ta']) ?></textarea>
          </div>
 
          <div class="form-group">
            <label>Trạng thái</label>
            <select name="trang_thai">
              <option value="hien" <?= $sp['trang_thai']==='hien' ? 'selected' : '' ?>>✅ Hiển thị (đang bán)</option>
              <option value="an"   <?= $sp['trang_thai']==='an'   ? 'selected' : '' ?>>🚫 Ẩn</option>
            </select>
          </div>
        </div>
      </div>
 
      <!-- CỘT PHẢI: HÌNH ẢNH -->
      <div>
        <div class="card-box">
          <h3 class="box-title">🖼️ Hình ảnh sản phẩm</h3>
 
          <!-- Preview ảnh hiện tại -->
          <div id="imgPreviewWrap" style="text-align:center;margin-bottom:16px">
            <?php if (!empty($sp['image'])): ?>
              <img id="imgPreview"
                   src="<?= htmlspecialchars(getImageSrc($sp['image'], '../')) ?>"
                   alt="Ảnh hiện tại"
                   style="max-width:100%;max-height:220px;object-fit:contain;border-radius:12px;border:2px solid #e2e8f0">
            <?php else: ?>
              <div id="imgPlaceholder" style="width:100%;height:180px;background:#f8fafc;border:2px dashed #cbd5e1;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-direction:column;color:#94a3b8">
                <span style="font-size:3rem"><?= htmlspecialchars($sp['icon'] ?? '📦') ?></span>
                <span style="font-size:.85rem;margin-top:8px">Chưa có ảnh</span>
              </div>
            <?php endif; ?>
          </div>
 
          <!-- TAB chọn cách nhập ảnh -->
          <div class="img-tab-wrap">
            <button type="button" class="img-tab active" onclick="switchTab('upload')">📁 Upload từ máy</button>
            <button type="button" class="img-tab" onclick="switchTab('url')">🌐 Dùng URL</button>
          </div>
 
          <!-- TAB: Upload file -->
          <div id="tabUpload" class="img-tab-content">
            <div class="upload-drop-zone" id="dropZone" onclick="document.getElementById('imageFileInput').click()">
              <span style="font-size:2rem">📂</span>
              <p style="margin:8px 0 4px;font-weight:600">Nhấn để chọn ảnh hoặc kéo thả vào đây</p>
              <p style="font-size:.8rem;color:#94a3b8">JPG, PNG, WEBP, GIF · Tối đa 2MB</p>
            </div>
            <input type="file" name="image_file" id="imageFileInput" accept="image/*"
                   style="display:none" onchange="previewFile(this)">
            <div id="fileNameDisplay" style="font-size:.82rem;color:#64748b;margin-top:6px;text-align:center"></div>
          </div>
 
          <!-- TAB: URL -->
          <div id="tabUrl" class="img-tab-content" style="display:none">
            <div class="form-group" style="margin-bottom:0">
              <label>URL ảnh từ internet</label>
              <input type="text" name="image_url" id="imageUrlInput"
                     value="<?= isImageUrl($sp['image'] ?? '') ? htmlspecialchars($sp['image']) : '' ?>"
                     placeholder="https://example.com/anh-san-pham.jpg"
                     oninput="previewUrl(this.value)">
              <span style="font-size:.8rem;color:#94a3b8">Nhập URL ảnh công khai (CDN, Google Drive public...)</span>
            </div>
            <button type="button" class="btn-outline" style="margin-top:8px;width:100%" onclick="previewUrl(document.getElementById('imageUrlInput').value)">
              👁️ Xem trước
            </button>
          </div>
 
          <!-- Xóa ảnh hiện tại -->
          <?php if (!empty($sp['image'])): ?>
          <div style="margin-top:12px;text-align:center">
            <label style="font-size:.82rem;color:#ef4444;cursor:pointer">
              <input type="checkbox" name="xoa_anh" value="1" onchange="toggleRemoveImage(this)">
              🗑️ Xóa ảnh hiện tại
            </label>
          </div>
          <?php endif; ?>
 
        </div><!-- end card-box -->
      </div><!-- end cột phải -->
 
    </div><!-- end form-grid-2col -->
 
    <div style="text-align:right;margin-top:20px;display:flex;gap:10px;justify-content:flex-end">
      <a href="san-pham.php" class="btn-outline">Hủy</a>
      <button type="submit" class="btn-primary" onclick="return validateForm()">
        <?= $is_edit ? '💾 Lưu thay đổi' : '➕ Thêm sản phẩm' ?>
      </button>
    </div>
  </form>
</div>
 
<style>
.form-grid-2col { display:grid; grid-template-columns:1fr 1fr; gap:20px; }
@media(max-width:900px){ .form-grid-2col{ grid-template-columns:1fr; } }
.card-box { background:#fff; border-radius:14px; padding:22px; box-shadow:0 1px 4px rgba(0,0,0,.07); margin-bottom:18px; }
.box-title { font-size:1rem; font-weight:700; margin:0 0 16px; color:#1e293b; }
.img-tab-wrap { display:flex; gap:8px; margin-bottom:12px; }
.img-tab { flex:1; padding:8px; border:2px solid #e2e8f0; background:#f8fafc; border-radius:8px; cursor:pointer; font-size:.88rem; font-weight:600; color:#64748b; transition:.2s; }
.img-tab.active { border-color:#1B4F9B; background:#eff6ff; color:#1B4F9B; }
.upload-drop-zone { border:2px dashed #93c5fd; border-radius:12px; padding:24px; text-align:center; cursor:pointer; transition:.2s; background:#f8fafc; }
.upload-drop-zone:hover { background:#eff6ff; border-color:#1B4F9B; }
</style>
 
<script>
function switchTab(tab) {
    document.querySelectorAll('.img-tab').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.img-tab-content').forEach(c => c.style.display='none');
    if (tab === 'upload') {
        document.getElementById('tabUpload').style.display = 'block';
        document.querySelectorAll('.img-tab')[0].classList.add('active');
        document.getElementById('imageUrlInput').value = '';
    } else {
        document.getElementById('tabUrl').style.display = 'block';
        document.querySelectorAll('.img-tab')[1].classList.add('active');
    }
}
 
function previewFile(input) {
    if (!input.files || !input.files[0]) return;
    const file = input.files[0];
    document.getElementById('fileNameDisplay').textContent = '📎 ' + file.name + ' (' + (file.size/1024).toFixed(0) + ' KB)';
    const reader = new FileReader();
    reader.onload = e => updatePreview(e.target.result);
    reader.readAsDataURL(file);
    // Xóa URL nếu đang nhập
    document.getElementById('imageUrlInput').value = '';
}
 
function previewUrl(url) {
    url = url.trim();
    if (!url) return;
    updatePreview(url);
}
 
function updatePreview(src) {
    const wrap = document.getElementById('imgPreviewWrap');
    let img = document.getElementById('imgPreview');
    if (!img) {
        wrap.innerHTML = '<img id="imgPreview" style="max-width:100%;max-height:220px;object-fit:contain;border-radius:12px;border:2px solid #e2e8f0">';
        img = document.getElementById('imgPreview');
    }
    img.src = src;
    img.onerror = () => { img.src=''; img.alt='❌ Không tải được ảnh'; };
    const ph = document.getElementById('imgPlaceholder');
    if (ph) ph.style.display = 'none';
}
 
function toggleRemoveImage(cb) {
    const img = document.getElementById('imgPreview');
    if (cb.checked) {
        if (img) img.style.opacity = '0.3';
    } else {
        if (img) img.style.opacity = '1';
    }
}
 
// Drag & drop
const dz = document.getElementById('dropZone');
if (dz) {
    dz.addEventListener('dragover', e => { e.preventDefault(); dz.style.background='#eff6ff'; });
    dz.addEventListener('dragleave', () => dz.style.background='');
    dz.addEventListener('drop', e => {
        e.preventDefault(); dz.style.background='';
        const file = e.dataTransfer.files[0];
        if (file && file.type.startsWith('image/')) {
            const input = document.getElementById('imageFileInput');
            const dt = new DataTransfer();
            dt.items.add(file);
            input.files = dt.files;
            previewFile(input);
        }
    });
}
 
function validateForm() {
    let ok = true;
    const ten = document.getElementById('fTen').value.trim();
    const dm  = document.getElementById('fDm').value;
    const tl  = parseFloat(document.getElementById('fTiLe').value);
 
    document.getElementById('fTenErr').textContent = ten.length >= 2 ? '' : 'Tên tối thiểu 2 ký tự';
    document.getElementById('fDmErr').textContent  = dm ? '' : 'Chọn danh mục';
    document.getElementById('fTiLeErr').textContent = (!isNaN(tl) && tl >= 0) ? '' : 'Tỉ lệ không hợp lệ';
 
    if (ten.length < 2 || !dm || isNaN(tl) || tl < 0) ok = false;
    return ok;
}
</script>
<?php include 'includes/footer.php'; ?>