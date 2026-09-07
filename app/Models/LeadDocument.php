<?php

declare(strict_types=1);

namespace App\Models;

use App\Security\CrmPermission;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class LeadDocument extends Model
{
    public const CATEGORY_QUOTATION = 'quotation';
    public const CATEGORY_PDF = 'pdf';
    public const CATEGORY_IMAGE = 'image';
    public const CATEGORY_ATTACHMENT = 'attachment';

    public const CATEGORIES = [
        self::CATEGORY_QUOTATION => 'عرض سعر',
        self::CATEGORY_PDF => 'ملف PDF',
        self::CATEGORY_IMAGE => 'صورة',
        self::CATEGORY_ATTACHMENT => 'مرفق عام',
    ];

    protected $fillable = [
        'lead_id',
        'pipeline_stage_id',
        'pipeline_stage_field_id',
        'lead_status_history_id',
        'category',
        'original_name',
        'stored_name',
        'disk',
        'path',
        'mime_type',
        'size',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'lead_id');
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(PipelineStage::class, 'pipeline_stage_id');
    }

    public function field(): BelongsTo
    {
        return $this->belongsTo(PipelineStageField::class, 'pipeline_stage_field_id');
    }

    public function history(): BelongsTo
    {
        return $this->belongsTo(LeadStatusHistory::class, 'lead_status_history_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function scopeQuotations(Builder $query): Builder
    {
        return $query->where('category', self::CATEGORY_QUOTATION);
    }

    public function scopeCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    public function scopeLatestFirst(Builder $query): Builder
    {
        return $query->orderByDesc('created_at')->orderByDesc('id');
    }

    public function isQuotation(): bool
    {
        return $this->category === self::CATEGORY_QUOTATION;
    }

    public function isPdf(): bool
    {
        return $this->mime_type === 'application/pdf'
            || str_ends_with(strtolower($this->path), '.pdf');
    }

    public function isImage(): bool
    {
        return str_starts_with((string) $this->mime_type, 'image/')
            || in_array(strtolower(pathinfo($this->path, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'webp', 'gif'], true);
    }

    public function formattedSize(): string
    {
        $bytes = (int) $this->size;
        if ($bytes <= 0) {
            return '—';
        }

        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 1) . ' KB';
        }

        return $bytes . ' B';
    }

    public function isAccessibleTo(User $user): bool
    {
        $lead = $this->lead ?? Lead::query()->find($this->lead_id);
        if ($lead === null || ! $lead->isAccessibleTo($user)) {
            return false;
        }

        if ($this->isQuotation()) {
            return $user->hasPermission(CrmPermission::QUOTATIONS_VIEW);
        }

        return true;
    }

    public function existsOnDisk(): bool
    {
        try {
            return Storage::disk($this->disk ?: 'local')->exists($this->path);
        } catch (\Throwable) {
            return false;
        }
    }
}
