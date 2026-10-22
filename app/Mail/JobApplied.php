<?php

namespace App\Mail;

use App\Models\Applicant;
use App\Models\Job;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class JobApplied extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Applicant $application,
        public readonly Job $job,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Job Application',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.job-applied',
        );
    }
}
