<?php
// admin/xem-phieu-nhap.php
require_once '../includes/config.php';

$id = (int)($_GET['id']??0);
// 1. Lấy thông tin phiếu nhập
$phieu = $conn->query("SELECT * FROM phieu_nhap WHERE id=$id")->fetch_assoc();

if (!$phieu) { 
    header('Location: nhap-hang.php'); 
    exit; 
}

$page_title = 'Chi tiết phiếu nhập: ' . $phieu['ma_phieu'];

// 2. TRUY VẤN CHUẨN: Lấy cột 'image' từ bảng san_pham (vì database đặt tên là image)
$items = $conn->query("SELECT c.*, s.ten, s.image 
                       FROM chi_tiet_nhap c 
                       JOIN san_pham s ON c.sp_id = s.id 
                       WHERE c.phieu_nhap_id = $id")->fetch_all(MYSQLI_ASSOC);

include 'includes/header.php';
?>

<div style="margin-bottom: 16px;">
    <a href="nhap-hang.php" class="btn-admin-secondary btn-sm" style="display:inline-flex; align-items:center; gap:6px; text-decoration:none;">
        ← Quay lại danh sách
    </a>
</div>

<div class="admin-form-card" style="max-width:800px; background:#fff; padding:20px; border-radius:8px; box-shadow:0 2px 4px rgba(0,0,0,0.05)">
    <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:24px; padding-bottom:16px; border-bottom:1px solid #eee; font-size:.9rem">
        <div><span style="color:#64748b">Mã phiếu:</span> <strong style="color:#1e293b"><?= htmlspecialchars($phieu['ma_phieu']) ?></strong></div>
        <div><span style="color:#64748b">Ngày nhập:</span> <strong><?= date('d/m/Y', strtotime($phieu['ngay_nhap'])) ?></strong></div>
        <div><span style="color:#64748b">Trạng thái:</span> 
            <span class="badge badge-<?= $phieu['trang_thai']==='hoan_thanh'?'delivered':'draft' ?>">
                <?= $phieu['trang_thai']==='hoan_thanh'?'Hoàn thành':'Nháp' ?>
            </span>
        </div>
        <div><span style="color:#64748b">Ghi chú:</span> <i style="color:#94a3b8"><?= htmlspecialchars($phieu['ghi_chu'] ?: '—') ?></i></div>
    </div>

    <div class="table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Sản phẩm</th>
                    <th style="text-align:center">Số lượng</th>
                    <th>Giá nhập</th>
                    <th>Thành tiền</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $it): 
                    // Dùng cột 'image' theo đúng file SQL bạn gửi
                    $img_name = $it['image'] ?: 'default.png';
                ?>
                <tr>
                    <td>
                        <div style="display:flex; align-items:center; gap:10px">
                            <img src="../images/products/<?= htmlspecialchars($img_name) ?>" 
                                 alt="sp" 
                                 style="width:35px; height:35px; object-fit:cover; border-radius:4px; border:1px solid #eee"
                                 onerror="this.src='../images/products/default.png'">
                            <span style="font-weight:600"><?= htmlspecialchars($it['ten']) ?></span>
                        </div>
                    </td>
                    <td style="text-align:center"><?= number_format($it['so_luong']) ?></td>
                    <td><?= formatGia($it['gia_nhap']) ?></td>
                    <td style="font-weight:700; color:#1e293b"><?= formatGia($it['so_luong'] * $it['gia_nhap']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="background:#f8fafc">
                    <td colspan="3" style="text-align:right; font-weight:700; padding:12px">Tổng cộng:</td>
                    <td style="font-weight:800; color:#1B4F9B; font-size:1.1rem; padding:12px">
                        <?= formatGia($phieu['tong_tien']) ?>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>