<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\PipelineStage;
use App\Models\User;
use App\Security\CrmPermission;

class PipelineStagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(CrmPermission::SETTINGS_ACCESS);
    }

    public function delete(User $user, ?PipelineStage $stage = null): bool
    {
        return $user->hasPermission(CrmPermission::PIPELINE_STAGES_DELETE);
    }
}
