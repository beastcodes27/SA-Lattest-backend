<?php

namespace App\Mail;

use App\Models\Organization;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrganizationRejected extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Organization $organization,
        public ?string $reason = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Update on your '.$this->organization->name.' registration',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.organization-rejected',
            with: [
                'organization' => $this->organization,
                'reason' => $this->reason,
            ],
        );
    }
}
