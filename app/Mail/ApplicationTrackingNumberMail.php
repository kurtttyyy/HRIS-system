<?php

namespace App\Mail;

use App\Models\Applicant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ApplicationTrackingNumberMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Applicant $applicant)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Application Tracking Number',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'email.application-tracking-number',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
