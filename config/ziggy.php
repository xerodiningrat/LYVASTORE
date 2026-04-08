<?php

return [
    'groups' => [
        'public' => [
            'home',
            'login',
            'register',
            'private-installment.*',
            'products.show',
            'transactions.history',
            'leaderboard',
            'coins.index',
            'articles.index',
            'account-issues.*',
            'vipayment.products.services',
            'vipayment.products.nickname',
            'duitku.payment-methods',
            'checkout.preview.store',
            'checkout.promo.resolve',
            'checkout.rating.store',
        ],
        'auth' => [
            'login',
            'logout',
            'register',
            'profile.*',
            'password.*',
            'verification.*',
            'appearance',
        ],
        'admin' => [
            'dashboard',
            'admin.*',
        ],
    ],
];
