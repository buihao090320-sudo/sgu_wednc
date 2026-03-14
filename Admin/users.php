<?php
require_once '../config/database.php';
checkLogin();

$message = '';
$error = '';

// Xử lý thêm/sửa user
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        // Thêm user
        if ($action == 'add') {
            $username = $_POST['username'];
            $password = md5($_POST['password']);
            $fullname = $_POST['fullname'];
            $email = $_POST['email'];
            $phone = $_POST['phone'];
            $role = $_POST['role'];
            
            // Kiểm tra username đã tồn tại chưa
            $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $check->execute([$username]);
            
            if ($check->rowCount() > 0) {
                $error = 'Tên đăng nhập đã tồn tại!';
            } else {
                $sql = "INSERT INTO users (username, password, fullname, email, phone, role) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                if ($stmt->execute([$username, $password, $fullname, $email, $phone, $role])) {
                    $message = 'Thêm người dùng thành công!';
                } else {
                    $error = 'Có lỗi xảy ra!';
                }
            }
        }
        
        // Sửa user
        elseif ($action == 'edit') {
            $id = $_POST['id'];
            $fullname = $_POST['fullname'];
            $email = $_POST['email'];
            $phone = $_POST['phone'];
            $role = $_POST['role'];
            
            $sql = "UPDATE users SET fullname=?, email=?, phone=?, role=? WHERE id=?";
            $stmt = $conn->prepare($sql);
            if ($stmt->execute([$fullname, $email, $phone, $role, $id])) {
                $message = 'Cập nhật thông tin thành công!';
            }
        }
        
        // Đổi mật khẩu
        elseif ($action == 'changepass') {
            $id = $_POST['id'];
            $password = md5($_POST['password']);
            
            $sql = "UPDATE users SET password=? WHERE id=?";
            $stmt = $conn->prepare($sql);
            if ($stmt->execute([$password, $id])) {
                $message = 'Đổi mật khẩu thành công!';
            }
        }
    }
}

// Xử lý khóa/mở khóa user
if (isset($_GET['toggle_status'])) {
    $id = $_GET['toggle_status'];
    $sql = "UPDATE users SET status = 1 - status WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);
    header('Location: users.php');
    exit();
}

// Lấy danh sách users
$users = $conn->query("SELECT * FROM users WHERE role != 'admin' ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý người dùng</title>
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
                    <h1 class="h2">Quản lý người dùng</h1>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                        <i class="bi bi-plus-circle"></i> Thêm người dùng
                    </button>
                </div>
                
                <?php if ($message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Tên đăng nhập</th>
                                <th>Họ tên</th>
                                <th>Email</th>
                                <th>Điện thoại</th>
                                <th>Vai trò</th>
                                <th>Trạng thái</th>
                                <th>Ngày tạo</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><?php echo $user['username']; ?></td>
                                <td><?php echo $user['fullname']; ?></td>
                                <td><?php echo $user['email']; ?></td>
                                <td><?php echo $user['phone']; ?></td>
                                <td>
                                    <?php if ($user['role'] == 'admin'): ?>
                                        <span class="badge bg-danger">Admin</span>
                                    <?php else: ?>
                                        <span class="badge bg-info">User</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($user['status'] == 1): ?>
                                        <span class="badge bg-success">Hoạt động</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Đã khóa</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-warning" onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-info" onclick="changePassword(<?php echo $user['id']; ?>)">
                                        <i class="bi bi-key"></i>
                                    </button>
                                    <a href="?toggle_status=<?php echo $user['id']; ?>" class="btn btn-sm <?php echo $user['status'] == 1 ? 'btn-secondary' : 'btn-success'; ?>">
                                        <i class="bi bi-<?php echo $user['status'] == 1 ? 'lock' : 'unlock'; ?>"></i>
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
    
    <!-- Modal thêm user -->
    <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="" onsubmit="return validateUserForm(this)">
                    <div class="modal-header">
                        <h5 class="modal-title">Thêm người dùng</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="mb-3">
                            <label for="username" class="form-label">Tên đăng nhập <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Mật khẩu <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="password" name="password" required minlength="6">
                        </div>
                        
                        <div class="mb-3">
                            <label for="fullname" class="form-label">Họ tên</label>
                            <input type="text" class="form-control" id="fullname" name="fullname">
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email">
                        </div>
                        
                        <div class="mb-3">
                            <label for="phone" class="form-label">Điện thoại</label>
                            <input type="text" class="form-control" id="phone" name="phone" pattern="[0-9]{10,11}">
                        </div>
                        
                        <div class="mb-3">
                            <label for="role" class="form-label">Vai trò</label>
                            <select class="form-control" id="role" name="role">
                                <option value="user">User</option>
                                <option value="admin">Admin</option>
                            </select>
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
    
    <!-- Modal sửa user -->
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="">
                    <div class="modal-header">
                        <h5 class="modal-title">Sửa thông tin người dùng</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" id="edit_id">
                        
                        <div class="mb-3">
                            <label for="edit_username" class="form-label">Tên đăng nhập</label>
                            <input type="text" class="form-control" id="edit_username" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_fullname" class="form-label">Họ tên</label>
                            <input type="text" class="form-control" id="edit_fullname" name="fullname">
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="edit_email" name="email">
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_phone" class="form-label">Điện thoại</label>
                            <input type="text" class="form-control" id="edit_phone" name="phone">
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_role" class="form-label">Vai trò</label>
                            <select class="form-control" id="edit_role" name="role">
                                <option value="user">User</option>
                                <option value="admin">Admin</option>
                            </select>
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
    
    <!-- Modal đổi mật khẩu -->
    <div class="modal fade" id="changePassModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="" onsubmit="return validatePassword(this)">
                    <div class="modal-header">
                        <h5 class="modal-title">Đổi mật khẩu</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="changepass">
                        <input type="hidden" name="id" id="pass_id">
                        
                        <div class="mb-3">
                            <label for="new_password" class="form-label">Mật khẩu mới <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="new_password" name="password" required minlength="6">
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Xác nhận mật khẩu <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="confirm_password" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                        <button type="submit" class="btn btn-primary">Đổi mật khẩu</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/script.js"></script>
    <script>
        function editUser(user) {
            document.getElementById('edit_id').value = user.id;
            document.getElementById('edit_username').value = user.username;
            document.getElementById('edit_fullname').value = user.fullname || '';
            document.getElementById('edit_email').value = user.email || '';
            document.getElementById('edit_phone').value = user.phone || '';
            document.getElementById('edit_role').value = user.role;
            
            new bootstrap.Modal(document.getElementById('editUserModal')).show();
        }
        
        function changePassword(id) {
            document.getElementById('pass_id').value = id;
            document.getElementById('new_password').value = '';
            document.getElementById('confirm_password').value = '';
            
            new bootstrap.Modal(document.getElementById('changePassModal')).show();
        }
        
        function validateUserForm(form) {
            if (form.password.value.length < 6) {
                alert('Mật khẩu phải có ít nhất 6 ký tự!');
                return false;
            }
            return true;
        }
        
        function validatePassword(form) {
            if (form.password.value.length < 6) {
                alert('Mật khẩu phải có ít nhất 6 ký tự!');
                return false;
            }
            if (form.password.value != document.getElementById('confirm_password').value) {
                alert('Mật khẩu xác nhận không khớp!');
                return false;
            }
            return true;
        }
    </script>
</body>
</html>