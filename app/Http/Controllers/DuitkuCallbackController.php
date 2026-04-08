<?php

namespace App\Http\Controllers;

use App\Services\DuitkuService;
use App\Services\TransactionService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DuitkuCallbackController extends Controller
{
    public function __invoke(Request $request, DuitkuService $duitku, TransactionService $transactions): Response
    {
        $payload = $request->all();

        if (! $duitku->verifyCallbackSignature($payload)) {
            return response('INVALID', 422);
        }

        $transactions->handleDuitkuCallback($payload);

        return response('OK');
    }
}
