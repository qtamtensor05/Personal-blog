<?php

declare(strict_types=1);

namespace App\Presentation\Web\Controllers;

use App\Application\Identity\Exceptions\InvalidPasswordResetToken;
use App\Application\Identity\UseCases\ResetPassword\ResetPasswordUseCase;
use App\Presentation\Web\Requests\ResetPasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;

final class ResetPasswordController extends Controller
{
    public function __invoke(
        ResetPasswordRequest $request,
        ResetPasswordUseCase $resetPassword,
    ): RedirectResponse {
        $data = $request->validated();

        try {
            $resetPassword->execute($data['email'], $data['token'], $data['password']);
        } catch (InvalidPasswordResetToken $exception) {
            throw ValidationException::withMessages(['email' => $exception->getMessage()]);
        }

        return redirect()
            ->route('login')
            ->with('status', 'Your password has been reset. Please sign in.');
    }
}
