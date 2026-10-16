<?php

declare(strict_types=1);

namespace App\Presentation\Api\V1\Middleware;

use App\Application\Identity\Contracts\AccessTokenIssuer;
use App\Application\Identity\Contracts\UserRepository;
use App\Application\Identity\Exceptions\InvalidAccessToken;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authenticates /api/v1 requests with a Bearer access token.
 * Sets the request attribute `auth_user_id` for downstream controllers.
 */
final readonly class AuthenticateWithJwt
{
    public function __construct(
        private AccessTokenIssuer $accessTokens,
        private UserRepository $users,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if ($token === null || $token === '') {
            return $this->unauthorized();
        }

        try {
            $userId = $this->accessTokens->verify($token);
        } catch (InvalidAccessToken) {
            return $this->unauthorized();
        }

        // Re-check the account on every request so lock/suspend takes effect
        // without waiting for the access token to expire.
        $user = $this->users->findById($userId);

        if ($user === null || ! $user->isActive() || $user->emailVerifiedAt() === null) {
            return $this->unauthorized();
        }

        $request->attributes->set('auth_user_id', $user->id());

        return $next($request);
    }

    private function unauthorized(): JsonResponse
    {
        return new JsonResponse(['message' => 'Unauthenticated.'], 401, [
            'WWW-Authenticate' => 'Bearer',
        ]);
    }
}
