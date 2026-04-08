<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

class MobileCatalogArtworkResolver
{
    private const CACHE_KEY = 'mobile-catalog-artwork-map.v1';

    /**
     * @return array{coverImage: string|null, iconImage: string|null}
     */
    public static function resolve(string $productId): array
    {
        $map = Cache::rememberForever(self::CACHE_KEY, fn () => self::buildMap());
        $entry = $map[$productId] ?? null;

        if (! is_array($entry)) {
            return [
                'coverImage' => null,
                'iconImage' => null,
            ];
        }

        return [
            'coverImage' => self::toAbsoluteUrl($entry['coverImage'] ?? null),
            'iconImage' => self::toAbsoluteUrl($entry['iconImage'] ?? null),
        ];
    }

    /**
     * @return array<string, array{coverImage: string, iconImage: string}>
     */
    private static function buildMap(): array
    {
        $files = [
            resource_path('js/data/home-product-artwork-map.ts'),
            resource_path('js/data/vip-product-artwork-map.ts'),
        ];

        $map = [];

        foreach ($files as $file) {
            if (! is_file($file)) {
                continue;
            }

            $contents = file_get_contents($file);

            if (! is_string($contents) || trim($contents) === '') {
                continue;
            }

            preg_match_all(
                "/'([^']+)'\s*:\s*\{\s*coverImage:\s*'([^']+)'\s*,\s*iconImage:\s*'([^']+)'\s*\}/",
                $contents,
                $matches,
                PREG_SET_ORDER,
            );

            foreach ($matches as $match) {
                $map[$match[1]] = [
                    'coverImage' => $match[2],
                    'iconImage' => $match[3],
                ];
            }
        }

        return $map;
    }

    private static function toAbsoluteUrl(?string $path): ?string
    {
        $value = trim((string) $path);

        if ($value === '') {
            return null;
        }

        if (preg_match('#^https?://#i', $value)) {
            return $value;
        }

        return url(ltrim($value, '/'));
    }
}
