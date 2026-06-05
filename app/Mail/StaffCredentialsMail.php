<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class StaffCredentialsMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $tenNv,
        public string $tenTk,
        public string $matKhau,
        public ?string $activationUrl = null,   // link kích hoạt (nếu cần xác thực email)
        public int $activationHours = 72,
    ) {}

    public function build()
    {
        return $this->subject('Tài khoản 8AM Coffee của bạn')
            ->view('emails.staff-credentials');
    }
}
