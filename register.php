<?php
// Bắt đầu session
session_start();

// Include file kết nối CSDL
require_once 'db.php';

// Khởi tạo mảng để chứa các lỗi
$errors = [];
$success_message = '';

// 1. Kiểm tra xem biểu mẫu đã được gửi đi chưa (Method POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 2. Lấy dữ liệu từ biểu mẫu và làm sạch (trim)
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];

    // 3. Xác thực (Validate) dữ liệu
    if (empty($username)) {
        $errors[] = "Tên đăng nhập là bắt buộc.";
    }
    if (empty($email)) {
        $errors[] = "Email là bắt buộc.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Định dạng email không hợp lệ.";
    }
    if (empty($password)) {
        $errors[] = "Mật khẩu là bắt buộc.";
    }
    if ($password !== $password_confirm) {
        $errors[] = "Mật khẩu xác nhận không khớp.";
    }

    // 4. Nếu không có lỗi xác thực
    if (empty($errors)) {
        try {
            // 4.1. Kiểm tra xem username hoặc email đã tồn tại chưa (Yêu cầu)
            // Sử dụng Prepared Statements để chống SQL Injection
            $sql_check = "SELECT id FROM users WHERE username = ? OR email = ?";
            $stmt_check = $pdo->prepare($sql_check);
            $stmt_check->execute([$username, $email]);
            $existing_user = $stmt_check->fetch();

            if ($existing_user) {
                $errors[] = "Tên đăng nhập hoặc Email đã tồn tại.";
            } else {
                // 4.2. Băm mật khẩu (Yêu cầu bắt buộc)
                // Sử dụng password_hash()
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // 4.3. Chèn người dùng mới vào CSDL
                // Sử dụng Prepared Statements
                $sql_insert = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)";
                $stmt_insert = $pdo->prepare($sql_insert);
                
                // Thực thi câu lệnh
                if ($stmt_insert->execute([$username, $email, $hashed_password])) {
                    $success_message = "Đăng ký thành công! Vui lòng <a href='login.php'>đăng nhập</a>.";
                } else {
                    $errors[] = "Đã xảy ra lỗi. Vui lòng thử lại.";
                }
            }
        } catch (PDOException $e) {
            // Xử lý lỗi CSDL
            $errors[] = "Lỗi CSDL: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký tài khoản</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
	<link rel="stylesheet" href="style.css">
</head>
<body class="bg-light">

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <h2 class="card-title text-center mb-4">Đăng ký tài khoản</h2>

                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <?php foreach ($errors as $error): ?>
                                    <p class="mb-0"><?php echo htmlspecialchars($error); ?></p>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($success_message)): ?>
                            <div class="alert alert-success">
                                <p class="mb-0"><?php echo $success_message; ?></p>
                            </div>
                        <?php endif; ?>

                        <form action="register.php" method="POST">
                            <div class="mb-3">
                                <label for="username" class="form-label">Tên đăng nhập</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">Mật khẩu</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>

                            <div class="mb-3">
                                <label for="password_confirm" class="form-label">Xác nhận mật khẩu</label>
                                <input type="password" class="form-control" id="password_confirm" name="password_confirm" required>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Đăng ký</button>
                            </div>
                        </form>
                    </div>
                    <div class="card-footer text-center py-3">
                        <small>Đã có tài khoản? <a href="login.php">Đăng nhập ngay</a></small>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>