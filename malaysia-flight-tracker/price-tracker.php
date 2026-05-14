<?php
require_once 'includes/flights_data.php';

$page_title  = 'Price Tracker & Alerts — Mia Flight n Go';
$active_page = 'tracker';
$base_path   = '';
$airports    = get_airport_list();

$sample_routes = [
    ['from'=>'KUL','to'=>'SIN','label'=>'KL → Singapore'],
    ['from'=>'KUL','to'=>'DXB','label'=>'KL → Dubai'],
    ['from'=>'KUL','to'=>'LHR','label'=>'KL → London'],
    ['from'=>'KUL','to'=>'SYD','label'=>'KL → Sydney'],
    ['from'=>'KUL','to'=>'NRT','label'=>'KL → Tokyo'],
    ['from'=>'SIN','to'=>'JFK','label'=>'Singapore → New York'],
];

$trend_data = [];
foreach ($sample_routes as $sr) {
    $fl = generate_flights($sr['from'], $sr['to'], date('Y-m-d', strtotime('+7 days')), 1);
    if ($fl) {
        $trend_data[] = [
            'route'   => $sr['label'],
            'from'    => $sr['from'], 'to' => $sr['to'],
            'current' => $fl[0]['economy_price'],
            'low'     => $fl[0]['low_price'],
            'high'    => $fl[0]['high_price'],
            'airline' => $fl[0]['airline'],
        ];
    }
}

require_once 'includes/header.php';
?>

<script>
window.AIRPORTS = <?= json_encode(array_map(fn($code,$ap)=>['code'=>$code,'city'=>$ap['city'],'country'=>$ap['country'],'name'=>$ap['name'],'region'=>$ap['region']], array_keys($airports), $airports), JSON_UNESCAPED_UNICODE) ?>;
</script>

<div class="tracker-page">
<div class="tracker-hero">
    <div class="container">
        <h1 class="tracker-title">✈️ Flight Price Tracker</h1>
        <p class="tracker-subtitle">Set a budget, watch a route — Mia Flight n Go alerts you on-screen the moment prices drop.</p>
    </div>
</div>

<div class="container tracker-layout">

    <!-- How it works -->
    <div class="how-it-works">
        <h2>How It Works</h2>
        <div class="steps">
            <div class="step">
                <div class="step-num">1</div>
                <div class="step-content"><h4>Search Any Route</h4><p>Search flights between any of our 80+ worldwide airports.</p></div>
            </div>
            <div class="step">
                <div class="step-num">2</div>
                <div class="step-content"><h4>Click 🔔 Watch</h4><p>Hit the Watch button on any flight card and set your target price.</p></div>
            </div>
            <div class="step">
                <div class="step-num">3</div>
                <div class="step-content"><h4>Get In-App Alerts</h4><p>Every time you visit Mia Flight n Go, prices are checked automatically. If your target is hit, the bell icon lights up.</p></div>
            </div>
        </div>
        <div class="step-cta">
            <a href="watchlist.php" class="btn-primary">📋 View My Watchlist</a>
            <a href="index.php" class="btn-outline-dark">🔍 Search Flights</a>
        </div>
    </div>

    <!-- Live Price Monitor -->
    <div class="live-monitor">
        <h2 class="section-title">📈 Live Price Monitor — Popular Routes</h2>
        <p class="section-sub">Economy class · 1 adult · departing next week · all prices in MYR</p>
        <div class="monitor-grid">
            <?php foreach ($trend_data as $td):
                $pct = min(100, round(($td['current']-$td['low'])/max(1,$td['high']-$td['low'])*100));
                $sc  = $pct<=20?'status--low':($pct<=60?'status--mid':'status--high');
                $st  = $pct<=20?'🟢 Great Deal':($pct<=60?'🟡 Average':'🔴 Expensive');
            ?>
            <div class="monitor-card">
                <div class="monitor-route"><?= htmlspecialchars($td['route']) ?></div>
                <div class="monitor-price-row">
                    <div class="monitor-price">RM <?= number_format($td['current']) ?></div>
                    <div class="monitor-status <?= $sc ?>"><?= $st ?></div>
                </div>
                <div class="monitor-range">
                    <span class="range-low">RM <?= number_format($td['low']) ?></span>
                    <div class="monitor-bar">
                        <div class="monitor-fill" style="width:<?= $pct ?>%"></div>
                        <div class="monitor-marker" style="left:<?= $pct ?>%"></div>
                    </div>
                    <span class="range-high">RM <?= number_format($td['high']) ?></span>
                </div>
                <div class="monitor-actions">
                    <a href="search.php?from=<?=$td['from']?>&to=<?=$td['to']?>&date=<?=date('Y-m-d',strtotime('+7 days'))?>&pax=1" class="monitor-search-btn">Search</a>
                    <button class="monitor-alert-btn watchlist-quick-add"
                            data-from="<?=$td['from']?>" data-to="<?=$td['to']?>"
                            data-date="<?=date('Y-m-d',strtotime('+7 days'))?>"
                            data-price="<?=$td['current']?>"
                            data-from-city="<?=htmlspecialchars(explode(' → ',$td['route'])[0])?>"
                            data-to-city="<?=htmlspecialchars(explode(' → ',$td['route'])[1]??' ')?>">
                        🔔 Watch
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Tips -->
    <div class="tips-section">
        <h2>💡 Tips to Find Cheapest International Flights</h2>
        <div class="tips-grid">
            <div class="tip-card"><div class="tip-icon">📅</div><h4>Book 6–12 Weeks Ahead</h4><p>International flights see the best prices when booked 6–12 weeks before departure.</p></div>
            <div class="tip-card"><div class="tip-icon">🕐</div><h4>Fly Tuesdays & Wednesdays</h4><p>Mid-week departures can be 15–30% cheaper than Friday or Sunday.</p></div>
            <div class="tip-card"><div class="tip-icon">🌏</div><h4>Use Hub Airports</h4><p>Flying via KUL, SIN, or DXB as a hub often unlocks cheaper fares than direct routes.</p></div>
            <div class="tip-card"><div class="tip-icon">🔔</div><h4>Set Multiple Alerts</h4><p>Watch several dates around your travel window — prices vary wildly day to day.</p></div>
            <div class="tip-card"><div class="tip-icon">✈️</div><h4>Compare All Cabin Classes</h4><p>Sometimes Business Class on a budget airline is cheaper than Economy on a full-service carrier.</p></div>
            <div class="tip-card"><div class="tip-icon">🎒</div><h4>Go Carry-On Only</h4><p>On LCCs like AirAsia, skipping checked baggage saves RM 80–250 per flight.</p></div>
        </div>
    </div>
</div>
</div>

<!-- Watchlist Modal -->
<div class="modal-overlay" id="watchlist-modal" style="display:none">
    <div class="modal-box">
        <div class="modal-header"><h3>🔔 Add to Watchlist</h3><button class="modal-close" id="modal-close">✕</button></div>
        <div class="modal-body">
            <p class="modal-route" id="modal-route"></p>
            <p class="modal-sub">Current price: <strong id="modal-current-price"></strong></p>
            <label class="modal-label">Your target price (MYR per person)</label>
            <div class="modal-price-wrap"><span class="modal-rm">RM</span><input type="number" id="modal-target" min="50" max="50000" placeholder="e.g. 500"></div>
            <p class="modal-hint">We'll notify you on-screen when the price hits your target.</p>
        </div>
        <div class="modal-footer">
            <button class="modal-cancel" id="modal-cancel">Cancel</button>
            <button class="modal-confirm" id="modal-confirm">Add to Watchlist</button>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
