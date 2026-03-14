<?php
// Cấu hình kết nối database
$host = 'localhost';
$dbname = 'sgu_wednc';
$username = 'root';
$password = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo "Kết nối thất bại: " . $e->getMessage();
    die();
}

// Hàm kiểm tra đăng nhập
function checkLogin() {
    session_start();
    if (!isset($_SESSION['admin_id'])) {
        header('Location: login.php');
        exit();
    }
}

// Hàm kiểm tra quyền admin
function isAdmin() {
    return isset($_SESSION['admin_role']) && $_SESSION['admin_role'] == 'admin';
}
?>