<?php

use Illuminate\Support\Str;

$parseCsv = static fn (?string $value): array => collect(explode(',', (string) $value))
    ->map(fn (string $item) => Str::lower(trim($item)))
    ->filter()
    ->values()
    ->all();

return [
    'managed_keywords' => $parseCsv(env('MANUAL_STOCK_PRODUCT_KEYWORDS', 'chatgpt,capcut')),
    'notification_emails' => $parseCsv(env('MANUAL_STOCK_NOTIFICATION_EMAILS', '')),
    'catalog_offers' => [
        'vip-game-chatgpt' => [
            [
                'tab_id' => 'purchase',
                'tab_label' => 'Pembelian',
                'group_id' => 'chatgpt',
                'group_label' => 'ChatGPT',
                'group_title' => 'Produk pilihan',
                'option' => [
                    'id' => 'chatgpt-sharing',
                    'code' => '',
                    'label' => 'ChatGPT Sharing',
                    'note' => 'Akses sharing dikirim manual oleh admin Lyva.',
                    'details' => [
                        'Cocok untuk kebutuhan sharing dengan harga paling hemat.',
                        'Stok akun sharing dikelola manual lewat dashboard admin.',
                        'Setelah pembayaran masuk, admin akan menyiapkan akun dari stok yang tersedia.',
                    ],
                    'accountFields' => [
                        [
                            'id' => 'account-email',
                            'label' => 'Email akun',
                            'placeholder' => 'Masukkan email akun',
                            'inputType' => 'email',
                        ],
                    ],
                    'price' => 5000,
                ],
            ],
        ],
    ],
];
