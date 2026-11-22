# Security rules
- Authorisation via Gates registered in `AppServiceProvider` from `App\Enums\Permission`; admins pass all.
  Every Livewire component authorises in `mount()` and again inside each mutating action.
- Mass assignment: models declare fillable attributes explicitly. Never fill from raw request arrays.
- All input validated. Foreign IDs validated with `exists:`.
- Files: stored on the `private` disk; downloads go through `DocumentController` which authorises and
  streams. Allowed MIME: pdf, jpg, jpeg, png, webp, docx, xlsx. Size limit from settings (default 10 MB).
  Reject executables, scripts, SVG, double extensions.
- Rate limits: login (Fortify), public enquiry form (`throttle:5,1`), document downloads (`throttle:60,1`).
- Production: `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`, HTTPS forced via `URL::forceScheme`.
- Never log passwords, OTPs, PINs, tokens or document contents. Audit log stores field diffs only.
- Logout via POST. CSRF everywhere (Livewire handles its own).
