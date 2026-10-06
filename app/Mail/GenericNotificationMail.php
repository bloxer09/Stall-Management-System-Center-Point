<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Symfony\Component\Mime\Email;

class GenericNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $subjectText;
    public string $htmlContent;
    public string $textContent;

    /**
     * Create a new message instance.
     */
    public function __construct(string $subjectText, string $htmlContent, string $textContent = '')
    {
        $this->subjectText = $subjectText;
        $this->htmlContent = $htmlContent;
        $this->textContent = $textContent ?: trim(strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>'], "\n", $htmlContent)));

        // Configure plain-text alternative directly on the underlying Symfony Message
        $this->withSymfonyMessage(function (Email $message) {
            if (!empty($this->textContent)) {
                $message->text($this->textContent);
            }
        });
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $fromEmail = config('mail.from.address') ?: 'no-reply@stallm.com';
        $fromName = config('mail.from.name') ?: 'Center Point Stall Management';

        return new Envelope(
            from: new Address($fromEmail, $fromName),
            subject: $this->subjectText,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            htmlString: $this->htmlContent,
        );
    }
}
