<?php

namespace Tests\Unit\Modules\Identity;

use App\Models\User;
use App\Modules\Identity\Actions\AuthenticateUserAction;
use App\Modules\Identity\Contracts\TokenService;
use App\Modules\Identity\Contracts\UserRepository;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuthenticateUserActionTest extends TestCase
{
    private UserRepository $users;

    private TokenService $tokens;

    private AuthenticateUserAction $action;

    protected function setUp(): void
    {
        parent::setUp();

        $this->users = $this->createMock(UserRepository::class);
        $this->tokens = $this->createMock(TokenService::class);

        $this->action = new AuthenticateUserAction(
            $this->users,
            $this->tokens,
        );
    }

    #[Test]
    public function it_rejects_unknown_email(): void
    {
        $this->users
            ->expects($this->once())
            ->method('findByEmailForAuthentication')
            ->with('unknown@example.com')
            ->willReturn(null);

        $this->expectException(AuthenticationException::class);

        $this->action->execute(
            'unknown@example.com',
            'password',
        );
    }

    #[Test]
    public function it_rejects_incorrect_password(): void
    {
        $user = new User;
        $user->password = Hash::make('correct-password');

        $this->users
            ->method('findByEmailForAuthentication')
            ->willReturn($user);

        $this->expectException(AuthenticationException::class);

        $this->action->execute(
            'user@example.com',
            'wrong-password',
        );
    }
}
