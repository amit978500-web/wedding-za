# Wedding Za Go-Live Runbook

This is the final production gate for Wedding Za.

## 1. Prepare production configuration

Copy:

```text
config.example.php
```

to:

```text
config.local.php
```

and fill the real production values.

Minimum launch values:

- final HTTPS `app_url`
- production MySQL host/database/user/password
- strong health token
- Razorpay live key ID
- Razorpay live key secret
- Razorpay live webhook secret
- contact email
- contact phone if used
- default Open Graph image
- notification delivery webhook if external email/push is enabled

Never commit `config.local.php`.

## 2. Upgrade the existing database

Run:

```bash
php scripts/upgrade-existing-database.php
```

The upgrade is additive. It does not reset customer, vendor, venue, booking or CRM data.

## 3. Back up before cutover

Run:

```bash
php scripts/backup-database.php
```

Keep a copy outside the web server.

## 4. Run the production gate

Run:

```bash
php scripts/go-live-check.php
```

The checker reports:

- `PASS` — ready
- `WARNING` — review before launch
- `BLOCKER` — must be fixed before launch

Do not cut over DNS while any BLOCKER remains.

The checker validates:

- PHP version and required extensions
- `config.local.php`
- final HTTPS app URL
- Apache security file
- PHP error-display safety
- health-check token
- MySQL configuration and connection
- required database migrations
- uploads/storage permissions
- backup directory
- Razorpay live credentials
- SEO/contact values
- optional email/push provider configuration
- image fallback asset

## 5. Production PHP settings

Recommended:

```ini
display_errors = Off
log_errors = On
expose_php = Off
session.cookie_httponly = On
session.cookie_secure = On
session.cookie_samesite = Lax
```

Use the hosting control panel or production `php.ini` / `.user.ini` according to the host.

## 6. HTTPS and domain

Before public launch:

- SSL certificate must be valid
- `app_url` must use `https://`
- HTTP should redirect to HTTPS at the hosting layer
- confirm the domain loads without certificate warnings
- confirm `.htaccess` is enabled

## 7. Razorpay

Use only live credentials for public launch.

Webhook endpoint:

```text
https://YOUR-DOMAIN/api/razorpay-webhook.php
```

Complete one controlled real-payment test before announcing the site publicly.

## 8. Email / push

In-app notifications work without an external provider.

If Email or Push is enabled, configure the HTTPS notification delivery webhook described in:

```text
docs/NOTIFICATIONS.md
```

Test:

- enquiry alert
- message alert
- quote alert
- booking alert
- site-visit alert
- payment alert

## 9. Final browser smoke test

On the real production domain test:

- homepage
- venue and vendor search
- Customer registration/login
- Venue registration/login
- Vendor registration/login
- Admin login
- enquiry creation
- CRM message
- quote
- booking
- payment
- notification
- profile/member card
- media upload
- mobile menu

Use at least one iPhone-size viewport and one Android-size viewport.

## 10. Health monitoring

Configure a strong health token, then check:

```bash
curl -H "X-WZ-Health-Token: YOUR_TOKEN" https://YOUR-DOMAIN/api/health.php
```

Expected healthy response: HTTP 200 and `"ok": true`.

## 11. Launch

Only after:

```bash
php scripts/go-live-check.php
```

returns:

```text
READY FOR LIVE
```

perform the DNS/domain cutover.

After launch, immediately re-test login, enquiry, payment webhook, notification delivery and admin access.
