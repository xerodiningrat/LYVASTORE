<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SiteSettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BrandingSettingsController extends Controller
{
    public function index(SiteSettingService $settings): Response
    {
        return Inertia::render('admin/Branding', [
            'branding' => $settings->branding(),
        ]);
    }

    public function update(Request $request, SiteSettingService $settings): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:40'],
            'tagline' => ['nullable', 'string', 'max:80'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
            'remove_logo' => ['nullable', 'boolean'],
        ]);

        $settings->saveBranding(
            $validated['logo'] ?? null,
            $validated['title'] ?? null,
            $validated['tagline'] ?? null,
            (bool) ($validated['remove_logo'] ?? false),
        );

        return back()->with('status', 'Branding sidebar berhasil diperbarui.');
    }
}
