<?php
// --- CẤU HÌNH ---
$imageDir = '../uploads/'; // Chú ý: Vì file này nằm trong thư mục admin/, nên ra ngoài 1 cấp để vào uploads
$musicDir = '../music/';

// Kiểm tra và tạo thư mục nếu chưa tồn tại
if (!is_dir($imageDir)) mkdir($imageDir, 0777, true);
if (!is_dir($musicDir)) mkdir($musicDir, 0777, true);

// --- XỬ LÝ UPLOAD (ĐÃ NÂNG CẤP ĐỂ HỖ TRỢ NHIỀU FILE) ---
$message = "";

if (isset($_POST['upload'])) {
    $type = $_POST['type'];
    $targetDir = ($type === 'image') ? $imageDir : $musicDir;
    
    // Các đuôi file cho phép
    $allowImages = ['jpg', 'png', 'jpeg', 'gif', 'webp'];
    $allowMusic = ['mp3', 'wav', 'ogg'];
    $allowedExts = ($type === 'image') ? $allowImages : $allowMusic;

    // Đếm số file được gửi lên
    $totalFiles = count($_FILES['files']['name']);
    $successCount = 0;
    $errorCount = 0;

    // Vòng lặp xử lý từng file
    for ($i = 0; $i < $totalFiles; $i++) {
        $fileName = $_FILES['files']['name'][$i];
        $fileTmp = $_FILES['files']['tmp_name'][$i];
        $fileError = $_FILES['files']['error'][$i];
        
        if ($fileError === UPLOAD_ERR_OK) {
            $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            // Kiểm tra định dạng
            if (in_array($fileType, $allowedExts)) {
                // Tạo tên file mới để tránh trùng: tên_cũ + timestamp + random
                $newFileName = pathinfo($fileName, PATHINFO_FILENAME) . '_' . time() . rand(10,99) . '.' . $fileType;
                $targetFilePath = $targetDir . $newFileName;

                if (move_uploaded_file($fileTmp, $targetFilePath)) {
                    $successCount++;
                } else {
                    $errorCount++;
                }
            } else {
                $errorCount++; // Sai định dạng
            }
        }
    }

    // Hiển thị thông báo tổng kết
    if ($successCount > 0) {
        $message .= "<div class='alert success'><i>✓</i> Đã tải lên thành công <strong>$successCount</strong> file!</div>";
    }
    if ($errorCount > 0) {
        $message .= "<div class='alert error'><i>✗</i> Có <strong>$errorCount</strong> file bị lỗi hoặc sai định dạng.</div>";
    }
}

// --- XỬ LÝ XÓA ---
if (isset($_GET['delete'])) {
    $fileToDelete = $_GET['delete'];
    $type = $_GET['type'];
    $dir = ($type === 'image') ? $imageDir : $musicDir;
    
    // Bảo mật: Chỉ lấy tên file, bỏ đường dẫn
    $filePath = $dir . basename($fileToDelete);

    if (file_exists($filePath)) {
        unlink($filePath);
        $message = "<div class='alert success'><i>✓</i> Đã xóa file: <strong>$fileToDelete</strong></div>";
    } else {
        $message = "<div class='alert error'><i>✗</i> File không tồn tại.</div>";
    }
}

// --- LẤY DANH SÁCH FILE ---
$images = glob($imageDir . "*.{jpg,jpeg,png,gif,webp,JPG,JPEG,PNG}", GLOB_BRACE);
$musics = glob($musicDir . "*.{mp3,wav,ogg}", GLOB_BRACE);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <link rel="shortcut icon" href="../favicon.ico" type="image/x-icon">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Media Manager - Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* GIỮ NGUYÊN TOÀN BỘ CSS CŨ CỦA BẠN */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: linear-gradient(135deg, #ea6666ff 0%, #a2514bff 100%); min-height: 100vh; padding: 40px 20px; }
        .container { max-width: 1200px; margin: 0 auto; background: white; border-radius: 20px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); overflow: hidden; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 40px; color: white; text-align: center; }
        .header h1 { font-size: 32px; font-weight: 700; margin-bottom: 8px; letter-spacing: -0.5px; }
        .header p { opacity: 0.9; font-size: 16px; }
        .content { padding: 40px; }
        .alert { padding: 16px 20px; margin-bottom: 30px; border-radius: 12px; display: flex; align-items: center; gap: 12px; font-size: 15px; animation: slideIn 0.3s ease; }
        @keyframes slideIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        .alert i { font-style: normal; font-size: 20px; font-weight: bold; }
        .success { background: #d4edda; color: #155724; border-left: 4px solid #28a745; }
        .error { background: #f8d7da; color: #721c24; border-left: 4px solid #dc3545; }
        .upload-section { background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); padding: 30px; border-radius: 16px; margin-bottom: 40px; border: 2px dashed #667eea; }
        .upload-section h3 { color: #333; font-size: 20px; margin-bottom: 20px; font-weight: 600; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; color: #555; font-weight: 500; font-size: 14px; }
        select { width: 100%; padding: 12px 16px; border: 2px solid #e0e0e0; border-radius: 10px; font-size: 15px; background: white; cursor: pointer; transition: all 0.3s ease; }
        select:focus { outline: none; border-color: #667eea; }
        input[type="file"] { width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 10px; background: white; cursor: pointer; font-size: 14px; }
        button { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; padding: 14px 32px; font-size: 15px; font-weight: 600; cursor: pointer; border-radius: 10px; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4); }
        button:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6); }
        button:active { transform: translateY(0); }
        .section-header { display: flex; align-items: center; justify-content: space-between; margin: 40px 0 20px 0; padding-bottom: 15px; border-bottom: 3px solid #f0f0f0; }
        .section-header h2 { color: #333; font-size: 24px; font-weight: 600; }
        .badge { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 6px 16px; border-radius: 20px; font-size: 14px; font-weight: 600; }
        .gallery { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px; margin-top: 20px; }
        .item { background: white; border: 2px solid #f0f0f0; border-radius: 12px; overflow: hidden; transition: all 0.3s ease; cursor: pointer; }
        .item:hover { transform: translateY(-5px); box-shadow: 0 10px 30px rgba(0,0,0,0.15); border-color: #667eea; }
        .item-image { width: 100%; height: 200px; object-fit: cover; background: #f5f5f5; }
        .item-info { padding: 15px; }
        .item-name { display: block; font-size: 13px; color: #666; margin-bottom: 10px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-weight: 500; }
        .music-list { list-style: none; padding: 0; }
        .music-item { display: flex; align-items: center; justify-content: space-between; background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); padding: 20px; margin-bottom: 12px; border-radius: 12px; border: 2px solid transparent; transition: all 0.3s ease; }
        .music-item:hover { border-color: #667eea; transform: translateX(5px); }
        .music-info strong { display: block; color: #333; font-size: 15px; margin-bottom: 10px; }
        audio { width: 300px; height: 35px; outline: none; }
        .btn-delete { background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%); color: white; text-decoration: none; padding: 8px 20px; font-size: 13px; font-weight: 600; border-radius: 8px; transition: all 0.3s ease; display: inline-block; box-shadow: 0 4px 15px rgba(238, 90, 111, 0.3); }
        .btn-delete:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(238, 90, 111, 0.5); }
        .empty-state { text-align: center; padding: 60px 20px; color: #999; }
        .empty-state svg { width: 80px; height: 80px; margin-bottom: 20px; opacity: 0.3; }
        .empty-state p { font-size: 16px; }
        @media (max-width: 768px) { .content { padding: 20px; } .header h1 { font-size: 24px; } .gallery { grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 15px; } audio { width: 200px; } .music-item { flex-direction: column; gap: 15px; align-items: flex-start; } }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>📁 Media Manager</h1>
        <p>Quản lý hình ảnh và nhạc của bạn</p>
    </div>

    <div class="content">
        <?php echo $message; ?>

        <div class="upload-section">
            <h3>📤 Tải lên file mới</h3>
            <form action="../upload_handler.php" method="post" enctype="multipart/form-data">
    
    <div class="form-group">
        <label>Loại file:</label>
        <select name="type">
            <option value="image">🖼️ Hình ảnh (JPG, PNG, GIF)</option>
            <option value="music">🎵 Nhạc (MP3, WAV, OGG)</option>
        </select>
    </div>
    
    <div class="form-group">
        <label>Chọn file (Giữ Ctrl để chọn nhiều):</label>
        <input type="file" name="files[]" multiple required>
    </div>
    
    <button type="submit" name="upload">🚀 Tải lên ngay</button>
</form>
        </div>

        <div class="section-header">
            <h2>🖼️ Thư viện Ảnh</h2>
            <span class="badge"><?php echo count($images); ?> file</span>
        </div>
        
        <?php if(empty($images)): ?>
            <div class="empty-state">
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/>
                </svg>
                <p>Chưa có hình ảnh nào. Hãy tải lên ảnh đầu tiên!</p>
            </div>
        <?php else: ?>
            <div class="gallery">
                <?php foreach($images as $img): ?>
                    <div class="item">
                        <img src="<?php echo $img; ?>" alt="Image" class="item-image">
                        <div class="item-info">
                            <span class="item-name" title="<?php echo basename($img); ?>">
                                <?php echo basename($img); ?>
                            </span>
                            <a href="?delete=<?php echo urlencode(basename($img)); ?>&type=image" 
                               class="btn-delete" 
                               onclick="return confirm('Bạn có chắc muốn xóa ảnh này?');">
                                🗑️ Xóa
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="section-header">
            <h2>🎵 Thư viện Nhạc</h2>
            <span class="badge"><?php echo count($musics); ?> file</span>
        </div>
        
        <?php if(empty($musics)): ?>
            <div class="empty-state">
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4V3h-6z"/>
                </svg>
                <p>Chưa có bài nhạc nào. Hãy tải lên nhạc đầu tiên!</p>
            </div>
        <?php else: ?>
            <ul class="music-list">
                <?php foreach($musics as $song): ?>
                    <li class="music-item">
                        <div class="music-info">
                            <strong>🎧 <?php echo basename($song); ?></strong>
                            <audio controls>
                                <source src="<?php echo $song; ?>" type="audio/mpeg">
                                Trình duyệt của bạn không hỗ trợ audio.
                            </audio>
                        </div>
                        <a href="?delete=<?php echo urlencode(basename($song)); ?>&type=music" 
                           class="btn-delete" 
                           onclick="return confirm('Bạn có chắc muốn xóa bài nhạc này?');">
                           🗑️ Xóa
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
