<?php

return [
    'commission_percent' => (float) env('AFFILIATE_COMMISSION_PERCENT', 5),
    'max_share_of_estimated_profit' => (float) env('AFFILIATE_MAX_SHARE_OF_ESTIMATED_PROFIT', 0.35),
    'first_signup_reward_coins' => (int) env('AFFILIATE_FIRST_SIGNUP_REWARD_COINS', 1000),
    'signup_reward_coins' => (int) env('AFFILIATE_SIGNUP_REWARD_COINS', 50),
    'freeze_days' => (int) env('AFFILIATE_FREEZE_DAYS', 2),
    'minimum_withdrawal' => (int) env('AFFILIATE_MINIMUM_WITHDRAWAL', 10000),
];
