<?php
require_once 'includes/flights_data.php';

$from    = strtoupper(preg_replace('/[^A-Z]/', '', $_GET['from']    ?? 'KUL'));
$to      = strtoupper(preg_replace('/[^A-Z]/', '', $_GET['to']      ?? 'PEN'));
$date    = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['date'] ?? '') ? $_GET['date'] : date('Y-m-d');
$pax     = max(1, min(9, (int)($_GET['pax'] ?? 1)));
$cabin   = in_array($_GET['class'] ?? '', ['economy','business']) ? $_GET['class'] : 'economy';
$fn      = htmlspecialchars($_GET['fn'] ?? '');
$dep     = htmlspecialchars($_GET['dep'] ?? '');
$arr     = htmlspecialchars($_GET['arr'] ?? '');
$ac      = strtoupper(preg_replace('/[^A-Z]/', '', $_GET['airline'] ?? 'AK'));

$airports = get_airport_list();
$flights  = generate_flights($from, $to, $date, $pax);

// Find this specific flight
$flight = null;
foreach ($flights as $f) {
    if ($f['flight_no'] === $fn && $f['departure'] === $dep) {
        $flight = $f;
        break;
    }
}

// Fallback to first flight if not found (demo mode)
if (!$flight && !empty($flights)) $flight = $flights[0];

$price     = $flight ? (($cabin === 'business') ? $flight['business_price'] : $flight['economy_price']) : 0;
$from_city = $airports[$from]['city'] ?? $from;
$to_city   = $airports[$to]['city']   ?? $to;

$page_title  = $flight ? "{$flight['flight_no']} {$from_city} → {$to_city} — FlightGo" : 'Flight Details — FlightGo';
$active_page = 'home';
$base_path   = '';

require_once 'includes/header.php';
?>

<div class="details-page">
<div class="container">
    <div class="breadcrumb">
        <a href="index.php">Home</a> ›
        <a href="search.php?from=<?=$from?>&to=<?=$to?>&date=<?=$date?>&pax=<?=$pax?>&class=<?=$cabin?>">
            <?= htmlspecialchars($from_city) ?> → <?= htmlspecialchars($to_city) ?>
        </a> ›
        Flight Details
    </div>

<?php if (!$flight): ?>
<div class="no-results"><div class="no-results-icon">✈️</div><h3>Flight not found</h3><a href="index.php" class="btn-primary">Search Again</a></div>
<?php else: ?>

    <!-- Flight Summary Card -->
    <div class="detail-hero">
        <div class="detail-airline-strip" style="background:<?= htmlspecialchars($flight['airline_color']) ?>">
            <div class="detail-airline-logo"><?= htmlspecialchars($flight['airline_code']) ?></div>
            <div>
                <div class="detail-airline-name"><?= htmlspecialchars($flight['airline']) ?></div>
                <div class="detail-flight-no">Flight <?= htmlspecialchars($flight['flight_no']) ?> · <?= htmlspecialchars($flight['aircraft']) ?></div>
            </div>
            <div class="detail-badge"><?= htmlspecialchars($flight['airline_type']) ?></div>
        </div>

        <div class="detail-route-card">
            <div class="detail-route">
                <div class="detail-endpoint">
                    <div class="detail-time"><?= htmlspecialchars($flight['departure']) ?></div>
                    <div class="detail-iata"><?= htmlspecialchars($flight['from']) ?></div>
                    <div class="detail-city"><?= htmlspecialchars($from_city) ?></div>
                    <div class="detail-airport"><?= htmlspecialchars($airports[$from]['name'] ?? '') ?></div>
                    <div class="detail-date"><?= date('D, d M Y', strtotime($flight['date'])) ?></div>
                </div>

                <div class="detail-middle">
                    <div class="detail-duration"><?= htmlspecialchars($flight['duration_fmt']) ?></div>
                    <div class="detail-route-line">
                        <div class="route-dot"></div>
                        <div class="route-dash-line"></div>
                        <div class="route-plane-icon">✈</div>
                        <div class="route-dash-line"></div>
                        <div class="route-dot"></div>
                    </div>
                    <div class="detail-direct">✅ Direct Flight · No stops</div>
                </div>

                <div class="detail-endpoint detail-endpoint--right">
                    <div class="detail-time"><?= htmlspecialchars($flight['arrival']) ?></div>
                    <div class="detail-iata"><?= htmlspecialchars($flight['to']) ?></div>
                    <div class="detail-city"><?= htmlspecialchars($to_city) ?></div>
                    <div class="detail-airport"><?= htmlspecialchars($airports[$to]['name'] ?? '') ?></div>
                    <div class="detail-date">
                        <?= date('D, d M Y', strtotime($flight['arr_date'])) ?>
                        <?php if ($flight['arr_date'] !== $flight['date']): ?><span class="next-day-badge">+1 day</span><?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="detail-grid">
        <div class="detail-left">

            <!-- Fare Options -->
            <div class="detail-section">
                <h2 class="section-title">Choose Your Fare</h2>
                <div class="fare-cards">
                    <div class="fare-card <?= $cabin === 'economy' ? 'fare-card--selected' : '' ?>">
                        <div class="fare-name">Economy</div>
                        <div class="fare-price"><?= format_price($flight['economy_price']) ?><span>/person</span></div>
                        <ul class="fare-features">
                            <li>✅ 7 kg cabin baggage</li>
                            <li><?= $flight['baggage'] === '20 kg included' ? '✅' : '💳' ?> <?= htmlspecialchars($flight['baggage']) ?></li>
                            <li><?= $flight['meal'] === 'Included' ? '✅' : '💳' ?> <?= htmlspecialchars($flight['meal']) ?></li>
                            <li><?= $flight['refundable'] ? '✅ Refundable' : '❌ Non-refundable' ?></li>
                            <li>💺 Standard seat</li>
                        </ul>
                        <a href="?from=<?=$from?>&to=<?=$to?>&date=<?=$date?>&pax=<?=$pax?>&class=economy&fn=<?=urlencode($fn)?>&dep=<?=urlencode($dep)?>&arr=<?=urlencode($arr)?>&airline=<?=$ac?>"
                           class="fare-btn <?= $cabin === 'economy' ? 'fare-btn--active' : '' ?>">
                            <?= $cabin === 'economy' ? 'Selected ✓' : 'Select Economy' ?>
                        </a>
                    </div>

                    <div class="fare-card fare-card--business <?= $cabin === 'business' ? 'fare-card--selected' : '' ?>">
                        <div class="fare-badge">Premium</div>
                        <div class="fare-name">Business Class</div>
                        <div class="fare-price"><?= format_price($flight['business_price']) ?><span>/person</span></div>
                        <ul class="fare-features">
                            <li>✅ 10 kg cabin baggage</li>
                            <li>✅ 30 kg check-in baggage</li>
                            <li>✅ Meal included</li>
                            <li>✅ Fully refundable</li>
                            <li>✅ Priority boarding</li>
                            <li>✅ Extra legroom seat</li>
                        </ul>
                        <a href="?from=<?=$from?>&to=<?=$to?>&date=<?=$date?>&pax=<?=$pax?>&class=business&fn=<?=urlencode($fn)?>&dep=<?=urlencode($dep)?>&arr=<?=urlencode($arr)?>&airline=<?=$ac?>"
                           class="fare-btn fare-btn--business <?= $cabin === 'business' ? 'fare-btn--active' : '' ?>">
                            <?= $cabin === 'business' ? 'Selected ✓' : 'Select Business' ?>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Price History Chart -->
            <div class="detail-section">
                <h2 class="section-title">📊 Price History (Last 30 Days)</h2>
                <p class="section-sub">Current price vs historical range for this route</p>
                <div class="price-chart-wrap">
                    <canvas id="priceChart" height="120"></canvas>
                </div>
                <div class="chart-legend">
                    <div class="chart-stats">
                        <div class="stat-box">
                            <div class="stat-val text-green">RM <?= number_format($flight['low_price']) ?></div>
                            <div class="stat-lbl">30-day Low</div>
                        </div>
                        <div class="stat-box stat-box--current">
                            <div class="stat-val"><?= format_price($price) ?></div>
                            <div class="stat-lbl">Current Price</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-val text-red">RM <?= number_format($flight['high_price']) ?></div>
                            <div class="stat-lbl">30-day High</div>
                        </div>
                    </div>
                    <?php
                        $vs_low = round((($price - $flight['low_price']) / max(1, $flight['low_price'])) * 100);
                        $verdict = $vs_low <= 5 ? ['🟢','Great time to buy! Price is near its 30-day low.','text-green']
                                 : ($vs_low <= 20 ? ['🟡','Price is average. Consider setting a price alert.','text-yellow']
                                 : ['🔴','Price is above average. Consider waiting or tracking drops.','text-red']);
                    ?>
                    <div class="price-verdict <?= $verdict[2] ?>">
                        <?= $verdict[0] ?> <?= $verdict[1] ?>
                    </div>
                </div>
                <script>
                const chartData = {
                    labels: <?= json_encode(array_column($flight['price_history'], 'date')) ?>,
                    prices: <?= json_encode(array_column($flight['price_history'], 'price')) ?>,
                    current: <?= $price ?>
                };
                </script>
            </div>

        </div>

        <!-- Right: Booking Summary -->
        <div class="detail-right">
            <div class="booking-summary">
                <h3 class="summary-title">Booking Summary</h3>

                <div class="summary-route">
                    <strong><?= htmlspecialchars($from_city) ?></strong>
                    <span>→</span>
                    <strong><?= htmlspecialchars($to_city) ?></strong>
                </div>
                <div class="summary-meta">
                    <?= date('D, d M Y', strtotime($date)) ?>
                    · <?= htmlspecialchars($flight['departure']) ?> – <?= htmlspecialchars($flight['arrival']) ?>
                </div>

                <div class="summary-line">
                    <span><?= ucfirst($cabin) ?> × <?= $pax ?></span>
                    <span><?= format_price($price) ?> × <?= $pax ?></span>
                </div>
                <div class="summary-line summary-line--tax">
                    <span>Taxes & Fees</span>
                    <span>Included</span>
                </div>
                <div class="summary-total">
                    <span>Total</span>
                    <span><?= format_price($price * $pax) ?></span>
                </div>

                <div class="seats-warning <?= $flight['seats_left'] <= 5 ? 'seats-warning--urgent' : '' ?>">
                    <?= $flight['seats_left'] <= 5
                        ? "🔥 Only {$flight['seats_left']} seats left at this price!"
                        : "✅ {$flight['seats_left']} seats available" ?>
                </div>

                <div class="booking-disclaimer">
                    <p>⚠️ FlightGo is a price comparison & tracker tool. Clicking below will take you to the airline's official website to complete your booking.</p>
                </div>

                <?php
                $airline_urls = ['AK'=>'https://www.airasia.com','MH'=>'https://www.malaysiaairlines.com','OD'=>'https://www.batikair.com','FY'=>'https://www.fireflyz.com.my'];
                $book_url = $airline_urls[$ac] ?? 'https://www.airasia.com';
                ?>
                <a href="<?= htmlspecialchars($book_url) ?>" target="_blank" rel="noopener" class="book-btn">
                    Book on <?= htmlspecialchars($flight['airline']) ?> →
                </a>

                <a href="price-tracker.php?from=<?=$from?>&to=<?=$to?>&target=<?=$price?>" class="alert-btn">
                    🔔 Set Price Alert for This Route
                </a>
            </div>
        </div>
    </div>

<?php endif; ?>
</div>
</div>

<?php require_once 'includes/footer.php'; ?>
