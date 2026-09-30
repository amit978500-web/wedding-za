# Wedding Za CRM

Wedding Za uses one shared CRM data model with four role-specific portals.

## Portals

### Admin CRM

```text
/admin/
```

Admin can manage:

- CRM enquiries
- business assignment
- booking status
- customers
- venues
- vendors
- tasks
- messages
- raw leads
- users
- CMS content
- media
- audit logs

### Customer CRM

```text
/crm/customer/
```

Customers can manage:

- enquiries
- bookings
- planning links
- shortlist
- tasks
- shared notes
- messages
- customer profile

### Vendor CRM

```text
/crm/vendor/
```

Vendors can manage:

- sales pipeline
- enquiry stage
- opportunity value
- next follow-up
- notes
- tasks
- bookings
- payment status
- customer messages
- business profile
- portfolio media

### Venue CRM

```text
/crm/venue/
```

Venue CRM is organized around venue operations:

```text
Dashboard

Leads
├── All Leads
├── New Leads
├── Follow-ups
├── Site Visits
└── Lost Leads

Functions
├── All Functions
├── Upcoming
└── Calendar

Bookings

Payments

Reports
```

Venue CRM includes:

- venue-specific profile fields
- lead stage tracking
- follow-up scheduling
- site-visit scheduling and status
- function/event schedule
- upcoming function view
- function calendar
- booking confirmation and value
- payment ledger
- payment method and reference tracking
- received/refunded payment status
- outstanding balance calculation
- lead conversion reporting
- booking/function reporting
- venue capacity
- rooms
- venue type
- address/locality
- confirmed booking → booked availability sync

## Sales pipeline

```text
New
→ Qualified
→ Proposal
→ Negotiation
→ Won / Lost
```

Moving an enquiry to `Won` automatically creates a tentative booking if one does not already exist.

## Public enquiry integration

New public vendor or venue enquiries are stored in `leads` and synchronized into `crm_enquiries`.

When the public business name matches an approved database profile, the enquiry is automatically assigned to that business account.

Admin can assign or reassign CRM enquiries manually.

## Historical leads

After installing the CRM migration, run:

```bash
php scripts/sync-crm-leads.php
```

This converts historical vendor/venue leads into CRM enquiries without duplicating already-synchronized records.

## Database migration

Existing Wedding Za installations should apply the CRM migrations in order:

```text
database/migrations/004-full-crm.sql
database/migrations/005-venue-crm-operations.sql
```

If migration 004 is already installed, apply only migration 005.

The migration adds:

- venue user role
- customer profiles
- venue profiles
- CRM enquiries
- bookings
- tasks
- notes
- messages
- venue availability

## QA

The CRM integration test creates temporary Customer, Vendor and Venue accounts and verifies:

- lead → CRM enquiry sync
- vendor assignment
- booking creation
- customer booking visibility
- tasks
- messages
- public approved vendors
- public approved venues

Run:

```bash
php scripts/qa-crm.php
```


## Venue CRM operations QA

Run:

```bash
php scripts/qa-venue-crm.php
```

This validates Venue lead assignment, New Leads, Follow-ups, Site Visits, Functions, Payments and Reports.
