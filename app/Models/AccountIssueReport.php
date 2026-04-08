<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountIssueReport extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'user_id',
        'public_id',
        'status',
        'product_id',
        'product_name',
        'issue_type',
        'transaction_reference',
        'account_email',
        'contact_whatsapp',
        'issue_message',
        'proof_path',
        'proof_url',
        'telegram_notified_at',
        'resolved_at',
        'admin_notes',
    ];

    protected function casts(): array
    {
        return [
            'telegram_notified_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
