<?php
// 1. Luôn bắt đầu session trước khi làm việc với nó
session_start();

// 2. Xóa tất cả các biến session
// (Cách an toàn để đảm bảo mọi thứ đều sạch)
$_SESSION = array();

// 3. Hủy bỏ session
// Hàm này phá hủy tất cả dữ liệu đã đăng ký cho một session
session_destroy();

// 4. Chuyển hướng người dùng về trang đăng nhập (login.php)
header("Location: login.php");
exit; // Dừng script ngay lập tức
?>