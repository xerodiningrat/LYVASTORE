<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Transaction extends Model
{
    use HasFactory;

    public const PRODUCT_SOURCE_MANUAL = 'manual';
    public const PRODUCT_SOURCE_MANUAL_STOCK = 'manual-stock';
    public const PRODUCT_SOURCE_VIPAYMENT = 'vipayment';

    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_EXPIRED = 'expired';

    public const PAYMENT_STATUS_UNPAID = 'unpaid';
    public const PAYMENT_STATUS_PAID = 'paid';
    public const PAYMENT_STATUS_FAILED = 'failed';
    public const PAYMENT_STATUS_EXPIRED = 'expired';

    public const MANUAL_FULFILLMENT_WAITING_STOCK = 'waiting-stock';
    public const MANUAL_FULFILLMENT_READY_TO_SEND = 'ready-to-send';
    public const MANUAL_FULFILLMENT_SENT = 'sent';

    protected $fillable = [
        'user_id',
        'public_id',
        'guest_token',
        'status',
        'payment_status',
        'vipayment_status',
        'manual_fulfillment_status',
        'product_source',
        'product_id',
        'product_name',
        'product_image',
        'package_code',
        'package_label',
        'quantity',
        'payment_method_code',
        'payment_method_label',
        'payment_method_type',
        'payment_method_image',
        'payment_badge',
        'payment_caption',
        'payment_display_type',
        'payment_reference_label',
        'payment_reference_value',
        'duitku_reference',
        'duitku_payment_url',
        'duitku_app_url',
        'duitku_qr_string',
        'vipayment_endpoint',
        'vipayment_trx_ids',
        'subtotal',
        'promo_code',
        'promo_label',
        'promo_discount',
        'promo_snapshot',
        'coin_spent_amount',
        'coin_spent_value',
        'coin_refunded_at',
        'total',
        'checkout_notice',
        'guarantee_text',
        'notes',
        'summary_rows',
        'account_fields',
        'contact_fields',
        'customer_name',
        'customer_email',
        'customer_whatsapp',
        'paid_at',
        'expires_at',
        'last_synced_at',
        'error_message',
        'fulfillment_note',
        'lyvaflow_processing_notified_at',
        'lyvaflow_completed_notified_at',
        'lyvaflow_failed_notified_at',
        'admin_manual_order_notified_at',
        'customer_completed_emailed_at',
        'fulfilled_at',
        'fulfilled_by_user_id',
        'rating_score',
        'rating_comment',
        'rated_at',
    ];

    protected function casts(): array
    {
        return [
            'notes' => 'array',
            'summary_rows' => 'array',
            'account_fields' => 'array',
            'contact_fields' => 'array',
            'vipayment_trx_ids' => 'array',
            'promo_snapshot' => 'array',
            'coin_refunded_at' => 'datetime',
            'paid_at' => 'datetime',
            'expires_at' => 'datetime',
            'last_synced_at' => 'datetime',
            'lyvaflow_processing_notified_at' => 'datetime',
            'lyvaflow_completed_notified_at' => 'datetime',
            'lyvaflow_failed_notified_at' => 'datetime',
            'admin_manual_order_notified_at' => 'datetime',
            'customer_completed_emailed_at' => 'datetime',
            'fulfilled_at' => 'datetime',
            'rated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function fulfilledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'fulfilled_by_user_id');
    }

    public function manualStockItem(): HasOne
    {
        return $this->hasOne(ManualStockItem::class, 'reserved_for_transaction_id');
    }
}
