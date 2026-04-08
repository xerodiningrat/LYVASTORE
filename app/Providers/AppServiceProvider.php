<?php

namespace App\Providers;

use App\Console\Commands\GeminiTestCommand;
use App\Console\Commands\GoogleSheetsBackfillTransactionsCommand;
use App\Console\Commands\RunFinanceAlertCommand;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('support-chat', function (Request $request) {
            return Limit::perMinute(12)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('duitku-payment-methods', function (Request $request) {
            return Limit::perMinute(30)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('vipayment-services', function (Request $request) {
            return Limit::perMinute(30)->by(($request->user()?->id ?: $request->ip()).'|'.(string) $request->route('product'));
        });

        RateLimiter::for('vipayment-nickname', function (Request $request) {
            return Limit::perMinute(90)->by(
                ($request->user()?->id ?: $request->ip())
                .'|'.(string) $request->route('product')
                .'|'.substr((string) $request->input('target', ''), 0, 32)
            );
        });

        RateLimiter::for('checkout-preview', function (Request $request) {
            return Limit::perMinute(8)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('checkout-promo', function (Request $request) {
            return Limit::perMinute(20)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('checkout-rating', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('account-issues', function (Request $request) {
            return Limit::perMinute(6)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('duitku-callback', function (Request $request) {
            return Limit::perMinute(120)->by($request->ip());
        });

        RateLimiter::for('lyvaflow-whatsapp-webhook', function (Request $request) {
            return Limit::perMinute(120)->by($request->ip());
        });

        $this->commands([
            GeminiTestCommand::class,
            GoogleSheetsBackfillTransactionsCommand::class,
            RunFinanceAlertCommand::class,
        ]);
    }
}
