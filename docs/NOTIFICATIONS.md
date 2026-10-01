# Wedding Za Notifications

Wedding Za always stores CRM notifications in-app. Email and push delivery are optional production channels.

## What is covered

The current notification pipeline supports:

- new marketplace enquiries
- CRM messages
- quotes sent to customers
- customer quote decisions
- booking creation
- venue site-visit updates
- venue payment updates
- marketplace review submissions

## Database upgrade

Existing installations should run:

```bash
php scripts/upgrade-existing-database.php
```

Migration 008 creates:

- `notification_preferences`
- `notification_delivery_log`

The upgrade is additive and does not reset or delete existing CRM data.

## External email / push delivery

Wedding Za uses a provider-neutral HTTPS webhook so production can connect to the provider selected by the business.

Configure these values in `config.local.php`:

```php
'notifications' => [
    'delivery_webhook_url' => 'https://YOUR-NOTIFICATION-SERVICE.example/webhook',
    'delivery_webhook_token' => 'YOUR_PRIVATE_TOKEN',
    'email_enabled' => true,
    'push_enabled' => true,
    'from_email' => 'notifications@your-domain.com',
    'from_name' => 'Wedding Za',
],
```

Environment-variable equivalents:

```text
WZ_NOTIFICATION_WEBHOOK_URL
WZ_NOTIFICATION_WEBHOOK_TOKEN
WZ_NOTIFICATION_EMAIL_ENABLED
WZ_NOTIFICATION_PUSH_ENABLED
WZ_NOTIFICATION_FROM_EMAIL
WZ_NOTIFICATION_FROM_NAME
```

If the webhook URL is blank, Wedding Za continues normally with in-app notifications only.

## Webhook payload

The delivery service receives JSON containing:

- event name
- notification ID/type/title/body/action URL
- recipient user ID/name/email/role
- requested channels: `email`, `push`, or both
- sender name/email
- timestamp

The provider service can route email through Resend, SendGrid, SES, Postmark or another mail provider, and push through OneSignal, Firebase Cloud Messaging or another push provider.

## User preferences

Customer, Vendor and Venue users can manage Email and Push preferences from their CRM Notifications page.

Admin users can manage the same preferences from Admin → Marketplace → Notifications.

In-app notifications are always available.

## Security

- Keep provider tokens only in `config.local.php` or environment variables.
- Never put email/push provider secrets in browser JavaScript.
- Use HTTPS for the delivery webhook in production.
- The notification webhook is called server-side.
