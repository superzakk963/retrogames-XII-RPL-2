// assets/main.js - RetroGames shared JavaScript

// ─── Password visibility toggle ───────────────────────────────────────────────
function togglePasswordVisibility(fieldId) {
    const field = document.getElementById(fieldId);
    if (!field) return;
    field.type = field.type === 'password' ? 'text' : 'password';
}

// ─── Password strength meter ──────────────────────────────────────────────────
const pwField = document.getElementById('password');
const pwStrength = document.getElementById('password-strength');
if (pwField && pwStrength) {
    pwField.addEventListener('input', function () {
        const val = this.value;
        let score = 0;
        let label = '';
        let cls = '';

        if (val.length >= 6)  score++;
        if (val.length >= 10) score++;
        if (/[A-Z]/.test(val)) score++;
        if (/[0-9]/.test(val)) score++;
        if (/[^A-Za-z0-9]/.test(val)) score++;

        if      (score <= 1) { label = 'Weak';   cls = 'strength-weak'; }
        else if (score <= 3) { label = 'Fair';   cls = 'strength-fair'; }
        else if (score <= 4) { label = 'Good';   cls = 'strength-good'; }
        else                  { label = 'Strong'; cls = 'strength-strong'; }

        pwStrength.textContent = val.length ? `Password strength: ${label}` : '';
        pwStrength.className = cls;
    });
}

// ─── Alert auto-dismiss ───────────────────────────────────────────────────────
document.querySelectorAll('.alert').forEach(alert => {
    setTimeout(() => {
        alert.style.transition = 'opacity 0.5s';
        alert.style.opacity = '0';
        setTimeout(() => alert.remove(), 500);
    }, 5000);
});

// ─── Confirm delete helpers ───────────────────────────────────────────────────
document.querySelectorAll('[data-confirm]').forEach(el => {
    el.addEventListener('click', function (e) {
        if (!confirm(this.dataset.confirm)) e.preventDefault();
    });
});

// ─── Mobile nav toggle ────────────────────────────────────────────────────────
const navToggle = document.getElementById('nav-toggle');
const mainNav = document.getElementById('main-nav');
if (navToggle && mainNav) {
    navToggle.addEventListener('click', () => {
        mainNav.classList.toggle('open');
    });
}

// ─── Game canvas responsive resize ───────────────────────────────────────────
function fitCanvasToContainer() {
    const containers = document.querySelectorAll('#game-container');
    containers.forEach(container => {
        const canvas = container.querySelector('canvas#gameCanvas');
        if (!canvas) return;
        const maxW = container.clientWidth;
        if (canvas.width > maxW) {
            const ratio = maxW / canvas.width;
            canvas.style.width = maxW + 'px';
            canvas.style.height = (canvas.height * ratio) + 'px';
        } else {
            canvas.style.width = '';
            canvas.style.height = '';
        }
    });
}
window.addEventListener('resize', fitCanvasToContainer);
fitCanvasToContainer();

// ─── Tab switching for leaderboard filter ────────────────────────────────────
document.querySelectorAll('.game-filter-tabs .tab').forEach(tab => {
    tab.addEventListener('click', function () {
        document.querySelectorAll('.game-filter-tabs .tab').forEach(t => t.classList.remove('active'));
        this.classList.add('active');
    });
});

// ─── Smooth scroll for anchor links ──────────────────────────────────────────
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            e.preventDefault();
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });
});

// ─── Admin: filter form on enter ─────────────────────────────────────────────
document.querySelectorAll('.filter-form input').forEach(input => {
    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') this.closest('form').submit();
    });
});

// ─── roundRect polyfill for older browsers ───────────────────────────────────
if (!CanvasRenderingContext2D.prototype.roundRect) {
    CanvasRenderingContext2D.prototype.roundRect = function (x, y, w, h, r) {
        if (w < 2 * r) r = w / 2;
        if (h < 2 * r) r = h / 2;
        this.beginPath();
        this.moveTo(x + r, y);
        this.arcTo(x + w, y, x + w, y + h, r);
        this.arcTo(x + w, y + h, x, y + h, r);
        this.arcTo(x, y + h, x, y, r);
        this.arcTo(x, y, x + w, y, r);
        this.closePath();
        return this;
    };
}

/* ═══════════════════════════════════════════════════════
   ✨ RETROGAMES v2 — UX ENHANCEMENT LAYER ✨
═══════════════════════════════════════════════════════ */

// ─── Toast notification system (window.toast) ────────────────────────────────
(function () {
    let container = document.querySelector('.toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
    }
    window.toast = function (message, type = 'info', duration = 3500) {
        const t = document.createElement('div');
        t.className = 'toast ' + type;
        t.textContent = message;
        container.appendChild(t);
        setTimeout(() => {
            t.classList.add('fadeout');
            setTimeout(() => t.remove(), 400);
        }, duration);
    };
})();

// ─── Confetti burst ──────────────────────────────────────────────────────────
window.confettiBurst = function (count = 80) {
    const colors = ['#7c3aed', '#f59e0b', '#10b981', '#3b82f6', '#ef4444', '#fbbf24'];
    for (let i = 0; i < count; i++) {
        const piece = document.createElement('div');
        piece.className = 'confetti-piece';
        piece.style.left = Math.random() * 100 + 'vw';
        piece.style.background = colors[Math.floor(Math.random() * colors.length)];
        piece.style.animationDuration = (2 + Math.random() * 2) + 's';
        piece.style.animationDelay = (Math.random() * 0.4) + 's';
        piece.style.transform = `rotate(${Math.random() * 360}deg)`;
        document.body.appendChild(piece);
        setTimeout(() => piece.remove(), 4500);
    }
};

// ─── Scroll-to-top FAB ───────────────────────────────────────────────────────
(function () {
    const btn = document.createElement('button');
    btn.id = 'scroll-top-btn';
    btn.title = 'Scroll to top';
    btn.innerHTML = '↑';
    btn.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
    document.body.appendChild(btn);
    window.addEventListener('scroll', () => {
        btn.classList.toggle('visible', window.scrollY > 400);
    }, { passive: true });
})();

// ─── HUD pulse helper for games ──────────────────────────────────────────────
window.hudPulse = function (selector) {
    const el = typeof selector === 'string' ? document.querySelector(selector) : selector;
    if (!el) return;
    el.classList.remove('hud-pulse');
    void el.offsetWidth; // restart animation
    el.classList.add('hud-pulse');
};

// ─── Card hover tilt (subtle) ────────────────────────────────────────────────
document.querySelectorAll('.game-card').forEach(card => {
    card.addEventListener('mousemove', e => {
        const r = card.getBoundingClientRect();
        const px = (e.clientX - r.left) / r.width  - 0.5;
        const py = (e.clientY - r.top)  / r.height - 0.5;
        card.style.transform = `translateY(-8px) scale(1.015) rotateX(${(-py * 4).toFixed(2)}deg) rotateY(${(px * 6).toFixed(2)}deg)`;
    });
    card.addEventListener('mouseleave', () => {
        card.style.transform = '';
    });
});

// ─── Animated number counter for stat cards ──────────────────────────────────
document.querySelectorAll('.stat-value').forEach(el => {
    const raw = el.textContent.replace(/[^\d]/g, '');
    const target = parseInt(raw, 10);
    if (!Number.isFinite(target) || target <= 0 || target > 1e9) return;
    const dur = 900;
    const start = performance.now();
    const formatter = new Intl.NumberFormat();
    function tick(now) {
        const t = Math.min(1, (now - start) / dur);
        const eased = 1 - Math.pow(1 - t, 3);
        el.textContent = formatter.format(Math.round(target * eased));
        if (t < 1) requestAnimationFrame(tick);
    }
    el.textContent = '0';
    requestAnimationFrame(tick);
});

// ─── Difficulty button group sync ────────────────────────────────────────────
document.querySelectorAll('.diff-group').forEach(group => {
    group.querySelectorAll('.diff-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            group.querySelectorAll('.diff-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
        });
    });
});

// ─── Keyboard shortcut: '/' focus search ─────────────────────────────────────
document.addEventListener('keydown', e => {
    if (e.key === '/' && !/^(INPUT|TEXTAREA|SELECT)$/.test(document.activeElement.tagName)) {
        const search = document.querySelector('.filter-form input[type="text"], .filter-form input[name="search"]');
        if (search) { e.preventDefault(); search.focus(); }
    }
});

// ─── Show flash on logout-style success ──────────────────────────────────────
// (handled server-side via .alert, kept here as hook for future use)

