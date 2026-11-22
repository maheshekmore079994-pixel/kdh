<?php

namespace Tests\Feature\Admin;

use App\Models\PaymentMethod;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FirstRunSetupTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_redirects_to_setup_when_no_users_exist(): void
    {
        $this->get(route('login'))->assertRedirect(route('setup'));
        $this->get(route('setup'))->assertOk()->assertSee('first-time setup', false);
    }

    public function test_setup_creates_admin_and_seeds_configuration_without_prices(): void
    {
        $this->post(route('setup.store'), [
            'name' => 'Test Owner',
            'email' => 'owner@example.test',
            'password' => 'secret-pass-1',
            'password_confirmation' => 'secret-pass-1',
        ])->assertRedirect(route('config.business'));

        $admin = User::query()->firstOrFail();
        $this->assertTrue($admin->isAdmin());
        $this->assertAuthenticatedAs($admin);
        $this->assertTrue(PaymentMethod::query()->where('code', 'cash')->exists());
        $this->assertGreaterThan(0, Service::query()->count());
        $this->assertSame(0, Service::query()->whereNotNull('default_price')->count());
    }

    public function test_setup_is_closed_once_a_user_exists(): void
    {
        User::factory()->create();

        $this->get(route('setup'))->assertNotFound();
        $this->post(route('setup.store'), [
            'name' => 'Intruder', 'email' => 'x@example.test', 'password' => 'secret-pass-1', 'password_confirmation' => 'secret-pass-1',
        ])->assertNotFound();
        $this->assertSame(1, User::query()->count());
        $this->get(route('login'))->assertOk();
    }
}
