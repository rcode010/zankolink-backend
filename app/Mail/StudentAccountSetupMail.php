<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StudentAccountSetupMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $username,
        public string $setupUrl,
        public string $expiresAt,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Complete Your ZankoLink Student Account Setup',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.students.account-setup',
            with: [
                'studentName' => $this->username,
                'setupUrl' => $this->setupUrl,
                'expiresAt' => $this->expiresAt,
            ],
        );
    }
}
