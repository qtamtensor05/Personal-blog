<?php

declare(strict_types=1);

namespace App\Infrastructure\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class PasswordResetMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly string $resetUrl) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Reset your password');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.identity.reset-password');
    }
}
