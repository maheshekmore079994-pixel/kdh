<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * The default seeder only seeds configuration. It never creates users, customers or transactions.
 * Create the first administrator with `php artisan kdh:install`.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(ConfigurationSeeder::class);
    }
}
