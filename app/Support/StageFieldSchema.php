<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Lead;
use App\Models\LeadStageFieldValue;
use App\Models\LeadStatusHistory;
use App\Models\PipelineStage;
use App\Models\PipelineStageField;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StageFieldSchema
{
    private static array $memoizedFields = [];

    /**
     * Resolve fields for a pipeline stage with per-request memoization.
     *
     * @return Collection<int, PipelineStageField>
     */
    public static function getFieldsForStage(PipelineStage|int $stage, bool $onlyActive = true): Collection
    {
        $stageId = $stage instanceof PipelineStage ? (int) $stage->id : (int) $stage;
        $memoKey = $stageId . '_' . ($onlyActive ? '1' : '0');

        if (isset(self::$memoizedFields[$memoKey])) {
            return self::$memoizedFields[$memoKey];
        }

        $cacheKey = PipelineStageField::CACHE_KEY_PREFIX . $stageId . ($onlyActive ? '' : '_all');

        $fields = Cache::remember(
            $cacheKey,
            now()->addHours(12),
            static function () use ($stageId, $onlyActive) {
                $query = PipelineStageField::query()
                    ->where('pipeline_stage_id', $stageId);

                if ($onlyActive) {
                    $query->where('is_active', true);
                }

                return $query->orderBy('position')->orderBy('id')->get();
            }
        );

        self::$memoizedFields[$memoKey] = $fields;

        return $fields;
    }

    /**
     * Clear cached fields for a stage.
     */
    public static function flushCache(int $stageId): void
    {
        self::$memoizedFields = [];
        PipelineStageField::flushCache($stageId);
    }

    /**
     * Normalize options input into a standard format:
     * [['value' => '...', 'label_ar' => '...', 'label_en' => '...']]
     */
    public static function normalizeOptions(mixed $options): array
    {
        if (empty($options)) {
            return [];
        }

        if (is_string($options)) {
            $decoded = json_decode($options, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $options = $decoded;
            } else {
                $options = preg_split('/[\r\n,]+/', $options);
            }
        }

        if (! is_array($options)) {
            return [];
        }

        $normalized = [];
        foreach ($options as $item) {
            if (is_string($item)) {
                $val = trim($item);
                if ($val !== '') {
                    $normalized[] = [
                        'value' => $val,
                        'label_ar' => $val,
                        'label_en' => $val,
                    ];
                }
            } elseif (is_array($item)) {
                $val = trim((string) ($item['value'] ?? $item['key'] ?? ''));
                $labelAr = trim((string) ($item['label_ar'] ?? $item['label'] ?? $val));
                $labelEn = trim((string) ($item['label_en'] ?? $labelAr));

                if ($val !== '') {
                    $normalized[] = [
                        'value' => $val,
                        'label_ar' => $labelAr !== '' ? $labelAr : $val,
                        'label_en' => $labelEn !== '' ? $labelEn : $labelAr,
                    ];
                }
            }
        }

        return $normalized;
    }

    /**
     * Evaluate a conditional rule against submitted or current data.
     */
    public static function evaluateCondition(?array $condition, array $data): bool
    {
        if (empty($condition) || empty($condition['field'])) {
            return true;
        }

        $fieldKey = (string) $condition['field'];
        $operator = (string) ($condition['operator'] ?? 'equals');
        $expected = $condition['value'] ?? null;

        $actual = $data[$fieldKey] ?? null;

        switch ($operator) {
            case 'equals':
                return (string) $actual === (string) $expected;

            case 'not_equals':
                return (string) $actual !== (string) $expected;

            case 'is_checked':
                return filter_var($actual, FILTER_VALIDATE_BOOLEAN) === true;

            case 'is_not_checked':
                return filter_var($actual, FILTER_VALIDATE_BOOLEAN) === false;

            case 'is_empty':
                if (is_array($actual)) {
                    return empty(array_filter($actual, static fn ($v) => $v !== null && $v !== ''));
                }
                return $actual === null || $actual === '';

            case 'is_not_empty':
                if (is_array($actual)) {
                    return ! empty(array_filter($actual, static fn ($v) => $v !== null && $v !== ''));
                }
                return $actual !== null && $actual !== '';

            default:
                return true;
        }
    }

    /**
     * Build Laravel validation rules for active stage fields.
     */
    public static function buildValidationRules(
        PipelineStage|int $stage,
        array $submittedValues = [],
        string $prefix = 'stage_fields.'
    ): array {
        $fields = self::getFieldsForStage($stage, true);
        $rules = [];

        foreach ($fields as $field) {
            $fieldName = $prefix . $field->key;
            $fieldRules = [];

            $isApplicable = self::evaluateCondition($field->conditions, $submittedValues);

            if (! $isApplicable) {
                $rules[$fieldName] = ['nullable'];
                continue;
            }

            if ($field->is_required) {
                if ($field->type === 'checkbox') {
                    $fieldRules[] = 'accepted';
                } else {
                    $fieldRules[] = 'required';
                }
            } else {
                $fieldRules[] = 'nullable';
            }

            switch ($field->type) {
                case 'text':
                    $fieldRules[] = 'string';
                    $fieldRules[] = 'max:255';
                    break;

                case 'textarea':
                    $fieldRules[] = 'string';
                    $fieldRules[] = 'max:5000';
                    break;

                case 'number':
                    $fieldRules[] = 'numeric';
                    break;

                case 'email':
                    $fieldRules[] = 'email';
                    $fieldRules[] = 'max:255';
                    break;

                case 'tel':
                    $fieldRules[] = 'string';
                    $fieldRules[] = 'max:50';
                    break;

                case 'url':
                    $fieldRules[] = 'url';
                    $fieldRules[] = 'max:500';
                    break;

                case 'date':
                    $fieldRules[] = 'date_format:Y-m-d';
                    break;

                case 'datetime':
                    $fieldRules[] = static function (string $attribute, mixed $value, \Closure $fail): void {
                        if ($value === null || $value === '') {
                            return;
                        }
                        if ($value instanceof Carbon) {
                            return;
                        }
                        try {
                            Carbon::parse((string) $value);
                        } catch (\Throwable) {
                            $fail(__('validation.date', ['attribute' => $attribute]));
                        }
                    };
                    break;

                case 'select':
                    $options = $field->normalizedOptions();
                    $allowedValues = array_column($options, 'value');
                    if (! empty($allowedValues)) {
                        $fieldRules[] = Rule::in($allowedValues);
                    } else {
                        $fieldRules[] = 'string';
                    }
                    break;

                case 'multiselect':
                    $fieldRules[] = 'array';
                    $options = $field->normalizedOptions();
                    $allowedValues = array_column($options, 'value');
                    if (! empty($allowedValues)) {
                        $rules[$fieldName . '.*'] = [Rule::in($allowedValues)];
                    }
                    break;

                case 'checkbox':
                    $fieldRules[] = 'boolean';
                    break;
            }

            if (! empty($field->validation_rules) && is_array($field->validation_rules)) {
                $fieldRules = array_merge($fieldRules, $field->validation_rules);
            }

            $rules[$fieldName] = $fieldRules;
        }

        return $rules;
    }

    /**
     * Build custom attribute labels for validation error messages.
     */
    public static function buildValidationAttributes(
        PipelineStage|int $stage,
        string $prefix = 'stage_fields.',
        ?string $locale = null
    ): array {
        $fields = self::getFieldsForStage($stage, true);
        $attributes = [];

        foreach ($fields as $field) {
            $attributes[$prefix . $field->key] = $field->localizedLabel($locale);
        }

        return $attributes;
    }

    /**
     * Validate and extract stage fields from incoming raw request input.
     *
     * @throws ValidationException
     */
    public static function validateAndExtract(
        PipelineStage|int $stage,
        array $rawInput,
        ?User $actor = null
    ): array {
        $activeFields = self::getFieldsForStage($stage, true);
        $stageModel = $stage instanceof PipelineStage ? $stage : PipelineStage::query()->find($stage);
        $stageId = $stageModel?->id ?? (int) $stage;

        $submitted = isset($rawInput['stage_fields']) && is_array($rawInput['stage_fields'])
            ? $rawInput['stage_fields']
            : $rawInput;

        // Merge any UploadedFile objects passed directly in rawInput
        foreach ($rawInput as $rKey => $rVal) {
            if ($rVal instanceof \Illuminate\Http\UploadedFile && ! isset($submitted[$rKey])) {
                $submitted[$rKey] = $rVal;
            }
        }

        // If files are present in request, merge them into submitted array for stage fields
        if (request()->hasFile('stage_fields')) {
            $stageFiles = request()->file('stage_fields');
            if (is_array($stageFiles)) {
                foreach ($stageFiles as $fKey => $fileObj) {
                    if ($fileObj !== null) {
                        $submitted[$fKey] = $fileObj;
                    }
                }
            }
        }

        // Backward compatibility: If direct quotation_file uploaded, map to quotation field if present
        $directQuotationFile = (isset($rawInput['quotation_file']) && $rawInput['quotation_file'] instanceof \Illuminate\Http\UploadedFile)
            ? $rawInput['quotation_file']
            : (request()->hasFile('quotation_file') ? request()->file('quotation_file') : null);

        if ($directQuotationFile !== null) {
            $qField = $activeFields->first(fn ($f) => $f->key === 'quotation_file' || ($f->options['document_category'] ?? null) === 'quotation');
            if ($qField !== null && (! isset($submitted[$qField->key]) || ! ($submitted[$qField->key] instanceof \Illuminate\Http\UploadedFile))) {
                $submitted[$qField->key] = $directQuotationFile;
            }
        }

        // Backward compatibility: If direct solution_type passed, map to solution_type field if present
        if (! isset($submitted['solution_type'])) {
            $solVal = $rawInput['solution_type'] ?? request()->input('solution_type');
            if ($solVal !== null && $solVal !== '') {
                $solField = $activeFields->firstWhere('key', 'solution_type');
                if ($solField !== null) {
                    $submitted['solution_type'] = $solVal;
                }
            }
        }

        // Backward compatibility: If direct client_type passed, map to client_type field if present
        if (! isset($submitted['client_type'])) {
            $ctVal = $rawInput['client_type'] ?? request()->input('client_type');
            if ($ctVal !== null && $ctVal !== '') {
                $ctField = $activeFields->firstWhere('key', 'client_type');
                if ($ctField !== null) {
                    $submitted['client_type'] = $ctVal;
                }
            }
        }

        // Security check 1: Disallow arbitrary unknown keys
        $activeKeys = $activeFields->pluck('key')->all();
        $submittedKeys = array_keys($submitted);
        $invalidKeys = array_diff($submittedKeys, $activeKeys);

        if (! empty($invalidKeys)) {
            // Filter out system parameters if raw request was passed
            $systemParams = [
                '_token', '_method', 'lead_id', 'lead_status_id', 'communication_type',
                'outcome', 'employee_name', 'next_follow_up_at', 'followed_up_at',
                'users_count', 'branches_count', 'job_title', 'disinterest_reason', 'solution_type', 'lines_count', 'extensions',
                'departments', 'quotation_file_path', 'quotation_sent', 'custom_fields', 'additional_phones', 'related_people',
                'kanban_popup', 'record_followup', 'lead_attributes', 'field_changes', 'history_note', 'force_history', 'campaign',
            ];
            $actualUnknown = array_diff($invalidKeys, $systemParams);

            if (! empty($actualUnknown)) {
                // Check if unknown key belongs to another stage
                $otherStageField = PipelineStageField::query()
                    ->whereIn('key', $actualUnknown)
                    ->where('pipeline_stage_id', '!=', $stageId)
                    ->first();

                if ($otherStageField !== null) {
                    throw ValidationException::withMessages([
                        reset($actualUnknown) => [
                            __('crm.stage_field_cross_stage_error', [
                                'field' => reset($actualUnknown),
                            ]) ?: 'الحقل المدخل يتبع مرحلة أخرى غير المرحلة المختارة.',
                        ],
                    ]);
                }

                // Check if inactive field on current stage
                $inactiveField = PipelineStageField::query()
                    ->where('pipeline_stage_id', $stageId)
                    ->whereIn('key', $actualUnknown)
                    ->where('is_active', false)
                    ->first();

                if ($inactiveField !== null) {
                    throw ValidationException::withMessages([
                        reset($actualUnknown) => [
                            __('crm.stage_field_inactive_error', [
                                'field' => reset($actualUnknown),
                            ]) ?: 'الحقل المطلوب معطل حالياً ولا يمكن استقبال قيم له.',
                        ],
                    ]);
                }

                throw ValidationException::withMessages([
                    reset($actualUnknown) => [
                        __('crm.stage_field_unknown_error', [
                            'field' => reset($actualUnknown),
                        ]) ?: 'الحقل غير معرّف في هذه المرحلة.',
                    ],
                ]);
            }
        }

        // Build rules and validate
        $rules = self::buildValidationRules($stage, $submitted, '');
        $attributes = self::buildValidationAttributes($stage, '', app()->getLocale());

        $validator = Validator::make($submitted, $rules, [], $attributes);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $validated = $validator->validated();
        $normalized = [];

        foreach ($activeFields as $field) {
            $isApplicable = self::evaluateCondition($field->conditions, $submitted);
            if (! $isApplicable) {
                continue;
            }

            if (array_key_exists($field->key, $validated)) {
                $val = $validated[$field->key];

                if ($field->type === 'checkbox') {
                    $normalized[$field->key] = filter_var($val, FILTER_VALIDATE_BOOLEAN) ? '1' : '0';
                } elseif ($field->type === 'multiselect') {
                    $normalized[$field->key] = is_array($val) ? json_encode(array_values($val)) : (string) $val;
                } elseif ($val === null || $val === '') {
                    $normalized[$field->key] = null;
                } elseif ($val instanceof Carbon) {
                    $normalized[$field->key] = $field->type === 'date' ? $val->format('Y-m-d') : $val->toDateTimeString();
                } else {
                    $normalized[$field->key] = (string) $val;
                }
            }
        }

        return $normalized;
    }

    /**
     * Persist stage field values for a Lead atomically.
     *
     * @return Collection<int, LeadStageFieldValue>
     */
    public static function persistValues(
        Lead $lead,
        PipelineStage|int $stage,
        array $values,
        ?LeadStatusHistory $history = null,
        ?User $actor = null
    ): Collection {
        $stageId = $stage instanceof PipelineStage ? (int) $stage->id : (int) $stage;
        $fields = self::getFieldsForStage($stage, false)->keyBy('key');

        $savedRecords = new Collection();

        DB::transaction(function () use ($lead, $stageId, $fields, $values, $history, $actor, &$savedRecords): void {
            foreach ($values as $key => $val) {
                if ($val === null) {
                    continue;
                }

                $field = $fields->get($key);
                $fieldType = $field ? (string) $field->type : 'text';
                $fieldId = $field ? (int) $field->id : null;

                $record = LeadStageFieldValue::query()->create([
                    'lead_id' => $lead->id,
                    'pipeline_stage_id' => $stageId,
                    'pipeline_stage_field_id' => $fieldId,
                    'lead_status_history_id' => $history?->id,
                    'field_key' => (string) $key,
                    'field_type' => $fieldType,
                    'value' => is_scalar($val) ? (string) $val : (is_array($val) ? json_encode($val) : (string) $val),
                    'created_by_user_id' => $actor?->id,
                ]);

                $savedRecords->push($record);
            }
        });

        return $savedRecords;
    }
}
