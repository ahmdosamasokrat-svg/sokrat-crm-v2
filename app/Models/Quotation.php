<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Quotation extends Model
{
    protected $fillable = [
        'quotation_no',
        'client_name',
        'location',
        'prepared_by',
        'quote_date',
        'system_title',
        'grand_total',
        'payload',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'quote_date' => 'date',
            'grand_total' => 'decimal:2',
            'payload' => 'array',
        ];
    }
}
