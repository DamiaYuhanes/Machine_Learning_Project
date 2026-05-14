/* ============================================================
   FlightGo — Main JavaScript
   ============================================================ */

(function () {
  'use strict';

  /* ---- Helpers ---- */
  const $ = (sel, ctx = document) => ctx.querySelector(sel);
  const $$ = (sel, ctx = document) => [...ctx.querySelectorAll(sel)];

  /* ---- Trip Type Tabs ---- */
  const tripTabs = $$('.trip-tab');
  const tripInput = $('#trip-type');
  const returnField = $('#return-field');

  tripTabs.forEach(tab => {
    tab.addEventListener('click', () => {
      tripTabs.forEach(t => t.classList.remove('active'));
      tab.classList.add('active');
      const type = tab.dataset.trip;
      if (tripInput) tripInput.value = type;
      if (returnField) {
        returnField.style.display = type === 'return' ? 'flex' : 'none';
      }
    });
  });

  /* ---- Swap Airports ---- */
  const swapBtn = $('#swap-btn');
  if (swapBtn) {
    swapBtn.addEventListener('click', () => {
      const fromSel = $('#from');
      const toSel   = $('#to');
      if (!fromSel || !toSel) return;
      const tmp = fromSel.value;
      fromSel.value = toSel.value;
      toSel.value   = tmp;
      swapBtn.style.transform = 'rotate(180deg)';
      setTimeout(() => swapBtn.style.transform = '', 300);
    });
  }

  /* ---- Passenger Counter ---- */
  const paxMinus = $('#pax-minus');
  const paxPlus  = $('#pax-plus');
  const paxInput = $('#pax');

  if (paxMinus && paxPlus && paxInput) {
    paxMinus.addEventListener('click', () => {
      const v = parseInt(paxInput.value, 10);
      if (v > 1) paxInput.value = v - 1;
    });
    paxPlus.addEventListener('click', () => {
      const v = parseInt(paxInput.value, 10);
      if (v < 9) paxInput.value = v + 1;
    });
  }

  /* ---- Enforce Return Date >= Departure Date ---- */
  const depDate = $('#dep-date');
  const retDate = $('#ret-date');
  if (depDate && retDate) {
    depDate.addEventListener('change', () => {
      if (retDate.value && retDate.value < depDate.value) {
        retDate.value = depDate.value;
      }
      retDate.min = depDate.value;
    });
  }

  /* ---- Search Form Validation ---- */
  const searchForm = $('#search-form');
  if (searchForm) {
    searchForm.addEventListener('submit', e => {
      const from = $('#from');
      const to   = $('#to');
      if (from && to && from.value === to.value && from.value !== '') {
        e.preventDefault();
        showToast('Departure and destination cannot be the same city.', 'error');
      }
    });
  }

  /* ---- Price Range Live Label ---- */
  const priceRange = $('#price-range');
  const priceVal   = $('#price-val');
  if (priceRange && priceVal) {
    priceRange.addEventListener('input', () => {
      priceVal.textContent = 'RM ' + priceRange.value;
    });
  }

  /* ---- Flight Card Entrance Animation ---- */
  const cards = $$('.flight-card, .route-card, .feature-card, .monitor-card, .tip-card');
  if ('IntersectionObserver' in window) {
    const io = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.style.opacity = '1';
          entry.target.style.transform = 'translateY(0)';
          io.unobserve(entry.target);
        }
      });
    }, { threshold: 0.08 });

    cards.forEach((card, i) => {
      card.style.opacity = '0';
      card.style.transform = 'translateY(20px)';
      card.style.transition = `opacity .4s ease ${i * 0.05}s, transform .4s ease ${i * 0.05}s`;
      io.observe(card);
    });
  }

  /* ---- Toast Notification ---- */
  function showToast(msg, type = 'info') {
    const existing = $('.tm-toast');
    if (existing) existing.remove();

    const toast = document.createElement('div');
    toast.className = 'tm-toast';
    toast.textContent = msg;
    Object.assign(toast.style, {
      position: 'fixed', bottom: '24px', right: '24px',
      padding: '14px 20px', borderRadius: '8px',
      background: type === 'error' ? '#e63946' : '#1d3557',
      color: '#fff', fontWeight: '600', fontSize: '.9rem',
      zIndex: '9999', boxShadow: '0 4px 16px rgba(0,0,0,.2)',
      transform: 'translateY(20px)', opacity: '0',
      transition: 'all .3s ease',
    });
    document.body.appendChild(toast);
    requestAnimationFrame(() => {
      toast.style.transform = 'translateY(0)';
      toast.style.opacity = '1';
    });
    setTimeout(() => {
      toast.style.opacity = '0';
      toast.style.transform = 'translateY(20px)';
      setTimeout(() => toast.remove(), 300);
    }, 3500);
  }

  /* ---- Price Chart (Flight Details Page) ---- */
  const chartCanvas = $('#priceChart');
  if (chartCanvas && typeof chartData !== 'undefined') {
    drawPriceChart(chartCanvas, chartData);
  }

  function drawPriceChart(canvas, data) {
    const ctx = canvas.getContext('2d');
    const W = canvas.offsetWidth || 600;
    const H = canvas.offsetHeight || 200;
    canvas.width  = W * window.devicePixelRatio;
    canvas.height = H * window.devicePixelRatio;
    ctx.scale(window.devicePixelRatio, window.devicePixelRatio);

    const prices = data.prices;
    const labels = data.labels;
    const current = data.current;
    const minP = Math.min(...prices) * 0.95;
    const maxP = Math.max(...prices) * 1.05;
    const pad = { top: 20, right: 20, bottom: 40, left: 60 };
    const cw = W - pad.left - pad.right;
    const ch = H - pad.top - pad.bottom;

    const xStep = cw / (prices.length - 1);
    const yScale = v => ch - ((v - minP) / (maxP - minP)) * ch + pad.top;
    const xScale = i => pad.left + i * xStep;

    // Grid
    ctx.strokeStyle = '#e0e0e0';
    ctx.lineWidth = 1;
    [0, 0.25, 0.5, 0.75, 1].forEach(t => {
      const y = pad.top + t * ch;
      ctx.beginPath(); ctx.moveTo(pad.left, y); ctx.lineTo(pad.left + cw, y);
      ctx.stroke();
      ctx.fillStyle = '#999'; ctx.font = '11px Inter, sans-serif'; ctx.textAlign = 'right';
      ctx.fillText('RM ' + Math.round(maxP - t * (maxP - minP)), pad.left - 6, y + 4);
    });

    // Gradient fill
    const grad = ctx.createLinearGradient(0, pad.top, 0, pad.top + ch);
    grad.addColorStop(0, 'rgba(69,123,157,0.25)');
    grad.addColorStop(1, 'rgba(69,123,157,0)');
    ctx.beginPath();
    prices.forEach((p, i) => {
      const x = xScale(i), y = yScale(p);
      i === 0 ? ctx.moveTo(x, y) : ctx.lineTo(x, y);
    });
    ctx.lineTo(xScale(prices.length - 1), yScale(minP) + ch);
    ctx.lineTo(xScale(0), yScale(minP) + ch);
    ctx.closePath();
    ctx.fillStyle = grad;
    ctx.fill();

    // Line
    ctx.beginPath();
    ctx.strokeStyle = '#457b9d'; ctx.lineWidth = 2.5;
    ctx.lineJoin = 'round'; ctx.lineCap = 'round';
    prices.forEach((p, i) => {
      const x = xScale(i), y = yScale(p);
      i === 0 ? ctx.moveTo(x, y) : ctx.lineTo(x, y);
    });
    ctx.stroke();

    // Current price line
    const cy = yScale(current);
    ctx.beginPath();
    ctx.strokeStyle = '#e63946'; ctx.lineWidth = 1.5;
    ctx.setLineDash([5, 4]);
    ctx.moveTo(pad.left, cy); ctx.lineTo(pad.left + cw, cy);
    ctx.stroke(); ctx.setLineDash([]);
    ctx.fillStyle = '#e63946'; ctx.font = 'bold 11px Inter, sans-serif'; ctx.textAlign = 'left';
    ctx.fillText('Current: RM ' + current, pad.left + 4, cy - 6);

    // Dots
    prices.forEach((p, i) => {
      const x = xScale(i), y = yScale(p);
      ctx.beginPath();
      ctx.arc(x, y, 3.5, 0, Math.PI * 2);
      ctx.fillStyle = '#fff'; ctx.fill();
      ctx.strokeStyle = '#457b9d'; ctx.lineWidth = 2;
      ctx.stroke();
    });

    // X-axis labels (every 3rd)
    ctx.fillStyle = '#999'; ctx.font = '10px Inter, sans-serif'; ctx.textAlign = 'center';
    labels.forEach((l, i) => {
      if (i % 3 === 0) {
        const x = xScale(i);
        const d = new Date(l);
        const txt = (d.getDate()) + '/' + (d.getMonth() + 1);
        ctx.fillText(txt, x, pad.top + ch + 18);
      }
    });
  }

  /* ---- Sticky search bar shrink ---- */
  const stickyBar = $('.search-bar-compact');
  if (stickyBar) {
    window.addEventListener('scroll', () => {
      stickyBar.style.boxShadow = window.scrollY > 10
        ? '0 4px 16px rgba(0,0,0,.25)' : 'none';
    }, { passive: true });
  }

  /* ---- Mobile filter toggle ---- */
  const filterSidebar = $('.filters-sidebar');
  if (filterSidebar && window.innerWidth < 900) {
    const toggleBtn = document.createElement('button');
    toggleBtn.textContent = '⚙ Filters';
    Object.assign(toggleBtn.style, {
      position: 'fixed', bottom: '20px', left: '50%',
      transform: 'translateX(-50%)',
      padding: '12px 28px', background: '#1d3557',
      color: '#fff', border: 'none', borderRadius: '30px',
      fontWeight: '700', fontSize: '.95rem', zIndex: '200',
      boxShadow: '0 4px 16px rgba(0,0,0,.3)', cursor: 'pointer',
    });
    document.body.appendChild(toggleBtn);
    let open = false;
    toggleBtn.addEventListener('click', () => {
      open = !open;
      filterSidebar.style.display = open ? 'block' : '';
      toggleBtn.textContent = open ? '✕ Close Filters' : '⚙ Filters';
    });
    filterSidebar.style.display = 'none';
  }

  console.log('✈ FlightGo loaded');
})();
