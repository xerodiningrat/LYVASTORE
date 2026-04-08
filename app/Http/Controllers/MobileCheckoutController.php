<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\User;
use App\Services\DuitkuService;
use App\Services\LyvaCoinService;
use App\Services\PromoCodeService;
use App\Services\SecurityEventService;
use App\Services\TransactionService;
use App\Services\VipaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MobileCheckoutController extends Controller
{
    private const MINIMUM_BANK_TRANSFER_AMOUNT = 10000;

    /**
     * @return array<string, string>
     */
    private function corsHeaders(): array
    {
        return [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, POST, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Accept, Origin',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function respond(array $payload, int $status = 200): JsonResponse
    {
        return response()->json($payload, $status)->withHeaders($this->corsHeaders());
    }

    private function normalizeWhatsapp(?string $value): ?string
    {
        $normalized = preg_replace('/[^0-9+]/', '', trim((string) $value));

        return $normalized !== '' ? $normalized : null;
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<int, array<string, string>>
     */
    private function mergeContactFields(array $validated, ?User $user): array
    {
        $contactFields = collect($validated['contactFields'] ?? [])
            ->filter(fn (mixed $field) => is_array($field) && filled($field['id'] ?? null))
            ->map(fn (array $field) => [
                'id' => trim((string) ($field['id'] ?? '')),
                'label' => trim((string) ($field['label'] ?? '')),
                'value' => trim((string) ($field['value'] ?? '')),
            ])
            ->values();

        $fallbacks = [
            'buyer-name' => [
                'label' => 'Nama pembeli',
                'value' => trim((string) ($validated['customerName'] ?? $user?->name ?? '')),
            ],
            'buyer-email' => [
                'label' => 'Email pembeli',
                'value' => trim((string) ($validated['customerEmail'] ?? $user?->email ?? '')),
            ],
            'buyer-whatsapp' => [
                'label' => 'WhatsApp pembeli',
                'value' => trim((string) ($validated['customerWhatsapp'] ?? $user?->whatsapp_number ?? '')),
            ],
        ];

        foreach ($fallbacks as $fieldId => $field) {
            $hasField = $contactFields->contains(fn (array $item) => $item['id'] === $fieldId && filled($item['value']));

            if (! $hasField && filled($field['value'])) {
                $contactFields->push([
                    'id' => $fieldId,
                    'label' => $field['label'],
                    'value' => $field['value'],
                ]);
            }
        }

        return $contactFields->all();
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function resolveUserForCheckout(array $validated): ?User
    {
        $email = strtolower(trim((string) ($validated['customerEmail'] ?? '')));
        $whatsapp = $this->normalizeWhatsapp($validated['customerWhatsapp'] ?? null);

        if ($email !== '') {
            $user = User::query()->where('email', $email)->first();

            if ($user) {
                return $user;
            }
        }

        if ($whatsapp !== null) {
            return User::query()->where('whatsapp_number', $whatsapp)->first();
        }

        return null;
    }

    private function makeAccessToken(Transaction $transaction): string
    {
        $parts = [
            (string) $transaction->public_id,
            (string) ($transaction->customer_email ?? ''),
            (string) ($transaction->customer_whatsapp ?? ''),
            (string) optional($transaction->created_at)->timestamp,
        ];

        return hash_hmac('sha256', implode('|', $parts), (string) config('app.key'));
    }

    private function canAccessTransaction(Transaction $transaction, ?string $submittedToken): bool
    {
        $token = trim((string) $submittedToken);

        return $token !== '' && hash_equals($this->makeAccessToken($transaction), $token);
    }

    public function store(
        Request $request,
        TransactionService $transactions,
        PromoCodeService $promos,
        VipaymentService $vipayment,
        DuitkuService $duitku,
        LyvaCoinService $coins,
        SecurityEventService $security,
    ): JsonResponse {
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
            'customerName' => ['nullable', 'string', 'max:255'],
            'customerEmail' => ['nullable', 'string', 'lowercase', 'email', 'max:255'],
            'customerWhatsapp' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s]{8,20}$/'],
        ], [
            'customerWhatsapp.regex' => 'Nomor WhatsApp hanya boleh berisi angka, spasi, tanda plus, atau tanda hubung.',
        ]);

        $resolvedUser = $this->resolveUserForCheckout($validated);

        if ($resolvedUser) {
            $request->setUserResolver(fn () => $resolvedUser);
        }

        $validated['contactFields'] = $this->mergeContactFields($validated, $resolvedUser);
        $validated['customerWhatsapp'] = $this->normalizeWhatsapp($validated['customerWhatsapp'] ?? $resolvedUser?->whatsapp_number);
        $validated['customerEmail'] = strtolower(trim((string) ($validated['customerEmail'] ?? $resolvedUser?->email ?? '')));
        $validated['customerName'] = trim((string) ($validated['customerName'] ?? $resolvedUser?->name ?? ''));

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
        $transaction = $transactions->hydrateCheckoutPaymentReference($transaction);

        return $this->respond([
            'message' => 'Checkout berhasil dibuat.',
            'data' => [
                'checkout' => $transactions->toCheckoutPayload($transaction),
                'accessToken' => $this->makeAccessToken($transaction),
            ],
            'meta' => [
                'generatedAt' => now()->toIso8601String(),
            ],
        ], 201);
    }

    public function show(Request $request, string $transaction, TransactionService $transactions): JsonResponse
    {
        $record = Transaction::query()->where('public_id', $transaction)->first();

        if (! $record || ! $this->canAccessTransaction($record, $request->query('token'))) {
            return $this->respond([
                'message' => 'Checkout tidak ditemukan atau akses tidak valid.',
            ], 404);
        }

        $record = $transactions->syncTransaction($record) ?? $record;
        $record = $transactions->hydrateCheckoutPaymentReference($record);

        return $this->respond([
            'message' => 'Detail checkout berhasil diambil.',
            'data' => [
                'checkout' => $transactions->toCheckoutPayload($record),
                'accessToken' => $this->makeAccessToken($record),
            ],
            'meta' => [
                'generatedAt' => now()->toIso8601String(),
            ],
        ]);
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

    /**
     * @param  array<string, mixed>  $validated
     */
    private function guardVipaymentPackageIntegrity(
        Request $request,
        array $validated,
        VipaymentService $vipayment,
        int $originalTotal,
        SecurityEventService $security,
    ): void {
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
            $security->warning('mobile_checkout_price_mismatch', $security->requestContext($request, [
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
    ): array {
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
            $security->warning('mobile_checkout_payment_method_invalid', $security->requestContext($request, [
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
