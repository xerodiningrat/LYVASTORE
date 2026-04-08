<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SiteSettingService;
use App\Services\VipaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ProductSettingsController extends Controller
{
    public function index(SiteSettingService $settings, VipaymentService $vipayment): Response
    {
        $vipCatalogProducts = [];

        if ($vipayment->configured()) {
            try {
                $vipCatalogProducts = $vipayment->getCatalogProducts();
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        return Inertia::render('admin/Products', [
            'status' => session('status'),
            'vipCatalogProducts' => $vipCatalogProducts,
            'overrides' => collect($settings->productArtworkOverrides())
                ->map(fn (array $override) => [
                    'coverImage' => $override['coverImage'],
                    'iconImage' => $override['iconImage'],
                ])
                ->all(),
            'displayOverrides' => $settings->productDisplayOverrides(),
            'hiddenProductIds' => $settings->hiddenProductIds(),
            'orderingOverrides' => $settings->productOrderingOverrides(),
        ]);
    }

    public function update(Request $request, string $productId, SiteSettingService $settings): RedirectResponse
    {
        $validated = $request->validate([
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
            'name' => ['nullable', 'string', 'max:120'],
            'category_title' => ['nullable', 'string', 'max:80'],
            'badge' => ['nullable', 'string', 'max:40'],
            'pinned' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:-9999', 'max:9999'],
        ]);

        if ($request->hasFile('image') && $validated['image']) {
            $settings->saveProductArtwork($productId, $validated['image']);
        }

        $name = Str::of((string) ($validated['name'] ?? ''))->trim()->value();
        $categoryTitle = Str::of((string) ($validated['category_title'] ?? ''))->trim()->value();
        $badge = $request->exists('badge')
            ? Str::of((string) ($validated['badge'] ?? ''))->trim()->value()
            : null;

        $settings->saveProductDisplayOverride(
            $productId,
            $name !== '' ? $name : null,
            $categoryTitle !== '' ? $categoryTitle : null,
            $badge,
        );
        $settings->saveProductOrderingOverride(
            $productId,
            $request->exists('pinned') ? (bool) ($validated['pinned'] ?? false) : null,
            $request->exists('sort_order') && filled($validated['sort_order'] ?? null) ? (int) $validated['sort_order'] : null,
        );

        return back()->with('status', 'Override produk berhasil diperbarui.');
    }

    public function destroy(string $productId, SiteSettingService $settings): RedirectResponse
    {
        $settings->removeProductArtwork($productId);
        $settings->removeProductDisplayOverride($productId);
        $settings->setProductVisibility($productId, false);
        $settings->removeProductOrderingOverride($productId);

        return back()->with('status', 'Override produk berhasil dihapus.');
    }

    public function updateVisibility(Request $request, string $productId, SiteSettingService $settings): RedirectResponse
    {
        $validated = $request->validate([
            'hidden' => ['required', 'boolean'],
        ]);

        $settings->setProductVisibility($productId, (bool) $validated['hidden']);

        return back()->with('status', (bool) $validated['hidden'] ? 'Produk berhasil disembunyikan dari katalog publik.' : 'Produk kembali ditampilkan di katalog publik.');
    }
}
