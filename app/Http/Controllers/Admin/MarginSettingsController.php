<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SiteSettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MarginSettingsController extends Controller
{
    public function index(SiteSettingService $settings): Response
    {
        return Inertia::render('admin/Margins', [
            'tiers' => $settings->marginTiers(),
        ]);
    }

    public function update(Request $request, SiteSettingService $settings): RedirectResponse
    {
        $validated = $request->validate([
            'tiers' => ['required', 'array', 'min:1'],
            'tiers.*.max' => ['nullable', 'integer', 'min:1'],
            'tiers.*.percent' => ['required', 'numeric', 'min:0'],
            'tiers.*.fixed' => ['required', 'integer', 'min:0'],
            'tiers.*.round_to' => ['required', 'integer', 'min:1'],
        ]);

        $settings->saveMarginTiers($validated['tiers']);

        return back()->with('status', 'Setting margin berhasil disimpan.');
    }
}
