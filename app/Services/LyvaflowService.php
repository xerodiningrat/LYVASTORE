<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class LyvaflowService
{
    public function configured(): bool
    {
        return filled(config('services.lyvaflow.api_key')) && filled($this->resolveBaseUrl());
    }

    /**
     * @return array<string, mixed>
     */
    public function getStatus(): array
    {
        if (! $this->configured()) {
            throw new RuntimeException('LYVAFLOW belum dikonfigurasi.');
        }

        $response = $this->request()->get('status');

        if (! $response->successful()) {
            throw new RuntimeException((string) ($response->json('error') ?? 'LYVAFLOW status tidak bisa diambil.'));
        }

        return is_array($response->json()) ? $response->json() : [];
    }

    /**
     * @return array<string, mixed>
     */
    public function sendWhatsappMessage(string $number, string $text, array $options = []): array
    {
        if (! $this->configured()) {
            throw new RuntimeException('LYVAFLOW belum dikonfigurasi.');
        }

        $jid = trim((string) ($options['jid'] ?? ''));
        $normalizedNumber = $this->normalizeWhatsappNumber($number);

        if ($jid === '' && $normalizedNumber === '') {
            throw new RuntimeException('Nomor WhatsApp tujuan tidak valid.');
        }

        $payload = [
            'text' => trim($text),
        ];
        $qrString = trim((string) ($options['qrString'] ?? ''));
        $simulateTyping = (bool) ($options['simulateTyping'] ?? false);
        $typingDurationMs = (int) ($options['typingDurationMs'] ?? 0);

        if ($jid !== '') {
            $payload['jid'] = $jid;
        }

        if ($normalizedNumber !== '') {
            $payload['number'] = $normalizedNumber;
        }

        if ($qrString !== '') {
            $payload['qrString'] = $qrString;
        }

        if ($simulateTyping && $typingDurationMs > 0) {
            $payload['simulateTyping'] = true;
            $payload['typingDurationMs'] = $typingDurationMs;
        }

        $response = $this->request()->post('messages/send', $payload);

        if (! $response->successful()) {
            throw new RuntimeException((string) ($response->json('error') ?? 'LYVAFLOW gagal mengirim pesan.'));
        }

        return is_array($response->json()) ? $response->json() : [];
    }

    /**
     * @param  array<int, string>  $introLines
     * @param  array<int, array{title?: string, lines?: array<int, string>, style?: string}>  $sections
     * @param  array<int, string>  $closingLines
     */
    public function composeStructuredMessage(string $title, array $introLines = [], array $sections = [], array $closingLines = []): string
    {
        $blocks = [
            ['*Lyva Indonesia*', '*'.trim($title).'*'],
            $this->normalizeMessageLines($introLines),
        ];

        foreach ($sections as $section) {
            $sectionLines = $this->normalizeMessageLines($section['lines'] ?? []);

            if ($sectionLines === []) {
                continue;
            }

            $formattedLines = [];
            $sectionTitle = trim((string) ($section['title'] ?? ''));

            if ($sectionTitle !== '') {
                $formattedLines[] = '*'.$sectionTitle.'*';
            }

            $style = trim((string) ($section['style'] ?? 'bullet'));

            foreach ($sectionLines as $index => $line) {
                $formattedLines[] = match ($style) {
                    'numbered' => ($index + 1).'. '.$line,
                    'plain' => $line,
                    default => '- '.$line,
                };
            }

            $blocks[] = $formattedLines;
        }

        $normalizedClosingLines = $this->normalizeMessageLines($closingLines);

        if ($normalizedClosingLines !== []) {
            $blocks[] = $normalizedClosingLines;
        }

        return trim(collect($blocks)
            ->filter(fn (mixed $block) => is_array($block) && $block !== [])
            ->map(fn (array $block) => implode("\n", $block))
            ->implode("\n\n"));
    }

    public function normalizeWhatsappNumber(?string $number): string
    {
        $normalized = preg_replace('/\D+/', '', (string) $number);

        if (! is_string($normalized) || $normalized === '') {
            return '';
        }

        if (Str::startsWith($normalized, '0')) {
            return '62'.substr($normalized, 1);
        }

        if (Str::startsWith($normalized, '8')) {
            return '62'.$normalized;
        }

        return $normalized;
    }

    /**
     * @param  array<int, string>  $lines
     * @return array<int, string>
     */
    private function normalizeMessageLines(array $lines): array
    {
        return collect($lines)
            ->map(fn (string $line) => trim($line))
            ->filter()
            ->values()
            ->all();
    }

    private function request(): PendingRequest
    {
        $request = Http::baseUrl($this->resolveBaseUrl())
            ->acceptJson()
            ->asJson()
            ->timeout((int) config('services.lyvaflow.timeout', 15))
            ->withToken((string) config('services.lyvaflow.api_key'));

        if (! (bool) config('services.lyvaflow.verify_ssl', true)) {
            $request = $request->withoutVerifying();
        }

        return $request;
    }

    private function resolveBaseUrl(): string
    {
        $baseUrl = trim((string) config('services.lyvaflow.base_url', ''));

        if ($baseUrl === '') {
            return '';
        }

        if (! preg_match('#^https?://#i', $baseUrl)) {
            $baseUrl = 'https://'.ltrim($baseUrl, '/');
        }

        return rtrim($baseUrl, '/');
    }
}
