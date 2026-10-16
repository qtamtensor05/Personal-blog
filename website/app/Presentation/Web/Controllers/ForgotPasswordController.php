<?php

declare(strict_types=1);

namespace App\Presentation\Web\Controllers;

use App\Application\Identity\UseCases\RequestPasswordReset\RequestPasswordResetUseCase;
use App\Presentation\Web\Requests\ForgotPasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Throwable;

final class ForgotPasswordController extends Controller
{
    public function __invoke(
        ForgotPasswordRequest $request,
        RequestPasswordResetUseCase $requestPasswordReset,
    ): RedirectResponse {
        try {
            $requestPasswordReset->execute($request->validated('email'));
        } catch (Throwable $exception) {
            // SMTP failures must not reveal whether the account exists.
            Log::error('Password reset email failed.', ['exception' => $exception]);
        }

        return back()->with(
            'status',
            'If an account exists for that email, a password reset link has been sent.',
        );
    }
}
