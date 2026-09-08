<?php
require_once '../includes/auth.php';
$myBest = 0;
if (isLoggedIn()) {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT MAX(s.score) AS best FROM scores s JOIN games g ON s.game_id = g.id WHERE s.user_id = ? AND g.slug = 'flappybird'");
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
<title>FLAPPY BIRD</title>
<style>
@import url('https://fonts.googleapis.com/css2?family=Press+Start+2P&family=Orbitron:wght@400;700;900&display=swap');
*{margin:0;padding:0;box-sizing:border-box;user-select:none;-webkit-tap-highlight-color:transparent}
html,body{width:100%;height:100%;overflow:hidden;background:#000}
body{display:flex;align-items:center;justify-content:center;font-family:'Orbitron',monospace;background:radial-gradient(ellipse at center,#050a14 0%,#000 100%)}

#stars{position:fixed;inset:0;pointer-events:none;z-index:0}
.star{position:absolute;border-radius:50%;background:#fff;animation:twinkle linear infinite}
@keyframes twinkle{0%,100%{opacity:.08}50%{opacity:.7}}

#game-root{position:relative;z-index:1;display:flex;flex-direction:column;align-items:center;justify-content:center;width:100vw;height:100vh}

/* HUD */
#hud-top{display:flex;justify-content:space-between;align-items:flex-start;width:100%;max-width:500px;padding:0 4px 10px;z-index:10}
.hud-block{display:flex;flex-direction:column;align-items:center;gap:2px}
.hud-label{font-size:7px;letter-spacing:3px;color:#0a2a4a;text-transform:uppercase}
.hud-val{font-family:'Press Start 2P',monospace;font-size:18px;color:#38bdf8;text-shadow:0 0 14px #38bdf888;line-height:1}
.hud-val-sm{font-family:'Press Start 2P',monospace;font-size:10px;color:#7dd3fc}

/* Difficulty pills */
#diff-row{display:flex;gap:6px;align-items:center}
.diff-pill{font-size:7px;letter-spacing:1px;padding:4px 10px;border-radius:20px;cursor:pointer;border:1px solid rgba(56,189,248,.2);color:#1a4a6a;background:transparent;font-family:'Orbitron',monospace;transition:all .2s}
.diff-pill.active{border-color:#38bdf8;color:#38bdf8;background:rgba(56,189,248,.08);box-shadow:0 0 10px rgba(56,189,248,.2)}

/* Canvas */
#canvas-wrap{position:relative;line-height:0;border-radius:8px;box-shadow:0 0 60px rgba(56,189,248,.2),0 0 120px rgba(56,189,248,.08),inset 0 0 40px rgba(0,0,0,.6)}
canvas{display:block;border-radius:8px;image-rendering:pixelated}

#canvas-wrap{max-height:calc(100vh - 100px)}
canvas{max-width:100%;max-height:calc(100vh - 100px);width:auto!important;height:auto!important}

/* Bottom HUD */
#hud-bot{display:flex;justify-content:space-between;align-items:center;width:100%;max-width:500px;padding:10px 4px 0}
.hint-text{font-size:7px;letter-spacing:2px;color:#0a1a3a;text-transform:uppercase}
#best-badge{background:linear-gradient(135deg,#0ea5e9,#38bdf8);color:#000;font-family:'Press Start 2P',monospace;font-size:8px;padding:5px 12px;border-radius:20px;letter-spacing:1px}

/* OVERLAY */
#overlay{position:fixed;inset:0;z-index:100;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:18px;background:rgba(0,0,0,.93);backdrop-filter:blur(20px);transition:opacity .4s}
#overlay.hidden{opacity:0;pointer-events:none}
.sub-label{font-size:8px;letter-spacing:6px;color:#0a2a4a;text-transform:uppercase}
.arcade-title{font-family:'Press Start 2P',monospace;font-size:clamp(18px,5vw,36px);background:linear-gradient(180deg,#fff 0%,#38bdf8 40%,#0ea5e9 100%);-webkit-background-clip:text;background-clip:text;color:transparent;letter-spacing:3px;text-align:center;line-height:1.4;animation:titlePulse 2s ease-in-out infinite}
@keyframes titlePulse{0%,100%{filter:drop-shadow(0 0 16px #38bdf855)}50%{filter:drop-shadow(0 0 32px #38bdf8aa)}}
.ov-desc{color:#1a4a6a;font-size:9px;text-align:center;max-width:280px;line-height:2.2;letter-spacing:1px}
.ov-desc span{color:#38bdf8}

/* Difficulty selector in overlay */
.ov-diff-row{display:flex;gap:10px}
.ov-diff{font-family:'Orbitron',monospace;font-size:9px;letter-spacing:1px;padding:8px 16px;border-radius:30px;cursor:pointer;border:1px solid rgba(56,189,248,.2);color:#1a4a6a;background:transparent;transition:all .2s}
.ov-diff:hover{border-color:#38bdf8;color:#38bdf8}
.ov-diff.active{border-color:#38bdf8;color:#38bdf8;box-shadow:0 0 14px rgba(56,189,248,.3);background:rgba(56,189,248,.08)}

#startBtn{font-family:'Press Start 2P',monospace;font-size:10px;letter-spacing:3px;background:transparent;border:2px solid #38bdf8;color:#38bdf8;padding:14px 44px;border-radius:40px;cursor:pointer;transition:all .2s;position:relative;overflow:hidden}
#startBtn::before{content:'';position:absolute;inset:0;background:#38bdf8;transform:scaleX(0);transform-origin:left;transition:transform .25s;z-index:-1}
#startBtn:hover{color:#000;box-shadow:0 0 30px #38bdf8}
#startBtn:hover::before{transform:scaleX(1)}
#ov-result{font-family:'Press Start 2P',monospace;font-size:13px;color:#38bdf8;text-align:center;line-height:2;display:none}

/* Score pop */
.score-pop{position:fixed;font-family:'Press Start 2P',monospace;font-size:12px;color:#38bdf8;pointer-events:none;z-index:200;animation:popUp .8s ease forwards;text-shadow:0 0 10px #38bdf8}
@keyframes popUp{0%{opacity:1;transform:translateY(0)}100%{opacity:0;transform:translateY(-50px)}}

/* Streak badge */
#streak-badge{position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);font-family:'Press Start 2P',monospace;font-size:18px;color:#fbbf24;text-shadow:0 0 20px #fbbf24;pointer-events:none;z-index:200;opacity:0;transition:opacity .2s}
#streak-badge.show{opacity:1;animation:streakPop .6s ease forwards}
@keyframes streakPop{0%{transform:translate(-50%,-50%) scale(.6)}60%{transform:translate(-50%,-50%) scale(1.2)}100%{transform:translate(-50%,-50%) scale(1);opacity:0}}

/* Countdown */
#countdown {
  position: fixed; top: 50%; left: 50%;
  transform: translate(-50%, -50%);
  font-family: 'Press Start 2P', monospace;
  font-size: 64px; color: #38bdf8;
  text-shadow: 0 0 20px #38bdf8;
  z-index: 200;
  pointer-events: none;
  display: none;
}
/* Pause Button */
#pauseBtn {
  position: fixed; top: 20px; right: 20px;
  z-index: 150;
  background: rgba(56, 189, 248, 0.1);
  border: 1px solid #38bdf8;
  color: #38bdf8;
  padding: 8px 16px;
  border-radius: 4px;
  font-family: 'Press Start 2P', monospace;
  font-size: 10px;
  cursor: pointer;
  transition: all 0.2s;
}
#pauseBtn:hover { background: rgba(56, 189, 248, 0.2); box-shadow: 0 0 10px #38bdf8; }
</style>
</head>
<body>
<div id="stars"></div>
<button id="pauseBtn" style="display:none">PAUSE</button>

<div id="overlay">
  <div class="sub-label">⚡ retrogames ⚡</div>
  <div class="arcade-title" id="overlayTitle">FLAPPY<br>BIRD</div>
  <div class="ov-desc" id="overlayDesc">
    Klik, <span>SPACE</span> atau <span>TAP</span> untuk terbang!<br>
    Hindari pipa, jangan jatuh ke tanah.
  </div>
  <div class="ov-diff-row" id="diffSelectors">
    <div class="ov-diff active" onclick="selectDiff(this,'easy')">🕊 EASY</div>
    <div class="ov-diff" onclick="selectDiff(this,'normal')">🐦 NORMAL</div>
    <div class="ov-diff" onclick="selectDiff(this,'hard')">🔥 HARD</div>
  </div>
  <div id="ov-result"></div>
  <div style="display:flex; gap:12px; flex-wrap:wrap; justify-content:center;">
    <a href="../index.php" id="back-link" style="font-family:'Press Start 2P',monospace; font-size:10px; letter-spacing:3px; background:transparent; border:2px solid #38bdf8; color:#38bdf8; padding:14px 44px; border-radius:40px; cursor:pointer; text-decoration:none; display:inline-flex; align-items:center;">← BACK</a>
    <button id="startBtn">▶ START</button>
    <button id="resumeBtn" style="display:none; font-family:'Press Start 2P',monospace; font-size:10px; letter-spacing:3px; background:transparent; border:2px solid #38bdf8; color:#38bdf8; padding:14px 44px; border-radius:40px; cursor:pointer;">RESUME</button>
</div>
  <div style="font-size:7px;letter-spacing:2px;color:#0a1a3a;text-align:center">SPACE / CLICK / TAP = FLAP</div>
</div>

<div id="game-root">
  <div id="hud-top">
    <div class="hud-block">
      <div class="hud-label">Score</div>
      <div class="hud-val" id="hud-score">0</div>
    </div>
    <div class="hud-block" id="diff-hud">
      <div id="diff-row" class="diff-row" style="display:flex;gap:6px">
        <div class="diff-pill active" onclick="selectDiff(this,'easy')">EASY</div>
        <div class="diff-pill" onclick="selectDiff(this,'normal')">NRM</div>
        <div class="diff-pill" onclick="selectDiff(this,'hard')">HARD</div>
      </div>
    </div>
    <div class="hud-block">
      <div class="hud-label">Best</div>
      <div class="hud-val-sm" id="hud-best"><?= number_format((int)$myBest) ?></div>
    </div>
  </div>
  <div id="canvas-wrap">
    <canvas id="gc"></canvas>
  </div>
  <div id="hud-bot">
    <div class="hint-text">Space / Click / Tap = Flap</div>
    <div id="best-badge">BEST <?= number_format((int)$myBest) ?></div>
  </div>
</div>
<div id="streak-badge"></div>
<div id="countdown"></div>

<script>
const LOGGED_IN = <?= isLoggedIn() ? 'true' : 'false' ?>;
const SAVE_URL = '../api/save_score.php';
let myBestScore = <?= (int)$myBest ?>;
(function(){
  const s=document.getElementById('stars');
  for(let i=0;i<60;i++){
    const d=document.createElement('div');d.className='star';
    const sz=Math.random()*1.8+.5;
    d.style.cssText=`width:${sz}px;height:${sz}px;top:${Math.random()*100}%;left:${Math.random()*100}%;animation-duration:${2+Math.random()*5}s;animation-delay:${Math.random()*5}s`;
    s.appendChild(d);
  }
})();

// ── Canvas ────────────────────────────────────────────────────────
const canvas=document.getElementById('gc');
const ctx=canvas.getContext('2d');
const W=480,H=700;
const DPR=Math.min(window.devicePixelRatio||1,2);
canvas.width=W*DPR; canvas.height=H*DPR;
canvas.style.width=W+'px'; canvas.style.height=H+'px';
ctx.scale(DPR,DPR);

// ── Audio ─────────────────────────────────────────────────────────
let audioCtx=null;
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
function playFlap()  { beep(680,0.07,0.1,'square'); }
function playScore() { beep(1100,0.08,0.12,'sine'); setTimeout(()=>beep(1600,0.08,0.1,'sine'),70); }
function playDeath() { for(let i=0;i<5;i++) setTimeout(()=>beep(220-i*20,0.15,0.15,'sawtooth'),i*110); }

// ── Config ────────────────────────────────────────────────────────
const GRAVITY=0.38, PIPE_W=56, BIRD_X=80;
const DIFF={
  easy:  {gap:175,speed:2.4,interval:1700,flap:-7.4},
  normal:{gap:145,speed:3.2,interval:1450,flap:-7.9},
  hard:  {gap:112,speed:4.3,interval:1200,flap:-8.3}
};
let diff='easy';
let bestScore = myBestScore;

async function saveScore(s) {
  if (!LOGGED_IN || s <= 0) return;
  const duration = Math.floor((Date.now() - startTime) / 1000);
  try {
    const r = await fetch(SAVE_URL, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ game: 'flappybird', score: s, level: 1, duration: duration }),
      keepalive: true
    });
    const d = await r.json();
    if (d.success && d.best_score > myBestScore) {
      myBestScore = d.best_score;
      bestScore = d.best_score;
      document.getElementById('hud-best').textContent = bestScore.toLocaleString();
      document.getElementById('best-badge').textContent = 'BEST ' + bestScore.toLocaleString();
    }
  } catch (e) {}
}

// ── State ─────────────────────────────────────────────────────────
let bird, pipes, score, running, animId, pipeTimer;
let paused = false, isCountingDown = false;
let clouds, particles=[];
let startTime, lastTs=0;
let screenFlash=0, shakeX=0, shakeY=0;
let groundOffset=0;
let streak=0;

// ── Diff selector ─────────────────────────────────────────────────
function selectDiff(el, d){
  diff=d;
  document.querySelectorAll('.ov-diff,.diff-pill').forEach(e=>{
    const eD=e.dataset.diff||(e.classList.contains('ov-diff')?
      ['easy','normal','hard'][Array.from(e.parentNode.children).indexOf(e)]:
      ['easy','normal','hard'][Array.from(e.parentNode.children).indexOf(e)]);
    e.classList.toggle('active', eD===d);
  });
}
document.querySelectorAll('.ov-diff,.diff-pill').forEach((e,_,arr)=>{
  const diffs=['easy','normal','hard'];
  const siblings=Array.from(e.parentNode.children);
  e.dataset.diff=diffs[siblings.indexOf(e)];
  e.onclick=()=>selectDiff(e, e.dataset.diff);
});

// ── Init helpers ──────────────────────────────────────────────────
function initClouds(){
  clouds=[];
  for(let i=0;i<6;i++)
    clouds.push({x:Math.random()*W,y:20+Math.random()*130,r:.3+Math.random()*.4,speed:.25+Math.random()*.3,alpha:.06+Math.random()*.1});
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
  const cfg=DIFF[diff];
  bird={x:BIRD_X,y:H/2,vy:0,rot:0,wing:0,wingDir:1,flapAnim:0,px:BIRD_X,py:H/2};
  pipes=[]; score=0; running=true; paused=false; particles=[];
  screenFlash=0; shakeX=0; shakeY=0; streak=0;
  pipeTimer=cfg.interval-600;
  initClouds();
  startTime=Date.now(); lastTs=0; groundOffset=0;
  hideOverlay();
  document.getElementById('hud-score').textContent='0';
  document.getElementById('pauseBtn').style.display='block';
  getAudio();
  runCountdown(() => {
    lastTs = performance.now();
    animId=requestAnimationFrame(loop);
  });
}

function togglePause() {
  if (!running || isCountingDown) return;
  paused = !paused;
  const overlay = document.getElementById('overlay');
  const title = document.getElementById('overlayTitle');
  const desc = document.getElementById('overlayDesc');
  const diffs = document.getElementById('diffSelectors');
  const result = document.getElementById('ov-result');
  const startBtn = document.getElementById('startBtn');
  const resumeBtn = document.getElementById('resumeBtn');

  if (paused) {
    title.innerHTML = 'PAUSED';
    desc.style.display = 'none';
    diffs.style.display = 'none';
    result.style.display = 'none';
    startBtn.style.display = 'none';
    resumeBtn.style.display = 'block';
    showOverlay();
    if (animId) cancelAnimationFrame(animId);
  } else {
    hideOverlay();
    runCountdown(() => {
      lastTs = performance.now();
      animId = requestAnimationFrame(loop);
    });
  }
}

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

// ── Loop ──────────────────────────────────────────────────────────
function loop(ts){
  if(!running || paused || isCountingDown) return;
  const dt=Math.min((ts-lastTs)/16.667,3);
  lastTs=ts;
  update(dt); draw();
  animId=requestAnimationFrame(loop);
}

// ── Update ────────────────────────────────────────────────────────
function update(dt){
  const cfg=DIFF[diff];
  bird.vy+=GRAVITY*dt;
  bird.y+=bird.vy*dt;
  bird.px+=(bird.x-bird.px)*.4;
  bird.py+=(bird.y-bird.py)*.4;
  bird.rot=Math.max(-28,Math.min(85,bird.vy*4));
  const flapSpeed=bird.flapAnim>0?3.5:1;
  bird.wing+=0.22*dt*flapSpeed*bird.wingDir;
  if(bird.wing>0.65||bird.wing<-0.12) bird.wingDir*=-1;
  if(bird.flapAnim>0) bird.flapAnim-=0.08*dt;
  groundOffset=(groundOffset+cfg.speed*dt)%40;
  
  pipeTimer += dt * 16.667;
  if(pipeTimer > cfg.interval){
    const topH=70+Math.random()*(H-cfg.gap-140);
    pipes.push({x:W+10,topH,scored:false,gp:Math.random()*Math.PI*2,trailX:[]});
    pipeTimer = 0;
  }
  
  pipes.forEach(p=>{
    p.x-=cfg.speed*dt;
    p.gp+=0.04*dt;
    p.trailX.push(p.x);
    if(p.trailX.length>8) p.trailX.shift();
  });
  pipes=pipes.filter(p=>p.x>-PIPE_W-20);
  pipes.forEach(p=>{
    if(!p.scored&&p.x+PIPE_W<BIRD_X){
      p.scored=true; score++; streak++;
      document.getElementById('hud-score').textContent=score;
      document.getElementById('hud-best').textContent=Math.max(score,bestScore);
      document.getElementById('best-badge').textContent='BEST '+Math.max(score,bestScore);
      playScore();
      screenFlash=0.35;
      scorePop('+1');
      if(streak>0&&streak%5===0) showStreak('🔥 x'+streak+'!');
    }
  });
  clouds.forEach(c=>{c.x-=c.speed*dt; if(c.x<-120) c.x=W+80;});
  particles.forEach(p=>{p.x+=p.vx*dt;p.y+=p.vy*dt;p.vy+=0.18*dt;p.life-=0.018*dt;});
  particles=particles.filter(p=>p.life>0);
  screenFlash=Math.max(0,screenFlash-0.025*dt);
  shakeX*=Math.pow(0.7,dt); shakeY*=Math.pow(0.7,dt);
  const gap=cfg.gap, bx=BIRD_X, by=bird.y, br=13;
  if(by-br<0||by+br>H-52){ endGame(); return; }
  for(const p of pipes){
    if(bx+br>p.x+5&&bx-br<p.x+PIPE_W-5){
      if(by-br<p.topH||by+br>p.topH+gap){ endGame(); return; }
    }
  }
}

// ── End ───────────────────────────────────────────────────────────
function endGame(){
  running=false; cancelAnimationFrame(animId);
  document.getElementById('pauseBtn').style.display='none';
  for(let i=0;i<35;i++){
    const a=Math.random()*Math.PI*2, sp=2+Math.random()*9;
    particles.push({x:bird.x,y:bird.y,vx:Math.cos(a)*sp,vy:Math.sin(a)*sp-2,life:1,
      color:`hsl(${35+Math.random()*30},90%,60%)`,size:3+Math.random()*5,rot:Math.random()*Math.PI});
  }
  shakeX=10; shakeY=10;
  playDeath();
  if(navigator.vibrate) navigator.vibrate([40,25,80]);
  if(score>bestScore) bestScore=score;
  document.getElementById('hud-best').textContent=bestScore;
  document.getElementById('best-badge').textContent='BEST '+bestScore;
  saveScore(score);
  draw();
  setTimeout(()=>{
    const res=document.getElementById('ov-result');
    res.style.display='block';
    res.innerHTML=`SCORE: ${score}<br><span style="font-size:9px;color:#7dd3fc">BEST: ${bestScore}</span>`;
    document.getElementById('startBtn').textContent='▶ RETRY';
    const title = document.getElementById('overlayTitle');
    const desc = document.getElementById('overlayDesc');
    const diffs = document.getElementById('diffSelectors');
    const startBtn = document.getElementById('startBtn');
    const resumeBtn = document.getElementById('resumeBtn');
    title.innerHTML = 'FLAPPY<br>BIRD';
    desc.style.display = 'block';
    diffs.style.display = 'flex';
    startBtn.style.display = 'block';
    resumeBtn.style.display = 'none';
    showOverlay();
  },750);
}

// ── FX ────────────────────────────────────────────────────────────
function scorePop(text){
  const el=document.createElement('div'); el.className='score-pop'; el.textContent=text;
  const cr=canvas.getBoundingClientRect();
  el.style.left=(cr.left+W/2-20)+'px'; el.style.top=(cr.top+60)+'px';
  document.body.appendChild(el);
  setTimeout(()=>el.remove(),800);
}
function showStreak(text){
  const sb=document.getElementById('streak-badge');
  sb.textContent=text; sb.className='show';
  setTimeout(()=>sb.className='',700);
}

// ── Draw ──────────────────────────────────────────────────────────
function draw(){
  ctx.save();
  const sx=(Math.random()-.5)*shakeX, sy=(Math.random()-.5)*shakeY;
  ctx.translate(sx,sy);
  const t=Math.min(score/40,1);
  const sky=ctx.createLinearGradient(0,0,0,H);
  sky.addColorStop(0,`hsl(${215+t*25},${62+t*12}%,${10+t*6}%)`);
  sky.addColorStop(.55,`hsl(${200+t*20},52%,${17+t*9}%)`);
  sky.addColorStop(1,'#1a3d2b');
  ctx.fillStyle=sky; ctx.fillRect(0,0,W,H);
  const sa=Math.max(0,.7-score*.025);
  if(sa>0){
    ctx.fillStyle=`rgba(255,255,255,${sa})`;
    for(let i=0;i<50;i++){
      const sx2=(i*53+17)%W, sy2=(i*29+11)%(H*.38);
      ctx.beginPath(); ctx.arc(sx2,sy2,.5+((i*7)%3)*.4,0,Math.PI*2); ctx.fill();
    }
  }
  clouds.forEach(c=>{
    ctx.globalAlpha=c.alpha;
    ctx.fillStyle='#bae6fd';
    ctx.beginPath(); ctx.ellipse(c.x,c.y,58*c.r,24*c.r,0,0,Math.PI*2); ctx.fill();
    ctx.beginPath(); ctx.ellipse(c.x-24*c.r,c.y+7*c.r,34*c.r,16*c.r,0,0,Math.PI*2); ctx.fill();
    ctx.beginPath(); ctx.ellipse(c.x+28*c.r,c.y+5*c.r,38*c.r,18*c.r,0,0,Math.PI*2); ctx.fill();
  });
  ctx.globalAlpha=1;
  const gap=DIFF[diff].gap;
  pipes.forEach(p=>{
    const glow=.45+.55*Math.sin(p.gp);
    p.trailX.forEach((tx,i)=>{
      const alpha=(i/p.trailX.length)*.06*glow;
      ctx.fillStyle=`rgba(34,197,94,${alpha})`;
      ctx.fillRect(tx,0,PIPE_W,p.topH);
      ctx.fillRect(tx,p.topH+gap,PIPE_W,H-p.topH-gap-50);
    });
    ctx.shadowBlur=10+glow*12; ctx.shadowColor=`rgba(34,197,94,${.3+glow*.25})`;
    const pg=ctx.createLinearGradient(p.x,0,p.x+PIPE_W,0);
    pg.addColorStop(0,'#14532d'); pg.addColorStop(.28,'#22c55e'); pg.addColorStop(.65,'#15803d'); pg.addColorStop(1,'#14532d');
    ctx.fillStyle=pg;
    ctx.fillRect(p.x,0,PIPE_W,p.topH);
    ctx.fillRect(p.x-7,p.topH-24,PIPE_W+14,24);
    ctx.fillRect(p.x,p.topH+gap,PIPE_W,H-p.topH-gap-50);
    ctx.fillRect(p.x-7,p.topH+gap,PIPE_W+14,24);
    ctx.fillStyle='rgba(255,255,255,.14)';
    ctx.fillRect(p.x+5,0,9,p.topH-24);
    ctx.fillRect(p.x+5,p.topH+gap+24,9,H-p.topH-gap-74);
    ctx.shadowBlur=0;
    ctx.strokeStyle=`rgba(74,222,128,${.15+glow*.2})`; ctx.lineWidth=1;
    ctx.beginPath(); ctx.moveTo(p.x,p.topH); ctx.lineTo(p.x+PIPE_W,p.topH); ctx.stroke();
    ctx.beginPath(); ctx.moveTo(p.x,p.topH+gap); ctx.lineTo(p.x+PIPE_W,p.topH+gap); ctx.stroke();
  });
  ctx.shadowBlur=0;
  const gr=ctx.createLinearGradient(0,H-52,0,H);
  gr.addColorStop(0,'#166534'); gr.addColorStop(.15,'#15803d'); gr.addColorStop(1,'#0d3320');
  ctx.fillStyle=gr; ctx.fillRect(0,H-52,W,52);
  ctx.fillStyle='#4ade80'; ctx.fillRect(0,H-52,W,5);
  ctx.fillStyle='rgba(0,0,0,.1)';
  for(let i=0;i<W/38+2;i++){
    const gx=(i*38-groundOffset+W)%W-18;
    ctx.fillRect(gx,H-52,18,52);
  }
  ctx.fillStyle='#22c55e';
  for(let i=0;i<W/20;i++){
    const gx=(i*20-(groundOffset*.5)%20+W)%W;
    ctx.beginPath(); ctx.arc(gx,H-52,3,Math.PI,0); ctx.fill();
  }
  ctx.save();
  ctx.translate(bird.px, bird.py);
  ctx.rotate(bird.rot*Math.PI/180);
  ctx.shadowBlur=18; ctx.shadowColor='rgba(251,191,36,.55)';
  const bg=ctx.createRadialGradient(-2,-5,2,0,0,17);
  bg.addColorStop(0,'#fef9c3'); bg.addColorStop(.45,'#fbbf24'); bg.addColorStop(1,'#b45309');
  ctx.fillStyle=bg;
  ctx.beginPath(); ctx.ellipse(0,0,17,14,-.05,0,Math.PI*2); ctx.fill();
  ctx.shadowBlur=0;
  const wy=Math.sin(bird.wing)*9-1;
  const wg=ctx.createLinearGradient(-10,wy,-10,wy+12);
  wg.addColorStop(0,'#fde68a'); wg.addColorStop(1,'#d97706');
  ctx.fillStyle=wg;
  ctx.beginPath(); ctx.ellipse(-3,wy,11,5.5,bird.wing*.25,0,Math.PI*2); ctx.fill();
  ctx.fillStyle='#d97706';
  ctx.beginPath(); ctx.moveTo(-13,1); ctx.lineTo(-23,-1); ctx.lineTo(-22,5); ctx.closePath(); ctx.fill();
  ctx.beginPath(); ctx.moveTo(-13,3); ctx.lineTo(-21,6); ctx.lineTo(-20,9); ctx.closePath(); ctx.fill();
  ctx.fillStyle='#fff'; ctx.shadowBlur=4; ctx.shadowColor='rgba(255,255,255,.5)';
  ctx.beginPath(); ctx.ellipse(7,-3,5.5,4.5,0,0,Math.PI*2); ctx.fill();
  ctx.shadowBlur=0;
  ctx.fillStyle='#0f172a';
  ctx.beginPath(); ctx.arc(8.5,-3,2.5,0,Math.PI*2); ctx.fill();
  ctx.fillStyle='#fff';
  ctx.beginPath(); ctx.arc(9.5,-4.2,1.1,0,Math.PI*2); ctx.fill();
  ctx.fillStyle='#ef4444';
  ctx.beginPath(); ctx.moveTo(13,-1.5); ctx.lineTo(22,-.5); ctx.lineTo(13,3.5); ctx.closePath(); ctx.fill();
  ctx.fillStyle='rgba(255,255,255,.3)';
  ctx.beginPath(); ctx.moveTo(13,-1.5); ctx.lineTo(22,-.5); ctx.lineTo(17,-.5); ctx.closePath(); ctx.fill();
  ctx.fillStyle='rgba(251,113,133,.35)';
  ctx.beginPath(); ctx.ellipse(4,4,5,3.5,0,0,Math.PI*2); ctx.fill();
  ctx.restore();
  particles.forEach(p=>{
    ctx.save();
    ctx.globalAlpha=Math.max(0,p.life);
    ctx.fillStyle=p.color;
    if(p.rot!==undefined){
      ctx.translate(p.x,p.y); ctx.rotate(p.rot+p.life*3);
      ctx.fillRect(-p.size*.5,-p.size*.25,p.size,p.size*.5);
    } else {
      ctx.beginPath(); ctx.arc(p.x,p.y,p.size*p.life,0,Math.PI*2); ctx.fill();
    }
    ctx.restore();
  });
  ctx.globalAlpha=1;
  if(running){
    ctx.textAlign='center'; ctx.textBaseline='top';
    ctx.fillStyle='rgba(0,0,0,.25)'; ctx.font='bold 28px monospace';
    ctx.fillText(score,W/2+2,18);
    ctx.fillStyle='rgba(255,255,255,.88)'; ctx.font='bold 28px monospace';
    ctx.fillText(score,W/2,16);
  }
  if(screenFlash>0){
    ctx.fillStyle=`rgba(56,189,248,${screenFlash*.25})`;
    ctx.fillRect(0,0,W,H);
  }
  ctx.restore();
}

// ── Input ─────────────────────────────────────────────────────────
function flap(){
  if(!running || paused || isCountingDown) return;
  bird.vy=DIFF[diff].flap;
  bird.flapAnim=1;
  playFlap();
  if(navigator.vibrate) navigator.vibrate(12);
}

canvas.addEventListener('click', e=>{e.preventDefault(); flap();});
canvas.addEventListener('touchstart', e=>{e.preventDefault(); flap();},{passive:false});
document.addEventListener('keydown', e=>{
  if(e.key===' '||e.key==='ArrowUp'||e.key==='w'||e.key==='W'){flap(); e.preventDefault();}
  if(e.key==='p'||e.key==='P'){togglePause();}
});
document.getElementById('startBtn').onclick=startGame;
document.getElementById('resumeBtn').onclick=togglePause;
document.getElementById('pauseBtn').onclick=togglePause;
document.getElementById('back-link').onclick=()=>{if(running && score>0) saveScore(score);};

ctx.fillStyle='#050a14'; ctx.fillRect(0,0,W,H);
</script>
</body>
</html>