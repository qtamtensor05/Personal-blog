<?php

declare(strict_types=1);

namespace App\Http\Controllers\Identity;

use App\Application\Identity\Exceptions\EmailAlreadyRegistered;
use App\Application\Identity\UseCases\RegisterUser\RegisterUserUseCase;
use App\Http\Controllers\Controller;
use App\Http\Requests\Identity\RegisterUserRequest;
use Illuminate\Http\RedirectResponse;
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

        return redirect()->route('login')
            ->with('status', 'Account created. Please verify your email.');
    }
}
