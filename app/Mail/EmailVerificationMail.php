<?php

// app/Mail/EmailVerificationMail.php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EmailVerificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $verificationUrl;

    public function __construct($verificationUrl)
    {
        $this->verificationUrl = $verificationUrl;
    }

    public function build()
    {
        return $this->subject('تحقق من بريدك الإلكتروني - أكاديمية الوردة')
                   ->view('emails.verify_email')
                   ->with([
                       'verificationUrl' => $this->verificationUrl
                   ]);
    }
}

// app/Mail/PasswordResetMail.php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    public $resetUrl;
    public $user;

    public function __construct($resetUrl, $user = null)
    {
        $this->resetUrl = $resetUrl;
        $this->user = $user;
    }

    public function build()
    {
        return $this->subject('إعادة تعيين كلمة المرور - أكاديمية الوردة')
                   ->view('emails.password-reset')
                   ->with([
                       'resetUrl' => $this->resetUrl,
                       'user' => $this->user
                   ]);
    }
}
