<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserVerificationEmail extends Mailable
{
    use Queueable, SerializesModels;


    public $verificationUrl;
    public $user;

    /**
     * Create a new message instance.
     */
    public function __construct($verificationUrl, $user)
    {
        $this->verificationUrl = $verificationUrl;
        $this->user = $user;
    }

    public function build()
    {
        return $this->subject('Verify Your Email Address')
            ->view('emails.verify')
            ->with([
                'url' => $this->verificationUrl,
                'user' => $this->user,
            ]);
    }
}
