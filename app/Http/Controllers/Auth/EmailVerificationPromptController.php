<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Az e-mail megerősítésre váró felhasználókat a megfelelő felületre irányítja.
 */
class EmailVerificationPromptController extends Controller
{
    /**
     * A hitelesített, de még nem megerősített felhasználónak megjeleníti a verify oldalt.
     */
    public function __invoke(Request $request): RedirectResponse|Response
    {
        return $request->user()->hasVerifiedEmail()
                    ? redirect()->intended(route('dashboard', absolute: false))
                    : Inertia::render('Auth/VerifyEmail', ['status' => session('status')]);
    }
}
