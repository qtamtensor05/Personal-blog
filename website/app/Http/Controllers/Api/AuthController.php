<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Identity\Actions\AuthenticateUserAction;
use App\Modules\Identity\Contracts\TokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AuthController extends Controller
{
    public function __construct(
        private readonly TokenService $tokens,
    ) {}

    public function login(
        Request $request,
        AuthenticateUserAction $action,
    ): JsonResponse {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:254'],
            'password' => ['required', 'string'],
        ]);

        $result = $action->execute(
            $data['email'],
            $data['password'],
        );

        $user = $result['user'];

        return response()->json([
            'access_token' => $result['access_token'],
            'token_type' => 'Bearer',
            'expires_in' => (int) config('jwt.ttl') * 60,
            'user' => [
                'id' => $user->getKey(),
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user('api');

        return response()->json([
            'user' => [
                'id' => $user->getKey(),
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }

    public function logout(): JsonResponse
    {
        $this->tokens->invalidateCurrentToken();

        return response()->json([
            'message' => 'Đăng xuất thành công.',
        ]);
    }

    public function refresh(): JsonResponse
    {
        $token = $this->tokens->refreshCurrentToken();

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => (int) config('jwt.ttl') * 60,
        ]);
    }
}
