<?php

declare(strict_types=1);

namespace App\Presentation\Web\Controllers;

use App\Application\Identity\UseCases\VerifyEmail\VerifyEmailUseCase;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

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

        return redirect()
            ->route('verification.success')
            ->with('status', 'Email verified successfully.');
    }
}
