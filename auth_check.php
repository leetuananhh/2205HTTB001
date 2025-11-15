<?php
// 1. Luôn bắt đầu session ở đầu file
// Hàm này sẽ tiếp tục session đã được tạo bởi login.php
session_start();

// 2. Kiểm tra xem 'user_id' có tồn tại trong session không
// (Nghĩa là người dùng đã đăng nhập thành công hay chưa)
if (!isset($_SESSION['user_id'])) {
    
    // 3. Nếu không tồn tại (chưa đăng nhập)
    // Chuyển hướng ngay lập tức về trang login.php
    header("Location: login.php");
    
    // 4. Dừng kịch bản (script)
    // Đảm bảo không có mã HTML hay PHP nào khác chạy sau khi chuyển hướng
    exit; 
}

// Nếu script chạy đến đây, nghĩa là người dùng đã đăng nhập.
// Chúng ta có thể lấy user_id để dùng nếu cần
$logged_in_user_id = $_SESSION['user_id'];
?>