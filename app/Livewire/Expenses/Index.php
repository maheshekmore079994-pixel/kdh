<?php

namespace App\Livewire\Expenses;

use App\Exceptions\DomainException;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\PaymentMethod;
use App\Services\ExpenseService;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Expenses')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $category = '';

    #[Url]
    public string $from = '';

    #[Url]
    public string $to = '';

    public ?int $editingId = null;

    public string $expenseDate = '';

    public ?int $categoryId = null;

    public string $description = '';

    public string $amount = '';

    public ?int $methodId = null;

    public string $vendor = '';

    public string $notes = '';

    public ?int $deletingId = null;

    public string $deleteReason = '';

    public ?int $viewingId = null;

    public function mount(): void
    {
        Gate::authorize('expenses');
        if (request()->boolean('new')) {
            $this->create();
        }
    }

    public function updated($name): void
    {
        if (in_array($name, ['search', 'category', 'from', 'to'], true)) {
            $this->resetPage();
        }
    }

    public function create(): void
    {
        Gate::authorize('expenses');
        $this->resetForm();
        $this->expenseDate = now()->toDateString();
        $this->categoryId = ExpenseCategory::query()->active()->value('id');
        $this->methodId = PaymentMethod::query()->active()->value('id');
        Flux::modal('expense-form')->show();
    }

    public function edit(int $id): void
    {
        Gate::authorize('expenses');
        $e = Expense::query()->findOrFail($id);
        $this->resetForm();
        $this->editingId = $e->id;
        $this->expenseDate = $e->expense_date->toDateString();
        $this->categoryId = $e->expense_category_id;
        $this->description = $e->description;
        $this->amount = (string) $e->amount;
        $this->methodId = $e->payment_method_id;
        $this->vendor = (string) $e->vendor;
        $this->notes = (string) $e->notes;
        Flux::modal('expense-form')->show();
    }

    public function save(ExpenseService $expenses): void
    {
        Gate::authorize('expenses');
        $v = $this->validate([
            'expenseDate' => ['required', 'date', 'before_or_equal:today'],
            'categoryId' => ['required', 'exists:expense_categories,id'],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'methodId' => ['required', 'exists:payment_methods,id'],
            'vendor' => ['nullable', 'string', 'max:150'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $data = [
            'expense_date' => $v['expenseDate'], 'expense_category_id' => $v['categoryId'], 'description' => $v['description'],
            'amount' => $v['amount'], 'payment_method_id' => $v['methodId'], 'vendor' => $v['vendor'] ?: null, 'notes' => $v['notes'] ?: null,
        ];

        try {
            $this->editingId ? $expenses->update(Expense::query()->findOrFail($this->editingId), $data) : $expenses->create($data);
        } catch (DomainException $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage());

            return;
        }

        Flux::modal('expense-form')->close();
        $this->resetForm();
        Flux::toast(variant: 'success', text: __('Expense saved.'));
    }

    public function startDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->deleteReason = '';
        $this->resetValidation();
        Flux::modal('expense-delete')->show();
    }

    public function delete(ExpenseService $expenses): void
    {
        Gate::authorize('expenses');
        $this->validate(['deleteReason' => ['required', 'string', 'min:3', 'max:500']]);
        $expenses->delete(Expense::query()->findOrFail($this->deletingId), $this->deleteReason);
        Flux::modal('expense-delete')->close();
        $this->deletingId = null;
        Flux::toast(variant: 'success', text: __('Expense deleted (kept in audit log).'));
    }

    public function view(int $id): void
    {
        $this->viewingId = $id;
        Flux::modal('expense-view')->show();
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'expenseDate', 'categoryId', 'description', 'amount', 'methodId', 'vendor', 'notes']);
        $this->resetValidation();
    }

    public function render(): View
    {
        $query = Expense::query()->with(['category', 'method', 'creator'])
            ->search($this->search)
            ->when($this->category, fn ($q) => $q->where('expense_category_id', $this->category))
            ->when($this->from, fn ($q) => $q->whereDate('expense_date', '>=', $this->from))
            ->when($this->to, fn ($q) => $q->whereDate('expense_date', '<=', $this->to))
            ->latest('expense_date')->latest('id');

        return view('livewire.expenses.index', [
            'expenses' => $query->paginate(20),
            'total' => (clone $query)->sum('amount'),
            'categories' => ExpenseCategory::query()->active()->get(),
            'methods' => PaymentMethod::query()->active()->get(),
            'viewing' => $this->viewingId ? Expense::query()->with(['category', 'method', 'creator'])->find($this->viewingId) : null,
        ]);
    }
}
