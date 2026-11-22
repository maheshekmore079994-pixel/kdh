# Deployment

## Local development (Windows, this machine)
Toolchain installed on 2026-11-07:
| Tool | Location |
|---|---|
| PHP 8.4.27 | `%LOCALAPPDATA%\Microsoft\WinGet\Packages\PHP.PHP.8.4_Microsoft.Winget.Source_8wekyb3d8bbwe\php.exe` (php.ini there, extensions enabled) |
| Composer 2.11 | `C:\Users\admin\bin\composer.phar` with `composer.cmd` shim |
| Node 26 LTS | `C:\Program Files\nodejs` |
| CA bundle for PHP | `C:\Users\admin\bin\certs\cacert.pem` (public roots + Avast scanning root; keeps TLS verification on) |

Both the PHP folder and `C:\Users\admin\bin` are on the user PATH. In Git Bash sessions started before that
change, prefix: `export PATH="/c/Users/admin/AppData/Local/Microsoft/WinGet/Packages/PHP.PHP.8.4_Microsoft.Winget.Source_8wekyb3d8bbwe:/c/Users/admin/bin:$PATH"`.

```
composer install && npm install
cp .env.example .env && php artisan key:generate
php artisan kdh:install            # migrate, seed configuration, create the administrator
npm run build                      # or: composer run dev (serves + vite + queue)
php artisan serve                  # http://127.0.0.1:8010 (port from SERVER_PORT in .env)
php artisan test --compact
```
KDH uses port 8010 (set by `SERVER_PORT` in `.env`) because 8000 is used by another app on this PC.
Dev database: SQLite at `database/database.sqlite`. A local admin `admin@localhost` was created for this
machine only during development; change or delete it before any real use (Users page).

## Production (hosted Laravel + MySQL)
Requirements: PHP 8.3+ (8.4 recommended) with bcmath, ctype, curl, dom, fileinfo, gd, intl, mbstring,
openssl, pdo_mysql, tokenizer, xml, zip; MySQL 8 (or MariaDB 10.6+); Composer; Node 22+ only for building
assets (build locally and upload `public/build` if the host has no Node); HTTPS certificate; cron access.

1. Create a MySQL database and user with privileges on that database only.
2. Upload the project (or `git clone`) outside the web root; point the web root to `public/`.
3. `composer install --no-dev --optimize-autoloader`
4. `.env` (never commit it):
   ```
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://your-domain.in
   APP_TIMEZONE=Asia/Kolkata
   DB_CONNECTION=mysql  DB_HOST=127.0.0.1  DB_DATABASE=...  DB_USERNAME=...  DB_PASSWORD=...
   SESSION_DRIVER=database
   SESSION_SECURE_COOKIE=true
   QUEUE_CONNECTION=database
   CACHE_STORE=database
   MAIL_MAILER=smtp ... (only if password reset emails are wanted)
   ```
5. `php artisan key:generate`, `php artisan storage:link`, `php artisan kdh:install` (creates the admin),
   `php artisan optimize` (config/route/view cache).
6. Permissions: the web server user must write to `storage/` and `bootstrap/cache/`.
7. Cron (every minute): `* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1`
   This runs: daily DB backup 02:00, backup clean 01:30, document prune 03:00, weekly stock verify.
8. Queue (optional, for emails): `php artisan queue:work --tries=3` under supervisor, or leave
   `QUEUE_CONNECTION=sync`.
9. Force HTTPS at the web server; the app also forces https URLs in production.
10. After each update: `php artisan down`, pull, `composer install --no-dev`, `php artisan migrate --force`,
    `php artisan optimize`, `php artisan up`.

### Backups and restore
- Automatic: spatie/laravel-backup writes `storage/app/private/<APP_NAME>/*.zip` daily (DB dump). MySQL dumps
  need the `mysqldump` binary on the server; configure `DB_DUMP_PATH` / `config/backup.php` if it is not on PATH.
- Manual: **Configuration → Documents & backups → Back up now** or `php artisan backup:run --only-db`.
- Copy backups off the server (Google Drive, external disk) regularly. Optional: add an S3/Drive disk in
  `config/filesystems.php` and list it in `config/backup.php` destinations.
- Restore: create an empty database, unzip the backup, `mysql -u user -p dbname < db-dumps/mysql-dbname.sql`,
  then restore `storage/app/private/documents` from your file backup.
- Rollback a migration: `php artisan migrate:rollback --step=1` (take a backup first).

### Security checklist (done in code)
- Fortify login with rate limiting, 2FA, password confirmation for security settings; no public registration.
- Gates enforced server-side per module; inactive users are logged out by middleware.
- CSRF on all forms, validation everywhere, mass assignment guarded, enum-validated statuses.
- Documents on the private disk, MIME + extension checked, executables refused, downloads authorised and
  throttled.
- Audit log for financial edits, reversals, cancellations, settings, users and logins; passwords/tokens never logged.
- `DB::prohibitDestructiveCommands` in production; strong password rule in production.

## Windows desktop app (NativePHP Desktop 2.x)
Status: `nativephp/desktop` 2.4 installed, `native:install` completed (Electron runtime + bundled PHP 8.4 under
`nativephp/binaries`, git-ignored), `config/nativephp.php` and `app/Providers/NativeAppServiceProvider.php`
configured. Build result: see DEVELOPMENT_PLAN.md Milestone 8.

Strategy (see ARCHITECTURE.md): the desktop app is a shell of the same codebase that must connect to the hosted
server over the internet. It bundles **no database server and no production credentials**. Internet is required.

Build steps (Windows 10+, Node 22+, PHP 8.3+):
```
composer require nativephp/desktop        # done
php artisan native:install --force         # done; re-run on a new machine (downloads ~500 MB, needs internet)
# .env used for the build (never the production server .env):
#   NATIVEPHP_APP_NAME="Khadavli Digital Hub"
#   NATIVEPHP_APP_ID=in.khadavlidigitalhub.app
#   NATIVEPHP_APP_VERSION=1.0.0
#   KDH_SERVER_URL=https://your-domain.in   # the hosted app; the window loads this URL
#   DB_CONNECTION=sqlite                      # local only; DB_* credentials are stripped by cleanup_env_keys
php artisan native:run                     # development run
php artisan native:build win               # produces an installer under dist/
```
Before each release bump `version` in `config/nativephp.php` (migrations run only when the version changes).
Code signing (optional, avoids SmartScreen warnings): Azure Trusted Signing via the `AZURE_*` /
`NATIVEPHP_AZURE_*` variables, or a traditional certificate per Electron docs.
How it works: `NativeAppServiceProvider` opens one window; when `KDH_SERVER_URL` is set it loads the hosted
login page, so all data lives on the server and the installer carries no database credentials
(`cleanup_env_keys` also strips `DB_*` and `MAIL_*` secrets). Without `KDH_SERVER_URL` the embedded Laravel
app runs locally against SQLite (developer use only). Internet is required; there is no offline mode.

### Building the Windows installer on this PC (verified 2026-11-22)
Run from Git Bash in the project folder:
```
export NODE_EXTRA_CA_CERTS="C:\Users\admin\bin\certs\cacert.pem"   # Node must trust the Avast scanning root
export npm_config_cafile="C:\Users\admin\bin\certs\cacert.pem"
sed -i 's/^APP_DEBUG=true/APP_DEBUG=false/' .env                           # operators never see debug pages
NATIVEPHP_APP_NAME="Khadavli Digital Hub" NATIVEPHP_APP_ID=in.khadavlidigitalhub.app \
  php artisan native:build win --no-interaction
sed -i 's/^APP_DEBUG=false/APP_DEBUG=true/' .env                           # restore local debugging
```
Output: `nativephp/electron/dist/Khadavli Digital Hub-<version>-setup.exe` (≈145 MB, git-ignored).

Known issues and fixes:
- **Stalls at "Updating Electron dependencies"**: Node does not use the Windows certificate store, so the Avast
  HTTPS scanner blocks downloads. `NODE_EXTRA_CA_CERTS` fixes it. The step is slow (≈10 s per package) but works.
- **"Cannot create symbolic link: A required privilege is not held"**: electron-builder's `winCodeSign` archive
  contains macOS symlinks. Either enable Windows Developer Mode, or extract once by hand (the macOS files are not
  needed): `7za x -y -o%LOCALAPPDATA%\electron-builder\Cache\winCodeSign\winCodeSign-2.6.0 <cached>.7z`
  using `vendor/nativephp/desktop/resources/electron/node_modules/7zip-bin/win/x64/7za.exe`. Already done on this PC.
- **App exits instantly when launched from VS Code / Claude Code terminals**: those shells set
  `ELECTRON_RUN_AS_NODE=1`. Launch from Explorer/Start menu, or `env -u ELECTRON_RUN_AS_NODE ./app.exe`.
- **Unsigned installer**: Windows SmartScreen shows "Windows protected your PC" → *More info* → *Run anyway*.
  Buy a code-signing certificate later to remove this.
- **"INSECURE BUILD" warning**: the PHP source ships readable inside the install folder. Acceptable for an
  in-house operator tool; see NativePHP docs on secure bundles if this changes.

Desktop data location: `%APPDATA%\khadavli-digital-hub` (`database\database.sqlite`, `storage\app\private` for
documents). Back this folder up daily while the shop runs on the desktop app without hosting.
First launch shows **First-time setup** to create the administrator.
