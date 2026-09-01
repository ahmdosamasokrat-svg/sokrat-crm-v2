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

    public const TYPES = [
        'text' => 'نص قصير',
        'textarea' => 'نص طويل / ملاحظات',
        'number' => 'رقم',
        'email' => 'بريد إلكتروني',
        'tel' => 'رقم هاتف',
        'url' => 'رابط موقع',
        'date' => 'تاريخ',
        'datetime' => 'تاريخ ووقت',
        'select' => 'قائمة اختيار أحادي',
        'multiselect' => 'قائمة اختيار متعدد',
        'checkbox' => 'مربع اختيار (نعم/لا)',
    ];

    public const OPERATORS = [
        'equals' => 'يساوي',
        'not_equals' => 'لا يساوي',
        'is_checked' => 'محدد (نعم)',
        'is_not_checked' => 'غير محدد (لا)',
        'is_empty' => 'فارغ',
        'is_not_empty' => 'غير فارغ',
    ];

    protected $fillable = [
        'pipeline_stage_id',
        'key',
        'label_ar',
        'label_en',
        'type',
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
