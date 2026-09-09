<?php
require_once '../includes/auth.php';
$myBest = 0;
if (isLoggedIn()) {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT MAX(s.score) AS best FROM scores s JOIN games g ON s.game_id = g.id WHERE s.user_id = ? AND g.slug = 'pacman' AND s.deleted_at IS NULL");
        $stmt->execute([$_SESSION['user_id']]);
        $row = $stmt->fetch();
        $myBest = $row['best'] ?? 0;
    } catch (Exception $e) { $myBest = 0; }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
<title>PAC-MAN</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Press+Start+2P&family=Orbitron:wght@400;700;900&display=swap');
  *{margin:0;padding:0;box-sizing:border-box;user-select:none}
  html,body{width:100%;height:100%;overflow:hidden;background:#000}
  body{display:flex;align-items:center;justify-content:center;font-family:'Orbitron',monospace;background:radial-gradient(ellipse at center,#0a0a1a 0%,#000 100%)}

  #stars{position:fixed;inset:0;pointer-events:none;z-index:0}
  .star{position:absolute;border-radius:50%;background:#fff;animation:twinkle linear infinite}
  @keyframes twinkle{0%,100%{opacity:.1}50%{opacity:.8}}

  #game-root{position:relative;z-index:1;display:flex;flex-direction:column;align-items:center;justify-content:center;width:100vw;height:100vh}

  #hud-top{display:flex;justify-content:space-between;align-items:center;width:100%;max-width:460px;padding:0 8px 10px;position:relative;z-index:10}
  .hud-block{display:flex;flex-direction:column;align-items:center;gap:2px}
  .hud-label{font-size:8px;letter-spacing:3px;color:#3a2800;text-transform:uppercase}
  .hud-val{font-family:'Press Start 2P',monospace;font-size:16px;color:#fbbf24;text-shadow:0 0 12px #fbbf2488;line-height:1}
  .hud-val-sm{font-family:'Press Start 2P',monospace;font-size:10px;color:#fbbf24}
  .lives-wrap{display:flex;gap:4px;align-items:center;margin-top:2px}
  .life-dot{width:12px;height:12px;background:#fbbf24;border-radius:50% 50% 50% 0;clip-path:polygon(50% 0%,100% 38%,100% 100%,0% 100%,0% 38%);transform:rotate(45deg);box-shadow:0 0 8px #fbbf24}
  .life-dot.lost{background:#1a1100;box-shadow:none}

  #canvas-wrap{position:relative;line-height:0;border-radius:6px;box-shadow:0 0 60px rgba(56,100,255,.25),0 0 120px rgba(56,100,255,.1),inset 0 0 30px rgba(0,0,0,.8)}
  canvas{display:block;border-radius:4px;image-rendering:pixelated}

  #hud-bot{display:flex;justify-content:space-between;align-items:center;width:100%;max-width:460px;padding:10px 8px 0}
  .hint-text{font-size:7px;letter-spacing:2px;color:#2a1a00;text-transform:uppercase}
  #level-badge{background:linear-gradient(135deg,#fbbf24,#f59e0b);color:#000;font-family:'Press Start 2P',monospace;font-size:9px;padding:5px 14px;border-radius:20px;letter-spacing:1px}

  #overlay{position:fixed;inset:0;z-index:100;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:20px;background:rgba(0,0,0,.92);backdrop-filter:blur(20px);transition:opacity .4s}
  #overlay.hidden{opacity:0;pointer-events:none}
  .arcade-title{font-family:'Press Start 2P',monospace;font-size:clamp(20px,5vw,42px);background:linear-gradient(180deg,#fff 0%,#fbbf24 40%,#f59e0b 100%);-webkit-background-clip:text;background-clip:text;color:transparent;letter-spacing:4px;text-align:center;line-height:1.3;animation:titlePulse 2s ease-in-out infinite}
  @keyframes titlePulse{0%,100%{filter:drop-shadow(0 0 20px #fbbf2466)}50%{filter:drop-shadow(0 0 40px #fbbf24aa)}}
  .sub-label{font-size:9px;letter-spacing:6px;color:#4a3a00;text-transform:uppercase;margin-top:-8px}
  .ov-desc{color:#4a3a00;font-size:10px;text-align:center;max-width:320px;line-height:2;letter-spacing:1px}
  .ov-desc span{color:#fbbf24}
  #startBtn{font-family:'Press Start 2P',monospace;font-size:11px;letter-spacing:3px;background:transparent;border:2px solid #fbbf24;color:#fbbf24;padding:14px 40px;border-radius:40px;cursor:pointer;transition:all .2s;position:relative;overflow:hidden}
  #startBtn::before{content:'';position:absolute;inset:0;background:#fbbf24;transform:scaleX(0);transform-origin:left;transition:transform .25s;z-index:-1}
  #startBtn:hover{color:#000;box-shadow:0 0 30px #fbbf24}
  #startBtn:hover::before{transform:scaleX(1)}
  #ov-score-reveal{font-family:'Press Start 2P',monospace;font-size:14px;color:#fbbf24;text-align:center;line-height:2}

  #power-flash{position:fixed;inset:0;pointer-events:none;z-index:5;background:rgba(251,191,36,.04);opacity:0;transition:opacity .1s}
  #power-flash.active{opacity:1;animation:flashPulse 0.6s ease-in-out infinite}
  @keyframes flashPulse{0%,100%{opacity:.04}50%{opacity:.12}}

  .score-pop{position:fixed;font-family:'Press Start 2P',monospace;font-size:13px;color:#fbbf24;pointer-events:none;z-index:50;animation:popUp .8s ease forwards;text-shadow:0 0 10px #fbbf24}
  @keyframes popUp{0%{opacity:1;transform:translateY(0)}100%{opacity:0;transform:translateY(-50px)}}

  /* Countdown */
  #countdown {
    position: fixed; top: 50%; left: 50%;
    transform: translate(-50%, -50%);
    font-family: 'Press Start 2P', monospace;
    font-size: 64px; color: #fbbf24;
    text-shadow: 0 0 20px #fbbf24;
    z-index: 200;
    pointer-events: none;
    display: none;
  }
</style>
</head>
<body>
<div id="stars"></div>
<div id="power-flash"></div>

<div id="overlay">
  <div class="sub-label">⚡ retrogames ⚡</div>
  <div class="arcade-title" id="overlayTitle">PAC-MAN</div>
  <div class="ov-desc" id="overlayDesc">
    Makan semua dot, hindari hantu!<br>
    <span>💊 Power pellet</span> = hantu bisa dimakan 7 detik
  </div>
  <div id="ov-score-reveal" style="display:none"></div>
  <div style="display:flex; gap:12px; flex-wrap:wrap; justify-content:center;">
    <a href="../index.php" id="back-link" style="font-family:'Press Start 2P',monospace; font-size:11px; letter-spacing:3px; background:transparent; border:2px solid #fbbf24; color:#fbbf24; padding:14px 40px; border-radius:40px; cursor:pointer; text-decoration:none; display:inline-flex; align-items:center;">← BACK</a>
    <button id="startBtn">▶ START</button>
    <button id="resumeBtn" style="display:none; font-family:'Press Start 2P',monospace; font-size:11px; letter-spacing:3px; background:transparent; border:2px solid #fbbf24; color:#fbbf24; padding:14px 40px; border-radius:40px; cursor:pointer;">RESUME</button>
</div>
  <div style="font-size:8px;letter-spacing:2px;color:#2a1a00;text-align:center">W A S D / ARROW KEYS &nbsp;·&nbsp; P = PAUSE</div>
</div>

<div id="game-root">
  <div id="hud-top">
    <div class="hud-block">
      <div class="hud-label">Score</div>
      <div class="hud-val" id="hud-score">0</div>
    </div>
    <div class="hud-block">
      <div class="hud-label">Best</div>
      <div class="hud-val-sm" id="hud-best"><?= number_format((int)$myBest) ?></div>
    </div>
    <div class="hud-block">
      <div class="hud-label">Lives</div>
      <div class="lives-wrap" id="hud-lives"></div>
    </div>
  </div>
  <div id="canvas-wrap">
    <canvas id="gc"></canvas>
  </div>
  <div id="hud-bot">
    <div class="hint-text">W A S D / Arrows &nbsp;·&nbsp; P pause</div>
    <div id="level-badge">LV 1</div>
  </div>
</div>
<div id="countdown"></div>

<script>
const LOGGED_IN = <?= isLoggedIn() ? 'true' : 'false' ?>;
const SAVE_URL = '../api/save_score.php';
let myBestScore = <?= (int)$myBest ?>;

async function saveScore(s) {
  if (!LOGGED_IN || s <= 0) return;
  const duration = Math.floor((Date.now() - startTime) / 1000);
  try {
    const r = await fetch(SAVE_URL, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ game: 'pacman', score: s, level: level, duration: duration }),
      keepalive: true
    });
    const d = await r.json();
    if (d.success && d.best_score > myBestScore) {
      myBestScore = d.best_score;
      document.getElementById('hud-best').textContent = myBestScore.toLocaleString();
    }
  } catch (e) {}
}
// ── Stars background ──────────────────────────────────────────────
(function(){
  const s=document.getElementById('stars');
  for(let i=0;i<80;i++){
    const d=document.createElement('div');d.className='star';
    const sz=Math.random()*2+1;
    d.style.cssText=`width:${sz}px;height:${sz}px;top:${Math.random()*100}%;left:${Math.random()*100}%;animation-duration:${2+Math.random()*4}s;animation-delay:${Math.random()*4}s`;
    s.appendChild(d);
  }
})();

// ── Canvas setup ──────────────────────────────────────────────────
const canvas = document.getElementById('gc');
const ctx    = canvas.getContext('2d');
const CELL=20, COLS=21, ROWS=23;
const CW=COLS*CELL, CH=ROWS*CELL;
const DPR = Math.min(window.devicePixelRatio||1, 2);
canvas.width  = CW*DPR; canvas.height = CH*DPR;
canvas.style.width  = CW+'px'; canvas.style.height = CH+'px';
ctx.scale(DPR, DPR);

// ── Audio ─────────────────────────────────────────────────────────
let audioCtx = null;
function getAudio(){
  if(!audioCtx) audioCtx=new(window.AudioContext||window.webkitAudioContext)();
  if(audioCtx.state==='suspended') audioCtx.resume();
  return audioCtx;
}
function beep(f,d,v=0.12,t='sine'){
  try{
    const a=getAudio(),o=a.createOscillator(),g=a.createGain();
    o.connect(g);g.connect(a.destination);
    o.type=t;o.frequency.value=f;g.gain.value=v;
    g.gain.exponentialRampToValueAtTime(0.0001,a.currentTime+d);
    o.start();o.stop(a.currentTime+d);
  }catch(e){}
}
function playEat()      { beep(660+Math.random()*100,0.04,0.06,'square'); }
function playPower()    { beep(400,0.1,0.15); setTimeout(()=>beep(700,0.15,0.15),80); }
function playGhostEat() { beep(900,0.1,0.2); setTimeout(()=>beep(1300,0.08,0.15),100); }
function playDeath()    { for(let i=0;i<6;i++) setTimeout(()=>beep(300-i*30,0.15,0.15,'sawtooth'),i*120); }

// ── Maze template ─────────────────────────────────────────────────
const MAZE=[
  [1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1],
  [1,2,2,2,2,2,2,2,2,2,1,2,2,2,2,2,2,2,2,2,1],
  [1,3,1,1,2,1,1,1,2,1,1,1,2,1,1,1,2,1,1,3,1],
  [1,2,1,1,2,1,1,1,2,1,1,1,2,1,1,1,2,1,1,2,1],
  [1,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,1],
  [1,2,1,1,2,1,2,1,1,1,1,1,1,1,2,1,2,1,1,2,1],
  [1,2,2,2,2,1,2,2,2,2,1,2,2,2,2,1,2,2,2,2,1],
  [1,1,1,1,2,1,1,1,0,0,0,0,0,1,1,1,2,1,1,1,1],
  [1,1,1,1,2,1,0,0,0,1,1,1,0,0,0,1,2,1,1,1,1],
  [1,1,1,1,2,0,0,1,0,0,0,0,0,1,0,0,2,1,1,1,1],
  [0,0,0,0,2,0,0,1,1,1,1,1,1,1,0,0,2,0,0,0,0],
  [1,1,1,1,2,0,0,1,0,0,0,0,0,1,0,0,2,1,1,1,1],
  [1,1,1,1,2,1,0,0,0,1,1,1,0,0,0,1,2,1,1,1,1],
  [1,1,1,1,2,1,0,0,0,0,0,0,0,0,0,1,2,1,1,1,1],
  [1,1,1,1,2,1,0,1,1,1,1,1,1,1,0,1,2,1,1,1,1],
  [1,2,2,2,2,2,2,2,2,2,1,2,2,2,2,2,2,2,2,2,1],
  [1,2,1,1,2,1,1,1,2,1,1,1,2,1,1,1,2,1,1,2,1],
  [1,3,2,1,2,2,2,2,2,2,0,2,2,2,2,2,2,1,2,3,1],
  [1,1,2,1,2,1,2,1,1,1,1,1,1,1,2,1,2,1,2,1,1],
  [1,2,2,2,2,1,2,2,2,2,1,2,2,2,2,1,2,2,2,2,1],
  [1,2,1,1,1,1,1,1,2,1,1,1,2,1,1,1,1,1,1,2,1],
  [1,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,1],
  [1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1]
];
const GC=['#FF3030','#FFB8FF','#00FFEE','#FFB852'];

// ── State ─────────────────────────────────────────────────────────
let board, dots, pac, ghosts, score, level, lives, running, paused;
let isCountingDown = false;
let powerMode=false, powerTimer=0;
let animId, lastTime=0, startTime;
let mouth=0, mouthT=0.3, mouthD=1;
let dying=false, dyingT=0;
let particles=[];
let pelletT=0;
let bestScore = myBestScore;
let pacT=0, ghostT=0;

// ── Init ──────────────────────────────────────────────────────────
function initBoard(){
  board = MAZE.map(r=>[...r]);
  dots  = 0;
  for(let r=0;r<ROWS;r++) for(let c=0;c<COLS;c++) if(board[r][c]===2) dots++;
}

function spawnGhosts(){
  ghosts=[
    {x:10,y:11,px:10*CELL+CELL/2,py:11*CELL+CELL/2,tx:0,ty:0,c:GC[0],scared:false,eaten:false},
    {x:9, y:11,px:9*CELL+CELL/2, py:11*CELL+CELL/2,tx:0,ty:0,c:GC[1],scared:false,eaten:false},
    {x:10,y:11,px:10*CELL+CELL/2,py:11*CELL+CELL/2,tx:0,ty:0,c:GC[2],scared:false,eaten:false},
    {x:11,y:11,px:11*CELL+CELL/2,py:11*CELL+CELL/2,tx:0,ty:0,c:GC[3],scared:false,eaten:false},
  ];
}

function runCountdown(callback) {
  const el = document.getElementById('countdown');
  el.style.display = 'block';
  isCountingDown = true;
  let count = 3;
  el.textContent = count;
  beep(600, 0.1);
  const interval = setInterval(() => {
    count--;
    if (count > 0) {
      el.textContent = count;
      beep(600, 0.1);
    } else if (count === 0) {
      el.textContent = 'GO!';
      beep(900, 0.1);
    } else {
      clearInterval(interval);
      el.style.display = 'none';
      isCountingDown = false;
      callback();
    }
  }, 800);
}

function startGame(){
  if(animId) cancelAnimationFrame(animId);
  initBoard(); spawnGhosts();
  pac={x:10,y:17,dx:0,dy:0,ndx:0,ndy:0,px:10*CELL+CELL/2,py:17*CELL+CELL/2};
  score=0; level=1; lives=3; running=true; paused=false;
  powerMode=false; powerTimer=0; dying=false; dyingT=0; particles=[];
  pacT=0; ghostT=0;
  startTime=Date.now();
  hideOverlay(); updateHUD();
  lastTime=performance.now();
  getAudio();
  runCountdown(() => {
    animId=requestAnimationFrame(loop);
  });
}

// ── Overlay helpers ───────────────────────────────────────────────
function hideOverlay(){
  const ov=document.getElementById('overlay');
  ov.classList.add('hidden');
  setTimeout(()=>{ov.style.display='none'},400);
}
function showOverlay(){
  const ov=document.getElementById('overlay');
  ov.style.display='flex';
  ov.classList.remove('hidden');
}

function togglePause(){
  if(!running || isCountingDown) return;
  paused=!paused;
  const overlay = document.getElementById('overlay');
  const title = document.getElementById('overlayTitle');
  const desc = document.getElementById('overlayDesc');
  const scoreReveal = document.getElementById('ov-score-reveal');
  const startBtn = document.getElementById('startBtn');
  const resumeBtn = document.getElementById('resumeBtn');

  if(paused){
    title.textContent = 'PAUSED';
    desc.style.display = 'none';
    scoreReveal.style.display = 'none';
    startBtn.style.display = 'none';
    resumeBtn.style.display = 'block';
    showOverlay();
    cancelAnimationFrame(animId);
  } else {
    hideOverlay();
    runCountdown(() => {
      lastTime=performance.now();
      animId=requestAnimationFrame(loop);
    });
  }
}

// ── Main loop ─────────────────────────────────────────────────────
function loop(ts){
  if(!running||paused||isCountingDown) return;
  const dt=Math.min(ts-lastTime,50); lastTime=ts;
  pacT+=dt; ghostT+=dt;

  const pSpeed=Math.max(70,200-level*8);
  const gSpeed=Math.max(110,260-level*10);
  if(pacT>pSpeed)  { pacT=0;  movePac(); }
  if(ghostT>gSpeed){ ghostT=0; ghosts.forEach(moveGhost); }

  if(powerMode){
    powerTimer-=dt;
    if(powerTimer<=0){
      powerMode=false;
      ghosts.forEach(g=>{g.scared=false;if(g.eaten)resetGhost(g);});
      document.getElementById('power-flash').classList.remove('active');
    }
  }

  // Smooth lerp
  const LF=0.3;
  pac.px+=(pac.x*CELL+CELL/2-pac.px)*LF;
  pac.py+=(pac.y*CELL+CELL/2-pac.py)*LF;
  ghosts.forEach(g=>{
    g.px+=(g.x*CELL+CELL/2-g.px)*LF;
    g.py+=(g.y*CELL+CELL/2-g.py)*LF;
  });

  // Mouth animation
  mouth+=(mouthT-mouth)*0.18;
  if(Math.abs(mouth-mouthT)<0.01){ mouthT=mouthT>0.03?0.03:0.3; }

  pelletT+=dt*0.004;

  // Particles
  particles.forEach(p=>{p.x+=p.vx;p.y+=p.vy;p.vy+=0.12;p.life-=0.022;});
  particles=particles.filter(p=>p.life>0);

  // Death animation
  if(dying){
    dyingT+=dt/1000;
    if(dyingT>=1){
      dying=false; dyingT=0;
      if(lives<=0){ endGame(); return; }
      resetRound();
    }
  }

  draw();
  animId=requestAnimationFrame(loop);
}

// ── Movement ──────────────────────────────────────────────────────
function resetRound(){
  pac.x=10; pac.y=17; pac.dx=0; pac.dy=0; pac.ndx=0; pac.ndy=0;
  pac.px=10*CELL+CELL/2; pac.py=17*CELL+CELL/2;
  spawnGhosts(); updateHUD();
}

function canMove(x,y,dx,dy){
  const nx=(x+dx+COLS)%COLS;
  const ny=(y+dy+ROWS)%ROWS;
  return board[ny][nx]!==1;
}

function movePac(){
  if(dying) return;
  if(canMove(pac.x,pac.y,pac.ndx,pac.ndy)){ pac.dx=pac.ndx; pac.dy=pac.ndy; }
  if(canMove(pac.x,pac.y,pac.dx,pac.dy)){
    const prevX=pac.x, prevY=pac.y;
    pac.x=(pac.x+pac.dx+COLS)%COLS;
    pac.y=(pac.y+pac.dy+ROWS)%ROWS;
    if(Math.abs(pac.x-prevX)>1 || Math.abs(pac.y-prevY)>1){
      pac.px=pac.x*CELL+CELL/2;
      pac.py=pac.y*CELL+CELL/2;
    }
  }
  const cell=board[pac.y][pac.x];
  if(cell===2){
    board[pac.y][pac.x]=0; score+=10; dots--;
    playEat();
    if(dots<=0) levelUp();
  }
  if(cell===3){
    board[pac.y][pac.x]=0; score+=50;
    powerMode=true; powerTimer=7000;
    ghosts.forEach(g=>{g.scared=true;g.eaten=false;});
    playPower();
    document.getElementById('power-flash').classList.add('active');
    burst(pac.x*CELL+CELL/2, pac.y*CELL+CELL/2, '#fbbf24', 18);
  }
  ghosts.forEach(g=>{
    if(Math.abs(g.x-pac.x)<=0 && Math.abs(g.y-pac.y)<=0){
      if(powerMode&&g.scared&&!g.eaten){
        g.eaten=true; g.scared=false; score+=200; playGhostEat();
        burst(g.x*CELL+CELL/2, g.y*CELL+CELL/2, g.c, 24);
        scorePop('+200', g.x*CELL, g.y*CELL);
        setTimeout(()=>resetGhost(g),2000);
      } else if(!g.eaten&&!dying) pacDeath();
    }
  });
  updateHUD();
}

function moveGhost(g){
  if(g.eaten) return;
  const dx=[0,0,-1,1], dy=[-1,1,0,0];
  const target=g.scared
    ? {x:COLS-pac.x, y:ROWS-pac.y}
    : {x:pac.x, y:pac.y};
  let best=1e9, bi=0;
  for(let i=0;i<4;i++){
    const nx=(g.x+dx[i]+COLS)%COLS, ny=(g.y+dy[i]+ROWS)%ROWS;
    if(board[ny][nx]===1) continue;
    if(dx[i]===-g.tx && dy[i]===-g.ty) continue;
    const d=(nx-target.x)**2+(ny-target.y)**2;
    if(d<best){ best=d; bi=i; }
  }
  g.tx=dx[bi]; g.ty=dy[bi];
  const prevX=g.x, prevY=g.y;
  g.x=(g.x+dx[bi]+COLS)%COLS;
  g.y=(g.y+dy[bi]+ROWS)%ROWS;
  if(Math.abs(g.x-prevX)>1 || Math.abs(g.y-prevY)>1){
    g.px=g.x*CELL+CELL/2;
    g.py=g.y*CELL+CELL/2;
  }
  if(Math.abs(g.x-pac.x)<=0 && Math.abs(g.y-pac.y)<=0){
    if(powerMode&&g.scared&&!g.eaten){
      g.eaten=true; g.scared=false; score+=200; playGhostEat();
      setTimeout(()=>resetGhost(g),2000);
    } else if(!g.eaten&&!dying) pacDeath();
  }
}

function resetGhost(g){
  g.x=10; g.y=11; g.px=10*CELL+CELL/2; g.py=11*CELL+CELL/2;
  g.tx=0; g.ty=-1; g.scared=false; g.eaten=false;
}

function pacDeath(){
  if(dying) return;
  lives--; dying=true; dyingT=0; playDeath();
  if(navigator.vibrate) navigator.vibrate([60,40,120]);
}

function levelUp(){
  level++; powerMode=false; powerTimer=0;
  document.getElementById('power-flash').classList.remove('active');
  pac.x=10; pac.y=17; pac.dx=0; pac.dy=0;
  pac.px=10*CELL+CELL/2; pac.py=17*CELL+CELL/2;
  spawnGhosts(); initBoard(); updateHUD();
}

function endGame(){
  running=false; cancelAnimationFrame(animId);
  if(score>bestScore) {
    bestScore=score;
    document.getElementById('hud-best').textContent = bestScore.toLocaleString();
  }
  saveScore(score);
  const el=document.getElementById('ov-score-reveal');
  el.style.display='block';
  el.innerHTML=`GAME OVER<br><span style="color:#fbbf24;font-size:18px">${score.toLocaleString()}</span><br><span style="font-size:9px;color:#4a3a00">LEVEL ${level}</span>`;
  document.getElementById('startBtn').textContent='▶ PLAY AGAIN';
  const title = document.getElementById('overlayTitle');
  const desc = document.getElementById('overlayDesc');
  const startBtn = document.getElementById('startBtn');
  const resumeBtn = document.getElementById('resumeBtn');
  title.innerHTML = 'PAC-MAN';
  desc.style.display = 'block';
  startBtn.style.display = 'block';
  resumeBtn.style.display = 'none';
  showOverlay();
}

// ── HUD ───────────────────────────────────────────────────────────
function updateHUD(){
  document.getElementById('hud-score').textContent=score.toLocaleString();
  document.getElementById('hud-best').textContent=Math.max(score,bestScore).toLocaleString();
  document.getElementById('level-badge').textContent='LV '+level;
  const lw=document.getElementById('hud-lives');
  lw.innerHTML='';
  for(let i=0;i<3;i++){
    const d=document.createElement('div');
    d.className='life-dot'+(i>=lives?' lost':'');
    lw.appendChild(d);
  }
}

// ── FX helpers ────────────────────────────────────────────────────
function burst(x,y,color,n){
  for(let i=0;i<n;i++){
    const a=Math.random()*Math.PI*2, sp=2+Math.random()*6;
    particles.push({x,y,vx:Math.cos(a)*sp,vy:Math.sin(a)*sp,life:1,color,size:3+Math.random()*4});
  }
}

function scorePop(text,sx,sy){
  const el=document.createElement('div');
  el.className='score-pop'; el.textContent=text;
  const cr=canvas.getBoundingClientRect();
  el.style.left=(cr.left+sx)+'px'; el.style.top=(cr.top+sy-20)+'px';
  document.body.appendChild(el);
  setTimeout(()=>el.remove(),800);
}

// ── Draw ──────────────────────────────────────────────────────────
function draw(){
  ctx.fillStyle='#000814'; ctx.fillRect(0,0,CW,CH);
  const cg=ctx.createRadialGradient(CW/2,CH/2,0,CW/2,CH/2,200);
  cg.addColorStop(0,'rgba(10,20,60,.5)'); cg.addColorStop(1,'transparent');
  ctx.fillStyle=cg; ctx.fillRect(0,0,CW,CH);
  for(let r=0;r<ROWS;r++){
    for(let c=0;c<COLS;c++){
      const cell=board[r][c], px=c*CELL, py=r*CELL;
      if(cell===1){
        ctx.fillStyle='#0d2050'; ctx.fillRect(px,py,CELL,CELL);
        ctx.strokeStyle='rgba(50,90,240,.9)'; ctx.lineWidth=1.5;
        ctx.strokeRect(px+1.5,py+1.5,CELL-3,CELL-3);
        ctx.fillStyle='rgba(80,130,255,.15)'; ctx.fillRect(px+2,py+2,4,4);
      } else if(cell===2){
        const pulse=0.65+0.35*Math.sin(pelletT+r*0.4+c*0.25);
        ctx.fillStyle=`rgba(251,191,36,${pulse})`;
        ctx.shadowBlur=4*pulse; ctx.shadowColor='#fbbf24';
        ctx.beginPath(); ctx.arc(px+CELL/2,py+CELL/2,2.5,0,Math.PI*2); ctx.fill();
        ctx.shadowBlur=0;
      } else if(cell===3){
        const pp=0.7+0.3*Math.sin(pelletT*2.5);
        ctx.fillStyle=`rgba(251,191,36,${pp})`;
        ctx.shadowBlur=14+pp*10; ctx.shadowColor='#fbbf24';
        ctx.beginPath(); ctx.arc(px+CELL/2,py+CELL/2,6,0,Math.PI*2); ctx.fill();
        ctx.shadowBlur=0;
      }
    }
  }
  ghosts.forEach(g=>{
    if(g.eaten) return;
    const px=g.px, py=g.py;
    const scared=powerMode&&g.scared;
    const flicker=scared&&powerTimer<2500&&Math.floor(Date.now()/180)%2===0;
    const bodyColor=scared?(flicker?'#ffffff':'#2244ff'):g.c;
    ctx.save();
    if(!scared){ ctx.shadowBlur=12; ctx.shadowColor=g.c; }
    ctx.fillStyle=bodyColor;
    const r2=CELL*.44;
    ctx.beginPath();
    ctx.arc(px,py-CELL*.1,r2,Math.PI,0);
    ctx.lineTo(px+r2,py+CELL*.38);
    for(let i=4;i>=0;i--)
      ctx.lineTo(px+r2*(i*2-4)/4, py+CELL*(.38+(i%2===0?.1:-.08)));
    ctx.lineTo(px-r2,py+CELL*.38);
    ctx.closePath(); ctx.fill();
    if(!scared){
      ctx.shadowBlur=0;
      ctx.fillStyle='#fff';
      ctx.beginPath(); ctx.ellipse(px-CELL*.14,py-CELL*.18,CELL*.13,CELL*.16,0,0,Math.PI*2); ctx.fill();
      ctx.beginPath(); ctx.ellipse(px+CELL*.14,py-CELL*.18,CELL*.13,CELL*.16,0,0,Math.PI*2); ctx.fill();
      ctx.fillStyle='#1a3fff';
      const ex=(pac.x-g.x)*0.08, ey=(pac.y-g.y)*0.08;
      ctx.beginPath(); ctx.arc(px-CELL*.14+ex,py-CELL*.18+ey,CELL*.07,0,Math.PI*2); ctx.fill();
      ctx.beginPath(); ctx.arc(px+CELL*.14+ex,py-CELL*.18+ey,CELL*.07,0,Math.PI*2); ctx.fill();
    } else {
      ctx.strokeStyle='rgba(255,255,255,.8)'; ctx.lineWidth=2; ctx.shadowBlur=0;
      ctx.beginPath();
      ctx.moveTo(px-CELL*.22,py-CELL*.22); ctx.lineTo(px-CELL*.10,py-CELL*.10);
      ctx.moveTo(px-CELL*.10,py-CELL*.22); ctx.lineTo(px-CELL*.22,py-CELL*.10);
      ctx.stroke();
      ctx.beginPath();
      ctx.moveTo(px+CELL*.10,py-CELL*.22); ctx.lineTo(px+CELL*.22,py-CELL*.10);
      ctx.moveTo(px+CELL*.22,py-CELL*.22); ctx.lineTo(px+CELL*.10,py-CELL*.10);
      ctx.stroke();
      ctx.beginPath();
      ctx.moveTo(px-CELL*.18,py+CELL*.06);
      for(let i=0;i<5;i++)
        ctx.lineTo(px-CELL*.18+i*CELL*.09, py+CELL*.06+(i%2===0?.06:-.04)*CELL);
      ctx.stroke();
    }
    ctx.restore();
  });
  if(!dying||dyingT<1){
    const px=pac.px, py=pac.py;
    if(dying){
      const t=dyingT;
      ctx.save();
      ctx.globalAlpha=Math.max(0,1-t*1.2);
      ctx.translate(px,py); ctx.rotate(t*Math.PI*3);
      const dr=CELL*.46*(1-t*.6);
      ctx.fillStyle='#fbbf24'; ctx.shadowBlur=20*(1-t); ctx.shadowColor='#fbbf24';
      ctx.beginPath(); ctx.arc(0,0,dr,t*Math.PI,Math.PI*2-t*Math.PI); ctx.lineTo(0,0); ctx.closePath(); ctx.fill();
      for(let i=0;i<8;i++){
        const a=i/8*Math.PI*2;
        ctx.strokeStyle=`rgba(251,191,36,${0.7*(1-t)})`; ctx.lineWidth=2.5;
        ctx.beginPath();
        ctx.moveTo(Math.cos(a)*dr*.8,Math.sin(a)*dr*.8);
        ctx.lineTo(Math.cos(a)*dr*2*(0.5+t),Math.sin(a)*dr*2*(0.5+t));
        ctx.stroke();
      }
      ctx.restore();
    } else {
      const angle=Math.atan2(pac.dy,pac.dx)||0;
      const mo=mouth+.04;
      ctx.save();
      ctx.shadowBlur=18; ctx.shadowColor='rgba(251,191,36,.6)';
      ctx.fillStyle='#fbbf24';
      ctx.beginPath(); ctx.moveTo(px,py);
      ctx.arc(px,py,CELL*.46,angle+mo,angle+Math.PI*2-mo);
      ctx.closePath(); ctx.fill();
      ctx.globalAlpha=0.25; ctx.fillStyle='#fff';
      ctx.beginPath(); ctx.arc(px-3,py-3,CELL*.18,0,Math.PI*2); ctx.fill();
      ctx.restore();
    }
  }
  particles.forEach(p=>{
    ctx.globalAlpha=Math.max(0,p.life);
    ctx.fillStyle=p.color;
    ctx.shadowBlur=8*p.life; ctx.shadowColor=p.color;
    ctx.beginPath(); ctx.arc(p.x,p.y,p.size*p.life*.8,0,Math.PI*2); ctx.fill();
  });
  ctx.globalAlpha=1; ctx.shadowBlur=0;
}

// ── Input ─────────────────────────────────────────────────────────
document.addEventListener('keydown',e=>{
  const m={
    ArrowUp:[0,-1],w:[0,-1],W:[0,-1],
    ArrowDown:[0,1],s:[0,1],S:[0,1],
    ArrowLeft:[-1,0],a:[-1,0],A:[-1,0],
    ArrowRight:[1,0],d:[1,0],D:[1,0]
  };
  if(m[e.key]){ [pac.ndx,pac.ndy]=m[e.key]; e.preventDefault(); }
  if(e.key==='p'||e.key==='P'){ togglePause(); }
});

document.getElementById('startBtn').onclick=startGame;
document.getElementById('resumeBtn').onclick=togglePause;
document.getElementById('back-link').onclick=()=>{if(running && score>0) saveScore(score);};
resize();

ctx.fillStyle='#000814'; ctx.fillRect(0,0,CW,CH);
updateHUD();
</script>
</body>
</html>