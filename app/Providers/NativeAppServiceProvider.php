<?php

namespace App\Providers;

use Native\Desktop\Contracts\ProvidesPhpIni;
use Native\Desktop\Facades\Menu;
use Native\Desktop\Facades\Window;

/**
 * Desktop (NativePHP / Electron) bootstrap.
 *
 * Strategy (docs/ARCHITECTURE.md): the Windows app is a shell for the hosted server. When
 * KDH_SERVER_URL is set in the build .env, the main window loads that URL over HTTPS and no
 * database credentials ship in the installer. Without it (developer builds) the embedded Laravel
 * app opens locally. Internet access is required either way; there is no offline mode.
 */
class NativeAppServiceProvider implements ProvidesPhpIni
{
    public function boot(): void
    {
        $serverUrl = rtrim((string) env('KDH_SERVER_URL', ''), '/');

        $window = Window::open()
            ->title((string) env('NATIVEPHP_APP_NAME', 'Khadavli Digital Hub'))
            ->width(1366)
            ->height(820)
            ->minWidth(1024)
            ->minHeight(640)
            ->rememberState();

        if ($serverUrl !== '') {
            $window->url($serverUrl.'/login');
        }

        Menu::create(
            Menu::app(),
            Menu::edit(),
            Menu::view(),
            Menu::window(),
        );
    }

    public function phpIni(): array
    {
        return [
            'memory_limit' => '256M',
            'upload_max_filesize' => '20M',
            'post_max_size' => '25M',
            'date.timezone' => 'Asia/Kolkata',
        ];
    }
}
