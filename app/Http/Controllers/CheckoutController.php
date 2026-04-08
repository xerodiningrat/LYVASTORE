<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Services\LyvaCoinService;
use App\Services\DuitkuService;
use App\Services\PromoCodeService;
use App\Services\SecurityEventService;
use App\Services\TransactionService;
use App\Services\VipaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CheckoutController extends Controller
{
    private const MINIMUM_BANK_TRANSFER_AMOUNT = 10000;

    public function store(
        Request $request,
        TransactionService $transactions,
        PromoCodeService $promos,
        VipaymentService $vipayment,
        DuitkuService $duitku,
        LyvaCoinService $coins,
        SecurityEventService $security,
    ): RedirectResponse
    {
        $validated = $request->validate([
            'productId' => ['required', 'string', 'max:120'],
            'productName' => ['required', 'string', 'max:255'],
            'productImage' => ['nullable', 'string', 'max:2048'],
            'packageCode' => ['nullable', 'string', 'max:160'],
            'packageLabel' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'integer', 'min:1'],
            'paymentMethodCode' => ['nullable', 'string', 'max:80'],
            'paymentLabel' => ['required', 'string', 'max:255'],
            'paymentImage' => ['nullable', 'string', 'max:2048'],
            'paymentBadge' => ['nullable', 'string', 'max:24'],
            'paymentCaption' => ['nullable', 'string', 'max:255'],
            'paymentType' => ['required', 'string', 'max:40'],
            'total' => ['required', 'numeric', 'min:0'],
            'checkoutIntentToken' => ['required', 'string', 'size:48'],
            'website' => ['nullable', 'string', 'max:255'],
            'formStartedAt' => ['required', 'integer', 'min:1'],
            'checkoutNotice' => ['nullable', 'string', 'max:2000'],
            'guaranteeText' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'array'],
            'notes.*' => ['string', 'max:1000'],
            'summaryRows' => ['nullable', 'array'],
            'summaryRows.*.label' => ['required_with:summaryRows', 'string', 'max:120'],
            'summaryRows.*.value' => ['nullable', 'string', 'max:2000'],
            'accountFields' => ['nullable', 'array'],
            'accountFields.*.id' => ['required_with:accountFields', 'string', 'max:120'],
            'accountFields.*.label' => ['nullable', 'string', 'max:120'],
            'accountFields.*.value' => ['nullable', 'string', 'max:2000'],
            'contactFields' => ['nullable', 'array'],
            'contactFields.*.id' => ['required_with:contactFields', 'string', 'max:120'],
            'contactFields.*.label' => ['nullable', 'string', 'max:120'],
            'contactFields.*.value' => ['nullable', 'string', 'max:2000'],
            'promoCode' => ['nullable', 'string', 'max:32'],
        ]);

        $this->guardCheckoutIntentToken($request, (string) ($validated['checkoutIntentToken'] ?? ''), $security);

        $originalTotal = (int) round((float) ($validated['total'] ?? 0));
        $this->guardVipaymentPackageIntegrity($request, $validated, $vipayment, $originalTotal, $security);

        $promoPreview = $promos->preview(
            $validated['promoCode'] ?? null,
            (string) ($validated['productId'] ?? ''),
            $originalTotal,
        );

        if (filled($validated['promoCode'] ?? null) && ! ($promoPreview['applied'] ?? false)) {
            throw ValidationException::withMessages([
                'promoCode' => (string) ($promoPreview['message'] ?? 'Kode promo tidak bisa dipakai.'),
            ]);
        }

        $validated['subtotal'] = $originalTotal;
        $validated['promoCode'] = $promoPreview['applied'] ? (string) ($promoPreview['code'] ?? '') : null;
        $validated['promoLabel'] = $promoPreview['applied'] ? (string) ($promoPreview['label'] ?? '') : null;
        $validated['promoDiscount'] = $promoPreview['applied'] ? (int) ($promoPreview['discount'] ?? 0) : 0;
        $validated['promoSnapshot'] = $promoPreview['applied'] ? ($promoPreview['snapshot'] ?? null) : null;
        $validated['total'] = $promoPreview['applied'] ? (int) ($promoPreview['finalTotal'] ?? $originalTotal) : $originalTotal;
        $validated['summaryRows'] = $this->decorateSummaryRows(
            is_array($validated['summaryRows'] ?? null) ? $validated['summaryRows'] : [],
            $originalTotal,
            $validated['promoCode'],
            (int) ($validated['promoDiscount'] ?? 0),
            (int) ($validated['total'] ?? $originalTotal),
        );
        $this->guardCoinPaymentBalance($request, $validated, $coins);
        $validated = $this->normalizeOfficialPaymentMethod($request, $validated, $duitku, $security);

        if (
            strtolower((string) ($validated['paymentType'] ?? '')) === 'bank-transfer'
            && (int) round((float) ($validated['total'] ?? 0)) < self::MINIMUM_BANK_TRANSFER_AMOUNT
        ) {
            throw ValidationException::withMessages([
                'paymentType' => 'Metode bank transfer / virtual account tersedia mulai Rp10.000.',
            ]);
        }

        $transaction = $transactions->createCheckoutTransaction($validated, $request);

        return to_route('checkout.show', ['transaction' => $transaction->public_id]);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function guardCoinPaymentBalance(Request $request, array $validated, LyvaCoinService $coins): void
    {
        if (strtolower((string) ($validated['paymentType'] ?? '')) !== 'lyva-coins') {
            return;
        }

        if (! $request->user()) {
            throw ValidationException::withMessages([
                'paymentType' => 'Login dulu untuk memakai Lyva Coins.',
            ]);
        }

        $requiredCoins = $coins->requiredCoinsForAmount((int) round((float) ($validated['total'] ?? 0)));

        if (! $coins->canUserPayAmount($request->user(), (int) round((float) ($validated['total'] ?? 0)))) {
            throw ValidationException::withMessages([
                'paymentType' => 'Saldo Lyva Coins kamu belum cukup untuk membayar transaksi ini.',
            ]);
        }

        $validated['coinPaymentAmount'] = $requiredCoins;
    }

    private function guardCheckoutIntentToken(Request $request, string $submittedToken, SecurityEventService $security): void
    {
        $sessionToken = (string) $request->session()->get('checkout_intent_token', '');

        if ($sessionToken === '' || ! hash_equals($sessionToken, $submittedToken)) {
            $security->warning('checkout_intent_token_invalid', $security->requestContext($request));
            throw ValidationException::withMessages([
                'checkout' => 'Sesi checkout tidak valid. Muat ulang halaman lalu coba lagi.',
            ]);
        }

        $request->session()->put('checkout_intent_token', Str::random(48));
    }

    public function resolvePromo(Request $request, PromoCodeService $promos): JsonResponse
    {
        $validated = $request->validate([
            'productId' => ['required', 'string', 'max:120'],
            'total' => ['required', 'integer', 'min:0'],
            'promoCode' => ['nullable', 'string', 'max:32'],
        ]);

        return response()->json(
            $promos->preview(
                $validated['promoCode'] ?? null,
                (string) $validated['productId'],
                (int) $validated['total'],
            )
        );
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function guardVipaymentPackageIntegrity(
        Request $request,
        array $validated,
        VipaymentService $vipayment,
        int $originalTotal,
        SecurityEventService $security,
    ): void
    {
        $service = $vipayment->findServiceOption(
            (string) ($validated['productId'] ?? ''),
            $validated['packageCode'] ?? null,
            $validated['packageLabel'] ?? null,
        );

        if (! $service) {
            return;
        }

        $quantity = max(1, (int) ($validated['quantity'] ?? 1));
        $expectedSubtotal = (int) $service['price'] * $quantity;

        if ($expectedSubtotal !== $originalTotal) {
            $security->warning('checkout_price_mismatch', $security->requestContext($request, [
                'product_id' => (string) ($validated['productId'] ?? ''),
                'package_code' => (string) ($validated['packageCode'] ?? ''),
                'package_label' => (string) ($validated['packageLabel'] ?? ''),
                'expected_subtotal' => $expectedSubtotal,
                'submitted_total' => $originalTotal,
            ]));
            throw ValidationException::withMessages([
                'total' => 'Harga checkout berubah atau tidak valid. Silakan pilih ulang paket sebelum membayar.',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function normalizeOfficialPaymentMethod(
        Request $request,
        array $validated,
        DuitkuService $duitku,
        SecurityEventService $security,
    ): array
    {
        if (! $duitku->configured() || ! filled($validated['paymentMethodCode'] ?? null)) {
            return $validated;
        }

        if (strtolower((string) ($validated['paymentType'] ?? '')) === 'lyva-coins') {
            return $validated;
        }

        $methods = $duitku->getPaymentMethods((int) round((float) ($validated['total'] ?? 0)));
        $requestedCode = Str::lower(trim((string) ($validated['paymentMethodCode'] ?? '')));
        $officialMethod = collect($methods)->first(
            fn (array $method) => Str::lower(trim((string) ($method['id'] ?? ''))) === $requestedCode
        );

        if (! $officialMethod) {
            $security->warning('checkout_payment_method_invalid', $security->requestContext($request, [
                'submitted_payment_method_code' => (string) ($validated['paymentMethodCode'] ?? ''),
                'submitted_total' => (int) round((float) ($validated['total'] ?? 0)),
            ]));
            throw ValidationException::withMessages([
                'paymentMethodCode' => 'Metode pembayaran tidak valid untuk nominal checkout ini.',
            ]);
        }

        $validated['paymentMethodCode'] = (string) ($officialMethod['id'] ?? $validated['paymentMethodCode']);
        $validated['paymentLabel'] = (string) ($officialMethod['label'] ?? $validated['paymentLabel']);
        $validated['paymentImage'] = $officialMethod['image'] ?? ($validated['paymentImage'] ?? null);
        $validated['paymentCaption'] = (string) ($officialMethod['caption'] ?? ($validated['paymentCaption'] ?? ''));
        $validated['paymentType'] = (string) ($officialMethod['group'] ?? ($validated['paymentType'] ?? ''));

        return $validated;
    }

    public function show(Request $request, string $transaction, TransactionService $transactions): RedirectResponse|Response
    {
        $record = $transactions->findAccessibleTransaction($request, $transaction, sync: false);

        if (! $record) {
            return to_route('home');
        }

        $shouldDeferBootstrap = $transactions->shouldDeferCheckoutBootstrap(
            $record,
            (string) $request->session()->pull('last_checkout_transaction_id', ''),
        );

        if (! $shouldDeferBootstrap) {
            $record = $transactions->syncTransaction($record);

            if (! $record) {
                return to_route('home');
            }

            $record = $transactions->hydrateCheckoutPaymentReference($record);
        }

        return Inertia::render('PaymentCheckout', [
            'checkout' => $transactions->toCheckoutPayload($record),
        ]);
    }

    public function rate(Request $request, string $transaction, TransactionService $transactions): RedirectResponse
    {
        $record = $transactions->findAccessibleTransaction($request, $transaction, sync: true);

        if (! $record) {
            return to_route('home');
        }

        if ($record->status !== Transaction::STATUS_COMPLETED) {
            throw ValidationException::withMessages([
                'rating' => 'Rating baru bisa dikirim setelah pesanan selesai.',
            ]);
        }

        $validated = $request->validate([
            'score' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:500'],
        ]);

        $record->forceFill([
            'rating_score' => (int) $validated['score'],
            'rating_comment' => filled($validated['comment'] ?? null) ? trim((string) $validated['comment']) : null,
            'rated_at' => now(),
        ])->save();

        return back();
    }

    /**
     * @param  array<int, array<string, mixed>>  $summaryRows
     * @return array<int, array<string, string>>
     */
    private function decorateSummaryRows(array $summaryRows, int $subtotal, ?string $promoCode, int $promoDiscount, int $finalTotal): array
    {
        $filteredRows = collect($summaryRows)
            ->filter(fn (mixed $row) => is_array($row))
            ->reject(function (array $row) {
                $label = strtolower(trim((string) ($row['label'] ?? '')));

                return in_array($label, ['subtotal', 'kode promo', 'diskon promo', 'total bayar'], true);
            })
            ->map(fn (array $row) => [
                'label' => trim((string) ($row['label'] ?? '')),
                'value' => trim((string) ($row['value'] ?? '')),
            ])
            ->filter(fn (array $row) => $row['label'] !== '' && $row['value'] !== '')
            ->values();

        $filteredRows->push([
            'label' => 'Subtotal',
            'value' => 'Rp'.number_format($subtotal, 0, ',', '.'),
        ]);

        if (filled($promoCode) && $promoDiscount > 0) {
            $filteredRows->push([
                'label' => 'Kode promo',
                'value' => (string) $promoCode,
            ]);
            $filteredRows->push([
                'label' => 'Diskon promo',
                'value' => '-Rp'.number_format($promoDiscount, 0, ',', '.'),
            ]);
        }

        $filteredRows->push([
            'label' => 'Total bayar',
            'value' => 'Rp'.number_format($finalTotal, 0, ',', '.'),
        ]);

        return $filteredRows->all();
    }
}
