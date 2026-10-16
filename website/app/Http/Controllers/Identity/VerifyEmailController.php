<?php

declare(strict_types=1);

namespace App\Http\Controllers\Identity;

use App\Application\Identity\UseCases\VerifyEmail\VerifyEmailUseCase;
use App\Http\Controllers\Controller;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class VerifyEmailController extends Controller
{
    public function __invoke(
        Request $request,
        int $user,
        VerifyEmailUseCase $verifyEmail,
    ): RedirectResponse {
        abort_unless($request->hasValidSignature(), 403);
        try {
            $verifyEmail->execute($user);
        } catch (DomainException) {
            abort(403, 'This email verification link cannot be used.');
        }

        return redirect('/')
            ->with('status', 'Email verified successfully.');
    }
}
