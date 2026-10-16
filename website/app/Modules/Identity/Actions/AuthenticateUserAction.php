<?php

namespace App\Modules\Identity\Actions;

use App\Modules\Identity\Contracts\TokenService;
use App\Modules\Identity\Contracts\UserRepository;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

final class AuthenticateUserAction
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly TokenService $tokens,
    ) {}

    public function execute(string $email, string $password): array
    {
        $user = $this->users->findByEmailForAuthentication($email);

        if ($user === null || ! Hash::check($password, $user->password)) {
            throw new AuthenticationException('Thông tin đăng nhập không hợp lệ.');
        }

        if (
            $user->status === 'PENDING_VERIFICATION'
            && ! $user->hasVerifiedEmail()
        ) {
            throw ValidationException::withMessages([
                'email' => 'Vui lòng xác minh email trước khi đăng nhập.',
            ]);
        }

        if ($user->locked_until?->isFuture()) {
            throw ValidationException::withMessages([
                'email' => 'Tài khoản hiện đang bị khóa.',
            ]);
        }

        if (! $user->canAccessApplication()) {
            throw new AuthenticationException('Tài khoản không được phép truy cập.');
        }

        $token = $this->tokens->issue($user);

        $this->users->recordSuccessfulLogin((int) $user->getKey());

        return [
            'user' => $user,
            'access_token' => $token,
        ];
    }
}
