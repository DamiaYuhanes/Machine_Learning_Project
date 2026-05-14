<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title ?? 'Mia Flight n Go — Global Flight Price Tracker') ?></title>
    <meta name="description" content="Mia Flight n Go — Compare international flight prices. Track deals from 80+ airports worldwide. Set price alerts and get notified instantly.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $base_path ?? '' ?>assets/css/style.css">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>✈️</text></svg>">
</head>
<body>
<header class="site-header">
    <div class="container header-inner">
        <a href="<?= $base_path ?? '' ?>index.php" class="logo">
            <span class="logo-icon">✈</span>
            <span class="logo-text">Mia Flight n <span class="logo-accent">Go</span></span>
        </a>
        <nav class="header-nav">
            <a href="<?= $base_path ?? '' ?>index.php"         class="nav-link <?= ($active_page??'')==='home'    ?'active':'' ?>">Search Flights</a>
            <a href="<?= $base_path ?? '' ?>price-tracker.php" class="nav-link <?= ($active_page??'')==='tracker' ?'active':'' ?>">Price Tracker</a>
            <a href="<?= $base_path ?? '' ?>watchlist.php"     class="nav-link <?= ($active_page??'')==='watchlist'?'active':'' ?>">My Watchlist</a>
        </nav>
        <div class="header-actions">
            <!-- Notification Bell -->
            <button class="notif-bell" id="notif-bell" title="Price Alerts" aria-label="Price alerts">
                <span class="bell-icon">🔔</span>
                <span class="bell-badge" id="bell-badge" style="display:none">0</span>
            </button>
            <div class="currency-wrap">
                <button class="currency-btn" id="currency-toggle">MYR ▾</button>
                <div class="currency-dropdown" id="currency-dropdown" style="display:none">
                    <div class="currency-opt active" data-code="MYR" data-rate="1">MYR — Malaysian Ringgit</div>
                    <div class="currency-opt" data-code="USD" data-rate="0.213">USD — US Dollar</div>
                    <div class="currency-opt" data-code="SGD" data-rate="0.286">SGD — Singapore Dollar</div>
                    <div class="currency-opt" data-code="EUR" data-rate="0.198">EUR — Euro</div>
                    <div class="currency-opt" data-code="GBP" data-rate="0.170">GBP — British Pound</div>
                    <div class="currency-opt" data-code="AUD" data-rate="0.327">AUD — Australian Dollar</div>
                    <div class="currency-opt" data-code="JPY" data-rate="32.1">JPY — Japanese Yen</div>
                    <div class="currency-opt" data-code="IDR" data-rate="3390">IDR — Indonesian Rupiah</div>
                </div>
            </div>
        </div>
    </div>
</header>

<!-- Notification Dropdown -->
<div class="notif-dropdown" id="notif-dropdown" style="display:none">
    <div class="notif-header">
        <span class="notif-title">🔔 Price Alerts</span>
        <button class="notif-close" id="notif-close">✕</button>
    </div>
    <div class="notif-body" id="notif-body">
        <p class="notif-empty">No alerts yet. Add flights to your watchlist!</p>
    </div>
    <div class="notif-footer">
        <a href="<?= $base_path ?? '' ?>watchlist.php" class="notif-manage">Manage Watchlist →</a>
    </div>
</div>
<div class="notif-overlay" id="notif-overlay" style="display:none"></div>
