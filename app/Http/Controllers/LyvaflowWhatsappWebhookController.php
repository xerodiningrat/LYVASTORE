<?php

namespace App\Http\Controllers;

use App\Services\WhatsappCommerceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class LyvaflowWhatsappWebhookController extends Controller
{
    public function __invoke(Request $request, WhatsappCommerceService $commerce): JsonResponse
    {
        $expectedSecret = trim((string) config('services.lyvaflow.incoming_webhook_secret'));
        $providedSecret = trim((string) $request->header('X-Lyvaflow-Webhook-Secret', ''));

        if ($expectedSecret === '' || ! hash_equals($expectedSecret, $providedSecret)) {
          abort(403, 'Webhook secret tidak valid.');
        }

        $validated = $request->validate([
            'userId' => ['nullable', 'string', 'max:120'],
            'sessionKey' => ['nullable', 'string', 'max:190'],
            'jid' => ['required', 'string', 'max:190'],
            'phoneNumber' => ['required', 'string', 'max:40'],
            'senderName' => ['nullable', 'string', 'max:190'],
            'text' => ['required', 'string', 'max:4000'],
            'messageId' => ['nullable', 'string', 'max:190'],
            'messageType' => ['nullable', 'string', 'max:40'],
            'time' => ['nullable', 'string', 'max:80'],
        ]);

        return response()->json($commerce->handleIncomingMessage($validated));
    }
}
