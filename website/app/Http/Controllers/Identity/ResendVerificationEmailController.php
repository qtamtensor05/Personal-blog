<?php

declare(strict_types=1);

namespace App\Http\Controllers\Identity;

use App\Application\Identity\UseCases\ResendVerificationEmail\ResendVerificationEmailUseCase;
use App\Http\Controllers\Controller;
use App\Http\Requests\Identity\ResendVerificationEmailRequest;
use Illuminate\Http\RedirectResponse;

final class ResendVerificationEmailController extends Controller
{
    public function __invoke(
        ResendVerificationEmailRequest $request,
        ResendVerificationEmailUseCase $resendVerificationEmail,
    ): RedirectResponse {
        $resendVerificationEmail->execute(
            $request->validated('email'),
        );

        return back()->with(
            'status',
            'If verification is required, an email will be sent.',
        );
    }
}
