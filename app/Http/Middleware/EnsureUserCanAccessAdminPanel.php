<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserCanAccessAdminPanel
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->canAccessAdminPanel()) {
            return $next($request);
        }

        return redirect()->route('home')->with('status', 'Panel dashboard khusus owner dan admin.');
    }
}
