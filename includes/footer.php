<section class="vision-finale">
    <div class="container vision-finale-grid">
        <div>
            <span>WEDDING ZA / 2026</span>

            <h2>
                Make the search
                <br>
                <em>feel like part of it.</em>
            </h2>
        </div>

        <div>
            <p>
                Find the right place, people and ideas.
                Save what feels right.
                Keep every important event decision in one place.
            </p>

            <a href="vendors.php">
                Start with vendors
                <span>↗</span>
            </a>
        </div>
    </div>
</section>

<footer class="vision-footer">
    <div class="container vision-footer-top">
        <a
            class="vision-footer-brand"
            href="index.php"
        >
            Wedding Za
        </a>

        <p>
            Design-led event discovery
            <br>
            for modern Indian celebrations.
        </p>
    </div>

    <div class="container vision-footer-links">
        <div>
            <small>DISCOVER</small>

            <a href="vendors.php">
                Vendors
            </a>

            <a href="real-weddings.php">
                Real celebrations
            </a>

            <a href="inspiration.php">
                Ideas
            </a>

            <a href="blog.php">
                Journal
            </a>
        </div>

        <div>
            <small>PLAN</small>

            <a href="planner.php">
                Planning studio
            </a>

            <a href="shortlist.php">
                Shortlist
            </a>

            <a href="invites.php">
                E-invites
            </a>

            <a href="submit-wedding.php">
                Submit a celebration
            </a>
        </div>

        <div>
            <small>BUSINESS</small>

            <a href="register-vendor.php">
                List your business
            </a>

            <a href="vendor-dashboard.php">
                Vendor dashboard
            </a>

            <a href="contact.php">
                Contact
            </a>

            <a href="about.php">
                About Wedding Za
            </a>
        </div>

        <div>
            <small>LEGAL</small>

            <a href="terms.php">
                Terms
            </a>

            <a href="privacy.php">
                Privacy
            </a>

            <a href="cancellation.php">
                Cancellation
            </a>

            <a href="careers.php">
                Careers
            </a>
        </div>
    </div>

    <div class="container vision-footer-bottom">
        <span>
            © <?= date('Y') ?> Wedding Za
        </span>

        <span>
            India · Jaipur · Udaipur · Goa · Delhi · Mumbai
        </span>
    </div>
</footer>

<div
    class="toast"
    id="toast"
    role="status"
    aria-live="polite"
></div>

<script>
window.WZ_BOOT = <?= json_encode([
    'page' => $pageKey,
    'loggedIn' => wz_is_logged_in(),
    'role' => wz_role(),
    'csrf' => wz_csrf_token(),
    'databaseReady' => wz_database_ready(),
], JSON_UNESCAPED_SLASHES) ?>;
</script>

<script src="https://cdn.jsdelivr.net/npm/gsap@3.15.0/dist/gsap.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/gsap@3.15.0/dist/ScrollTrigger.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/lenis@1.3.26/dist/lenis.min.js"></script>
<script src="assets/js/app.js?v=4.0.0"></script>
<script src="assets/js/vision.js?v=4.0.1"></script>
</body>
</html>
