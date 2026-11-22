<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\User;
use Database\Seeders\ConfigurationSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Validator;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

class InstallCommand extends Command
{
    protected $signature = 'kdh:install
        {--name= : Administrator name}
        {--email= : Administrator email}
        {--password= : Administrator password}
        {--skip-migrate : Do not run migrations}';

    protected $description = 'First-time setup: migrate, seed configuration and create the administrator account';

    public function handle(): int
    {
        if (! $this->option('skip-migrate')) {
            $this->components->task('Running migrations', fn () => Artisan::call('migrate', ['--force' => true]) === 0);
        }

        $this->components->task('Seeding configuration (no transactions)', function () {
            Artisan::call('db:seed', ['--class' => ConfigurationSeeder::class, '--force' => true]);

            return true;
        });

        if (User::query()->where('role', Role::Admin->value)->exists()) {
            $this->components->info('An administrator already exists. Skipping admin creation.');

            return self::SUCCESS;
        }

        $name = $this->option('name') ?: text('Administrator name', required: true);
        $email = $this->option('email') ?: text('Administrator email', required: true);
        $pass = $this->option('password') ?: password('Administrator password (min 8 characters)', required: true);

        $validator = Validator::make(
            ['name' => $name, 'email' => $email, 'password' => $pass],
            ['name' => 'required|string|max:100', 'email' => 'required|email|unique:users,email', 'password' => 'required|string|min:8'],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->components->error($error);
            }

            return self::FAILURE;
        }

        if (! $this->option('password') && ! confirm("Create administrator {$email}?", default: true)) {
            return self::SUCCESS;
        }

        User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => $pass,
            'role' => Role::Admin,
            'permissions' => null,
            'is_active' => true,
        ]);

        $this->components->info("Administrator {$email} created. Log in at /login.");

        return self::SUCCESS;
    }
}
