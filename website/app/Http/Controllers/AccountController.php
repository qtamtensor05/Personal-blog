<?php

namespace App\Http\Controllers;

use App\Modules\Identity\Actions\UpdateAccountAction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final class AccountController extends Controller
{
    public function show(Request $request)
    {
        return response()->view('auth.account', ['account' => $request->user()])->header('Cache-Control', 'no-store, private');
    }

    public function update(Request $request, string $section, UpdateAccountAction $action)
    {
        $user = $request->user();
        $action->execute($user, $section, $request->all());
        if ($section === 'password') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect('/login')->with('status', 'Đã đổi mật khẩu và đăng xuất các phiên. Vui lòng đăng nhập lại.');
        }
        if ($section === 'email') {
            $user->refresh();
            $request->session()->forget('platform_authenticated_user');
            $request->session()->regenerate(true);
            try {
                $user->sendEmailVerificationNotification();
            } catch (\Throwable $exception) {
                report($exception);

                return redirect('/email/verify')->with('status', 'Email đã đổi nhưng chưa gửi được thư xác minh. Hãy thử gửi lại.');
            }

            return redirect('/email/verify')->with('status', 'Đã đổi email. Hãy xác minh địa chỉ mới trước khi tiếp tục.');
        }

        return redirect('/account')->with('status', 'Đã cập nhật hồ sơ.');
    }
}
