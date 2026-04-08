<?php

use App\Http\Middleware\EnsureWhatsappIsVerified;
use App\Http\Middleware\EnsureUserCanAccessAdminPanel;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\RejectBotLikeRequests;
use App\Http\Middleware\SecureHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            SecureHeaders::class,
        ]);

        $middleware->alias([
            'whatsapp.verified' => EnsureWhatsappIsVerified::class,
            'admin.panel' => EnsureUserCanAccessAdminPanel::class,
            'bot.guard' => RejectBotLikeRequests::class,
        ]);

        $middleware->redirectUsersTo('/');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
