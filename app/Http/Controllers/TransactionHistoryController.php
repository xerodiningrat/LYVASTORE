<?php

namespace App\Http\Controllers;

use App\Services\TransactionService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TransactionHistoryController extends Controller
{
    public function __invoke(Request $request, TransactionService $transactions): Response
    {
        $history = $transactions->getVisibleTransactions($request)
            ->map(fn ($transaction) => $transactions->toHistoryPayload($transaction))
            ->values()
            ->all();

        return Inertia::render('CheckTransaction', [
            'transactions' => $history,
        ]);
    }
}
