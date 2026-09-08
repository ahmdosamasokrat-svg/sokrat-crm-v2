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

        // 1. Fast path: leverage already eager-loaded relation if passed
        if ($stage instanceof PipelineStage && $onlyActive && $stage->relationLoaded('activeFields')) {
            self::$memoizedFields[$memoKey] = $stage->activeFields;
            return $stage->activeFields;
        }

        if ($stage instanceof PipelineStage && ! $onlyActive && $stage->relationLoaded('fields')) {
            self::$memoizedFields[$memoKey] = $stage->fields;
            return $stage->fields;
        }

        // 2. Perform indexed query with per-request memoization.
        // We avoid storing live Eloquent Collection objects into persistent database cache,
        // which prevents PHP __PHP_Incomplete_Class deserialization failure across web workers.
        $query = PipelineStageField::query()
            ->where('pipeline_stage_id', $stageId);

        if ($onlyActive) {
            $query->where('is_active', true);
        }

        $fields = $query->orderBy('position')->orderBy('id')->get();

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
     * Evaluate a conditional rule or compound clause (all/any) against submitted data.
     */
    public static function evaluateCondition(?array $condition, array $data, array $visited = []): bool
    {
        if (empty($condition)) {
            return true;
        }

        // Compound clause: 'all'
        if (! empty($condition['all']) && is_array($condition['all'])) {
            foreach ($condition['all'] as $subRule) {
                if (! self::evaluateCondition($subRule, $data, $visited)) {
                    return false;
                }
            }
            return true;
        }

        // Compound clause: 'any'
        if (! empty($condition['any']) && is_array($condition['any'])) {
            foreach ($condition['any'] as $subRule) {
                if (self::evaluateCondition($subRule, $data, $visited)) {
                    return true;
                }
            }
            return false;
        }

        // Compound clause: 'rules' with 'clause' ('all'|'any')
        if (! empty($condition['rules']) && is_array($condition['rules'])) {
            $clause = strtolower((string) ($condition['clause'] ?? 'all'));
            if ($clause === 'any') {
                foreach ($condition['rules'] as $subRule) {
                    if (self::evaluateCondition($subRule, $data, $visited)) {
                        return true;
                    }
                }
                return false;
            }
            foreach ($condition['rules'] as $subRule) {
                if (! self::evaluateCondition($subRule, $data, $visited)) {
                    return false;
                }
            }
            return true;
        }

        if (empty($condition['field'])) {
            return true;
        }

        $fieldKey = (string) $condition['field'];

        if (in_array($fieldKey, $visited, true)) {
            return false; // Break evaluation cycle
        }
        $visited[] = $fieldKey;

        $operator = (string) ($condition['operator'] ?? 'equals');
        $expected = $condition['value'] ?? null;
        $actual = $data[$fieldKey] ?? null;

        return self::evaluateOperator($operator, $actual, $expected);
    }

    /**
     * Evaluate a single operator against actual and expected values.
     */
    public static function evaluateOperator(string $operator, mixed $actual, mixed $expected): bool
    {
        switch ($operator) {
            case 'equals':
                if (is_array($actual) || is_array($expected)) {
                    if (is_array($actual) && is_array($expected)) {
                        return $actual == $expected;
                    }
                    if (is_array($actual)) {
                        return count($actual) === 1 && (string) reset($actual) === (string) $expected;
                    }
                    return count($expected) === 1 && (string) reset($expected) === (string) $actual;
                }
                return (string) $actual === (string) $expected;

            case 'not_equals':
                if (is_array($actual) || is_array($expected)) {
                    if (is_array($actual) && is_array($expected)) {
                        return $actual != $expected;
                    }
                    if (is_array($actual)) {
                        return count($actual) !== 1 || (string) reset($actual) !== (string) $expected;
                    }
                    return count($expected) !== 1 || (string) reset($expected) !== (string) $actual;
                }
                return (string) $actual !== (string) $expected;

            case 'is_checked':
            case 'is_true':
                return filter_var($actual, FILTER_VALIDATE_BOOLEAN) === true;

            case 'is_not_checked':
            case 'is_false':
                return filter_var($actual, FILTER_VALIDATE_BOOLEAN) === false;

            case 'is_empty':
                return $actual === null || $actual === '' || $actual === [];

            case 'is_not_empty':
                return $actual !== null && $actual !== '' && $actual !== [];

            case 'contains':
                if (is_array($actual)) {
                    return in_array($expected, $actual, false);
                }
                if ($expected === null || $expected === '') {
                    return true;
                }
                $actualStr = is_scalar($actual) ? (string) $actual : (is_array($actual) ? json_encode($actual) : '');
                $expectedStr = is_scalar($expected) ? (string) $expected : (is_array($expected) ? json_encode($expected) : '');
                return str_contains(
                    mb_strtolower($actualStr),
                    mb_strtolower($expectedStr)
                );

            case 'in':
                $allowed = is_array($expected)
                    ? $expected
                    : array_map('trim', explode(',', (string) $expected));
                if (is_array($actual)) {
                    return ! empty(array_intersect(array_map('strval', $actual), array_map('strval', $allowed)));
                }
                if (! is_scalar($actual) && $actual !== null) {
                    return false;
                }
                return in_array((string) $actual, array_map('strval', $allowed), true);

            default:
                return true;
        }
    }

    /**
     * Detect cyclic dependencies in a collection of fields or candidate conditions.
     */
    public static function hasCyclicDependency(
        mixed $fields,
        ?string $candidateKey = null,
        ?array $candidateConditions = null
    ): bool {
        $graph = [];

        foreach ($fields as $field) {
            $key = is_array($field) ? ($field['key'] ?? '') : (string) $field->key;
            if ($key === '') continue;

            $conds = is_array($field) ? ($field['conditions'] ?? null) : $field->conditions;
            $graph[$key] = self::extractReferencedFields($conds);
        }

        if ($candidateKey !== null) {
            $graph[$candidateKey] = self::extractReferencedFields($candidateConditions);
        }

        $visited = [];
        $recStack = [];

        $dfs = function (string $node) use (&$dfs, &$graph, &$visited, &$recStack): bool {
            $visited[$node] = true;
            $recStack[$node] = true;

            foreach ($graph[$node] ?? [] as $neighbor) {
                if (! isset($visited[$neighbor])) {
                    if ($dfs($neighbor)) {
                        return true;
                    }
                } elseif (! empty($recStack[$neighbor])) {
                    return true;
                }
            }

            $recStack[$node] = false;
            return false;
        };

        foreach (array_keys($graph) as $node) {
            if (! isset($visited[$node])) {
                if ($dfs($node)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Extract all field keys referenced by a condition tree.
     */
    public static function extractReferencedFields(?array $condition): array
    {
        if (empty($condition)) {
            return [];
        }

        $fields = [];
        if (! empty($condition['field'])) {
            $fields[] = (string) $condition['field'];
        }

        foreach (['all', 'any', 'rules'] as $clauseKey) {
            if (! empty($condition[$clauseKey]) && is_array($condition[$clauseKey])) {
                foreach ($condition[$clauseKey] as $subRule) {
                    $fields = array_merge($fields, self::extractReferencedFields($subRule));
                }
            }
        }

        return array_unique($fields);
    }

    /**
     * Evaluate condition taking parent field visibility into account (Parent -> Child -> Grandchild).
     */
    public static function evaluateFieldApplicability(PipelineStageField $field, array $data, Collection $allFields, array $visited = []): bool
    {
        if (empty($field->conditions)) {
            return true;
        }

        if (in_array($field->key, $visited, true)) {
            return false; // Cycle guard
        }
        $visited[] = $field->key;

        $referencedKeys = self::extractReferencedFields($field->conditions);
        foreach ($referencedKeys as $refKey) {
            $parentField = $allFields->firstWhere('key', $refKey);
            if ($parentField && ! empty($parentField->conditions)) {
                if (! self::evaluateFieldApplicability($parentField, $data, $allFields, $visited)) {
                    return false;
                }
            }
        }

        return self::evaluateCondition($field->conditions, $data);
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

            $isApplicable = self::evaluateFieldApplicability($field, $submittedValues, $fields);

            if (! $isApplicable) {
                $rules[$fieldName] = ['nullable'];
                continue;
            }

            if ($field->is_required) {
                if (in_array($field->type, ['checkbox', 'boolean'], true)) {
                    $fieldRules[] = 'accepted';
                } else {
                    $fieldRules[] = 'required';
                }
            } else {
                $fieldRules[] = 'nullable';
            }

            // If value is already an uploaded/normalized file payload or path, do not re-run file/mime rules
            $submittedVal = $submittedValues[$field->key] ?? null;
            $isAlreadyFilePayload = (is_array($submittedVal) && ($submittedVal['type'] ?? '') === 'file')
                || (is_string($submittedVal) && $submittedVal !== '');

            if ($isAlreadyFilePayload && in_array($field->type, ['file', 'image', 'pdf'], true)) {
                $rules[$fieldName] = ['nullable'];
                continue;
            }

            // Merge canonical lead attribute rules if field is bound to canonical customer data
            if ($field->isCanonical() && ! empty($field->binding_target)) {
                $cfg = $field->canonicalConfig();
                if ($cfg && ! empty($cfg['rules']) && is_array($cfg['rules'])) {
                    $fieldRules = array_merge($fieldRules, $cfg['rules']);
                }
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

                case 'time':
                    $fieldRules[] = static function (string $attribute, mixed $value, \Closure $fail): void {
                        if ($value === null || $value === '') {
                            return;
                        }
                        if (! is_string($value) || ! preg_match('/^(?:2[0-3]|[01]?[0-9]):[0-5][0-9](?::[0-5][0-9])?$/', trim($value))) {
                            $fail(__('validation.date_format', ['attribute' => $attribute, 'format' => 'H:i']));
                        }
                    };
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
                case 'radio':
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
                case 'boolean':
                    $fieldRules[] = 'boolean';
                    break;

                case 'currency':
                    $fieldRules[] = 'numeric';
                    break;
                case 'file':
                case 'image':
                case 'pdf':
                    $submittedVal = $submittedValues[$field->key] ?? null;
                    if (is_array($submittedVal) && ($submittedVal['type'] ?? '') === 'file') {
                        $fieldRules[] = 'nullable';
                    } elseif (is_string($submittedVal) && $submittedVal !== '') {
                        $fieldRules[] = 'nullable';
                    } else {
                        $fieldRules[] = 'file';
                        if ($field->type === 'image') {
                            $fieldRules[] = 'image';
                            $fieldRules[] = 'mimes:jpeg,png,jpg,webp,gif';
                            $fieldRules[] = 'max:5120';
                        } elseif ($field->type === 'pdf') {
                            $fieldRules[] = 'mimes:pdf';
                            $fieldRules[] = 'max:10240';
                        } else {
                            $fieldRules[] = 'mimes:pdf,doc,docx,xls,xlsx,txt,png,jpg,jpeg,webp,zip';
                            $fieldRules[] = 'max:10240';
                        }
                    }
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
     * Build custom validation messages for stage fields.
     */
    public static function buildValidationMessages(
        PipelineStage|int $stage,
        string $prefix = 'stage_fields.',
        ?string $locale = null
    ): array {
        $fields = self::getFieldsForStage($stage, true);
        $messages = [];
        $isAr = ($locale ?? app()->getLocale()) === 'ar';

        foreach ($fields as $field) {
            $key = $prefix . $field->key;
            $label = $field->localizedLabel($locale);

            if ($field->type === 'multiselect') {
                $messages[$key . '.array'] = $isAr
                    ? "يجب اختيار قيمة واحدة أو أكثر لـ {$label}."
                    : "The {$label} field must be an array.";
                $messages[$key . '.required'] = $isAr
                    ? "حقل {$label} مطلوب."
                    : "The {$label} field is required.";
                $messages[$key . '.*.in'] = $isAr
                    ? "القيمة المختارة في {$label} غير صالحة."
                    : "The selected value in {$label} is invalid.";
            } else {
                $messages[$key . '.required'] = $isAr
                    ? "حقل {$label} مطلوب."
                    : "The {$label} field is required.";
            }
        }

        return $messages;
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

        // Normalize multiselect inputs before validation
        foreach ($activeFields as $field) {
            if ($field->type === 'multiselect') {
                if (array_key_exists($field->key, $submitted)) {
                    $raw = $submitted[$field->key];
                    if ($raw === '' || $raw === null) {
                        $submitted[$field->key] = [];
                    } elseif (is_string($raw)) {
                        $trimmed = trim($raw);
                        if ($trimmed === '') {
                            $submitted[$field->key] = [];
                        } else {
                            $decoded = json_decode($trimmed, true);
                            if (is_array($decoded)) {
                                $submitted[$field->key] = array_values(array_filter($decoded, static fn ($v) => $v !== null && $v !== ''));
                            } else {
                                $parts = array_map('trim', explode(',', $trimmed));
                                $submitted[$field->key] = array_values(array_filter($parts, static fn ($v) => $v !== ''));
                            }
                        }
                    } elseif (is_array($raw)) {
                        $submitted[$field->key] = array_values(array_filter($raw, static fn ($v) => $v !== null && $v !== ''));
                    }
                } elseif ($field->is_required) {
                    $submitted[$field->key] = [];
                }
            }
        }

        // Build rules, messages, attributes and validate
        $rules = self::buildValidationRules($stage, $submitted, '');
        $attributes = self::buildValidationAttributes($stage, '', app()->getLocale());
        $messages = self::buildValidationMessages($stage, '', app()->getLocale());

        $validator = Validator::make($submitted, $rules, $messages, $attributes);
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $validated = $validator->validated();
        $normalized = [];

        foreach ($activeFields as $field) {
            $isApplicable = self::evaluateFieldApplicability($field, $submitted, $activeFields);
            if (! $isApplicable) {
                continue;
            }

            if (array_key_exists($field->key, $validated)) {
                $val = $validated[$field->key];
                if (is_array($val) && ($val['type'] ?? '') === 'file') {
                    $normalized[$field->key] = $val;
                } elseif ($val instanceof \Illuminate\Http\UploadedFile) {
                    $storedPath = $val->store('crm-v2/documents', 'local');
                    $normalized[$field->key] = [
                        'type' => 'file',
                        'path' => $storedPath,
                        'original_name' => $val->getClientOriginalName(),
                        'mime_type' => $val->getClientMimeType(),
                        'size' => (int) $val->getSize(),
                        'category' => $field->type === 'pdf' ? \App\Models\LeadDocument::CATEGORY_PDF : ($field->type === 'image' ? \App\Models\LeadDocument::CATEGORY_IMAGE : \App\Models\LeadDocument::CATEGORY_ATTACHMENT),
                    ];
                } elseif (in_array($field->type, ['checkbox', 'boolean'], true)) {
                    $normalized[$field->key] = filter_var($val, FILTER_VALIDATE_BOOLEAN) ? '1' : '0';
                } elseif ($field->type === 'multiselect') {
                    $normalized[$field->key] = is_array($val) ? json_encode(array_values($val)) : (string) $val;
                } elseif ($val === null || $val === '') {
                    $normalized[$field->key] = null;
                } elseif ($val instanceof Carbon) {
                    $normalized[$field->key] = $field->type === 'date' ? $val->format('Y-m-d') : $val->toDateTimeString();
                } elseif (in_array($field->type, ['number', 'currency'], true)) {
                    $normalized[$field->key] = is_numeric($val) ? (string) $val : null;
                } else {
                    $normalized[$field->key] = trim((string) $val);
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

                if (is_array($val) && ($val['type'] ?? '') === 'file') {
                    $docCategory = $val['category'] ?? \App\Models\LeadDocument::CATEGORY_ATTACHMENT;
                    if ($field && ($field->binding_target === 'quotation_file_path' || str_contains($key, 'quotation'))) {
                        $docCategory = \App\Models\LeadDocument::CATEGORY_QUOTATION;
                        $lead->update(['quotation_file_path' => $val['path']]);
                    }

                    \App\Models\LeadDocument::query()->create([
                        'lead_id' => $lead->id,
                        'pipeline_stage_id' => $stageId,
                        'pipeline_stage_field_id' => $fieldId,
                        'lead_status_history_id' => $history?->id,
                        'category' => $docCategory,
                        'original_name' => $val['original_name'] ?? basename((string) $val['path']),
                        'stored_name' => basename((string) $val['path']),
                        'disk' => 'local',
                        'path' => $val['path'],
                        'mime_type' => $val['mime_type'] ?? null,
                        'size' => $val['size'] ?? 0,
                        'created_by_user_id' => $actor?->id,
                    ]);

                    $record = LeadStageFieldValue::query()->create([
                        'lead_id' => $lead->id,
                        'pipeline_stage_id' => $stageId,
                        'pipeline_stage_field_id' => $fieldId,
                        'lead_status_history_id' => $history?->id,
                        'field_key' => (string) $key,
                        'field_type' => $fieldType,
                        'value' => $val['path'],
                        'created_by_user_id' => $actor?->id,
                    ]);
                    $savedRecords->push($record);
                    continue;
                }
                $record = LeadStageFieldValue::query()->create([
                    'lead_id' => $lead->id,
                    'pipeline_stage_id' => $stageId,
                    'pipeline_stage_field_id' => $fieldId,
                    'lead_status_history_id' => $history?->id,
                    'field_key' => (string) $key,
                    'field_type' => $fieldType,
                    'value' => $val === null
                        ? null
                        : (is_scalar($val) ? (string) $val : (is_array($val) ? json_encode($val) : (string) $val)),
                    'created_by_user_id' => $actor?->id,
                ]);

                $savedRecords->push($record);
            }
        });

        return $savedRecords;
    }
    /**
     * Separate extracted values into canonical Lead attributes and custom stage field values.
     *
     * @param PipelineStage|int $stage
     * @param array<string, mixed> $normalizedValues
     * @return array{
     *     canonical: array<string, mixed>,
     *     custom: array<string, mixed>
     * }
     */
    public static function splitValues(PipelineStage|int $stage, array $normalizedValues): array
    {
        $fields = self::getFieldsForStage($stage, false)->keyBy('key');
        $canonical = [];
        $custom = [];

        foreach ($normalizedValues as $key => $val) {
            $field = $fields->get($key);
            if ($field && $field->isCanonical() && ! empty($field->binding_target)) {
                // Ensure target is strictly in the verified allowlist
                if (array_key_exists($field->binding_target, PipelineStageField::CANONICAL_FIELDS)) {
                    $target = (string) $field->binding_target;
                    // Format canonical value according to column type
                    $cfg = PipelineStageField::CANONICAL_FIELDS[$target];
                    if (in_array($cfg['type'], ['number', 'integer'], true)) {
                        $canonical[$target] = $val !== null && is_numeric($val) ? 0 + $val : null;
                    } elseif (in_array($cfg['type'], ['checkbox', 'boolean'], true)) {
                        $canonical[$target] = $val !== null ? filter_var($val, FILTER_VALIDATE_BOOLEAN) : false;
                    } elseif (is_array($val) && isset($val['path'])) {
                        $canonical[$target] = (string) $val['path'];
                    } else {
                        $canonical[$target] = $val !== null ? (is_scalar($val) ? trim((string) $val) : json_encode($val)) : null;
                    }
                }
            } else {
                $custom[$key] = $val;
            }
        }

        return [
            'canonical' => $canonical,
            'custom' => $custom,
        ];
    }

    /**
     * Resolve pre-filled values for stage fields based on Lead's current data.
     *
     * @param Lead $lead
     * @param PipelineStage|int $stage
     * @return array<string, mixed>
     */
    public static function prefillValues(Lead $lead, PipelineStage|int $stage): array
    {
        $fields = ($stage instanceof PipelineStage && $stage->relationLoaded('activeFields'))
            ? $stage->activeFields
            : self::getFieldsForStage($stage, true);

        $values = [];

        foreach ($fields as $field) {
            if ($field instanceof PipelineStageField) {
                $values[$field->key] = $field->getInitialValue($lead);
            }
        }

        return $values;
    }
}
