<?php

namespace App\Livewire\Customers;

use App\Models\Customer;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Modelable;
use Livewire\Component;

/**
 * Reusable customer search + quick-create. Emits the selected id through wire:model.
 */
class Picker extends Component
{
    #[Modelable]
    public ?int $customerId = null;

    public string $search = '';

    public bool $creating = false;

    public string $newName = '';

    public string $newMobile = '';

    public bool $required = false;

    public function select(int $id): void
    {
        $this->customerId = $id;
        $this->search = '';
        $this->creating = false;
    }

    public function clear(): void
    {
        $this->customerId = null;
        $this->search = '';
    }

    public function startCreate(): void
    {
        $this->creating = true;
        if (preg_match('/^\d{10}$/', $this->search)) {
            $this->newMobile = $this->search;
        } else {
            $this->newName = $this->search;
        }
    }

    public function create(): void
    {
        $validated = $this->validate([
            'newName' => ['required', 'string', 'max:150'],
            'newMobile' => ['required', 'regex:/^[6-9]\d{9}$/'],
        ], ['newMobile.regex' => __('Enter a valid 10-digit Indian mobile number.')]);

        $existing = Customer::query()->where('mobile', $validated['newMobile'])->first();
        if ($existing) {
            $this->select($existing->id);
            $this->dispatch('toast', text: __('A customer with this mobile already exists and was selected.'));

            return;
        }

        $customer = Customer::query()->create([
            'name' => trim($validated['newName']),
            'mobile' => $validated['newMobile'],
            'created_by' => Auth::id(),
        ]);

        $this->reset(['newName', 'newMobile', 'creating']);
        $this->select($customer->id);
    }

    public function render(): View
    {
        $results = strlen($this->search) >= 2 && ! $this->customerId
            ? Customer::query()->search($this->search)->orderBy('name')->limit(8)->get()
            : collect();

        return view('livewire.customers.picker', [
            'results' => $results,
            'selected' => $this->customerId ? Customer::query()->find($this->customerId) : null,
        ]);
    }
}
