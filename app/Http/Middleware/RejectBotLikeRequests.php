<?php

namespace App\Http\Middleware;

use App\Services\SecurityEventService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RejectBotLikeRequests
{
    private const MIN_FILL_MILLISECONDS = 800;
    private const MAX_FORM_AGE_SECONDS = 7200;

    public function handle(Request $request, Closure $next): Response
    {
        $trap = trim((string) $request->input('website', ''));
        $startedAt = (int) $request->input('formStartedAt', 0);
        $nowMilliseconds = (int) round(microtime(true) * 1000);
        $security = app(SecurityEventService::class);

        if ($trap !== '') {
            $security->warning('bot_guard_honeypot_triggered', $security->requestContext($request));
            abort(422, 'Permintaan ditolak.');
        }

        if ($startedAt <= 0) {
            $security->warning('bot_guard_missing_form_started_at', $security->requestContext($request));
            abort(422, 'Permintaan tidak valid.');
        }

        $elapsed = $nowMilliseconds - $startedAt;

        if ($elapsed < self::MIN_FILL_MILLISECONDS || $elapsed > (self::MAX_FORM_AGE_SECONDS * 1000)) {
            $security->warning('bot_guard_invalid_fill_time', $security->requestContext($request, [
                'elapsed_ms' => $elapsed,
            ]));
            abort(422, 'Permintaan tidak valid.');
        }

        return $next($request);
    }
}
