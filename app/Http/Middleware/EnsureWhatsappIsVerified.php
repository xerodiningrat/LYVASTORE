<?php

namespace App\Http\Middleware;

use App\Services\WhatsappVerificationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureWhatsappIsVerified
{
    public function __construct(
        private readonly WhatsappVerificationService $verification,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $this->verification->isVerified($user)) {
            return $next($request);
        }

        if (
            $request->routeIs('verification.whatsapp.notice')
            || $request->routeIs('verification.whatsapp.verify')
            || $request->routeIs('verification.whatsapp.send')
            || $request->routeIs('profile.edit')
            || $request->routeIs('profile.update')
            || $request->routeIs('profile.destroy')
            || $request->routeIs('logout')
        ) {
            return $next($request);
        }

        return redirect()->route('verification.whatsapp.notice');
    }
}
