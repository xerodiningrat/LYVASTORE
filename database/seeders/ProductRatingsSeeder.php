<?php

namespace Database\Seeders;

use App\Models\Transaction;
use App\Services\VipaymentService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductRatingsSeeder extends Seeder
{
    private const SEEDED_GUEST_TOKEN_PREFIX = 'seed-rating:';

    /**
     * @var array<int, string>
     */
    private array $firstNames = [
        'Adi', 'Agus', 'Aldo', 'Andi', 'Arif', 'Bagas', 'Bayu', 'Bima', 'Dafa', 'Dani', 'Dimas', 'Eka', 'Fajar', 'Farhan', 'Firman',
        'Galih', 'Hadi', 'Irfan', 'Joko', 'Kevin', 'Maman', 'Rafli', 'Raka', 'Rangga', 'Rian', 'Rifki', 'Rio', 'Rizky', 'Wahyu', 'Yogi',
        'Aisyah', 'Anisa', 'Aulia', 'Citra', 'Dewi', 'Dinda', 'Fitri', 'Intan', 'Laras', 'Maya', 'Nabila', 'Nadya', 'Putri', 'Rani', 'Salsa',
        'Sari', 'Tasya', 'Tiara', 'Vina', 'Wulan',
    ];

    /**
     * @var array<int, string>
     */
    private array $lastNames = [
        'Pratama', 'Saputra', 'Wijaya', 'Setiawan', 'Nugraha', 'Permana', 'Ramadhan', 'Kurniawan', 'Hidayat', 'Firmansyah',
        'Maharani', 'Lestari', 'Puspita', 'Rahmawati', 'Salsabila', 'Putra', 'Maulana', 'Anjani', 'Pangestu', 'Syahputra',
    ];

    /**
     * @var array<int, string>
     */
    private array $positiveComments = [
        'Top up-nya cepat, nominal langsung sesuai, mantap buat langganan.',
        'Checkout simpel dan pembayaran langsung kebaca. Aman dipakai.',
        'Pesanan masuk sesuai pilihan, prosesnya juga rapi dan jelas.',
        'Sudah beberapa kali order di sini, sejauh ini lancar terus.',
        'Harga oke, proses cepat, dan tampilannya enak dipahami.',
        'Cocok buat top up dadakan, tidak ribet dan responsnya cepat.',
        'Metode pembayaran lengkap, jadi lebih gampang pilih yang cocok.',
        'Baru coba sekali dan hasilnya memuaskan. Bakal repeat order.',
        'Pelayanan bagus, transaksi beres tanpa kendala.',
        'Pesanan selesai cepat, detail produknya juga jelas.',
    ];

    /**
     * @var array<int, string>
     */
    private array $neutralComments = [
        'Prosesnya cukup cepat dan produknya masuk sesuai pesanan.',
        'Overall oke, checkout mudah dan tidak bikin bingung.',
        'Transaksi aman, tinggal pilih nominal lalu lanjut bayar.',
        'Lumayan praktis buat top up, terutama kalau lagi butuh cepat.',
        'Produknya sesuai dan alur pembeliannya jelas.',
        'Sudah dicoba dan sejauh ini aman, semoga konsisten.',
    ];

    public function run(): void
    {
        $products = $this->collectProducts();

        if ($products === []) {
            $this->command?->warn('Tidak ada produk yang bisa dibuatkan rating dummy.');

            return;
        }

        DB::transaction(function () use ($products): void {
            Transaction::query()
                ->where('guest_token', 'like', self::SEEDED_GUEST_TOKEN_PREFIX.'%')
                ->delete();

            $now = CarbonImmutable::now('Asia/Jakarta');

            foreach ($products as $product) {
                $this->seedRatingsForProduct($product['id'], $product['name'], $now);
            }
        });

        $this->command?->info('Rating dummy berhasil dibuat untuk '.count($products).' produk.');
    }

    /**
     * @return array<int, array{id: string, name: string}>
     */
    private function collectProducts(): array
    {
        $products = collect($this->readLocalCatalogProducts());

        try {
            /** @var VipaymentService $vipayment */
            $vipayment = app(VipaymentService::class);

            if ($vipayment->configured()) {
                $products = $products->merge(
                    collect($vipayment->getCatalogProducts())
                        ->map(fn (array $product): array => [
                            'id' => (string) ($product['id'] ?? ''),
                            'name' => (string) ($product['name'] ?? ''),
                        ])
                );
            }
        } catch (\Throwable $exception) {
            report($exception);
        }

        return $products
            ->filter(fn (array $product): bool => $product['id'] !== '' && $product['name'] !== '')
            ->unique('id')
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{id: string, name: string}>
     */
    private function readLocalCatalogProducts(): array
    {
        $path = resource_path('js/data/catalog.ts');

        if (! is_file($path)) {
            return [];
        }

        $contents = (string) file_get_contents($path);

        preg_match_all("/id:\\s*'([^']+)'\\s*,\\s*name:\\s*'([^']+)'/m", $contents, $matches, PREG_SET_ORDER);

        return collect($matches)
            ->map(static fn (array $match): array => [
                'id' => trim((string) ($match[1] ?? '')),
                'name' => trim((string) ($match[2] ?? '')),
            ])
            ->values()
            ->all();
    }

    private function seedRatingsForProduct(string $productId, string $productName, CarbonImmutable $now): void
    {
        $ratingCount = random_int(55, 72);
        $scores = $this->generateScores($ratingCount);

        $rows = [];

        for ($index = 0; $index < $ratingCount; $index++) {
            $createdAt = $now
                ->subDays(random_int(1, 120))
                ->subHours(random_int(0, 23))
                ->subMinutes(random_int(0, 59));
            $paidAt = $createdAt->addMinutes(random_int(1, 12));
            $fulfilledAt = $paidAt->addMinutes(random_int(1, 45));
            $ratedAt = $fulfilledAt->addMinutes(random_int(2, 360));
            $score = $scores[$index] ?? 5;
            $customerName = $this->randomIndonesianName();
            $customerEmail = Str::slug($customerName, '.').random_int(10, 9999).'@gmail.com';
            $paymentLabel = collect(['QRIS', 'DANA', 'GoPay', 'OVO', 'BCA Virtual Account', 'Mandiri Virtual Account'])->random();
            $quantity = random_int(1, 3);
            $total = random_int(12000, 250000);

            $rows[] = [
                'public_id' => 'SR'.strtoupper(Str::random(10)),
                'guest_token' => self::SEEDED_GUEST_TOKEN_PREFIX.$productId.':'.$index,
                'status' => Transaction::STATUS_COMPLETED,
                'payment_status' => Transaction::PAYMENT_STATUS_PAID,
                'product_source' => Transaction::PRODUCT_SOURCE_VIPAYMENT,
                'product_id' => $productId,
                'product_name' => $productName,
                'package_code' => 'seed-package-'.Str::slug($productName).'-'.$index,
                'package_label' => $this->randomPackageLabel($productName),
                'quantity' => $quantity,
                'payment_method_code' => Str::slug($paymentLabel),
                'payment_method_label' => $paymentLabel,
                'payment_method_type' => 'seeded',
                'payment_badge' => Str::upper(Str::substr(Str::slug($paymentLabel, ''), 0, 2)),
                'payment_caption' => 'Pembayaran otomatis',
                'total' => $total,
                'customer_name' => $customerName,
                'customer_email' => $customerEmail,
                'customer_whatsapp' => '08'.random_int(1111111111, 9999999999),
                'paid_at' => $paidAt,
                'fulfilled_at' => $fulfilledAt,
                'rating_score' => $score,
                'rating_comment' => $this->randomComment($score, $productName),
                'rated_at' => $ratedAt,
                'created_at' => $createdAt,
                'updated_at' => $ratedAt,
            ];
        }

        Transaction::query()->insert($rows);
    }

    /**
     * @return array<int, int>
     */
    private function generateScores(int $ratingCount): array
    {
        $targetAverage = random_int(470, 492) / 100;
        $scores = [];

        for ($index = 0; $index < $ratingCount; $index++) {
            $scores[] = $this->randomScoreSample();
        }

        while ($this->averageScore($scores) < $targetAverage) {
            $upgradableIndexes = array_keys(array_filter($scores, static fn (int $score): bool => $score < 5));

            if ($upgradableIndexes === []) {
                break;
            }

            $pickedIndex = $upgradableIndexes[array_rand($upgradableIndexes)];
            $scores[$pickedIndex]++;
        }

        shuffle($scores);

        return $scores;
    }

    private function randomScoreSample(): int
    {
        $roll = random_int(1, 1000);

        if ($roll <= 760) {
            return 5;
        }

        if ($roll <= 980) {
            return 4;
        }

        return 3;
    }

    /**
     * @param  array<int, int>  $scores
     */
    private function averageScore(array $scores): float
    {
        if ($scores === []) {
            return 0;
        }

        return array_sum($scores) / count($scores);
    }

    private function randomIndonesianName(): string
    {
        return $this->firstNames[array_rand($this->firstNames)].' '.$this->lastNames[array_rand($this->lastNames)];
    }

    private function randomComment(int $score, string $productName): string
    {
        $baseComment = $score >= 4
            ? $this->positiveComments[array_rand($this->positiveComments)]
            : $this->neutralComments[array_rand($this->neutralComments)];

        $mentionsProduct = random_int(0, 1) === 1;

        if (! $mentionsProduct) {
            return $baseComment;
        }

        return $productName.': '.$baseComment;
    }

    private function randomPackageLabel(string $productName): string
    {
        $variants = [
            'Paket Utama',
            'Paket Populer',
            'Nominal Favorit',
            'Instant Top Up',
            'Promo Hari Ini',
        ];

        return $productName.' - '.$variants[array_rand($variants)];
    }
}
