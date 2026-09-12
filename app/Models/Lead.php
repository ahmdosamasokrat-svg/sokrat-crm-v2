<?php

namespace App\Models;

use App\Security\CrmPermission;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'lead_status_id',
        'name',
        'first_name',
        'last_name',
        'company_name',
        'activity',
        'governorate',
        'address',
        'users_count',
        'branches_count',
        'job_title',
        'disinterest_reason',
        'solution_type',
        'lines_count',
        'extensions',
        'departments',
        'quotation_file_path',
        'phone',
        'email',
        'source',
        'quotation_sent',
        'assigned_employee',
        'assigned_user_id',
        'created_by',
        'created_by_user_id',
        'notes',
        'custom_fields',
        'next_follow_up_at',
        'deleted_by_user_id',
        'deleted_from_stage_id',
        'deleted_reason',
    ];
    protected function casts(): array
    {
        return [
            'quotation_sent' => 'boolean',
            'users_count' => 'integer',
            'branches_count' => 'integer',
            'lines_count' => 'integer',
            'custom_fields' => 'array',
            'next_follow_up_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function scopeAccessibleTo(Builder $query, User $user): Builder
    {
        if (! $user->hasPermission(CrmPermission::LEADS_SCOPE_ALL)) {
            $groupIds = [];

            if ($user->hasPermission(CrmPermission::LEADS_SCOPE_GROUP)) {
                $user->loadMissing('groups');
                $groupIds = $user->groups->modelKeys();
            }

            $query->where(
                static function (Builder $accessQuery) use (
                    $user,
                    $groupIds,
                ): void {
                    $accessQuery
                        ->where('assigned_user_id', $user->getKey())
                        ->orWhere('created_by_user_id', $user->getKey());

                    if ($groupIds !== []) {
                        $accessQuery->orWhereHas(
                            'assignedUser.groups',
                            static fn (Builder $groupQuery): Builder => $groupQuery
                                ->whereKey($groupIds),
                        );
                    }
                },
            );
        }

        if ($user->hasRestrictedPipelineStageAccess()) {
            $query->whereHas(
                'status.stage.permittedUsers',
                static fn (Builder $users): Builder => $users->whereKey($user->getKey()),
            );
        }

        return $query;
    }

    public function isAccessibleTo(User $user): bool
    {
        return self::query()
            ->whereKey($this->getKey())
            ->accessibleTo($user)
            ->exists();
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(
            LeadStatus::class,
            'lead_status_id'
        );
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'assigned_user_id'
        );
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by_user_id'
        );
    }

    public function campaigns(): BelongsToMany
    {
        return $this->belongsToMany(Campaign::class)->withTimestamps();
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(
            LeadStatusHistory::class
        )->orderByDesc('changed_at');
    }

    public function followups(): HasMany
    {
        return $this->hasMany(
            LeadFollowup::class
        )->orderByDesc('followed_up_at');
    }

    public function stageValues(): HasMany
    {
        return $this->hasMany(LeadStageFieldValue::class, 'lead_id')
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }
    public function documents(): HasMany
    {
        return $this->hasMany(LeadDocument::class, 'lead_id')
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }
    public function deletedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by_user_id');
    }

    public function deletedFromStage(): BelongsTo
    {
        return $this->belongsTo(PipelineStage::class, 'deleted_from_stage_id');
    }


    public function currentStage(): ?PipelineStage
    {
        return $this->status?->stage;
    }
}
