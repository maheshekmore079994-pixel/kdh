# Decisions & Assumptions

| ID | Decision | Why | Reversible? |
|---|---|---|---|
| D-01 | Laravel 13 + Livewire starter kit (Livewire 4, Flux free, Fortify) | Latest stable; official starter kit gives tested auth (login, reset, 2FA, confirm). | Yes |
| D-02 | No public registration, no passkeys, no email verification | Staff accounts are created by the admin; shop has no mail server configured. | Yes (Fortify features) |
| D-03 | Dev DB SQLite, prod MySQL 8 | No MySQL on the dev machine; migrations kept portable. | n/a |
| D-04 | Own lightweight roles: `users.role` + `users.permissions` JSON, Gates per module | Two roles only; avoids spatie/permission tables and complexity. | Yes |
| D-05 | Class-based Livewire components (not single-file ⚡ pages) for business modules | Easier testing and reuse; the kit's auth/settings pages stay as shipped. | Yes |
| D-06 | Money as DECIMAL strings handled with `bcmath`/`round(…,2)` helpers | Avoid float errors in INR calculations. | No |
| D-07 | Offline-first SQLite sync deferred to a future phase | Reliable two-way sync of financial data is a large project; MVP requires internet. | Future |
| D-08 | Desktop = NativePHP Desktop 2.x wrapper pointed at hosted backend | Requirement: one codebase, no bundled MySQL, no credentials in installer. | Yes |
| D-09 | PDF via dompdf; exports via openspout | Light, no headless browser needed on shared hosting. | Yes |
| D-10 | Backups via spatie/laravel-backup to local disk (and optional S3/Drive later) | Standard, scheduled, tested. | Yes |
| D-11 | Tax disabled by default; GSTIN blank | No GST registration information supplied. | Config |
| D-12 | Invoice numbers `INV-YYYY-NNNNN`, jobs `JOB-YYYY-NNNNN`, etc. prefixes configurable | Unique, sortable, yearly reset optional. | Config |
| D-13 | Profit shown as "estimate" with documented exclusions | Service consumable costs are not tracked per job. | Doc |
| D-14 | Walk-in invoices can have no customer, but any balance due requires a customer | Dues must be attributable. | No |
| D-15 | Jobs optionally billed; one invoice per job | Keeps advance/balance tracking in one place (payments). | Yes |
| D-16 | Livewire views folder `resources/views/livewire`, routes under `/app` prefix | Clean separation from the public website. | Yes |
| D-17 | Toolchain installed via winget (php.new one-liner blocked by policy) | Safer: hash-verified installers. | n/a |
| D-18 | Shop jobs live in `service_jobs` (model `ServiceJob`) | Laravel's queue owns the `jobs` table. | No |
| D-19 | Walk-in bills must be settled in full; any balance due requires a customer | Enforced in POS after payment is recorded, not in `InvoiceService::create`, so job billing can create unpaid invoices for known customers. | No |
| D-20 | Date-range filters use `whereDate()` | Laravel stores `date` casts with a time part in SQLite; `whereBetween` on strings missed the last day. Works on MySQL too. | No |
| D-21 | Cancelling an invoice/purchase requires its payments to be reversed first | Keeps receipts and reversals explicit in the audit trail instead of implicit refunds. | Yes |

## Assumptions to confirm with the owner
- Single shop, single currency, no multi-branch.
- Staff share one hosted installation; operators log in with their own accounts.
- Receipts are printed on an 80 mm thermal printer or A4 laser; both templates provided.
- Opening hours, address, map, GSTIN, prices to be filled in Settings by the admin after installation.