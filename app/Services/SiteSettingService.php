<?php

namespace App\Services;

use App\Models\SiteSetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SiteSettingService
{
    private const BRANDING_KEY = 'branding.admin_panel';
    private const PRODUCT_ARTWORK_KEY = 'catalog.product_artwork_overrides';
    private const PRODUCT_DISPLAY_KEY = 'catalog.product_display_overrides';
    private const PRODUCT_VISIBILITY_KEY = 'catalog.hidden_product_ids';
    private const PRODUCT_ORDERING_KEY = 'catalog.product_ordering_overrides';
    private const VIP_MARGIN_KEY = 'pricing.vipayment_margin_tiers';

    /**
     * @param  mixed  $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $setting = SiteSetting::query()->where('key', $key)->first();

        return $setting?->value ?? $default;
    }

    public function put(string $key, mixed $value): void
    {
        SiteSetting::query()->updateOrCreate(
            ['key' => $key],
            ['value' => is_array($value) ? $value : ['value' => $value]],
        );
    }

    /**
     * @return array<int, array{max: int|null, percent: float, fixed: int, round_to: int}>
     */
    public function marginTiers(): array
    {
        $tiers = $this->get(self::VIP_MARGIN_KEY);

        if (! is_array($tiers) || $tiers === []) {
            $tiers = config('vipayment.selling_price.tiers', []);
        }

        return collect($tiers)
            ->map(fn ($tier) => $this->normalizeMarginTier(is_array($tier) ? $tier : []))
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $tiers
     */
    public function saveMarginTiers(array $tiers): void
    {
        $normalized = collect($tiers)
            ->map(fn (array $tier) => $this->normalizeMarginTier($tier))
            ->values()
            ->all();

        $this->put(self::VIP_MARGIN_KEY, $normalized);
    }

    /**
     * @return array<string, array{coverImage: string, iconImage: string, coverPath?: string, iconPath?: string}>
     */
    public function productArtworkOverrides(): array
    {
        $overrides = $this->get(self::PRODUCT_ARTWORK_KEY, []);

        if (! is_array($overrides)) {
            return [];
        }

        return collect($overrides)
            ->mapWithKeys(function ($override, string $productId) {
                if (! is_array($override)) {
                    return [];
                }

                $coverPath = filled($override['cover_path'] ?? null) ? (string) $override['cover_path'] : null;
                $iconPath = filled($override['icon_path'] ?? null) ? (string) $override['icon_path'] : $coverPath;

                if (! $coverPath && ! $iconPath) {
                    return [];
                }

                return [
                    $productId => [
                        'coverImage' => $coverPath ? Storage::disk('public')->url($coverPath) : ($iconPath ? Storage::disk('public')->url($iconPath) : ''),
                        'iconImage' => $iconPath ? Storage::disk('public')->url($iconPath) : ($coverPath ? Storage::disk('public')->url($coverPath) : ''),
                        'coverPath' => $coverPath,
                        'iconPath' => $iconPath,
                    ],
                ];
            })
            ->all();
    }

    public function saveProductArtwork(string $productId, UploadedFile $file): void
    {
        $productId = Str::lower(trim($productId));
        $path = $file->store("admin/products/{$productId}", 'public');
        $overrides = $this->get(self::PRODUCT_ARTWORK_KEY, []);
        $current = is_array($overrides[$productId] ?? null) ? $overrides[$productId] : [];

        $this->deleteStoredPath($current['cover_path'] ?? null);
        $this->deleteStoredPath($current['icon_path'] ?? null);

        $overrides[$productId] = [
            'cover_path' => $path,
            'icon_path' => $path,
        ];

        $this->put(self::PRODUCT_ARTWORK_KEY, $overrides);
    }

    /**
     * @return array<string, array{name?: string, categoryTitle?: string, badge?: string|null}>
     */
    public function productDisplayOverrides(): array
    {
        $overrides = $this->get(self::PRODUCT_DISPLAY_KEY, []);

        if (! is_array($overrides)) {
            return [];
        }

        return collect($overrides)
            ->mapWithKeys(function ($override, string $productId) {
                if (! is_array($override)) {
                    return [];
                }

                $name = filled($override['name'] ?? null) ? trim((string) $override['name']) : null;
                $categoryTitle = filled($override['category_title'] ?? null) ? trim((string) $override['category_title']) : null;
                $badge = array_key_exists('badge', $override)
                    ? (filled($override['badge']) ? trim((string) $override['badge']) : null)
                    : null;

                if (! $name && ! $categoryTitle && ! array_key_exists('badge', $override)) {
                    return [];
                }

                return [
                    $productId => array_filter([
                        'name' => $name,
                        'categoryTitle' => $categoryTitle,
                        'badge' => $badge,
                    ], static fn ($value, string $key) => $value !== null || $key === 'badge', ARRAY_FILTER_USE_BOTH),
                ];
            })
            ->all();
    }

    public function saveProductDisplayOverride(string $productId, ?string $name, ?string $categoryTitle, mixed $badge): void
    {
        $productId = Str::lower(trim($productId));
        $overrides = $this->get(self::PRODUCT_DISPLAY_KEY, []);

        if (! is_array($overrides)) {
            $overrides = [];
        }

        $normalizedName = filled($name) ? trim((string) $name) : null;
        $normalizedCategoryTitle = filled($categoryTitle) ? trim((string) $categoryTitle) : null;
        $badgeProvided = $badge !== null;
        $normalizedBadge = $badgeProvided && filled($badge) ? trim((string) $badge) : null;

        if (! $normalizedName && ! $normalizedCategoryTitle && ! $badgeProvided) {
            unset($overrides[$productId]);
            $this->put(self::PRODUCT_DISPLAY_KEY, $overrides);

            return;
        }

        $overrides[$productId] = array_filter([
            'name' => $normalizedName,
            'category_title' => $normalizedCategoryTitle,
            'badge' => $badgeProvided ? $normalizedBadge : null,
        ], static fn ($value, string $key) => $value !== null || $key === 'badge', ARRAY_FILTER_USE_BOTH);

        $this->put(self::PRODUCT_DISPLAY_KEY, $overrides);
    }

    public function removeProductArtwork(string $productId): void
    {
        $productId = Str::lower(trim($productId));
        $overrides = $this->get(self::PRODUCT_ARTWORK_KEY, []);
        $current = is_array($overrides[$productId] ?? null) ? $overrides[$productId] : null;

        if (! $current) {
            return;
        }

        $this->deleteStoredPath($current['cover_path'] ?? null);
        $this->deleteStoredPath($current['icon_path'] ?? null);
        unset($overrides[$productId]);

        $this->put(self::PRODUCT_ARTWORK_KEY, $overrides);
    }

    public function removeProductDisplayOverride(string $productId): void
    {
        $productId = Str::lower(trim($productId));
        $overrides = $this->get(self::PRODUCT_DISPLAY_KEY, []);

        if (! is_array($overrides) || ! array_key_exists($productId, $overrides)) {
            return;
        }

        unset($overrides[$productId]);
        $this->put(self::PRODUCT_DISPLAY_KEY, $overrides);
    }

    /**
     * @return array<int, string>
     */
    public function hiddenProductIds(): array
    {
        $hidden = $this->get(self::PRODUCT_VISIBILITY_KEY, []);

        if (! is_array($hidden)) {
            return [];
        }

        return collect($hidden)
            ->map(fn ($productId) => Str::lower(trim((string) $productId)))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function isProductHidden(string $productId): bool
    {
        $normalizedProductId = Str::lower(trim($productId));

        return in_array($normalizedProductId, $this->hiddenProductIds(), true);
    }

    public function setProductVisibility(string $productId, bool $hidden): void
    {
        $normalizedProductId = Str::lower(trim($productId));
        $productIds = collect($this->hiddenProductIds());

        if ($hidden) {
            $productIds->push($normalizedProductId);
        } else {
            $productIds = $productIds->reject(fn (string $currentId) => $currentId === $normalizedProductId);
        }

        $this->put(
            self::PRODUCT_VISIBILITY_KEY,
            $productIds
                ->filter()
                ->unique()
                ->values()
                ->all(),
        );
    }

    /**
     * @return array<string, array{pinned?: bool, sortOrder?: int|null}>
     */
    public function productOrderingOverrides(): array
    {
        $overrides = $this->get(self::PRODUCT_ORDERING_KEY, []);

        if (! is_array($overrides)) {
            return [];
        }

        return collect($overrides)
            ->mapWithKeys(function ($override, string $productId) {
                if (! is_array($override)) {
                    return [];
                }

                $pinned = array_key_exists('pinned', $override) ? (bool) $override['pinned'] : null;
                $sortOrder = filled($override['sort_order'] ?? null) ? (int) $override['sort_order'] : null;

                if ($pinned === null && $sortOrder === null) {
                    return [];
                }

                return [
                    $productId => array_filter([
                        'pinned' => $pinned,
                        'sortOrder' => $sortOrder,
                    ], static fn ($value) => $value !== null),
                ];
            })
            ->all();
    }

    public function saveProductOrderingOverride(string $productId, ?bool $pinned, ?int $sortOrder): void
    {
        $normalizedProductId = Str::lower(trim($productId));
        $overrides = $this->get(self::PRODUCT_ORDERING_KEY, []);

        if (! is_array($overrides)) {
            $overrides = [];
        }

        if ($pinned === null && $sortOrder === null) {
            unset($overrides[$normalizedProductId]);
            $this->put(self::PRODUCT_ORDERING_KEY, $overrides);

            return;
        }

        $overrides[$normalizedProductId] = array_filter([
            'pinned' => $pinned,
            'sort_order' => $sortOrder,
        ], static fn ($value) => $value !== null);

        $this->put(self::PRODUCT_ORDERING_KEY, $overrides);
    }

    public function removeProductOrderingOverride(string $productId): void
    {
        $normalizedProductId = Str::lower(trim($productId));
        $overrides = $this->get(self::PRODUCT_ORDERING_KEY, []);

        if (! is_array($overrides) || ! array_key_exists($normalizedProductId, $overrides)) {
            return;
        }

        unset($overrides[$normalizedProductId]);
        $this->put(self::PRODUCT_ORDERING_KEY, $overrides);
    }

    /**
     * @return array{title: string, tagline: string, logoUrl: string|null, logoPath: string|null}
     */
    public function branding(): array
    {
        $branding = $this->get(self::BRANDING_KEY, []);

        if (! is_array($branding)) {
            $branding = [];
        }

        $logoPath = filled($branding['logo_path'] ?? null) ? (string) $branding['logo_path'] : null;

        return [
            'title' => (string) ($branding['title'] ?? 'LYVA ADMIN'),
            'tagline' => (string) ($branding['tagline'] ?? 'Control Center'),
            'logoUrl' => $logoPath ? Storage::disk('public')->url($logoPath) : null,
            'logoPath' => $logoPath,
        ];
    }

    public function saveBranding(?UploadedFile $logo, ?string $title, ?string $tagline, bool $removeLogo = false): void
    {
        $branding = $this->get(self::BRANDING_KEY, []);

        if (! is_array($branding)) {
            $branding = [];
        }

        if ($removeLogo) {
            $this->deleteStoredPath($branding['logo_path'] ?? null);
            $branding['logo_path'] = null;
        }

        if ($logo) {
            $this->deleteStoredPath($branding['logo_path'] ?? null);
            $branding['logo_path'] = $logo->store('admin/branding', 'public');
        }

        $branding['title'] = filled($title) ? trim((string) $title) : 'LYVA ADMIN';
        $branding['tagline'] = filled($tagline) ? trim((string) $tagline) : 'Control Center';

        $this->put(self::BRANDING_KEY, $branding);
    }

    /**
     * @return array<string, mixed>
     */
    public function sharedPayload(): array
    {
        return [
            'branding' => $this->branding(),
            'productArtworkOverrides' => collect($this->productArtworkOverrides())
                ->map(fn (array $override) => [
                    'coverImage' => $override['coverImage'],
                    'iconImage' => $override['iconImage'],
                ])
                ->all(),
            'productDisplayOverrides' => $this->productDisplayOverrides(),
            'hiddenProductIds' => $this->hiddenProductIds(),
            'productOrderingOverrides' => $this->productOrderingOverrides(),
        ];
    }

    /**
     * @param  array<string, mixed>  $tier
     * @return array{max: int|null, percent: float, fixed: int, round_to: int}
     */
    private function normalizeMarginTier(array $tier): array
    {
        $max = $tier['max'] ?? null;

        return [
            'max' => filled($max) ? (int) $max : null,
            'percent' => round(max(0, (float) ($tier['percent'] ?? 0)), 4),
            'fixed' => max(0, (int) ($tier['fixed'] ?? 0)),
            'round_to' => max(1, (int) ($tier['round_to'] ?? 100)),
        ];
    }

    private function deleteStoredPath(mixed $path): void
    {
        if (! filled($path)) {
            return;
        }

        $normalized = (string) $path;

        if (Storage::disk('public')->exists($normalized)) {
            Storage::disk('public')->delete($normalized);
        }
    }
}
