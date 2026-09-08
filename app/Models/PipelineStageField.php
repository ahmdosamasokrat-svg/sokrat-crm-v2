<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class PipelineStageField extends Model
{
    use SoftDeletes;

    public const CACHE_KEY_PREFIX = 'crm.pipeline_stage_fields.stage_';

    public const BINDING_TYPES = [
        'custom' => 'حقل مخصص (Custom Field)',
        'canonical' => 'بيانات العميل (Customer Data)',
    ];

    public const CANONICAL_FIELDS = [
        'name' => [
            'target' => 'name',
            'label_ar' => 'اسم العميل بالكامل',
            'label_en' => 'Full Customer Name',
            'type' => 'text',
            'rules' => ['string', 'max:150'],
            'placeholder_ar' => 'أدخل الاسم بالكامل',
            'placeholder_en' => 'Enter full name',
        ],
        'first_name' => [
            'target' => 'first_name',
            'label_ar' => 'الاسم الأول / الاسم الشخصي',
            'label_en' => 'First Name',
            'type' => 'text',
            'rules' => ['string', 'max:100'],
            'placeholder_ar' => 'أدخل الاسم الأول',
            'placeholder_en' => 'Enter first name',
        ],
        'last_name' => [
            'target' => 'last_name',
            'label_ar' => 'اسم العائلة',
            'label_en' => 'Last Name',
            'type' => 'text',
            'rules' => ['string', 'max:100'],
            'placeholder_ar' => 'أدخل اسم العائلة',
            'placeholder_en' => 'Enter last name',
        ],
        'phone' => [
            'target' => 'phone',
            'label_ar' => 'رقم الهاتف',
            'label_en' => 'Phone Number',
            'type' => 'tel',
            'rules' => ['string', 'max:50'],
            'placeholder_ar' => 'مثال: 05xxxxxxxx',
            'placeholder_en' => 'e.g. 05xxxxxxxx',
        ],
        'email' => [
            'target' => 'email',
            'label_ar' => 'البريد الإلكتروني',
            'label_en' => 'Email Address',
            'type' => 'email',
            'rules' => ['email', 'max:190'],
            'placeholder_ar' => 'example@domain.com',
            'placeholder_en' => 'example@domain.com',
        ],
        'company_name' => [
            'target' => 'company_name',
            'label_ar' => 'اسم الشركة / المؤسسة',
            'label_en' => 'Company / Organization',
            'type' => 'text',
            'rules' => ['string', 'max:150'],
            'placeholder_ar' => 'أدخل اسم الشركة',
            'placeholder_en' => 'Enter company name',
        ],
        'activity' => [
            'target' => 'activity',
            'label_ar' => 'النشاط التجاري',
            'label_en' => 'Business Activity',
            'type' => 'text',
            'rules' => ['string', 'max:150'],
            'placeholder_ar' => 'مثال: تجارة التجزئة، مقاولات',
            'placeholder_en' => 'e.g. Retail, Contracting',
        ],
        'governorate' => [
            'target' => 'governorate',
            'label_ar' => 'المحافظة / المنطقة',
            'label_en' => 'Governorate / Region',
            'type' => 'text',
            'rules' => ['string', 'max:100'],
            'placeholder_ar' => 'أدخل المحافظة أو المنطقة',
            'placeholder_en' => 'Enter governorate or region',
        ],
        'address' => [
            'target' => 'address',
            'label_ar' => 'العنوان التفصيلي',
            'label_en' => 'Address',
            'type' => 'text',
            'rules' => ['string', 'max:255'],
            'placeholder_ar' => 'المدينة، الحي، الشارع',
            'placeholder_en' => 'City, District, Street',
        ],
        'job_title' => [
            'target' => 'job_title',
            'label_ar' => 'المسمى الوظيفي / المنصب',
            'label_en' => 'Job Title',
            'type' => 'text',
            'rules' => ['string', 'max:150'],
            'placeholder_ar' => 'مثال: المدير العام، مسؤول المشتريات',
            'placeholder_en' => 'e.g. General Manager, Purchasing Officer',
        ],
        'users_count' => [
            'target' => 'users_count',
            'label_ar' => 'عدد المستخدمين',
            'label_en' => 'Users Count',
            'type' => 'number',
            'rules' => ['integer', 'min:0', 'max:1000000'],
            'placeholder_ar' => 'أدخل عدد المستخدمين',
            'placeholder_en' => 'Enter users count',
        ],
        'branches_count' => [
            'target' => 'branches_count',
            'label_ar' => 'عدد الفروع',
            'label_en' => 'Branches Count',
            'type' => 'number',
            'rules' => ['integer', 'min:0', 'max:1000000'],
            'placeholder_ar' => 'أدخل عدد الفروع',
            'placeholder_en' => 'Enter branches count',
        ],
        'notes' => [
            'target' => 'notes',
            'label_ar' => 'ملاحظات العميل',
            'label_en' => 'Lead Notes',
            'type' => 'textarea',
            'rules' => ['string', 'max:5000'],
            'placeholder_ar' => 'أدخل أي ملاحظات هامة',
            'placeholder_en' => 'Enter notes',
        ],
        'solution_type' => [
            'target' => 'solution_type',
            'label_ar' => 'نوع النظام / الحل المطلوب',
            'label_en' => 'System / Solution Type',
            'type' => 'text',
            'rules' => ['string', 'max:100'],
            'placeholder_ar' => 'نوع النظام أو الخدمة المطلوبة',
            'placeholder_en' => 'Requested system or service',
        ],
        'lines_count' => [
            'target' => 'lines_count',
            'label_ar' => 'عدد الخطوط',
            'label_en' => 'Lines Count',
            'type' => 'number',
            'rules' => ['integer', 'min:0', 'max:1000000'],
            'placeholder_ar' => 'عدد الخطوط المطلوبة',
            'placeholder_en' => 'Requested lines count',
        ],
        'extensions' => [
            'target' => 'extensions',
            'label_ar' => 'الملحقات والتحويلات',
            'label_en' => 'Extensions',
            'type' => 'text',
            'rules' => ['string', 'max:255'],
            'placeholder_ar' => 'تفاصيل الملحقات والتحويلات',
            'placeholder_en' => 'Extensions details',
        ],
        'departments' => [
            'target' => 'departments',
            'label_ar' => 'الأقسام المعنية',
            'label_en' => 'Departments',
            'type' => 'text',
            'rules' => ['string', 'max:255'],
            'placeholder_ar' => 'مثال: المبيعات، خدمة العملاء',
            'placeholder_en' => 'e.g. Sales, Support',
        ],
        'disinterest_reason' => [
            'target' => 'disinterest_reason',
            'label_ar' => 'سبب عدم الاهتمام / الرفض',
            'label_en' => 'Disinterest / Lost Reason',
            'type' => 'textarea',
            'rules' => ['string', 'max:2000'],
            'placeholder_ar' => 'اكتب سبب عدم الاهتمام بالتفصيل',
            'placeholder_en' => 'Explain disinterest reason',
        ],
        'quotation_file_path' => [
            'target' => 'quotation_file_path',
            'label_ar' => 'ملف عرض السعر الرسمي',
            'label_en' => 'Quotation File',
            'type' => 'file',
            'rules' => ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx,png,jpg,jpeg', 'max:10240'],
            'placeholder_ar' => 'ارفق ملف عرض السعر',
            'placeholder_en' => 'Attach quotation file',
        ],
    ];

    public const TYPES = [
        'text' => 'نص قصير',
        'textarea' => 'نص طويل / ملاحظات',
        'number' => 'رقم',
        'email' => 'بريد إلكتروني',
        'tel' => 'رقم هاتف',
        'url' => 'رابط موقع',
        'date' => 'تاريخ',
        'time' => 'وقت',
        'datetime' => 'تاريخ ووقت',
        'select' => 'قائمة اختيار أحادي',
        'multiselect' => 'قائمة اختيار متعدد',
        'radio' => 'أزرار اختيار أحادي (Radio)',
        'checkbox' => 'مربع اختيار (نعم/لا)',
        'boolean' => 'نعم / لا (Yes / No)',
        'currency' => 'مبلغ مالي / عملة',
        'file' => 'ملف مرفق (File)',
        'image' => 'صورة (Image)',
        'pdf' => 'مستند PDF',
    ];

    public const OPERATORS = [
        'equals' => 'يساوي',
        'not_equals' => 'لا يساوي',
        'is_checked' => 'محدد (نعم)',
        'is_not_checked' => 'غير محدد (لا)',
        'is_empty' => 'فارغ',
        'is_not_empty' => 'غير فارغ',
        'contains' => 'يحتوي على',
        'in' => 'ضمن قائمة',
        'is_true' => 'صحيح (True)',
        'is_false' => 'خاطئ (False)',
    ];

    protected $fillable = [
        'pipeline_stage_id',
        'key',
        'label_ar',
        'label_en',
        'type',
        'binding_type',
        'binding_target',
        'placeholder_ar',
        'placeholder_en',
        'help_text_ar',
        'help_text_en',
        'is_required',
        'options',
        'validation_rules',
        'conditions',
        'show_on_transition',
        'show_on_stage_view',
        'show_in_history',
        'is_active',
        'position',
        'default_value',
    ];

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'show_on_transition' => 'boolean',
            'show_on_stage_view' => 'boolean',
            'show_in_history' => 'boolean',
            'is_active' => 'boolean',
            'position' => 'integer',
            'options' => 'array',
            'validation_rules' => 'array',
            'conditions' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::saved(static function (PipelineStageField $field): void {
            static::flushCache((int) $field->pipeline_stage_id);
        });

        static::deleted(static function (PipelineStageField $field): void {
            static::flushCache((int) $field->pipeline_stage_id);
        });

        static::restored(static function (PipelineStageField $field): void {
            static::flushCache((int) $field->pipeline_stage_id);
        });
    }

    public static function flushCache(int $stageId): void
    {
        Cache::forget(self::CACHE_KEY_PREFIX . $stageId);
        Cache::forget(self::CACHE_KEY_PREFIX . $stageId . '_all');
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(PipelineStage::class, 'pipeline_stage_id');
    }

    public function values(): HasMany
    {
        return $this->hasMany(LeadStageFieldValue::class, 'pipeline_stage_field_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('position')->orderBy('id');
    }

    public function scopeForTransition(Builder $query): Builder
    {
        return $query->where('show_on_transition', true);
    }
    public function scopeCanonical(Builder $query): Builder
    {
        return $query->where('binding_type', 'canonical');
    }

    public function scopeCustom(Builder $query): Builder
    {
        return $query->where(function (Builder $q): void {
            $q->where('binding_type', 'custom')
                ->orWhereNull('binding_type');
        });
    }

    public function isCanonical(): bool
    {
        return $this->binding_type === 'canonical';
    }

    public function isCustom(): bool
    {
        return ! $this->isCanonical();
    }

    public function canonicalConfig(): ?array
    {
        if (! $this->isCanonical() || empty($this->binding_target)) {
            return null;
        }

        return self::CANONICAL_FIELDS[$this->binding_target] ?? null;
    }

    public function getInitialValue(?Lead $lead = null): mixed
    {
        if ($this->isCanonical() && $lead !== null && ! empty($this->binding_target)) {
            return $lead->getAttribute($this->binding_target);
        }

        return $this->default_value;
    }


    public function localizedLabel(?string $locale = null): string
    {
        $loc = $locale ?? app()->getLocale();
        if ($loc === 'en' && ! empty($this->label_en)) {
            return (string) $this->label_en;
        }

        return (string) $this->label_ar;
    }

    public function localizedPlaceholder(?string $locale = null): string
    {
        $loc = $locale ?? app()->getLocale();
        if ($loc === 'en' && ! empty($this->placeholder_en)) {
            return (string) $this->placeholder_en;
        }

        return (string) ($this->placeholder_ar ?? '');
    }

    public function localizedHelpText(?string $locale = null): string
    {
        $loc = $locale ?? app()->getLocale();
        if ($loc === 'en' && ! empty($this->help_text_en)) {
            return (string) $this->help_text_en;
        }

        return (string) ($this->help_text_ar ?? '');
    }

    /**
     * Normalized options collection for select/multiselect types.
     * Each item: ['value' => '...', 'label_ar' => '...', 'label_en' => '...']
     */
    public function normalizedOptions(): array
    {
        $opts = $this->options;
        if (! is_array($opts) || empty($opts)) {
            return [];
        }

        $normalized = [];
        foreach ($opts as $item) {
            if (is_string($item)) {
                $val = trim($item);
                $normalized[] = [
                    'value' => $val,
                    'label_ar' => $val,
                    'label_en' => $val,
                ];
            } elseif (is_array($item) && isset($item['value'])) {
                $val = (string) $item['value'];
                $labelAr = (string) ($item['label_ar'] ?? $item['label'] ?? $val);
                $labelEn = (string) ($item['label_en'] ?? $labelAr);
                $normalized[] = [
                    'value' => $val,
                    'label_ar' => $labelAr,
                    'label_en' => $labelEn,
                ];
            }
        }

        return $normalized;
    }

    public function getRequiredAttribute(): bool
    {
        return (bool) $this->is_required;
    }

    public function setRequiredAttribute(bool|int|string $value): void
    {
        $this->attributes['is_required'] = (bool) $value;
    }
}
