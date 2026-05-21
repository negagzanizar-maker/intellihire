/**
 * IntelliHire — main.js
 * Chart.js, animated counters, AI summary, modals, toasts
 */

document.addEventListener('DOMContentLoaded', () => {
  const nav = document.querySelector('.topnav');
  const pageHeader = document.querySelector('.page-header');
  if (!window.location.hash && nav && pageHeader) {
    requestAnimationFrame(() => {
      const navHeight = nav.getBoundingClientRect().height;
      const headerTop = pageHeader.getBoundingClientRect().top;
      if (window.scrollY > 0 && window.scrollY < navHeight * 1.5 && headerTop < navHeight + 8) {
        window.scrollTo({ top: 0, left: 0 });
      }
    });
  }

  const ui = {
    ink: '#111827',
    muted: '#3F4857',
    dim: '#6B7280',
    panel: '#FFFFFC',
    grid: 'rgba(17,24,39,.08)',
    indigo: '#9400D3',
    violet: '#330066',
    cyan: '#2563EB',
    green: '#047857',
    amber: '#B7791F',
    rose: '#B42318',
  };
  const chartPalette = ['#111827', '#9400D3', '#330066', '#D8A8FF', '#047857', '#2563EB', '#B7791F'];

  // ── Animated KPI Counters ─────────────────────────────────────
  document.querySelectorAll('[data-target]').forEach(el => {
    const target = parseInt(el.dataset.target, 10);
    if (isNaN(target)) return;
    let start   = 0;
    const dur   = 1200;
    const step  = 16;
    const inc   = target / (dur / step);
    const timer = setInterval(() => {
      start += inc;
      if (start >= target) { el.textContent = target; clearInterval(timer); }
      else el.textContent = Math.floor(start);
    }, step);
  });

  // ── Chart : Donut ────────────────────────────────────────────
  const donutCanvas = document.getElementById('chartDonut');
  if (donutCanvas && typeof chartDonut !== 'undefined') {
    new Chart(donutCanvas, {
      type: 'doughnut',
      data: {
        labels:   chartDonut.labels,
        datasets: [{
          data:            chartDonut.values,
          backgroundColor: chartDonut.values.map((_, i) => chartPalette[i % chartPalette.length]),
          borderColor:     '#FFFCF6',
          borderWidth:     3,
          hoverBorderColor: ui.ink,
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '65%',
        plugins: {
          legend: {
            position: 'bottom',
            labels: {
              color: ui.muted,
              font: { size: 11, family: "'JetBrains Mono', monospace" },
              padding: 16,
              usePointStyle: true,
              pointStyleWidth: 8,
            }
          },
          tooltip: {
            backgroundColor: ui.panel,
            borderColor: 'rgba(17,24,39,.12)',
            borderWidth: 1,
            titleColor: ui.ink,
            bodyColor: ui.muted,
            padding: 10,
          }
        }
      }
    });
  }

  // ── Chart : Bar (Candidatures par offre) ─────────────────────
  const barCanvas = document.getElementById('chartBar');
  if (barCanvas && typeof chartBar !== 'undefined') {
    new Chart(barCanvas, {
      type: 'bar',
      data: {
        labels:   chartBar.labels,
        datasets: [{
          label:           'Candidatures',
          data:            chartBar.values,
          backgroundColor: 'rgba(148,0,211,.14)',
          borderColor:     ui.indigo,
          borderWidth:     1.5,
          borderRadius:    6,
          hoverBackgroundColor: 'rgba(148,0,211,.24)',
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          x: {
            ticks: {
              color: ui.dim,
              font: { size: 10 },
              maxRotation: 30,
              callback: (val, idx) => {
                const lbl = chartBar.labels[idx] || '';
                return lbl.length > 18 ? lbl.slice(0, 17) + '…' : lbl;
              }
            },
            grid: { color: ui.grid },
          },
          y: {
            beginAtZero: true,
            ticks: { color: ui.dim, font: { size: 10 }, stepSize: 1 },
            grid: { color: ui.grid },
          }
        },
        plugins: {
          legend: { display: false },
          tooltip: {
            backgroundColor: ui.panel,
            borderColor: 'rgba(17,24,39,.12)',
            borderWidth: 1,
            titleColor: ui.ink,
            bodyColor: ui.muted,
          }
        }
      }
    });
  }

  // ── Chart : Line (Candidatures par mois) ─────────────────────
  const lineCanvas = document.getElementById('chartLine');
  if (lineCanvas && typeof chartLine !== 'undefined') {
    new Chart(lineCanvas, {
      type: 'line',
      data: {
        labels:   chartLine.labels,
        datasets: [{
          label:           'Candidatures',
          data:            chartLine.values,
          borderColor:     ui.indigo,
          backgroundColor: 'rgba(148,0,211,.08)',
          borderWidth:     2,
          tension:         .4,
          fill:            true,
          pointBackgroundColor: ui.indigo,
          pointBorderColor:    '#FFFCF6',
          pointBorderWidth:    2,
          pointRadius:         4,
          pointHoverRadius:    6,
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          x: {
            ticks: { color: ui.dim, font: { size: 10 } },
            grid:  { color: ui.grid },
          },
          y: {
            beginAtZero: true,
            ticks: { color: ui.dim, font: { size: 10 }, stepSize: 1 },
            grid:  { color: ui.grid },
          }
        },
        plugins: {
          legend: { display: false },
          tooltip: {
            backgroundColor: ui.panel,
            borderColor: 'rgba(17,24,39,.12)',
            borderWidth: 1,
            titleColor: ui.ink,
            bodyColor: ui.muted,
          }
        }
      }
    });
  }

  function esc(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
  }

  // Analyse IA locale candidature
  const aiSummaryBtn = document.getElementById('ai-summary-btn');
  const aiSummaryResult = document.getElementById('ai-summary-result');
  const aiSummaryStatus = document.getElementById('ai-summary-status');

  if (aiSummaryBtn && aiSummaryResult && aiSummaryStatus) {
    const initialLabel = aiSummaryBtn.textContent.trim();

    aiSummaryBtn.addEventListener('click', () => {
      const url = aiSummaryBtn.dataset.aiUrl;
      if (!url) return;

      aiSummaryBtn.disabled = true;
      aiSummaryBtn.textContent = 'Analyse en cours...';
      aiSummaryStatus.textContent = 'Preparation de la synthese locale.';
      aiSummaryStatus.classList.remove('error');

      fetch(url, { headers: { 'Accept': 'application/json' } })
        .then(r => r.json().then(data => ({ ok: r.ok, data })))
        .then(({ ok, data }) => {
          if (!ok || !data.success) {
            throw new Error(data.message || 'Analyse indisponible.');
          }

          renderAiSummary(data.data);
          aiSummaryStatus.textContent = 'Analyse generee localement.';
        })
        .catch(err => {
          aiSummaryResult.classList.add('hidden');
          aiSummaryResult.innerHTML = '';
          aiSummaryStatus.textContent = err.message || 'Analyse indisponible.';
          aiSummaryStatus.classList.add('error');
        })
        .finally(() => {
          aiSummaryBtn.disabled = false;
          aiSummaryBtn.textContent = initialLabel;
        });
    });
  }

  function renderAiSummary(data) {
    if (!aiSummaryResult || !data) return;

    aiSummaryResult.innerHTML = `
      <p class="ai-summary-overview">${esc(data.overview || '')}</p>
      <div class="ai-summary-grid">
        ${renderAiSection('Forces', data.strengths)}
        ${renderAiSection('Points a verifier', data.risks)}
        ${renderAiSection('Questions d entretien', data.questions)}
      </div>
      <div class="ai-summary-note">
        <strong>Note interne proposee</strong><br>
        ${esc(data.note || '')}
      </div>`;

    aiSummaryResult.classList.remove('hidden');
  }

  function renderAiSection(title, items) {
    const list = Array.isArray(items) && items.length ? items : ['Aucune suggestion disponible.'];
    return `<section class="ai-summary-section">
      <h3>${esc(title)}</h3>
      <ul class="ai-summary-list">
        ${list.map(item => `<li>${esc(item)}</li>`).join('')}
      </ul>
    </section>`;
  }

  // ── Modal Décision Entretien ──────────────────────────────────
  window.openDecision = (id, current) => {
    const modal  = document.getElementById('modal-decision');
    const form   = document.getElementById('form-decision');
    const select = document.getElementById('select-decision');
    if (!modal || !form) return;
    form.action    = `index.php?url=entretiens/decision/${id}`;
    if (select) select.value = current;
    modal.classList.remove('hidden');
  };
  window.closeDecision = () => {
    document.getElementById('modal-decision')?.classList.add('hidden');
  };

  // Close modal on backdrop click
  document.getElementById('modal-decision')?.addEventListener('click', e => {
    if (e.target === e.currentTarget) closeDecision();
  });

  // ── Entretien: type change ────────────────────────────────────
  const typeSelect = document.getElementById('type-select');
  const lieuLabel  = document.getElementById('lieu-label');
  const lieuInput  = document.getElementById('lieu-input');
  if (typeSelect && lieuLabel && lieuInput) {
    typeSelect.addEventListener('change', () => {
      if (typeSelect.value === 'Visio') {
        lieuLabel.textContent  = 'Lien de réunion (Zoom, Teams…) *';
        lieuInput.placeholder  = 'https://meet.google.com/xxx-yyy-zzz';
        lieuInput.type         = 'url';
      } else {
        lieuLabel.textContent  = 'Lieu *';
        lieuInput.placeholder  = 'Ex: Bureau RH, 2ème étage';
        lieuInput.type         = 'text';
      }
    });
  }

  // ── Toast System ─────────────────────────────────────────────
  window.showToast = (msg, type = 'info', duration = 4000) => {
    const container = document.getElementById('toast-container');
    if (!container) return;
    const icons = { success: '✅', error: '❌', info: 'ℹ️', warning: '⚠️' };
    const toast  = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerHTML = `
      <span>${icons[type] || 'ℹ️'}</span>
      <span class="toast-msg">${esc(msg)}</span>
      <span class="toast-close" onclick="this.closest('.toast').remove()">✕</span>`;
    container.appendChild(toast);
    setTimeout(() => {
      toast.style.opacity = '0';
      toast.style.transition = 'opacity .4s';
      setTimeout(() => toast.remove(), 400);
    }, duration);
  };

  // Show flash alerts as toasts
  document.querySelectorAll('[data-flash]').forEach(el => {
    const type = el.dataset.flash || 'info';
    showToast(el.textContent, type);
    el.remove();
  });

  // Auto-dismiss alerts
  document.querySelectorAll('.alert').forEach(el => {
    setTimeout(() => {
      el.style.transition = 'opacity .5s';
      el.style.opacity = '0';
      setTimeout(() => el.remove(), 500);
    }, 5000);
  });

  // Row links — clickable table rows / cards ─────────────────
  // Any element with [data-href] becomes a clickable surface that
  // navigates to that URL (unless the user actually clicked a real
  // <a> or <button> inside it — those keep working normally).
  document.querySelectorAll('[data-href]').forEach(el => {
    if (el.closest('[data-vue-island]')) return;

    el.addEventListener('click', e => {
      if (e.target.closest('a, button, input, select, textarea, label')) return;
      const href = el.dataset.href;
      if (!href) return;
      if (e.ctrlKey || e.metaKey || e.button === 1) {
        window.open(href, '_blank');
      } else {
        window.location.href = href;
      }
    });
    // Keyboard accessibility
    if (!el.hasAttribute('tabindex')) el.setAttribute('tabindex', '0');
    el.addEventListener('keydown', e => {
      if ((e.key === 'Enter' || e.key === ' ') && e.target === el) {
        e.preventDefault();
        window.location.href = el.dataset.href;
      }
    });
  });

  // Confirm Delete ────────────────────────────────────────────
  document.querySelectorAll('[data-confirm]').forEach(el => {
    el.addEventListener('click', e => {
      if (!confirm(el.dataset.confirm || 'Confirmer cette action ?')) {
        e.preventDefault();
      }
    });
  });

  // ── Score fill animation ──────────────────────────────────────
  document.querySelectorAll('.score-fill').forEach(el => {
    const w = el.style.width;
    el.style.width = '0';
    setTimeout(() => { el.style.width = w; }, 300);
  });

});



