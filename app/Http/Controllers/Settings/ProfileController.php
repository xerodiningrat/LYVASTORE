<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use App\Services\AffiliateService;
use App\Services\WhatsappVerificationService;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\User;

class ProfileController extends Controller
{
    /**
     * Show the user's profile settings page.
     */
    public function edit(Request $request, AffiliateService $affiliate): Response
    {
        return Inertia::render('account/Profile', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => $request->session()->get('status'),
            'emailVerifiedFlash' => $request->query('verified') === 'email',
            'affiliate' => $affiliate->profileSummary($request->user()),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request, WhatsappVerificationService $verification): RedirectResponse
    {
        $validated = $request->validated();
        $validated['whatsapp_number'] = preg_replace('/[\s-]+/', '', trim((string) ($validated['whatsapp_number'] ?? '')));
        $removeAvatar = $request->boolean('remove_avatar') && ! $request->hasFile('avatar');

        $user = $request->user();
        $incomingWhatsapp = (string) ($validated['whatsapp_number'] ?? '');

        if (
            $incomingWhatsapp !== ''
            && $incomingWhatsapp !== (string) $user->whatsapp_number
            && User::query()
                ->where('whatsapp_number', $incomingWhatsapp)
                ->whereKeyNot($user->id)
                ->exists()
        ) {
            throw ValidationException::withMessages([
                'whatsapp_number' => 'Nomor WhatsApp ini sudah dipakai akun lain.',
            ]);
        }

        $oldAvatarPath = $user->avatar_path;

        $user->fill(Arr::except($validated, ['avatar', 'remove_avatar']));

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $whatsappChanged = $user->isDirty('whatsapp_number');

        if ($whatsappChanged) {
            $user->forceFill([
                'whatsapp_verified_at' => null,
                'whatsapp_verification_code' => null,
                'whatsapp_verification_expires_at' => null,
                'whatsapp_verification_sent_at' => null,
            ]);
        }

        if ($removeAvatar) {
            $user->avatar_path = null;
        }

        if ($request->hasFile('avatar')) {
            $user->avatar_path = $request->file('avatar')->store('avatars', 'public');
        }

        $user->save();

        if ($user->wasChanged('email') && $user instanceof MustVerifyEmail) {
            $user->sendEmailVerificationNotification();
        }

        if (
            filled($oldAvatarPath)
            && $oldAvatarPath !== $user->avatar_path
        ) {
            Storage::disk('public')->delete((string) $oldAvatarPath);
        }

        if ($whatsappChanged) {
            $request->session()->put('post_whatsapp_verification_redirect', route('profile.edit', absolute: false));

            try {
                $status = $verification->sendCode($user->fresh(), force: true, purpose: WhatsappVerificationService::PURPOSE_PROFILE_UPDATE);
            } catch (\Throwable $exception) {
                report($exception);
                $status = 'Nomor WhatsApp berhasil diperbarui, tapi OTP nomor baru belum bisa dikirim. Coba kirim ulang dari halaman verifikasi.';
            }

            return redirect()->route('verification.whatsapp.notice')->with('status', $status);
        }

        return to_route('profile.edit')->with('status', 'Profil berhasil diperbarui.');
    }

    /**
     * Delete the user's profile.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        if (filled($user->avatar_path)) {
            Storage::disk('public')->delete((string) $user->avatar_path);
        }

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
