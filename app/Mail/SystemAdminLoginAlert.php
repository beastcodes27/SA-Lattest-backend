<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SystemAdminLoginAlert extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $ipAddress,
        public ?string $userAgent,
        public string $loggedInAt,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'System admin console sign-in alert',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.system-admin-login-alert',
            with: [
                'user' => $this->user,
                'ipAddress' => $this->ipAddress,
                'userAgent' => $this->userAgent,
                'loggedInAt' => $this->loggedInAt,
            ],
        );
    }
}
