<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Email chứa mã OTP xác thực 2 lớp khi đăng nhập.
 */
class OtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $tenNv,
        public string $otp,
        public int $phut,       // số phút còn hiệu lực
    ) {}

    public function build()
    {
        return $this->subject('Mã xác thực đăng nhập 8AM Coffee: ' . $this->otp)
            ->view('emails.otp');
    }
}
