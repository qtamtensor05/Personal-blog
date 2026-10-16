<?php

declare(strict_types=1);

namespace App\Infrastructure\Mail;

use App\Application\Identity\Contracts\PasswordResetEmailSender;
use Illuminate\Support\Facades\Mail;

final class LaravelPasswordResetEmailSender implements PasswordResetEmailSender
{
    public function send(string $email, string $token): void
    {
        $url = route('password.reset', ['token' => $token, 'email' => $email]);

        Mail::to($email)->send(new PasswordResetMail($url));
    }
}
