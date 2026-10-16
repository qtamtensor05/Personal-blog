<?php

declare(strict_types=1);

namespace App\Presentation\Web\Controllers;

use App\Application\Identity\Exceptions\EmailAlreadyRegistered;
use App\Application\Identity\UseCases\RegisterUser\RegisterUserUseCase;
use App\Presentation\Web\Requests\RegisterUserRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;

final class RegisterUserController extends Controller
{
    public function __invoke(
        RegisterUserRequest $request,
        RegisterUserUseCase $registerUser,
    ): RedirectResponse {
        $data = $request->validated();

        try {
            $registerUser->execute(
                name: $data['name'],
                email: $data['email'],
                password: $data['password'],
            );
        } catch (EmailAlreadyRegistered) {
            throw ValidationException::withMessages([
                'email' => 'Email address is already registered.',
            ]);
        }

        return redirect()
            ->route('verification.notice')
            ->with('status', 'Account created successfully! Please check your email to verify your account.');
    }
}
