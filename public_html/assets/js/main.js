// ============================================================
// KYN — Interaksi landing page
// ============================================================
(() => {
  'use strict';

  // ---- Filter program ----
  document.querySelectorAll('[data-filter-group]').forEach(group => {
    const chips = group.querySelectorAll('.chip');
    const grid  = document.querySelector('.program-grid');
    if (!grid) return;
    chips.forEach(chip => {
      chip.addEventListener('click', () => {
        chips.forEach(c => c.classList.remove('active'));
        chip.classList.add('active');
        const f = chip.dataset.filter;
        grid.querySelectorAll('.program-card').forEach(card => {
          card.style.display = (f === 'all' || card.dataset.category === f) ? '' : 'none';
        });
      });
    });
  });

  // ---- Toast auto-hide ----
  const toast = document.querySelector('.toast');
  if (toast) setTimeout(() => toast.remove(), 4200);

  // ---- Smooth scroll untuk anchor (fallback browser lama) ----
  document.querySelectorAll('a[href^="#"]').forEach(a => {
    a.addEventListener('click', e => {
      const id = a.getAttribute('href');
      if (id.length > 1) {
        const el = document.querySelector(id);
        if (el) { e.preventDefault(); el.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
      }
    });
  });

  // ---- Form: validasi client-side ringan ----
  const form = document.querySelector('.form-grid');
  if (form) {
    form.addEventListener('submit', e => {
      const name = form.querySelector('[name="full_name"]');
      const phone = form.querySelector('[name="phone_wa"]');
      const consent = form.querySelector('[name="consent"]');
      let ok = true;
      [name, phone].forEach(f => {
        if (f && !f.value.trim()) { f.style.borderColor = 'var(--bad)'; ok = false; }
        else if (f) f.style.borderColor = '';
      });
      if (consent && !consent.checked) { ok = false; alert('Centang persetujuan data pribadi.'); }
      if (!ok) { e.preventDefault(); }
    });
  }
})();