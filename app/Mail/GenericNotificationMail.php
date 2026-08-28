<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

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
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $fromEmail = config('mail.from.address') ?? env('MAIL_FROM_ADDRESS', 'no-reply@stallm.com');
        $fromName = config('mail.from.name') ?? env('MAIL_FROM_NAME', 'STALLM System');

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
            textString: $this->textContent,
        );
    }
}
