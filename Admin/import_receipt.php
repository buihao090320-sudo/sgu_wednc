<?php
require_once '../config/database.php';
checkLogin();

$message = '';
$error = '';

// Tạo mã phiếu nhập tự động
function generateReceiptCode($conn) {
    $date = date('Ymd');
    $sql = "SELECT COUNT(*) as total FROM import_receipts WHERE receipt_code LIKE 'PN{$date}%'";
    $result = $conn->query($sql)->fetch(PDO::FETCH_ASSOC);
    $num = $result['total'] + 1;
    return "PN{$date}" . str_pad($num, 3, '0', STR_PAD_LEFT);
}

// Xử lý tạo phiếu nhập mới
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'create') {
        $receipt_code = generateReceiptCode($conn);
        $import_date = $_POST['import_date'];
        $note = $_POST['note'];
        $created_by = $_SESSION['admin_id'];
        
        // Tạo phiếu nhập
        $sql = "INSERT INTO import_receipts (receipt_code, import_date, note, created_by, status) VALUES (?, ?, ?, ?, 0)";
        $stmt = $conn->prepare($sql);
        
        if ($stmt->execute([$receipt_code, $import_date, $note, $created_by])) {
            $receipt_id = $conn->lastInsertId();
            $_SESSION['current_receipt'] = $receipt_id;
            $message = 'Tạo phiếu nhập thành công!';
        } else {
            $error = 'Có lỗi xảy ra!';
        }
    }
    
    // Thêm sản phẩm vào phiếu
    elseif ($_POST['action'] == 'add_product') {
        $receipt_id = $_POST['receipt_id'];
        $product_id = $_POST['product_id'];
        $import_price = $_POST['import_price'];
        $quantity = $_POST['quantity'];
        
        // Kiểm tra sản phẩm đã có trong phiếu chưa
        $check = $conn->prepare("SELECT id, quantity FROM import_receipt_details WHERE receipt_id = ? AND product_id = ?");
        $check->execute([$receipt_id, $product_id]);
        $existing = $check->fetch(PDO::FETCH_ASSOC);
        
        if ($existing) {
            // Cập nhật số lượng
            $new_quantity = $existing['quantity'] + $quantity;
            $sql = "UPDATE import_receipt_details SET quantity = ?, import_price = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$new_quantity, $import_price, $existing['id']]);
        } else {
            // Thêm mới
            $sql = "INSERT INTO import_receipt_details (receipt_id, product_id, import_price, quantity) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$receipt_id, $product_id, $import_price, $quantity]);
        }
        
        $message = 'Thêm sản phẩm vào phiếu thành công!';
    }
    
    // Hoàn thành phiếu nhập
    elseif ($_POST['action'] == 'complete') {
        $receipt_id = $_POST['receipt_id'];
        
        // Cập nhật số lượng tồn kho
        $details = $conn->prepare("SELECT product_id, quantity FROM import_receipt_details WHERE receipt_id = ?");
        $details->execute([$receipt_id]);
        
        while ($row = $details->fetch(PDO::FETCH_ASSOC)) {
            $update = $conn->prepare("UPDATE products SET quantity = quantity + ? WHERE id = ?");
            $update->execute([$row['quantity'], $row['product_id']]);
        }
        
        // Cập nhật trạng thái phiếu
        $sql = "UPDATE import_receipts SET status = 1 WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$receipt_id]);
        
        unset($_SESSION['current_receipt']);
        $message = 'Hoàn thành phiếu nhập thành công!';
    }
}

// Xóa sản phẩm khỏi phiếu
if (isset($_GET['remove'])) {
    $detail_id = $_GET['remove'];
    $sql = "DELETE FROM import_receipt_details WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$detail_id]);
    header('Location: import_receipt.php');
    exit();
}

// Lấy danh sách phiếu nhập chưa hoàn thành
$current_receipt = null;
if (isset($_SESSION['current_receipt'])) {
    $stmt = $conn->prepare("SELECT * FROM import_receipts WHERE id = ? AND status = 0");
    $stmt->execute([$_SESSION['current_receipt']]);
    $current_receipt = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Lấy chi tiết phiếu hiện tại
$receipt_details = [];
if ($current_receipt) {
    $stmt = $conn->prepare("
        SELECT ird.*, p.name as product_name, p.product_code 
        FROM import_receipt_details ird
        JOIN products p ON ird.product_id = p.id
        WHERE ird.receipt_id = ?
    ");
    $stmt->execute([$current_receipt['id']]);
    $receipt_details = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Lấy danh sách sản phẩm để chọn
$products = $conn->query("SELECT id, product_code, name FROM products WHERE is_deleted = 0 ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý nhập kho</title>
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
                    <h1 class="h2">Quản lý nhập kho</h1>
                </div>
                
                <?php if ($message): ?>
                    <div class="alert alert-success"><?php echo $message; ?></div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if (!$current_receipt): ?>
                    <!-- Form tạo phiếu nhập mới -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5>Tạo phiếu nhập mới</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="" onsubmit="return validateReceiptForm(this)">
                                <input type="hidden" name="action" value="create">
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="import_date" class="form-label">Ngày nhập <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" id="import_date" name="import_date" value="<?php echo date('Y-m-d'); ?>" required>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="note" class="form-label">Ghi chú</label>
                                        <input type="text" class="form-control" id="note" name="note">
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">Tạo phiếu nhập</button>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Danh sách phiếu nhập chưa hoàn thành -->
                    <div class="card">
                        <div class="card-header">
                            <h5>Phiếu nhập chưa hoàn thành</h5>
                        </div>
                        <div class="card-body">
                            <?php
                            $pending_receipts = $conn->query("
                                SELECT ir.*, u.fullname as creator 
                                FROM import_receipts ir
                                JOIN users u ON ir.created_by = u.id
                                WHERE ir.status = 0
                                ORDER BY ir.created_at DESC
                            ")->fetchAll(PDO::FETCH_ASSOC);
                            ?>
                            
                            <?php if (count($pending_receipts) > 0): ?>
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Mã phiếu</th>
                                            <th>Ngày nhập</th>
                                            <th>Người tạo</th>
                                            <th>Ghi chú</th>
                                            <th>Thao tác</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($pending_receipts as $receipt): ?>
                                        <tr>
                                            <td><?php echo $receipt['receipt_code']; ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($receipt['import_date'])); ?></td>
                                            <td><?php echo $receipt['creator']; ?></td>
                                            <td><?php echo $receipt['note']; ?></td>
                                            <td>
                                                <a href="?continue=<?php echo $receipt['id']; ?>" class="btn btn-sm btn-info">Tiếp tục</a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <p class="text-muted">Không có phiếu nhập nào chưa hoàn thành.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                <?php else: ?>
                    <!-- Chi tiết phiếu nhập hiện tại -->
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5>Phiếu nhập: <?php echo $current_receipt['receipt_code']; ?></h5>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>Ngày nhập:</strong> <?php echo date('d/m/Y', strtotime($current_receipt['import_date'])); ?>
                                </div>
                                <div class="col-md-6">
                                    <strong>Ghi chú:</strong> <?php echo $current_receipt['note']; ?>
                                </div>
                            </div>
                            
                            <!-- Form thêm sản phẩm -->
                            <form method="POST" action="" class="mb-4" onsubmit="return validateAddProduct(this)">
                                <input type="hidden" name="action" value="add_product">
                                <input type="hidden" name="receipt_id" value="<?php echo $current_receipt['id']; ?>">
                                
                                <div class="row">
                                    <div class="col-md-5 mb-3">
                                        <label for="product_id" class="form-label">Chọn sản phẩm</label>
                                        <select class="form-control" id="product_id" name="product_id" required>
                                            <option value="">-- Chọn sản phẩm --</option>
                                            <?php foreach ($products as $product): ?>
                                                <option value="<?php echo $product['id']; ?>">
                                                    <?php echo $product['product_code'] . ' - ' . $product['name']; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-3 mb-3">
                                        <label for="import_price" class="form-label">Giá nhập</label>
                                        <input type="number" class="form-control" id="import_price" name="import_price" required min="0" step="1000">
                                    </div>
                                    
                                    <div class="col-md-2 mb-3">
                                        <label for="quantity" class="form-label">Số lượng</label>
                                        <input type="number" class="form-control" id="quantity" name="quantity" required min="1">
                                    </div>
                                    
                                    <div class="col-md-2 mb-3 d-flex align-items-end">
                                        <button type="submit" class="btn btn-success w-100">Thêm</button>
                                    </div>
                                </div>
                            </form>
                            
                            <!-- Danh sách sản phẩm trong phiếu -->
                            <h6>Danh sách sản phẩm:</h6>
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Mã SP</th>
                                        <th>Tên sản phẩm</th>
                                        <th>Giá nhập</th>
                                        <th>Số lượng</th>
                                        <th>Thành tiền</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $total = 0;
                                    foreach ($receipt_details as $detail): 
                                        $amount = $detail['import_price'] * $detail['quantity'];
                                        $total += $amount;
                                    ?>
                                    <tr>
                                        <td><?php echo $detail['product_code']; ?></td>
                                        <td><?php echo $detail['product_name']; ?></td>
                                        <td><?php echo number_format($detail['import_price']); ?>đ</td>
                                        <td><?php echo $detail['quantity']; ?></td>
                                        <td><?php echo number_format($amount); ?>đ</td>
                                        <td>
                                            <a href="?remove=<?php echo $detail['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Xóa sản phẩm này?')">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="4" class="text-end">Tổng cộng:</th>
                                        <th><?php echo number_format($total); ?>đ</th>
                                        <th></th>
                                    </tr>
                                </tfoot>
                            </table>
                            
                            <div class="text-end">
                                <form method="POST" action="" style="display: inline;">
                                    <input type="hidden" name="action" value="complete">
                                    <input type="hidden" name="receipt_id" value="<?php echo $current_receipt['id']; ?>">
                                    <button type="submit" class="btn btn-primary" onclick="return confirm('Hoàn thành phiếu nhập?')">
                                        Hoàn thành phiếu nhập
                                    </button>
                                </form>
                                <a href="import_receipt.php?cancel=<?php echo $current_receipt['id']; ?>" class="btn btn-secondary" onclick="return confirm('Hủy phiếu nhập?')">
                                    Hủy
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function validateReceiptForm(form) {
            if (!form.import_date.value) {
                alert('Vui lòng chọn ngày nhập!');
                return false;
            }
            return true;
        }
        
        function validateAddProduct(form) {
            if (!form.product_id.value) {
                alert('Vui lòng chọn sản phẩm!');
                return false;
            }
            if (form.import_price.value <= 0) {
                alert('Giá nhập phải lớn hơn 0!');
                return false;
            }
            if (form.quantity.value <= 0) {
                alert('Số lượng phải lớn hơn 0!');
                return false;
            }
            return true;
        }
    </script>
</body>
</html>