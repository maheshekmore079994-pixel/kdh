<?php

namespace App\Services;

use App\Exceptions\DomainException;
use App\Models\Expense;
use App\Support\Money;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ExpenseService
{
    public function __construct(
        private readonly NumberingService $numbering,
        private readonly AuditService $audit,
    ) {}

    public function create(array $data): Expense
    {
        if (Money::cmp($data['amount'] ?? 0, 0) <= 0) {
            throw new DomainException(__('Expense amount must be greater than zero.'));
        }

        return DB::transaction(function () use ($data) {
            $expense = Expense::query()->create([
                'expense_number' => $this->numbering->next('expense'),
                'expense_date' => $data['expense_date'],
                'expense_category_id' => $data['expense_category_id'],
                'description' => trim($data['description']),
                'amount' => Money::of($data['amount']),
                'payment_method_id' => $data['payment_method_id'],
                'vendor' => $data['vendor'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => Auth::id(),
            ]);

            $this->audit->log('expense.created', $expense, [], ['expense_number' => $expense->expense_number, 'amount' => $expense->amount, 'description' => $expense->description]);

            return $expense;
        });
    }

    public function update(Expense $expense, array $data): Expense
    {
        if (Money::cmp($data['amount'] ?? 0, 0) <= 0) {
            throw new DomainException(__('Expense amount must be greater than zero.'));
        }

        return DB::transaction(function () use ($expense, $data) {
            $expense->fill([
                'expense_date' => $data['expense_date'],
                'expense_category_id' => $data['expense_category_id'],
                'description' => trim($data['description']),
                'amount' => Money::of($data['amount']),
                'payment_method_id' => $data['payment_method_id'],
                'vendor' => $data['vendor'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);
            $this->audit->logChanges('expense.updated', $expense);
            $expense->save();

            return $expense;
        });
    }

    /** Soft delete with a mandatory reason (kept for audit). */
    public function delete(Expense $expense, string $reason): void
    {
        if (trim($reason) === '') {
            throw new DomainException(__('A reason is required to delete an expense.'));
        }

        DB::transaction(function () use ($expense, $reason) {
            $expense->deleted_reason = $reason;
            $expense->deleted_by = Auth::id();
            $expense->save();
            $this->audit->log('expense.deleted', $expense, ['amount' => $expense->amount, 'description' => $expense->description], [], $reason);
            $expense->delete();
        });
    }
}
