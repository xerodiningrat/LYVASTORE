<?php

namespace App\Services;

use App\Models\Transaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class SupportChatService
{
    private const ORDER_ID_CANDIDATE_PATTERN = '/\b[A-Z0-9-]{10,24}\b/i';
    private const SUPPORT_WHATSAPP = '085771756364';

    public function __construct(
        private readonly TransactionService $transactions,
    ) {}

    public function configured(): bool
    {
        return filled(config('services.lyva_chatbot.endpoint'));
    }

    /**
     * @param  array<int, array{role?: string, content?: string}>  $history
     * @return array{reply: string, provider: string}
     */
    public function reply(string $message, array $history = []): array
    {
        $normalizedMessage = trim($message);

        if ($normalizedMessage === '') {
            return [
                'reply' => 'Tulis pertanyaanmu dulu ya. Aku siap bantu soal produk, pembayaran, atau cek transaksi.',
                'provider' => 'fallback',
            ];
        }

        $orderStatusReply = $this->generateOrderStatusReply($normalizedMessage);

        if ($orderStatusReply !== null) {
            return [
                'reply' => $orderStatusReply,
                'provider' => 'order-status',
            ];
        }

        try {
            return [
                'reply' => $this->generateLocalBotReply($normalizedMessage),
                'provider' => 'local-bot',
            ];
        } catch (\Throwable $exception) {
            report($exception);
        }

        return [
            'reply' => $this->generateFallbackReply($normalizedMessage),
            'provider' => 'fallback',
        ];
    }

    private function generateLocalBotReply(string $message): string
    {
        $endpoint = trim((string) config('services.lyva_chatbot.endpoint', 'http://127.0.0.1:8200/api/chat'));

        if ($endpoint === '') {
            throw new RuntimeException('Local chatbot endpoint is not configured.');
        }

        $response = Http::acceptJson()
            ->timeout(12)
            ->post(
                $endpoint,
                ['question' => $message],
            );

        if (! $response->successful()) {
            throw new RuntimeException('Local chatbot request failed with status '.$response->status().'.');
        }

        $reply = trim((string) ($response->json('answer') ?? ''));

        if ($reply === '') {
            throw new RuntimeException('Local chatbot reply was empty.');
        }

        return $reply;
    }

    private function generateOrderStatusReply(string $message): ?string
    {
        $publicId = $this->extractOrderId($message);

        if ($publicId === null) {
            if (! $this->looksLikeOrderStatusQuestion($message)) {
                return null;
            }

            return 'Boleh, kirim ID pesanan kamu dulu ya. Contohnya `LYVA123456ABCDEF`, nanti aku cek statusnya untukmu.';
        }

        $transaction = Transaction::query()->where('public_id', $publicId)->first();

        if (! $transaction) {
            return "ID pesanan #{$publicId} belum ketemu di sistem. Coba cek lagi penulisannya ya.";
        }

        $transaction = $this->transactions->syncTransaction($transaction, force: true) ?? $transaction;

        return $this->formatOrderStatusReply($transaction);
    }

    private function extractOrderId(string $message): ?string
    {
        preg_match_all(self::ORDER_ID_CANDIDATE_PATTERN, Str::upper($message), $matches);

        $candidates = collect($matches[0] ?? [])
            ->map(fn (mixed $value): string => trim((string) $value))
            ->filter(fn (string $value): bool => $value !== '' && preg_match('/\d/', $value) === 1)
            ->unique()
            ->values();

        foreach ($candidates as $candidate) {
            if (Transaction::query()->where('public_id', $candidate)->exists()) {
                return $candidate;
            }
        }

        return null;
    }

    private function looksLikeOrderStatusQuestion(string $message): bool
    {
        $value = Str::lower(trim($message));

        return $this->containsAny($value, [
            'cek id pesanan',
            'cek pesanan',
            'status pesanan',
            'status order',
            'cek order',
            'cek transaksi',
            'status transaksi',
            'invoice',
            'id pesanan',
            'id order',
            'nomor pesanan',
        ]);
    }

    private function formatOrderStatusReply(Transaction $transaction): string
    {
        $label = trim((string) ($transaction->package_label ?: $transaction->product_name ?: 'Pesanan'));
        $prefix = "Status ID pesanan #{$transaction->public_id} untuk {$label}:";

        return match ($transaction->status) {
            Transaction::STATUS_PENDING => $transaction->payment_status === Transaction::PAYMENT_STATUS_UNPAID
                ? "{$prefix} masih menunggu pembayaran. Kalau sudah bayar tapi status belum berubah, tunggu sebentar lalu cek lagi ya."
                : "{$prefix} pembayaran sudah masuk dan pesanan sedang disiapkan untuk diproses.",
            Transaction::STATUS_PROCESSING => "{$prefix} saat ini masih diproses.",
            Transaction::STATUS_COMPLETED => "{$prefix} sudah berhasil.",
            Transaction::STATUS_FAILED => "{$prefix} saat ini error. Untuk bantuan lebih lanjut, hubungi WhatsApp ".self::SUPPORT_WHATSAPP.'.',
            Transaction::STATUS_EXPIRED => "{$prefix} sudah expired. Kalau kamu butuh bantuan, hubungi WhatsApp ".self::SUPPORT_WHATSAPP.'.',
            default => "{$prefix} saat ini masih diproses.",
        };
    }

    private function generateFallbackReply(string $message): string
    {
        $value = Str::lower(trim($message));
        $compactValue = preg_replace('/[^a-z0-9]+/', '', $value) ?: '';
        $isDeliveryQuestion = $this->containsAny($value, [
            'berapa lama',
            'kapan',
            'berapa cepat',
            'dikirim',
            'di kirim',
            'masuk',
            'selesai',
            'estimasi',
            'proses',
        ]);
        $isChatGptProduct = $this->containsAny($value, ['chatgpt', 'chat gpt']) || Str::contains($compactValue, 'chatgpt');
        $isCapcutProduct = $this->containsAny($value, ['capcut', 'cap cut']) || Str::contains($compactValue, 'capcut');

        if (Str::contains($value, ['halo', 'hai', 'hello', 'pagi', 'siang', 'malam'])) {
            return 'Halo, aku Lyva Assistant. Aku bisa bantu pilih produk, jelaskan alur checkout, metode pembayaran, atau arahkan cara cek transaksi.';
        }

        if (($isChatGptProduct || $isCapcutProduct) && $isDeliveryQuestion) {
            $productName = $isChatGptProduct ? 'ChatGPT' : 'CapCut Pro';

            return "Untuk {$productName}, pengiriman biasanya diproses manual oleh admin setelah pembayaran masuk. Estimasi amannya sekitar 5-30 menit kalau stok/account ready. Kalau lagi antre atau perlu penyesuaian akun, bisa sedikit lebih lama. Untuk cek pesanan yang sudah dibuat, buka Riwayat Transaksi ya.";
        }

        if (Str::contains($value, ['mobile legends', 'ml', 'free fire', 'pubg', 'genshin', 'hok', 'honor of kings'])) {
            return 'Kalau kamu cari top up game, tinggal sebut nama game dan nominal yang kamu mau. Aku bisa bantu arahkan ke produk yang cocok atau jelaskan cara isi user ID dan zone.';
        }

        if ($isChatGptProduct || $isCapcutProduct || Str::contains($value, ['spotify', 'netflix', 'akun premium'])) {
            return 'Untuk produk akun premium seperti ChatGPT atau CapCut, biasanya kamu pilih paket dulu lalu isi data akun yang diminta saat checkout. Setelah pembayaran masuk, order akan diproses oleh admin. Kalau mau, sebut juga pertanyaanmu, misalnya soal estimasi kirim, alur login, atau status pesanan.';
        }

        if (Str::contains($value, ['bayar', 'pembayaran', 'qris', 'ovo', 'gopay', 'dana', 'transfer', 'va'])) {
            return 'Di LYVA kamu bisa pakai QRIS, e-wallet, dan virtual account sesuai nominal yang dipilih. Kalau pembayaranmu belum masuk, biasanya paling aman cek dulu halaman riwayat transaksi.';
        }

        if (Str::contains($value, ['status', 'pesanan', 'transaksi', 'invoice', 'cek order', 'riwayat'])) {
            return 'Kalau mau cek status pesanan, buka halaman Riwayat Transaksi lalu masukkan data invoice atau identitas transaksi yang kamu punya. Dari situ status pembayaran dan proses order biasanya langsung kelihatan.';
        }

        if (Str::contains($value, ['error', 'gagal', 'kendala', 'bantuan', 'support'])) {
            return 'Kalau ada kendala saat checkout atau pesanan terasa belum masuk, aku sarankan cek Riwayat Transaksi dulu. Kalau masih bermasalah, kirim detail produk, metode bayar, dan waktu transaksi supaya bisa ditelusuri lebih cepat.';
        }

        return 'Aku siap bantu soal produk, pembayaran, atau cek transaksi di LYVA. Coba kirim pertanyaan yang lebih spesifik, misalnya nama produk, metode bayar, atau kendala yang kamu alami.';
    }

    /**
     * @param  array<int, string>  $needles
     */
    private function containsAny(string $value, array $needles): bool
    {
        return collect($needles)->contains(fn (string $needle) => Str::contains($value, Str::lower($needle)));
    }
}
