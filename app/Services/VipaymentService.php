<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class VipaymentService
{
    private const PUBLIC_PRODUCT_AVAILABILITY_CACHE_KEY = 'vipayment.public-unavailable-product-ids.v2';

    /**
     * @var array<int, string>
     */
    private const PUBLIC_VIPAYMENT_PRODUCT_IDS = [
        'mobile-legends-a',
        'mobile-legends-b',
        'mobile-legends-gift-skin',
        'mobile-legends-global',
        'bigo-live',
        'genshin-impact',
        'honor-of-kings',
        'zenless-zone-zero',
        'delta-force',
        'crystal-of-atlan',
        'solo-leveling-arise',
        'wuthering-waves',
        'mobile-legends-login',
        'free-fire-login',
        'hok-login',
        'pubgm-login',
        'blood-strike-login',
        'steam-wallet',
        'google-play',
        'apple-store',
        'razer-gold',
        'ps-store',
        'telkomsel',
        'indosat',
        'xl',
        'tri',
        'smartfren',
        'afk-journey',
        'hero-reborn',
        'isekai-feast',
        'free-fire-global',
        'ace-racer',
        'wuxia-rising',
        'astra-knights',
        'eggy-party',
        'arena-of-valor',
    ];

    /**
     * @var array<string, string>
     */
    private const PUBLIC_PRODUCT_LABELS = [
        'mobile-legends-a' => 'Mobile Legends A',
        'mobile-legends-b' => 'Mobile Legends B',
        'mobile-legends-gift-skin' => 'Mobile Legends Gift Skin',
        'mobile-legends-global' => 'Mobile Legends Global',
        'bigo-live' => 'Bigo Live Diamonds',
        'genshin-impact' => 'Genshin Impact',
        'honor-of-kings' => 'Honor of Kings',
        'zenless-zone-zero' => 'Zenless Zone Zero',
        'delta-force' => 'Delta Force',
        'crystal-of-atlan' => 'Crystal of Atlan',
        'solo-leveling-arise' => 'Solo Leveling: Arise',
        'wuthering-waves' => 'Wuthering Waves',
        'mobile-legends-login' => 'Mobile Legends Login',
        'free-fire-login' => 'Free Fire Login',
        'hok-login' => 'Honor of Kings Login',
        'pubgm-login' => 'PUBG Mobile Login',
        'blood-strike-login' => 'Blood Strike Login',
        'steam-wallet' => 'Steam Wallet IDR',
        'google-play' => 'Google Play Gift Code',
        'apple-store' => 'Apple App Store',
        'razer-gold' => 'Razer Gold',
        'ps-store' => 'PlayStation Store',
        'telkomsel' => 'Telkomsel Pulsa',
        'indosat' => 'Indosat IM3',
        'xl' => 'XL Axiata',
        'tri' => 'Tri Indonesia',
        'smartfren' => 'Smartfren',
        'afk-journey' => 'AFK Journey',
        'hero-reborn' => 'Hero Reborn Eternal Pact - Razer Link',
        'isekai-feast' => 'Isekai Feast: Tales of Recipes',
        'free-fire-global' => 'Free Fire Global',
        'ace-racer' => 'Ace Racer',
        'wuxia-rising' => 'Wuxia Rising Star - Razer Link',
        'astra-knights' => 'Astra: Knights of Veda',
        'eggy-party' => 'Eggy Party',
        'arena-of-valor' => 'Arena of Valor',
    ];

    public function __construct(
        private readonly SiteSettingService $settings,
    ) {}

    /**
     * @return array<int, string>
     */
    public function unavailablePublicProductIds(): array
    {
        if (! $this->configured()) {
            return [];
        }

        return Cache::remember(
            self::PUBLIC_PRODUCT_AVAILABILITY_CACHE_KEY.'.'.md5(json_encode([
                'tier' => config('vipayment.price_tier', 'basic'),
                'pricing' => $this->pricingCacheFingerprint(),
                'version' => '2026-03-29-public-availability-v2',
            ])),
            now()->addMinutes(max(10, (int) config('vipayment.cache_ttl', 10))),
            function () {
                return collect(self::PUBLIC_VIPAYMENT_PRODUCT_IDS)
                    ->filter(function (string $productId) {
                        try {
                            $mapping = $this->resolveMapping($productId);

                            if ($mapping === null) {
                                return true;
                            }

                            $services = $this->getProductServices($productId);

                            return ! is_array($services) || $services === [];
                        } catch (\Throwable $exception) {
                            report($exception);

                            return true;
                        }
                    })
                    ->values()
                    ->all();
            },
        );
    }

    public function isProductPubliclyAvailable(string $productId): bool
    {
        if (! in_array($productId, self::PUBLIC_VIPAYMENT_PRODUCT_IDS, true)) {
            return true;
        }

        return ! in_array($productId, $this->unavailablePublicProductIds(), true);
    }

    /**
     * @return array<int, array{id: string, name: string, reason: string}>
     */
    public function unavailablePublicProductsReport(): array
    {
        return collect($this->unavailablePublicProductIds())
            ->map(fn (string $productId) => [
                'id' => $productId,
                'name' => self::PUBLIC_PRODUCT_LABELS[$productId] ?? Str::headline(str_replace('-', ' ', $productId)),
                'reason' => 'Layanan aktif dari VIPPayment sedang kosong atau mapping produk tidak tersedia.',
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{id: string, label: string, placeholder: string, inputType?: string, required?: bool}>
     */
    private function inferAccountFields(string $endpoint, string $productId, array $service): array
    {
        $makeField = static fn (string $id, string $label, string $placeholder, string $inputType = 'text', bool $required = true): array => [
            'id' => $id,
            'label' => $label,
            'placeholder' => $placeholder,
            'inputType' => $inputType,
            'required' => $required,
        ];

        $detailsText = collect($this->extractServiceDetails((string) ($service['description'] ?? $service['note'] ?? '')))
            ->push((string) ($service['description'] ?? ''))
            ->push((string) ($service['note'] ?? ''))
            ->implode(' ');

        $productContext = Str::of($productId)->replace(['-', '_'], ' ')->lower()->value();
        $value = Str::lower(implode(' ', array_filter([
            $productContext,
            (string) ($service['game'] ?? ''),
            (string) ($service['name'] ?? ''),
            (string) ($service['description'] ?? ''),
            (string) ($service['note'] ?? ''),
            $detailsText,
        ])));
        $mentionsExplicitInputRequest = Str::contains($value, [
            'masukan',
            'masukkan',
            'saat order',
            'gmail tujuan',
            'email tujuan',
            'invite email',
            'via invite email',
            'alamat email',
            'data username',
            'username dan password',
            'email dan password',
            'user id',
            'uid',
        ]);

        if ($endpoint === 'prepaid') {
            if (Str::contains($value, ['pln', 'token listrik', 'id pelanggan', 'nomor meter', 'meteran listrik'])) {
                return [$makeField('target-number', 'Nomor meter / ID pelanggan', 'Masukkan nomor meter atau ID pelanggan')];
            }

            if (Str::contains($value, ['k-vision', 'k vision', 'nex parabola', 'orange tv'])) {
                return [$makeField('account-number', 'Nomor pelanggan / smart card', 'Masukkan nomor pelanggan atau smart card')];
            }

            if (Str::contains($value, ['brizzi', 'brizi', 'tapcash', 'flazz', 'e-toll', 'etoll', 'e-money', 'emoney'])) {
                return [$makeField('account-number', 'Nomor kartu', 'Masukkan nomor kartu')];
            }

            if (Str::contains($value, ['doku'])) {
                return [$makeField('account-number', 'DOKU ID', 'Masukkan DOKU ID')];
            }

            if (Str::contains($value, ['razer gold'])) {
                return [$makeField('account-user-id', 'User ID / email akun', 'Masukkan user ID atau email akun')];
            }

            if (Str::contains($value, ['tix id'])) {
                return [$makeField('account-number', 'Nomor HP akun TIX ID', '08xxxxxxxxxx')];
            }

            if (Str::contains($value, ['likee'])) {
                return [$makeField('account-number', 'ID Likee', 'Masukkan ID Likee')];
            }

            if (Str::contains($value, ['maxim customer', 'id maxim', 'maxim id'])) {
                return [$makeField('account-number', 'ID Maxim Customer', 'Masukkan ID Maxim Customer')];
            }

            if (Str::contains($value, ['grab driver'])) {
                return [$makeField('account-number', 'Nomor HP akun Grab Driver', '08xxxxxxxxxx')];
            }

            if (Str::contains($value, ['grab penumpang', 'grab customer', 'grab'])) {
                return [$makeField('account-number', 'Nomor HP akun Grab', '08xxxxxxxxxx')];
            }

            if (Str::contains($value, ['alfamart voucher', 'voucher alfamart', 'indomaret', 'voucher indomaret'])) {
                return [$makeField('account-number', 'Nomor akun / nomor HP', 'Masukkan nomor akun atau nomor HP')];
            }

            if (Str::contains($value, ['dana', 'gopay', 'go pay', 'ovo', 'shopeepay', 'shopee pay', 'linkaja', 'sakuku', 'isaku', 'paypal'])) {
                return [$makeField('account-number', 'Nomor akun / nomor HP', '08xxxxxxxxxx')];
            }

            if (Str::contains($value, ['globe', 'starhub', 'sun telecom', 'philippines smart', 'smart 30', 'smart 50', 'xox', 'vietnam topup'])) {
                return [$makeField('target-number', 'Nomor tujuan', 'Masukkan nomor tujuan')];
            }

            return [$makeField('target-number', 'Nomor tujuan', '08xxxxxxxxxx')];
        }

        $isGiftVariant = Str::contains($productContext, 'gift');
        $mentionsEmail = Str::contains($value, ['email', 'e-mail', 'invite email', 'gmail', 'g-mail']);
        $mentionsNoPassword = Str::contains($value, ['tanpa password', 'without password']);
        $mentionsPassword = Str::contains($value, ['password']) && ! $mentionsNoPassword;
        $mentionsUsername = Str::contains($value, ['username', 'user name', 'akun login', 'login akun', 'email / username', 'email/username', 'login id']);
        $isAccountStyle = Str::contains($value, ['chatgpt', 'capcut', 'canva', 'netflix', 'spotify', 'youtube', 'prime video', 'bstation', 'iqiyi', 'alight motion']);
        $isEmailOnlyAccountStyle = Str::contains($value, ['youtube premium', 'youtube', 'iqiyi', 'gmail tujuan', 'invite gmail', 'invite via gmail']);
        $isLoginStyle = ! $isAccountStyle && ($mentionsUsername || $mentionsPassword);
        $isGiftStyle = $isGiftVariant || Str::contains($value, ['gift']);
        $optionalZone = Str::contains($value, [
            'server (jika dibutuhkan)',
            'server jika dibutuhkan',
            'server bila dibutuhkan',
            'jika dibutuhkan',
            'jika perlu',
            'if needed',
            'if required',
            'optional zone',
            'zone optional',
        ]);
        $requiresZoneByProduct = Str::contains($productContext, [
            'mobile legends',
            'mobile legend',
            'mlbb',
            'pubg mobile',
        ]);
        $requiresZoneByInstruction = Str::contains($value, [
            'user id dan server',
            'user id & server',
            'id dan server',
            'server / zone',
            'server/zone',
            'zone id',
            'id zone',
        ]);
        $requiresZone = ! $isAccountStyle && ($requiresZoneByProduct || ($requiresZoneByInstruction && ! $optionalZone));

        if (Str::contains($value, ['likee'])) {
            return [$makeField('account-user-id', 'ID Likee', 'Masukkan ID Likee')];
        }

        if (Str::contains($productContext, ['steam wallet', 'steam wallet code'])) {
            return [$makeField('account-username', 'Username / email Steam', 'Masukkan username atau email Steam')];
        }

        if (Str::contains($productContext, ['voucher psn', 'psn'])) {
            return [$makeField('account-username', 'Online ID / email PSN', 'Masukkan Online ID atau email PSN')];
        }

        if (Str::contains($productContext, ['voucher roblox'])) {
            return [$makeField('account-username', 'Username Roblox', 'Masukkan username Roblox')];
        }

        if (Str::contains($productContext, ['roblox via login'])) {
            return [
                $makeField('account-username', 'Email / username akun', 'Masukkan email atau username akun'),
                $makeField('account-password', 'Password akun', 'Masukkan password akun', 'password'),
                $makeField('account-security-code', '5 kode keamanan Roblox', 'Masukkan 5 kode keamanan Roblox jika ada', 'text', false),
            ];
        }

        if (
            Str::contains($productContext, [
                'amazon prime video',
                'bstation premium',
                'iqiyi premium',
                'netflix',
                'spotify',
                'vidio premier',
                'vision plus',
                'viu premium',
                'wetv premium',
            ])
            && ! $mentionsExplicitInputRequest
        ) {
            return [];
        }

        if (Str::contains($value, ['riot cash']) || Str::contains($productContext, ['voucher valorant'])) {
            return [$makeField('account-username', 'Riot ID / username', 'Masukkan Riot ID atau username')];
        }

        if (Str::contains($productContext, ['garena shell'])) {
            return [$makeField('account-user-id', 'User ID Garena', 'Masukkan user ID Garena')];
        }

        if (Str::contains($productContext, ['megaxus'])) {
            return [$makeField('account-user-id', 'User ID Megaxus', 'Masukkan user ID Megaxus')];
        }

        if (Str::contains($productContext, ['pb zepetto', 'zepetto'])) {
            return [$makeField('account-user-id', 'User ID Zepetto', 'Masukkan user ID Zepetto')];
        }

        if (Str::contains($productContext, ['genshin impact', 'honkai star rail', 'honkai impact', 'zenless zone zero', 'zzz'])) {
            $fields = [$makeField('account-user-id', 'UID', 'Masukkan UID')];

            if ($requiresZone) {
                $fields[] = $makeField('account-zone', 'Server / zone', 'Masukkan server atau zone');
            }

            return $fields;
        }

        if (Str::contains($productContext, ['free fire', 'free-fire'])) {
            return [$makeField('account-user-id', 'Player ID', 'Masukkan Player ID')];
        }

        if (Str::contains($productContext, ['honor of kings'])) {
            return [$makeField('account-user-id', 'Player ID', 'Masukkan Player ID')];
        }

        if (Str::contains($productContext, ['call of duty mobile', 'codm'])) {
            return [$makeField('account-user-id', 'Open ID / UID', 'Masukkan Open ID atau UID')];
        }

        if (Str::contains($productContext, ['growtopia'])) {
            return [$makeField('account-user-id', 'GrowID', 'Masukkan GrowID')];
        }

        if (Str::contains($productContext, ['arena of valor'])) {
            return [$makeField('account-user-id', 'Player ID', 'Masukkan Player ID')];
        }

        if (Str::contains($productContext, ['pubg mobile', 'pubg new state'])) {
            $fields = [$makeField('account-user-id', 'Character ID', 'Masukkan Character ID')];

            if ($requiresZone) {
                $fields[] = $makeField('account-zone', 'Server / zone', 'Masukkan server atau zone');
            }

            return $fields;
        }

        if (Str::contains($productContext, ['point blank', 'pb cash'])) {
            return [$makeField('account-user-id', 'PB ID', 'Masukkan PB ID')];
        }

        if (Str::contains($productContext, ['arena breakout'])) {
            return [$makeField('account-user-id', 'Player ID', 'Masukkan Player ID')];
        }

        if (Str::contains($productContext, ['delta force'])) {
            return [$makeField('account-user-id', 'Player ID', 'Masukkan Player ID')];
        }

        if (Str::contains($productContext, ['blood strike'])) {
            return [$makeField('account-user-id', 'User ID', 'Masukkan User ID')];
        }

        if ($isAccountStyle && ! $isLoginStyle) {
            $fields = [$makeField('account-email', 'Email akun', 'Masukkan email akun', 'email')];

            if (! $mentionsEmail && ! $mentionsPassword && ! $isEmailOnlyAccountStyle) {
                $fields[0]['label'] = 'Email / username akun';
                $fields[0]['placeholder'] = 'Masukkan email atau username akun';
                $fields[0]['inputType'] = 'text';
            }

            if ($mentionsPassword) {
                $fields[] = $makeField('account-password', 'Password akun', 'Masukkan password akun', 'password');
            }

            return $fields;
        }

        if ($isLoginStyle) {
            $fields = [$makeField('account-username', 'Email / username akun', 'Masukkan email atau username akun')];

            if (! $mentionsNoPassword) {
                $fields[] = $makeField('account-password', 'Password akun', 'Masukkan password akun', 'password');
            }

            if ($requiresZone) {
                $fields[] = $makeField('account-zone', 'Server / zone', 'Masukkan server atau zone');
            }

            return $fields;
        }

        if ($mentionsEmail) {
            $fields = [$makeField('account-email', 'Email akun', 'Masukkan email akun', 'email')];

            if ($mentionsPassword) {
                $fields[] = $makeField('account-password', 'Password akun', 'Masukkan password akun', 'password');
            }

            return $fields;
        }

        $primaryLabel = 'User ID';
        $primaryPlaceholder = 'Masukkan user ID';

        if ($isAccountStyle) {
            $primaryLabel = 'Email / username akun';
            $primaryPlaceholder = 'Masukkan email atau username akun';
        } elseif ($isGiftStyle) {
            $primaryLabel = 'User ID penerima';
            $primaryPlaceholder = 'Masukkan user ID penerima';
        }

        $fields = [$makeField('account-user-id', $primaryLabel, $primaryPlaceholder)];

        if ($requiresZone) {
            $fields[] = $makeField('account-zone', 'Server / zone', 'Masukkan server atau zone');
        }

        return $fields;
    }

    /**
     * @return array<int, array{id: string, name: string, categoryId: string, categoryTitle: string, badge?: string|null}>
     */
    public function getCatalogProducts(): array
    {
        if (! $this->configured()) {
            throw new RuntimeException('VIPayment belum dikonfigurasi.');
        }

        return collect($this->getCatalogEntries())
            ->map(fn (array $entry) => $this->publicCatalogProduct($entry))
            ->values()
            ->all();
    }

    /**
     * @return array{id: string, name: string, categoryId: string, categoryTitle: string, badge?: string|null}|null
     */
    public function findCatalogProduct(string $productId): ?array
    {
        if (! $this->configured()) {
            return null;
        }

        $entry = collect($this->getCatalogEntries())->firstWhere('id', $productId);

        return $entry ? $this->publicCatalogProduct($entry) : null;
    }

    public function configured(): bool
    {
        return filled(config('vipayment.api_id')) && filled(config('vipayment.api_key'));
    }

    public function resolveOrderEndpointForProduct(string $productId): ?string
    {
        if (! $this->configured()) {
            return null;
        }

        $mapping = $this->resolveMapping($productId);

        return $mapping['endpoint'] ?? null;
    }

    public function supportsNicknameLookup(string $productId): bool
    {
        return $this->resolveNicknameLookupCode($productId) !== null;
    }

    public function nicknameLookupConfig(string $productId): array
    {
        $lookupCode = $this->resolveNicknameLookupCode($productId);

        return [
            'enabled' => $lookupCode !== null,
            'requiresZone' => $lookupCode !== null ? $this->nicknameLookupRequiresZone($lookupCode) : false,
            'targetFieldId' => 'account-user-id',
            'zoneFieldId' => 'account-zone',
            'buttonLabel' => 'Cek username',
        ];
    }

    public function lookupGameNickname(string $productId, string $target, ?string $zone = null): ?string
    {
        if (! $this->configured()) {
            throw new RuntimeException('VIPayment belum dikonfigurasi.');
        }

        $lookupCode = $this->resolveNicknameLookupCode($productId);

        if ($lookupCode === null) {
            return null;
        }

        $normalizedTarget = trim($target);
        $normalizedZone = trim((string) $zone);

        if ($normalizedTarget === '') {
            throw new RuntimeException('User ID wajib diisi terlebih dulu.');
        }

        if ($this->nicknameLookupRequiresZone($lookupCode) && $normalizedZone === '') {
            throw new RuntimeException('Zone / server wajib diisi untuk produk ini.');
        }

        $cacheKey = 'vipayment.nickname.'.md5(json_encode([
            'product' => $productId,
            'code' => $lookupCode,
            'target' => $normalizedTarget,
            'zone' => $normalizedZone,
        ]));

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($lookupCode, $normalizedTarget, $normalizedZone) {
            $payload = [
                'type' => 'get-nickname',
                'code' => $lookupCode,
                'target' => $normalizedTarget,
            ];

            if ($normalizedZone !== '') {
                $payload['additional_target'] = $normalizedZone;
            }

            $responsePayload = $this->sendRequest('game-feature', $payload, swallowServiceNotFound: true);
            $nickname = $responsePayload['data'] ?? null;

            if (! is_string($nickname)) {
                return null;
            }

            $nickname = trim($nickname);

            return $nickname !== '' ? $nickname : null;
        });
    }

    /**
     * @return array{code: string, endpoint: string}|null
     */
    public function findServiceCodeByLabel(string $productId, string $packageLabel): ?array
    {
        if (! $this->configured()) {
            return null;
        }

        $tabs = $this->getProductServices($productId);

        if (! is_array($tabs) || $tabs === []) {
            return null;
        }

        $normalizedTarget = Str::lower(trim($packageLabel));

        foreach ($tabs as $tab) {
            foreach ($tab['groups'] ?? [] as $group) {
                foreach ($group['options'] ?? [] as $option) {
                    $label = Str::lower(trim((string) ($option['label'] ?? '')));

                    if ($label === $normalizedTarget) {
                        $endpoint = $this->resolveOrderEndpointForProduct($productId);

                        if (! $endpoint || ! filled($option['code'] ?? null)) {
                            return null;
                        }

                        return [
                            'code' => (string) $option['code'],
                            'endpoint' => $endpoint,
                        ];
                    }
                }
            }
        }

        return null;
    }

    /**
     * @return array{code: string, endpoint: string, label: string, price: int}|null
     */
    public function findServiceOption(string $productId, ?string $packageCode = null, ?string $packageLabel = null): ?array
    {
        if (! $this->configured()) {
            return null;
        }

        $tabs = $this->getProductServices($productId);

        if (! is_array($tabs) || $tabs === []) {
            return null;
        }

        $normalizedCode = Str::lower(trim((string) $packageCode));
        $normalizedLabel = Str::lower(trim((string) $packageLabel));
        $endpoint = $this->resolveOrderEndpointForProduct($productId);

        if (! $endpoint) {
            return null;
        }

        foreach ($tabs as $tab) {
            foreach ($tab['groups'] ?? [] as $group) {
                foreach ($group['options'] ?? [] as $option) {
                    $optionCode = Str::lower(trim((string) ($option['code'] ?? '')));
                    $optionLabel = Str::lower(trim((string) ($option['label'] ?? '')));

                    if (
                        ($normalizedCode !== '' && $optionCode === $normalizedCode)
                        || ($normalizedLabel !== '' && $optionLabel === $normalizedLabel)
                    ) {
                        if (! filled($option['code'] ?? null) || (int) ($option['price'] ?? 0) <= 0) {
                            return null;
                        }

                        return [
                            'code' => (string) $option['code'],
                            'endpoint' => $endpoint,
                            'label' => (string) ($option['label'] ?? ''),
                            'price' => (int) ($option['price'] ?? 0),
                        ];
                    }
                }
            }
        }

        return null;
    }

    /**
     * @param  array<int, array{id?: string, value?: string|null}>  $accountFields
     * @return array{trxid: string, status: string, note: string, price: int}
     */
    public function placeOrder(string $endpoint, string $serviceCode, array $accountFields, ?string $fallbackPrimaryValue = null): array
    {
        if (! in_array($endpoint, ['game-feature', 'prepaid'], true)) {
            throw new RuntimeException('Endpoint VIPayment tidak didukung.');
        }

        $normalizedFields = collect($accountFields)
            ->mapWithKeys(fn (array $field) => [(string) ($field['id'] ?? '') => trim((string) ($field['value'] ?? ''))])
            ->filter(fn (string $value, string $key) => $key !== '' && $value !== '');

        $primaryValue = $normalizedFields->first(fn (string $value, string $key) => in_array($key, [
            'account-user-id',
            'target-number',
            'account-number',
            'account-email',
            'account-username',
        ], true));

        $primaryValue = is_string($primaryValue) && $primaryValue !== ''
            ? $primaryValue
            : trim((string) $fallbackPrimaryValue);

        if ($primaryValue === '') {
            throw new RuntimeException('Data akun utama untuk VIPayment belum lengkap.');
        }

        $payload = [
            'type' => 'order',
            'service' => $serviceCode,
            'data_no' => $primaryValue,
        ];

        if ($endpoint === 'game-feature') {
            $zoneValue = $normalizedFields->get('account-zone');

            if (is_string($zoneValue) && $zoneValue !== '') {
                $payload['data_zone'] = $zoneValue;
            }

            $additionalData = $normalizedFields
                ->except(['account-user-id', 'target-number', 'account-number', 'account-email', 'account-username', 'account-zone'])
                ->values()
                ->implode('|');

            if ($additionalData !== '') {
                $payload['post_additional_data'] = $additionalData;
            }
        }

        $responsePayload = $this->sendRequest($endpoint, $payload);
        $orderData = $responsePayload['data'] ?? null;

        if (! is_array($orderData)) {
            throw new RuntimeException('VIPayment tidak mengembalikan data transaksi.');
        }

        return [
            'trxid' => (string) ($orderData['trxid'] ?? ''),
            'status' => (string) ($orderData['status'] ?? 'waiting'),
            'note' => (string) ($orderData['note'] ?? ''),
            'price' => (int) ($orderData['price'] ?? 0),
        ];
    }

    /**
     * @return array{trxid: string, status: string, note: string, price: int}|null
     */
    public function getOrderStatus(string $endpoint, string $trxId): ?array
    {
        if (! in_array($endpoint, ['game-feature', 'prepaid'], true)) {
            throw new RuntimeException('Endpoint VIPayment tidak didukung.');
        }

        $responsePayload = $this->sendRequest($endpoint, [
            'type' => 'status',
            'trxid' => $trxId,
        ]);

        $statuses = collect($responsePayload['data'] ?? []);
        $statusData = $statuses->firstWhere('trxid', $trxId) ?? $statuses->first();

        if (! is_array($statusData)) {
            return null;
        }

        return [
            'trxid' => (string) ($statusData['trxid'] ?? $trxId),
            'status' => (string) ($statusData['status'] ?? 'waiting'),
            'note' => (string) ($statusData['note'] ?? ''),
            'price' => (int) ($statusData['price'] ?? 0),
        ];
    }

    /**
     * @return array<int, array{id: string, label: string, groups: array<int, array{id: string, label: string, title: string, options: array<int, array{id: string, code: string, label: string, note: string, details?: array<int, string>, accountFields?: array<int, array{id: string, label: string, placeholder: string, inputType?: string}>, price: int, oldPrice?: int, discountLabel?: string}>}>}>|null
     */
    public function getProductServices(string $productId): ?array
    {
        if (! $this->configured()) {
            throw new RuntimeException('VIPayment belum dikonfigurasi.');
        }

        $mapping = $this->resolveMapping($productId);

        if ($mapping === null) {
            return null;
        }

        return Cache::remember(
            $this->buildCacheKey($productId, $mapping),
            now()->addMinutes((int) config('vipayment.cache_ttl', 10)),
            fn () => $this->fetchAndTransformServices($mapping, $productId),
        );
    }

    /**
     * @param  array{endpoint: string, payloads: array<int, array<string, string>>}  $mapping
     * @return array<int, array{id: string, label: string, groups: array<int, array{id: string, label: string, title: string, options: array<int, array{id: string, code: string, label: string, note: string, details?: array<int, string>, accountFields?: array<int, array{id: string, label: string, placeholder: string, inputType?: string}>, price: int, oldPrice?: int, discountLabel?: string}>}>}>
     */
    private function fetchAndTransformServices(array $mapping, string $productId): array
    {
        $services = $this->fetchServices($mapping['endpoint'], $mapping['payloads'])
            ->filter(fn (array $service) => filled($service['code'] ?? null));

        $tabs = $mapping['endpoint'] === 'prepaid'
            ? $this->transformPrepaidServices($services, $productId)
            : $this->transformGameServices($services, $productId);

        return $this->appendManualCatalogOffers($tabs, $productId);
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $services
     * @return array<int, array{id: string, label: string, groups: array<int, array{id: string, label: string, title: string, options: array<int, array{id: string, code: string, label: string, note: string, details?: array<int, string>, accountFields?: array<int, array{id: string, label: string, placeholder: string, inputType?: string}>, price: int, oldPrice?: int, discountLabel?: string}>}>}>
     */
    private function transformGameServices(Collection $services, string $productId): array
    {
        $tabs = $services
            ->filter(fn (array $service) => ($service['status'] ?? '') === 'available')
            ->map(function (array $service) use ($productId) {
                $gameName = trim((string) ($service['game'] ?? 'Pembelian'));
                $tabId = $this->determineGameTabId($gameName, (string) ($service['name'] ?? ''));
                $price = $this->extractPrice($service['price'] ?? []);
                $details = $this->extractServiceDetails((string) ($service['description'] ?? ''));

                return [
                    'tabId' => $tabId,
                    'tabLabel' => $tabId === 'gift' ? 'Gift Voucher' : 'Pembelian',
                    'groupId' => Str::slug($gameName),
                    'groupLabel' => $this->shortenGroupLabel($gameName),
                    'groupTitle' => 'Produk pilihan',
                    'option' => [
                        'id' => Str::slug((string) $service['code']),
                        'code' => (string) $service['code'],
                        'label' => trim((string) ($service['name'] ?? 'Produk VIPayment')),
                        'note' => $details[0] ?? $gameName,
                        'details' => $details,
                        'accountFields' => $this->inferAccountFields('game-feature', $productId, $service),
                        'price' => $price,
                    ],
                ];
            })
            ->filter(fn (array $service) => $service['option']['price'] > 0)
            ->groupBy('tabId')
            ->map(function (Collection $tabGroups, string $tabId) {
                $first = $tabGroups->first();

                return [
                    'id' => $tabId,
                    'label' => $first['tabLabel'],
                    'groups' => $tabGroups
                        ->groupBy('groupId')
                        ->map(function (Collection $groupServices, string $groupId) {
                            $firstGroup = $groupServices->first();

                            return [
                                'id' => $groupId,
                                'label' => $firstGroup['groupLabel'],
                                'title' => $firstGroup['groupTitle'],
                                'options' => $groupServices
                                    ->pluck('option')
                                    ->sortBy('price')
                                    ->values()
                                    ->all(),
                            ];
                        })
                        ->values()
                        ->all(),
                ];
            })
            ->sortBy(fn (array $tab) => $tab['id'] === 'purchase' ? 0 : 1)
            ->values()
            ->all();

        return $tabs;
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $services
     * @return array<int, array{id: string, label: string, groups: array<int, array{id: string, label: string, title: string, options: array<int, array{id: string, code: string, label: string, note: string, details?: array<int, string>, accountFields?: array<int, array{id: string, label: string, placeholder: string, inputType?: string}>, price: int, oldPrice?: int, discountLabel?: string}>}>}>
     */
    private function transformPrepaidServices(Collection $services, string $productId): array
    {
        $groups = $services
            ->filter(fn (array $service) => ($service['status'] ?? '') === 'available')
            ->map(function (array $service) use ($productId) {
                $groupName = trim((string) ($service['category'] ?? $service['type'] ?? $service['brand'] ?? 'Nominal Pilihan'));
                $price = $this->extractPrice($service['price'] ?? []);
                $details = $this->extractServiceDetails((string) ($service['note'] ?? ''));

                return [
                    'groupId' => Str::slug($groupName),
                    'groupLabel' => Str::limit($groupName, 24, ''),
                    'groupTitle' => 'Produk pilihan',
                    'option' => [
                        'id' => Str::slug((string) $service['code']),
                        'code' => (string) $service['code'],
                        'label' => trim((string) ($service['name'] ?? 'Produk VIPayment')),
                        'note' => $details[0] ?? trim((string) ($service['brand'] ?? $groupName)),
                        'details' => $details,
                        'accountFields' => $this->inferAccountFields('prepaid', $productId, $service),
                        'price' => $price,
                    ],
                ];
            })
            ->filter(fn (array $service) => $service['option']['price'] > 0)
            ->groupBy('groupId')
            ->map(function (Collection $groupServices, string $groupId) {
                $first = $groupServices->first();

                return [
                    'id' => $groupId,
                    'label' => $first['groupLabel'],
                    'title' => $first['groupTitle'],
                    'options' => $groupServices
                        ->pluck('option')
                        ->sortBy('price')
                        ->values()
                        ->all(),
                ];
            })
            ->values()
            ->all();

        if ($groups === []) {
            return [];
        }

        return [[
            'id' => 'purchase',
            'label' => 'Pembelian',
            'groups' => $groups,
        ]];
    }

    /**
     * @param  array<int, array{id: string, label: string, groups: array<int, array{id: string, label: string, title: string, options: array<int, array{id: string, code: string, label: string, note: string, details?: array<int, string>, accountFields?: array<int, array{id: string, label: string, placeholder: string, inputType?: string}>, price: int, oldPrice?: int, discountLabel?: string|null}>}>}>  $tabs
     * @return array<int, array{id: string, label: string, groups: array<int, array{id: string, label: string, title: string, options: array<int, array{id: string, code: string, label: string, note: string, details?: array<int, string>, accountFields?: array<int, array{id: string, label: string, placeholder: string, inputType?: string}>, price: int, oldPrice?: int, discountLabel?: string|null}>}>}>
     */
    private function appendManualCatalogOffers(array $tabs, string $productId): array
    {
        $manualOffers = collect(config("manual_stock.catalog_offers.{$productId}", []))
            ->filter(fn (mixed $offer) => is_array($offer))
            ->values();

        if ($manualOffers->isEmpty()) {
            return $tabs;
        }

        $tabMap = collect($tabs)->mapWithKeys(function (array $tab) {
            return [
                (string) ($tab['id'] ?? Str::uuid()->toString()) => [
                    'id' => (string) ($tab['id'] ?? 'purchase'),
                    'label' => (string) ($tab['label'] ?? 'Pembelian'),
                    'groups' => collect($tab['groups'] ?? [])->mapWithKeys(function (array $group) {
                        return [
                            (string) ($group['id'] ?? Str::uuid()->toString()) => [
                                'id' => (string) ($group['id'] ?? 'manual-offers'),
                                'label' => (string) ($group['label'] ?? 'Pilihan'),
                                'title' => (string) ($group['title'] ?? 'Produk pilihan'),
                                'options' => collect($group['options'] ?? [])->values()->all(),
                            ],
                        ];
                    })->all(),
                ],
            ];
        });

        $manualOffers->each(function (array $offer) use (&$tabMap) {
            $tabId = (string) ($offer['tab_id'] ?? 'purchase');
            $groupId = (string) ($offer['group_id'] ?? 'manual-offers');
            $option = $offer['option'] ?? null;

            if (! is_array($option) || (int) ($option['price'] ?? 0) <= 0) {
                return;
            }

            $tab = $tabMap->get($tabId, [
                'id' => $tabId,
                'label' => (string) ($offer['tab_label'] ?? 'Pembelian'),
                'groups' => [],
            ]);

            $groups = collect($tab['groups'] ?? []);
            $group = $groups->get($groupId, [
                'id' => $groupId,
                'label' => (string) ($offer['group_label'] ?? 'Pilihan'),
                'title' => (string) ($offer['group_title'] ?? 'Produk pilihan'),
                'options' => [],
            ]);

            $groupOptions = collect($group['options'] ?? [])
                ->reject(fn (array $existingOption) => Str::lower(trim((string) ($existingOption['label'] ?? ''))) === Str::lower(trim((string) ($option['label'] ?? ''))))
                ->push([
                    'id' => (string) ($option['id'] ?? Str::slug((string) ($option['label'] ?? 'manual-offer'))),
                    'code' => (string) ($option['code'] ?? ''),
                    'label' => trim((string) ($option['label'] ?? 'Produk manual')),
                    'note' => trim((string) ($option['note'] ?? 'Produk manual Lyva')),
                    'details' => collect($option['details'] ?? [])->map(fn (mixed $detail) => trim((string) $detail))->filter()->values()->all(),
                    'accountFields' => collect($option['accountFields'] ?? [])
                        ->map(function (mixed $field) {
                            if (! is_array($field)) {
                                return null;
                            }

                            return [
                                'id' => (string) ($field['id'] ?? ''),
                                'label' => (string) ($field['label'] ?? ''),
                                'placeholder' => (string) ($field['placeholder'] ?? ''),
                                'inputType' => (string) ($field['inputType'] ?? 'text'),
                            ];
                        })
                        ->filter(fn (?array $field) => is_array($field) && $field['id'] !== '' && $field['label'] !== '')
                        ->values()
                        ->all(),
                    'price' => (int) $option['price'],
                    'oldPrice' => isset($option['oldPrice']) ? (int) $option['oldPrice'] : null,
                    'discountLabel' => filled($option['discountLabel'] ?? null) ? (string) $option['discountLabel'] : null,
                ])
                ->sortBy('price')
                ->values()
                ->all();

            $groups->put($groupId, [
                ...$group,
                'options' => $groupOptions,
            ]);

            $tabMap->put($tabId, [
                ...$tab,
                'groups' => $groups->all(),
            ]);
        });

        return $tabMap
            ->values()
            ->map(function (array $tab) {
                return [
                    'id' => $tab['id'],
                    'label' => $tab['label'],
                    'groups' => collect($tab['groups'] ?? [])
                        ->map(function (array $group) {
                            return [
                                'id' => $group['id'],
                                'label' => $group['label'],
                                'title' => $group['title'],
                                'options' => collect($group['options'] ?? [])
                                    ->sortBy('price')
                                    ->values()
                                    ->all(),
                            ];
                        })
                        ->values()
                        ->all(),
                ];
            })
            ->sortBy(fn (array $tab) => $tab['id'] === 'purchase' ? 0 : 1)
            ->values()
            ->all();
    }

    /**
     * @return array{endpoint: string, payloads: array<int, array<string, string>>}|null
     */
    private function resolveMapping(string $productId): ?array
    {
        $gameMappings = [
            'mobile-legends' => ['Mobile Legends A', 'Mobile Legends B', 'Mobile Legends Gift'],
            'mobile-legends-a' => 'Mobile Legends A',
            'mobile-legends-b' => 'Mobile Legends B',
            'mobile-legends-gift-skin' => 'Mobile Legends Gift',
            'mobile-legends-global' => ['Mobile Legends A', 'Mobile Legends B', 'Mobile Legends Gift'],
            'mobile-legends-login' => ['Mobile Legends A', 'Mobile Legends B', 'Mobile Legends Gift'],
            'ml-first-topup' => ['Mobile Legends A', 'Mobile Legends B', 'Mobile Legends Gift'],
            'free-fire' => 'Free Fire',
            'free-fire-global' => 'Free Fire',
            'free-fire-login' => 'Free Fire',
            'bigo-live' => 'Bigo Live',
            'genshin-impact' => 'Genshin Impact',
            'honor-of-kings' => 'Honor of Kings',
            'hok-login' => 'Honor of Kings',
            'pubgm-login' => 'PUBG Mobile',
            'blood-strike-login' => 'Blood Strike',
            'steam-wallet' => 'Steam Wallet',
            'google-play' => 'Google Play',
            'apple-store' => 'App Store',
            'razer-gold' => 'Razer Gold',
            'ps-store' => 'PlayStation',
            'spotify' => 'Spotify',
            'netflix' => 'Netflix',
            'youtube-premium' => 'YouTube Premium',
            'viu' => 'Viu',
            'vidio' => 'Vidio',
            'zenless-zone-zero' => 'Zenless Zone Zero',
            'delta-force' => 'Delta Force',
            'crystal-of-atlan' => 'Crystal of Atlan',
            'solo-leveling-arise' => 'Solo Leveling',
            'wuthering-waves' => 'Wuthering Waves',
            'afk-journey' => 'AFK Journey',
            'hero-reborn' => 'Hero Reborn Eternal Pact',
            'isekai-feast' => 'Isekai Feast',
            'ace-racer' => 'Ace Racer',
            'wuxia-rising' => 'Wuxia Rising Star',
            'astra-knights' => 'Astra',
            'eggy-party' => 'Eggy Party',
            'arena-of-valor' => 'Arena of Valor',
        ];

        if (isset($gameMappings[$productId])) {
            $filters = collect((array) $gameMappings[$productId])
                ->map(fn (string $filter) => [
                    'filter_game' => $filter,
                    'filter_status' => 'available',
                ])
                ->values()
                ->all();

            return [
                'endpoint' => 'game-feature',
                'payloads' => $filters,
            ];
        }

        $prepaidMappings = [
            'telkomsel' => 'Telkomsel',
            'indosat' => 'Indosat',
            'xl' => 'XL',
            'tri' => 'Tri',
            'smartfren' => 'Smartfren',
        ];

        if (isset($prepaidMappings[$productId])) {
            return [
                'endpoint' => 'prepaid',
                'payloads' => [[
                    'filter_type' => 'brand',
                    'filter_value' => $prepaidMappings[$productId],
                ]],
            ];
        }

        if (Str::startsWith($productId, 'vip-')) {
            $dynamicCatalogEntry = collect($this->getCatalogEntries())->firstWhere('id', $productId);

            if ($dynamicCatalogEntry !== null) {
                return $dynamicCatalogEntry['mapping'] ?? null;
            }
        }

        return null;
    }

    private function determineGameTabId(string $gameName, string $serviceName): string
    {
        $value = Str::lower($gameName.' '.$serviceName);

        return Str::contains($value, 'gift') ? 'gift' : 'purchase';
    }

    private function shortenGroupLabel(string $groupName): string
    {
        return Str::limit(trim($groupName), 24, '');
    }

    /**
     * @param  array<string, mixed>  $price
     */
    private function extractPrice(array $price): int
    {
        $basePrice = $this->extractBasePrice($price);

        if ($basePrice <= 0) {
            return 0;
        }

        return $this->applySellingPrice($basePrice);
    }

    /**
     * @param  array<string, mixed>  $price
     */
    private function extractBasePrice(array $price): int
    {
        $preferredTier = Str::lower((string) config('vipayment.price_tier', 'basic'));

        foreach ([$preferredTier, 'basic', 'premium', 'special'] as $tier) {
            $value = (int) ($price[$tier] ?? 0);

            if ($value > 0) {
                return $value;
            }
        }

        return 0;
    }

    private function applySellingPrice(int $basePrice): int
    {
        if (! (bool) config('vipayment.selling_price.enabled', true)) {
            return $basePrice;
        }

        $tier = collect($this->settings->marginTiers())
            ->first(function (array $pricingTier) use ($basePrice) {
                $max = $pricingTier['max'] ?? null;

                return $max === null || $basePrice <= (int) $max;
            });

        if (! is_array($tier)) {
            return $basePrice;
        }

        $percent = max(0, (float) ($tier['percent'] ?? 0));
        $fixed = max(0, (int) ($tier['fixed'] ?? 0));
        $roundTo = max(1, (int) ($tier['round_to'] ?? 100));
        $priceWithMargin = $basePrice + (int) ceil($basePrice * $percent) + $fixed;

        return $this->roundSellingPrice($priceWithMargin, $roundTo);
    }

    private function roundSellingPrice(int $price, int $roundTo): int
    {
        return (int) (ceil($price / $roundTo) * $roundTo);
    }

    private function cleanText(string $value): string
    {
        $decoded = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $withoutTags = strip_tags($decoded);
        $normalized = preg_replace('/\s+/', ' ', $withoutTags);

        return trim((string) $normalized);
    }

    private function resolveNicknameLookupCode(string $productId): ?string
    {
        $value = Str::lower(trim($productId));
        $isMobileLegendsRegionVariant = Str::contains($value, ['global', 'region', 'brazil', 'malaysia', 'philippines', 'russia', 'singapore']);

        return match (true) {
            Str::contains($value, 'mobile-legends') && ! Str::contains($value, ['gift', 'login']) => $isMobileLegendsRegionVariant ? 'mobile-legends-region' : 'mobile-legends',
            Str::contains($value, 'free-fire') && ! Str::contains($value, ['login']) => 'free-fire',
            Str::contains($value, ['pubg', 'pubgm']) => 'pubgm',
            Str::contains($value, 'valorant') && ! Str::contains($value, ['voucher']) => 'valorant',
            Str::contains($value, 'genshin-impact') => 'genshin-impact',
            Str::contains($value, 'honkai-star-rail') => 'honkai-star-rail',
            Str::contains($value, ['pointblank', 'point-blank', 'pb-zepetto']) => 'pointblank',
            default => null,
        };
    }

    private function nicknameLookupRequiresZone(string $lookupCode): bool
    {
        return in_array($lookupCode, ['mobile-legends', 'mobile-legends-region', 'genshin-impact', 'honkai-star-rail'], true);
    }

    /**
     * @return array<int, string>
     */
    private function extractServiceDetails(string $value): array
    {
        $decoded = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $withLineBreaks = preg_replace('/<\s*br\s*\/?>/i', "\n", $decoded) ?? $decoded;
        $withLineBreaks = preg_replace('/<\s*\/li\s*>/i', "\n", $withLineBreaks) ?? $withLineBreaks;
        $withLineBreaks = preg_replace('/<\s*li[^>]*>/i', '', $withLineBreaks) ?? $withLineBreaks;
        $plainText = strip_tags($withLineBreaks);

        return collect(preg_split('/[\r\n]+/', $plainText) ?: [])
            ->map(fn (string $line) => $this->cleanText($line))
            ->filter(fn (string $line) => $line !== '' && $line !== '-')
            ->values()
            ->all();
    }

    private function resolveBaseUrl(): string
    {
        $configuredBaseUrl = trim((string) config('vipayment.base_url', ''));
        $baseUrl = $configuredBaseUrl !== '' ? $configuredBaseUrl : 'https://vip-reseller.co.id';

        if (! preg_match('#^https?://#i', $baseUrl)) {
            $baseUrl = 'https://'.ltrim($baseUrl, '/');
        }

        $baseUrl = rtrim($baseUrl, '/');

        if (! filter_var($baseUrl, FILTER_VALIDATE_URL)) {
            throw new RuntimeException('VIPAYMENT_BASE_URL tidak valid. Gunakan domain lengkap, misalnya https://vip-reseller.co.id.');
        }

        return $baseUrl;
    }

    /**
     * @param  array{endpoint: string, payloads: array<int, array<string, string>>}  $mapping
     */
    private function buildCacheKey(string $productId, array $mapping): string
    {
        return 'vipayment.services.'.md5(json_encode([
            'product' => $productId,
            'mapping' => $mapping,
            'tier' => config('vipayment.price_tier', 'basic'),
            'pricing' => $this->pricingCacheFingerprint(),
            'manual_offers' => config("manual_stock.catalog_offers.{$productId}", []),
            'schema_version' => 'account-fields-v22-streaming-input-audit',
        ]));
    }

    /**
     * @param  array<int, array<string, string>>  $payloads
     * @return Collection<int, array<string, mixed>>
     */
    private function fetchServices(string $endpoint, array $payloads): Collection
    {
        $normalizedPayloads = $payloads === [] ? [[]] : $payloads;

        return collect($normalizedPayloads)->flatMap(function (array $payload) use ($endpoint) {
            $responsePayload = $this->sendRequest($endpoint, [
                'type' => 'services',
                ...$payload,
            ], swallowServiceNotFound: true);

            if ($responsePayload === []) {
                return [];
            }

            return $responsePayload['data'] ?? [];
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function sendRequest(string $endpoint, array $payload, bool $swallowServiceNotFound = false): array
    {
        $response = Http::timeout((int) config('vipayment.timeout', 15))
            ->acceptJson()
            ->asForm()
            ->post($this->resolveBaseUrl().'/api/'.$endpoint, [
                'key' => (string) config('vipayment.api_key'),
                'sign' => md5((string) config('vipayment.api_id').(string) config('vipayment.api_key')),
                ...$payload,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException((string) ($response->json('message') ?? 'VIPayment sedang tidak bisa dihubungi.'));
        }

        $responsePayload = $response->json();

        if (($responsePayload['result'] ?? false) !== true) {
            $message = (string) ($responsePayload['message'] ?? 'VIPayment menolak permintaan.');

            if ($swallowServiceNotFound && Str::lower($message) === 'layanan tidak ditemukan.') {
                return [];
            }

            throw new RuntimeException($message);
        }

        return is_array($responsePayload) ? $responsePayload : [];
    }

    /**
     * @return array<int, array{id: string, name: string, categoryId: string, categoryTitle: string, badge?: string|null, mapping: array{endpoint: string, payloads: array<int, array<string, string>>}}>
     */
    private function getCatalogEntries(): array
    {
        return Cache::remember(
            'vipayment.catalog.'.md5(json_encode([
                'tier' => config('vipayment.price_tier', 'basic'),
                'pricing' => $this->pricingCacheFingerprint(),
                'version' => '2026-03-29-home-categories-v4',
            ])),
            now()->addMinutes((int) config('vipayment.cache_ttl', 10)),
            function () {
                return [
                    ...$this->buildGameCatalogEntries(),
                    ...$this->buildPrepaidCatalogEntries(),
                ];
            },
        );
    }

    /**
     * @return array<int, array{id: string, name: string, categoryId: string, categoryTitle: string, badge?: string|null, mapping: array{endpoint: string, payloads: array<int, array<string, string>>}}>
     */
    private function buildGameCatalogEntries(): array
    {
        return $this->fetchServices('game-feature', [[
            'filter_status' => 'available',
        ]])
            ->filter(fn (array $service) => ($service['status'] ?? '') === 'available')
            ->map(fn (array $service) => trim((string) ($service['game'] ?? '')))
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->map(function (string $gameName) {
                $category = $this->resolveCatalogCategory($gameName, 'game-feature');

                return [
                    'id' => 'vip-game-'.Str::slug($gameName),
                    'name' => $gameName,
                    'categoryId' => $category['id'],
                    'categoryTitle' => $category['title'],
                    'badge' => null,
                    'mapping' => [
                        'endpoint' => 'game-feature',
                        'payloads' => [[
                            'filter_game' => $gameName,
                            'filter_status' => 'available',
                        ]],
                    ],
                ];
            })
            ->all();
    }

    /**
     * @return array<int, array{id: string, name: string, categoryId: string, categoryTitle: string, badge?: string|null, mapping: array{endpoint: string, payloads: array<int, array<string, string>>}}>
     */
    private function buildPrepaidCatalogEntries(): array
    {
        return $this->fetchServices('prepaid', [[]])
            ->filter(fn (array $service) => ($service['status'] ?? '') === 'available')
            ->map(fn (array $service) => trim((string) ($service['brand'] ?? '')))
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->map(function (string $brandName) {
                $category = $this->resolveCatalogCategory($brandName, 'prepaid');

                return [
                    'id' => 'vip-prepaid-'.Str::slug($brandName),
                    'name' => $brandName,
                    'categoryId' => $category['id'],
                    'categoryTitle' => $category['title'],
                    'badge' => null,
                    'mapping' => [
                        'endpoint' => 'prepaid',
                        'payloads' => [[
                            'filter_type' => 'brand',
                            'filter_value' => $brandName,
                        ]],
                    ],
                ];
            })
            ->all();
    }

    /**
     * @return array{id: string, title: string}
     */
    private function resolveCatalogCategory(string $name, string $endpoint): array
    {
        $value = Str::lower($name);

        if (Str::contains($value, ['login'])) {
            return [
                'id' => 'login',
                'title' => 'Top Up Login',
            ];
        }

        if (Str::contains($value, ['roblox', 'capcut', 'chatgpt'])) {
            return [
                'id' => 'popular',
                'title' => 'Lagi Populer',
            ];
        }

        if ($endpoint === 'prepaid') {
            if (Str::contains($value, ['dana', 'gopay', 'go pay', 'ovo', 'shopeepay', 'linkaja', 'sakuku', 'isaku', 'paypal', 'maxim', 'grab', 'saldo'])) {
                return [
                    'id' => 'ewallet',
                    'title' => 'E-Wallet',
                ];
            }

            return [
                'id' => 'pulsa',
                'title' => 'Pulsa',
            ];
        }

        if (Str::contains($value, ['wallet', 'gift', 'app store', 'playstation', 'google play', 'steam', 'razer'])) {
            return [
                'id' => 'voucher',
                'title' => 'Voucher',
            ];
        }

        if (Str::contains($value, ['spotify', 'netflix', 'youtube', 'viu', 'vidio', 'bigo', 'prime video', 'alight motion', 'canva', 'warp plus'])) {
            return [
                'id' => 'entertainment',
                'title' => 'Entertainment',
            ];
        }

        return [
            'id' => 'instant',
            'title' => 'Top Up Langsung',
        ];
    }

    /**
     * @param  array{id: string, name: string, categoryId: string, categoryTitle: string, badge?: string|null}  $entry
     * @return array{id: string, name: string, categoryId: string, categoryTitle: string, badge?: string|null}
     */
    private function publicCatalogProduct(array $entry): array
    {
        $inferredCategory = $this->inferCatalogCategory($entry);

        return [
            'id' => $entry['id'],
            'name' => $entry['name'],
            'categoryId' => $inferredCategory['id'],
            'categoryTitle' => $inferredCategory['title'],
            'badge' => $entry['badge'] ?? null,
        ];
    }

    /**
     * @param  array{id: string, name: string, categoryId: string, categoryTitle: string, badge?: string|null}  $entry
     * @return array{id: string, title: string}
     */
    private function inferCatalogCategory(array $entry): array
    {
        $defaultCategory = [
            'id' => (string) $entry['categoryId'],
            'title' => (string) $entry['categoryTitle'],
        ];

        $haystack = Str::lower(trim(sprintf('%s %s', (string) $entry['id'], (string) $entry['name'])));

        $exactIdMap = [
            'vip-game-roblox-via-login' => ['id' => 'login', 'title' => 'Top Up Login'],
            'vip-game-alight-motion' => ['id' => 'entertainment', 'title' => 'Entertainment'],
            'vip-game-amazon-prime-video' => ['id' => 'entertainment', 'title' => 'Entertainment'],
            'vip-game-bstation-premium' => ['id' => 'entertainment', 'title' => 'Entertainment'],
            'vip-game-canva-pro' => ['id' => 'entertainment', 'title' => 'Entertainment'],
            'vip-game-iqiyi-premium' => ['id' => 'entertainment', 'title' => 'Entertainment'],
            'vip-game-vidio-premier' => ['id' => 'entertainment', 'title' => 'Entertainment'],
            'vip-game-viu-premium' => ['id' => 'entertainment', 'title' => 'Entertainment'],
            'vip-game-vision-plus' => ['id' => 'entertainment', 'title' => 'Entertainment'],
            'vip-game-warp-plus' => ['id' => 'entertainment', 'title' => 'Entertainment'],
            'vip-game-wetv-premium' => ['id' => 'entertainment', 'title' => 'Entertainment'],
            'vip-game-youtube-premium' => ['id' => 'entertainment', 'title' => 'Entertainment'],
            'vip-prepaid-smart' => ['id' => 'pulsa', 'title' => 'Pulsa'],
            'vip-prepaid-starhub' => ['id' => 'pulsa', 'title' => 'Pulsa'],
            'vip-prepaid-sun-telecom' => ['id' => 'pulsa', 'title' => 'Pulsa'],
            'vip-prepaid-philippines-smart' => ['id' => 'pulsa', 'title' => 'Pulsa'],
            'vip-prepaid-tix-id' => ['id' => 'entertainment', 'title' => 'Entertainment'],
            'vip-prepaid-xox' => ['id' => 'pulsa', 'title' => 'Pulsa'],
            'vip-prepaid-k-vision-dan-gol' => ['id' => 'entertainment', 'title' => 'Entertainment'],
            'vip-prepaid-nex-parabola' => ['id' => 'entertainment', 'title' => 'Entertainment'],
            'vip-prepaid-orange-tv' => ['id' => 'entertainment', 'title' => 'Entertainment'],
            'vip-prepaid-likee' => ['id' => 'entertainment', 'title' => 'Entertainment'],
            'vip-prepaid-alfamart-voucher' => ['id' => 'voucher', 'title' => 'Voucher'],
            'vip-prepaid-bri-brizzi' => ['id' => 'ewallet', 'title' => 'E-Wallet'],
            'vip-prepaid-doku' => ['id' => 'ewallet', 'title' => 'E-Wallet'],
            'vip-prepaid-globe' => ['id' => 'pulsa', 'title' => 'Pulsa'],
            'vip-prepaid-indomaret' => ['id' => 'voucher', 'title' => 'Voucher'],
            'vip-prepaid-mandiri-e-toll' => ['id' => 'ewallet', 'title' => 'E-Wallet'],
            'vip-prepaid-shopee-pay' => ['id' => 'ewallet', 'title' => 'E-Wallet'],
            'vip-prepaid-tapcash-bni' => ['id' => 'ewallet', 'title' => 'E-Wallet'],
            'vip-prepaid-razer-gold' => ['id' => 'voucher', 'title' => 'Voucher'],
        ];

        if (array_key_exists($entry['id'], $exactIdMap)) {
            return $exactIdMap[$entry['id']];
        }

        $keywordMap = [
            'ewallet' => ['doku', 'brizzi', 'brizi', 'tapcash', 'e-toll', 'etoll', 'shopee pay', 'shopeepay'],
            'entertainment' => ['iqiyi', 'wetv', 'vision plus', 'vidio', 'viu', 'youtube premium', 'bstation', 'nex parabola', 'orange tv', 'k-vision', 'k vision', 'tix id', 'likee'],
            'voucher' => ['razer gold', 'voucher', 'alfamart voucher', 'indomaret'],
        ];

        foreach ($keywordMap['ewallet'] as $keyword) {
            if (Str::contains($haystack, $keyword)) {
                return ['id' => 'ewallet', 'title' => 'E-Wallet'];
            }
        }

        foreach ($keywordMap['entertainment'] as $keyword) {
            if (Str::contains($haystack, $keyword)) {
                return ['id' => 'entertainment', 'title' => 'Entertainment'];
            }
        }

        foreach ($keywordMap['voucher'] as $keyword) {
            if (Str::contains($haystack, $keyword)) {
                return ['id' => 'voucher', 'title' => 'Voucher'];
            }
        }

        return $defaultCategory;
    }

    private function pricingCacheFingerprint(): string
    {
        return md5(json_encode([
            'enabled' => (bool) config('vipayment.selling_price.enabled', true),
            'tiers' => $this->settings->marginTiers(),
        ]));
    }
}
