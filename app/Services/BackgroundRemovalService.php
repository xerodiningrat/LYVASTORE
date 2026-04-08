<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;

class BackgroundRemovalService
{
    private const STORAGE_DISK = 'public';

    private const STORAGE_DIRECTORY = 'background-remover-public';

    /**
     * @return array{
     *     ready: bool,
     *     message: string,
     *     installCommand: string,
     *     pythonBinary: string,
     *     scriptPath: string,
     *     modelDirectory: string,
     *     timeoutSeconds: int,
     *     tempRetentionMinutes: int
     * }
     */
    public function diagnostics(): array
    {
        $this->cleanupExpiredFiles();

        $pythonBinary = $this->pythonBinary();
        $scriptPath = $this->scriptPath();
        $modelDirectory = $this->modelDirectory();
        $timeoutSeconds = $this->timeoutSeconds();
        $tempRetentionMinutes = $this->tempRetentionMinutes();

        if (! is_file($scriptPath)) {
            return [
                'ready' => false,
                'message' => 'Script Python untuk hapus background belum ditemukan.',
                'installCommand' => $this->installCommand(),
                'pythonBinary' => $pythonBinary,
                'scriptPath' => $scriptPath,
                'modelDirectory' => $modelDirectory,
                'timeoutSeconds' => $timeoutSeconds,
                'tempRetentionMinutes' => $tempRetentionMinutes,
            ];
        }

        if ($this->binaryLooksLikePath($pythonBinary) && ! is_file($pythonBinary)) {
            return [
                'ready' => false,
                'message' => 'Binary Python untuk background remover belum ada. Buat virtual environment lalu install dependensinya.',
                'installCommand' => $this->installCommand(),
                'pythonBinary' => $pythonBinary,
                'scriptPath' => $scriptPath,
                'modelDirectory' => $modelDirectory,
                'timeoutSeconds' => $timeoutSeconds,
                'tempRetentionMinutes' => $tempRetentionMinutes,
            ];
        }

        $process = new Process([
            $pythonBinary,
            '-c',
            'import importlib.util, json; missing=[name for name in ("rembg",) if importlib.util.find_spec(name) is None]; print(json.dumps({"missing": missing}))',
        ]);
        $process->setTimeout(20);
        $process->run();

        if (! $process->isSuccessful()) {
            return [
                'ready' => false,
                'message' => 'Python untuk background remover belum siap: '.$this->processError($process),
                'installCommand' => $this->installCommand(),
                'pythonBinary' => $pythonBinary,
                'scriptPath' => $scriptPath,
                'modelDirectory' => $modelDirectory,
                'timeoutSeconds' => $timeoutSeconds,
                'tempRetentionMinutes' => $tempRetentionMinutes,
            ];
        }

        $payload = json_decode($process->getOutput(), true);
        $missingPackages = is_array($payload['missing'] ?? null) ? $payload['missing'] : [];

        if ($missingPackages !== []) {
            return [
                'ready' => false,
                'message' => 'Dependensi Python belum lengkap: '.implode(', ', $missingPackages).'.',
                'installCommand' => $this->installCommand(),
                'pythonBinary' => $pythonBinary,
                'scriptPath' => $scriptPath,
                'modelDirectory' => $modelDirectory,
                'timeoutSeconds' => $timeoutSeconds,
                'tempRetentionMinutes' => $tempRetentionMinutes,
            ];
        }

        return [
            'ready' => true,
            'message' => 'Tool siap dipakai. File upload dan hasil PNG hanya disimpan sementara lalu dihapus otomatis setelah melewati batas waktu.',
            'installCommand' => $this->installCommand(),
            'pythonBinary' => $pythonBinary,
            'scriptPath' => $scriptPath,
            'modelDirectory' => $modelDirectory,
            'timeoutSeconds' => $timeoutSeconds,
            'tempRetentionMinutes' => $tempRetentionMinutes,
        ];
    }

    /**
     * @return array{
     *     originalName: string,
     *     originalUrl: string,
     *     resultName: string,
     *     resultUrl: string,
     *     processedAtLabel: string,
     *     outputSizeLabel: string
     * }
     */
    public function removeBackground(UploadedFile $image, ?int $userId = null): array
    {
        $this->cleanupExpiredFiles();

        $tool = $this->diagnostics();

        if (! $tool['ready']) {
            throw new RuntimeException($tool['message']);
        }

        $disk = Storage::disk(self::STORAGE_DISK);
        $jobId = (string) Str::uuid();
        $filePrefix = 'user-'.($userId ?: 'guest').'-'.$jobId;
        $originalExtension = Str::lower($image->getClientOriginalExtension() ?: $image->guessExtension() ?: 'png');
        $storageRoot = $disk->path(self::STORAGE_DIRECTORY);
        $originalPath = self::STORAGE_DIRECTORY.'/'.$filePrefix.'-original.'.$originalExtension;
        $resultPath = self::STORAGE_DIRECTORY.'/'.$filePrefix.'-background-removed.png';

        // Keep request-time writes in a single known-good directory to avoid nested mkdir permission issues.
        File::ensureDirectoryExists($storageRoot);
        File::ensureDirectoryExists($this->modelDirectory());
        File::ensureDirectoryExists($this->numbaCacheDirectory());
        $storedOriginalPath = $image->storeAs(self::STORAGE_DIRECTORY, basename($originalPath), self::STORAGE_DISK);
        $resultAbsolutePath = $disk->path($resultPath);

        if (! is_string($storedOriginalPath) || $storedOriginalPath === '') {
            throw new RuntimeException('File asli gagal disimpan ke storage publik.');
        }

        File::ensureDirectoryExists(dirname($resultAbsolutePath));

        $process = new Process([
            $this->pythonBinary(),
            $this->scriptPath(),
            '--input',
            $disk->path($originalPath),
            '--output',
            $resultAbsolutePath,
            '--model',
            $this->modelName(),
        ], null, [
            'U2NET_HOME' => $this->modelDirectory(),
            'NUMBA_CACHE_DIR' => $this->numbaCacheDirectory(),
            'HOME' => base_path(),
        ]);

        $process->setTimeout($this->timeoutSeconds());

        try {
            $process->run();
        } catch (Throwable $exception) {
            $disk->delete([$originalPath, $resultPath]);

            throw new RuntimeException('Engine background remover dihentikan sistem. Coba pakai gambar yang lebih ringan atau ulangi prosesnya.', previous: $exception);
        }

        if (! $process->isSuccessful()) {
            $disk->delete([$originalPath, $resultPath]);

            throw new RuntimeException($this->processError($process));
        }

        if (! $disk->exists($resultPath)) {
            $disk->delete([$originalPath, $resultPath]);

            throw new RuntimeException('Proses selesai, tetapi file PNG transparan tidak ditemukan.');
        }

        return [
            'originalName' => (string) $image->getClientOriginalName(),
            'originalUrl' => $disk->url($originalPath),
            'resultName' => pathinfo((string) $image->getClientOriginalName(), PATHINFO_FILENAME).'-no-bg.png',
            'resultUrl' => $disk->url($resultPath),
            'processedAtLabel' => now()->timezone('Asia/Jakarta')->locale('id')->translatedFormat('d M Y, H:i'),
            'outputSizeLabel' => $this->humanFileSize((int) $disk->size($resultPath)),
        ];
    }

    public function cleanupExpiredFiles(): int
    {
        $disk = Storage::disk(self::STORAGE_DISK);

        if (! $disk->exists(self::STORAGE_DIRECTORY)) {
            return 0;
        }

        $expiredBefore = CarbonImmutable::now()->subMinutes($this->tempRetentionMinutes())->timestamp;
        $deleted = 0;

        foreach ($disk->allFiles(self::STORAGE_DIRECTORY) as $path) {
            try {
                if ($disk->lastModified($path) > $expiredBefore) {
                    continue;
                }

                if ($disk->delete($path)) {
                    $deleted++;
                }
            } catch (Throwable) {
                // Keep cleanup best-effort so upload flow never breaks because of stale files.
            }
        }

        return $deleted;
    }

    private function pythonBinary(): string
    {
        return (string) config('services.background_remover.python_binary');
    }

    private function scriptPath(): string
    {
        return (string) config('services.background_remover.script_path');
    }

    private function modelDirectory(): string
    {
        return (string) config('services.background_remover.model_path');
    }

    private function numbaCacheDirectory(): string
    {
        return storage_path('app/background-remover/numba-cache');
    }

    private function modelName(): string
    {
        return (string) config('services.background_remover.model', 'u2netp');
    }

    private function timeoutSeconds(): int
    {
        return max(30, (int) config('services.background_remover.timeout', 180));
    }

    private function tempRetentionMinutes(): int
    {
        return max(1, (int) config('services.background_remover.temp_retention_minutes', 15));
    }

    private function installCommand(): string
    {
        return 'python3 -m venv .venv-background-remover && .venv-background-remover/bin/pip install -r requirements/background-remover.txt';
    }

    private function binaryLooksLikePath(string $binary): bool
    {
        return str_contains($binary, '/')
            || str_contains($binary, '\\')
            || Str::startsWith($binary, '.');
    }

    private function processError(Process $process): string
    {
        $message = trim($process->getErrorOutput());

        if ($message === '') {
            $message = trim($process->getOutput());
        }

        if ($message === '') {
            $message = 'Proses background remover gagal dijalankan.';
        }

        return preg_replace('/\s+/', ' ', $message) ?: 'Proses background remover gagal dijalankan.';
    }

    private function humanFileSize(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $power = min((int) floor(log($bytes, 1024)), count($units) - 1);

        return number_format($bytes / (1024 ** $power), $power === 0 ? 0 : 1, ',', '.').' '.$units[$power];
    }
}
