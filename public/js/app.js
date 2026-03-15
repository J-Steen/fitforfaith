/* FitForFaith — App JavaScript */
'use strict';

// ── Navigation mobile toggle ──────────────────────────────────────
const navToggle = document.getElementById('navToggle');
const navLinks  = document.getElementById('navLinks');

if (navToggle && navLinks) {
  navToggle.addEventListener('click', () => {
    const isOpen = navLinks.classList.toggle('open');
    navToggle.setAttribute('aria-expanded', isOpen);
  });
  // Close nav on link click
  navLinks.querySelectorAll('a').forEach(a => {
    a.addEventListener('click', () => navLinks.classList.remove('open'));
  });
}

// ── Animated counter on stats ─────────────────────────────────────
function animateCounter(el) {
  const target  = parseInt(el.textContent.replace(/[^0-9]/g, ''), 10);
  if (isNaN(target) || target === 0) return;

  const duration = 1200;
  const start    = performance.now();

  function update(now) {
    const elapsed  = now - start;
    const progress = Math.min(elapsed / duration, 1);
    const eased    = 1 - Math.pow(1 - progress, 3); // ease-out cubic
    const current  = Math.floor(eased * target);

    // Preserve original format (R prefix, comma separators)
    const original = el.dataset.original;
    if (original && original.startsWith('R')) {
      el.textContent = 'R' + current.toLocaleString();
    } else {
      el.textContent = current.toLocaleString();
    }

    if (progress < 1) requestAnimationFrame(update);
    else {
      el.textContent = original || el.textContent;
    }
  }

  el.dataset.original = el.textContent;
  requestAnimationFrame(update);
}

// Run counter animation when stat cards enter viewport
const statValues = document.querySelectorAll('.stat-value');
if (statValues.length && 'IntersectionObserver' in window) {
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        animateCounter(entry.target);
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.5 });

  statValues.forEach(el => observer.observe(el));
} else {
  // Fallback: just animate all immediately
  statValues.forEach(animateCounter);
}

// ── Leaderboard tab persistence ───────────────────────────────────
const tabBtns = document.querySelectorAll('.tab-btn');
tabBtns.forEach(btn => {
  btn.addEventListener('click', e => {
    tabBtns.forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
  });
});

// ── Auto-dismiss flash alerts ─────────────────────────────────────
document.querySelectorAll('.alert').forEach(alert => {
  setTimeout(() => {
    alert.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
    alert.style.opacity    = '0';
    alert.style.transform  = 'translateY(-8px)';
    setTimeout(() => alert.remove(), 500);
  }, 5000);
});

// ── Form enhancement ──────────────────────────────────────────────
// Show password toggle
document.querySelectorAll('input[type="password"]').forEach(input => {
  const wrap = document.createElement('div');
  wrap.style.cssText = 'position:relative;';
  input.parentNode.insertBefore(wrap, input);
  wrap.appendChild(input);

  const toggle = document.createElement('button');
  toggle.type = 'button';
  toggle.innerHTML = '👁';
  toggle.style.cssText = 'position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;font-size:1rem;opacity:0.5;padding:0;line-height:1;';
  toggle.setAttribute('aria-label', 'Toggle password visibility');
  toggle.addEventListener('click', () => {
    input.type = input.type === 'password' ? 'text' : 'password';
    toggle.style.opacity = input.type === 'text' ? '1' : '0.5';
  });
  wrap.appendChild(toggle);
});

// ── Stale form protection ─────────────────────────────────────────
let formDirty = false;
document.querySelectorAll('form input, form select, form textarea').forEach(el => {
  el.addEventListener('change', () => { formDirty = true; });
});
// Don't warn when submitting
document.querySelectorAll('form').forEach(f => {
  f.addEventListener('submit', () => { formDirty = false; });
});

// ── Smooth progress bar animation ────────────────────────────────
document.querySelectorAll('.progress-bar').forEach(bar => {
  const target = bar.getAttribute('data-width') || bar.style.width;
  bar.style.width = '0';
  setTimeout(() => { bar.style.width = target; }, 200);
});

// ── PWA Service Worker registration ──────────────────────────────
if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('/public/js/sw.js', { scope: '/' })
      .then(reg => console.log('SW registered'))
      .catch(err => console.log('SW registration failed:', err));
  });
}

// ── Add to home screen prompt ─────────────────────────────────────
let deferredPrompt;
window.addEventListener('beforeinstallprompt', (e) => {
  e.preventDefault();
  deferredPrompt = e;

  // Show a subtle install banner after 30s
  setTimeout(() => {
    const banner = document.createElement('div');
    banner.className = 'toast';
    banner.innerHTML = `
      <div style="display:flex;align-items:center;gap:12px;">
        <span style="font-size:1.5rem;">📱</span>
        <div>
          <div style="font-weight:700;font-size:0.875rem;">Add to Home Screen</div>
          <div style="color:var(--text-muted);font-size:0.8rem;">Get the app-like experience</div>
        </div>
        <button id="pwaInstallBtn" style="margin-left:auto;padding:6px 14px;background:var(--primary);color:#fff;border:none;border-radius:100px;cursor:pointer;font-size:0.8rem;font-weight:600;">Install</button>
        <button id="pwaDismiss" style="background:none;border:none;color:var(--text-muted);cursor:pointer;padding:4px;">✕</button>
      </div>
    `;

    let container = document.querySelector('.toast-container');
    if (!container) {
      container = document.createElement('div');
      container.className = 'toast-container';
      document.body.appendChild(container);
    }
    container.appendChild(banner);

    document.getElementById('pwaInstallBtn')?.addEventListener('click', () => {
      deferredPrompt.prompt();
      banner.remove();
    });
    document.getElementById('pwaDismiss')?.addEventListener('click', () => banner.remove());
  }, 30000);
});

// ── Leaderboard live refresh (every 60 seconds on leaderboard page) ──
if (window.location.pathname.includes('leaderboard')) {
  setInterval(() => {
    // Just update the "last updated" text without full reload
    const timeEl = document.querySelector('.section-sub');
    if (timeEl && timeEl.textContent.includes('Updated')) {
      const now = new Date().toLocaleTimeString('en-ZA', { hour:'2-digit', minute:'2-digit' });
      timeEl.textContent = timeEl.textContent.replace(/Updated.*/, 'Updated at ' + now);
    }
  }, 60000);
}
