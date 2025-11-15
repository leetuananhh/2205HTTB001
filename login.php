<?php
// Luôn bắt đầu session ở đầu file
session_start();

// 1. Kiểm tra xem người dùng đã đăng nhập chưa
// Nếu đã đăng nhập, chuyển hướng họ đến trang index.php
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit; // Dừng script
}

// Include file kết nối CSDL
require_once 'db.php';

// Khởi tạo biến lỗi
$error_message = '';

// 2. Kiểm tra xem biểu mẫu đã được gửi đi chưa (Method POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 3. Lấy dữ liệu từ biểu mẫu
    $username = $_POST['username'];
    $password = $_POST['password'];

    // 4. Xác thực dữ liệu cơ bản
    if (empty($username) || empty($password)) {
        $error_message = "Vui lòng nhập cả tên đăng nhập và mật khẩu.";
    } else {
        try {
            // 5. Tìm user trong CSDL
            // Sử dụng Prepared Statements để chống SQL Injection
            $sql = "SELECT id, username, password FROM users WHERE username = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$username]);

            // Lấy thông tin user
            $user = $stmt->fetch();

            // 6. Xác thực người dùng
            // Kiểm tra xem user có tồn tại VÀ mật khẩu có khớp không
            // Sử dụng password_verify() (Yêu cầu bắt buộc)
            if ($user && password_verify($password, $user['password'])) {
                
                // 7. Đăng nhập thành công: Khởi tạo Session
                // Lưu trữ thông tin cần thiết vào Session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];

                // 8. Chuyển hướng đến trang quản lý công việc (index.php)
                header("Location: index.php");
                exit; // Dừng script sau khi chuyển hướng

            } else {
                // Nếu thông tin không khớp
                $error_message = "Tên đăng nhập hoặc mật khẩu không chính xác.";
            }

        } catch (PDOException $e) {
            $error_message = "Lỗi CSDL: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
	<link rel="stylesheet" href="style.css">
</head>
<body class="bg-light">

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <h2 class="card-title text-center mb-4">Đăng nhập</h2>

                        <?php if (!empty($error_message)): ?>
                            <div class="alert alert-danger">
                                <?php echo htmlspecialchars($error_message); ?>
                            </div>
                        <?php endif; ?>

                        <form action="login.php" method="POST">
                            <div class="mb-3">
                                <label for="username" class="form-label">Tên đăng nhập</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">Mật khẩu</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Đăng nhập</button>
                            </div>
                        </form>
                    </div>
                    <div class="card-footer text-center py-3">
                        <small>Chưa có tài khoản? <a href="register.php">Đăng ký ngay</a></small>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>