<?php
// 1. NGƯỜI BẢO VỆ: Kiểm tra xem đã đăng nhập chưa
// File này đã bao gồm session_start()
require_once 'auth_check.php'; 

// 2. KẾT NỐI CSDL: Lấy biến $pdo để sử dụng
require_once 'db.php';

// 3. Lấy thông tin người dùng từ Session
// Chúng ta có $logged_in_user_id từ file auth_check.php
$user_id = $logged_in_user_id; 
$username = $_SESSION['username']; // Lấy username để chào

$errors = [];
$success_message = '';

// --- PHẦN XỬ LÝ LOGIC (C, U, D) ---

// 4. XỬ LÝ (C) CREATE - THÊM CÔNG VIỆC MỚI
// Kiểm tra nếu là method POST và có action là 'add_task'
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add_task') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    // Kiểm tra due_date có rỗng không
    $due_date = !empty($_POST['due_date']) ? $_POST['due_date'] : NULL;

    if (empty($title)) {
        $errors[] = "Tiêu đề công việc là bắt buộc.";
    } else {
        try {
            // Sử dụng Prepared Statements để chống SQL Injection
            $sql = "INSERT INTO tasks (user_id, title, description, due_date) VALUES (?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$user_id, $title, $description, $due_date]);
            $success_message = "Đã thêm công việc mới thành công!";
        } catch (PDOException $e) {
            $errors[] = "Lỗi CSDL: " . $e->getMessage();
        }
    }
}

// 5. XỬ LÝ (U) UPDATE - ĐÁNH DẤU HOÀN THÀNH
if (isset($_GET['action']) && $_GET['action'] == 'toggle_status' && isset($_GET['id'])) {
    $task_id = $_GET['id'];
    // Lấy trạng thái hiện tại
    $current_status = $_GET['status'];
    // Chuyển đổi trạng thái:
    $new_status = ($current_status == 'completed') ? 'pending' : 'completed';

    try {
        // Luôn kiểm tra user_id để đảm bảo user chỉ update task của chính mình
        $sql = "UPDATE tasks SET status = ? WHERE id = ? AND user_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$new_status, $task_id, $user_id]);
        $success_message = "Đã cập nhật trạng thái công việc!";
    } catch (PDOException $e) {
        $errors[] = "Lỗi CSDL: " . $e->getMessage();
    }
}

// 6. XỬ LÝ (D) DELETE - XÓA CÔNG VIỆC
if (isset($_GET['action']) && $_GET['action'] == 'delete_task' && isset($_GET['id'])) {
    $task_id = $_GET['id'];

    try {
        // Luôn kiểm tra user_id để đảm bảo user chỉ xóa task của chính mình
        $sql = "DELETE FROM tasks WHERE id = ? AND user_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$task_id, $user_id]);
        $success_message = "Đã xóa công việc thành công!";
    } catch (PDOException $e) {
        $errors[] = "Lỗi CSDL: " . $e->getMessage();
    }
}


// --- PHẦN (R) READ - LẤY DỮ LIỆU ĐỂ HIỂN THỊ ---

// 7. Xử lý Lọc (Filter) và Sắp xếp (Sort)
$filter_status = $_GET['filter_status'] ?? 'all'; // 'all', 'pending', 'in_progress', 'completed'
$sort_by = $_GET['sort_by'] ?? 'due_date_asc'; // 'due_date_asc', 'due_date_desc', 'created_at_desc'

// Xây dựng câu lệnh SQL
$sql_select = "SELECT * FROM tasks WHERE user_id = ?";
$params = [$user_id]; // Tham số cho prepared statement

// Thêm điều kiện Lọc
if ($filter_status != 'all') {
    $sql_select .= " AND status = ?";
    $params[] = $filter_status;
}

// Thêm điều kiện Sắp xếp
switch ($sort_by) {
    case 'due_date_desc':
        $sql_select .= " ORDER BY due_date DESC";
        break;
    case 'created_at_desc':
        $sql_select .= " ORDER BY created_at DESC";
        break;
    case 'due_date_asc':
    default:
        // Sắp xếp các giá trị NULL (không có ngày hết hạn) ở cuối
        $sql_select .= " ORDER BY ISNULL(due_date), due_date ASC";
        break;
}

// Thực thi câu lệnh SELECT
$stmt_select = $pdo->prepare($sql_select);
$stmt_select->execute($params);
$tasks = $stmt_select->fetchAll();

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale-1.0">
    <title>Bảng điều khiển - Công việc</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.5/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        /* CSS nhỏ để gạch ngang công việc đã hoàn thành */
        .task-completed {
            text-decoration: line-through;
            color: #6c757d; /* Màu xám */
        }
    </style>
</head>
<body class="bg-light">

    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="index.php">Ứng dụng Quản lý Nhân Viên</a>
            
            <span class="navbar-text text-white me-3">
                Chào, <strong><?php echo htmlspecialchars($username); ?></strong>!
            </span>
            
            <a href="logout.php" class="btn btn-outline-light">
                <i class="bi bi-box-arrow-right"></i> Đăng xuất
            </a>
        </div>
    </nav>

    <div class="container mt-4">

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $error): ?>
                    <p class="mb-0"><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php echo htmlspecialchars($success_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>


        <div class="card shadow-sm mb-4">
            <div class="card-header">
                <h5 class="mb-0">Thêm nhân viên mới</h5>
            </div>
            <div class="card-body">
                <form action="index.php" method="POST">
                    <input type="hidden" name="action" value="add_task">
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="title" class="form-label">Tên nhân viên (Bắt buộc)</label>
                            <input type="text" class="form-control" id="title" name="title" required>
                        </div>
                        <div class="col-md-6">
                            <label for="due_date" class="form-label">Ngày sinh</label>
                            <input type="date" class="form-control" id="due_date" name="due_date">
                        </div>
                        <div class="col-12">
                            <label for="description" class="form-label">Mô tả (Tùy chọn)</label>
                            <textarea class="form-control" id="description" name="description" rows="2"></textarea>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-plus-lg"></i> Thêm nhân viên
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>


        <div class="card shadow-sm">
            <div class="card-header">
                <h5 class="mb-0">Danh sách nhân viên của bạn</h5>
            </div>
            <div class="card-body">

                <form action="index.php" method="GET" class="row g-3 mb-3 pb-3 border-bottom">
                    <div class="col-md-5">
                        <label for="filter_status" class="form-label">Lọc theo trạng thái</label>
                        <select name="filter_status" id="filter_status" class="form-select">
                            <option value="all" <?php if ($filter_status == 'all') echo 'selected'; ?>>Tất cả</option>
                            <option value="pending" <?php if ($filter_status == 'pending') echo 'selected'; ?>>Đang chờ</option>
                            <option value="in_progress" <?php if ($filter_status == 'in_progress') echo 'selected'; ?>>Đang thực hiện</option>
                            <option value="completed" <?php if ($filter_status == 'completed') echo 'selected'; ?>>Hoàn thành</option>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label for="sort_by" class="form-label">Sắp xếp theo</label>
                        <select name="sort_by" id="sort_by" class="form-select">
                            <option value="due_date_asc" <?php if ($sort_by == 'due_date_asc') echo 'selected'; ?>>Ngày hết hạn (Gần nhất)</option>
                            <option value="due_date_desc" <?php if ($sort_by == 'due_date_desc') echo 'selected'; ?>>Ngày hết hạn (Xa nhất)</option>
                            <option value="created_at_desc" <?php if ($sort_by == 'created_at_desc') echo 'selected'; ?>>Ngày tạo (Mới nhất)</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-secondary w-100">Lọc / Sắp xếp</button>
                    </div>
                </form>
                
                
                <div class="row pt-3"> <?php if (empty($tasks)): ?>
                        <div class="col-12">
                            <div class="alert alert-info text-center">
                                Bạn chưa có nhân viên nào (hoặc không tìm thấy kết quả lọc).
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($tasks as $task): ?>
                            <div class="col-md-6 col-lg-4 mb-4">
                                
                                <div class="card shadow-sm h-100 <?php if ($task['status'] == 'completed') echo 'border-success'; ?>">
                                    
                                    <div class="card-body d-flex flex-column">
                                        
                                        <div class="flex-grow-1">
                                            <h5 class="card-title <?php if ($task['status'] == 'completed') echo 'task-completed'; ?>">
                                                <a href="index.php?action=toggle_status&id=<?php echo $task['id']; ?>&status=<?php echo $task['status']; ?>"
                                                   class="<?php echo $task['status'] == 'completed' ? 'text-success' : 'text-secondary'; ?>"
                                                   title="<?php echo $task['status'] == 'completed' ? 'Đánh dấu Chưa hoàn thành' : 'Đánh dấu Hoàn thành'; ?>">
                                                    
                                                    <?php if ($task['status'] == 'completed'): ?>
                                                        <i class="bi bi-check-square-fill"></i>
                                                    <?php else: ?>
                                                        <i class="bi bi-square"></i>
                                                    <?php endif; ?>
                                                </a>
                                                <span class="ms-2"><?php echo htmlspecialchars($task['title']); ?></span>
                                            </h5>

                                            <span class="badge mb-2
                                                <?php 
                                                    if ($task['status'] == 'completed') echo 'bg-success';
                                                    elseif ($task['status'] == 'in_progress') echo 'bg-info';
                                                    else echo 'bg-warning text-dark';
                                                ?>">
                                                <?php echo htmlspecialchars($task['status']); ?>
                                            </span>

                                            <?php if (!empty($task['description'])): ?>
                                                <p class="card-text text-muted small">
                                                    <?php echo nl2br(htmlspecialchars($task['description'])); // Dùng nl2br để giữ xuống dòng ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>

                                        <div>
                                            <?php if (!empty($task['due_date'])): ?>
                                                <small class="text-danger d-block mb-2">
                                                    <i class="bi bi-calendar-event"></i>
                                                    Hết hạn: <?php echo date("d/m/Y", strtotime($task['due_date'])); ?>
                                                </small>
                                            <?php endif; ?>
                                            
                                            <hr class="my-2">

                                            <div class="text-end"> <a href="edit_task.php?id=<?php echo $task['id']; ?>" class="btn btn-sm btn-outline-primary" title="Chỉnh sửa">
                                                    <i class="bi bi-pencil"></i> Sửa
                                                </a>
                                                
                                                <a href="index.php?action=delete_task&id=<?php echo $task['id']; ?>" 
                                                   class="btn btn-sm btn-outline-danger" 
                                                   title="Xóa công việc"
                                                   onclick="return confirm('Bạn có chắc chắn muốn xóa công việc này?');">
                                                    <i class="bi bi-trash"></i> Xóa
                                                </a>
                                            </div>
                                        </div>
                                    </div> </div> </div> <?php endforeach; ?>
                    <?php endif; ?>
                </div> </div>
        </div>

    </div> <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>