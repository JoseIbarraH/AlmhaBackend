<?php

namespace App\Domains\Blog\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Domains\Blog\Models\Blog;
use App\Domains\Client\Models\Subscriber;

class NewsletterMail extends Mailable
{
    use Queueable, SerializesModels;

    public $blog;
    public $subscriber;

    /**
     * Create a new message instance.
     */
    public function __construct(Blog $blog, Subscriber $subscriber)
    {
        $this->blog = $blog;
        $this->subscriber = $subscriber;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->blog->translation->title ?? 'New Blog Post from Almha',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'blog::mail.newsletter',
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
