<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TechnicalSupportDevice extends Model
{
    protected $fillable = [
        'device_key',
        'name',
        'company_name',
        'image_path',
        'dns_name',
        'os',
        'employees_count',
        'lines_count',
        'is_online',
        'notes',
        'is_custom',
        'is_hidden',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'is_online' => 'boolean',
            'is_custom' => 'boolean',
            'is_hidden' => 'boolean',
            'employees_count' => 'integer',
            'lines_count' => 'integer',
        ];
    }

    public function scopeAccessibleTo(Builder $query, User $user): Builder
    {
        if ($user->isSuperAdmin()) {
            return $query;
        }

        return $query->where('created_by_user_id', $user->getKey());
    }

    public function isAccessibleTo(User $user): bool
    {
        return self::query()
            ->whereKey($this->getKey())
            ->accessibleTo($user)
            ->exists();
    }

    public function ips(): HasMany
    {
        return $this->hasMany(TechnicalSupportIp::class, 'device_key', 'device_key');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(TechnicalSupportTicket::class, 'device_key', 'device_key');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
