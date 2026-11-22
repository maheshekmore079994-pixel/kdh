<?php

use App\Livewire\Configuration\AuditLogs;
use App\Livewire\Configuration\Billing;
use App\Livewire\Configuration\Business;
use App\Livewire\Configuration\Catalogue;
use App\Livewire\Configuration\Documents;
use App\Livewire\Configuration\Lists;
use App\Livewire\Configuration\Website;
use App\Livewire\Dashboard;
use App\Livewire\Users\Index;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Business application routes (authenticated, under /app)
|--------------------------------------------------------------------------
| Every route here requires a logged-in, active user. Module access is
| enforced inside each Livewire component with Gate::authorize().
*/

Route::middleware(['auth', 'active'])->prefix('app')->group(function () {
    Route::get('dashboard', Dashboard::class)->name('dashboard');

    // Users & configuration
    Route::get('users', Index::class)->name('users.index');
    Route::get('configuration/business', Business::class)->name('config.business');
    Route::get('configuration/billing', Billing::class)->name('config.billing');
    Route::get('configuration/website', Website::class)->name('config.website');
    Route::get('configuration/documents', Documents::class)->name('config.documents');
    Route::get('configuration/catalogue', Catalogue::class)->name('config.catalogue');
    Route::get('configuration/lists', Lists::class)->name('config.lists');
    Route::get('configuration/audit-log', AuditLogs::class)->name('config.audit');

    require __DIR__.'/app-modules.php';
});
