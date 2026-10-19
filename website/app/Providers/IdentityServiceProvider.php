<?php

declare(strict_types=1);

namespace App\Providers;

use App\Application\Identity\Contracts\PasswordHasher;
use App\Application\Identity\Contracts\PasswordResetEmailSender;
use App\Application\Identity\Contracts\PasswordResetTokenRepository;
use App\Application\Identity\Contracts\UserRepository;
use App\Application\Identity\Contracts\VerificationEmailSender;
use App\Application\Identity\Contracts\VerificationUrlGenerator;
use App\Application\Identity\UseCases\RegisterUser\RegisterUserService;
use App\Application\Identity\UseCases\RegisterUser\RegisterUserUseCase;
use App\Application\Identity\UseCases\RequestPasswordReset\RequestPasswordResetService;
use App\Application\Identity\UseCases\RequestPasswordReset\RequestPasswordResetUseCase;
use App\Application\Identity\UseCases\ResendVerificationEmail\ResendVerificationEmailService;
use App\Application\Identity\UseCases\ResendVerificationEmail\ResendVerificationEmailUseCase;
use App\Application\Identity\UseCases\ResetPassword\ResetPasswordService;
use App\Application\Identity\UseCases\ResetPassword\ResetPasswordUseCase;
use App\Application\Identity\UseCases\SendVerificationEmail\SendVerificationEmailService;
use App\Application\Identity\UseCases\SendVerificationEmail\SendVerificationEmailUseCase;
use App\Application\Identity\UseCases\VerifyEmail\VerifyEmailService;
use App\Application\Identity\UseCases\VerifyEmail\VerifyEmailUseCase;
use App\Infrastructure\Identity\Persistence\QueryBuilderPasswordResetTokenRepository;
use App\Infrastructure\Identity\Persistence\QueryBuilderUserRepository;
use App\Infrastructure\Mail\LaravelPasswordResetEmailSender;
use App\Infrastructure\Mail\LaravelVerificationEmailSender;
use App\Infrastructure\Security\LaravelPasswordHasher;
use App\Infrastructure\Security\LaravelVerificationUrlGenerator;
use Illuminate\Support\ServiceProvider;

final class IdentityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(UserRepository::class, QueryBuilderUserRepository::class);

        $this->app
            ->when(RegisterUserService::class)
            ->needs('$emailVerificationEnabled')
            ->give(fn (): bool => (bool) config('identity.email_verification_enabled'));

        $this->app->bind(PasswordHasher::class, LaravelPasswordHasher::class);

        $this->app->bind(RegisterUserUseCase::class, RegisterUserService::class);

        $this->app->bind(SendVerificationEmailUseCase::class, SendVerificationEmailService::class);

        $this->app->bind(VerificationUrlGenerator::class, LaravelVerificationUrlGenerator::class);
        $this->app->bind(VerifyEmailUseCase::class, VerifyEmailService::class);

        $this->app->bind(VerificationEmailSender::class, LaravelVerificationEmailSender::class);

        $this->app->bind(
            ResendVerificationEmailUseCase::class,
            ResendVerificationEmailService::class,
        );

        $this->app->bind(
            PasswordResetTokenRepository::class,
            QueryBuilderPasswordResetTokenRepository::class,
        );
        $this->app->bind(PasswordResetEmailSender::class, LaravelPasswordResetEmailSender::class);
        $this->app->bind(RequestPasswordResetUseCase::class, RequestPasswordResetService::class);
        $this->app->bind(ResetPasswordUseCase::class, ResetPasswordService::class);
    }
}
