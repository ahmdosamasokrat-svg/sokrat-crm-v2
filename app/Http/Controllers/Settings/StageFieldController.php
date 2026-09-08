<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\PipelineStage;
use App\Models\PipelineStageField;
use App\Support\CrmDatabaseGuard;
use App\Support\StageFieldSchema;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StageFieldController extends Controller
{
    /**
     * Friendly preset templates for common CRM stage workflows.
     */
    public const PRESETS = [
        'lost_lead' => [
            'key' => 'lost_lead',
            'name_ar' => 'نموذج أسباب عدم الاهتمام / الرفض',
            'name_en' => 'Disinterest / Lost Lead Reasons',
            'description_ar' => 'حقل اختيار سبب الرفض مع حقل نصي للملاحظات والشروط.',
            'fields' => [
                [
                    'key' => 'reason',
                    'label_ar' => 'سبب عدم الاهتمام',
                    'label_en' => 'Disinterest Reason',
                    'type' => 'select',
                    'placeholder_ar' => 'اختر سبب عدم الاهتمام',
                    'is_required' => true,
                    'options' => [
                        ['value' => 'high_price', 'label_ar' => 'السعر مرتفع', 'label_en' => 'High Price'],
                        ['value' => 'not_convinced', 'label_ar' => 'غير مقتنع بالفكرة', 'label_en' => 'Not Convinced'],
                        ['value' => 'no_budget', 'label_ar' => 'لا توجد ميزانية حالياً', 'label_en' => 'No Budget Currently'],
                        ['value' => 'competitor', 'label_ar' => 'يتعامل مع جهة أخرى', 'label_en' => 'Using Another Provider'],
                        ['value' => 'bad_timing', 'label_ar' => 'التوقيت غير مناسب', 'label_en' => 'Bad Timing'],
                        ['value' => 'other', 'label_ar' => 'سبب آخر', 'label_en' => 'Other Reason'],
                    ],
                ],
                [
                    'key' => 'notes',
                    'label_ar' => 'ملاحظات وتفاصيل',
                    'label_en' => 'Notes & Details',
                    'type' => 'textarea',
                    'placeholder_ar' => 'أدخل تفاصيل إضافية حول سبب الرفض',
                    'is_required' => false,
                    'conditions' => [
                        'field' => 'reason',
                        'operator' => 'equals',
                        'value' => 'other',
                    ],
                ],
            ],
        ],
        'callback_scheduling' => [
            'key' => 'callback_scheduling',
            'name_ar' => 'نموذج إعادة الاتصال / المتابعة اللاحقة',
            'name_en' => 'Callback / Scheduled Follow-up',
            'description_ar' => 'تحديد موعد الاتصال القادم وملاحظات محاولة الاتصال.',
            'fields' => [
                [
                    'key' => 'callback_at',
                    'label_ar' => 'موعد إعادة الاتصال',
                    'label_en' => 'Next Callback Date',
                    'type' => 'datetime',
                    'placeholder_ar' => 'حدد تاريخ ووقت إعادة الاتصال',
                    'is_required' => true,
                ],
                [
                    'key' => 'notes',
                    'label_ar' => 'ملاحظات المحاولة',
                    'label_en' => 'Attempt Notes',
                    'type' => 'textarea',
                    'placeholder_ar' => 'أدخل أي ملاحظات حول محاولة الاتصال',
                    'is_required' => false,
                ],
            ],
        ],
        'qualification' => [
            'key' => 'qualification',
            'name_ar' => 'نموذج تأهيل العميل المبدئي',
            'name_en' => 'Lead Qualification Questions',
            'description_ar' => 'أسئلة لتأهيل اهتمام العميل وجدوى التواصل.',
            'fields' => [
                [
                    'key' => 'interest_level',
                    'label_ar' => 'درجة الاهتمام',
                    'label_en' => 'Interest Level',
                    'type' => 'select',
                    'is_required' => true,
                    'options' => [
                        ['value' => 'high', 'label_ar' => 'اهتمام عالي (جاهز للتنفيذ)', 'label_en' => 'High'],
                        ['value' => 'medium', 'label_ar' => 'اهتمام متوسط (يحتاج تفاصيل)', 'label_en' => 'Medium'],
                        ['value' => 'low', 'label_ar' => 'اهتمام مبدئي فقط', 'label_en' => 'Low'],
                    ],
                ],
                [
                    'key' => 'expected_budget',
                    'label_ar' => 'الميزانية المتوقعة',
                    'label_en' => 'Expected Budget',
                    'type' => 'number',
                    'placeholder_ar' => 'المبلغ التقديري',
                    'is_required' => false,
                ],
                [
                    'key' => 'decision_maker',
                    'label_ar' => 'هل العميل هو صاحب القرار؟',
                    'label_en' => 'Is Decision Maker?',
                    'type' => 'checkbox',
                    'is_required' => false,
                ],
            ],
        ],
        'custom_review' => [
            'key' => 'custom_review',
            'name_ar' => 'نموذج مراجعة وتوثيق المرحلة',
            'name_en' => 'Stage Review & Verification',
            'description_ar' => 'توثيق حالة مراجعة البيانات قبل اعتماد الانتقال.',
            'fields' => [
                [
                    'key' => 'review_status',
                    'label_ar' => 'نتيجة المراجعة',
                    'label_en' => 'Review Outcome',
                    'type' => 'select',
                    'is_required' => true,
                    'options' => [
                        ['value' => 'approved', 'label_ar' => 'معتمد ومستوفى الشروط', 'label_en' => 'Approved'],
                        ['value' => 'pending_docs', 'label_ar' => 'معلق لنواقص بيانات', 'label_en' => 'Pending Documents'],
                        ['value' => 'rejected', 'label_ar' => 'مرفوض', 'label_en' => 'Rejected'],
                    ],
                ],
                [
                    'key' => 'notes',
                    'label_ar' => 'ملاحظات المراجعة',
                    'label_en' => 'Review Notes',
                    'type' => 'textarea',
                    'is_required' => false,
                ],
            ],
        ],
    ];

    public function index(PipelineStage $stage): View
    {
        $this->assertCrmDatabase();

        $fields = PipelineStageField::query()
            ->where('pipeline_stage_id', $stage->id)
            ->withCount('values')
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        $otherFields = $fields->where('is_active', true);

        return view('settings.stages.fields', [
            'stage' => $stage,
            'fields' => $fields,
            'otherFields' => $otherFields,
            'presets' => self::PRESETS,
            'fieldTypes' => PipelineStageField::TYPES,
            'bindingTypes' => PipelineStageField::BINDING_TYPES,
            'canonicalFields' => PipelineStageField::CANONICAL_FIELDS,
            'operators' => PipelineStageField::OPERATORS,
        ]);
    }

    public function store(Request $request, PipelineStage $stage): RedirectResponse
    {
        $this->assertCrmDatabase();

        $validated = $this->validateFieldInput($request, $stage, null);

        $nextPos = (int) (PipelineStageField::query()
            ->where('pipeline_stage_id', $stage->id)
            ->max('position') ?? 0) + 1;

        $options = $this->extractOptions($request, $validated);
        $conditions = $this->extractConditions($validated);
        $existingFields = PipelineStageField::query()
            ->where('pipeline_stage_id', $stage->id)
            ->whereNull('deleted_at');
        if (StageFieldSchema::hasCyclicDependency($existingFields->get(), $validated['key'], $conditions)) {
            throw ValidationException::withMessages([
                'condition_field' => 'يوجد تكرار حلقي غير مسموح به في الشروط بين الحقول (Circular dependency detected).',
            ]);
        }

        PipelineStageField::query()->create([
            'pipeline_stage_id' => $stage->id,
            'key' => $validated['key'],
            'label_ar' => $validated['label_ar'],
            'label_en' => $validated['label_en'] ?? null,
            'type' => $validated['type'],
            'binding_type' => $validated['binding_type'] ?? 'custom',
            'binding_target' => $validated['binding_target'] ?? null,
            'placeholder_ar' => $validated['placeholder_ar'] ?? null,
            'placeholder_en' => $validated['placeholder_en'] ?? null,
            'help_text_ar' => $validated['help_text_ar'] ?? null,
            'help_text_en' => $validated['help_text_en'] ?? null,
            'is_required' => (bool) ($validated['is_required'] ?? false),
            'default_value' => $validated['default_value'] ?? null,
            'options' => $options,
            'conditions' => $conditions,
            'show_on_transition' => (bool) ($validated['show_on_transition'] ?? true),
            'show_on_stage_view' => (bool) ($validated['show_on_stage_view'] ?? true),
            'show_in_history' => (bool) ($validated['show_in_history'] ?? true),
            'is_active' => true,
            'position' => $nextPos,
        ]);

        StageFieldSchema::flushCache((int) $stage->id);

        return redirect()
            ->route('v2.settings.stages.fields.index', $stage)
            ->with('success', 'تمت إضافة السؤال / الحقل بنجاح.');
    }

    public function update(Request $request, PipelineStage $stage, PipelineStageField $field): RedirectResponse
    {
        $this->assertCrmDatabase();

        if ((int) $field->pipeline_stage_id !== (int) $stage->id) {
            abort(404);
        }

        $validated = $this->validateFieldInput($request, $stage, $field);

        $options = $this->extractOptions($request, $validated);
        $conditions = $this->extractConditions($validated);
        $existingFields = PipelineStageField::query()
            ->where('pipeline_stage_id', $stage->id)
            ->where('id', '!=', $field->id)
            ->whereNull('deleted_at');
        if (StageFieldSchema::hasCyclicDependency($existingFields->get(), $field->key, $conditions)) {
            throw ValidationException::withMessages([
                'condition_field' => 'يوجد تكرار حلقي غير مسموح به في الشروط بين الحقول (Circular dependency detected).',
            ]);
        }

        $field->update([
            'label_ar' => $validated['label_ar'],
            'label_en' => $validated['label_en'] ?? null,
            'type' => $validated['type'],
            'binding_type' => $validated['binding_type'] ?? $field->binding_type ?? 'custom',
            'binding_target' => $validated['binding_target'] ?? $field->binding_target,
            'placeholder_ar' => $validated['placeholder_ar'] ?? null,
            'placeholder_en' => $validated['placeholder_en'] ?? null,
            'help_text_ar' => $validated['help_text_ar'] ?? null,
            'help_text_en' => $validated['help_text_en'] ?? null,
            'is_required' => (bool) ($validated['is_required'] ?? false),
            'default_value' => $validated['default_value'] ?? null,
            'options' => $options,
            'conditions' => $conditions,
            'show_on_transition' => (bool) ($validated['show_on_transition'] ?? true),
            'show_on_stage_view' => (bool) ($validated['show_on_stage_view'] ?? true),
            'show_in_history' => (bool) ($validated['show_in_history'] ?? true),
        ]);

        StageFieldSchema::flushCache((int) $stage->id);

        return redirect()
            ->route('v2.settings.stages.fields.index', $stage)
            ->with('success', 'تم تعديل السؤال / الحقل بنجاح.');
    }

    public function destroy(PipelineStage $stage, PipelineStageField $field): RedirectResponse
    {
        $this->assertCrmDatabase();

        if ((int) $field->pipeline_stage_id !== (int) $stage->id) {
            abort(404);
        }

        $field->delete();
        StageFieldSchema::flushCache((int) $stage->id);

        return redirect()
            ->route('v2.settings.stages.fields.index', $stage)
            ->with('success', 'تم حذف السؤال من المرحلة.');
    }

    public function toggle(PipelineStage $stage, PipelineStageField $field): RedirectResponse
    {
        $this->assertCrmDatabase();

        if ((int) $field->pipeline_stage_id !== (int) $stage->id) {
            abort(404);
        }

        $field->update(['is_active' => ! $field->is_active]);
        StageFieldSchema::flushCache((int) $stage->id);

        return redirect()
            ->route('v2.settings.stages.fields.index', $stage)
            ->with('success', $field->is_active ? 'تم تفعيل السؤال.' : 'تم تعطيل السؤال.');
    }

    public function reorder(Request $request, PipelineStage $stage): JsonResponse|RedirectResponse
    {
        $this->assertCrmDatabase();

        $order = $request->input('order', []);
        if (is_array($order) && ! empty($order)) {
            foreach ($order as $pos => $fieldId) {
                PipelineStageField::query()
                    ->where('id', (int) $fieldId)
                    ->where('pipeline_stage_id', $stage->id)
                    ->update(['position' => $pos + 1]);
            }
            StageFieldSchema::flushCache((int) $stage->id);
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()
            ->route('v2.settings.stages.fields.index', $stage)
            ->with('success', 'تم حفظ ترتيب الأسئلة بنجاح.');
    }

    public function applyPreset(Request $request, PipelineStage $stage): RedirectResponse
    {
        $this->assertCrmDatabase();

        $presetKey = (string) $request->input('preset_key');
        if (! isset(self::PRESETS[$presetKey])) {
            return redirect()
                ->route('v2.settings.stages.fields.index', $stage)
                ->withErrors(['preset' => 'النموذج المختار غير معرّف.']);
        }

        $preset = self::PRESETS[$presetKey];
        $existingCount = PipelineStageField::query()->where('pipeline_stage_id', $stage->id)->count();

        if ($existingCount > 0 && ! $request->boolean('confirm_overwrite')) {
            return redirect()
                ->route('v2.settings.stages.fields.index', $stage)
                ->with('preset_confirm_needed', $presetKey)
                ->withErrors(['preset' => 'المرحلة تحتوي على أسئلة حالية. أكّد التطبيق لإضافة حقول النموذج.']);
        }

        $nextPos = (int) (PipelineStageField::query()
            ->where('pipeline_stage_id', $stage->id)
            ->max('position') ?? 0) + 1;

        foreach ($preset['fields'] as $fData) {
            $key = $fData['key'];
            // If key collision on same stage, suffix with random token
            $exists = PipelineStageField::query()
                ->where('pipeline_stage_id', $stage->id)
                ->where('key', $key)
                ->exists();

            if ($exists) {
                $key = $key . '_' . Str::lower(Str::random(4));
            }

            PipelineStageField::query()->create([
                'pipeline_stage_id' => $stage->id,
                'key' => $key,
                'label_ar' => $fData['label_ar'],
                'label_en' => $fData['label_en'] ?? null,
                'type' => $fData['type'],
                'placeholder_ar' => $fData['placeholder_ar'] ?? null,
                'is_required' => (bool) ($fData['is_required'] ?? false),
                'options' => $fData['options'] ?? null,
                'conditions' => $fData['conditions'] ?? null,
                'show_on_transition' => true,
                'show_on_stage_view' => true,
                'show_in_history' => true,
                'is_active' => true,
                'position' => $nextPos++,
            ]);
        }

        StageFieldSchema::flushCache((int) $stage->id);

        return redirect()
            ->route('v2.settings.stages.fields.index', $stage)
            ->with('success', 'تم تطبيق نموذج الأسئلة الجاهز بنجاح.');
    }

    /**
     * @throws ValidationException
     */
    private function validateFieldInput(Request $request, PipelineStage $stage, ?PipelineStageField $field): array
    {
        $bindingType = $request->input('binding_type', $field?->binding_type ?? 'custom');
        if ($bindingType === 'canonical') {
            $target = (string) $request->input('binding_target', $field?->binding_target ?? '');
            if (! array_key_exists($target, PipelineStageField::CANONICAL_FIELDS)) {
                throw ValidationException::withMessages([
                    'binding_target' => 'الحقل الأساسي المختار غير صالح.',
                ]);
            }

            // Check duplicate canonical field on same stage
            $existsQuery = PipelineStageField::query()
                ->where('pipeline_stage_id', $stage->id)
                ->where('binding_type', 'canonical')
                ->where('binding_target', $target);

            if ($field !== null) {
                $existsQuery->where('id', '!=', $field->id);
            }

            if ($existsQuery->exists()) {
                throw ValidationException::withMessages([
                    'binding_target' => 'هذا الحقل الأساسي مضاف بالفعل لهذه المرحلة ولا يمكن تكراره.',
                ]);
            }

            $canonicalDef = PipelineStageField::CANONICAL_FIELDS[$target];
            if (! $request->filled('label_ar')) {
                $request->merge(['label_ar' => $canonicalDef['label_ar']]);
            }
            if (! $request->filled('label_en') && ! empty($canonicalDef['label_en'])) {
                $request->merge(['label_en' => $canonicalDef['label_en']]);
            }
            if (! $request->filled('type')) {
                $request->merge(['type' => $canonicalDef['type']]);
            }
            if (! $request->filled('placeholder_ar') && ! empty($canonicalDef['placeholder_ar'])) {
                $request->merge(['placeholder_ar' => $canonicalDef['placeholder_ar']]);
            }
            if (! $request->filled('placeholder_en') && ! empty($canonicalDef['placeholder_en'])) {
                $request->merge(['placeholder_en' => $canonicalDef['placeholder_en']]);
            }
        }

        $rules = [
            'binding_type' => ['nullable', 'string', Rule::in(array_keys(PipelineStageField::BINDING_TYPES))],
            'binding_target' => ['nullable', 'string', Rule::in(array_keys(PipelineStageField::CANONICAL_FIELDS))],
            'label_ar' => ['required', 'string', 'max:255'],
            'label_en' => ['nullable', 'string', 'max:255'],
            'type' => ['required', 'string', Rule::in(array_keys(PipelineStageField::TYPES))],
            'placeholder_ar' => ['nullable', 'string', 'max:255'],
            'placeholder_en' => ['nullable', 'string', 'max:255'],
            'help_text_ar' => ['nullable', 'string', 'max:1000'],
            'help_text_en' => ['nullable', 'string', 'max:1000'],
            'is_required' => ['nullable', 'boolean'],
            'default_value' => ['nullable', 'string', 'max:5000'],
            'show_on_transition' => ['nullable', 'boolean'],
            'show_on_stage_view' => ['nullable', 'boolean'],
            'show_in_history' => ['nullable', 'boolean'],
            'condition_field' => ['nullable', 'string', 'max:100'],
            'condition_operator' => ['nullable', 'string', Rule::in(array_keys(PipelineStageField::OPERATORS))],
            'condition_value' => ['nullable', 'string', 'max:255'],
            'options_raw' => ['nullable', 'string'],
            'options' => ['nullable', 'array'],
        ];

        // Safe auto-key generation for non-technical users
        if ($field === null) {
            $rawKey = $request->input('key');
            if (empty($rawKey)) {
                if ($bindingType === 'canonical' && ! empty($request->input('binding_target'))) {
                    $baseKey = (string) $request->input('binding_target');
                } else {
                    $baseKey = Str::slug((string) $request->input('label_en', '')) ?: 'q_' . Str::lower(Str::random(6));
                }
                $key = str_replace('-', '_', $baseKey);
            } else {
                $key = str_replace('-', '_', Str::slug((string) $rawKey));
            }

            // Ensure key is safe and unique within stage
            $origKey = $key;
            $counter = 1;
            while (PipelineStageField::query()
                ->where('pipeline_stage_id', $stage->id)
                ->where('key', $key)
                ->exists()) {
                $key = $origKey . '_' . $counter++;
            }

            $request->merge(['key' => $key]);
            $rules['key'] = ['required', 'string', 'max:100'];
        }

        return $request->validate($rules, [
            'label_ar.required' => 'اسم/نص السؤال مطلوب.',
            'type.required' => 'نوع الإجابة مطلوب.',
            'type.in' => 'نوع الإجابة المختار غير مدعوم.',
            'binding_target.required' => 'اختر الحقل الأساسي المطلوب ربطه.',
        ]);
    }

    private function extractOptions(Request $request, array $validated): ?array
    {
        if (! in_array($validated['type'], ['select', 'multiselect', 'radio'], true)) {
            return null;
        }

        $options = [];

        if ($request->has('options_list') && is_array($request->input('options_list'))) {
            $rawList = $request->input('options_list');
            foreach ($rawList as $opt) {
                if (is_array($opt) && ! empty($opt['label_ar'])) {
                    $val = ! empty($opt['value']) ? trim((string) $opt['value']) : Str::slug((string) $opt['label_ar'], '_');
                    if ($val === '') {
                        $val = 'opt_' . Str::lower(Str::random(4));
                    }
                    $options[] = [
                        'value' => $val,
                        'label_ar' => trim((string) $opt['label_ar']),
                        'label_en' => ! empty($opt['label_en']) ? trim((string) $opt['label_en']) : trim((string) $opt['label_ar']),
                    ];
                }
            }
        } elseif (! empty($validated['options_raw'])) {
            $options = StageFieldSchema::normalizeOptions($validated['options_raw']);
        } elseif (! empty($validated['options']) && is_array($validated['options'])) {
            $options = StageFieldSchema::normalizeOptions($validated['options']);
        }

        return ! empty($options) ? $options : null;
    }

    private function extractConditions(array $validated): ?array
    {
        if (empty($validated['condition_field'])) {
            return null;
        }

        return [
            'field' => (string) $validated['condition_field'],
            'operator' => (string) ($validated['condition_operator'] ?? 'equals'),
            'value' => $validated['condition_value'] ?? null,
        ];
    }

    private function assertCrmDatabase(): void
    {
        CrmDatabaseGuard::ensureConnected();
    }
}
