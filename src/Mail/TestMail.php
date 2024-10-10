<?php

namespace Go2Flow\Ezport\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Spatie\MailcoachMailer\Concerns\UsesMailcoachMail;

class TestMail extends Mailable
{
    use Queueable, SerializesModels, UsesMailcoachMail;

    /**
     * Create a new message instance.
     */
    public function __construct(private string $message)
    {
        //
    }

    public function build()
    {
        $this
            ->mailcoachMail('swi1-error', [
                'content' => $this->message,
            ]);

    }
}
