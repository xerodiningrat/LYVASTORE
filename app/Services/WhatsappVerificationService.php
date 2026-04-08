<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

class WhatsappVerificationService
{
    public const PURPOSE_LOGIN = 'login';

    public const PURPOSE_PROFILE_UPDATE = 'profile_update';

    public const PURPOSE_REGISTER = 'register';

    public const PURPOSE_RESEND = 'resend';

    public function __construct(
        private readonly LyvaflowService $lyvaflow,
    ) {}

    public function isVerified(?User $user): bool
    {
        return $user?->whatsapp_verified_at !== null;
    }

    public function hasDeliverableWhatsappNumber(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $this->lyvaflow->normalizeWhatsappNumber($user->whatsapp_number) !== '';
    }

    public function shouldThrottleResend(User $user, int $seconds = 60): bool
    {
        return $user->whatsapp_verification_sent_at !== null
            && $user->whatsapp_verification_sent_at->diffInSeconds(now()) < $seconds;
    }

    public function cooldownSeconds(User $user, int $seconds = 60): int
    {
        if (! $this->shouldThrottleResend($user, $seconds)) {
            return 0;
        }

        return max(0, $seconds - $user->whatsapp_verification_sent_at->diffInSeconds(now()));
    }

    public function sendCode(User $user, bool $force = false, string $purpose = self::PURPOSE_RESEND): string
    {
        if ($this->isVerified($user)) {
            return 'Nomor WhatsApp kamu sudah terverifikasi.';
        }

        if (! $this->hasDeliverableWhatsappNumber($user)) {
            throw new RuntimeException('Nomor WhatsApp akun masih kosong atau tidak valid.');
        }

        if (! $force && $this->shouldThrottleResend($user)) {
            $seconds = $this->cooldownSeconds($user);

            return 'Tunggu '.$seconds.' detik sebelum minta kode baru.';
        }

        $code = (string) random_int(100000, 999999);

        $user->forceFill([
            'whatsapp_verification_code' => Hash::make($code),
            'whatsapp_verification_expires_at' => now()->addMinutes(10),
            'whatsapp_verification_sent_at' => now(),
        ])->save();

        if (! $this->lyvaflow->configured()) {
            throw new RuntimeException('LYVAFLOW belum dikonfigurasi untuk kirim kode verifikasi WhatsApp.');
        }

        $this->lyvaflow->sendWhatsappMessage(
            (string) $user->whatsapp_number,
            $this->buildVerificationMessage($user, $code, $purpose),
        );

        return $this->verificationStatusMessage($purpose);
    }

    public function verify(User $user, string $code): bool
    {
        $normalizedCode = trim($code);

        if ($normalizedCode === '') {
            return false;
        }

        if ($this->isVerified($user)) {
            return true;
        }

        if (
            ! filled($user->whatsapp_verification_code)
            || ! $user->whatsapp_verification_expires_at
            || now()->greaterThan($user->whatsapp_verification_expires_at)
        ) {
            return false;
        }

        if (! Hash::check($normalizedCode, (string) $user->whatsapp_verification_code)) {
            return false;
        }

        $user->forceFill([
            'whatsapp_verified_at' => now(),
            'whatsapp_verification_code' => null,
            'whatsapp_verification_expires_at' => null,
        ])->save();

        if ($this->lyvaflow->configured()) {
            try {
                $this->lyvaflow->sendWhatsappMessage(
                    (string) $user->whatsapp_number,
                    $this->buildVerificationSuccessMessage($user),
                );
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        return true;
    }

    public function maskNumber(?string $number): string
    {
        $normalized = $this->lyvaflow->normalizeWhatsappNumber($number);

        if ($normalized === '') {
            return '-';
        }

        if (strlen($normalized) <= 6) {
            return $normalized;
        }

        return substr($normalized, 0, 4).str_repeat('*', max(0, strlen($normalized) - 8)).substr($normalized, -4);
    }

    private function buildVerificationMessage(User $user, string $code, string $purpose): string
    {
        $maskedNumber = $this->maskNumber($user->whatsapp_number);
        $verificationUrl = route('verification.whatsapp.notice');

        [$title, $contextLines, $closingLines] = match ($purpose) {
            self::PURPOSE_REGISTER => [
                'Aktivasi Akun',
                [
                    'Halo '.$this->resolveRecipient($user).',',
                    'Akun Lyva Indonesia kamu sudah dibuat dan hampir siap dipakai.',
                    'Masukkan OTP berikut untuk mengaktifkan nomor WhatsApp utama kamu.',
                ],
                [
                    'Buka verifikasi: '.$verificationUrl,
                    'Setelah verifikasi selesai, kamu bisa checkout lebih cepat dan menerima update transaksi otomatis.',
                ],
            ],
            self::PURPOSE_LOGIN => [
                'Verifikasi Login',
                [
                    'Halo '.$this->resolveRecipient($user).',',
                    'Kami mendeteksi login ke akun Lyva Indonesia kamu.',
                    'Sebelum lanjut, verifikasi nomor WhatsApp kamu dengan OTP berikut.',
                ],
                [
                    'Buka verifikasi: '.$verificationUrl,
                    'Kalau ini bukan kamu, segera ganti password akun setelah berhasil masuk.',
                ],
            ],
            self::PURPOSE_PROFILE_UPDATE => [
                'Verifikasi Nomor Baru',
                [
                    'Halo '.$this->resolveRecipient($user).',',
                    'Nomor WhatsApp di akun Lyva Indonesia kamu baru saja diperbarui.',
                    'Masukkan OTP berikut untuk mengaktifkan nomor baru dan tetap menerima notifikasi transaksi.',
                ],
                [
                    'Buka verifikasi: '.$verificationUrl,
                    'Pastikan nomor ini aktif agar info pesanan dan kode produk tidak terlewat.',
                ],
            ],
            default => [
                'Kode OTP Baru',
                [
                    'Halo '.$this->resolveRecipient($user).',',
                    'Berikut OTP terbaru untuk melanjutkan verifikasi akun Lyva Indonesia kamu.',
                ],
                [
                    'Buka verifikasi: '.$verificationUrl,
                    'Kalau kamu tidak meminta kode ini, abaikan pesan ini.',
                ],
            ],
        };

        return $this->lyvaflow->composeStructuredMessage(
            $title,
            $contextLines,
            [
                [
                    'title' => 'Kode OTP',
                    'style' => 'plain',
                    'lines' => [
                        '*'.$code.'*',
                        'Berlaku 10 menit untuk nomor '.$maskedNumber.'.',
                    ],
                ],
                [
                    'title' => 'Langkah Cepat',
                    'style' => 'numbered',
                    'lines' => [
                        'Buka halaman verifikasi akun Lyva Indonesia.',
                        'Masukkan 6 digit kode di atas.',
                        'Jangan bagikan kode ini ke siapa pun.',
                    ],
                ],
            ],
            $closingLines,
        );
    }

    private function buildVerificationSuccessMessage(User $user): string
    {
        return $this->lyvaflow->composeStructuredMessage(
            'WhatsApp Berhasil Diverifikasi',
            [
                'Halo '.$this->resolveRecipient($user).',',
                'Nomor WhatsApp kamu sekarang sudah aktif di akun Lyva Indonesia.',
            ],
            [
                [
                    'title' => 'Yang Sudah Siap',
                    'lines' => [
                        'Login dan checkout lebih cepat.',
                        'Update status transaksi otomatis via WhatsApp.',
                        'Pengiriman kode atau voucher ke nomor ini jika produk mendukung.',
                    ],
                ],
                [
                    'title' => 'Akses Cepat',
                    'style' => 'plain',
                    'lines' => [
                        'Home: '.route('home'),
                        'Profil: '.route('profile.edit'),
                    ],
                ],
            ],
            [
                'Simpan nomor ini tetap aktif agar info transaksi tidak terlewat.',
            ],
        );
    }

    private function verificationStatusMessage(string $purpose): string
    {
        return match ($purpose) {
            self::PURPOSE_REGISTER => 'Akun berhasil dibuat. OTP verifikasi sudah kami kirim ke WhatsApp kamu.',
            self::PURPOSE_LOGIN => 'OTP login sudah kami kirim ke WhatsApp kamu. Masukkan 6 digit kode untuk lanjut.',
            self::PURPOSE_PROFILE_UPDATE => 'Nomor WhatsApp berhasil diperbarui. OTP nomor baru sudah kami kirim.',
            default => 'Kode verifikasi baru sudah kami kirim ke WhatsApp kamu.',
        };
    }

    private function resolveRecipient(User $user): string
    {
        $name = trim((string) $user->name);

        if ($name === '') {
            return 'Kak';
        }

        return Str::headline(Str::before($name, ' '));
    }
}
