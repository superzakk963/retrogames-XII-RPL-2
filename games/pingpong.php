<?php
$gameSlug = 'pingpong';
require_once 'game_base.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
<title>PING PONG • RetroGames</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Share+Tech+Mono&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box;user-select:none}
:root{
  --blue:#4fc3f7;--red:#ef5350;--gold:#ffd54f;
  --panel:rgba(5,8,20,.72);--border:rgba(79,195,247,.18);
  --glow-blue:0 0 18px rgba(79,195,247,.55);
  --glow-red:0 0 18px rgba(239,83,80,.55);
}
html,body{width:100vw;height:100vh;overflow:hidden;background:#030810;font-family:'Orbitron',monospace}
#arena{
  position:fixed;inset:0;display:flex;align-items:center;justify-content:center;
  background:radial-gradient(ellipse 120% 120% at 50% 50%,#06102a 0%,#030810 70%);
}
#gameCanvas{
  display:block;border-radius:4px;
  box-shadow:0 0 0 1px rgba(79,195,247,.12),0 0 80px rgba(79,195,247,.08),0 0 200px rgba(79,195,247,.04);
}
#hud{
  position:fixed;top:0;left:0;right:0;z-index:20;
  display:flex;justify-content:center;align-items:stretch;
  pointer-events:none;height:74px;
}
.hud-side{
  display:flex;align-items:center;gap:14px;padding:0 28px;
  background:var(--panel);border-bottom:1px solid var(--border);
  backdrop-filter:blur(12px);min-width:220px;
}
.hs-p1{border-radius:0 0 0 0;justify-content:flex-start;border-right:1px solid var(--border)}
.hs-p2{justify-content:flex-end;border-left:1px solid var(--border)}
.hud-center{
  display:flex;flex-direction:column;align-items:center;justify-content:center;
  background:var(--panel);border-bottom:1px solid var(--border);
  backdrop-filter:blur(12px);padding:0 28px;gap:2px;
}
.player-name{font-size:9px;letter-spacing:3px;opacity:.45;text-transform:uppercase;font-family:'Share Tech Mono',monospace}
.score-num{font-size:42px;font-weight:900;line-height:1;letter-spacing:-1px}
.score-num.p1{color:var(--blue);text-shadow:var(--glow-blue)}
.score-num.p2{color:var(--red);text-shadow:var(--glow-red)}
.vs-badge{font-size:10px;letter-spacing:4px;color:rgba(255,255,255,.2);font-family:'Share Tech Mono',monospace}
.rally-wrap{text-align:center}
.rally-label{font-size:8px;letter-spacing:3px;color:rgba(255,255,255,.25);text-transform:uppercase;font-family:'Share Tech Mono',monospace}
.rally-val{font-size:16px;font-weight:700;color:#7fffd4;letter-spacing:2px;line-height:1.2}
#best-badge{
  position:fixed;top:82px;right:18px;z-index:18;
  background:var(--panel);border:1px solid var(--border);
  backdrop-filter:blur(10px);border-radius:10px;padding:8px 14px;
  pointer-events:none;text-align:right;
}
#best-badge .bl{font-size:8px;letter-spacing:3px;color:rgba(255,255,255,.25);text-transform:uppercase;font-family:'Share Tech Mono',monospace;margin-bottom:2px}
#best-badge .bv{font-size:14px;font-weight:700;color:var(--gold)}
#match-point{
  position:fixed;top:82px;left:50%;transform:translateX(-50%);z-index:25;
  background:rgba(0,0,0,.9);border:1px solid var(--gold);border-radius:40px;
  padding:6px 22px;font-size:10px;font-weight:700;letter-spacing:4px;
  color:var(--gold);text-transform:uppercase;display:none;
  animation:mpPulse .6s ease-in-out infinite alternate;
  font-family:'Share Tech Mono',monospace;
}
@keyframes mpPulse{from{box-shadow:0 0 6px var(--gold)}to{box-shadow:0 0 22px var(--gold),0 0 50px rgba(255,213,79,.25)}}
#lb{
  position:fixed;bottom:18px;left:18px;z-index:18;
  background:var(--panel);border:1px solid var(--border);
  backdrop-filter:blur(10px);border-radius:14px;padding:14px 18px;
  min-width:200px;pointer-events:none;
}
#lb h4{font-size:8px;letter-spacing:3px;color:rgba(79,195,247,.6);text-transform:uppercase;margin-bottom:10px;font-family:'Share Tech Mono',monospace}
.lbe{display:flex;justify-content:space-between;align-items:center;font-size:11px;padding:5px 0;border-bottom:1px solid rgba(79,195,247,.07)}
.lbe:last-child{border-bottom:none}
.lbr{color:rgba(255,255,255,.18);margin-right:6px;font-size:9px;font-family:'Share Tech Mono',monospace}
.lbn{color:rgba(255,255,255,.55)}
.lbs{color:var(--blue);font-weight:700;font-family:'Share Tech Mono',monospace}
#keys-hint{
  position:fixed;bottom:22px;left:50%;transform:translateX(-50%);z-index:10;
  color:rgba(255,255,255,.12);font-size:10px;letter-spacing:1px;
  pointer-events:none;white-space:nowrap;font-family:'Share Tech Mono',monospace;
}
#game-ctrl{
  position:fixed;bottom:18px;right:18px;z-index:20;
  display:flex;gap:8px;
}
.ctrl-btn{
  background:var(--panel);border:1px solid var(--border);
  color:rgba(79,195,247,.8);padding:9px 20px;border-radius:40px;
  cursor:pointer;font-size:11px;font-weight:700;letter-spacing:2px;
  transition:all .18s;text-transform:uppercase;font-family:'Orbitron',monospace;
  backdrop-filter:blur(10px);
}
.ctrl-btn:hover{background:rgba(79,195,247,.12);border-color:var(--blue);color:var(--blue);box-shadow:var(--glow-blue)}
#overlay{
  position:fixed;inset:0;background:rgba(3,8,16,.94);backdrop-filter:blur(18px);
  z-index:50;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:18px;
  overflow:hidden;
}
.ov-tag{font-size:9px;letter-spacing:6px;color:rgba(255,255,255,.2);text-transform:uppercase;font-family:'Share Tech Mono',monospace}
.ov-title{
  font-size:72px;font-weight:900;letter-spacing:6px;line-height:1;
  background:linear-gradient(135deg,var(--blue) 0%,#a5f3fc 40%,var(--red) 100%);
  -webkit-background-clip:text;background-clip:text;color:transparent;
  filter:drop-shadow(0 0 30px rgba(79,195,247,.4));
}
#ov-msg{
  color:rgba(255,255,255,.35);font-size:13px;text-align:center;
  max-width:400px;line-height:1.8;font-family:'Share Tech Mono',monospace;
  letter-spacing:.5px;
}
.mode-row{display:flex;gap:10px}
.mode-opt{
  padding:11px 26px;border-radius:40px;font-size:10px;font-weight:700;
  cursor:pointer;letter-spacing:2px;transition:all .2s;
  border:1px solid rgba(79,195,247,.2);color:rgba(255,255,255,.3);
  background:transparent;text-transform:uppercase;font-family:'Orbitron',monospace;
}
.mode-opt:hover{border-color:var(--blue);color:var(--blue)}
.mode-opt.active{border-color:var(--blue);color:var(--blue);background:rgba(79,195,247,.08);box-shadow:var(--glow-blue)}
.diff-wrap{display:flex;flex-direction:column;align-items:center;gap:8px}
.diff-label{font-size:8px;letter-spacing:3px;color:rgba(255,255,255,.2);text-transform:uppercase;font-family:'Share Tech Mono',monospace}
.diff-row{display:flex;gap:8px}
.diff-opt{
  padding:7px 18px;border-radius:40px;font-size:10px;font-weight:700;
  cursor:pointer;letter-spacing:1px;transition:all .18s;
  border:1px solid rgba(79,195,247,.15);color:rgba(255,255,255,.25);
  background:transparent;text-transform:uppercase;font-family:'Orbitron',monospace;
}
.diff-opt:hover{border-color:var(--blue);color:var(--blue)}
.diff-opt.active{border-color:var(--blue);color:var(--blue);background:rgba(79,195,247,.08)}
.btn-start{
  background:transparent;border:2px solid var(--blue);color:var(--blue);
  padding:14px 56px;font-size:12px;font-weight:700;letter-spacing:5px;
  cursor:pointer;border-radius:60px;transition:all .25s;text-transform:uppercase;
  font-family:'Orbitron',monospace;
}
.btn-start:hover{background:rgba(79,195,247,.12);box-shadow:var(--glow-blue),0 0 50px rgba(79,195,247,.15);transform:scale(1.03)}
#nav-home{
  position:fixed;top:82px;left:18px;z-index:18;
  background:var(--panel);border:1px solid var(--border);backdrop-filter:blur(10px);
  border-radius:40px;padding:8px 18px;
}
#nav-home a{color:rgba(79,195,247,.6);text-decoration:none;font-size:11px;letter-spacing:2px;font-family:'Share Tech Mono',monospace}
#nav-home a:hover{color:var(--blue)}
header, nav, footer, .navbar, .site-header, .site-footer,
.top-bar, #header, #footer, #navbar, .breadcrumb,
.game-header, .game-description, .page-title {
    display: none !important;
}
.game-section, .game-layout, .game-main,
.container, .wrapper, main, #main, #content {
    all: unset !important;
    display: block !important;
    position: static !important;
    margin: 0 !important; padding: 0 !important;
}
#countdown {
  position: fixed; top: 50%; left: 50%;
  transform: translate(-50%, -50%);
  font-family: 'Orbitron', monospace;
  font-size: 80px; font-weight: 900;
  color: var(--blue);
  text-shadow: 0 0 30px var(--blue);
  z-index: 100;
  pointer-events: none;
  display: none;
}
</style>
</head>
<body>
<div id="arena"><canvas id="gameCanvas"></canvas></div>
<div id="hud">
  <div class="hud-side hs-p1">
    <div class="score-num p1" id="hud-p1">0</div>
    <div><div class="player-name" id="p1-label">PLAYER 1</div></div>
  </div>
  <div class="hud-center">
    <div class="vs-badge">VS</div>
    <div class="rally-wrap"><div class="rally-label">RALLY</div><div class="rally-val" id="hud-rally">0</div></div>
  </div>
  <div class="hud-side hs-p2">
    <div><div class="player-name" id="p2-label">CPU</div></div>
    <div class="score-num p2" id="hud-p2">0</div>
  </div>
</div>
<div id="best-badge"><div class="bl">BEST</div><div class="bv" id="best-val"><?= number_format((int)$myBest) ?></div></div>
<div id="nav-home"><a href="../index.php">← HOME</a></div>
<div id="match-point">MATCH POINT ⚡</div>
<div id="lb">
  <h4>🏆 Top Scores</h4>
  <?php if(empty($topScores)): ?>
  <div class="lbe"><span class="lbn">No scores yet</span></div>
  <?php else: ?>
  <?php foreach($topScores as $i=>$s): ?>
  <div class="lbe">
    <span><span class="lbr">#<?= $i+1 ?></span><span class="lbn"><?= sanitize($s['username']) ?></span></span>
    <span class="lbs"><?= number_format($s['best_score']) ?></span>
  </div>
  <?php endforeach; ?>
  <?php endif; ?>
</div>
<div id="keys-hint">P1: W / S &nbsp;•&nbsp; P2 / CPU: ↑ ↓ &nbsp;•&nbsp; P = PAUSE</div>
<div id="game-ctrl"><button class="ctrl-btn" onclick="startGame()">↺ NEW</button><button class="ctrl-btn" id="pauseBtn" onclick="togglePause()">⏸ PAUSE</button></div>
<div id="overlay">
  <div class="ov-tag">⚡ retrogames ⚡</div>
  <div class="ov-title" id="overlayTitle">PING PONG</div>
  <div id="ov-msg">Pilih mode permainan dan mulai bertanding!</div>
  <div class="mode-row" id="modeSelector">
    <div class="mode-opt mode-opt-main active" onclick="selectMode(this,'cpu')">🤖 VS CPU</div>
    <div class="mode-opt mode-opt-main" onclick="selectMode(this,'2p')">👥 2 PLAYER</div>
  </div>
  <div id="cpu-only-options">
    <div class="diff-wrap" style="margin-bottom:10px;">
      <div class="diff-label">MATCH TYPE</div>
      <div class="diff-row">
        <div class="diff-opt match-opt active" onclick="selectMatchMode(this,'classic')">CLASSIC (7)</div>
        <div class="diff-opt match-opt" onclick="selectMatchMode(this,'endless')">ENDLESS</div>
      </div>
    </div>
    <div class="diff-wrap" id="diff-wrap">
      <div class="diff-label">CPU DIFFICULTY</div>
      <div class="diff-row">
        <div class="diff-opt active" onclick="selectDiff(this,'easy')">EASY</div>
        <div class="diff-opt" onclick="selectDiff(this,'normal')">NORMAL</div>
        <div class="diff-opt" onclick="selectDiff(this,'hard')">HARD</div>
      </div>
    </div>
  </div>
  <div style="display:flex; gap:12px; flex-wrap:wrap; justify-content:center;"><a href="../index.php" class="btn-start" style="text-decoration:none; display:inline-flex; align-items:center; gap:8px;">← BACK</a><button class="btn-start" id="startBtn">▶ START GAME</button><button class="btn-start" id="resumeBtn" style="display:none">RESUME</button></div>
</div>
<div id="countdown"></div>
<script>
const LOGGED_IN=<?= isLoggedIn()?'true':'false' ?>;
const SAVE_URL='../api/save_score.php';
let myBestScore=<?= (int)$myBest ?>;
const canvas=document.getElementById('gameCanvas');
const ctx=canvas.getContext('2d');
let W,H,PW,PH,BALL_R,scaleF;
const WIN_SCORE=7;
const CPU_SPEED={easy:2.5,normal:4.2,hard:6.8};
function resizeCanvas(){
  const vw=window.innerWidth,vh=window.innerHeight,hudH=74,avW=vw-4,avH=vh-hudH-4,ratio=3/2;
  if(avW/avH>ratio){H=Math.floor(avH);W=Math.floor(H*ratio);}else{W=Math.floor(avW);H=Math.floor(W/ratio);}
  W=Math.max(W,480);H=Math.max(H,320);
  canvas.width=W;canvas.height=H;
  scaleF=W/600;PW=Math.round(12*scaleF);PH=Math.round(72*scaleF);BALL_R=Math.round(8*scaleF);
  canvas.style.marginTop=hudH+'px';
}
resizeCanvas();
window.addEventListener('resize',()=>{resizeCanvas();if(!gameRunning)drawIdle();});
let mode='cpu',matchMode='classic',cpuDiff='easy';
let p1,p2,ball,score1,score2,rally,gameRunning,paused,animId,isCountingDown=false;
let particles=[];let startTime;let lastTime=0;
let shakeX=0,shakeY=0;
let audioCtx=null;
function getAudio(){if(!audioCtx)audioCtx=new(window.AudioContext||window.webkitAudioContext)();if(audioCtx.state==='suspended')audioCtx.resume();return audioCtx;}
function playBeep(f,d,v=0.13,t='sine'){try{const a=getAudio(),o=a.createOscillator(),g=a.createGain();o.connect(g);g.connect(a.destination);o.type=t;o.frequency.value=f;g.gain.value=v;g.gain.exponentialRampToValueAtTime(.0001,a.currentTime+d);o.start();o.stop(a.currentTime+d);}catch(e){}}
function playHit(side){playBeep(side==='p1'?640:520,.06,.14,'square');}
function playWall(){playBeep(880,.04,.08,'square');}
function playScore(){playBeep(280,.32,.18,'sawtooth');}
function selectMode(el,m){ 
  document.querySelectorAll('.mode-opt-main').forEach(e=>e.classList.remove('active')); 
  el.classList.add('active'); mode=m; 
  document.getElementById('cpu-only-options').style.display=m==='cpu'?'block':'none'; 
  document.getElementById('p2-label').textContent=m==='cpu'?'CPU':'PLAYER 2'; 
}
function selectMatchMode(el,m){
  document.querySelectorAll('.match-opt').forEach(e=>e.classList.remove('active'));
  el.classList.add('active'); matchMode=m;
}
function selectDiff(el,d){ document.querySelectorAll('.diff-opt').forEach(e=>e.classList.remove('active')); el.classList.add('active'); cpuDiff=d; }
function resetBall(){ const angle=(Math.random()*.5-.25)+(Math.random()<.5?0:Math.PI), spd=(5+Math.min(rally*.14,3.5))*scaleF; ball={x:W/2,y:H/2,vx:Math.cos(angle)*spd,vy:Math.sin(angle)*spd,trail:[]}; }
function runCountdown(callback, speed = 800) {
  const el = document.getElementById('countdown');
  el.style.display = 'block'; isCountingDown = true;
  let count = 3;
  el.textContent = count;
  playBeep(600, 0.1);
  const interval = setInterval(() => {
    count--;
    if (count > 0) { el.textContent = count; playBeep(600, 0.1); }
    else if (count === 0) { el.textContent = 'GO!'; playBeep(900, 0.1); }
    else { clearInterval(interval); el.style.display = 'none'; isCountingDown = false; callback(); }
  }, speed);
}
function startGame(){
  if(gameRunning && score1 > 0) handleUnload();
  if(animId)cancelAnimationFrame(animId);
  p1={y:H/2-PH/2};p2={y:H/2-PH/2};
  score1=0;score2=0;rally=0;
  gameRunning=true;paused=false;particles=[];shakeX=0;shakeY=0;
  startTime=Date.now(); resetBall();
  document.getElementById('hud-p1').textContent='0'; document.getElementById('hud-p2').textContent='0'; document.getElementById('hud-rally').textContent='0';
  document.getElementById('overlay').style.display='none';
  document.getElementById('match-point').style.display='none';
  document.getElementById('pauseBtn').textContent='⏸ PAUSE';
  getAudio();
  runCountdown(() => { lastTime=performance.now(); animId=requestAnimationFrame(loop); }, 800);
}
function togglePause(){
  if(!gameRunning || isCountingDown)return;
  paused=!paused;
  const btn=document.getElementById('pauseBtn'), overlay = document.getElementById('overlay'), title = document.getElementById('overlayTitle'), msg = document.getElementById('ov-msg'), modeSel = document.getElementById('modeSelector'), cpuOpts = document.getElementById('cpu-only-options'), startBtn = document.getElementById('startBtn'), resumeBtn = document.getElementById('resumeBtn');
  if(!paused){
    overlay.style.display='none';
    runCountdown(() => { lastTime=performance.now(); animId=requestAnimationFrame(loop); }, 800);
    btn.textContent='⏸ PAUSE';
  } else {
    cancelAnimationFrame(animId);
    btn.textContent='▶ RESUME';
    title.textContent = 'PAUSED';
    msg.textContent='Permainan dihentikan sejenak';
    modeSel.style.display = 'none'; cpuOpts.style.display = 'none'; startBtn.style.display = 'none'; resumeBtn.style.display = 'inline-flex';
    overlay.style.display = 'flex';
  }
}
const keys={};
document.addEventListener('keydown',e=>{ keys[e.key]=true; if(e.key==='p'||e.key==='P')togglePause(); if(['ArrowUp','ArrowDown',' '].includes(e.key))e.preventDefault(); });
document.addEventListener('keyup',e=>{keys[e.key]=false;});
function loop(ts){
  if(!gameRunning||paused)return;
  const dt=Math.min((ts-lastTime)/16.667,3);lastTime=ts;
  if(!isCountingDown) update(dt);
  draw();
  animId=requestAnimationFrame(loop);
}
function update(dt){
  const spd=6.5*scaleF*dt;
  if(keys['w']||keys['W'])p1.y=Math.max(0,p1.y-spd); if(keys['s']||keys['S'])p1.y=Math.min(H-PH,p1.y+spd);
  if(mode==='2p'){ if(keys['ArrowUp'])p2.y=Math.max(0,p2.y-spd); if(keys['ArrowDown'])p2.y=Math.min(H-PH,p2.y+spd); } else { const cs=CPU_SPEED[cpuDiff]*scaleF*dt, target=ball.y-PH/2; if(Math.abs(p2.y-target)>1)p2.y+=Math.sign(target-p2.y)*Math.min(cs,Math.abs(target-p2.y)); p2.y=Math.max(0,Math.min(H-PH,p2.y)); }
  ball.trail.push({x:ball.x,y:ball.y}); if(ball.trail.length>16)ball.trail.shift();
  ball.x+=ball.vx*dt;ball.y+=ball.vy*dt;
  if(ball.y-BALL_R<0){ball.y=BALL_R;ball.vy=Math.abs(ball.vy);playWall();spawnSparks(ball.x,ball.y,'#fff',5);}
  if(ball.y+BALL_R>H){ball.y=H-BALL_R;ball.vy=-Math.abs(ball.vy);playWall();spawnSparks(ball.x,ball.y,'#fff',5);}
  if(rally>8){const amt=Math.min((rally-8)*.04,1.2);shakeX=(Math.random()-.5)*amt;shakeY=(Math.random()-.5)*amt;} else{shakeX*=.85;shakeY*=.85;}
  const p1x=PW+Math.round(10*scaleF);
  if(ball.x-BALL_R<p1x&&ball.x>PW&&ball.y>=p1.y-2&&ball.y<=p1.y+PH+2){ const rel=(ball.y-(p1.y+PH/2))/(PH/2), angle=rel*1.1, spd2=Math.min(Math.hypot(ball.vx,ball.vy)+.3*scaleF,14*scaleF); ball.vx=Math.abs(Math.cos(angle)*spd2); ball.vy=Math.sin(angle)*spd2; ball.x=p1x+BALL_R; rally++;document.getElementById('hud-rally').textContent=rally; playHit('p1');spawnSparks(ball.x,ball.y,'#4fc3f7',16); shakeX=2;shakeY=.6;checkMatchPoint(); }
  const p2x=W-PW-Math.round(10*scaleF);
  if(ball.x+BALL_R>p2x&&ball.x<W-PW&&ball.y>=p2.y-2&&ball.y<=p2.y+PH+2){ const rel=(ball.y-(p2.y+PH/2))/(PH/2), angle=Math.PI-rel*1.1, spd2=Math.min(Math.hypot(ball.vx,ball.vy)+.3*scaleF,14*scaleF); ball.vx=-Math.abs(Math.cos(angle)*spd2); ball.vy=Math.sin(angle)*spd2; ball.x=p2x-BALL_R; rally++;document.getElementById('hud-rally').textContent=rally; playHit('p2');spawnSparks(ball.x,ball.y,'#ef5350',16); shakeX=-2;shakeY=.6;checkMatchPoint(); }
  if(ball.x<0){
    score2++;document.getElementById('hud-p2').textContent=score2;
    rally=0;playScore();spawnSparks(W*.08,H/2,'#ef5350',24);
    if(score2>=WIN_SCORE){endGame(2);return;}
    ball.x=W/2; ball.y=H/2; ball.vx=0; ball.vy=0;
    runCountdown(() => { resetBall(); }, 400);
    checkMatchPoint();
  }
  if(ball.x>W){
    score1++;document.getElementById('hud-p1').textContent=score1;
    rally=0;playScore();spawnSparks(W*.92,H/2,'#4fc3f7',24);
    if(matchMode==='classic' && score1>=WIN_SCORE){endGame(1);return;}
    ball.x=W/2; ball.y=H/2; ball.vx=0; ball.vy=0;
    runCountdown(() => { resetBall(); }, 400);
    checkMatchPoint();
  }
  particles.forEach(p=>{p.x+=p.vx*dt;p.y+=p.vy*dt;p.vy+=.12*dt;p.life-=.028*dt;}); particles=particles.filter(p=>p.life>0);
}
function checkMatchPoint(){ 
  if(matchMode==='endless') {
    document.getElementById('match-point').style.display=score2>=WIN_SCORE-1?'block':'none';
    document.getElementById('match-point').textContent='SURVIVAL MODE ⚡';
    return;
  }
  const mp=score1>=WIN_SCORE-1||score2>=WIN_SCORE-1; 
  document.getElementById('match-point').style.display=mp?'block':'none'; 
  document.getElementById('match-point').textContent='MATCH POINT ⚡';
}
function spawnSparks(cx,cy,color,n=12){ for(let i=0;i<n;i++){ const a=Math.random()*Math.PI*2,sp=(2+Math.random()*6)*scaleF; particles.push({x:cx,y:cy,vx:Math.cos(a)*sp,vy:Math.sin(a)*sp,life:1,color,size:(1.5+Math.random()*3)*scaleF}); } }
function endGame(winner){
  gameRunning=false;cancelAnimationFrame(animId);
  document.getElementById('match-point').style.display='none';
  const dur=Math.floor((Date.now()-startTime)/1000), winScore=score1;
  const winTxt=(matchMode==='endless') ? (score1 >= 10 ? 'SKOR LUAR BIASA! 🌟' : 'PERMAINAN BERAKHIR') : (winner===1 ?(mode==='2p'?'PLAYER 1 MENANG! 🏆':'KAMU MENANG! 🏆') :(mode==='2p'?'PLAYER 2 MENANG! 🏆':'CPU MENANG! 🤖'));
  document.getElementById('ov-msg').innerHTML=`${winTxt}<br><span style="color:#4fc3f7;font-size:22px;font-weight:900">${score1}</span><span style="opacity:.4"> — </span><span style="color:#ef5350;font-size:22px;font-weight:900">${score2}</span>`;
  document.getElementById('startBtn').style.display=''; document.getElementById('startBtn').textContent='▶ MAIN LAGI';
  document.getElementById('overlayTitle').textContent = 'PING PONG';
  document.getElementById('modeSelector').style.display = 'flex';
  document.getElementById('cpu-only-options').style.display = mode==='cpu'?'block':'none';
  document.getElementById('resumeBtn').style.display = 'none';
  document.getElementById('overlay').style.display='flex';
  if(LOGGED_IN&&winScore>0)saveScore(winScore,1,dur);
}
async function saveScore(s,lv,dur){ 
  if(!LOGGED_IN || s <= 0) return;
  try{ 
    const r=await fetch(SAVE_URL,{
      method:'POST',
      keepalive: true,
      headers:{'Content-Type':'application/json'},
      body:JSON.stringify({game:'pingpong',score:s,level:lv,duration:dur})
    }); 
    const d=await r.json(); 
    if(d.success){
      myBestScore=d.best_score;
      const el = document.getElementById('best-val');
      if(el) el.textContent=d.best_score.toLocaleString();
    } 
  }catch(e){} 
}

function handleUnload() {
  if (gameRunning && score1 > 0) {
    const dur = Math.floor((Date.now() - startTime) / 1000);
    const data = JSON.stringify({game:'pingpong', score:score1, level:1, duration:dur});
    const blob = new Blob([data], {type: 'application/json'});
    navigator.sendBeacon(SAVE_URL, blob);
  }
}
window.addEventListener('beforeunload', handleUnload);

// Update navigation links to save before leaving
document.querySelectorAll('a[href="../index.php"]').forEach(link => {
  link.addEventListener('click', (e) => {
    if (gameRunning && score1 > 0) {
      handleUnload();
      gameRunning = false; // Prevent double save
    }
  });
});

function drawIdle(){ ctx.fillStyle='#05081a';ctx.fillRect(0,0,W,H); drawBG(); }
function draw(){
  ctx.save();ctx.translate(shakeX,shakeY); ctx.fillStyle='#030810';ctx.fillRect(-4,-4,W+8,H+8); drawBG();
  drawPaddle(Math.round(6*scaleF),p1.y,'#93c5fd','#1d4ed8','#4fc3f7');
  drawPaddle(W-PW-Math.round(6*scaleF),p2.y,'#fca5a5','#b91c1c','#ef5350');
  const trailCol=ball.vx>0?'79,195,247':'239,83,80'; ball.trail.forEach((t,i)=>{ const frac=i/ball.trail.length; ctx.globalAlpha=frac*0.45; ctx.fillStyle=`rgba(${trailCol},1)`; ctx.beginPath();ctx.arc(t.x,t.y,BALL_R*frac*.85,0,Math.PI*2);ctx.fill(); });ctx.globalAlpha=1;
  const spd=Math.hypot(ball.vx,ball.vy); ctx.shadowBlur=24+spd*.4;ctx.shadowColor='#fff'; const bgrad=ctx.createRadialGradient(ball.x-BALL_R*.35,ball.y-BALL_R*.35,BALL_R*.1,ball.x,ball.y,BALL_R); bgrad.addColorStop(0,'#fff');bgrad.addColorStop(1,'#a5f3fc'); ctx.fillStyle=bgrad;ctx.beginPath();ctx.arc(ball.x,ball.y,BALL_R,0,Math.PI*2);ctx.fill(); ctx.shadowBlur=0;
  particles.forEach(p=>{ ctx.globalAlpha=Math.max(0,p.life*.9); ctx.fillStyle=p.color; ctx.beginPath();ctx.arc(p.x,p.y,Math.max(0,p.size*p.life),0,Math.PI*2);ctx.fill(); });ctx.globalAlpha=1; ctx.restore();
}
function drawBG(){
  const rg=ctx.createRadialGradient(W/2,H/2,0,W/2,H/2,W*.7); rg.addColorStop(0,'rgba(10,20,60,.55)');rg.addColorStop(1,'transparent'); ctx.fillStyle=rg;ctx.fillRect(0,0,W,H);
  ctx.setLineDash([Math.round(10*scaleF),Math.round(8*scaleF)]); ctx.strokeStyle='rgba(79,195,247,.1)';ctx.lineWidth=Math.max(1,Math.round(2*scaleF)); ctx.beginPath();ctx.moveTo(W/2,0);ctx.lineTo(W/2,H);ctx.stroke();ctx.setLineDash([]);
  ctx.strokeStyle='rgba(79,195,247,.05)';ctx.lineWidth=1; ctx.beginPath();ctx.arc(W/2,H/2,Math.round(65*scaleF),0,Math.PI*2);ctx.stroke();
  const lw=Math.round(36*scaleF), lg1=ctx.createLinearGradient(0,0,lw,0); lg1.addColorStop(0,`rgba(79,195,247,${.04+score1/WIN_SCORE*.07})`);lg1.addColorStop(1,'transparent'); ctx.fillStyle=lg1;ctx.fillRect(0,0,lw,H);
  const lg2=ctx.createLinearGradient(W,0,W-lw,0); lg2.addColorStop(0,`rgba(239,83,80,${.04+score2/WIN_SCORE*.07})`);lg2.addColorStop(1,'transparent'); ctx.fillStyle=lg2;ctx.fillRect(W-lw,0,lw,H);
}
function drawPaddle(x,y,top,bot,glowCol){ ctx.shadowBlur=18;ctx.shadowColor=glowCol; const g=ctx.createLinearGradient(x,y,x,y+PH); g.addColorStop(0,top);g.addColorStop(1,bot); ctx.fillStyle=g; ctx.beginPath(); const r=Math.round(6*scaleF); ctx.roundRect(x,y,PW,PH,r); ctx.fill(); ctx.shadowBlur=0; ctx.fillStyle='rgba(255,255,255,.18)'; ctx.fillRect(x+Math.round(2*scaleF),y+Math.round(4*scaleF),Math.round(4*scaleF),PH*.38); }
document.getElementById('startBtn').onclick=startGame;
document.getElementById('resumeBtn').onclick=togglePause;
drawIdle();
</script>
</body>
</html>