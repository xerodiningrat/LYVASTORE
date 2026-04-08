<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class DuitkuService
{
    private const MINIMUM_BANK_TRANSFER_AMOUNT = 10000;

    private const NEXT_DATA_SCRIPT_PATTERN = '/<script id="__NEXT_DATA__" type="application\/json">(.*?)<\/script>/s';

    public function configured(): bool
    {
        return filled(config('duitku.merchant_code')) && filled(config('duitku.api_key'));
    }

    /**
     * @return array<int, array{id: string, label: string, caption: string, image: string|null, fee: int}>
     */
    public function getPaymentMethods(int $amount): array
    {
        if (! $this->configured()) {
            throw new RuntimeException('Duitku belum dikonfigurasi.');
        }

        $cacheKey = sprintf(
            'duitku:payment-methods:%s:%s:%d',
            config('duitku.sandbox') ? 'sandbox' : 'production',
            (string) config('duitku.merchant_code'),
            $amount,
        );

        $resolver = fn () => $this->requestPaymentMethods($amount);

        try {
            return Cache::remember(
                $cacheKey,
                now()->addMinutes((int) config('duitku.cache_ttl', 10)),
                $resolver,
            );
        } catch (Throwable $exception) {
            report($exception);

            return $resolver();
        }
    }

    /**
     * @return array<int, array{id: string, label: string, caption: string, image: string|null, fee: int, group: string}>
     */
    private function requestPaymentMethods(int $amount): array
    {
        $merchantCode = (string) config('duitku.merchant_code');
        $apiKey = (string) config('duitku.api_key');
        $datetime = now()->format('Y-m-d H:i:s');
        $signature = hash('sha256', $merchantCode.$amount.$datetime.$apiKey);

        $response = Http::timeout((int) config('duitku.timeout', 15))
            ->acceptJson()
            ->asJson()
            ->post($this->getPaymentMethodUrl(), [
                'merchantcode' => $merchantCode,
                'amount' => $amount,
                'datetime' => $datetime,
                'signature' => $signature,
            ]);

        if (! $response->successful()) {
            $message = (string) ($response->json('responseMessage') ?? $response->json('Message') ?? 'Duitku sedang tidak bisa dihubungi.');

            throw new RuntimeException($message);
        }

        $payload = $response->json();

        if (($payload['responseCode'] ?? null) !== '00') {
            throw new RuntimeException((string) ($payload['responseMessage'] ?? 'Duitku menolak permintaan metode pembayaran.'));
        }

        return collect($payload['paymentFee'] ?? [])
            ->map(function (array $payment) use ($amount) {
                $fee = (int) ($payment['totalFee'] ?? 0);
                $id = (string) ($payment['paymentMethod'] ?? '');
                $label = (string) ($payment['paymentName'] ?? 'Metode Pembayaran');

                return [
                    'id' => $id,
                    'label' => $label,
                    'caption' => $fee > 0 ? 'Biaya admin '.$this->formatRupiah($fee) : 'Tanpa biaya admin',
                    'image' => $payment['paymentImage'] ?? null,
                    'fee' => $fee,
                    'group' => $this->resolvePaymentGroup($id, $label),
                ];
            })
            ->filter(fn (array $payment) => filled($payment['id']))
            ->filter(fn (array $payment) => ! $this->shouldHideForSmallAmount($payment, $amount))
            ->values()
            ->all();
    }

    /**
     * @param  array{
     *     merchantOrderId: string,
     *     paymentAmount: int,
     *     productDetails: string,
     *     paymentMethod?: string|null,
     *     customerVaName?: string|null,
     *     email?: string|null,
     *     phoneNumber?: string|null,
     *     itemDetails?: array<int, array{name: string, price: int, quantity: int}>,
     *     customerDetail?: array<string, mixed>,
     *     callbackUrl?: string|null,
     *     returnUrl?: string|null,
     *     expiryPeriod?: int|null,
     *     additionalParam?: string|null,
     *     merchantUserInfo?: string|null,
     * }  $payload
     * @return array<string, mixed>
     */
    public function createInvoice(array $payload): array
    {
        if (! $this->configured()) {
            throw new RuntimeException('Duitku belum dikonfigurasi.');
        }

        $merchantCode = (string) config('duitku.merchant_code');
        $apiKey = (string) config('duitku.api_key');
        $timestamp = (string) (int) round(microtime(true) * 1000);
        $signature = hash('sha256', $merchantCode.$timestamp.$apiKey);

        $requestPayload = array_filter([
            'paymentAmount' => (int) $payload['paymentAmount'],
            'merchantOrderId' => (string) $payload['merchantOrderId'],
            'productDetails' => (string) $payload['productDetails'],
            'additionalParam' => $payload['additionalParam'] ?? '',
            'merchantUserInfo' => $payload['merchantUserInfo'] ?? '',
            'paymentMethod' => $payload['paymentMethod'] ?? null,
            'customerVaName' => $payload['customerVaName'] ?? null,
            'email' => $payload['email'] ?? null,
            'phoneNumber' => $payload['phoneNumber'] ?? null,
            'itemDetails' => $payload['itemDetails'] ?? [],
            'customerDetail' => $payload['customerDetail'] ?? null,
            'callbackUrl' => $payload['callbackUrl'] ?? null,
            'returnUrl' => $payload['returnUrl'] ?? null,
            'expiryPeriod' => $payload['expiryPeriod'] ?? 15,
        ], fn ($value) => $value !== null && $value !== '');

        $response = Http::timeout((int) config('duitku.timeout', 15))
            ->acceptJson()
            ->withHeaders([
                'x-duitku-signature' => $signature,
                'x-duitku-timestamp' => $timestamp,
                'x-duitku-merchantcode' => $merchantCode,
            ])
            ->asJson()
            ->post($this->getCreateInvoiceUrl(), $requestPayload);

        if (! $response->successful()) {
            $message = (string) ($response->json('statusMessage') ?? $response->json('message') ?? 'Duitku gagal membuat invoice.');

            throw new RuntimeException($message);
        }

        $responsePayload = $response->json();

        if (($responsePayload['statusCode'] ?? null) !== '00') {
            throw new RuntimeException((string) ($responsePayload['statusMessage'] ?? 'Duitku menolak pembuatan invoice.'));
        }

        return [
            'reference' => (string) ($responsePayload['reference'] ?? ''),
            'paymentUrl' => $responsePayload['paymentUrl'] ?? null,
            'vaNumber' => $responsePayload['vaNumber'] ?? null,
            'qrString' => $responsePayload['qrString'] ?? null,
            'amount' => (int) ($responsePayload['amount'] ?? $payload['paymentAmount']),
            'statusCode' => (string) ($responsePayload['statusCode'] ?? '00'),
            'statusMessage' => (string) ($responsePayload['statusMessage'] ?? 'SUCCESS'),
        ];
    }

    /**
     * @return array{
     *     reference: string,
     *     statusCode: string,
     *     statusMessage: string,
     *     vaNumber: string|null,
     *     paymentUrl: string|null,
     *     qrString: string|null,
     *     expiredDate: string|null,
     *     redirectUrl: string|null,
     * }
     */
    public function getCheckoutPaymentDetail(string $paymentUrl, string $channel): array
    {
        $paymentUrl = trim($paymentUrl);
        $channel = trim($channel);

        if ($paymentUrl === '' || $channel === '') {
            throw new RuntimeException('URL pembayaran atau channel Duitku belum tersedia.');
        }

        $pageState = $this->getCheckoutPageState($paymentUrl);
        $ticket = data_get($pageState, 'props.pageProps.ticket');
        $reference = data_get($pageState, 'props.pageProps.reference') ?: $this->extractCheckoutReferenceFromUrl($paymentUrl);

        if (! filled($ticket) || ! filled($reference)) {
            throw new RuntimeException('Duitku checkout page tidak mengembalikan tiket pembayaran yang valid.');
        }

        $response = Http::timeout((int) config('duitku.timeout', 15))
            ->acceptJson()
            ->withHeaders([
                'Content-Type' => 'application/json',
                'X-Timestamp' => (string) (int) round(microtime(true) * 1000),
                'X-Duitku-Ticket' => (string) $ticket,
            ])
            ->asJson()
            ->post($this->resolveCheckoutProcessUrl($paymentUrl, (string) $reference), [
                'channel' => $channel,
            ]);

        if (! $response->successful()) {
            $message = (string) ($response->json('statusMessage') ?? $response->json('message') ?? 'Duitku gagal memuat detail pembayaran.');

            throw new RuntimeException($message);
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            throw new RuntimeException('Respons detail pembayaran Duitku tidak valid.');
        }

        return [
            'reference' => (string) $reference,
            'statusCode' => (string) ($payload['statusCode'] ?? ''),
            'statusMessage' => (string) ($payload['statusMessage'] ?? ''),
            'vaNumber' => isset($payload['vaNumber']) ? (string) $payload['vaNumber'] : null,
            'paymentUrl' => isset($payload['paymentUrl']) ? (string) $payload['paymentUrl'] : null,
            'qrString' => isset($payload['qrString']) ? (string) $payload['qrString'] : null,
            'expiredDate' => isset($payload['expiredDate']) ? (string) $payload['expiredDate'] : null,
            'redirectUrl' => isset($payload['redirectUrl']) ? (string) $payload['redirectUrl'] : null,
        ];
    }

    /**
     * @return array{merchantOrderId: string, reference: string|null, amount: int, fee: float, statusCode: string, statusMessage: string}
     */
    public function getTransactionStatus(string $merchantOrderId): array
    {
        if (! $this->configured()) {
            throw new RuntimeException('Duitku belum dikonfigurasi.');
        }

        $merchantCode = (string) config('duitku.merchant_code');
        $apiKey = (string) config('duitku.api_key');
        $signature = md5($merchantCode.$merchantOrderId.$apiKey);

        $response = Http::timeout((int) config('duitku.timeout', 15))
            ->acceptJson()
            ->asForm()
            ->post($this->getTransactionStatusUrl(), [
                'merchantCode' => $merchantCode,
                'merchantOrderId' => $merchantOrderId,
                'signature' => $signature,
            ]);

        if (! $response->successful()) {
            $message = (string) ($response->json('statusMessage') ?? $response->json('message') ?? 'Duitku gagal mengecek status transaksi.');

            throw new RuntimeException($message);
        }

        $payload = $response->json();

        return [
            'merchantOrderId' => (string) ($payload['merchantOrderId'] ?? $merchantOrderId),
            'reference' => $payload['reference'] ?? null,
            'amount' => (int) ($payload['amount'] ?? 0),
            'fee' => (float) ($payload['fee'] ?? 0),
            'statusCode' => (string) ($payload['statusCode'] ?? ''),
            'statusMessage' => (string) ($payload['statusMessage'] ?? ''),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function verifyCallbackSignature(array $payload): bool
    {
        if (! $this->configured()) {
            return false;
        }

        $merchantCode = (string) ($payload['merchantCode'] ?? $payload['merchantcode'] ?? '');
        $amount = (string) ($payload['amount'] ?? '');
        $merchantOrderId = (string) ($payload['merchantOrderId'] ?? '');
        $signature = Str::lower((string) ($payload['signature'] ?? ''));

        if ($merchantCode === '' || $amount === '' || $merchantOrderId === '' || $signature === '') {
            return false;
        }

        $expected = md5($merchantCode.$amount.$merchantOrderId.(string) config('duitku.api_key'));

        return hash_equals($expected, $signature);
    }

    private function formatRupiah(int $amount): string
    {
        return 'Rp'.number_format($amount, 0, ',', '.');
    }

    /**
     * @param  array{id: string, label: string, group?: string}  $payment
     */
    private function shouldHideForSmallAmount(array $payment, int $amount): bool
    {
        if ($amount >= self::MINIMUM_BANK_TRANSFER_AMOUNT) {
            return false;
        }

        return ! in_array($payment['group'] ?? $this->resolvePaymentGroup($payment['id'], $payment['label']), [
            'qris',
            'dana',
            'gopay',
            'ovo',
            'shopeepay',
            'linkaja',
            'paylater',
        ], true);
    }

    private function resolvePaymentGroup(string $id, string $label): string
    {
        $value = Str::lower(trim($id.' '.$label));

        if (Str::contains($value, 'qris')) {
            return 'qris';
        }

        if (Str::contains($value, ['paylater', 'indodana', 'kredivo'])) {
            return 'paylater';
        }

        if (Str::contains($value, 'dana')) {
            return 'dana';
        }

        if (Str::contains($value, ['gopay', 'go pay'])) {
            return 'gopay';
        }

        if (Str::contains($value, 'ovo')) {
            return 'ovo';
        }

        if (Str::contains($value, 'shopeepay')) {
            return 'shopeepay';
        }

        if (Str::contains($value, 'linkaja')) {
            return 'linkaja';
        }

        if (Str::contains($value, ['credit', 'kredit']) || Str::startsWith($value, 'vc ')) {
            return 'credit-card';
        }

        if (Str::contains($value, ['retail', 'indomaret', 'alfamart'])) {
            return 'retail';
        }

        if (Str::contains($value, [
            'bank',
            'transfer',
            'virtual account',
            ' va',
            'va ',
            'briva',
            'bniva',
            'mybva',
            'permatava',
            'cimbva',
            'danamonva',
            'atm bersama',
            'permata',
            'bca',
            'bni',
            'bri',
            'mandiri',
            'cimb',
            'danamon',
            'maybank',
            'bsi',
            'artha graha',
        ])) {
            return 'bank-transfer';
        }

        return $value;
    }

    /**
     * @return array<string, mixed>
     */
    private function getCheckoutPageState(string $paymentUrl): array
    {
        $response = Http::timeout((int) config('duitku.timeout', 15))
            ->withHeaders([
                'Accept' => 'text/html,application/xhtml+xml',
            ])
            ->get($paymentUrl);

        if (! $response->successful()) {
            throw new RuntimeException('Duitku checkout page tidak bisa dimuat.');
        }

        $html = (string) $response->body();

        if (! preg_match(self::NEXT_DATA_SCRIPT_PATTERN, $html, $matches)) {
            throw new RuntimeException('Payload checkout Duitku tidak ditemukan.');
        }

        $payload = json_decode($matches[1], true);

        if (! is_array($payload)) {
            throw new RuntimeException('Payload checkout Duitku tidak valid.');
        }

        return $payload;
    }

    private function resolveCheckoutProcessUrl(string $paymentUrl, string $reference): string
    {
        $scheme = (string) (parse_url($paymentUrl, PHP_URL_SCHEME) ?: 'https');
        $host = (string) (parse_url($paymentUrl, PHP_URL_HOST) ?: '');

        if ($host === '') {
            throw new RuntimeException('Host checkout Duitku tidak valid.');
        }

        return sprintf('%s://%s/api/process/%s', $scheme, $host, $reference);
    }

    private function extractCheckoutReferenceFromUrl(string $paymentUrl): ?string
    {
        $query = parse_url($paymentUrl, PHP_URL_QUERY);

        if (! is_string($query) || $query === '') {
            return null;
        }

        parse_str($query, $parameters);
        $reference = $parameters['reference'] ?? null;

        return filled($reference) ? (string) $reference : null;
    }

    private function getCreateInvoiceUrl(): string
    {
        $baseUrl = (bool) config('duitku.sandbox', true)
            ? 'https://api-sandbox.duitku.com'
            : 'https://api-prod.duitku.com';

        return $baseUrl.'/api/merchant/createInvoice';
    }

    private function getPaymentMethodUrl(): string
    {
        return $this->resolveBaseUrl().'/webapi/api/merchant/paymentmethod/getpaymentmethod';
    }

    private function getTransactionStatusUrl(): string
    {
        return $this->resolveBaseUrl().'/webapi/api/merchant/transactionStatus';
    }

    private function resolveBaseUrl(): string
    {
        $configuredBaseUrl = trim((string) config('duitku.base_url', ''));
        $baseUrl = $configuredBaseUrl !== ''
            ? $configuredBaseUrl
            : ((bool) config('duitku.sandbox', true) ? 'https://sandbox.duitku.com' : 'https://passport.duitku.com');

        if (! preg_match('#^https?://#i', $baseUrl)) {
            $baseUrl = 'https://'.ltrim($baseUrl, '/');
        }

        $baseUrl = preg_replace('#/webapi/api/merchant/paymentmethod/getpaymentmethod/?$#i', '', $baseUrl) ?? $baseUrl;
        $baseUrl = rtrim($baseUrl, '/');

        if (! filter_var($baseUrl, FILTER_VALIDATE_URL)) {
            throw new RuntimeException('DUITKU_BASE_URL tidak valid. Gunakan domain Duitku yang lengkap, misalnya https://sandbox.duitku.com.');
        }

        return $baseUrl;
    }
}
