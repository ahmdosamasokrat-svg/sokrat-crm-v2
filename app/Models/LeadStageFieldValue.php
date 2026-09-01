<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadStageFieldValue extends Model
{
    protected $fillable = [
        'lead_id',
        'pipeline_stage_id',
        'pipeline_stage_field_id',
        'lead_status_history_id',
        'field_key',
        'field_type',
        'value',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(PipelineStage::class, 'pipeline_stage_id');
    }

    public function field(): BelongsTo
    {
        return $this->belongsTo(PipelineStageField::class, 'pipeline_stage_field_id')->withTrashed();
    }

    public function statusHistory(): BelongsTo
    {
        return $this->belongsTo(LeadStatusHistory::class, 'lead_status_history_id');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * Get the decoded typed value.
     */
    public function getTypedValue(): mixed
    {
        if ($this->value === null || $this->value === '') {
            return null;
        }

        switch ($this->field_type) {
            case 'checkbox':
                return (bool) $this->value;
            case 'number':
                return is_numeric($this->value) ? 0 + $this->value : $this->value;
            case 'datetime':
                try {
                    return Carbon::parse($this->value);
                } catch (\Throwable) {
                    return $this->value;
                }
            case 'date':
                try {
                    return Carbon::parse($this->value)->startOfDay();
                } catch (\Throwable) {
                    return $this->value;
                }
            case 'multiselect':
                $decoded = json_decode($this->value, true);
                if (is_array($decoded)) {
                    return $decoded;
                }
                return explode(',', $this->value);
            case 'select':
            case 'text':
            case 'textarea':
            case 'email':
            case 'tel':
            case 'url':
            default:
                return (string) $this->value;
        }
    }

    /**
     * Human-friendly formatted representation of value for display.
     */
    public function formattedValue(?string $locale = null): string
    {
        $loc = $locale ?? app()->getLocale();

        if ($this->value === null || $this->value === '') {
            return '—';
        }

        switch ($this->field_type) {
            case 'checkbox':
                return (bool) $this->value
                    ? ($loc === 'en' ? 'Yes' : 'نعم')
                    : ($loc === 'en' ? 'No' : 'لا');

            case 'datetime':
                try {
                    return Carbon::parse($this->value)->format('Y-m-d H:i');
                } catch (\Throwable) {
                    return (string) $this->value;
                }

            case 'date':
                try {
                    return Carbon::parse($this->value)->format('Y-m-d');
                } catch (\Throwable) {
                    return (string) $this->value;
                }

            case 'select':
                $field = $this->field;
                if ($field) {
                    $opts = $field->normalizedOptions();
                    foreach ($opts as $opt) {
                        if ((string) $opt['value'] === (string) $this->value) {
                            return $loc === 'en' ? $opt['label_en'] : $opt['label_ar'];
                        }
                    }
                }
                return (string) $this->value;

            case 'multiselect':
                $items = $this->getTypedValue();
                if (! is_array($items)) {
                    return (string) $this->value;
                }

                $field = $this->field;
                if ($field) {
                    $opts = $field->normalizedOptions();
                    $labels = [];
                    foreach ($items as $item) {
                        $found = false;
                        foreach ($opts as $opt) {
                            if ((string) $opt['value'] === (string) $item) {
                                $labels[] = $loc === 'en' ? $opt['label_en'] : $opt['label_ar'];
                                $found = true;
                                break;
                            }
                        }
                        if (! $found) {
                            $labels[] = (string) $item;
                        }
                    }
                    return implode(', ', $labels);
                }

                return implode(', ', $items);

            case 'number':
            case 'text':
            case 'textarea':
            case 'email':
            case 'tel':
            case 'url':
            default:
                return (string) $this->value;
        }
    }
}
