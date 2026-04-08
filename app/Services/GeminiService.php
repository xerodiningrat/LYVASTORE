<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class GeminiService
{
    public function configured(): bool
    {
        return filled(config('services.gemini.api_key'));
    }

    public function model(): string
    {
        return (string) config('services.gemini.model', 'gemini-2.5-pro');
    }

    public function generateText(string $prompt): string
    {
        if (! $this->configured()) {
            throw new RuntimeException('Gemini API key belum dikonfigurasi.');
        }

        $response = Http::timeout((int) config('services.gemini.timeout', 20))
            ->acceptJson()
            ->post(sprintf(
                'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent?key=%s',
                $this->model(),
                urlencode((string) config('services.gemini.api_key')),
            ), [
                'contents' => [[
                    'parts' => [[
                        'text' => $prompt,
                    ]],
                ]],
                'generationConfig' => [
                    'temperature' => 0.7,
                    'maxOutputTokens' => 512,
                ],
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Gemini request failed with status '.$response->status().': '.$response->body());
        }

        $text = trim((string) data_get($response->json(), 'candidates.0.content.parts.0.text', ''));

        if ($text === '') {
            throw new RuntimeException('Gemini reply kosong.');
        }

        return $text;
    }
}
