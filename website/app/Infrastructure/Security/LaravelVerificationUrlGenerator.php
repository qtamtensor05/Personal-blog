<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Application\Identity\Contracts\VerificationUrlGenerator;
use Illuminate\Support\Facades\URL;

final class LaravelVerificationUrlGenerator implements VerificationUrlGenerator
{
    public function generate(int $userId): string
    {
        return URL::temporarySignedRoute('identity.email.verify', now()->addMinutes(60), [
            'user' => $userId,
        ]);
    }
}
