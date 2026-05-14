<footer class="site-footer">
    <div class="container footer-inner">
        <div class="footer-brand">
            <span class="logo-icon">✈</span>
            <span class="logo-text">Flight<span class="logo-accent">Go</span></span>
            <p class="footer-tagline">Malaysia's smartest flight price tracker</p>
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
                <h4>Popular Routes</h4>
                <ul>
                    <li><a href="search.php?from=KUL&to=BKI&date=<?= date('Y-m-d', strtotime('+7 days')) ?>&pax=1">KL → Kota Kinabalu</a></li>
                    <li><a href="search.php?from=KUL&to=PEN&date=<?= date('Y-m-d', strtotime('+7 days')) ?>&pax=1">KL → Penang</a></li>
                    <li><a href="search.php?from=KUL&to=KCH&date=<?= date('Y-m-d', strtotime('+7 days')) ?>&pax=1">KL → Kuching</a></li>
                    <li><a href="search.php?from=KUL&to=LGK&date=<?= date('Y-m-d', strtotime('+7 days')) ?>&pax=1">KL → Langkawi</a></li>
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
            <p>© <?= date('Y') ?> FlightGo · Prices are indicative and may vary. Not an official booking platform.</p>
        </div>
    </div>
</footer>
<script src="<?= $base_path ?? '' ?>assets/js/main.js"></script>
</body>
</html>
