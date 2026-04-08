<?php

$baseUrl = env('VIPAYMENT_BASE_URL');

return [
    'api_id' => env('VIPAYMENT_API_ID'),

    'api_key' => env('VIPAYMENT_API_KEY'),

    'base_url' => filled($baseUrl) ? $baseUrl : 'https://vip-reseller.co.id',

    'timeout' => (int) env('VIPAYMENT_TIMEOUT', 15),

    'cache_ttl' => (int) env('VIPAYMENT_CACHE_TTL', 10),

    'price_tier' => env('VIPAYMENT_PRICE_TIER', 'basic'),

    'selling_price' => [
        'enabled' => filter_var(env('VIPAYMENT_SELLING_PRICE_ENABLED', true), FILTER_VALIDATE_BOOL),
        'tiers' => [
            [
                'max' => 10000,
                'percent' => (float) env('VIPAYMENT_MARGIN_UNDER_10K_PERCENT', 0.12),
                'fixed' => (int) env('VIPAYMENT_MARGIN_UNDER_10K_FIXED', 400),
                'round_to' => (int) env('VIPAYMENT_MARGIN_UNDER_10K_ROUND', 100),
            ],
            [
                'max' => 25000,
                'percent' => (float) env('VIPAYMENT_MARGIN_UNDER_25K_PERCENT', 0.10),
                'fixed' => (int) env('VIPAYMENT_MARGIN_UNDER_25K_FIXED', 600),
                'round_to' => (int) env('VIPAYMENT_MARGIN_UNDER_25K_ROUND', 100),
            ],
            [
                'max' => 50000,
                'percent' => (float) env('VIPAYMENT_MARGIN_UNDER_50K_PERCENT', 0.085),
                'fixed' => (int) env('VIPAYMENT_MARGIN_UNDER_50K_FIXED', 900),
                'round_to' => (int) env('VIPAYMENT_MARGIN_UNDER_50K_ROUND', 100),
            ],
            [
                'max' => 100000,
                'percent' => (float) env('VIPAYMENT_MARGIN_UNDER_100K_PERCENT', 0.07),
                'fixed' => (int) env('VIPAYMENT_MARGIN_UNDER_100K_FIXED', 1200),
                'round_to' => (int) env('VIPAYMENT_MARGIN_UNDER_100K_ROUND', 500),
            ],
            [
                'max' => 250000,
                'percent' => (float) env('VIPAYMENT_MARGIN_UNDER_250K_PERCENT', 0.055),
                'fixed' => (int) env('VIPAYMENT_MARGIN_UNDER_250K_FIXED', 1800),
                'round_to' => (int) env('VIPAYMENT_MARGIN_UNDER_250K_ROUND', 500),
            ],
            [
                'max' => null,
                'percent' => (float) env('VIPAYMENT_MARGIN_ABOVE_250K_PERCENT', 0.045),
                'fixed' => (int) env('VIPAYMENT_MARGIN_ABOVE_250K_FIXED', 2500),
                'round_to' => (int) env('VIPAYMENT_MARGIN_ABOVE_250K_ROUND', 1000),
            ],
        ],
    ],
];
