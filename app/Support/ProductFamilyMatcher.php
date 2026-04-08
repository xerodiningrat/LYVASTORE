<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

class ProductFamilyMatcher
{
    /**
     * @return array{familyId: string|null, title: string|null, normalizedName: string}
     */
    public static function resolve(?string $productId, ?string $productName = null): array
    {
        $productId = trim((string) $productId);
        $normalizedName = self::normalizeName($productName);

        foreach (self::familyRules() as $rule) {
            if ($rule['match']($productId, $normalizedName)) {
                return [
                    'familyId' => $rule['id'],
                    'title' => $rule['title'],
                    'normalizedName' => $normalizedName,
                ];
            }
        }

        return [
            'familyId' => null,
            'title' => null,
            'normalizedName' => $normalizedName,
        ];
    }

    public static function applyTransactionScope(Builder $query, ?string $productId, ?string $productName = null): Builder
    {
        $resolved = self::resolve($productId, $productName);
        $normalizedProductId = trim((string) $productId);

        return $query->where(function (Builder $builder) use ($resolved, $normalizedProductId): void {
            match ($resolved['familyId']) {
                'mobile-legends' => $builder
                    ->where('product_id', 'like', 'mobile-legends%')
                    ->orWhereRaw('lower(product_name) like ?', ['mobile legends%']),
                'free-fire' => $builder
                    ->where('product_id', 'like', 'free-fire%')
                    ->orWhereRaw('lower(product_name) like ?', ['free fire%']),
                'pubg-mobile' => $builder
                    ->where('product_id', 'like', 'pubg%')
                    ->orWhereRaw('lower(product_name) like ?', ['pubg mobile%']),
                'honor-of-kings' => $builder
                    ->where('product_id', 'like', 'honor-of-kings%')
                    ->orWhere('product_id', 'like', 'hok-%')
                    ->orWhereRaw('lower(product_name) like ?', ['honor of kings%']),
                default => self::applyExactProductScope($builder, $normalizedProductId, $resolved['normalizedName']),
            };
        });
    }

    /**
     * @return array<int, array{id: string, title: string, match: callable(string, string): bool}>
     */
    private static function familyRules(): array
    {
        return [
            [
                'id' => 'mobile-legends',
                'title' => 'Mobile Legends',
                'match' => static fn (string $productId, string $normalizedName): bool =>
                    str_starts_with($productId, 'mobile-legends') || str_starts_with($normalizedName, 'mobile legends'),
            ],
            [
                'id' => 'free-fire',
                'title' => 'Free Fire',
                'match' => static fn (string $productId, string $normalizedName): bool =>
                    str_starts_with($productId, 'free-fire') || str_starts_with($normalizedName, 'free fire'),
            ],
            [
                'id' => 'pubg-mobile',
                'title' => 'PUBG Mobile',
                'match' => static fn (string $productId, string $normalizedName): bool =>
                    str_starts_with($productId, 'pubg') || str_starts_with($normalizedName, 'pubg mobile'),
            ],
            [
                'id' => 'honor-of-kings',
                'title' => 'Honor of Kings',
                'match' => static fn (string $productId, string $normalizedName): bool =>
                    str_starts_with($productId, 'honor-of-kings')
                    || str_starts_with($productId, 'hok-')
                    || str_starts_with($normalizedName, 'honor of kings'),
            ],
        ];
    }

    private static function applyExactProductScope(Builder $builder, string $productId, string $normalizedName): void
    {
        if ($productId !== '') {
            $builder->where('product_id', $productId);

            if ($normalizedName !== '') {
                $builder->orWhereRaw('lower(product_name) = ?', [$normalizedName]);
            }

            return;
        }

        if ($normalizedName !== '') {
            $builder->whereRaw('lower(product_name) = ?', [$normalizedName]);
        }
    }

    private static function normalizeName(?string $productName): string
    {
        return mb_strtolower(trim((string) $productName));
    }
}
