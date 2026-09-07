<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Lead;
use App\Models\PipelineStageField;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final class LeadColumnConfig
{
    public const MANDATORY_COLUMNS = [
        'core:customer',
        'core:status',
        'core:actions',
    ];

    public const STANDARD_COLUMNS = [
        'core:contact' => [
            'key' => 'core:contact',
            'type' => 'standard',
            'label_ar' => 'بيانات الاتصال',
            'label_en' => 'Contact Data',
            'default' => true,
        ],
        'core:company_source' => [
            'key' => 'core:company_source',
            'type' => 'standard',
            'label_ar' => 'الشركة / المصدر',
            'label_en' => 'Company / Source',
            'default' => true,
        ],
        'core:employee' => [
            'key' => 'core:employee',
            'type' => 'standard',
            'label_ar' => 'الموظف المسؤول',
            'label_en' => 'Responsible Employee',
            'default' => true,
        ],
        'core:next_followup' => [
            'key' => 'core:next_followup',
            'type' => 'standard',
            'label_ar' => 'المتابعة القادمة',
            'label_en' => 'Next Follow-up',
            'default' => true,
        ],
        'core:created_at' => [
            'key' => 'core:created_at',
            'type' => 'standard',
            'label_ar' => 'تاريخ الإضافة',
            'label_en' => 'Created Date',
            'default' => true,
        ],
    ];

    /**
     * Resolve the active table columns for the current request, user, and context.
     *
     * @param  Collection<int, PipelineStageField>  $availableStageFields
     * @return array{
     *     visible_columns: array<int, array<string, mixed>>,
     *     visible_keys: array<int, string>,
     *     standard_columns: array<string, array<string, mixed>>,
     *     selected_field_ids: array<int, int>,
     *     is_customized: bool
     * }
     */
    public static function resolve(
        Request $request,
        User $user,
        Collection $availableStageFields,
        ?int $contextStageId = null,
        array $selectedStageFieldIds = []
    ): array {
        $sessionKey = 'leads_table_columns_' . $user->id;

        // 1. Determine requested raw keys
        $rawColumnsParam = $request->query('columns');
        $hasUrlColumns = $rawColumnsParam !== null && $rawColumnsParam !== '';
        $isRestoreDefault = $request->query('columns') === 'default';

        if ($isRestoreDefault) {
            session()->forget($sessionKey);
            $hasUrlColumns = false;
        }

        if ($hasUrlColumns) {
            $rawList = is_array($rawColumnsParam)
                ? $rawColumnsParam
                : explode(',', is_scalar($rawColumnsParam) ? (string) $rawColumnsParam : '');
            $requestedKeys = array_values(array_unique(array_filter(array_map('trim', $rawList))));
            session([$sessionKey => $requestedKeys]);
        } elseif (session()->has($sessionKey)) {
            $requestedKeys = (array) session($sessionKey, []);
        } else {
            $requestedKeys = null;
        }

        $isCustomized = $requestedKeys !== null;

        // Default set if no customization: all standard columns
        if ($requestedKeys === null) {
            $enabledStandardKeys = array_keys(self::STANDARD_COLUMNS);
            $enabledFieldIds = $selectedStageFieldIds;
        } else {
            $enabledStandardKeys = [];
            $enabledFieldIds = [];

            foreach ($requestedKeys as $key) {
                if (isset(self::STANDARD_COLUMNS[$key])) {
                    $enabledStandardKeys[] = $key;
                } elseif (str_starts_with($key, 'stage_field:')) {
                    $id = (int) substr($key, 12);
                    if ($id > 0) {
                        $enabledFieldIds[] = $id;
                    }
                } elseif (is_numeric($key)) {
                    $id = (int) $key;
                    if ($id > 0) {
                        $enabledFieldIds[] = $id;
                    }
                }
            }

            // Also merge any currently active selectedStageFieldIds
            foreach ($selectedStageFieldIds as $fid) {
                if (! in_array((int) $fid, $enabledFieldIds, true)) {
                    $enabledFieldIds[] = (int) $fid;
                }
            }
        }

        // Validate and filter dynamic stage fields against available active fields
        $validStageFields = $availableStageFields
            ->whereIn('id', $enabledFieldIds)
            ->values();

        // If a single context stage is active, prune fields from other stages
        if ($contextStageId !== null && $contextStageId > 0) {
            $validStageFields = $validStageFields
                ->where('pipeline_stage_id', $contextStageId)
                ->values();
        }

        $visibleKeys = [];

        // Build standard columns metadata
        $standardColumnsMeta = [];
        foreach (self::STANDARD_COLUMNS as $sKey => $sDef) {
            $isVisible = in_array($sKey, $enabledStandardKeys, true);
            if ($isVisible) {
                $visibleKeys[] = $sKey;
            }
            $standardColumnsMeta[$sKey] = array_merge($sDef, [
                'visible' => $isVisible,
            ]);
        }

        // Build visible column list
        $visibleColumns = [];

        // Core Mandatory: Customer
        $visibleColumns[] = [
            'key' => 'core:customer',
            'type' => 'core',
            'label_ar' => 'العميل',
            'label_en' => 'Client',
            'mandatory' => true,
        ];

        // Standard: Contact Data
        if (in_array('core:contact', $enabledStandardKeys, true)) {
            $visibleColumns[] = [
                'key' => 'core:contact',
                'type' => 'standard',
                'label_ar' => self::STANDARD_COLUMNS['core:contact']['label_ar'],
                'label_en' => self::STANDARD_COLUMNS['core:contact']['label_en'],
                'mandatory' => false,
            ];
        }

        // Standard: Company / Source
        if (in_array('core:company_source', $enabledStandardKeys, true)) {
            $visibleColumns[] = [
                'key' => 'core:company_source',
                'type' => 'standard',
                'label_ar' => self::STANDARD_COLUMNS['core:company_source']['label_ar'],
                'label_en' => self::STANDARD_COLUMNS['core:company_source']['label_en'],
                'mandatory' => false,
            ];
        }

        // Core Mandatory: Status
        $visibleColumns[] = [
            'key' => 'core:status',
            'type' => 'core',
            'label_ar' => 'الحالة الحالية',
            'label_en' => 'Current Status',
            'mandatory' => true,
        ];

        // Standard: Assigned Employee
        if (in_array('core:employee', $enabledStandardKeys, true)) {
            $visibleColumns[] = [
                'key' => 'core:employee',
                'type' => 'standard',
                'label_ar' => self::STANDARD_COLUMNS['core:employee']['label_ar'],
                'label_en' => self::STANDARD_COLUMNS['core:employee']['label_en'],
                'mandatory' => false,
            ];
        }

        // Standard: Next Follow-up
        if (in_array('core:next_followup', $enabledStandardKeys, true)) {
            $visibleColumns[] = [
                'key' => 'core:next_followup',
                'type' => 'standard',
                'label_ar' => self::STANDARD_COLUMNS['core:next_followup']['label_ar'],
                'label_en' => self::STANDARD_COLUMNS['core:next_followup']['label_en'],
                'mandatory' => false,
            ];
        }

        // Standard: Created Date
        if (in_array('core:created_at', $enabledStandardKeys, true)) {
            $visibleColumns[] = [
                'key' => 'core:created_at',
                'type' => 'standard',
                'label_ar' => self::STANDARD_COLUMNS['core:created_at']['label_ar'],
                'label_en' => self::STANDARD_COLUMNS['core:created_at']['label_en'],
                'mandatory' => false,
            ];
        }

        // Dynamic Stage Fields (Deduplicated against standard columns and by canonical binding_target)
        $hasStandardContact = in_array('core:contact', $enabledStandardKeys, true);
        $hasStandardCompany = in_array('core:company_source', $enabledStandardKeys, true);

        $selectedFieldIds = [];
        $seenCanonicalTargets = [];

        foreach ($validStageFields as $stageField) {
            $fieldKey = (string) $stageField->key;
            $bindingTarget = (string) ($stageField->binding_target ?: $fieldKey);
            $isCanonical = $stageField->binding_type === 'canonical';

            // 1. Deduplicate against mandatory Customer column (name)
            if ($isCanonical && in_array($bindingTarget, ['name', 'first_name', 'last_name'], true)) {
                $selectedFieldIds[] = (int) $stageField->id;
                continue;
            }

            // 2. Deduplicate against standard Contact column (phone, email)
            if ($hasStandardContact && $isCanonical && in_array($bindingTarget, ['phone', 'email', 'mobile_phone'], true)) {
                $selectedFieldIds[] = (int) $stageField->id;
                continue;
            }

            // 3. Deduplicate against standard Company/Source column (company_name, source)
            if ($hasStandardCompany && $isCanonical && in_array($bindingTarget, ['company_name', 'source'], true)) {
                $selectedFieldIds[] = (int) $stageField->id;
                continue;
            }

            // 4. Deduplicate multiple canonical fields with same binding_target across stages
            if ($isCanonical && ! empty($bindingTarget)) {
                if (isset($seenCanonicalTargets[$bindingTarget])) {
                    $selectedFieldIds[] = (int) $stageField->id;
                    continue;
                }
                $seenCanonicalTargets[$bindingTarget] = true;
            }

            $colKey = 'stage_field:' . $stageField->id;
            $visibleKeys[] = $colKey;
            $selectedFieldIds[] = (int) $stageField->id;

            $visibleColumns[] = [
                'key' => $colKey,
                'type' => 'stage_field',
                'field_id' => (int) $stageField->id,
                'field_key' => $stageField->key,
                'binding_target' => $isCanonical ? $bindingTarget : null,
                'label_ar' => $stageField->localizedLabel('ar'),
                'label_en' => $stageField->localizedLabel('en'),
                'stage_field' => $stageField,
                'mandatory' => false,
            ];
        }

        // Core Mandatory: Actions at end
        $visibleColumns[] = [
            'key' => 'core:actions',
            'type' => 'core',
            'label_en' => 'Actions',
            'mandatory' => true,
        ];

        $visibleKeys = array_values(array_unique(array_column($visibleColumns, 'key')));

        return [
            'visible_columns' => $visibleColumns,
            'visible_keys' => $visibleKeys,
            'standard_columns' => $standardColumnsMeta,
            'selected_field_ids' => $selectedFieldIds,
            'is_customized' => $isCustomized,
        ];
    }

    /**
     * Formats the value of a dynamic stage field for table cell rendering.
     */
    public static function formatDynamicCellValue(
        Lead $lead,
        PipelineStageField $field,
        mixed $customValue = null,
        ?string $locale = null
    ): string {
        $locale = $locale ?? app()->getLocale();

        // 1. If canonical binding, read directly from Lead model attribute
        if ($field->binding_type === 'canonical' && ! empty($field->binding_target)) {
            $raw = $lead->getAttribute($field->binding_target);
            if ($raw !== null && $raw !== '') {
                if ($field->type === 'select') {
                    foreach ($field->normalizedOptions() as $opt) {
                        if ((string) $opt['value'] === (string) $raw) {
                            return $locale === 'en' ? $opt['label_en'] : $opt['label_ar'];
                        }
                    }
                }
                return (string) $raw;
            }
        }

        // 2. Custom value from stage value record
        $val = $customValue;
        if ($val === null || $val === '') {
            return '—';
        }

        switch ($field->type) {
            case 'select':
                foreach ($field->normalizedOptions() as $opt) {
                    if ((string) $opt['value'] === (string) $val) {
                        return $locale === 'en' ? $opt['label_en'] : $opt['label_ar'];
                    }
                }
                return (string) $val;

            case 'checkbox':
            case 'boolean':
                return ($val === '1' || $val === 1 || $val === true || $val === 'yes')
                    ? ($locale === 'en' ? 'Yes' : 'نعم')
                    : ($locale === 'en' ? 'No' : 'لا');

            case 'date':
                try {
                    return Carbon::parse((string) $val)->format('d/m/Y');
                } catch (\Throwable) {
                    return (string) $val;
                }

            case 'datetime':
                try {
                    return Carbon::parse((string) $val)->format('d/m/Y - h:i A');
                } catch (\Throwable) {
                    return (string) $val;
                }

            case 'number':
            case 'currency':
                return is_numeric($val) ? number_format((float) $val) : (string) $val;

            case 'textarea':
                return Str::limit(trim((string) $val), 60);

            default:
                return (string) $val;
        }
    }
}
