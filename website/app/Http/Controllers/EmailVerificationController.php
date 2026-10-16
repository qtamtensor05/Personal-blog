<?php

namespace App\Http\Controllers;

use App\Modules\Identity\Actions\ResendEmailVerificationAction;
use App\Modules\Identity\Actions\VerifyCurrentEmailAction;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class EmailVerificationController extends Controller
{
    public function verify(
        EmailVerificationRequest $request,
        VerifyCurrentEmailAction $action,
    ): RedirectResponse {
        $action->execute(
            (int) $request->user()->getKey(),
            (string) $request->route('hash'),
        );

        return redirect('/workspace');
    }

    public function resend(
        Request $request,
        ResendEmailVerificationAction $action,
    ): RedirectResponse {
        $action->execute($request->user());

        return back()->with(
            'status',
            'Đã gửi lại email xác minh.',
        );
    }
}
