<?php
require_once '../config/database.php';
checkLogin();

$message = '';
$error = '';

// Xử lý thêm/sửa/xóa sản phẩm
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        // Thêm sản phẩm
        if ($action == 'add') {
            $product_code = $_POST['product_code'];
            $name = $_POST['name'];
            $category_id = $_POST['category_id'];
            $description = $_POST['description'];
            $unit = $_POST['unit'];
            $quantity = $_POST['quantity'];
            $profit_percent = $_POST['profit_percent'];
            $status = isset($_POST['status']) ? 1 : 0;
            
            // Xử lý upload ảnh
            $image = '';
            if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                $target_dir = "../uploads/products/";
                if (!file_exists($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }
                $image = time() . '_' . basename($_FILES['image']['name']);
                $target_file = $target_dir . $image;
                move_uploaded_file($_FILES['image']['tmp_name'], $target_file);
            }
            
            $sql = "INSERT INTO products (product_code, name, category_id, description, unit, quantity, image, profit_percent, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            if ($stmt->execute([$product_code, $name, $category_id, $description, $unit, $quantity, $image, $profit_percent, $status])) {
                $message = 'Thêm sản phẩm thành công!';
            } else {
                $error = 'Có lỗi xảy ra!';
            }
        }
        
        // Sửa sản phẩm
        elseif ($action == 'edit') {
            $id = $_POST['id'];
            $product_code = $_POST['product_code'];
            $name = $_POST['name'];
            $category_id = $_POST['category_id'];
            $description = $_POST['description'];
            $unit = $_POST['unit'];
            $profit_percent = $_POST['profit_percent'];
            $status = isset($_POST['status']) ? 1 : 0;
            
            // Lấy ảnh cũ
            $stmt = $conn->prepare("SELECT image FROM products WHERE id = ?");
            $stmt->execute([$id]);
            $old_image = $stmt->fetch(PDO::FETCH_ASSOC)['image'];
            $image = $old_image;
            
            // Xử lý upload ảnh mới
            if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                $target_dir = "../uploads/products/";
                $image = time() . '_' . basename($_FILES['image']['name']);
                $target_file = $target_dir . $image;
                move_uploaded_file($_FILES['image']['tmp_name'], $target_file);
                
                // Xóa ảnh cũ
                if ($old_image && file_exists($target_dir . $old_image)) {
                    unlink($target_dir . $old_image);
                }
            }
            
            // Xóa ảnh nếu được yêu cầu
            if (isset($_POST['remove_image']) && $_POST['remove_image'] == 1) {
                if ($old_image && file_exists("../uploads/products/" . $old_image)) {
                    unlink("../uploads/products/" . $old_image);
                }
                $image = '';
            }
            
            $sql = "UPDATE products SET product_code=?, name=?, category_id=?, description=?, unit=?, image=?, profit_percent=?, status=? WHERE id=?";
            $stmt = $conn->prepare($sql);
            if ($stmt->execute([$product_code, $name, $category_id, $description, $unit, $image, $profit_percent, $status, $id])) {
                $message = 'Cập nhật sản phẩm thành công!';
            } else {
                $error = 'Có lỗi xảy ra!';
            }
        }
    }
}

// Xử lý xóa sản phẩm
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    
    // Kiểm tra sản phẩm đã được nhập hàng chưa
    $check = $conn->prepare("SELECT COUNT(*) as total FROM import_receipt_details WHERE product_id = ?");
    $check->execute([$id]);
    $imported = $check->fetch(PDO::FETCH_ASSOC)['total'] > 0;
    
    if ($imported) {
        // Đã nhập hàng -> xóa mềm
        $sql = "UPDATE products SET is_deleted = 1, status = 0 WHERE id = ?";
        $message = 'Đã ẩn sản phẩm (đã từng nhập hàng)';
    } else {
        // Chưa nhập hàng -> xóa hẳn
        $sql = "DELETE FROM products WHERE id = ?";
        $message = 'Xóa sản phẩm thành công!';
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);
    header('Location: products.php');
    exit();
}

// Lấy danh sách loại sản phẩm
$categories = $conn->query("SELECT * FROM categories")->fetchAll(PDO::FETCH_ASSOC);

// Lấy danh sách sản phẩm
$products = $conn->query("SELECT p.*, c.name as category_name 
                          FROM products p 
                          LEFT JOIN categories c ON p.category_id = c.id 
                          WHERE p.is_deleted = 0 
                          ORDER BY p.id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý sản phẩm</title>
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
                    <h1 class="h2">Quản lý sản phẩm</h1>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
                        <i class="bi bi-plus-circle"></i> Thêm sản phẩm
                    </button>
                </div>
                
                <?php if ($message): ?>
                    <div class="alert alert-success"><?php echo $message; ?></div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Mã SP</th>
                                <th>Hình ảnh</th>
                                <th>Tên sản phẩm</th>
                                <th>Loại</th>
                                <th>ĐVT</th>
                                <th>Số lượng</th>
                                <th>% LN</th>
                                <th>Trạng thái</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                            <tr>
                                <td><?php echo $product['id']; ?></td>
                                <td><?php echo $product['product_code']; ?></td>
                                <td>
                                    <?php if ($product['image']): ?>
                                        <img src="../uploads/products/<?php echo $product['image']; ?>" width="50" height="50" style="object-fit: cover;">
                                    <?php else: ?>
                                        <span class="text-muted">Chưa có ảnh</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $product['name']; ?></td>
                                <td><?php echo $product['category_name']; ?></td>
                                <td><?php echo $product['unit']; ?></td>
                                <td><?php echo $product['quantity']; ?></td>
                                <td><?php echo $product['profit_percent']; ?>%</td>
                                <td>
                                    <?php if ($product['status'] == 1): ?>
                                        <span class="badge bg-success">Đang bán</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Ẩn</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-warning" onclick="editProduct(<?php echo htmlspecialchars(json_encode($product)); ?>)">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <a href="?delete=<?php echo $product['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bạn có chắc muốn xóa?')">
                                        <i class="bi bi-trash"></i>
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
    
    <!-- Modal thêm sản phẩm -->
    <div class="modal fade" id="addProductModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="" enctype="multipart/form-data" onsubmit="return validateProductForm(this)">
                    <div class="modal-header">
                        <h5 class="modal-title">Thêm sản phẩm</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="product_code" class="form-label">Mã sản phẩm <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="product_code" name="product_code" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Tên sản phẩm <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="category_id" class="form-label">Loại sản phẩm</label>
                                <select class="form-control" id="category_id" name="category_id">
                                    <option value="">-- Chọn loại --</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>"><?php echo $cat['name']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="unit" class="form-label">Đơn vị tính</label>
                                <input type="text" class="form-control" id="unit" name="unit" placeholder="cái, chiếc, hộp,...">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="quantity" class="form-label">Số lượng ban đầu</label>
                                <input type="number" class="form-control" id="quantity" name="quantity" value="0" min="0">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="profit_percent" class="form-label">Tỉ lệ lợi nhuận (%)</label>
                                <input type="number" class="form-control" id="profit_percent" name="profit_percent" value="0" min="0" step="0.01">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Mô tả</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="image" class="form-label">Hình ảnh</label>
                            <input type="file" class="form-control" id="image" name="image" accept="image/*">
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="status" name="status" checked>
                                <label class="form-check-label" for="status">
                                    Hiển thị sản phẩm (đang bán)
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                        <button type="submit" class="btn btn-primary">Thêm</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal sửa sản phẩm -->
    <div class="modal fade" id="editProductModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="modal-header">
                        <h5 class="modal-title">Sửa sản phẩm</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" id="edit_id">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_product_code" class="form-label">Mã sản phẩm <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="edit_product_code" name="product_code" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="edit_name" class="form-label">Tên sản phẩm <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="edit_name" name="name" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_category_id" class="form-label">Loại sản phẩm</label>
                                <select class="form-control" id="edit_category_id" name="category_id">
                                    <option value="">-- Chọn loại --</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>"><?php echo $cat['name']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="edit_unit" class="form-label">Đơn vị tính</label>
                                <input type="text" class="form-control" id="edit_unit" name="unit">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_profit_percent" class="form-label">Tỉ lệ lợi nhuận (%)</label>
                                <input type="number" class="form-control" id="edit_profit_percent" name="profit_percent" step="0.01">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_description" class="form-label">Mô tả</label>
                            <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Hình ảnh hiện tại</label>
                            <div id="current_image"></div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_image" class="form-label">Thay đổi hình ảnh</label>
                            <input type="file" class="form-control" id="edit_image" name="image" accept="image/*">
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="remove_image" name="remove_image" value="1">
                                <label class="form-check-label" for="remove_image">
                                    Xóa hình ảnh hiện tại
                                </label>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="edit_status" name="status">
                                <label class="form-check-label" for="edit_status">
                                    Hiển thị sản phẩm (đang bán)
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                        <button type="submit" class="btn btn-primary">Cập nhật</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editProduct(product) {
            document.getElementById('edit_id').value = product.id;
            document.getElementById('edit_product_code').value = product.product_code;
            document.getElementById('edit_name').value = product.name;
            document.getElementById('edit_category_id').value = product.category_id || '';
            document.getElementById('edit_unit').value = product.unit || '';
            document.getElementById('edit_description').value = product.description || '';
            document.getElementById('edit_profit_percent').value = product.profit_percent;
            document.getElementById('edit_status').checked = product.status == 1;
            
            // Hiển thị ảnh hiện tại
            const currentImage = document.getElementById('current_image');
            if (product.image) {
                currentImage.innerHTML = '<img src="../uploads/products/' + product.image + '" width="100" height="100" style="object-fit: cover;">';
            } else {
                currentImage.innerHTML = '<span class="text-muted">Chưa có ảnh</span>';
            }
            
            new bootstrap.Modal(document.getElementById('editProductModal')).show();
        }
        
        function validateProductForm(form) {
            if (form.product_code.value.trim() == '') {
                alert('Vui lòng nhập mã sản phẩm!');
                return false;
            }
            if (form.name.value.trim() == '') {
                alert('Vui lòng nhập tên sản phẩm!');
                return false;
            }
            if (form.quantity.value < 0) {
                alert('Số lượng không thể âm!');
                return false;
            }
            if (form.profit_percent.value < 0) {
                alert('Tỉ lệ lợi nhuận không thể âm!');
                return false;
            }
            return true;
        }
    </script>
</body>
</html>