'use strict';

// ── Toast ─────────────────────────────────────────────────────────────────────
let toastTimer;
function showToast(msg, type = 'success', ms = 2400) {
  let el = document.getElementById('toast');
  if (!el) {
    el = document.createElement('div');
    el.id = 'toast'; el.className = 'toast';
    document.body.appendChild(el);
  }
  clearTimeout(toastTimer);
  el.textContent = msg;
  el.className = `toast ${type} show`;
  toastTimer = setTimeout(() => el.classList.remove('show'), ms);
}

// ── Local Time display ────────────────────────────────────────────────────────
function localiseMatchTimes() {
  document.querySelectorAll('[data-utc]').forEach(el => {
    const utc = el.dataset.utc;
    if (!utc) return;
    try {
      const d = new Date(utc.replace(' ', 'T') + 'Z');
      const opts = { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' };
      el.textContent = d.toLocaleString(navigator.language || 'es', opts);
      el.title = 'UTC: ' + utc;
    } catch {}
  });
}

// ── PIN inputs ────────────────────────────────────────────────────────────────
function initPinInputs() {
  const pins = document.querySelectorAll('.pin-input');
  pins.forEach((inp, i) => {
    inp.addEventListener('input', e => {
      const val = e.target.value.replace(/\D/g, '').slice(0, 1);
      e.target.value = val;
      if (val && i < pins.length - 1) pins[i + 1].focus();
    });
    inp.addEventListener('keydown', e => {
      if (e.key === 'Backspace' && !inp.value && i > 0) {
        pins[i - 1].focus();
        pins[i - 1].value = '';
      }
    });
    inp.addEventListener('paste', e => {
      const text = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '');
      pins.forEach((p, j) => { p.value = text[j] || ''; });
      e.preventDefault();
      if (text.length >= pins.length) pins[pins.length - 1].focus();
    });
  });
}

// ── Match prediction forms ────────────────────────────────────────────────────
function initPredictionForms() {
  document.querySelectorAll('.predict-form').forEach(form => {
    // Track whether this form already has a saved prediction
    const btn = form.querySelector('.save-btn');
    form.dataset.saved = btn && btn.textContent.trim() === 'Cambiar' ? '1' : '0';

    form.addEventListener('submit', async e => {
      e.preventDefault();
      const btn = form.querySelector('.save-btn');
      const matchId = form.dataset.matchId;
      const g1 = form.querySelector('[name="g1"]').value;
      const g2 = form.querySelector('[name="g2"]').value;

      if (g1 === '' || g2 === '') {
        showToast('Introduce ambos goles', 'error'); return;
      }

      const prevText = btn.textContent.trim();
      btn.disabled = true;
      btn.textContent = '…';

      try {
        const fd = new FormData();
        fd.append('match_id', matchId);
        fd.append('g1', g1);
        fd.append('g2', g2);

        const res = await fetch('api/predict.php', { method: 'POST', body: fd });
        const data = await res.json();

        if (data.ok) {
          const isNew = form.dataset.saved === '0';
          form.dataset.saved = '1';
          showToast(isNew ? '✅ Apuesta guardada' : '🔄 Apuesta actualizada', 'success');
          updatePredDisplay(form, parseInt(g1), parseInt(g2));
          btn.textContent = 'Cambiar';
        } else {
          showToast(data.error || 'Error al guardar', 'error');
          btn.textContent = prevText;
        }
      } catch {
        showToast('Error de conexión', 'error');
        btn.textContent = prevText;
      } finally {
        btn.disabled = false;
      }
    });

    // Score input: auto-advance and select on focus
    const inputs = form.querySelectorAll('.score-input');
    inputs.forEach((inp, i) => {
      inp.addEventListener('input', e => {
        const val = e.target.value.replace(/\D/g, '').slice(0, 2);
        e.target.value = val;
        if (val.length >= 1 && i < inputs.length - 1) inputs[i + 1].focus();
      });
      inp.addEventListener('focus', () => inp.select());
    });
  });
}

function updatePredDisplay(form, g1, g2) {
  // Update pred-info section
  const predInfo = form.querySelector('.pred-info');
  if (predInfo) {
    predInfo.innerHTML =
      `<span class="text-muted" style="font-size:.75rem">Tu apuesta:</span>` +
      `<span class="pred-score">${g1} - ${g2}</span>`;
  }

  const card = form.closest('.match-card');
  if (card) {
    const ptsSpan = card.querySelector('.pred-pts');
    if (ptsSpan) { ptsSpan.textContent = 'Pendiente'; ptsSpan.className = 'pred-pts pending'; }
    // Mark card as having a prediction (for filter tabs)
    card.classList.add('has-pred');

    // ── Re-apply active filter so the card stays visible ──────────────────
    const activeFilter = document.querySelector('.tab-btn.active')?.dataset.filter;
    if (activeFilter === 'my') {
      card.style.display = '';              // show the card
      const group = card.closest('.match-group');
      if (group) group.style.display = ''; // show its group if hidden
    }
  }

  // Update the top-bar counter by re-counting from DOM
  const badge = document.querySelector('.top-bar-badge');
  if (badge) {
    const done  = document.querySelectorAll('.match-card.has-pred').length;
    const total = document.querySelectorAll('.match-card').length;
    badge.textContent = `${done}/${total} predichos`;
  }
}

// ── Filter Tabs ───────────────────────────────────────────────────────────────
function initFilterTabs() {
  document.querySelectorAll('.filter-tabs').forEach(container => {
    container.querySelectorAll('.tab-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        container.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        applyFilter(btn.dataset.filter);
      });
    });
  });
}

function applyFilter(filter) {
  document.querySelectorAll('.date-group').forEach(group => {
    let anyVisible = false;
    group.querySelectorAll('.match-card').forEach(card => {
      let show = false;
      if (filter === 'all')    show = true;
      if (filter === 'my')     show = card.classList.contains('has-pred');
      if (filter === 'open')   show = card.classList.contains('open');
      if (filter === 'done')   show = card.classList.contains('has-result');
      card.style.display = show ? '' : 'none';
      if (show) anyVisible = true;
    });
    // Hide the date group header when no cards are visible
    group.style.display = anyVisible ? '' : 'none';
  });
}

// ── Scroll to first open match ────────────────────────────────────────────────
function scrollToFirstOpen() {
  const anchor = document.getElementById('primer-abierto');
  if (!anchor) return;
  // Small delay so the page is fully laid out
  setTimeout(() => {
    const topBarH = parseInt(getComputedStyle(document.documentElement).getPropertyValue('--top-h')) || 56;
    const y = anchor.getBoundingClientRect().top + window.scrollY - topBarH - 8;
    window.scrollTo({ top: Math.max(0, y), behavior: 'smooth' });
  }, 120);
}

// ── Top 4 duplicate team guard ────────────────────────────────────────────────
function initTop4Selects() {
  const selects = Array.from(document.querySelectorAll('.pos-select'));
  selects.forEach(sel => {
    sel.addEventListener('change', () => enforceTop4Unique(selects));
  });
}
function enforceTop4Unique(selects) {
  const chosen = selects.map(s => s.value).filter(Boolean);
  selects.forEach(sel => {
    Array.from(sel.options).forEach(opt => {
      if (!opt.value) return;
      opt.disabled = chosen.includes(opt.value) && opt.value !== sel.value;
    });
  });
}

// ── Countdown ─────────────────────────────────────────────────────────────────
function initCountdowns() {
  document.querySelectorAll('[data-countdown]').forEach(el => {
    function tick() {
      const target = new Date(el.dataset.countdown.replace(' ', 'T') + 'Z');
      const now = new Date();
      const diff = target - now;
      if (diff <= 0) { el.textContent = 'Comenzado'; return; }
      const h = Math.floor(diff / 3600000);
      const m = Math.floor((diff % 3600000) / 60000);
      const s = Math.floor((diff % 60000) / 1000);
      if (h >= 24) {
        const d = Math.floor(h / 24);
        el.textContent = `${d}d ${h % 24}h`;
      } else {
        el.textContent = `${h}h ${String(m).padStart(2,'0')}m ${String(s).padStart(2,'0')}s`;
      }
    }
    tick();
    setInterval(tick, 1000);
  });
}

// ── Init ──────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  localiseMatchTimes();
  initPinInputs();
  initPredictionForms();
  initFilterTabs();
  initTop4Selects();
  initCountdowns();
  enforceTop4Unique(Array.from(document.querySelectorAll('.pos-select')));
  scrollToFirstOpen();
});
