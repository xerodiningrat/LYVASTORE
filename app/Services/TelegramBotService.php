<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class TelegramBotService
{
    public function configured(): bool
    {
        return filled(config('services.telegram.bot_token')) && filled(config('services.telegram.chat_id'));
    }

    /**
     * @return array<string, mixed>
     */
    public function sendMessage(string $text, array $payload = []): array
    {
        if (! $this->configured()) {
            throw new RuntimeException('Telegram bot belum dikonfigurasi.');
        }

        $response = $this->request()->post('/sendMessage', [
            'chat_id' => (string) config('services.telegram.chat_id'),
            'text' => trim($text),
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true,
            ...$payload,
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('Telegram bot gagal mengirim notifikasi.');
        }

        return is_array($response->json()) ? $response->json() : [];
    }

    private function request(): PendingRequest
    {
        return Http::baseUrl('https://api.telegram.org/bot'.trim((string) config('services.telegram.bot_token')))
            ->acceptJson()
            ->asJson()
            ->timeout((int) config('services.telegram.timeout', 15));
    }
}
