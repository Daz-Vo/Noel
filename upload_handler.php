<?php
// --- CẤU HÌNH HỆ THỐNG ---
$imageDir = 'uploads/';
$musicDir = 'music/';

// Kiểm tra và tự động tạo thư mục nếu chưa có
if (!file_exists($imageDir)) mkdir($imageDir, 0777, true);
if (!file_exists($musicDir)) mkdir($musicDir, 0777, true);

// Định nghĩa các đuôi file cho phép
$allowedImageTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
$allowedMusicTypes = ['mp3', 'wav', 'ogg'];

// --- BẮT ĐẦU XỬ LÝ ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload'])) {

    // 1. Xác định loại upload (Ảnh hay Nhạc) từ form gửi lên
    $type = isset($_POST['type']) ? $_POST['type'] : 'image';
    
    if ($type === 'image') {
        $targetDir = $imageDir;
        $allowedTypes = $allowedImageTypes;
        $label = "ảnh";
    } else {
        $targetDir = $musicDir;
        $allowedTypes = $allowedMusicTypes;
        $label = "bài nhạc";

        // --- [MỚI] LOGIC XÓA NHẠC CŨ ---
        // Nếu là upload nhạc, xóa tất cả file cũ trong thư mục music trước
        $files = glob($musicDir . '*'); // Lấy tất cả file trong folder music
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file); // Xóa file
            }
        }
        // -------------------------------
    }

    // 2. Kiểm tra xem có file nào được chọn không
    if (!isset($_FILES['files']) || empty($_FILES['files']['name'][0])) {
        redirect("admin/index.php?msg=" . urlencode("Vui lòng chọn file!") . "&type=error");
    }

    // 3. Chuẩn bị biến đếm
    $totalFiles = count($_FILES['files']['name']);
    $successCount = 0;
    $errors = [];

    // 4. VÒNG LẶP XỬ LÝ TỪNG FILE
    for ($i = 0; $i < $totalFiles; $i++) {
        $fileName = $_FILES['files']['name'][$i];
        $fileTmp  = $_FILES['files']['tmp_name'][$i];
        $fileSize = $_FILES['files']['size'][$i];
        $fileError = $_FILES['files']['error'][$i];

        // Bỏ qua nếu file lỗi
        if ($fileError !== UPLOAD_ERR_OK) {
            $errors[] = "$fileName (Lỗi hệ thống)";
            continue;
        }

        // Kiểm tra đuôi file
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if (!in_array($fileExt, $allowedTypes)) {
            $errors[] = "$fileName (Sai định dạng)";
            continue;
        }

        // Đổi tên file để tránh trùng lặp
        $nameWithoutExt = pathinfo($fileName, PATHINFO_FILENAME);
        $cleanName = preg_replace('/[^a-zA-Z0-9_-]/', '', $nameWithoutExt);
        
        $newFileName = $cleanName . '_' . time() . rand(10, 99) . '.' . $fileExt;
        $targetFilePath = $targetDir . $newFileName;

        // Di chuyển file vào thư mục
        if (move_uploaded_file($fileTmp, $targetFilePath)) {
            $successCount++;
        } else {
            $errors[] = "$fileName (Không thể lưu)";
        }
    }

    // 5. TỔNG KẾT VÀ CHUYỂN HƯỚNG
    if ($successCount > 0) {
        $msg = "Đã tải lên thành công $successCount $label.";
        if (count($errors) > 0) {
            $msg .= " (Có " . count($errors) . " file lỗi).";
        }
        redirect("admin/index.php?msg=" . urlencode($msg) . "&type=success");
    } else {
        $msg = "Thất bại! " . implode(", ", $errors);
        redirect("admin/index.php?msg=" . urlencode($msg) . "&type=error");
    }

} else {
    // Nếu truy cập trực tiếp file này mà không submit form
    redirect("admin/index.php");
}

// Hàm chuyển hướng an toàn
function redirect($url) {
    header("Location: $url");
    exit();
}
?>