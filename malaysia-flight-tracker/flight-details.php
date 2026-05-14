<?php
require_once 'includes/flights_data.php';

$from  = strtoupper(preg_replace('/[^A-Z]/', '', $_GET['from']    ?? 'KUL'));
$to    = strtoupper(preg_replace('/[^A-Z]/', '', $_GET['to']      ?? 'SIN'));
$date  = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['date'] ?? '') ? $_GET['date'] : date('Y-m-d');
$pax   = max(1, min(9, (int)($_GET['pax']   ?? 1)));
$cabin = in_array($_GET['class'] ?? '', ['economy','business','first']) ? $_GET['class'] : 'economy';
$fn    = htmlspecialchars($_GET['fn']      ?? '');
$dep   = htmlspecialchars($_GET['dep']     ?? '');
$ac    = strtoupper(preg_replace('/[^A-Z]/', '', $_GET['airline'] ?? ''));

$airports = get_airport_list();
$flights  = generate_flights($from, $to, $date, $pax);

$flight = null;
foreach ($flights as $f) {
    if ($f['flight_no'] === $fn && $f['departure'] === $dep) { $flight = $f; break; }
}
if (!$flight && !empty($flights)) $flight = $flights[0];

$price = $flight ? match($cabin) {
    'business' => $flight['business_price'],
    'first'    => $flight['first_price'] ?? $flight['business_price'],
    default    => $flight['economy_price'],
} : 0;

$from_ap   = $airports[$from] ?? ['city'=>$from,'country'=>'','name'=>$from];
$to_ap     = $airports[$to]   ?? ['city'=>$to,  'country'=>'','name'=>$to];
$from_city = $from_ap['city'];
$to_city   = $to_ap['city'];

$page_title  = $flight ? "{$flight['flight_no']} {$from_city} → {$to_city} — Mia Flight n Go" : 'Flight Details — Mia Flight n Go';
$active_page = 'home';
$base_path   = '';

require_once 'includes/header.php';
?>

<script>
window.AIRPORTS = <?= json_encode(array_map(fn($code,$ap)=>['code'=>$code,'city'=>$ap['city'],'country'=>$ap['country'],'name'=>$ap['name'],'region'=>$ap['region']], array_keys($airports), $airports), JSON_UNESCAPED_UNICODE) ?>;
</script>

<div class="details-page">
<div class="container">
    <div class="breadcrumb">
        <a href="index.php">Home</a> ›
        <a href="search.php?from=<?=$from?>&to=<?=$to?>&date=<?=$date?>&pax=<?=$pax?>&class=<?=$cabin?>">
            <?= htmlspecialchars($from_city) ?> → <?= htmlspecialchars($to_city) ?>
        </a> › Flight Details
    </div>

<?php if (!$flight): ?>
<div class="no-results"><h3>Flight not found</h3><a href="index.php" class="btn-primary">Search Again</a></div>
<?php else: ?>

    <div class="detail-hero">
        <div class="detail-airline-strip" style="background:<?= htmlspecialchars($flight['airline_color']) ?>">
            <div class="detail-airline-logo"><?= htmlspecialchars($flight['airline_code']) ?></div>
            <div>
                <div class="detail-airline-name"><?= htmlspecialchars($flight['airline']) ?></div>
                <div class="detail-flight-no">Flight <?= htmlspecialchars($flight['flight_no']) ?> · <?= htmlspecialchars($flight['aircraft']) ?> · <?= number_format($flight['distance_km']) ?> km</div>
            </div>
            <div class="detail-badge"><?= htmlspecialchars($flight['airline_type']) ?></div>
        </div>

        <div class="detail-route-card">
            <div class="detail-route">
                <div class="detail-endpoint">
                    <div class="detail-time"><?= htmlspecialchars($flight['departure']) ?></div>
                    <div class="detail-iata"><?= htmlspecialchars($flight['from']) ?></div>
                    <div class="detail-city"><?= htmlspecialchars($from_city) ?></div>
                    <div class="detail-country"><?= htmlspecialchars($from_ap['country']) ?></div>
                    <div class="detail-airport"><?= htmlspecialchars($from_ap['name']) ?></div>
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
                    <div class="detail-direct"><?= $flight['stops']===0?'✅ Direct Flight':'⚠ '.$flight['stops'].' stop(s)' ?></div>
                </div>
                <div class="detail-endpoint detail-endpoint--right">
                    <div class="detail-time"><?= htmlspecialchars($flight['arrival']) ?></div>
                    <div class="detail-iata"><?= htmlspecialchars($flight['to']) ?></div>
                    <div class="detail-city"><?= htmlspecialchars($to_city) ?></div>
                    <div class="detail-country"><?= htmlspecialchars($to_ap['country']) ?></div>
                    <div class="detail-airport"><?= htmlspecialchars($to_ap['name']) ?></div>
                    <div class="detail-date">
                        <?= date('D, d M Y', strtotime($flight['arr_date'])) ?>
                        <?php if ($flight['arr_date']!==$flight['date']): ?><span class="next-day-badge">+1 day</span><?php endif; ?>
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
                    <div class="fare-card <?= $cabin==='economy'?'fare-card--selected':'' ?>">
                        <div class="fare-name">Economy</div>
                        <div class="fare-price"><?= format_price($flight['economy_price']) ?><span>/person</span></div>
                        <ul class="fare-features">
                            <li>✅ 7 kg cabin bag</li>
                            <li><?= $flight['baggage']==='23–30 kg included'?'✅':'💳' ?> <?= htmlspecialchars($flight['baggage']) ?></li>
                            <li><?= $flight['meal']==='Included'?'✅':'💳' ?> <?= htmlspecialchars($flight['meal']) ?></li>
                            <li><?= $flight['refundable']?'✅ Refundable':'❌ Non-refundable' ?></li>
                        </ul>
                        <a href="?from=<?=$from?>&to=<?=$to?>&date=<?=$date?>&pax=<?=$pax?>&class=economy&fn=<?=urlencode($fn)?>&dep=<?=urlencode($dep)?>&airline=<?=$ac?>"
                           class="fare-btn <?= $cabin==='economy'?'fare-btn--active':'' ?>"><?= $cabin==='economy'?'Selected ✓':'Select Economy' ?></a>
                    </div>

                    <div class="fare-card fare-card--business <?= $cabin==='business'?'fare-card--selected':'' ?>">
                        <div class="fare-badge">Premium</div>
                        <div class="fare-name">Business</div>
                        <div class="fare-price"><?= format_price($flight['business_price']) ?><span>/person</span></div>
                        <ul class="fare-features">
                            <li>✅ 10 kg cabin bag</li>
                            <li>✅ 30 kg check-in</li>
                            <li>✅ Meal included</li>
                            <li>✅ Refundable</li>
                            <li>✅ Priority boarding</li>
                            <li>✅ Extra legroom</li>
                        </ul>
                        <a href="?from=<?=$from?>&to=<?=$to?>&date=<?=$date?>&pax=<?=$pax?>&class=business&fn=<?=urlencode($fn)?>&dep=<?=urlencode($dep)?>&airline=<?=$ac?>"
                           class="fare-btn fare-btn--business <?= $cabin==='business'?'fare-btn--active':'' ?>"><?= $cabin==='business'?'Selected ✓':'Select Business' ?></a>
                    </div>

                    <?php if ($flight['first_price']): ?>
                    <div class="fare-card fare-card--first <?= $cabin==='first'?'fare-card--selected':'' ?>">
                        <div class="fare-badge fare-badge--gold">First Class</div>
                        <div class="fare-name">First Class</div>
                        <div class="fare-price"><?= format_price($flight['first_price']) ?><span>/person</span></div>
                        <ul class="fare-features">
                            <li>✅ Private suite</li>
                            <li>✅ 40 kg baggage</li>
                            <li>✅ Fine dining</li>
                            <li>✅ Fully refundable</li>
                            <li>✅ Lounge access</li>
                            <li>✅ Chauffeur service</li>
                        </ul>
                        <a href="?from=<?=$from?>&to=<?=$to?>&date=<?=$date?>&pax=<?=$pax?>&class=first&fn=<?=urlencode($fn)?>&dep=<?=urlencode($dep)?>&airline=<?=$ac?>"
                           class="fare-btn fare-btn--first <?= $cabin==='first'?'fare-btn--active':'' ?>"><?= $cabin==='first'?'Selected ✓':'Select First' ?></a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Price History -->
            <div class="detail-section">
                <h2 class="section-title">📊 Price History (Last 30 Days)</h2>
                <p class="section-sub">Historical economy price for this route</p>
                <div class="price-chart-wrap"><canvas id="priceChart" height="130"></canvas></div>
                <div class="chart-legend">
                    <div class="chart-stats">
                        <div class="stat-box"><div class="stat-val text-green">RM <?= number_format($flight['low_price']) ?></div><div class="stat-lbl">30-day Low</div></div>
                        <div class="stat-box stat-box--current"><div class="stat-val"><?= format_price($price) ?></div><div class="stat-lbl">Current</div></div>
                        <div class="stat-box"><div class="stat-val text-red">RM <?= number_format($flight['high_price']) ?></div><div class="stat-lbl">30-day High</div></div>
                    </div>
                    <?php
                    $vs_low = round((($price-$flight['low_price'])/max(1,$flight['low_price']))*100);
                    $v = $vs_low<=5?['🟢','Great time to buy! Near 30-day low.','text-green']:($vs_low<=20?['🟡','Average price. Consider setting an alert.','text-yellow']:['🔴','Above average. Wait or track drops.','text-red']);
                    ?>
                    <div class="price-verdict <?= $v[2] ?>"><?= $v[0] ?> <?= $v[1] ?></div>
                </div>
                <script>
                const chartData = {
                    labels: <?= json_encode(array_column($flight['price_history'],'date')) ?>,
                    prices: <?= json_encode(array_column($flight['price_history'],'price')) ?>,
                    current: <?= $price ?>
                };
                </script>
            </div>
        </div>

        <!-- Booking Summary -->
        <div class="detail-right">
            <div class="booking-summary">
                <h3 class="summary-title">Booking Summary</h3>
                <div class="summary-route"><strong><?= htmlspecialchars($from_city) ?></strong><span>→</span><strong><?= htmlspecialchars($to_city) ?></strong></div>
                <div class="summary-country"><?= htmlspecialchars($from_ap['country']) ?> → <?= htmlspecialchars($to_ap['country']) ?></div>
                <div class="summary-meta"><?= date('D, d M Y', strtotime($date)) ?> · <?= $flight['departure'] ?>–<?= $flight['arrival'] ?></div>

                <div class="summary-line"><span><?= ucfirst($cabin) ?> × <?= $pax ?></span><span><?= format_price($price) ?> × <?= $pax ?></span></div>
                <div class="summary-line summary-line--tax"><span>Taxes & Fees</span><span>Included</span></div>
                <div class="summary-total"><span>Total</span><span><?= format_price($price*$pax) ?></span></div>

                <div class="seats-warning <?= $flight['seats_left']<=5?'seats-warning--urgent':'' ?>">
                    <?= $flight['seats_left']<=5 ? "🔥 Only {$flight['seats_left']} seats left!" : "✅ {$flight['seats_left']} seats available" ?>
                </div>

                <div class="booking-disclaimer">
                    ⚠️ Mia Flight n Go is a price tracker. Clicking below opens the airline's official booking site.
                </div>

                <?php
                $links = ['AK'=>'https://www.airasia.com','MH'=>'https://www.malaysiaairlines.com',
                          'EK'=>'https://www.emirates.com','QR'=>'https://www.qatarairways.com',
                          'SQ'=>'https://www.singaporeair.com','BA'=>'https://www.britishairways.com',
                          'LH'=>'https://www.lufthansa.com','QF'=>'https://www.qantas.com'];
                $url = $links[$flight['airline_code']] ?? 'https://www.google.com/travel/flights';
                ?>
                <a href="<?= htmlspecialchars($url) ?>" target="_blank" rel="noopener" class="book-btn">
                    Book on <?= htmlspecialchars($flight['airline']) ?> →
                </a>

                <!-- Watchlist button on details page -->
                <button class="alert-btn watchlist-add"
                        data-from="<?= $from ?>" data-to="<?= $to ?>"
                        data-date="<?= $date ?>" data-price="<?= $price ?>"
                        data-airline="<?= htmlspecialchars($flight['airline']) ?>"
                        data-flight-no="<?= htmlspecialchars($flight['flight_no']) ?>"
                        data-dep="<?= $flight['departure'] ?>"
                        data-from-city="<?= htmlspecialchars($from_city) ?>"
                        data-to-city="<?= htmlspecialchars($to_city) ?>">
                    🔔 Add to Price Watchlist
                </button>
            </div>
        </div>
    </div>

<?php endif; ?>
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
