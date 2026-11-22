<?php

namespace App\Models;

use App\Enums\JobStatus;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceJob extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'service_jobs';

    protected $fillable = [
        'job_number', 'customer_id', 'service_id', 'service_variant_id', 'title', 'description', 'quantity',
        'unit_price', 'total_amount', 'assigned_to', 'received_at', 'expected_at', 'delivered_at', 'status',
        'notes', 'invoice_id', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'unit_price' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'received_at' => 'datetime',
            'expected_at' => 'datetime',
            'delivered_at' => 'datetime',
            'status' => JobStatus::class,
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ServiceVariant::class, 'service_variant_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(JobStatusHistory::class)->orderBy('id');
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function paymentStatus(): ?PaymentStatus
    {
        if (! $this->invoice) {
            return null;
        }

        return PaymentStatus::fromAmounts($this->invoice->paid_amount, $this->invoice->total);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        return $query->where(fn (Builder $q) => $q
            ->where('job_number', 'like', "%{$term}%")
            ->orWhere('title', 'like', "%{$term}%")
            ->orWhereHas('customer', fn (Builder $c) => $c
                ->where('name', 'like', "%{$term}%")
                ->orWhere('mobile', 'like', "%{$term}%")));
    }
}
