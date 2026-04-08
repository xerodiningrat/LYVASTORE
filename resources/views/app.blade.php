<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="description" content="Lyva adalah Lyva Indonesia, tempat top up game, voucher digital, e-wallet, pulsa, dan langganan premium seperti ChatGPT Premium dengan proses cepat, aman, dan praktis.">
        <meta name="robots" content="index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1">
        <meta name="theme-color" content="#ffffff">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="default">
        <meta name="apple-mobile-web-app-title" content="LYVA Indonesia">
        <meta property="og:site_name" content="Lyva Indonesia">
        <meta property="og:type" content="website">
        <meta property="og:title" content="Lyva | Top Up Game & Voucher Digital | Lyva Indonesia">
        <meta property="og:description" content="Lyva adalah Lyva Indonesia, tempat top up game, voucher digital, e-wallet, pulsa, dan langganan premium seperti ChatGPT Premium.">
        <meta property="og:image" content="{{ url('/brand/lyva-mascot-hd.png') }}">
        <meta property="og:locale" content="id_ID">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="Lyva | Top Up Game & Voucher Digital | Lyva Indonesia">
        <meta name="twitter:description" content="Lyva adalah Lyva Indonesia, tempat top up game, voucher digital, e-wallet, pulsa, dan langganan premium seperti ChatGPT Premium.">
        <meta name="twitter:image" content="{{ url('/brand/lyva-mascot-hd.png') }}">

        <title inertia>LYVA | TOP UP GAME & VOUCHER DIGITAL | LYVA INDONESIA</title>
        <link rel="icon" type="image/png" sizes="32x32" href="/brand/lyva-mascot-mark.png">
        <link rel="apple-touch-icon" href="/brand/lyva-mascot-hd.png">
        <link rel="manifest" href="/manifest.webmanifest">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        @php
            $routeName = request()->route()?->getName() ?? '';
            $ziggyGroups = ['public'];
            $requiresAuthRoutes = \Illuminate\Support\Str::is(
                ['login', 'logout', 'register', 'profile.*', 'password.*', 'verification.*', 'appearance'],
                $routeName,
            );
            $isAdminRoute = $routeName === 'dashboard' || \Illuminate\Support\Str::startsWith($routeName, 'admin.');

            if ($isAdminRoute) {
                $ziggyGroups[] = 'auth';
                $ziggyGroups[] = 'admin';
            } elseif (auth()->check() || $requiresAuthRoutes) {
                $ziggyGroups[] = 'auth';
            }
        @endphp

        @routes($ziggyGroups, nonce: \Illuminate\Support\Facades\Vite::cspNonce())
        @vite(['resources/js/app.ts'])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
