<?php

namespace App\Livewire\Users;

use App\Enums\Permission;
use App\Enums\Role;
use App\Models\User;
use App\Services\AuditService;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Users')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public ?int $editingId = null;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $role = 'operator';

    public array $permissions = [];

    public bool $is_active = true;

    public string $password = '';

    public function mount(): void
    {
        Gate::authorize('users');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        Gate::authorize('users');
        $this->resetForm();
        $this->permissions = Permission::operatorDefaults();
        Flux::modal('user-form')->show();
    }

    public function edit(int $id): void
    {
        Gate::authorize('users');
        $user = User::query()->findOrFail($id);
        $this->resetForm();
        $this->editingId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->phone = (string) $user->phone;
        $this->role = $user->role->value;
        $this->permissions = $user->permissions ?? [];
        $this->is_active = $user->is_active;
        Flux::modal('user-form')->show();
    }

    public function save(AuditService $audit): void
    {
        Gate::authorize('users');

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($this->editingId)],
            'phone' => ['nullable', 'string', 'max:20'],
            'role' => ['required', Rule::enum(Role::class)],
            'permissions' => ['array'],
            'permissions.*' => [Rule::in(Permission::values())],
            'is_active' => ['boolean'],
            'password' => [$this->editingId ? 'nullable' : 'required', 'string', Password::default()],
        ]);

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?: null,
            'role' => $validated['role'],
            'permissions' => $validated['role'] === Role::Admin->value ? null : array_values($validated['permissions']),
            'is_active' => $validated['is_active'],
        ];

        if ($this->editingId) {
            $user = User::query()->findOrFail($this->editingId);

            if ($user->id === auth()->id() && (! $data['is_active'] || $data['role'] !== Role::Admin->value) && $user->isAdmin()) {
                $this->addError('role', __('You cannot deactivate or demote your own administrator account.'));

                return;
            }

            if ($user->isAdmin() && $data['role'] !== Role::Admin->value && $this->isLastActiveAdmin($user)) {
                $this->addError('role', __('At least one active administrator is required.'));

                return;
            }

            $user->fill($data);
            if (filled($validated['password'])) {
                $user->password = $validated['password'];
            }
            $audit->logChanges('user.updated', $user);
            $user->save();
            Flux::toast(variant: 'success', text: __('User updated.'));
        } else {
            $user = User::query()->create($data + ['password' => $validated['password']]);
            $audit->log('user.created', $user, [], ['name' => $user->name, 'email' => $user->email, 'role' => $user->role->value]);
            Flux::toast(variant: 'success', text: __('User created.'));
        }

        Flux::modal('user-form')->close();
        $this->resetForm();
    }

    public function toggleActive(int $id, AuditService $audit): void
    {
        Gate::authorize('users');
        $user = User::query()->findOrFail($id);

        if ($user->id === auth()->id()) {
            Flux::toast(variant: 'danger', text: __('You cannot deactivate your own account.'));

            return;
        }
        if ($user->is_active && $user->isAdmin() && $this->isLastActiveAdmin($user)) {
            Flux::toast(variant: 'danger', text: __('At least one active administrator is required.'));

            return;
        }

        $user->is_active = ! $user->is_active;
        $audit->logChanges($user->is_active ? 'user.activated' : 'user.deactivated', $user);
        $user->save();
        Flux::toast(variant: 'success', text: $user->is_active ? __('User activated.') : __('User deactivated.'));
    }

    private function isLastActiveAdmin(User $user): bool
    {
        return User::query()->where('role', Role::Admin->value)->where('is_active', true)->where('id', '!=', $user->id)->doesntExist();
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'email', 'phone', 'role', 'permissions', 'is_active', 'password']);
        $this->resetValidation();
    }

    public function render(): View
    {
        $users = User::query()
            ->when($this->search, fn ($q) => $q->where(fn ($w) => $w->where('name', 'like', "%{$this->search}%")->orWhere('email', 'like', "%{$this->search}%")))
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.users.index', [
            'users' => $users,
            'allPermissions' => Permission::cases(),
        ]);
    }
}
