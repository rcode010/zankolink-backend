<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TwoFactorCodeMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public int $otp,
        public User $user
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your ZankoLink Verification Code');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.two-factor-code');
    }
}
