/* ============================================================
   Mia Flight n Go — Main JavaScript
   Handles: airport search, swap, pax counter, watchlist,
            price-check API, in-app notifications, chart
   ============================================================ */
(function () {
  'use strict';

  /* ── Helpers ─────────────────────────────────────────────── */
  const $ = (s, c = document) => c.querySelector(s);
  const $$ = (s, c = document) => [...c.querySelectorAll(s)];

  /* ── Toast ───────────────────────────────────────────────── */
  function toast(msg, type = 'info', duration = 3500) {
    $$('.fg-toast').forEach(t => t.remove());
    const el = document.createElement('div');
    el.className = `fg-toast fg-toast--${type}`;
    el.textContent = msg;
    document.body.appendChild(el);
    setTimeout(() => {
      el.style.opacity = '0'; el.style.transform = 'translateY(12px)';
      el.style.transition = 'all .3s ease';
      setTimeout(() => el.remove(), 320);
    }, duration);
  }

  /* ── Airport Autocomplete ────────────────────────────────── */
  const REGION_LABELS = {
    MY: '🇲🇾 Malaysia', SEA: '🌏 Southeast Asia', NEA: '🌏 Northeast Asia',
    SA: '🇮🇳 South Asia', ME: '🌍 Middle East', EU: '🇪🇺 Europe',
    AM: '🌎 Americas', OC: '🌏 Oceania', AF: '🌍 Africa',
  };

  function buildAirportDropdown(inputEl, hiddenEl, dropdownEl, opts = {}) {
    if (!inputEl || !hiddenEl || !dropdownEl) return;
    let focused = -1;

    function search(q) {
      if (!q || q.length < 1) { closeDropdown(); return; }
      q = q.toLowerCase();
      // Score and sort
      const scored = (window.AIRPORTS || []).map(ap => {
        let score = 0;
        if (ap.code.toLowerCase() === q)        score = 100;
        else if (ap.code.toLowerCase().startsWith(q)) score = 80;
        else if (ap.city.toLowerCase().startsWith(q)) score = 70;
        else if (ap.city.toLowerCase().includes(q))   score = 50;
        else if (ap.country.toLowerCase().includes(q)) score = 30;
        else if (ap.name.toLowerCase().includes(q))    score = 20;
        return { ap, score };
      }).filter(x => x.score > 0).sort((a, b) => b.score - a.score).slice(0, 20);

      if (!scored.length) { closeDropdown(); return; }

      // Group by region
      const grouped = {};
      scored.forEach(({ ap }) => {
        if (!grouped[ap.region]) grouped[ap.region] = [];
        grouped[ap.region].push(ap);
      });

      dropdownEl.innerHTML = '';
      Object.entries(grouped).forEach(([r, aps]) => {
        const hdr = document.createElement('div');
        hdr.className = 'airport-group-header';
        hdr.textContent = REGION_LABELS[r] || r;
        dropdownEl.appendChild(hdr);
        aps.forEach(ap => {
          const opt = document.createElement('div');
          opt.className = 'airport-option';
          opt.innerHTML = `<span class="airport-option-code">${ap.code}</span>
            <span class="airport-option-city">${ap.city}</span>
            <div class="airport-option-detail">${ap.name} · ${ap.country}</div>`;
          opt.addEventListener('mousedown', e => {
            e.preventDefault();
            selectAirport(ap);
          });
          dropdownEl.appendChild(opt);
        });
      });
      dropdownEl.classList.add('open');
      focused = -1;
    }

    function selectAirport(ap) {
      inputEl.value = `${ap.city} (${ap.code})`;
      hiddenEl.value = ap.code;
      closeDropdown();
      if (opts.onSelect) opts.onSelect(ap);
    }

    function closeDropdown() {
      dropdownEl.classList.remove('open');
      dropdownEl.innerHTML = '';
      focused = -1;
    }

    inputEl.addEventListener('input', () => search(inputEl.value.trim()));
    inputEl.addEventListener('focus', () => {
      if (inputEl.value.trim().length >= 1) search(inputEl.value.trim());
    });
    inputEl.addEventListener('blur', () => setTimeout(closeDropdown, 200));
    inputEl.addEventListener('keydown', e => {
      const opts = $$('.airport-option', dropdownEl);
      if (e.key === 'ArrowDown') { e.preventDefault(); focused = Math.min(focused + 1, opts.length - 1); opts.forEach((o, i) => o.classList.toggle('focused', i === focused)); opts[focused]?.scrollIntoView({ block: 'nearest' }); }
      else if (e.key === 'ArrowUp') { e.preventDefault(); focused = Math.max(focused - 1, 0); opts.forEach((o, i) => o.classList.toggle('focused', i === focused)); }
      else if (e.key === 'Enter') { e.preventDefault(); if (focused >= 0) opts[focused]?.dispatchEvent(new MouseEvent('mousedown', { bubbles: true })); }
      else if (e.key === 'Escape') closeDropdown();
    });
  }

  // Wire up airport inputs on index/search pages
  buildAirportDropdown($('#from-search'), $('#from'), $('#from-dropdown'));
  buildAirportDropdown($('#to-search'),   $('#to'),   $('#to-dropdown'));

  // Compact search bar (search.php)
  buildAirportDropdown($('#c-from-search'), $('#c-from'), $('#c-from-dropdown'));
  buildAirportDropdown($('#c-to-search'),   $('#c-to'),   $('#c-to-dropdown'));

  const compactForm = $('.compact-form');
  if (compactForm) {
    compactForm.addEventListener('submit', e => {
      const t = $('#c-to');
      if (!t || !t.value) {
        e.preventDefault();
        toast('Please select a destination airport.', 'error');
        $('#c-to-search')?.focus();
      }
    });
  }

  // Watchlist quick-add form (watchlist.php)
  buildAirportDropdown($('#qw-from-search'), $('#qw-from'), $('#qw-from-dropdown'));
  buildAirportDropdown($('#qw-to-search'),   $('#qw-to'),   $('#qw-to-dropdown'));

  /* ── Trip Type Tabs ──────────────────────────────────────── */
  const tripTabs   = $$('.trip-tab');
  const tripInput  = $('#trip-type');
  const returnFld  = $('#return-field');
  tripTabs.forEach(tab => {
    tab.addEventListener('click', () => {
      tripTabs.forEach(t => t.classList.remove('active'));
      tab.classList.add('active');
      const type = tab.dataset.trip;
      if (tripInput) tripInput.value = type;
      if (returnFld) returnFld.style.display = type === 'return' ? 'flex' : 'none';
    });
  });

  /* ── Swap Airports ───────────────────────────────────────── */
  const swapBtn = $('#swap-btn');
  if (swapBtn) {
    swapBtn.addEventListener('click', () => {
      const fi = $('#from'), ti = $('#to'), fs = $('#from-search'), ts = $('#to-search');
      if (!fi || !ti) return;
      [fi.value, ti.value] = [ti.value, fi.value];
      if (fs && ts) [fs.value, ts.value] = [ts.value, fs.value];
      swapBtn.style.transform = 'rotate(180deg)';
      setTimeout(() => swapBtn.style.transform = '', 300);
    });
  }

  /* ── Passenger Counter ───────────────────────────────────── */
  const paxMinus = $('#pax-minus'), paxPlus = $('#pax-plus'), paxInput = $('#pax');
  if (paxMinus && paxPlus && paxInput) {
    paxMinus.addEventListener('click', () => { if (+paxInput.value > 1) paxInput.value = +paxInput.value - 1; });
    paxPlus.addEventListener('click',  () => { if (+paxInput.value < 9) paxInput.value = +paxInput.value + 1; });
  }

  /* ── Return Date Guard ───────────────────────────────────── */
  const depDate = $('#dep-date'), retDate = $('#ret-date');
  if (depDate && retDate) {
    depDate.addEventListener('change', () => {
      if (retDate.value && retDate.value < depDate.value) retDate.value = depDate.value;
      retDate.min = depDate.value;
    });
  }

  /* ── Search Form Validation ──────────────────────────────── */
  const searchForm = $('#search-form');
  if (searchForm) {
    searchForm.addEventListener('submit', e => {
      const f = $('#from'), t = $('#to');
      if (!t || !t.value) {
        e.preventDefault();
        toast('Please select a destination airport.', 'error');
        $('#to-search')?.focus();
        return;
      }
      if (f && t && f.value && t.value && f.value === t.value) {
        e.preventDefault();
        toast('Departure and destination cannot be the same.', 'error');
      }
    });
  }

  /* ── Price Range Label ───────────────────────────────────── */
  const priceRange = $('#price-range'), priceVal = $('#price-val');
  if (priceRange && priceVal) {
    priceRange.addEventListener('input', () => {
      priceVal.textContent = 'RM ' + Number(priceRange.value).toLocaleString();
    });
  }

  /* ════════════════════════════════════════════════════════════
     WATCHLIST ENGINE
     Stores items in localStorage. Each item:
     { id, from, to, fromCity, toCity, date, targetPrice, airline, flightNo, dep, addedAt }
  ════════════════════════════════════════════════════════════ */
  const WL_KEY = 'mia_flightngo_watchlist_v2';

  function wlLoad()        { try { return JSON.parse(localStorage.getItem(WL_KEY) || '[]'); } catch { return []; } }
  function wlSave(items)   { localStorage.setItem(WL_KEY, JSON.stringify(items)); }
  function wlAdd(item)     { const items = wlLoad(); if (!items.find(i => i.id === item.id)) { items.push(item); wlSave(items); } }
  function wlRemove(id)    { wlSave(wlLoad().filter(i => i.id !== id)); }
  function wlClear()       { wlSave([]); }

  function makeWlId(from, to, date) {
    return `${from}-${to}-${date}`;
  }

  /* ── Watchlist Modal ─────────────────────────────────────── */
  let _pendingWlData = null;

  function openWatchlistModal(data) {
    _pendingWlData = data;
    const modal = $('#watchlist-modal');
    if (!modal) return;
    const route = $('#modal-route'), cp = $('#modal-current-price'), target = $('#modal-target');
    if (route) route.textContent = `${data.fromCity || data.from} → ${data.toCity || data.to}`;
    if (cp)    cp.textContent    = `RM ${Number(data.price).toLocaleString()}`;
    if (target) { target.value = ''; target.focus(); }
    modal.style.display = 'flex';
  }

  function closeWatchlistModal() {
    const modal = $('#watchlist-modal');
    if (modal) modal.style.display = 'none';
    _pendingWlData = null;
  }

  function confirmWatchlist() {
    const target = +($('#modal-target')?.value || 0);
    if (!_pendingWlData) return;
    if (!target || target < 50) { toast('Please enter a valid target price (min RM 50).', 'error'); return; }

    const id   = makeWlId(_pendingWlData.from, _pendingWlData.to, _pendingWlData.date);
    const item = {
      id,
      from:        _pendingWlData.from,
      to:          _pendingWlData.to,
      fromCity:    _pendingWlData.fromCity || _pendingWlData.from,
      toCity:      _pendingWlData.toCity   || _pendingWlData.to,
      date:        _pendingWlData.date,
      targetPrice: target,
      currentPriceAtAdd: +_pendingWlData.price,
      airline:     _pendingWlData.airline   || '',
      flightNo:    _pendingWlData.flightNo  || '',
      dep:         _pendingWlData.dep       || '',
      addedAt:     new Date().toISOString(),
    };

    wlAdd(item);
    closeWatchlistModal();
    toast(`✅ Added ${item.fromCity} → ${item.toCity} to watchlist! Target: RM ${target.toLocaleString()}`, 'success');

    // Mark watch buttons for this route
    $$('.watchlist-add, .watch-btn').forEach(btn => {
      if (btn.dataset.from === item.from && btn.dataset.to === item.to && btn.dataset.date === item.date) {
        btn.classList.add('watching');
        btn.textContent = '✓ Watching';
      }
    });

    updateBellBadge();
    setTimeout(checkPricesAndNotify, 400);
  }

  // Wire modal buttons
  document.addEventListener('click', e => {
    // Open modal from any "watchlist-add" or "watchlist-quick-add" button
    if (e.target.matches('.watchlist-add, .watch-btn, .watchlist-quick-add')) {
      const d = e.target.dataset;
      openWatchlistModal({ from: d.from, to: d.to, date: d.date, price: d.price, fromCity: d.fromCity, toCity: d.toCity, airline: d.airline || '', flightNo: d.flightNo || '', dep: d.dep || '' });
    }
    if (e.target.matches('#modal-close, #modal-cancel')) closeWatchlistModal();
    if (e.target.matches('#modal-confirm')) confirmWatchlist();
    if (e.target.matches('.modal-overlay') && e.target.id === 'watchlist-modal') closeWatchlistModal();
  });
  document.addEventListener('keydown', e => { if (e.key === 'Escape') closeWatchlistModal(); });

  // Mark already-watched buttons on page load
  function markWatchedButtons() {
    const items = wlLoad();
    $$('.watchlist-add, .watch-btn').forEach(btn => {
      const id = makeWlId(btn.dataset.from, btn.dataset.to, btn.dataset.date);
      if (items.find(i => i.id === id)) {
        btn.classList.add('watching');
        btn.textContent = '✓ Watching';
      }
    });
  }
  markWatchedButtons();

  /* ── Price Check API ─────────────────────────────────────── */
  let _lastCheckResults = [];

  async function checkPricesAndNotify() {
    const items = wlLoad();
    if (!items.length) { updateBellBadge(0); return; }

    try {
      // Determine base path for API
      const isInRoot = !window.location.pathname.includes('/api/');
      const apiPath  = isInRoot ? 'api/check-prices.php' : '../api/check-prices.php';

      const res = await fetch(apiPath, {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify(items.map(i => ({ id: i.id, from: i.from, to: i.to, date: i.date, targetPrice: i.targetPrice }))),
      });

      if (!res.ok) return;
      const data = await res.json();
      if (!data.ok) return;

      _lastCheckResults = data.results || [];
      updateBellBadge(data.matched_count || 0);
      buildNotifDropdown(_lastCheckResults, items);
    } catch (err) {
      // API unreachable (e.g. no PHP server) — simulate with local prices
      simulatePriceCheck(items);
    }
  }

  // Fallback when no PHP server is running
  function simulatePriceCheck(items) {
    const results = items.map(i => {
      const fakeCurrent = Math.round(i.currentPriceAtAdd * (0.85 + Math.random() * 0.35));
      return { id: i.id, ok: true, from: i.from, to: i.to, date: i.date, currentPrice: fakeCurrent, targetPrice: i.targetPrice, matched: fakeCurrent <= i.targetPrice, diff: fakeCurrent - i.targetPrice };
    });
    _lastCheckResults = results;
    const matched = results.filter(r => r.matched).length;
    updateBellBadge(matched);
    buildNotifDropdown(results, items);
  }

  /* ── Bell Badge ──────────────────────────────────────────── */
  function updateBellBadge(count) {
    if (count === undefined) {
      const matched = _lastCheckResults.filter(r => r.matched).length;
      count = matched;
    }
    const badge = $('#bell-badge');
    if (!badge) return;
    if (count > 0) {
      badge.textContent = count;
      badge.style.display = 'flex';
    } else {
      badge.style.display = 'none';
    }
  }

  /* ── Notification Dropdown ───────────────────────────────── */
  function buildNotifDropdown(results, wlItems) {
    const body = $('#notif-body');
    if (!body) return;
    if (!results.length) { body.innerHTML = '<p class="notif-empty">Your watchlist is empty.</p>'; return; }

    body.innerHTML = '';
    results.forEach(r => {
      const wl = wlItems.find(i => i.id === r.id);
      if (!wl) return;
      const matched = r.matched;
      const diff    = r.diff || 0;

      const item = document.createElement('div');
      item.className = `notif-item ${matched ? 'notif-item--matched' : 'notif-item--above'}`;

      const dotCls = matched ? 'notif-dot--green' : (Math.abs(diff) < wl.targetPrice * 0.1 ? 'notif-dot--yellow' : 'notif-dot--red');
      const diffStr = diff <= 0 ? `✅ RM ${Math.abs(diff).toLocaleString()} below target!` : `RM ${diff.toLocaleString()} above target`;

      item.innerHTML = `
        <div class="notif-dot ${dotCls}"></div>
        <div class="notif-item-text">
          <div class="notif-item-route">${wl.fromCity} → ${wl.toCity}</div>
          <div class="notif-item-price">Current: <strong>RM ${(r.currentPrice||0).toLocaleString()}</strong> · Target: RM ${wl.targetPrice.toLocaleString()}</div>
          ${matched ? `<div class="notif-item-match">${diffStr}</div>` : `<div style="font-size:.78rem;color:var(--text-muted)">${diffStr}</div>`}
          <a href="search.php?from=${wl.from}&to=${wl.to}&date=${wl.date}&pax=1" class="notif-item-link">Search now →</a>
        </div>`;
      body.appendChild(item);
    });
  }

  /* ── Bell Toggle ─────────────────────────────────────────── */
  const bell     = $('#notif-bell');
  const dropdown = $('#notif-dropdown');
  const overlay  = $('#notif-overlay');
  const closeBtn = $('#notif-close');

  function openNotif() {
    if (!dropdown) return;
    dropdown.style.display = 'block';
    overlay.style.display  = 'block';
  }
  function closeNotif() {
    if (!dropdown) return;
    dropdown.style.display = 'none';
    overlay.style.display  = 'none';
  }
  bell?.addEventListener('click', () => dropdown?.style.display === 'none' ? openNotif() : closeNotif());
  closeBtn?.addEventListener('click', closeNotif);
  overlay?.addEventListener('click', closeNotif);

  /* ════════════════════════════════════════════════════════════
     WATCHLIST PAGE
  ════════════════════════════════════════════════════════════ */
  const wlItemsEl   = $('#watchlist-items');
  const wlEmptyEl   = $('#watchlist-empty');
  const wlLoadingEl = $('#watchlist-loading');
  const wlStatsEl   = $('#watchlist-stats');
  const wlActionsEl = $('#watchlist-actions');
  const wlQwCard    = $('#quick-watch-card');

  if (wlItemsEl) { // We're on watchlist.php
    renderWatchlistPage();
  }

  async function renderWatchlistPage() {
    const items = wlLoad();

    if (wlLoadingEl) wlLoadingEl.style.display = 'block';
    if (wlEmptyEl)   wlEmptyEl.style.display   = 'none';

    if (!items.length) {
      if (wlLoadingEl) wlLoadingEl.style.display = 'none';
      if (wlEmptyEl)   wlEmptyEl.style.display   = 'block';
      return;
    }

    // Fetch current prices
    let results = [];
    try {
      const res = await fetch('api/check-prices.php', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(items.map(i => ({ id: i.id, from: i.from, to: i.to, date: i.date, targetPrice: i.targetPrice }))),
      });
      const data = await res.json();
      results = data.results || [];
    } catch {
      results = items.map(i => ({
        id: i.id, ok: true,
        currentPrice: Math.round(i.currentPriceAtAdd * (0.88 + Math.random() * 0.30)),
        targetPrice: i.targetPrice,
        matched: false, diff: 0,
      }));
      results.forEach(r => { r.matched = r.currentPrice <= r.targetPrice; r.diff = r.currentPrice - r.targetPrice; });
    }

    if (wlLoadingEl) wlLoadingEl.style.display = 'none';

    // Stats
    const matched = results.filter(r => r.matched).length;
    const above   = results.filter(r => !r.matched).length;
    if (wlStatsEl) {
      wlStatsEl.style.display = 'flex';
      $('#stat-total').textContent   = items.length;
      $('#stat-matched').textContent = matched;
      $('#stat-above').textContent   = above;
    }
    if (wlActionsEl) wlActionsEl.style.display = 'flex';

    // Render items
    wlItemsEl.innerHTML = '';
    items.forEach(wl => {
      const r = results.find(x => x.id === wl.id) || {};
      const cur = r.currentPrice || wl.currentPriceAtAdd || 0;
      const tgt = wl.targetPrice;
      const diff = cur - tgt;
      const matched = diff <= 0;

      const card = document.createElement('div');
      card.className = `wl-item ${matched ? 'wl-item--matched' : 'wl-item--above'}`;
      card.dataset.id = wl.id;

      const dotCls = matched ? 'status-dot--green' : (diff < tgt * 0.15 ? 'status-dot--yellow' : 'status-dot--red');
      const diffStr = matched ? `▼ RM ${Math.abs(diff).toLocaleString()} below target` : `▲ RM ${Math.abs(diff).toLocaleString()} above target`;
      const diffCls = matched ? 'wl-diff--below' : 'wl-diff--above';
      const dateStr = new Date(wl.date).toLocaleDateString('en-MY', { day: 'numeric', month: 'short', year: 'numeric' });

      card.innerHTML = `
        <div class="wl-item-status ${dotCls}"></div>
        <div class="wl-item-route">
          <div class="wl-route-cities">
            <span class="wl-from-city">${wl.fromCity}</span>
            <span class="wl-arrow">✈</span>
            <span class="wl-to-city">${wl.toCity}</span>
          </div>
          <div class="wl-meta">${dateStr} · ${wl.from} → ${wl.to}${wl.airline ? ' · ' + wl.airline : ''}</div>
          ${matched ? '<div class="wl-matched-banner">🎉 Price matched your target!</div>' : ''}
        </div>
        <div class="wl-price-info">
          <div class="wl-current-section">
            <div class="wl-price-label">Current</div>
            <div class="wl-current-price">RM ${cur.toLocaleString()}</div>
          </div>
          <div class="wl-vs">vs</div>
          <div class="wl-target-section">
            <div class="wl-price-label">Target</div>
            <div class="wl-target-price">RM ${tgt.toLocaleString()}</div>
          </div>
          <div class="wl-diff-section">
            <div class="wl-price-label">Diff</div>
            <div class="wl-diff ${diffCls}">${diffStr}</div>
          </div>
        </div>
        <div class="wl-item-actions">
          <a href="search.php?from=${wl.from}&to=${wl.to}&date=${wl.date}&pax=1" class="wl-search-btn">🔍 Search</a>
          <button class="wl-remove-btn" data-id="${wl.id}">🗑 Remove</button>
        </div>`;
      wlItemsEl.appendChild(card);
    });

    // Remove buttons
    $$('.wl-remove-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        wlRemove(btn.dataset.id);
        btn.closest('.wl-item')?.remove();
        const remaining = wlLoad().length;
        if (!remaining) { wlStatsEl.style.display = 'none'; wlEmptyEl.style.display = 'block'; wlActionsEl.style.display = 'none'; }
        $('#stat-total').textContent = remaining;
        toast('Removed from watchlist.', 'info');
        updateBellBadge();
      });
    });

    // Clear all
    $('#wl-clear-all')?.addEventListener('click', () => {
      if (!confirm('Remove all items from your watchlist?')) return;
      wlClear();
      wlItemsEl.innerHTML = '';
      wlStatsEl.style.display  = 'none';
      wlActionsEl.style.display = 'none';
      wlEmptyEl.style.display  = 'block';
      updateBellBadge(0);
      toast('Watchlist cleared.', 'info');
    });

    // Show quick-watch form
    $('#show-quick-watch')?.addEventListener('click', () => {
      if (wlQwCard) { wlQwCard.style.display = wlQwCard.style.display === 'none' ? 'block' : 'none'; }
    });

    // Quick-Watch Add
    $('#qw-add-btn')?.addEventListener('click', () => {
      const from   = $('#qw-from')?.value;
      const to     = $('#qw-to')?.value;
      const date   = $('#qw-date')?.value;
      const target = +($('#qw-target')?.value || 0);
      if (!from || !to || !date || !target) { toast('Please fill all fields.', 'error'); return; }
      if (from === to) { toast('From and To cannot be the same.', 'error'); return; }

      const fromSearch = $('#qw-from-search')?.value || from;
      const toSearch   = $('#qw-to-search')?.value   || to;
      const fromCity   = fromSearch.replace(/\s*\(.*\)/, '').trim() || from;
      const toCity     = toSearch.replace(/\s*\(.*\)/, '').trim()   || to;

      wlAdd({ id: makeWlId(from, to, date), from, to, fromCity, toCity, date, targetPrice: target, currentPriceAtAdd: target, addedAt: new Date().toISOString() });
      toast(`Added ${fromCity} → ${toCity} to watchlist!`, 'success');
      renderWatchlistPage();
    });

    _lastCheckResults = results;
    updateBellBadge(matched);
    buildNotifDropdown(results, items);
  }

  /* ── Price History Chart ─────────────────────────────────── */
  const chartCanvas = $('#priceChart');
  if (chartCanvas && typeof chartData !== 'undefined') drawChart(chartCanvas, chartData);

  function drawChart(canvas, data) {
    const dpr = window.devicePixelRatio || 1;
    const W = canvas.offsetWidth || 580, H = canvas.offsetHeight || 160;
    canvas.width = W * dpr; canvas.height = H * dpr;
    const ctx = canvas.getContext('2d');
    ctx.scale(dpr, dpr);

    const prices = data.prices, labels = data.labels, cur = data.current;
    const minP = Math.min(...prices) * 0.94, maxP = Math.max(...prices) * 1.06;
    const pad = { t: 22, r: 18, b: 36, l: 68 };
    const cw = W - pad.l - pad.r, ch = H - pad.t - pad.b;

    const xS = i => pad.l + (i / (prices.length - 1)) * cw;
    const yS = v => pad.t + ch - ((v - minP) / (maxP - minP)) * ch;

    // Grid
    ctx.strokeStyle = '#e8eaf0'; ctx.lineWidth = 1;
    [0, .25, .5, .75, 1].forEach(t => {
      const y = pad.t + t * ch;
      ctx.beginPath(); ctx.moveTo(pad.l, y); ctx.lineTo(pad.l + cw, y); ctx.stroke();
      ctx.fillStyle = '#9ca3af'; ctx.font = '11px Inter,sans-serif'; ctx.textAlign = 'right';
      ctx.fillText('RM' + Math.round(maxP - t * (maxP - minP)).toLocaleString(), pad.l - 5, y + 4);
    });

    // Fill gradient
    const grad = ctx.createLinearGradient(0, pad.t, 0, pad.t + ch);
    grad.addColorStop(0, 'rgba(69,123,157,.22)'); grad.addColorStop(1, 'rgba(69,123,157,0)');
    ctx.beginPath();
    prices.forEach((p, i) => i === 0 ? ctx.moveTo(xS(i), yS(p)) : ctx.lineTo(xS(i), yS(p)));
    ctx.lineTo(xS(prices.length - 1), pad.t + ch); ctx.lineTo(xS(0), pad.t + ch);
    ctx.closePath(); ctx.fillStyle = grad; ctx.fill();

    // Line
    ctx.beginPath(); ctx.strokeStyle = '#457b9d'; ctx.lineWidth = 2.5; ctx.lineJoin = 'round';
    prices.forEach((p, i) => i === 0 ? ctx.moveTo(xS(i), yS(p)) : ctx.lineTo(xS(i), yS(p)));
    ctx.stroke();

    // Current price dashed line
    const cy = yS(cur);
    ctx.beginPath(); ctx.strokeStyle = '#e63946'; ctx.lineWidth = 1.5; ctx.setLineDash([5, 4]);
    ctx.moveTo(pad.l, cy); ctx.lineTo(pad.l + cw, cy); ctx.stroke(); ctx.setLineDash([]);
    ctx.fillStyle = '#e63946'; ctx.font = 'bold 11px Inter,sans-serif'; ctx.textAlign = 'left';
    ctx.fillText('Now: RM' + cur.toLocaleString(), pad.l + 4, cy - 6);

    // Dots
    prices.forEach((p, i) => {
      ctx.beginPath(); ctx.arc(xS(i), yS(p), 3.5, 0, Math.PI * 2);
      ctx.fillStyle = '#fff'; ctx.fill(); ctx.strokeStyle = '#457b9d'; ctx.lineWidth = 2; ctx.stroke();
    });

    // X labels
    ctx.fillStyle = '#9ca3af'; ctx.font = '10px Inter,sans-serif'; ctx.textAlign = 'center';
    labels.forEach((l, i) => {
      if (i % 3 === 0) {
        const d = new Date(l);
        ctx.fillText(`${d.getDate()}/${d.getMonth()+1}`, xS(i), pad.t + ch + 18);
      }
    });
  }

  /* ── Card Entrance Animation ─────────────────────────────── */
  if ('IntersectionObserver' in window) {
    const io = new IntersectionObserver(entries => {
      entries.forEach(e => { if (e.isIntersecting) { e.target.style.opacity = '1'; e.target.style.transform = 'translateY(0)'; io.unobserve(e.target); } });
    }, { threshold: 0.07 });
    $$('.flight-card, .route-card, .feature-card, .monitor-card, .tip-card, .wl-item').forEach((el, i) => {
      el.style.cssText += `opacity:0;transform:translateY(18px);transition:opacity .4s ease ${i*0.04}s,transform .4s ease ${i*0.04}s`;
      io.observe(el);
    });
  }

  /* ── Init: check prices on every page load ───────────────── */
  setTimeout(checkPricesAndNotify, 600);

  // ── Client-side filters (time + duration) ──────────────────
  function applyClientFilters() {
    const checkedTimes = [...$$('.dep-time-cb:checked')].map(c => c.value);
    const maxDur = parseInt($('#dur-filter')?.value || 25);
    $$('.flight-card').forEach(card => {
      const dep = card.dataset.dep || '00:00';
      const h = parseInt(dep.split(':')[0]);
      const dur = parseInt(card.dataset.dur || 0);
      let timeOk = checkedTimes.length === 0;
      if (!timeOk) {
        if (checkedTimes.includes('night')     && h >= 0  && h < 6)  timeOk = true;
        if (checkedTimes.includes('morning')   && h >= 6  && h < 12) timeOk = true;
        if (checkedTimes.includes('afternoon') && h >= 12 && h < 18) timeOk = true;
        if (checkedTimes.includes('evening')   && h >= 18 && h < 24) timeOk = true;
      }
      const durOk = maxDur >= 25 || dur <= maxDur * 60;
      card.style.display = (timeOk && durOk) ? '' : 'none';
    });
    // Update count
    const visible = $$('.flight-card').filter(c => c.style.display !== 'none').length;
    const meta = $('.results-meta');
    if (meta) {
      meta.querySelector('strong').textContent = visible + ' flight' + (visible !== 1 ? 's' : '') + ' found';
    }
  }

  $$('.dep-time-cb').forEach(cb => cb.addEventListener('change', applyClientFilters));

  const durFilter = $('#dur-filter');
  const durVal = $('#dur-filter-val');
  if (durFilter) {
    durFilter.addEventListener('input', () => {
      const v = parseInt(durFilter.value);
      durVal.textContent = v >= 25 ? 'Any' : v + 'h max';
      applyClientFilters();
    });
  }

  // ── Price Calendar ────────────────────────────────────────
  const openCalBtn = $('#open-price-cal');
  const calModal = $('#price-cal-modal');
  const calClose = $('#cal-modal-close');

  function renderPriceCalendar() {
    const prices = window.CAL_PRICES || {};
    const selected = window.CAL_SELECTED || '';
    const from = window.CAL_FROM || '';
    const to = window.CAL_TO || '';
    const cabin = window.CAL_CABIN || 'economy';
    const pax = window.CAL_PAX || 1;
    const cal = $('#price-calendar');
    const sub = $('#cal-subtitle');
    if (!cal) return;

    const vals = Object.values(prices).filter(v => v > 0);
    const minP = vals.length ? Math.min(...vals) : 0;
    const maxP = vals.length ? Math.max(...vals) : 0;
    const range = maxP - minP || 1;

    if (sub) sub.textContent = `${from} → ${to} · ${cabin.charAt(0).toUpperCase()+cabin.slice(1)} · ${pax} pax`;

    // Build 5-week grid starting from today
    const today = new Date();
    today.setHours(0,0,0,0);
    const startDate = new Date(today);
    // Start from Monday of current week
    const dow = startDate.getDay();
    startDate.setDate(startDate.getDate() - (dow === 0 ? 6 : dow - 1));

    let html = '<div class="cal-grid"><div class="cal-day-header">Mon</div><div class="cal-day-header">Tue</div><div class="cal-day-header">Wed</div><div class="cal-day-header">Thu</div><div class="cal-day-header">Fri</div><div class="cal-day-header">Sat</div><div class="cal-day-header">Sun</div>';

    for (let w = 0; w < 5; w++) {
      for (let d = 0; d < 7; d++) {
        const dt = new Date(startDate);
        dt.setDate(startDate.getDate() + w*7 + d);
        const key = dt.toISOString().split('T')[0];
        const price = prices[key];
        const isPast = dt < today;
        const isSel = key === selected;

        let cls = 'cal-cell';
        if (isPast) cls += ' cal-cell--past';
        if (isSel) cls += ' cal-cell--selected';
        if (price) {
          const pct = (price - minP) / range;
          if (pct <= 0.25) cls += ' cal-cell--low';
          else if (pct <= 0.65) cls += ' cal-cell--mid';
          else cls += ' cal-cell--high';
        }

        const url = `search.php?from=${from}&to=${to}&date=${key}&pax=${pax}&class=${cabin}`;
        const priceStr = price ? 'RM ' + price.toLocaleString() : '—';
        const dayNum = dt.getDate();
        const monthStr = dt.toLocaleString('default', { month: 'short' });

        html += isPast
          ? `<div class="${cls}"><div class="cal-date">${dayNum}<span>${monthStr}</span></div><div class="cal-price">—</div></div>`
          : `<a href="${url}" class="${cls}"><div class="cal-date">${dayNum}<span>${monthStr}</span></div><div class="cal-price">${priceStr}</div></a>`;
      }
    }
    html += '</div>';
    cal.innerHTML = html;
  }

  if (openCalBtn && calModal) {
    openCalBtn.addEventListener('click', () => {
      calModal.style.display = 'flex';
      renderPriceCalendar();
    });
  }
  if (calClose && calModal) {
    calClose.addEventListener('click', () => calModal.style.display = 'none');
  }
  calModal?.addEventListener('click', e => { if (e.target === calModal) calModal.style.display = 'none'; });

  // ── Currency Switcher ─────────────────────────────────────
  const CURR_KEY = 'mia_flightngo_currency';
  let currRate = 1, currCode = 'MYR';

  function loadCurrency() {
    try {
      const saved = JSON.parse(localStorage.getItem(CURR_KEY) || '{}');
      currCode = saved.code || 'MYR';
      currRate = saved.rate || 1;
    } catch (e) { currCode = 'MYR'; currRate = 1; }
  }

  function applyPrices() {
    $$('.fx').forEach(el => {
      const myr = parseInt(el.dataset.myr || 0);
      const converted = Math.round(myr * currRate);
      el.textContent = currCode === 'MYR'
        ? 'RM ' + myr.toLocaleString()
        : currCode + ' ' + converted.toLocaleString();
    });
    const btn = $('#currency-toggle');
    if (btn) btn.textContent = currCode + ' ▾';
    $$('.currency-opt').forEach(o => o.classList.toggle('active', o.dataset.code === currCode));
  }

  function saveCurrency(code, rate) {
    currCode = code; currRate = rate;
    localStorage.setItem(CURR_KEY, JSON.stringify({code, rate}));
    applyPrices();
  }

  const currToggle = $('#currency-toggle');
  const currDrop = $('#currency-dropdown');
  if (currToggle && currDrop) {
    currToggle.addEventListener('click', e => {
      e.stopPropagation();
      currDrop.style.display = currDrop.style.display === 'none' ? 'block' : 'none';
    });
    document.addEventListener('click', () => { if (currDrop) currDrop.style.display = 'none'; });
    $$('.currency-opt').forEach(opt => {
      opt.addEventListener('click', () => {
        saveCurrency(opt.dataset.code, parseFloat(opt.dataset.rate));
        currDrop.style.display = 'none';
      });
    });
  }

  loadCurrency();
  applyPrices();

  console.log('✈ Mia Flight n Go ready');
})();
