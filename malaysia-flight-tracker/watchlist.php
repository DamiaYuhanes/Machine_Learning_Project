<?php
require_once 'includes/flights_data.php';

$page_title  = 'My Watchlist — Mia Flight n Go';
$active_page = 'watchlist';
$base_path   = '';
$airports    = get_airport_list();

require_once 'includes/header.php';
?>

<script>
window.AIRPORTS = <?= json_encode(array_map(fn($code,$ap)=>['code'=>$code,'city'=>$ap['city'],'country'=>$ap['country'],'name'=>$ap['name'],'region'=>$ap['region']], array_keys($airports), $airports), JSON_UNESCAPED_UNICODE) ?>;
</script>

<div class="watchlist-page">
    <div class="tracker-hero">
        <div class="container">
            <h1 class="tracker-title">📋 My Price Watchlist</h1>
            <p class="tracker-subtitle">Flights you're tracking. Get notified on-screen when prices hit your target.</p>
        </div>
    </div>

    <div class="container watchlist-container">

        <!-- Empty state (shown by JS when watchlist is empty) -->
        <div class="watchlist-empty" id="watchlist-empty" style="display:none">
            <div class="empty-icon">✈️</div>
            <h3>Your watchlist is empty</h3>
            <p>Search for flights and click <strong>"🔔 Watch"</strong> on any flight to start tracking prices.</p>
            <a href="index.php" class="btn-primary">Search Flights</a>
        </div>

        <!-- Loading state -->
        <div class="watchlist-loading" id="watchlist-loading">
            <div class="loading-spinner"></div>
            <p>Checking latest prices…</p>
        </div>

        <!-- Stats bar (shown when items exist) -->
        <div class="watchlist-stats" id="watchlist-stats" style="display:none">
            <div class="wl-stat">
                <div class="wl-stat-val" id="stat-total">0</div>
                <div class="wl-stat-lbl">Watching</div>
            </div>
            <div class="wl-stat">
                <div class="wl-stat-val text-green" id="stat-matched">0</div>
                <div class="wl-stat-lbl">Price Matched</div>
            </div>
            <div class="wl-stat">
                <div class="wl-stat-val text-red" id="stat-above">0</div>
                <div class="wl-stat-lbl">Above Target</div>
            </div>
            <button class="wl-clear-btn" id="wl-clear-all">🗑 Clear All</button>
        </div>

        <!-- Watchlist items (rendered by JS) -->
        <div class="watchlist-items" id="watchlist-items"></div>

        <!-- Add Quick Watch form -->
        <div class="quick-watch-card" id="quick-watch-card" style="display:none">
            <h3>➕ Add New Route to Watch</h3>
            <div class="qw-form">
                <div class="qw-field">
                    <label>From</label>
                    <div class="airport-search-wrap">
                        <span class="input-icon">🛫</span>
                        <input type="text" id="qw-from-search" class="airport-search-input" placeholder="City or code…" autocomplete="off">
                        <input type="hidden" id="qw-from">
                        <div class="airport-dropdown" id="qw-from-dropdown"></div>
                    </div>
                </div>
                <div class="qw-field">
                    <label>To</label>
                    <div class="airport-search-wrap">
                        <span class="input-icon">🛬</span>
                        <input type="text" id="qw-to-search" class="airport-search-input" placeholder="City or code…" autocomplete="off">
                        <input type="hidden" id="qw-to">
                        <div class="airport-dropdown" id="qw-to-dropdown"></div>
                    </div>
                </div>
                <div class="qw-field">
                    <label>Date</label>
                    <input type="date" id="qw-date" min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d', strtotime('+14 days')) ?>">
                </div>
                <div class="qw-field">
                    <label>Target Price (MYR)</label>
                    <div class="modal-price-wrap">
                        <span class="modal-rm">RM</span>
                        <input type="number" id="qw-target" min="50" placeholder="e.g. 800">
                    </div>
                </div>
                <button class="qw-add-btn" id="qw-add-btn">+ Add to Watchlist</button>
            </div>
        </div>

        <div class="watchlist-actions" id="watchlist-actions" style="display:none">
            <button class="btn-outline-dark" id="show-quick-watch">➕ Add Another Route</button>
        </div>

    </div>
</div>

<!-- Watchlist Item Template (cloned by JS) -->
<template id="wl-item-template">
    <div class="wl-item" data-id="">
        <div class="wl-item-status"></div>
        <div class="wl-item-route">
            <div class="wl-route-cities">
                <span class="wl-from-city"></span>
                <span class="wl-arrow">✈</span>
                <span class="wl-to-city"></span>
            </div>
            <div class="wl-meta"></div>
        </div>
        <div class="wl-price-info">
            <div class="wl-current-section">
                <div class="wl-price-label">Current Price</div>
                <div class="wl-current-price"></div>
            </div>
            <div class="wl-vs">vs</div>
            <div class="wl-target-section">
                <div class="wl-price-label">Your Target</div>
                <div class="wl-target-price"></div>
            </div>
            <div class="wl-diff-section">
                <div class="wl-price-label">Difference</div>
                <div class="wl-diff"></div>
            </div>
        </div>
        <div class="wl-item-actions">
            <a href="#" class="wl-search-btn">🔍 Search</a>
            <button class="wl-remove-btn" data-id="">🗑</button>
        </div>
    </div>
</template>

<!-- Watchlist Modal (for editing target) -->
<div class="modal-overlay" id="watchlist-modal" style="display:none">
    <div class="modal-box">
        <div class="modal-header"><h3>🔔 Add to Watchlist</h3><button class="modal-close" id="modal-close">✕</button></div>
        <div class="modal-body">
            <p class="modal-route" id="modal-route"></p>
            <p class="modal-sub">Current price: <strong id="modal-current-price"></strong></p>
            <label class="modal-label">Your target price (MYR per person)</label>
            <div class="modal-price-wrap">
                <span class="modal-rm">RM</span>
                <input type="number" id="modal-target" min="50" max="50000" placeholder="e.g. 500">
            </div>
            <p class="modal-hint">We'll highlight this in green when the current price drops to or below your target.</p>
        </div>
        <div class="modal-footer">
            <button class="modal-cancel" id="modal-cancel">Cancel</button>
            <button class="modal-confirm" id="modal-confirm">Add to Watchlist</button>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
