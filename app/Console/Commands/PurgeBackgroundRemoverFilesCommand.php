<?php

namespace App\Console\Commands;

use App\Services\BackgroundRemovalService;
use Illuminate\Console\Command;

class PurgeBackgroundRemoverFilesCommand extends Command
{
    protected $signature = 'lyva:background-remover:purge';

    protected $description = 'Delete expired temporary files created by the background remover tool';

    public function handle(BackgroundRemovalService $backgroundRemoval): int
    {
        $deleted = $backgroundRemoval->cleanupExpiredFiles();

        $this->info("Background remover cleanup selesai. File dihapus: {$deleted}.");

        return self::SUCCESS;
    }
}
