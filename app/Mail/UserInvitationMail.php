<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;

    public string $magicLink;

    public function __construct(User $user, string $magicLink)
    {
        $this->user = $user;
        $this->magicLink = $magicLink;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Invitation to Checking TA Workspace',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.user-invitation',
        );
    }
}
