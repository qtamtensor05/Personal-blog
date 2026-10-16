<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateEmailRequest;
use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Modules\Identity\Actions\UpdateAccountAction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

final class AccountController extends Controller
{
    public function show(Request $request): Response
    {
        return response()
            ->view('auth.account', [
                'account' => $request->user(),
            ])
            ->header('Cache-Control', 'no-store, private');
    }

    public function updateProfile(
        UpdateProfileRequest $request,
        UpdateAccountAction $action,
    ): RedirectResponse {
        $action->execute(
            $request->user(),
            'profile',
            $request->validated(),
        );

        return redirect('/account')->with(
            'status',
            'Đã cập nhật hồ sơ.',
        );
    }

    public function updateEmail(
        UpdateEmailRequest $request,
        UpdateAccountAction $action,
    ): RedirectResponse {
        $user = $request->user();

        $stamp = $action->execute(
            $user,
            'email',
            $request->validated(),
        );

        $user->refresh();

        $request->session()->put('identity_stamp', $stamp);
        $request->session()->forget('platform_authenticated_user');
        $request->session()->regenerate(true);

        try {
            $user->sendEmailVerificationNotification();
        } catch (\Throwable $exception) {
            report($exception);

            return redirect('/email/verify')->with(
                'status',
                'Email đã đổi nhưng chưa gửi được thư xác minh. Hãy thử gửi lại.',
            );
        }

        return redirect('/email/verify')->with(
            'status',
            'Đã đổi email. Hãy xác minh địa chỉ mới trước khi tiếp tục.',
        );
    }

    public function updatePassword(
        UpdatePasswordRequest $request,
        UpdateAccountAction $action,
    ): RedirectResponse {
        $action->execute(
            $request->user(),
            'password',
            $request->validated(),
        );

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login')->with(
            'status',
            'Đã đổi mật khẩu. Vui lòng đăng nhập lại.',
        );
    }
}
