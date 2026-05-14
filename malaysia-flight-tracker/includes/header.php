<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title ?? 'TerbangMY — Malaysia Flight Price Tracker') ?></title>
    <meta name="description" content="Find the cheapest flights in Malaysia. Compare prices from AirAsia, Malaysia Airlines, Batik Air and more. Track prices and get alerts.">
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
            <span class="logo-text">Terbang<span class="logo-accent">MY</span></span>
        </a>
        <nav class="header-nav">
            <a href="<?= $base_path ?? '' ?>index.php" class="nav-link <?= ($active_page ?? '') === 'home' ? 'active' : '' ?>">Flights</a>
            <a href="<?= $base_path ?? '' ?>price-tracker.php" class="nav-link <?= ($active_page ?? '') === 'tracker' ? 'active' : '' ?>">Price Tracker</a>
        </nav>
        <div class="header-cta">
            <span class="currency-badge">MYR</span>
        </div>
    </div>
</header>
