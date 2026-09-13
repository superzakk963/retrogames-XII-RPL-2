<?php
require_once '../includes/auth.php';
$myBest = 0;
if (isLoggedIn()) {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT MAX(s.score) AS best FROM scores s JOIN games g ON s.game_id = g.id WHERE s.user_id = ? AND g.slug = 'tetris' AND s.deleted_at IS NULL");
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
<title>TETRIS • RetroGames</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Share+Tech+Mono&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box;user-select:none}
:root{
  --purple:#c084fc;--pink:#f472b6;--gold:#fbbf24;
  --panel:rgba(5,2,18,.75);--border:rgba(192,132,252,.18);
  --glow:0 0 20px rgba(192,132,252,.45);
}
html,body{ width:100vw;height:100vh;overflow:hidden; background:#050212;font-family:'Orbitron',monospace; }
#arena{ position:fixed;inset:0; background:radial-gradient(ellipse 100% 120% at 50% 60%,#0d0426 0%,#050212 75%); }
#layout{ position:fixed;inset:0; display:flex;align-items:center;justify-content:center; gap:16px; }
#sidebar-left,#sidebar-right{ display:flex;flex-direction:column;gap:10px; width:150px;flex-shrink:0; }
#canvas-wrap{ position:relative;flex-shrink:0; display:flex;align-items:center;justify-content:center; }
#gameCanvas{ display:block;border-radius:4px; box-shadow:0 0 0 1px rgba(192,132,252,.15), 0 0 60px rgba(168,85,247,.12), 0 0 140px rgba(168,85,247,.05); }
.panel{ background:var(--panel);border:1px solid var(--border); backdrop-filter:blur(12px);border-radius:12px;padding:12px 14px; }
.panel-label{ font-size:7px;letter-spacing:3px;color:rgba(192,132,252,.4); text-transform:uppercase;margin-bottom:4px; font-family:'Share Tech Mono',monospace; }
.panel-val{ font-size:26px;font-weight:900;color:var(--purple);line-height:1; text-shadow:0 0 18px rgba(192,132,252,.6);letter-spacing:-1px; }
.panel-val-sm{ font-size:15px;font-weight:700;color:var(--purple); font-family:'Share Tech Mono',monospace; }
#next-panel{text-align:center}
#nextCanvas{display:block;margin:6px auto 0}
#combo-display{ text-align:center;padding:10px 12px; background:var(--panel);border:1px solid rgba(251,191,36,.25); border-radius:12px;backdrop-filter:blur(12px); transition:opacity .3s; }
#combo-display .cv{ font-size:18px;font-weight:900;color:var(--gold); text-shadow:0 0 16px rgba(251,191,36,.5); font-family:'Share Tech Mono',monospace; }
#nav-home{ position:fixed;top:14px;left:14px;z-index:18; background:var(--panel);border:1px solid var(--border); backdrop-filter:blur(10px);border-radius:40px;padding:7px 16px; }
#nav-home a{color:rgba(192,132,252,.6);text-decoration:none;font-size:10px;letter-spacing:2px;font-family:'Share Tech Mono',monospace}
#nav-home a:hover{color:var(--purple)}
#game-ctrl{ position:fixed;bottom:14px;right:14px;z-index:20;display:flex;gap:8px; }
/* Playtime widget */
#playtime-widget{position:fixed;bottom:14px;left:14px;z-index:18;display:flex;align-items:center;gap:7px;background:var(--panel);border:1px solid color-mix(in srgb,var(--pt-accent,#c084fc) 35%,transparent);border-radius:40px;padding:6px 14px;pointer-events:none;backdrop-filter:blur(10px);font-family:'Share Tech Mono','Orbitron',monospace}
#playtime-widget .pt-icon{font-size:11px;filter:drop-shadow(0 0 6px var(--pt-accent,#c084fc))}
#playtime-widget .pt-label{font-size:8px;letter-spacing:2px;color:color-mix(in srgb,var(--pt-accent,#c084fc) 55%,transparent);text-transform:uppercase}
#playtime-widget .pt-value{font-size:12px;font-weight:700;letter-spacing:1px;color:var(--pt-accent,#c084fc);text-shadow:0 0 10px color-mix(in srgb,var(--pt-accent,#c084fc) 60%,transparent);min-width:38px;text-align:right}
.ctrl-btn{ background:var(--panel);border:1px solid var(--border); color:rgba(192,132,252,.8);padding:7px 16px;border-radius:40px; cursor:pointer;font-size:9px;font-weight:700;letter-spacing:2px; transition:all .18s;text-transform:uppercase; font-family:'Orbitron',monospace;backdrop-filter:blur(10px); }
.ctrl-btn:hover{background:rgba(192,132,252,.1);border-color:var(--purple);color:var(--purple);box-shadow:var(--glow)}
#keys-hint{ position:fixed;bottom:18px;left:50%;transform:translateX(-50%); color:rgba(255,255,255,.1);font-size:8px;letter-spacing:1px; pointer-events:none;white-space:nowrap;font-family:'Share Tech Mono',monospace;z-index:10; }
#overlay{ position:fixed;inset:0;background:rgba(5,2,18,.95);backdrop-filter:blur(18px); z-index:50;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:16px; }
.ov-tag{font-size:8px;letter-spacing:6px;color:rgba(255,255,255,.18);text-transform:uppercase;font-family:'Share Tech Mono',monospace}
.ov-title{ font-size:72px;font-weight:900;letter-spacing:8px;line-height:1; background:linear-gradient(135deg,var(--purple) 0%,var(--pink) 100%); -webkit-background-clip:text;background-clip:text;color:transparent; filter:drop-shadow(0 0 32px rgba(192,132,252,.5)); }
#ov-msg{ color:rgba(255,255,255,.3);font-size:11px;text-align:center; max-width:360px;line-height:1.9;font-family:'Share Tech Mono',monospace;letter-spacing:.5px; }
.lvl-row{display:flex;gap:8px}
.lvl-opt{ padding:8px 20px;border-radius:40px;font-size:8px;font-weight:700; cursor:pointer;letter-spacing:2px;transition:all .2s; border:1px solid rgba(192,132,252,.2);color:rgba(255,255,255,.25); background:transparent;text-transform:uppercase;font-family:'Orbitron',monospace; }
.lvl-opt:hover{border-color:var(--purple);color:var(--purple)}
.lvl-opt.active{border-color:var(--purple);color:var(--purple);background:rgba(192,132,252,.08);box-shadow:var(--glow)}
.btn-start{ background:transparent;border:2px solid var(--purple);color:var(--purple); padding:13px 52px;font-size:10px;font-weight:700;letter-spacing:5px; cursor:pointer;border-radius:60px;transition:all .25s;text-transform:uppercase; font-family:'Orbitron',monospace; }
.btn-start:hover{background:rgba(192,132,252,.1);box-shadow:var(--glow),0 0 60px rgba(192,132,252,.15);transform:scale(1.03)}
#flash{position:fixed;inset:0;pointer-events:none;z-index:30;opacity:0;background:rgba(192,132,252,.12);transition:opacity .08s}
#countdown { position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); font-family: 'Orbitron', monospace; font-size: 80px; font-weight: 900; color: var(--purple); text-shadow: 0 0 30px var(--purple); z-index: 100; pointer-events: none; display: none; }
</style>
</head>
<body>
<div id="arena"></div>
<div id="flash"></div>
<div id="layout">
  <div id="sidebar-left">
    <div class="panel"><div class="panel-label">SCORE</div><div class="panel-val" id="hud-score">0</div></div>
    <div class="panel"><div class="panel-label">BEST</div><div class="panel-val-sm" id="hud-best"><?= number_format((int)$myBest) ?></div></div>
    <div class="panel"><div class="panel-label">LEVEL</div><div class="panel-val-sm" id="hud-level">1</div></div>
    <div class="panel"><div class="panel-label">LINES</div><div class="panel-val-sm" id="hud-lines">0</div></div>
    <div id="combo-display" style="opacity:0"><div class="panel-label">COMBO</div><div class="cv" id="hud-combo">×1</div></div>
  </div>
  <div id="canvas-wrap"><canvas id="gameCanvas"></canvas></div>
  <div id="sidebar-right"><div class="panel" id="next-panel"><div class="panel-label">NEXT</div><canvas id="nextCanvas" width="104" height="104"></canvas></div></div>
</div>
<div id="nav-home"><a href="../index.php" onclick="if(gameRunning && score > 0) saveScore(score, level, Math.floor((Date.now()-startTime)/1000))">← HOME</a></div>
<div id="keys-hint">← → GERAK &nbsp;•&nbsp; ↑ ROTATE &nbsp;•&nbsp; ↓ SOFT DROP &nbsp;•&nbsp; SPACE HARD DROP &nbsp;•&nbsp; P PAUSE</div>
<div id="game-ctrl"><button class="ctrl-btn" onclick="startGame()">↺ NEW</button><button class="ctrl-btn" id="pauseBtn" onclick="togglePause()">⏸ PAUSE</button></div>
<div id="overlay">
  <div class="ov-tag">⚡ retrogames ⚡</div>
  <div class="ov-title" id="overlayTitle">TETRIS</div>
  <div id="ov-msg">Susun blok, bersihkan baris, raih skor tertinggi!<br>4 baris sekaligus = TETRIS 🎉</div>
  <div class="lvl-row" id="lvlSelectors"><div class="lvl-opt active" onclick="selectLevel(this,1)">LVL 1</div><div class="lvl-opt" onclick="selectLevel(this,5)">LVL 5</div><div class="lvl-opt" onclick="selectLevel(this,10)">LVL 10</div></div>
  <div style="display:flex; gap:12px; flex-wrap:wrap; justify-content:center;"><a href="../index.php" class="btn-start" style="text-decoration:none; display:inline-flex; align-items:center; gap:8px;" onclick="if(gameRunning && score > 0) saveScore(score, level, Math.floor((Date.now()-startTime)/1000))">← BACK</a><button class="btn-start" id="startBtn">▶ START GAME</button><button class="btn-start" id="resumeBtn" style="display:none">RESUME</button></div>
</div>
<div id="countdown"></div>
<script src="../assets/playtime.js"></script>
<script>
const LOGGED_IN=<?= isLoggedIn()?'true':'false' ?>;
const SAVE_URL = '../api/save_score.php';
const canvas=document.getElementById('gameCanvas');
const ctx=canvas.getContext('2d');
const ncan=document.getElementById('nextCanvas');
const nctx=ncan.getContext('2d');
const COLS=10,ROWS=20;
let CELL,W,H;
function resize(){
  const vh=window.innerHeight,vw=window.innerWidth;
  const sideW=150,gap=16,totalSides=2*(sideW+gap)+32;
  const availW=vw-totalSides;
  const availH=vh-32;
  const byW=Math.floor(availW/COLS);
  const byH=Math.floor(availH/ROWS);
  CELL=Math.max(20,Math.min(byW,byH,44));
  W=CELL*COLS; H=CELL*ROWS;
  canvas.width=W; canvas.height=H;
}
window.addEventListener('resize',resize);
const PIECES={
  I:{shape:[[0,0,0,0],[1,1,1,1],[0,0,0,0],[0,0,0,0]],color:'#22d3ee',glow:'#06b6d4'},
  O:{shape:[[1,1],[1,1]],color:'#fde047',glow:'#ca8a04'},
  T:{shape:[[0,1,0],[1,1,1],[0,0,0]],color:'#c084fc',glow:'#a855f7'},
  S:{shape:[[0,1,1],[1,1,0],[0,0,0]],color:'#4ade80',glow:'#16a34a'},
  Z:{shape:[[1,1,0],[0,1,1],[0,0,0]],color:'#f87171',glow:'#dc2626'},
  J:{shape:[[1,0,0],[1,1,1],[0,0,0]],color:'#60a5fa',glow:'#2563eb'},
  L:{shape:[[0,0,1],[1,1,1],[0,0,0]],color:'#fb923c',glow:'#ea580c'},
};
const PNAMES=Object.keys(PIECES);
const SCORE_TABLE=[0,100,300,500,800];
const SPEEDS=[800,700,600,500,400,300,220,150,100,80,65,55,45,38,30];
let board,current,next,score,level,lines,gameRunning,paused,combo,animId,lastDrop,startTime,startLevel=1,isCountingDown=false;
let particles=[];
let lockDelay=false;
function selectLevel(el,l){ document.querySelectorAll('.lvl-opt').forEach(e=>e.classList.remove('active')); el.classList.add('active'); startLevel=l; }
function randomPiece(){
  const n=PNAMES[Math.floor(Math.random()*PNAMES.length)];
  const p=PIECES[n];
  return{ shape:p.shape.map(r=>[...r]), color:p.color,glow:p.glow, x:Math.floor(COLS/2)-Math.floor(p.shape[0].length/2), y:0 };
}
function isValid(sh,px,py){
  for(let r=0;r<sh.length;r++) for(let c=0;c<sh[r].length;c++){
    if(!sh[r][c]) continue;
    const nx=px+c, ny=py+r;
    if(nx<0||nx>=COLS||ny>=ROWS) return false;
    if(ny>=0&&board[ny][nx]) return false;
  }
  return true;
}
function rotate(sh){
  const N=sh.length,M=sh[0].length;
  const rot=Array.from({length:M},()=>Array(N).fill(0));
  for(let r=0;r<N;r++) for(let c=0;c<M;c++) rot[c][N-1-r]=sh[r][c];
  return rot;
}
function calcGhost(){ let gy=current.y; while(isValid(current.shape,current.x,gy+1)) gy++; return gy; }
function isOnGround(){ return !isValid(current.shape,current.x,current.y+1); }
function runCountdown(callback) {
  const el = document.getElementById('countdown');
  el.style.display = 'block'; isCountingDown = true;
  let count = 3;
  el.textContent = count;
  const interval = setInterval(() => {
    count--;
    if (count > 0) { el.textContent = count; }
    else if (count === 0) { el.textContent = 'GO!'; }
    else { clearInterval(interval); el.style.display = 'none'; isCountingDown = false; callback(); }
  }, 800);
}
function startGame(){
  if(animId) cancelAnimationFrame(animId);
  board=Array.from({length:ROWS},()=>Array(COLS).fill(null));
  level=startLevel; score=0; lines=0; combo=0;
  particles=[]; gameRunning=true; paused=false; lockDelay=false;
  current=randomPiece(); next=randomPiece();
  lastDrop=performance.now(); startTime=Date.now();
  document.getElementById('overlay').style.display='none';
  document.getElementById('pauseBtn').textContent='⏸ PAUSE';
  document.getElementById('combo-display').style.opacity='0';
  updateHUD(); drawNext();
  runCountdown(() => { if(window.Playtime)Playtime.onGameStart(); lastDrop=performance.now(); animId=requestAnimationFrame(gameLoop); });
}
function togglePause(){
  if(!gameRunning || isCountingDown) return;
  paused=!paused;
  const btn=document.getElementById('pauseBtn'), overlay = document.getElementById('overlay'), title = document.getElementById('overlayTitle'), msg = document.getElementById('ov-msg'), lvls = document.getElementById('lvlSelectors'), startBtn = document.getElementById('startBtn'), resumeBtn = document.getElementById('resumeBtn');
  if(!paused){
    overlay.style.display='none';
    runCountdown(() => { if(window.Playtime)Playtime.onGameResume(); lastDrop=performance.now(); animId=requestAnimationFrame(gameLoop); });
    btn.textContent='⏸ PAUSE';
  }else{
    cancelAnimationFrame(animId);
    btn.textContent='▶ RESUME';
    if(window.Playtime)Playtime.onGamePause();
    title.textContent = 'PAUSED';
    msg.textContent = 'Permainan dihentikan sejenak';
    lvls.style.display = 'none';
    startBtn.style.display = 'none';
    resumeBtn.style.display = 'inline-flex';
    overlay.style.display = 'flex';
  }
}
function gameLoop(ts){
  if(!gameRunning||paused||isCountingDown) return;
  const sp=SPEEDS[Math.min(level-1,SPEEDS.length-1)];
  if(ts-lastDrop>sp){ dropPiece(); lastDrop=ts; }
  drawAll();
  animId=requestAnimationFrame(gameLoop);
}
function dropPiece(){ if(isValid(current.shape,current.x,current.y+1)){ current.y++; lockDelay=false; }else{ placePiece(); } }
function hardDrop(){ let d=0; while(isValid(current.shape,current.x,current.y+1)){ current.y++; d++; } score+=d*2; placePiece(); updateHUD(); }
function placePiece(){
  for(let r=0;r<current.shape.length;r++) for(let c=0;c<current.shape[r].length;c++){ if(!current.shape[r][c]) continue; const ny=current.y+r; if(ny<0){ endGame(); return; } board[ny][current.x+c]={color:current.color,glow:current.glow}; }
  const cl=clearLines();
  if(cl>0){ combo++; const bs=SCORE_TABLE[cl]*level+(combo>1?combo*60:0); score+=bs; lines+=cl; level=Math.max(startLevel,Math.floor(lines/10)+startLevel); spawnLineClear(); flashScreen(); }else{ combo=0; }
  document.getElementById('combo-display').style.opacity=combo>1?'1':'0';
  lockDelay=false; current=next; next=randomPiece(); drawNext();
  if(!isValid(current.shape,current.x,current.y)){ endGame(); return; }
  updateHUD();
}
function clearLines(){ let cl=0; for(let r=ROWS-1;r>=0;r--){ if(board[r].every(c=>c!==null)){ board.splice(r,1); board.unshift(Array(COLS).fill(null)); cl++; r++; } } return cl; }
function spawnLineClear(){ for(let i=0;i<40;i++){ const a=Math.random()*Math.PI*2, sp=2+Math.random()*9; particles.push({ x:Math.random()*W, y:Math.random()*H, vx:Math.cos(a)*sp, vy:Math.sin(a)*sp, life:1, color:current.color, size:2+Math.random()*5 }); } }
function flashScreen(){ const f=document.getElementById('flash'); f.style.opacity='1'; setTimeout(()=>f.style.opacity='0',80); }
function endGame(){
  gameRunning=false; cancelAnimationFrame(animId);
  if(window.Playtime)Playtime.onGameEnd();
  const dur=Math.floor((Date.now()-startTime)/1000);
  document.getElementById('ov-msg').innerHTML=`GAME OVER<br>Score: <strong style="color:#c084fc;font-size:18px">${score.toLocaleString()}</strong>&nbsp;•&nbsp; Level: ${level} &nbsp;•&nbsp; Lines: ${lines}`;
  document.getElementById('startBtn').style.display=''; document.getElementById('startBtn').textContent='▶ MAIN LAGI';
  document.getElementById('overlayTitle').textContent = 'TETRIS';
  document.getElementById('lvlSelectors').style.display = 'flex';
  document.getElementById('resumeBtn').style.display = 'none';
  document.getElementById('overlay').style.display='flex';
  if(LOGGED_IN) saveScore(score,level,dur);
}
function shadeColor(hex,f){ const r=parseInt(hex.slice(1,3),16), g=parseInt(hex.slice(3,5),16), b=parseInt(hex.slice(5,7),16); return`rgb(${Math.round(r*f)},${Math.round(g*f)},${Math.round(b*f)})`; }
function drawCell(cx,x,y,color,alpha=1){
  const px=x*CELL, py=y*CELL; cx.globalAlpha=alpha; const g=cx.createLinearGradient(px,py,px+CELL,py+CELL); g.addColorStop(0,color); g.addColorStop(1,shadeColor(color,.62)); cx.fillStyle=g; cx.fillRect(px+1,py+1,CELL-2,CELL-2);
  const shine=Math.max(3,Math.floor(CELL*.12)); cx.fillStyle='rgba(255,255,255,.22)'; cx.fillRect(px+1,py+1,CELL-2,shine); cx.fillRect(px+1,py+1,shine,CELL-2); cx.fillStyle='rgba(0,0,0,.28)'; cx.fillRect(px+1,py+CELL-shine,CELL-2,shine); cx.fillRect(px+CELL-shine,py+1,shine,CELL-2); cx.globalAlpha=1;
}
function drawAll(){
  ctx.fillStyle='#07031a'; ctx.fillRect(0,0,W,H);
  const rg=ctx.createRadialGradient(W/2,H*.6,0,W/2,H*.6,W*.8); rg.addColorStop(0,'rgba(60,10,90,.45)'); rg.addColorStop(1,'transparent'); ctx.fillStyle=rg; ctx.fillRect(0,0,W,H);
  ctx.strokeStyle='rgba(192,132,252,.055)'; ctx.lineWidth=.5;
  for(let r=0;r<=ROWS;r++){ctx.beginPath();ctx.moveTo(0,r*CELL);ctx.lineTo(W,r*CELL);ctx.stroke();}
  for(let c=0;c<=COLS;c++){ctx.beginPath();ctx.moveTo(c*CELL,0);ctx.lineTo(c*CELL,H);ctx.stroke();}
  for(let r=0;r<ROWS;r++) for(let c=0;c<COLS;c++){ if(!board[r][c]) continue; const cell=board[r][c]; ctx.save(); ctx.shadowBlur=6; ctx.shadowColor=cell.glow||cell.color; drawCell(ctx,c,r,cell.color); ctx.restore(); }
  if(gameRunning||paused){
    const gy=calcGhost(); if(gy!==current.y){ for(let r=0;r<current.shape.length;r++) for(let c=0;c<current.shape[r].length;c++){ if(!current.shape[r][c]) continue; drawCell(ctx,current.x+c,gy+r,current.color,.16); } }
    ctx.save(); ctx.shadowBlur=CELL*.55; ctx.shadowColor=current.glow; for(let r=0;r<current.shape.length;r++) for(let c=0;c<current.shape[r].length;c++){ if(!current.shape[r][c]) continue; drawCell(ctx,current.x+c,current.y+r,current.color); } ctx.restore();
  }
  particles.forEach(p=>{ p.x+=p.vx; p.y+=p.vy; p.vy+=.08; p.life-=.022; ctx.globalAlpha=Math.max(0,p.life); ctx.shadowBlur=8; ctx.shadowColor=p.color; ctx.fillStyle=p.color; ctx.beginPath(); ctx.arc(p.x,p.y,Math.max(0,p.size*p.life),0,Math.PI*2); ctx.fill(); });
  ctx.globalAlpha=1; ctx.shadowBlur=0; particles=particles.filter(p=>p.life>0);
  if(combo>1){ ctx.save(); ctx.shadowBlur=16; ctx.shadowColor='#fbbf24'; ctx.fillStyle='#fbbf24'; ctx.font=`900 ${Math.round(CELL*.68)}px Orbitron,monospace`; ctx.textAlign='center'; ctx.fillText(`COMBO ×${combo}`,W/2,CELL*.82); ctx.restore(); }
  ctx.strokeStyle='rgba(192,132,252,.3)'; ctx.lineWidth=1.5; ctx.strokeRect(1,1,W-2,H-2);
}
function drawNext(){
  const nc=Math.round(ncan.width/5); nctx.fillStyle='#07031a'; nctx.fillRect(0,0,ncan.width,ncan.height);
  const ox=Math.floor((ncan.width-next.shape[0].length*nc)/2), oy=Math.floor((ncan.height-next.shape.length*nc)/2);
  for(let r=0;r<next.shape.length;r++) for(let c=0;c<next.shape[r].length;c++){ if(!next.shape[r][c]) continue; const px=ox+c*nc, py=oy+r*nc; nctx.save(); nctx.shadowBlur=10; nctx.shadowColor=next.glow; const g=nctx.createLinearGradient(px,py,px+nc,py+nc); g.addColorStop(0,next.color); g.addColorStop(1,shadeColor(next.color,.65)); nctx.fillStyle=g; nctx.fillRect(px+1,py+1,nc-2,nc-2); nctx.fillStyle='rgba(255,255,255,.2)'; nctx.fillRect(px+1,py+1,nc-2,4); nctx.restore(); }
}
function updateHUD(){ document.getElementById('hud-score').textContent=score.toLocaleString(); document.getElementById('hud-level').textContent=level; document.getElementById('hud-lines').textContent=lines; document.getElementById('hud-combo').textContent=`×${combo}`; }
async function saveScore(s, lv, dur) {
  if (!LOGGED_IN || s <= 0) return;
  try {
    const body = JSON.stringify({ game: 'tetris', score: s, level: lv, duration: dur });
    const r = await fetch(SAVE_URL, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: body,
      keepalive: true
    });
    const d = await r.json();
    if (d.success) {
      if (d.best_score) {
        document.getElementById('hud-best').textContent = d.best_score.toLocaleString();
      }
    }
  } catch (e) { console.error("Save score error:", e); }
}
document.addEventListener('keydown',e=>{
  if(!gameRunning||paused){ if(e.key==='p'||e.key==='P') togglePause(); return; }
  switch(e.key){
    case'ArrowLeft': if(isValid(current.shape,current.x-1,current.y)){ current.x--; if(!isOnGround()) lockDelay=false; } e.preventDefault(); break;
    case'ArrowRight': if(isValid(current.shape,current.x+1,current.y)){ current.x++; if(!isOnGround()) lockDelay=false; } e.preventDefault(); break;
    case'ArrowDown': if(isValid(current.shape,current.x,current.y+1)){ current.y++; score+=1; updateHUD(); }else{ placePiece(); } e.preventDefault(); break;
    case'ArrowUp': case'x': case'X':{ const rot=rotate(current.shape); const kicks=[0,-1,1,-2,2]; for(const k of kicks){ if(isValid(rot,current.x+k,current.y)){ current.shape=rot; current.x+=k; break; } } e.preventDefault(); break; }
    case' ': hardDrop(); e.preventDefault(); break;
    case'p': case'P': togglePause(); break;
  }
});
document.getElementById('startBtn').onclick=startGame;
document.getElementById('resumeBtn').onclick=togglePause;
resize(); ctx.fillStyle='#07031a'; ctx.fillRect(0,0,W,H);
</script>
<script>
if (window.Playtime) Playtime.init({ game: 'tetris', url: '../api/playtime.php', loggedIn: LOGGED_IN, accent: '#c084fc' });
</script>
</body>
</html>