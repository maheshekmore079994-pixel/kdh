<?php

namespace App\Providers;

use App\Enums\Permission;
use App\Listeners\RecordSuccessfulLogin;
use App\Models\User;
use App\Services\SettingsService;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SettingsService::class);
    }

    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureGates();
        Event::listen(Login::class, RecordSuccessfulLogin::class);
    }

    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(app()->isProduction());

        // Force https links only when the site itself is served over https (hosted server).
        // The desktop app runs as "production" but serves plain http on 127.0.0.1.
        if (app()->isProduction() && str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(10)->mixedCase()->letters()->numbers()
            : null,
        );
    }

    /**
     * One Gate per module permission. Administrators pass every check; operators need the
     * permission in their `permissions` list. Inactive users are always denied.
     */
    protected function configureGates(): void
    {
        Gate::before(function (User $user) {
            if (! $user->is_active) {
                return false;
            }

            return $user->isAdmin() ? true : null;
        });

        foreach (Permission::cases() as $permission) {
            Gate::define($permission->value, fn (User $user) => $user->hasPermission($permission));
        }
    }
}
