<?php
require_once '../config/database.php';
checkLogin();

$message = '';
$error = '';

// Xử lý thêm loại
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'add') {
        $name = $_POST['name'];
        $description = $_POST['description'];
        
        $sql = "INSERT INTO categories (name, description) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        if ($stmt->execute([$name, $description])) {
            $message = 'Thêm loại sản phẩm thành công!';
        } else {
            $error = 'Có lỗi xảy ra!';
        }
    }
    
    // Sửa loại
    elseif ($_POST['action'] == 'edit') {
        $id = $_POST['id'];
        $name = $_POST['name'];
        $description = $_POST['description'];
        
        $sql = "UPDATE categories SET name=?, description=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        if ($stmt->execute([$name, $description, $id])) {
            $message = 'Cập nhật thành công!';
        }
    }
}

// Xử lý xóa loại
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    
    // Kiểm tra xem có sản phẩm nào thuộc loại này không
    $check = $conn->prepare("SELECT COUNT(*) as total FROM products WHERE category_id = ?");
    $check->execute([$id]);
    $count = $check->fetch(PDO::FETCH_ASSOC)['total'];
    
    if ($count > 0) {
        $error = 'Không thể xóa vì đang có sản phẩm thuộc loại này!';
    } else {
        $sql = "DELETE FROM categories WHERE id = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt->execute([$id])) {
            $message = 'Xóa thành công!';
        }
    }
}

// Lấy danh sách loại
$categories = $conn->query("SELECT * FROM categories ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý loại sản phẩm</title>
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
                    <h1 class="h2">Quản lý loại sản phẩm</h1>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                        <i class="bi bi-plus-circle"></i> Thêm loại
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
                                <th>Tên loại</th>
                                <th>Mô tả</th>
                                <th>Ngày tạo</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $cat): ?>
                            <tr>
                                <td><?php echo $cat['id']; ?></td>
                                <td><?php echo $cat['name']; ?></td>
                                <td><?php echo $cat['description']; ?></td>
                                <td><?php echo date('d/m/Y', strtotime($cat['created_at'])); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-warning" onclick="editCategory(<?php echo htmlspecialchars(json_encode($cat)); ?>)">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <a href="?delete=<?php echo $cat['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bạn có chắc muốn xóa?')">
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
    
    <!-- Modal thêm loại -->
    <div class="modal fade" id="addCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="" onsubmit="return validateForm(this)">
                    <div class="modal-header">
                        <h5 class="modal-title">Thêm loại sản phẩm</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Tên loại <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Mô tả</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
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
    
    <!-- Modal sửa loại -->
    <div class="modal fade" id="editCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="">
                    <div class="modal-header">
                        <h5 class="modal-title">Sửa loại sản phẩm</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" id="edit_id">
                        
                        <div class="mb-3">
                            <label for="edit_name" class="form-label">Tên loại <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_name" name="name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_description" class="form-label">Mô tả</label>
                            <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
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
        function editCategory(cat) {
            document.getElementById('edit_id').value = cat.id;
            document.getElementById('edit_name').value = cat.name;
            document.getElementById('edit_description').value = cat.description || '';
            
            new bootstrap.Modal(document.getElementById('editCategoryModal')).show();
        }
        
        function validateForm(form) {
            if (form.name.value.trim() == '') {
                alert('Vui lòng nhập tên loại!');
                return false;
            }
            return true;
        }
    </script>
</body>
</html>