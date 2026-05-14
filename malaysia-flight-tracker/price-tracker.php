<?php
require_once 'includes/flights_data.php';

$page_title  = 'Price Tracker & Alerts — TerbangMY';
$active_page = 'tracker';
$base_path   = '';
$airports    = get_airport_list();

$from   = strtoupper(preg_replace('/[^A-Z]/', '', $_GET['from']   ?? 'KUL'));
$to     = strtoupper(preg_replace('/[^A-Z]/', '', $_GET['to']     ?? ''));
$target = is_numeric($_GET['target'] ?? '') ? (int)$_GET['target'] : 300;

$success_msg = '';
$error_msg   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $p_from   = strtoupper(preg_replace('/[^A-Z]/', '', $_POST['from']   ?? ''));
    $p_to     = strtoupper(preg_replace('/[^A-Z]/', '', $_POST['to']     ?? ''));
    $p_date   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['date']  ?? '') ? $_POST['date'] : '';
    $p_target = is_numeric($_POST['target'] ?? '') ? (int)$_POST['target'] : 0;
    $p_email  = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);

    if (!$p_from || !$p_to || !$p_date || !$p_target || !filter_var($p_email, FILTER_VALIDATE_EMAIL)) {
        $error_msg = 'Please fill in all fields correctly.';
    } else {
        $from_city_p = $airports[$p_from]['city'] ?? $p_from;
        $to_city_p   = $airports[$p_to]['city']   ?? $p_to;
        $success_msg = "✅ Alert set! We'll email <strong>$p_email</strong> when {$from_city_p} → {$to_city_p} drops below <strong>RM $p_target</strong> on " . date('d M Y', strtotime($p_date)) . ".";
    }
}

// Generate sample price data for the tracker view
$sample_routes_tracker = [
    ['from'=>'KUL','to'=>'BKI','label'=>'KL → Kota Kinabalu'],
    ['from'=>'KUL','to'=>'PEN','label'=>'KL → Penang'],
    ['from'=>'KUL','to'=>'LGK','label'=>'KL → Langkawi'],
    ['from'=>'KUL','to'=>'KCH','label'=>'KL → Kuching'],
];

$trend_data = [];
foreach ($sample_routes_tracker as $sr) {
    $fl = generate_flights($sr['from'], $sr['to'], date('Y-m-d', strtotime('+7 days')), 1);
    if ($fl) {
        $trend_data[] = [
            'route'     => $sr['label'],
            'from'      => $sr['from'],
            'to'        => $sr['to'],
            'current'   => $fl[0]['economy_price'],
            'low'       => $fl[0]['low_price'],
            'high'      => $fl[0]['high_price'],
            'history'   => $fl[0]['price_history'],
            'airline'   => $fl[0]['airline'],
            'flight_no' => $fl[0]['flight_no'],
        ];
    }
}

require_once 'includes/header.php';
?>

<div class="tracker-page">
<div class="tracker-hero">
    <div class="container">
        <h1 class="tracker-title">✈️ Flight Price Tracker</h1>
        <p class="tracker-subtitle">Never overpay for flights again. Set your target price and get an email the moment fares drop.</p>
    </div>
</div>

<div class="container tracker-layout">

    <!-- Alert Setup Form -->
    <div class="tracker-form-card">
        <h2 class="card-title">🔔 Set a Price Alert</h2>
        <p class="card-sub">Enter your route, travel date, and budget. We'll notify you when the price drops.</p>

        <?php if ($success_msg): ?>
        <div class="alert-success"><?= $success_msg ?></div>
        <?php elseif ($error_msg): ?>
        <div class="alert-error"><?= htmlspecialchars($error_msg) ?></div>
        <?php endif; ?>

        <form method="POST" action="price-tracker.php" class="tracker-form">
            <div class="tform-row">
                <div class="tform-group">
                    <label>From</label>
                    <select name="from" required>
                        <option value="">Select city</option>
                        <?php foreach ($airports as $code => $ap): ?>
                        <option value="<?= $code ?>" <?= $code === $from ? 'selected' : '' ?>>
                            <?= htmlspecialchars($ap['city']) ?> (<?= $code ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="tform-group">
                    <label>To</label>
                    <select name="to" required>
                        <option value="">Select city</option>
                        <?php foreach ($airports as $code => $ap): ?>
                        <option value="<?= $code ?>" <?= $code === $to ? 'selected' : '' ?>>
                            <?= htmlspecialchars($ap['city']) ?> (<?= $code ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="tform-row">
                <div class="tform-group">
                    <label>Travel Date</label>
                    <input type="date" name="date" required
                           min="<?= date('Y-m-d') ?>"
                           value="<?= date('Y-m-d', strtotime('+14 days')) ?>">
                </div>
                <div class="tform-group">
                    <label>Target Price (MYR per person)</label>
                    <div class="price-input-wrap">
                        <span class="currency-prefix">RM</span>
                        <input type="number" name="target" required min="50" max="5000"
                               value="<?= htmlspecialchars($target) ?>" placeholder="e.g. 200">
                    </div>
                </div>
            </div>
            <div class="tform-row">
                <div class="tform-group tform-group--wide">
                    <label>Email Address</label>
                    <input type="email" name="email" required placeholder="your@email.com">
                </div>
            </div>
            <button type="submit" class="tracker-submit-btn">
                🔔 Activate Price Alert
            </button>
            <p class="form-note">* This is a demo. No actual emails are sent. For a real implementation, integrate with your SMTP server.</p>
        </form>
    </div>

    <!-- How It Works -->
    <div class="how-it-works">
        <h2>How Price Tracking Works</h2>
        <div class="steps">
            <div class="step">
                <div class="step-num">1</div>
                <div class="step-content">
                    <h4>Set Your Route & Budget</h4>
                    <p>Choose your departure and destination cities, travel date, and the maximum price you want to pay.</p>
                </div>
            </div>
            <div class="step">
                <div class="step-num">2</div>
                <div class="step-content">
                    <h4>We Monitor 24/7</h4>
                    <p>Our system continuously checks prices across all Malaysian airlines — AirAsia, Malaysia Airlines, Batik Air and more.</p>
                </div>
            </div>
            <div class="step">
                <div class="step-num">3</div>
                <div class="step-content">
                    <h4>Get Alerted Instantly</h4>
                    <p>The moment a fare drops to or below your target price, you receive an email with a direct link to book.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Live Price Monitor -->
    <div class="live-monitor">
        <h2 class="section-title">📈 Live Price Monitor — Popular Routes</h2>
        <p class="section-sub">Current economy class prices vs 30-day range (1 adult, departing next week)</p>
        <div class="monitor-grid">
            <?php foreach ($trend_data as $td):
                $pct = min(100, round(($td['current'] - $td['low']) / max(1, $td['high'] - $td['low']) * 100));
                $status_cls = $pct <= 20 ? 'status--low' : ($pct <= 60 ? 'status--mid' : 'status--high');
                $status_txt = $pct <= 20 ? '🟢 Great Deal' : ($pct <= 60 ? '🟡 Average' : '🔴 Expensive');
            ?>
            <div class="monitor-card">
                <div class="monitor-route"><?= htmlspecialchars($td['route']) ?></div>
                <div class="monitor-price-row">
                    <div class="monitor-price">RM <?= number_format($td['current']) ?></div>
                    <div class="monitor-status <?= $status_cls ?>"><?= $status_txt ?></div>
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
                    <a href="search.php?from=<?=$td['from']?>&to=<?=$td['to']?>&date=<?=date('Y-m-d',strtotime('+7 days'))?>&pax=1" class="monitor-search-btn">Search Flights</a>
                    <a href="price-tracker.php?from=<?=$td['from']?>&to=<?=$td['to']?>&target=<?=$td['current']?>" class="monitor-alert-btn">🔔 Alert Me</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Tips Section -->
    <div class="tips-section">
        <h2>💡 Tips to Get the Cheapest Flights in Malaysia</h2>
        <div class="tips-grid">
            <div class="tip-card">
                <div class="tip-icon">📅</div>
                <h4>Book 3–8 Weeks Ahead</h4>
                <p>Sweet spot for domestic Malaysian flights. Too early or too late both cost more.</p>
            </div>
            <div class="tip-card">
                <div class="tip-icon">🕐</div>
                <h4>Fly Off-Peak Hours</h4>
                <p>Early morning (6–8am) and late night (10pm+) flights are often 15–25% cheaper.</p>
            </div>
            <div class="tip-card">
                <div class="tip-icon">📆</div>
                <h4>Avoid Weekends & Holidays</h4>
                <p>Fly on Tuesdays or Wednesdays for the best deals. Public holidays spike prices dramatically.</p>
            </div>
            <div class="tip-card">
                <div class="tip-icon">🔔</div>
                <h4>Use Price Alerts</h4>
                <p>Airlines regularly drop prices. Set an alert and book when the fare hits your budget.</p>
            </div>
            <div class="tip-card">
                <div class="tip-icon">✈️</div>
                <h4>Compare All Airlines</h4>
                <p>AirAsia is not always cheapest! Check Malaysia Airlines and Batik Air for deals.</p>
            </div>
            <div class="tip-card">
                <div class="tip-icon">🎒</div>
                <h4>Go Carry-On Only</h4>
                <p>Save RM 30–80 on short routes by packing light and skipping checked baggage.</p>
            </div>
        </div>
    </div>
</div>
</div>

<?php require_once 'includes/footer.php'; ?>
