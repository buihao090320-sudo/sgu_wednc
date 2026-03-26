<?php
// includes/config.php – Cấu hình kết nối CSDL
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'vpp_saigon');
define('SITE_NAME', 'Văn phòng Phẩm Sài Gòn');
define('PHI_GIAO_HANG', 30000);

// Bước 1: Kết nối MySQL (chưa chọn DB để kiểm tra trước)
$conn = @new mysqli(DB_HOST, DB_USER, DB_PASS);
if ($conn->connect_error) {
    die('
    <div style="font-family:Arial,sans-serif;max-width:600px;margin:60px auto;padding:32px;border:2px solid #dc2626;border-radius:12px;background:#fff5f5">
        <h2 style="color:#dc2626;margin:0 0 12px">&#10060; Không thể kết nối MySQL</h2>
        <p><strong>Lỗi:</strong> ' . htmlspecialchars($conn->connect_error) . '</p>
        <hr style="border-color:#fca5a5;margin:16px 0">
        <h3 style="color:#b91c1c">Cách khắc phục:</h3>
        <ol style="line-height:1.9;padding-left:20px">
            <li>Mở <strong>XAMPP Control Panel</strong></li>
            <li>Nhấn <strong>Start</strong> bên cạnh <strong>MySQL</strong></li>
            <li>Chờ MySQL hiển thị nền xanh lá</li>
            <li>Tải lại trang này</li>
        </ol>
    </div>');
}

// Bước 2: Tự động tạo DB nếu chưa có
$conn->query("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$conn->select_db(DB_NAME);
$conn->set_charset('utf8mb4');

// Bước 3: Kiểm tra đã import SQL chưa
$check = $conn->query("SHOW TABLES LIKE 'san_pham'");
if ($check && $check->num_rows === 0) {
    $base_dir = dirname(dirname(__FILE__));
    $sql_path = $base_dir . DIRECTORY_SEPARATOR . 'vpp_saigon.sql';
    die('
    <div style="font-family:Arial,sans-serif;max-width:700px;margin:40px auto;padding:32px;border:2px solid #f59e0b;border-radius:12px;background:#fffbeb">
        <h2 style="color:#d97706;margin:0 0 10px">&#9888; Chưa import cơ sở dữ liệu</h2>
        <p>Database <strong>vpp_saigon</strong> đã được tạo nhưng chưa có dữ liệu.</p>
        <hr style="border-color:#fde68a;margin:14px 0">
        <h3 style="color:#92400e;margin:0 0 10px">Các bước import file SQL:</h3>
        <ol style="line-height:2.2;padding-left:20px">
            <li>Mở: <a href="http://localhost/phpmyadmin/" target="_blank" style="color:#1B4F9B;font-weight:bold">http://localhost/phpmyadmin/</a></li>
            <li>Click vào database <strong>vpp_saigon</strong> ở cột trái</li>
            <li>Click tab <strong>Import</strong> trên menu</li>
            <li>Nhấn <strong>Choose File</strong> &rarr; chọn file: <code style="background:#fef3c7;padding:2px 6px;border-radius:4px">' . htmlspecialchars($sql_path) . '</code></li>
            <li>Cuộn xuống &rarr; nhấn nút <strong>Go</strong></li>
            <li>Tải lại trang này</li>
        </ol>
        <div style="background:#fef3c7;border:1px solid #fde68a;border-radius:8px;padding:12px;margin-top:12px;font-size:0.88rem">
            &#128161; <strong>Mẹo nhanh:</strong> Mở phpMyAdmin &rarr; chọn vpp_saigon &rarr; tab <strong>SQL</strong> &rarr; paste toàn bộ nội dung file vpp_saigon.sql &rarr; nhấn <strong>Go</strong>
        </div>
    </div>');
}

session_start();

// ── Helpers ──────────────────────────────────────────────────────────────────

function formatGia($so) {
    return number_format((float)$so, 0, ',', '.') . 'đ';
}

function giaBan($gia_nhap, $ti_le) {
    return round($gia_nhap * (1 + $ti_le / 100));
}

function capNhatGiaNhapBinhQuan($conn, $sp_id, $so_luong_nhap, $gia_nhap_moi) {
    $res = $conn->query("SELECT so_luong_ton, gia_nhap FROM san_pham WHERE id=" . (int)$sp_id);
    $row = $res->fetch_assoc();
    $ton_cu = (int)$row['so_luong_ton'];
    $gia_cu = (float)$row['gia_nhap'];
    if ($ton_cu + $so_luong_nhap > 0) {
        $gia_bq = ($ton_cu * $gia_cu + $so_luong_nhap * $gia_nhap_moi) / ($ton_cu + $so_luong_nhap);
    } else {
        $gia_bq = $gia_nhap_moi;
    }
    $gia_bq      = round($gia_bq, 2);
    $ton_moi     = $ton_cu + $so_luong_nhap;
    $conn->query("UPDATE san_pham SET gia_nhap=$gia_bq, so_luong_ton=$ton_moi WHERE id=" . (int)$sp_id);
}

function requireLogin() {
    if (empty($_SESSION['user_id'])) {
        // Lấy thư mục gốc của project tự động
        $script = $_SERVER['SCRIPT_NAME']; // VD: /vpp_saigon (1)/vpp/them-gio-hang.php
        $base_url = dirname($script);       // VD: /vpp_saigon (1)/vpp
        // Nếu file nằm ngay root thì dirname trả về '/' hoặc '\'
        if ($base_url === '/' || $base_url === '\\' || $base_url === '.') {
            $base_url = '';
        }
        header('Location: ' . $base_url . '/dang-nhap.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}
function requireAdmin() {
    if (empty($_SESSION['admin_id'])) {
        $script = $_SERVER['SCRIPT_NAME'];
        if (strpos($script, '/admin/') !== false) {
            header('Location: login.php');
        } else {
            header('Location: admin/login.php');
        }
        exit;
    }
}

function soLuongGioHang($conn) {
    if (empty($_SESSION['user_id'])) return 0;
    $uid = (int)$_SESSION['user_id'];
    $res = $conn->query("SELECT SUM(so_luong) as tong FROM gio_hang WHERE nguoi_dung_id=$uid");
    $row = $res->fetch_assoc();
    return (int)($row['tong'] ?? 0);
}
?>
