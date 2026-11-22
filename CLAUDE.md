# KHADAVLI DIGITAL HUB — Business Management System

Laravel 13 + Livewire 4 + Flux + Tailwind 4 application for an Indian cyber cafe / Xerox / printing /
CSC & online services centre. One codebase serves the web app, the public website and (later) the
NativePHP Windows desktop client.

**Read before any task:** this file, `.claude/rules/*.md`, and the relevant `docs/*.md`.
Update the docs when requirements or implementation change. Business rules below are permanent.

## Business identity (do not invent anything else)
- Name: KHADAVLI DIGITAL HUB · Tagline on logo: "CSC & Online Service Center"
- Email: khadavlidigitalhub@gmail.com · Phone: 8055000414 · India · INR (₹) · Asia/Kolkata
- Address, opening hours, GST registration, prices, maps: **unknown** → configurable in Settings, never hard-coded.
- Brand assets: `public/images/brand/` (copied from `logo/extracted/`, the approved real-pixel extraction).
  Never redraw the logo or alter QR codes. `logo/vector-rebuild/` is rejected, reference only.

## Non-negotiable business rules
1. Never invent prices, expenses, purchases, payments, customers or transactions. No fake sample data.
   Seeders create only: the admin user, payment methods, units, category *names*, service *names* with
   NULL prices, number sequences and settings keys.
2. Every price/charge is admin-configurable (service catalogue + variants). Tax is optional and configurable.
3. Money = `DECIMAL(12,2)`, quantities = `DECIMAL(12,3)`. Never floats. Use `App\Support\Money` helpers.
4. Financial records are never hard-deleted: soft delete + reversal/cancellation with reason, actor, time.
5. Stock never changes silently: every change is a `stock_movements` row created inside a DB transaction.
6. Unpaid invoice ≠ cash received. Reports separate sales (invoiced) from receipts (payments) by method.
7. Audit log every financial edit, cancellation, reversal, settings change, user/permission change.
8. Never collect UPI PINs, OTPs, passwords, bank credentials of customers. Never auto-initiate payments or
   submit external government applications; the operator confirms everything.
9. Minimise customer data: name + mobile required, everything else optional.
10. Permissions are enforced server-side (Gates), the UI only hides.
11. Uploaded documents: private disk, validated type/size, no executables, authorised download only.

## Tech stack & conventions
- PHP 8.4, Laravel 13, Livewire 4 (class components in `app/Livewire`, views in `resources/views/livewire`),
  Flux UI (free), Tailwind 4, Fortify auth (login, reset, 2FA, password confirm; **no public registration**).
- Dev DB: SQLite (`database/database.sqlite`). Production DB: MySQL 8. Migrations must work on both.
- Services layer: `app/Services/*` hold business logic (InvoiceService, PaymentService, StockService, …);
  Livewire components stay thin. All multi-table writes use `DB::transaction()`.
- Numbering: `App\Services\NumberingService` with row-locked `number_sequences` table.
- Dates: Asia/Kolkata, display `d M Y` / `d M Y, h:i A`; currency via `money()` helper → `₹1,23,456.00`.
- Tests: PHPUnit (`php artisan test`). Add tests for every money/stock/permission path. Fix, never skip.
- Code style: `composer lint` (Pint).
- Toolchain quirks (Windows dev box): see `docs/DEPLOYMENT.md` → Local development.

## Commands
```
composer run dev            # serve (port 8010 via SERVER_PORT) + queue + vite
php artisan migrate         # run migrations
php artisan test --compact  # tests
composer lint               # pint
php artisan kdh:install     # first-time setup (admin user, defaults)
php artisan backup:run      # spatie backup
php artisan documents:prune # delete attachments past retention
php artisan stock:verify    # check product balances vs movements
php artisan native:build win # Windows installer (Milestone 8)
```

## Docs map
- `docs/BUSINESS_PROFILE.md` identity, services, workflows
- `docs/PRODUCT_REQUIREMENTS.md` modules + acceptance criteria
- `docs/ARCHITECTURE.md` layers, desktop/online strategy
- `docs/DATABASE_SCHEMA.md` tables, relations, constraints
- `docs/DEVELOPMENT_PLAN.md` milestones + progress checklist (keep current)
- `docs/USER_MANUAL.md` operator instructions
- `docs/DEPLOYMENT.md` local, production, Windows desktop
- `docs/DECISIONS.md` decisions & assumptions
