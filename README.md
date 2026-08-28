# TVTCommerce_AdminAuditLog

**Free** Magento 2.4.x admin extension from [TVT Commerce](https://tvtcommerce.com).

A general-purpose admin action audit log — records **who did what and when** across the
entire Magento admin panel, not just actions taken through any particular tool. Every
admin controller action (product save, CMS edit, config change, customer edit, etc.) is
captured and shown in a filterable, sortable grid under **Admin Audit Log > Log**.

This is useful for **every** Magento store, whether or not you use any AI product from
TVT Commerce — it's plain accountability/traceability tooling: "who changed this price
last Tuesday?", "did anyone touch this CMS block?", "what did this new admin account
actually do?".

## What it does

- Listens to `controller_action_postdispatch_adminhtml` — i.e. it observes the same
  event Magento itself fires after every single admin controller action, so no
  individual controller needs to be modified.
- Records: acting admin (ID + username, the latter denormalized so the row still makes
  sense after the admin user account is deleted), the full action name (e.g.
  `catalog_product_save`), the HTTP method, the client IP, and the submitted field values
  under **Changed Fields** — a readable `key: value; nested.key: value` summary (not a raw
  JSON blob), so you can see exactly what was submitted in that action directly in the grid.
- **Sanitization**: any parameter whose key looks sensitive (`password`, `api_key`,
  `license_key`, `token`, `secret`, `webhook`, `card`, `cvv`, `auth` — matched
  case-insensitively as a substring) has its *value* replaced with `[REDACTED]`. The key
  itself is kept, so you can still see that a sensitive field was part of the request.
  See `Model/ParamSanitizer.php`.
- By default, only state-changing **POST** requests are logged (keeps the table small
  and focused). Turn on **Log Read-Only (GET) Requests** in settings if you also want
  every admin page view recorded.
- Login/logout controllers are always skipped — credential-entry requests are never
  logged, sanitized or not.
- A daily cron (`tvt_admin_audit_log_cleanup`, 02:30 server time) deletes log rows older
  than the configured retention window (default 90 days), so the table doesn't grow
  forever.
- The grid supports a **Delete** mass-action for manually clearing selected entries.
- If logging a single request ever fails for any reason, the failure is logged to the
  system log and swallowed — a broken audit log must never break the admin action it's
  observing.

This module makes **no external network calls** of any kind — no license server, no
telemetry, no third-party API. Everything happens locally against your own database.

## Installation

1. Copy (or clone) this module into your Magento installation, e.g.
   `<magento-root>/app/code/TVTCommerce/AdminAuditLog`, **or** add it as a Composer path
   repository:

   ```json
   {
       "repositories": {
           "tvtcommerce-admin-audit-log": {
               "type": "path",
               "url": "../path/to/module-admin-audit-log"
           }
       }
   }
   ```

   then:

   ```bash
   composer require tvtcommerce/module-admin-audit-log:@dev
   ```

2. Enable the module and run setup upgrade:

   ```bash
   bin/magento module:enable TVTCommerce_AdminAuditLog
   bin/magento setup:upgrade
   bin/magento cache:flush
   ```

3. Go to **Stores > Configuration > TVTCommerce > Admin Audit Log** to enable logging,
   set the retention window, and optionally turn on GET-request logging.

4. Go to **Admin Audit Log > Log** in the admin menu to view the grid.

## Configuration reference

| Field | Path | Default |
|---|---|---|
| Enable Audit Log | `tvtcommerce_admin_audit_log/general/enabled` | No |
| Retention (Days) | `tvtcommerce_admin_audit_log/general/retention_days` | 90 |
| Log Read-Only (GET) Requests | `tvtcommerce_admin_audit_log/general/log_get_requests` | No |

## Permissions (ACL)

- `TVTCommerce_AdminAuditLog::root` — root resource
  - `TVTCommerce_AdminAuditLog::view` — view the log grid
  - `TVTCommerce_AdminAuditLog::manage` — clear (mass-delete) log entries
- `TVTCommerce_AdminAuditLog::config` — nested under `Magento_Config::config`, controls
  access to the settings page

## Running the unit tests

The sanitization logic (`Model/ParamSanitizer.php`) has zero Magento dependencies and is
covered by a standalone PHPUnit suite, isolated from the module's own Composer manifest
(which requires `magento/module-*` packages that need repo.magento.com credentials):

```bash
cd tests
composer install
vendor/bin/phpunit
```

## Also from TVT Commerce

This module is a free, general-purpose tool. If you're looking for AI-powered features
on top of your Magento admin, TVT Commerce also builds:

- **AI Copilot** — an in-admin AI assistant that can answer questions and (with your
  confirmation) perform actions across catalog, CMS, customers, and more.
- **AI Merchandiser** — AI-generated merchandising suggestions (pricing, reorder,
  dead-stock) reviewed and approved from an admin grid.

Both are paid, more advanced tools for the "what's happening in my store" and "what
should I change" problem space that this free audit log only observes and records. Learn
more at [tvtcommerce.com](https://tvtcommerce.com).

## License

MIT
