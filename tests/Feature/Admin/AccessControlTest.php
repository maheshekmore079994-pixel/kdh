<?php

namespace Tests\Feature\Admin;

use App\Enums\Permission;
use App\Livewire\Users\Index as UsersIndex;
use App\Models\User;
use Database\Seeders\ConfigurationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ConfigurationSeeder::class);
    }

    public function test_guest_is_redirected_from_app_pages(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->get(route('users.index'))->assertRedirect(route('login'));
    }

    public function test_admin_can_open_dashboard_and_configuration_pages(): void
    {
        $admin = User::factory()->admin()->create();

        foreach (['dashboard', 'users.index', 'config.business', 'config.billing', 'config.website', 'config.documents', 'config.catalogue', 'config.lists', 'config.audit'] as $route) {
            $this->actingAs($admin)->get(route($route))->assertOk();
        }
    }

    public function test_operator_without_permission_is_forbidden(): void
    {
        $operator = User::factory()->operator([Permission::Billing])->create();

        $this->actingAs($operator)->get(route('dashboard'))->assertOk();
        $this->actingAs($operator)->get(route('users.index'))->assertForbidden();
        $this->actingAs($operator)->get(route('config.business'))->assertForbidden();
    }

    public function test_operator_with_permission_is_allowed(): void
    {
        $operator = User::factory()->operator([Permission::Users])->create();

        $this->actingAs($operator)->get(route('users.index'))->assertOk();
    }

    public function test_inactive_user_is_logged_out(): void
    {
        $user = User::factory()->inactive()->create();

        $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_admin_can_create_operator_with_permissions(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(UsersIndex::class)
            ->call('create')
            ->set('name', 'Test Operator')
            ->set('email', 'operator@example.test')
            ->set('role', 'operator')
            ->set('permissions', ['billing', 'jobs'])
            ->set('password', 'secret-pass-123')
            ->call('save')
            ->assertHasNoErrors();

        $user = User::query()->where('email', 'operator@example.test')->firstOrFail();
        $this->assertFalse($user->isAdmin());
        $this->assertTrue($user->hasPermission(Permission::Billing));
        $this->assertFalse($user->hasPermission(Permission::Reports));
        $this->assertDatabaseHas('audit_logs', ['action' => 'user.created', 'auditable_id' => $user->id]);
    }

    public function test_last_active_admin_cannot_be_demoted(): void
    {
        $admin = User::factory()->admin()->create();
        $other = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(UsersIndex::class)
            ->call('toggleActive', $other->id);

        $this->assertFalse($other->fresh()->is_active);

        Livewire::actingAs($admin)
            ->test(UsersIndex::class)
            ->call('edit', $admin->id)
            ->set('role', 'operator')
            ->call('save')
            ->assertHasErrors('role');
    }

    public function test_login_records_last_login_and_audit(): void
    {
        $user = User::factory()->create();

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password']);

        $this->assertNotNull($user->fresh()->last_login_at);
        $this->assertDatabaseHas('audit_logs', ['action' => 'auth.login', 'user_id' => $user->id]);
    }
}
