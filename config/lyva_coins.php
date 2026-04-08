<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Nilai Tukar Coins
    |--------------------------------------------------------------------------
    |
    | 1 coin mewakili berapa rupiah nilai reward ketika nanti dipakai untuk
    | penukaran benefit / produk.
    |
    */
    'coin_value_rupiah' => (int) env('LYVA_COIN_VALUE_RUPIAH', 1),

    /*
    |--------------------------------------------------------------------------
    | Batas Cashback Maksimum dari Harga Jual
    |--------------------------------------------------------------------------
    |
    | Coins tidak akan melebihi persentase ini dari total harga jual. Dengan
    | begitu nominal reward tetap kompetitif dan tidak terlalu agresif.
    |
    */
    'max_reward_percent_of_selling_price' => (float) env('LYVA_COIN_MAX_PERCENT_OF_SELLING_PRICE', 0.01),

    /*
    |--------------------------------------------------------------------------
    | Porsi Reward dari Margin Estimasi
    |--------------------------------------------------------------------------
    |
    | Reward coins juga dibatasi berdasarkan porsi margin estimasi supaya profit
    | inti tetap terjaga meskipun coins nanti bisa ditukar dengan produk.
    |
    */
    'reward_share_of_estimated_profit' => (float) env('LYVA_COIN_REWARD_SHARE_OF_ESTIMATED_PROFIT', 0.15),

    /*
    |--------------------------------------------------------------------------
    | Reward Minimum
    |--------------------------------------------------------------------------
    */
    'minimum_reward_coins' => (int) env('LYVA_COIN_MINIMUM_REWARD', 1),
];
