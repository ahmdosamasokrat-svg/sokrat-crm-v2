<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FollowupCustomerField extends Model
{
    use SoftDeletes;

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

    protected $fillable = [
        'key',
        'lead_attribute',
        'label_ar',
        'label_en',
        'type',
        'placeholder_ar',
        'placeholder_en',
        'help_text_ar',
        'help_text_en',
        'is_required',
        'options',
        'is_system',
        'is_active',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'is_system' => 'boolean',
            'is_active' => 'boolean',
            'position' => 'integer',
            'options' => 'array',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('position')->orderBy('id');
    }

    public function localizedLabel(?string $locale = null): string
    {
        return ($locale ?? app()->getLocale()) === 'en' && $this->label_en
            ? (string) $this->label_en
            : (string) $this->label_ar;
    }

    public function localizedPlaceholder(?string $locale = null): string
    {
        return ($locale ?? app()->getLocale()) === 'en' && $this->placeholder_en
            ? (string) $this->placeholder_en
            : (string) ($this->placeholder_ar ?? '');
    }

    public function localizedHelpText(?string $locale = null): string
    {
        return ($locale ?? app()->getLocale()) === 'en' && $this->help_text_en
            ? (string) $this->help_text_en
            : (string) ($this->help_text_ar ?? '');
    }

    public function normalizedOptions(): array
    {
        return array_values(array_filter(array_map(
            static function (mixed $option): ?array {
                if (is_string($option) && trim($option) !== '') {
                    return ['value' => trim($option), 'label_ar' => trim($option), 'label_en' => trim($option)];
                }

                if (! is_array($option) || trim((string) ($option['value'] ?? '')) === '') {
                    return null;
                }

                $value = trim((string) $option['value']);
                $labelAr = trim((string) ($option['label_ar'] ?? $option['label'] ?? $value));

                return [
                    'value' => $value,
                    'label_ar' => $labelAr !== '' ? $labelAr : $value,
                    'label_en' => trim((string) ($option['label_en'] ?? $labelAr)) ?: $labelAr,
                ];
            },
            is_array($this->options) ? $this->options : [],
        )));
    }
}
