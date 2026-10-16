<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterUserRequest;
use App\Modules\Identity\Actions\AuthenticateWebUserAction;
use App\Modules\Identity\Actions\DetermineLoginAccessAction;
use App\Modules\Identity\Actions\EnsureRegistrationEnabledAction;
use App\Modules\Identity\Actions\RecordSuccessfulLoginAction;
use App\Modules\Identity\Actions\RegisterUserAction;
use App\Modules\Identity\Enums\LoginAccessDecision;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

final class AuthController extends Controller
{
    public function loginForm(): View
    {
        return view('auth.login');
    }

    public function registerForm(): View
    {
        return view('auth.register');
    }

    public function login(
        LoginRequest $request,
        AuthenticateWebUserAction $authenticateWebUser,
        RecordSuccessfulLoginAction $recordSuccessfulLogin,
        DetermineLoginAccessAction $determineLoginAccess,
    ): RedirectResponse {
        $credentials = $request->validated();

        $authenticated = $authenticateWebUser->execute(
            $credentials['email'],
            $credentials['password'],
            $request->boolean('remember'),
        );

        if (! $authenticated) {
            return back()
                ->withErrors([
                    'email' => 'Thông tin đăng nhập không đúng.',
                ])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        $user = $request->user();

        $decision = $determineLoginAccess->execute($user);

        if ($decision === LoginAccessDecision::VerificationRequired) {
            return redirect('/email/verify');
        }

        if ($decision === LoginAccessDecision::Denied) {
            $authenticateWebUser->logout($request);

            return back()->withErrors([
                'email' => 'Tài khoản chưa xác minh hoặc đang bị hạn chế.',
            ]);
        }

        $recordSuccessfulLogin->execute(
            (int) $user->getKey(),
        );

        return redirect()->intended('/workspace');
    }

    public function register(
        RegisterUserRequest $request,
        RegisterUserAction $registerUser,
        EnsureRegistrationEnabledAction $registration,
    ): RedirectResponse {
        abort_unless(
            $registration->execute(),
            403,
            'Đăng ký tạm thời đóng.',
        );

        $user = $registerUser->execute($request->validated());

        Auth::guard('web')->login($user);

        $request->session()->regenerate();

        return redirect('/email/verify');
    }

    public function logout(
        Request $request,
        AuthenticateWebUserAction $authenticateWebUser,
    ): RedirectResponse {
        $authenticateWebUser->logout($request);

        return redirect('/');
    }
}
