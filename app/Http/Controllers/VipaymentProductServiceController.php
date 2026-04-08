<?php

namespace App\Http\Controllers;

use App\Services\VipaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class VipaymentProductServiceController extends Controller
{
    public function __invoke(Request $request, string $product, VipaymentService $vipayment): JsonResponse
    {
        if (! $vipayment->configured()) {
            return response()->json([
                'message' => 'VIPayment belum dikonfigurasi. Isi `VIPAYMENT_API_ID` dan `VIPAYMENT_API_KEY` terlebih dulu.',
                'data' => [],
                'source' => 'fallback',
            ], 503);
        }

        try {
            $services = $vipayment->getProductServices($product);

            if ($services === null) {
                return response()->json([
                    'message' => 'Produk ini belum punya mapping VIPayment. Data lokal tetap dipakai.',
                    'data' => [],
                    'source' => 'fallback',
                ]);
            }

            if ($services === []) {
                return response()->json([
                    'message' => 'VIPayment belum mengembalikan layanan untuk produk ini. Data lokal tetap dipakai.',
                    'data' => [],
                    'source' => 'fallback',
                ]);
            }

            return response()->json([
                'message' => 'Produk berhasil diambil dari VIPayment.',
                'data' => $services,
                'source' => 'vipayment',
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => $exception->getMessage() ?: 'Gagal mengambil data produk dari VIPayment.',
                'data' => [],
                'source' => 'fallback',
            ], 502);
        }
    }
}
