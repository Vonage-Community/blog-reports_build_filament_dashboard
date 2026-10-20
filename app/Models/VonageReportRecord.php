<?php

namespace App\Models;

use App\Enums\VonageProduct;
use Database\Factories\VonageReportRecordFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'product',
    'external_id',
    'account_id',
    'direction',
    'channel',
    'from',
    'to',
    'status',
    'error_code',
    'error_description',
    'country',
    'country_name',
    'network_name',
    'total_price',
    'currency',
    'latency_ms',
    'duration_seconds',
    'occurred_at',
    'finalized_at',
    'payload',
])]
class VonageReportRecord extends Model
{
    /** @use HasFactory<VonageReportRecordFactory> */
    use HasFactory;

    /**
     * Statuses that represent a successful outcome across SMS, Messages and Voice.
     *
     * @var list<string>
     */
    public const SUCCESSFUL_STATUSES = ['delivered', 'read', 'completed', 'answered'];

    /**
     * Statuses that represent a failed outcome across SMS, Messages and Voice.
     *
     * @var list<string>
     */
    public const FAILED_STATUSES = ['failed', 'rejected', 'expired', 'undeliverable', 'busy', 'timeout', 'unanswered', 'cancelled'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'product' => VonageProduct::class,
            'total_price' => 'decimal:8',
            'latency_ms' => 'integer',
            'duration_seconds' => 'integer',
            'occurred_at' => 'datetime',
            'finalized_at' => 'datetime',
            'payload' => 'array',
        ];
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeFailed(Builder $query): void
    {
        $query->whereIn('status', self::FAILED_STATUSES);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeSuccessful(Builder $query): void
    {
        $query->whereIn('status', self::SUCCESSFUL_STATUSES);
    }

    public static function statusColor(?string $status): string
    {
        return match (true) {
            in_array($status, self::SUCCESSFUL_STATUSES, true) => 'success',
            in_array($status, self::FAILED_STATUSES, true) => 'danger',
            default => 'warning',
        };
    }
}
