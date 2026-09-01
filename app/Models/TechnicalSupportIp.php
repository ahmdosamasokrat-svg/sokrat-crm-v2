<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TechnicalSupportIp extends Model
{
    protected $fillable = [
        'device_key',
        'ip_address',
        'label',
        'created_by_user_id',
    ];

    public function scopeAccessibleTo(Builder $query, User $user): Builder
    {
        if ($user->isSuperAdmin()) {
            return $query;
        }

        return $query
            ->where('created_by_user_id', $user->getKey())
            ->whereHas(
                'device',
                static fn (Builder $deviceQuery): Builder => $deviceQuery
                    ->accessibleTo($user),
            );
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(TechnicalSupportDevice::class, 'device_key', 'device_key');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
