<?php

namespace App\Livewire\Customers;

use App\Models\Customer;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Url;
use Livewire\Component;

class Show extends Component
{
    public Customer $customer;

    #[Url]
    public string $tab = 'invoices';

    public function mount(Customer $customer): void
    {
        Gate::authorize('customers');
        $this->customer = $customer;
    }

    public function render(): View
    {
        $customer = $this->customer;

        return view('livewire.customers.show', [
            'due' => $customer->outstandingDue(),
            'invoices' => $this->tab === 'invoices' ? $customer->invoices()->latest('invoice_date')->latest('id')->limit(100)->get() : collect(),
            'jobs' => $this->tab === 'jobs' ? $customer->jobs()->with('invoice')->latest('received_at')->limit(100)->get() : collect(),
            'payments' => $this->tab === 'payments' ? $customer->payments()->with(['invoice', 'method'])->latest('paid_at')->limit(100)->get() : collect(),
            'documents' => $this->tab === 'documents' ? $customer->documents()->latest()->get() : collect(),
            'stats' => [
                'invoices' => $customer->invoices()->count(),
                'jobs' => $customer->jobs()->count(),
                'billed' => $customer->invoices()->where('status', '!=', 'cancelled')->sum('total'),
            ],
        ])->title($customer->name);
    }
}
