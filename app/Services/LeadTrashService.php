<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Lead;
use App\Models\LeadStatus;
use App\Models\LeadStatusHistory;
use App\Models\PipelineStage;
use App\Models\User;
use App\Security\CrmPermission;
use App\Support\CrmDatabaseGuard;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class LeadTrashService
{
    /**
     * Soft-delete a lead to trash with auditable metadata.
     *
     * @param  Lead  $lead
     * @param  User  $actor
     * @param  string|null  $reason
     * @return bool
     */
    public function trashLead(Lead $lead, User $actor, ?string $reason = null): bool
    {
        CrmDatabaseGuard::ensureConnected();

        $stageId = $lead->currentStage()?->id;

        $lead->deleted_by_user_id = $actor->id ?? null;
        $lead->deleted_from_stage_id = $stageId;
        $lead->deleted_reason = $reason ?? 'manual_delete';
        $lead->save();

        return (bool) $lead->delete();
    }

    /**
     * Restore a lead from trash.
     * If the original stage/status was deleted, a destination stage must be resolved.
     *
     * @param  Lead  $lead
     * @param  User  $actor
     * @param  int|null  $destinationStageId
     * @param  int|null  $destinationStatusId
     * @return Lead
     *
     * @throws ValidationException
     */
    public function restoreLead(
        Lead $lead,
        User $actor,
        ?int $destinationStageId = null,
        ?int $destinationStatusId = null
    ): Lead {
        CrmDatabaseGuard::ensureConnected();

        return DB::transaction(function () use ($lead, $actor, $destinationStageId, $destinationStatusId): Lead {
            /** @var Lead $lockedLead */
            $lockedLead = Lead::onlyTrashed()
                ->lockForUpdate()
                ->findOrFail($lead->id);

            // Check if original status and stage exist and are active
            $originalStatus = LeadStatus::query()
                ->whereNull('deleted_at')
                ->with('stage')
                ->find($lockedLead->lead_status_id);

            $originalStageValid = $originalStatus !== null
                && $originalStatus->stage !== null
                && (bool) $originalStatus->stage->is_active
                && $originalStatus->stage->deleted_at === null;

            $resolvedStatus = $originalStatus;
            $statusChanged = false;

            if ($destinationStageId !== null) {
                // Admin explicitly requested a destination stage
                $targetStage = PipelineStage::query()
                    ->whereNull('deleted_at')
                    ->where('is_active', true)
                    ->find($destinationStageId);

                if ($targetStage === null) {
                    throw ValidationException::withMessages([
                        'destination_stage_id' => 'المرحلة المختارة للاستعادة غير موجودة أو غير نشطة.',
                    ]);
                }

                if ($destinationStatusId !== null) {
                    $resolvedStatus = LeadStatus::query()
                        ->where('pipeline_stage_id', $targetStage->id)
                        ->whereNull('deleted_at')
                        ->find($destinationStatusId);
                }

                if ($resolvedStatus === null || (int) $resolvedStatus->pipeline_stage_id !== (int) $targetStage->id) {
                    $resolvedStatus = $targetStage->ensureDefaultStatus();
                }

                $statusChanged = (int) $lockedLead->lead_status_id !== (int) $resolvedStatus->id;
            } elseif (! $originalStageValid) {
                // Original stage no longer exists or is inactive -> fallback to default active stage
                $defaultStage = PipelineStage::getDefaultStage();
                if ($defaultStage === null) {
                    throw ValidationException::withMessages([
                        'destination_stage_id' => 'لا توجد مرحلة نشطة متاحة لاستعادة العميل إليها.',
                    ]);
                }

                $resolvedStatus = $defaultStage->ensureDefaultStatus();
                $statusChanged = true;
            }

            $actorName = trim((string) $actor->name) ?: 'System';
            $now = now();

            if ($statusChanged && $resolvedStatus !== null) {
                $targetStageName = $resolvedStatus->stage?->name_ar ?? 'المرحلة البديلة';
                $historyNote = 'استعادة العميل من سلة المهملات ونقله إلى مرحلة (' . $targetStageName . ') بواسطة ' . $actorName;

                DB::table('lead_status_histories')->insert([
                    'lead_id' => $lockedLead->id,
                    'from_status_id' => $lockedLead->lead_status_id,
                    'to_status_id' => $resolvedStatus->id,
                    'changed_by' => $actorName,
                    'changed_by_user_id' => $actor->id ?? null,
                    'note' => $historyNote,
                    'changed_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $lockedLead->lead_status_id = $resolvedStatus->id;
            } else {
                DB::table('lead_status_histories')->insert([
                    'lead_id' => $lockedLead->id,
                    'from_status_id' => $lockedLead->lead_status_id,
                    'to_status_id' => $lockedLead->lead_status_id,
                    'changed_by' => $actorName,
                    'changed_by_user_id' => $actor->id ?? null,
                    'note' => 'استعادة العميل من سلة المهملات بواسطة ' . $actorName,
                    'changed_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            // Restore the lead and clear trash metadata
            $lockedLead->deleted_at = null;
            $lockedLead->deleted_by_user_id = null;
            $lockedLead->deleted_from_stage_id = null;
            $lockedLead->deleted_reason = null;
            $lockedLead->save();

            return $lockedLead;
        });
    }

    /**
     * Permanently force-delete a lead from trash.
     *
     * @param  Lead  $lead
     * @param  User  $actor
     * @return bool
     */
    public function forceDeleteLead(Lead $lead, User $actor): bool
    {
        CrmDatabaseGuard::ensureConnected();

        return DB::transaction(function () use ($lead): bool {
            /** @var Lead $lockedLead */
            $lockedLead = Lead::onlyTrashed()
                ->lockForUpdate()
                ->findOrFail($lead->id);

            // Clean up files
            $quotationPath = trim((string) $lockedLead->quotation_file_path);
            if ($quotationPath !== '' && Storage::disk('local')->exists($quotationPath)) {
                Storage::disk('local')->delete($quotationPath);
            }

            return (bool) $lockedLead->forceDelete();
        });
    }

    /**
     * Build the query for Lead Trash with scoping and filters.
     *
     * @param  User  $actor
     * @param  array<string, mixed>  $filters
     * @return Builder
     */
    public function getTrashQuery(User $actor, array $filters = []): Builder
    {
        $query = Lead::onlyTrashed()
            ->with([
                'status.stage',
                'assignedUser:id,name',
                'deletedByUser:id,name',
                'deletedFromStage',
            ])
            ->accessibleTo($actor)
            ->orderByDesc('deleted_at');

        if (! empty($filters['search'])) {
            $term = trim((string) $filters['search']);
            $query->where(static function (Builder $q) use ($term): void {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('company_name', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%");
            });
        }

        if (! empty($filters['stage_id'])) {
            $stageId = (int) $filters['stage_id'];
            $query->where(static function (Builder $q) use ($stageId): void {
                $q->where('deleted_from_stage_id', $stageId)
                    ->orWhereHas('status', static fn (Builder $sq) => $sq->withTrashed()->where('pipeline_stage_id', $stageId));
            });
        }
        if (! empty($filters['deleted_by'])) {
            $query->where('deleted_by_user_id', (int) $filters['deleted_by']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('deleted_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('deleted_at', '<=', $filters['date_to']);
        }

        return $query;
    }

    /**
     * Count accessible trashed leads.
     *
     * @param  User  $actor
     * @return int
     */
    public function getTrashCount(User $actor): int
    {
        return Lead::onlyTrashed()
            ->accessibleTo($actor)
            ->count();
    }
}
