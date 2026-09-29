# Changelog

All notable Wedding Za changes are recorded here.

## [1.2.0] - 2026-09-29

### Added

- Connected Admin CRM portal.
- Connected Customer CRM portal.
- Connected Vendor CRM portal.
- Connected Venue CRM portal.
- Shared CRM enquiry pipeline.
- CRM enquiry detail with notes and follow-up tasks.
- Opportunity value and next-follow-up tracking.
- Won-enquiry to booking conversion.
- Booking status and payment-state management.
- Customer, Vendor and Venue CRM messaging.
- Role-specific CRM profiles.
- Venue capacity, rooms, type, locality and address management.
- Venue availability calendar.
- Confirmed venue booking to availability synchronization.
- Vendor and venue CRM portfolio uploads.
- Admin customer CRM overview.
- Admin venue moderation.
- Admin CRM enquiry assignment.
- Admin booking operations.
- Admin task assignment.
- Admin message oversight.
- Public enquiry to CRM synchronization.
- Approved venue profiles in the public marketplace.
- Historical lead-to-CRM migration command.
- CRM integration QA.
- CRM browser and HTTP route coverage.

### Changed

- Customer accounts now route into Customer CRM.
- Vendor accounts now route into Vendor CRM.
- Venue accounts are now a first-class account role.
- Legacy account and vendor dashboard URLs redirect to the new CRM portals.
- Website account menu exposes Customer, Vendor and Venue CRM entry points.
- Admin dashboard now prioritizes CRM operations.

### Database

Apply:

```text
database/migrations/004-full-crm.sql
```

for existing Wedding Za databases.

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
