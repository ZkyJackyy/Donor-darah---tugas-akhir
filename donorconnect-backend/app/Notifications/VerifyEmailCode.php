<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VerifyEmailCode extends Notification
{
    use Queueable;

    public function __construct(protected string $code)
    {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Kode Verifikasi Email - Sahabat Donor')
            ->greeting('Halo, ' . $notifiable->name)
            ->line('Gunakan kode berikut untuk memverifikasi email Anda:')
            ->line(new \Illuminate\Support\HtmlString('<h1 style="text-align:center;letter-spacing:4px;">' . $this->code . '</h1>'))
            ->line('Kode ini berlaku selama 15 menit.')
            ->line('Jika Anda tidak merasa mendaftar di Sahabat Donor, abaikan email ini.');
    }
}
