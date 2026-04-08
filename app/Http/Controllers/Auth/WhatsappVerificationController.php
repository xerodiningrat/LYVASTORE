<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\WhatsappVerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class WhatsappVerificationController extends Controller
{
    public function show(Request $request, WhatsappVerificationService $verification): Response|RedirectResponse
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if ($verification->isVerified($user)) {
            return redirect()->intended(route('home', absolute: false));
        }

        if (! $verification->hasDeliverableWhatsappNumber($user)) {
            return redirect()
                ->route('profile.edit')
                ->with('status', 'Nomor WhatsApp akunmu masih kosong atau tidak valid. Isi nomor WhatsApp dulu agar OTP bisa dikirim.');
        }

        return Inertia::render('auth/VerifyWhatsapp', [
            'status' => $request->session()->get('status'),
            'maskedWhatsappNumber' => $verification->maskNumber($user->whatsapp_number),
            'cooldownSeconds' => $verification->cooldownSeconds($user),
            'expiresAt' => $user->whatsapp_verification_expires_at?->toIso8601String(),
        ]);
    }

    public function store(Request $request, WhatsappVerificationService $verification): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if (! $verification->verify($user, (string) $request->input('code'))) {
            throw ValidationException::withMessages([
                'code' => 'Kode verifikasi WhatsApp tidak valid atau sudah kedaluwarsa.',
            ]);
        }

        $redirect = (string) $request->session()->pull('post_whatsapp_verification_redirect', '');

        if ($redirect !== '' && str_starts_with($redirect, '/')) {
            return redirect()->to($redirect);
        }

        return redirect()->intended(route('home', absolute: false));
    }

    public function resend(Request $request, WhatsappVerificationService $verification): RedirectResponse
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if (! $verification->hasDeliverableWhatsappNumber($user)) {
            return redirect()
                ->route('profile.edit')
                ->with('status', 'Nomor WhatsApp akunmu masih kosong atau tidak valid. Isi nomor WhatsApp dulu agar OTP bisa dikirim.');
        }

        try {
            $status = $verification->sendCode($user, purpose: WhatsappVerificationService::PURPOSE_RESEND);
        } catch (\Throwable $exception) {
            report($exception);
            $status = 'Kode OTP belum bisa dikirim sekarang. Coba lagi beberapa saat lagi.';
        }

        return back()->with('status', $status);
    }
}
