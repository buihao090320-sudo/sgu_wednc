<?php
// admin/lich-su-nhap.php
require_once '../includes/config.php';

$sp_id = (int)($_GET['sp_id']??0);
$sp = $conn->query("SELECT * FROM san_pham WHERE id=$sp_id")->fetch_assoc();

if (!$sp) { 
    header('Location: quan-ly-gia.php'); 
    exit; 
}

$page_title = 'Lịch sử nhập – '.$sp['ten'];

// Truy vấn lịch sử nhập hàng
$history = $conn->query("SELECT p.ngay_nhap, p.ma_phieu, c.so_luong, c.gia_nhap, (c.so_luong * c.gia_nhap) as thanh_tien 
                         FROM chi_tiet_nhap c 
                         JOIN phieu_nhap p ON c.phieu_nhap_id = p.id 
                         WHERE c.sp_id = $sp_id AND p.trang_thai = 'hoan_thanh' 
                         ORDER BY p.ngay_nhap DESC")->fetch_all(MYSQLI_ASSOC);

include 'includes/header.php';

// Xác định tên file ảnh (linh hoạt giữa cột image hoặc icon)
$img_name = $sp['image'] ?? $sp['icon'] ?? 'default.png';
?>

<div style="margin-bottom: 20px;">
    <a href="quan-ly-gia.php" class="btn-admin-secondary btn-sm" style="display:inline-flex; align-items:center; gap:6px;">
        ← Quay lại Quản lý giá
    </a>
</div>

<div class="admin-form-card" style="max-width:800px; background: #fff; padding: 24px; border-radius: 8px; shadow: 0 1px 3px rgba(0,0,0,0.1);">
    
    <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 20px; border-bottom: 1px solid #f1f5f9; padding-bottom: 15px;">
        <img src="../images/products/<?= htmlspecialchars($img_name) ?>" 
             alt="Sản phẩm" 
             style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px; border: 1px solid #e2e8f0;">
        
        <div>
            <h2 style="margin: 0; font-size: 1.25rem; color: #1e293b;"><?= htmlspecialchars($sp['ten']) ?></h2>
            <div style="font-size: 0.9rem; color: #64748b; margin-top: 4px;">
                Giá nhập bình quân hiện tại: <strong style="color: #1B4F9B; font-size: 1rem;"><?= formatGia($sp['gia_nhap']) ?></strong>
            </div>
        </div>
    </div>

    <?php if (empty($history)): ?>
        <div style="text-align: center; padding: 40px; color: #94a3b8; background: #f8fafc; border-radius: 8px;">
            <p>Sản phẩm này chưa có lịch sử nhập hàng hoàn tất.</p>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Mã phiếu</th>
                        <th>Ngày nhập</th>
                        <th style="text-align: center;">SL nhập</th>
                        <th>Giá nhập</th>
                        <th>Thành tiền</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($history as $h): ?>
                    <tr>
                        <td><code style="background: #f1f5f9; padding: 2px 6px; border-radius: 4px;"><?= htmlspecialchars($h['ma_phieu']) ?></code></td>
                        <td><?= date('d/m/Y', strtotime($h['ngay_nhap'])) ?></td>
                        <td style="text-align: center; font-weight: 600;"><?= number_format($h['so_luong']) ?></td>
                        <td><?= formatGia($h['gia_nhap']) ?></td>
                        <td style="font-weight: 700; color: #0f172a;"><?= formatGia($h['thanh_tien']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>