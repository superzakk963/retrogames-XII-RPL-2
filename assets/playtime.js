/* ============================================================
 * RetroGames — Playtime Tracker
 * ------------------------------------------------------------
 * Counts how long the user actually plays (pause time excluded)
 * and shows a live timer widget in the game UI.
 *
 * Usage in a game page:
 *   <script src="../assets/playtime.js"></script>   (after other scripts)
 *   Playtime.init({
 *     game: 'tetris',                     // game slug
 *     url: '../api/playtime.php',         // API endpoint
 *     loggedIn: LOGGED_IN,                // PHP flag
 *     accent: '#c084fc'                   // widget color (optional)
 *   });
 *
 * Hooks to call from the game code:
 *   Playtime.onGameStart();   // a round actually begins (after countdown)
 *   Playtime.onGamePause();   // game paused / overlay shown
 *   Playtime.onGameResume();  // game resumed
 *   Playtime.onGameEnd();     // round finished (session stays open so
 *                             // the next round joins the same session)
 * ============================================================ */
(function () {
  'use strict';

  if (window.Playtime) return; // guard against double-include

  var HEARTBEAT_EVERY = 30; // seconds of play between server syncs

  var cfg = {
    game: '',
    url: '',
    loggedIn: false,
    accent: '#c084fc'
  };

  var session = {
    id: 0,
    // seconds accumulated but not yet sent to the server
    pending: 0,
    // timestamp of the last tick start (null while paused / between rounds)
    tickStart: null,
    // total this session (local view, server is authoritative)
    local: 0,
    starting: false
  };

  var els = {};
  var tickerTimer = null;

  /* ---------- helpers ---------- */

  function post(data) {
    if (!cfg.url) return;
    try {
      var blob = new Blob([JSON.stringify(data)], { type: 'application/json' });
      if (navigator.sendBeacon) {
        navigator.sendBeacon(cfg.url, blob);
        return;
      }
    } catch (e) {}
    fetch(cfg.url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data),
      keepalive: true
    }).catch(function () {});
  }

  function fmt(totalSec) {
    var s = Math.floor(totalSec);
    var h = Math.floor(s / 3600);
    var m = Math.floor((s % 3600) / 60);
    var r = s % 60;
    var mm = (m < 10 && h > 0 ? '0' : '') + m;
    var ss = (r < 10 ? '0' : '') + r;
    return h > 0 ? h + ':' + mm + ':' + ss : m + ':' + ss;
  }

  /* ---------- widget ---------- */

  function buildWidget() {
    var wrap = document.createElement('div');
    wrap.id = 'playtime-widget';
    wrap.innerHTML =
      '<span class="pt-icon">⏱</span>' +
      '<span class="pt-label">PLAYTIME</span>' +
      '<span class="pt-value" id="playtime-value">0:00</span>';
    wrap.style.setProperty('--pt-accent', cfg.accent);
    document.body.appendChild(wrap);
    els.value = wrap.querySelector('#playtime-value');
  }

  function render() {
    if (els.value) els.value.textContent = fmt(session.local);
  }

  function startTicker() {
    if (tickerTimer) return;
    tickerTimer = setInterval(tick, 1000);
  }

  function tick() {
    if (session.tickStart === null) return;
    var now = Date.now();
    var delta = Math.floor((now - session.tickStart) / 1000);
    if (delta <= 0) return;
    session.tickStart = now;
    session.local += delta;
    session.pending += delta;
    render();

    if (session.pending >= HEARTBEAT_EVERY) flush();
  }

  function flush() {
    if (!session.id || session.pending <= 0) return;
    var secs = session.pending;
    session.pending = 0;
    fetch(cfg.url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'heartbeat', game: cfg.game, session_id: session.id, seconds: secs }),
      keepalive: true
    })
      .then(function (r) { return r.json(); })
      .then(function (d) {
        // Session was closed elsewhere (e.g. a second tab of the same
        // game) → drop it so the next start hook opens a fresh one.
        if (d && !d.success && /closed|not found/i.test(d.message || '')) {
          session.id = 0;
        }
      })
      .catch(function () {});
  }

  /* ---------- lifecycle ---------- */

  function ensureSession() {
    if (session.id || session.starting || !cfg.loggedIn) return;
    session.starting = true;
    fetch(cfg.url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'start', game: cfg.game })
    })
      .then(function (r) { return r.json(); })
      .then(function (d) {
        if (d && d.success) session.id = d.session_id;
      })
      .catch(function () {})
      .then(function () { session.starting = false; });
  }

  /** Accumulate the seconds since tickStart into local + pending. */
  function bankElapsed() {
    if (session.tickStart === null) return;
    var delta = Math.floor((Date.now() - session.tickStart) / 1000);
    session.tickStart = null;
    if (delta > 0) {
      session.local += delta;
      session.pending += delta;
    }
  }

  window.Playtime = {
    init: function (options) {
      cfg.game = options.game || '';
      cfg.url = options.url || '';
      cfg.loggedIn = !!options.loggedIn;
      cfg.accent = options.accent || cfg.accent;
      if (!cfg.loggedIn) return; // widget + tracking only for logged-in users

      buildWidget();
      render();

      // Never lose the last unsent seconds when leaving the page.
      // A single 'end' beacon carries everything: it accumulates the
      // seconds server-side and closes the session atomically.
      window.addEventListener('pagehide', function () {
        if (!session.id) return;
        var delta = session.tickStart !== null
          ? Math.floor((Date.now() - session.tickStart) / 1000)
          : 0;
        var secs = session.pending + delta;
        session.pending = 0;
        session.tickStart = null;
        post({ action: 'end', game: cfg.game, session_id: session.id, seconds: secs });
      });
    },

    /** Call when a round actually starts (after any countdown). */
    onGameStart: function () {
      if (!cfg.loggedIn) return;
      ensureSession();
      if (session.tickStart === null) session.tickStart = Date.now();
      startTicker();
    },

    /** Call when the game is paused or the overlay covers play. */
    onGamePause: function () {
      if (!cfg.loggedIn) return;
      bankElapsed();
      render();
      flush();
    },

    /** Call when the game resumes from pause. */
    onGameResume: function () {
      if (!cfg.loggedIn) return;
      ensureSession();
      if (session.tickStart === null) session.tickStart = Date.now();
      startTicker();
    },

    /** Call when a round ends. The session stays open so the next
     *  round keeps counting into the same play session, but the
     *  timer stops: game-over/menu time is NOT playtime. */
    onGameEnd: function () {
      if (!cfg.loggedIn) return;
      bankElapsed();
      render();
      flush();
    }
  };
})();
