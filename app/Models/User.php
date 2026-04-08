<?php

namespace App\Models;

use App\Notifications\Auth\CustomVerifyEmailNotification;
use Illuminate\Auth\MustVerifyEmail as MustVerifyEmailTrait;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class User extends Authenticatable implements MustVerifyEmailContract
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, MustVerifyEmailTrait, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'whatsapp_number',
        'avatar_path',
        'affiliate_status',
        'affiliate_code',
        'referred_by_user_id',
        'affiliate_applied_at',
        'affiliate_approved_at',
        'referred_at',
        'whatsapp_verified_at',
        'whatsapp_verification_code',
        'whatsapp_verification_expires_at',
        'whatsapp_verification_sent_at',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'avatar_path',
        'whatsapp_verification_code',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var list<string>
     */
    protected $appends = [
        'avatar',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'whatsapp_verified_at' => 'datetime',
            'affiliate_applied_at' => 'datetime',
            'affiliate_approved_at' => 'datetime',
            'referred_at' => 'datetime',
            'whatsapp_verification_expires_at' => 'datetime',
            'whatsapp_verification_sent_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function affiliateWithdrawals(): HasMany
    {
        return $this->hasMany(AffiliateWithdrawal::class);
    }

    public function referredBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'referred_by_user_id');
    }

    public function referredUsers(): HasMany
    {
        return $this->hasMany(self::class, 'referred_by_user_id');
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new CustomVerifyEmailNotification());
    }

    public function isOwner(): bool
    {
        $email = Str::lower(trim((string) $this->email));

        return $email !== '' && in_array($email, config('admin.owner_emails', []), true);
    }

    public function canAccessAdminPanel(): bool
    {
        if ($this->isOwner()) {
            return true;
        }

        $email = Str::lower(trim((string) $this->email));

        return $email !== '' && in_array($email, config('admin.admin_emails', []), true);
    }

    protected function avatar(): Attribute
    {
        return Attribute::get(function (): ?string {
            if (! filled($this->avatar_path)) {
                return null;
            }

            return Storage::disk('public')->url((string) $this->avatar_path);
        });
    }
}
