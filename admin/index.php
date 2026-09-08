<?php
// --- CẤU HÌNH ---
$imageDir = '../uploads/'; // Chú ý: Vì file này nằm trong thư mục admin/, nên ra ngoài 1 cấp để vào uploads
$musicDir = '../music/';

// Kiểm tra và tạo thư mục nếu chưa tồn tại
if (!is_dir($imageDir)) mkdir($imageDir, 0777, true);
if (!is_dir($musicDir)) mkdir($musicDir, 0777, true);

// --- HIỂN THỊ THÔNG BÁO TỪ UPLOAD HANDLER ---
$message = "";
if (isset($_GET['msg']) && isset($_GET['type'])) {
    $msgClass = ($_GET['type'] === 'success') ? 'success' : 'error';
    $icon = ($_GET['type'] === 'success') ? '✓' : '✗';
    $message = "<div class='alert $msgClass'><i>$icon</i> " . htmlspecialchars($_GET['msg']) . "</div>";
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
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; 
            background: radial-gradient(circle at center, #1a0b1c 0%, #000000 100%); 
            color: #fff;
            height: 100vh;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
        }
        body::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background-image: radial-gradient(white 1px, transparent 1px), radial-gradient(white 1px, transparent 1px);
            background-position: 0 0, 25px 25px;
            background-size: 50px 50px;
            opacity: 0.05;
            z-index: -1;
        }

        .container { 
            width: 100%;
            max-width: 1400px; 
            height: 95vh;
            display: flex;
            flex-direction: column;
            background: rgba(20, 20, 30, 0.6); 
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 24px; 
            box-shadow: 0 30px 60px rgba(0,0,0,0.8), inset 0 0 20px rgba(255,255,255,0.02); 
            overflow: hidden; 
        }
        .header { 
            flex-shrink: 0;
            background: linear-gradient(to bottom, rgba(211, 47, 47, 0.15) 0%, transparent 100%);
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            padding: 30px 40px; 
            text-align: center; 
        }
        .header h1 { 
            font-size: 32px; 
            font-weight: 700; 
            margin-bottom: 8px; 
            color: #FFD700; 
            text-shadow: 0 0 30px rgba(255, 215, 0, 0.4);
            letter-spacing: 0.5px; 
        }
        .header p { opacity: 0.7; font-size: 15px; color: #fff; }
        
        .content { 
            flex: 1; 
            padding: 30px 40px; 
            display: flex; 
            gap: 40px;
            overflow: hidden;
            min-height: 0;
        }

        .left-panel {
            width: 400px;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
            padding-right: 15px;
            height: 100%;
        }
        
        .right-panel {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
            padding-right: 15px;
            height: 100%;
        }
        
        .alert { 
            padding: 16px 20px; margin-bottom: 20px; border-radius: 12px; 
            display: flex; align-items: center; gap: 12px; font-size: 14px; 
            animation: slideIn 0.3s ease; 
            backdrop-filter: blur(10px);
        }
        @keyframes slideIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        .alert i { font-style: normal; font-size: 20px; }
        .success { background: rgba(40, 167, 69, 0.15); color: #d4edda; border: 1px solid rgba(40, 167, 69, 0.3); }
        .error { background: rgba(220, 53, 69, 0.15); color: #f8d7da; border: 1px solid rgba(220, 53, 69, 0.3); }
        
        .upload-section { 
            background: rgba(0, 0, 0, 0.3); 
            padding: 25px; border-radius: 20px; margin-bottom: 30px; 
            border: 2px dashed rgba(255, 215, 0, 0.3); 
            transition: all 0.3s ease;
        }
        .upload-section:hover { border-color: rgba(255, 215, 0, 0.6); background: rgba(0, 0, 0, 0.4); }
        .upload-section h3 { color: #FFD700; font-size: 18px; margin-bottom: 20px; font-weight: 600; }
        
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 10px; color: #ccc; font-weight: 500; font-size: 14px; }
        select, input[type="file"] { 
            width: 100%; padding: 12px 16px; 
            border: 1px solid rgba(255, 255, 255, 0.1); 
            border-radius: 12px; font-size: 14px; 
            background: rgba(255, 255, 255, 0.05); color: #fff;
            transition: all 0.3s ease; 
        }
        select option { background: #1a1a24; color: #fff; }
        select:focus, input[type="file"]:focus { outline: none; border-color: #FFD700; background: rgba(255, 255, 255, 0.08); box-shadow: 0 0 15px rgba(255,215,0,0.1); }
        
        button { 
            background: linear-gradient(135deg, #d32f2f 0%, #b71c1c 100%); 
            color: white; border: none; padding: 12px 24px; 
            font-size: 14px; font-weight: 600; cursor: pointer; 
            border-radius: 12px; transition: all 0.3s ease; 
            box-shadow: 0 4px 15px rgba(211, 47, 47, 0.3); 
            text-transform: uppercase; letter-spacing: 1px; width: 100%;
        }
        button:hover { box-shadow: 0 8px 25px rgba(211, 47, 47, 0.5); background: linear-gradient(135deg, #f44336 0%, #d32f2f 100%); }
        button:active { box-shadow: 0 2px 10px rgba(211, 47, 47, 0.5); }
        
        .section-header { 
            display: flex; align-items: center; justify-content: space-between; 
            margin: 0 0 20px 0; padding-bottom: 12px; 
            border-bottom: 1px solid rgba(255, 255, 255, 0.08); 
        }
        .section-header h2 { color: #fff; font-size: 20px; font-weight: 600; letter-spacing: 0.5px; }
        .badge { 
            background: rgba(255, 215, 0, 0.15); color: #FFD700; 
            padding: 6px 16px; border-radius: 20px; font-size: 13px; font-weight: 600; 
            border: 1px solid rgba(255, 215, 0, 0.3);
        }
        
        .gallery { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px; }
        .item { 
            background: rgba(0, 0, 0, 0.3); 
            border: 1px solid rgba(255, 255, 255, 0.05); 
            border-radius: 16px; overflow: hidden; 
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); 
            cursor: pointer; 
        }
        .item:hover { 
            box-shadow: 0 20px 40px rgba(0,0,0,0.6), 0 0 20px rgba(255,215,0,0.15); 
            border-color: rgba(255, 215, 0, 0.4); 
            background: rgba(255, 255, 255, 0.03);
        }
        .item-image { width: 100%; height: 180px; object-fit: cover; background: #111; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .item-info { padding: 14px; display: flex; flex-direction: column; gap: 12px; }
        .item-name { 
            display: block; font-size: 13px; color: #ccc; 
            overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-weight: 500; 
        }
        
        .music-list { list-style: none; padding: 0; }
        .music-item { 
            display: flex; flex-direction: column; gap: 12px;
            background: rgba(0, 0, 0, 0.3); 
            padding: 16px; margin-bottom: 16px; border-radius: 16px; 
            border: 1px solid rgba(255, 255, 255, 0.05); 
            transition: all 0.3s ease; 
        }
        .music-item:hover { 
            border-color: rgba(255, 215, 0, 0.3); 
            background: rgba(255, 255, 255, 0.03); 
            box-shadow: 0 10px 20px rgba(0,0,0,0.3);
        }
        .music-info { width: 100%; }
        .music-info strong { display: block; color: #fff; font-size: 14px; margin-bottom: 10px; word-break: break-all; }
        audio { 
            width: 100%; height: 35px; outline: none; 
            border-radius: 20px; 
        }
        audio::-webkit-media-controls-panel { background-color: #f0f0f0; }
        
        .btn-delete { 
            background: rgba(220, 53, 69, 0.1); color: #ff6b6b; 
            text-decoration: none; padding: 8px 16px; font-size: 13px; font-weight: 600; 
            border-radius: 8px; transition: all 0.3s ease; 
            border: 1px solid rgba(220, 53, 69, 0.3);
            text-align: center; display: inline-block; align-self: flex-start;
        }
        .btn-delete:hover { 
            background: #dc3545; color: #fff; 
            box-shadow: 0 6px 20px rgba(220, 53, 69, 0.4); 
        }
        
        .empty-state { text-align: center; padding: 40px 20px; color: #666; }
        .empty-state svg { width: 60px; height: 60px; margin-bottom: 15px; opacity: 0.15; fill: #FFD700; }
        .empty-state p { font-size: 14px; color: #999; }
        
        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: rgba(0,0,0,0.2); border-radius: 10px; }
        ::-webkit-scrollbar-thumb { background: rgba(255, 215, 0, 0.3); border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: rgba(255, 215, 0, 0.5); }

        @media (max-width: 900px) { 
            .content { flex-direction: column; overflow-y: auto; }
            .left-panel, .right-panel { width: 100%; overflow: visible; padding-right: 0; }
            body { height: auto; overflow: visible; }
            .container { height: auto; max-height: none; }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>📁 Media Manager</h1>
        <p>Quản lý hình ảnh và nhạc của bạn</p>
    </div>

    <div class="content">
        <div class="left-panel">
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

        <div class="right-panel">
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
        </div>
    </div>
</div>

</body>
</html>
