<?php
require_once 'includes/flights_data.php';

$page_title  = 'Mia Flight n Go — Global Flight Price Tracker';
$active_page = 'home';
$base_path   = '';
$airports    = get_airport_list();
$grouped     = get_airports_grouped();

$popular_routes = [
    ['from'=>'KUL','to'=>'SIN','tag'=>'Most Popular'],
    ['from'=>'KUL','to'=>'BKK','tag'=>'Best Deal'],
    ['from'=>'KUL','to'=>'LHR','tag'=>'Long Haul Pick'],
    ['from'=>'KUL','to'=>'DXB','tag'=>'Trending'],
    ['from'=>'KUL','to'=>'SYD','tag'=>'Holiday Spot'],
    ['from'=>'SIN','to'=>'NRT','tag'=>'Asia Favourite'],
    ['from'=>'KUL','to'=>'ICN','tag'=>'K-Trip'],
    ['from'=>'KUL','to'=>'HKG','tag'=>'Quick Getaway'],
];

$next_week = date('Y-m-d', strtotime('+7 days'));
foreach ($popular_routes as &$r) {
    $fl = generate_flights($r['from'], $r['to'], $next_week, 1);
    $r['min_price']  = $fl ? $fl[0]['economy_price'] : null;
    $r['from_city']  = $airports[$r['from']]['city'] ?? $r['from'];
    $r['to_city']    = $airports[$r['to']]['city']   ?? $r['to'];
    $r['to_country'] = $airports[$r['to']]['country'] ?? '';
}
unset($r);

require_once 'includes/header.php';
?>

<!-- Airport datalist for autocomplete -->
<datalist id="airport-list">
    <?php foreach ($airports as $code => $ap): ?>
    <option value="<?= $code ?>" label="<?= htmlspecialchars("{$ap['city']}, {$ap['country']} ({$code})") ?>">
    <?php endforeach; ?>
</datalist>

<section class="hero">
    <div class="hero-bg"></div>
    <div class="container hero-content">
        <h1 class="hero-title">Fly Anywhere. Pay Less.</h1>
        <p class="hero-subtitle">Compare flights from 80+ airports worldwide. Track prices & get alerted when fares drop.</p>

        <div class="search-card" id="search-card">
            <div class="trip-tabs">
                <button class="trip-tab active" data-trip="one-way">One Way</button>
                <button class="trip-tab" data-trip="return">Return</button>
            </div>

            <form class="search-form" action="search.php" method="GET" id="search-form">
                <input type="hidden" name="trip" id="trip-type" value="one-way">

                <div class="form-row">
                    <div class="form-group airport-group">
                        <label>From</label>
                        <div class="airport-search-wrap">
                            <span class="input-icon">🛫</span>
                            <input type="text" id="from-search" class="airport-search-input"
                                   placeholder="City or airport code…" autocomplete="off"
                                   value="Kuala Lumpur (KUL)">
                            <input type="hidden" name="from" id="from" value="KUL" required>
                            <div class="airport-dropdown" id="from-dropdown"></div>
                        </div>
                    </div>

                    <button type="button" class="swap-btn" id="swap-btn" title="Swap airports">⇌</button>

                    <div class="form-group airport-group">
                        <label>To</label>
                        <div class="airport-search-wrap">
                            <span class="input-icon">🛬</span>
                            <input type="text" id="to-search" class="airport-search-input"
                                   placeholder="City or airport code…" autocomplete="off">
                            <input type="hidden" name="to" id="to" value="" required>
                            <div class="airport-dropdown" id="to-dropdown"></div>
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Departure</label>
                        <div class="input-wrap">
                            <span class="input-icon">📅</span>
                            <input type="date" name="date" id="dep-date" required
                                   min="<?= date('Y-m-d') ?>"
                                   value="<?= date('Y-m-d', strtotime('+3 days')) ?>">
                        </div>
                    </div>

                    <div class="form-group" id="return-field" style="display:none">
                        <label>Return</label>
                        <div class="input-wrap">
                            <span class="input-icon">📅</span>
                            <input type="date" name="return_date" id="ret-date"
                                   min="<?= date('Y-m-d') ?>"
                                   value="<?= date('Y-m-d', strtotime('+10 days')) ?>">
                        </div>
                    </div>

                    <div class="form-group pax-group">
                        <label>Passengers</label>
                        <div class="pax-control">
                            <button type="button" class="pax-btn" id="pax-minus">−</button>
                            <input type="number" name="pax" id="pax" value="1" min="1" max="9" readonly>
                            <button type="button" class="pax-btn" id="pax-plus">+</button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Cabin Class</label>
                        <div class="input-wrap">
                            <span class="input-icon">💺</span>
                            <select name="class" id="cabin-class">
                                <option value="economy">Economy</option>
                                <option value="business">Business</option>
                                <option value="first">First Class</option>
                            </select>
                        </div>
                    </div>
                </div>

                <button type="submit" class="search-btn">
                    <span>🔍</span> Search Flights
                </button>
            </form>
        </div>
    </div>
</section>

<!-- Airlines Strip -->
<section class="airlines-strip">
    <div class="container">
        <p class="strip-label">Comparing prices from</p>
        <div class="airlines-scroll">
            <?php
            $show = ['AK'=>'AirAsia','MH'=>'Malaysia Airlines','EK'=>'Emirates','SQ'=>'Singapore Airlines',
                     'QR'=>'Qatar Airways','BA'=>'British Airways','CX'=>'Cathay Pacific',
                     'TG'=>'Thai Airways','QF'=>'Qantas','LH'=>'Lufthansa'];
            foreach ($show as $c => $n): ?>
            <div class="airline-chip"><?= $n ?></div>
            <?php endforeach; ?>
            <div class="airline-chip airline-chip--more">+30 more</div>
        </div>
    </div>
</section>

<!-- Popular Routes -->
<section class="popular-routes">
    <div class="container">
        <div class="section-header">
            <h2>Popular International Routes</h2>
            <p>Cheapest economy fares found for next week</p>
        </div>
        <div class="routes-grid">
            <?php foreach ($popular_routes as $r): ?>
            <a href="search.php?from=<?= $r['from'] ?>&to=<?= $r['to'] ?>&date=<?= $next_week ?>&pax=1&class=economy"
               class="route-card">
                <div class="route-tag"><?= htmlspecialchars($r['tag']) ?></div>
                <div class="route-cities">
                    <div>
                        <div class="route-city"><?= htmlspecialchars($r['from_city']) ?></div>
                        <div class="route-code"><?= $r['from'] ?></div>
                    </div>
                    <span class="route-arrow">✈</span>
                    <div>
                        <div class="route-city"><?= htmlspecialchars($r['to_city']) ?></div>
                        <div class="route-code"><?= $r['to'] ?> · <?= htmlspecialchars($r['to_country']) ?></div>
                    </div>
                </div>
                <?php if ($r['min_price']): ?>
                <div class="route-price">
                    <span class="from-label">from</span>
                    <span class="price-val"><?= format_price($r['min_price']) ?></span>
                </div>
                <?php endif; ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Features -->
<section class="features">
    <div class="container">
        <div class="section-header"><h2>Why Mia Flight n Go?</h2></div>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">🌍</div>
                <h3>80+ Airports Worldwide</h3>
                <p>Search flights across Asia, Europe, Middle East, Americas, Africa and Oceania.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📊</div>
                <h3>Price History Charts</h3>
                <p>See 30-day price trends for every flight so you know when prices are low.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🔔</div>
                <h3>In-App Price Alerts</h3>
                <p>Add flights to your watchlist with a target price. Get notified on the website the moment prices match.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">⚡</div>
                <h3>40+ Airlines</h3>
                <p>Compare AirAsia, Emirates, Singapore Airlines, British Airways and many more in one search.</p>
            </div>
        </div>
    </div>
</section>

<!-- Watchlist CTA -->
<section class="trend-banner">
    <div class="container">
        <div class="trend-content">
            <div>
                <h3>📋 Your Price Watchlist</h3>
                <p>Set a target price for any route. We check prices every time you visit and alert you on-screen when your price is hit.</p>
            </div>
            <a href="watchlist.php" class="btn-outline">View My Watchlist →</a>
        </div>
    </div>
</section>

<!-- Airport JS data for autocomplete -->
<script>
window.AIRPORTS = <?= json_encode(array_map(fn($code, $ap) => [
    'code'    => $code,
    'city'    => $ap['city'],
    'country' => $ap['country'],
    'name'    => $ap['name'],
    'region'  => $ap['region'],
], array_keys($airports), $airports), JSON_UNESCAPED_UNICODE) ?>;
</script>

<?php require_once 'includes/footer.php'; ?>
