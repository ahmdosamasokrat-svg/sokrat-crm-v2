<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\PipelineStage;
use App\Models\PipelineStageField;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class StageFieldTemplates
{
    /**
     * Reusable field templates library.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function all(): array
    {
        return [
            'customer_contact' => [
                'key' => 'customer_contact',
                'name_ar' => 'بيانات التواصل مع العميل',
                'name_en' => 'Customer Contact Information',
                'description_ar' => 'إضافة حقول التواصل الأساسية: اسم العميل، الهاتف، البريد، والعنوان.',
                'description_en' => 'Basic contact fields: Customer Name, Phone, Email, and Address.',
                'icon' => 'bi-person-lines-fill',
                'fields' => [
                    [
                        'key' => 'name',
                        'label_ar' => 'اسم العميل بالكامل',
                        'label_en' => 'Customer Full Name',
                        'type' => 'text',
                        'binding_type' => 'canonical',
                        'binding_target' => 'name',
                        'is_required' => true,
                    ],
                    [
                        'key' => 'phone',
                        'label_ar' => 'رقم الهاتف',
                        'label_en' => 'Phone Number',
                        'type' => 'tel',
                        'binding_type' => 'canonical',
                        'binding_target' => 'phone',
                        'is_required' => true,
                    ],
                    [
                        'key' => 'email',
                        'label_ar' => 'البريد الإلكتروني',
                        'label_en' => 'Email Address',
                        'type' => 'email',
                        'binding_type' => 'canonical',
                        'binding_target' => 'email',
                        'is_required' => false,
                    ],
                    [
                        'key' => 'address',
                        'label_ar' => 'العنوان التفصيلي',
                        'label_en' => 'Address',
                        'type' => 'text',
                        'binding_type' => 'canonical',
                        'binding_target' => 'address',
                        'is_required' => false,
                    ],
                ],
            ],

            'company_info' => [
                'key' => 'company_info',
                'name_ar' => 'بيانات الشركة والمؤسسة',
                'name_en' => 'Company & Organization Details',
                'description_ar' => 'حقول النشاط المؤسسي: اسم الشركة، النشاط، المنصب، المحافظة، والعنوان.',
                'description_en' => 'Organization fields: Company Name, Activity, Job Title, Governorate, Address.',
                'icon' => 'bi-building',
                'fields' => [
                    [
                        'key' => 'company_name',
                        'label_ar' => 'اسم الشركة / المؤسسة',
                        'label_en' => 'Company Name',
                        'type' => 'text',
                        'binding_type' => 'canonical',
                        'binding_target' => 'company_name',
                        'is_required' => true,
                    ],
                    [
                        'key' => 'activity',
                        'label_ar' => 'النشاط التجاري',
                        'label_en' => 'Business Activity',
                        'type' => 'text',
                        'binding_type' => 'canonical',
                        'binding_target' => 'activity',
                        'is_required' => false,
                    ],
                    [
                        'key' => 'job_title',
                        'label_ar' => 'المسمى الوظيفي / المنصب',
                        'label_en' => 'Job Title',
                        'type' => 'text',
                        'binding_type' => 'canonical',
                        'binding_target' => 'job_title',
                        'is_required' => false,
                    ],
                    [
                        'key' => 'governorate',
                        'label_ar' => 'المحافظة / المنطقة',
                        'label_en' => 'Governorate / Region',
                        'type' => 'text',
                        'binding_type' => 'canonical',
                        'binding_target' => 'governorate',
                        'is_required' => false,
                    ],
                    [
                        'key' => 'address',
                        'label_ar' => 'العنوان التفصيلي',
                        'label_en' => 'Address',
                        'type' => 'text',
                        'binding_type' => 'canonical',
                        'binding_target' => 'address',
                        'is_required' => false,
                    ],
                ],
            ],

            'appointment' => [
                'key' => 'appointment',
                'name_ar' => 'موعد ومقابلة',
                'name_en' => 'Appointment & Meeting Scheduling',
                'description_ar' => 'تحديد موعد المقابلة: تاريخ الزيارة، وقت الزيارة، وملاحظات الموعد.',
                'description_en' => 'Meeting scheduling: Appointment Date, Time, and Notes.',
                'icon' => 'bi-calendar-event',
                'fields' => [
                    [
                        'key' => 'appointment_date',
                        'label_ar' => 'تاريخ المقابلة',
                        'label_en' => 'Meeting Date',
                        'type' => 'date',
                        'binding_type' => 'custom',
                        'is_required' => true,
                    ],
                    [
                        'key' => 'appointment_time',
                        'label_ar' => 'وقت المقابلة',
                        'label_en' => 'Meeting Time',
                        'type' => 'time',
                        'binding_type' => 'custom',
                        'is_required' => true,
                    ],
                    [
                        'key' => 'appointment_notes',
                        'label_ar' => 'ملاحظات المقابلة ومكانها',
                        'label_en' => 'Meeting Location & Notes',
                        'type' => 'textarea',
                        'binding_type' => 'custom',
                        'is_required' => false,
                    ],
                ],
            ],

            'followup' => [
                'key' => 'followup',
                'name_ar' => 'متابعة وإعادة اتصال',
                'name_en' => 'Follow-up & Callback',
                'description_ar' => 'تحديد موعد المتابعة القادمة وتاريخ الاتصال وملاحظات المحاولة.',
                'description_en' => 'Follow-up scheduling: Callback Date, Time, and Attempt Notes.',
                'icon' => 'bi-telephone-forward',
                'fields' => [
                    [
                        'key' => 'callback_date',
                        'label_ar' => 'تاريخ إعادة الاتصال',
                        'label_en' => 'Callback Date',
                        'type' => 'date',
                        'binding_type' => 'custom',
                        'is_required' => true,
                    ],
                    [
                        'key' => 'callback_time',
                        'label_ar' => 'وقت إعادة الاتصال',
                        'label_en' => 'Callback Time',
                        'type' => 'time',
                        'binding_type' => 'custom',
                        'is_required' => false,
                    ],
                    [
                        'key' => 'callback_notes',
                        'label_ar' => 'ملاحظات محاولة الاتصال',
                        'label_en' => 'Attempt Notes',
                        'type' => 'textarea',
                        'binding_type' => 'custom',
                        'is_required' => false,
                    ],
                ],
            ],

            'quotation' => [
                'key' => 'quotation',
                'name_ar' => 'تفاصيل عرض السعر',
                'name_en' => 'Quotation & Proposal Details',
                'description_ar' => 'بيانات العرض: نوع النظام أو الحل، عدد الخطوط، والميزانية المتوقعة.',
                'description_en' => 'Proposal details: Solution Type, Lines Count, and Expected Budget.',
                'icon' => 'bi-file-earmark-text',
                'fields' => [
                    [
                        'key' => 'solution_type',
                        'label_ar' => 'نوع النظام / الحل المطلوب',
                        'label_en' => 'Solution Type',
                        'type' => 'select',
                        'binding_type' => 'canonical',
                        'binding_target' => 'solution_type',
                        'options' => [
                            ['value' => 'call_center', 'label_ar' => 'Call Center (سنترال سحابي)', 'label_en' => 'Call Center'],
                            ['value' => 'erp', 'label_ar' => 'ERP (إدارة موارد المؤسسة)', 'label_en' => 'ERP System'],
                            ['value' => 'crm', 'label_ar' => 'CRM (إدارة علاقات العملاء)', 'label_en' => 'CRM System'],
                        ],
                        'is_required' => true,
                    ],
                    [
                        'key' => 'lines_count',
                        'label_ar' => 'عدد الخطوط المطلوبة',
                        'label_en' => 'Lines Count',
                        'type' => 'number',
                        'binding_type' => 'canonical',
                        'binding_target' => 'lines_count',
                        'is_required' => false,
                    ],
                    [
                        'key' => 'expected_amount',
                        'label_ar' => 'المبلغ التقديري / الميزانية',
                        'label_en' => 'Estimated Amount / Budget',
                        'type' => 'currency',
                        'binding_type' => 'custom',
                        'is_required' => false,
                    ],
                ],
            ],

            'contract_service' => [
                'key' => 'contract_service',
                'name_ar' => 'بيانات التعاقد والخدمة',
                'name_en' => 'Contract & Service Details',
                'description_ar' => 'توثيق العقد: قيمة التعاقد، تاريخ البدء، وملاحظات شروط الاتفاق.',
                'description_en' => 'Contract details: Deal Value, Start Date, Terms & Notes.',
                'icon' => 'bi-file-earmark-check',
                'fields' => [
                    [
                        'key' => 'contract_value',
                        'label_ar' => 'قيمة التعاقد النهائية',
                        'label_en' => 'Contract Value',
                        'type' => 'currency',
                        'binding_type' => 'custom',
                        'is_required' => true,
                    ],
                    [
                        'key' => 'start_date',
                        'label_ar' => 'تاريخ بدء الخدمة',
                        'label_en' => 'Service Start Date',
                        'type' => 'date',
                        'binding_type' => 'custom',
                        'is_required' => false,
                    ],
                    [
                        'key' => 'contract_notes',
                        'label_ar' => 'شروط وملاحظات التعاقد',
                        'label_en' => 'Terms & Notes',
                        'type' => 'textarea',
                        'binding_type' => 'custom',
                        'is_required' => false,
                    ],
                ],
            ],
        ];
    }

    /**
     * Get a specific template by key.
     */
    public static function get(string $key): ?array
    {
        return self::all()[$key] ?? null;
    }

    /**
     * Apply a field template atomically to a stage, skipping canonical duplicates.
     *
     * @param PipelineStage $stage
     * @param string $templateKey
     * @return array{success: bool, added: int, skipped: int, template_name: string}
     * @throws InvalidArgumentException
     */
    public static function apply(PipelineStage $stage, string $templateKey): array
    {
        $template = self::get($templateKey);
        if ($template === null) {
            throw new InvalidArgumentException("Template '{$templateKey}' is not found.");
        }

        return DB::transaction(function () use ($stage, $template, $templateKey): array {
            // Load existing stage fields
            $existingFields = $stage->fields()->get();
            $existingCanonicalTargets = $existingFields
                ->where('binding_type', 'canonical')
                ->pluck('binding_target')
                ->filter()
                ->all();

            $existingKeys = $existingFields->pluck('key')->all();

            $added = 0;
            $skipped = 0;

            $maxPos = (int) ($existingFields->max('position') ?? 0);

            foreach ($template['fields'] as $f) {
                $isCanonical = ($f['binding_type'] ?? 'custom') === 'canonical';
                $bindingTarget = $f['binding_target'] ?? null;

                // 1. Skip canonical duplicates
                if ($isCanonical && ! empty($bindingTarget) && in_array($bindingTarget, $existingCanonicalTargets, true)) {
                    $skipped++;
                    continue;
                }

                // 2. Generate unique key within stage
                $baseKey = (string) ($f['key'] ?? 'field');
                $key = $baseKey;
                $counter = 1;
                while (in_array($key, $existingKeys, true)) {
                    $key = $baseKey . '_' . $counter++;
                }
                $existingKeys[] = $key;

                // 3. Increment position
                $maxPos++;

                // 4. Create the standard PipelineStageField record
                PipelineStageField::query()->create([
                    'pipeline_stage_id' => $stage->id,
                    'key' => $key,
                    'label_ar' => $f['label_ar'],
                    'label_en' => $f['label_en'] ?? null,
                    'type' => $f['type'],
                    'binding_type' => $f['binding_type'],
                    'binding_target' => $bindingTarget,
                    'is_required' => (bool) ($f['is_required'] ?? false),
                    'default_value' => $f['default_value'] ?? null,
                    'options' => $f['options'] ?? null,
                    'is_active' => true,
                    'position' => $maxPos,
                ]);

                if ($isCanonical && ! empty($bindingTarget)) {
                    $existingCanonicalTargets[] = $bindingTarget;
                }

                $added++;
            }

            StageFieldSchema::flushCache((int) $stage->id);

            return [
                'success' => true,
                'added' => $added,
                'skipped' => $skipped,
                'template_name' => app()->getLocale() === 'en' ? $template['name_en'] : $template['name_ar'],
            ];
        });
    }
}
