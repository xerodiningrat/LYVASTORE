<?php

namespace App\Http\Middleware;

use App\Models\Transaction;
use App\Services\LyvaCoinService;
use App\Services\PromoCodeService;
use App\Services\SiteSettingService;
use App\Services\SupportChatService;
use App\Services\VipaymentService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Str;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');
        $user = $request->user();
        $settings = app(SiteSettingService::class);
        $coinProgram = app(LyvaCoinService::class);
        $promos = app(PromoCodeService::class);
        $supportChat = app(SupportChatService::class);
        $vipayment = app(VipaymentService::class);
        $adminPanel = $settings->sharedPayload();

        return array_merge(parent::share($request), [
            ...parent::share($request),
            'name' => config('app.name'),
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            'auth' => [
                'user' => $user ? array_merge($user->toArray(), [
                    'is_admin' => $user->canAccessAdminPanel(),
                    'is_owner' => $user->isOwner(),
                ]) : null,
                'coins' => fn () => [
                    'balance' => $user
                        ? app(LyvaCoinService::class)->balanceForUser($user)
                        : 0,
                ],
            ],
            'adminPanel' => [
                'branding' => $adminPanel['branding'],
                'productArtworkOverrides' => $adminPanel['productArtworkOverrides'],
                'productDisplayOverrides' => $adminPanel['productDisplayOverrides'],
                'hiddenProductIds' => $adminPanel['hiddenProductIds'],
                'productOrderingOverrides' => $adminPanel['productOrderingOverrides'],
                'deployStatus' => $this->deployStatus(),
            ],
            'support' => [
                'chatUrl' => config('services.support.chat_url') ?: route('transactions.history', absolute: false),
                'chatEndpoint' => route('support.chat.reply', absolute: false),
                'aiEnabled' => $supportChat->configured(),
            ],
            'flash' => [
                'status' => fn () => $request->session()->get('status'),
            ],
            'security' => [
                'checkoutIntentToken' => fn () => $this->checkoutIntentToken($request),
                'cspNonce' => fn () => Vite::cspNonce(),
            ],
            'promos' => fn () => $promos->publicPromos(),
            'unavailableProductIds' => fn () => $this->safeUnavailableProductIds($vipayment),
            'coinProgram' => $coinProgram->frontendConfig(),
            'recentPurchases' => fn () => $this->recentPurchases(),
        ]);
    }

    /**
     * @return array<int, array<string, string|null>>
     */
    private function recentPurchases(): array
    {
        $todayStart = CarbonImmutable::now('Asia/Jakarta')->startOfDay()->timezone(config('app.timezone'));

        return Transaction::query()
            ->where('payment_status', Transaction::PAYMENT_STATUS_PAID)
            ->where('product_source', Transaction::PRODUCT_SOURCE_VIPAYMENT)
            ->whereIn('status', [Transaction::STATUS_PROCESSING, Transaction::STATUS_COMPLETED])
            ->whereNotNull('product_name')
            ->where(function ($query) use ($todayStart) {
                $query
                    ->where('paid_at', '>=', $todayStart->toDateTimeString())
                    ->orWhere(function ($nested) use ($todayStart) {
                        $nested
                            ->whereNull('paid_at')
                            ->where('created_at', '>=', $todayStart->toDateTimeString());
                    });
            })
            ->orderByDesc('paid_at')
            ->orderByDesc('id')
            ->limit(8)
            ->get([
                'public_id',
                'status',
                'product_name',
                'product_image',
                'package_label',
                'customer_name',
                'total',
                'paid_at',
                'created_at',
            ])
            ->map(fn (Transaction $transaction) => [
                'id' => (string) $transaction->public_id,
                'customerLabel' => $this->maskCustomerName($transaction->customer_name),
                'productLabel' => $this->formatPurchaseProductLabel($transaction),
                'amountLabel' => 'Rp'.number_format((int) ($transaction->total ?? 0), 0, ',', '.'),
                'timeLabel' => $this->formatPurchaseTimeLabel($transaction),
                'occurredAt' => optional($transaction->paid_at ?? $transaction->created_at)?->copy()->timezone('Asia/Jakarta')->toIso8601String(),
                'statusLabel' => $transaction->status === Transaction::STATUS_COMPLETED ? 'Pesanan selesai' : 'Pembayaran masuk',
                'productImage' => $transaction->product_image ?: null,
            ])
            ->values()
            ->all();
    }

    private function maskCustomerName(?string $name): string
    {
        $normalizedName = trim((string) $name);

        if ($normalizedName === '') {
            return 'Pelanggan';
        }

        $firstName = collect(preg_split('/\s+/', $normalizedName) ?: [])
            ->filter()
            ->first();

        if (! is_string($firstName) || $firstName === '') {
            return 'Pelanggan';
        }

        if (Str::startsWith(Str::lower($firstName), ['feb'])) {
            return 'Pel***';
        }

        return Str::substr($firstName, 0, min(3, Str::length($firstName))).'***';
    }

    private function checkoutIntentToken(Request $request): string
    {
        $token = (string) $request->session()->get('checkout_intent_token', '');

        if ($token !== '') {
            return $token;
        }

        $token = Str::random(48);
        $request->session()->put('checkout_intent_token', $token);

        return $token;
    }

    /**
     * Keep public pages rendering even when the upstream VIPayment API is slow.
     *
     * @return array<int, string>
     */
    private function safeUnavailableProductIds(VipaymentService $vipayment): array
    {
        try {
            return $vipayment->unavailablePublicProductIds();
        } catch (\Throwable $exception) {
            report($exception);

            Log::warning('Unable to resolve unavailable VIPayment products for shared Inertia props.', [
                'message' => $exception->getMessage(),
            ]);

            return [];
        }
    }

    private function formatPurchaseProductLabel(Transaction $transaction): string
    {
        $productName = trim((string) $transaction->product_name);
        $packageLabel = trim((string) ($transaction->package_label ?? ''));

        if ($packageLabel === '' || strcasecmp($productName, $packageLabel) === 0) {
            return $productName;
        }

        return $productName.' • '.$packageLabel;
    }

    private function formatPurchaseTimeLabel(Transaction $transaction): string
    {
        $referenceTime = $transaction->paid_at ?? $transaction->created_at;

        if (! $referenceTime) {
            return 'Baru saja';
        }

        return $referenceTime
            ->copy()
            ->timezone('Asia/Jakarta')
            ->locale('id')
            ->diffForHumans();
    }

    /**
     * @return array{deployedAt: string|null, deployedAtLabel: string|null, mode: string|null}
     */
    private function deployStatus(): array
    {
        $path = storage_path('logs/deploy-status.json');

        if (! File::exists($path)) {
            return [
                'deployedAt' => null,
                'deployedAtLabel' => null,
                'mode' => null,
            ];
        }

        $payload = json_decode((string) File::get($path), true);

        if (! is_array($payload)) {
            return [
                'deployedAt' => null,
                'deployedAtLabel' => null,
                'mode' => null,
            ];
        }

        return [
            'deployedAt' => filled($payload['deployed_at'] ?? null) ? (string) $payload['deployed_at'] : null,
            'deployedAtLabel' => filled($payload['deployed_at_label'] ?? null) ? (string) $payload['deployed_at_label'] : null,
            'mode' => filled($payload['mode'] ?? null) ? (string) $payload['mode'] : null,
        ];
    }
}
