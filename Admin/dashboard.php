<?php
require_once '../config/database.php';
checkLogin();

// Lấy thống kê
$stats = [];

// Tổng số user
$stmt = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'user'");
$stats['users'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Tổng số sản phẩm
$stmt = $conn->query("SELECT COUNT(*) as total FROM products WHERE is_deleted = 0");
$stats['products'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Tổng số đơn hàng
$stmt = $conn->query("SELECT COUNT(*) as total FROM orders");
$stats['orders'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Đơn hàng chưa xử lý
$stmt = $conn->query("SELECT COUNT(*) as total FROM orders WHERE status = 'pending'");
$stats['pending_orders'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Doanh thu (đơn hàng đã giao)
$stmt = $conn->query("SELECT SUM(total_amount) as total FROM orders WHERE status = 'delivered'");
$stats['revenue'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Tổng quan</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <span class="text-muted">Xin chào, <?php echo $_SESSION['admin_fullname']; ?></span>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <div class="card text-white bg-primary">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">Người dùng</h6>
                                        <h2 class="mb-0"><?php echo $stats['users']; ?></h2>
                                    </div>
                                    <i class="bi bi-people fs-1"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="card text-white bg-success">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">Sản phẩm</h6>
                                        <h2 class="mb-0"><?php echo $stats['products']; ?></h2>
                                    </div>
                                    <i class="bi bi-box fs-1"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="card text-white bg-warning">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">Đơn hàng</h6>
                                        <h2 class="mb-0"><?php echo $stats['orders']; ?></h2>
                                        <small>Chưa xử lý: <?php echo $stats['pending_orders']; ?></small>
                                    </div>
                                    <i class="bi bi-cart fs-1"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="card text-white bg-info">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="card-title">Doanh thu</h6>
                                        <h2 class="mb-0"><?php echo number_format($stats['revenue']); ?>đ</h2>
                                    </div>
                                    <i class="bi bi-cash-stack fs-1"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Đơn hàng gần đây -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5>Đơn hàng gần đây</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Mã đơn</th>
                                    <th>Ngày đặt</th>
                                    <th>Tổng tiền</th>
                                    <th>Trạng thái</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $sql = "SELECT * FROM orders ORDER BY order_date DESC LIMIT 5";
                                $stmt = $conn->query($sql);
                                while($order = $stmt->fetch(PDO::FETCH_ASSOC)):
                                ?>
                                <tr>
                                    <td><?php echo $order['order_code']; ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($order['order_date'])); ?></td>
                                    <td><?php echo number_format($order['total_amount']); ?>đ</td>
                                    <td>
                                        <?php
                                        $status_class = [
                                            'pending' => 'warning',
                                            'confirmed' => 'info',
                                            'shipped' => 'primary',
                                            'delivered' => 'success',
                                            'cancelled' => 'danger'
                                        ];
                                        $status_text = [
                                            'pending' => 'Chưa xử lý',
                                            'confirmed' => 'Đã xác nhận',
                                            'shipped' => 'Đang giao',
                                            'delivered' => 'Đã giao',
                                            'cancelled' => 'Đã hủy'
                                        ];
                                        ?>
                                        <span class="badge bg-<?php echo $status_class[$order['status']]; ?>">
                                            <?php echo $status_text[$order['status']]; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="order_detail.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-info">Xem</a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>