<footer class="site-footer">
    <div class="container footer-inner">
        <div class="footer-brand">
            <span class="logo-icon">✈</span>
            <span class="logo-text">Mia Flight n <span class="logo-accent">Go</span></span>
            <p class="footer-tagline">Global flight price tracker — 80+ airports worldwide</p>
        </div>
        <div class="footer-links">
            <div class="footer-col">
                <h4>Airlines</h4>
                <ul>
                    <li>AirAsia</li>
                    <li>Malaysia Airlines</li>
                    <li>Batik Air Malaysia</li>
                    <li>Firefly</li>
                    <li>AirAsia X</li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Popular International</h4>
                <ul>
                    <li><a href="search.php?from=KUL&to=SIN&date=<?= date('Y-m-d', strtotime('+7 days')) ?>&pax=1">KL → Singapore</a></li>
                    <li><a href="search.php?from=KUL&to=LHR&date=<?= date('Y-m-d', strtotime('+7 days')) ?>&pax=1">KL → London</a></li>
                    <li><a href="search.php?from=KUL&to=DXB&date=<?= date('Y-m-d', strtotime('+7 days')) ?>&pax=1">KL → Dubai</a></li>
                    <li><a href="search.php?from=KUL&to=NRT&date=<?= date('Y-m-d', strtotime('+7 days')) ?>&pax=1">KL → Tokyo</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Features</h4>
                <ul>
                    <li>Live Price Comparison</li>
                    <li>Price History Charts</li>
                    <li>Price Drop Alerts</li>
                    <li>Flexible Date Search</li>
                </ul>
            </div>
        </div>
    </div>
    <div class="footer-bottom">
        <div class="container">
            <p>© <?= date('Y') ?> Mia Flight n Go · Prices are indicative and may vary. Not an official booking platform.</p>
        </div>
    </div>
</footer>
<script src="<?= $base_path ?? '' ?>assets/js/main.js"></script>
</body>
</html>
