<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Campaign;
use App\Models\Lead;
use App\Models\LeadFollowup;
use App\Models\LeadStatus;
use App\Models\LeadStatusHistory;
use App\Models\PipelineStage;
use App\Models\User;
use App\Support\StageFieldSchema;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LeadTransitionService
{
    /**
     * Perform a centralized Lead status transition inside an atomic database transaction.
     *
     * @param  Lead  $lead
     * @param  LeadStatus  $toStatus
     * @param  User  $actor
     * @param  array<string, mixed>  $context
     * @return array{
     *     lead: Lead,
     *     history: ?LeadStatusHistory,
     *     followup: ?LeadFollowup,
     *     stage_field_values: \Illuminate\Database\Eloquent\Collection,
     *     changed: bool
     * }
     *
     * @throws ValidationException
     */
    public function transition(
        Lead $lead,
        LeadStatus $toStatus,
        User $actor,
        array $context = []
    ): array {
        return DB::transaction(function () use ($lead, $toStatus, $actor, $context): array {
            /** @var Lead $lockedLead */
            $lockedLead = Lead::query()
                ->lockForUpdate()
                ->findOrFail($lead->getKey());

            $fromStatus = $lockedLead->status ?? LeadStatus::query()->find($lockedLead->lead_status_id);
            $fromStatusId = $fromStatus?->id ?? (int) $lockedLead->lead_status_id;
            $toStatusId = (int) $toStatus->id;
            $statusChanged = $fromStatusId !== $toStatusId;

            // 1. Destination stage resolution
            $toStage = $toStatus->stage ?? PipelineStage::query()->find($toStatus->pipeline_stage_id);

            if ($toStage === null || ! $actor->canAccessPipelineStage($toStage)) {
                throw ValidationException::withMessages([
                    'lead_status_id' => 'ليس لديك صلاحية نقل العميل إلى هذه المرحلة.',
                ]);
            }

            // 2. Validate stage field schema and extract normalized stage values
            $normalizedStageValues = [];
            if ($toStage !== null) {
                $rawStageInputs = isset($context['stage_fields']) && is_array($context['stage_fields'])
                    ? $context['stage_fields']
                    : [];

                // Backward compatibility mapping for canonical rules if field exists on stage
                $stageFields = StageFieldSchema::getFieldsForStage($toStage, true);
                $stageFieldKeys = $stageFields->pluck('key')->all();

                if (in_array('callback_at', $stageFieldKeys, true) && ! isset($rawStageInputs['callback_at']) && ! empty($context['next_follow_up_at'])) {
                    $rawStageInputs['callback_at'] = $context['next_follow_up_at'] instanceof Carbon
                        ? $context['next_follow_up_at']->toDateTimeString()
                        : (string) $context['next_follow_up_at'];
                }

                if (in_array('reason', $stageFieldKeys, true) && ! isset($rawStageInputs['reason'])) {
                    $disReason = (string) ($context['disinterest_reason'] ?? $context['outcome'] ?? '');
                    if ($disReason !== '') {
                        $reasonField = $stageFields->firstWhere('key', 'reason');
                        $opts = $reasonField ? $reasonField->normalizedOptions() : [];
                        $optVals = array_column($opts, 'value');
                        $optLabelsAr = array_column($opts, 'label_ar');
                        $optLabelsEn = array_column($opts, 'label_en');
                        $allAllowed = array_merge($optVals, $optLabelsAr, $optLabelsEn);

                        if (in_array($disReason, $allAllowed, true) || empty($optVals)) {
                            $rawStageInputs['reason'] = $disReason;
                        } elseif (in_array('other', $optVals, true)) {
                            $rawStageInputs['reason'] = 'other';
                        } else {
                            $rawStageInputs['reason'] = $optVals[0] ?? $disReason;
                        }
                    }
                }

                try {
                    $normalizedStageValues = StageFieldSchema::validateAndExtract($toStage, $rawStageInputs, $actor);
                } catch (ValidationException $e) {
                    $errors = $e->errors();
                    if (isset($errors['callback_at']) && ! isset($errors['next_follow_up_at'])) {
                        $errors['next_follow_up_at'] = $errors['callback_at'];
                    }
                    if (isset($errors['reason']) && ! isset($errors['disinterest_reason'])) {
                        $errors['disinterest_reason'] = $errors['reason'];
                    }
                    throw ValidationException::withMessages($errors);
                }
            }

            // 3. Business Rule Validation
            $this->validateTransitionRules($lockedLead, $fromStatus, $toStatus, $context);

            // 4. Resolve next_follow_up_at based on business rules and stage fields
            $nextFollowUpAt = $this->resolveNextFollowUpAt($toStatus, $context, $lockedLead, $normalizedStageValues);

            // 5. Prepare Lead attributes for update (including dynamic canonical stage fields)
            $splitValues = $toStage !== null ? StageFieldSchema::splitValues($toStage, $normalizedStageValues) : ['canonical' => [], 'custom' => []];
            $canonicalUpdates = $splitValues['canonical'];

            $leadUpdateData = [
                'lead_status_id' => $toStatusId,
                'next_follow_up_at' => $nextFollowUpAt,
            ];

            // Apply validated canonical stage fields (Type A)
            foreach ($canonicalUpdates as $cAttr => $cVal) {
                if (! in_array($cAttr, ['id', 'created_at', 'updated_at', 'lead_status_id'], true)) {
                    $leadUpdateData[$cAttr] = $cVal;
                }
            }

            if (array_key_exists('assigned_user_id', $context)) {
                $leadUpdateData['assigned_user_id'] = $context['assigned_user_id'];
            }
            if (array_key_exists('assigned_employee', $context)) {
                $leadUpdateData['assigned_employee'] = $context['assigned_employee'];
            }
            if (array_key_exists('disinterest_reason', $context)) {
                $leadUpdateData['disinterest_reason'] = $context['disinterest_reason'];
            } elseif (! empty($normalizedStageValues['reason'])) {
                $leadUpdateData['disinterest_reason'] = (string) $normalizedStageValues['reason'];
            }
            if (array_key_exists('notes', $context) && $context['notes'] !== null) {
                $leadUpdateData['notes'] = $context['notes'];
            }

            // Additional lead attributes if passed in context (e.g. direct lead edit)
            if (! empty($context['lead_attributes']) && is_array($context['lead_attributes'])) {
                foreach ($context['lead_attributes'] as $attrKey => $attrVal) {
                    if (! in_array($attrKey, ['id', 'created_at', 'updated_at'], true)) {
                        $leadUpdateData[$attrKey] = $attrVal;
                    }
                }
                // Ensure lead_status_id and next_follow_up_at resolved take precedence
                $leadUpdateData['lead_status_id'] = $toStatusId;
                $leadUpdateData['next_follow_up_at'] = $nextFollowUpAt;
            }

            // Track canonical field changes for audit and followups
            $canonicalFieldChanges = [];
            foreach ($canonicalUpdates as $cAttr => $cVal) {
                $oldVal = $lockedLead->getAttribute($cAttr);
                $oldStr = $oldVal === null ? '' : trim((string) $oldVal);
                $newStr = $cVal === null ? '' : trim((string) $cVal);
                if ($oldStr !== $newStr) {
                    $cfg = \App\Models\PipelineStageField::CANONICAL_FIELDS[$cAttr] ?? null;
                    $label = $cfg ? (app()->getLocale() === 'en' ? $cfg['label_en'] : $cfg['label_ar']) : $cAttr;
                    $canonicalFieldChanges[] = [
                        'field' => $cAttr,
                        'label' => $label,
                        'old' => $oldStr !== '' ? $oldStr : '—',
                        'new' => $newStr !== '' ? $newStr : '—',
                    ];
                }
            }

            $lockedLead->update($leadUpdateData);

            // Handle campaign association if provided
            if (isset($context['campaign']) && $context['campaign'] instanceof Campaign) {
                $lockedLead->campaigns()->sync([$context['campaign']->id]);
            } elseif (! empty($context['campaign_id'])) {
                $lockedLead->campaigns()->sync([(int) $context['campaign_id']]);
            }

            // 6. Create LeadFollowup record if follow-up context is provided
            $followupRecord = null;
            $hasFollowupContext = ! empty($context['record_followup'])
                || ! empty($context['communication_type'])
                || ! empty($context['outcome']);

            if ($hasFollowupContext) {
                $communicationType = (string) ($context['communication_type'] ?? 'other');
                $outcome = isset($context['outcome']) ? (string) $context['outcome'] : null;
                $employeeName = ! empty($context['employee_name'])
                    ? (string) $context['employee_name']
                    : (trim((string) $actor->name) ?: 'System');

                $followupData = [
                    'lead_id' => $lockedLead->id,
                    'from_status_id' => $fromStatusId,
                    'to_status_id' => $toStatusId,
                    'user_id' => $actor->id ?? null,
                    'employee_name' => $employeeName,
                    'communication_type' => $communicationType,
                    'outcome' => $outcome,
                    'next_follow_up_at' => $nextFollowUpAt,
                    'followed_up_at' => ! empty($context['followed_up_at'])
                        ? ($context['followed_up_at'] instanceof Carbon ? $context['followed_up_at'] : Carbon::parse($context['followed_up_at']))
                        : now(),
                ];

                $providedChanges = (isset($context['field_changes']) && is_array($context['field_changes']))
                    ? $context['field_changes']
                    : [];
                $mergedChanges = array_merge($providedChanges, $canonicalFieldChanges);
                if (! empty($mergedChanges)) {
                    $followupData['field_changes'] = $mergedChanges;
                }

                $followupRecord = LeadFollowup::query()->create($followupData);
            }

            // 7. Create LeadStatusHistory atomically if status changed or force_history requested
            $historyRecord = null;
            if ($statusChanged || ! empty($context['force_history'])) {
                $actorName = trim((string) $actor->name) ?: 'System';
                $defaultNote = $statusChanged
                    ? 'تغيير الحالة إلى ' . $toStatus->localizedName()
                    : 'تحديث حالة العميل';

                $historyNote = $context['history_note'] ?? $defaultNote;

                $historyRecord = LeadStatusHistory::query()->create([
                    'lead_id' => $lockedLead->id,
                    'from_status_id' => $fromStatusId,
                    'to_status_id' => $toStatusId,
                    'changed_by' => $actorName,
                    'changed_by_user_id' => $actor->id ?? null,
                    'note' => $historyNote,
                    'changed_at' => now(),
                ]);
            }

            // 8. Persist LeadStageFieldValue records atomically within the same transaction
            $savedStageValues = collect();
            if ($toStage !== null && ! empty($normalizedStageValues)) {
                $savedStageValues = StageFieldSchema::persistValues(
                    $lockedLead,
                    $toStage,
                    $normalizedStageValues,
                    $historyRecord,
                    $actor
                );
            }

            return [
                'lead' => $lockedLead->fresh(['status.stage', 'assignedUser', 'stageValues']),
                'history' => $historyRecord,
                'followup' => $followupRecord,
                'stage_field_values' => $savedStageValues,
                'changed' => $statusChanged,
            ];
        });
    }

    /**
     * Centralized transition validation rules.
     *
     * @throws ValidationException
     */
    protected function validateTransitionRules(
        Lead $lead,
        ?LeadStatus $fromStatus,
        LeadStatus $toStatus,
        array $context
    ): void {
        // Enforce not_interested reason requirement if legacy field is checked
        if ($toStatus->code === 'not_interested') {
            $reason = trim((string) ($context['disinterest_reason'] ?? $context['stage_fields']['reason'] ?? ''));
            if ($reason === '') {
                // If there's a reason question configured on stage, StageFieldSchema handles it.
                // If not, enforce legacy disinterest_reason rule
                $stage = $toStatus->stage;
                $hasReasonField = $stage && StageFieldSchema::getFieldsForStage($stage, true)->where('key', 'reason')->isNotEmpty();
                if (! $hasReasonField) {
                    throw ValidationException::withMessages([
                        'disinterest_reason' => [__('crm.reason_required') ?: 'يجب تسجيل السبب عند اختيار غير مهتم.'],
                    ]);
                }
            }
        }
    }

    /**
     * Resolve the appropriate next_follow_up_at datetime.
     */
    protected function resolveNextFollowUpAt(
        LeadStatus $toStatus,
        array $context,
        Lead $lead,
        array $normalizedStageValues = []
    ): ?Carbon {
        // 1. If explicit callback_at provided in stage fields, use it
        if (! empty($normalizedStageValues['callback_at'])) {
            try {
                return Carbon::parse($normalizedStageValues['callback_at']);
            } catch (\Throwable) {
                // continue to fallback
            }
        }

        // 2. If explicit next_follow_up_at provided in context
        if (array_key_exists('next_follow_up_at', $context)) {
            $raw = $context['next_follow_up_at'];
            if ($raw === null || $raw === '') {
                return null;
            }
            if ($raw instanceof Carbon) {
                return $raw;
            }
            try {
                return Carbon::parse((string) $raw);
            } catch (\Throwable) {
                return null;
            }
        }

        // 3. Status-specific semantic rules
        if ($toStatus->code === 'not_interested') {
            return null;
        }

        // 4. Default: preserve existing next_follow_up_at
        return $lead->next_follow_up_at ? Carbon::parse($lead->next_follow_up_at) : null;
    }
}
