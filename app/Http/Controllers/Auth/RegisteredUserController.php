<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\CoinAdjustment;
use App\Models\User;
use App\Services\AffiliateService;
use App\Services\TransactionService;
use App\Services\WhatsappVerificationService;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Show the registration page.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('auth/Register', [
            'affiliateCode' => strtoupper(trim((string) $request->query('ref', ''))),
        ]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request, TransactionService $transactions, WhatsappVerificationService $verification, AffiliateService $affiliate): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:' . User::class,
            'whatsapp_number' => ['required', 'string', 'max:20', 'regex:/^[0-9+\-\s]{10,20}$/'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'affiliate_code' => ['nullable', 'string', 'max:32'],
        ], [
            'whatsapp_number.regex' => 'Nomor WhatsApp hanya boleh berisi angka, spasi, tanda plus, atau tanda hubung.',
        ]);

        $whatsappNumber = preg_replace('/[\s-]+/', '', trim((string) $request->input('whatsapp_number')));

        if (User::query()->where('whatsapp_number', $whatsappNumber)->exists()) {
            throw ValidationException::withMessages([
                'whatsapp_number' => 'Nomor WhatsApp ini sudah dipakai akun lain.',
            ]);
        }

        $referrer = $affiliate->resolveReferrerByCode((string) $request->input('affiliate_code', ''));

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'whatsapp_number' => $whatsappNumber,
            'password' => Hash::make($request->password),
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

        Auth::login($user);

        $transactions->syncGuestTransactionsToUser($user, $request);

        $redirect = (string) $request->input('redirect', '');

        if ($redirect !== '' && str_starts_with($redirect, '/')) {
            $request->session()->put('post_whatsapp_verification_redirect', $redirect);
        }

        try {
            $status = $verification->sendCode($user, force: true, purpose: WhatsappVerificationService::PURPOSE_REGISTER);
        } catch (\Throwable $exception) {
            report($exception);
            $status = 'Akun berhasil dibuat, tapi OTP WhatsApp belum bisa dikirim. Coba kirim ulang dari halaman verifikasi.';
        }

        return redirect()->route('verification.whatsapp.notice')->with('status', $status);
    }
}
