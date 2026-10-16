<?php

namespace App\Http\Controllers;

use App\Modules\Identity\Actions\ResetUserPasswordAction;
use App\Modules\Identity\Actions\SendPasswordResetLinkAction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;

final class PasswordController extends Controller
{
    public function send(
        Request $request,
        SendPasswordResetLinkAction $action,
    ): RedirectResponse {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $action->execute($request->string('email')->toString());

        return back()->with(
            'status',
            'Nếu tài khoản hợp lệ, email đặt lại mật khẩu sẽ được gửi. Vui lòng chờ trước khi thử lại.',
        );
    }

    public function reset(
        Request $request,
        ResetUserPasswordAction $action,
    ): RedirectResponse {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => [
                'required',
                'confirmed',
                PasswordRule::defaults(),
            ],
        ]);

        $status = $action->execute($data);

        if ($status === Password::PasswordReset) {
            return redirect('/login')->with(
                'status',
                'Đã đặt lại mật khẩu. Vui lòng đăng nhập.',
            );
        }

        return back()
            ->withErrors([
                'email' => 'Liên kết không hợp lệ, đã dùng hoặc hết hạn. Hãy yêu cầu email mới.',
            ])
            ->onlyInput('email');
    }
}
