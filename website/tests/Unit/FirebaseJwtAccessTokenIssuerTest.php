<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Application\Identity\Exceptions\InvalidAccessToken;
use App\Infrastructure\Security\FirebaseJwtAccessTokenIssuer;
use Firebase\JWT\JWT;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class FirebaseJwtAccessTokenIssuerTest extends TestCase
{
    private const SECRET = 'test-secret-that-is-at-least-32-bytes-long';

    public function test_it_issues_a_token_that_verifies_to_the_user_id(): void
    {
        $issuer = new FirebaseJwtAccessTokenIssuer(self::SECRET, 'https://blog.test', 15);

        $issued = $issuer->issue(42);

        self::assertSame(42, $issuer->verify($issued->token));
        self::assertGreaterThan(time(), $issued->expiresAt->getTimestamp());
    }

    public function test_it_rejects_a_token_signed_with_another_secret(): void
    {
        $issuer = new FirebaseJwtAccessTokenIssuer(self::SECRET, 'https://blog.test', 15);
        $other = new FirebaseJwtAccessTokenIssuer(str_repeat('x', 40), 'https://blog.test', 15);

        $this->expectException(InvalidAccessToken::class);

        $issuer->verify($other->issue(42)->token);
    }

    public function test_it_rejects_an_expired_token(): void
    {
        $issuer = new FirebaseJwtAccessTokenIssuer(self::SECRET, 'https://blog.test', 15);
        $token = JWT::encode(
            [
                'iss' => 'https://blog.test',
                'sub' => '42',
                'typ' => 'access',
                'iat' => time() - 3600,
                'exp' => time() - 60,
            ],
            self::SECRET,
            'HS256',
        );

        $this->expectException(InvalidAccessToken::class);

        $issuer->verify($token);
    }

    public function test_it_rejects_a_token_from_another_issuer(): void
    {
        $issuer = new FirebaseJwtAccessTokenIssuer(self::SECRET, 'https://blog.test', 15);
        $foreign = new FirebaseJwtAccessTokenIssuer(self::SECRET, 'https://evil.test', 15);

        $this->expectException(InvalidAccessToken::class);

        $issuer->verify($foreign->issue(42)->token);
    }

    public function test_it_requires_a_strong_secret(): void
    {
        $this->expectException(RuntimeException::class);

        new FirebaseJwtAccessTokenIssuer('short', 'https://blog.test', 15);
    }
}
