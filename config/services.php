<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'lyvaflow' => [
        'base_url' => env('LYVAFLOW_BASE_URL', 'https://lyvaflow.my.id/external/v1'),
        'api_key' => env('LYVAFLOW_API_KEY'),
        'timeout' => (int) env('LYVAFLOW_TIMEOUT', 15),
        'verify_ssl' => filter_var(env('LYVAFLOW_VERIFY_SSL', true), FILTER_VALIDATE_BOOL),
        'incoming_webhook_secret' => env('LYVAFLOW_INCOMING_WEBHOOK_SECRET'),
        'reply_delay_enabled' => filter_var(env('LYVAFLOW_REPLY_DELAY_ENABLED', true), FILTER_VALIDATE_BOOL),
        'reply_delay_base_min_ms' => (int) env('LYVAFLOW_REPLY_DELAY_BASE_MIN_MS', 2200),
        'reply_delay_base_max_ms' => (int) env('LYVAFLOW_REPLY_DELAY_BASE_MAX_MS', 4200),
        'reply_delay_long_message_bonus_ms' => (int) env('LYVAFLOW_REPLY_DELAY_LONG_MESSAGE_BONUS_MS', 2200),
        'reply_delay_qr_bonus_ms' => (int) env('LYVAFLOW_REPLY_DELAY_QR_BONUS_MS', 1800),
        'reply_delay_max_ms' => (int) env('LYVAFLOW_REPLY_DELAY_MAX_MS', 9500),
    ],

    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'chat_id' => env('TELEGRAM_CHAT_ID'),
        'timeout' => (int) env('TELEGRAM_TIMEOUT', 15),
    ],

    'support' => [
        'chat_url' => env('SUPPORT_CHAT_URL'),
    ],

    'lyva_chatbot' => [
        'endpoint' => env('LYVA_CHATBOT_ENDPOINT', 'http://127.0.0.1:8200/api/chat'),
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'chat_model' => env('OPENAI_CHAT_MODEL', 'gpt-5'),
        'timeout' => (int) env('OPENAI_TIMEOUT', 20),
    ],

    'google_sheets' => [
        'webhook_url' => env('GOOGLE_SHEETS_WEBHOOK_URL'),
        'webhook_token' => env('GOOGLE_SHEETS_WEBHOOK_TOKEN'),
        'timeout' => (int) env('GOOGLE_SHEETS_TIMEOUT', 15),
    ],

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-2.5-pro'),
        'timeout' => (int) env('GEMINI_TIMEOUT', 20),
    ],

    'background_remover' => [
        'python_binary' => env('BACKGROUND_REMOVER_PYTHON', base_path('.venv-background-remover/bin/python')),
        'script_path' => env('BACKGROUND_REMOVER_SCRIPT', base_path('scripts/background_remover/remove_background.py')),
        'model' => env('BACKGROUND_REMOVER_MODEL', 'u2netp'),
        'model_path' => env('BACKGROUND_REMOVER_MODEL_PATH', storage_path('app/background-remover/models')),
        'timeout' => (int) env('BACKGROUND_REMOVER_TIMEOUT', 180),
        'temp_retention_minutes' => max(1, (int) env('BACKGROUND_REMOVER_TTL_MINUTES', 15)),
    ],

];
