<?php

declare(strict_types=1);

namespace App\Providers;

use App\Application\Identity\Contracts\AccessTokenIssuer;
use App\Application\Identity\Contracts\PasswordHasher;
use App\Application\Identity\Contracts\RefreshTokenRepository;
use App\Application\Identity\Contracts\UserRepository;
use App\Application\Identity\Contracts\VerificationEmailSender;
use App\Application\Identity\Contracts\VerificationUrlGenerator;
use App\Application\Identity\Services\TokenPairFactory;
use App\Application\Identity\UseCases\IssueTokens\IssueTokensService;
use App\Application\Identity\UseCases\IssueTokens\IssueTokensUseCase;
use App\Application\Identity\UseCases\RefreshTokens\RefreshTokensService;
use App\Application\Identity\UseCases\RefreshTokens\RefreshTokensUseCase;
use App\Application\Identity\UseCases\RegisterUser\RegisterUserService;
use App\Application\Identity\UseCases\RegisterUser\RegisterUserUseCase;
use App\Application\Identity\UseCases\ResendVerificationEmail\ResendVerificationEmailService;
use App\Application\Identity\UseCases\ResendVerificationEmail\ResendVerificationEmailUseCase;
use App\Application\Identity\UseCases\RevokeRefreshToken\RevokeRefreshTokenService;
use App\Application\Identity\UseCases\RevokeRefreshToken\RevokeRefreshTokenUseCase;
use App\Application\Identity\UseCases\SendVerificationEmail\SendVerificationEmailService;
use App\Application\Identity\UseCases\SendVerificationEmail\SendVerificationEmailUseCase;
use App\Application\Identity\UseCases\VerifyEmail\VerifyEmailService;
use App\Application\Identity\UseCases\VerifyEmail\VerifyEmailUseCase;
use App\Infrastructure\Identity\Persistence\QueryBuilderRefreshTokenRepository;
use App\Infrastructure\Identity\Persistence\QueryBuilderUserRepository;
use App\Infrastructure\Mail\LaravelVerificationEmailSender;
use App\Infrastructure\Security\FirebaseJwtAccessTokenIssuer;
use App\Infrastructure\Security\LaravelPasswordHasher;
use App\Infrastructure\Security\LaravelVerificationUrlGenerator;
use Illuminate\Support\ServiceProvider;

final class IdentityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(UserRepository::class, QueryBuilderUserRepository::class);

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

        $this->app->bind(RefreshTokenRepository::class, QueryBuilderRefreshTokenRepository::class);

        $this->app->singleton(
            AccessTokenIssuer::class,
            fn () => new FirebaseJwtAccessTokenIssuer(
                secret: (string) config('jwt.secret'),
                issuer: (string) config('jwt.issuer'),
                ttlMinutes: (int) config('jwt.access_ttl_minutes'),
            ),
        );

        $this->app->bind(
            TokenPairFactory::class,
            fn ($app) => new TokenPairFactory(
                accessTokens: $app->make(AccessTokenIssuer::class),
                refreshTokens: $app->make(RefreshTokenRepository::class),
                refreshTtlDays: (int) config('jwt.refresh_ttl_days'),
            ),
        );

        $this->app->bind(IssueTokensUseCase::class, IssueTokensService::class);
        $this->app->bind(RefreshTokensUseCase::class, RefreshTokensService::class);
        $this->app->bind(RevokeRefreshTokenUseCase::class, RevokeRefreshTokenService::class);
    }
}
