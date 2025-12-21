<?php
// Tự động tìm file nhạc trong thư mục music/
$files = glob("music/*.{mp3,wav,ogg}", GLOB_BRACE);
$musicFile = !empty($files) ? $files[0] : "";
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8" />
    <link rel="shortcut icon" href="../favicon.ico" type="image/x-icon">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no" />
    <title>Magic Christmas 3D</title>
    <link rel="stylesheet" href="style.css" />
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">

    <script type="importmap">
      {
        "imports": {
          "three": "https://cdn.jsdelivr.net/npm/three@0.160.0/build/three.module.js",
          "@mediapipe/tasks-vision": "https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision@0.10.3/+esm"
        }
      }
    </script>
</head>
<body>
    <audio id="bg-music" loop></audio>

    <div id="loader">✨ ĐANG TẢI CÂY THÔNG... ✨</div>

    <div id="hand-cursor">🎅</div>

    <div id="canvas-container"></div>

    <div id="ui-layer">
        <div class="guide" id="instruction-text">
            🖐 <b>Mở tay:</b> Bung lụa & Xoay &nbsp;|&nbsp; 
            👌 <b>Chụm:</b> Con trỏ &nbsp;|&nbsp; 
            ✊ <b>Nắm tay:</b> Xem ảnh &nbsp;|&nbsp; 
            🫶 <b>Tim:</b>I Love You
        </div>
    </div>

    <div id="webcam-wrapper">
        <video id="webcam" autoplay playsinline style="display: none"></video>
        <canvas id="webcam-preview"></canvas>
    </div>

    <script>
        var musicPath = "<?php echo $musicFile; ?>";
        const audio = document.getElementById("bg-music");
        if (musicPath) {
            audio.src = musicPath + "?t=" + new Date().getTime();
        }
    </script>

    <script type="module" src="script.js"></script>
</body>
</html>