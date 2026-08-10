<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lead extends Model
{
    protected $fillable = [
        'lead_status_id',
        'name',
        'first_name',
        'last_name',
        'company_name',
        'activity',
        'governorate',
        'address',
        'users_count',
        'branches_count',
        'job_title',
        'disinterest_reason',
        'solution_type',
        'lines_count',
        'extensions',
        'departments',
        'quotation_file_path',
        'phone',
        'email',
        'source',
        'quotation_sent',
        'assigned_employee',
        'created_by',
        'notes',
        'next_follow_up_at',
    ];

    protected function casts(): array
    {
        return [
            'quotation_sent' => 'boolean',
            'users_count' => 'integer',
            'branches_count' => 'integer',
            'lines_count' => 'integer',
            'next_follow_up_at' => 'datetime',
        ];
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(
            LeadStatus::class,
            'lead_status_id'
        );
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(
            LeadStatusHistory::class
        )->orderByDesc('changed_at');
    }
}
