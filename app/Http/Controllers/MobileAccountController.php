<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Services\TransactionService;
use App\Support\MobileUserAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileAccountController extends Controller
{
    /**
     * @return array<string, string>
     */
    private function corsHeaders(): array
    {
        return [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Accept, Origin, Authorization, X-Lyva-Mobile-Token',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function respond(array $payload, int $status = 200): JsonResponse
    {
        return response()->json($payload, $status)->withHeaders($this->corsHeaders());
    }

    public function history(
        Request $request,
        TransactionService $transactions,
        MobileUserAccess $mobileUserAccess,
    ): JsonResponse {
        $user = $mobileUserAccess->resolveUserFromRequest($request);

        if (! $user) {
            return $this->respond([
                'message' => 'Sesi aplikasi sudah tidak valid. Login ulang dulu ya.',
            ], 401);
        }

        $request->setUserResolver(fn () => $user);

        $entries = $transactions->getVisibleTransactions($request)
            ->take(50)
            ->map(fn (Transaction $transaction) => [
                'checkout' => $transactions->toCheckoutPayload($transaction),
                'accessToken' => $this->makeCheckoutAccessToken($transaction),
            ])
            ->values()
            ->all();

        return $this->respond([
            'message' => 'Riwayat transaksi berhasil diambil.',
            'data' => [
                'entries' => $entries,
            ],
            'meta' => [
                'count' => count($entries),
                'generatedAt' => now()->toIso8601String(),
            ],
        ]);
    }

    private function makeCheckoutAccessToken(Transaction $transaction): string
    {
        $parts = [
            (string) $transaction->public_id,
            (string) ($transaction->customer_email ?? ''),
            (string) ($transaction->customer_whatsapp ?? ''),
            (string) optional($transaction->created_at)->timestamp,
        ];

        return hash_hmac('sha256', implode('|', $parts), (string) config('app.key'));
    }
}
