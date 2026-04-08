<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Services\AffiliateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AffiliateController extends Controller
{
    public function apply(Request $request, AffiliateService $affiliate): RedirectResponse
    {
        $affiliate->apply($request->user());

        return to_route('profile.edit')->with('status', 'Pendaftaran affiliate berhasil dikirim. Status akun kamu sekarang menunggu persetujuan admin.');
    }

    public function withdraw(Request $request, AffiliateService $affiliate): RedirectResponse
    {
        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $affiliate->requestWithdrawal($request->user(), $validated['notes'] ?? null);

        return to_route('profile.edit')->with('status', 'Permintaan penarikan affiliate berhasil dikirim. Tim admin akan proses pencairannya.');
    }
}
