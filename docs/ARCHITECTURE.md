# Architecture

## Stack
| Layer | Choice |
|---|---|
| Runtime | PHP 8.4, Laravel 13 |
| UI | Livewire 4 (class components), Flux UI free, Tailwind 4, Vite (vite-plus) |
| Auth | Laravel Fortify: login, password reset, 2FA (TOTP), password confirmation. No registration. |
| DB | MySQL 8 (production), SQLite (dev/tests) |
| PDF | barryvdh/laravel-dompdf |
| Spreadsheets | openspout/openspout (CSV/XLSX streaming) |
| Backups | spatie/laravel-backup |
| Desktop | NativePHP Desktop 2.x (Electron) — Milestone 8 |

## Layout
```
app/
  Enums/            Role, Permission, InvoiceStatus, JobStatus, StockMovementType, PaymentMethodType, ...
  Models/           Eloquent models (fillable explicit, casts, relations, scopes)
  Services/         InvoiceService, PaymentService, StockService, PurchaseService, JobService,
                    ExpenseService, NumberingService, SettingsService, AuditService, ReportService,
                    DocumentService, PrintCostService
  Livewire/         One folder per module; thin components calling services
  Http/Controllers/ Print/PDF/export/download controllers, public website controllers
  Policies/         (Gates are used; policies only where ownership matters)
  Support/          money(), indian_number(), date helpers
resources/views/
  layouts/, components/, livewire/<module>/, print/, public/, pdf/
routes/
  web.php (public site + auth), app.php (authenticated business app under /app), settings.php
```

## Request flow
Livewire component → validates → calls Service inside `DB::transaction()` → Service writes models, stock
movements, audit rows → component toasts and refreshes. Controllers only for file responses (PDF, CSV, download)
and the public website.

## Authorisation
`App\Enums\Permission` lists module permissions. `AppServiceProvider` registers a Gate per permission:
admin → allowed; operator → allowed if the permission is in `users.permissions` (JSON array).
`Gate::before` short-circuits for admins. Components call `Gate::authorize()`.

## Web vs desktop strategy (initial release)
- Hosted Laravel + MySQL on a VPS/shared host with HTTPS (see DEPLOYMENT.md).
- Browser clients use the hosted URL.
- The NativePHP Windows app is a packaged shell of the same codebase configured to run against the hosted
  backend: it needs internet access; no local MySQL; no DB credentials in the installer (the desktop build
  ships only `APP_URL` of the hosted API/web app and uses the hosted app's session login).
- **Offline-first SQLite sync is a documented future phase (DECISIONS.md D-07).** The UI shows "Internet
  connection required" in the desktop About screen.

## Settings
Key/value table (`settings`: group, key, value JSON). `SettingsService` caches the whole table and exposes
`setting('business.name')`. Groups: business, invoice, tax, website, documents, backup.

## Numbering
`number_sequences` (key, prefix, padding, next_number, reset_yearly, year). `NumberingService::next('invoice')`
locks the row (`lockForUpdate`) inside the caller's transaction and returns e.g. `INV-2026-00001`.

## Printing
Blade views in `resources/views/print/` rendered as HTML (browser print, A4 or 80 mm via CSS) and as PDF
through dompdf. Business details and logo come from settings.

## Files
`private` disk → `storage/app/private`. `DocumentController@download` authorises and streams.
`documents:prune` command deletes files past the configured retention.
