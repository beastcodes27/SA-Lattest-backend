<?php

namespace App\Mail;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewOrganizationRegistration extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Organization $organization,
        public User $admin,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New registration: '.$this->organization->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.new-organization-registration',
            with: [
                'organization' => $this->organization,
                'admin' => $this->admin,
            ],
        );
    }
}
