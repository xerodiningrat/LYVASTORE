<?php

namespace App\Http\Controllers;

use App\Models\CoinAdjustment;
use App\Models\User;
use App\Services\AffiliateService;
use App\Services\WhatsappVerificationService;
use App\Support\MobileUserAccess;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;

class MobileAuthController extends Controller
{
    /**
     * @return array<string, string>
     */
    private function corsHeaders(): array
    {
        return [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, POST, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Accept, Origin',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function respond(array $payload, int $status = 200): JsonResponse
    {
        return response()->json($payload, $status)->withHeaders($this->corsHeaders());
    }

    /**
     * @return array<string, mixed>
     */
    private function userPayload(User $user, WhatsappVerificationService $verification): array
    {
        return [
            'id' => $user->id,
            'name' => (string) $user->name,
            'email' => (string) $user->email,
            'whatsappNumber' => (string) $user->whatsapp_number,
            'maskedWhatsappNumber' => $verification->maskNumber($user->whatsapp_number),
            'whatsappVerified' => $verification->isVerified($user),
            'emailVerified' => $user->email_verified_at !== null,
        ];
    }

    private function normalizeWhatsapp(?string $value): string
    {
        return preg_replace('/[\s-]+/', '', trim((string) $value)) ?: '';
    }

    /**
     * @return array{id: string, email: string, name: string, picture: ?string}
     */
    private function resolveGoogleProfile(string $accessToken): array
    {
        try {
            $response = Http::acceptJson()
                ->timeout(15)
                ->withToken($accessToken)
                ->get('https://www.googleapis.com/oauth2/v2/userinfo');
        } catch (\Throwable $exception) {
            report($exception);

            throw ValidationException::withMessages([
                'google' => 'Google Sign-In belum bisa diverifikasi sekarang. Coba lagi sebentar ya.',
            ]);
        }

        if (! $response->ok()) {
            throw ValidationException::withMessages([
                'google' => 'Google Sign-In belum bisa diverifikasi sekarang. Coba lagi sebentar ya.',
            ]);
        }

        $payload = $response->json();
        $email = Str::lower(trim((string) ($payload['email'] ?? '')));
        $name = trim((string) ($payload['name'] ?? ''));
        $id = trim((string) ($payload['id'] ?? ''));
        $picture = trim((string) ($payload['picture'] ?? ''));
        $verifiedEmail = (bool) ($payload['verified_email'] ?? true);

        if ($email === '' || $name === '' || $id === '' || ! $verifiedEmail) {
            throw ValidationException::withMessages([
                'google' => 'Data akun Google belum lengkap atau email belum terverifikasi.',
            ]);
        }

        return [
            'id' => $id,
            'email' => $email,
            'name' => $name,
            'picture' => $picture !== '' ? $picture : null,
        ];
    }

    private function ensureGoogleUser(array $profile): User
    {
        $user = User::query()->where('email', $profile['email'])->first();

        if (! $user) {
            return User::query()->create([
                'name' => $profile['name'],
                'email' => $profile['email'],
                'email_verified_at' => now(),
                'password' => Hash::make(Str::random(40)),
            ]);
        }

        $updates = [];

        if (! $user->email_verified_at) {
            $updates['email_verified_at'] = now();
        }

        if (blank($user->name) && filled($profile['name'])) {
            $updates['name'] = $profile['name'];
        }

        if ($updates !== []) {
            $user->forceFill($updates)->save();
        }

        return $user->fresh();
    }

    public function google(Request $request, WhatsappVerificationService $verification, MobileUserAccess $mobileUserAccess): JsonResponse
    {
        $validated = $request->validate([
            'access_token' => ['required', 'string'],
        ]);

        $googleProfile = $this->resolveGoogleProfile((string) $validated['access_token']);
        $existingUser = User::query()->where('email', $googleProfile['email'])->first();
        $user = $this->ensureGoogleUser($googleProfile);
        $isNewUser = ! $existingUser;
        $requiresWhatsappCollection = $this->normalizeWhatsapp($user->whatsapp_number) === '';
        $requiresWhatsappVerification = ! $requiresWhatsappCollection && ! $verification->isVerified($user);
        $otpSent = ! $requiresWhatsappVerification;
        $message = $isNewUser
            ? 'Akun Google berhasil terhubung. Lengkapi nomor WhatsApp dulu ya.'
            : 'Login Google berhasil.';

        if ($requiresWhatsappVerification) {
            try {
                $message = $verification->sendCode($user, purpose: WhatsappVerificationService::PURPOSE_LOGIN);
                $otpSent = true;
            } catch (\Throwable $exception) {
                report($exception);
                $otpSent = false;
                $message = 'Login Google berhasil, tapi OTP WhatsApp belum bisa dikirim sekarang. '.($exception->getMessage() ?: 'Coba kirim ulang kode.');
            }
        }

        if (! $requiresWhatsappCollection && ! $requiresWhatsappVerification) {
            $message = 'Login Google berhasil. Akun kamu siap dipakai.';
        }

        return $this->respond([
            'message' => $message,
            'data' => [
                'accessToken' => $mobileUserAccess->makeAccessToken($user->fresh()),
                'user' => $this->userPayload($user->fresh(), $verification),
                'isNewUser' => $isNewUser,
                'requiresWhatsappCollection' => $requiresWhatsappCollection,
                'requiresWhatsappVerification' => $requiresWhatsappVerification,
                'nextStep' => $requiresWhatsappCollection ? 'collect_whatsapp' : ($requiresWhatsappVerification ? 'verify_whatsapp' : 'done'),
                'otpSent' => $otpSent,
            ],
        ]);
    }

    public function googleWhatsapp(Request $request, WhatsappVerificationService $verification, MobileUserAccess $mobileUserAccess): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
            'whatsapp_number' => ['required', 'string', 'max:20', 'regex:/^[0-9+\-\s]{10,20}$/'],
        ], [
            'whatsapp_number.regex' => 'Nomor WhatsApp hanya boleh berisi angka, spasi, tanda plus, atau tanda hubung.',
        ]);

        $user = User::query()->where('email', Str::lower(trim((string) $validated['email'])))->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'email' => 'Akun Google belum ditemukan. Coba login ulang dulu.',
            ]);
        }

        $whatsappNumber = $this->normalizeWhatsapp((string) $validated['whatsapp_number']);

        if (User::query()->where('whatsapp_number', $whatsappNumber)->whereKeyNot($user->id)->exists()) {
            throw ValidationException::withMessages([
                'whatsapp_number' => 'Nomor WhatsApp ini sudah dipakai akun lain.',
            ]);
        }

        $user->forceFill([
            'whatsapp_number' => $whatsappNumber,
            'whatsapp_verified_at' => null,
            'whatsapp_verification_code' => null,
            'whatsapp_verification_expires_at' => null,
            'whatsapp_verification_sent_at' => null,
        ])->save();

        $otpSent = true;

        try {
            $status = $verification->sendCode($user->fresh(), force: true, purpose: WhatsappVerificationService::PURPOSE_REGISTER);
        } catch (\Throwable $exception) {
            report($exception);
            $otpSent = false;
            $status = 'Nomor WhatsApp berhasil disimpan, tapi OTP belum bisa dikirim. '.($exception->getMessage() ?: 'Coba kirim ulang dari aplikasi.');
        }

        return $this->respond([
            'message' => $status,
            'data' => [
                'accessToken' => $mobileUserAccess->makeAccessToken($user->fresh()),
                'user' => $this->userPayload($user->fresh(), $verification),
                'requiresWhatsappCollection' => false,
                'requiresWhatsappVerification' => ! $verification->isVerified($user->fresh()),
                'nextStep' => 'verify_whatsapp',
                'otpSent' => $otpSent,
            ],
        ]);
    }

    public function register(Request $request, WhatsappVerificationService $verification, AffiliateService $affiliate, MobileUserAccess $mobileUserAccess): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'whatsapp_number' => ['required', 'string', 'max:20', 'regex:/^[0-9+\-\s]{10,20}$/'],
            'password' => ['required', Rules\Password::defaults()],
            'password_confirmation' => ['required', 'same:password'],
            'affiliate_code' => ['nullable', 'string', 'max:32'],
        ], [
            'whatsapp_number.regex' => 'Nomor WhatsApp hanya boleh berisi angka, spasi, tanda plus, atau tanda hubung.',
            'password_confirmation.same' => 'Konfirmasi password belum sama.',
        ]);

        $whatsappNumber = preg_replace('/[\s-]+/', '', trim((string) $validated['whatsapp_number']));

        if (User::query()->where('whatsapp_number', $whatsappNumber)->exists()) {
            throw ValidationException::withMessages([
                'whatsapp_number' => 'Nomor WhatsApp ini sudah dipakai akun lain.',
            ]);
        }

        $referrer = $affiliate->resolveReferrerByCode((string) ($validated['affiliate_code'] ?? ''));

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'whatsapp_number' => $whatsappNumber,
            'password' => Hash::make((string) $validated['password']),
            'referred_by_user_id' => $referrer?->id,
            'referred_at' => $referrer ? now() : null,
        ]);

        if ($referrer) {
            $hasPreviousSignupReward = CoinAdjustment::query()
                ->where('user_id', $referrer->id)
                ->where('reference', 'like', 'affiliate-signup-%')
                ->exists();

            $rewardCoins = $hasPreviousSignupReward
                ? max(0, (int) config('affiliate.signup_reward_coins', 50))
                : max(0, (int) config('affiliate.first_signup_reward_coins', 1000));

            if ($rewardCoins > 0) {
                CoinAdjustment::query()->create([
                    'user_id' => $referrer->id,
                    'amount' => $rewardCoins,
                    'direction' => 'credit',
                    'reason' => 'Bonus referral pendaftaran user baru',
                    'reference' => 'affiliate-signup-'.$user->id,
                    'meta' => [
                        'referred_user_id' => $user->id,
                        'referred_user_email' => $user->email,
                    ],
                ]);
            }
        }

        event(new Registered($user));

        if ($user instanceof MustVerifyEmail) {
            $user->sendEmailVerificationNotification();
        }

        $otpSent = true;

        try {
            $status = $verification->sendCode($user, force: true, purpose: WhatsappVerificationService::PURPOSE_REGISTER);
        } catch (\Throwable $exception) {
            report($exception);
            $otpSent = false;
            $status = 'Akun berhasil dibuat, tapi OTP WhatsApp belum bisa dikirim. '.($exception->getMessage() ?: 'Coba kirim ulang dari aplikasi.');
        }

        return $this->respond([
            'message' => $status,
            'data' => [
                'accessToken' => $mobileUserAccess->makeAccessToken($user->fresh()),
                'user' => $this->userPayload($user->fresh(), $verification),
                'requiresWhatsappVerification' => ! $verification->isVerified($user),
                'nextStep' => 'verify_whatsapp',
                'otpSent' => $otpSent,
            ],
        ], 201);
    }

    public function login(Request $request, WhatsappVerificationService $verification, MobileUserAccess $mobileUserAccess): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $email = strtolower(trim((string) $validated['email']));
        $user = User::query()->where('email', $email)->first();

        if (! $user || ! Hash::check((string) $validated['password'], (string) $user->password)) {
            throw ValidationException::withMessages([
                'email' => 'Email atau password salah.',
            ]);
        }

        $requiresWhatsappVerification = ! $verification->isVerified($user);
        $message = 'Login berhasil. Akun kamu siap dipakai.';

        $otpSent = ! $requiresWhatsappVerification;

        if ($requiresWhatsappVerification) {
            try {
                $message = $verification->sendCode($user, purpose: WhatsappVerificationService::PURPOSE_LOGIN);
                $otpSent = true;
            } catch (\Throwable $exception) {
                report($exception);
                $otpSent = false;
                $message = 'Login berhasil, tapi OTP WhatsApp belum bisa dikirim sekarang. '.($exception->getMessage() ?: 'Coba kirim ulang kode.');
            }
        }

        return $this->respond([
            'message' => $message,
            'data' => [
                'accessToken' => $mobileUserAccess->makeAccessToken($user->fresh()),
                'user' => $this->userPayload($user->fresh(), $verification),
                'requiresWhatsappVerification' => $requiresWhatsappVerification,
                'nextStep' => $requiresWhatsappVerification ? 'verify_whatsapp' : 'done',
                'otpSent' => $otpSent,
            ],
        ]);
    }

    public function verifyWhatsapp(Request $request, WhatsappVerificationService $verification, MobileUserAccess $mobileUserAccess): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
            'code' => ['required', 'digits:6'],
        ]);

        $user = User::query()->where('email', strtolower(trim((string) $validated['email'])))->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'email' => 'Akun tidak ditemukan.',
            ]);
        }

        if (! $verification->verify($user, (string) $validated['code'])) {
            throw ValidationException::withMessages([
                'code' => 'Kode verifikasi WhatsApp tidak valid atau sudah kedaluwarsa.',
            ]);
        }

        return $this->respond([
            'message' => 'Nomor WhatsApp berhasil diverifikasi.',
            'data' => [
                'accessToken' => $mobileUserAccess->makeAccessToken($user->fresh()),
                'user' => $this->userPayload($user->fresh(), $verification),
                'requiresWhatsappVerification' => false,
                'nextStep' => 'done',
            ],
        ]);
    }

    public function resendWhatsapp(Request $request, WhatsappVerificationService $verification, MobileUserAccess $mobileUserAccess): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
            'purpose' => ['nullable', 'string', 'in:register,login,resend'],
        ]);

        $user = User::query()->where('email', strtolower(trim((string) $validated['email'])))->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'email' => 'Akun tidak ditemukan.',
            ]);
        }

        $otpSent = true;

        try {
            $status = $verification->sendCode(
                $user,
                purpose: (string) ($validated['purpose'] ?? WhatsappVerificationService::PURPOSE_RESEND),
            );
        } catch (\Throwable $exception) {
            report($exception);
            $otpSent = false;
            $status = 'Kode OTP belum bisa dikirim sekarang. '.($exception->getMessage() ?: 'Coba lagi beberapa saat lagi.');
        }

        return $this->respond([
            'message' => $status,
            'data' => [
                'accessToken' => $mobileUserAccess->makeAccessToken($user->fresh()),
                'user' => $this->userPayload($user->fresh(), $verification),
                'requiresWhatsappVerification' => ! $verification->isVerified($user),
                'nextStep' => 'verify_whatsapp',
                'otpSent' => $otpSent,
            ],
        ]);
    }
}
