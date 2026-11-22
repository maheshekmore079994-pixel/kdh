<?php

namespace App\Livewire\Configuration;

use App\Enums\PaymentMethodType;
use App\Models\ExpenseCategory;
use App\Models\PaymentMethod;
use App\Models\ProductCategory;
use App\Models\Unit;
use App\Services\AuditService;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Simple reference lists: payment methods, expense categories, product categories, units.
 */
#[Title('Lists & categories')]
class Lists extends Component
{
    #[Url]
    public string $tab = 'payment-methods';

    public ?int $editingId = null;

    public string $name = '';

    public string $code = '';

    public string $type = 'other';

    public bool $is_active = true;

    public function mount(): void
    {
        Gate::authorize('settings');
    }

    public function create(): void
    {
        Gate::authorize('settings');
        $this->resetForm();
        Flux::modal('list-form')->show();
    }

    public function edit(int $id): void
    {
        Gate::authorize('settings');
        $row = $this->modelClass()::query()->findOrFail($id);
        $this->resetForm();
        $this->editingId = $row->id;
        $this->name = $row->name;
        $this->code = $row->code ?? $row->abbreviation ?? '';
        $this->type = $row->type?->value ?? 'other';
        $this->is_active = (bool) $row->is_active;
        Flux::modal('list-form')->show();
    }

    public function save(AuditService $audit): void
    {
        Gate::authorize('settings');
        $class = $this->modelClass();
        $table = (new $class)->getTable();

        $rules = ['name' => ['required', 'string', 'max:100'], 'is_active' => ['boolean']];
        if ($this->tab === 'payment-methods') {
            $rules['type'] = ['required', Rule::enum(PaymentMethodType::class)];
            $rules['code'] = ['required', 'string', 'max:30', Rule::unique($table, 'code')->ignore($this->editingId)];
        }
        if ($this->tab === 'units') {
            $rules['code'] = ['required', 'string', 'max:10', Rule::unique($table, 'abbreviation')->ignore($this->editingId)];
        }
        $validated = $this->validate($rules);

        $data = ['name' => trim($validated['name']), 'is_active' => $validated['is_active']];
        if ($this->tab === 'payment-methods') {
            $data['code'] = Str::slug($validated['code']);
            $data['type'] = $validated['type'];
        }
        if ($this->tab === 'units') {
            $data['abbreviation'] = trim($validated['code']);
        }

        /** @var Model $row */
        $row = $this->editingId ? $class::query()->findOrFail($this->editingId) : new $class;
        $row->fill($data);
        $isNew = ! $row->exists;
        if ($isNew) {
            $row->save();
            $audit->log("list.{$this->tab}.created", $row, [], $data);
        } else {
            $audit->logChanges("list.{$this->tab}.updated", $row);
            $row->save();
        }

        Flux::modal('list-form')->close();
        $this->resetForm();
        Flux::toast(variant: 'success', text: __('Saved.'));
    }

    public function toggle(int $id, AuditService $audit): void
    {
        Gate::authorize('settings');
        $row = $this->modelClass()::query()->findOrFail($id);
        $row->is_active = ! $row->is_active;
        $audit->logChanges("list.{$this->tab}.toggled", $row);
        $row->save();
    }

    public function updatedTab(): void
    {
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'code', 'type', 'is_active']);
        $this->resetValidation();
    }

    /** @return class-string<Model> */
    private function modelClass(): string
    {
        return match ($this->tab) {
            'expense-categories' => ExpenseCategory::class,
            'product-categories' => ProductCategory::class,
            'units' => Unit::class,
            default => PaymentMethod::class,
        };
    }

    public function render(): View
    {
        $class = $this->modelClass();
        $query = $class::query();
        $rows = $this->tab === 'units' ? $query->orderBy('name')->get() : $query->orderBy('sort_order')->orderBy('name')->get();

        return view('livewire.configuration.lists', [
            'rows' => $rows,
            'tabs' => [
                'payment-methods' => __('Payment methods'),
                'expense-categories' => __('Expense categories'),
                'product-categories' => __('Product categories'),
                'units' => __('Units'),
            ],
            'types' => PaymentMethodType::cases(),
        ]);
    }
}
