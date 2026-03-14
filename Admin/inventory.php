<?php
require_once '../config/database.php';
checkLogin();

$warning_level = isset($_GET['warning_level']) ? $_GET['warning_level'] : 10;
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-d', strtotime('-30 days'));
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');

// Lấy thông tin tồn kho
$sql = "SELECT 
            p.id,
            p.product_code,
            p.name,
            p.unit,
            p.quantity as current_stock,
            c.name as category_name,
            (SELECT COALESCE(SUM(quantity), 0) FROM import_receipt_details ird 
             JOIN import_receipts ir ON ird.receipt_id = ir.id 
             WHERE ird.product_id = p.id AND ir.status = 1) as total_import,
            (SELECT COALESCE(SUM(quantity), 0) FROM order_details od 
             JOIN orders o ON od.order_id = o.id 
             WHERE od.product_id = p.id AND o.status = 'delivered') as total_sold
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.is_deleted = 0
        ORDER BY p.name";

$products = $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// Cảnh báo sắp hết hàng
$low_stock = array_filter($products, function($p) use ($warning_level) {
    return $p['current_stock'] <= $warning_level;
});
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý tồn kho</title>
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
                    <h1 class="h2">Quản lý tồn kho</h1>
                </div>
                
                <!-- Cảnh báo sắp hết hàng -->
                <?php if (count($low_stock) > 0): ?>
                <div class="alert alert-warning">
                    <h5><i class="bi bi-exclamation-triangle"></i> Cảnh báo sản phẩm sắp hết hàng:</h5>
                    <ul>
                        <?php foreach ($low_stock as $product): ?>
                        <li>
                            <?php echo $product['name']; ?> - 
                            Còn <?php echo $product['current_stock']; ?> <?php echo $product['unit']; ?>
                            (Ngưỡng: <?php echo $warning_level; ?>)
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
                
                <!-- Cấu hình ngưỡng cảnh báo -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Cấu hình cảnh báo</h5>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="" class="row">
                            <div class="col-md-4">
                                <label for="warning_level" class="form-label">Ngưỡng cảnh báo (số lượng)</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="warning_level" name="warning_level" value="<?php echo $warning_level; ?>" min="1">
                                    <button type="submit" class="btn btn-primary">Áp dụng</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Thống kê nhập xuất theo thời gian -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Thống kê nhập - xuất theo thời gian</h5>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="" class="row">
                            <input type="hidden" name="warning_level" value="<?php echo $warning_level; ?>">
                            
                            <div class="col-md-3">
                                <label for="date_from" class="form-label">Từ ngày</label>
                                <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo $date_from; ?>">
                            </div>
                            
                            <div class="col-md-3">
                                <label for="date_to" class="form-label">Đến ngày</label>
                                <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo $date_to; ?>">
                            </div>
                            
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary">Xem thống kê</button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Bảng tồn kho -->
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Mã SP</th>
                                <th>Tên sản phẩm</th>
                                <th>Loại</th>
                                <th>ĐVT</th>
                                <th>Tổng nhập</th>
                                <th>Tổng bán</th>
                                <th>Tồn kho</th>
                                <th>Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): 
                                $status_class = $product['current_stock'] <= $warning_level ? 'table-warning' : '';
                            ?>
                            <tr class="<?php echo $status_class; ?>">
                                <td><?php echo $product['product_code']; ?></td>
                                <td><?php echo $product['name']; ?></td>
                                <td><?php echo $product['category_name']; ?></td>
                                <td><?php echo $product['unit']; ?></td>
                                <td><?php echo $product['total_import']; ?></td>
                                <td><?php echo $product['total_sold']; ?></td>
                                <td><?php echo $product['current_stock']; ?></td>
                                <td>
                                    <?php if ($product['current_stock'] <= 0): ?>
                                        <span class="badge bg-danger">Hết hàng</span>
                                    <?php elseif ($product['current_stock'] <= $warning_level): ?>
                                        <span class="badge bg-warning">Sắp hết</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">Còn hàng</span>
                                    <?php endif; ?>
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
</body>
</html>