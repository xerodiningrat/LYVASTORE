<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManualStockItem extends Model
{
    public const STATUS_AVAILABLE = 'available';
    public const STATUS_RESERVED = 'reserved';
    public const STATUS_USED = 'used';

    protected $fillable = [
        'product_id',
        'product_name',
        'package_label',
        'stock_label',
        'stock_value',
        'notes',
        'status',
        'created_by_user_id',
        'reserved_for_transaction_id',
        'reserved_at',
        'used_at',
    ];

    protected function casts(): array
    {
        return [
            'reserved_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function reservedTransaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'reserved_for_transaction_id');
    }
}
