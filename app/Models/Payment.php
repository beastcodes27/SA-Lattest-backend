<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'organization_id',
    'user_id',
    'plan',
    'billing_cycle',
    'amount',
    'original_amount',
    'discount_amount',
    'currency',
    'mobile_provider',
    'phone_number',
    'reference',
    'external_transaction_id',
    'ussd_code',
    'status',
    'failure_reason',
    'metadata',
    'paid_at',
    'failed_at',
])]
class Payment extends Model
{
    public const PROVIDERS = [
        'mpesa' => 'M-Pesa (Vodacom)',
        'tigopesa' => 'Tigo Pesa (Yas)',
        'airtelmoney' => 'Airtel Money',
        'halopesa' => 'HaloPesa (Halotel)',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELED = 'canceled';

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'original_amount' => 'integer',
            'discount_amount' => 'integer',
            'metadata' => 'array',
            'paid_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function markAsCompleted(?string $externalId = null, ?array $additionalMeta = null): bool
    {
        $meta = $this->metadata ?? [];
        if ($additionalMeta) {
            $meta = array_merge($meta, $additionalMeta);
        }

        return $this->forceFill([
            'status' => self::STATUS_COMPLETED,
            'external_transaction_id' => $externalId ?? ($this->external_transaction_id ?? 'TXN-'.strtoupper(bin2hex(random_bytes(6)))),
            'paid_at' => now(),
            'metadata' => $meta,
        ])->save();
    }

    public function markAsFailed(string $reason = 'Payment transaction failed or timed out.'): bool
    {
        return $this->forceFill([
            'status' => self::STATUS_FAILED,
            'failure_reason' => $reason,
            'failed_at' => now(),
        ])->save();
    }

    public function getProviderNameAttribute(): string
    {
        return self::PROVIDERS[$this->mobile_provider] ?? ucfirst($this->mobile_provider);
    }

    public function getFormattedAmountAttribute(): string
    {
        return 'TZS '.number_format($this->amount);
    }
}
