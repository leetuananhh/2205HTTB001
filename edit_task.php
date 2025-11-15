<?php
// 1. NGƯỜI BẢO VỆ: Kiểm tra xem đã đăng nhập chưa
require_once 'auth_check.php'; 

// 2. KẾT NỐI CSDL: Lấy biến $pdo để sử dụng
require_once 'db.php';

// Lấy ID người dùng đang đăng nhập
$user_id = $_SESSION['user_id'];
$errors = [];
$task = null; // Biến để lưu thông tin công việc

// --- LOGIC XỬ LÝ ---

// 3. XỬ LÝ (POST) - CẬP NHẬT CÔNG VIỆC
// Kiểm tra nếu biểu mẫu được gửi đi
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Lấy dữ liệu từ form
    $task_id = $_POST['task_id'];
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $due_date = !empty($_POST['due_date']) ? $_POST['due_date'] : NULL;
    $status = $_POST['status'];

    // Xác thực dữ liệu
    if (empty($title)) {
        $errors[] = "Tiêu đề là bắt buộc.";
    }
    // Kiểm tra các trạng thái hợp lệ
    $allowed_statuses = ['pending', 'in_progress', 'completed'];
    if (!in_array($status, $allowed_statuses)) {
        $errors[] = "Trạng thái không hợp lệ.";
    }

    if (empty($errors)) {
        try {
            // (U) Update: Dùng Prepared Statements
            // Quan trọng: Phải kiểm tra cả user_id để đảm bảo bảo mật!
            $sql_update = "UPDATE tasks SET title = ?, description = ?, due_date = ?, status = ? 
                           WHERE id = ? AND user_id = ?";
            $stmt_update = $pdo->prepare($sql_update);
            $stmt_update->execute([$title, $description, $due_date, $status, $task_id, $user_id]);

            // Sau khi cập nhật, chuyển hướng về trang chủ
            // (Chúng ta có thể thêm một tham số để báo thành công nếu muốn)
            header("Location: index.php?update_success=1");
            exit;

        } catch (PDOException $e) {
            $errors[] = "Lỗi CSDL: " . $e->getMessage();
        }
    }
}

// 4. XỬ LÝ (GET) - LẤY THÔNG TIN CÔNG VIỆC ĐỂ SỬA
// Kiểm tra xem có 'id' trên URL không (từ link ở index.php)
if (isset($_GET['id'])) {
    $task_id = $_GET['id'];

    try {
        // (R) Select: Lấy thông tin công việc
        // Quan trọng: Phải kiểm tra cả user_id!
        $sql_select = "SELECT * FROM tasks WHERE id = ? AND user_id = ?";
        $stmt_select = $pdo->prepare($sql_select);
        $stmt_select->execute([$task_id, $user_id]);
        $task = $stmt_select->fetch();

        // Nếu không tìm thấy công việc (hoặc công việc không thuộc về user này)
        if (!$task) {
            // Chuyển hướng về trang chủ để tránh lỗi
            header("Location: index.php");
            exit;
        }

    } catch (PDOException $e) {
        $errors[] = "Lỗi CSDL: " . $e->getMessage();
    }
} else {
    // Nếu không có ID, không thể sửa, chuyển về trang chủ
    header("Location: index.php");
    exit;
}

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chỉnh sửa công việc</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
	<link rel="stylesheet" href="style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.5/font/bootstrap-icons.min.css" rel="stylesheet">
</head>
<body class="bg-light">

    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="index.php">Ứng dụng Quản lý Công việc</a>
            <span class="navbar-text text-white me-3">
                Chào, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>!
            </span>
            <a href="logout.php" class="btn btn-outline-light">
                <i class="bi bi-box-arrow-right"></i> Đăng xuất
            </a>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <?php foreach ($errors as $error): ?>
                            <p class="mb-0"><?php echo htmlspecialchars($error); ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if ($task): ?>
                    <div class="card shadow-sm">
                        <div class="card-header">
                            <h5 class="mb-0">Chỉnh sửa công việc</h5>
                        </div>
                        <div class="card-body">
                            <form action="edit_task.php" method="POST">
                                
                                <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">

                                <div class="mb-3">
                                    <label for="title" class="form-label">Tiêu đề (Bắt buộc)</label>
                                    <input type="text" class="form-control" id="title" name="title" 
                                           value="<?php echo htmlspecialchars($task['title']); ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label for="description" class="form-label">Mô tả</label>
                                    <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($task['description']); ?></textarea>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="due_date" class="form-label">Ngày hết hạn</label>
                                        <input type="date" class="form-control" id="due_date" name="due_date" 
                                               value="<?php echo htmlspecialchars($task['due_date']); ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="status" class="form-label">Trạng thái</label>
                                        <select class="form-select" id="status" name="status">
                                            <option value="pending" <?php if ($task['status'] == 'pending') echo 'selected'; ?>>
                                                Đang chờ
                                            </option>
                                            <option value="in_progress" <?php if ($task['status'] == 'in_progress') echo 'selected'; ?>>
                                                Đang thực hiện
                                            </option>
                                            <option value="completed" <?php if ($task['status'] == 'completed') echo 'selected'; ?>>
                                                Hoàn thành
                                            </option>
                                        </select>
                                    </div>
                                </div>

                                <div class="mt-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-save"></i> Lưu thay đổi
                                    </button>
                                    <a href="index.php" class="btn btn-secondary">
                                        <i class="bi bi-x-circle"></i> Hủy
                                    </a>
                                </div>

                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning">
                        Không tìm thấy công việc được yêu cầu hoặc bạn không có quyền sửa công việc này.
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div> <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>