<?php

namespace App\Http\Controllers;

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

final class PasswordController extends Controller
{
    public function send(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        Password::sendResetLink($request->only('email'));

        return back()->with('status', 'Nếu tài khoản hợp lệ, email đặt lại mật khẩu sẽ được gửi. Vui lòng chờ trước khi thử lại.');
    }

    public function reset(Request $request)
    {
        $data = $request->validate(['token' => 'required', 'email' => 'required|email', 'password' => ['required', 'confirmed', PasswordRule::defaults()]]);
        $status = Password::reset($data, function ($user, $password): void {
            $user->forceFill(['password' => Hash::make($password), 'remember_token' => Str::random(60)])->save();
            \DB::table('sessions')->where('user_id', $user->id)->delete();
            event(new PasswordReset($user));
        });

        return $status === Password::PasswordReset
            ? redirect('/login')->with('status', 'Đã đặt lại mật khẩu. Vui lòng đăng nhập.')
            : back()->withErrors(['email' => 'Liên kết không hợp lệ, đã dùng hoặc hết hạn. Hãy yêu cầu email mới.'])->onlyInput('email');
    }
}
