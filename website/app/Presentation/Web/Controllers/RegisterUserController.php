<?php

declare(strict_types=1);

namespace App\Presentation\Web\Controllers;

use App\Application\Identity\Exceptions\EmailAlreadyRegistered;
use App\Application\Identity\UseCases\RegisterUser\RegisterUserUseCase;
use App\Application\PlatformSettings\UseCases\ViewPlatformSettings\ViewPlatformSettingsService;
use App\Presentation\Web\Requests\RegisterUserRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;

final class RegisterUserController extends Controller
{
    public function __invoke(
        RegisterUserRequest $request,
        RegisterUserUseCase $registerUser,
        ViewPlatformSettingsService $platformSettings,
    ): RedirectResponse {
        $data = $request->validated();

        if (! $platformSettings->execute()->data->registrationEnabled) {
            throw ValidationException::withMessages([
                'email' => 'New account registration is currently disabled.',
            ]);
        }

        try {
            $user = $registerUser->execute(
                name: $data['name'],
                email: $data['email'],
                password: $data['password'],
            );
        } catch (EmailAlreadyRegistered) {
            throw ValidationException::withMessages([
                'email' => 'Email address is already registered.',
            ]);
        }

        if ($user->emailVerifiedAt() !== null) {
            return redirect()
                ->route('login')
                ->with('status', 'Account created successfully! You can sign in now.');
        }

        return redirect()
            ->route('login')
            ->with(
                'status',
                'Your account has been created. We have sent a verification email to your email address. Please check your inbox and spam folder before signing in.'
            );
    }
}
