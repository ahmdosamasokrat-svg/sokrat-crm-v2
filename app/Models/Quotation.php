<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Quotation extends Model
{
    protected $fillable = [
        'quotation_no',
        'client_name',
        'location',
        'prepared_by',
        'quote_date',
        'system_title',
        'grand_total',
        'payload',
        'created_by',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'quote_date' => 'date',
            'grand_total' => 'decimal:2',
            'payload' => 'array',
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by_user_id'
        );
    }
}
