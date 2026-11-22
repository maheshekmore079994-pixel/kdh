<?php

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Scheduled tasks (run `php artisan schedule:run` every minute via cron /
| Task Scheduler, see docs/DEPLOYMENT.md)
|--------------------------------------------------------------------------
*/

Schedule::command('backup:clean')->dailyAt('01:30');
Schedule::command('backup:run --only-db')->dailyAt('02:00');
Schedule::command('documents:prune')->dailyAt('03:00');
Schedule::command('stock:verify')->weeklyOn(1, '03:30');
