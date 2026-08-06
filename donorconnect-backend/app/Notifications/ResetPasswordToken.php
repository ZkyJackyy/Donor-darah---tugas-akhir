<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordToken extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected string $token)
    {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Reset Password - Sahabat Donor')
            ->greeting('Halo, ' . $notifiable->name)
            ->line('Kami menerima permintaan untuk mengatur ulang password akun Anda.')
            ->line('Gunakan token berikut pada aplikasi untuk mengatur ulang password Anda:')
            ->line(new \Illuminate\Support\HtmlString('<p style="text-align:center;font-size:14px;letter-spacing:1px;word-break:break-all;"><strong>' . $this->token . '</strong></p>'))
            ->line('Token ini berlaku selama 15 menit.')
            ->line('Jika Anda tidak meminta reset password, abaikan email ini — password Anda tidak akan berubah.');
    }
}
