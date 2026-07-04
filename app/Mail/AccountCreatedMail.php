<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccountCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $plainPassword
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your ZankoLink Account'
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.account-created-mail',
            with: [
                'name' => $this->user->name,
                'email' => $this->user->email,
                'temporaryPassword' => $this->plainPassword,
            ]
        );
    }
}
