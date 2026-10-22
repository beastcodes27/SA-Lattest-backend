<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmployeeIdChanged extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $employee,
        public string $oldEmployeeId,
        public string $newEmployeeId,
        public string $organizationName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your employee ID has been updated',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.employee-id-changed',
            with: [
                'employee' => $this->employee,
                'oldEmployeeId' => $this->oldEmployeeId,
                'newEmployeeId' => $this->newEmployeeId,
                'organizationName' => $this->organizationName,
            ],
        );
    }
}
