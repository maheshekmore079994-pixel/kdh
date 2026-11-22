<?php

namespace App\Livewire\Jobs;

use App\Exceptions\DomainException;
use App\Models\Service;
use App\Models\ServiceJob;
use App\Models\User;
use App\Services\JobService;
use App\Support\Money;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class Form extends Component
{
    public ?ServiceJob $job = null;

    public ?int $customerId = null;

    public ?int $serviceId = null;

    public ?int $variantId = null;

    public string $title = '';

    public string $description = '';

    public string $quantity = '1';

    public string $unitPrice = '';

    public ?int $assignedTo = null;

    public string $receivedAt = '';

    public string $expectedAt = '';

    public string $notes = '';

    public function mount(?ServiceJob $job = null): void
    {
        Gate::authorize('jobs');

        if ($job?->exists) {
            $this->job = $job;
            $this->customerId = $job->customer_id;
            $this->serviceId = $job->service_id;
            $this->variantId = $job->service_variant_id;
            $this->title = $job->title;
            $this->description = (string) $job->description;
            $this->quantity = qty($job->quantity);
            $this->unitPrice = (string) $job->unit_price;
            $this->assignedTo = $job->assigned_to;
            $this->receivedAt = $job->received_at->format('Y-m-d\TH:i');
            $this->expectedAt = $job->expected_at?->format('Y-m-d\TH:i') ?? '';
            $this->notes = (string) $job->notes;
        } else {
            $this->customerId = request()->integer('customer') ?: null;
            $this->receivedAt = now()->format('Y-m-d\TH:i');
            $this->assignedTo = auth()->id();
        }
    }

    public function updatedServiceId(): void
    {
        $this->variantId = null;
        $service = $this->serviceId ? Service::query()->with('variants')->find($this->serviceId) : null;
        if (! $service) {
            return;
        }
        if ($this->title === '' || Service::query()->where('name', $this->title)->exists()) {
            $this->title = $service->name;
        }
        if ($service->variants->isEmpty() && $service->default_price !== null) {
            $this->unitPrice = (string) $service->default_price;
        }
    }

    public function updatedVariantId(): void
    {
        $service = $this->serviceId ? Service::query()->with('variants')->find($this->serviceId) : null;
        $variant = $service?->variants->firstWhere('id', $this->variantId);
        if ($variant) {
            $this->unitPrice = (string) $variant->unit_price;
            $this->title = $service->name.' – '.$variant->name;
            if (bccomp($variant->min_quantity, (string) ((float) $this->quantity ?: 0), 3) > 0) {
                $this->quantity = qty($variant->min_quantity);
            }
        }
    }

    public function save(JobService $jobs): void
    {
        Gate::authorize('jobs');

        $v = $this->validate([
            'customerId' => ['required', 'exists:customers,id'],
            'serviceId' => ['nullable', 'exists:services,id'],
            'variantId' => ['nullable', 'exists:service_variants,id'],
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'unitPrice' => ['required', 'numeric', 'min:0'],
            'assignedTo' => ['nullable', 'exists:users,id'],
            'receivedAt' => ['required', 'date'],
            'expectedAt' => ['nullable', 'date', 'after_or_equal:receivedAt'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], ['customerId.required' => __('A customer is required for a job.')]);

        $data = [
            'customer_id' => $v['customerId'],
            'service_id' => $v['serviceId'],
            'service_variant_id' => $v['variantId'],
            'title' => $v['title'],
            'description' => $v['description'] ?: null,
            'quantity' => $v['quantity'],
            'unit_price' => $v['unitPrice'],
            'assigned_to' => $v['assignedTo'],
            'received_at' => $v['receivedAt'],
            'expected_at' => $v['expectedAt'] ?: null,
            'notes' => $v['notes'] ?: null,
        ];

        try {
            $job = $this->job ? $jobs->update($this->job, $data) : $jobs->create($data);
        } catch (DomainException $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage());

            return;
        }

        Flux::toast(variant: 'success', text: $this->job ? __('Job updated.') : __('Job :n created.', ['n' => $job->job_number]));
        $this->redirectRoute('jobs.show', $job, navigate: true);
    }

    public function render(): View
    {
        $service = $this->serviceId ? Service::query()->with('variants')->find($this->serviceId) : null;

        return view('livewire.jobs.form', [
            'services' => Service::query()->active()->with('category')->orderBy('sort_order')->orderBy('name')->get(),
            'variants' => $service?->variants->where('is_active', true) ?? collect(),
            'operators' => User::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'total' => Money::mul((float) ($this->quantity ?: 0), (float) ($this->unitPrice ?: 0)),
        ])->title($this->job ? __('Edit job') : __('New job'));
    }
}
