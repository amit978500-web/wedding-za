# Changelog

All notable Wedding Za changes are recorded here.

## [1.1.0] - 2026-09-25

### Added

- Protected admin operations workspace.
- Vendor approval, featuring and plan controls.
- Free / Featured / Pro vendor plan foundation.
- Lead status management and internal notes.
- User account status management.
- Journal CMS connected to public articles.
- Secure media upload service.
- Admin media library.
- Vendor portfolio uploads.
- Approved MySQL vendors in the public marketplace.
- Audit logging.
- Login throttling.
- Stronger password policy.
- CLI-only admin account creator.
- Protected health endpoint.
- Database backup command.
- Operations runbook.
- Configurable analytics.
- Organization, WebSite, Article and LocalBusiness structured data.
- CMS articles and approved vendors in the dynamic sitemap.
- MySQL schema validation in CI.
- HTTP smoke testing.
- Chromium desktop and mobile browser QA.

### Changed

- Re-aligned `develop` with the tested `main` release before hardening work.
- Strengthened browser security headers and CSP.
- Blocked admin routes from crawler indexing.
- Extended vendor records for uploaded galleries and commercial plan state.

### Notes

- Real licensed production photography still needs to be supplied and uploaded.
- Payment gateway processing is not connected; vendor plan and billing-state operations are prepared for future payment integration.
- Transactional email remains a separate production phase.

## [1.0.0] - 2026-09-22

### Added

- Full multi-event positioning across Wedding Za.
- Premium homepage planning dock.
- Vendor Discovery V2.
- Event-aware vendor filtering.
- Vendor Profile V2.
- Dynamic event landing pages.
- Dynamic city landing pages.
- Connected Planning Studio.
- Connected Shortlist workspace.
- Host account workspace.
- Vendor account flow.
- Vendor business onboarding.
- Vendor business dashboard.
- Optional MySQL persistence.
- Password-hashed persistent accounts.
- CSRF-protected account actions.
- Database-backed lead and enquiry storage.
- Persistent host planning workspace sync.
- CSV fallback for local development.
- Dynamic sitemap.
- Canonical and Open Graph metadata.
- Functional standalone e-invite export.
- GitHub Actions syntax checks.
- Repository-wide human-readable code style rules.

### Changed

- Reworked the codebase into simple line-by-line formatting.
- Removed fake vendor dashboard KPI data from the production path.
- Replaced placeholder account messaging with real persistence status.
- Updated asset cache version to 4.0.0.
- Strengthened Apache security headers and config protection.

### Notes

- Demo images and advanced animation libraries are still loaded remotely.
- Production email delivery, image uploads and payment processing are not included in 1.0.0.
