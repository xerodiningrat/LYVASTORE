<?php

namespace App\Http\Controllers;

use App\Services\LyvaCoinService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LyvaCoinController extends Controller
{
    public function __invoke(Request $request, LyvaCoinService $lyvaCoinService): Response
    {
        $user = $request->user();

        return Inertia::render('account/Coins', [
            'balance' => $lyvaCoinService->balanceForUser($user),
            'rewardRate' => $lyvaCoinService->rewardRateLabel(),
            'transactionCount' => $lyvaCoinService->rewardedTransactionCountForUser($user),
            'recentRewards' => $user ? $lyvaCoinService->recentRewardsForUser($user)->values()->all() : [],
        ]);
    }
}
