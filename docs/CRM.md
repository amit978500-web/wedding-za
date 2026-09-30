# Wedding Za CRM

Wedding Za uses one shared CRM data model with four role-specific portals.

## Portals

### Admin CRM

```text
/admin/
```

Admin CRM is organized around platform operations:

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
├── Payments
├── Invoices
├── Refunds
└── Commission

Customers

Venues
├── All Venues
├── Active
└── Inactive

Vendors
├── All Vendors
└── Active

Reports

Website
├── Cities
├── Categories
├── Venues
└── Blogs

Team
```

Admin CRM includes:

- cross-platform lead assignment
- follow-up scheduling
- site-visit tracking
- function/event schedule
- booking operations
- payment transaction ledger
- invoice register
- refund view
- Wedding Za commission tracking
- customer directory
- venue approval and active/inactive views
- vendor approval and active view
- platform performance reports
- website discovery inventory
- blog CMS
- admin team profiles
- media library and audit log utility access

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
database/migrations/006-admin-crm-operations.sql
```

Apply only the migrations that are not already installed.

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


## Admin CRM operations QA

Run:

```bash
php scripts/qa-admin-crm.php
```

This validates Admin Leads, Follow-ups, Site Visits, Functions, Payments, Invoices, Commission, Reports and Team data.
