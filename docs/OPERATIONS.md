# Wedding Za Operations

## Admin access

Create or update an admin account from the command line:

```bash
php scripts/create-admin.php "Admin Name" admin@example.com "StrongPassword123"
```

Then open:

```text
/admin/login.php
```

The admin workspace manages:

- vendor approvals and featured status
- leads and internal notes
- user account status
- Journal CMS content
- media uploads
- audit history

## Vendor moderation

New database vendor profiles begin as `pending`.

An admin must approve the profile before it appears in the public marketplace.

Featured vendors are sorted ahead of non-featured database vendors.

## Media

Accepted upload formats:

- JPG
- PNG
- WebP

Limits:

- 8 MB maximum
- minimum 300 × 300 pixels
- MIME type is checked with Fileinfo
- uploaded names are replaced with random names
- PHP execution is denied in the upload directory

Vendor uploads are appended to the vendor gallery.

Use owned or properly licensed images only.

## Health monitoring

Set a strong random value in:

```php
'operations' => [
    'health_token' => 'replace-with-a-long-random-token',
],
```

Check:

```bash
curl \
  -H "X-WZ-Health-Token: YOUR_TOKEN" \
  https://your-domain.com/api/health.php
```

A healthy installation returns HTTP 200 with `"ok": true`.

A database outage returns HTTP 503.

## Database backups

Run:

```bash
php scripts/backup-database.php
```

The server needs `mysqldump` available.

Backups are written to:

```text
storage/backups/
```

That directory is excluded from Git and must be protected by your server backup policy.

Recommended schedule:

- daily database backup
- keep at least 7 daily backups
- keep at least 4 weekly backups
- keep an off-server copy
- periodically test restore procedures

## Security operations

Review regularly:

- `admin/audit.php`
- suspended accounts
- pending vendors
- repeated login failures
- new leads
- server error logs
- upload directory permissions

Login throttling currently blocks an email + IP combination after five failed attempts within 15 minutes.

## Analytics

Analytics code loads only when `analytics.measurement_id` is configured.

Leave it blank until your privacy/cookie approach is approved for the jurisdictions where the site operates.

## Release QA

GitHub Actions verifies:

- PHP syntax
- JavaScript syntax
- JSON validity
- MySQL schema import
- public HTTP routes
- admin login route
- protected health endpoint

Visual and device QA should still be performed on the real staging/production environment before a public campaign.
