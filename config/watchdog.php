<?php

return [
    'enabled' => filter_var(env('WATCHDOG_ENABLED', true), FILTER_VALIDATE_BOOL),
    'lookback_minutes' => (int) env('WATCHDOG_LOOKBACK_MINUTES', 10),
    'dedupe_minutes' => (int) env('WATCHDOG_DEDUPE_MINUTES', 30),
    'max_entries_per_scan' => (int) env('WATCHDOG_MAX_ENTRIES', 8),
    'log_files' => [
        storage_path('logs/laravel.log'),
        storage_path('logs/security.log'),
    ],
];
