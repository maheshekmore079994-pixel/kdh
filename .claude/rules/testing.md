# Testing rules
- Feature tests in `tests/Feature/<Module>/`, `RefreshDatabase`, SQLite in-memory.
- Factories produce clearly fake data ("Test Customer", mobiles starting 99999). Factories never run in
  production seeders.
- Per module cover: happy path, validation failure, permission denied for an operator without the permission,
  money rounding, partial payment / balance, cancellation not corrupting reports or stock.
- Report tests assert totals from known fixtures and that cancelled/reversed rows are excluded.
- Run `php artisan test --compact` after each module. Fix failures; never skip to hide them.
- No sleeps, no network. `Storage::fake('private')` for uploads.
