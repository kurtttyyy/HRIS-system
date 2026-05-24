<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ApplicationInterviewMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $interview;

    /**
     * Create a new message instance.
     */
    public function __construct($interview)
    {
        $this->interview = $interview;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $date = trim((string) ($this->interview->date ?? ''));
        $subject = 'Application Interview Schedule';
        if ($date !== '') {
            $subject .= ': '.$date;
        }

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'email.application-schedule',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
