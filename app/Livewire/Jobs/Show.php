<?php

namespace App\Livewire\Jobs;

use App\Enums\JobStatus;
use App\Exceptions\DomainException;
use App\Models\PaymentMethod;
use App\Models\ServiceJob;
use App\Services\JobService;
use App\Services\PaymentService;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class Show extends Component
{
    public ServiceJob $job;

    public string $statusNote = '';

    public string $cancelNote = '';

    public string $payAmount = '';

    public ?int $payMethodId = null;

    public string $payReference = '';

    public function mount(ServiceJob $job): void
    {
        Gate::authorize('jobs');
        $this->job = $job;
        $this->payMethodId = PaymentMethod::query()->active()->value('id');
    }

    public function setStatus(string $status, JobService $jobs): void
    {
        Gate::authorize('jobs');
        $to = JobStatus::from($status);
        if ($to === JobStatus::Cancelled && trim($this->cancelNote) === '') {
            $this->addError('cancelNote', __('Give a reason for cancelling.'));

            return;
        }
        try {
            $jobs->changeStatus($this->job, $to, $to === JobStatus::Cancelled ? $this->cancelNote : ($this->statusNote ?: null));
        } catch (DomainException $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage());

            return;
        }
        $this->reset(['statusNote', 'cancelNote']);
        $this->job->refresh();
        Flux::modal('cancel-job')->close();
        Flux::toast(variant: 'success', text: __('Job marked :s.', ['s' => $to->label()]));
    }

    public function bill(JobService $jobs): void
    {
        Gate::authorize('billing');
        try {
            $invoice = $jobs->bill($this->job);
        } catch (DomainException $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage());

            return;
        }
        $this->job->refresh();
        $this->payAmount = (string) $invoice->balance_due;
        Flux::toast(variant: 'success', text: __('Invoice :n created.', ['n' => $invoice->invoice_number]));
    }

    public function recordPayment(PaymentService $payments): void
    {
        Gate::authorize('billing');
        $this->validate(['payAmount' => ['required', 'numeric', 'gt:0'], 'payMethodId' => ['required', 'exists:payment_methods,id']]);
        if (! $this->job->invoice) {
            Flux::toast(variant: 'danger', text: __('Bill the job first.'));

            return;
        }
        try {
            $payments->record($this->job->invoice, $this->payAmount, (int) $this->payMethodId, null, $this->payReference ?: null);
        } catch (DomainException $e) {
            $this->addError('payAmount', $e->getMessage());

            return;
        }
        $this->job->refresh();
        $this->reset(['payReference']);
        $this->payAmount = (string) $this->job->invoice->balance_due;
        Flux::modal('job-payment')->close();
        Flux::toast(variant: 'success', text: __('Payment recorded.'));
    }

    public function render(): View
    {
        $this->job->load(['customer', 'service', 'variant', 'assignee', 'creator', 'invoice.payments.method', 'histories.user']);

        return view('livewire.jobs.show', [
            'methods' => PaymentMethod::query()->active()->get(),
            'transitions' => $this->job->status->allowedTransitions(),
        ])->title($this->job->job_number);
    }
}
