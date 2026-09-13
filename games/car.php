<?php
$gameSlug = 'car';
require_once 'game_base.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>NIGHT RACER • Car Racing</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@300;500;700&family=Share+Tech+Mono&display=swap" rel="stylesheet">
    <style>
        :root {
            --red: #ff3a2d;
            --orange: #ff7a1a;
            --yellow: #ffd60a;
            --blue: #3a9bff;
            --bg: #060610;
            --card: rgba(10,10,25,0.85);
            --border: rgba(255,58,45,0.3);
            --text-dim: #4a4a6a;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; user-select: none; -webkit-tap-highlight-color: transparent; }

        html, body {
            background: var(--bg) !important;
            overflow: hidden !important;
            font-family: 'Rajdhani', sans-serif;
            width: 100% !important; height: 100% !important;
        }
        header, nav, footer, .navbar, .site-header, .site-footer,
        .top-bar, #header, #footer, #navbar, #nav-wrapper,
        .breadcrumb, .page-title, .game-header, .game-description {
            display: none !important;
        }
        .game-section, .game-layout, .game-main,
        .container, .wrapper, main, #main, #content, #app {
            all: unset !important;
            display: block !important;
            position: static !important;
            width: auto !important; height: auto !important;
            margin: 0 !important; padding: 0 !important;
            background: transparent !important;
            overflow: visible !important;
            border: none !important; box-shadow: none !important;
        }
        canvas { display: block; position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 1; }

        #hud {
            position: fixed; top: 20px; right: 20px; z-index: 20;
            display: flex; flex-direction: column; gap: 2px;
            pointer-events: none;
        }
        .hud-panel {
            background: var(--card);
            backdrop-filter: blur(16px);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 14px 20px;
            min-width: 160px;
            position: relative;
            overflow: hidden;
        }
        .hud-panel::before {
            content: '';
            position: absolute; top: 0; left: 0; right: 0; height: 1px;
            background: linear-gradient(90deg, transparent, var(--red), transparent);
            opacity: 0.6;
        }
        .hud-score-val {
            font-family: 'Share Tech Mono', monospace;
            font-size: 42px; font-weight: 400;
            color: var(--red);
            text-shadow: 0 0 20px rgba(255,58,45,0.6), 0 0 40px rgba(255,58,45,0.3);
            line-height: 1;
            font-variant-numeric: tabular-nums;
            display: block;
            width: 100%;
            text-align: left;
        }
        .hud-label {
            font-size: 9px; letter-spacing: 3px; color: var(--text-dim);
            text-transform: uppercase; margin-bottom: 2px; font-weight: 700;
        }
        .hud-stats {
            display: grid; grid-template-columns: 1fr 1fr;
            gap: 10px; margin-top: 4px;
        }
        .hud-stat-val {
            font-family: 'Share Tech Mono', monospace;
            font-size: 16px;
            color: var(--yellow);
            text-shadow: 0 0 8px rgba(255,214,10,0.4);
            font-variant-numeric: tabular-nums;
            display: flex;
            align-items: baseline;
            gap: 2px;
        }
        .hud-stat-val .unit {
            font-size: 9px;
            color: var(--text-dim);
            font-family: 'Rajdhani', sans-serif;
            font-weight: 700;
            letter-spacing: 1px;
        }
        .hud-best-val {
            font-family: 'Share Tech Mono', monospace;
            font-size: 18px;
            color: #a0a0c0; margin-bottom: 8px;
            font-variant-numeric: tabular-nums;
            text-align: left;
            display: block;
        }
        .lives-row { display: flex; gap: 5px; align-items: center; margin-top: 2px; }
        .heart { font-size: 16px; transition: transform 0.3s, opacity 0.3s; }
        .heart.empty { opacity: 0.2; filter: grayscale(1); transform: scale(0.8); }

        .coin-progress { margin-top: 8px; }
        .coin-bar-track {
            width: 100%; height: 4px; background: rgba(255,255,255,0.08);
            border-radius: 4px; overflow: hidden;
        }
        .coin-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--yellow), var(--orange));
            border-radius: 4px;
            transition: width 0.3s cubic-bezier(0.34,1.56,0.64,1);
            box-shadow: 0 0 8px var(--yellow);
        }
        .coin-text {
            display: flex; justify-content: space-between;
            font-size: 10px; color: var(--text-dim); margin-bottom: 4px;
            font-family: 'Orbitron', monospace;
        }

        .speed-gauge {
            position: relative;
            display: flex; align-items: center; gap: 8px; margin-top: 4px;
        }
        .speed-bar-track {
            flex: 1; height: 6px; background: rgba(255,255,255,0.08);
            border-radius: 6px; overflow: hidden;
        }
        .speed-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--blue), var(--red));
            border-radius: 6px;
            transition: width 0.2s ease;
            box-shadow: 0 0 10px rgba(58,155,255,0.5);
        }

        #nav {
            position: fixed; top: 20px; left: 20px; z-index: 20;
            background: var(--card); backdrop-filter: blur(12px);
            padding: 8px 18px; border-radius: 40px;
            border: 1px solid var(--border);
        }
        #nav a { color: #555; text-decoration: none; font-size: 12px; letter-spacing: 2px; transition: color 0.2s; }
        #nav a:hover { color: var(--red); }

        #overlay {
            position: fixed; inset: 0;
            background: rgba(0,0,0,0.93);
            backdrop-filter: blur(20px);
            z-index: 50;
            display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            gap: 18px;
            transition: opacity 0.4s;
        }
        .overlay-eyebrow {
            font-size: 11px; letter-spacing: 6px; color: var(--text-dim);
            font-weight: 700;
        }
        .overlay-title {
            font-family: 'Orbitron', monospace;
            font-size: clamp(36px, 7vw, 72px);
            font-weight: 900;
            background: linear-gradient(135deg, var(--red) 0%, var(--orange) 50%, var(--yellow) 100%);
            -webkit-background-clip: text; background-clip: text; color: transparent;
            letter-spacing: 4px;
            text-shadow: none;
            animation: titlePulse 3s ease-in-out infinite;
        }
        @keyframes titlePulse {
            0%, 100% { filter: brightness(1); }
            50% { filter: brightness(1.2); }
        }
        #overlayMsg {
            color: #5a5a7a; font-size: 13px; text-align: center;
            letter-spacing: 2px; max-width: 360px; line-height: 1.8;
        }
        .overlay-row { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; justify-content: center; }

        .color-selector { display: flex; gap: 12px; }
        .color-option {
            width: 32px; height: 32px; border-radius: 50%;
            cursor: pointer; border: 2px solid transparent;
            transition: transform 0.2s, border-color 0.2s, box-shadow 0.2s;
        }
        .color-option:hover { transform: scale(1.15); }
        .color-option.selected {
            border-color: var(--yellow);
            box-shadow: 0 0 12px var(--yellow);
            transform: scale(1.1);
        }

        #difficultySelect {
            background: rgba(255,58,45,0.08);
            border: 1px solid var(--border); color: var(--red);
            padding: 10px 20px; font-size: 12px;
            font-family: 'Rajdhani', sans-serif; font-weight: 700;
            letter-spacing: 2px; border-radius: 40px; cursor: pointer;
            outline: none; transition: 0.2s;
        }
        #difficultySelect:hover { background: rgba(255,58,45,0.15); }
        #difficultySelect option { background: #111; }

        .btn-neon {
            background: transparent;
            border: 2px solid var(--red); color: var(--red);
            padding: 12px 36px; font-size: 13px;
            font-family: 'Orbitron', monospace;
            font-weight: 700; letter-spacing: 4px;
            cursor: pointer; border-radius: 60px;
            transition: 0.25s;
            position: relative; overflow: hidden;
        }
        .btn-neon::before {
            content: '';
            position: absolute; inset: 0;
            background: var(--red);
            opacity: 0; transform: scaleX(0);
            transition: transform 0.3s, opacity 0.3s;
            transform-origin: left;
        }
        .btn-neon:hover::before { transform: scaleX(1); opacity: 0.15; }
        .btn-neon:hover { box-shadow: 0 0 24px rgba(255,58,45,0.5), inset 0 0 24px rgba(255,58,45,0.05); }
        .btn-neon:active { transform: scale(0.96); }

        /* Playtime widget */
        #playtime-widget {
            position: fixed; bottom: 24px; left: 14px; z-index: 20;
            display: flex; align-items: center; gap: 7px;
            background: var(--card); backdrop-filter: blur(16px);
            border: 1px solid color-mix(in srgb, var(--pt-accent, #ef4444) 35%, transparent);
            border-radius: 40px; padding: 6px 14px;
            pointer-events: none;
            font-family: 'Share Tech Mono', 'Orbitron', monospace;
        }
        #playtime-widget .pt-icon { font-size: 11px; filter: drop-shadow(0 0 6px var(--pt-accent, #ef4444)); }
        #playtime-widget .pt-label { font-size: 8px; letter-spacing: 2px; color: color-mix(in srgb, var(--pt-accent, #ef4444) 55%, transparent); text-transform: uppercase; }
        #playtime-widget .pt-value { font-size: 12px; font-weight: 700; letter-spacing: 1px; color: var(--pt-accent, #ef4444); text-shadow: 0 0 10px color-mix(in srgb, var(--pt-accent, #ef4444) 60%, transparent); min-width: 38px; text-align: right; }

        #warningToast {
            position: fixed; bottom: 80px; left: 50%;
            transform: translateX(-50%) translateY(20px);
            background: rgba(10,10,20,0.9);
            border: 1px solid rgba(255,214,10,0.4);
            padding: 10px 24px; border-radius: 40px;
            font-size: 13px; color: var(--yellow);
            font-family: 'Orbitron', monospace; font-weight: 700;
            letter-spacing: 2px;
            z-index: 25; pointer-events: none;
            white-space: nowrap;
            backdrop-filter: blur(12px);
            opacity: 0;
            transition: opacity 0.25s, transform 0.25s;
        }
        #warningToast.visible {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }

        #nitroBar {
            position: fixed; bottom: 24px; left: 50%;
            transform: translateX(-50%);
            width: min(300px, 60vw);
            z-index: 20;
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.3s;
        }
        #nitroBar.visible { opacity: 1; }
        .nitro-label {
            font-family: 'Orbitron', monospace;
            font-size: 9px; letter-spacing: 4px;
            color: var(--orange); text-align: center;
            margin-bottom: 5px;
        }
        .nitro-track {
            width: 100%; height: 5px;
            background: rgba(255,120,0,0.15);
            border-radius: 5px; overflow: hidden;
            border: 1px solid rgba(255,120,0,0.3);
        }
        .nitro-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--orange), var(--yellow));
            border-radius: 5px;
            box-shadow: 0 0 10px var(--orange);
            transition: width 0.05s linear;
        }

        .game-controls {
            position: fixed; bottom: 24px; right: 24px;
            display: flex; gap: 8px; z-index: 20;
        }
        .game-controls button {
            background: var(--card); border: 1px solid var(--border);
            color: #666; padding: 8px 16px; border-radius: 30px;
            cursor: pointer; font-size: 11px;
            font-family: 'Rajdhani', sans-serif; font-weight: 700;
            letter-spacing: 2px; transition: 0.2s;
            backdrop-filter: blur(8px);
        }
        .game-controls button:hover { color: var(--red); border-color: var(--red); }

        #comboDisplay {
            position: fixed; left: 50%; top: 40%;
            transform: translateX(-50%);
            font-family: 'Orbitron', monospace;
            font-size: 28px; font-weight: 900;
            color: var(--yellow);
            text-shadow: 0 0 20px var(--yellow);
            pointer-events: none; z-index: 22;
            opacity: 0;
            transition: opacity 0.3s, transform 0.3s;
        }
        #comboDisplay.pop {
            opacity: 1;
            animation: comboPop 0.8s forwards;
        }
        @keyframes comboPop {
            0% { opacity: 1; transform: translateX(-50%) scale(1.3); }
            60% { opacity: 1; transform: translateX(-50%) scale(1); }
            100% { opacity: 0; transform: translateX(-50%) translateY(-30px) scale(0.9); }
        }

        #countdown {
            position: fixed; top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            font-family: 'Orbitron', monospace;
            font-size: 120px; font-weight: 900;
            color: var(--red);
            text-shadow: 0 0 30px var(--red);
            z-index: 100;
            pointer-events: none;
            display: none;
        }
    </style>
</head>
<body>

<canvas id="gameCanvas"></canvas>
<div id="nav"><a href="../index.php">← HOME</a></div>

<div id="hud">
    <div class="hud-panel">
        <div class="hud-label">SCORE</div>
        <div class="hud-score-val" id="scoreValue">0</div>
        <div style="margin-top:6px;">
            <div class="hud-label">BEST</div>
            <div class="hud-best-val" id="bestValue"><?= number_format((int)$myBest) ?></div>
        </div>
        <div class="hud-stats">
            <div class="hud-stat">
                <div class="hud-label">SPEED</div>
                <div class="hud-stat-val"><span id="speedValue">0</span><span class="unit">km/h</span></div>
            </div>
            <div class="hud-stat">
                <div class="hud-label">DIST</div>
                <div class="hud-stat-val"><span id="distValue">0</span><span class="unit">m</span></div>
            </div>
        </div>
        <div style="margin-top:8px;">
            <div class="speed-gauge">
                <div class="speed-bar-track" style="flex:1;">
                    <div class="speed-bar-fill" id="speedBar" style="width:10%;"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="hud-panel" style="margin-top:2px;">
        <div class="hud-label">LIVES</div>
        <div class="lives-row" id="livesRow">
            <span class="heart" id="h1">❤️</span>
            <span class="heart" id="h2">❤️</span>
            <span class="heart" id="h3">❤️</span>
        </div>
        <div class="coin-progress" style="margin-top:10px;">
            <div class="coin-text">
                <span>COINS</span>
                <span id="coinsText">0 / 15</span>
            </div>
            <div class="coin-bar-track">
                <div class="coin-bar-fill" id="coinBar" style="width:0%;"></div>
            </div>
        </div>
    </div>
</div>

<div id="overlay">
    <div class="overlay-eyebrow">⚡ NIGHT RACER ⚡</div>
    <div class="overlay-title" id="overlayTitle">CAR RACING</div>
    <div id="overlayMsg"></div>
    <div class="color-selector" id="colorSelector">
        <div class="color-option" style="background:#ef4444" data-color="#ef4444"></div>
        <div class="color-option" style="background:#3b82f6" data-color="#3b82f6"></div>
        <div class="color-option" style="background:#22c55e" data-color="#22c55e"></div>
        <div class="color-option" style="background:#a855f7" data-color="#a855f7"></div>
        <div class="color-option" style="background:#f59e0b" data-color="#f59e0b"></div>
    </div>
    <div class="overlay-row">
        <select id="difficultySelect">
            <option value="easy">🌿 EASY</option>
            <option value="normal" selected>⚡ NORMAL</option>
            <option value="hard">🔥 HARD</option>
        </select>
        <a href="../index.php" id="back-link" class="btn-neon" style="text-decoration:none; display:inline-flex; align-items:center; gap:8px;">← BACK</a>
        <button class="btn-neon" id="startButton">START ENGINE</button>
        <button class="btn-neon" id="resumeButton" style="display:none">RESUME</button>
    </div>
    <div id="overlayControls" style="color:#333; font-size:11px; letter-spacing:2px;">← → STEER &nbsp;•&nbsp; ↑ NITRO &nbsp;•&nbsp; 🛡️ SHIELD &nbsp;•&nbsp; ⏳ SLOWMO</div>
</div>

<div id="countdown"></div>
<div id="warningToast"></div>

<div id="nitroBar">
    <div class="nitro-label">🔥 NITRO ACTIVE</div>
    <div class="nitro-track">
        <div class="nitro-fill" id="nitroFill" style="width:100%;"></div>
    </div>
</div>

<div id="comboDisplay"></div>

<div class="game-controls">
    <button id="newGameBtn">↺ NEW</button>
    <button id="pauseBtn">⏸ PAUSE</button>
</div>

<script src="../assets/playtime.js"></script>
<script>
const LOGGED_IN = <?= isLoggedIn() ? 'true' : 'false' ?>;
const GAME_SLUG = 'car';
const SAVE_SCORE_URL = '../api/save_score.php';
const DB_BEST = <?= (int)$myBest ?>;

const canvas = document.getElementById('gameCanvas');
const ctx = canvas.getContext('2d');
let W, H;
function resizeCanvas() { W = canvas.width = window.innerWidth; H = canvas.height = window.innerHeight; }
window.addEventListener('resize', resizeCanvas);
resizeCanvas();

const ROAD_W = 360;
const LANE_W = ROAD_W / 3;
const CAR_W = 46;
const CAR_H = 72;
const COIN_R = 10;
function roadX() { return (W - ROAD_W) / 2; }

let difficulty = 'normal';
const diffSettings = {
    easy:   { baseSpeed: 3.2, spawnRate: 0.012, speedInc: 0.0003 },
    normal: { baseSpeed: 4.2, spawnRate: 0.017, speedInc: 0.0005 },
    hard:   { baseSpeed: 5.5, spawnRate: 0.024, speedInc: 0.0008 },
};

let audioCtx = null;
const soundCooldown = {};
function playSound(type) {
    const now = performance.now();
    const limits = { coin: 150, pickup: 300, crash: 500, shield: 400, slowmo: 400, nitro: 600 };
    if (soundCooldown[type] && now - soundCooldown[type] < (limits[type] || 200)) return;
    soundCooldown[type] = now;
    if (!audioCtx) audioCtx = new (window.AudioContext || window.webkitAudioContext)();
    if (audioCtx.state === 'suspended') audioCtx.resume();
    try {
        const osc = audioCtx.createOscillator();
        const gain = audioCtx.createGain();
        osc.connect(gain); gain.connect(audioCtx.destination);
        const configs = {
            coin:   { freq: 1100, dur: 0.08, vol: 0.1, type: 'sine' },
            pickup: { freq: 700,  dur: 0.12, vol: 0.12, type: 'sine' },
            crash:  { freq: 180,  dur: 0.4,  vol: 0.2,  type: 'sawtooth' },
            shield: { freq: 660,  dur: 0.18, vol: 0.15, type: 'sine' },
            slowmo: { freq: 520,  dur: 0.22, vol: 0.15, type: 'sine' },
            nitro:  { freq: 880,  dur: 0.15, vol: 0.2,  type: 'square' },
        };
        const c = configs[type] || { freq: 440, dur: 0.1, vol: 0.1, type: 'sine' };
        osc.type = c.type;
        osc.frequency.value = c.freq;
        gain.gain.value = c.vol;
        gain.gain.exponentialRampToValueAtTime(0.0001, audioCtx.currentTime + c.dur);
        osc.start(); osc.stop(audioCtx.currentTime + c.dur);
    } catch(e) {}
}

let player = { x: 0, y: 0, vx: 0, color: '#ef4444', targetX: 0 };
let enemies = [], coins = [], powerups = [], particles = [];
let roadY = 0, distance = 0, coinBonus = 0, score = 0;
let coinsCollected = 0, baseSpeed = 4.2, currentSpeed = 4.2;
let lives = 3, invincibleFrames = 0, shieldFrames = 0, slowmoFrames = 0;
let scoreMultiplier = 1, combo = 1, comboTimer = 0, frameCount = 0;
let gameRunning = false, paused = false, animFrame = null;
let isCountingDown = false;
let shakeX = 0, shakeY = 0, shakeIntensity = 0;
let highScore = DB_BEST;
let boostActive = false, boostTimer = 0, boostMaxTime = 80, originalSpeed = 0;
let startTime;

let stars = [];
for (let i = 0; i < 180; i++) stars.push({
    x: Math.random() * 2000, y: Math.random() * 2000,
    size: Math.random() * 1.8 + 0.4,
    speed: 0.2 + Math.random() * 0.5,
    alpha: 0.2 + Math.random() * 0.4
});

const CAR_COLORS = ['#e74c3c','#3498db','#2ecc71','#f39c12','#9b59b6','#1abc9c','#e91e63'];

document.getElementById('bestValue').textContent = highScore.toLocaleString();

function updateHUD() {
    document.getElementById('scoreValue').textContent = Math.floor(score).toLocaleString();
    const kmh = Math.min(Math.floor(currentSpeed * 18), 320);
    document.getElementById('speedValue').textContent = kmh;
    document.getElementById('distValue').textContent = Math.floor(distance) + 'm';
    document.getElementById('coinsText').textContent = coinsCollected + ' / 15';
    const pct = (coinsCollected / 15) * 100;
    document.getElementById('coinBar').style.width = pct + '%';
    const speedPct = Math.min((currentSpeed / 12) * 100, 100);
    document.getElementById('speedBar').style.width = speedPct + '%';
    for (let i = 1; i <= 3; i++) {
        const el = document.getElementById('h' + i);
        if (i <= lives) { el.classList.remove('empty'); el.textContent = '❤️'; }
        else { el.classList.add('empty'); el.textContent = '🖤'; }
    }
}

let toastTimeout = null;
let toastQueue = [];
let toastActive = false;
function showMessage(msg) {
    toastQueue.push(msg);
    if (!toastActive) processToast();
}
function processToast() {
    if (toastQueue.length === 0) { toastActive = false; return; }
    toastActive = true;
    const msg = toastQueue.shift();
    const el = document.getElementById('warningToast');
    el.textContent = msg;
    el.classList.add('visible');
    if (toastTimeout) clearTimeout(toastTimeout);
    toastTimeout = setTimeout(() => {
        el.classList.remove('visible');
        setTimeout(processToast, 300);
    }, 1600);
}

function updateNitroBar() {
    const bar = document.getElementById('nitroBar');
    if (boostActive) {
        bar.classList.add('visible');
        document.getElementById('nitroFill').style.width = (boostTimer / boostMaxTime * 100) + '%';
    } else {
        bar.classList.remove('visible');
    }
}

function showCombo(c) {
    if (c < 2) return;
    const el = document.getElementById('comboDisplay');
    el.textContent = 'x' + c + ' COMBO!';
    el.classList.remove('pop');
    void el.offsetWidth;
    el.classList.add('pop');
}

function addExplosion(x, y) {
    for (let i = 0; i < 28; i++) {
        const a = Math.random() * Math.PI * 2, sp = 2.5 + Math.random() * 6;
        particles.push({ x, y, vx: Math.cos(a)*sp, vy: Math.sin(a)*sp - 2, life: 1, decay: 0.018 + Math.random()*0.01, color: `hsl(${Math.random()*30+5},90%,60%)`, size: 3 + Math.random()*5 });
    }
    shakeIntensity = 10;
    playSound('crash');
}
function addCoinSparkle(x, y) {
    for (let i = 0; i < 8; i++) particles.push({ x, y, vx: (Math.random()-0.5)*3.5, vy: (Math.random()-0.5)*3.5 - 1.2, life: 0.8, decay: 0.025, color: '#ffd60a', size: 2 + Math.random()*3 });
}
function addShieldGlow(x, y) {
    for (let i = 0; i < 10; i++) particles.push({ x: x+(Math.random()-0.5)*50, y: y+(Math.random()-0.5)*50, vx:0, vy:-0.5, life: 0.5, decay: 0.02, color: '#88aaff', size: 5+Math.random()*8 });
}
function updateParticles() {
    for (let i = particles.length - 1; i >= 0; i--) {
        const p = particles[i];
        p.x += p.vx; p.y += p.vy;
        p.vy += 0.08;
        p.life -= p.decay;
        if (p.life <= 0) particles.splice(i, 1);
    }
    if (shakeIntensity > 0) {
        shakeX = (Math.random()-0.5)*shakeIntensity*2;
        shakeY = (Math.random()-0.5)*shakeIntensity*2;
        shakeIntensity = Math.max(0, shakeIntensity - 0.6);
    } else { shakeX = shakeY = 0; }
}

function drawStars() {
    for (const s of stars) {
        const x = ((s.x + frameCount * s.speed * 0.4) % W + W) % W;
        const y = ((s.y + frameCount * s.speed * 0.15) % H + H) % H;
        const tw = 0.6 + Math.sin(frameCount * 0.02 + s.x) * 0.4;
        ctx.fillStyle = `rgba(200,200,255,${s.alpha * tw})`;
        ctx.fillRect(x, y, s.size, s.size);
    }
}

let roadLineOffset = 0;
function drawRoad() {
    const rx = roadX();
    ctx.fillStyle = '#07070e';
    ctx.fillRect(0, 0, rx, H);
    ctx.fillRect(rx + ROAD_W, 0, W - rx - ROAD_W, H);
    const grd = ctx.createLinearGradient(rx, 0, rx + ROAD_W, 0);
    grd.addColorStop(0, '#0e0e18');
    grd.addColorStop(0.3, '#181828');
    grd.addColorStop(0.7, '#181828');
    grd.addColorStop(1, '#0e0e18');
    ctx.fillStyle = grd;
    ctx.fillRect(rx, 0, ROAD_W, H);
    ctx.strokeStyle = 'rgba(255,255,255,0.02)';
    ctx.lineWidth = 1;
    for (let i = 0; i < 6; i++) {
        const lx = rx + (i * ROAD_W / 5);
        ctx.beginPath(); ctx.moveTo(lx, 0); ctx.lineTo(lx, H); ctx.stroke();
    }
    ctx.lineWidth = 3;
    ctx.strokeStyle = '#ffd60a';
    ctx.shadowBlur = 8; ctx.shadowColor = '#ffd60a40';
    ctx.beginPath(); ctx.moveTo(rx + 3, 0); ctx.lineTo(rx + 3, H); ctx.stroke();
    ctx.beginPath(); ctx.moveTo(rx + ROAD_W - 3, 0); ctx.lineTo(rx + ROAD_W - 3, H); ctx.stroke();
    ctx.shadowBlur = 0;
    roadLineOffset = (roadLineOffset + currentSpeed) % 70;
    ctx.setLineDash([28, 42]);
    ctx.lineDashOffset = -roadLineOffset;
    ctx.strokeStyle = 'rgba(255,255,255,0.25)';
    ctx.lineWidth = 2;
    for (let i = 1; i <= 2; i++) {
        const lx = rx + i * LANE_W;
        ctx.beginPath(); ctx.moveTo(lx, 0); ctx.lineTo(lx, H); ctx.stroke();
    }
    ctx.setLineDash([]); ctx.lineDashOffset = 0;
    const treeOffset = (frameCount * currentSpeed * 0.8) % 140;
    for (let i = 0; i < 9; i++) {
        const ty = (i * 140 - treeOffset + 140) % (H + 140) - 40;
        _drawTree(rx - 32, ty);
        _drawTree(rx + ROAD_W + 12, ty);
    }
    if (slowmoFrames > 0) {
        ctx.fillStyle = `rgba(40,80,255,${Math.min(0.08, slowmoFrames / 2000)})`;
        ctx.fillRect(0, 0, W, H);
    }
}
function _drawTree(x, y) {
    ctx.fillStyle = '#1a2e1a';
    ctx.fillRect(x + 6, y + 22, 8, 28);
    ctx.fillStyle = '#1e4d2b';
    ctx.beginPath(); ctx.arc(x + 10, y + 16, 16, 0, Math.PI*2); ctx.fill();
    ctx.fillStyle = '#2d6a40';
    ctx.beginPath(); ctx.arc(x + 10, y + 10, 10, 0, Math.PI*2); ctx.fill();
}

function drawCar(x, y, color, isPlayer=false, hasShield=false) {
    ctx.save();
    const alpha = (isPlayer && invincibleFrames > 0 && Math.floor(frameCount / 4) % 2 === 0) ? 0.35 : 1;
    ctx.globalAlpha = alpha;
    ctx.shadowColor = isPlayer ? color : 'rgba(0,0,0,0.6)';
    ctx.shadowBlur = isPlayer ? 16 : 8;
    ctx.fillStyle = color;
    ctx.beginPath();
    ctx.roundRect(x + 5, y + 8, CAR_W - 10, CAR_H - 16, 4);
    ctx.fill();
    ctx.fillStyle = isPlayer ? '#1a2540' : '#1a1a2a';
    ctx.beginPath();
    ctx.roundRect(x + 10, y + 16, CAR_W - 20, CAR_H * 0.35, 3);
    ctx.fill();
    ctx.fillStyle = isPlayer ? 'rgba(100,180,255,0.35)' : 'rgba(60,60,120,0.4)';
    ctx.fillRect(x + 13, y + 18, CAR_W - 26, 14);
    ctx.fillStyle = '#111';
    const ww = 9, wh = 16;
    ctx.fillRect(x, y + 16, ww, wh);
    ctx.fillRect(x + CAR_W - ww, y + 16, ww, wh);
    ctx.fillRect(x, y + CAR_H - wh - 8, ww, wh);
    ctx.fillRect(x + CAR_W - ww, y + CAR_H - wh - 8, ww, wh);
    ctx.fillStyle = '#333';
    ctx.beginPath(); ctx.arc(x + 4.5, y + 24, 3.5, 0, Math.PI*2); ctx.fill();
    ctx.beginPath(); ctx.arc(x + CAR_W - 4.5, y + 24, 3.5, 0, Math.PI*2); ctx.fill();
    ctx.beginPath(); ctx.arc(x + 4.5, y + CAR_H - 16, 3.5, 0, Math.PI*2); ctx.fill();
    ctx.beginPath(); ctx.arc(x + CAR_W - 4.5, y + CAR_H - 16, 3.5, 0, Math.PI*2); ctx.fill();
    if (isPlayer) {
        ctx.fillStyle = '#ff6600';
        ctx.fillRect(x + 8, y + CAR_H - 8, 9, 5);
        ctx.fillRect(x + CAR_W - 17, y + CAR_H - 8, 9, 5);
        ctx.shadowColor = '#ff6600'; ctx.shadowBlur = 10;
        ctx.fillRect(x + 8, y + CAR_H - 8, 9, 5);
        ctx.fillRect(x + CAR_W - 17, y + CAR_H - 8, 9, 5);
    } else {
        ctx.fillStyle = '#fffde0';
        ctx.fillRect(x + 8, y + 3, 9, 6);
        ctx.fillRect(x + CAR_W - 17, y + 3, 9, 6);
        ctx.shadowColor = '#ffffaa'; ctx.shadowBlur = 12;
        ctx.fillRect(x + 8, y + 3, 9, 6);
    }
    if (boostActive && isPlayer) {
        ctx.shadowColor = '#ff8800'; ctx.shadowBlur = 20;
        ctx.fillStyle = '#ff8800';
        const fl = 8 + Math.random() * 12;
        ctx.beginPath();
        ctx.moveTo(x + 12, y + CAR_H);
        ctx.lineTo(x + 22, y + CAR_H + fl);
        ctx.lineTo(x + CAR_W - 12, y + CAR_H);
        ctx.closePath(); ctx.fill();
        ctx.fillStyle = '#ffd60a';
        ctx.beginPath();
        ctx.moveTo(x + 16, y + CAR_H);
        ctx.lineTo(x + CAR_W/2, y + CAR_H + fl * 0.6);
        ctx.lineTo(x + CAR_W - 16, y + CAR_H);
        ctx.closePath(); ctx.fill();
    }
    if (hasShield) {
        ctx.shadowColor = '#88aaff'; ctx.shadowBlur = 18;
        ctx.strokeStyle = `rgba(140,170,255,${0.5 + 0.4 * Math.sin(frameCount * 0.12)})`;
        ctx.lineWidth = 2.5;
        ctx.beginPath();
        ctx.ellipse(x + CAR_W/2, y + CAR_H/2, CAR_W/1.6, CAR_H/1.6, 0, 0, Math.PI*2);
        ctx.stroke();
    }
    ctx.globalAlpha = 1;
    ctx.shadowBlur = 0;
    ctx.restore();
}

function drawParticles() {
    for (const p of particles) {
        ctx.globalAlpha = Math.max(0, p.life);
        ctx.fillStyle = p.color;
        ctx.beginPath();
        ctx.arc(p.x, p.y, p.size * p.life, 0, Math.PI*2);
        ctx.fill();
    }
    ctx.globalAlpha = 1;
}

function drawCoins() {
    for (const c of coins) {
        const glow = 0.6 + 0.4 * Math.sin(frameCount * 0.1 + c.x);
        ctx.shadowColor = '#ffd60a'; ctx.shadowBlur = 10 * glow;
        ctx.fillStyle = `hsl(48, 100%, ${50 + glow*10}%)`;
        ctx.beginPath(); ctx.arc(c.x, c.y, c.r, 0, Math.PI*2); ctx.fill();
        ctx.fillStyle = 'rgba(255,255,255,0.5)';
        ctx.beginPath(); ctx.arc(c.x - c.r*0.3, c.y - c.r*0.3, c.r*0.35, 0, Math.PI*2); ctx.fill();
        ctx.shadowBlur = 0;
    }
}

function drawPowerups() {
    for (const p of powerups) {
        const pulse = 1 + 0.08 * Math.sin(frameCount * 0.1);
        ctx.save();
        ctx.translate(p.x + 15, p.y + 15);
        ctx.scale(pulse, pulse);
        ctx.shadowColor = p.type === 'shield' ? '#88aaff' : '#ffd60a';
        ctx.shadowBlur = 16;
        ctx.font = '28px serif';
        ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
        ctx.fillText(p.type === 'shield' ? '🛡️' : '⏳', 0, 0);
        ctx.restore();
        ctx.shadowBlur = 0;
    }
}

function spawnEnemy() {
    const lane = Math.floor(Math.random() * 3);
    const laneX = roadX() + lane * LANE_W + (LANE_W - CAR_W) / 2;
    for (const e of enemies) {
        if (e.laneIdx === lane && e.y < CAR_H + 100) return;
    }
    enemies.push({
        x: laneX, y: -CAR_H,
        color: CAR_COLORS[Math.floor(Math.random() * CAR_COLORS.length)],
        speedMult: 0.85 + Math.random() * 0.65,
        laneIdx: lane, passedNear: false
    });
}
function spawnCoin() {
    if (Math.random() > 0.008) return;
    const lane = Math.floor(Math.random() * 3);
    coins.push({ x: roadX() + lane * LANE_W + LANE_W/2, y: -COIN_R, r: COIN_R });
}
function spawnPowerup() {
    if (powerups.length >= 2) return;
    if (frameCount - (window._lastPwrFrame || 0) < 480) return;
    if (Math.random() > 0.002) return;
    const lane = Math.floor(Math.random() * 3);
    powerups.push({ x: roadX() + lane * LANE_W + (LANE_W - 30)/2, y: -30, type: Math.random() < 0.5 ? 'shield' : 'slow' });
    window._lastPwrFrame = frameCount;
}

function resolveEnemyCollisions() {
    enemies.sort((a, b) => a.y - b.y);
    for (let i = 0; i < enemies.length - 1; i++) {
        const a = enemies[i], b = enemies[i+1];
        if (a.laneIdx === b.laneIdx && b.y - a.y < CAR_H + 4) {
            b.y = a.y + CAR_H + 4;
        }
    }
}

function initGame() {
    const s = diffSettings[difficulty];
    baseSpeed = s.baseSpeed;
    currentSpeed = baseSpeed;
    player.x = roadX() + LANE_W + (LANE_W - CAR_W) / 2;
    player.y = H - CAR_H - 40;
    player.vx = 0;
    enemies = []; coins = []; powerups = []; particles = [];
    distance = 0; coinBonus = 0; coinsCollected = 0; score = 0;
    lives = 3; invincibleFrames = 0; shieldFrames = 0; slowmoFrames = 0;
    scoreMultiplier = 1; combo = 1; comboTimer = 0; frameCount = 0;
    boostActive = false; boostTimer = 0; shakeIntensity = 0;
    roadLineOffset = 0;
    document.getElementById('nitroBar').classList.remove('visible');
    updateHUD();
}

function loseLife() {
    if (invincibleFrames > 0 || shieldFrames > 0) return;
    lives--;
    addExplosion(player.x + CAR_W/2, player.y + CAR_H/2);
    updateHUD();
    if (lives <= 0) {
        gameRunning = false;
        if (animFrame) cancelAnimationFrame(animFrame);
        if (window.Playtime) Playtime.onGameEnd();
        const finalScore = Math.floor(score);
        if (finalScore > highScore) {
            highScore = finalScore;
            document.getElementById('bestValue').textContent = highScore.toLocaleString();
            if (LOGGED_IN) saveScore(finalScore, null, Math.floor((Date.now() - startTime) / 1000));
        }
        document.getElementById('overlayMsg').innerHTML = `
            <span style="color:#ff3a2d;font-family:Orbitron;font-size:16px;">CRASH!</span><br>
            <span style="color:#555;font-size:12px;">SCORE: ${finalScore.toLocaleString()} &nbsp;•&nbsp; BEST: ${highScore.toLocaleString()}</span>
        `;
        document.getElementById('startButton').textContent = 'RESTART';
        document.getElementById('overlayTitle').textContent = 'CAR RACING';
        document.getElementById('resumeButton').style.display = 'none';
        document.getElementById('startButton').style.display = 'block';
        document.getElementById('colorSelector').style.display = 'flex';
        document.getElementById('difficultySelect').style.display = 'block';
        document.getElementById('overlayControls').style.display = 'block';
        document.getElementById('overlay').style.display = 'flex';
        return;
    }
    invincibleFrames = 50;
    enemies = enemies.filter(e => !(Math.abs(e.x - player.x) < CAR_W && Math.abs(e.y - player.y) < CAR_H));
}

function activateShield() {
    if (shieldFrames > 0) return;
    shieldFrames = 200;
    addShieldGlow(player.x + CAR_W/2, player.y + CAR_H/2);
    showMessage('🛡️ SHIELD ACTIVE');
    playSound('shield');
}

function activateBoost() {
    if (boostActive || slowmoFrames > 0) return;
    boostActive = true;
    boostTimer = boostMaxTime;
    originalSpeed = baseSpeed;
    currentSpeed = Math.min(13, currentSpeed + 3.5);
    showMessage('🔥 NITRO!');
    playSound('nitro');
}

function updateBoost() {
    if (!boostActive) return;
    boostTimer--;
    updateNitroBar();
    if (boostTimer <= 0) {
        boostActive = false;
        currentSpeed = baseSpeed;
        document.getElementById('nitroBar').classList.remove('visible');
    }
}

function update() {
    if (!gameRunning || paused || isCountingDown) return;
    frameCount++;
    const slow = slowmoFrames > 0 ? 0.55 : 1;
    distance += currentSpeed * 0.5 * slow;
    score = Math.floor(distance) + coinBonus;
    if (comboTimer > 0) { comboTimer--; if (comboTimer === 0) combo = 1; }
    player.vx *= 0.82;
    if (keys.ArrowLeft) player.vx -= 1.0;
    if (keys.ArrowRight) player.vx += 1.0;
    player.x += player.vx;
    player.x = Math.max(roadX() + 6, Math.min(roadX() + ROAD_W - CAR_W - 6, player.x));
    const s = diffSettings[difficulty];
    if (Math.random() < s.spawnRate) spawnEnemy();
    spawnCoin();
    spawnPowerup();
    if (frameCount % 420 === 0 && baseSpeed < 10.5) {
        baseSpeed += 0.2;
        if (!boostActive && slowmoFrames === 0) currentSpeed = baseSpeed;
    }
    if (slowmoFrames > 0) {
        slowmoFrames--;
        if (slowmoFrames === 0) {
            if (!boostActive) currentSpeed = baseSpeed;
            scoreMultiplier = 1;
            showMessage('⏳ Slowmo ended');
        }
    } else if (!boostActive) {
        currentSpeed = baseSpeed;
        scoreMultiplier = 1;
    }
    if (shieldFrames > 0) shieldFrames--;
    updateBoost();
    const spd = currentSpeed;
    for (const e of enemies) e.y += (spd + 1.8) * (e.speedMult || 1);
    for (const c of coins) c.y += spd * 0.7;
    for (const p of powerups) p.y += spd + 0.8;
    resolveEnemyCollisions();
    enemies = enemies.filter(e => e.y < H + CAR_H + 10);
    coins = coins.filter(c => c.y < H + 20);
    powerups = powerups.filter(p => p.y < H + 60);
    for (let i = coins.length - 1; i >= 0; i--) {
        const c = coins[i];
        if (Math.abs(c.x - (player.x + CAR_W/2)) < CAR_W/2 + c.r &&
            Math.abs(c.y - (player.y + CAR_H/2)) < CAR_H/2 + c.r) {
            coinBonus += 50 * Math.max(1, Math.floor(combo / 2));
            addCoinSparkle(c.x, c.y);
            coins.splice(i, 1);
            combo = Math.min(8, combo + 1);
            comboTimer = 90;
            coinsCollected++;
            playSound('coin');
            if (coinsCollected >= 15 && lives < 3) {
                lives = Math.min(3, lives + 1);
                coinsCollected = 0;
                showMessage('❤️ EXTRA LIFE!');
                playSound('shield');
            } else if (coinsCollected >= 15) {
                coinsCollected = 0;
                showMessage('✨ +500 BONUS');
                coinBonus += 500;
            }
        }
    }
    score = Math.floor(distance) + coinBonus;
    updateHUD();
    for (let i = powerups.length - 1; i >= 0; i--) {
        const p = powerups[i];
        if (Math.abs(p.x + 15 - (player.x + CAR_W/2)) < CAR_W/2 + 14 &&
            Math.abs(p.y + 15 - (player.y + CAR_H/2)) < CAR_H/2 + 14) {
            if (p.type === 'shield') activateShield();
            else {
                if (slowmoFrames <= 0) {
                    slowmoFrames = 160;
                    scoreMultiplier = 2;
                    currentSpeed = Math.max(2.5, currentSpeed * 0.5);
                    showMessage('⏳ SLOW MOTION x2');
                    playSound('slowmo');
                } else {
                    slowmoFrames = Math.min(280, slowmoFrames + 60);
                    showMessage('⏳ +TIME');
                }
                addCoinSparkle(p.x + 15, p.y + 15);
            }
            powerups.splice(i, 1);
        }
    }
    for (const e of enemies) {
        if (invincibleFrames > 0 || shieldFrames > 0) break;
        if (e.x < player.x + CAR_W - 10 && e.x + CAR_W - 10 > player.x &&
            e.y < player.y + CAR_H - 10 && e.y + CAR_H - 10 > player.y) {
            loseLife();
            if (lives <= 0) return;
            break;
        }
        if (!e.passedNear && e.y > player.y + CAR_H - 10 && e.y < player.y + CAR_H + 35) {
            if (Math.abs(e.x - player.x) < LANE_W) {
                e.passedNear = true;
                combo = Math.min(8, combo + 1);
                comboTimer = 100;
                if (combo > 1) showCombo(combo);
            }
        }
    }
    if (invincibleFrames > 0) invincibleFrames--;
    updateParticles();
}

function draw() {
    ctx.clearRect(0, 0, W, H);
    ctx.save();
    if (shakeIntensity > 0.5) ctx.translate(shakeX, shakeY);
    drawStars();
    drawRoad();
    drawCoins();
    drawPowerups();
    for (const e of enemies) drawCar(e.x, e.y, e.color, false, false);
    drawCar(player.x, player.y, player.color, true, shieldFrames > 0);
    drawParticles();
    if (shakeIntensity > 6) {
        ctx.fillStyle = `rgba(255,80,0,${shakeIntensity * 0.01})`;
        ctx.fillRect(0, 0, W, H);
    }
    ctx.restore();
    if (paused) {
        ctx.fillStyle = 'rgba(0,0,0,0.88)';
        ctx.fillRect(0, 0, W, H);
    }
}

const keys = { ArrowLeft: false, ArrowRight: false };

function runCountdown(callback) {
    const el = document.getElementById('countdown');
    el.style.display = 'block';
    isCountingDown = true;
    let count = 3;
    el.textContent = count;
    playSound('coin'); 
    const interval = setInterval(() => {
        count--;
        if (count > 0) {
            el.textContent = count;
            playSound('coin'); 
        } else if (count === 0) {
            el.textContent = 'GO!';
            playSound('nitro');
        } else {
            clearInterval(interval);
            el.style.display = 'none';
            isCountingDown = false;
            callback();
        }
    }, 800);
}

function gameLoop() {
    if (!gameRunning || paused || isCountingDown) return;
    update();
    draw();
    animFrame = requestAnimationFrame(gameLoop);
}

function startGame() {
    if (animFrame) cancelAnimationFrame(animFrame);
    initGame();
    gameRunning = true; paused = false;
    startTime = Date.now();
    document.getElementById('overlay').style.display = 'none';
    runCountdown(() => {
        if (window.Playtime) Playtime.onGameStart();
        animFrame = requestAnimationFrame(gameLoop);
    });
}

function togglePause() {
    if (!gameRunning || isCountingDown) return;
    paused = !paused;
    const overlay = document.getElementById('overlay');
    const title = document.getElementById('overlayTitle');
    const startBtn = document.getElementById('startButton');
    const resumeBtn = document.getElementById('resumeButton');
    const colorSel = document.getElementById('colorSelector');
    const diffSel = document.getElementById('difficultySelect');
    const controls = document.getElementById('overlayControls');

    if (paused) {
        title.textContent = 'PAUSED';
        if (window.Playtime) Playtime.onGamePause();
        startBtn.style.display = 'none';
        resumeBtn.style.display = 'block';
        colorSel.style.display = 'none';
        diffSel.style.display = 'none';
        controls.style.display = 'none';
        overlay.style.display = 'flex';
        if (animFrame) cancelAnimationFrame(animFrame);
    } else {
        overlay.style.display = 'none';
        runCountdown(() => {
            if (window.Playtime) Playtime.onGameResume();
            animFrame = requestAnimationFrame(gameLoop);
        });
    }
}

async function saveScore(s, lvl, dur) {
    if (!LOGGED_IN || s <= 0) return;
    try {
        const res = await fetch(SAVE_SCORE_URL, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ game: GAME_SLUG, score: Math.floor(s), level: lvl, duration: dur }),
            keepalive: true
        });
        const data = await res.json();
        if (data.success && data.best_score) {
            highScore = data.best_score;
            document.getElementById('bestValue').textContent = highScore.toLocaleString();
        }
    } catch(e) {}
}

document.addEventListener('keydown', (e) => {
    if (e.key === 'ArrowLeft')  { keys.ArrowLeft = true;  e.preventDefault(); }
    if (e.key === 'ArrowRight') { keys.ArrowRight = true; e.preventDefault(); }
    if (e.key === 'ArrowUp')    { if (gameRunning && !paused) activateBoost(); e.preventDefault(); }
    if (e.key === 'p' || e.key === 'P') { if (gameRunning) togglePause(); }
});
document.addEventListener('keyup', (e) => {
    if (e.key === 'ArrowLeft')  keys.ArrowLeft = false;
    if (e.key === 'ArrowRight') keys.ArrowRight = false;
});

document.getElementById('startButton').addEventListener('click', () => { startGame(); });
document.getElementById('resumeButton').addEventListener('click', () => { togglePause(); });
document.getElementById('newGameBtn').addEventListener('click', () => {
    startGame();
    document.getElementById('overlay').style.display = 'none';
});
document.getElementById('pauseBtn').addEventListener('click', togglePause);
document.getElementById('back-link').addEventListener('click', () => { if(gameRunning && score > 0) saveScore(score, null, Math.floor((Date.now() - startTime) / 1000)); });

if (window.Playtime) Playtime.init({ game: 'car', url: '../api/playtime.php', loggedIn: LOGGED_IN, accent: '#ef4444' });
document.querySelector('#nav a').addEventListener('click', () => { if(gameRunning && score > 0) saveScore(score, null, Math.floor((Date.now() - startTime) / 1000)); });
document.getElementById('difficultySelect').addEventListener('change', (e) => { difficulty = e.target.value; });

const savedColor = localStorage.getItem('racerColor');
if (savedColor) player.color = savedColor;
document.querySelectorAll('.color-option').forEach(opt => {
    if (opt.dataset.color === player.color) opt.classList.add('selected');
    opt.addEventListener('click', () => {
        player.color = opt.dataset.color;
        localStorage.setItem('racerColor', player.color);
        document.querySelectorAll('.color-option').forEach(o => o.classList.remove('selected'));
        opt.classList.add('selected');
    });
});

initGame();
draw();
document.getElementById('overlay').style.display = 'flex';
document.getElementById('startButton').textContent = 'START RACE';
document.getElementById('overlayMsg').innerHTML = '← → STEER &nbsp;•&nbsp; ↑ NITRO &nbsp;•&nbsp; 🛡️ SHIELD &nbsp;•&nbsp; ⏳ SLOWMO';

(function() {
    const keep = new Set();
    ['gameCanvas','hud','nav','overlay','warningToast','nitroBar','comboDisplay','countdown'].forEach(id => {
        let node = document.getElementById(id);
        while (node && node !== document.body) { keep.add(node); node = node.parentElement; }
    });
    document.querySelectorAll('.game-controls').forEach(el => {
        let node = el;
        while (node && node !== document.body) { keep.add(node); node = node.parentElement; }
    });
    Array.from(document.body.children).forEach(child => {
        if (!keep.has(child) && child.tagName !== 'SCRIPT' && child.tagName !== 'STYLE') {
            child.style.setProperty('display', 'none', 'important');
        }
    });
    document.documentElement.style.setProperty('background', '#060610', 'important');
    document.body.style.setProperty('background', '#060610', 'important');
    document.body.style.setProperty('overflow', 'hidden', 'important');
})();
</script>
</div></div></section>
</body>
</html>