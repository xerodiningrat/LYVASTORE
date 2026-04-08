<?php

namespace App\Services;

use App\Models\Transaction;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class WhatsappCommerceService
{
    private const PRODUCT_CATALOG = [
        [
            'product_id' => 'vip-game-chatgpt',
            'product_name' => 'ChatGPT',
            'keywords' => ['chatgpt', 'chat gpt'],
        ],
        [
            'product_id' => 'vip-game-capcut-pro',
            'product_name' => 'CapCut Pro',
            'keywords' => ['capcut', 'cap cut'],
        ],
        [
            'product_id' => 'vip-game-canva-pro',
            'product_name' => 'Canva Pro',
            'keywords' => ['canva', 'canva pro'],
        ],
        [
            'product_id' => 'vip-game-netflix',
            'product_name' => 'Netflix',
            'keywords' => ['netflix'],
        ],
        [
            'product_id' => 'vip-game-spotify-premium',
            'product_name' => 'Spotify Premium',
            'keywords' => ['spotify', 'spotify premium'],
        ],
        [
            'product_id' => 'vip-game-youtube-premium',
            'product_name' => 'YouTube Premium',
            'keywords' => ['youtube premium', 'youtube', 'yt premium'],
        ],
        [
            'product_id' => 'vip-game-bstation-premium',
            'product_name' => 'Bstation Premium',
            'keywords' => ['bstation', 'b station', 'bstation premium', 'bili bili', 'bilibili'],
        ],
        [
            'product_id' => 'vip-game-iqiyi-premium',
            'product_name' => 'iQIYI Premium',
            'keywords' => ['iqiyi', 'iqyi', 'i qiyi', 'iqiyi premium', 'iqyi premium'],
        ],
        [
            'product_id' => 'vip-game-vidio-premier',
            'product_name' => 'Vidio Premier',
            'keywords' => ['vidio', 'vidio premier', 'vidio premium'],
        ],
        [
            'product_id' => 'vip-game-viu-premium',
            'product_name' => 'Viu Premium',
            'keywords' => ['viu', 'viu premium'],
        ],
        [
            'product_id' => 'vip-game-wetv-premium',
            'product_name' => 'WeTV Premium',
            'keywords' => ['wetv', 'we tv', 'wetv premium', 'we tv premium'],
        ],
        [
            'product_id' => 'vip-game-vision-plus',
            'product_name' => 'Vision Plus',
            'keywords' => ['vision plus', 'vision+', 'visionplus'],
        ],
        [
            'product_id' => 'vip-prepaid-telkomsel',
            'product_name' => 'Pulsa Telkomsel',
            'keywords' => ['telkomsel', 'pulsa telkomsel', 'simpati', 'as telkomsel'],
        ],
        [
            'product_id' => 'vip-prepaid-indosat',
            'product_name' => 'Pulsa Indosat',
            'keywords' => ['indosat', 'pulsa indosat', 'im3', 'mentari'],
        ],
        [
            'product_id' => 'vip-prepaid-tri',
            'product_name' => 'Pulsa Tri',
            'keywords' => ['tri', 'three', 'pulsa tri', 'pulsa 3'],
        ],
        [
            'product_id' => 'vip-prepaid-xl',
            'product_name' => 'Pulsa XL',
            'keywords' => ['xl', 'pulsa xl'],
        ],
        [
            'product_id' => 'vip-prepaid-axis',
            'product_name' => 'Pulsa AXIS',
            'keywords' => ['axis', 'pulsa axis'],
        ],
        [
            'product_id' => 'vip-prepaid-smartfren',
            'product_name' => 'Pulsa Smartfren',
            'keywords' => ['smartfren', 'pulsa smartfren', 'smart fren'],
        ],
        [
            'product_id' => 'vip-prepaid-byu',
            'product_name' => 'Pulsa by.U',
            'keywords' => ['byu', 'by.u', 'pulsa byu', 'pulsa by.u'],
        ],
        [
            'product_id' => 'vip-prepaid-dana',
            'product_name' => 'DANA',
            'keywords' => ['dana', 'saldo dana', 'topup dana', 'top up dana'],
        ],
        [
            'product_id' => 'vip-prepaid-go-pay',
            'product_name' => 'GoPay',
            'keywords' => ['gopay', 'go pay', 'saldo gopay', 'saldo go pay', 'topup gopay', 'top up gopay'],
        ],
        [
            'product_id' => 'vip-prepaid-ovo',
            'product_name' => 'OVO',
            'keywords' => ['ovo', 'saldo ovo', 'topup ovo', 'top up ovo'],
        ],
        [
            'product_id' => 'vip-prepaid-linkaja',
            'product_name' => 'LinkAja',
            'keywords' => ['linkaja', 'link aja', 'saldo linkaja', 'saldo link aja'],
        ],
        [
            'product_id' => 'vip-prepaid-shopee-pay',
            'product_name' => 'ShopeePay',
            'keywords' => ['shopeepay', 'shopee pay', 'saldo shopeepay', 'saldo shopee pay'],
        ],
        [
            'product_id' => 'vip-prepaid-doku',
            'product_name' => 'DOKU',
            'keywords' => ['doku', 'saldo doku'],
        ],
        [
            'product_id' => 'vip-prepaid-bri-brizzi',
            'product_name' => 'BRI Brizzi',
            'keywords' => ['brizzi', 'bri brizzi', 'saldo brizzi'],
        ],
        [
            'product_id' => 'vip-prepaid-tapcash-bni',
            'product_name' => 'TapCash BNI',
            'keywords' => ['tapcash', 'tap cash', 'bni tapcash', 'tapcash bni'],
        ],
        [
            'product_id' => 'vip-prepaid-mandiri-e-toll',
            'product_name' => 'Mandiri E-Toll',
            'keywords' => ['e toll', 'etoll', 'e-toll', 'mandiri e toll', 'mandiri etoll'],
        ],
        [
            'product_id' => 'vip-prepaid-grab',
            'product_name' => 'Grab',
            'keywords' => ['grab', 'saldo grab', 'topup grab', 'top up grab'],
        ],
        [
            'product_id' => 'vip-prepaid-maxim',
            'product_name' => 'Maxim',
            'keywords' => ['maxim', 'saldo maxim', 'topup maxim', 'top up maxim'],
        ],
    ];
    private const MENU_CACHE_PREFIX = 'lyvaflow:wa-commerce:';
    private const FOLLOW_UP_NOTICE_CACHE_PREFIX = 'lyvaflow:wa-commerce-notice:';
    private const AWAITING_ACCOUNT_STATE = 'awaiting-account';
    private const MENU_CACHE_MINUTES = 30;
    private const FOLLOW_UP_NOTICE_MINUTES = 180;

    public function __construct(
        private readonly VipaymentService $vipayment,
        private readonly DuitkuService $duitku,
        private readonly ManualStockService $manualStock,
        private readonly LyvaflowService $lyvaflow,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function handleIncomingMessage(array $payload): array
    {
        $jid = trim((string) ($payload['jid'] ?? ''));
        $phoneNumber = $this->lyvaflow->normalizeWhatsappNumber((string) ($payload['phoneNumber'] ?? ''));
        $text = trim((string) ($payload['text'] ?? ''));
        $senderName = trim((string) ($payload['senderName'] ?? '')) ?: 'Kak';

        if (($phoneNumber === '' && $jid === '') || $text === '') {
            return ['handled' => false, 'reason' => 'empty_payload'];
        }

        $cachedState = $this->findCachedState($jid, $phoneNumber);
        $replyJid = $this->resolveReplyJid($jid, $cachedState);
        $replyPhoneNumber = $this->resolveReplyPhoneNumber($phoneNumber, $cachedState);

        try {
            if ($this->wantsProductCatalog($text)) {
                $this->reply($replyJid, $replyPhoneNumber, $this->buildProductCatalogMessage($senderName), [
                    'replyType' => 'menu',
                ]);

                return ['handled' => true, 'reason' => 'catalog_sent'];
            }

            if ($this->isCancelMessage($text)) {
                $this->forgetCachedState($jid, $phoneNumber, $cachedState);
                $this->forgetFollowUpNotice($jid, $phoneNumber, $cachedState);
                $this->reply($replyJid, $replyPhoneNumber, $this->buildCancelMessage($senderName), [
                    'replyType' => 'cancel',
                ]);

                return ['handled' => true, 'reason' => 'cancelled'];
            }

            $matchedProduct = $this->matchProductIntent($text);

            if (is_array($matchedProduct)) {
                $options = $this->resolveMenuOptions(
                    (string) $matchedProduct['product_id'],
                    (string) ($matchedProduct['product_name'] ?? 'Produk'),
                );

                if ($options === []) {
                    $this->reply($replyJid, $replyPhoneNumber, $this->buildUnavailableProductMessage($senderName, (string) ($matchedProduct['product_name'] ?? 'produk ini')), [
                        'replyType' => 'error',
                    ]);

                    return ['handled' => true, 'reason' => 'no_options_notice'];
                }

                $cachedState = $this->storeCachedState([
                    'type' => 'product-menu',
                    'product' => $matchedProduct,
                    'options' => $options,
                    'jid' => $jid,
                    'phone_number' => $phoneNumber,
                    'created_at' => now()->toIso8601String(),
                ], $jid, $phoneNumber, null);
                $this->storeFollowUpNotice($jid, $phoneNumber, (string) ($matchedProduct['product_name'] ?? 'produk ini'), $cachedState);

                $this->reply($replyJid, $replyPhoneNumber, $this->buildProductMenuMessage($senderName, $matchedProduct, $options), [
                    'replyType' => 'menu',
                ]);

                return ['handled' => true, 'reason' => 'menu_sent', 'options' => count($options)];
            }

            if (! is_array($cachedState) && $this->looksLikeCommerceFollowUp($text)) {
                $productName = $this->findFollowUpNotice($jid, $phoneNumber);

                if ($productName !== '') {
                    $this->reply($replyJid, $replyPhoneNumber, $this->buildExpiredFollowUpMessage($senderName, $productName), [
                        'replyType' => 'expired',
                    ]);

                    return ['handled' => true, 'reason' => 'expired_followup_notice'];
                }

                $this->reply($replyJid, $replyPhoneNumber, $this->buildMissingStateMessage($senderName), [
                    'replyType' => 'invalid',
                ]);

                return ['handled' => true, 'reason' => 'missing_state_notice'];
            }

            if (is_array($cachedState) && ($cachedState['type'] ?? '') === 'product-menu' && preg_match('/^\d+$/', $text) === 1) {
                $optionIndex = ((int) $text) - 1;
                $options = is_array($cachedState['options'] ?? null) ? $cachedState['options'] : [];
                $selectedOption = $options[$optionIndex] ?? null;
                $product = is_array($cachedState['product'] ?? null) ? $cachedState['product'] : null;

                if (! is_array($selectedOption) || ! is_array($product)) {
                    $this->reply($jid, $phoneNumber, 'Pilihan tidak ditemukan. Ketik nama produk lagi untuk melihat daftar paket.', [
                        'replyType' => 'invalid',
                    ]);

                    return ['handled' => true, 'reason' => 'invalid_selection'];
                }

                $cachedState = $this->storeCachedState([
                    'type' => self::AWAITING_ACCOUNT_STATE,
                    'product' => $product,
                    'selected_option' => $selectedOption,
                    'jid' => $replyJid,
                    'phone_number' => $replyPhoneNumber,
                    'created_at' => now()->toIso8601String(),
                ], $replyJid, $replyPhoneNumber, $cachedState);
                $this->storeFollowUpNotice($replyJid, $replyPhoneNumber, (string) ($product['product_name'] ?? 'produk ini'), $cachedState);

                $this->reply($replyJid, $replyPhoneNumber, $this->buildAccountPromptMessage($senderName, $product, $selectedOption), [
                    'replyType' => 'prompt',
                ]);

                return ['handled' => true, 'reason' => 'awaiting_account_input'];
            }

            if (is_array($cachedState) && ($cachedState['type'] ?? '') === self::AWAITING_ACCOUNT_STATE) {
                $selectedOption = is_array($cachedState['selected_option'] ?? null) ? $cachedState['selected_option'] : null;
                $product = is_array($cachedState['product'] ?? null) ? $cachedState['product'] : null;

                if (! is_array($selectedOption) || ! is_array($product)) {
                    $this->forgetCachedState($jid, $phoneNumber, $cachedState);
                    $this->forgetFollowUpNotice($jid, $phoneNumber, $cachedState);
                    $this->reply($replyJid, $replyPhoneNumber, $this->buildMissingStateMessage($senderName), [
                        'replyType' => 'invalid',
                    ]);

                    return ['handled' => true, 'reason' => 'stale_state_notice'];
                }

                $accountInput = $this->normalizeAccountInput($text);

                if ($accountInput === null) {
                    $this->storeFollowUpNotice($replyJid, $replyPhoneNumber, (string) ($product['product_name'] ?? 'produk ini'), $cachedState);
                    $this->reply($replyJid, $replyPhoneNumber, $this->buildInvalidAccountPromptMessage($senderName, $product, $selectedOption), [
                        'replyType' => 'invalid',
                    ]);

                    return ['handled' => true, 'reason' => 'invalid_account_input'];
                }

                $invoice = $this->createWhatsappInvoice($replyPhoneNumber, $senderName, $product, $selectedOption, $accountInput);
                $this->forgetCachedState($jid, $phoneNumber, $cachedState);
                $this->forgetFollowUpNotice($jid, $phoneNumber, $cachedState);
                $this->reply($replyJid, $replyPhoneNumber, $this->buildInvoiceMessage($senderName, $product, $invoice, $accountInput), [
                    'qrString' => (string) ($invoice['qrString'] ?? ''),
                    'replyType' => 'invoice',
                ]);

                return ['handled' => true, 'reason' => 'invoice_sent', 'transactionId' => $invoice['publicId'] ?? null];
            }

            return ['handled' => false, 'reason' => 'ignored'];
        } catch (\Throwable $exception) {
            report($exception);

            $this->reply($replyJid, $replyPhoneNumber, $this->buildTemporaryErrorMessage($senderName), [
                'replyType' => 'error',
            ]);

            return ['handled' => true, 'reason' => 'temporary_error_notice'];
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function resolveMenuOptions(string $productId, string $fallbackProductName = 'Produk'): array
    {
        $tabs = $this->vipayment->getProductServices($productId) ?? [];

        return collect($tabs)
            ->flatMap(fn (array $tab) => $tab['groups'] ?? [])
            ->flatMap(fn (array $group) => $group['options'] ?? [])
            ->filter(fn (mixed $option) => is_array($option) && (int) ($option['price'] ?? 0) > 0)
            ->sortBy(fn (array $option) => (int) ($option['price'] ?? 0))
            ->values()
            ->map(fn (array $option, int $index) => [
                'number' => $index + 1,
                'id' => (string) ($option['id'] ?? ''),
                'code' => (string) ($option['code'] ?? ''),
                'label' => trim((string) ($option['label'] ?? ('Paket '.$fallbackProductName))),
                'note' => trim((string) ($option['note'] ?? '')),
                'price' => (int) ($option['price'] ?? 0),
                'accountFieldLabel' => $this->resolvePrimaryAccountFieldLabel($option),
            ])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $selectedOption
     * @return array<string, mixed>
     */
    private function createWhatsappInvoice(string $phoneNumber, string $senderName, array $product, array $selectedOption, string $accountInput): array
    {
        $paymentMethod = collect($this->duitku->getPaymentMethods((int) $selectedOption['price']))
            ->first(function (array $method) {
                $group = Str::lower(trim((string) ($method['group'] ?? '')));
                $label = Str::lower(trim((string) ($method['label'] ?? '')));

                return $group === 'qris' || Str::contains($label, 'qris');
            });

        if (! is_array($paymentMethod)) {
            throw new \RuntimeException('Metode pembayaran QRIS Duitku belum tersedia untuk nominal ini.');
        }

        $publicId = 'LYVAWA'.Str::upper(Str::random(10));
        $transaction = Transaction::create([
            'public_id' => $publicId,
            'guest_token' => 'wa:'.$phoneNumber,
            'status' => Transaction::STATUS_PENDING,
            'payment_status' => Transaction::PAYMENT_STATUS_UNPAID,
            'product_source' => $this->resolveProductSource((string) ($product['product_id'] ?? '')),
            'product_id' => (string) ($product['product_id'] ?? ''),
            'product_name' => (string) ($product['product_name'] ?? 'Produk Digital'),
            'package_code' => filled($selectedOption['code'] ?? null) ? (string) $selectedOption['code'] : null,
            'package_label' => (string) $selectedOption['label'],
            'quantity' => 1,
            'payment_method_code' => (string) ($paymentMethod['id'] ?? ''),
            'payment_method_label' => (string) ($paymentMethod['label'] ?? 'QRIS'),
            'payment_method_type' => (string) ($paymentMethod['group'] ?? 'qris'),
            'payment_method_image' => $paymentMethod['image'] ?? null,
            'payment_caption' => (string) ($paymentMethod['caption'] ?? ''),
            'total' => (int) $selectedOption['price'],
            'checkout_notice' => 'Pesanan dibuat otomatis dari percakapan WhatsApp LYVAFLOW.',
            'guarantee_text' => 'Admin akan follow up data akun setelah pembayaran diverifikasi.',
            'notes' => [
                'Order otomatis dari WhatsApp.',
                'Setelah pembayaran berhasil, admin akan follow up data akun produk bila dibutuhkan.',
            ],
            'summary_rows' => [
                ['label' => 'Channel', 'value' => 'WhatsApp'],
                ['label' => 'Nomor WhatsApp', 'value' => $phoneNumber],
                ['label' => $this->resolveSummaryAccountLabel($selectedOption), 'value' => $accountInput],
            ],
            'account_fields' => [
                [
                    'id' => 'account-email',
                    'label' => $this->resolveSummaryAccountLabel($selectedOption),
                    'value' => $accountInput,
                ],
            ],
            'contact_fields' => [
                ['id' => 'buyer-whatsapp', 'label' => 'Nomor WhatsApp', 'value' => $phoneNumber],
            ],
            'customer_name' => $senderName,
            'customer_email' => $this->resolveCustomerEmail($accountInput),
            'customer_whatsapp' => $phoneNumber,
            'expires_at' => now()->addMinutes(15),
            'last_synced_at' => now(),
        ]);

        $invoice = $this->duitku->createInvoice([
            'merchantOrderId' => $transaction->public_id,
            'paymentAmount' => (int) $transaction->total,
            'productDetails' => $transaction->package_label,
            'paymentMethod' => $transaction->payment_method_code,
            'customerVaName' => $transaction->customer_name ?: 'Pembeli WhatsApp',
            'phoneNumber' => $transaction->customer_whatsapp,
            'itemDetails' => [[
                'name' => $transaction->package_label,
                'price' => (int) $transaction->total,
                'quantity' => 1,
            ]],
            'customerDetail' => [
                'firstName' => $transaction->customer_name ?: 'Pembeli',
                'phoneNumber' => $transaction->customer_whatsapp,
            ],
            'callbackUrl' => route('duitku.callback'),
            'returnUrl' => route('home'),
            'expiryPeriod' => 15,
            'additionalParam' => $transaction->product_id,
            'merchantUserInfo' => 'whatsapp:'.$phoneNumber,
        ]);

        if (
            blank($invoice['qrString'] ?? null)
            && filled($invoice['paymentUrl'] ?? null)
            && filled($transaction->payment_method_code)
        ) {
            try {
                $detail = $this->duitku->getCheckoutPaymentDetail(
                    (string) $invoice['paymentUrl'],
                    (string) $transaction->payment_method_code,
                );

                if (filled($detail['qrString'] ?? null)) {
                    $invoice['qrString'] = (string) $detail['qrString'];
                }

                if (filled($detail['paymentUrl'] ?? null)) {
                    $invoice['paymentUrl'] = (string) $detail['paymentUrl'];
                } elseif (filled($detail['redirectUrl'] ?? null)) {
                    $invoice['paymentUrl'] = (string) $detail['redirectUrl'];
                }

                if (filled($detail['reference'] ?? null)) {
                    $invoice['reference'] = (string) $detail['reference'];
                }
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        $displayType = filled($invoice['qrString'] ?? null) ? 'qris' : 'qris';
        $referenceLabel = 'QRIS';
        $referenceValue = (string) ($invoice['reference'] ?? $transaction->public_id);

        $transaction->forceFill([
            'duitku_reference' => $invoice['reference'] ?? null,
            'duitku_payment_url' => $invoice['paymentUrl'] ?? null,
            'duitku_app_url' => $invoice['paymentUrl'] ?? null,
            'duitku_qr_string' => $invoice['qrString'] ?? null,
            'payment_display_type' => $displayType,
            'payment_reference_label' => $referenceLabel,
            'payment_reference_value' => $referenceValue,
        ])->save();

        return [
            'publicId' => $transaction->public_id,
            'packageLabel' => $transaction->package_label,
            'paymentLabel' => $transaction->payment_method_label,
            'total' => (int) $transaction->total,
            'reference' => (string) ($invoice['reference'] ?? $transaction->public_id),
            'paymentUrl' => (string) ($invoice['paymentUrl'] ?? ''),
            'qrString' => (string) ($invoice['qrString'] ?? ''),
            'expiresAt' => optional($transaction->expires_at)->toIso8601String(),
        ];
    }

    private function resolveProductSource(string $productId): string
    {
        return $this->manualStock->supportsProduct($productId)
            ? Transaction::PRODUCT_SOURCE_MANUAL_STOCK
            : Transaction::PRODUCT_SOURCE_MANUAL;
    }

    private function resolveCustomerEmail(string $accountInput): ?string
    {
        $value = trim($accountInput);

        return filter_var($value, FILTER_VALIDATE_EMAIL) ? $value : null;
    }

    /**
     * @param  array<int, array<string, mixed>>  $options
     */
    private function buildProductMenuMessage(string $senderName, array $product, array $options): string
    {
        $lines = collect($options)->map(function (array $option) {
            return $option['number'].'. '.$option['label'].' - '.$this->formatRupiah((int) ($option['price'] ?? 0));
        })->all();
        $productName = (string) ($product['product_name'] ?? 'Produk');

        return $this->lyvaflow->composeStructuredMessage(
            'Daftar Harga '.$productName,
            [
                'Halo '.$senderName.',',
                'Berikut pilihan paket '.$productName.' yang siap dipesan lewat WhatsApp.',
                'Balas angka sesuai nomor paket yang kamu pilih untuk lanjut ke pembayaran QRIS.',
            ],
            [
                [
                    'title' => 'Pilih Paket',
                    'style' => 'plain',
                    'lines' => $lines,
                ],
            ],
            [
                'Ketik 0 atau batal kalau ingin membatalkan pilihan.',
                'Balas dalam 30 menit supaya sesi pembelian tetap aktif.',
                $this->buildRestartInstruction($productName, 'buka pilihan lagi'),
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $invoice
     */
    private function buildInvoiceMessage(string $senderName, array $product, array $invoice, string $accountInput): string
    {
        $productName = (string) ($product['product_name'] ?? 'Produk');
        $hasQrImage = filled($invoice['qrString'] ?? null);

        return $this->lyvaflow->composeStructuredMessage(
            'Pembayaran QRIS '.$productName,
            [
                'Halo '.$senderName.',',
                'Paket '.$productName.' kamu sudah kami siapkan.',
                $hasQrImage
                    ? 'Silakan scan gambar QRIS yang kami kirim di chat ini untuk lanjut pembayaran.'
                    : 'Silakan lanjut pembayaran QRIS lewat link di bawah ini.',
            ],
            [
                [
                    'title' => 'Ringkasan Order',
                    'lines' => [
                        'Invoice: #'.($invoice['publicId'] ?? '-'),
                        'Paket: '.($invoice['packageLabel'] ?? '-'),
                        'Akun: '.$accountInput,
                        'Total: '.$this->formatRupiah((int) ($invoice['total'] ?? 0)),
                        'Metode bayar: '.($invoice['paymentLabel'] ?? 'QRIS'),
                    ],
                ],
                [
                    'title' => 'Pembayaran',
                    'style' => 'plain',
                    'lines' => array_filter([
                        $hasQrImage ? 'Scan gambar QRIS yang muncul di atas.' : null,
                        ! $hasQrImage && filled($invoice['paymentUrl'] ?? null) ? 'Buka QRIS: '.$invoice['paymentUrl'] : null,
                        filled($invoice['reference'] ?? null) ? 'Reference: '.(string) $invoice['reference'] : null,
                        filled($invoice['expiresAt'] ?? null)
                            ? 'Berlaku sampai: '.date('d M Y H:i', strtotime((string) $invoice['expiresAt'])).' WIB'
                            : null,
                    ]),
                ],
            ],
            [
                'Setelah pembayaran masuk, admin akan follow up data akun produk bila diperlukan.',
                'Kalau mau lihat paket lain, ketik nama produknya lagi.',
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $selectedOption
     */
    private function buildAccountPromptMessage(string $senderName, array $product, array $selectedOption): string
    {
        $accountFieldLabel = (string) ($selectedOption['accountFieldLabel'] ?? 'Email / username akun');
        $productName = (string) ($product['product_name'] ?? 'produk ini');
        $accountFieldExample = $this->buildAccountFieldExample($accountFieldLabel);

        return $this->lyvaflow->composeStructuredMessage(
            'Data Akun '.$productName,
            [
                'Halo '.$senderName.',',
                'Kamu memilih paket '.($selectedOption['label'] ?? $productName).'.',
                'Sebelum invoice dibuat, kirim dulu data akun yang mau diproses.',
            ],
            [
                [
                    'title' => 'Kirim Balasan',
                    'style' => 'plain',
                    'lines' => [
                        $accountFieldLabel.': '.$accountFieldExample,
                    ],
                ],
            ],
            [
                'Setelah data akun kamu kirim, sistem akan langsung buat invoice QRIS.',
                'Ketik 0 atau batal kalau ingin membatalkan.',
                'Balas dalam 30 menit supaya sesi pembelian tetap aktif.',
                $this->buildRestartInstruction($productName, 'mulai lagi'),
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $selectedOption
     */
    private function buildInvalidAccountPromptMessage(string $senderName, array $product, array $selectedOption): string
    {
        $accountFieldLabel = (string) ($selectedOption['accountFieldLabel'] ?? 'Email / username akun');
        $productName = (string) ($product['product_name'] ?? 'Produk');
        $accountFieldExample = $this->buildAccountFieldExample($accountFieldLabel);

        return $this->lyvaflow->composeStructuredMessage(
            'Data Akun '.$productName.' Belum Valid',
            [
                'Halo '.$senderName.',',
                'Format data akun yang kamu kirim belum bisa diproses.',
            ],
            [
                [
                    'title' => 'Coba Kirim Lagi',
                    'style' => 'plain',
                    'lines' => [
                        $accountFieldLabel.': '.$accountFieldExample,
                    ],
                ],
            ],
            [
                'Kalau ingin ganti pilihan paket, ketik nama produknya lagi.',
                'Balas dalam 30 menit supaya sesi pembelian tetap aktif.',
                $this->buildRestartInstruction($productName, 'buka pilihan lagi'),
            ],
        );
    }

    private function buildExpiredFollowUpMessage(string $senderName, string $productName): string
    {
        return $this->lyvaflow->composeStructuredMessage(
            'Sesi Pembelian '.$productName.' Sudah Habis',
            [
                'Halo '.$senderName.',',
                'Balasan kamu kami terima, tapi sesi pembelian sebelumnya sudah lewat lebih dari 30 menit.',
            ],
            [
                [
                    'title' => 'Lanjutkan Lagi',
                    'style' => 'plain',
                    'lines' => [
                        $this->buildDirectRestartInstruction($productName),
                    ],
                ],
            ],
            [
                'Kalau kamu mau beli lagi, kirim nama produk dari awal supaya sistem buka sesi baru.',
            ],
        );
    }

    private function buildCancelMessage(string $senderName): string
    {
        return $this->lyvaflow->composeStructuredMessage(
            'Pilihan Dibatalkan',
            [
                'Halo '.$senderName.',',
                'Pilihan paket produk kamu sudah dibatalkan.',
            ],
            [],
            [
                'Kalau mau lihat daftar paket lagi, cukup ketik nama produknya.',
            ],
        );
    }

    private function buildProductCatalogMessage(string $senderName): string
    {
        $entertainmentProducts = [];
        $pulsaProducts = [];
        $walletProducts = [];

        foreach (self::PRODUCT_CATALOG as $product) {
            $productName = trim((string) ($product['product_name'] ?? ''));

            if ($productName === '') {
                continue;
            }

            if (Str::startsWith($productName, 'Pulsa ')) {
                $pulsaProducts[] = $productName;
                continue;
            }

            if (in_array($productName, ['DANA', 'GoPay', 'OVO', 'LinkAja', 'ShopeePay', 'DOKU', 'BRI Brizzi', 'TapCash BNI', 'Mandiri E-Toll', 'Grab', 'Maxim'], true)) {
                $walletProducts[] = $productName;
                continue;
            }

            $entertainmentProducts[] = $productName;
        }

        return $this->lyvaflow->composeStructuredMessage(
            'Daftar Produk Lyva',
            [
                'Halo '.$senderName.',',
                'Ini daftar produk yang bisa kamu pesan lewat WhatsApp sekarang.',
            ],
            [
                [
                    'title' => 'Entertainment & Premium',
                    'style' => 'plain',
                    'lines' => $entertainmentProducts,
                ],
                [
                    'title' => 'Pulsa',
                    'style' => 'plain',
                    'lines' => $pulsaProducts,
                ],
                [
                    'title' => 'E-Wallet & Saldo',
                    'style' => 'plain',
                    'lines' => $walletProducts,
                ],
            ],
            [
                'Ketik nama produk yang kamu mau untuk lihat daftar paket.',
                'Contoh: ChatGPT, iQIYI, Telkomsel, DANA, atau GoPay.',
            ],
        );
    }

    private function buildUnavailableProductMessage(string $senderName, string $productName): string
    {
        return $this->lyvaflow->composeStructuredMessage(
            $productName.' Belum Siap Ditampilkan',
            [
                'Halo '.$senderName.',',
                'Paket untuk '.$productName.' belum berhasil dimuat saat ini.',
            ],
            [],
            [
                'Coba kirim lagi nama produknya beberapa saat lagi.',
                'Kalau masih sama, kirim kata produk yang lain atau hubungi admin untuk cek stok/provider.',
            ],
        );
    }

    private function buildMissingStateMessage(string $senderName): string
    {
        return $this->lyvaflow->composeStructuredMessage(
            'Sesi Pembelian Belum Aktif',
            [
                'Halo '.$senderName.',',
                'Balasan kamu masuk, tapi belum ada sesi pilihan paket yang aktif.',
            ],
            [],
            [
                'Ketik nama produk dulu, misalnya ChatGPT, iQIYI, Netflix, DANA, atau Telkomsel.',
                'Setelah menu paket muncul, baru balas angka sesuai nomor paket yang kamu pilih.',
            ],
        );
    }

    private function buildTemporaryErrorMessage(string $senderName): string
    {
        return $this->lyvaflow->composeStructuredMessage(
            'Sistem Sedang Sibuk',
            [
                'Halo '.$senderName.',',
                'Permintaan kamu sudah masuk, tapi sistem kami sedang lambat saat mengambil data produk atau membuat invoice.',
            ],
            [],
            [
                'Coba kirim lagi pesan yang sama beberapa saat lagi.',
                'Kalau sudah muncul daftar paket, lanjut balas angka seperti biasa.',
            ],
        );
    }

    private function buildRestartInstruction(string $productName, string $action): string
    {
        $normalizedProductName = trim($productName) !== '' ? trim($productName) : 'produk ini';
        $lowerProductName = Str::lower($normalizedProductName);

        return 'Kalau lewat 30 menit, ketik ulang '.$normalizedProductName.' atau beli '.$lowerProductName.' untuk '.$action.'.';
    }

    private function buildDirectRestartInstruction(string $productName): string
    {
        $normalizedProductName = trim($productName) !== '' ? trim($productName) : 'produk ini';
        $lowerProductName = Str::lower($normalizedProductName);

        return 'Ketik '.$normalizedProductName.' atau beli '.$lowerProductName.' untuk buka pilihan paket lagi.';
    }

    private function buildAccountFieldExample(string $accountFieldLabel): string
    {
        $label = Str::lower(trim($accountFieldLabel));

        if (Str::contains($label, ['nomor hp', 'nomor tujuan', 'no hp', 'nomor whatsapp', 'phone', 'telepon', 'nomor'])) {
            return 'contoh 081234567890';
        }

        if (Str::contains($label, ['user id', 'userid', 'id pelanggan', 'id akun'])) {
            return 'contoh 123456789';
        }

        if (Str::contains($label, ['email'])) {
            return 'contoh akun@email.com';
        }

        if (Str::contains($label, ['username', 'user name'])) {
            return 'contoh usernamekamu';
        }

        return 'contoh akun@email.com';
    }

    private function matchProductIntent(string $text): ?array
    {
        $value = Str::lower(trim($text));
        $compact = str_replace(' ', '', $value);
        $normalized = preg_replace('/\s+/', ' ', $value) ?? $value;

        foreach (self::PRODUCT_CATALOG as $product) {
            $keywords = collect($product['keywords'] ?? [])
                ->filter(fn (mixed $keyword) => is_string($keyword) && trim($keyword) !== '')
                ->map(fn (string $keyword) => Str::lower(trim($keyword)))
                ->values()
                ->all();

            foreach ($keywords as $keyword) {
                $compactKeyword = str_replace(' ', '', $keyword);

                if (Str::contains($value, [$keyword]) || Str::contains($compact, $compactKeyword)) {
                    return $product;
                }

                $pattern = '/\b(?:beli|buy|mau beli|mau order|order|pesan|harga|info|minat|ingin beli)\b.*'.preg_quote($keyword, '/').'/i';

                if (preg_match($pattern, $normalized) === 1) {
                    return $product;
                }
            }
        }

        return null;
    }

    private function wantsProductCatalog(string $text): bool
    {
        $value = Str::lower(trim($text));

        if ($value === '') {
            return false;
        }

        return Str::contains($value, [
            'list produk',
            'daftar produk',
            'menu produk',
            'produk apa aja',
            'produk apa saja',
            'ada produk apa',
            'ada apa aja',
            'produk yang ada',
        ]);
    }

    private function isCancelMessage(string $text): bool
    {
        return in_array(Str::lower(trim($text)), ['0', 'batal', 'cancel'], true);
    }

    private function looksLikeCommerceFollowUp(string $text): bool
    {
        $normalized = Str::lower(trim($text));

        if ($normalized === '') {
            return false;
        }

        return preg_match('/^\d{1,2}$/', $normalized) === 1
            || in_array($normalized, ['0', 'batal', 'cancel'], true)
            || Str::contains($normalized, '@');
    }

    /**
     * @param  array<string, mixed>|null  $state
     */
    private function findCachedState(string $jid, string $phoneNumber): ?array
    {
        foreach ($this->stateIdentifiers($jid, $phoneNumber) as $identifier) {
            $cachedState = Cache::get($this->cacheKey($identifier));

            if (is_array($cachedState)) {
                return $cachedState;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $state
     * @param  array<string, mixed>|null  $existingState
     * @return array<string, mixed>
     */
    private function storeCachedState(array $state, string $jid, string $phoneNumber, ?array $existingState): array
    {
        $replyJid = $this->resolveReplyJid($jid, $existingState);
        $replyPhoneNumber = $this->resolveReplyPhoneNumber($phoneNumber, $existingState);

        $state['jid'] = $replyJid;
        $state['phone_number'] = $replyPhoneNumber;
        $state['cache_identifiers'] = $this->stateIdentifiers($replyJid, $replyPhoneNumber, $existingState);

        foreach ($state['cache_identifiers'] as $identifier) {
            Cache::put($this->cacheKey($identifier), $state, now()->addMinutes(self::MENU_CACHE_MINUTES));
        }

        return $state;
    }

    /**
     * @param  array<string, mixed>|null  $state
     */
    private function forgetCachedState(string $jid, string $phoneNumber, ?array $state = null): void
    {
        foreach ($this->stateIdentifiers($jid, $phoneNumber, $state) as $identifier) {
            Cache::forget($this->cacheKey($identifier));
        }
    }

    /**
     * @param  array<string, mixed>|null  $state
     */
    private function findFollowUpNotice(string $jid, string $phoneNumber, ?array $state = null): string
    {
        foreach ($this->stateIdentifiers($jid, $phoneNumber, $state) as $identifier) {
            $productName = trim((string) Cache::get($this->followUpNoticeCacheKey($identifier), ''));

            if ($productName !== '') {
                return $productName;
            }
        }

        return '';
    }

    /**
     * @param  array<string, mixed>|null  $state
     */
    private function storeFollowUpNotice(string $jid, string $phoneNumber, string $productName, ?array $state = null): void
    {
        $cleanProductName = trim($productName) !== '' ? trim($productName) : 'produk ini';

        foreach ($this->stateIdentifiers($jid, $phoneNumber, $state) as $identifier) {
            Cache::put(
                $this->followUpNoticeCacheKey($identifier),
                $cleanProductName,
                now()->addMinutes(self::FOLLOW_UP_NOTICE_MINUTES),
            );
        }
    }

    /**
     * @param  array<string, mixed>|null  $state
     */
    private function forgetFollowUpNotice(string $jid, string $phoneNumber, ?array $state = null): void
    {
        foreach ($this->stateIdentifiers($jid, $phoneNumber, $state) as $identifier) {
            Cache::forget($this->followUpNoticeCacheKey($identifier));
        }
    }

    /**
     * @param  array<string, mixed>|null  $state
     * @return array<int, string>
     */
    private function stateIdentifiers(string $jid, string $phoneNumber, ?array $state = null): array
    {
        $identifiers = [];

        $this->appendIdentifier($identifiers, 'jid', $jid);
        $this->appendIdentifier($identifiers, 'phone', $phoneNumber);

        if (is_array($state)) {
            foreach (($state['cache_identifiers'] ?? []) as $identifier) {
                if (is_string($identifier) && trim($identifier) !== '') {
                    $identifiers[$identifier] = $identifier;
                }
            }

            $this->appendIdentifier($identifiers, 'jid', (string) ($state['jid'] ?? ''));
            $this->appendIdentifier($identifiers, 'phone', (string) ($state['phone_number'] ?? ''));
        }

        return array_values($identifiers);
    }

    /**
     * @param  array<string, string>  $identifiers
     */
    private function appendIdentifier(array &$identifiers, string $type, string $value): void
    {
        $normalizedValue = $type === 'phone'
            ? $this->lyvaflow->normalizeWhatsappNumber($value)
            : trim($value);

        if ($normalizedValue === '') {
            return;
        }

        $identifier = $type.':'.$normalizedValue;
        $identifiers[$identifier] = $identifier;
    }

    /**
     * @param  array<string, mixed>|null  $state
     */
    private function resolveReplyJid(string $jid, ?array $state = null): string
    {
        $currentJid = trim($jid);

        if ($currentJid !== '') {
            return $currentJid;
        }

        return trim((string) ($state['jid'] ?? ''));
    }

    /**
     * @param  array<string, mixed>|null  $state
     */
    private function resolveReplyPhoneNumber(string $phoneNumber, ?array $state = null): string
    {
        $normalizedPhoneNumber = $this->lyvaflow->normalizeWhatsappNumber($phoneNumber);

        if ($normalizedPhoneNumber !== '') {
            return $normalizedPhoneNumber;
        }

        return $this->lyvaflow->normalizeWhatsappNumber((string) ($state['phone_number'] ?? ''));
    }

    private function cacheKey(string $identifier): string
    {
        return self::MENU_CACHE_PREFIX.$identifier;
    }

    private function followUpNoticeCacheKey(string $identifier): string
    {
        return self::FOLLOW_UP_NOTICE_CACHE_PREFIX.$identifier;
    }

    /**
     * @param  array<string, mixed>  $option
     */
    private function resolvePrimaryAccountFieldLabel(array $option): string
    {
        $field = collect($option['accountFields'] ?? [])
            ->first(fn (mixed $item) => is_array($item) && filled($item['label'] ?? null));

        return trim((string) ($field['label'] ?? 'Email / username akun'));
    }

    /**
     * @param  array<string, mixed>  $selectedOption
     */
    private function resolveSummaryAccountLabel(array $selectedOption): string
    {
        return trim((string) ($selectedOption['accountFieldLabel'] ?? 'Email / username akun'));
    }

    private function normalizeAccountInput(string $text): ?string
    {
        $value = trim($text);

        if ($value === '' || mb_strlen($value) < 4) {
            return null;
        }

        if (mb_strlen($value) > 190) {
            return null;
        }

        return $value;
    }

    private function reply(string $jid, string $phoneNumber, string $message, array $options = []): void
    {
        $typingDurationMs = $this->resolveReplyDelayMs($message, $options);

        $this->lyvaflow->sendWhatsappMessage($phoneNumber, $message, [
            'jid' => $jid,
            'qrString' => (string) ($options['qrString'] ?? ''),
            'simulateTyping' => $typingDurationMs > 0,
            'typingDurationMs' => $typingDurationMs,
        ]);
    }

    /**
     * @param  array<string, mixed>  $options
     */
    private function resolveReplyDelayMs(string $message, array $options = []): int
    {
        if (! (bool) config('services.lyvaflow.reply_delay_enabled', true)) {
            return 0;
        }

        $minDelayMs = max(0, (int) config('services.lyvaflow.reply_delay_base_min_ms', 2200));
        $maxDelayMs = max($minDelayMs, (int) config('services.lyvaflow.reply_delay_base_max_ms', 4200));
        $longMessageBonusMs = max(0, (int) config('services.lyvaflow.reply_delay_long_message_bonus_ms', 2200));
        $qrBonusMs = max(0, (int) config('services.lyvaflow.reply_delay_qr_bonus_ms', 1800));
        $absoluteMaxDelayMs = max($maxDelayMs, (int) config('services.lyvaflow.reply_delay_max_ms', 9500));
        $messageLength = mb_strlen(trim($message));
        $lineCount = substr_count($message, "\n") + 1;
        $hasQr = trim((string) ($options['qrString'] ?? '')) !== '';
        $replyType = trim((string) ($options['replyType'] ?? ''));

        if ($messageLength >= 350 || $lineCount >= 12) {
            $minDelayMs += (int) ($longMessageBonusMs * 0.6);
            $maxDelayMs += $longMessageBonusMs;
        } elseif ($messageLength >= 160 || $lineCount >= 7) {
            $minDelayMs += 700;
            $maxDelayMs += 1400;
        }

        if ($hasQr) {
            $minDelayMs += 900;
            $maxDelayMs += $qrBonusMs;
        }

        [$typeMinBonusMs, $typeMaxBonusMs] = $this->resolveReplyTypeDelayBonus($replyType);

        if ($typeMinBonusMs > 0 || $typeMaxBonusMs > 0) {
            $minDelayMs += $typeMinBonusMs;
            $maxDelayMs += $typeMaxBonusMs;
        }

        if ($maxDelayMs < $minDelayMs) {
            $maxDelayMs = $minDelayMs;
        }

        $maxDelayMs = min($maxDelayMs, $absoluteMaxDelayMs);
        $minDelayMs = min($minDelayMs, $maxDelayMs);

        return random_int($minDelayMs, $maxDelayMs);
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function resolveReplyTypeDelayBonus(string $replyType): array
    {
        return match ($replyType) {
            'menu' => [500, 1300],
            'prompt' => [300, 900],
            'invalid' => [200, 700],
            'expired' => [300, 800],
            'invoice' => [900, 1800],
            'cancel' => [0, 300],
            default => [0, 0],
        };
    }

    private function formatRupiah(int $amount): string
    {
        return 'Rp'.number_format($amount, 0, ',', '.');
    }
}
