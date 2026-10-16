<?php

declare(strict_types=1);

namespace App\Presentation\Api\V1\Controllers;

use App\Application\Identity\DTOs\AuthTokens;
use App\Application\Identity\Exceptions\AccountNotActive;
use App\Application\Identity\Exceptions\InvalidCredentials;
use App\Application\Identity\Exceptions\InvalidRefreshToken;
use App\Application\Identity\UseCases\IssueTokens\IssueTokensUseCase;
use App\Application\Identity\UseCases\RefreshTokens\RefreshTokensUseCase;
use App\Application\Identity\UseCases\RevokeRefreshToken\RevokeRefreshTokenUseCase;
use App\Presentation\Api\V1\Requests\IssueTokenRequest;
use App\Presentation\Api\V1\Requests\RefreshTokenRequest;
use DateTimeInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class AuthTokenController extends Controller
{
    public function store(IssueTokenRequest $request, IssueTokensUseCase $issueTokens): JsonResponse
    {
        try {
            $tokens = $issueTokens->execute(
                $request->validated('email'),
                $request->validated('password'),
                $request->ip(),
                $request->userAgent(),
            );
        } catch (InvalidCredentials|AccountNotActive $exception) {
            return new JsonResponse(['message' => $exception->getMessage()], 401);
        }

        return $this->tokenResponse($tokens, 201);
    }

    public function refresh(RefreshTokenRequest $request, RefreshTokensUseCase $refreshTokens): JsonResponse
    {
        try {
            $tokens = $refreshTokens->execute(
                $request->validated('refresh_token'),
                $request->ip(),
                $request->userAgent(),
            );
        } catch (InvalidRefreshToken $exception) {
            return new JsonResponse(['message' => $exception->getMessage()], 401);
        }

        return $this->tokenResponse($tokens, 200);
    }

    public function destroy(RefreshTokenRequest $request, RevokeRefreshTokenUseCase $revoke): JsonResponse
    {
        $revoke->execute(
            (int) $request->attributes->get('auth_user_id'),
            $request->validated('refresh_token'),
        );

        return new JsonResponse(null, 204);
    }

    public function me(Request $request): JsonResponse
    {
        return new JsonResponse(['data' => ['id' => (int) $request->attributes->get('auth_user_id')]]);
    }

    private function tokenResponse(AuthTokens $tokens, int $status): JsonResponse
    {
        return new JsonResponse([
            'token_type' => 'Bearer',
            'access_token' => $tokens->accessToken,
            'access_token_expires_at' => $tokens->accessTokenExpiresAt->format(DateTimeInterface::ATOM),
            'refresh_token' => $tokens->refreshToken,
            'refresh_token_expires_at' => $tokens->refreshTokenExpiresAt->format(DateTimeInterface::ATOM),
        ], $status, ['Cache-Control' => 'no-store']);
    }
}
