<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TechnicalSupportTicket extends Model
{
    public const STATUS_OPEN = 'open';

    public const STATUS_CLOSED = 'closed';

    public const SUPPORT_TIME_WARNING_SECONDS = 10 * 60;

    public const SUPPORT_TIME_CRITICAL_SECONDS = 30 * 60;

    protected $fillable = [
        'device_key',
        'subject',
        'description',
        'status',
        'resolution',
        'opened_by_user_id',
        'closed_by_user_id',
        'opened_at',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function scopeAccessibleTo(Builder $query, User $user): Builder
    {
        if ($user->isSuperAdmin()
            || $user->hasPermission('technical_support.view')
            || $user->hasPermission('technical_support.manage')) {
            return $query;
        }

        return $query->where(
            static fn (Builder $accessQuery): Builder => $accessQuery
                ->where('opened_by_user_id', $user->getKey())
                ->orWhere('closed_by_user_id', $user->getKey()),
        );
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(TechnicalSupportDevice::class, 'device_key', 'device_key');
    }

    public function openedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by_user_id');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by_user_id');
    }

    public function supportTimeSeconds(): ?int
    {
        if ($this->opened_at === null) {
            return null;
        }

        return (int) abs($this->opened_at->diffInSeconds($this->closed_at ?? now()));
    }

    public function supportTimeBand(?int $seconds = null): string
    {
        $seconds ??= $this->supportTimeSeconds();

        if ($seconds === null) {
            return 'untracked';
        }

        if ($seconds > self::SUPPORT_TIME_CRITICAL_SECONDS) {
            return 'critical';
        }

        if ($seconds >= self::SUPPORT_TIME_WARNING_SECONDS) {
            return 'warning';
        }

        return 'fast';
    }
}
