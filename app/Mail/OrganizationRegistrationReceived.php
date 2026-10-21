<?php

namespace App\Mail;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrganizationRegistrationReceived extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Organization $organization,
        public User $admin,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'We received your '.$this->organization->name.' registration',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.organization-registration-received',
            with: [
                'organization' => $this->organization,
                'admin' => $this->admin,
            ],
        );
    }
}
