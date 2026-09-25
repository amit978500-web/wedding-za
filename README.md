# Wedding Za — Production-Ready Marketplace Foundation

Wedding Za is a premium Indian celebration discovery and planning platform for:

- Weddings
- Engagements and roka
- Birthdays
- Anniversaries
- Baby showers
- Corporate events
- Festive functions
- Private parties

The codebase is intentionally written in a simple, readable, line-by-line style.

## Product areas

The website includes:

- Multi-event cinematic homepage
- Dynamic event landing pages
- Dynamic city landing pages
- Vendor discovery and filtering
- Vendor profile pages
- Shortlist
- Event brief
- Planning checklist
- Budget tracker
- Persistent planning workspace for signed-in hosts
- Host account workspace
- Vendor account and business onboarding
- Vendor business dashboard
- Database-backed enquiries
- Real celebrations
- Inspiration board
- Journal and articles
- E-invite builder with standalone HTML export
- Contact, careers and legal pages
- Dynamic sitemap and SEO metadata
- Protected admin operations workspace
- Vendor moderation and listing plans
- Lead operations and internal notes
- Journal CMS
- Secure media library and vendor gallery uploads
- Audit logging
- Login throttling and stronger session security
- Protected health monitoring
- Database backup command
- Desktop and mobile Chromium QA

## Local start

### Windows

1. Clone or download the repository.
2. Double-click `START-WEDDING-ZA.bat`.
3. Keep the terminal window open.
4. The browser normally opens at `http://127.0.0.1:8088`.

The launcher checks common PHP installations including XAMPP, Laragon and WAMP.

### Manual PHP start

```bash
php -S 127.0.0.1:8088
```

Then open:

```text
http://127.0.0.1:8088
```

## Database setup

The public website works without MySQL.

Without MySQL:

- Shortlist is stored in the browser.
- Planner data is stored in the browser.
- Account access runs in local session mode.
- Lead forms fall back to `storage/leads.csv`.

With MySQL configured:

- Users are persistent.
- Passwords use PHP `password_hash()`.
- Host planning workspaces sync to the database.
- Vendor business profiles are persistent.
- Vendor enquiries are stored in the database.
- Vendor dashboard reads real business data.

Setup:

1. Create a MySQL database.
2. Import `database/schema.sql`.
3. Copy `config.example.php` to `config.local.php`.
4. Fill in the database credentials.
5. Set `app_url` to the production site URL.

See `database/README.md` for the short setup guide.

Never commit `config.local.php`.

## Account routes

Host:

```text
register.php?role=host
login.php?role=host
account.php
```

Vendor:

```text
register.php?role=vendor
login.php?role=vendor
register-vendor.php
vendor-dashboard.php
```

## Important files

```text
index.php
event.php
city.php
vendors.php
vendor.php
planner.php
shortlist.php
account.php
register.php
register-vendor.php
vendor-dashboard.php
invites.php
api/lead.php
api/workspace.php
includes/auth.php
includes/database.php
assets/data/site.json
assets/js/app.js
assets/js/vision.js
assets/css/app.css
assets/css/vision.css
database/schema.sql
```

## Code style

Read:

```text
docs/CODE-STYLE.md
```

Project rules include:

- one logical statement per line
- readable PHP control flow
- visibly nested HTML/PHP templates
- expanded JavaScript functions
- one CSS declaration per line
- no minified-looking source code

The repository also includes `.editorconfig`.

## Quality checks

GitHub Actions validates:

- every PHP file with `php -l`
- JavaScript syntax
- JSON validity
- clean MySQL 8 schema import
- live PHP HTTP smoke tests
- desktop Chromium routes and interactions
- mobile Chromium routes and interactions

Workflow:

```text
.github/workflows/code-quality.yml
```

## Motion dependencies

Advanced motion uses CDN-hosted:

- GSAP 3.15.0
- ScrollTrigger 3.15.0
- Lenis 1.3.26

The site still retains its non-pinned city rail behavior and a reduced-motion fallback.

## Production deployment

For a normal PHP shared host:

1. Upload the repository contents to the web root.
2. Use PHP 8.1 or newer.
3. Create/import the MySQL database.
4. Add `config.local.php`.
5. Ensure `storage/` is writable if CSV fallback is required.
6. Confirm Apache reads `.htaccess`.
7. Open `sitemap.php` and confirm production URLs.
8. Test host registration and login.
9. Test vendor registration and dashboard.
10. Test enquiry submission.
11. Test planner sync while logged in.
12. Enable HTTPS.

## Current external dependencies

The design still uses remote:

- Google Fonts
- Unsplash demo imagery
- GSAP / ScrollTrigger / Lenis CDNs

For a completely self-hosted production package, replace remote demo imagery with licensed local media and choose a compliant font delivery strategy.

## Git workflow

- `main` — stable releases
- `develop` — integration branch
- `feature/*` — focused feature work
- `release/*` — final release preparation

Read:

```text
docs/DEVELOPMENT-WORKFLOW.md
```


## Admin operations

Create an admin account from the command line:

```bash
php scripts/create-admin.php "Admin Name" admin@example.com "StrongPassword123"
```

Then open:

```text
/admin/login.php
```

See `docs/OPERATIONS.md` for vendor moderation, media uploads, backups, health checks and release operations.

## Media uploads

Production media uploads support JPG, PNG and WebP.

Uploaded media is:

- MIME-validated
- size-limited
- dimension-validated
- randomly renamed
- recorded in MySQL
- blocked from PHP execution

Use only owned or properly licensed media.

## Analytics

Set `analytics.measurement_id` in `config.local.php` when your production analytics/privacy setup is ready.

Analytics remains disabled when the value is blank.
