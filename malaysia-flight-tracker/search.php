<?php
require_once 'includes/flights_data.php';

$from        = strtoupper(preg_replace('/[^A-Z]/', '', $_GET['from'] ?? 'KUL')) ?: 'KUL';
$to          = strtoupper(preg_replace('/[^A-Z]/', '', $_GET['to']   ?? ''));
if (!$to || $to === $from) {
    header('Location: index.php?error=' . (!$to ? 'nodest' : 'samedest'));
    exit;
}
$date        = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['date'] ?? '') ? $_GET['date'] : date('Y-m-d', strtotime('+3 days'));
$pax         = max(1, min(9, (int)($_GET['pax'] ?? 1)));
$cabin       = in_array($_GET['class'] ?? '', ['economy','business','first']) ? $_GET['class'] : 'economy';
$trip        = ($_GET['trip'] ?? '') === 'return' ? 'return' : 'one-way';
$return_date = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['return_date'] ?? '') ? $_GET['return_date'] : date('Y-m-d', strtotime('+10 days'));
$sort        = in_array($_GET['sort'] ?? '', ['best','price','duration','departure','airline']) ? $_GET['sort'] : 'price';
$max_price   = isset($_GET['max_price']) && is_numeric($_GET['max_price']) ? (int)$_GET['max_price'] : 99999;
$stops_filter   = $_GET['stops']   ?? 'any';
$airline_filter = $_GET['airline'] ?? '';

$airports = get_airport_list();
$grouped  = get_airports_grouped();
$flights  = generate_flights($from, $to, $date, $pax);

$filtered = array_filter($flights, function($f) use ($max_price, $stops_filter, $airline_filter, $cabin) {
    $price = match($cabin) { 'business'=>$f['business_price'], 'first'=>($f['first_price']??PHP_INT_MAX), default=>$f['economy_price'] };
    if ($price > $max_price) return false;
    if ($stops_filter === 'direct' && $f['stops'] > 0) return false;
    if ($airline_filter && $f['airline_code'] !== $airline_filter) return false;
    return true;
});

usort($filtered, function($a, $b) use ($sort, $cabin) {
    $pa = match($cabin) { 'business'=>$a['business_price'], 'first'=>($a['first_price']??PHP_INT_MAX), default=>$a['economy_price'] };
    $pb = match($cabin) { 'business'=>$b['business_price'], 'first'=>($b['first_price']??PHP_INT_MAX), default=>$b['economy_price'] };
    return match($sort) {
        'best'      => ($pa + $a['duration_mins']/60*150) <=> ($pb + $b['duration_mins']/60*150),
        'duration'  => $a['duration_mins'] <=> $b['duration_mins'],
        'departure' => strcmp($a['departure'], $b['departure']),
        'airline'   => strcmp($a['airline'], $b['airline']),
        default     => $pa <=> $pb,
    };
});
$filtered = array_values($filtered);

$from_ap   = $airports[$from] ?? ['city'=>$from, 'country'=>''];
$to_ap     = $airports[$to]   ?? ['city'=>$to,   'country'=>''];
$from_city = $from_ap['city'];
$to_city   = $to_ap['city'];

$page_title  = "Flights $from → $to — Mia Flight n Go";
$active_page = 'home';
$base_path   = '';

require_once 'includes/header.php';
?>

<!-- Airport autocomplete data -->
<script>
window.AIRPORTS = <?= json_encode(array_map(fn($code, $ap) => [
    'code'=>$code,'city'=>$ap['city'],'country'=>$ap['country'],'name'=>$ap['name'],'region'=>$ap['region']
], array_keys($airports), $airports), JSON_UNESCAPED_UNICODE) ?>;
</script>

<!-- Compact search bar -->
<div class="search-bar-compact">
    <div class="container">
        <form class="compact-form" action="search.php" method="GET">
            <input type="hidden" name="trip" value="<?= htmlspecialchars($trip) ?>">
            <?php if ($trip==='return'): ?>
            <input type="hidden" name="return_date" value="<?= htmlspecialchars($return_date) ?>">
            <?php endif; ?>
            <div class="compact-row">
                <!-- From -->
                <div class="compact-airport-wrap">
                    <input type="text" id="c-from-search" class="compact-airport-input" autocomplete="off"
                           value="<?= htmlspecialchars("{$from_city} ({$from})") ?>" placeholder="From">
                    <input type="hidden" name="from" id="c-from" value="<?= htmlspecialchars($from) ?>">
                    <div class="airport-dropdown compact-dropdown" id="c-from-dropdown"></div>
                </div>
                <span class="compact-arrow">✈</span>
                <!-- To -->
                <div class="compact-airport-wrap">
                    <input type="text" id="c-to-search" class="compact-airport-input" autocomplete="off"
                           value="<?= htmlspecialchars("{$to_city} ({$to})") ?>" placeholder="To">
                    <input type="hidden" name="to" id="c-to" value="<?= htmlspecialchars($to) ?>">
                    <div class="airport-dropdown compact-dropdown" id="c-to-dropdown"></div>
                </div>
                <input type="date" name="date" value="<?= htmlspecialchars($date) ?>" class="compact-input"
                       min="<?= date('Y-m-d') ?>">
                <select name="pax" class="compact-select compact-small">
                    <?php for ($i=1;$i<=9;$i++): ?>
                    <option value="<?= $i ?>" <?= $i===$pax?'selected':'' ?>><?= $i ?> Pax</option>
                    <?php endfor; ?>
                </select>
                <select name="class" class="compact-select compact-small">
                    <option value="economy"  <?= $cabin==='economy' ?'selected':'' ?>>Economy</option>
                    <option value="business" <?= $cabin==='business'?'selected':'' ?>>Business</option>
                    <option value="first"    <?= $cabin==='first'   ?'selected':'' ?>>First</option>
                </select>
                <button type="submit" class="compact-search-btn">🔍 Search</button>
            </div>
        </form>
    </div>
</div>

<?php
// Generate 30-day price calendar for current route
$cal_prices = [];
for ($d = 0; $d < 31; $d++) {
    $cal_date = date('Y-m-d', strtotime("+$d days"));
    $cal_fl = generate_flights($from, $to, $cal_date, 1);
    if ($cal_fl) {
        $cal_prices[$cal_date] = match($cabin) {
            'business' => $cal_fl[0]['business_price'],
            'first'    => $cal_fl[0]['first_price'] ?? $cal_fl[0]['economy_price'],
            default    => $cal_fl[0]['economy_price'],
        };
    }
}
$cal_min = $cal_prices ? min($cal_prices) : 0;
$cal_max = $cal_prices ? max($cal_prices) : 0;
?>
<script>
window.CAL_PRICES = <?= json_encode($cal_prices) ?>;
window.CAL_FROM = '<?= $from ?>';
window.CAL_TO = '<?= $to ?>';
window.CAL_CABIN = '<?= $cabin ?>';
window.CAL_PAX = <?= $pax ?>;
window.CAL_SELECTED = '<?= $date ?>';
</script>

<div class="results-page">
    <div class="container results-layout">

        <!-- Filters Sidebar -->
        <aside class="filters-sidebar">
            <form method="GET" action="search.php">
                <input type="hidden" name="from"  value="<?= htmlspecialchars($from) ?>">
                <input type="hidden" name="to"    value="<?= htmlspecialchars($to) ?>">
                <input type="hidden" name="date"  value="<?= htmlspecialchars($date) ?>">
                <input type="hidden" name="pax"   value="<?= $pax ?>">
                <input type="hidden" name="class" value="<?= htmlspecialchars($cabin) ?>">
                <input type="hidden" name="trip"  value="<?= htmlspecialchars($trip) ?>">

                <h3 class="filter-title">Filter Results</h3>

                <div class="filter-group">
                    <label class="filter-label">Max Price (MYR)</label>
                    <div class="price-range-wrap">
                        <input type="range" name="max_price" id="price-range"
                               min="50" max="15000" step="50"
                               value="<?= min($max_price,15000) ?>"
                               oninput="document.getElementById('price-val').textContent='RM '+Number(this.value).toLocaleString()">
                        <span class="price-range-val" id="price-val">RM <?= number_format(min($max_price,15000)) ?></span>
                    </div>
                </div>

                <div class="filter-group">
                    <label class="filter-label">Stops</label>
                    <div class="radio-group">
                        <label class="radio-opt"><input type="radio" name="stops" value="any"    <?= $stops_filter==='any'   ?'checked':'' ?>> Any</label>
                        <label class="radio-opt"><input type="radio" name="stops" value="direct" <?= $stops_filter==='direct'?'checked':'' ?>> Direct only</label>
                    </div>
                </div>

                <div class="filter-group">
                    <label class="filter-label">Airline</label>
                    <div class="radio-group">
                        <label class="radio-opt"><input type="radio" name="airline" value="" <?= !$airline_filter?'checked':'' ?>> All Airlines</label>
                        <?php foreach (array_unique(array_column($flights,'airline_code')) as $ac):
                            $al = $flights[array_search($ac,array_column($flights,'airline_code'))];?>
                        <label class="radio-opt">
                            <input type="radio" name="airline" value="<?= $ac ?>" <?= $airline_filter===$ac?'checked':'' ?>>
                            <?= htmlspecialchars($al['airline']) ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="filter-group">
                    <label class="filter-label">Departure Time</label>
                    <div class="time-chips">
                        <label class="time-chip"><input type="checkbox" class="dep-time-cb" value="night"> 🌑 Night<br><small>00–06</small></label>
                        <label class="time-chip"><input type="checkbox" class="dep-time-cb" value="morning"> ☀️ Morning<br><small>06–12</small></label>
                        <label class="time-chip"><input type="checkbox" class="dep-time-cb" value="afternoon"> 🌤 Afternoon<br><small>12–18</small></label>
                        <label class="time-chip"><input type="checkbox" class="dep-time-cb" value="evening"> 🌙 Evening<br><small>18–24</small></label>
                    </div>
                </div>

                <div class="filter-group">
                    <label class="filter-label">Max Flight Duration: <strong id="dur-filter-val">Any</strong></label>
                    <input type="range" id="dur-filter" min="1" max="25" step="1" value="25" style="width:100%">
                </div>

                <button type="submit" class="filter-apply-btn">Apply Filters</button>
                <a href="search.php?from=<?= $from ?>&to=<?= $to ?>&date=<?= $date ?>&pax=<?= $pax ?>&class=<?= $cabin ?>" class="filter-reset">Reset</a>
            </form>
        </aside>

        <main class="results-main">
            <div class="results-header">
                <div>
                    <h1 class="results-title">
                        <?= htmlspecialchars($from_city) ?> <span>→</span> <?= htmlspecialchars($to_city) ?>
                        <?php if ($to_ap['country']): ?><small class="results-country"><?= htmlspecialchars($to_ap['country']) ?></small><?php endif; ?>
                    </h1>
                    <p class="results-meta">
                        <?= date('D, d M Y', strtotime($date)) ?> · <?= $pax ?> pax · <?= ucfirst($cabin) ?> ·
                        <strong><?= count($filtered) ?> flight<?= count($filtered)!==1?'s':'' ?> found</strong>
                    </p>
                </div>
                <div class="sort-bar">
                    <span>Sort:</span>
                    <?php foreach (['best'=>'Best','price'=>'Price','duration'=>'Duration','departure'=>'Departure','airline'=>'Airline'] as $k=>$v): ?>
                    <a href="?from=<?=$from?>&to=<?=$to?>&date=<?=$date?>&pax=<?=$pax?>&class=<?=$cabin?>&sort=<?=$k?>&max_price=<?=$max_price?>&stops=<?=$stops_filter?>&airline=<?=$airline_filter?>"
                       class="sort-btn <?= $sort===$k?'active':'' ?>"><?= $v ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
            <button class="cal-trigger-btn" id="open-price-cal">📅 Price Calendar</button>

            <?php if (empty($filtered)): ?>
            <div class="no-results">
                <div class="no-results-icon">✈️</div>
                <h3>No flights found</h3>
                <p>Try adjusting your filters or a different date.</p>
                <a href="search.php?from=<?=$from?>&to=<?=$to?>&date=<?=$date?>&pax=<?=$pax?>" class="btn-primary">Clear Filters</a>
            </div>
            <?php else:
                $cheapest = $filtered[0];
                $cp = match($cabin) {'business'=>$cheapest['business_price'],'first'=>($cheapest['first_price']??0),default=>$cheapest['economy_price']};
            ?>
            <div class="cheapest-banner">
                <span>💡 Cheapest today: <strong><?= format_price($cp) ?></strong> with <?= htmlspecialchars($cheapest['airline']) ?> at <?= $cheapest['departure'] ?></span>
                <button class="alert-link watchlist-quick-add"
                        data-from="<?= $from ?>" data-to="<?= $to ?>"
                        data-date="<?= $date ?>" data-price="<?= $cp ?>"
                        data-from-city="<?= htmlspecialchars($from_city) ?>"
                        data-to-city="<?= htmlspecialchars($to_city) ?>">
                    🔔 Watch This Route
                </button>
            </div>

            <div class="flight-list">
                <?php foreach ($filtered as $idx => $f):
                    $price = match($cabin) {'business'=>$f['business_price'],'first'=>($f['first_price']??0),default=>$f['economy_price']};
                    $is_best = ($idx === 0);
                ?>
                <div class="flight-card <?= $is_best?'flight-card--best':'' ?>"
                     data-dep="<?= $f['departure'] ?>"
                     data-dur="<?= $f['duration_mins'] ?>"
                     data-co2="<?= $f['co2_kg'] ?>"
                     data-eco="<?= $f['economy_price'] ?>"
                     data-biz="<?= $f['business_price'] ?>">
                    <?php if ($is_best): ?><div class="best-tag">Best Value</div><?php endif; ?>

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
                                <div class="time"><?= $f['departure'] ?></div>
                                <div class="iata"><?= $f['from'] ?></div>
                                <div class="iata-city"><?= htmlspecialchars($from_city) ?></div>
                            </div>
                            <div class="duration-block">
                                <div class="duration-line">
                                    <div class="line-dot"></div>
                                    <div class="line-bar"></div>
                                    <div class="line-plane">✈</div>
                                    <div class="line-bar"></div>
                                    <div class="line-dot"></div>
                                </div>
                                <div class="duration-label"><?= $f['duration_fmt'] ?></div>
                                <div class="dist-label"><?= number_format($f['distance_km']) ?> km</div>
                                <div class="stops-label"><?= $f['stops']===0?'✅ Direct':'⚠ '.$f['stops'].' stop' ?></div>
                            </div>
                            <div class="time-block time-block--right">
                                <div class="time"><?= $f['arrival'] ?></div>
                                <div class="iata"><?= $f['to'] ?></div>
                                <div class="iata-city"><?= htmlspecialchars($to_city) ?></div>
                                <?php if ($f['arr_date'] !== $f['date']): ?><div class="next-day">+1</div><?php endif; ?>
                            </div>
                        </div>

                        <div class="flight-amenities">
                            <span class="amenity">🧳 <?= htmlspecialchars($f['baggage']) ?></span>
                            <span class="amenity">🍽 <?= htmlspecialchars($f['meal']) ?></span>
                            <?php if ($f['refundable']): ?><span class="amenity amenity--green">✅ Refundable</span><?php endif; ?>
                            <span class="amenity amenity--seats"><?= $f['seats_left'] ?> seats</span>
                            <span class="amenity amenity--co2">🌿 <?= $f['co2_kg'] ?> kg CO₂</span>
                        </div>

                        <div class="flight-price-block">
                            <div class="price-per">per person</div>
                            <div class="price-main"><span class="fx" data-myr="<?= $price ?>"><?= format_price($price) ?></span></div>
                            <?php if ($pax>1): ?><div class="price-total"><?= format_price($price*$pax) ?> total</div><?php endif; ?>

                            <a href="flight-details.php?from=<?= $from ?>&to=<?= $to ?>&date=<?= $date ?>&pax=<?= $pax ?>&class=<?= $cabin ?>&fn=<?= urlencode($f['flight_no']) ?>&dep=<?= urlencode($f['departure']) ?>&airline=<?= $f['airline_code'] ?>"
                               class="select-btn">Select →</a>

                            <!-- Watchlist button -->
                            <button class="watch-btn watchlist-add"
                                    data-from="<?= $from ?>" data-to="<?= $to ?>"
                                    data-date="<?= $date ?>" data-price="<?= $price ?>"
                                    data-airline="<?= htmlspecialchars($f['airline']) ?>"
                                    data-flight-no="<?= htmlspecialchars($f['flight_no']) ?>"
                                    data-dep="<?= $f['departure'] ?>"
                                    data-from-city="<?= htmlspecialchars($from_city) ?>"
                                    data-to-city="<?= htmlspecialchars($to_city) ?>">
                                🔔 Watch
                            </button>

                            <div class="seats-urgency <?= $f['seats_left']<=5?'urgency--high':'' ?>">
                                <?= $f['seats_left']<=5 ? '🔥 Only '.$f['seats_left'].' left!' : $f['seats_left'].' seats available' ?>
                            </div>
                        </div>
                    </div>

                    <!-- Price trend bar -->
                    <div class="price-trend">
                        <span class="trend-label">30-day range:</span>
                        <span class="trend-low">RM <?= number_format($f['low_price']) ?></span>
                        <div class="trend-bar-wrap">
                            <div class="trend-bar">
                                <?php $pct = min(100, round(($price-$f['low_price'])/max(1,$f['high_price']-$f['low_price'])*100)); ?>
                                <div class="trend-marker" style="left:<?= $pct ?>%"></div>
                            </div>
                        </div>
                        <span class="trend-high">RM <?= number_format($f['high_price']) ?></span>
                        <a href="flight-details.php?from=<?=$from?>&to=<?=$to?>&date=<?=$date?>&pax=<?=$pax?>&class=<?=$cabin?>&fn=<?=urlencode($f['flight_no'])?>&dep=<?=urlencode($f['departure'])?>&airline=<?=$f['airline_code']?>"
                           class="trend-history-link">Chart →</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Date strip -->
            <div class="date-strip-section">
                <h3>Check other dates</h3>
                <div class="date-strip">
                    <?php for ($i=-3;$i<=3;$i++):
                        $d = date('Y-m-d', strtotime("$date $i days"));
                        if ($d < date('Y-m-d')) continue;
                        $df = generate_flights($from,$to,$d,$pax);
                        $dp = $df ? match($cabin){'business'=>$df[0]['business_price'],'first'=>($df[0]['first_price']??0),default=>$df[0]['economy_price']} : null;
                    ?>
                    <a href="search.php?from=<?=$from?>&to=<?=$to?>&date=<?=$d?>&pax=<?=$pax?>&class=<?=$cabin?>"
                       class="date-chip <?= $d===$date?'date-chip--active':'' ?>">
                        <div class="chip-day"><?= date('D',strtotime($d)) ?></div>
                        <div class="chip-date"><?= date('d M',strtotime($d)) ?></div>
                        <?php if ($dp): ?><div class="chip-price">RM<?= number_format($dp) ?></div><?php endif; ?>
                    </a>
                    <?php endfor; ?>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<!-- Price Calendar Modal -->
<div class="modal-overlay" id="price-cal-modal" style="display:none">
    <div class="modal-box modal-box--wide">
        <div class="modal-header">
            <h3>📅 Price Calendar</h3>
            <button class="modal-close" id="cal-modal-close">✕</button>
        </div>
        <div class="modal-body">
            <p class="modal-sub" id="cal-subtitle"></p>
            <div class="cal-legend">
                <span class="cal-leg cal-leg--low">Cheapest</span>
                <span class="cal-leg cal-leg--mid">Average</span>
                <span class="cal-leg cal-leg--high">Expensive</span>
            </div>
            <div class="price-calendar" id="price-calendar"></div>
        </div>
    </div>
</div>

<!-- Watchlist Modal -->
<div class="modal-overlay" id="watchlist-modal" style="display:none">
    <div class="modal-box">
        <div class="modal-header">
            <h3>🔔 Add to Watchlist</h3>
            <button class="modal-close" id="modal-close">✕</button>
        </div>
        <div class="modal-body">
            <p class="modal-route" id="modal-route"></p>
            <p class="modal-sub">Current price: <strong id="modal-current-price"></strong></p>
            <label class="modal-label">Your target price (MYR per person)</label>
            <div class="modal-price-wrap">
                <span class="modal-rm">RM</span>
                <input type="number" id="modal-target" min="50" max="50000" placeholder="e.g. 500">
            </div>
            <p class="modal-hint">We'll notify you on-screen when the price drops to or below your target.</p>
        </div>
        <div class="modal-footer">
            <button class="modal-cancel" id="modal-cancel">Cancel</button>
            <button class="modal-confirm" id="modal-confirm">Add to Watchlist</button>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
