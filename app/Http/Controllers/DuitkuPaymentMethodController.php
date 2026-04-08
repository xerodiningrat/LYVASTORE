<?php

namespace App\Http\Controllers;

use App\Services\DuitkuService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class DuitkuPaymentMethodController extends Controller
{
    public function __invoke(Request $request, DuitkuService $duitku): JsonResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'integer', 'min:1000'],
        ]);

        if (! $duitku->configured()) {
            return response()->json([
                'message' => 'Duitku belum dikonfigurasi. Isi `DUITKU_MERCHANT_CODE` dan `DUITKU_API_KEY` terlebih dulu.',
                'data' => [],
            ], 503);
        }

        try {
            return response()->json([
                'message' => 'Metode pembayaran berhasil diambil dari Duitku.',
                'data' => $duitku->getPaymentMethods((int) $validated['amount']),
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => $exception->getMessage() ?: 'Gagal mengambil metode pembayaran dari Duitku.',
                'data' => [],
            ], 502);
        }
    }
}
