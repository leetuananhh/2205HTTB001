<?php
// Thông tin cấu hình cơ sở dữ liệu (Database Configuration)
$host = 'localhost'; // Hoặc '127.0.0.1'
$db_name = 'todo_app'; // Tên CSDL bạn vừa tạo
$username = 'root'; // Tên người dùng MySQL (mặc định là 'root')
$password = ''; // Mật khẩu MySQL (mặc định là rỗng nếu dùng XAMPP)

// Cấu hình DSN (Data Source Name)
$dsn = "mysql:host=$host;dbname=$db_name;charset=utf8mb4";

// Cấu hình các tùy chọn cho PDO
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Bật chế độ báo lỗi (ném ngoại lệ)
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Đặt chế độ fetch mặc định là mảng kết hợp (associative array)
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Tắt chế độ mô phỏng prepared statements, sử dụng native
];

// Khởi tạo kết nối PDO
try {
    // Tạo một đối tượng PDO mới
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (\PDOException $e) {
    // Nếu có lỗi kết nối, bắt ngoại lệ và hiển thị thông báo lỗi
    // Trong môi trường production (thực tế), bạn nên log lỗi thay vì in ra
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

// Giờ đây, biến $pdo đã sẵn sàng để được sửu dụng ở bất kỳ file nào 'include' file này.
?>