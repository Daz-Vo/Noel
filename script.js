import * as THREE from "three";
import { FilesetResolver, HandLandmarker } from "@mediapipe/tasks-vision";

// --- CẤU HÌNH ---
const CONFIG = {
  goldCount: 2000,
  redCount: 500,
  giftCount: 200,
  explodeRadius: 65,
  photoOrbitRadius: 35,
  treeHeight: 60,
  treeBaseRadius: 30,
  cameraZ: 100,
};

// --- TRẠNG THÁI ---
const STATE = {
  mode: "TREE",
  isPinching: false,
  hand: {
    detected: false,
    screenX: 0,
    screenY: 0,
    x: 0,
    y: 0,
  },
  rotation: { x: 0, y: 0 },
  targetRotation: { x: 0, y: 0 },
  selectedIndex: -1,
  loadedPhotos: [],
};

// --- BIẾN TOÀN CỤC ---
let scene, camera, renderer;
let mainGroup;
let groupGold, groupRed, groupGift;
let photoMeshes = [];
let titleMesh, starMesh, loveMesh;
let clock = new THREE.Clock();
let cursorElement;
let handLandmarker, video, webcamCanvas, webcamCtx;

// --- KHỞI TẠO ---
async function init() {
  cursorElement = document.getElementById("hand-cursor");

  initThree();
  createDecorations();
  await loadServerImages();
  createParticleGroups();
  await initMediaPipe();

  const loader = document.getElementById("loader");
  if (loader) {
    loader.style.opacity = 0;
    setTimeout(() => loader.remove(), 800);
  }

  animate();
}

// --- THREE.JS SETUP ---
function initThree() {
  const container = document.getElementById("canvas-container");
  scene = new THREE.Scene();
  scene.fog = new THREE.FogExp2(0x000000, 0.002);

  camera = new THREE.PerspectiveCamera(
    60,
    window.innerWidth / window.innerHeight,
    0.1,
    1000
  );
  camera.position.z = CONFIG.cameraZ;

  renderer = new THREE.WebGLRenderer({ antialias: true, alpha: false });
  renderer.setSize(window.innerWidth, window.innerHeight);
  renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
  // --- THÊM DÒNG NÀY ---
  // Bắt buộc renderer xuất ra màu chuẩn sRGB để màu sắc tươi tắn đúng thực tế
  renderer.outputColorSpace = THREE.SRGBColorSpace;
  // --------------------
  container.appendChild(renderer.domElement);

  mainGroup = new THREE.Group();
  scene.add(mainGroup);

  window.addEventListener("resize", () => {
    camera.aspect = window.innerWidth / window.innerHeight;
    camera.updateProjectionMatrix();
    renderer.setSize(window.innerWidth, window.innerHeight);
  });
}

// --- TEXTURE ---
function createCustomTexture(type) {
  const canvas = document.createElement("canvas");
  canvas.width = 64;
  canvas.height = 64;
  const ctx = canvas.getContext("2d");
  const cx = 32,
    cy = 32;

  if (type === "gold") {
    const grd = ctx.createRadialGradient(cx, cy, 0, cx, cy, 20);
    grd.addColorStop(0, "#FFFFFF");
    grd.addColorStop(0.2, "#FFFFE0");
    grd.addColorStop(1, "rgba(0,0,0,0)");
    ctx.fillStyle = grd;
    ctx.fillRect(0, 0, 64, 64);
  } else if (type === "red") {
    const grd = ctx.createRadialGradient(cx, cy, 0, cx, cy, 25);
    grd.addColorStop(0, "#FFAAAA");
    grd.addColorStop(0.4, "#FF0000");
    grd.addColorStop(1, "rgba(0,0,0,0)");
    ctx.fillStyle = grd;
    ctx.fillRect(0, 0, 64, 64);
  } else if (type === "gift") {
    ctx.fillStyle = "#D32F2F";
    ctx.fillRect(10, 10, 44, 44);
    ctx.fillStyle = "#FFD700";
    ctx.fillRect(28, 10, 8, 44);
    ctx.fillRect(10, 28, 44, 8);
  }
  return new THREE.CanvasTexture(canvas);
}

// --- PARTICLES ---
function createParticleGroups() {
  const texGold = createCustomTexture("gold");
  const texRed = createCustomTexture("red");
  const texGift = createCustomTexture("gift");

  groupGold = createParticleSystem("gold", CONFIG.goldCount, 2.0, texGold);
  groupRed = createParticleSystem("red", CONFIG.redCount, 3.5, texRed);
  groupGift = createParticleSystem("gift", CONFIG.giftCount, 3.0, texGift);
}

function createParticleSystem(type, count, size, texture) {
  const pPositions = [];
  const sizes = [];
  const phases = [];
  const pTreeTargets = [];
  const pExplodeTargets = [];
  const pHeartTargets = [];

  for (let i = 0; i < count; i++) {
    // TREE
    const h = Math.random() * CONFIG.treeHeight;
    const y = h - CONFIG.treeHeight / 2;
    let radiusRatio =
      type === "gold" ? Math.sqrt(Math.random()) : 0.9 + Math.random() * 0.1;
    const maxR = (1 - h / CONFIG.treeHeight) * CONFIG.treeBaseRadius;
    const r = maxR * radiusRatio;
    const theta = Math.random() * Math.PI * 2;
    pTreeTargets.push(r * Math.cos(theta), y, r * Math.sin(theta));

    // EXPLODE
    const u = Math.random();
    const v = Math.random();
    const phi = Math.acos(2 * v - 1);
    const lam = 2 * Math.PI * u;
    const rad = CONFIG.explodeRadius * Math.cbrt(Math.random());
    pExplodeTargets.push(
      rad * Math.sin(phi) * Math.cos(lam),
      rad * Math.sin(phi) * Math.sin(lam),
      rad * Math.cos(phi)
    );

    // HEART
    const tHeart = Math.random() * Math.PI * 2;
    let hx = 16 * Math.pow(Math.sin(tHeart), 3);
    let hy =
      13 * Math.cos(tHeart) -
      5 * Math.cos(2 * tHeart) -
      2 * Math.cos(3 * tHeart) -
      Math.cos(4 * tHeart);
    const rFill = Math.pow(Math.random(), 0.3);
    hx *= rFill;
    hy *= rFill;
    let hz = (Math.random() - 0.5) * 8 * rFill;
    const scaleH = 2.0;
    pHeartTargets.push(hx * scaleH, hy * scaleH + 5, hz);

    // Init
    pPositions.push(
      pTreeTargets[i * 3],
      pTreeTargets[i * 3 + 1],
      pTreeTargets[i * 3 + 2]
    );
    sizes.push(size);
    phases.push(Math.random() * Math.PI * 2);
  }

  const geo = new THREE.BufferGeometry();
  geo.setAttribute("position", new THREE.Float32BufferAttribute(pPositions, 3));
  geo.setAttribute("size", new THREE.Float32BufferAttribute(sizes, 1));

  const colors = new Float32Array(count * 3);
  const baseColor = new THREE.Color(
    type === "gold" ? 0xffd700 : type === "red" ? 0xff0000 : 0xffffff
  );
  for (let i = 0; i < count; i++) {
    colors[i * 3] = baseColor.r;
    colors[i * 3 + 1] = baseColor.g;
    colors[i * 3 + 2] = baseColor.b;
  }
  geo.setAttribute("color", new THREE.BufferAttribute(colors, 3));

  geo.userData = {
    tree: pTreeTargets,
    explode: pExplodeTargets,
    heart: pHeartTargets,
    phases: phases,
    baseColor: baseColor,
    baseSize: size,
  };

  const mat = new THREE.PointsMaterial({
    size: size,
    map: texture,
    transparent: true,
    opacity: 1.0,
    vertexColors: true,
    blending: THREE.AdditiveBlending,
    depthWrite: false,
    sizeAttenuation: true,
  });

  const points = new THREE.Points(geo, mat);
  mainGroup.add(points);
  return points;
}

// --- DECORATIONS ---
function createDecorations() {
  // 1. Merry Christmas (Scene)
  const canvas = document.createElement("canvas");
  canvas.width = 1024;
  canvas.height = 256;
  const ctx = canvas.getContext("2d");
  ctx.font = 'bold italic 90px "Times New Roman"';
  ctx.fillStyle = "#FFD700";
  ctx.textAlign = "center";
  ctx.shadowColor = "#FF0000";
  ctx.shadowBlur = 40;
  ctx.fillText("MERRY CHRISTMAS", 512, 130);
  const tex = new THREE.CanvasTexture(canvas);
  const mat = new THREE.MeshBasicMaterial({
    map: tex,
    transparent: true,
    blending: THREE.AdditiveBlending,
    side: THREE.DoubleSide,
  });

  titleMesh = new THREE.Mesh(new THREE.PlaneGeometry(60, 15), mat);
  titleMesh.position.set(0, 45, 0);
  scene.add(titleMesh);

  // 2. NGÔI SAO (Scene - Đứng yên)
  const starCanvas = document.createElement("canvas");
  starCanvas.width = 128;
  starCanvas.height = 128;
  const sCtx = starCanvas.getContext("2d");
  sCtx.fillStyle = "#FFFF00";
  sCtx.shadowColor = "#FFF";
  sCtx.shadowBlur = 20;
  sCtx.beginPath();
  const cx = 64,
    cy = 64,
    outer = 50,
    inner = 20;
  for (let i = 0; i < 5; i++) {
    sCtx.lineTo(
      cx + Math.cos(((18 + i * 72) / 180) * Math.PI) * outer,
      cy - Math.sin(((18 + i * 72) / 180) * Math.PI) * outer
    );
    sCtx.lineTo(
      cx + Math.cos(((54 + i * 72) / 180) * Math.PI) * inner,
      cy - Math.sin(((54 + i * 72) / 180) * Math.PI) * inner
    );
  }
  sCtx.closePath();
  sCtx.fill();
  const starTex = new THREE.CanvasTexture(starCanvas);
  starMesh = new THREE.Mesh(
    new THREE.PlaneGeometry(12, 12),
    new THREE.MeshBasicMaterial({
      map: starTex,
      transparent: true,
      blending: THREE.AdditiveBlending,
    })
  );
  starMesh.position.set(0, 35, 0);
  scene.add(starMesh);

  // 3. I LOVE YOU (Scene)
  const loveCanvas = document.createElement("canvas");
  loveCanvas.width = 1024;
  loveCanvas.height = 256;
  const lCtx = loveCanvas.getContext("2d");
  lCtx.font = 'bold 120px "Segoe UI", sans-serif';
  lCtx.fillStyle = "#FF69B4";
  lCtx.textAlign = "center";
  lCtx.shadowColor = "#FF1493";
  lCtx.shadowBlur = 40;
  lCtx.fillText("I LOVE YOU", 512, 130);
  const loveTex = new THREE.CanvasTexture(loveCanvas);
  loveMesh = new THREE.Mesh(
    new THREE.PlaneGeometry(70, 18),
    new THREE.MeshBasicMaterial({
      map: loveTex,
      transparent: true,
      blending: THREE.AdditiveBlending,
    })
  );
  loveMesh.visible = false;
  scene.add(loveMesh);
}

// --- SERVER IMAGES ---
async function loadServerImages() {
  try {
    const response = await fetch("get_images.php");
    const images = await response.json();
    const loader = new THREE.TextureLoader();

    const borderMat = new THREE.MeshBasicMaterial({ color: 0xffd700 });

    for (let url of images) {
      const tex = await loader.loadAsync(url);
      // Đánh dấu texture này là sRGB để nó không bị render nhợt nhạt
      tex.colorSpace = THREE.SRGBColorSpace;
      
      // Tính toán tỷ lệ ảnh
      const aspect = tex.image.width / tex.image.height;
      let w = 10;
      let h = 10;
      if (aspect > 1) {
        h = 10 / aspect;
      } else {
        w = 10 * aspect;
      }
      
      const frameGeo = new THREE.PlaneGeometry(w, h);
      const borderGeo = new THREE.PlaneGeometry(w + 1, h + 1);

      const mat = new THREE.MeshBasicMaterial({
        map: tex,
        side: THREE.DoubleSide,
        color: 0xffffff,
      });
      const mesh = new THREE.Mesh(frameGeo, mat);
      const border = new THREE.Mesh(borderGeo, borderMat);
      border.position.z = -0.1;
      mesh.add(border);

      const angle = (photoMeshes.length / images.length) * Math.PI * 2;
      const r = CONFIG.photoOrbitRadius;
      mesh.userData = {
        originalPos: new THREE.Vector3(
          Math.cos(angle) * r,
          Math.random() * 20 - 10,
          Math.sin(angle) * r
        ),
      };
      mesh.position.copy(mesh.userData.originalPos);
      mesh.lookAt(0, 0, 0);

      scene.add(mesh);
      photoMeshes.push(mesh);
    }
  } catch (e) {
    console.warn("Lỗi tải ảnh:", e);
  }
}

// --- MEDIA PIPE ---
async function initMediaPipe() {
  video = document.getElementById("webcam");
  webcamCanvas = document.getElementById("webcam-preview");
  webcamCtx = webcamCanvas.getContext("2d");
  webcamCanvas.width = 160;
  webcamCanvas.height = 120;

  const vision = await FilesetResolver.forVisionTasks(
    "https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision@0.10.3/wasm"
  );
  handLandmarker = await HandLandmarker.createFromOptions(vision, {
    baseOptions: {
      modelAssetPath: `https://storage.googleapis.com/mediapipe-models/hand_landmarker/hand_landmarker/float16/1/hand_landmarker.task`,
      delegate: "GPU",
    },
    runningMode: "VIDEO",
    numHands: 2,
  });

  if (navigator.mediaDevices?.getUserMedia) {
    const stream = await navigator.mediaDevices.getUserMedia({ video: true });
    video.srcObject = stream;
    video.addEventListener("loadeddata", predictWebcam);
  }
}

async function predictWebcam() {
  if (handLandmarker && video.currentTime > 0) {
    const result = handLandmarker.detectForVideo(video, performance.now());
    processGestures(result);
    if (webcamCtx) {
      webcamCtx.clearRect(0, 0, 160, 120);
      webcamCtx.drawImage(video, 0, 0, 160, 120);
    }
  }
  requestAnimationFrame(predictWebcam);
}

// --- LOGIC CỬ CHỈ ---
function processGestures(result) {
  if (!result.landmarks || result.landmarks.length === 0) {
    STATE.hand.detected = false;
    STATE.isPinching = false;
    STATE.selectedIndex = -1;
    if (cursorElement) cursorElement.style.display = "none";
    STATE.mode = "TREE";
    return;
  }

  const audio = document.getElementById("bg-music");
  if (audio && audio.paused) audio.play().catch(() => {});

  STATE.hand.detected = true;

  // 1. TIM
  if (result.landmarks.length === 2) {
    const h1 = result.landmarks[0];
    const h2 = result.landmarks[1];
    const distIndex = Math.hypot(h1[8].x - h2[8].x, h1[8].y - h2[8].y);
    const distThumb = Math.hypot(h1[4].x - h2[4].x, h1[4].y - h2[4].y);
    if (distIndex < 0.15 && distThumb < 0.15) {
      STATE.mode = "HEART";
      if (cursorElement) cursorElement.style.display = "none";
      return;
    }
  }

  // 2. MỘT TAY
  const lm = result.landmarks[0];
  const indexTip = lm[8];
  const thumbTip = lm[4];
  const wrist = lm[0];

  const midX = (indexTip.x + thumbTip.x) / 2;
  const midY = (indexTip.y + thumbTip.y) / 2;
  STATE.hand.screenX = (1 - midX) * window.innerWidth;
  STATE.hand.screenY = midY * window.innerHeight;

  const pinchDist = Math.hypot(
    indexTip.x - thumbTip.x,
    indexTip.y - thumbTip.y
  );
  const tips = [8, 12, 16, 20];
  let avgDistToWrist = 0;
  tips.forEach(
    (i) => (avgDistToWrist += Math.hypot(lm[i].x - wrist.x, lm[i].y - wrist.y))
  );
  avgDistToWrist /= 4;

  let isPinching = false;
  if (STATE.isPinching) {
    isPinching = pinchDist < 0.08; // Hysteresis: allow slightly looser pinch if already pinching
  } else {
    isPinching = pinchDist < 0.04; // Require tight pinch to start
  }

  const isFist = avgDistToWrist < 0.25 && !isPinching;
  const isOpen = avgDistToWrist > 0.3 && !isPinching;

  STATE.isPinching = isPinching;

  if (isFist) {
    if (STATE.selectedIndex !== -1) {
      STATE.mode = "PHOTO";
    }
    if (cursorElement) cursorElement.style.display = "none";
  } else if (isPinching) {
    if (cursorElement) {
      cursorElement.style.display = "block";
      cursorElement.style.left = `${STATE.hand.screenX}px`;
      cursorElement.style.top = `${STATE.hand.screenY}px`;
    }
    findClosestPhoto();
    if (STATE.mode !== "PHOTO") STATE.mode = "EXPLODE";
  } else if (isOpen) {
    STATE.mode = "EXPLODE";
    if (cursorElement) cursorElement.style.display = "none";
    STATE.hand.x = (1 - lm[9].x - 0.5) * 2;
    STATE.hand.y = (lm[9].y - 0.5) * 2;
  } else {
    if (cursorElement) cursorElement.style.display = "none";
  }
}

function findClosestPhoto() {
  let closestDist = Infinity;
  let closestIndex = -1;
  photoMeshes.forEach((mesh, i) => {
    const pos = mesh.position.clone();
    pos.project(camera);
    const x = (pos.x * 0.5 + 0.5) * window.innerWidth;
    const y = (-(pos.y * 0.5) + 0.5) * window.innerHeight;
    const dist = Math.hypot(x - STATE.hand.screenX, y - STATE.hand.screenY);
    if (dist < closestDist) {
      closestDist = dist;
      closestIndex = i;
    }
  });
  if (closestDist < 150) {
    STATE.selectedIndex = closestIndex;
  } else {
    STATE.selectedIndex = -1;
  }
}

// --- ANIMATION UPDATE ---
function updateParticles(group, type, dt, time) {
  const positions = group.geometry.attributes.position.array;
  const sizes = group.geometry.attributes.size.array;
  const colors = group.geometry.attributes.color.array;
  const userData = group.geometry.userData;

  let targetArr;
  if (STATE.mode === "TREE") targetArr = userData.tree;
  else if (STATE.mode === "HEART") targetArr = userData.heart;
  else targetArr = userData.explode;

  const speed = 2.0 * dt;

  for (let i = 0; i < positions.length; i++) {
    positions[i] += (targetArr[i] - positions[i]) * speed;
  }
  group.geometry.attributes.position.needsUpdate = true;

  const count = positions.length / 3;
  for (let i = 0; i < count; i++) {
    let brightness = 0.8 + 0.5 * Math.sin(time * 5 + userData.phases[i]);
    if (STATE.mode === "HEART" && i % 3 !== 0) sizes[i] = 0;
    else sizes[i] = userData.baseSize;

    colors[i * 3] = userData.baseColor.r * brightness;
    colors[i * 3 + 1] = userData.baseColor.g * brightness;
    colors[i * 3 + 2] = userData.baseColor.b * brightness;
  }
  group.geometry.attributes.color.needsUpdate = true;
  group.geometry.attributes.size.needsUpdate = true;
}

function animate() {
  requestAnimationFrame(animate);
  const dt = clock.getDelta();
  const time = clock.getElapsedTime();

  if (groupGold) updateParticles(groupGold, "gold", dt, time);
  if (groupRed) updateParticles(groupRed, "red", dt, time);
  if (groupGift) updateParticles(groupGift, "gift", dt, time);

  // XỬ LÝ HIỂN THỊ
  if (STATE.mode === "TREE") {
    if (titleMesh) titleMesh.visible = true;
    if (starMesh) starMesh.visible = true;
    if (loveMesh) loveMesh.visible = false;

    STATE.targetRotation.y += 0.2 * dt;
    STATE.targetRotation.x = 0;

    photoMeshes.forEach((m) => (m.visible = false));
  } else if (STATE.mode === "HEART") {
    if (titleMesh) titleMesh.visible = false;
    if (starMesh) starMesh.visible = false;
    if (loveMesh) {
      loveMesh.visible = true;
      loveMesh.scale.set(1, 1, 1);
    }
    photoMeshes.forEach((m) => (m.visible = false));

    // --- SỬA LỖI ĐỨNG HÌNH Ở ĐÂY ---
    // Thay vì dùng .set() gây lỗi, ta gán trực tiếp giá trị
    if (mainGroup) {
      mainGroup.rotation.x = 0;
      mainGroup.rotation.y = 0;
      mainGroup.rotation.z = 0;
      
      const s = 1 + Math.abs(Math.sin(time * 3)) * 0.1;
      mainGroup.scale.set(s, s, s);
    }
    STATE.targetRotation.x = 0;
    STATE.targetRotation.y = 0;
    STATE.rotation.x = 0;
    STATE.rotation.y = 0;
    // ------------------------------
  } else if (STATE.mode === "EXPLODE" || STATE.mode === "PHOTO") {
    if (titleMesh) titleMesh.visible = false;
    if (starMesh) starMesh.visible = false;
    if (loveMesh) loveMesh.visible = false;

    if (STATE.mode === "EXPLODE" && STATE.hand.detected) {
      STATE.targetRotation.y = STATE.hand.x * 2.5;
      STATE.targetRotation.x = STATE.hand.y * 0.5;
    }

    photoMeshes.forEach((mesh, i) => {
      if (STATE.mode === "PHOTO" && i === STATE.selectedIndex) {
        mesh.position.lerp(new THREE.Vector3(0, 0, 50), 0.1);
        mesh.scale.lerp(new THREE.Vector3(2.5, 2.5, 2.5), 0.1);
        mesh.rotation.set(0, 0, 0);
        mesh.lookAt(camera.position);
        mesh.visible = true;
      } else {
        if (STATE.mode === "PHOTO") {
          mesh.scale.lerp(new THREE.Vector3(0, 0, 0), 0.1);
        } else {
          const original = mesh.userData.originalPos;
          const groupRotY = mainGroup ? mainGroup.rotation.y : 0;
          const angle = Math.atan2(original.x, original.z) + groupRotY;
          const r = CONFIG.photoOrbitRadius;

          const x = Math.sin(angle) * r;
          const z = Math.cos(angle) * r;
          
          // Khi chụm tay, dừng hoàn toàn hiệu ứng nhấp nhô để ảnh đứng yên tuyệt đối
          const bobbing = STATE.isPinching ? 0 : Math.sin(time + i) * 2;
          const y = original.y + bobbing;

          mesh.position.lerp(new THREE.Vector3(x, y, z), 0.1);
          mesh.scale.lerp(new THREE.Vector3(1, 1, 1), 0.1);
          mesh.lookAt(camera.position);
          mesh.visible = true;
        }
      }
    });
  }

  // XOAY GROUP CHÍNH (CÂY) - Chỉ xoay khi không phải là HEART
  if (STATE.mode !== "HEART") {
    if (!STATE.isPinching) {
      STATE.rotation.x += (STATE.targetRotation.x - STATE.rotation.x) * 5 * dt;
      STATE.rotation.y += (STATE.targetRotation.y - STATE.rotation.y) * 5 * dt;
    }

    if (mainGroup) {
      mainGroup.rotation.y = STATE.rotation.y;
      mainGroup.rotation.x = STATE.rotation.x;
      mainGroup.scale.set(1, 1, 1);
    }
  }

  // SAO TỰ XOAY TRÒN
  if (starMesh) starMesh.rotation.z -= dt;

  // CHỮ ĐỨNG YÊN
  if (titleMesh) titleMesh.rotation.set(0, 0, 0);

  renderer.render(scene, camera);
}

init();
