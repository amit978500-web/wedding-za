# Wedding Za — All Celebrations Edition 3.2

Wedding Za is now positioned as a premium Indian **celebration and event discovery platform**, not a wedding-only directory.

It supports discovery for:
- Weddings
- Engagements / roka
- Birthdays
- Anniversaries
- Baby showers
- Corporate events
- Festive functions
- Private parties

Weddings remain an important category, but the homepage, vendor finder, vendor directory, planning tools, editorial system and business onboarding are now designed around multiple event types.

## Start on Windows
1. Extract the ZIP completely.
2. Double-click `START-WEDDING-ZA.bat`.
3. Keep the black terminal window open.
4. The browser opens automatically, normally at `http://127.0.0.1:8088`.

The launcher detects PHP in PATH and common XAMPP, Laragon and WAMP installations.

## Manual start
```bash
php -S 127.0.0.1:8088
```
Then open `http://127.0.0.1:8088`.

## Main UX changes
- New multi-event cinematic homepage hero.
- Hero message: **“Whatever the occasion. Make it unforgettable.”**
- Indian event imagery rather than wedding-only hero imagery.
- Event-type shortcuts directly in the hero.
- Dedicated event-type discovery chapter for eight celebration types.
- Vendor finder now asks: event type → vendor category → city.
- Vendor directory has a working event-type filter.
- Vendor profiles contain multi-event suitability data.
- Broader vendor categories: Photography & Films, Beauty & Styling, Fashion & Styling, Entertainment, etc.
- Real Celebrations now includes Wedding, Engagement, Corporate and Birthday examples.
- Planner/checklist and budget language updated for general events.
- Inspiration and Journal content broadened beyond weddings.
- Vendor onboarding and contact flows updated for event businesses and hosts.

## Animation
Advanced motion uses CDN-hosted:
- GSAP 3.15.0
- ScrollTrigger 3.15.0
- Lenis 1.3.26

The destination rail remains non-pinned to avoid the stuck-scroll feeling fixed in the previous build. Reduced-motion accessibility is retained.

## Main files
- `index.php` — all-celebrations cinematic homepage
- `vendors.php` — event-aware vendor directory
- `vendor.php` — dynamic vendor profile
- `city.php` — dynamic city guide
- `inspiration.php` — inspiration board
- `real-weddings.php` — Real Celebrations archive (legacy filename retained for compatibility)
- `wedding-story.php` — dynamic celebration story (legacy filename retained)
- `blog.php` / `article.php` — journal
- `planner.php` — event checklist + budget tracker
- `invites.php` — digital invitation builder
- `shortlist.php` — saved vendors
- `register-vendor.php` — event-business onboarding
- `vendor-dashboard.php` — business dashboard concept
- `assets/data/site.json` — event types, vendors, stories and editorial demo data
- `assets/css/vision.css` — editorial design system
- `assets/js/app.js` — filters, shortlist, planner, forms and core interactions
- `assets/js/vision.js` — cinematic motion layer
- `api/lead.php` — local demo lead endpoint

## Production note
This is a strong PHP front-end/product foundation. Before public launch, connect production authentication, a database/CMS, CRM/email delivery, uploads, moderation, analytics, security controls and backups.

---

## Professional Git workflow

This project uses a stable `main` branch and a daily-work `develop` branch.

Read `GIT-START-HERE.txt` and `docs/DEVELOPMENT-WORKFLOW.md` before contributing. Runtime lead data and secret/environment files are excluded by `.gitignore`.
