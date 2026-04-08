<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmailVerificationPromptController extends Controller
{
    /**
     * Show the email verification prompt page.
     */
    public function __invoke(Request $request): RedirectResponse|Response
    {
        $redirectTo = $request->user()?->canAccessAdminPanel()
            ? route('dashboard', absolute: false)
            : route('home', absolute: false);

        return $request->user()->hasVerifiedEmail()
                    ? redirect()->intended($redirectTo)
                    : Inertia::render('auth/VerifyEmail', ['status' => $request->session()->get('status')]);
    }
}
