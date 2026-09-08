<?php
$gameSlug = 'snake';
require_once 'game_base.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>NEON SNAKE</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@300;500;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; user-select: none; -webkit-tap-highlight-color: transparent; }

        html, body {
            width: 100% !important; height: 100% !important;
            background: #04080f !important;
            overflow: hidden !important;
            font-family: 'Rajdhani', sans-serif !important;
        }

        header, nav, footer, .navbar, .site-header, .site-footer,
        .top-bar, #header, #footer, #navbar, .breadcrumb,
        .game-header, .game-description, .page-title {
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

        canvas {
            position: fixed !important;
            top: 0 !important; left: 0 !important;
            width: 100% !important; height: 100% !important;
            display: block !important;
            z-index: 1;
        }

        :root {
            --green: #00ff88;
            --green-dim: #00cc66;
            --green-dark: #003320;
            --acid: #aaff00;
            --bg: #04080f;
            --card: rgba(4, 20, 12, 0.88);
            --border: rgba(0, 255, 136, 0.25);
            --text-dim: #1a5a3a;
        }

        #hud {
            position: fixed; top: 20px; right: 20px; z-index: 20;
            display: flex; flex-direction: column; gap: 3px;
            pointer-events: none;
        }
        .hud-panel {
            background: var(--card);
            backdrop-filter: blur(18px);
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 14px 20px;
            min-width: 150px;
            position: relative; overflow: hidden;
        }
        .hud-panel::before {
            content: '';
            position: absolute; top: 0; left: 0; right: 0; height: 1px;
            background: linear-gradient(90deg, transparent, var(--green), transparent);
            opacity: 0.5;
        }
        .hud-label {
            font-size: 9px; letter-spacing: 3px; color: var(--text-dim);
            text-transform: uppercase; margin-bottom: 2px; font-weight: 700;
        }
        .hud-score-val {
            font-size: 32px;
            min-width: 60px;
            color: var(--green);
            text-shadow: 0 0 20px rgba(0,255,136,0.7), 0 0 40px rgba(0,255,136,0.3);
            letter-spacing: -1px;
            transition: transform 0.1s;
        }
        .hud-score-val.pop { animation: scorePop 0.25s ease; }
        @keyframes scorePop {
            0% { transform: scale(1); }
            50% { transform: scale(1.3); }
            100% { transform: scale(1); }
        }
        .hud-stats { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 2px; }
        .hud-stat-val {
            font-family: 'Orbitron', monospace;
            font-size: 15px; font-weight: 700; color: var(--acid);
            text-shadow: 0 0 8px rgba(170,255,0,0.4);
        }
        .hud-best-val {
            font-family: 'Orbitron', monospace;
            font-size: 17px; font-weight: 700; color: #5a9a6a; margin-bottom: 8px;
        }

        #nav {
            position: fixed; top: 20px; left: 20px; z-index: 20;
            background: var(--card); backdrop-filter: blur(12px);
            padding: 8px 18px; border-radius: 40px;
            border: 1px solid var(--border);
        }
        #nav a { color: #2a5a3a; text-decoration: none; font-size: 12px; letter-spacing: 2px; transition: color 0.2s; }
        #nav a:hover { color: var(--green); }

        #overlay {
            position: fixed; inset: 0;
            background: rgba(0,0,0,0.92);
            backdrop-filter: blur(24px);
            z-index: 50;
            display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            gap: 16px;
        }
        .overlay-eyebrow {
            font-size: 10px; letter-spacing: 6px; color: var(--text-dim); font-weight: 700;
        }
        .overlay-title {
            font-family: 'Orbitron', monospace;
            font-size: clamp(52px, 9vw, 96px); font-weight: 900;
            background: linear-gradient(135deg, var(--green) 0%, var(--acid) 100%);
            -webkit-background-clip: text; background-clip: text; color: transparent;
            letter-spacing: 8px;
            filter: drop-shadow(0 0 30px rgba(0,255,136,0.4));
            animation: titleBreath 3s ease-in-out infinite;
        }
        @keyframes titleBreath {
            0%, 100% { filter: drop-shadow(0 0 20px rgba(0,255,136,0.3)); }
            50% { filter: drop-shadow(0 0 40px rgba(0,255,136,0.6)); }
        }
        #ov-msg {
            color: #2a5a3a; font-size: 13px; text-align: center;
            letter-spacing: 1px; max-width: 360px; line-height: 1.8;
        }
        #ov-msg span { color: var(--green); font-weight: 700; }

        .diff-row { display: flex; gap: 8px; }
        .diff-opt {
            padding: 8px 20px; border-radius: 30px;
            font-size: 11px; font-weight: 700; letter-spacing: 2px;
            cursor: pointer; border: 1px solid #0a3a1a;
            color: #2a5a3a; background: transparent; transition: 0.2s;
            font-family: 'Rajdhani', sans-serif;
        }
        .diff-opt:hover, .diff-opt.active {
            border-color: var(--green); color: var(--green);
            box-shadow: 0 0 14px rgba(0,255,136,0.3);
            background: rgba(0,255,136,0.07);
        }

        .color-row { display: flex; gap: 10px; }
        .color-opt {
            width: 28px; height: 28px; border-radius: 50%;
            cursor: pointer; border: 2px solid transparent; transition: 0.2s;
        }
        .color-opt.selected, .color-opt:hover {
            border-color: white; transform: scale(1.2);
            box-shadow: 0 0 10px currentColor;
        }

        .btn-neon {
            background: transparent;
            border: 2px solid var(--green); color: var(--green);
            padding: 13px 48px; font-size: 13px;
            font-family: 'Orbitron', monospace; font-weight: 700; letter-spacing: 4px;
            cursor: pointer; border-radius: 60px; transition: 0.25s;
            position: relative; overflow: hidden;
        }
        .btn-neon::after {
            content: ''; position: absolute; inset: 0;
            background: var(--green); opacity: 0;
            transition: opacity 0.3s; pointer-events: none;
        }
        .btn-neon:hover::after { opacity: 0.12; }
        .btn-neon:hover {
            box-shadow: 0 0 28px rgba(0,255,136,0.5), inset 0 0 20px rgba(0,255,136,0.05);
        }
        .btn-neon:active { transform: scale(0.96); }

        #lb-panel {
            position: fixed; bottom: 20px; left: 20px; z-index: 20;
            background: var(--card); backdrop-filter: blur(12px);
            border-radius: 18px; padding: 14px 18px; min-width: 190px;
            border: 1px solid var(--border); pointer-events: none;
        }
        #lb-panel h4 {
            color: var(--green); font-size: 9px; letter-spacing: 3px;
            margin-bottom: 10px; text-transform: uppercase;
        }
        .lbe {
            display: flex; justify-content: space-between;
            font-size: 11px; color: #2a5a3a; padding: 5px 0;
            border-bottom: 1px solid rgba(0,255,136,0.06);
        }
        .lbe:last-child { border-bottom: none; }
        .lbr { color: #0a2a1a; margin-right: 6px; font-size: 10px; }
        .lbsc { color: var(--green); font-weight: 700; font-family: 'Orbitron', monospace; font-size: 12px; }

        .game-controls {
            position: fixed; bottom: 20px; right: 20px; z-index: 20;
            display: flex; gap: 8px;
            background: var(--card); backdrop-filter: blur(12px);
            padding: 8px 14px; border-radius: 40px;
            border: 1px solid var(--border);
        }
        .ctrl-btn {
            background: transparent; border: 1px solid rgba(0,255,136,0.3);
            color: #3a7a5a; padding: 7px 16px; border-radius: 30px;
            cursor: pointer; font-size: 11px; font-weight: 700;
            letter-spacing: 1px; transition: 0.2s;
            font-family: 'Rajdhani', sans-serif;
        }
        .ctrl-btn:hover { color: var(--green); border-color: var(--green); }

        #keys-hint {
            position: fixed; bottom: 28px; left: 50%;
            transform: translateX(-50%);
            color: #0a2a1a; font-size: 10px; letter-spacing: 2px;
            z-index: 10; pointer-events: none; white-space: nowrap;
        }

        #toast {
            position: fixed; top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            font-family: 'Orbitron', monospace; font-size: 22px; font-weight: 900;
            color: var(--green); text-shadow: 0 0 20px var(--green);
            pointer-events: none; z-index: 30;
            opacity: 0; transition: opacity 0.2s;
            letter-spacing: 4px;
        }
        #toast.show { opacity: 1; animation: toastAnim 0.7s forwards; }
        @keyframes toastAnim {
            0% { opacity: 1; transform: translate(-50%, -50%) scale(1.2); }
            70% { opacity: 1; transform: translate(-50%, -70%) scale(1); }
            100% { opacity: 0; transform: translate(-50%, -90%) scale(0.9); }
        }

        #countdown {
            position: fixed; top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            font-family: 'Orbitron', monospace;
            font-size: 100px; font-weight: 900;
            color: var(--green);
            text-shadow: 0 0 30px var(--green);
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
        <div class="hud-score-val" id="hud-score">0</div>
        <div style="margin-top:8px">
            <div class="hud-label">BEST</div>
            <div class="hud-best-val" id="hud-best"><?= number_format((int)$myBest) ?></div>
        </div>
    </div>
    <div class="hud-panel">
        <div class="hud-stats">
            <div>
                <div class="hud-label">LENGTH</div>
                <div class="hud-stat-val" id="hud-len">3</div>
            </div>
            <div>
                <div class="hud-label">LEVEL</div>
                <div class="hud-stat-val" id="hud-lvl">1</div>
            </div>
        </div>
    </div>
</div>

<div id="overlay">
    <div class="overlay-eyebrow">⚡ RETROGAMES ⚡</div>
    <div class="overlay-title" id="overlayTitle">SNAKE</div>
    <div id="ov-msg">Makan apel, hindari dinding dan ekormu sendiri!</div>

    <div class="diff-row" id="diffSelectors">
        <div class="diff-opt" onclick="selectDiff(this,'easy')">🐢 EASY</div>
        <div class="diff-opt active" onclick="selectDiff(this,'normal')">⚡ NORMAL</div>
        <div class="diff-opt" onclick="selectDiff(this,'hard')">🔥 HARD</div>
    </div>

    <div class="color-row" id="colorSelectors">
        <div class="color-opt selected" style="background:#00ff88" data-color="#00ff88" onclick="selectColor(this)"></div>
        <div class="color-opt" style="background:#3b82f6" data-color="#3b82f6" onclick="selectColor(this)"></div>
        <div class="color-opt" style="background:#f59e0b" data-color="#f59e0b" onclick="selectColor(this)"></div>
        <div class="color-opt" style="background:#ef4444" data-color="#ef4444" onclick="selectColor(this)"></div>
        <div class="color-opt" style="background:#a855f7" data-color="#a855f7" onclick="selectColor(this)"></div>
        <div class="color-opt" style="background:#aaff00" data-color="#aaff00" onclick="selectColor(this)"></div>
    </div>

    <div style="display:flex; gap:12px; flex-wrap:wrap; justify-content:center;">
        <a href="../index.php" class="btn-neon" style="text-decoration:none; display:inline-flex; align-items:center; gap:8px;">← BACK</a>
        <button class="btn-neon" id="startBtn">▶ START GAME</button>
        <button class="btn-neon" id="resumeBtn" style="display:none">RESUME</button>
    </div>
</div>

<div id="lb-panel">
    <h4>🏆 Top Scores</h4>
    <?php if(empty($topScores)): ?>
        <div class="lbe"><span style="color:#1a3a2a">No scores yet</span></div>
    <?php else: ?>
        <?php foreach($topScores as $i => $s): ?>
        <div class="lbe">
            <span><span class="lbr">#<?= $i+1 ?></span><?= sanitize($s['username']) ?></span>
            <span class="lbsc"><?= number_format($s['best_score']) ?></span>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<div id="keys-hint">W A S D / ↑ ↓ ← → &nbsp;•&nbsp; P = PAUSE &nbsp;•&nbsp; R = RESTART</div>
<div id="toast"></div>
<div id="countdown"></div>

<div class="game-controls">
    <button class="ctrl-btn" onclick="restartGame()">↺ NEW</button>
    <button class="ctrl-btn" id="pauseBtn" onclick="togglePause()">⏸ PAUSE</button>
</div>

<script>
const LOGGED_IN = <?= isLoggedIn() ? 'true' : 'false' ?>;
const SAVE_URL = '../api/save_score.php';
let myBestScore = <?= (int)$myBest ?>;

const canvas = document.getElementById('gameCanvas');
const ctx = canvas.getContext('2d');
let W, H, CELL;
const COLS = 24, ROWS = 14;

function resizeCanvas() {
    W = canvas.width = window.innerWidth;
    H = canvas.height = window.innerHeight;
    CELL = Math.floor(Math.min(W / COLS, (H * 0.78) / ROWS));
}
window.addEventListener('resize', resizeCanvas);

let audioCtx = null;
const sndCooldown = {};
function initAudio() { if (audioCtx) return; audioCtx = new (window.AudioContext || window.webkitAudioContext)(); }
function playTone(freq, dur, vol, type='sine') {
    const now = performance.now();
    if (sndCooldown[freq] && now - sndCooldown[freq] < 80) return;
    sndCooldown[freq] = now;
    if (!audioCtx) initAudio();
    if (audioCtx.state === 'suspended') audioCtx.resume();
    try {
        const o = audioCtx.createOscillator();
        const g = audioCtx.createGain();
        o.connect(g); g.connect(audioCtx.destination);
        o.type = type; o.frequency.value = freq;
        g.gain.value = vol;
        g.gain.exponentialRampToValueAtTime(0.0001, audioCtx.currentTime + dur);
        o.start(); o.stop(audioCtx.currentTime + dur);
    } catch(e) {}
}
function playEat() { playTone(1047, 0.1, 0.1); setTimeout(() => playTone(1319, 0.08, 0.08), 60); }
function playDie() { playTone(220, 0.4, 0.15, 'sawtooth'); }
function playWin() { [523, 659, 784].forEach((f, i) => setTimeout(() => playTone(f, 0.2, 0.12), i * 120)); }

let snake, direction, nextDir, food, bonusFood = null;
let score, gameActive, paused, gameInterval, animFrame;
let isCountingDown = false;
let difficulty = 'normal', snakeColor = '#00ff88';
let foodPulse = 0, bonusPulse = 0;
let particles = [];
let shakeX = 0, shakeY = 0, shakePower = 0;
let frameCount = 0;
let level = 1, eatCount = 0;
const speedMap = { easy: 160, normal: 115, hard: 72 };
let startTime;
let snakePrev = [];
let interpT = 0, stepDuration = 115;
let lastStepTime = 0;

function burst(x, y, color, n=16) {
    for (let i = 0; i < n; i++) {
        const a = Math.random() * Math.PI * 2;
        const sp = 1.5 + Math.random() * 4;
        particles.push({ x, y, vx: Math.cos(a) * sp, vy: Math.sin(a) * sp, life: 1, decay: 0.022 + Math.random() * 0.01, color, size: 2 + Math.random() * 4 });
    }
}
function updateParticles() {
    for (let i = particles.length - 1; i >= 0; i--) {
        const p = particles[i];
        p.x += p.vx; p.y += p.vy; p.vy += 0.1;
        p.life -= p.decay;
        if (p.life <= 0) particles.splice(i, 1);
    }
    if (shakePower > 0) {
        shakeX = (Math.random() - 0.5) * shakePower * 2;
        shakeY = (Math.random() - 0.5) * shakePower * 2;
        shakePower = Math.max(0, shakePower - 0.7);
    } else { shakeX = shakeY = 0; }
}

let toastTimer = null;
function showToast(msg) {
    const el = document.getElementById('toast');
    el.textContent = msg; el.classList.remove('show');
    void el.offsetWidth; el.classList.add('show');
    if (toastTimer) clearTimeout(toastTimer);
    toastTimer = setTimeout(() => el.classList.remove('show'), 800);
}

function selectDiff(el, d) {
    document.querySelectorAll('.diff-opt').forEach(e => e.classList.remove('active'));
    el.classList.add('active'); difficulty = d; stepDuration = speedMap[d];
}
function selectColor(el) {
    document.querySelectorAll('.color-opt').forEach(e => e.classList.remove('selected'));
    el.classList.add('selected'); snakeColor = el.dataset.color;
}
function hexToRgb(hex) {
    const r = parseInt(hex.slice(1,3),16), g = parseInt(hex.slice(3,5),16), b = parseInt(hex.slice(5,7),16);
    return {r,g,b};
}
function getEmptyCells() {
    const set = new Set(snake.map(s => s.x + ',' + s.y));
    const empty = [];
    for (let x = 0; x < COLS; x++) for (let y = 0; y < ROWS; y++) if (!set.has(x+','+y)) empty.push({x, y});
    return empty;
}
function placeFood() {
    const empty = getEmptyCells();
    if (empty.length === 0) { endGame(true); return false; }
    food = empty[Math.floor(Math.random() * empty.length)];
    return true;
}
function placeBonusFood() {
    const empty = getEmptyCells().filter(c => !(c.x === food.x && c.y === food.y));
    if (empty.length === 0) return;
    bonusFood = { ...empty[Math.floor(Math.random() * empty.length)], timer: 120 };
}
function updateHUD() {
    const el = document.getElementById('hud-score');
    const prev = el.textContent;
    el.textContent = score;
    if (String(score) !== prev) { el.classList.remove('pop'); void el.offsetWidth; el.classList.add('pop'); }
    document.getElementById('hud-len').textContent = snake.length;
    document.getElementById('hud-best').textContent = myBestScore;
    document.getElementById('hud-lvl').textContent = level;
}

function runCountdown(callback) {
    const el = document.getElementById('countdown');
    el.style.display = 'block';
    isCountingDown = true;
    let count = 3;
    el.textContent = count;
    playTone(600, 0.1, 0.1);
    const interval = setInterval(() => {
        count--;
        if (count > 0) { el.textContent = count; playTone(600, 0.1, 0.1); }
        else if (count === 0) { el.textContent = 'GO!'; playTone(900, 0.1, 0.1); }
        else { clearInterval(interval); el.style.display = 'none'; isCountingDown = false; callback(); }
    }, 800);
}

function initGame() {
    if (gameInterval) clearInterval(gameInterval);
    if (animFrame) cancelAnimationFrame(animFrame);
    particles = []; shakePower = 0; frameCount = 0;
    level = 1; eatCount = 0; bonusFood = null;
    stepDuration = speedMap[difficulty];
    const mx = Math.floor(COLS / 2), my = Math.floor(ROWS / 2);
    snake = [{x: mx, y: my}, {x: mx-1, y: my}, {x: mx-2, y: my}];
    snakePrev = snake.map(s => ({...s}));
    direction = {x:1, y:0}; nextDir = {x:1, y:0};
    score = 0; gameActive = true; paused = false;
    interpT = 0; lastStepTime = performance.now();
    startTime = Date.now();
    if (!placeFood()) return;
    updateHUD();
    document.getElementById('pauseBtn').textContent = '⏸ PAUSE';
    document.getElementById('overlay').style.display = 'none';
    runCountdown(() => {
        lastStepTime = performance.now();
        gameInterval = setInterval(gameStep, stepDuration);
        animFrame = requestAnimationFrame(drawLoop);
    });
    initAudio();
}

function gameStep() {
    if (!gameActive || paused || isCountingDown) return;
    snakePrev = snake.map(s => ({...s}));
    interpT = 0; lastStepTime = performance.now();
    direction = {...nextDir};
    const head = {x: snake[0].x + direction.x, y: snake[0].y + direction.y};
    if (head.x < 0 || head.x >= COLS || head.y < 0 || head.y >= ROWS) { endGame(false); return; }
    if (snake.some(s => s.x === head.x && s.y === head.y)) { endGame(false); return; }
    snake.unshift(head);
    let ate = false;
    if (head.x === food.x && head.y === food.y) {
        score += level; eatCount++; ate = true;
        const fx = food.x * CELL + CELL/2 + (W - COLS*CELL)/2;
        const fy = food.y * CELL + CELL/2 + (H - ROWS*CELL)/2;
        burst(fx, fy, '#ff6644', 18); burst(fx, fy, '#ffcc44', 12);
        playEat();
        if (eatCount % 5 === 0) {
            level++; clearInterval(gameInterval);
            stepDuration = Math.max(50, stepDuration - 8);
            gameInterval = setInterval(gameStep, stepDuration);
            showToast('LEVEL ' + level + '!');
        }
        if (eatCount % 3 === 0) placeBonusFood();
        shakePower = 5; placeFood(); updateHUD();
    } else if (bonusFood && head.x === bonusFood.x && head.y === bonusFood.y) {
        score += level * 3; ate = true;
        const bx = bonusFood.x * CELL + CELL/2 + (W - COLS*CELL)/2;
        const by = bonusFood.y * CELL + CELL/2 + (H - ROWS*CELL)/2;
        burst(bx, by, '#aaff00', 25); playEat(); showToast('BONUS x3!');
        shakePower = 6; bonusFood = null; updateHUD();
    } else { snake.pop(); }
    if (!ate) updateHUD();
}

function endGame(isWin) {
    if (!gameActive) return;
    gameActive = false; clearInterval(gameInterval);
    isWin ? playWin() : playDie();
    const ox = (W - COLS*CELL)/2, oy = (H - ROWS*CELL)/2;
    snake.forEach((seg, i) => { setTimeout(() => { burst(seg.x*CELL+CELL/2+ox, seg.y*CELL+CELL/2+oy, snakeColor, 8); }, i * 15); });
    shakePower = 14;
    const duration = Math.floor((Date.now() - startTime) / 1000);
    const mm = Math.floor(duration/60), ss = (duration%60).toString().padStart(2,'0');
    document.getElementById('ov-msg').innerHTML = isWin
        ? `🎉 <span>SEMPURNA!</span><br>Score: <span>${score}</span> &nbsp;•&nbsp; Time: ${mm}:${ss}`
        : `<span>GAME OVER</span><br>Score: <span>${score}</span> &nbsp;•&nbsp; Length: <span>${snake.length}</span><br>Time: ${mm}:${ss}`;
    document.getElementById('startBtn').textContent = '▶ PLAY AGAIN';
    document.getElementById('overlayTitle').textContent = 'SNAKE';
    document.getElementById('diffSelectors').style.display = 'flex';
    document.getElementById('colorSelectors').style.display = 'flex';
    document.getElementById('startBtn').style.display = 'block';
    document.getElementById('resumeBtn').style.display = 'none';
    setTimeout(() => document.getElementById('overlay').style.display = 'flex', 600);
    if (LOGGED_IN && score > 0) {
        fetch(SAVE_URL, { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({game: 'snake', score, level: 1, duration}) })
        .then(r => r.json()).then(d => { if (d.success && d.best_score > myBestScore) { myBestScore = d.best_score; document.getElementById('hud-best').textContent = myBestScore; } });
    }
}

function gridOffset() { return { ox: Math.floor((W - COLS*CELL)/2), oy: Math.floor((H - ROWS*CELL)/2) }; }
function drawBg() {
    ctx.clearRect(0, 0, W, H);
    const bg = ctx.createRadialGradient(W/2, H/2, 0, W/2, H/2, Math.max(W,H)*0.7);
    bg.addColorStop(0, '#060e0a'); bg.addColorStop(1, '#02050a'); ctx.fillStyle = bg; ctx.fillRect(0, 0, W, H);
}
function drawGrid(ox, oy) {
    const boardW = COLS * CELL, boardH = ROWS * CELL;
    ctx.fillStyle = 'rgba(0,30,15,0.9)'; ctx.beginPath(); ctx.roundRect(ox, oy, boardW, boardH, 16); ctx.fill();
    ctx.strokeStyle = 'rgba(0,255,136,0.04)'; ctx.lineWidth = 1;
    for (let x = 0; x <= COLS; x++) { ctx.beginPath(); ctx.moveTo(ox + x*CELL, oy); ctx.lineTo(ox + x*CELL, oy+boardH); ctx.stroke(); }
    for (let y = 0; y <= ROWS; y++) { ctx.beginPath(); ctx.moveTo(ox, oy + y*CELL); ctx.lineTo(ox+boardW, oy + y*CELL); ctx.stroke(); }
    ctx.fillStyle = 'rgba(0,255,136,0.08)';
    for (let x = 0; x <= COLS; x++) for (let y = 0; y <= ROWS; y++) { ctx.beginPath(); ctx.arc(ox + x*CELL, oy + y*CELL, 1.2, 0, Math.PI*2); ctx.fill(); }
    ctx.save(); ctx.shadowBlur = 20; ctx.shadowColor = 'rgba(0,255,136,0.3)'; ctx.strokeStyle = 'rgba(0,255,136,0.35)'; ctx.lineWidth = 2;
    ctx.beginPath(); ctx.roundRect(ox, oy, boardW, boardH, 16); ctx.stroke(); ctx.restore();
}
function drawFood(ox, oy) {
    if (!food) return; foodPulse += 0.08;
    const fx = ox + food.x * CELL + CELL/2, fy = oy + food.y * CELL + CELL/2, r = CELL * 0.32 + Math.sin(foodPulse) * 2.5;
    ctx.save(); ctx.shadowBlur = 20 + Math.sin(foodPulse) * 5; ctx.shadowColor = '#ff4444';
    const fg = ctx.createRadialGradient(fx - r*0.3, fy - r*0.3, 0, fx, fy, r);
    fg.addColorStop(0, '#ff8888'); fg.addColorStop(0.5, '#ff3333'); fg.addColorStop(1, '#cc0000'); ctx.fillStyle = fg;
    ctx.beginPath(); ctx.arc(fx, fy, r, 0, Math.PI*2); ctx.fill();
    ctx.fillStyle = 'rgba(255,255,255,0.5)'; ctx.beginPath(); ctx.ellipse(fx - r*0.28, fy - r*0.3, r*0.22, r*0.14, -0.5, 0, Math.PI*2); ctx.fill();
    ctx.strokeStyle = '#2a7a2a'; ctx.lineWidth = 1.5; ctx.beginPath(); ctx.moveTo(fx, fy - r); ctx.quadraticCurveTo(fx+3, fy-r-5, fx+5, fy-r-3); ctx.stroke();
    ctx.restore();
}
function drawBonusFood(ox, oy) {
    if (!bonusFood) return; bonusPulse += 0.12; bonusFood.timer--;
    if (bonusFood.timer <= 0) { bonusFood = null; return; }
    const bx = ox + bonusFood.x * CELL + CELL/2, by = oy + bonusFood.y * CELL + CELL/2, r = CELL * 0.3 + Math.sin(bonusPulse) * 3, alpha = bonusFood.timer < 30 ? bonusFood.timer / 30 : 1;
    ctx.save(); ctx.globalAlpha = alpha; ctx.shadowBlur = 18; ctx.shadowColor = '#aaff00'; ctx.fillStyle = '#aaff00'; ctx.beginPath();
    for (let i = 0; i < 5; i++) {
        const a = (i * 4 * Math.PI / 5) - Math.PI/2 + bonusPulse * 0.3, ai = ((i * 4 + 2) * Math.PI / 5) - Math.PI/2 + bonusPulse * 0.3;
        if (i === 0) ctx.moveTo(bx + Math.cos(a)*r, by + Math.sin(a)*r); else ctx.lineTo(bx + Math.cos(a)*r, by + Math.sin(a)*r);
        ctx.lineTo(bx + Math.cos(ai)*r*0.45, by + Math.sin(ai)*r*0.45);
    }
    ctx.closePath(); ctx.fill(); ctx.restore();
}
function drawSnake(ox, oy, t) {
    const rgb = hexToRgb(snakeColor), len = snake.length;
    for (let i = len - 1; i >= 0; i--) {
        const cur = snake[i], prev = snakePrev[i] || cur, ix = prev.x + (cur.x - prev.x) * t, iy = prev.y + (cur.y - prev.y) * t, px = ox + ix * CELL, py = oy + iy * CELL;
        const isHead = i === 0, progress = 1 - (i / len), brightness = 0.25 + progress * 0.75, pad = isHead ? 1 : Math.max(2, 3 - progress * 1.5), s = CELL - pad * 2, radius = isHead ? 8 : 4;
        ctx.save();
        if (isHead) { ctx.shadowBlur = 16; ctx.shadowColor = snakeColor; } else { ctx.shadowBlur = 4 * progress; ctx.shadowColor = snakeColor; }
        const segColor = `rgba(${Math.round(rgb.r * brightness)},${Math.round(rgb.g * brightness)},${Math.round(rgb.b * brightness)},1)`;
        ctx.fillStyle = isHead ? snakeColor : segColor; ctx.beginPath(); ctx.roundRect(px + pad, py + pad, s, s, radius); ctx.fill();
        if (!isHead && i % 2 === 0 && s > 8) { ctx.fillStyle = `rgba(${Math.round(rgb.r * brightness * 1.3)},${Math.round(rgb.g * brightness * 1.3)},${Math.round(rgb.b * brightness * 1.3)},0.4)`; ctx.beginPath(); ctx.roundRect(px + pad + s*0.2, py + pad + s*0.2, s*0.6, s*0.6, 3); ctx.fill(); }
        if (isHead) {
            const dir = direction, eyeR = CELL * 0.13, eyeOff = CELL * 0.22; let e1x, e1y, e2x, e2y; const hcx = px + CELL/2, hcy = py + CELL/2;
            if (dir.x === 1) { e1x = hcx+eyeOff; e1y = hcy-eyeOff; e2x = hcx+eyeOff; e2y = hcy+eyeOff; }
            else if (dir.x === -1) { e1x = hcx-eyeOff; e1y = hcy-eyeOff; e2x = hcx-eyeOff; e2y = hcy+eyeOff; }
            else if (dir.y === -1) { e1x = hcx-eyeOff; e1y = hcy-eyeOff; e2x = hcx+eyeOff; e2y = hcy-eyeOff; }
            else { e1x = hcx-eyeOff; e1y = hcy+eyeOff; e2x = hcx+eyeOff; e2y = hcy+eyeOff; }
            ctx.shadowBlur = 0; ctx.fillStyle = '#fff'; ctx.beginPath(); ctx.arc(e1x, e1y, eyeR, 0, Math.PI*2); ctx.fill(); ctx.beginPath(); ctx.arc(e2x, e2y, eyeR, 0, Math.PI*2); ctx.fill();
            ctx.fillStyle = '#001a08'; ctx.beginPath(); ctx.arc(e1x + dir.x*eyeR*0.4, e1y + dir.y*eyeR*0.4, eyeR*0.55, 0, Math.PI*2); ctx.fill(); ctx.beginPath(); ctx.arc(e2x + dir.x*eyeR*0.4, e2y + dir.y*eyeR*0.4, eyeR*0.55, 0, Math.PI*2); ctx.fill();
        }
        ctx.restore();
    }
}
function drawParticlesAll() {
    for (const p of particles) { ctx.globalAlpha = Math.max(0, p.life); ctx.fillStyle = p.color; ctx.beginPath(); ctx.arc(p.x, p.y, p.size * p.life, 0, Math.PI*2); ctx.fill(); }
    ctx.globalAlpha = 1;
}

function draw(now) {
    frameCount++; interpT = Math.min(1, (now - lastStepTime) / stepDuration);
    ctx.save(); if (shakePower > 0.5) ctx.translate(shakeX, shakeY);
    drawBg(); const {ox, oy} = gridOffset(); drawGrid(ox, oy); drawFood(ox, oy); drawBonusFood(ox, oy);
    if (snake && snake.length) drawSnake(ox, oy, interpT); drawParticlesAll(); updateParticles();
    ctx.restore();
    if (paused && gameActive) { ctx.fillStyle = 'rgba(0,0,0,0.75)'; ctx.fillRect(0, 0, W, H); }
}
function drawLoop(now) { draw(now); animFrame = requestAnimationFrame(drawLoop); }

function startGame() { document.getElementById('overlay').style.display = 'none'; initGame(); }
function restartGame() { document.getElementById('overlay').style.display = 'none'; initGame(); }
function togglePause() {
    if (!gameActive || isCountingDown) return;
    paused = !paused;
    const overlay = document.getElementById('overlay'), title = document.getElementById('overlayTitle'), diffs = document.getElementById('diffSelectors'), colors = document.getElementById('colorSelectors'), startBtn = document.getElementById('startBtn'), resumeBtn = document.getElementById('resumeBtn');
    if (paused) {
        title.textContent = 'PAUSED'; diffs.style.display = 'none'; colors.style.display = 'none'; startBtn.style.display = 'none'; resumeBtn.style.display = 'block'; overlay.style.display = 'flex'; clearInterval(gameInterval);
    } else {
        overlay.style.display = 'none'; runCountdown(() => { lastStepTime = performance.now(); gameInterval = setInterval(gameStep, stepDuration); });
    }
    document.getElementById('pauseBtn').textContent = paused ? '▶ RESUME' : '⏸ PAUSE';
}

document.addEventListener('keydown', (e) => {
    if (e.key === 'r' || e.key === 'R') { restartGame(); e.preventDefault(); return; }
    if (e.key === 'p' || e.key === 'P') { togglePause(); e.preventDefault(); return; }
    if (!gameActive || paused) return;
    const map = { 'ArrowUp':{x:0,y:-1}, 'w':{x:0,y:-1}, 'W':{x:0,y:-1}, 'ArrowDown':{x:0,y:1}, 's':{x:0,y:1}, 'S':{x:0,y:1}, 'ArrowLeft':{x:-1,y:0}, 'a':{x:-1,y:0}, 'A':{x:-1,y:0}, 'ArrowRight':{x:1,y:0}, 'd':{x:1,y:0}, 'D':{x:1,y:0}, };
    const nd = map[e.key]; if (nd && !(nd.x === -direction.x && nd.y === -direction.y)) { nextDir = nd; e.preventDefault(); }
});

document.getElementById('startBtn').onclick = startGame;
document.getElementById('resumeBtn').onclick = togglePause;

if (!CanvasRenderingContext2D.prototype.roundRect) {
    CanvasRenderingContext2D.prototype.roundRect = function(x,y,w,h,r) {
        if (w < 2*r) r = w/2; if (h < 2*r) r = h/2;
        this.beginPath(); this.moveTo(x+r,y); this.lineTo(x+w-r,y); this.quadraticCurveTo(x+w,y,x+w,y+r); this.lineTo(x+w,y+h-r); this.quadraticCurveTo(x+w,y+h,x+w-r,y+h); this.lineTo(x+r,y+h); this.quadraticCurveTo(x,y+h,x,y+h-r); this.lineTo(x,y+r); this.quadraticCurveTo(x,y,x+r,y); this.closePath(); return this;
    };
}

(function() {
    const keep = new Set();
    ['gameCanvas','hud','nav','overlay','lb-panel','keys-hint','toast','countdown'].forEach(id => {
        let node = document.getElementById(id); while (node && node !== document.body) { keep.add(node); node = node.parentElement; }
    });
    document.querySelectorAll('.game-controls').forEach(el => {
        let node = el; while (node && node !== document.body) { keep.add(node); node = node.parentElement; }
    });
    Array.from(document.body.children).forEach(child => { if (!keep.has(child) && !['SCRIPT','STYLE'].includes(child.tagName)) { child.style.setProperty('display', 'none', 'important'); } });
    document.documentElement.style.setProperty('background', '#04080f', 'important');
    document.body.style.setProperty('background', '#04080f', 'important');
    document.body.style.setProperty('overflow', 'hidden', 'important');
})();

resizeCanvas(); animFrame = requestAnimationFrame(drawLoop); document.getElementById('overlay').style.display = 'flex';
</script>
</div></div></section>
</body>
</html>