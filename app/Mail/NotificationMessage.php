<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NotificationMessage extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $title,
        public string $body,
        public ?string $recipientName = null,
        public ?string $category = null,
        public ?string $actionUrl = null,
        public ?string $actionLabel = null,
        public ?string $occurredAt = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.notification',
            with: [
                'title' => $this->title,
                'body' => $this->body,
                'recipientName' => $this->recipientName,
                'category' => $this->category,
                'actionUrl' => $this->actionUrl,
                'actionLabel' => $this->actionLabel,
                'occurredAt' => $this->occurredAt,
            ],
        );
    }
}
