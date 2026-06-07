<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PasswordResetLink extends Mailable
{
    use Queueable, SerializesModels;

    public readonly string $resetUrl;

    public function __construct(
        public readonly User $user,
        string $token,
    ) {
        $this->resetUrl = rtrim(config('services.worksyne.client_url'), '/').'/reset-password?'.http_build_query([
            'token' => $token,
            'email' => $user->email,
        ]);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Reset your Worksyne password',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.password-reset',
        );
    }
}
