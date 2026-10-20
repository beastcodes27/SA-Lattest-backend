<?php

namespace App\Mail;

use App\Models\Organization;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrganizationApproved extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Organization $organization,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->organization->name.' has been approved',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.organization-approved',
            with: [
                'organization' => $this->organization,
            ],
        );
    }
}
