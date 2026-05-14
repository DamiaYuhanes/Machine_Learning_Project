<?php
require_once 'includes/flights_data.php';

// Sanitise inputs
$from        = strtoupper(preg_replace('/[^A-Z]/', '', $_GET['from']        ?? 'KUL'));
$to          = strtoupper(preg_replace('/[^A-Z]/', '', $_GET['to']          ?? 'PEN'));
$date        = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['date'] ?? '') ? $_GET['date'] : date('Y-m-d', strtotime('+3 days'));
$pax         = max(1, min(9, (int)($_GET['pax'] ?? 1)));
$cabin       = in_array($_GET['class'] ?? '', ['economy','business']) ? $_GET['class'] : 'economy';
$trip        = ($_GET['trip'] ?? '') === 'return' ? 'return' : 'one-way';
$return_date = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['return_date'] ?? '') ? $_GET['return_date'] : date('Y-m-d', strtotime('+10 days'));

$sort     = in_array($_GET['sort'] ?? '', ['price','duration','departure','airline']) ? $_GET['sort'] : 'price';
$max_price = isset($_GET['max_price']) && is_numeric($_GET['max_price']) ? (int)$_GET['max_price'] : 9999;
$stops_filter = $_GET['stops'] ?? 'any';
$airline_filter = $_GET['airline'] ?? '';

$airports = get_airport_list();
$flights  = generate_flights($from, $to, $date, $pax);
$return_flights = ($trip === 'return') ? generate_flights($to, $from, $return_date, $pax) : [];

// Apply filters
$filtered = array_filter($flights, function($f) use ($max_price, $stops_filter, $airline_filter, $cabin) {
    $price = ($cabin === 'business') ? $f['business_price'] : $f['economy_price'];
    if ($price > $max_price) return false;
    if ($stops_filter === 'direct' && $f['stops'] > 0) return false;
    if ($airline_filter && $f['airline_code'] !== $airline_filter) return false;
    return true;
});

// Sort
usort($filtered, function($a, $b) use ($sort, $cabin) {
    return match($sort) {
        'duration'  => $a['duration_mins'] <=> $b['duration_mins'],
        'departure' => strcmp($a['departure'], $b['departure']),
        'airline'   => strcmp($a['airline'], $b['airline']),
        default     => ($cabin === 'business'
                        ? $a['business_price'] <=> $b['business_price']
                        : $a['economy_price']  <=> $b['economy_price']),
    };
});
$filtered = array_values($filtered);

$from_city = $airports[$from]['city'] ?? $from;
$to_city   = $airports[$to]['city']   ?? $to;
$page_title = "Flights $from → $to on " . date('d M Y', strtotime($date)) . ' — FlightGo';
$active_page = 'home';
$base_path = '';

require_once 'includes/header.php';
?>

<div class="search-bar-compact">
    <div class="container">
        <form class="compact-form" action="search.php" method="GET">
            <input type="hidden" name="trip" value="<?= htmlspecialchars($trip) ?>">
            <div class="compact-row">
                <select name="from" class="compact-select">
                    <?php foreach ($airports as $code => $ap): ?>
                    <option value="<?= $code ?>" <?= $code === $from ? 'selected' : '' ?>>
                        <?= htmlspecialchars($ap['city']) ?> (<?= $code ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
                <span class="compact-arrow">✈</span>
                <select name="to" class="compact-select">
                    <?php foreach ($airports as $code => $ap): ?>
                    <option value="<?= $code ?>" <?= $code === $to ? 'selected' : '' ?>>
                        <?= htmlspecialchars($ap['city']) ?> (<?= $code ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
                <input type="date" name="date" value="<?= htmlspecialchars($date) ?>" class="compact-input"
                       min="<?= date('Y-m-d') ?>">
                <?php if ($trip === 'return'): ?>
                <input type="date" name="return_date" value="<?= htmlspecialchars($return_date) ?>" class="compact-input"
                       min="<?= date('Y-m-d') ?>">
                <?php endif; ?>
                <select name="pax" class="compact-select compact-small">
                    <?php for ($i = 1; $i <= 9; $i++): ?>
                    <option value="<?= $i ?>" <?= $i === $pax ? 'selected' : '' ?>><?= $i ?> Pax</option>
                    <?php endfor; ?>
                </select>
                <select name="class" class="compact-select compact-small">
                    <option value="economy" <?= $cabin === 'economy' ? 'selected' : '' ?>>Economy</option>
                    <option value="business" <?= $cabin === 'business' ? 'selected' : '' ?>>Business</option>
                </select>
                <button type="submit" class="compact-search-btn">🔍 Update</button>
            </div>
        </form>
    </div>
</div>

<div class="results-page">
    <div class="container results-layout">

        <!-- Sidebar Filters -->
        <aside class="filters-sidebar">
            <form method="GET" action="search.php" id="filter-form">
                <input type="hidden" name="from"  value="<?= htmlspecialchars($from) ?>">
                <input type="hidden" name="to"    value="<?= htmlspecialchars($to) ?>">
                <input type="hidden" name="date"  value="<?= htmlspecialchars($date) ?>">
                <input type="hidden" name="pax"   value="<?= $pax ?>">
                <input type="hidden" name="class" value="<?= htmlspecialchars($cabin) ?>">
                <input type="hidden" name="trip"  value="<?= htmlspecialchars($trip) ?>">
                <?php if ($trip === 'return'): ?>
                <input type="hidden" name="return_date" value="<?= htmlspecialchars($return_date) ?>">
                <?php endif; ?>

                <h3 class="filter-title">Filter Results</h3>

                <div class="filter-group">
                    <label class="filter-label">Max Price (MYR)</label>
                    <div class="price-range-wrap">
                        <input type="range" name="max_price" id="price-range"
                               min="50" max="2000" step="10"
                               value="<?= min($max_price, 2000) ?>"
                               oninput="document.getElementById('price-val').textContent='RM '+this.value">
                        <span class="price-range-val" id="price-val">RM <?= min($max_price, 2000) ?></span>
                    </div>
                </div>

                <div class="filter-group">
                    <label class="filter-label">Stops</label>
                    <div class="radio-group">
                        <label class="radio-opt">
                            <input type="radio" name="stops" value="any" <?= $stops_filter === 'any' ? 'checked' : '' ?>>
                            Any
                        </label>
                        <label class="radio-opt">
                            <input type="radio" name="stops" value="direct" <?= $stops_filter === 'direct' ? 'checked' : '' ?>>
                            Direct only
                        </label>
                    </div>
                </div>

                <div class="filter-group">
                    <label class="filter-label">Airline</label>
                    <div class="radio-group">
                        <label class="radio-opt">
                            <input type="radio" name="airline" value="" <?= !$airline_filter ? 'checked' : '' ?>>
                            All Airlines
                        </label>
                        <?php foreach (array_unique(array_column($flights, 'airline_code')) as $ac): ?>
                        <?php $al = $flights[array_search($ac, array_column($flights, 'airline_code'))]; ?>
                        <label class="radio-opt">
                            <input type="radio" name="airline" value="<?= $ac ?>" <?= $airline_filter === $ac ? 'checked' : '' ?>>
                            <?= htmlspecialchars($al['airline']) ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <button type="submit" class="filter-apply-btn">Apply Filters</button>
                <a href="search.php?from=<?= $from ?>&to=<?= $to ?>&date=<?= $date ?>&pax=<?= $pax ?>&class=<?= $cabin ?>" class="filter-reset">Reset</a>
            </form>
        </aside>

        <!-- Main Results -->
        <main class="results-main">
            <div class="results-header">
                <div>
                    <h1 class="results-title">
                        <?= htmlspecialchars($from_city) ?> <span>→</span> <?= htmlspecialchars($to_city) ?>
                    </h1>
                    <p class="results-meta">
                        <?= date('D, d M Y', strtotime($date)) ?> ·
                        <?= $pax ?> passenger<?= $pax > 1 ? 's' : '' ?> ·
                        <?= ucfirst($cabin) ?> ·
                        <strong><?= count($filtered) ?> flight<?= count($filtered) !== 1 ? 's' : '' ?> found</strong>
                    </p>
                </div>
                <div class="sort-bar">
                    <span>Sort:</span>
                    <?php foreach (['price'=>'Price','duration'=>'Duration','departure'=>'Departure','airline'=>'Airline'] as $k=>$v): ?>
                    <a href="?from=<?= $from ?>&to=<?= $to ?>&date=<?= $date ?>&pax=<?= $pax ?>&class=<?= $cabin ?>&sort=<?= $k ?>&max_price=<?= $max_price ?>&stops=<?= $stops_filter ?>&airline=<?= $airline_filter ?>"
                       class="sort-btn <?= $sort === $k ? 'active' : '' ?>"><?= $v ?></a>
                    <?php endforeach; ?>
                </div>
            </div>

            <?php if (empty($filtered)): ?>
            <div class="no-results">
                <div class="no-results-icon">✈️</div>
                <h3>No flights found</h3>
                <p>Try adjusting your filters or searching a different date.</p>
                <a href="search.php?from=<?= $from ?>&to=<?= $to ?>&date=<?= $date ?>&pax=<?= $pax ?>" class="btn-primary">Clear Filters</a>
            </div>
            <?php else: ?>

            <!-- Cheapest price callout -->
            <?php $cheapest = $filtered[0]; $cp = ($cabin === 'business') ? $cheapest['business_price'] : $cheapest['economy_price']; ?>
            <div class="cheapest-banner">
                <span>💡 Cheapest flight today: <strong><?= format_price($cp) ?></strong> with <?= htmlspecialchars($cheapest['airline']) ?> at <?= $cheapest['departure'] ?></span>
                <a href="price-tracker.php?from=<?= $from ?>&to=<?= $to ?>&target=<?= $cp ?>" class="alert-link">🔔 Set Price Alert</a>
            </div>

            <div class="flight-list">
                <?php foreach ($filtered as $f):
                    $price = ($cabin === 'business') ? $f['business_price'] : $f['economy_price'];
                    $is_cheapest = ($f === $filtered[0]);
                ?>
                <div class="flight-card <?= $is_cheapest ? 'flight-card--best' : '' ?>">
                    <?php if ($is_cheapest): ?><div class="best-tag">Best Value</div><?php endif; ?>

                    <div class="flight-card-inner">
                        <div class="flight-airline">
                            <div class="airline-logo" style="background:<?= htmlspecialchars($f['airline_color']) ?>">
                                <?= htmlspecialchars($f['airline_code']) ?>
                            </div>
                            <div class="airline-info">
                                <div class="airline-name"><?= htmlspecialchars($f['airline']) ?></div>
                                <div class="flight-number"><?= htmlspecialchars($f['flight_no']) ?></div>
                                <div class="airline-type-badge"><?= htmlspecialchars($f['airline_type']) ?></div>
                            </div>
                        </div>

                        <div class="flight-route-info">
                            <div class="time-block">
                                <div class="time"><?= htmlspecialchars($f['departure']) ?></div>
                                <div class="iata"><?= htmlspecialchars($f['from']) ?></div>
                            </div>
                            <div class="duration-block">
                                <div class="duration-line">
                                    <div class="line-dot"></div>
                                    <div class="line-bar"></div>
                                    <div class="line-plane">✈</div>
                                    <div class="line-bar"></div>
                                    <div class="line-dot"></div>
                                </div>
                                <div class="duration-label"><?= htmlspecialchars($f['duration_fmt']) ?></div>
                                <div class="stops-label"><?= $f['stops'] === 0 ? '✅ Direct' : $f['stops'].' stop' ?></div>
                            </div>
                            <div class="time-block">
                                <div class="time"><?= htmlspecialchars($f['arrival']) ?></div>
                                <div class="iata"><?= htmlspecialchars($f['to']) ?></div>
                                <?php if ($f['arr_date'] !== $f['date']): ?><div class="next-day">+1</div><?php endif; ?>
                            </div>
                        </div>

                        <div class="flight-amenities">
                            <span class="amenity" title="Baggage">🧳 <?= htmlspecialchars($f['baggage']) ?></span>
                            <span class="amenity" title="Meal">🍽 <?= htmlspecialchars($f['meal']) ?></span>
                            <?php if ($f['refundable']): ?><span class="amenity amenity--green" title="Refundable">✅ Refundable</span><?php endif; ?>
                            <span class="amenity amenity--seats"><?= $f['seats_left'] ?> seats left</span>
                        </div>

                        <div class="flight-price-block">
                            <div class="price-per">per person</div>
                            <div class="price-main"><?= format_price($price) ?></div>
                            <div class="price-total"><?= format_price($price * $pax) ?> total</div>
                            <a href="flight-details.php?id=<?= urlencode($f['id']) ?>&from=<?= $from ?>&to=<?= $to ?>&date=<?= $date ?>&pax=<?= $pax ?>&class=<?= $cabin ?>&fn=<?= urlencode($f['flight_no']) ?>&dep=<?= urlencode($f['departure']) ?>&arr=<?= urlencode($f['arrival']) ?>&airline=<?= urlencode($f['airline_code']) ?>"
                               class="select-btn">Select →</a>
                            <div class="seats-urgency <?= $f['seats_left'] <= 5 ? 'urgency--high' : '' ?>">
                                <?= $f['seats_left'] <= 5 ? '🔥 Only '.$f['seats_left'].' left!' : $f['seats_left'].' seats available' ?>
                            </div>
                        </div>
                    </div>

                    <!-- Price trend mini bar -->
                    <div class="price-trend">
                        <span class="trend-label">30-day price range:</span>
                        <span class="trend-low">RM <?= number_format($f['low_price']) ?></span>
                        <div class="trend-bar-wrap">
                            <div class="trend-bar">
                                <div class="trend-fill"
                                     style="width:<?= min(100, round(($price - $f['low_price']) / max(1, $f['high_price'] - $f['low_price']) * 100)) ?>%"></div>
                                <div class="trend-marker"
                                     style="left:<?= min(100, round(($price - $f['low_price']) / max(1, $f['high_price'] - $f['low_price']) * 100)) ?>%"></div>
                            </div>
                        </div>
                        <span class="trend-high">RM <?= number_format($f['high_price']) ?></span>
                        <a href="flight-details.php?id=<?= urlencode($f['id']) ?>&from=<?= $from ?>&to=<?= $to ?>&date=<?= $date ?>&pax=<?= $pax ?>&class=<?= $cabin ?>&fn=<?= urlencode($f['flight_no']) ?>&dep=<?= urlencode($f['departure']) ?>&arr=<?= urlencode($f['arrival']) ?>&airline=<?= urlencode($f['airline_code']) ?>"
                           class="trend-history-link">View History →</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Date picker strip -->
            <div class="date-strip-section">
                <h3>Check other dates</h3>
                <div class="date-strip">
                    <?php for ($i = -3; $i <= 3; $i++):
                        $d = date('Y-m-d', strtotime("$date $i days"));
                        $df = generate_flights($from, $to, $d, $pax);
                        $dp = $df ? (($cabin==='business') ? $df[0]['business_price'] : $df[0]['economy_price']) : null;
                    ?>
                    <a href="search.php?from=<?= $from ?>&to=<?= $to ?>&date=<?= $d ?>&pax=<?= $pax ?>&class=<?= $cabin ?>"
                       class="date-chip <?= $d === $date ? 'date-chip--active' : '' ?>">
                        <div class="chip-day"><?= date('D', strtotime($d)) ?></div>
                        <div class="chip-date"><?= date('d M', strtotime($d)) ?></div>
                        <?php if ($dp): ?><div class="chip-price">RM<?= number_format($dp) ?></div><?php endif; ?>
                    </a>
                    <?php endfor; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
