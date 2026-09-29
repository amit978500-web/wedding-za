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

Venues receive the Vendor CRM capabilities plus:

- venue-specific profile fields
- capacity
- rooms
- venue type
- address/locality
- availability calendar
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

Existing Wedding Za installations should apply:

```text
database/migrations/004-full-crm.sql
```

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
