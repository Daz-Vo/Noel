<?php
// Tắt báo lỗi để tránh làm hỏng định dạng JSON
error_reporting(0);
header('Content-Type: application/json');

// Thư mục chứa ảnh
$dir = 'uploads/';
$images = [];

if (is_dir($dir)) {
    $files = scandir($dir);
    foreach ($files as $file) {
        // Lấy đuôi file
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        // Kiểm tra đúng là ảnh không
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            // Trả về đường dẫn: uploads/ten_anh.jpg
            $images[] = $dir . $file;
        }
    }
}

// Trả về dữ liệu cho Javascript
echo json_encode($images);
?>