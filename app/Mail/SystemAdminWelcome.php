<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SystemAdminWelcome extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $admin,
        public string $temporaryPassword,
        public string $createdByName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your system administrator account',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.system-admin-welcome',
            with: [
                'admin' => $this->admin,
                'temporaryPassword' => $this->temporaryPassword,
                'createdByName' => $this->createdByName,
            ],
        );
    }
}
