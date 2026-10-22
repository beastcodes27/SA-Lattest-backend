<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AttendanceReportEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $reportTitle,
        public string $reportPeriod,
        public string $organizationName,
        public string $preparedBy,
        public string $pdfContents,
        public string $filename,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->reportTitle.' — '.$this->reportPeriod,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.attendance-report',
            with: [
                'reportTitle' => $this->reportTitle,
                'reportPeriod' => $this->reportPeriod,
                'organizationName' => $this->organizationName,
                'preparedBy' => $this->preparedBy,
                'filename' => $this->filename,
            ],
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => $this->pdfContents, $this->filename)
                ->withMime('application/pdf'),
        ];
    }
}
