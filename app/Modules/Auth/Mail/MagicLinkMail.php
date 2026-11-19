<?php

namespace App\Modules\Auth\Mail;

use App\Modules\Workspace\Models\Workspace;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class MagicLinkMail extends Mailable
{
    use Queueable;

    public function __construct(public string $url, public int $minutes) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('Your sign-in link for :name', ['name' => Workspace::current()->name]));
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.magic-link', with: [
            'workspace' => Workspace::current()->name,
        ]);
    }
}
