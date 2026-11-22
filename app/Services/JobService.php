<?php

namespace App\Services;

use App\Enums\JobStatus;
use App\Exceptions\DomainException;
use App\Models\Invoice;
use App\Models\JobStatusHistory;
use App\Models\ServiceJob;
use App\Support\Money;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class JobService
{
    public function __construct(
        private readonly NumberingService $numbering,
        private readonly InvoiceService $invoices,
        private readonly AuditService $audit,
    ) {}

    /**
     * @param  array{customer_id: int, service_id?: int|null, service_variant_id?: int|null, title: string, description?: string|null, quantity: string|float, unit_price: string|float, assigned_to?: int|null, received_at?: string|null, expected_at?: string|null, notes?: string|null}  $data
     */
    public function create(array $data): ServiceJob
    {
        return DB::transaction(function () use ($data) {
            $job = ServiceJob::query()->create([
                'job_number' => $this->numbering->next('job'),
                'customer_id' => $data['customer_id'],
                'service_id' => $data['service_id'] ?? null,
                'service_variant_id' => $data['service_variant_id'] ?? null,
                'title' => trim($data['title']),
                'description' => $data['description'] ?? null,
                'quantity' => number_format((float) ($data['quantity'] ?: 1), 3, '.', ''),
                'unit_price' => Money::of($data['unit_price'] ?? 0),
                'total_amount' => Money::mul((float) ($data['quantity'] ?: 1), (float) ($data['unit_price'] ?? 0)),
                'assigned_to' => $data['assigned_to'] ?? null,
                'received_at' => $data['received_at'] ?? now(),
                'expected_at' => $data['expected_at'] ?? null,
                'status' => JobStatus::Pending,
                'notes' => $data['notes'] ?? null,
                'created_by' => Auth::id(),
            ]);

            JobStatusHistory::query()->create([
                'service_job_id' => $job->id, 'from_status' => null, 'to_status' => JobStatus::Pending->value,
                'user_id' => Auth::id(), 'note' => 'Job received', 'created_at' => now(),
            ]);

            $this->audit->log('job.created', $job, [], ['job_number' => $job->job_number, 'customer_id' => $job->customer_id, 'total_amount' => $job->total_amount]);

            return $job;
        });
    }

    public function update(ServiceJob $job, array $data): ServiceJob
    {
        if ($job->status === JobStatus::Delivered || $job->status === JobStatus::Cancelled) {
            throw new DomainException(__('Delivered or cancelled jobs cannot be edited.'));
        }

        return DB::transaction(function () use ($job, $data) {
            $job->fill([
                'customer_id' => $data['customer_id'],
                'service_id' => $data['service_id'] ?? null,
                'service_variant_id' => $data['service_variant_id'] ?? null,
                'title' => trim($data['title']),
                'description' => $data['description'] ?? null,
                'quantity' => number_format((float) ($data['quantity'] ?: 1), 3, '.', ''),
                'unit_price' => Money::of($data['unit_price'] ?? 0),
                'total_amount' => Money::mul((float) ($data['quantity'] ?: 1), (float) ($data['unit_price'] ?? 0)),
                'assigned_to' => $data['assigned_to'] ?? null,
                'received_at' => $data['received_at'] ?? $job->received_at,
                'expected_at' => $data['expected_at'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            if ($job->invoice && ($job->isDirty('total_amount') || $job->isDirty('customer_id'))) {
                throw new DomainException(__('This job is already billed (:n). Cancel the invoice before changing the amount or customer.', ['n' => $job->invoice->invoice_number]));
            }

            $this->audit->logChanges('job.updated', $job);
            $job->save();

            return $job;
        });
    }

    public function changeStatus(ServiceJob $job, JobStatus $to, ?string $note = null): ServiceJob
    {
        if (! $job->status->canTransitionTo($to)) {
            throw new DomainException(__('A :from job cannot be moved to :to.', ['from' => $job->status->label(), 'to' => $to->label()]));
        }

        return DB::transaction(function () use ($job, $to, $note) {
            $from = $job->status;
            $job->status = $to;
            $job->delivered_at = $to === JobStatus::Delivered ? now() : ($to === JobStatus::Cancelled ? null : $job->delivered_at);
            $job->save();

            JobStatusHistory::query()->create([
                'service_job_id' => $job->id, 'from_status' => $from->value, 'to_status' => $to->value,
                'user_id' => Auth::id(), 'note' => $note, 'created_at' => now(),
            ]);

            $this->audit->log('job.status', $job, ['status' => $from->value], ['status' => $to->value], $note);

            return $job;
        });
    }

    /** Create the invoice for a job (one per job) using the job's quantity and price. */
    public function bill(ServiceJob $job): Invoice
    {
        if ($job->invoice_id && $job->invoice && ! $job->invoice->isCancelled()) {
            throw new DomainException(__('This job is already billed (:n).', ['n' => $job->invoice->invoice_number]));
        }
        if ($job->status === JobStatus::Cancelled) {
            throw new DomainException(__('Cancelled jobs cannot be billed.'));
        }
        if (Money::cmp($job->total_amount, 0) <= 0) {
            throw new DomainException(__('Set a price on the job before billing it.'));
        }

        return DB::transaction(function () use ($job) {
            $invoice = $this->invoices->create([
                'customer_id' => $job->customer_id,
                'service_job_id' => $job->id,
                'notes' => __('Job :n', ['n' => $job->job_number]),
            ], [[
                'type' => 'service',
                'service_id' => $job->service_id,
                'service_variant_id' => $job->service_variant_id,
                'description' => $job->title,
                'quantity' => $job->quantity,
                'unit' => $job->service?->unit ?? 'pcs',
                'unit_price' => $job->unit_price,
            ]]);

            $job->invoice_id = $invoice->id;
            $job->save();

            return $invoice;
        });
    }
}
