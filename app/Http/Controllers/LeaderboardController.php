<?php

namespace App\Http\Controllers;

use App\Services\LeaderboardService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LeaderboardController extends Controller
{
    public function __invoke(Request $request, LeaderboardService $leaderboardService): Response
    {
        return Inertia::render('Leaderboard', [
            'defaultBoard' => LeaderboardService::PERIOD_MONTHLY,
            'boards' => $leaderboardService->boardsFor($request->user()),
        ]);
    }
}
