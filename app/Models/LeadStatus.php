<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeadStatus extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'pipeline_stage_id',
        'code',
        'name_ar',
        'position',
        'color',
        'is_terminal',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'is_terminal' => 'boolean',
            'deleted_at' => 'datetime',
        ];
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(
            PipelineStage::class,
            'pipeline_stage_id'
        );
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $query->whereHas(
            'stage',
            static fn (Builder $stages): Builder => $stages->visibleTo($user),
        );
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    public function localizedName(?string $locale = null): string
    {
        $loc = $locale ?? app()->getLocale();
        if ($loc === 'en') {
            $translationKey = 'crm.status_' . str_replace('-', '_', (string) $this->code);
            if (\Illuminate\Support\Facades\Lang::has($translationKey)) {
                return __($translationKey);
            }
        }

        return (string) $this->name_ar;
    }
}
