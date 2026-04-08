<?php

namespace App\Support;

class MobileCatalogCategoryResolver
{
    /**
     * @return array{categoryId: string, categoryTitle: string, sortOrder: int}|null
     */
    public static function resolve(string $productId): ?array
    {
        $normalizedId = self::normalizeId($productId);

        foreach (self::sections() as $sectionIndex => $section) {
            $itemIndex = array_search($normalizedId, $section['items'], true);

            if ($itemIndex !== false) {
                return [
                    'categoryId' => $section['id'],
                    'categoryTitle' => $section['title'],
                    'sortOrder' => ($sectionIndex * 100) + $itemIndex,
                ];
            }
        }

        return null;
    }

    /**
     * @return array<int, array{id: string, title: string, items: array<int, string>}>
     */
    private static function sections(): array
    {
        return [
            [
                'id' => 'popular',
                'title' => 'Lagi Populer',
                'items' => [
                    'mobile-legends-a',
                    'mobile-legends-b',
                    'bigo-live',
                    'free-fire',
                    'genshin-impact',
                    'mobile-legends-gift-skin',
                    'mobile-legends-global',
                ],
            ],
            [
                'id' => 'instant',
                'title' => 'Top Up Langsung',
                'items' => [
                    'afk-journey',
                    'hero-reborn',
                    'isekai-feast',
                    'free-fire-global',
                    'ace-racer',
                    'wuxia-rising',
                    'astra-knights',
                    'honor-of-kings',
                    'eggy-party',
                    'arena-of-valor',
                ],
            ],
            [
                'id' => 'release',
                'title' => 'Baru Rilis',
                'items' => [
                    'zenless-zone-zero',
                    'delta-force',
                    'crystal-of-atlan',
                    'solo-leveling-arise',
                    'wuthering-waves',
                ],
            ],
            [
                'id' => 'login',
                'title' => 'Top Up Login',
                'items' => [
                    'mobile-legends-login',
                    'free-fire-login',
                    'hok-login',
                    'pubgm-login',
                    'blood-strike-login',
                ],
            ],
            [
                'id' => 'voucher',
                'title' => 'Voucher',
                'items' => [
                    'steam-wallet',
                    'google-play',
                    'apple-store',
                    'razer-gold',
                    'ps-store',
                ],
            ],
            [
                'id' => 'pulsa',
                'title' => 'Pulsa',
                'items' => [
                    'telkomsel',
                    'indosat',
                    'xl',
                    'tri',
                    'smartfren',
                ],
            ],
            [
                'id' => 'entertainment',
                'title' => 'Entertainment',
                'items' => [
                    'spotify',
                    'netflix',
                    'youtube-premium',
                    'viu',
                    'vidio',
                ],
            ],
        ];
    }

    private static function normalizeId(string $productId): string
    {
        $trimmed = trim($productId);

        $aliases = [
            'vip-game-mobile-legends-a' => 'mobile-legends-a',
            'vip-game-mobile-legends-b' => 'mobile-legends-b',
            'vip-game-mobile-legends-gift' => 'mobile-legends-gift-skin',
            'vip-game-mobile-legends-global' => 'mobile-legends-global',
            'vip-game-mobile-legends-via-login' => 'mobile-legends-login',
            'vip-game-free-fire' => 'free-fire',
            'vip-game-free-fire-global' => 'free-fire-global',
            'vip-game-free-fire-via-login' => 'free-fire-login',
            'vip-game-genshin-impact' => 'genshin-impact',
            'vip-game-honor-of-kings' => 'honor-of-kings',
            'vip-game-honor-of-kings-via-login' => 'hok-login',
            'vip-game-zenless-zone-zero-zzz' => 'zenless-zone-zero',
            'vip-game-pubg-mobile-id' => 'pubgm-login',
            'vip-game-google-play-indonesia' => 'google-play',
            'vip-game-steam-wallet-code' => 'steam-wallet',
            'vip-game-voucher-psn' => 'ps-store',
            'vip-game-voucher-razer-gold' => 'razer-gold',
            'vip-game-vidio-premier' => 'vidio',
            'vip-game-viu-premium' => 'viu',
            'vip-game-youtube-premium' => 'youtube-premium',
            'vip-game-netflix' => 'netflix',
            'vip-game-spotify-premium' => 'spotify',
            'vip-prepaid-telkomsel' => 'telkomsel',
            'vip-prepaid-indosat' => 'indosat',
            'vip-prepaid-tri' => 'tri',
            'vip-prepaid-smartfren' => 'smartfren',
            'vip-prepaid-xl' => 'xl',
        ];

        if (isset($aliases[$trimmed])) {
            return $aliases[$trimmed];
        }

        foreach (['vip-game-', 'vip-prepaid-'] as $prefix) {
            if (str_starts_with($trimmed, $prefix)) {
                return substr($trimmed, strlen($prefix));
            }
        }

        return $trimmed;
    }
}
