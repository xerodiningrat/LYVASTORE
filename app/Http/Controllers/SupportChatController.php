<?php

namespace App\Http\Controllers;

use App\Services\SupportChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupportChatController extends Controller
{
    public function __invoke(Request $request, SupportChatService $supportChat): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:1000'],
            'website' => ['nullable', 'string', 'max:255'],
            'formStartedAt' => ['required', 'integer', 'min:1'],
            'history' => ['nullable', 'array', 'max:12'],
            'history.*.role' => ['required_with:history', 'string', 'in:user,assistant'],
            'history.*.content' => ['required_with:history', 'string', 'max:2000'],
        ]);

        $result = $supportChat->reply(
            (string) $validated['message'],
            is_array($validated['history'] ?? null) ? $validated['history'] : [],
        );

        return response()->json([
            'message' => 'Balasan support berhasil dibuat.',
            'data' => [
                'reply' => $result['reply'],
                'provider' => $result['provider'],
            ],
        ]);
    }
}
