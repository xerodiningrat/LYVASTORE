<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinanceAlertLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'alert_date',
        'fingerprint',
        'level',
        'title',
        'body',
        'first_detected_at',
        'last_detected_at',
        'last_notified_at',
        'seen_count',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'alert_date' => 'date',
            'first_detected_at' => 'datetime',
            'last_detected_at' => 'datetime',
            'last_notified_at' => 'datetime',
            'meta' => 'array',
        ];
    }
}
