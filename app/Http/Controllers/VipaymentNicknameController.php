<?php

namespace App\Http\Controllers;

use App\Services\VipaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class VipaymentNicknameController extends Controller
{
    public function __invoke(Request $request, string $product, VipaymentService $vipayment): JsonResponse
    {
        $validated = $request->validate([
            'target' => ['required', 'string', 'max:120'],
            'zone' => ['nullable', 'string', 'max:120'],
        ]);

        if (! $vipayment->configured()) {
            return response()->json([
                'status' => 'unavailable',
                'message' => 'VIPayment belum dikonfigurasi.',
            ], 503);
        }

        if (! $vipayment->supportsNicknameLookup($product)) {
            return response()->json([
                'status' => 'unsupported',
                'message' => 'Cek username belum tersedia untuk produk ini.',
            ]);
        }

        try {
            $nickname = $vipayment->lookupGameNickname(
                $product,
                (string) ($validated['target'] ?? ''),
                $validated['zone'] ?? null,
            );

            if (! is_string($nickname) || $nickname === '') {
                return response()->json([
                    'status' => 'not_found',
                    'message' => 'Username tidak ditemukan.',
                ]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Nickname berhasil ditemukan.',
                'nickname' => $nickname,
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'status' => 'error',
                'message' => $exception->getMessage() ?: 'Gagal mengecek username.',
            ], 422);
        }
    }
}
