<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewPasswordNotification extends Notification
{
    use Queueable;

    public $password;

    public function __construct($password)
    {
        $this->password = $password;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('كلمة المرور الجديدة لحسابك')
            ->line('تم إعادة تعيين كلمة المرور لحسابك بناءً على طلبك.')
            ->line('كلمة المرور الجديدة الخاصة بك هي: **' . $this->password . '**')
            ->line('نوصي بتغيير كلمة المرور هذه فور تسجيل الدخول إلى حسابك لأسباب أمنية.')
            ->action('تسجيل الدخول', url('/login'))
            ->line('إذا لم تطلب إعادة تعيين كلمة المرور، يرجى الاتصال بدعمنا فوراً.')
            ->line('فريق الدعم NBMstore.');
    }
}
