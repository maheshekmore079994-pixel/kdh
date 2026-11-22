<div>
    <x-page-header :title="__('Users')" :subtitle="__('Staff accounts and module permissions')">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search name or email')" size="sm" class="w-64" />
        <flux:button wire:click="create" icon="user-plus" variant="primary" size="sm">{{ __('New user') }}</flux:button>
    </x-page-header>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <flux:table :paginate="$users">
            <flux:table.columns>
                <flux:table.column>{{ __('Name') }}</flux:table.column>
                <flux:table.column>{{ __('Role') }}</flux:table.column>
                <flux:table.column>{{ __('Permissions') }}</flux:table.column>
                <flux:table.column>{{ __('Status') }}</flux:table.column>
                <flux:table.column>{{ __('Last login') }}</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse($users as $user)
                    <flux:table.row :key="$user->id">
                        <flux:table.cell>
                            <div class="font-medium text-zinc-900 dark:text-white">{{ $user->name }}</div>
                            <div class="text-xs text-zinc-500">{{ $user->email }}@if($user->phone) · {{ $user->phone }}@endif</div>
                        </flux:table.cell>
                        <flux:table.cell><flux:badge size="sm" :color="$user->isAdmin() ? 'amber' : 'zinc'">{{ $user->role->label() }}</flux:badge></flux:table.cell>
                        <flux:table.cell class="max-w-xs text-xs text-zinc-500">
                            @if($user->isAdmin())
                                {{ __('All modules') }}
                            @else
                                {{ collect($user->permissions ?? [])->map(fn($p) => \App\Enums\Permission::from($p)->label())->join(', ') ?: __('None') }}
                            @endif
                        </flux:table.cell>
                        <flux:table.cell><flux:badge size="sm" :color="$user->is_active ? 'green' : 'red'">{{ $user->is_active ? __('Active') : __('Inactive') }}</flux:badge></flux:table.cell>
                        <flux:table.cell class="text-xs text-zinc-500">{{ $user->last_login_at?->format('d M Y, h:i A') ?? '—' }}</flux:table.cell>
                        <flux:table.cell class="text-end">
                            <flux:button wire:click="edit({{ $user->id }})" size="xs" icon="pencil-square" variant="ghost">{{ __('Edit') }}</flux:button>
                            @if($user->id !== auth()->id())
                                <flux:button wire:click="toggleActive({{ $user->id }})" wire:confirm="{{ $user->is_active ? __('Deactivate this user? They will be logged out immediately.') : __('Activate this user?') }}" size="xs" variant="ghost" :icon="$user->is_active ? 'no-symbol' : 'check-circle'">{{ $user->is_active ? __('Deactivate') : __('Activate') }}</flux:button>
                            @endif
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6"><x-empty-state icon="user-group" :title="__('No users found')" class="border-0" /></flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    <flux:modal name="user-form" class="w-full max-w-lg">
        <form wire:submit="save" class="space-y-5">
            <div>
                <flux:heading size="lg">{{ $editingId ? __('Edit user') : __('New user') }}</flux:heading>
                <flux:subheading>{{ __('Operators only see the modules you tick below. Administrators can do everything.') }}</flux:subheading>
            </div>

            <flux:input wire:model="name" :label="__('Full name')" required autofocus />
            <flux:input wire:model="email" :label="__('Email (login)')" type="email" required />
            <div class="grid grid-cols-2 gap-4">
                <flux:input wire:model="phone" :label="__('Phone')" />
                <flux:select wire:model.live="role" :label="__('Role')">
                    <flux:select.option value="operator">{{ __('Operator') }}</flux:select.option>
                    <flux:select.option value="admin">{{ __('Administrator') }}</flux:select.option>
                </flux:select>
            </div>

            @if($role === 'operator')
                <flux:fieldset>
                    <flux:legend>{{ __('Module permissions') }}</flux:legend>
                    <div class="grid gap-2 sm:grid-cols-2">
                        @foreach($allPermissions as $permission)
                            <flux:checkbox wire:model="permissions" :value="$permission->value" :label="$permission->label()" :description="$permission->description()" />
                        @endforeach
                    </div>
                    <flux:error name="permissions" />
                </flux:fieldset>
            @endif

            <flux:input wire:model="password" type="password" :label="$editingId ? __('New password (leave blank to keep)') : __('Password')" viewable autocomplete="new-password" />
            <flux:switch wire:model="is_active" :label="__('Active')" :description="__('Inactive users cannot log in.')" />

            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">{{ __('Cancel') }}</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary">{{ __('Save') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
