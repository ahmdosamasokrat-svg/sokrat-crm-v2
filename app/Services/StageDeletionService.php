<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Lead;
use App\Models\LeadStatus;
use App\Models\PipelineStage;
use App\Models\PipelineStageField;
use App\Models\User;
use App\Support\CrmDatabaseGuard;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class StageDeletionService
{
    /**
     * Safely delete a pipeline stage with explicit lead resolution.
     *
     * @param  PipelineStage  $stage
     * @param  User  $actor
     * @param  array{
     *     lead_action?: 'move'|'trash',
     *     destination_stage_id?: int|string|null,
     *     destination_status_id?: int|string|null,
     *     replacement_default_stage_id?: int|string|null
     * }  $options
     * @return array{
     *     success: bool,
     *     stage_id: int,
     *     stage_name: string,
     *     leads_count: int,
     *     action: string,
     *     destination_stage_id: ?int,
     *     destination_stage_name: ?string
     * }
     *
     * @throws ValidationException
     * @throws RuntimeException
     */
    public function deleteStage(PipelineStage $stage, User $actor, array $options = []): array
    {
        CrmDatabaseGuard::ensureConnected();

        return DB::transaction(function () use ($stage, $actor, $options): array {
            // 1. Lock the stage for update
            /** @var PipelineStage $lockedStage */
            $lockedStage = PipelineStage::query()
                ->lockForUpdate()
                ->findOrFail($stage->id);

            // 2. Check if stage is protected/system
            if ($lockedStage->isSystem()) {
                throw ValidationException::withMessages([
                    'stage' => 'المراحل المحمية للنظام لا يمكن حذفها.',
                ]);
            }

            // 3. Ensure at least one other active stage remains in the CRM
            $otherActiveCount = PipelineStage::query()
                ->where('is_active', true)
                ->whereNull('deleted_at')
                ->where('id', '!=', $lockedStage->id)
                ->count();

            if ($otherActiveCount === 0) {
                throw ValidationException::withMessages([
                    'stage' => 'لا يمكن حذف هذه المرحلة لأنها المرحلة النشطة الوحيدة المتبقية في النظام.',
                ]);
            }

            // 4. Check if deleting the default stage used for new leads
            $isDefaultStage = $lockedStage->isDefault()
                || ((int) (PipelineStage::getDefaultStage()?->id ?? 0) === (int) $lockedStage->id);

            if ($isDefaultStage) {
                $replacementDefaultId = $options['replacement_default_stage_id'] ?? null;
                if (empty($replacementDefaultId)) {
                    throw ValidationException::withMessages([
                        'replacement_default_stage_id' => 'هذه المرحلة هي المرحلة الافتراضية للعملاء الجدد. يرجى اختيار مرحلة بديلة لتكون الافتراضية قبل الحذف.',
                    ]);
                }

                $replacementDefaultStage = PipelineStage::query()
                    ->whereNull('deleted_at')
                    ->where('is_active', true)
                    ->where('id', '!=', $lockedStage->id)
                    ->find((int) $replacementDefaultId);

                if ($replacementDefaultStage === null) {
                    throw ValidationException::withMessages([
                        'replacement_default_stage_id' => 'المرحلة الافتراضية البديلة المختارة غير صحيحة أو غير نشطة.',
                    ]);
                }

                // Promote replacement stage as default
                PipelineStage::query()
                    ->whereNull('deleted_at')
                    ->where('is_default', true)
                    ->update(['is_default' => false]);

                $replacementDefaultStage->update(['is_default' => true]);
            }

            // 5. Calculate and lock active leads currently in this stage
            $stageStatusIds = $lockedStage->statuses()
                ->pluck('id')
                ->all();

            $leadQuery = Lead::query()
                ->whereIn('lead_status_id', $stageStatusIds)
                ->whereNull('deleted_at')
                ->lockForUpdate();

            // Fetch stable array of immutable Lead IDs to prevent chunking/offset drift
            $allLeadIds = (clone $leadQuery)->pluck('id')->all();
            $leadsCount = count($allLeadIds);

            $leadAction = $options['lead_action'] ?? null;
            $destinationStage = null;

            // 6. If stage contains leads, resolution is required
            if ($leadsCount > 0) {
                if (! in_array($leadAction, ['move', 'trash'], true)) {
                    throw ValidationException::withMessages([
                        'lead_action' => 'هذه المرحلة تحتوي على عملاء. يرجى اختيار الإجراء المطلوب (نقل العملاء أو نقلهم إلى سلة المهملات).',
                    ]);
                }

                if ($leadAction === 'move') {
                    $destinationStageId = $options['destination_stage_id'] ?? null;
                    if (empty($destinationStageId)) {
                        throw ValidationException::withMessages([
                            'destination_stage_id' => 'يرجى اختيار المرحلة المراد نقل العملاء إليها.',
                        ]);
                    }

                    if ((int) $destinationStageId === (int) $lockedStage->id) {
                        throw ValidationException::withMessages([
                            'destination_stage_id' => 'لا يمكن اختيار نفس المرحلة المراد حذفها كمرحلة هدف.',
                        ]);
                    }

                    $destinationStage = PipelineStage::query()
                        ->whereNull('deleted_at')
                        ->where('is_active', true)
                        ->find((int) $destinationStageId);

                    if ($destinationStage === null) {
                        throw ValidationException::withMessages([
                            'destination_stage_id' => 'المرحلة الهدف المختارة غير موجودة أو غير نشطة.',
                        ]);
                    }

                    // Resolve destination status
                    $destinationStatusId = $options['destination_status_id'] ?? null;
                    $destinationStatus = null;
                    if (! empty($destinationStatusId)) {
                        $destinationStatus = LeadStatus::query()
                            ->where('pipeline_stage_id', $destinationStage->id)
                            ->whereNull('deleted_at')
                            ->find((int) $destinationStatusId);
                    }
                    if ($destinationStatus === null) {
                        $destinationStatus = $destinationStage->ensureDefaultStatus();
                    }

                    // Perform safe batch migration of leads (using stable IDs)
                    $movedCount = $this->batchMoveLeads(
                        $allLeadIds,
                        $destinationStatus,
                        $lockedStage,
                        $actor
                    );

                    // Row count assertion: verify all expected leads were moved
                    if ($movedCount !== $leadsCount) {
                        throw new RuntimeException("Stage deletion failed: expected to move {$leadsCount} leads, but only {$movedCount} were moved. Transaction rolled back.");
                    }
                } elseif ($leadAction === 'trash') {
                    // Perform safe batch soft-deletion of leads to trash (using stable IDs)
                    $trashedCount = $this->batchTrashLeads(
                        $allLeadIds,
                        $lockedStage,
                        $actor
                    );

                    // Row count assertion: verify all expected leads were soft-deleted
                    if ($trashedCount !== $leadsCount) {
                        throw new RuntimeException("Stage deletion failed: expected to trash {$leadsCount} leads, but only {$trashedCount} were trashed. Transaction rolled back.");
                    }

                    // Post-trash verification before committing
                    $verifiedTrashCount = Lead::onlyTrashed()
                        ->where('deleted_from_stage_id', $lockedStage->id)
                        ->whereIn('id', $allLeadIds)
                        ->count();

                    if ($verifiedTrashCount !== $leadsCount) {
                        throw new RuntimeException("Stage deletion post-check failed: expected {$leadsCount} trashed leads in DB, found {$verifiedTrashCount}. Transaction rolled back.");
                    }
                }
            }

            // 7. Soft-delete the stage (booted hook soft-deletes child LeadStatus and PipelineStageField)
            $lockedStage->delete();

            // 8. Re-normalize positions of remaining stages to contiguous 1..N
            $allRemaining = PipelineStage::query()
                ->whereNull('deleted_at')
                ->orderBy('position')
                ->orderBy('id')
                ->get();

            $pos = 1;
            foreach ($allRemaining as $st) {
                if ((int) $st->position !== $pos) {
                    $st->update(['position' => $pos]);
                }
                $pos++;
            }

            // 9. Invalidate caches immediately
            PipelineMappingService::clearCache();
            PipelineStage::clearSidebarCache();
            Cache::forget(PipelineStage::DASHBOARD_CACHE_KEY);
            Cache::forget(PipelineMappingService::DASHBOARD_LEGACY_KEY);

            return [
                'success' => true,
                'stage_id' => $lockedStage->id,
                'stage_name' => $lockedStage->name_ar,
                'leads_count' => $leadsCount,
                'action' => $leadsCount > 0 ? (string) $leadAction : 'empty_delete',
                'destination_stage_id' => $destinationStage?->id,
                'destination_stage_name' => $destinationStage?->name_ar,
            ];
        });
    }

    /**
     * Batch move leads using stable IDs while preserving audit history.
     *
     * @param  list<int>  $leadIds
     * @param  LeadStatus  $destinationStatus
     * @param  PipelineStage  $sourceStage
     * @param  User  $actor
     * @return int
     */
    private function batchMoveLeads(
        array $leadIds,
        LeadStatus $destinationStatus,
        PipelineStage $sourceStage,
        User $actor
    ): int {
        if (empty($leadIds)) {
            return 0;
        }

        $chunkSize = 500;
        $actorName = trim((string) $actor->name) ?: 'System';
        $now = now();
        $historyNote = 'نقل جماعي بسبب حذف المرحلة (' . $sourceStage->name_ar . ')';
        $totalMoved = 0;

        foreach (array_chunk($leadIds, $chunkSize) as $chunk) {
            // Read current lead statuses for audit history before update
            $leadPairs = DB::table('leads')
                ->whereIn('id', $chunk)
                ->select(['id', 'lead_status_id'])
                ->get();

            // 1. Bulk update status on leads
            $affected = DB::table('leads')
                ->whereIn('id', $chunk)
                ->update([
                    'lead_status_id' => $destinationStatus->id,
                    'updated_at' => $now,
                ]);

            $totalMoved += $affected;

            // 2. Bulk insert LeadStatusHistory entries
            $historyRows = [];
            foreach ($leadPairs as $row) {
                $historyRows[] = [
                    'lead_id' => $row->id,
                    'from_status_id' => $row->lead_status_id,
                    'to_status_id' => $destinationStatus->id,
                    'changed_by' => $actorName,
                    'changed_by_user_id' => $actor->id ?? null,
                    'note' => $historyNote,
                    'changed_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if (! empty($historyRows)) {
                DB::table('lead_status_histories')->insert($historyRows);
            }
        }

        return $totalMoved;
    }

    /**
     * Batch soft-delete leads into Lead Trash using stable IDs.
     *
     * @param  list<int>  $leadIds
     * @param  PipelineStage  $sourceStage
     * @param  User  $actor
     * @return int
     */
    private function batchTrashLeads(
        array $leadIds,
        PipelineStage $sourceStage,
        User $actor
    ): int {
        if (empty($leadIds)) {
            return 0;
        }

        $chunkSize = 500;
        $now = now();
        $reason = 'stage_deleted: ' . $sourceStage->name_ar;
        $totalTrashed = 0;

        foreach (array_chunk($leadIds, $chunkSize) as $chunk) {
            $affected = DB::table('leads')
                ->whereIn('id', $chunk)
                ->whereNull('deleted_at')
                ->update([
                    'deleted_at' => $now,
                    'deleted_by_user_id' => $actor->id ?? null,
                    'deleted_from_stage_id' => $sourceStage->id,
                    'deleted_reason' => $reason,
                    'updated_at' => $now,
                ]);

            $totalTrashed += $affected;
        }

        return $totalTrashed;
    }
}
