<?php

namespace App\Console\Commands;

use App\Services\GeminiService;
use Illuminate\Console\Command;

class GeminiTestCommand extends Command
{
    protected $signature = 'lyva:gemini-test {prompt=Halo, perkenalkan dirimu sebagai asisten LYVA Indonesia.}';

    protected $description = 'Test koneksi Gemini API dari server';

    public function handle(GeminiService $gemini): int
    {
        if (! $gemini->configured()) {
            $this->error('GEMINI_API_KEY belum diisi di .env');

            return self::FAILURE;
        }

        try {
            $reply = $gemini->generateText((string) $this->argument('prompt'));
            $this->info('Gemini connected.');
            $this->newLine();
            $this->line($reply);

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            report($exception);
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
