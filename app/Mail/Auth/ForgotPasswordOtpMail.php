<?php

declare(strict_types=1);

namespace App\Mail\Auth;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ForgotPasswordOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $otp,
        public readonly string $recipientName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Mã khôi phục mật khẩu MESA',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'auth.mail.forgot-password-otp',
            with: [
                'otp' => $this->otp,
                'recipientName' => $this->recipientName,
            ],
        );
    }
}
