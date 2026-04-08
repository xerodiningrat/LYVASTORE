<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SecurityEventService
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function info(string $event, array $context = []): void
    {
        Log::channel('security')->info($event, $context);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function warning(string $event, array $context = []): void
    {
        Log::channel('security')->warning($event, $context);
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    public function requestContext(Request $request, array $extra = []): array
    {
        return [
            ...$extra,
            'ip' => $request->ip(),
            'method' => $request->method(),
            'path' => $request->path(),
            'user_id' => $request->user()?->id,
            'guest_token' => $request->session()->get('guest_transaction_token'),
            'user_agent' => (string) $request->userAgent(),
        ];
    }
}
