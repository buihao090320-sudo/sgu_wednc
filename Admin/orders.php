<?php
require_once '../config/database.php';
checkLogin();

$message = '';

// Cập nhật trạng thái đơn hàng
if (isset($_GET['update_status'])) {
    $id = $_GET['update_status'];
    $status = $_GET['status'];
    
    $sql = "UPDATE orders SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$status, $id]);
    
    $message = 'Cập nhật trạng thái thành công!';
}

// Lấy bộ lọc
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'order_date';
$order = isset($_GET['order']) ? $_GET['order'] : 'DESC';

// Xây dựng câu query
$sql = "SELECT o.*, u.fullname, u.username 
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.id 
        WHERE 1=1";

$params = [];

if ($status_filter) {
    $sql .= " AND o.status = ?";
    $params[] = $status_filter;
}

if ($date_from) {
    $sql .= " AND DATE(o.order_date) >= ?";
    $params[] = $date_from;
}

if ($date_to) {
    $sql .= " AND DATE(o.order_date) <= ?";
    $params[] = $date_to;
}

$sql .= " ORDER BY $sort_by $order";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý đơn hàng</title>
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
                    <h1 class="h2">Quản lý đơn hàng</h1>
                </div>
                
                <?php if ($message): ?>
                    <div class="alert alert-success"><?php echo $message; ?></div>
                <?php endif; ?>
                
                <!-- Bộ lọc -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Bộ lọc</h5>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="" class="row">
                            <div class="col-md-3 mb-3">
                                <label for="status" class="form-label">Trạng thái</label>
                                <select class="form-control" id="status" name="status">
                                    <option value="">Tất cả</option>
                                    <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Chưa xử lý</option>
                                    <option value="confirmed" <?php echo $status_filter == 'confirmed' ? 'selected' : ''; ?>>Đã xác nhận</option>
                                    <option value="shipped" <?php echo $status_filter == 'shipped' ? 'selected' : ''; ?>>Đang giao</option>
                                    <option value="delivered" <?php echo $status_filter == 'delivered' ? 'selected' : ''; ?>>Đã giao</option>
                                    <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Đã hủy</option>
                                </select>
                            </div>
                            
                            <div class="col-md-3 mb-3">
                                <label for="date_from" class="form-label">Từ ngày</label>
                                <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo $date_from; ?>">
                            </div>
                            
                            <div class="col-md-3 mb-3">
                                <label for="date_to" class="form-label">Đến ngày</label>
                                <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo $date_to; ?>">
                            </div>
                            
                            <div class="col-md-3 mb-3">
                                <label for="sort_by" class="form-label">Sắp xếp theo</label>
                                <select class="form-control" id="sort_by" name="sort_by">
                                    <option value="order_date" <?php echo $sort_by == 'order_date' ? 'selected' : ''; ?>>Ngày đặt</option>
                                    <option value="shipping_address" <?php echo $sort_by == 'shipping_address' ? 'selected' : ''; ?>>Địa chỉ</option>
                                    <option value="total_amount" <?php echo $sort_by == 'total_amount' ? 'selected' : ''; ?>>Tổng tiền</option>
                                </select>
                            </div>
                            
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-primary">Lọc</button>
                                <a href="orders.php" class="btn btn-secondary">Xóa lọc</a>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Danh sách đơn hàng -->
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Mã đơn</th>
                                <th>Khách hàng</th>
                                <th>Ngày đặt</th>
                                <th>Tổng tiền</th>
                                <th>Địa chỉ</th>
                                <th>Trạng thái</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><?php echo $order['order_code']; ?></td>
                                <td><?php echo $order['fullname'] ?: $order['username']; ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($order['order_date'])); ?></td>
                                <td><?php echo number_format($order['total_amount']); ?>đ</td>
                                <td><?php echo $order['shipping_address']; ?></td>
                                <td>
                                    <select class="form-select form-select-sm" onchange="updateStatus(<?php echo $order['id']; ?>, this.value)">
                                        <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>Chưa xử lý</option>
                                        <option value="confirmed" <?php echo $order['status'] == 'confirmed' ? 'selected' : ''; ?>>Đã xác nhận</option>
                                        <option value="shipped" <?php echo $order['status'] == 'shipped' ? 'selected' : ''; ?>>Đang giao</option>
                                        <option value="delivered" <?php echo $order['status'] == 'delivered' ? 'selected' : ''; ?>>Đã giao</option>
                                        <option value="cancelled" <?php echo $order['status'] == 'cancelled' ? 'selected' : ''; ?>>Đã hủy</option>
                                    </select>
                                </td>
                                <td>
                                    <a href="order_detail.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-info">
                                        <i class="bi bi-eye"></i> Chi tiết
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateStatus(orderId, status) {
            if (confirm('Cập nhật trạng thái đơn hàng?')) {
                window.location.href = '?update_status=' + orderId + '&status=' + status;
            }
        }
    </script>
</body>
</html>