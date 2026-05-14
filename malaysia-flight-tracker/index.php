<?php
require_once 'includes/flights_data.php';

$page_title  = 'FlightGo — Malaysia Flight Price Tracker';
$active_page = 'home';
$base_path   = '';
$airports    = get_airport_list();

$popular_routes = [
    ['from' => 'KUL', 'to' => 'BKI', 'tag' => 'Most Popular'],
    ['from' => 'KUL', 'to' => 'PEN', 'tag' => 'Best Deal'],
    ['from' => 'KUL', 'to' => 'KCH', 'tag' => 'Trending'],
    ['from' => 'KUL', 'to' => 'LGK', 'tag' => 'Holiday Spot'],
    ['from' => 'PEN', 'to' => 'BKI', 'tag' => 'Explorer'],
    ['from' => 'JHB', 'to' => 'BKI', 'tag' => 'Adventure'],
];

// Show prices for popular routes (1 week ahead)
$next_week = date('Y-m-d', strtotime('+7 days'));
foreach ($popular_routes as &$r) {
    $fl = generate_flights($r['from'], $r['to'], $next_week, 1);
    $r['min_price'] = $fl ? $fl[0]['economy_price'] : null;
}
unset($r);

require_once 'includes/header.php';
?>

<section class="hero">
    <div class="hero-bg"></div>
    <div class="container hero-content">
        <h1 class="hero-title">Find the Cheapest Flights in Malaysia</h1>
        <p class="hero-subtitle">Compare prices from AirAsia, Malaysia Airlines, Batik Air & more. Track price drops and fly smart.</p>

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
                        <div class="airport-input-wrap">
                            <span class="input-icon">🛫</span>
                            <select name="from" id="from" required>
                                <option value="">Select departure city</option>
                                <?php foreach ($airports as $code => $ap): ?>
                                    <option value="<?= $code ?>"><?= htmlspecialchars($ap['city']) ?> (<?= $code ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <button type="button" class="swap-btn" id="swap-btn" title="Swap airports">⇌</button>

                    <div class="form-group airport-group">
                        <label>To</label>
                        <div class="airport-input-wrap">
                            <span class="input-icon">🛬</span>
                            <select name="to" id="to" required>
                                <option value="">Select arrival city</option>
                                <?php foreach ($airports as $code => $ap): ?>
                                    <option value="<?= $code ?>"><?= htmlspecialchars($ap['city']) ?> (<?= $code ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Departure Date</label>
                        <div class="input-wrap">
                            <span class="input-icon">📅</span>
                            <input type="date" name="date" id="dep-date" required
                                   min="<?= date('Y-m-d') ?>"
                                   value="<?= date('Y-m-d', strtotime('+3 days')) ?>">
                        </div>
                    </div>

                    <div class="form-group return-field" id="return-field" style="display:none">
                        <label>Return Date</label>
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

                    <div class="form-group class-group">
                        <label>Class</label>
                        <div class="input-wrap">
                            <span class="input-icon">💺</span>
                            <select name="class" id="cabin-class">
                                <option value="economy">Economy</option>
                                <option value="business">Business</option>
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

<!-- Airline Strip -->
<section class="airlines-strip">
    <div class="container">
        <p class="strip-label">Comparing prices from</p>
        <div class="airlines-list">
            <div class="airline-badge" style="--c:#FF0000">AirAsia</div>
            <div class="airline-badge" style="--c:#003087">Malaysia Airlines</div>
            <div class="airline-badge" style="--c:#C8102E">Batik Air</div>
            <div class="airline-badge" style="--c:#FF6600">Firefly</div>
            <div class="airline-badge" style="--c:#FF0000">AirAsia X</div>
        </div>
    </div>
</section>

<!-- Popular Routes -->
<section class="popular-routes">
    <div class="container">
        <div class="section-header">
            <h2>Popular Routes</h2>
            <p>Best deals found for next week</p>
        </div>
        <div class="routes-grid">
            <?php foreach ($popular_routes as $r): ?>
            <a href="search.php?from=<?= $r['from'] ?>&to=<?= $r['to'] ?>&date=<?= $next_week ?>&pax=1"
               class="route-card">
                <div class="route-tag"><?= htmlspecialchars($r['tag']) ?></div>
                <div class="route-cities">
                    <span class="route-city"><?= htmlspecialchars($airports[$r['from']]['city']) ?></span>
                    <span class="route-arrow">✈</span>
                    <span class="route-city"><?= htmlspecialchars($airports[$r['to']]['city']) ?></span>
                </div>
                <div class="route-codes"><?= $r['from'] ?> → <?= $r['to'] ?></div>
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
        <div class="section-header">
            <h2>Why FlightGo?</h2>
        </div>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">📊</div>
                <h3>Price History Charts</h3>
                <p>See how prices have changed over the past 30 days so you know the best time to buy.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🔔</div>
                <h3>Price Drop Alerts</h3>
                <p>Set your target price and get notified by email when fares drop below your budget.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">⚡</div>
                <h3>Real-Time Comparison</h3>
                <p>Instantly compare all available flights sorted by price, duration, or airline.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🇲🇾</div>
                <h3>Malaysia Focused</h3>
                <p>Specialised for domestic Malaysian routes with all local airlines covered.</p>
            </div>
        </div>
    </div>
</section>

<!-- Price Trend Banner -->
<section class="trend-banner">
    <div class="container">
        <div class="trend-content">
            <div>
                <h3>📈 Track Prices Like a Pro</h3>
                <p>Set up a price alert and we'll notify you when your route drops in price. Save up to 40% by flying at the right time.</p>
            </div>
            <a href="price-tracker.php" class="btn-outline">Set Price Alert →</a>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
