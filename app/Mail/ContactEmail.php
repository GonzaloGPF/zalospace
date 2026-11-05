<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactEmail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public string $name,
        public string $email,
        public string $message,
    ) {
        //
    }

    /**
     * Get the message envelope, so that replying goes straight to the visitor.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            replyTo: [new Address($this->email, $this->name)],
            subject: tl('contact_email'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.contact_email',
        );
    }
}
