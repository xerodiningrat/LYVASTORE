<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class TransactionService
{
    private const RECENT_TRANSACTION_SESSION_KEY = 'recent_transaction_public_ids';

    public function __construct(
        private readonly DuitkuService $duitku,
        private readonly VipaymentService $vipayment,
        private readonly LyvaflowService $lyvaflow,
        private readonly ManualStockService $manualStock,
        private readonly LyvaCoinService $coins,
        private readonly AdminOrderNotificationService $adminOrderNotifications,
        private readonly CustomerOrderNotificationService $customerOrderNotifications,
        private readonly GoogleSheetsSyncService $googleSheets,
        private readonly SecurityEventService $securityEvents,
    ) {}

    public function ensureGuestToken(Request $request): string
    {
        $token = (string) $request->session()->get('guest_transaction_token', '');

        if ($token !== '') {
            return $token;
        }

        $token = (string) Str::uuid();
        $request->session()->put('guest_transaction_token', $token);

        return $token;
    }

    public function createCheckoutTransaction(array $validated, Request $request): Transaction
    {
        $this->pruneExpiredGuestTransactions();

        $guestToken = $this->ensureGuestToken($request);
        $user = $request->user();
        $accountFields = $this->normalizeFields($validated['accountFields'] ?? []);
        $contactFields = $this->normalizeFields($validated['contactFields'] ?? []);
        $now = now();
        $expiresAt = $now->copy()->addMinutes(15);
        $publicId = 'LYVA'.Str::upper(Str::random(12));
        $customerEmail = $this->extractFieldValue($contactFields, ['buyer-email']) ?? $user?->email;
        $customerWhatsapp = $this->normalizeWhatsapp($this->extractFieldValue($contactFields, ['buyer-whatsapp']) ?? $user?->whatsapp_number);
        $productId = (string) ($validated['productId'] ?? '');
        $packageLabel = (string) ($validated['packageLabel'] ?? '');
        $resolvedPackageCode = $validated['packageCode'] ?? null;
        $vipaymentEndpoint = null;

        if (filled($resolvedPackageCode)) {
            $vipaymentEndpoint = $this->vipayment->resolveOrderEndpointForProduct($productId);
        } elseif ($productId !== '' && $packageLabel !== '') {
            try {
                $resolvedService = $this->vipayment->findServiceCodeByLabel($productId, $packageLabel);
                $resolvedPackageCode = $resolvedService['code'] ?? null;
                $vipaymentEndpoint = $resolvedService['endpoint'] ?? null;
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        $resolvedPaymentMethodCode = $validated['paymentMethodCode'] ?? null;
        $isCoinPayment = Str::lower((string) ($validated['paymentType'] ?? '')) === 'lyva-coins';

        if (! $isCoinPayment && ! filled($resolvedPaymentMethodCode) && $this->duitku->configured()) {
            try {
                $matchedPaymentMethod = collect($this->duitku->getPaymentMethods((int) round((float) ($validated['total'] ?? 0))))
                    ->first(fn (array $payment) => Str::lower((string) ($payment['label'] ?? '')) === Str::lower((string) ($validated['paymentLabel'] ?? '')));

                $resolvedPaymentMethodCode = $matchedPaymentMethod['id'] ?? null;
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        $productSource = $this->manualStock->supportsProduct($productId)
            ? Transaction::PRODUCT_SOURCE_MANUAL_STOCK
            : (filled($resolvedPackageCode) && $vipaymentEndpoint ? Transaction::PRODUCT_SOURCE_VIPAYMENT : Transaction::PRODUCT_SOURCE_MANUAL);

        $existingTransaction = $this->findRecentDuplicateCheckout(
            $user?->id,
            $guestToken,
            $productId,
            (string) ($validated['packageLabel'] ?? ''),
            (int) ($validated['quantity'] ?? 1),
            (int) round((float) ($validated['total'] ?? 0)),
            $resolvedPaymentMethodCode ? (string) $resolvedPaymentMethodCode : null,
            $customerEmail,
            $customerWhatsapp,
        );

        if ($existingTransaction) {
            $this->securityEvents->info('duplicate_checkout_reused', [
                'public_id' => $existingTransaction->public_id,
                'product_id' => $productId,
                'payment_method_code' => $resolvedPaymentMethodCode,
                'user_id' => $user?->id,
                'guest_token' => $guestToken,
            ]);
            $request->session()->put('last_checkout_transaction_id', $existingTransaction->public_id);
            $this->rememberRecentTransaction($request, $existingTransaction->public_id);

            return $existingTransaction;
        }

        $transaction = DB::transaction(function () use ($validated, $guestToken, $user, $accountFields, $contactFields, $now, $expiresAt, $publicId, $customerEmail, $customerWhatsapp, $vipaymentEndpoint, $productSource, $resolvedPackageCode, $resolvedPaymentMethodCode, $isCoinPayment) {
            $coinSpentAmount = $isCoinPayment ? $this->coins->requiredCoinsForAmount((int) round((float) ($validated['total'] ?? 0))) : 0;

            if ($isCoinPayment) {
                if (! $user) {
                    throw new RuntimeException('Login diperlukan untuk pembayaran Lyva Coins.');
                }

                Transaction::query()
                    ->where('user_id', $user->id)
                    ->lockForUpdate()
                    ->get(['id']);

                if (! $this->coins->canUserPayAmount($user->fresh(), (int) round((float) ($validated['total'] ?? 0)))) {
                    throw new RuntimeException('Saldo Lyva Coins tidak cukup.');
                }
            }

            $transaction = Transaction::create([
                'user_id' => $user?->id,
                'public_id' => $publicId,
                'guest_token' => $guestToken,
                'status' => Transaction::STATUS_PENDING,
                'payment_status' => $isCoinPayment ? Transaction::PAYMENT_STATUS_PAID : Transaction::PAYMENT_STATUS_UNPAID,
                'product_source' => $productSource,
                'product_id' => (string) $validated['productId'],
                'product_name' => (string) $validated['productName'],
                'product_image' => $validated['productImage'] ?? null,
                'package_code' => $resolvedPackageCode,
                'package_label' => (string) $validated['packageLabel'],
                'quantity' => (int) $validated['quantity'],
                'payment_method_code' => $resolvedPaymentMethodCode,
                'payment_method_label' => (string) $validated['paymentLabel'],
                'payment_method_type' => $validated['paymentType'] ?? null,
                'payment_method_image' => $validated['paymentImage'] ?? null,
                'payment_badge' => $validated['paymentBadge'] ?? null,
                'payment_caption' => $validated['paymentCaption'] ?? null,
                'vipayment_endpoint' => $vipaymentEndpoint,
                'subtotal' => (int) round((float) ($validated['subtotal'] ?? $validated['total'] ?? 0)),
                'promo_code' => filled($validated['promoCode'] ?? null) ? (string) $validated['promoCode'] : null,
                'promo_label' => filled($validated['promoLabel'] ?? null) ? (string) $validated['promoLabel'] : null,
                'promo_discount' => max(0, (int) ($validated['promoDiscount'] ?? 0)),
                'promo_snapshot' => is_array($validated['promoSnapshot'] ?? null) ? $validated['promoSnapshot'] : null,
                'coin_spent_amount' => $coinSpentAmount,
                'coin_spent_value' => $coinSpentAmount,
                'total' => (int) round((float) $validated['total']),
                'checkout_notice' => $validated['checkoutNotice'] ?? null,
                'guarantee_text' => $validated['guaranteeText'] ?? null,
                'notes' => $validated['notes'] ?? [],
                'summary_rows' => $validated['summaryRows'] ?? [],
                'account_fields' => $accountFields,
                'contact_fields' => $contactFields,
                'customer_name' => $user?->name ?? $this->extractFieldValue($contactFields, ['buyer-name']),
                'customer_email' => $customerEmail,
                'customer_whatsapp' => $customerWhatsapp,
                'paid_at' => $isCoinPayment ? $now : null,
                'expires_at' => $isCoinPayment ? null : $expiresAt,
                'last_synced_at' => $now,
            ]);

            try {
                if ($isCoinPayment) {
                    $transaction->forceFill([
                        'payment_display_type' => 'coin',
                        'payment_reference_label' => 'Lyva Coins',
                        'payment_reference_value' => (string) $coinSpentAmount.' Coins',
                        'duitku_payment_url' => null,
                        'duitku_app_url' => null,
                        'duitku_qr_string' => null,
                    ])->save();
                } elseif ($this->duitku->configured() && filled($transaction->payment_method_code)) {
                    $invoice = $this->duitku->createInvoice([
                        'merchantOrderId' => $transaction->public_id,
                        'paymentAmount' => (int) $transaction->total,
                        'productDetails' => $transaction->package_label,
                        'paymentMethod' => $transaction->payment_method_code,
                        'customerVaName' => $transaction->customer_name ?: 'Lyva Customer',
                        'email' => $transaction->customer_email,
                        'phoneNumber' => $transaction->customer_whatsapp,
                        'itemDetails' => [[
                            'name' => $transaction->package_label,
                            'price' => (int) $transaction->total,
                            'quantity' => 1,
                        ]],
                        'customerDetail' => [
                            'firstName' => $transaction->customer_name ?: 'Lyva',
                            'email' => $transaction->customer_email,
                            'phoneNumber' => $transaction->customer_whatsapp,
                        ],
                        'callbackUrl' => route('duitku.callback'),
                        'returnUrl' => route('checkout.show', ['transaction' => $transaction->public_id]),
                        'expiryPeriod' => 15,
                        'additionalParam' => $transaction->product_id,
                        'merchantUserInfo' => $transaction->user_id ? 'user:'.$transaction->user_id : 'guest:'.$transaction->guest_token,
                    ]);

                    $this->applyInvoiceData($transaction, $invoice);
                } else {
                    $this->applyFallbackPaymentData($transaction);
                }
            } catch (\Throwable $exception) {
                report($exception);
                $transaction->error_message = $exception->getMessage();
                $this->applyFallbackPaymentData($transaction);
            }

            return $transaction->fresh();
        });

        $request->session()->put('last_checkout_transaction_id', $transaction->public_id);
        $this->rememberRecentTransaction($request, $transaction->public_id);
        $this->dispatchGoogleSheetsSync($transaction, 'created');

        if ($isCoinPayment) {
            $transaction = $this->syncTransaction($transaction, force: true) ?? $transaction;
        }

        return $transaction;
    }

    private function findRecentDuplicateCheckout(
        ?int $userId,
        string $guestToken,
        string $productId,
        string $packageLabel,
        int $quantity,
        int $total,
        ?string $paymentMethodCode,
        ?string $customerEmail,
        ?string $customerWhatsapp,
    ): ?Transaction {
        $query = Transaction::query()
            ->where('status', Transaction::STATUS_PENDING)
            ->where('payment_status', Transaction::PAYMENT_STATUS_UNPAID)
            ->where('product_id', $productId)
            ->where('package_label', $packageLabel)
            ->where('quantity', $quantity)
            ->where('total', $total)
            ->where('created_at', '>=', now()->subMinutes(2));

        if ($paymentMethodCode) {
            $query->where('payment_method_code', $paymentMethodCode);
        }

        if ($userId) {
            $query->where('user_id', $userId);
        } else {
            $query->whereNull('user_id')->where('guest_token', $guestToken);
        }

        if ($customerEmail) {
            $query->where('customer_email', $customerEmail);
        }

        if ($customerWhatsapp) {
            $query->where('customer_whatsapp', $customerWhatsapp);
        }

        return $query->latest('id')->first();
    }

    public function findAccessibleTransaction(Request $request, string $publicId, bool $sync = true): ?Transaction
    {
        $this->pruneExpiredGuestTransactions();

        if ($request->user()) {
            $this->syncGuestTransactionsToUser($request->user(), $request);
        }

        $query = Transaction::query()->where('public_id', $publicId);

        if ($request->user()) {
            $query->where('user_id', $request->user()->id);
        } else {
            $guestToken = (string) $request->session()->get('guest_transaction_token', '');
            $recentTransactionIds = $this->recentTransactionIds($request);

            if ($guestToken === '' && ! in_array($publicId, $recentTransactionIds, true)) {
                return null;
            }

            $query->whereNull('user_id')->where(function ($builder) use ($guestToken, $publicId, $recentTransactionIds) {
                $hasCondition = false;

                if ($guestToken !== '') {
                    $builder->where('guest_token', $guestToken);
                    $hasCondition = true;
                }

                if (in_array($publicId, $recentTransactionIds, true)) {
                    if ($hasCondition) {
                        $builder->orWhere('public_id', $publicId);
                    } else {
                        $builder->where('public_id', $publicId);
                    }
                }
            });
        }

        $transaction = $query->first();

        if (! $transaction) {
            return null;
        }

        if (! $sync) {
            return $this->sanitizeStoredPaymentReference($transaction);
        }

        return $this->syncTransaction($transaction);
    }

    public function hydrateCheckoutPaymentReference(Transaction $transaction): Transaction
    {
        if (! $this->shouldHydrateCheckoutPaymentReference($transaction) && ! $this->shouldHydrateCheckoutQrisData($transaction)) {
            return $transaction;
        }

        try {
            $detail = $this->duitku->getCheckoutPaymentDetail(
                (string) $transaction->duitku_payment_url,
                (string) $transaction->payment_method_code,
            );
        } catch (\Throwable $exception) {
            report($exception);

            return $transaction;
        }

        $updates = [];
        $virtualAccountNumber = $detail['vaNumber'] ?? null;
        $qrString = isset($detail['qrString']) ? trim((string) $detail['qrString']) : '';

        if ($this->isNumericVirtualAccount($virtualAccountNumber)) {
            $updates['payment_display_type'] = 'bank-transfer';
            $updates['payment_reference_label'] = 'Nomor Virtual Account';
            $updates['payment_reference_value'] = preg_replace('/\s+/', '', (string) $virtualAccountNumber);
        }

        if ($qrString !== '') {
            $updates['payment_display_type'] = 'qris';
            $updates['payment_reference_label'] = 'QRIS';
            $updates['payment_reference_value'] = (string) ($detail['reference'] ?? $transaction->duitku_reference ?? $transaction->public_id);
            $updates['duitku_qr_string'] = $qrString;
        }

        if (filled($detail['paymentUrl'] ?? null)) {
            $updates['duitku_payment_url'] = $detail['paymentUrl'];
            $updates['duitku_app_url'] = $detail['paymentUrl'];
        } elseif (filled($detail['redirectUrl'] ?? null)) {
            $updates['duitku_payment_url'] = $detail['redirectUrl'];
            $updates['duitku_app_url'] = $detail['redirectUrl'];
        }

        if ($updates === []) {
            return $transaction;
        }

        $transaction->forceFill($updates)->save();

        return $transaction->fresh();
    }

    public function shouldDeferCheckoutBootstrap(Transaction $transaction, ?string $recentCheckoutId = null): bool
    {
        return filled($recentCheckoutId)
            && hash_equals($transaction->public_id, trim((string) $recentCheckoutId))
            && $transaction->status === Transaction::STATUS_PENDING
            && $transaction->payment_status === Transaction::PAYMENT_STATUS_UNPAID
            && ($transaction->created_at?->diffInSeconds(now()) ?? PHP_INT_MAX) <= 30;
    }

    /**
     * @return Collection<int, Transaction>
     */
    public function getVisibleTransactions(Request $request): Collection
    {
        $this->pruneExpiredGuestTransactions();

        if ($request->user()) {
            $this->syncGuestTransactionsToUser($request->user(), $request);

            $transactions = Transaction::query()
                ->where('user_id', $request->user()->id)
                ->latest()
                ->take(50)
                ->get();
        } else {
            $guestToken = (string) $request->session()->get('guest_transaction_token', '');
            $recentTransactionIds = $this->recentTransactionIds($request);

            if ($guestToken === '' && $recentTransactionIds === []) {
                return collect();
            }

            $transactions = Transaction::query()
                ->whereNull('user_id')
                ->where(function ($builder) use ($guestToken, $recentTransactionIds) {
                    $hasCondition = false;

                    if ($guestToken !== '') {
                        $builder->where('guest_token', $guestToken);
                        $hasCondition = true;
                    }

                    if ($recentTransactionIds !== []) {
                        if ($hasCondition) {
                            $builder->orWhereIn('public_id', $recentTransactionIds);
                        } else {
                            $builder->whereIn('public_id', $recentTransactionIds);
                        }
                    }
                })
                ->latest()
                ->take(50)
                ->get();
        }

        $this->syncTransactions($transactions);

        return $transactions
            ->map(fn (Transaction $transaction) => $transaction->fresh())
            ->filter()
            ->values();
    }

    public function syncGuestTransactionsToUser(User $user, Request $request): int
    {
        $guestToken = (string) $request->session()->get('guest_transaction_token', '');
        $normalizedWhatsapp = $this->normalizeWhatsapp($user->whatsapp_number);

        $query = Transaction::query()
            ->whereNull('user_id')
            ->where(function ($builder) use ($guestToken, $user, $normalizedWhatsapp) {
                $hasCondition = false;

                if ($guestToken !== '') {
                    $builder->where('guest_token', $guestToken);
                    $hasCondition = true;
                }

                if ($hasCondition) {
                    $builder->orWhere('customer_email', $user->email);
                } else {
                    $builder->where('customer_email', $user->email);
                    $hasCondition = true;
                }

                if ($normalizedWhatsapp) {
                    if ($hasCondition) {
                        $builder->orWhere('customer_whatsapp', $normalizedWhatsapp);
                    } else {
                        $builder->where('customer_whatsapp', $normalizedWhatsapp);
                    }
                }
            });

        return $query->update([
            'user_id' => $user->id,
        ]);
    }

    public function pruneExpiredGuestTransactions(): int
    {
        return Transaction::query()
            ->whereNull('user_id')
            ->where(function ($query) {
                $query
                    ->where('status', Transaction::STATUS_EXPIRED)
                    ->orWhere(function ($subQuery) {
                        $subQuery
                            ->where('payment_status', Transaction::PAYMENT_STATUS_UNPAID)
                            ->whereNotNull('expires_at')
                            ->where('expires_at', '<=', now());
                    });
            })
            ->delete();
    }

    public function handleDuitkuCallback(array $payload): ?Transaction
    {
        $publicId = (string) ($payload['merchantOrderId'] ?? '');

        if ($publicId === '') {
            return null;
        }

        return Cache::lock('duitku-callback:'.$publicId, 10)->block(3, function () use ($payload, $publicId) {
            $transaction = Transaction::query()->where('public_id', $publicId)->first();

            if (! $transaction) {
                return null;
            }

            if (! $this->isTrustedDuitkuCallbackPayload($transaction, $payload)) {
                $this->securityEvents->warning('duitku_callback_rejected_payload_mismatch', [
                    'public_id' => $publicId,
                    'merchant_code' => $payload['merchantCode'] ?? $payload['merchantcode'] ?? null,
                    'amount' => $payload['amount'] ?? null,
                ]);

                return null;
            }

            return $this->syncTransaction($transaction, force: true);
        });
    }

    public function toCheckoutPayload(Transaction $transaction): array
    {
        [$paymentReferenceLabel, $paymentReferenceValue] = $this->resolveCheckoutPaymentReference($transaction);

        return [
            'publicId' => $transaction->public_id,
            'productId' => $transaction->product_id,
            'productName' => $transaction->product_name,
            'productImage' => $transaction->product_image,
            'packageLabel' => $transaction->package_label,
            'quantity' => $transaction->quantity,
            'paymentLabel' => $transaction->payment_method_label,
            'paymentImage' => $transaction->payment_method_image,
            'paymentBadge' => $transaction->payment_badge,
            'paymentCaption' => $transaction->payment_caption,
            'paymentType' => $transaction->payment_method_type,
            'paymentDisplayType' => $transaction->payment_display_type,
            'paymentReferenceLabel' => $paymentReferenceLabel,
            'paymentReferenceValue' => $paymentReferenceValue,
            'paymentUrl' => $transaction->duitku_payment_url,
            'qrString' => $transaction->duitku_qr_string,
            'total' => (int) $transaction->total,
            'checkoutNotice' => $transaction->checkout_notice,
            'guaranteeText' => $transaction->guarantee_text,
            'notes' => $transaction->notes ?? [],
            'summaryRows' => $transaction->summary_rows ?? [],
            'transactionId' => '#'.$transaction->public_id,
            'status' => $transaction->status,
            'paymentStatus' => $transaction->payment_status,
            'statusLabel' => $this->statusLabel($transaction),
            'errorMessage' => $transaction->error_message,
            'paymentDeadlineLabel' => $this->formatJakartaDateTime($this->effectiveExpiresAt($transaction)),
            'createdAtLabel' => $this->formatJakartaDateTime($transaction->created_at),
            'expiresInMinutes' => 15,
            'expiresAtIso' => $this->effectiveExpiresAt($transaction)?->timezone('Asia/Jakarta')->toIso8601String(),
            'ratingScore' => $transaction->rating_score ? (int) $transaction->rating_score : null,
            'ratingComment' => $transaction->rating_comment,
            'ratedAtLabel' => $this->formatJakartaDateTime($transaction->rated_at),
        ];
    }

    public function toHistoryPayload(Transaction $transaction): array
    {
        return [
            'id' => (string) $transaction->id,
            'title' => $transaction->package_label,
            'game' => $transaction->product_name,
            'invoice' => '#'.$transaction->public_id,
            'createdAt' => $this->formatJakartaDateTime($transaction->created_at),
            'amount' => $this->formatRupiah((int) $transaction->total),
            'paymentMethod' => $transaction->payment_method_label,
            'status' => $transaction->status,
            'detailUrl' => route('checkout.show', ['transaction' => $transaction->public_id]),
        ];
    }

    /**
     * @param  iterable<int, Transaction>  $transactions
     */
    public function syncTransactions(iterable $transactions): void
    {
        foreach ($transactions as $transaction) {
            $this->syncTransaction($transaction);
        }
    }

    /**
     * @return array<int, string>
     */
    private function recentTransactionIds(Request $request): array
    {
        return collect($request->session()->get(self::RECENT_TRANSACTION_SESSION_KEY, []))
            ->map(fn (mixed $value) => trim((string) $value))
            ->filter()
            ->unique()
            ->take(50)
            ->values()
            ->all();
    }

    private function rememberRecentTransaction(Request $request, string $publicId): void
    {
        $normalizedId = trim($publicId);

        if ($normalizedId === '') {
            return;
        }

        $recentIds = collect($request->session()->get(self::RECENT_TRANSACTION_SESSION_KEY, []))
            ->prepend($normalizedId)
            ->map(fn (mixed $value) => trim((string) $value))
            ->filter()
            ->unique()
            ->take(50)
            ->values()
            ->all();

        $request->session()->put(self::RECENT_TRANSACTION_SESSION_KEY, $recentIds);
    }

    public function syncTransaction(Transaction $transaction, bool $force = false): ?Transaction
    {
        $transaction = $this->sanitizeStoredPaymentReference($transaction);
        $originalStatus = (string) $transaction->status;
        $originalPaymentStatus = (string) $transaction->payment_status;

        if (! $force && $transaction->last_synced_at && $transaction->last_synced_at->diffInSeconds(now()) < 8) {
            return $transaction;
        }

        $effectiveExpiresAt = $this->effectiveExpiresAt($transaction);

        if ($transaction->status === Transaction::STATUS_PENDING && $effectiveExpiresAt && now()->greaterThanOrEqualTo($effectiveExpiresAt)) {
            return $this->expireTransaction($transaction);
        }

        if (
            $transaction->status === Transaction::STATUS_PENDING
            && $transaction->payment_status === Transaction::PAYMENT_STATUS_UNPAID
            && $this->shouldSyncDuitkuStatus($transaction)
        ) {
            try {
                if ($this->duitku->configured()) {
                    $status = $this->duitku->getTransactionStatus($transaction->public_id);
                    $statusCode = (string) ($status['statusCode'] ?? '');

                    if ($statusCode === '00' && $this->matchesPaidAmount($transaction, (int) ($status['amount'] ?? 0))) {
                        $transaction = $this->markTransactionPaid($transaction);
                    } elseif ($statusCode === '00') {
                        $this->securityEvents->warning('duitku_paid_status_rejected_amount_mismatch', [
                            'public_id' => $transaction->public_id,
                            'expected_total' => (int) $transaction->total,
                            'reported_amount' => (int) ($status['amount'] ?? 0),
                        ]);
                    } elseif ($statusCode === '02') {
                        return $this->expireTransaction($transaction, markAsFailed: ! ($effectiveExpiresAt && now()->greaterThanOrEqualTo($effectiveExpiresAt)));
                    }
                }
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        if ($transaction->payment_status === Transaction::PAYMENT_STATUS_PAID && $transaction->status === Transaction::STATUS_PENDING) {
            $transaction = $transaction->product_source === Transaction::PRODUCT_SOURCE_MANUAL_STOCK
                ? $this->manualStock->preparePaidTransaction($transaction)
                : $this->dispatchVipaymentOrder($transaction);
        }

        if ($transaction->status === Transaction::STATUS_PROCESSING && $transaction->product_source === Transaction::PRODUCT_SOURCE_VIPAYMENT) {
            $transaction = $this->syncVipaymentStatus($transaction);
        }

        $transaction->forceFill([
            'last_synced_at' => now(),
        ])->save();

        $transaction = $transaction->fresh();

        if (! $transaction) {
            return null;
        }

        $transaction = $this->dispatchAdminManualOrderNotification($transaction, $originalStatus, $originalPaymentStatus);
        $transaction = $this->dispatchGoogleSheetsStatusSync($transaction, $originalStatus, $originalPaymentStatus);

        $transaction = $this->dispatchCustomerCompletedEmail($transaction, $originalStatus, $originalPaymentStatus);

        $transaction = $this->refundCoinsIfNeeded($transaction);

        return $this->dispatchLyvaflowStatusNotification($transaction, $originalStatus, $originalPaymentStatus);
    }

    public function markManualPaymentReceived(Transaction $transaction): Transaction
    {
        if ((string) $transaction->product_id !== (string) config('private_installment.product_id')) {
            throw new RuntimeException('Transaksi ini bukan pembayaran private SeaBank.');
        }

        if ($transaction->payment_status === Transaction::PAYMENT_STATUS_PAID) {
            return $transaction->fresh() ?? $transaction;
        }

        $transaction = $this->markTransactionPaid($transaction);

        return $this->syncTransaction($transaction, force: true) ?? $transaction;
    }

    public function completeManualStockFulfillment(Transaction $transaction, ?User $admin = null, bool $forceWithoutStock = false): Transaction
    {
        $originalStatus = (string) $transaction->status;
        $originalPaymentStatus = (string) $transaction->payment_status;
        $fulfillmentNote = $this->buildManualFulfillmentNote($transaction, $forceWithoutStock);
        $allowWithoutStock = $forceWithoutStock || $this->allowsManualConfirmationWithoutStock($transaction);

        $transaction = $this->manualStock->completeFulfillment(
            $transaction,
            $admin,
            $fulfillmentNote,
            $allowWithoutStock,
        );
        $transaction->forceFill([
            'last_synced_at' => now(),
        ])->save();

        $transaction = $transaction->fresh();

        if (! $transaction) {
            return $transaction;
        }

        $transaction = $this->dispatchGoogleSheetsSync($transaction, 'manual-completed');
        $transaction = $this->dispatchCustomerCompletedEmail($transaction, $originalStatus, $originalPaymentStatus);

        return $this->dispatchLyvaflowStatusNotification($transaction, $originalStatus, $originalPaymentStatus);
    }

    /**
     * @return array{canComplete: bool, actionLabel: string, replyChatUrl: string|null, previewMessage: string|null}
     */
    public function manualFulfillmentActionMeta(Transaction $transaction): array
    {
        $canConfirmWithoutStock = $this->allowsManualConfirmationWithoutStock($transaction);

        $canComplete = $transaction->product_source === Transaction::PRODUCT_SOURCE_MANUAL_STOCK
            && $transaction->payment_status === Transaction::PAYMENT_STATUS_PAID
            && in_array($transaction->status, [Transaction::STATUS_PENDING, Transaction::STATUS_PROCESSING], true)
            && ($transaction->manualStockItem !== null || $canConfirmWithoutStock);

        $actionLabel = $this->isInviteOnlyManualPackage($transaction)
            ? 'Invite sudah dikirim'
            : ($this->isPrivateAccountManualPackage($transaction) && $transaction->manualStockItem === null
                ? 'Konfirmasi private & selesai'
                : 'Kirim data & selesai');

        $fulfillmentNote = $this->buildManualFulfillmentNote($transaction);

        if (! $canComplete || ! filled($fulfillmentNote)) {
            return [
                'canComplete' => false,
                'actionLabel' => $this->isInviteOnlyManualPackage($transaction)
                    ? 'Invite belum siap'
                    : ($this->isPrivateAccountManualPackage($transaction) ? 'Konfirmasi private belum siap' : 'Tunggu stok siap'),
                'replyChatUrl' => null,
                'previewMessage' => null,
            ];
        }

        $previewMessage = $this->buildManualFulfillmentPreviewMessage($transaction, $fulfillmentNote);
        $normalizedWhatsapp = $this->lyvaflow->normalizeWhatsappNumber((string) ($transaction->customer_whatsapp ?? ''));
        $replyChatUrl = $normalizedWhatsapp !== ''
            ? 'https://wa.me/'.$normalizedWhatsapp.'?text='.rawurlencode($previewMessage)
            : null;

        return [
            'canComplete' => true,
            'actionLabel' => $actionLabel,
            'replyChatUrl' => $replyChatUrl,
            'previewMessage' => $previewMessage,
        ];
    }

    public function manualFulfillmentCategory(Transaction $transaction): ?string
    {
        if ($this->isInviteOnlyManualPackage($transaction)) {
            return 'invite';
        }

        if ($this->isPrivateAccountManualPackage($transaction)) {
            return 'private-account';
        }

        if ($transaction->product_source === Transaction::PRODUCT_SOURCE_MANUAL_STOCK) {
            return 'manual-stock';
        }

        return null;
    }

    public function manualTargetEmail(Transaction $transaction): ?string
    {
        return $this->extractManualAccountEmail($transaction);
    }

    private function markTransactionPaid(Transaction $transaction): Transaction
    {
        $transaction->forceFill([
            'payment_status' => Transaction::PAYMENT_STATUS_PAID,
            'paid_at' => $transaction->paid_at ?? now(),
            'error_message' => null,
        ])->save();

        return $transaction->fresh();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function isTrustedDuitkuCallbackPayload(Transaction $transaction, array $payload): bool
    {
        $merchantCode = trim((string) ($payload['merchantCode'] ?? $payload['merchantcode'] ?? ''));
        $configuredMerchantCode = trim((string) config('duitku.merchant_code'));
        $callbackAmount = (int) round((float) ($payload['amount'] ?? 0));

        if ($configuredMerchantCode === '' || ! hash_equals($configuredMerchantCode, $merchantCode)) {
            return false;
        }

        return $this->matchesPaidAmount($transaction, $callbackAmount);
    }

    private function matchesPaidAmount(Transaction $transaction, int $reportedAmount): bool
    {
        return $reportedAmount > 0 && $reportedAmount === (int) $transaction->total;
    }

    private function dispatchVipaymentOrder(Transaction $transaction): Transaction
    {
        if ($transaction->product_source !== Transaction::PRODUCT_SOURCE_VIPAYMENT || ! $transaction->package_code || ! $transaction->vipayment_endpoint) {
            $transaction->forceFill([
                'status' => Transaction::STATUS_COMPLETED,
                'vipayment_status' => null,
                'error_message' => null,
            ])->save();

            return $transaction->fresh();
        }

        if (($transaction->vipayment_trx_ids ?? []) !== []) {
            return $transaction->fresh();
        }

        return Cache::lock('vip-order:'.$transaction->public_id, 20)->block(5, function () use ($transaction) {
            $freshTransaction = Transaction::query()->find($transaction->id);

            if (! $freshTransaction) {
                return $transaction;
            }

            if (($freshTransaction->vipayment_trx_ids ?? []) !== []) {
                return $freshTransaction->fresh();
            }

            $trxIds = [];
            $statuses = [];
            $notes = [];

            try {
                for ($index = 0; $index < max(1, (int) $freshTransaction->quantity); $index += 1) {
                    $result = $this->vipayment->placeOrder(
                        (string) $freshTransaction->vipayment_endpoint,
                        (string) $freshTransaction->package_code,
                        $freshTransaction->account_fields ?? [],
                        (string) ($freshTransaction->customer_email ?: $freshTransaction->customer_whatsapp ?: ''),
                    );

                    if (($result['trxid'] ?? '') === '') {
                        throw new RuntimeException('VIPayment tidak mengembalikan ID transaksi.');
                    }

                    $trxIds[] = (string) $result['trxid'];
                    $statuses[] = (string) ($result['status'] ?? 'waiting');

                    if (filled($result['note'] ?? null)) {
                        $notes[] = (string) $result['note'];
                    }
                }
            } catch (\Throwable $exception) {
                report($exception);
                $this->securityEvents->warning('vip_order_dispatch_failed', [
                    'public_id' => $freshTransaction->public_id,
                    'product_id' => $freshTransaction->product_id,
                    'message' => $exception->getMessage(),
                ]);

                $freshTransaction->forceFill([
                    'status' => Transaction::STATUS_FAILED,
                    'vipayment_status' => 'error',
                    'error_message' => $exception->getMessage(),
                    'fulfillment_note' => null,
                ])->save();

                return $freshTransaction->fresh();
            }

            $aggregateStatus = $this->aggregateVipaymentStatuses($statuses);

            $freshTransaction->forceFill([
                'status' => $aggregateStatus === 'success' ? Transaction::STATUS_COMPLETED : ($aggregateStatus === 'error' ? Transaction::STATUS_FAILED : Transaction::STATUS_PROCESSING),
                'vipayment_status' => $aggregateStatus,
                'vipayment_trx_ids' => $trxIds,
                'error_message' => null,
                'fulfillment_note' => $this->joinFulfillmentNotes($notes),
            ])->save();

            return $freshTransaction->fresh();
        });
    }

    private function syncVipaymentStatus(Transaction $transaction): Transaction
    {
        if (! $transaction->vipayment_endpoint || ($transaction->vipayment_trx_ids ?? []) === []) {
            return $transaction;
        }

        $statuses = [];
        $errorNotes = [];
        $statusNotes = [];

        foreach ($transaction->vipayment_trx_ids as $trxId) {
            try {
                $status = $this->vipayment->getOrderStatus((string) $transaction->vipayment_endpoint, (string) $trxId);

                if (! $status) {
                    continue;
                }

                $statuses[] = (string) ($status['status'] ?? 'waiting');

                 if (filled($status['note'] ?? null)) {
                    $statusNotes[] = (string) $status['note'];
                }

                if (($status['status'] ?? '') === 'error' && filled($status['note'] ?? null)) {
                    $errorNotes[] = (string) $status['note'];
                }
            } catch (\Throwable $exception) {
                report($exception);
                $errorNotes[] = $exception->getMessage();
            }
        }

        if ($statuses === []) {
            return $transaction;
        }

        $aggregateStatus = $this->aggregateVipaymentStatuses($statuses);

        $transaction->forceFill([
            'status' => $aggregateStatus === 'success' ? Transaction::STATUS_COMPLETED : ($aggregateStatus === 'error' ? Transaction::STATUS_FAILED : Transaction::STATUS_PROCESSING),
            'vipayment_status' => $aggregateStatus,
            'error_message' => $errorNotes !== [] ? implode(' | ', $errorNotes) : null,
            'fulfillment_note' => $this->joinFulfillmentNotes($statusNotes) ?: $transaction->fulfillment_note,
        ])->save();

        return $transaction->fresh();
    }

    private function expireTransaction(Transaction $transaction, bool $markAsFailed = false): ?Transaction
    {
        $transaction->forceFill([
            'status' => $markAsFailed ? Transaction::STATUS_FAILED : Transaction::STATUS_EXPIRED,
            'payment_status' => $markAsFailed ? Transaction::PAYMENT_STATUS_FAILED : Transaction::PAYMENT_STATUS_EXPIRED,
            'error_message' => $markAsFailed ? 'Pembayaran gagal diverifikasi.' : 'Batas waktu pembayaran habis.',
        ])->save();

        $transaction = $this->refundCoinsIfNeeded($transaction);

        if ($transaction->user_id === null && $transaction->status === Transaction::STATUS_EXPIRED) {
            $transaction->delete();

            return null;
        }

        return $transaction->fresh();
    }

    private function refundCoinsIfNeeded(Transaction $transaction): Transaction
    {
        if (
            (int) ($transaction->coin_spent_amount ?? 0) <= 0
            || $transaction->coin_refunded_at
            || $transaction->payment_method_type !== 'lyva-coins'
            || ! in_array($transaction->status, [Transaction::STATUS_FAILED, Transaction::STATUS_EXPIRED], true)
        ) {
            return $transaction;
        }

        $transaction->forceFill([
            'coin_refunded_at' => now(),
        ])->save();

        return $transaction->fresh();
    }

    private function applyInvoiceData(Transaction $transaction, array $invoice): void
    {
        $displayType = 'code';
        $referenceLabel = 'Kode Pembayaran';
        $referenceValue = (string) ($invoice['reference'] ?? $transaction->public_id);
        $paymentType = Str::lower((string) $transaction->payment_method_type);
        $isQrisLikePayment = $this->isQrisPaymentDescriptor(
            (string) $transaction->payment_method_type,
            (string) $transaction->payment_method_label,
            (string) $transaction->payment_badge,
            (string) $transaction->payment_caption,
        );

        if (filled($invoice['qrString'] ?? null)) {
            $displayType = 'qris';
            $referenceLabel = 'QRIS';
            $referenceValue = (string) $invoice['reference'];
        } elseif ($isQrisLikePayment) {
            $displayType = 'qris';
            $referenceLabel = 'QRIS';
            $referenceValue = (string) ($invoice['reference'] ?? $transaction->public_id);
        } elseif (filled($invoice['vaNumber'] ?? null)) {
            $displayType = 'bank-transfer';
            $referenceLabel = 'Nomor Virtual Account';
            $referenceValue = (string) $invoice['vaNumber'];
        } elseif ($paymentType === 'bank-transfer') {
            $displayType = 'bank-transfer';
            $referenceLabel = 'Nomor Virtual Account';
            $referenceValue = '';
        }

        $transaction->forceFill([
            'duitku_reference' => $invoice['reference'] ?? null,
            'duitku_payment_url' => $invoice['paymentUrl'] ?? null,
            'duitku_app_url' => $invoice['paymentUrl'] ?? null,
            'duitku_qr_string' => $invoice['qrString'] ?? null,
            'payment_display_type' => $displayType,
            'payment_reference_label' => $referenceLabel,
            'payment_reference_value' => $referenceValue,
        ])->save();
    }

    private function applyFallbackPaymentData(Transaction $transaction): void
    {
        $paymentType = Str::lower((string) $transaction->payment_method_type);
        $displayType = 'code';
        $referenceLabel = 'Kode Pembayaran';
        $referenceValue = 'PAY-'.Str::upper(Str::random(10));

        if ($this->isQrisPaymentDescriptor(
            (string) $transaction->payment_method_type,
            (string) $transaction->payment_method_label,
            (string) $transaction->payment_badge,
            (string) $transaction->payment_caption,
        )) {
            $displayType = 'qris';
            $referenceLabel = 'QRIS';
            $referenceValue = $transaction->public_id;
        } elseif ($paymentType === 'bank-transfer') {
            $displayType = 'bank-transfer';
            $referenceLabel = 'Nomor Virtual Account';
            $referenceValue = '';
        }

        $transaction->forceFill([
            'payment_display_type' => $displayType,
            'payment_reference_label' => $referenceLabel,
            'payment_reference_value' => $referenceValue,
        ])->save();
    }

    /**
     * @return array{0: string|null, 1: string|null}
     */
    private function resolveCheckoutPaymentReference(Transaction $transaction): array
    {
        $referenceLabel = $transaction->payment_reference_label;
        $referenceValue = $transaction->payment_reference_value;

        if ($this->isBankTransferTransaction($transaction) && ! $this->isNumericVirtualAccount($referenceValue)) {
            return ['Nomor Virtual Account', ''];
        }

        return [$referenceLabel, $referenceValue];
    }

    private function sanitizeStoredPaymentReference(Transaction $transaction): Transaction
    {
        if (! $this->isBankTransferTransaction($transaction)) {
            return $transaction;
        }

        $referenceLabel = $transaction->payment_reference_label ?: 'Nomor Virtual Account';
        $referenceValue = $this->isNumericVirtualAccount($transaction->payment_reference_value)
            ? preg_replace('/\s+/', '', (string) $transaction->payment_reference_value)
            : '';

        if (
            $referenceLabel === $transaction->payment_reference_label
            && $referenceValue === $transaction->payment_reference_value
        ) {
            return $transaction;
        }

        $transaction->forceFill([
            'payment_reference_label' => $referenceLabel,
            'payment_reference_value' => $referenceValue,
        ])->save();

        return $transaction->fresh();
    }

    private function shouldSyncDuitkuStatus(Transaction $transaction): bool
    {
        return filled($transaction->duitku_reference)
            || filled($transaction->duitku_payment_url)
            || filled($transaction->duitku_app_url)
            || filled($transaction->duitku_qr_string);
    }

    private function isBankTransferTransaction(Transaction $transaction): bool
    {
        return Str::lower((string) ($transaction->payment_display_type ?: $transaction->payment_method_type)) === 'bank-transfer';
    }

    private function isQrisTransaction(Transaction $transaction): bool
    {
        if (Str::lower((string) $transaction->payment_display_type) === 'qris') {
            return true;
        }

        if (filled($transaction->duitku_qr_string)) {
            return true;
        }

        return $this->isQrisPaymentDescriptor(
            (string) $transaction->payment_method_type,
            (string) $transaction->payment_method_label,
            (string) $transaction->payment_badge,
            (string) $transaction->payment_caption,
        );
    }

    private function shouldHydrateCheckoutPaymentReference(Transaction $transaction): bool
    {
        return $this->isBankTransferTransaction($transaction)
            && ! $this->isNumericVirtualAccount($transaction->payment_reference_value)
            && filled($transaction->duitku_payment_url)
            && filled($transaction->payment_method_code)
            && $transaction->status === Transaction::STATUS_PENDING
            && $transaction->payment_status === Transaction::PAYMENT_STATUS_UNPAID;
    }

    private function shouldHydrateCheckoutQrisData(Transaction $transaction): bool
    {
        return $this->isQrisTransaction($transaction)
            && ! filled($transaction->duitku_qr_string)
            && filled($transaction->duitku_payment_url)
            && filled($transaction->payment_method_code)
            && $transaction->status === Transaction::STATUS_PENDING
            && $transaction->payment_status === Transaction::PAYMENT_STATUS_UNPAID;
    }

    private function isNumericVirtualAccount(?string $value): bool
    {
        if (! filled($value)) {
            return false;
        }

        return preg_match('/^\d+$/', preg_replace('/\s+/', '', (string) $value)) === 1;
    }

    private function isQrisPaymentDescriptor(string ...$parts): bool
    {
        $value = Str::lower(trim(implode(' ', $parts)));

        if ($value === '') {
            return false;
        }

        return Str::contains($value, ['qris', 'qr', 'scan', 'snap qr', 'qr code']);
    }

    /**
     * @param  array<int, array{id?: string, label?: string, value?: string|null}>  $fields
     * @return array<int, array{id: string, label: string, value: string}>
     */
    private function normalizeFields(array $fields): array
    {
        return collect($fields)
            ->map(function (array $field) {
                return [
                    'id' => trim((string) ($field['id'] ?? '')),
                    'label' => trim((string) ($field['label'] ?? '')),
                    'value' => trim((string) ($field['value'] ?? '')),
                ];
            })
            ->filter(fn (array $field) => $field['id'] !== '' && $field['value'] !== '')
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array{id: string, value: string}>  $fields
     * @param  array<int, string>  $ids
     */
    private function extractFieldValue(array $fields, array $ids): ?string
    {
        $field = collect($fields)->first(fn (array $field) => in_array($field['id'], $ids, true));

        return is_array($field) && filled($field['value']) ? (string) $field['value'] : null;
    }

    private function normalizeWhatsapp(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        $normalized = preg_replace('/[\s-]+/', '', trim($value));

        return $normalized !== '' ? $normalized : null;
    }

    /**
     * @param  array<int, string>  $statuses
     */
    private function aggregateVipaymentStatuses(array $statuses): string
    {
        $normalizedStatuses = collect($statuses)
            ->map(fn (string $status) => Str::lower($status))
            ->values();

        if ($normalizedStatuses->contains('error')) {
            return 'error';
        }

        if ($normalizedStatuses->every(fn (string $status) => $status === 'success')) {
            return 'success';
        }

        return 'processing';
    }

    private function statusLabel(Transaction $transaction): string
    {
        return match ($transaction->status) {
            Transaction::STATUS_PENDING => 'Belum bayar',
            Transaction::STATUS_PROCESSING => 'Diproses',
            Transaction::STATUS_COMPLETED => 'Berhasil',
            Transaction::STATUS_FAILED => 'Error',
            Transaction::STATUS_EXPIRED => 'Expired',
            default => 'Belum bayar',
        };
    }

    private function formatJakartaDateTime(?Carbon $value): ?string
    {
        return $value?->copy()->timezone('Asia/Jakarta')->format('d M Y - H:i');
    }

    private function formatRupiah(int $amount): string
    {
        return 'Rp'.number_format($amount, 0, ',', '.');
    }

    private function effectiveExpiresAt(Transaction $transaction): ?Carbon
    {
        if (! $transaction->expires_at) {
            return null;
        }

        if (! $transaction->created_at) {
            return $transaction->expires_at->copy();
        }

        $minutesDiff = abs($transaction->created_at->diffInMinutes($transaction->expires_at, false));

        if ($minutesDiff > 30) {
            return $transaction->created_at->copy()->addMinutes(15);
        }

        return $transaction->expires_at->copy();
    }

    private function dispatchAdminManualOrderNotification(Transaction $transaction, string $originalStatus, string $originalPaymentStatus): Transaction
    {
        if (
            $transaction->product_source !== Transaction::PRODUCT_SOURCE_MANUAL_STOCK
            || $transaction->payment_status !== Transaction::PAYMENT_STATUS_PAID
            || $transaction->status !== Transaction::STATUS_PROCESSING
            || $transaction->admin_manual_order_notified_at
        ) {
            return $transaction;
        }

        if (
            $originalStatus === Transaction::STATUS_PROCESSING
            && $originalPaymentStatus === Transaction::PAYMENT_STATUS_PAID
        ) {
            return $transaction;
        }

        try {
            return $this->adminOrderNotifications->notifyManualOrder($transaction, $transaction->manualStockItem);
        } catch (\Throwable $exception) {
            report($exception);

            return $transaction;
        }
    }

    private function dispatchLyvaflowStatusNotification(Transaction $transaction, string $originalStatus, string $originalPaymentStatus): Transaction
    {
        if (! $this->lyvaflow->configured() || ! filled($transaction->customer_whatsapp)) {
            return $transaction;
        }

        try {
            if (
                $this->shouldSendLyvaflowProcessingNotification($transaction)
            ) {
                $this->lyvaflow->sendWhatsappMessage(
                    (string) $transaction->customer_whatsapp,
                    $this->buildLyvaflowProcessingMessage($transaction),
                );

                $transaction->forceFill([
                    'lyvaflow_processing_notified_at' => now(),
                ])->save();

                return $transaction->fresh();
            }

            if (
                $this->shouldSendLyvaflowCompletedNotification($transaction)
            ) {
                $this->lyvaflow->sendWhatsappMessage(
                    (string) $transaction->customer_whatsapp,
                    $this->buildLyvaflowCompletedMessage($transaction),
                );

                $transaction->forceFill([
                    'lyvaflow_completed_notified_at' => now(),
                ])->save();

                return $transaction->fresh();
            }

            if (
                $this->shouldSendLyvaflowFailedNotification($transaction)
            ) {
                $this->lyvaflow->sendWhatsappMessage(
                    (string) $transaction->customer_whatsapp,
                    $this->buildLyvaflowFailedMessage($transaction),
                );

                $transaction->forceFill([
                    'lyvaflow_failed_notified_at' => now(),
                ])->save();

                return $transaction->fresh();
            }
        } catch (\Throwable $exception) {
            Log::warning('lyvaflow_status_notification_failed', [
                'public_id' => $transaction->public_id,
                'status' => $transaction->status,
                'payment_status' => $transaction->payment_status,
                'customer_whatsapp' => $transaction->customer_whatsapp,
                'error' => $exception->getMessage(),
            ]);
            report($exception);
        }

        return $transaction;
    }

    private function shouldSendLyvaflowProcessingNotification(Transaction $transaction): bool
    {
        return $transaction->status === Transaction::STATUS_PROCESSING
            && $transaction->payment_status === Transaction::PAYMENT_STATUS_PAID
            && ! $transaction->lyvaflow_processing_notified_at
            && $this->shouldSendProcessingWhatsappNotification($transaction);
    }

    private function shouldSendLyvaflowCompletedNotification(Transaction $transaction): bool
    {
        return $transaction->status === Transaction::STATUS_COMPLETED
            && $transaction->payment_status === Transaction::PAYMENT_STATUS_PAID
            && ! $transaction->lyvaflow_completed_notified_at;
    }

    private function shouldSendLyvaflowFailedNotification(Transaction $transaction): bool
    {
        return $transaction->status === Transaction::STATUS_FAILED
            && $transaction->payment_status === Transaction::PAYMENT_STATUS_PAID
            && ! $transaction->lyvaflow_failed_notified_at;
    }

    private function shouldSendProcessingWhatsappNotification(Transaction $transaction): bool
    {
        return $transaction->paid_at !== null;
    }

    private function dispatchCustomerCompletedEmail(Transaction $transaction, string $originalStatus, string $originalPaymentStatus): Transaction
    {
        if (
            $transaction->status !== Transaction::STATUS_COMPLETED
            || $transaction->payment_status !== Transaction::PAYMENT_STATUS_PAID
            || ! filled($transaction->customer_email)
            || $transaction->customer_completed_emailed_at
        ) {
            return $transaction;
        }

        if (
            $originalStatus === Transaction::STATUS_COMPLETED
            && $originalPaymentStatus === Transaction::PAYMENT_STATUS_PAID
        ) {
            return $transaction;
        }

        try {
            return $this->customerOrderNotifications->sendCompletedOrder($transaction);
        } catch (\Throwable $exception) {
            report($exception);

            return $transaction;
        }
    }

    private function dispatchGoogleSheetsStatusSync(Transaction $transaction, string $originalStatus, string $originalPaymentStatus): Transaction
    {
        if (
            $transaction->status === $originalStatus
            && $transaction->payment_status === $originalPaymentStatus
        ) {
            return $transaction;
        }

        return $this->dispatchGoogleSheetsSync($transaction, 'status-changed');
    }

    private function dispatchGoogleSheetsSync(Transaction $transaction, string $event): Transaction
    {
        if (! $this->googleSheets->configured()) {
            return $transaction;
        }

        try {
            $this->googleSheets->syncTransaction($transaction, $event);
        } catch (\Throwable $exception) {
            report($exception);
        }

        return $transaction;
    }

    private function buildLyvaflowProcessingMessage(Transaction $transaction): string
    {
        return $this->lyvaflow->composeStructuredMessage(
            'Pembayaran Diterima',
            [
                'Halo '.$this->resolveNotificationRecipient($transaction).',',
                'Pembayaran untuk transaksi #'.$transaction->public_id.' sudah kami terima.',
                'Pesanan kamu sedang diproses oleh provider. Tidak perlu melakukan pembayaran ulang.',
            ],
            [
                [
                    'title' => 'Ringkasan Pesanan',
                    'lines' => $this->buildLyvaflowOrderSummaryLines($transaction),
                ],
                [
                    'title' => 'Data Akun',
                    'lines' => $this->buildLyvaflowAccountSummaryLines($transaction),
                ],
                [
                    'title' => 'Status Proses',
                    'lines' => [
                        'Status saat ini: pesanan sedang diproses.',
                        ...$this->buildLyvaflowProcessDetailLines($transaction, includeFulfillmentNote: true),
                    ],
                ],
                [
                    'title' => 'Akses Cepat',
                    'style' => 'plain',
                    'lines' => [
                        'Pantau status: '.route('checkout.show', ['transaction' => $transaction->public_id]),
                        'ID transaksi: #'.$transaction->public_id,
                    ],
                ],
            ],
            [
                'Simpan ID transaksi ini agar proses pengecekan nanti lebih cepat.',
            ],
        );
    }

    private function buildLyvaflowCompletedMessage(Transaction $transaction): string
    {
        $manualDeliveryLines = $this->manualFulfillmentDeliveryLines($transaction);

        return $this->lyvaflow->composeStructuredMessage(
            'Pesanan Selesai',
            [
                'Halo '.$this->resolveNotificationRecipient($transaction).',',
                'Transaksi #'.$transaction->public_id.' sudah selesai.',
                filled($transaction->fulfillment_note)
                    ? 'Detail hasil pesanan kamu sudah kami lampirkan di bagian hasil proses.'
                    : 'Pesanan kamu sudah berhasil diproses dan siap dipakai.',
            ],
            [
                [
                    'title' => 'Ringkasan Pesanan',
                    'lines' => $this->buildLyvaflowOrderSummaryLines($transaction),
                ],
                [
                    'title' => 'Data Akun',
                    'lines' => $this->buildLyvaflowAccountSummaryLines($transaction),
                ],
                ...($manualDeliveryLines !== [] ? [[
                    'title' => $this->isInviteOnlyManualPackage($transaction) ? 'Status Invite' : 'Data Akses',
                    'style' => 'plain',
                    'lines' => $manualDeliveryLines,
                ]] : []),
                [
                    'title' => 'Hasil Proses',
                    'lines' => [
                        'Status saat ini: pesanan sudah selesai.',
                        ...$this->buildLyvaflowProcessDetailLines(
                            $transaction,
                            includeFulfillmentNote: $manualDeliveryLines === [],
                        ),
                    ],
                ],
                [
                    'title' => 'Akses Cepat',
                    'style' => 'plain',
                    'lines' => [
                        'Detail transaksi: '.route('checkout.show', ['transaction' => $transaction->public_id]),
                    ],
                ],
            ],
            [
                'Terima kasih sudah bertransaksi di Lyva Indonesia.',
            ],
        );
    }

    /**
     * @return array<int, string>
     */
    private function manualFulfillmentDeliveryLines(Transaction $transaction): array
    {
        if ($transaction->product_source !== Transaction::PRODUCT_SOURCE_MANUAL_STOCK || ! filled($transaction->fulfillment_note)) {
            return [];
        }

        return collect(preg_split('/\r\n|\r|\n/', (string) $transaction->fulfillment_note) ?: [])
            ->map(fn (string $line) => trim($line))
            ->filter()
            ->values()
            ->all();
    }

    private function buildLyvaflowFailedMessage(Transaction $transaction): string
    {
        $errorMessage = trim((string) $transaction->error_message);

        return $this->lyvaflow->composeStructuredMessage(
            'Pesanan Butuh Pengecekan',
            [
                'Halo '.$this->resolveNotificationRecipient($transaction).',',
                'Pembayaran untuk transaksi #'.$transaction->public_id.' sudah kami terima, tetapi pesanan mengalami kendala saat diproses provider.',
                'Jangan lakukan pembayaran ulang dulu sebelum cek status terbaru transaksi ini.',
            ],
            [
                [
                    'title' => 'Ringkasan Pesanan',
                    'lines' => $this->buildLyvaflowOrderSummaryLines($transaction),
                ],
                [
                    'title' => 'Data Akun',
                    'lines' => $this->buildLyvaflowAccountSummaryLines($transaction),
                ],
                [
                    'title' => 'Detail Kendala',
                    'lines' => array_filter([
                        ...$this->buildLyvaflowProcessDetailLines($transaction, includeFulfillmentNote: false),
                        $errorMessage !== '' ? 'Kendala: '.Str::limit($errorMessage, 180) : null,
                    ]),
                ],
                [
                    'title' => 'Akses Cepat',
                    'style' => 'plain',
                    'lines' => [
                        'Cek status terbaru: '.route('checkout.show', ['transaction' => $transaction->public_id]),
                        'ID transaksi: #'.$transaction->public_id,
                    ],
                ],
            ],
            [
                'Simpan ID transaksi ini untuk proses pengecekan lanjutan.',
            ],
        );
    }

    /**
     * @return array<int, string>
     */
    private function buildLyvaflowOrderSummaryLines(Transaction $transaction): array
    {
        $lines = [
            'ID transaksi: #'.$transaction->public_id,
            'Produk: '.$transaction->product_name,
            'Paket: '.$transaction->package_label,
            'Total: '.$this->formatRupiah((int) $transaction->total),
        ];

        if (filled($transaction->payment_method_label)) {
            $lines[] = 'Metode bayar: '.$transaction->payment_method_label;
        }

        return $lines;
    }

    /**
     * @return array<int, string>
     */
    private function buildLyvaflowAccountSummaryLines(Transaction $transaction): array
    {
        $summaryRows = collect($transaction->summary_rows ?? [])
            ->filter(fn (mixed $row) => is_array($row))
            ->map(function (array $row) {
                $label = trim((string) ($row['label'] ?? ''));
                $value = trim((string) ($row['value'] ?? ''));

                if ($label === '' || $value === '' || $value === 'Belum diisi') {
                    return null;
                }

                if (Str::contains(Str::lower($label), ['password', 'kata sandi'])) {
                    return null;
                }

                return Str::limit($label, 30).': '.Str::limit($value, 72);
            })
            ->filter()
            ->take(3)
            ->values();

        return $summaryRows->map(fn (mixed $row) => (string) $row)->all();
    }

    /**
     * @return array<int, string>
     */
    private function buildLyvaflowProcessDetailLines(Transaction $transaction, bool $includeFulfillmentNote = true): array
    {
        $lines = [];
        $trxIds = collect($transaction->vipayment_trx_ids ?? [])
            ->filter(fn (mixed $trxId) => filled($trxId))
            ->values();

        if ($trxIds->isNotEmpty()) {
            $visibleTrxIds = $trxIds->take(3)->implode(', ');
            $remaining = $trxIds->count() - min(3, $trxIds->count());
            $lines[] = 'Ref proses: '.$visibleTrxIds.($remaining > 0 ? ' +'.$remaining.' lainnya' : '');
        }

        if ($includeFulfillmentNote && filled($transaction->fulfillment_note)) {
            $lines[] = 'Catatan: '.Str::limit((string) $transaction->fulfillment_note, 180);
        }

        return $lines;
    }

    /**
     * @param  array<int, string>  $notes
     */
    private function joinFulfillmentNotes(array $notes): ?string
    {
        $joined = collect($notes)
            ->map(fn (string $note) => trim($note))
            ->filter()
            ->unique()
            ->implode(' | ');

        return $joined !== '' ? $joined : null;
    }

    private function buildManualFulfillmentNote(Transaction $transaction, bool $forceWithoutStock = false): ?string
    {
        $stockItem = $transaction->manualStockItem;

        if ($stockItem) {
            $rawValue = trim((string) $stockItem->stock_value);

            if ($rawValue === '') {
                return null;
            }

            $lines = [];
            $segments = collect(explode('|', $rawValue))
                ->map(fn (string $segment) => trim($segment))
                ->filter()
                ->values();

            if ($segments->count() >= 2) {
                $lines[] = 'Email / username: '.$segments->get(0);
                $lines[] = 'Password: '.$segments->get(1);

                if ($segments->count() > 2) {
                    $segments->slice(2)->each(function (string $segment, int $index) use (&$lines) {
                        $lines[] = 'Data tambahan '.($index + 1).': '.$segment;
                    });
                }
            } else {
                $lines[] = ($stockItem->stock_label ?: 'Data akun').': '.$rawValue;
            }

            if (filled($stockItem->notes)) {
                $lines[] = 'Catatan: '.trim((string) $stockItem->notes);
            }

            return implode("\n", $lines);
        }

        if ($this->isInviteOnlyManualPackage($transaction)) {
            $targetEmail = $this->extractManualAccountEmail($transaction);
            $lines = [
                'Invite untuk paket '.$transaction->package_label.' sudah dikirim oleh admin Lyva.',
            ];

            if ($targetEmail !== null) {
                $lines[] = 'Email tujuan invite: '.$targetEmail;
            }

            $lines[] = 'Silakan cek inbox, promotion, dan folder spam lalu terima undangan yang masuk.';

            return implode("\n", $lines);
        }

        if ($this->isPrivateAccountManualPackage($transaction)) {
            $targetEmail = $this->extractManualAccountEmail($transaction);
            $lines = [
                'Private account untuk paket '.$transaction->package_label.' sudah dikirim atau dikonfirmasi oleh admin Lyva.',
            ];

            if ($targetEmail !== null) {
                $lines[] = 'Akun / email tujuan: '.$targetEmail;
            }

            $lines[] = 'Silakan cek chat WhatsApp atau email yang kamu gunakan saat order untuk detail akses akun.';

            return implode("\n", $lines);
        }

        if (! $forceWithoutStock) {
            return null;
        }

        $targetEmail = $this->extractManualAccountEmail($transaction);
        $lines = [
            'Pesanan manual untuk paket '.$transaction->package_label.' sudah dikonfirmasi oleh admin Lyva.',
        ];

        if ($targetEmail !== null) {
            $lines[] = 'Email tujuan / akun: '.$targetEmail;
        }

        $lines[] = 'Jika data akun dikirim terpisah, silakan cek chat WhatsApp atau email yang kamu gunakan saat order.';

        return implode("\n", $lines);
    }

    private function allowsManualConfirmationWithoutStock(Transaction $transaction): bool
    {
        return $this->isInviteOnlyManualPackage($transaction) || $this->isPrivateAccountManualPackage($transaction);
    }

    private function isInviteOnlyManualPackage(Transaction $transaction): bool
    {
        $value = $this->manualPackageSearchText($transaction);

        return $value !== '' && Str::contains($value, ['invite', 'invitation', 'undangan']);
    }

    private function isPrivateAccountManualPackage(Transaction $transaction): bool
    {
        $value = $this->manualPackageSearchText($transaction);

        return $value !== ''
            && Str::contains($value, ['private account', 'private akun', 'private acc'])
            && ! Str::contains($value, ['sharing']);
    }

    private function manualPackageSearchText(Transaction $transaction): string
    {
        return Str::lower(trim(implode(' ', array_filter([
            (string) ($transaction->product_id ?? ''),
            (string) ($transaction->product_name ?? ''),
            (string) ($transaction->package_label ?? ''),
            (string) ($transaction->package_code ?? ''),
        ]))));
    }

    private function extractManualAccountEmail(Transaction $transaction): ?string
    {
        $emailField = collect($transaction->account_fields ?? [])
            ->first(function (array $field) {
                $id = Str::lower(trim((string) ($field['id'] ?? '')));
                $label = Str::lower(trim((string) ($field['label'] ?? '')));

                return in_array($id, ['account-email', 'account-username'], true)
                    || Str::contains($label, ['email', 'username']);
            });

        if (is_array($emailField) && filled($emailField['value'] ?? null)) {
            return trim((string) $emailField['value']);
        }

        $summaryRow = collect($transaction->summary_rows ?? [])
            ->first(function (array $row) {
                $label = Str::lower(trim((string) ($row['label'] ?? '')));

                return Str::contains($label, ['email', 'username']);
            });

        return is_array($summaryRow) && filled($summaryRow['value'] ?? null)
            ? trim((string) $summaryRow['value'])
            : null;
    }

    private function buildManualFulfillmentPreviewMessage(Transaction $transaction, string $fulfillmentNote): string
    {
        $previewTransaction = $transaction->replicate();
        $previewTransaction->forceFill([
            'status' => Transaction::STATUS_COMPLETED,
            'payment_status' => Transaction::PAYMENT_STATUS_PAID,
            'fulfillment_note' => $fulfillmentNote,
        ]);

        return $this->buildLyvaflowCompletedMessage($previewTransaction);
    }

    private function resolveNotificationRecipient(Transaction $transaction): string
    {
        $customerName = trim((string) ($transaction->customer_name ?? ''));

        if ($customerName === '') {
            return 'Kak';
        }

        return Str::headline(Str::before($customerName, ' '));
    }
}
