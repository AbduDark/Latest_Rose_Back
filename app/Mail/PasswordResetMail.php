<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    public $resetUrl;
    public $user;

    public function __construct($resetUrl, $user)
    {
        $this->resetUrl = $resetUrl;
        $this->user = $user;
    }

    public function build()
    {
        return $this->view('emails.password-reset')
                    ->subject('إعادة تعيين كلمة المرور')
                    ->with([
                        'resetUrl' => $this->resetUrl,
                        'user' => $this->user
                    ]);
    }
}
