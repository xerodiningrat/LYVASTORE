<?php

$sandbox = env('DUITKU_SANDBOX', true);
$baseUrl = env('DUITKU_BASE_URL');

return [
    'sandbox' => $sandbox,

    'merchant_code' => env('DUITKU_MERCHANT_CODE'),

    'api_key' => env('DUITKU_API_KEY'),

    'base_url' => filled($baseUrl) ? $baseUrl : ($sandbox ? 'https://sandbox.duitku.com' : 'https://passport.duitku.com'),

    'timeout' => (int) env('DUITKU_TIMEOUT', 15),

    'cache_ttl' => (int) env('DUITKU_CACHE_TTL', 10),
];
