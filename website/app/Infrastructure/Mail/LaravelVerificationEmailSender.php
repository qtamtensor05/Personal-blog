<?php

declare(strict_types=1);

namespace App\Infrastructure\Mail;

use App\Application\Identity\Contracts\VerificationEmailSender;
use App\Mail\Identity\EmailVerificationMail;
use Illuminate\Support\Facades\Mail;

final class LaravelVerificationEmailSender implements VerificationEmailSender
{
    public function send(string $email, string $verificationUrl): void
    {
        Mail::to($email)->send(
            new EmailVerificationMail($verificationUrl),
        );
    }
}
