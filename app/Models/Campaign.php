<?php

declare(strict_types=1);

namespace App\Models;

use App\Security\CrmPermission;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable([
    'name',
    'image_path',
    'cost',
    'starts_at',
    'ends_at',
    'created_by_user_id',
])]
class Campaign extends Model
{
    protected function casts(): array
    {
        return [
            'cost' => 'decimal:2',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public function leads(): BelongsToMany
    {
        return $this->belongsToMany(Lead::class)->withTimestamps();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function isActive(): bool
    {
        $now = now();

        return ($this->starts_at === null || $this->starts_at <= $now)
            && ($this->ends_at === null || $this->ends_at >= $now);
    }

    public function isUpcoming(): bool
    {
        return $this->starts_at !== null && $this->starts_at > now();
    }

    public function isEnded(): bool
    {
        return $this->ends_at !== null && $this->ends_at < now();
    }

    public function scopeActive(Builder $query, ?\DateTimeInterface $date = null): Builder
    {
        $target = $date ?? now();

        return $query
            ->where(static function (Builder $q) use ($target): void {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', $target);
            })
            ->where(static function (Builder $q) use ($target): void {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', $target);
            });
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isSuperAdmin()) {
            return $query;
        }

        return $query->where(static function (Builder $q) use ($user): void {
            $q->whereHas(
                'users',
                static fn (Builder $uq) => $uq->whereKey($user->id),
            );

            if ($user->hasPermission(CrmPermission::CAMPAIGNS_CREATE) || $user->hasPermission('campaigns.create')) {
                $q->orWhere('created_by_user_id', $user->id);
            }
        });
    }
}
