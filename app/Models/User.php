<?php

namespace App\Models;

use App\Security\CrmPermission;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'name',
    'username',
    'email',
    'voip_extension',
    'locale',
    'timezone',
    'mobile_phone',
    'whatsapp_opt_in_at',
    'password',
    'is_active',
    'pipeline_stage_access_mode',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'whatsapp_opt_in_at' => 'datetime',
        ];
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class);
    }

    public function campaigns(): BelongsToMany
    {
        return $this->belongsToMany(Campaign::class)->withTimestamps();
    }

    public function pipelineStages(): BelongsToMany
    {
        return $this->belongsToMany(PipelineStage::class);
    }

    public function hasRestrictedPipelineStageAccess(): bool
    {
        return ! $this->isSuperAdmin()
            && $this->pipeline_stage_access_mode === 'selected';
    }

    public function canAccessPipelineStage(PipelineStage|int $stage): bool
    {
        if (! $this->hasRestrictedPipelineStageAccess()) {
            return true;
        }

        $stageId = $stage instanceof PipelineStage
            ? $stage->getKey()
            : $stage;

        if ($this->relationLoaded('pipelineStages')) {
            return $this->pipelineStages->contains('id', (int) $stageId);
        }

        return $this->pipelineStages()->whereKey($stageId)->exists();
    }

    public function assignedLeads(): HasMany
    {
        return $this->hasMany(
            Lead::class,
            'assigned_user_id',
        );
    }

    public function createdLeads(): HasMany
    {
        return $this->hasMany(
            Lead::class,
            'created_by_user_id',
        );
    }

    public function notificationPreference(): HasOne
    {
        return $this->hasOne(NotificationPreference::class);
    }

    public function notificationOccurrences(): HasMany
    {
        return $this->hasMany(NotificationOccurrence::class, 'recipient_user_id');
    }

    public function pushSubscriptions(): HasMany
    {
        return $this->hasMany(PushSubscription::class);
    }

    public function isSuperAdmin(): bool
    {
        $this->loadMissing('groups');

        return $this->groups->contains(
            'code',
            Group::SUPER_ADMIN_CODE,
        );
    }

    public function hasPermission(
        CrmPermission|string $permission,
    ): bool {
        if (! $this->is_active) {
            return false;
        }

        if ($this->isSuperAdmin()) {
            return true;
        }

        $code = $permission instanceof CrmPermission
            ? $permission->value
            : $permission;

        $this->loadMissing('groups.permissions');

        return $this->groups->contains(
            static fn (Group $group): bool => $group->permissions
                ->contains('code', $code),
        );
    }
}
