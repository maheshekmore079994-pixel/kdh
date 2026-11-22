# Development Plan & Progress

Legend: ☐ pending · ◐ in progress · ☑ done. Keep this file current.

## Milestone 1 — Inspection, knowledge, architecture, installation
- ☑ Environment inspected (PHP/Composer/MySQL absent; Node 21) and toolchain installed (PHP 8.4, Composer 2.11, Node 26)
- ☑ Laravel 13 + Livewire starter kit scaffolded; registration/passkeys/email-verification removed; 29 kit tests pass
- ☑ Packages: dompdf, openspout, spatie/laravel-backup
- ☑ CLAUDE.md, .claude/rules, docs/* created
- ☑ Brand assets copied to public/images/brand

## Milestone 2 — Auth, roles, dashboard, settings, core DB
- ☑ Core migrations (all tables in DATABASE_SCHEMA.md)
- ☑ Enums, models, factories
- ☑ Roles/permissions Gates + user management UI
- ☑ Settings service + Configuration UI (business, billing & tax, website, documents & backups, catalogue, lists, audit log)
- ☑ `kdh:install` command (admin user + config seed)
- ☑ Branded layout, sidebar navigation
- ☑ Dashboard with real aggregates

## Milestone 3 — Customers, catalogue, POS, invoices, payments
- ☑ Customers CRUD + duplicate detection + history + picker
- ☑ Service categories/services/variants CRUD
- ☑ POS billing component, InvoiceService, NumberingService
- ☑ Payments (full/partial), reversal
- ☑ Invoice list/detail, A4 + 80mm receipt print, PDF, payment receipt
- ☑ Tests: billing, rounding, partial payments, dues, cancellation, stock

## Milestone 4 — Jobs, expenses, purchases, inventory
- ☑ Jobs CRUD, status workflow, history, job receipt (HTML+PDF), billing from job, advances
- ☑ Expenses + categories + attachments + reasoned delete
- ☑ Suppliers, purchases, posting, cancellation, supplier payments + reversal
- ☑ Products, stock movements, manual adjustments, low stock, integrity check
- ☑ Documents (private uploads, authorised download, retention prune)
- ☑ Tests

## Milestone 5 — Accounts, reports, printing, exports
- ☑ Cashbook, sales/payments/expense/purchase registers, customer & supplier dues, valuation, P&L estimate
- ☑ CSV/XLSX/PDF exports (shared ReportBuilder)
- ☑ Print cost calculator with admin cost profiles
- ☑ Tests for report calculations

## Milestone 6 — Public website & enquiries
- ☑ Pages: Home, About, Services, Contact, Privacy, Terms; SEO meta, JSON-LD, sitemap
- ☑ Enquiry form (throttled, honeypot) + admin enquiry management
- ☑ Website content settings

## Milestone 7 — Security review, tests, backups, deployment prep
- ☑ Audit log viewer, documents:prune, stock:verify, scheduled backups
- ☑ Security checklist (see DEPLOYMENT.md)
- ☑ DEPLOYMENT.md, USER_MANUAL.md
- ☐ Browser-level QA pass by the owner (automated tests cover rendering and logic)

## Milestone 8 — NativePHP Windows packaging
- ☑ nativephp/desktop 2.4 installed; `native:install --force` completed (Electron + PHP 8.4 runtime cached in nativephp/binaries)
- ☑ config/nativephp.php hardened (DB_*/MAIL_* stripped from the bundled .env, prebuild = npm run build), NativeAppServiceProvider opens the hosted server URL (KDH_SERVER_URL)
- ◐ `php artisan native:build win` attempted twice on 2026-11-07: pre-build steps succeed, then the "Updating Electron dependencies" (npm install inside vendor/nativephp/desktop/resources/electron) step made no progress for 9+ minutes both times and was stopped. Not verified. Next: run the build on a machine/network without TLS interception (Avast) or with `npm config set cafile C:SERSDMININRTSCERT.PEM`, THEN TEST THE INSTALLER FROM DIST/. STEPS IN DEPLOYMENT.MD.

## Issues / notes
- Node 21 was too old for vite-plus; upgraded to Node 26 LTS.
- `composer lint`/pint from cmd needs PHP on the Windows PATH (added to user PATH).

## Phase 2 — Shop-floor improvements (planned 2026-11-22)
- ☑ First-run setup page (`/setup`) for fresh installs, including the desktop app
- ☑ Dev server moved to port 8010 (`SERVER_PORT`), code pushed to github.com/maheshekmore079994-pixel/kdh
- ☐ Operator rate card: read-only, searchable price list of all services and variants for operators, plus a printable/QR rate card for the counter
- ☐ Service portal directory: official portal URL, required documents checklist and notes per service; "Open portal" button on jobs (opens the official site in the browser, operator still submits manually)
- ☐ Customer document checklist per job (received / returned) and a "documents returned to customer" step before delivery
- ☐ Quick customer lookup on POS by mobile with last jobs and dues; WhatsApp "job ready" message link (opens WhatsApp, operator sends)
- ☐ Desktop-to-hosted migration: export local desktop data and import into the hosted MySQL once hosting is back
- ☐ Hosting: deploy to server, MySQL, HTTPS, cron, backups off-site; then rebuild the desktop app with KDH_SERVER_URL set
