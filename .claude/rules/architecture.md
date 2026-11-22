# Architecture rules
- Thin Livewire components → `app/Services` for business logic → Eloquent models. No business logic in Blade.
- Every write that touches more than one table, money or stock runs inside `DB::transaction()`.
- Services throw `App\Exceptions\DomainException`; components catch it and show a Flux toast.
- Public website routes live in `routes/web.php` with no auth; the business app lives under the `/app`
  prefix with `auth` middleware (see `routes/app.php`). Never mix them.
- Desktop client (NativePHP) loads the same Laravel app and talks to the hosted backend over HTTPS. No DB
  credentials ship in the installer. Offline mode is **not** implemented; say so in UI and docs.
- Keep dependencies minimal. Prefer framework features over packages.
- Queues only for backups and emails. Everything else synchronous.
- Enums in `app/Enums` (backed string enums) for statuses, roles, permissions, movement types.
- Settings are key/value rows in `settings`, accessed through `App\Services\SettingsService` (cached).
