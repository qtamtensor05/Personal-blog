<?php

declare(strict_types=1);

namespace App\Presentation\Web\Controllers;

use App\Presentation\Web\Requests\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

final class LoginController extends Controller
{
    public function __invoke(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->safe()->only(['email', 'password']);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'These credentials do not match our records.',
            ]);
        }

        $user = Auth::user();

        // The database user provider does not filter soft-deleted rows.
        if (
            $user === null ||
            $user->deleted_at !== null ||
            $user->status !== 'ACTIVE' ||
            $user->email_verified_at === null
        ) {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => 'Your account must be active and email verified before signing in.',
            ]);
        }

        $request->session()->regenerate();

        $request->session()->forget('url.intended');

        return redirect()->route('home');
    }
}
