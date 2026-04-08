<?php

namespace App\Console\Commands;

use App\Services\SiteSettingService;
use App\Services\VipaymentService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

class GenerateSitemapCommand extends Command
{
    protected $signature = 'lyva:generate-sitemap';

    protected $description = 'Generate sitemap.xml for public pages and product detail pages';

    public function handle(VipaymentService $vipayment, SiteSettingService $settings): int
    {
        $baseUrl = rtrim((string) config('app.url', 'https://lyvaindonesia.com'), '/');
        $hiddenProductIds = $settings->hiddenProductIds();

        $staticPaths = collect([
            '/',
            '/riwayat-transaksi',
            '/leaderboard',
            '/artikel',
            '/lyva-coins',
            '/kalkulator',
        ]);

        $productPaths = collect();

        if ($vipayment->configured()) {
            try {
                $productPaths = collect($vipayment->getCatalogProducts())
                    ->map(fn (array $product) => trim((string) ($product['id'] ?? '')))
                    ->filter()
                    ->reject(fn (string $productId) => in_array($productId, $hiddenProductIds, true))
                    ->filter(fn (string $productId) => $vipayment->isProductPubliclyAvailable($productId))
                    ->map(fn (string $productId) => '/produk/'.$productId);
            } catch (\Throwable $exception) {
                report($exception);
                $this->warn('Gagal mengambil katalog VIPayment. Sitemap produk dibuat dari halaman statis dulu.');
            }
        }

        $urls = $staticPaths
            ->merge($productPaths)
            ->unique()
            ->values();

        $xml = $this->buildXml($baseUrl, $urls);

        File::put(public_path('sitemap.xml'), $xml);

        $this->info('Sitemap berhasil dibuat: '.public_path('sitemap.xml'));
        $this->line('Total URL: '.$urls->count());

        return self::SUCCESS;
    }

    private function buildXml(string $baseUrl, Collection $paths): string
    {
        $lastmod = now()->toAtomString();

        $items = $paths
            ->map(function (string $path) use ($baseUrl, $lastmod): string {
                $location = htmlspecialchars($baseUrl.$path, ENT_XML1);

                return <<<XML
    <url>
        <loc>{$location}</loc>
        <lastmod>{$lastmod}</lastmod>
    </url>
XML;
            })
            ->implode("\n");

        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
{$items}
</urlset>
XML;
    }
}
