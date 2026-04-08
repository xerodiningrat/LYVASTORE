<?php

return [
    'enabled' => filter_var(env('PRIVATE_INSTALLMENT_ENABLED', true), FILTER_VALIDATE_BOOL),
    'path' => trim((string) env('PRIVATE_INSTALLMENT_PATH', 'private/cicilan-5-juta-30k'), '/'),
    'access_key' => env('PRIVATE_INSTALLMENT_ACCESS_KEY'),
    'title' => env('PRIVATE_INSTALLMENT_TITLE', 'Halaman Private Pembayaran SeaBank'),
    'description' => env(
        'PRIVATE_INSTALLMENT_DESCRIPTION',
        'Halaman pembayaran private dengan nominal bebas melalui transfer manual SeaBank. Minimal pembayaran Rp50.000.',
    ),
    'product_id' => env('PRIVATE_INSTALLMENT_PRODUCT_ID', 'private-cicilan-5-juta-30k'),
    'product_name' => env('PRIVATE_INSTALLMENT_PRODUCT_NAME', 'Pembayaran Private SeaBank'),
    'package_label' => env('PRIVATE_INSTALLMENT_PACKAGE_LABEL', 'Pembayaran Private SeaBank'),
    'image' => env('PRIVATE_INSTALLMENT_IMAGE', '/brand/lyva-mascot-hd.png'),
    'target_amount' => (int) env('PRIVATE_INSTALLMENT_TARGET_AMOUNT', 5030000),
    'minimum_amount' => (int) env('PRIVATE_INSTALLMENT_MINIMUM_AMOUNT', 50000),
    'default_amount' => (int) env('PRIVATE_INSTALLMENT_DEFAULT_AMOUNT', 50000),
    'bank_name' => env('PRIVATE_INSTALLMENT_BANK_NAME', 'SeaBank'),
    'account_number' => env('PRIVATE_INSTALLMENT_ACCOUNT_NUMBER', '901662731812'),
    'account_holder' => env('PRIVATE_INSTALLMENT_ACCOUNT_HOLDER', 'Febyanta Yoga Pratama'),
    'checkout_notice' => env(
        'PRIVATE_INSTALLMENT_CHECKOUT_NOTICE',
        'Pembayaran utang dilakukan lewat transfer manual SeaBank. Bisa dicicil mulai Rp50.000 dan progres pembayaran akan terus diperbarui.',
    ),
    'guarantee_text' => env(
        'PRIVATE_INSTALLMENT_GUARANTEE_TEXT',
        'Gunakan halaman ini hanya untuk penerima link private yang dituju.',
    ),
    'notes' => [
        'Total utang: Rp5.030.000.',
        'Pembayaran bisa dicicil dengan nominal bebas, minimal Rp50.000.',
        'Transfer dilakukan manual ke rekening SeaBank yang tampil di halaman ini.',
        'Pengecekan pembayaran dilakukan dari notifikasi uang masuk di email.',
        'Setelah transfer, status transaksi bisa dipantau dari halaman checkout private.',
    ],
    'reminder' => [
        'enabled' => filter_var(env('PRIVATE_INSTALLMENT_WEEKLY_REMINDER_ENABLED', true), FILTER_VALIDATE_BOOL),
        'target_whatsapp' => env('PRIVATE_INSTALLMENT_WEEKLY_REMINDER_WHATSAPP', '085745570925'),
        'day_of_week' => (int) env('PRIVATE_INSTALLMENT_WEEKLY_REMINDER_DAY', 1),
        'time' => env('PRIVATE_INSTALLMENT_WEEKLY_REMINDER_TIME', '09:00'),
    ],
];
