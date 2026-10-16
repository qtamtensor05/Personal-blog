<?php

declare(strict_types=1);

namespace App\Presentation\Web\Controllers;

use App\Application\Identity\UseCases\ResendVerificationEmail\ResendVerificationEmailUseCase;
use App\Presentation\Web\Requests\ResendVerificationEmailRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;

final class ResendVerificationEmailController extends Controller
{
    public function __invoke(
        ResendVerificationEmailRequest $request,
        ResendVerificationEmailUseCase $resendVerificationEmail,
    ): RedirectResponse {
        $resendVerificationEmail->execute($request->validated('email'));

        return back()->with('status', 'If verification is required, an email will be sent.');
    }
}
